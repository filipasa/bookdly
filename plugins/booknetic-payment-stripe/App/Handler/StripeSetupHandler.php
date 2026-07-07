<?php

namespace BookneticAddon\StripePaymentGateway\Handler;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticAddon\StripePaymentGateway\Integration\StripeConnect;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class StripeSetupHandler extends StripeConnect
{

    private static $view = 'connect_setup_settings.php';
    private $setupState = false;

    public function handleTenant($tenantInf)
    {
        $stripeConnect = StripeConnectHelper::getInstance();

        if ( ! $stripeConnect->checkApiStatus() )
        {
            StripeConnectHelper::setView('connect_settings_error.php');
            return false;
        }

        $stripeConnectedAccounts = $stripeConnect->getAllStripeAccounts();
        
        if ( empty( $stripeConnectedAccounts ) )
        {
            $this->prepareSetup( $tenantInf );
        }

        foreach ( $stripeConnectedAccounts as $account )
        {
            if ( isset($account->metadata['tenantId']) && $tenantInf->id == $account->metadata->tenantId)
            {
                $checkDB = Tenant::getData( $tenantInf->id, 'stripe_connect_account_id' );

                if ( empty( $checkDB ) || $checkDB != $account->metadata->tenantId )
                {
                    Tenant::setData($tenantInf->id, 'stripe_connect_account_id', $account->id);
                }

                $this->setupState = true;

                break;
            }
            else
            {
                $this->prepareSetup( $tenantInf );
            }
        }

        if ( !$this->setupState )
            return false;

        return parent::handleTenant($tenantInf);
    }
    
    private function prepareSetup( $tenantInf )
    {
        if ( Tenant::getData( $tenantInf->id, 'stripe_connect_verified') == 1 )
            Tenant::setData( $tenantInf->id, 'stripe_connect_verified', 0 );

        $params = [
            'tos_page'     => Helper::getOption( 'stripe_connect_terms_page', ''),
            'pricing'      => StripeConnectHelper::getFeeAndFeeType()
        ];

        StripeConnectHelper::setView( self::$view, $params );
    }
}