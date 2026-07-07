<?php

namespace BookneticAddon\Tax;

use BookneticAddon\Tax\Backend\Ajax;
use BookneticAddon\Tax\Model\Tax;
use BookneticApp\Config;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\MenuUI;
use BookneticAddon\Tax\Backend\Controller;
use BookneticAddon\Tax\Listener;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\UI\TabUI;
use BookneticSaaS\Models\Tenant;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, TaxAddon::getAddonSlug() );
}

class TaxAddon extends AddonLoader
{

	public function init ()
	{
		Capabilities::registerTenantCapability( 'tax', bkntc__('Tax module') );

		add_filter( 'bkntc_price_name' , [ Listener::class, 'priceName' ]);

		if( ! Capabilities::tenantCan( 'tax' ) )
			return;

		Capabilities::register( 'tax', bkntc__('Tax') );
		Capabilities::register( 'tax_add', bkntc__('Add new'), 'tax' );
		Capabilities::register( 'tax_edit', bkntc__('Edit'), 'tax' );
		Capabilities::register( 'tax_delete', bkntc__('Delete'), 'tax' );

		add_action( 'bkntc_appointment_requests_load', [ Listener::class, 'calculateTax' ], 100 );

        //Templates Addon Hooks
        add_action( 'bkntc_template_base_fields', [ Listener::class, 'setTemplateField' ] );
        add_action( 'bkntc_template_field_labels', [ Listener::class, 'setTemplateFieldLabel' ] );
        add_action( 'bkntc_template_apply_template', [ Listener::class, 'applyTemplate' ] );
        add_action( 'bkntc_template_field_counts', [ Listener::class, 'templateFieldCount' ], 10, 2 );
        add_action( 'bkntc_template_fetch_template_data', [ Listener::class, 'fetchTemplateData' ] );

        add_filter( 'bkntc_datatable_after_render', [ Listener::class , 'paymentExportCsv'] , 10 , 2);

        Config::getShortCodeService()->addReplacer([ Listener::class, 'replaceShortCodeText' ]);
        Config::getShortCodeService()->registerShortCodesLazily([ Listener::class, 'registerShortCodes' ]);
	}

    public function initFrontend()
    {
        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_style( 'booknetic-tax-init', self::loadAsset('assets/frontend/css/tax.css'), ['booknetic'] );

            if ( Helper::getOption('hide_accordion_default', 'off') == 'on' && Helper::getOption('hide_tax_excluded_text' ,'on') != 'on' )
            {
                wp_enqueue_script('booknetic-tax-position', self::loadAsset('assets/frontend/js/init.js'), ['booknetic'] );
            }

        });

        add_action( 'bkntc_service_step_footer',               [ Listener::class, 'frontend_render_ui'] );
        add_action( 'bkntc_service_extras_step_footer',        [ Listener::class, 'frontend_render_ui'] );

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjax' ] );
    }

	public function initBackend ()
	{
		if( ! Capabilities::tenantCan( 'tax' ) )
			return;

		if( Capabilities::userCan('tax'))
        {
            Route::get( 'tax', Controller::class );
            Route::post( 'tax', Ajax::class );

            MenuUI::get( 'tax' )
                ->setTitle( bkntc__( 'Taxes' ) )
                ->setIcon('fa fa-percent')
                ->setPriority( 830 );

            TabUI::get('payment_settings')
                ->item('tax')
                ->addView( __DIR__ . '/Backend/view/tab/tax_section.php' );

            add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );
            add_filter('bkntc_after_request_settings_save_payments_settings' , function ( $arr )
            {
                Helper::setOption('hide_tax_excluded_text' , Helper::_post('hide_tax_excluded_text' , 'off' ,'string' ,['on' ,'off'] ));
                return $arr;
            });

            add_filter('bkntc_add_tables_for_export', [ self::class, 'getAddonTables' ]);
        }
	}

    public static function enqueueAssets ( $module, $action )
    {
        if( $module == 'settings' && $action == 'payments_settings' )
        {
            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/tax_helper.js') . '"></script>';
        }
    }

    public function initSaaSBackend()
    {
		Tenant::onDeleting( [ Listener::class, 'beforeTenantDelete' ] );
    }

    public static function addFilesThroughAjax ( $result )
    {
        $result[ 'files' ][] = [
            'type' => 'css',
            'src'  => self::loadAsset( 'assets/frontend/css/tax.css' ),
            'id'   => 'booknetic-tax-init',
        ];

        if ( Helper::getOption('hide_accordion_default', 'off') == 'on' && Helper::getOption('hide_tax_excluded_text' ,'on') != 'on' )
        {
            $result[ 'files' ][] = [
                'type' => 'js',
                'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
                'id'   => 'booknetic-tax-position',
            ];
        }

        return $result;
    }

    public static function getAddonTables($tables)
    {
        $tables[] = Tax::getTableName();

        return $tables;
    }
}