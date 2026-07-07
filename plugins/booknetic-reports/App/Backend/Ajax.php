<?php

namespace BookneticAddon\Reports\Backend;

use BookneticAddon\Reports\Helpers\Reports;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

    public function get_appointment_report_via_count ()
    {
        Capabilities::must('reports');

        $type       = Helper::_post('type', 'daily', 'string', ['daily', 'monthly', 'annually']);
        $filters    = Helper::_post('filters', [], 'array');

        $dateRange  = Reports::getDateRangeByType($type);
        $sql        = Reports::sql($type,'count(0)');

        $response = Appointment::where('starts_at', '>=', Date::epoch($dateRange['start']))
            ->where('ends_at', '<=', Date::epoch($dateRange['end']))
            ->groupBy( $sql['groupBy'] )
            ->select( $sql['select'] );

        if( isset( $filters['location'] ) && is_numeric( $filters['location'] ) && $filters['location'] > 0 )
        {
            $response->where('location_id', $filters['location']);
        }

        if( isset( $filters['service'] ) && is_numeric( $filters['service'] ) && $filters['service'] > 0 )
        {
            $response->where('service_id', $filters['service']);
        }

        if( isset( $filters['staff'] ) && is_numeric( $filters['staff'] ) && $filters['staff'] > 0 )
        {
            $response->where('staff_id', $filters['staff']);
        }

        if( isset( $filters['status'] ) && array_key_exists( $filters['status'], Helper::getAppointmentStatuses() ) )
        {
            $response->where('status', $filters['status']);
        }

        $response = $response->fetchAll();

        return $this->response(true, [
            'response' => Reports::Iterate($response, $dateRange['start'], $dateRange['end'], $dateRange['format'], $dateRange['iter'])
        ]);
    }

    public function get_appointment_report_via_price ()
    {
        Capabilities::must('reports');

        $type       = Helper::_post('type', 'daily', 'string', ['daily', 'monthly', 'annually']);
        $filters    = Helper::_post('filters', [], 'array');

        $dateRange  = Reports::getDateRangeByType($type);
        $sql        = Reports::sql($type, 'SUM(`price`*`negative_or_positive`)');

        $response = Appointment::leftJoin('prices', ['id'])
            ->where('starts_at', '>=', Date::epoch($dateRange['start']))
            ->where('starts_at', '<=', Date::epoch($dateRange['end']))
            ->groupBy( $sql['groupBy'] )
            ->select( $sql['select'], true );

        if( isset( $filters['location'] ) && is_numeric( $filters['location'] ) && $filters['location'] > 0 )
        {
            $response->where('location_id', $filters['location']);
        }

        if( isset( $filters['service'] ) && is_numeric( $filters['service'] ) && $filters['service'] > 0 )
        {
            $response->where('service_id', $filters['service']);
        }

        if( isset( $filters['staff'] ) && is_numeric( $filters['staff'] ) && $filters['staff'] > 0 )
        {
            $response->where('staff_id', $filters['staff']);
        }

        if( isset( $filters['status'] ) && array_key_exists( $filters['status'], Helper::getAppointmentStatuses() ) )
        {
            $response->where('status', $filters['status']);
        }

        $response = $response->fetchAll();

        return $this->response(true, [
            'response' => Reports::Iterate($response, $dateRange['start'], $dateRange['end'], $dateRange['format'], $dateRange['iter'])
        ]);
    }

    public function get_location_report()
    {
        Capabilities::must('reports');

        $type = Helper::_post('type', 'this-week', 'string', ['this-week', 'previous-week', 'this-month', 'previous-month', 'this-year', 'previous-year']);

        $dateRange  = Reports::getDateRangeByType($type);

        $response   = Appointment::leftJoin( 'location' )
            ->where('starts_at', '>=', Date::epoch($dateRange['start']))
            ->where('starts_at', '<=', Date::epoch($dateRange['end']))
            ->groupBy( 'location_id' )
            ->select( '`name` AS `title`, count(0) as `val`', true )
            ->fetchAll();

        $labels = [];
        $values = [];
        foreach ( $response AS $item )
        {
            $labels[] = $item['title'];
            $values[] = $item['val'];
        }

        return $this->response(true, [
            'response' => [
                'labels'    =>  $labels,
                'values'    =>  $values
            ]
        ]);
    }

    public function get_staff_report()
    {
        Capabilities::must('reports');

        $type = Helper::_post('type', 'this-week', 'string', ['this-week', 'previous-week', 'this-month', 'previous-month', 'this-year', 'previous-year']);

        $dateRange = Reports::getDateRangeByType($type);

        $response = Appointment::leftJoin( 'staff' )
            ->where('starts_at', '>=', Date::epoch($dateRange['start']))
            ->where('starts_at', '<=', Date::epoch($dateRange['end']))
            ->groupBy( 'staff_id' )
            ->select( '`name` AS `title`, count(0) as `val`', true )
            ->fetchAll();

        $labels = [];
        $values = [];
        foreach ( $response AS $item )
        {
            $labels[] = $item['title'];
            $values[] = $item['val'];
        }

        return $this->response(true, [
            'response' => [
                'labels'    =>  $labels,
                'values'    =>  $values
            ]
        ]);
    }

}