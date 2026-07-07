<?php

namespace BookneticAddon\Googlecalendar;

use BookneticAddon\Googlecalendar\Helpers\CalendarHelper;
use BookneticApp\Models\Data;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Service;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Session;
use BookneticAddon\Googlecalendar\Model\StaffBusySlot;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticAddon\Googlecalendar\Integration\GoogleCalendarService;

class Listener
{

    public static function add_calendar_row_to_staff_view($parameters)
    {
        if ( is_array( $parameters ) )
        {
            $staff = isset( $parameters['staff'] ) ? $parameters['staff'] : null;
        }
        else if ( is_object( $parameters ) )
        {
            if ( method_exists( $parameters, 'getStaff' ) )
            {
                $staff = $parameters->getStaff();
            }
            else if ( isset( $parameters->staff ) )
            {
                $staff = $parameters->staff;
            }
            else
            {
                $staff = $parameters;
            }
        }
        else
        {
            $staff = null;
        }

        if ( is_array( $staff ) )
        {
            $cid = isset( $staff['id'] ) ? $staff['id'] : 0;
        }
        else if ( is_object( $staff ) )
        {
            if ( method_exists( $staff, 'getId' ) )
            {
                $cid = $staff->getId();
            }
            else if ( isset( $staff->id ) )
            {
                $cid = $staff->id;
            }
            else
            {
                $cid = 0;
            }
        }
        else
        {
            $cid = 0;
        }

        $staffInfo = $cid ? Staff::get( $cid ) : null;
        return ['staff'=>$staffInfo];
    }

    public static function save_staff_google_calendar ( $arr )
    {
		if ( ! empty( $arr[ 'staff_id' ] ) && $arr[ 'is_edit' ] === true )
		{
			$google_calendar_id		= Helper::_post('google_calendar_id', '', 'string');
			$getOldInf              = Staff::get( $arr[ 'staff_id' ] );

			if ( ! empty( $getOldInf->getData( 'google_access_token' ) ) && ! empty( $google_calendar_id ) )
			{
				Staff::setData( $arr[ 'staff_id' ], 'google_calendar_id', $google_calendar_id );
			}
		}

        return $arr;
    }

    public static function cronjob_google_calendar()
    {
        GoogleCalendarService::syncEventsOnBackground();
    }

    public static function merge_busy_slots_google_calendar( $busyRanges, CalendarService $calendarService )
    {
        $t0 = (new \DateTime($calendarService->dateFrom, $calendarService->clientTz))->setTimezone($calendarService->serverTz)->modify("-{$calendarService->serviceMarginBefore} minutes")->format("Y-m-d");
        $t1 = (new \DateTime($calendarService->dateTo, $calendarService->clientTz))->setTimezone($calendarService->serverTz)->modify("+{$calendarService->serviceMarginAfter} minutes")->format("Y-m-t");
        $staffGoogleCalendar = self::staffCalendar( $calendarService->getStaffInf(), $t0, $t1, $calendarService->getExcludeAppointmentId() );

        foreach ( $staffGoogleCalendar as $event )
        {
            $busyRanges[] = [ Date::epoch($event['date'] . " {$event['start_time']}") , Date::epoch($event['date'] . " {$event['start_time']}") + $event['duration'] * 60 ];
        }

        return $busyRanges;
    }

    public static function staffCalendar( $staff, $start_date, $end_date, $exclude_appointment_id )
    {
        if( is_numeric( $staff ) )
        {
            $staff = Staff::get( $staff );
        }

        $google_calendar_2way_sync = Helper::getOption('google_calendar_2way_sync', 'off', false);

        if( $google_calendar_2way_sync == 'off' || empty( $staff->getData( 'google_access_token' ) ) || empty( $staff->getData( 'google_calendar_id' ) ) )
        {
            return [];
        }

        if( $google_calendar_2way_sync == 'on_background' )
        {
            $fetchBusySlotsFromDB = StaffBusySlot::where('staff_id', $staff['id'])->where('date', '>=', $start_date)->where('date', '<=', $end_date)->fetchAll();
            $all_events = [];

            foreach ( $fetchBusySlotsFromDB AS $busySlotInf )
            {
                $all_events[] = [
                    'date'					=>	$busySlotInf->date,
                    'start_time'			=>	$busySlotInf->start_time,
                    'duration'				=>	$busySlotInf->duration,
                    'extras_duration'		=>	0,
                    'buffer_before'			=>	0,
                    'buffer_after'			=>	0,
                    'service_id'			=>	0,
                    'staff_id'				=>	$staff['id'],
                    'weight'                =>	1,
                    'id'					=>	0
                ];
            }

            return $all_events;
        }

        $access_token = $staff->getData( 'google_access_token' );
        $calendar_id = $staff->getData( 'google_calendar_id' );

        $googleCalendarSerivce = new GoogleCalendarService();
        $googleCalendarSerivce->setAccessToken( $staff );

        return $googleCalendarSerivce->getEvents( $start_date, $end_date, $calendar_id, $exclude_appointment_id, $staff['id'] );
    }

