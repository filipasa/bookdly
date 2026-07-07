<?php

namespace BookneticAddon\PaypalPaymentGateway\Helpers;

use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Helper as RegularHelper;
use BookneticSaaS\Models\Tenant;
use BookneticVendor\GuzzleHttp\Client;
use Exception;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PaypalSplitHelper
{
    private static $partnerSetupURI = 'v2/customer/partner-referrals';
    private static $partnerCheckURI = 'v1/customer/partners/';
    private static $baseURL;

    private static $apiContext;
    private static $tokenObject;

    public static $view;
    public static $emptyView = 'paypal_split_settings_error.php';
    public static $params = [];

    public static function setMode( $mode )
    {
        if ( $mode === 'live' )
            self::$baseURL = 'https://api-m.paypal.com';
        else
            self::$baseURL = 'https://api-m.sandbox.paypal.com';
    }

    public static function getApiContext()
    {
        if ( is_null( self::$apiContext ) )
        {
            self::$apiContext =  new ApiContext( self::getTokenObject() );
        }
        return self::$apiContext;
    }

    public static function getTokenObject()
    {
        if ( is_null( self::$tokenObject ) )
        {
            self::$tokenObject =  new OAuthTokenCredential( Helper::getOption( 'paypal_split_client_id', '', false ), Helper::getOption( 'paypal_split_client_secret', '', false ) );
        }
        return self::$tokenObject;
    }

    public static function getToken()
    {
        try {
            return self::getTokenObject()->getAccessToken([]);
        }
        catch( Exception $e )
        {
            throw new Exception('something went wrong');
        }
    }

    public static function post ( $slug, $data = [], $default = [], $additionalHeader = [] )
    {
        self::setMode( Helper::getOption( 'paypal_split_mode', 'sandbox', false ) );

        $headers = [
            'Authorization' => 'Bearer ' . self::getToken(),
            'Content-Type'  => 'application/json',
        ];

        if ( !$additionalHeader )
        {
            foreach ( $additionalHeader as $key => $header )
            {
                $headers[$key] = $header;
            }
        }

        try
        {
            $client = new Client( [
                'headers' => $headers,
            ] );

            $response = $client->post( static::$baseURL . '/' . $slug, [ 'body' => json_encode($data) ] );

            $apiRes = json_decode( $response->getBody(), true );

            return [
                'status' => $response->getStatusCode(),
                'body' => $apiRes,
            ];

        }
        catch ( Exception $e )
        {
            return $default;
        }
    }

    public static function get ( $slug, $default = [], $additionalHeader = [] )
    {
        self::setMode( Helper::getOption( 'paypal_split_mode', 'sandbox', false ) );

        $headers = [
            'Authorization' => 'Bearer ' . self::getToken(),
            'Content-Type'  => 'application/json',
        ];

        if ( !$additionalHeader )
        {
            foreach ( $additionalHeader as $key => $header )
            {
                $headers[$key] = $header;
            }
        }

        try
        {
            $client = new Client( [
                'headers' => $headers,
            ] );

            $response = $client->get( static::$baseURL . '/' . $slug, [] );

            $apiRes = json_decode( $response->getBody(), true );

            return [
                'status' => $response->getStatusCode(),
                'body' => $apiRes,
            ];

        }
        catch ( Exception $e )
        {
            return $default;
        }
    }

    public static function createSellerAccount( $tenantInf )
    {
        $reqBody = [
            'tracking_id' => $tenantInf->id,
            'partner_config_override' => [
                'partner_logo_url' => 'https://www.booknetic.com/assets/front/img/booknetic-logo-white.svg',
                'return_url' => site_url() . '/?' . RegularHelper::getSlugName() . '_action=paypal_split_status',
                'return_url_description' => 'Click to confirm.',
                'show_add_credit_card' => true
            ],
            'operations' => [[
                'operation' => 'API_INTEGRATION',
                'api_integration_preference' => [
                    'rest_api_integration' => [
                        'integration_method' => 'PAYPAL',
                        'integration_type'   => 'THIRD_PARTY',
                        'third_party_details' => [
                            'features' => [
                                'PAYMENT',
                                'REFUND'
                            ]
                        ]
                    ]
                ]
            ]],
            'legal_consents' => [[
                'type' => 'SHARE_DATA_CONSENT',
                'granted' => true
            ]],
            'products' => [
                'EXPRESS_CHECKOUT'
            ]
        ];

        return self::post( self::$partnerSetupURI, $reqBody, false );

    }

    public static function checkSellerAccount( $tenantInf, $merchantId, $tenantMerchantId )
    {
        $result = self::get( self::$partnerCheckURI . $merchantId . '/merchant-integrations/' . $tenantMerchantId , false );

        if ( ! $result )
            return false;

        if ( $result['status'] != 200 )
            return false;

        if ( $result['body']['tracking_id'] != $tenantInf->id )
            return false;

        if ( ! $result['body']['payments_receivable'] )
            return false;

        if ( ! $result['body']['primary_email_confirmed'] )
            return false;

        return true;

    }

    public static function setView( $view, $params = [] )
    {
        self::$view = $view;

        if ( ! empty( $params ) )
        {
            self::$params = $params;
        }

    }

    public static function getView()
    {
        return empty(self::$view) ? self::$emptyView : self::$view;
    }

    public static function getParams()
    {
        return self::$params;
    }

    public static function webhookURL()
    {
        return site_url() . '/?' . RegularHelper::getSlugName() . '_action=paypal_split_webhook';
    }

    public static function canUsePaypalSplit()
    {
        $tenantId = Permission::tenantId();

        if ( empty( $tenantId ) )
            return false;

        if ( Tenant::getData( $tenantId, 'paypal_split_verified') == 1 )
        {
            return true;
        }

        return false;
    }

}