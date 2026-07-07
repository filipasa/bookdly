<?php

namespace BookneticAddon\Reports\Backend;

use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Capabilities;


class Controller extends \BookneticApp\Providers\Core\Controller
{

    public function index()
    {
        Capabilities::must('reports');

        $data = [];
        $data['locations'] = Location::fetchAll();
        $data['staff'] = Staff::fetchAll();
        $data['services'] = Service::fetchAll();
        $data['status'] = Helper::getAppointmentStatuses();
        $this->view( 'index', $data );
    }

}
