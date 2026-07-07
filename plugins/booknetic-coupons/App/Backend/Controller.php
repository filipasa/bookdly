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
        $dataTable = new DataTableUI( Coupon::select("*, IFNULL(usage_limit, '-') AS usage_limit_txt") );

        $dataTable->setTitle(bkntc__('Coupons'));
        $dataTable->addNewBtn(bkntc__('ADD COUPON'));

        $dataTable->addAction('edit', bkntc__('Edit'));

        if (Capabilities::userCan('coupons_delete'))
        {
            $dataTable->addAction('delete', bkntc__('Delete'), function ($ids)
            {
                Coupon::where('id', $ids)->delete();
            }, DataTableUI::ACTION_FLAG_BULK_SINGLE );
        }

        $dataTable->searchBy(["code", 'discount', 'start_date', 'end_date']);

        $dataTable->addColumns(bkntc__('№'), DataTableUI::ROW_INDEX);
        $dataTable->addColumns(bkntc__('CODE'), 'code');
        $dataTable->addColumns(bkntc__('DISCOUNT'), function( $coupon )
        {
            return $coupon[ 'discount_type' ] === 'percent' ? Math::floor( $coupon['discount'], 2 ) . '%' : Helper::price( $coupon['discount'] );
        }, ['order_by_field' => 'discount']);
        $dataTable->addColumns(bkntc__('USAGE LIMIT'), 'usage_limit_txt');
        $dataTable->addColumns(bkntc__('Times Used'), fn( $coupon ) => $coupon->numberOfUses() );

        $dataTable->addColumns(bkntc__('STATUS'), function( $coupon )
        {
            if ( ! is_null( $coupon[ 'start_date' ] ) && Date::epoch() < Date::epoch( $coupon[ 'start_date' ] ) )
            {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-warning">' .  bkntc__( 'Inactive' ) . '</button>';
            }
            else if ( ! is_null( $coupon[ 'end_date' ] ) && Date::epoch() > Date::epoch( $coupon[ 'end_date' ] ) )
            {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">' .  bkntc__( 'Expired' ) . '</button>';
            }
            else
            {
                if ( $coupon[ 'usage_limit_txt' ] === '-' || ( (int) $coupon[ 'usage_limit_txt' ] - (int) $coupon->numberOfUses() > 0 ) )
                {
                    $statusBtn = '<button type="button" class="btn btn-xs btn-light-success">' .  bkntc__( 'Active' ) . '</button>';
                }
                else
                {
                    $statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">' .  bkntc__( 'Expired' ) . '</button>';
                }
            }

            return $statusBtn;
        }, [ 'is_html' => true ] );

        $dataTable->addColumns(bkntc__('USAGE HISTORY'), function()
        {
            return '<img class="invoice-icon" src="' . Helper::icon('invoice.svg') . '">';
        }, ['attr' => ['column' => 'usage_history'], 'is_html' => true,]);

        $table = $dataTable->renderHTML();

        $this->view( 'index', [ 'table' => $table ] );
    }

}
