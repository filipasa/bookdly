<?php

namespace BookneticAddon\StripePaymentGateway\Integration;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class StripeConnect
{
    private static $view = 'connect_settings.php';
    private $next;
    private $chain;


    public function nextChain( $next )
    {
        $this->next = $next;

        return $next;
    }

    public function handleTenant( $tenantInf )
    {
        if ( !$this->next )
        {
            return true;
        }

        return $this->next->handleTenant( $tenantInf );
    }

    public function setChain($chain)
    {
        $this->chain = $chain;
    }

    public function checkTenant( $tenantInf )
    {
        if ( $this->chain->handleTenant($tenantInf) )
        {
            $verifiedTenant = Tenant::getData( $tenantInf->id, 'stripe_connect_verified' );

            if ( $verifiedTenant != 1 )
            {
                Tenant::setData( $tenantInf->id, 'stripe_connect_verified', 1 );
            }

            $params['pricing'] = StripeConnectHelper::getFeeAndFeeType();

            StripeConnectHelper::setView( self::$view, $params );
        }

        return true;
    }
}