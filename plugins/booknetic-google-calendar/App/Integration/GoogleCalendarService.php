<?php

namespace BookneticAddon\Googlecalendar\Integration;

use BookneticApp\Models\Data;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\DB;
use BookneticAddon\Googlecalendar\Model\StaffBusySlot;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticVendor\Google\Client;
use BookneticVendor\Google\Service\Calendar;

class GoogleCalendarService
{

	const APPLICATION_NAME = 'Booknetic';
	const ACCESS_TYPE = 'offline';

	private $client_id;
	private $client_secret;
	private $access_token;
	private $client;
	private $service;

	public static function redirectURI()
	{
		return admin_url( 'admin.php?page=' . Backend::getSlugName() . '&module=staff&google=true' );
	}

	public static function syncEventsOnBackground()
	{
		if(
			!(
				Helper::getOption('google_calendar_enable', 'off', false) == 'on'
				&& Helper::getOption('google_calendar_2way_sync', 'off', false) == 'on_background'
			)
		)
		{
			return;
		}

		if( Helper::isSaaSVersion() )
		{
			$tenantIdBackup = Permission::tenantId();

            $getTenantList = Staff::noTenant()
                ->innerJoin('data', [],  Data::getField('row_id'), Staff::getField('id'))
                ->where(Data::getField('table_name'), 'staff')
                ->where(Staff::getField('is_active'), 1)
                ->where(Staff::getField('tenant_id'), 'is not', null)
                ->groupBy(Staff::getField('tenant_id'))
                ->select(Staff::getField('tenant_id'), true)
                ->fetchAll();

			foreach( $getTenantList AS $row )
			{
				Permission::setTenantId( $row->tenant_id );
				self::syncEvents();
			}

            Permission::setTenantId( $tenantIdBackup );
		}
		else
		{
			self::syncEvents();
		}
	}

	public static function syncEvents()
	{
		$sync_interval = Helper::getOption('google_calendar_sync_interval', '1', false);
		$sync_interval = in_array( $sync_interval, ['1', '2', '3'] ) ? (int)$sync_interval : 1;

		$start_date = Date::dateSQL();
		$end_date = Date::dateSQL('now', '+'.$sync_interval.' month');

//        more simplified version...
//        $staffList = Staff::select([ Staff::getField('id') ])->leftJoin( 'data', [], Data::getField( 'row_id' ) , Staff::getField( 'id' ) )->where( Data::getField('data_key'), 'google_calendar_id' )->where( "IFNULL(" . Staff::getField( "id" ) . ", '')", '<>', '' )->fetchAll();
        $staffList = Staff::select(DB::table('staff').'.id as staff_id')->leftJoin('data', ['row_id', 'data_key'],  DB::table('data').'.row_id', DB::table('staff').'.id')->where(DB::table('data').'.data_key', 'google_calendar_id')->where('IFNULL('.DB::table('staff').'.id,\'\')', '<>', '' )->fetchAll();

		foreach ( $staffList AS $staffInf )
		{

			$calendar_id    = Staff::getData( $staffInf->staff_id,'google_calendar_id' );

			$googleCalendarSerivce = new GoogleCalendarService();
			$googleCalendarSerivce->setAccessToken( Staff::get($staffInf->staff_id) );

			$staff_events = $googleCalendarSerivce->getEvents( $start_date, $end_date, $calendar_id, -1, $staffInf->staff_id, true );
			StaffBusySlot::where('staff_id', $staffInf->staff_id)->where('date', '>=', $start_date)->where('date', '<=', $end_date)->delete();

			foreach ( $staff_events AS $eventInf )
			{
				StaffBusySlot::insert([
					'staff_id'          =>  $staffInf->staff_id,
					'date'              =>  $eventInf['date'],
					'start_time'        =>  $eventInf['start_time'],
					'duration'          =>  $eventInf['duration'],
					'google_event_id'   =>  $eventInf['google_event_id']
				]);
			}
		}
	}

	public function __construct()
	{
		$this->client_id = Helper::getOption('google_calendar_client_id', '', false);
		$this->client_secret = Helper::getOption('google_calendar_client_secret', '', false);
	}

	public function setAccessToken( $staffId )
	{
        $staffId = is_object($staffId) ? $staffId->id : $staffId;
        $access_token = Staff::getData($staffId, 'google_access_token');
		$this->access_token = !is_array( $access_token ) ? json_decode( $access_token, true ) : $access_token;

		$this->getClient()->setAccessToken( $this->access_token );

        if ( $this->getClient()->isAccessTokenExpired() )
        {
            $refresh_token = $this->getClient()->getRefreshToken();
            $this->getClient()->fetchAccessTokenWithRefreshToken( $refresh_token );

            $this->access_token = $this->getClient()->getAccessToken();
            Staff::setData($staffId, 'google_access_token', json_encode($this->getClient()->getAccessToken()));
        }

		return $this;
	}

	public function createAuthURL( $redirect = true )
	{
		$authUrl = $this->getClient()->createAuthUrl();

		if( $redirect )
		{
			Helper::redirect( $authUrl );
		}

		return $authUrl;
	}

	public function fetchAccessToken()
	{
		$code = Helper::_get('code', '', 'string');

		if( empty( $code ) )
			return false;

		$this->getClient()->authenticate( $code );
		$access_token = $this->getClient()->getAccessToken();

		return json_encode($access_token);
	}

