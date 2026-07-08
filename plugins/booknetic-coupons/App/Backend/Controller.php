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
use BookneticApp\Providers\Request\Post;

class Controller extends \BookneticApp\Providers\Core\Controller
{

    public function index()
    {
        // Intercept DataTable actions manually since we bypass DataTableUI
        if (Post::has('fs-data-table-action')) {
            $action = Post::string('fs-data-table-action');
            $ids = Post::array('ids', 'int');
            if ($action === 'delete' && Capabilities::userCan('coupons_delete')) {
                Coupon::where('id', $ids)->delete();
                return $this->response(true);
            }
        }

        $coupons = Coupon::fetchAll();

        $this->view( 'index', [ 'coupons' => $coupons ] );
    }

}
