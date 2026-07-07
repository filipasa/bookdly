<?php

namespace BookneticAddon\Coupons\Backend;


use BookneticAddon\Coupons\Model\Coupon;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use function BookneticAddon\Coupons\bkntc__;

class Ajax extends Controller
{

    public function add_new()
    {
        $cid = Helper::_post('id', '0', 'integer');

        $services = [];
        $staff = [];

        if( $cid > 0 )
        {
            Capabilities::must('coupons_edit');

            $couponInf = Coupon::get( $cid );

            foreach ( explode(',', $couponInf['services']) AS $serviceId )
            {
                if( $serviceId > 0 )
                {
                    $serviceInf = Service::get( $serviceId );
                    $services[] = [ $serviceId, $serviceInf['name'] ];
                }
            }

            foreach ( explode(',', $couponInf['staff']) AS $staffId )
            {
                if( $staffId > 0 )
                {
                    $serviceInf = Staff::get( $staffId );
                    $staff[] = [ $staffId, $serviceInf['name'] ];
                }
            }
        }
        else
        {
            Capabilities::must('coupons_add');

            $couponInf = [
                'id'                =>  null,
                'code'              =>  null,
                'discount_type'     =>  null,
                'discount'          =>  null,
                'services'          =>  null,
                'staff'             =>  null,
                'start_date'        =>  null,
                'end_date'          =>  null,
                'usage_limit'       =>  null,
                'once_per_customer' =>  null,
                'once_per_booking'  =>  null
            ];
        }

        TabUI::get( 'coupons_add_new' )
            ->item( 'details' )
            ->setTitle( bkntc__( 'Coupon Details' ) )
            ->addView(__DIR__ . '/view/tabs/coupons_add_new_details.php')
            ->setPriority( 1 );

        return $this->modalView( 'add_new', [
            'coupon'	=>	$couponInf,
            'services'	=>	$services,
            'staff'		=>	$staff
        ]);
    }

    public function save_coupon()
    {
        $id					=	Helper::_post('id', '0', 'integer');

        $code				=	Helper::_post('code', '', 'string');
        $discount			=	Helper::_post('discount', '0', 'float');
        $discount_type		=	Helper::_post('discount_type', 'percent', 'string', ['percent', 'price']);
        $start_date			=	Helper::_post('start_date', '', 'string');
        $end_date			=	Helper::_post('end_date', '', 'string');
        $usage_limit		=	Helper::_post('usage_limit', 0, 'int');
        $once_per_customer	=	Helper::_post('once_per_customer', 'false', 'string', [ 'true', 'false' ]);
        $once_per_booking	=	Helper::_post('once_per_booking', 'false', 'string', [ 'true', 'false' ]);
        $services			=	Helper::_post('services', '', 'string');
        $staff				=	Helper::_post('staff', '', 'string');

        $usage_limit = $usage_limit <= 0 ? null : $usage_limit;

        if( $discount < 0 )
        {
            return $this->response(false, bkntc__('Discount cannot be negative number!'));
        }

        if ( $discount_type == 'percent' )
        {
            if ( $discount <= 0 )
            {
                return $this->response( false, bkntc__( 'Discount percent should be more than 0%!' ) );
            }

            if ( $discount > 100 )
            {
                return $this->response( false, bkntc__( 'Discount percent count cannot be more than 100%!' ) );
            }
        }

        $servicesArr = json_decode( $services, true );
        $services = [];
        foreach ( $servicesArr AS $serviceId )
        {
            $services[] = (int)$serviceId;
        }
        $services = implode(',', $services);

        $staffArr = json_decode( $staff, true );
        $staff = [];
        foreach ( $staffArr AS $staffid )
        {
            $staff[] = (int)$staffid;
        }
        $staff = implode(',', $staff);

        if( empty($code) )
        {
            return $this->response(false, bkntc__('Please type the coupon code field!'));
        }

        $code = mb_strtoupper( $code );

        $checkIfCodeIsExists = Coupon::where( 'code', $code )->fetch();

        if ( empty( $id ) && ! empty( $checkIfCodeIsExists ) )
        {
            return $this->response( false, bkntc__( 'Coupon with same code is already exists!' ) );
        }

        $sqlData = [
            'code'				=>	$code,
            'discount'			=>	$discount,
            'discount_type'		=>	$discount_type,
            'start_date'		=>	empty($start_date) ? null : Date::dateSQL( Date::reformatDateFromCustomFormat( $start_date ) ),
            'end_date'			=>	empty($end_date) ? null : Date::dateSQL( Date::reformatDateFromCustomFormat( $end_date ) ),
            'usage_limit'		=>	$usage_limit,
            'once_per_customer'	=>	$once_per_customer == 'true',
            'once_per_booking'	=>	$once_per_booking == 'true',
            'services'			=>	$services,
            'staff'				=>	$staff
        ];

        if( $id > 0 )
        {
            Capabilities::must( 'coupons_edit' );

            Coupon::where('id', $id)->update( $sqlData );
        }
        else
        {
            Capabilities::must( 'coupons_add' );

            Coupon::insert( $sqlData );
        }

        return $this->response(true );
    }

