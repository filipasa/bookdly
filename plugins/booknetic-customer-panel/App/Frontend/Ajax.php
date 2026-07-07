<?php

namespace BookneticAddon\Customerpanel\Frontend;

use BookneticAddon\Customerpanel\CustomerPanelHelper;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Service;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\FrontendAjax;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use function BookneticAddon\Customerpanel\bkntc__;

class Ajax extends FrontendAjax
{

    public function save_profile()
    {
        if( ! CustomerPanelHelper::canUseCustomerPanel() )
        {
            return $this->response( false );
        }

        $name		=	Helper::_post('name', '', 'str');
        $surname	=	Helper::_post('surname', '', 'str');
        $email		=	Helper::_post('email', '', 'str');
        $phone		=	Helper::_post('phone', '', 'str');
        $birthdate	=	Helper::_post('birthdate', '', 'str');
        $gender		=	Helper::_post('gender', '', 'str', ['', 'male', 'female']);

        if( empty( $name ) || empty( $surname ) || empty( $email ) || empty( $phone ) || ( ! empty( $birthdate ) && ! Date::isValid( $birthdate ) ) )
        {
            return $this->response( false, bkntc__('Please fill all required fields!') );
        }

	    /**
	     * Eger email deyishibse o halda wp_users`de de deyishsin emaili.
	     */
        if( $email != CustomerPanelHelper::myCustomer()->email )
        {
	        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )
	        {
		        return $this->response(false, bkntc__('Please enter a valid email address!') );
	        }

	        $checkIfEmailAlreadyExist = Customer::where('email', $email)->fetch();

	        if( $checkIfEmailAlreadyExist || email_exists( $email ) )
	        {
		        return $this->response(false, bkntc__('This email address is already used by another customer.') );
	        }

	        wp_update_user([
		        'ID'         => Permission::userId(),
		        'user_email' => $email
	        ]);
        }

        Customer::where('user_id', Permission::userId())->noTenant()->update([
            'first_name'		=>	trim( $name ),
            'last_name'			=>	trim( $surname ),
            'phone_number'		=>	$phone,
            'email'				=>	$email,
            'gender'			=>	$gender,
            'birthdate'			=>	empty( $birthdate ) ? null : Date::dateSQL( $birthdate )
        ]);

        wp_update_user([
            'ID' => Permission::userId(),
            'first_name' => trim($name),
            'last_name'	=>	trim( $surname )
        ]);

