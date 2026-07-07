<?php

namespace BookneticAddon\Invoices;

use BookneticAddon\Invoices\Backend\SaaSController;
use BookneticAddon\Invoices\Model\Invoice;
use BookneticApp\Config;
use BookneticApp\Providers\UI\MenuUI;
use BookneticAddon\Invoices\Backend\Ajax;
use BookneticAddon\Invoices\Backend\Controller;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\UI\TabUI;
use BookneticSaaS\Models\Tenant;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, InvoicesAddon::getAddonSlug() );
}

class InvoicesAddon extends AddonLoader
{
    public function init()
    {
	    Capabilities::registerTenantCapability( 'invoices', bkntc__('Invoices') );

	    if( ! Capabilities::tenantCan( 'invoices' ) )
		    return;

	    Capabilities::register('invoices' , bkntc__('Invoices') );
        Capabilities::register('invoices_add' , bkntc__('Add new') , 'invoices');
        Capabilities::register('invoices_edit' , bkntc__('Edit') , 'invoices');
        Capabilities::register('invoices_delete' , bkntc__('Delete') , 'invoices');
        Capabilities::register('appointments_invoices_tab', bkntc__('Invoices Tab') , 'appointments');

        Config::getShortCodeService()->addReplacer([ Listener::class , 'replaceShortCodes' ]);
        Config::getShortCodeService()->registerShortCodesLazily([ Listener::class, 'registerShortCodes' ]);

        //Templates Addon Hooks
        add_action( 'bkntc_template_base_fields', [ Listener::class, 'setTemplateField' ] );
        add_action( 'bkntc_template_field_labels', [ Listener::class, 'setTemplateFieldLabel' ] );
        add_action( 'bkntc_template_apply_template', [ Listener::class, 'applyTemplate' ] );
        add_action( 'bkntc_template_field_counts', [ Listener::class, 'templateFieldCount' ], 10, 2 );
        add_action( 'bkntc_template_fetch_template_data', [ Listener::class, 'fetchTemplateData' ] );
    }

    public function initBackend()
    {
	    if( ! Capabilities::tenantCan( 'invoices' ) )
		    return;

	    if( Capabilities::userCan('invoices') )
	    {
	        Route::get('invoices', new Controller( Config::getShortCodeService() ) );
	        Route::post('invoices', Ajax::class );

	        MenuUI::get( 'invoices' )
	            ->setTitle( bkntc__( 'Invoices' ) )
	            ->setIcon('fa fa-file-alt')
	            ->setPriority( 910 );
	    }

        if( Capabilities::userCan('appointments_invoices_tab') )
        {
            TabUI::get( 'appointments_info' )
                ->item( 'invoices' )
                ->setTitle( bkntc__( 'Invoices' ) )
                ->addView( __DIR__ . '/Backend/view/tabs/appointment_info_fields.php', [ Listener::class, 'addInfoTab' ] );
        }

        add_filter('bkntc_add_tables_for_export', [ self::class, 'getAddonTables' ]);
    }

    public function initSaaSBackend()
    {
		Tenant::onDeleting( [ Listener::class, 'beforeTenantDelete' ] );
    }

    public static function getAddonTables($tables)
    {
        $tables[] = Invoice::getTableName();

        return $tables;
    }

}