    public function get_services()
    {
        $search		= Helper::_post('q', '', 'string');

        $services = Service::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data = [];

        foreach ( $services AS $service )
        {
            $data[] = [
                'id'				=>	(int)$service['id'],
                'text'				=>	htmlspecialchars($service['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_staff()
    {
        $search	= Helper::_post('q', '', 'string');

        $staff  = Staff::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data   = [];

        foreach ( $staff AS $staffInf )
        {
            $data[] = [
                'id'				=>	(int)$staffInf['id'],
                'text'				=>	htmlspecialchars($staffInf['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function load_edit_tab_content()
    {
        Capabilities::must('appointments_coupons_tab');

        $appointment_id	    = Helper::_post('appointment', '0', 'integer');
        $service_id		    = Helper::_post('service', '0', 'integer');
        $staff_id		    = Helper::_post('staff', '0', 'integer');
        $customer_id		= Helper::_post('customer', '0', 'integer');

        $available_coupons = Coupon::where(function ( $qb ) use( $service_id )
        {
            $qb->whereFindInSet( 'services', $service_id )->orWhere('services', null)->orWhere('services', '');
        })->where( function ( $qb ) use ( $staff_id )
        {
            $qb->whereFindInSet('staff', $staff_id)->orWhere('staff', null)->orWhere('staff', '');
        })->fetchAll();

        return $this->modalView( 'edit_tab', [
            'available_coupons'	=> $available_coupons,
            'coupon'            => Appointment::getData( $appointment_id, 'coupon_id' )
        ] );
    }

    public function coupons_usage_history ()
    {
        $couponId   = Helper::_post('id', '0', 'integer');
        $data       = [];
        $coupInf    = [];
        $counter    = 0;

        $couponInf = Data::where('table_name', Appointment::getTableName() )
            ->where('data_key', 'coupon_id')
            ->where('data_value', $couponId)
            ->fetchAll();

        foreach ( $couponInf as $coupon )
        {
            $appointmentSmartInfo = AppointmentSmartObject::load( $coupon->row_id );

            if( ! $appointmentSmartInfo->validate() )
            {
                continue;
            }

            $customerInfo               = $appointmentSmartInfo->getCustomerInf();
            $appointmentInfo            = $appointmentSmartInfo->getAppointmentInfo();
            $serviceInfo                = $appointmentSmartInfo->getServiceInf();

            $coupInf[ 'coupon-' . $counter . '-customer_id' ] 	    = $customerInfo->id;
            $coupInf[ 'coupon-' . $counter . '-id' ] 		        = $coupon->data_value;
            $coupInf[ 'coupon-' . $counter . '-coupon_amount' ]     = Appointment::getData( $coupon->row_id, 'coupon_amount' );
            $coupInf[ 'coupon-' . $counter . '-first_name' ] 		= $customerInfo->first_name;
            $coupInf[ 'coupon-' . $counter . '-last_name' ] 		= $customerInfo->last_name;
            $coupInf[ 'coupon-' . $counter . '-profile_image' ] 	= $customerInfo->profile_image;
            $coupInf[ 'coupon-' . $counter . '-email' ] 			= $customerInfo->email;
            $coupInf[ 'coupon-' . $counter . '-service_name' ]      = $serviceInfo->name;
            $coupInf[ 'coupon-' . $counter . '-date' ]              = $appointmentInfo->date;
            $coupInf[ 'coupon-' . $counter . '-appointment_id' ]    = $appointmentInfo->id;

            $data[] = $coupInf;

            $counter++;
        }

        TabUI::get( 'coupons_usage_history' )
            ->item( 'details' )
            ->setTitle( bkntc__( 'Usage History Details' ) )
            ->addView(__DIR__ . '/view/tabs/coupons_usage_history_details.php')
            ->setPriority( 1 );


        return $this->modalView(  'coupons_usage_history', [
            'coupons'		=> $data
        ] );
    }

}
