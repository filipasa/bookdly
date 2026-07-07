<?php

namespace BookneticAddon\Reports;

use BookneticApp\Providers\UI\MenuUI;
use BookneticAddon\Reports\Backend\Ajax;
use BookneticAddon\Reports\Backend\Controller;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, ReportsAddon::getAddonSlug() );
}

class ReportsAddon extends AddonLoader
{

    public function init ()
    {
	    Capabilities::registerTenantCapability( 'reports', bkntc__('Reports module') );

	    if( ! Capabilities::tenantCan( 'reports' ) )
		    return;

	    Capabilities::register( 'reports', bkntc__('Reports'));

	    add_filter( 'bkntc_localization' , function ($lang){
	        return array_merge(
	            ['appointment_count'					        => bkntc__('Appointment count')],
                $lang
            );
        });
    }

    public function initBackend ()
    {
	    if( ! Capabilities::tenantCan( 'reports' ) )
		    return;

	    if( Capabilities::userCan('reports') )
        {
            Route::get( 'reports', Controller::class );
            Route::post( 'reports', Ajax::class );

            MenuUI::get( 'reports' )
                ->setTitle( bkntc__( 'Reports' ) )
                ->setIcon('fa fa-chart-line')
                ->setPriority( 110 );
        }
    }

}