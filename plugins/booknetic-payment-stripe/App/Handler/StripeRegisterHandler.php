<?php

namespace BookneticAddon\StripePaymentGateway\Handler;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticAddon\StripePaymentGateway\Integration\StripeConnect;
use BookneticSaaS\Models\Tenant;

class StripeRegisterHandler extends StripeConnect
{
    private static $view = 'connect_register_settings.php';

    public function handleTenant($tenantInf)
    {
        $tenantAccId = Tenant::getData( $tenantInf->id, 'stripe_connect_account_id' );

        $tenantsStripeAcc = StripeConnectHelper::getInstance()->retreiveAccount( $tenantAccId );

        if ( ! $tenantsStripeAcc->charges_enabled || ! $tenantsStripeAcc->payouts_enabled )
        {
            if ( Tenant::getData( $tenantInf->id, 'stripe_connect_verified') == 1 )
                Tenant::setData( $tenantInf->id, 'stripe_connect_verified', 0 );

            $requirements = empty ( $tenantsStripeAcc->requirements->pending_verification ) ? $tenantsStripeAcc->requirements->currently_due : $tenantsStripeAcc->requirements->pending_verification;

            StripeConnectHelper::setView( self::$view, [
                'status' => false,
                'reason' => $tenantsStripeAcc->requirements->disabled_reason,
                'requirments' => $requirements
            ] );

            return false;
        }

        return parent::handleTenant($tenantInf);
    }

}