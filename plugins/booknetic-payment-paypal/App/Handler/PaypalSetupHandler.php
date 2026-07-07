<?php

namespace BookneticAddon\PaypalPaymentGateway\Handler;

use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticAddon\PaypalPaymentGateway\Integration\PaypalSplit;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class PaypalSetupHandler extends PaypalSplit
{
    private static $view = 'paypal_split_setup_settings.php';

    public function handleTenant( $tenantInf )
    {

        if ( empty(Helper::getOption( 'paypal_split_merchant_id', '' ) ) )
            return false;

        if ( empty( Tenant::getData( $tenantInf->id, 'paypal_split_tenant_merchant_id' ) ) )
        {
            $params = [
                'platform_fee' => Helper::getOption('paypal_split_platform_fee', '0', false),
                'fee_type'     => Helper::getOption('paypal_split_fee_type', '0', false) == 'percent' ? '%' : Helper::currencySymbol(),
            ];

            PaypalSplitHelper::setView( self::$view, $params );
            return false;
        }

        return parent::handleTenant($tenantInf);
    }

}