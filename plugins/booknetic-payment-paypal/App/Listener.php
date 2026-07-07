<?php

namespace BookneticAddon\PaypalPaymentGateway;

use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticSaaS\Models\Tenant;
use PayPal\Api\VerifyWebhookSignature;

class Listener
{

    public static function saveSettings ( $response )
    {
        $paypal_client_id     = Helper::_post( 'paypal_client_id', '', 'string' );
        $paypal_client_secret = Helper::_post( 'paypal_client_secret', '', 'string' );
        $paypal_mode          = Helper::_post( 'paypal_mode', 'sandbox', 'string', [ 'sandbox', 'live' ] );

        if ( PaymentGatewayService::find( 'paypal' )->isEnabled() && ( empty( $paypal_client_id ) || empty( $paypal_client_secret ) || empty( $paypal_mode ) ) )
        {
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Paypal payment gateway!' ), true );
        }

        Helper::setOption( 'paypal_client_id', $paypal_client_id );
        Helper::setOption( 'paypal_client_secret', $paypal_client_secret );
        Helper::setOption( 'paypal_mode', $paypal_mode );

        return $response;
    }

    public static function saveSplitSettings ( $response )
    {
        $paypal_split_mode          = Helper::_post( 'input_paypal_split_mode', '', 'str', [ 'sandbox', 'live' ] );
        $paypal_split_webhook_id    = Helper::_post( 'input_paypal_split_webhook_id', '', 'str' );
        $paypal_split_client_id     = Helper::_post( 'input_paypal_split_client_id', '', 'str' );
        $paypal_split_client_secret = Helper::_post( 'input_paypal_split_client_secret', '', 'str' );
        $paypal_split_merchant_id   = Helper::_post( 'input_paypal_split_merchant_id', '', 'str' );
        $paypal_split_bn            = Helper::_post( 'input_paypal_split_bn', '', 'str' );
        $paypal_split_platform_fee  = Helper::_post( 'input_paypal_split_platform_fee', '0', 'int' );
        $paypal_split_fee_type      = Helper::_post( 'input_paypal_split_fee_type', 'percent', 'str', [ 'price', 'percent' ] );
        $paypal_split_terms_page    = Helper::_post( 'input_paypal_split_terms_page', '#', 'str' );


        if (
            PaymentGatewayService::find( 'paypal_split' )->isEnabled() &&
            (
                empty( $paypal_split_client_id ) ||
                empty( $paypal_split_webhook_id ) ||
                empty( $paypal_split_client_secret ) ||
                empty( $paypal_split_merchant_id ) ||
                empty( $paypal_split_bn ) ||
                empty( $paypal_split_platform_fee ) ||
                empty( $paypal_split_fee_type ) ||
                empty( $paypal_split_mode )
            )
        )
        {
            return Helper::response( false, bkntc__( 'Please, fill all fields to enable Paypal Split payment gateway!' ), true );
        }

        Helper::setOption( 'paypal_split_mode', $paypal_split_mode, false );
        Helper::setOption( 'paypal_split_webhook_id', $paypal_split_webhook_id, false );
        Helper::setOption( 'paypal_split_client_id', $paypal_split_client_id, false );
        Helper::setOption( 'paypal_split_client_secret', $paypal_split_client_secret, false );
        Helper::setOption( 'paypal_split_merchant_id', $paypal_split_merchant_id, false );
        Helper::setOption( 'paypal_split_bn', $paypal_split_bn, false );
        Helper::setOption( 'paypal_split_platform_fee', $paypal_split_platform_fee, false );
        Helper::setOption( 'paypal_split_fee_type', $paypal_split_fee_type, false );
        Helper::setOption( 'paypal_split_terms_page', $paypal_split_terms_page, false );

        return $response;
    }

    public static function checkPaypalSplitSetupCallback ()
    {
        $paypalSplitStatus = Helper::_get( Helper::getSlugName() . '_action', '', 'str' );
//        $merchantId         = Helper::_get( 'merchantId', false, 'str' );
//        $merchantIdInPayPal = Helper::_get( 'merchantIdInPayPal', false, 'str' );

        if ( empty( $paypalSplitStatus ) && empty( $merchantId ) && empty( $merchantIdInPayPal ) )
            return;

        if ( $paypalSplitStatus == 'paypal_split_status' )
        {
            echo '<script type="text/javascript">let bc = new BroadcastChannel("bkntc_split_communication"); bc.postMessage({ "status" : true, "view": `' . htmlspecialchars( Helper::renderView( __DIR__ . '/Backend/view/split_payments/paypal_split_verify_settings.php', [ 'tenant_message' => bkntc__( 'If you completed all the steps your account is sent to be reviewed' ) ] ) ) . '` }); window.close() </script>';
            exit();
        }

    }

