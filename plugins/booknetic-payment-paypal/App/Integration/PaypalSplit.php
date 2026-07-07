<?php

namespace BookneticAddon\PaypalPaymentGateway\Integration;

use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class PaypalSplit
{
    private static $view = 'paypal_split_settings.php';
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

    public function setChain( $chain )
    {
        $this->chain = $chain;
    }


    public function checkTenant( $tenantInf )
    {
        if ( $this->chain->handleTenant($tenantInf) )
        {
            $isTenantVerified = Tenant::getData( $tenantInf->id, 'paypal_split_verified' );

            if ($isTenantVerified != 1)
            {
                Tenant::setData($tenantInf->id, 'paypal_split_verified', 1);
            }

            $params = [
                'platform_fee' => Helper::getOption('paypal_split_platform_fee', '0', false),
                'fee_type'     => Helper::getOption('paypal_split_fee_type', '0', false) == 'percent' ? '%' : Helper::currencySymbol(),
            ];

            PaypalSplitHelper::setView( self::$view, $params );
        }

        return true;
    }
}