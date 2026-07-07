<?php

namespace BookneticAddon\Coupons;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData as ARData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests as Request;
use BookneticApp\Models\Appointment;
use BookneticAddon\Coupons\Model\Coupon;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;

class Listener
{

    public static function init_coupons()
    {
        $coupon = self::getCoupon();

        if( empty( $coupon ) )
            return;

        if ( ! empty( $coupon->start_date ) && Date::epoch() < Date::epoch( $coupon->start_date ) )
            return;

        if ( ! empty( $coupon->end_date ) && Date::epoch() > Date::epoch( $coupon->end_date ) )
            return;

        $appliedCount = 0;

        foreach ( Request::appointments() as $appointment )
        {
            $servicesFilter = explode( ',', $coupon->services );
            $staffFilter    = explode( ',', $coupon->staff );

            if ( ( ! empty( $coupon->services ) && ! in_array( (string) $appointment->serviceId, $servicesFilter ) ) || ( ! empty( $coupon->staff ) && ! in_array( (string) $appointment->staffId, $staffFilter ) ) )
                continue;

            if ( ! $appointment->calledFromBackend && $coupon->once_per_customer == 1 && $appointment->customerId > 0 )
            {
                if( ($appointment->serviceInf->is_recurring && count( $appointment->getAllTimeslots() ) > 1) || $coupon->checkCustomerUsage( $appointment->customerId ) )
                    continue;
            }

            $checkNumberOfUsage = $coupon->numberOfUses( $appointment->appointmentId ) + $appliedCount;

            $c = count($appointment->getAllTimeslots());

            if ( ! is_null( $coupon->usage_limit ) && $coupon->usage_limit < ($checkNumberOfUsage + $c) )
                continue;

            $appliedCount += $c;

            if ( $coupon->once_per_booking == 1 && $appliedCount > 1 )
                continue;

            if( $coupon->discount_type === 'price' )
            {
                $couponDiscount = ( $coupon->discount * $appointment->weight ) > $appointment->getSubTotal() ? $appointment->getSubTotal() : ( $coupon->discount * $appointment->weight );
            }
            else
            {
                $couponDiscount = Math::floor( $coupon->discount * $appointment->getSubTotal() / 100 );
            }

            $coupon['calculated_amount'] = $couponDiscount;

            $discountPriceObj = $appointment->price('discount');
            $currentDiscount = $discountPriceObj->getPrice();

            if( $couponDiscount > 0 )
            {
                $discountPriceObj->setPrice( $currentDiscount + $couponDiscount * -1, true );
                $discountPriceObj->setHidden( false );
            }

            //todo://slowly migrate all dynamic properties to static.
            // Or do not create a property at all
            //php 8.1 does not support dynamic property declaration

            $appointment->couponInf = $coupon;

        }
    }

    public static function appointment_insert_data_coupon ( ARData $appointmentObj )
    {
        if ( isset( $appointmentObj->couponInf ) )
        {
            Appointment::setData( $appointmentObj->appointmentId, 'coupon_id', $appointmentObj->couponInf->id );
            Appointment::setData( $appointmentObj->appointmentId, 'coupon_amount', $appointmentObj->couponInf['calculated_amount'] );
        }
        else
        {
            Appointment::deleteData( $appointmentObj->appointmentId, 'coupon_id' );
            Appointment::deleteData( $appointmentObj->appointmentId, 'coupon_amount' );
        }
    }

    public static function add_info_tab ( $appointmentId )
    {
        $cpnId  = Appointment::getData( $appointmentId, 'coupon_id' );
        $cpnInf = Coupon::get( $cpnId );
        $amount = Appointment::getData( $appointmentId, 'coupon_amount' );

        return [
            'coupon'                    =>  $cpnInf,
            'coupon_calculated_amount'  =>  $amount
        ];
    }

    public static function replace_short_code_text ( $text, $data )
    {
        if( ! isset( $data['appointment_id'] ) )
            return $text;

        $couponId = Appointment::getData($data['appointment_id'], 'coupon_id');
        $coupon = Coupon::get( $couponId );

        return str_replace('{coupon_code}', $coupon ? $coupon->code : '', $text);
    }

    public static function beforeTenantDelete($tenantId)
    {
        Coupon::noTenant()->where('tenant_id', $tenantId)->delete();
    }

    private static function getCoupon()
    {
        if( Request::self()->calledFromBackend )
        {
            $id = Helper::_post( 'coupon' , -1 , 'int' );

            if ( $id == -1 )
                return null;

            return Coupon::get( $id );
        }

        $code = Helper::_post( 'coupon' , '', 'str' );

        if ( empty( $code ) )
            return null;

        return Coupon::where( 'code', $code )->fetch();
    }

}
