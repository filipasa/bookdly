<?php

namespace BookneticAddon\PaypalPaymentGateway\Handler;

use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticAddon\PaypalPaymentGateway\Integration\PaypalSplit;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;
use function BookneticAddon\PaypalPaymentGateway\bkntc__;

class PaypalVerifyHandler extends PaypalSplit
{
    private static $view = 'paypal_split_verify_settings.php';

    public function handleTenant( $tenantInf )
    {
        $saasMerchantId = Helper::getOption( 'paypal_split_merchant_id', '' );
        $tenantReferralMerchantId = Tenant::getData( $tenantInf->id, 'paypal_split_tenant_merchant_id' );

        $checkResult = PaypalSplitHelper::checkSellerAccount( $tenantInf, $saasMerchantId, $tenantReferralMerchantId );

        if ( ! $checkResult )
        {
            $params = [
                'tenant_message' => bkntc__('Your account is under review')
            ];

            PaypalSplitHelper::setView( self::$view, $params );
            return false;
        }

        return parent::handleTenant($tenantInf);
    }

}