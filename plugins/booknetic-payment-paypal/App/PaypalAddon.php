<?php

namespace BookneticAddon\PaypalPaymentGateway;

use BookneticAddon\PaypalPaymentGateway\Backend\Ajax;
use BookneticAddon\PaypalPaymentGateway\Handler\PaypalSetupHandler;
use BookneticAddon\PaypalPaymentGateway\Handler\PaypalVerifyHandler;
use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticAddon\PaypalPaymentGateway\Integration\PaypalSplit;
use BookneticAddon\PaypalPaymentGateway\PaypalSplitGateway;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use BookneticSaaS\Providers\Core\Permission as SaaSPermission;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Providers\UI\TabUI as SaaSTabUI;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, PaypalAddon::getAddonSlug() );
}

class PaypalAddon extends AddonLoader
{

	public function init ()
	{
		Capabilities::registerTenantCapability( 'paypal', bkntc__('Paypal integration') );

		if( ! Capabilities::tenantCan( 'paypal' ) )
			return;

        Capabilities::register('paypal_settings' , bkntc__('Paypal settings') , 'settings');

		Paypal::load();
	}

	public function initBackend ()
	{
        if( ! Capabilities::tenantCan( 'paypal' ) && ! Capabilities::tenantCan( 'paypal_split' ) )
			return;

		if( Capabilities::userCan('paypal_settings') )
        {
            TabUI::get('payment_gateways_settings')
                ->item('paypal')
                ->setTitle('Paypal')
                ->addView( __DIR__ . '/Backend/view/settings.php' );

            add_filter( 'bkntc_after_request_settings_save_payment_gateways_settings',  [ Listener::class , 'saveSettings' ]);
            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );
        }

        if ( Helper::isSaaSVersion() && Capabilities::tenantCan( 'paypal_split' ) )
        {
            Route::post('paypal_split_settings', Ajax::class);

            add_action('bkntc_before_request_settings_payment_gateways_settings', function()
            {
                $chain = new PaypalSetupHandler();
                $chain->nextChain( new PaypalVerifyHandler() );

                $paypalSplit = new PaypalSplit();

                $paypalSplit->setChain( $chain );

                $paypalSplit->checkTenant( Permission::tenantInf() );

                TabUI::get( 'payment_gateways_settings' )
                    ->item( 'paypal_split' )
                    ->setTitle( 'Paypal Split' )
                    ->addView( __DIR__ . '/Backend/view/split_payments/' . PaypalSplitHelper::getView(), PaypalSplitHelper::getParams() );
            });
        }

    }

    public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'settings' && $action == 'payment_gateways_settings' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/paypal-settings.js') . '"></script>';
        }
    }

	public function initFrontend()
	{
		if( ! Capabilities::tenantCan( 'paypal' ) )
			return;

		Listener::checkPaypalCallback();

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-paypal-init', PaypalAddon::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic' ] );
        });
        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-paypal-init', self::loadAsset('assets/frontend/js/init.js' ), [ 'booknetic-cp' ] );
        });

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjax' ] );
    }

    public function initSaaS()
    {
        SaaSPermission::enableSplitPayments();

        Capabilities::registerTenantCapability( 'paypal_split', bkntc__('Paypal Split Payments') );

        if ( ! Capabilities::tenantCan( 'paypal_split' ) )
            return;

        Capabilities::register( 'paypal_split_settings', bkntc__('Paypal Split Payments'), 'settings' );

        PaypalSplitGateway::load();

    }

    public function initSaaSBackend()
    {
        SaaSTabUI::get('payment_split_payments_settings')
            ->item('paypal_split')
            ->setTitle('Paypal Multiparty')
            ->addView( __DIR__ . '/Backend/view/split_payments/settings_saas.php' );

        add_filter( 'bkntcsaas_after_request_settings_save_payment_split_payments_settings',  [ Listener::class , 'saveSplitSettings' ]);
    }

    public function initSaaSFrontend()
    {
        Listener::verifyPaypalSplitWebhook();
        Listener::checkPaypalSplitSetupCallback();
        Listener::checkPaypalSplitPaymentCallback();

        if ( ! Capabilities::tenantCan( 'paypal_split' ) )
            return;

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-paypal-init-split', self::loadAsset('assets/frontend/js/init-paypal-split.js' ), [ 'booknetic' ] );
        });
        add_action('bkntc_after_customer_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-paypal-init-split', self::loadAsset('assets/frontend/js/init-paypal-split.js' ), [ 'booknetic-cp' ] );
        });

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjaxSaaS' ] );
    }

    public static function addFilesThroughAjax ( $result )
    {
        $result[ 'files' ][] = [
            'type' => 'js',
            'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
            'id'   => 'booknetic-paypal-init',
        ];

        return $result;
    }

    public static function addFilesThroughAjaxSaaS ( $result )
    {
        $result[ 'files' ][] = [
            'type' => 'js',
            'src'  => self::loadAsset( 'assets/frontend/js/init-paypal-split.js' ),
            'id'   => 'booknetic-paypal-init-split',
        ];

        return $result;
    }
}