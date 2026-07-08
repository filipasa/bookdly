<?php

namespace BookneticAddon\Coupons\Backend;

use BookneticAddon\Coupons\Model\Coupon;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use function BookneticAddon\Coupons\bkntc__;

class Controller extends \BookneticApp\Providers\Core\Controller
{

    public function index()
    {
        $coupons = Coupon::fetchAll();

        $this->view( 'index', [ 'coupons' => $coupons ] );
    }

}
