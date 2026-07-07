<?php

namespace BookneticAddon\Customerpanel;

use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class CustomerPanelHelper
{

	private static $myCustomer;

	public static function canUseCustomerPanel()
	{
		if( Helper::getOption('customer_panel_enable', 'off') != 'on' )
			return false;

		if( ! self::myCustomer() )
			return false;

		return true;
	}

	public static function myCustomer()
	{
		if( is_null( self::$myCustomer ) )
		{
			$userId = Permission::userId();
			if ( $userId > 0 )
			{
				self::$myCustomer = Customer::where('user_id', $userId)->noTenant()->fetch();
				if ( ! self::$myCustomer )
				{
					$user = wp_get_current_user();
					if ( $user && $user->exists() )
					{
						$firstName = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
						$lastName = ! empty( $user->last_name ) ? $user->last_name : '';
						
						global $wpdb;
						$wpdb->insert( $wpdb->prefix . 'bkntc_customers', [
							'user_id'    => $userId,
							'email'      => $user->user_email,
							'first_name' => $firstName,
							'last_name'  => $lastName,
							'tenant_id'  => 0
						] );
						
						self::$myCustomer = Customer::where('user_id', $userId)->noTenant()->fetch();
					}
				}
			}
		}

		return self::$myCustomer;
	}

	public static function myCustomersIDs()
	{
		$customers  = Customer::where( 'user_id', Permission::userId() )->noTenant()->fetchAll();
		$customerIds = [];

		foreach ( $customers AS $customerInf )
		{
			$customerIds[] = $customerInf->id;
		}

		return $customerIds;
	}

	public static function canRescheduleAppointment( $appointment )
	{

        $allowedRescheduleStatuses = Helper::getOption('customer_panel_reschedule_allowed_status', '');
        $allowedRescheduleStatuses = explode(',', $allowedRescheduleStatuses);

		if ( Helper::getOption('customer_panel_allow_reschedule', 'on') != 'on' )
			return false;

        if ( !in_array($appointment->status, $allowedRescheduleStatuses ) && !empty($allowedRescheduleStatuses) )
            return false;

		if(Date::epoch() >= Date::epoch($appointment->starts_at))
			return false;

		$minute = Helper::getOption('time_restriction_to_make_changes_on_appointments', '5');

		if(Date::epoch('+'.$minute.' minutes') > Date::epoch($appointment->starts_at))
			return false;

		return true;
	}

	public static function canCancelAppointment( $appointment )
	{
		if ( Helper::getOption('customer_panel_allow_cancel', 'off') != 'on' )
			return false;

		if ( in_array($appointment->status, ['canceled', 'rejected']) )
			return false;

		if(Date::epoch() >= Date::epoch($appointment->starts_at))
			return false;

		$minute = Helper::getOption('time_restriction_to_make_changes_on_appointments', '5');

		if(Date::epoch('+'.$minute.' minutes') > Date::epoch($appointment->starts_at))
			return false;

		return true;
	}

	public static function canChangeAppointmentStatus( $appointment )
	{
		$allStatuses    = Helper::getAppointmentStatuses();
		$statuses       = Helper::getOption( 'customer_panel_allowed_status', '' );
		$statusesArray  = explode(',', $statuses);
		$minute = Helper::getOption('time_restriction_to_make_changes_on_appointments', '0');

		$statusesArray  = array_filter($statusesArray, function ($item) use ($allStatuses, $appointment)
		{
			if (empty($item))
				return false;

			if (!array_key_exists($item, $allStatuses))
				return false;

			if ($item == $appointment->status)
				return false;

			return true;
		});

		if ( Date::epoch('+'. $minute . ' minutes') >= Date::epoch($appointment->starts_at) )
		{
			$statusesArray = [];
		}

		return count( $statusesArray ) > 0;
	}

    public static function customerPanelURL()
    {
        $customerPanelPageID = Helper::getOption('customer_panel_page_id', '');

        if( empty( $customerPanelPageID ) )
            return '';

        return get_page_link( (int)$customerPanelPageID );
    }

    public static function getPreviousBookingTenantID()
    {
        global $wpdb;
        $userId = Permission::userId();
        if ( ! $userId ) {
            return null;
        }

        // 1. Get customer IDs for this user
        $customers = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, tenant_id FROM {$wpdb->prefix}bkntc_customers WHERE user_id = %d",
            $userId
        ), ARRAY_A );

        if ( empty( $customers ) ) {
            return null;
        }

        $customerIds = array_column( $customers, 'id' );

        // 2. Query the most recent appointment to find the last tenant they booked with
        $customerIdsPlaceholder = implode( ',', array_map( 'intval', $customerIds ) );
        $lastAppointment = $wpdb->get_row(
            "SELECT tenant_id FROM {$wpdb->prefix}bkntc_appointments WHERE customer_id IN ($customerIdsPlaceholder) ORDER BY id DESC LIMIT 1",
            ARRAY_A
        );

        if ( ! empty( $lastAppointment['tenant_id'] ) ) {
            return (int) $lastAppointment['tenant_id'];
        }

        // 3. Fallback to the tenant_id of their first customer account
        return (int) $customers[0]['tenant_id'];
    }

    public static function getCompanyLink()
    {
        $tenantId = self::getPreviousBookingTenantID();
        if ( $tenantId > 0 ) {
            return site_url( 'book-now/' ) . '?bkntc_page_id=' . $tenantId;
        }

        $companyLink = Helper::getOption( 'company_website', '' );

        if ( ! empty( $companyLink ) )
            return htmlspecialchars( $companyLink );

        return site_url() . '/' . (Permission::tenantInf() ? htmlspecialchars( Permission::tenantInf()->domain ) : '');
    }
}