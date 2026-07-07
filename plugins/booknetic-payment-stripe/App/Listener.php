<?php

namespace BookneticAddon\StripePaymentGateway;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticSaaS\Models\Tenant;
use Stripe\Exception\ApiErrorException;

class Listener
{

    public static function saveSettings ( $response )
    {
        $stripe_client_id     = Helper::_post( 'stripe_client_id', '', 'string' );
        $stripe_client_secret = Helper::_post( 'stripe_client_secret', '', 'string' );

        if ( PaymentGatewayService::find( 'stripe' )->isEnabled() && ( empty( $stripe_client_id ) || empty( $stripe_client_secret ) ) )
        {
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Stripe payment gateway!' ), true );
        }

        Helper::setOption( 'stripe_client_id', $stripe_client_id );
        Helper::setOption( 'stripe_client_secret', $stripe_client_secret );

        return $response;

    }

    public static function saveSplitSettings ( $response )
    {
        $stripe_connect_client_id     = Helper::_post( 'stripe_connect_client_id', '', 'str' );
        $stripe_connect_client_secret = Helper::_post( 'stripe_connect_client_secret', '', 'str' );
        $stripe_connect_platform_fee  = Helper::_post( 'stripe_connect_platform_fee', '0', 'float' );
        $stripe_connect_fee_type      = Helper::_post( 'stripe_connect_fee_type', 'price', [ 'price', 'percent' ] );
        $stripe_connect_terms_page    = Helper::_post( 'stripe_connect_terms_page', '', 'str' );

        if ( $stripe_connect_platform_fee < 0 )
        {
            return Helper::response( false, bkntc__( 'Fee cannot be less than 0' ), false );
        }

        if ( $stripe_connect_fee_type == 'percent' && $stripe_connect_platform_fee > 100 )
        {
            return Helper::response( false, bkntc__( 'Fee cannot be higher than 100%' ), false );
        }


        if (
            PaymentGatewayService::find( 'stripe_split' )->isEnabled() &&
            (
                empty( $stripe_connect_client_id ) ||
                empty( $stripe_connect_client_secret ) ||
                empty( $stripe_connect_platform_fee ) ||
                empty( $stripe_connect_fee_type )
            )
        )
        {
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Stripe Connect payment gateway!' ), false );
        }

        Helper::setOption( 'stripe_connect_client_id', $stripe_connect_client_id, false );
        Helper::setOption( 'stripe_connect_client_secret', $stripe_connect_client_secret, false );
        Helper::setOption( 'stripe_connect_platform_fee', $stripe_connect_platform_fee, false );
        Helper::setOption( 'stripe_connect_fee_type', $stripe_connect_fee_type, false );
        Helper::setOption( 'stripe_connect_terms_page', $stripe_connect_terms_page, false );

        return $response;
    }


    public static function checkStripeConnectSetupCallback ()
    {
        $connectStatus = Helper::_get( 'bkntc_stripe_connect_setup', '', 'string' );

        if ( empty( $connectStatus ) )
            return;

        $tenantAccId = Tenant::getData( Permission::tenantId(), 'stripe_connect_account_id' );

        $tenantsStripeAcc = StripeConnectHelper::getInstance()->retreiveAccount( $tenantAccId );

        if ( !$tenantsStripeAcc->charges_enabled || !$tenantsStripeAcc->payouts_enabled )
        {
            $params = [
                'status' => false,
                'reason' => $tenantsStripeAcc->requirements->disabled_reason,
                'requirments' => empty ( $tenantsStripeAcc->requirements->pending_verification ) ? $tenantsStripeAcc->requirements->currently_due : $tenantsStripeAcc->requirements->pending_verification,
            ];
        }
        else
        {
            $params = [];
        }

        echo '<script type="text/javascript">if(window.opener!==null){ window.opener.setupCompleted( true, `' . htmlspecialchars( Helper::renderView( __DIR__ . '/Backend/view/connect/connect_register_settings.php', $params ) ) . '` ); window.close() } </script>';
        exit();
    }

