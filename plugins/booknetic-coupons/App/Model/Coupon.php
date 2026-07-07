<?php

namespace BookneticAddon\Coupons\Model;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;


/**
 * @property-read int $id
 * @property string $type
 * @property string $code
 * @property string $discount_type
 * @property float $discount
 * @property int $series_count
 * @property string $services
 * @property string $staff
 * @property string $start_date
 * @property string $end_date
 * @property int $usage_limit
 * @property int $once_per_customer
 * @property int $once_per_booking
 * @property int $tenant_id
 */
class Coupon extends Model
{
	use MultiTenant;

    public static function numberOfUses( $coupon = null, $excludeAppointmentId = null )
    {
        if( empty( $coupon ) )
            return 0;

        if( ! isset( $coupon->number_of_uses ) )
        {
            $usageTimes = Data::select( [ 'SUM(weight) AS number_of_uses' ] )
                ->leftJoin( 'appointments', false )
                ->where( 'table_name', Appointment::getTableName() )
                ->where( 'data_key', 'coupon_id' )
                ->where( 'data_value', $coupon->id );

            if( $excludeAppointmentId > 0 )
            {
                $usageTimes->where( 'row_id', '!=', $excludeAppointmentId );
            }

            $usageTimes = $usageTimes->fetch();

            $coupon->number_of_uses = (int)$usageTimes->number_of_uses;
        }

        return $coupon->number_of_uses;
    }

    public static function checkCustomerUsage( $coupon = null, $customerId = false )
    {
        if( empty( $coupon ) || empty( $customerId ) )
            return false;

        $check = Data::leftJoin( 'appointments', false )
                        ->where('table_name', Appointment::getTableName())
                        ->where('data_key', 'coupon_id')
                        ->where('data_value', $coupon->id)
                        ->where( Appointment::getField('customer_id'), $customerId )
                        ->count();

        return $check > 0;
    }

}
