<?php

namespace BookneticAddon\Coupons\Frontend;

use BookneticAddon\Coupons\Listener;
use BookneticApp\Backend\Appointments\Helpers\AppointmentPriceObject;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\Helpers\Helper;

use BookneticApp\Providers\Helpers\Math;
use function BookneticAddon\Coupons\bkntc__;

class Ajax extends FrontendAjax
{

    public function summary_with_coupon ()
    {
        $appointmentRequests = AppointmentRequests::load();
        $hasCoupon = false;
        foreach ($appointmentRequests->appointments as $appointment)
        {
            if (isset($appointment->couponInf) )
            {
                $hasCoupon = true;
                break;
            }
        }
        if( ! $hasCoupon && Helper::_post('coupon' , '','str') !== '' )
        {
            return $this->response(false, bkntc__('Coupon not found!'));
        }


        //  doit deposit
        return $this->response( true, [
            'sum_price'             =>  $appointmentRequests->getSubTotal( true ),
            'sum_price_txt'         =>  Helper::price( $appointmentRequests->getSubTotal( true ) ),
            'prices_html'           =>  $appointmentRequests->getPricesHTML( true ),
            'deposit_txt'           =>  Helper::price( $appointmentRequests->getDepositPrice( true ) ),
//            'deposit_txt'           =>  Helper::price( 0 ),
        ], true );
    }

}