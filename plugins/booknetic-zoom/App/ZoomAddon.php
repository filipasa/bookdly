<?php

namespace BookneticAddon\Zoom;

use BookneticAddon\Zoom\Backend\Ajax;
use BookneticApp\Providers\Helpers\Date;
use BookneticAddon\Zoom\Integration\ZoomService;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\Core\Route;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, ZoomAddon::getAddonSlug() );
}

class ZoomAddon extends AddonLoader
{

    public function init ()
    {
        Capabilities::registerTenantCapability( 'zoom', bkntc__('Zoom integration') );
		// doit: bu ashagidaki register kechmelidi tenantCan()-in ichine. Eyni shey boyuk ehtimal google calendarda daolacaq. Chunki tenantin accesi yoxdusa Zoom-a o demekdi ki, zooma aid hechneyi gormeyecek tenant. Sanki zoom addonu yoxdu onda. User Capabilitysi de olmamalidi.
        Capabilities::register( 'zoom_settings', bkntc__( 'Zoom settings' ), 'settings' );

        if( Helper::getOption( 'zoom_enable', 'off', false ) === 'off' )
        {
            add_filter( 'bkntc_tenant_capability_filter', function ( $can, $capability )
            {
                if( $capability == 'zoom' )
                    return false;

                return $can;
            }, 10, 2);
        }

        if( Capabilities::tenantCan( 'zoom' ) && Helper::getOption('zoom_enable', 'off') == 'on' )
        {
            Config::getShortCodeService()->addReplacer([ Listener::class , 'zoomShortCodes' ]);

            Config::getShortCodeService()->registerShortCode( 'zoom_meeting_url', [
                'name'      =>  bkntc__('Zoom meeting URL'),
                'category'  =>  'appointment_info',
                'depends'   =>  'appointment_id'
            ] );

            Config::getShortCodeService()->registerShortCode( 'zoom_meeting_host_url', [
                'name'      =>  bkntc__('Zoom meeting start URL'),
                'category'  =>  'appointment_info',
                'depends'   =>  'appointment_id'
            ] );

            Config::getShortCodeService()->registerShortCode( 'zoom_meeting_password', [
                'name'      =>  bkntc__('Zoom meeting password'),
                'category'  =>  'appointment_info',
                'depends'   =>  'appointment_id'
            ]);

            // Note:
            // Priority'ə 9'u elə belə yazmamışıq, Zoom GoogleCalendar-dan öncə işə düşməlidir.
            // beləliklə GoogleCalendar eventinin mətnində Zoom linki istifadə edilə biləcək
            // burda düzəliş edəndə belə halları düşünüb edin.

            add_action( 'bkntc_appointment_before_mutation' ,   [Listener::class , 'appointment_before_mutation']);
            add_action( 'bkntc_appointment_after_mutation' ,    [Listener::class , 'appointment_after_mutation'] , 9);

            add_filter( 'bkntc_after_request_staff_save_staff',     [ Listener::class, 'zoom_data_save_staff' ], 1, 1 );
            add_filter( 'bkntc_after_request_services_save_service',[ Listener::class, 'zoom_data_save_service' ], 1, 1 );
        }
    }

    public function initBackend ()
    {
        if( Capabilities::tenantCan( 'zoom' ) && Helper::getOption('zoom_enable', 'off') == 'on' )
        {
            Route::post('zoom', Ajax::class, ['connect_zoom', 'disconnect_zoom', 'fetch_zoom_users']);

            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );

            TabUI::get( 'staff_add' )
                ->item( 'details' )
                ->addView( __DIR__ . '/Backend/view/tabs/zoom_fields.php', [ Listener::class, 'add_zoom_row_to_staff_view' ] );

            TabUI::get( 'services_add' )
                ->item( 'details' )
                ->addView( __DIR__ . '/Backend/view/tabs/zoom_fields_services.php', [ Listener::class,'add_zoom_row_to_service_view' ]);
        }

