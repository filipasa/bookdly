<?php

namespace BookneticAddon\Googlecalendar;

use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticAddon\Googlecalendar\Backend\Ajax;
use BookneticAddon\Googlecalendar\Model\StaffBusySlot;
use BookneticAddon\Googlecalendar\Integration\GoogleCalendarService;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\UI\TabUI;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, GoogleCalendarAddon::getAddonSlug() );
}

class GoogleCalendarAddon extends AddonLoader
{

	public function init()
	{
        add_action( 'bkntc_cronjob', [ Listener::class , 'cronjob_google_calendar' ] );

		Capabilities::registerTenantCapability('google_calendar', bkntc__('Google Calendar integration'));
        Capabilities::register( 'google_calendar_settings', bkntc__( 'Google Calendar settings' ), 'settings' );

        if( Helper::getOption( 'google_calendar_enable', 'off', false ) === 'off' )
        {
            add_filter( 'bkntc_tenant_capability_filter', function ( $can, $capability )
            {
                if( $capability == 'google_calendar' )
                    return false;

                return $can;
            }, 10, 2);
        }

		if( Capabilities::tenantCan( 'google_calendar' ) )
        {
            add_action( 'bkntc_appointment_before_mutation',    [ Listener::class, 'bkntc_appointment_before_mutation' ] );
            add_action( 'bkntc_appointment_after_mutation',     [ Listener::class, 'bkntc_appointment_after_mutation' ] );

            add_filter( 'bkntc_busy_slots',                 [ Listener::class , 'merge_busy_slots_google_calendar' ], 10, 2 );
            add_filter( 'bkntc_calendar_events',            [ Listener::class , 'merge_google_calendar_events' ], 10, 4 );

            add_filter( 'bkntc_after_request_staff_save_staff', [ Listener::class, 'save_staff_google_calendar' ], 1, 1 );
        }

        Staff::onDeleted( function ( $id ) {
            StaffBusySlot::where( 'staff_id', $id )->delete();
        } );

        add_filter( 'bkntc_localization' , function ( $lang )
        {
            return array_merge(
                [
                    'google_calendar' => bkntc__('Google Calendar')
                ],
                $lang
            );
        });
    }

	public function initSaaS()
	{

	}

	public function initBackend()
	{
        if ( ! Helper::isSaaSVersion() && Capabilities::userCan( 'google_calendar_settings' ) )
        {
            Route::post( 'googlecalendar_settings', Ajax::class, ['settings_view', 'settings_save'] );

            SettingsMenuUI::get( 'integrations' )
                ->subItem( 'settings_view', 'googlecalendar_settings' )
                ->setTitle( bkntc__('Google calendar') )
                ->setPriority( 18 );
        }

		if( Capabilities::tenantCan( 'google_calendar' ) )
        {
            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );

            Route::post( 'googlecalendar', Ajax::class, ['login_google_account', 'logout_google_account', 'fetch_google_calendars', 'save_calendar_module_settings'] );

            TabUI::get( 'staff_add' )
                ->item( 'details' )
                ->addView( __DIR__ . '/Backend/view/tabs/google_calendar_fields.php', [Listener::class,'add_calendar_row_to_staff_view']);

            self::googleAuth();
        }
	}

	public function initSaaSBackend()
	{
		\BookneticSaaS\Providers\Core\Route::post( 'googlecalendar_settings', Ajax::class, ['settings_view', 'settings_save'] );

		\BookneticSaaS\Providers\UI\SettingsMenuUI::get( 'integrations' )
		              ->subItem( 'settings_view', 'googlecalendar_settings' )
		              ->setTitle( bkntc__('Google calendar') )
		              ->setPriority( 18 );
	}

	public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'staff' && ( $action == 'add_new' || $action == 'edit' ) )
        {
            echo '<link rel="stylesheet" href="' . self::loadAsset('assets/css/google_calendar.css') . '">';
            echo '<script type="application/javascript" src="' . self::loadAsset( 'assets/js/google_calendar_helper.js' ) . '"></script>';
            echo '<script type="application/javascript" src="' . self::loadAsset( 'assets/js/google_calendar_staff.js' ) . '"></script>';
        }


	    if( $module == 'calendar' && $action == 'index' )
	    {
		    echo '<script type="application/javascript">var google_calendar_enabled = ' . json_encode( Session::get('show_gc_events', 'off') ) . '</script>';
		    echo '<script type="application/javascript" src="' . self::loadAsset( 'assets/js/google_calendar_calendar.js' ) . '"></script>';
	    }
    }

    public function googleAuth()
    {
        $google = Helper::_get('google', '', 'string', ['true']);

        if( empty( $google ) )
            return;

        $staff_id = (int)Session::get('google_staff_id');

        if( empty( $staff_id ) )
            return;

        Session::delete('google_staff_id');

        $googleService = new GoogleCalendarService();
        $access_token = $googleService->fetchAccessToken();

        if( empty( $access_token ) || $access_token == 'null' )
            return;

		Staff::setData( $staff_id, 'google_access_token', $access_token );

        Helper::redirect('admin.php?page=' . Helper::getSlugName() . '&module=staff&edit=' . $staff_id);
    }

}