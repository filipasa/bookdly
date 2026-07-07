<?php

namespace BookneticAddon\Coupons;

use BookneticAddon\Coupons\Model\Coupon;
use BookneticApp\Config;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\UI\MenuUI;
use BookneticAddon\Coupons\Backend\Controller;
use BookneticAddon\Coupons\Backend\Ajax;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Models\Tenant;
use BookneticApp\Backend\Settings\Helpers\LocalizationService;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, CouponsAddon::getAddonSlug() );
}

class CouponsAddon extends AddonLoader
{

    public function init()
    {
	    Capabilities::registerTenantCapability('coupons', bkntc__('Coupons'));

	    if( ! Capabilities::tenantCan( 'coupons' ) )
		    return;

        add_action( 'bkntc_appointment_requests_load',      [ Listener::class, 'init_coupons' ] );
        add_action( 'bkntc_appointment_created', [ Listener::class, 'appointment_insert_data_coupon' ] );

        Capabilities::register('coupons', bkntc__('Coupons'));
        Capabilities::register('coupons_add', bkntc__('Add New') , 'coupons');
        Capabilities::register('coupons_edit', bkntc__('Edit') , 'coupons');
        Capabilities::register('coupons_delete', bkntc__('Delete') , 'coupons');
        Capabilities::register('appointments_coupons_tab', bkntc__('Coupons tab') , 'appointments');

        $this->registerShortCodes();
        Config::getShortCodeService()->addReplacer([ Listener::class, 'replace_short_code_text' ]);
    }

    public function initBackend()
    {
		if( ! Capabilities::tenantCan( 'coupons' ) )
			return;

        Route::post('coupons', Ajax::class );

        if( Capabilities::userCan('coupons') )
        {
            Route::get('coupons', Controller::class );

            MenuUI::get( 'coupons' )
                  ->setTitle( bkntc__( 'Coupons' ) )
                  ->setIcon('fa fa-tag')
                  ->setPriority( 810 );

            TabUI::get('settings_booking_steps')
                ->item('confirm')
                ->addView( __DIR__ . '/Backend/view/tabs/coupon_section.php' );
        }

        if ( Capabilities::userCan('appointments_coupons_tab') )
        {
            add_action( 'bkntc_appointment_after_edit', [ Listener::class, 'appointment_insert_data_coupon' ] );

            TabUI::get( 'appointments_add_new' )
                 ->item( 'coupons' )
                 ->setTitle( bkntc__( 'Coupons' ) )
                 ->addView( __DIR__ . '/Backend/view/tabs/appointment_add_edit_modal.php' );

            TabUI::get( 'appointments_info' )
                 ->item( 'coupons' )
                 ->setTitle( bkntc__( 'Coupons' ) )
                 ->addView( __DIR__ . '/Backend/view/tabs/appointment_info_modal.php', [ Listener::class,  'add_info_tab' ] );

            TabUI::get( 'appointments_edit' )
                 ->item( 'coupons' )
                 ->setTitle( bkntc__( 'Coupons' ) )
                 ->addView( __DIR__ . '/Backend/view/tabs/appointment_add_edit_modal.php' );
        }

        Service::onDeleted( function ( $serviceId )
        {
            DB::DB()->query( DB::DB()->prepare("UPDATE `".DB::table('coupons')."` SET services=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`services`,','),%s,'')) WHERE FIND_IN_SET(%d, `services`)", [",{$serviceId},", $serviceId]) );
        });

        Staff::onDeleted( function ( $staffId )
        {
            DB::DB()->query( DB::DB()->prepare("UPDATE `".DB::table('coupons')."` SET staff=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`staff`,','),%s,'')) WHERE FIND_IN_SET(%d, `staff`)", [",{$staffId},", $staffId]) );
        });

        add_filter('settings_booking_panel_labels_load' , function ($result){
            $result['Coupon'] = bkntc__('Coupon');
            return $result;
        });

        add_filter( 'bkntc_labels_settings_translates', function ( $translates ) {
            $translates[ 'other_translates' ][ 'Coupon' ] = bkntc__( 'Coupon' );
            return $translates;
        } );

        add_filter( 'bkntc_save_booking_labels_settings', function (  $translates , $language) {
            LocalizationService::saveFiles( $language, [ 'Coupon' => $translates[ 'Coupon' ] ] , CouponsAddon::getAddonSlug() );
            unset( $translates[ 'Coupon' ] );
            return $translates ;
        }, 10, 2 );

        add_filter('bkntc_add_tables_for_export', [ self::class, 'getAddonTables' ]);
    }

    public function initFrontend()
    {
	    if( ! Capabilities::tenantCan( 'coupons' ) )
		    return;

	    if( ! ( Helper::getOption('hide_coupon_section', 'off') == 'on' ) )
        {
            $this->setFrontendAjaxController( Frontend\Ajax::class );

            add_action('bkntc_after_booking_panel_shortcode', function ()
            {
                wp_enqueue_script( 'booknetic-coupons-init', CouponsAddon::loadAsset( 'assets/frontend/js/init.js'), [ 'booknetic' ] );
                wp_enqueue_style( 'booknetic-coupons-init', CouponsAddon::loadAsset( 'assets/frontend/css/coupon.css' ), [ 'booknetic' ] );
            });
        }

        add_filter( 'bkntc_frontend_localization', function ( $localization ) {
            $localization[ 'coupon' ] = bkntc__( 'Coupon' );
            $localization[ 'coupon_ok_btn' ] = bkntc__( 'OK' );
            $localization[ 'coupon_cancel_btn' ] = bkntc__( 'Cancel' );
            return $localization;
        } );

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjax' ] );
    }

    public function registerShortCodes()
    {
        Config::getShortCodeService()->registerShortCode( 'coupon_code', [
            'name'      =>  bkntc__('Coupon Code'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id',
        ]);
    }

    public function initSaaSBackend()
    {
		Tenant::onDeleting( [ Listener::class, 'beforeTenantDelete' ] );
    }

    public static function addFilesThroughAjax ( $result )
    {
        $result[ 'files' ] = array_merge( $result[ 'files' ], [
            [
                'type' => 'js',
                'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
                'id'   => 'booknetic-coupons-init',
            ],
            [
                'type' => 'css',
                'src'  => self::loadAsset( 'assets/frontend/css/coupon.css' ),
                'id'   => 'booknetic-coupons-init',
            ],
        ] );

        return $result;
    }

    public static function getAddonTables($tables)
    {
        $tables[] = Coupon::getTableName();

        return $tables;
    }
}
