<?php

namespace BookneticAddon\Zoom;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Models\Data;
use BookneticApp\Providers\DB\Collection;
use BookneticAddon\Zoom\Integration\ZoomService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;

class Listener
{

    private static $oldAppointmentInf = null;

    public static function appointment_before_mutation( $appointmentId )
    {
        self::$oldAppointmentInf = is_null($appointmentId) ? null : Appointment::get( $appointmentId );
    }

    public static function appointment_after_mutation($appointmentId)
    {
        $old = self::$oldAppointmentInf;
        $new = is_null($appointmentId) ? null : Appointment::get($appointmentId);

        $oldZ = is_null($old) ? null : $old->getData('zoom_meeting_data');
        $oldGZ = self::getGroupMeetingData($old);
        $newGZ = self::getGroupMeetingData($new);

        // depends on status and staff
        $newStateNeedsEvent =
            !empty($new) &&
            !empty( Staff::getData($new->staff_id, 'zoom_user') ) &&
            ( Service::getData($new->service_id, 'activate_zoom','0') =='1' ) &&
            in_array($new->status, Helper::getBusyAppointmentStatuses());

        $oldZReused = false;

        if ($newStateNeedsEvent)
        {
            // reuse $oldZ or $newGZ
            if (!empty($newGZ))
            {
                // reuse $newGZ
                $new->setData('zoom_meeting_data', $newGZ);
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
                $new->setData('zoom_meeting_data', self::create($new));
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
                self::delete($oldZ);
            }
        }

        if (!$newStateNeedsEvent && !empty($oldZ))
            Appointment::deleteData($old->id, 'zoom_meeting_data');
    }

    private static function update ( $appointmentObj , $zoomMeetingData )
    {
        $meetingId = json_decode($zoomMeetingData,true)['id'];
        $zoomService = new ZoomService();
        $zoomService->setAppointment( $appointmentObj )->updateMeeting( $meetingId );
    }

    private static function create( $appointmentObj )
    {
        $zoomService = new ZoomService();
        $meetingData = $zoomService->setAppointment($appointmentObj)->createMeeting();
        return json_encode($meetingData);
    }

    private static function delete ( $zoomMeetingData )
    {
        $meetingId = json_decode($zoomMeetingData,true)['id'];
        $zoomService = new ZoomService();
        $zoomService->deleteMeeting( $meetingId );

    }

    private static function getGroupMeetingData($appointmentInf)
    {
        if ( is_null($appointmentInf) )
            return null;

        $row = Appointment::where('service_id' , $appointmentInf->service_id )
            ->where('location_id' , $appointmentInf->location_id)
            ->where('staff_id' , $appointmentInf->staff_id)
            ->where( 'starts_at' , $appointmentInf->starts_at )
            ->where(Appointment::getField('id'), '<>', $appointmentInf->id)
            ->innerJoin( Data::getTableName() , ['data_value'] , [
                [Data::getField('row_id'), '=', Appointment::getField('id')],
                [Data::getField('table_name'), '=', "'" . Appointment::getTableName() . "'"],
                [Data::getField('data_key'), '=', "'zoom_meeting_data'"]
            ] )
            ->select(Data::getField('data_value'), true)
            ->limit(1)
            ->fetch();

        if ( empty($row) )
            return null;

        return $row->data_value;
    }

    public static function zoomShortCodes( $text, $data )
    {
		if( ! isset( $data['appointment_id'] ) )
			return $text;

        return str_replace( [
            '{zoom_meeting_url}',
            '{zoom_meeting_host_url}',
            '{zoom_meeting_password}',
        ], [
            self::getZoomData('url', $data  ),
            self::getZoomData('host_url', $data),
            self::getZoomData('password', $data ),
        ], $text );
    }

    public static function getZoomData( $fieldName, $data )
    {
		$appointmentData = AppointmentSmartObject::load( $data['appointment_id'] );

		if( ! $appointmentData->getInfo() )
			return '';

        $zoomData = json_decode( $appointmentData->getInfo()->getData( 'zoom_meeting_data' ), true );

        if( empty( $zoomData ) || !is_array( $zoomData ) )
            return '';

	    if( $fieldName == 'url' )
	    {
		    return $zoomData['join_url'];
	    }
		else if( $fieldName == 'host_url' )
		{
			return $zoomData['start_url'];
		}
        else if( $fieldName == 'password' )
        {
            return isset( $zoomData['password'] ) ? $zoomData['password'] : '';
        }
        else
        {
            return '';
        }
    }

    public static function zoom_data_save_staff ( $arr )
    {
    	if ( ! empty( $arr[ 'staff_id' ] ) )
	    {
		    $zoom_user = Helper::_post( 'zoom_user', '', 'string' );
		    $zoom_user = empty( $zoom_user ) ? '' : json_decode( $zoom_user, true );

		    if ( ! is_array( $zoom_user ) || ! isset( $zoom_user[ 'id' ] ) || ! is_string( $zoom_user[ 'id' ] ) || ! isset( $zoom_user[ 'name' ] ) || ! is_string( $zoom_user[ 'name' ] )  )
		    {
			    $zoom_user = [
				    'id'    =>  '',
				    'name'  =>  ''
			    ];
		    }

		    Staff::setData( $arr[ 'staff_id' ], 'zoom_user', json_encode( $zoom_user ) );
	    }

        return $arr;
    }

    public static function zoom_data_save_service ( $arr )
    {
        if ( ! empty( $arr[ 'id' ] ) )
        {
	        $activateZoom = Helper::_post( 'activate_zoom', '0', 'string', [ '1' ] ) ? 1 : 0;

	        Service::setData( $arr[ 'id' ], 'activate_zoom', $activateZoom );
        }

        return $arr;
    }

    public static function add_zoom_row_to_staff_view ($parameters)
    {
        $cid = $parameters['staff']['id'];

        if ( $cid > 0 )
        {
	        $staffInfo = Staff::get( $cid );

	        if ( ! $staffInfo )
	        {
		        $staffInfo = new Collection();
	        }
        }
        else
        {
	        $staffInfo = new Collection();
        }

        return [ 'staff' => $staffInfo ];
    }

    public static function add_zoom_row_to_service_view ($parameters)
    {
        $sid = $parameters['service']['id'];

        if ( $sid > 0 )
        {
	        $serviceInfo	= Service::get( $sid );

	        if ( ! $serviceInfo )
	        {
		        $serviceInfo	= new Collection();
	        }
        }
        else
        {
	        $serviceInfo	= new Collection();
        }

        return [ 'service'	=>	$serviceInfo ];
    }

}