    public static function checkStripeConnectCallback ()
    {
        $sessionId             = Helper::_get( 'bkntc_stripe_split_session_id', '', 'string' );
        $bookneticStripeStatus = Helper::_get( 'bkntc_stripe_split_status', false, 'string', [ 'success', 'cancel' ] );

        if ( empty( $sessionId ) )
            return;

        if ( empty( $bookneticStripeStatus ) )
        {
            echo '<script src="//js.stripe.com/v3/"></script>' .
                '<div>...</div>' .
                '<script type="text/javascript">
                    var stripeSplit = Stripe("' . htmlspecialchars( Helper::getOption( 'stripe_connect_client_id', '', false ) ) . '");
                    stripeSplit.redirectToCheckout({ sessionId: "' . htmlspecialchars( $sessionId ) . '" });
                </script>';
            exit();
        }

        try
        {
            $sessionInf = StripeConnectHelper::getInstance()->getStripeClient()->checkout->sessions->retrieve( $sessionId );
        } catch ( ApiErrorException $e )
        {
            exit;
        }

        if (
            ( isset( $sessionInf->payment_status ) && $sessionInf->payment_status == 'paid' ) &&
            ( isset( $sessionInf->metadata ) && isset( $sessionInf->metadata->payment_id ) ) &&
            ( $bookneticStripeStatus == 'success' )
        )
        {
            if ( isset( $sessionInf->metadata->type ) && $sessionInf->metadata->type === 'create_payment_link' )
            {
                $appointmentIds = explode( ',', base64_decode( $sessionInf->metadata->appointment_ids ) );

                foreach ( $appointmentIds as $appointmentId )
                {
                    PaymentGatewayService::confirmPaymentLink( $appointmentId, $sessionInf->amount_total / 100, 'stripe_split' );
                }

                $thanksYouPage = Helper::getOption( 'redirect_url_after_booking', '' );
                $redirectUrl   = empty( $thanksYouPage ) ? site_url() : $thanksYouPage;

                echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( true )}window.location.href = "' . $redirectUrl . '"</script>';
            }
            else
            {
                PaymentGatewayService::confirmPayment( $sessionInf->metadata->payment_id );
                echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
            }
            exit;
        }

        if (
            ( isset( $sessionInf->payment_status ) && $sessionInf->payment_status != 'paid' ) &&
            ( isset( $sessionInf->metadata ) && isset( $sessionInf->metadata->payment_id ) ) &&
            ( $bookneticStripeStatus == 'cancel' )
        )
        {
            if ( $sessionInf->metadata->type !== 'create_payment_link' )
            {
                PaymentGatewayService::cancelPayment( $sessionInf->metadata->payment_id );
            }
            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( false )}window.location.href = "' . site_url() . '"</script>';
            exit;
        }

        exit;
    }

    public static function checkStripeCallback ()
    {
        $appointmentId         = Helper::_get( 'bkntc_appointment_id', '', 'string' );
        $bookneticStripeStatus = Helper::_get( 'bkntc_stripe_status', false, 'string', [ 'success', 'cancel' ] );

        if( empty( $appointmentId ) || empty( $bookneticStripeStatus ) )
            return;

        $sessionId = Appointment::getData( $appointmentId, 'remote_payment_id' );

        if ( empty( $sessionId ) )
            return;

        try
        {
            $sessionInf = \Stripe\Checkout\Session::retrieve( $sessionId );
        } catch ( ApiErrorException $e )
        {
            exit;
        }

        if (
            isset( $sessionInf->payment_status )
            && $sessionInf->payment_status == 'paid'
            && isset( $sessionInf->metadata->payment_id )
            && $bookneticStripeStatus == 'success'
        )
        {
            if ( isset( $sessionInf->metadata->type ) && $sessionInf->metadata->type === 'create_payment_link' )
            {
                $appointmentIds = explode( ',', base64_decode( $sessionInf->metadata->appointment_ids ) );

                foreach ( $appointmentIds as $appointmentId )
                {
                    PaymentGatewayService::confirmPaymentLink( $appointmentId, $sessionInf->amount_total / 100, 'stripe' );
                }

                $thanksYouPage = Helper::getOption( 'redirect_url_after_booking', '' );
                $redirectUrl   = empty( $thanksYouPage ) ? site_url() : $thanksYouPage;

                echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( true )}window.location.href = "' . $redirectUrl . '"</script>';
            }
            else
            {
                PaymentGatewayService::confirmPayment( $sessionInf->metadata->payment_id );
                echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
            }
            exit;
        }

        if (
            isset( $sessionInf->metadata->payment_id )
            && isset($sessionInf->payment_status)
            && $sessionInf->payment_status != 'paid'
            && $bookneticStripeStatus == 'cancel'
        ) {
            if ( isset( $sessionInf->metadata->type ) && $sessionInf->metadata->type !== 'create_payment_link' ) {
                PaymentGatewayService::cancelPayment( $sessionInf->metadata->payment_id );
            }

            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( false )}window.location.href = "' . site_url() . '"</script>';
            exit;
        }

        exit;
    }

}