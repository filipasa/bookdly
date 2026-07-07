<?php

namespace BookneticAddon\Googlecalendar\Backend;

use BookneticApp\Config;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Capabilities;
use BookneticAddon\Googlecalendar\Integration\GoogleCalendarService;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Session;
use function BookneticAddon\Googlecalendar\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function settings_view()
	{
	    $shortcode_list = Config::getShortCodeService()->getShortCodesList();
        $necessaryStatus = Helper::getOption('google_calendar_necessary_status');
        $necessaryStatus = explode(',',$necessaryStatus);
		return $this->modalView( 'google_calendar_settings', [
		    'all_shortcode' => $shortcode_list,
            'google_calendar_appointment_status'=>$necessaryStatus
        ] );
	}

    public function settings_save ()
    {
        $appointmentStatuses = Helper::getAppointmentStatuses();
        $google_calendar_enable				= Helper::_post('google_calendar_enable', 'off', 'string', ['on', 'off']);
        $google_calendar_client_id			= Helper::_post('google_calendar_client_id', '', 'string');
        $google_calendar_client_secret		= Helper::_post('google_calendar_client_secret', '', 'string');

        $google_calendar_event_title		= Helper::_post('google_calendar_event_title', '', 'string');
        $google_calendar_event_description	= Helper::_post('google_calendar_event_description', '', 'string');
        $google_calendar_2way_sync			= Helper::_post('google_calendar_2way_sync', 'off', 'string', ['on', 'off', 'on_background']);
        $google_calendar_sync_interval	    = Helper::_post('google_calendar_sync_interval', '', 'string', ['1', '2', '3']);
        $google_calendar_add_attendees		= Helper::_post('google_calendar_add_attendees', 'off', 'string', ['on', 'off']);
        $google_calendar_send_notification	= Helper::_post('google_calendar_send_notification', 'off', 'string', ['on', 'off']);
        $google_calendar_can_see_attendees	= Helper::_post('google_calendar_can_see_attendees', 'off', 'string', ['on', 'off']);
        $google_calendar_appointment_status	= Helper::_post('google_calendar_appointment_status', [], 'array');

        if( $google_calendar_add_attendees == 'off' )
        {
            $google_calendar_send_notification = 'off';
        }
        $google_calendar_appointment_status = array_filter($google_calendar_appointment_status , function ($item) use($appointmentStatuses)
        {
            return array_key_exists($item,$appointmentStatuses);
        });
        $google_calendar_appointment_status = join(',',$google_calendar_appointment_status);

        Helper::setOption('google_calendar_enable', $google_calendar_enable);
        Helper::setOption('google_calendar_client_id', $google_calendar_client_id);
        Helper::setOption('google_calendar_client_secret', $google_calendar_client_secret);
        Helper::setOption('google_calendar_event_title', $google_calendar_event_title);
        Helper::setOption('google_calendar_event_description', $google_calendar_event_description);
        Helper::setOption('google_calendar_2way_sync', $google_calendar_2way_sync);
        Helper::setOption('google_calendar_sync_interval', $google_calendar_sync_interval);
        Helper::setOption('google_calendar_add_attendees', $google_calendar_add_attendees);
        Helper::setOption('google_calendar_send_notification', $google_calendar_send_notification);
        Helper::setOption('google_calendar_can_see_attendees', $google_calendar_can_see_attendees);
        Helper::setOption('google_calendar_necessary_status', $google_calendar_appointment_status);

        return $this->response(true);
    }

    public function login_google_account ()
    {
        $staffId = Helper::_post('staff_id', '', 'int');

        if( !( $staffId > 0 ) )
        {
            return $this->response( false );
        }

        Session::set( 'google_staff_id', $staffId );

        $googleService = new GoogleCalendarService();
        $url = $googleService->createAuthURL( false );

        return $this->response( true, [
            'redirect'	=>	$url
        ] );
    }

    public function logout_google_account ()
    {
        $staffId = Helper::_post('staff_id', '', 'int');

        if( !( $staffId > 0 ) )
        {
            return $this->response(false);
        }

        $staffInf = Staff::get( $staffId );
        $accessToken = $staffInf->getData( 'google_access_token' );

        if( empty( $accessToken ) )
        {
            return $this->response(false);
        }

        if( Data::where('data_key','google_calendar_id')->where('data_value',$staffInf->getData('google_calendar_id'))->count() == 1 )
        {
            $googleService = new GoogleCalendarService();
            $googleService->setAccessToken( $staffInf )->revokeToken();
        }

        Staff::deleteData( $staffId, 'google_access_token' );
	    Staff::deleteData( $staffId, 'google_calendar_id' );

        return $this->response( true );
    }

    public function fetch_google_calendars ()
    {
        $staffId	= Helper::_post('staff_id', '', 'int');
        $search		= Helper::_post('q', '', 'str');

        if( !( $staffId > 0 ) )
        {
            return $this->response(false);
        }

        $staffInf = Staff::get( $staffId );

        $accessToken = $staffInf->getData( 'google_access_token' );

        if( empty( $accessToken ) )
        {
            return $this->response(false, bkntc__('Firstly click the login button!'));
        }

        $googleService = new GoogleCalendarService();
        $googleService->setAccessToken( $staffInf );

        $calendars = $googleService->getCalendarsList();
        if( is_string( $calendars ) )
        {
            return $this->response(false, $calendars);
        }

        $data = [];

        foreach ( $calendars AS $calendar )
        {
            if( !empty( $search ) && strpos( $calendar['id'], $search ) === false )
                continue;

            $data[] = [
                'id'				=>	htmlspecialchars($calendar['id']),
                'text'				=>	htmlspecialchars($calendar['summary'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function save_calendar_module_settings ()
    {
        $showGCEvents = Helper::_post('show_gc_events', 'off', 'string', ['on', 'off']);

        if( $showGCEvents === "on" )
        {
            Session::set( 'show_gc_events', 'on' );
        }
        else
        {
            Session::set( 'show_gc_events', 'off' );
        }

        return $this->response( true );
    }

}