        if ( (!Helper::isSaaSVersion() || Capabilities::tenantCan( 'zoom' )) && Capabilities::userCan( 'zoom_settings' ) )
        {
            Route::post('zoom_settings', Ajax::class, ['settings_view', 'save_settings']);

            SettingsMenuUI::get( 'integrations' )
                ->subItem( 'settings_view', 'zoom_settings' )
                ->setTitle( bkntc__( 'Zoom' ) );
        }
    }

    public function initSaaS()
    {

    }

    public function initSaaSBackend()
    {
        \BookneticSaaS\Providers\Core\Route::post('zoom', Ajax::class, ['settings_view_saas', 'save_settings_saas']);

        \BookneticSaaS\Providers\UI\SettingsMenuUI::get( 'integrations' )
            ->subItem( 'settings_view_saas', 'zoom' )
            ->setTitle( bkntc__('Zoom')  )
            ->setPriority( 17 );
    }

    public function initFrontend()
    {
        $bookneticAction = Helper::_get( 'booknetic_action', '', 'string' );

        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_style( 'customer_panel_frontend_zoom_integration', self::loadAsset( 'assets/frontend/css/customer_panel_zoom.css' ), ['booknetic-cp']);
        });

        if( $bookneticAction == 'zoom_oauth_callback' )
        {
            $state  = Helper::_get('state', '', 'string');
            $code   = Helper::_get('code', '', 'string');

            if( Session::get('zoom_state') !== $state )
            {
                return;
            }

            Session::delete('zoom_state');

            $result = ZoomService::getToken( $code );
            if( $result['status'] === false )
            {
                Helper::redirect( Route::getURL('settings') . '&setting=connect_zoom&success=false&msg=' . urlencode( $result['error'] ) );
            }

            Helper::redirect( Route::getURL('settings') . '&setting=connect_zoom&success=true' );
        }

        add_action( 'bkntc_customer_panel_appointment_actions', [ self::class, 'customerPanelMeetingButton' ] );
    }

    public static function customerPanelMeetingButton ( $appointmentId )
    {

        $zoomData = Data::where( 'table_name', Appointment::getTableName() )
            ->where( 'row_id', $appointmentId )
            ->where( 'data_key', 'zoom_meeting_data' )
            ->fetch();

		$appointment = Appointment::get( $appointmentId );
		$now = Date::epoch();
		
        if ( isset( $zoomData->data_value ) && $now <= $appointment->busy_to )
        {
            $zoomData = json_decode( $zoomData->data_value, true );

            if ( ! empty( $zoomData ) || is_array( $zoomData ) )
            {
                echo "<a href='" . $zoomData[ 'join_url' ] . "' target='_blank'><button class='booknetic_zoom_btn' title='Password: " . $zoomData[ 'password' ] . "'><i class='fa fa-video'></i></button></a>";
            }
        }
    }

    public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'staff' && $action == 'add_new' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/zoom_helper.js') . '"></script>';
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/zoom_staff.js') . '"></script>';
        }

        if( $module == 'services' && $action == 'add_new' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/zoom_services.js') . '"></script>';
        }

        if( $module == 'appointments' && $action == 'info' )
        {
            echo '<link rel="stylesheet" href="' . self::loadAsset('assets/backend/css/appointment_info_zoom.css') . '">';

            $zoomMeetingURL = self::getZoomMeetingURL();

            $_mn = Helper::_post('_mn');

            if ( ! empty( $zoomMeetingURL ) )
            {
                echo "<script>
                ( function ( $ ) {
                    $( document ).ready( function () {
                        $( '#FSModal" . $_mn . " .fs-modal-footer' ).prepend( '<a href=\'" . $zoomMeetingURL . "\' class=\'btn btn-lg btn-info zoom-meeting-btn\' target=\'_blank\'>" . bkntc__( 'START MEETING' ) . "</a>' );
                    } );
                } )( jQuery );
                </script>";
            }
        }
    }

    private static function getZoomMeetingURL ()
    {
        $appointmentID   = Helper::_post( 'id', '0', 'integer' );

        $zoomMeetingUrl  = '';
        $zoomMeetingData = Appointment::getData( $appointmentID, 'zoom_meeting_data' );

        if ( ! empty( $zoomMeetingData ) )
        {
            $zoomMeetingData = json_decode( $zoomMeetingData, true );

            if( isset( $zoomMeetingData[ 'start_url' ] ) && ! empty( $zoomMeetingData[ 'start_url' ] ) && is_string( $zoomMeetingData[ 'start_url' ] ) )
            {
                $zoomMeetingUrl = $zoomMeetingData[ 'start_url' ];
            }
        }

        return $zoomMeetingUrl;
    }

}