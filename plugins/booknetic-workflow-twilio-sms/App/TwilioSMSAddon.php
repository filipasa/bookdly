<?php

namespace BookneticAddon\TwilioSMS;

use BookneticApp\Config;
use BookneticAddon\TwilioSMS\Backend\Ajax;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;
use BookneticApp\Providers\Core\Route;

function bkntc__ ( $text, $params = [], $esc = true )
{
	return \bkntc__( $text, $params, $esc, TwilioSMSAddon::getAddonSlug() );
}

class TwilioSMSAddon extends AddonLoader
{

	public function init ()
	{
		Capabilities::registerTenantCapability( 'twilio_sms', bkntc__('Twilio SMS integration'), 'workflow'  );

		if( ! Capabilities::tenantCan( 'twilio_sms' ) )
			return;

	    Capabilities::register('twilio_sms_settings' , bkntc__('Twilio SMS settings') , 'settings');
		Capabilities::registerLimit( 'twilio_sms_allowed_max_number', bkntc__('Allowed maximum SMS [Twilio]') );

        Config::getWorkflowDriversManager()->register( new TwilioSMSWorkflowDriver() );
	}

	public function initBackend ()
	{
		if( ! Capabilities::tenantCan( 'twilio_sms' ) )
			return;

        $ajaxController = new Ajax(Config::getWorkflowEventsManager());

        Route::post( 'twilio_sms_workflow', $ajaxController, [ 'workflow_action_edit_view', 'workflow_action_save_data', 'workflow_action_send_test_data' ] );

        if ( ! Helper::isSaaSVersion() && Capabilities::userCan('twilio_sms_settings') )
        {
            Route::post( 'twilio_sms', $ajaxController, [ 'settings_view', 'save_settings' ] );

            SettingsMenuUI::get( 'integrations' )
                          ->subItem( 'settings_view', 'twilio_sms' )
                          ->setTitle( bkntc__( 'SMS Twilio' ) )
                          ->setPriority( 15 );
        }

        add_filter('bkntc_tenant_limits' , function ($limitsArr ,$currentPlanInf){
            $limits = json_decode($currentPlanInf->permissions,true)['limits'];

            if( ! array_key_exists('twilio_sms_allowed_max_number' , $limits )) return $limitsArr;

            $count = (new TwilioSMSWorkflowDriver())->getUsage();
            $limitsArr['twilio-sms'] = ['title'=>bkntc__('Twilio SMS') , 'current_usage'=>$count , 'max_usage'=>$limits['twilio_sms_allowed_max_number'] ];

            return $limitsArr;
        },10,2);
	}

    public function initSaaS()
    {
        \BookneticSaaS\Config::getWorkflowDriversManager()->register( new TwilioSMSWorkflowDriver() );
    }

    public function initSaaSBackend()
	{
        $ajaxController = new Ajax(\BookneticSaaS\Config::getWorkflowEventsManager());

        SaaSRoute::post( 'twilio_sms_workflow', $ajaxController, [ 'workflow_action_edit_view', 'workflow_action_save_data', 'workflow_action_send_test_data' ] );

        SaaSRoute::post( 'twilio_sms', $ajaxController, [ 'settings_view', 'save_settings' ] );

		\BookneticSaaS\Providers\UI\SettingsMenuUI::get( 'integrations' )
		              ->subItem( 'settings_view', 'twilio_sms' )
		              ->setTitle( bkntc__( 'SMS Twilio' ) )
		              ->setPriority( 15 );
	}

}