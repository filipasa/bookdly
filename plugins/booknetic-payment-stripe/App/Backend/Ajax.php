<?php

namespace BookneticAddon\StripePaymentGateway\Backend;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    private $stripeConnect;

    public function __construct()
    {
        $this->stripeConnect = StripeConnectHelper::getInstance();
    }

    public function generate_register_link()
    {
        if ( ! $this->stripeConnect->checkApiStatus() )
            return $this->response(false, bkntc__('Something went wrong.') );

        $tenantInf = StripeConnectHelper::getTenantInf();

        if ( ! empty( $tenantInf ) )
            $accId = $this->stripeConnect->createAccount( $tenantInf );
        else
            return $this->response(false, 'Something went wrong.');


        $url = $this->stripeConnect->generateOnboardingURL( $accId );

        return $this->response(true, [ 'url' => $url ]);
    }

    public function generate_verify_link()
    {
        if ( ! $this->stripeConnect->checkApiStatus() )
            return $this->response(false, 'Something went wrong.');

        $accId = Tenant::getData(Permission::tenantId(), 'stripe_connect_account_id') ;

        if ( empty( $accId ) )
            return $this->response(false, 'Something went wrong.');

        $url = $this->stripeConnect->generateOnboardingURL( $accId );

        return $this->response(true, [ 'url' => $url ]);
    }

    public function generate_login_link()
    {
        if ( ! $this->stripeConnect->checkApiStatus() )
            return $this->response(false, 'Something went wrong.');

        $accId = Tenant::getData(Permission::tenantId(), 'stripe_connect_account_id') ;

        if ( ! empty( $accId ) )
            $url = $this->stripeConnect->generateLoginlink( $accId );
        else
            return $this->response(false, 'Something went wrong.');


        return $this->response(true, [ 'url' => $url ]);
    }

    public function connected_tenants_saas()
    {
        if ( ! $this->stripeConnect->checkApiStatus() )
            return $this->response(false, 'Credentials are not correct!');

        $accounts = $this->stripeConnect->getAllStripeAccounts();

        $connectedAccounts = [];

        //stage 1 collect the raw data
        foreach ( $accounts AS $account )
        {
            if ( isset( $account->metadata['tenantId'] ) )
            {
                $connectedAccounts[ $account->metadata['tenantId'] ] = [
                    'status' => ( $account->charges_enabled && $account->payouts_enabled ) ? 'Complete' : $account->requirements->disabled_reason,
//                    'charges_enabled' => $account->charges_enabled,
//                    'payouts_enabled' => $account->payouts_enabled,
//                    'disabled_reason' => $account->requirements->disabled_reason,
                ];
            }
        }

        if ( empty( $connectedAccounts ) )
        {
            return $this->response( false, 'You don\' have any connected account' );
        }

        //stage 2 fetch the raw tenant data from DB
        $tenantsRaw = Tenant::select( [ 'id', 'email' ] )->where( 'id', 'IN', array_keys( $connectedAccounts ) )->fetchAll();
        $tenantsDataRaw = Data::noTenant()
            ->where( 'table_name', 'tenants' )
            ->where( 'data_key', 'IN', [ 'stripe_connect_verified', 'stripe_connect_account_id' ] )
            ->where( 'row_id', 'IN', array_keys( $connectedAccounts ) )->fetchAll();


        //stage 3 parse the raw db data
        $tenants = array_column( $tenantsRaw, 'email', 'id' );
        $tenantsData = [];

        foreach ( $tenantsDataRaw AS $tenantDataRaw )
        {
            $tenantsData[ $tenantDataRaw['row_id'] ][ $tenantDataRaw['data_key'] ] = $tenantDataRaw['data_value'];
        }

        //stage 4 validate
        foreach( $connectedAccounts AS $key => $account )
        {
            if ( array_key_exists( $key, $tenants ) && array_key_exists( $key, $tenantsData ) )
            {
                $connectedAccounts[ $key ][ 'email' ] = $tenants[ $key ];
                $connectedAccounts[ $key ][ 'stripe_connect_account_id' ] = $tenantsData[ $key ][ 'stripe_connect_account_id' ];
                $connectedAccounts[ $key ][ 'stripe_connect_verified' ] = $tenantsData[ $key ][ 'stripe_connect_verified' ];
                //TODO: burda elave olaraq Data cedvelinde olan verified 1,0 olmaqini stripedan gelen status ile yoxla
            }
            else
            {
                unset( $connectedAccounts[ $key ] );
            }
        }

        return $this->modalView( 'connected_tenants', [ 'tenants' => $connectedAccounts ] );

    }

    public function delete_connected_tenant_account()
    {
        $accounts = Helper::_post( 'accounts', '', 'string' );

        if ( empty( $accounts ) )
        {
            return $this->response( false, 'Please select a tenant before deleting' );
        }

        $accounts = json_decode( $accounts, true );

        $balanceZero = true;

        foreach( $accounts AS $account )
        {
            $response = $this->stripeConnect->getStripeClient()->accounts->delete( $account[ 'account' ] );

            if ( $response->isDeleted() )
            {
                Tenant::deleteData( $account['id'], 'stripe_connect_account_id' );
                Tenant::deleteData( $account['id'], 'stripe_connect_verified' );
            }
            else
            {
                $balanceZero = false;
            }
        }

        if ( ! $balanceZero )
        {
            return $this->response( false, 'Some of the accounts could not be deleted as their balances are not zero or some unknown problem occurred' );
        }

        return $this->response( true, [ 'message' => 'Successfully deleted' ] );

    }

}