    public static function verifyPaypalSplitWebhook ()
    {
        $shouldContinue = Helper::_get( Helper::getSlugName() . '_action', '' );

        if ( $shouldContinue !== 'paypal_split_webhook' )
            return;

        $webhookId = Helper::getOption( 'paypal_split_webhook_id', '', false );

        $payload = @file_get_contents( "php://input" );

        if (
            empty( $webhookId ) ||
            !isset( $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ) ||
            !isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ) ||
            !isset( $_SERVER['HTTP_PAYPAL_CERT_URL'] ) ||
            !isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) ||
            !isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] )
        )
        {
            exit();
        }

        $signatureVerification = new VerifyWebhookSignature();
        $signatureVerification->setAuthAlgo( $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] );
        $signatureVerification->setTransmissionId( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] );
        $signatureVerification->setCertUrl( $_SERVER['HTTP_PAYPAL_CERT_URL'] );
        $signatureVerification->setWebhookId( $webhookId );
        $signatureVerification->setTransmissionSig( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] );
        $signatureVerification->setTransmissionTime( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] );

        $signatureVerification->setRequestBody( $payload );

        try
        {
            $output = $signatureVerification->post( PaypalSplitHelper::getApiContext() );
        } catch ( \Exception $ex )
        {
            http_response_code( 400 );
            exit();
        }


        if ( $output->getVerificationStatus() !== 'SUCCESS' )
        {
            http_response_code( 400 );
            exit();
        }

        $payload = json_decode( $payload, true );

        if ( $payload['event_type'] == 'MERCHANT.ONBOARDING.COMPLETED' )
        {
            $tenantsMerchantId = $payload['resource']['merchant_id'];
            Tenant::setData( Permission::tenantId(), 'paypal_split_tenant_merchant_id', $tenantsMerchantId );
        }

    }

    public static function checkPaypalSplitPaymentCallback ()
    {
        $bookneticPaypalSplitStatus = Helper::_get( 'bkntc_paypal_split_status', '', 'str' );
        $PayerID                    = Helper::_get( 'PayerID', '', 'str' );
        $token                      = Helper::_get( 'token', '', 'str' );
        $type                       = Helper::_get( 'type', '', 'string' );
        $bookneticToken             = Helper::_get( 'bkntc_token', '', 'str' );

        if ( empty( $token ) || empty( $bookneticPaypalSplitStatus ) || empty( $bookneticToken ) )
            return;

        $bookneticTokenParts = explode( '.', $bookneticToken );

        if ( count( $bookneticTokenParts ) !== 3 ) return false;

        $payload = json_decode( base64_decode( $bookneticTokenParts[1] ), true );
        $secret  = Helper::getOption( 'paypal_split_client_secret', '', false );

        if ( !array_key_exists( 'payment_id', $payload ) )
            return;

        if ( !Helper::validateToken( $bookneticToken, $secret ) )
            return;

        $bookneticPaymentId = $payload['payment_id'];

        if ( $bookneticPaypalSplitStatus == 'cancel' )
        {
            if ( !empty( $bookneticPaymentId ) )
            {
                PaymentGatewayService::cancelPayment( $bookneticPaymentId );
            }
            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( false )}window.location.href = "' . site_url() . '"</script>';
            exit;
        }

        if ( empty( $PayerID ) )
            return;

        if ( $bookneticPaypalSplitStatus == 'success' )
        {
            $paypalSplit = new PaypalSplitGateway();
            $result      = $paypalSplit->check( $token );
        }

        if ( $type === 'create_payment_link' )
        {
            PaymentGatewayService::confirmPaymentLink( $bookneticPaymentId, $result['body']['gross_total_amount']['value'], 'paypal_split' );
            $thanksYouPage = Helper::getOption( 'redirect_url_after_booking', '' );
            $redirectUrl   = empty( $thanksYouPage ) ? site_url() : $thanksYouPage;
            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( true )}window.location.href = "' . $redirectUrl . '"</script>';
            exit;
        }
        else
        {
            PaymentGatewayService::confirmPayment( $bookneticPaymentId );
            echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
        }
        exit;
    }

    public static function checkPaypalCallback ()
    {
        $bookneticPaypalStatus = Helper::_get( 'bkntc_paypal_status', false, 'string', [ 'success', 'cancel' ] );
        $PayerID               = Helper::_get( 'PayerID', '', 'string' );
        $paymentId             = Helper::_get( 'paymentId', '', 'string' );
        $token                 = Helper::_get( 'token', '', 'string' );
        $bookneticPaymentId    = Helper::_get( 'bkntc_payment_id', '', 'string' );
        $type                  = Helper::_get( 'type', '', 'string' );


        if ( empty( $token ) || empty( $bookneticPaypalStatus ) )
            return;

        if ( $bookneticPaypalStatus == 'cancel' )
        {
            if ( !empty( $bookneticPaymentId ) )
            {
                PaymentGatewayService::cancelPayment( $bookneticPaymentId );
            }
            echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( false )}window.location.href = "' . site_url() . '"</script>';
            exit;
        }

        if ( empty( $PayerID ) || empty( $paymentId ) )
            return;

        $paypal               = new Paypal();
        $result               = $paypal->check( $PayerID, $paymentId );
        $appointmentPaymentId = $result->transactions[0]->custom;

        if ( empty( $result ) )
            return;

        if ( $result->state == 'approved' )
        {
            if ( $type === 'create_payment_link' )
            {
                $appointmentIds = explode( ',', base64_decode( $appointmentPaymentId ) );
                $amountTotal    = $result->transactions[0]->amount->total;
                foreach ( $appointmentIds as $appointmentId )
                {
                    PaymentGatewayService::confirmPaymentLink( $appointmentId, $amountTotal, 'paypal' );
                }
                $thanksYouPage = Helper::getOption( 'redirect_url_after_booking', '' );
                $redirectUrl   = empty( $thanksYouPage ) ? site_url() : $thanksYouPage;
                echo '<script>if(window.opener!==null){window.opener.bookneticPaymentStatus( true )}window.location.href = "' . $redirectUrl . '"</script>';
                exit;
            }
            else
            {
                PaymentGatewayService::confirmPayment( $appointmentPaymentId );
                echo '<script>window.opener.bookneticPaymentStatus( true );</script>';
            }
            exit;
        }

        exit;
    }

}