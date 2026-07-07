<?php

namespace BookneticAddon\PaypalPaymentGateway;


use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\Tenant;
use PayPal\Api\Item;

use function BookneticAddon\PaypalPaymentGateway\bkntc__;

class PaypalSplitGateway extends PaymentGatewayService
{

    protected $slug = 'paypal_split';

    private $_paymentId;
    private $_tax = 0;
    private $_orderURI = 'v1/checkout/orders';
    private $_fee;
    private $_jwtToken;
    private $_saasMerhcantId;
    private $_tenantMerchantId;
    private $_apiContext;
    private $_successURL;
    private $_cancelURL;
    private $_currency;
    public $createPaymentLink;


    public function __construct()
    {
        $this->setDefaultTitle(bkntc__('Paypal'));
        $this->setDefaultIcon( PaypalAddon::loadAsset('assets/frontend/icons/paypal.svg' ) );

        $this->initPaypalSettings();
    }

    public function when( $status, $appointmentRequests = null )
    {
        if( $status && Helper::getOption('hide_confirm_details_step', 'off') == 'on' )
        {
            return false;
        }

        return $status;
    }

    private function initPaypalSettings()
    {
        $mode = Helper::getOption( 'paypal_split_mode', 'sandbox', false ) == 'live' ? 'live' : 'sandbox';

        $this->_apiContext = PaypalSplitHelper::getApiContext();

        $this->_apiContext->setConfig([ 'mode' => $mode ]);

    }

    public function setId( $paymentId )
    {
        $this->_paymentId = $paymentId;

        return $this;
    }

    public function setSaaSMerchantId( $merchantId )
    {
        $this->_saasMerhcantId = $merchantId;
    }

    public function setTenantMerchantId( $merchantId )
    {
        $this->_tenantMerchantId = $merchantId;
    }

    public function setFee( $rawFee, $feeType )
    {
        $sumPrice = array_sum( array_map( function ($item) {
            return $item->getPrice() * $item->getQuantity();
        },$this->_items ) );

        if ( $feeType == 'price' )
        {
            $fee = Math::mul( $rawFee, 100 );
        }
        else
        {
            $fee = Math::div( Math::mul( $rawFee, $sumPrice + $this->_tax ), 100 );
        }

        $this->_fee = $fee;
    }

    public function generateToken()
    {
        $header = [
            'payment_gateway' => 'paypal'
        ];

        $payload = [
            'payment_id' => $this->_paymentId
        ];

        $secret = Helper::getOption( 'paypal_split_client_secret', '', false );

        return Helper::generateToken( $header, $payload, $secret );
    }

    public function setTax( $tax_amount )
    {
        $this->_tax = $tax_amount;
    }

    public function addItem(  $itemId , $itemName , $price )
    {
        $item = new Item();
        $item->setName( $itemName )
            ->setCurrency( $this->_currency )
            ->setQuantity(1)
//            ->setSku( $itemId )
            ->setPrice( $price );
        $this->_items[] = $item;
    }

    public function setCurrency( $currency )
    {
        $this->_currency = $currency;
    }

    public function setSuccessURL( $url )
    {
        $this->_successURL = $url;

        return $this;
    }

    public function setCancelURL( $url )
    {
        $this->_cancelURL = $url;

        return $this;
    }

    public function createPaymentRequest()
    {
        $sumPrice = array_sum( array_map( function ($item) {
            return $item->getPrice() * $item->getQuantity();
        },$this->_items ) );

        $reqBody = [
            'purchase_units' => [[
                'reference_id' => $this->_saasMerhcantId,
                'amount' => [
                    'currency' => $this->_currency,
                    'total' => $sumPrice + $this->_tax
                ],
                'payee' => [
                    'merchant_id' => $this->_tenantMerchantId
                ],
                'partner_fee_details' => [
                    'receiver' => [
                        'merchant_id' => $this->_saasMerhcantId
                    ],
                    'amount' => [
                        'value' => $this->_fee,
                        'currency' => $this->_currency
                    ]
                ]
            ]],
            'redirect_urls' => [
                'return_url' => $this->_successURL,
                'cancel_url' => $this->_cancelURL
            ],
            'application_context' => [
                'user_action' => 'commit'
            ],
            'intent' => 'SALE',
        ];

        $result = PaypalSplitHelper::post( $this->_orderURI, $reqBody, false, [ 'PayPal-Partner-Attribution-Id' => Helper::getOption('paypal_split_bn', '', false) ] );

        $falseResponse = [
            'status'	=> false,
            'error'		=> bkntc__('Couldn\'t create a payment!')
        ];

        if ( ! $result )
        {
            return $falseResponse;
        }

        if ( !isset($result['body']['status']) && $result['body']['status'] !== 'CREATED' )
        {
            return $falseResponse;
        }

        return [
            'status'	=> true,
            'url'		=> $result['body']['links'][1]['href']
        ];
    }

    public function check( $orderToken )
    {
        $result = PaypalSplitHelper::get( $this->_orderURI . '/' . $orderToken, false, [ 'PayPal-Partner-Attribution-Id' => Helper::getOption('paypal_split_bn', '', false) ] );

        if ( !$result )
        {
            return false;
        }

        if ( !isset($result['body']['status']) && $result['body']['status'] != 'APPROVED' )
        {
            return false;
        }

        $result = $this->confirmOrder( $orderToken );

        if ( !isset($result['body']['status']) && $result['body']['status'] != 'COMPLETED' )
        {
            return false;
        }

        return $result;

    }