        return $this->response( true, ['message' => bkntc__('Profile data was saved successfully')] );
    }

    public function change_password()
    {
        if( ! CustomerPanelHelper::canUseCustomerPanel() )
        {
            return $this->response( false );
        }

        $old_password			=	Helper::_post('old_password', '', 'str');
        $new_password			=	Helper::_post('new_password', '', 'str');
        $repeat_new_password	=	Helper::_post('repeat_new_password', '', 'str');

        if( $new_password != $repeat_new_password || empty( $new_password ) )
        {
            return $this->response( false, bkntc__('Password does not match!') );
        }

        $userId = Permission::userId();

        $userInf = get_user_by('id', $userId);
        if( $userInf && !wp_check_password( $old_password, $userInf->data->user_pass, $userId ) )
        {
            return $this->response( false, bkntc__('Current password is wrong!') );
        }

        wp_set_password( $new_password, $userId );

        do_action( 'bkntc_customer_reset_password', Customer::select([ 'id' ])->where('user_id', $userId)->fetch()->id );

        return $this->response( true, ['message' => bkntc__('Password was changed successfully')] );
    }

    public function reschedule_appointment()
    {
	    if ( ! CustomerPanelHelper::canUseCustomerPanel() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	=	Helper::_post('id', '', 'int');
        $date			=	Helper::_post('date', '', 'str');
        $time			=	Helper::_post('time', '', 'str');

        $date = Date::reformatDateFromCustomFormat( $date );// doit: Customer panelde ele bil yigishdirmishdiq format meselesini?

        $newD = new \DateTime(Date::dateTimeSQL($date . ' ' .$time), Date::getTimeZone(Helper::getOption('client_timezone_enable', 'off') == 'on') );
        $newD->setTimezone(Date::getTimeZone());
        $date = $newD->format('Y-m-d');
        $time = $newD->format('H:i');

        $appointmentInfo = Appointment::noTenant()->get( $appointment_id );
        if ( Helper::isSaaSVersion() )
        {
            Permission::setTenantId( $appointmentInfo->tenant_id );
        }

        $allowedRescheduleStatuses = Helper::getOption('customer_panel_reschedule_allowed_status', '', false);
        $allowedRescheduleStatuses = explode(',', $allowedRescheduleStatuses);

        if ( ! preg_match( '/^[0-9]{2}:[0-9]{2}$/', $time ) )
        {
            return $this->response( false, bkntc__( 'Time format is wrong.' ) );
        }

        if ( Date::epoch() >= Date::epoch( $date . ' ' . $time . ':00' ) )
        {
            return $this->response( false, bkntc__( 'You can not change the date and time to past.' ) );
        }

        if ( ! $appointmentInfo || ! in_array( $appointmentInfo->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
        {
            return $this->response( false );
        }

	    if ( Helper::getOption( 'customer_panel_allow_reschedule', 'on', false ) != 'on' )
	    {
		    return $this->response( false );
	    }

        if ( ! in_array($appointmentInfo->status, $allowedRescheduleStatuses) && !empty($allowedRescheduleStatuses) )
        {
            return $this->response( false );
        }

        $minute = Helper::getOption( 'time_restriction_to_make_changes_on_appointments', '5', false );
        $beforeThisTime = Helper::getOption( 'min_time_req_prior_booking', '5' );

        if ( Date::epoch( '+' . $minute . ' minutes' ) > Date::epoch( $appointmentInfo->starts_at ) )
        {
            return $this->response( false, bkntc__( 'Minimum time requirement prior to change the appointment date and time is %s', [ Helper::secFormatWithName( $minute * 60 ) ] ) );
        }

        $before = Date::epoch( '+' . $beforeThisTime . ' minutes' );

        if ( $before > Date::epoch( $date . ' ' . $time . ':00' ) )
        {
            return $this->response( false, bkntc__( 'You cannot change the appointment less than %s in advance', [ Helper::secFormatWithName( $beforeThisTime * 60 ) ] ) );
        }

        if ( Date::dateSQL( $appointmentInfo->date ) == Date::dateSQL( $date ) && Date::timeSQL( $appointmentInfo->start_time ) == Date::timeSQL( $time ) )
        {
            return $this->response( false, bkntc__( 'You have not changed the date and time.' ) );
        }

        try
        {
	        AppointmentService::reschedule( $appointment_id, $date, $time );

            return $this->response( true, [
                'message' => bkntc__( 'Appointment was rescheduled successfully!' ),
            ]);
        }
        catch ( \Exception $e )
        {
        	return $this->response( false, $e->getMessage() );
        }
    }

    public function get_allowed_statuses()
    {
	    if( ! CustomerPanelHelper::myCustomersIDs() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	    = Helper::_post('id', 0, 'int');
        $appointmentInf     = Appointment::noTenant()->where('id', $appointment_id )->fetch();

	    if ( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
	    {
		    return $this->response( false );
	    }

	    if( Helper::isSaaSVersion() )
	    {
		    Permission::setTenantId( $appointmentInf->tenant_id );
	    }

        $statuses = Helper::getOption('customer_panel_allowed_status', '', false);
        $statusesArray = explode(',', $statuses);
        $status = $appointmentInf->status;
        $dataForReturn = [];

        foreach ( Helper::getAppointmentStatuses() AS $statusKey => $statusVal )
        {
            if( ! in_array( $statusKey, $statusesArray ) || $status === $statusKey)
            {
                continue;
            }

            $dataForReturn[] = [
                'id'=>$statusKey,
                'text' =>$statusVal['title']
            ];
        }

        return $this->response(true, [ 'results' => $dataForReturn ] );
    }

    public function get_allowed_payment_gateways()
    {
	    if( ! CustomerPanelHelper::myCustomersIDs() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	    = Helper::_post('id', 0, 'int');
        $appointmentInf     = Appointment::noTenant()->where('id', $appointment_id )->fetch();

	    if ( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
	    {
		    return $this->response( false );
	    }

	    if( Helper::isSaaSVersion() )
	    {
		    Permission::setTenantId( $appointmentInf->tenant_id );
	    }

        $serviceCustomMethods = Service::getData($appointmentInf->service_id , 'custom_payment_methods');
        $serviceCustomMethods = json_decode($serviceCustomMethods,true);
        if( empty( $serviceCustomMethods ) )
        {
            $paymentMethods = PaymentGatewayService::getEnabledGatewayNames();
        }else
        {
            $paymentMethods = $serviceCustomMethods;
        }

        $totalPrice = AppointmentPrice::where('appointment_id', $appointmentInf->id )
            ->select('sum(price * negative_or_positive) as total_price', true)->fetch()->total_price;
        if( $totalPrice == $appointmentInf->paid_amount )
            $paymentMethods = [];

        $dataForReturn = [];
        foreach ( $paymentMethods AS $paymentMethod )
        {
            if( $paymentMethod === 'local' )
                continue;

            $dataForReturn[] = [
                'id'=>$paymentMethod,
                'text' => PaymentGatewayService::find($paymentMethod)->getTitle()
            ];
        }

        return $this->response(true, [ 'results' => $dataForReturn ] );
    }

    public function change_appointment_status()
    {
	    if( ! CustomerPanelHelper::canUseCustomerPanel() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	            = Helper::_post('id', 0, 'int');
        $appointment_status         = Helper::_post('status', '', 'string');
	    $appointmentInf             = Appointment::noTenant()->where('id', $appointment_id )->fetch();

	    if( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
	    {
		    return $this->response( false );
	    }

	    if( Helper::isSaaSVersion() )
	    {
		    Permission::setTenantId( $appointmentInf->tenant_id );
	    }

		if ( ! CustomerPanelHelper::canChangeAppointmentStatus( $appointmentInf ) )
		{
			return $this->response(false, bkntc__('Time limit to change appointment status expired'));
		}

        $statuses = Helper::getOption( 'customer_panel_allowed_status', '', false );
        $statusesArray = explode(',', $statuses);
        $status = $appointmentInf->status;

        if ( ! in_array( $appointment_status ,$statusesArray )  )
        {
            return $this->response(false, bkntc__('Invalid appointment status') );
        }
        else if ( $status === $appointment_status )
        {
            return $this->response(false, bkntc__('Change current status before save') );
        }

        AppointmentService::setStatus($appointmentInf->id, $appointment_status);

        return $this->response(true, [
			'message' => bkntc__('Appointment status changed to %s', [ Helper::appointmentStatus( $appointment_status )[ 'title' ] ])
        ] );
    }

    public function cancel_appointment()
    {
	    if( ! CustomerPanelHelper::canUseCustomerPanel() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	= Helper::_post('id', 0, 'int');
	    $appointmentInf = Appointment::noTenant()->where('id', $appointment_id )->fetch();

	    if( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
	    {
		    return $this->response( false );
	    }

	    if( Helper::isSaaSVersion() )
	    {
		    Permission::setTenantId( $appointmentInf->tenant_id );
	    }

		if ( ! CustomerPanelHelper::canCancelAppointment( $appointmentInf ) )
		{
			return $this->response(false, bkntc__('Time limit to cancel appointment expired'));
		}

        AppointmentService::setStatus($appointmentInf->id, 'canceled');

        return $this->response(true, [
			'message' => bkntc__('Appointment was canceled successfully!')
        ] );
    }

    public function create_payment_link()
    {
        if( Helper::getOption('hide_pay_now_btn_customer_panel', 'off', false)=='on' )
            return $this->response(false);

	    if( ! CustomerPanelHelper::canUseCustomerPanel() )
	    {
		    return $this->response( false );
	    }

        $appointment_id	            = Helper::_post('id', 0, 'int');
        $paymentMethod              = Helper::_post('payment_method', '', 'string');
	    $appointmentInf             = Appointment::noTenant()->where('id', $appointment_id )->fetch();

	    if( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
	    {
		    return $this->response( false );
	    }

	    if( Helper::isSaaSVersion() )
	    {
		    Permission::setTenantId( $appointmentInf->tenant_id );
	    }

        $serviceCustomMethods = Service::getData($appointmentInf->service_id , 'custom_payment_methods');
        $serviceCustomMethods = json_decode($serviceCustomMethods,true);

        if( empty( $serviceCustomMethods ) )
        {
            $paymentMethods = PaymentGatewayService::getEnabledGatewayNames();
        }else
        {
            $paymentMethods = $serviceCustomMethods;
        }
        $paymentMethods = array_filter($paymentMethods , function ($paymentMethod){
           return $paymentMethod != 'local';
        });

        if ( ! in_array( $paymentMethod ,$paymentMethods )  )
        {
            return $this->response(false, bkntc__('Invalid payment method') );
        }

        $totalAmountQuery = AppointmentPrice::where('appointment_id', DB::field( Appointment::getField('id') ))
            ->select('sum(price * negative_or_positive)', true);

        $appointments = Appointment::leftJoin('customer', ['first_name', 'last_name', 'email', 'profile_image', 'phone_number'])
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->where(Appointment::getField('id') , $appointment_id )
            ->selectSubQuery( $totalAmountQuery, 'total_price' );

        $appointment = $appointments->fetch();

        $paymentGatewayService = PaymentGatewayService::find( $paymentMethod );

        if(! property_exists( $paymentGatewayService  ,'createPaymentLink'))
        {
            return $this->response(false);
        }

        $data = $paymentGatewayService->createPaymentLink([$appointment]);

        if( ! isset( $data->data['url'] ) )
            return $this->response(false);

        return $this->response(true, [
			'url' => $data->data['url']
        ] );
    }

    public function get_available_times_of_appointment()
    {
        if( ! CustomerPanelHelper::canUseCustomerPanel() )
        {
            return $this->response( false );
        }

        $appointmentId = Helper::_post( 'id', 0, 'int' );
        $search        = Helper::_post( 'q', '', 'string' );
        $date		   = Helper::_post( 'date', '', 'string' );

        $date = Date::reformatDateFromCustomFormat( $date );// doit: Customer panelde ele bil yigishdirmishdiq format meselesini?

        $appointmentInf = Appointment::noTenant()->get( $appointmentId );

        if( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
        {
            return $this->response( false );
        }

        $customerId = $appointmentInf->customer_id;
        $staff	    = $appointmentInf->staff_id;
        $service    = $appointmentInf->service_id;

        if( Helper::isSaaSVersion() )
        {
            Permission::setTenantId( $appointmentInf->tenant_id );
        }

        if( Helper::getOption('customer_panel_allow_reschedule', 'on', false) != 'on' )
        {
            return $this->response( false );
        }

        $extras_arr = [];
        $appointmentExtras = AppointmentExtra::where('appointment_id', $appointmentInf->id)->fetchAll();
        foreach ( $appointmentExtras AS $extra )
        {
            $extra_inf = $extra->extra()->fetch();
            $extra_inf['quantity'] = $extra['quantity'];
            $extra_inf['customer'] = $customerId;

            $extras_arr[] = $extra_inf;
        }

        $date = Date::dateSQL( $date );

        $serviceInf  = apply_filters( 'bkntc_set_service_duration_frontend', Service::get( $service ), $appointmentId );

	    $calendarData = new CalendarService( $date );
	    $calendarData->setStaffId( $staff )
	                 ->setLocationId( $appointmentInf->location_id )
	                 ->setServiceInf( $serviceInf )
	                 ->setServiceExtras( $extras_arr )
	                 ->setExcludeAppointmentId( $appointmentInf->id )
	                 ->setShowExistingTimeSlots( true );
	    $calendarData = $calendarData->getCalendar();

        $data = $calendarData['dates'];

        if( ! isset( $data[ $date ] ) )
            return $this->response(true, [ 'results' => [] ] );

        return $this->response(true, [ 'results' => $this->getDataForReturn( $data[ $date ], $search ) ] );
    }

    private function getDataForReturn( $data, $search )
    {
        $dataForReturn = [];

        foreach ( $data AS $dataInf )
        {
            $startTime = $dataInf[ 'start_time_format' ];

            if( ! empty( $search ) && strpos( $startTime, $search ) === false )
                continue;

            $clientTime = $dataInf[ 'start_time_format' ];
            $text       = $clientTime . ( $dataInf[ 'weight' ] > 0 && $dataInf[ 'max_capacity' ] > 1 ? ' [ ' . ( int ) $dataInf[ 'weight' ] . '/' . ( int ) $dataInf[ 'max_capacity' ] . ' ]' : '' );

            $result = [
                'id'		   => $clientTime,
                'text'		   => $text,
                'max_capacity' => $dataInf[ 'max_capacity' ],
                'weight'       => $dataInf[ 'weight' ]
            ];

            $dataForReturn[] = apply_filters( 'bkntc_customer_panel_render_date_time', $result, $dataInf );
        }

        return $dataForReturn;
    }

    public function delete_profile()
    {
        if( ! CustomerPanelHelper::canUseCustomerPanel() )
        {
            return $this->response( false );
        }

        if( Helper::getOption('customer_panel_allow_delete_account', 'on', false) != 'on' )
        {
            return $this->response( false );
        }

        Customer::where('user_id', Permission::userId())->noTenant()->update([
            'user_id'		=>	null,
            'first_name'	=>	'[-] ID: ' . CustomerPanelHelper::myCustomer()->id,
            'last_name'		=>	'',
            'phone_number'	=>	'',
            'email'			=>	'',
            'birthdate'		=>	null,
            'gender'		=>	'',
            'notes'			=>	'',
            'profile_image'	=>	''
        ]);

	    require_once ABSPATH . 'wp-admin/includes/user.php';

        wp_logout();
        wp_delete_user( Permission::userId() );

        return $this->response( true, ['redirect_url' => site_url('/')] );
    }

	public function get_appointments_list()
	{
		$customerIds = CustomerPanelHelper::myCustomersIDs();
		if ( empty( $customerIds ) )
		{
			return $this->response( true, [
				'list_html'  =>  '',
				'payments_html'  =>  ''
			] );
		}

		$totalPricesSubQuery = AppointmentPrice::where('appointment_id', DB::field( Appointment::getField('id') ))->select('sum(price * negative_or_positive)');

		$appointments = Appointment::noTenant()
		                           ->leftJoin('service', 'name')
		                           ->leftJoin('staff', ['name', 'profile_image'])
		                           ->leftJoin('location', 'address')
		                           ->where(Appointment::getField('customer_id'), $customerIds)
		                           ->selectSubQuery($totalPricesSubQuery, 'total_price')
		                           ->orderBy(Appointment::getField('id')." desc")
		                           ->fetchAll();

		foreach ( $appointments AS $appointment )
		{
			Permission::setTenantId( $appointment->tenant_id );
			$allStatuses = Helper::getAppointmentStatuses();

			if ( array_key_exists($appointment->status, $allStatuses) )
			{
				$status = $allStatuses[$appointment->status];
			}
			else
			{
				$status = ['title' => $appointment->status, 'color' => '#000'];
			}

			$appointment->status_text = $status['title'];
			$appointment->status_color = $status['color'];
		}

		return $this->response( true, [
			'list_html'  =>  Helper::renderView( __DIR__ . '/view/appointments_list.php', [ 'appointments' => $appointments ] ),
			'payments_html'  =>  Helper::renderView( __DIR__ . '/view/payments_list.php', [ 'appointments' => $appointments ] )
		] );
	}

    public function get_available_dates()
    {
        if( ! CustomerPanelHelper::canUseCustomerPanel() )
        {
            return $this->response( false );
        }

        $appointment_id	    = Helper::_post('appointment_id', 0, 'int');
        $month			    = Helper::_post('current_month', '', 'string');
        $year			    = Helper::_post('current_year', '', 'string');
        $startDate = new \DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $startDate  = $startDate->format('Y-m-d');
        $endDate    = $endDate->modify('+1 months')->format('Y-m-d');

        $appointmentInf = Appointment::noTenant()->get( $appointment_id );


        if( ! $appointmentInf || ! in_array( $appointmentInf->customer_id, CustomerPanelHelper::myCustomersIDs() ) )
        {
            return $this->response( false );
        }

        $customer_id    = $appointmentInf->customer_id;
        $staff			= $appointmentInf->staff_id;
        $service		= $appointmentInf->service_id;

        if( Helper::isSaaSVersion() )
        {
            Permission::setTenantId( $appointmentInf->tenant_id );
        }

        if( Helper::getOption('customer_panel_allow_reschedule', 'on', false) != 'on' )
        {
            return $this->response( false );
        }

        $extras_arr = [];
        $appointmentExtras = AppointmentExtra::where('appointment_id', $appointmentInf->id)->fetchAll();
        foreach ( $appointmentExtras AS $extra )
        {
            $extra_inf = $extra->extra()->fetch();
            $extra_inf['quantity'] = $extra['quantity'];
            $extra_inf['customer'] = $customer_id;

            $extras_arr[] = $extra_inf;
        }

        $startDate = Date::dateSQL( $startDate );
        $endDate = Date::dateSQL( $endDate );

        $serviceInf  = apply_filters( 'bkntc_set_service_duration_frontend', Service::get( $service ), $appointmentInf->id );

        $calendarData = new CalendarService( $startDate , $endDate );
        $calendarData->setStaffId( $staff )
            ->setLocationId( $appointmentInf->location_id )
            ->setServiceInf( $serviceInf )
            ->setServiceExtras( $extras_arr )
            ->setExcludeAppointmentId( $appointmentInf->id )
            ->setShowExistingTimeSlots( true );
        $calendarData = $calendarData->getCalendar();

        $availableDates = array_keys( array_filter($calendarData['dates'], function ($item)
        {
            return ! empty($item);
        }));

        $format = Helper::isSaaSVersion() ? 'Y-m-d' : null;
        $availableDates = array_map(function ($availableDate) use($format){
           return Date::convertDateFormat($availableDate , $format );
        },$availableDates);

        return $this->response(true, [ 'available_dates' => $availableDates ] );
    }


}