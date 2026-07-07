<?php

namespace BookneticAddon\StripePaymentGateway\Helpers;

use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;
use Stripe\StripeClient;

class StripeConnectHelper
{

    private static $stripeConnectedAccounts;
    public static $view;
    public static $emptyView = 'connect_settings_error.php';
    public static $params = [];
    private $stripe;
    private $hasErrors = false;
    private static $instance;

    public function __construct()
    {
        try
        {
            $this->stripe = new StripeClient( Helper::getOption('stripe_connect_client_secret', '') );
        }
        catch ( \Exception $e )
        {
            $this->hasErrors = true;
        }
    }

    public function createAccount( $tenantInf )
    {
        $account = $this->stripe->accounts->create([
            'type' => 'express',
            'email'=> $tenantInf->email,
            'metadata' => [
                'tenantId' => $tenantInf->id
            ],
        ]);

        Tenant::setData( $tenantInf->id, 'stripe_connect_account_id', $account->id );

        return $account->id;
    }

    public function retreiveAccount( $accountId )
    {
        return $this->stripe->accounts->retrieve( $accountId );
    }

    public function generateOnboardingURL( $accountId )
    {
        $accLink = $this->stripe->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => site_url(),
                'return_url' => site_url() . '/?bkntc_stripe_connect_setup=finished' ,
                'type' => 'account_onboarding',
            ]);

        return $accLink->url;
    }

    public function generateLoginlink( $accountId )
    {
        $accLogin = $this->stripe->accounts->createLoginLink( $accountId );
        return $accLogin->url;
    }


    public function checkApiStatus()
    {
        if ( $this->hasErrors )
            return false;

        try
        {
            $this->stripe->accounts->all();
            return true;
        }
        catch ( \Exception $e )
        {
            return false;
        }
    }

    public function getAllStripeAccounts()
    {
        if ( empty( self::$stripeConnectedAccounts ) )
        {
            self::$stripeConnectedAccounts = $this->stripe->accounts->all()->data;
        }

        return self::$stripeConnectedAccounts;
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

    public static function getTenantInf()
    {
        $tenantInf = Permission::tenantInf();

        if ( !empty($tenantInf) )
        {
            return $tenantInf;
        }
        else
        {
            throw new \Exception('Something wen\'t wrong');
        }

    }

    public static function getFeeAndFeeType()
    {
        $pricing = [];

        if ( Helper::getOption('stripe_connect_fee_type', '0') == 'percent' )
        {
            $pricing[] = Helper::getOption('stripe_connect_platform_fee', '0');
            $pricing[] = '%';
        }
        else
        {
            $pricing[] = Helper::currencySymbol();
            $pricing[] = Helper::getOption('stripe_connect_platform_fee', '0');
        }

        return $pricing;
    }

    public static function canUseStripeConnect()
    {
        $tenantId = Permission::tenantId();

        if ( empty( $tenantId ) )
            return false;

        if ( Tenant::getData( $tenantId, 'stripe_connect_verified') == 1 )
        {
            return true;
        }

        return false;
    }

    public static function getInstance()
    {
        if( is_null(self::$instance ))
        {
            self::$instance = new StripeConnectHelper();
        }
        return self::$instance;
    }

    public function getStripeClient()
    {
        return $this->stripe;
    }


}