    private function confirmOrder( $orderToken )
    {
        $reqBody = [
          "disbursement_mode" => "INSTANT"
        ];

        $result = PaypalSplitHelper::post( $this->_orderURI . '/' . $orderToken . '/pay', $reqBody, false, [ 'PayPal-Partner-Attribution-Id' => Helper::getOption('paypal_split_bn', '', false) ] );

        return $result;

    }

    private function sumTaxAmount( AppointmentRequestData $appointmentObj )
    {
        $taxAmountSum = 0;

        foreach ( $appointmentObj->getPrices() as $priceKey => $price )
        {
            if ( substr($priceKey, 0, 4) === "tax-" )
            {
                $taxAmountSum += $appointmentObj->getPayableTodayPrice( $priceKey, true );
            }
        }

        return $taxAmountSum;
    }

    private function sumTaxAmountBySmartObject( AppointmentSmartObject $appointmentObj )
    {
        $taxAmountSum = 0;

        foreach ( $appointmentObj->getPrices() as $priceKey => $price )
        {
            if ( substr($price->unique_key, 0, 4) === "tax-" )
            {
                $taxAmountSum += $appointmentObj->getPrice( $price->unique_key )->price;
            }
        }

        return $taxAmountSum;
    }

    /**
     * @param AppointmentRequests $appointmentRequests
     * @return object
     */
    public function doPayment( $appointmentRequests )
    {
        $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );

        $this->setId( $appointmentRequests->paymentId );
        $this->setCurrency( Helper::getOption('currency', 'USD') );
        $totalTax = 0;

        $this->setSaaSMerchantId( Helper::getOption( 'paypal_split_merchant_id', '', false ) );
        $this->setTenantMerchantId( Tenant::getData( Permission::tenantId(), 'paypal_split_tenant_merchant_id' ) );

        $this->resetItems();

        foreach ($appointmentRequests->appointments as $appointment)
        {
            $tax = $this->sumTaxAmount( $appointment);
            $totalTax += $tax;
            $this->addItem(
                "SERVICE-".$appointment->serviceInf->id ,
                $appointment->serviceInf->name ,
                $appointment->getPayableToday( true ) - $tax
            );
        }

        $rawFee = Helper::getOption( 'paypal_split_platform_fee', '', false );
        $feeType = Helper::getOption( 'paypal_split_fee_type', '', false );

        $this->setFee( $rawFee, $feeType );
        $this->setTax( $totalTax );
        $this->setSuccessURL(site_url() . '/?bkntc_paypal_split_status=success&bkntc_token='. $this->generateToken() );
        $this->setCancelURL(site_url() . '/?bkntc_paypal_split_status=cancel&bkntc_token='. $this->generateToken() );

        $res = $this->createPaymentRequest();

        if( $res['status'] )
        {
            $response_status = true;
            $response_data   = [ 'url' => $res['url'] ];
        }
        else
        {
            $response_status = false;
            $response_data   = [ 'error_msg' => $res['error'] ];
        }

        return (object) [
            'status'    => $response_status,
            'data'      => $response_data
        ];
    }

    public function createPaymentLink( $appointments )
    {
        $appointmentIds = array_map(function ($appointment){
            return $appointment->id;
        },$appointments);
        $appointmentIds = base64_encode(implode(',',$appointmentIds));

        $this->setId( $appointmentIds );
        $this->setCurrency( Helper::getOption('currency', 'USD') );
        $totalTax = 0;

        $this->setSaaSMerchantId( Helper::getOption( 'paypal_split_merchant_id', '', false ) );
        $this->setTenantMerchantId( Tenant::getData( Permission::tenantId(), 'paypal_split_tenant_merchant_id' ) );

        $this->resetItems();

        foreach ($appointments as $appointment)
        {
            $appointmentSmartObject = AppointmentSmartObject::load($appointment->id);

            $tax = $this->sumTaxAmountBySmartObject( $appointmentSmartObject );
            $totalTax += $tax;
            $this->addItem(
                "SERVICE-".$appointment->service_id ,
                $appointment->service_name ,
                Math::sub($appointment->total_price , $appointment->paid_amount) - $tax
            );
        }

        $rawFee = Helper::getOption( 'paypal_split_platform_fee', '', false );
        $feeType = Helper::getOption( 'paypal_split_fee_type', '', false );

        $this->setFee( $rawFee, $feeType );
        $this->setTax( $totalTax );
        $this->setSuccessURL(site_url() . '/?bkntc_paypal_split_status=success&type=create_payment_link&bkntc_token='. $this->generateToken() );
        $this->setCancelURL(site_url() . '/?bkntc_paypal_split_status=cancel&type=create_payment_link&bkntc_token='. $this->generateToken() );

        $res = $this->createPaymentRequest();


        if( $res['status'] )
        {
            $response_status = true;
            $response_data   = [ 'url' => $res['url'] ];
        }
        else
        {
            $response_status = false;
            $response_data   = [ 'error_msg' => $res['error'] ];
        }

        return (object) [
            'status'    => $response_status,
            'data'      => $response_data
        ];
    }


}