    public static function merge_google_calendar_events($events, $startTime, $endTime , $staffFilterSanitized=[])
    {
        if( Session::get('show_gc_events', 'off') === "on" )
        {
            if( ! ( Permission::isAdministrator() || Capabilities::userCan( 'calendar' ) ) )
            {
                Helper::response( false, bkntc__( 'You do not have sufficient permissions to perform this action' ) );
            }

            $staffList = Staff::select(DB::table('staff').'.id as id, '.DB::table('staff').'.name as name')
                ->leftJoin('data', ['row_id', 'data_key'],  DB::table('data').'.row_id', DB::table('staff').'.id')
                ->where(DB::table('data').'.data_key', 'google_calendar_id')
                ->where('IFNULL('.DB::table('staff').'.id,\'\')', '<>', '' );

            if( ! empty( $staffFilterSanitized ) )
            {
                $staffList->where( Staff::getField( 'id' ) , $staffFilterSanitized );
            }

            $staffList = $staffList->groupBy( 'data_value' )
                ->fetchAll();

            $allStaffsEvents = [];
            foreach ( $staffList as $staffInf )
            {
               $access_token = $staffInf->getData('google_access_token');
               $calendar_id = $staffInf->getData('google_calendar_id');

               $googleCalendarEvents = [];

               if( $access_token && $calendar_id )
               {
                   $googleCalendarService = new GoogleCalendarService();
                   $googleCalendarService->setAccessToken( $staffInf );
                   $googleCalendarEvents = $googleCalendarService->getEvents($startTime, $endTime, $calendar_id, 0, $staffInf->id, true);
               }

               $allStaffsEvents = array_merge( $allStaffsEvents, $googleCalendarEvents );
            }

            foreach ($allStaffsEvents as $val)
			{
                $staff = Staff::get($val['staff_id']);
                $events[] = [
                    'appointment_id'        => 0,
                    'title'                 => htmlspecialchars($val['title']),
                    'event_title'           => htmlspecialchars(\BookneticApp\Providers\Helpers\StringUtil::cutText($val['title'], 15)),
                    'color'                 => htmlspecialchars($val['color']),
                    'text_color'            => static::getContrastColor($val['color']),
                    'location_name'         => '',
                    'service_name'          => 'gc_event',
                    'staff_name'            => empty($staff) ? '' : htmlspecialchars($staff->name),
                    'staff_profile_image'   => empty($staff) ? '' : Helper::profileImage($staff->profile_image, 'Staff'),
                    'gc_icon'               => GoogleCalendarAddon::loadAsset('assets/icons/gc.png'),
                    'start_time'            => Date::time($val['start_time']),
                    'end_time'              => Date::time(Date::epoch($val['start_time']) + ($val['duration'] + $val['extras_duration']) * 60),
                    'start'                 => Date::dateSQL($val['date']) . 'T' . Date::format('H:i:s', $val['start_time']),
                    'end'                   => Date::format('Y-m-d\TH:i:s', Date::epoch($val['date'] . ' ' . $val['start_time']) + ($val['duration'] + $val['extras_duration']) * 60),
                    'customer'              => '',
                    'customers_count'       => 0,
                    'status'                => '',
                    'resourceId'            => empty( $staff ) ? '' : $staff->id,
                    'staff_id'              => empty( $staff ) ? '' : $staff->id
                ];
            }
        }

        return $events;
    }

