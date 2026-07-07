<?php

namespace BookneticAddon\StripePaymentGateway;

use BookneticAddon\StripePaymentGateway\Helpers\StripeConnectHelper;
use BookneticAddon\StripePaymentGateway\Helpers\StripeHelper;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\Tenant;
use Stripe\Exception\ApiErrorException;
use function BookneticAddon\StripePaymentGateway\bkntc__;

class StripeConnectGateway extends PaymentGatewayService
{

    protected $slug = 'stripe_split';

    private $_paymentId;
    private $_type;
    private $_successURL;
    private $_cancelURL;
    private $_appointmentIds;
    private $_stripeClient;
    private $_fee = [];
    public $createPaymentLink = true;


    public function __construct()
    {
        $this->setDefaultTitle(bkntc__('Credit Card'));
        $this->setDefaultIcon( StripeAddon::loadAsset( 'assets/frontend/icons/stripe.svg' ) );

        $this->_stripeClient = StripeConnectHelper::getInstance()->getStripeClient();
    }

    public function when( $status, $appointmentRequests = null )
    {
        if( $status && Helper::getOption('hide_confirm_details_step', 'off') == 'on' )
        {
            return false;
        }

        return $status;
    }

    public function setId( $paymentId )
    {
        $this->_paymentId = $paymentId;

        return $this;
    }

    public function setType( $type )
    {
        $this->_type = $type;

        return $this;
    }

    public function setAppointmentIds( $arr )
    {
        $this->_appointmentIds = $arr;

        return $this;
    }

    public function addItem( $price , $currency , $itemName , $itemImage )
    {
        $this->_items[] = [
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => StripeHelper::normalizePrice( $price, $currency ),
                'product_data' => [
                    'name' => $itemName,
                    'images' => [$itemImage],
                ],
            ],
            'quantity' => 1
        ];
        return $this;
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

    private function createPaymentRequest()
    {
        try
        {
            $checkout_session = $this->_stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $this->_items,
                'mode' => 'payment',
                'payment_intent_data' => [
                    'application_fee_amount' => $this->_fee['fee'],
                    'transfer_data' => [
                        'destination' => $this->_fee['destination'],
                    ],
                ],
                'success_url' => $this->_successURL,
                'cancel_url' => $this->_cancelURL,
                "metadata" => ['payment_id' => $this->_paymentId ,'type'=>$this->_type,'appointment_ids'=>$this->_appointmentIds]
            ]);
        }
        catch (ApiErrorException $e)
        {
            return 0;
        }

        return $checkout_session->id;
    }

    public function setFee( $tenantId, $totalPrice, $rawFee, $feeType = 'percent' )
    {
        if ( $feeType == 'price' )
        {
            $fee = Math::floor( $rawFee );
        }
        else
        {
            $fee = Math::floor( Math::div( Math::mul( $rawFee, $totalPrice ), 100 ) );
        }

        $fee = StripeHelper::normalizePrice( $fee, Helper::getOption('currency', 'USD') );

        $this->_fee['destination'] = Tenant::getData( $tenantId, 'stripe_connect_account_id' );
        $this->_fee['fee'] = $fee;
    }

    /**
     * @param AppointmentRequests $appointmentRequests
     * @return object
     */
    public function doPayment( $appointmentRequests )
    {
        $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );
        $totalPrice = 0;

        $this->setId( $appointmentRequests->paymentId  );

        $this->resetItems();

        foreach ( $appointmentRequests->appointments as $appointmentObj)
        {
            $this->addItem(
                $appointmentObj->getPayableToday( true ),
                Helper::getOption('currency', 'USD') ,
                $appointmentObj->serviceInf->name,
                Helper::profileImage( $appointmentObj->serviceInf->image, 'Services')
            );

            $totalPrice += $appointmentObj->getPayableToday( true );

        }
        $this->setSuccessURL(site_url() . '/?bkntc_stripe_split_status=success&bkntc_stripe_split_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);
        $this->setCancelURL(site_url() . '/?bkntc_stripe_split_status=cancel&bkntc_stripe_split_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);

        $this->setFee( Permission::tenantId(), $totalPrice, Helper::getOption( 'stripe_connect_platform_fee', '0', false ), Helper::getOption( 'stripe_connect_fee_type', 'percent', false ) );

        $stripeSessionId = $this->createPaymentRequest();

        $status = true;
        $data = [ 'url' => site_url() . '/?bkntc_stripe_split_session_id=' . $stripeSessionId . $tenant_id_param ];

        if( $stripeSessionId === 0 )
        {
            $status = false;
            $data = [ 'error_msg' => bkntc__( "Couldn't create a payment!" ) ];
        }

        return (object) [
            'status'    => $status,
            'data'      => $data
        ];
    }

    public function createPaymentLink( $appointments )
    {
        $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );
        $totalPrice = 0;

        $appointmentIds = array_map(function ($appointment){
            return $appointment->id;
        },$appointments);
        $appointmentIds = base64_encode(implode(',',$appointmentIds));

        $this->setId($appointmentIds);
        $this->setType('create_payment_link');
        $this->setAppointmentIds( $appointmentIds );

        $this->resetItems();

        foreach ( $appointments as $appointmentObj)
        {
            $this->addItem(
                Math::sub($appointmentObj->total_price , $appointmentObj->paid_amount),
                Helper::getOption('currency', 'USD') ,
                $appointmentObj->service_name,
                Helper::profileImage( $appointmentObj->staff_profile_image, 'Services')
            );

            $totalPrice += $appointmentObj->getPayableToday( true );
        }
        $this->setSuccessURL(site_url() . '/?bkntc_stripe_split_status=success&bkntc_stripe_split_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);
        $this->setCancelURL(site_url() . '/?bkntc_stripe_split_status=cancel&bkntc_stripe_split_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);

        $this->setFee( Permission::tenantId(), $totalPrice, Helper::getOption( 'stripe_connect_platform_fee', '0', false ), Helper::getOption( 'stripe_connect_fee_type', 'percent', false ) );

        $stripeSessionId = $this->createPaymentRequest();

        $status = true;
        $data = [ 'url' => site_url() . '/?bkntc_stripe_split_session_id=' . $stripeSessionId . $tenant_id_param ];

        if( $stripeSessionId === 0 )
        {
            $status = false;
            $data = [ 'error_msg' => bkntc__( "Couldn't create a payment!" ) ];
        }

        return  (object) [
            'status'    => $status,
            'data'      => $data
        ];
    }

}