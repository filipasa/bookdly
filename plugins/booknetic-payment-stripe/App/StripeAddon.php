<?php


namespace BookneticAddon\StripePaymentGateway;

use BookneticAddon\StripePaymentGateway\Backend\Ajax;
use BookneticAddon\StripePaymentGateway\Handler\StripeRegisterHandler;
use BookneticAddon\StripePaymentGateway\Handler\StripeSetupHandler;
use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticAddon\StripePaymentGateway\Integration\StripeConnect;
use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Core\Permission as SaaSPermission;
use BookneticSaaS\Providers\UI\TabUI as SaaSTabUI;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\UI\TabUI;

function bkntc__($text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, StripeAddon::getAddonSlug() );
}

class StripeAddon extends AddonLoader
{
    public function init()
    {
	    Capabilities::registerTenantCapability( 'stripe', bkntc__('Stripe integration') );

	    if( ! Capabilities::tenantCan( 'stripe' ) )
		    return;

        Capabilities::register('stripe_settings' , bkntc__('Stripe settings') , 'settings');

        Stripe::load();
    }

    public function initBackend()
    {
        Route::post('stripe_connect_settings', Ajax::class, [ 'generate_register_link', 'generate_verify_link', 'generate_login_link' ]);

        if( ! Capabilities::tenantCan( 'stripe' ) && ! Capabilities::tenantCan( 'stripe_connect' ) )
		    return;

	    if( ( ! Helper::isSaaSVersion() || Capabilities::tenantCan( 'stripe' ) ) && Capabilities::userCan('stripe_settings') )
        {
            TabUI::get('payment_gateways_settings')
                ->item('stripe')
                ->setTitle('Stripe')
                ->addView( __DIR__ . '/Backend/view/settings.php' );

            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );
            add_filter( 'bkntc_after_request_settings_save_payment_gateways_settings',  [ Listener::class , 'saveSettings' ]);
        }

        if( Helper::isSaaSVersion() && Capabilities::tenantCan('stripe_connect') )
        {
            add_action('bkntc_before_request_settings_payment_gateways_settings', function()
            {
                $chain = new StripeSetupHandler();
                $chain->nextChain( new StripeRegisterHandler() );

                $stripeConnect = new StripeConnect();
                $stripeConnect->setChain( $chain );

                $stripeConnect->checkTenant( StripeConnectHelper::getTenantInf() );

                TabUI::get('payment_gateways_settings')
                    ->item('stripe_split')
                    ->setTitle('Stripe Connect')
                    ->addView( __DIR__ . '/Backend/view/connect/' . StripeConnectHelper::getView(), StripeConnectHelper::getParams() );
            });
        }
    }

    public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'settings' && $action == 'payment_gateways_settings' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/stripe-settings.js') . '"></script>';
        }
    }

    public function initFrontend()
    {
        if ( Capabilities::tenantCan('stripe_connect') )
        {
            Listener::checkStripeConnectCallback();
        }

	    if( ! Capabilities::tenantCan( 'stripe' ) )
		    return;

	    Listener::checkStripeCallback();

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-stripe-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic' ] );
        });
        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-stripe-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic-cp' ] );
        });

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjax' ] );
    }

    public function initSaaS()
    {
        SaaSPermission::enableSplitPayments();

        Capabilities::registerTenantCapability( 'stripe_connect', bkntc__('Stripe Connect integration') );

        if( ! Capabilities::tenantCan( 'stripe_connect' ) )
            return;

        Capabilities::register('stripe_connect_settings' , bkntc__('Stripe Connect settings') , 'settings');

        StripeConnectGateway::load();
    }


    public function initSaaSBackend()
    {
        SaasRoute::post('stripe_connect_settings', Ajax::class, [ 'connected_tenants_saas', 'delete_connected_tenant_account' ]);

        SaaSTabUI::get('payment_split_payments_settings')
            ->item('stripe_split')
            ->setTitle('Stripe Connect')
            ->addView( __DIR__ . '/Backend/view/modal/connect_settings_saas.php' );

        add_filter( 'bkntcsaas_after_request_settings_save_payment_split_payments_settings', [ Listener::class , 'saveSplitSettings' ]);
    }


    public function initSaaSFrontend()
    {
        if( ! Capabilities::tenantCan( 'stripe_connect' ) )
            return;

        Listener::checkStripeConnectSetupCallback();

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-stripe-init-split', self::loadAsset('assets/frontend/js/init-stripe-connect.js' ), [ 'booknetic' ] );
        });
        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-stripe-init-split', self::loadAsset('assets/frontend/js/init-stripe-connect.js' ), [ 'booknetic-cp' ] );
        });

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjaxSaaS' ] );
    }

    public static function addFilesThroughAjax ( $result )
    {
        $result[ 'files' ][] = [
            'type' => 'js',
            'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
            'id'   => 'booknetic-stripe-init',
        ];

        return $result;
    }

    public static function addFilesThroughAjaxSaaS ( $result )
    {
        $result[ 'files' ][] = [
            'type' => 'js',
            'src'  => self::loadAsset( 'assets/frontend/js/init-stripe-connect.js' ),
            'id'   => 'booknetic-stripe-init-split',
        ];

        return $result;
    }

}