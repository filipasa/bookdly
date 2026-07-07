<?php

namespace BookneticAddon\TwilioWhatsapp;

use BookneticApp\Config;
use BookneticAddon\TwilioWhatsapp\Backend\Ajax;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticApp\Providers\Core\Route as RegularRoute;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, TwilioWhatsappAddon::getAddonSlug() );
}

class TwilioWhatsappAddon extends AddonLoader
{

	public function init ()
	{
		Capabilities::registerTenantCapability( 'twilio_whatsapp', bkntc__('Twilio WhatsApp integration'), 'workflow'  );

		if( ! Capabilities::tenantCan( 'twilio_whatsapp' ) )
			return;

		Capabilities::register('twilio_whatsapp_settings' , bkntc__('Twilio WhatsApp settings') , 'settings');
		Capabilities::registerLimit( 'twilio_whatsapp_allowed_max_number', bkntc__('Allowed maximum WhatsApp messages [Twilio]') );

        Config::getWorkflowDriversManager()->register( new TwilioWhatsappWorkflowDriver() );
	}

	public function initBackend ()
	{
		if( ! Capabilities::tenantCan( 'twilio_whatsapp' ) )
			return;

        $ajaxController = new Ajax(Config::getWorkflowEventsManager());

        RegularRoute::post( 'twilio_whatsapp_workflow', $ajaxController, [ 'workflow_action_edit_view', 'workflow_action_save_data', 'workflow_action_send_test_data' ] );

		if ( ! Helper::isSaaSVersion() && Capabilities::userCan('twilio_whatsapp_settings') )
		{
            RegularRoute::post( 'twilio_whatsapp', $ajaxController, [ 'settings_view', 'save_settings' ] );

			SettingsMenuUI::get( 'integrations' )
			              ->subItem( 'settings_view', 'twilio_whatsapp' )
			              ->setTitle( bkntc__( 'WhatsApp Twilio' ) )
			              ->setPriority( 16 );
		}

        add_filter('bkntc_tenant_limits' , function ($limitsArr ,$currentPlanInf){

            $limits = json_decode($currentPlanInf->permissions,true)['limits'];

            if( ! array_key_exists('twilio_whatsapp_allowed_max_number' , $limits )) return $limitsArr;

            $count = (new TwilioWhatsappWorkflowDriver())->getUsage();

            $limitsArr['twilio-whatsapp'] = ['title'=>bkntc__('Twilio Whatsapp') , 'current_usage'=>$count , 'max_usage'=>$limits['twilio_whatsapp_allowed_max_number'] ];

            return $limitsArr;
        },10,2);
	}

    public function initSaaS()
    {
        \BookneticSaaS\Config::getWorkflowDriversManager()->register( new TwilioWhatsappWorkflowDriver() );
    }

    public function initSaaSBackend()
	{
        $ajaxController = new Ajax(\BookneticSaaS\Config::getWorkflowEventsManager());

        SaaSRoute::post( 'twilio_whatsapp_workflow', $ajaxController, [ 'workflow_action_edit_view', 'workflow_action_save_data', 'workflow_action_send_test_data' ] );


        SaaSRoute::post( 'twilio_whatsapp', $ajaxController, [ 'settings_view', 'save_settings' ] );

		\BookneticSaaS\Providers\UI\SettingsMenuUI::get( 'integrations' )
		                                          ->subItem( 'settings_view', 'twilio_whatsapp' )
		                                          ->setTitle( bkntc__( 'WhatsApp Twilio' ) )
		                                          ->setPriority( 15 );
	}

}