	public function revokeToken()
	{
		$this->getClient()->revokeToken();

		return $this;
	}

	public function getCalendarsList()
	{
		try
		{
			$calendarList = $this->getService()->calendarList->listCalendarList([
				'minAccessRole'	=>	'writer'
			]);
		}
		catch ( \Exception $e )
		{
			return $e->getMessage();
		}

		return $calendarList->getItems();
	}

	public function getEvents( $start_date, $end_date, $calendar_id = 'primary', $exclude_appointment_id = 0, $staff_id = 0, $return_with_event_id = false, $exclude_booknetic_appointments = false )
	{
		$all_events = [];
		$pageToken = null;

		while( true )
		{
			$optParams = [
				'maxResults'	=>	200,
				'orderBy'		=>	'startTime',
				'singleEvents'	=>	true,
				'timeMin'		=>	Date::format( 'c', Date::format( 'Y-m-d 00:00:00', $start_date ) ),
				'timeMax'		=>	Date::format( 'c', Date::format( 'Y-m-d 23:59:59', $end_date ) )
			];

			if( !is_null( $pageToken ) )
			{
				$optParams['pageToken'] = $pageToken;
			}

			try
			{
				$results	= $this->getService()->events->listEvents($calendar_id, $optParams);
				$pageToken	= $results->getNextPageToken();
				$events		= $results->getItems();
			}
			catch (\Exception $e)
			{
				$pageToken	= null;
				$events		= [];
			}

			/**
			 * @var $event \Google_Service_Calendar_Event
			 */
			foreach ( $events AS $event )
			{
				if ( $event->getTransparency() == 'transparent' )
				{
					continue;
				}

				$extended_properties = $event->getExtendedProperties();
                if (!is_null( $extended_properties ) && (isset($extended_properties->private['BookneticAppointmentId']) || isset($extended_properties->private['BookneticStaffId']) ))
                {
                    if (isset($extended_properties->private['BookneticStaffId']))
                    {
                        if ($extended_properties->private['BookneticStaffId'] == $staff_id) continue;
                    }
                    else continue;
                }


				$date_based	= empty( $event->start->dateTime );

				$event_start = $date_based ? $event->start->date : $event->start->dateTime;
				$event_end = $date_based ? $event->end->date : $event->end->dateTime;

				$event_start_date = Date::dateSQL( $event_start );

				$start_cursor = Date::epoch( $event_start );
				$end_cursor = Date::epoch( $event_end );

				while( $start_cursor < $end_cursor )
				{
					$e_date = Date::dateSQL( $start_cursor );
					$e_start_time = $e_date == $event_start_date ?  Date::timeSQL( $event_start ) : '00:00';

					$start_cursor = Date::epoch( $start_cursor, '+1 days' );

					if( $start_cursor < $end_cursor )
					{
						$duration = 24 * 60;
					}
					else
					{
						$duration = ( $end_cursor - Date::epoch( $e_date . ' ' . $e_start_time ) ) / 60;
					}

					$eventRow = [
						'date'					=>	$e_date,
						'start_time'			=>	$e_start_time,
						'duration'				=>	$duration,
						'extras_duration'		=>	0,
						'buffer_before'			=>	0,
						'buffer_after'			=>	0,
						'service_id'			=>	0,
						'staff_id'				=>	$staff_id,
						'weight'                =>	1,
						'id'					=>	0,
                        'color'                 => $this->getColorByColorId( $event->getColorId() ),
                        'title'                 => is_null( $event->getSummary() ) ? 'Google Calendar Event' : $event->getSummary()
					];

					if( $return_with_event_id )
					{
						$eventRow['google_event_id'] = $event->id;
					}

					$all_events[] = $eventRow;
				}
			}

			if( !$pageToken )
			{
				break;
			}
		}

		return $all_events;
	}

	/**
	 * @return GoogleCalendarEvent
	 */
	public function event()
	{
		return new GoogleCalendarEvent( $this );
	}

	/**
	 * @return Client
	 */
	public function getClient()
	{
		if( is_null( $this->client ) )
		{
			$this->client = new Client();

			$this->client->setApplicationName(static::APPLICATION_NAME);
			$this->client->setClientId($this->client_id);
			$this->client->setClientSecret($this->client_secret);
			$this->client->setRedirectUri(static::redirectURI());
			$this->client->setAccessType(static::ACCESS_TYPE);
			$this->client->setPrompt('consent');
			$this->client->addScope(Calendar::CALENDAR);
		}

		return $this->client;
	}

	public function getService()
	{
		if( is_null( $this->service ) )
		{
			$this->service = new Calendar( $this->getClient() );
		}

		return $this->service;
	}

    private function getColorByColorId( $colorId )
    {
        switch ( $colorId )
        {
            case 1:
                return "#7986cb";
            case 2:
                return "#33b679";
            case 3:
                return "#8e24aa";
            case 4:
                return "#e67c73";
            case 5:
                return "#f6c026";
            case 6:
                return "#f5511d";
            case 8:
                return "#616161";
            case 9:
                return "#3f51b5";
            case 10:
                return "#0b8043";
            case 11:
                return "#d60000";
            default:
                return "#039be5";
        }

    }

}