    private static function getContrastColor( $hexcolor )
    {
        $r = hexdec(substr($hexcolor, 1, 2));
        $g = hexdec(substr($hexcolor, 3, 2));
        $b = hexdec(substr($hexcolor, 5, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 185) ? '#292D32' : '#FFF';
    }

    private static function update($appointmentInf, $eventId)
    {
        $gcService = new GoogleCalendarService();
        $gcService->setAccessToken($appointmentInf->staff_id);

        return $gcService->event()
            ->setCalendarId( Staff::getData( $appointmentInf->staff_id, 'google_calendar_id' ) )
            ->setAppointmentInf( $appointmentInf )
            ->update($eventId);
    }

    private static function delete($appointmentInf, $eventId)
    {
        $gcService = new GoogleCalendarService();
        $gcService->setAccessToken($appointmentInf->staff_id);

        return $gcService->event()
            ->setCalendarId( Staff::getData( $appointmentInf->staff_id, 'google_calendar_id' ) )
            ->setAppointmentInf( $appointmentInf )
            ->delete($eventId);
    }

    private static function create($appointmentInf)
    {
        $gcService = new GoogleCalendarService();
        $gcService->setAccessToken($appointmentInf->staff_id);

        return $gcService->event()
            ->setCalendarId( Staff::getData( $appointmentInf->staff_id, 'google_calendar_id' ) )
            ->setAppointmentInf( $appointmentInf )
            ->insert();
    }

    private static $oldAppointmentInf = null;

    public static function bkntc_appointment_before_mutation($appointmentId)
    {
        self::$oldAppointmentInf = is_null($appointmentId) ? null : Appointment::get($appointmentId);
    }

    public static function bkntc_appointment_after_mutation($appointmentId)
    {
        $old = self::$oldAppointmentInf;
        $new = is_null($appointmentId) ? null : Appointment::get($appointmentId);

        $oldZ = is_null($old) ? null : $old->getData('google_event_id');
        $oldGZ = self::getGroupEventId($old);
        $newGZ = self::getGroupEventId($new);

        // depends on status and staff
        $newStateNeedsEvent =
            !empty($new) &&
            !empty( Staff::getData($new->staff_id, 'google_access_token') ) &&
            !empty( Staff::getData($new->staff_id, 'google_calendar_id') ) &&
            in_array($new->status, CalendarHelper::getNecessaryStatus()) &&
            in_array( $new->status, Helper::getBusyAppointmentStatuses() );

        $oldZReused = false;

        if ($newStateNeedsEvent)
        {
            // reuse $oldZ or $newGZ
            if (!empty($newGZ))
            {
                // reuse $newGZ
                $new->setData('google_event_id', $newGZ);
                self::update($new, $newGZ);
                $oldZReused = $oldZ == $newGZ;
            }
            else if (!empty($oldZ) && empty($oldGZ) && $old->staff_id == $new->staff_id)
            {
                // reuse $oldZ
                self::update($new, $oldZ);
                $oldZReused = true;
            }
            else
            {
                // create new fresh Z
                $new->setData('google_event_id', self::create($new));
            }
        }

        if (!empty($oldZ) && !$oldZReused)
        {
            if (!empty($oldGZ))
            {
                self::update($old, $oldGZ);
            }
            else
            {
                self::delete($old, $oldZ);
            }
        }

        if (!$newStateNeedsEvent && !empty($oldZ))
            Appointment::deleteData($old->id, 'google_event_id');
    }

    private static function getGroupEventId( $appointmentInf )
    {
        if ( is_null($appointmentInf) )
            return null;

        $row = Appointment::where('service_id' , $appointmentInf->service_id )
            ->where('location_id' , $appointmentInf->location_id)
            ->where('staff_id' , $appointmentInf->staff_id)
            ->where( 'starts_at' , $appointmentInf->starts_at )
            ->where( function ($query){
                $query->where('payment_method' ,'local')->orWhere('payment_status','paid');
            } )
            ->where(Appointment::getField('id'), '<>', $appointmentInf->id)
            ->innerJoin( Data::getTableName() , ['data_value'] , [
                [Data::getField('row_id'), '=', Appointment::getField('id')],
                [Data::getField('table_name'), '=', "'" . Appointment::getTableName() . "'"],
                [Data::getField('data_key'), '=', "'google_event_id'"]
            ] )
            ->select(Data::getField('data_value'), true)
            ->limit(1)
            ->fetch();

        if ( empty($row) )
            return null;

        return $row->data_value;

    }

}