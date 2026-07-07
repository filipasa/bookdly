<?php

namespace BookneticAddon\StripePaymentGateway;

use BookneticAddon\StripePaymentGateway\Helpers\StripeHelper;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Math;
use Stripe\Exception\ApiErrorException;

class Stripe extends PaymentGatewayService
{

	protected $slug = 'stripe';

	private $_paymentId;
	private $_type;
	private $_successURL;
	private $_cancelURL;
    private $_appointmentIds;
    public $createPaymentLink = true;


	public function __construct()
	{
        $this->setDefaultTitle(bkntc__('Credit Card'));
		$this->setDefaultIcon( StripeAddon::loadAsset( 'assets/frontend/icons/stripe.svg' ) );

		\Stripe\Stripe::setApiKey( Helper::getOption('stripe_client_secret') );
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
        $productData = ['name' => $itemName];
        if( ! empty( $itemImage ) )
        {
            $productData['images'] = [ $itemImage ];
        }
        $this->_items[] = [
            'price_data' => [
                'currency'      => $currency,
                'unit_amount'   => StripeHelper::normalizePrice( $price, $currency ),
                'product_data'  => $productData,
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

    /**
     * @throws ApiErrorException
     */
    private function createPaymentRequest()
    {
      return \Stripe\Checkout\Session::create([
          'payment_method_types'  => ['card'],
          'line_items'            => $this->_items,
          'mode'                  => 'payment',
          'payment_intent_data'   => [
              'setup_future_usage' => 'off_session',
          ],
          'success_url'           => $this->_successURL,
          'cancel_url'            => $this->_cancelURL,
          "metadata"              => [
              'payment_id'        =>  $this->_paymentId,
              'type'              =>  $this->_type,
              'appointment_ids'   =>  $this->_appointmentIds
          ]
      ]);
    }

    /**
     * @param AppointmentRequests $appointmentRequests
     * @return object
     */
    public function doPayment( $appointmentRequests )
    {
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
        }

        $tenantIdParam = Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '';
        $appointmentId = $appointmentRequests->appointments[0]->getFirstAppointmentId();

        $url = site_url() . '/?bkntc_appointment_id=' . $appointmentId . $tenantIdParam . '&bkntc_stripe_status=';

		$this->setSuccessURL( $url . 'success' );
		$this->setCancelURL( $url . 'cancel' );

        try {
            $stripeSession = $this->createPaymentRequest();

            $data = [
                'url'               =>  $stripeSession->url,
                'remote_payment_id' =>  $stripeSession->id
            ];
        } catch ( \Exception $e ) {
            $data = [
                'error_msg' => bkntc__( 'Payment failed:' ) . ' ' . htmlspecialchars( $e->getMessage() )
            ];
        }

		return (object) [
			'status'    => empty( $data[ 'error_msg' ] ),
			'data'      => $data
		];
    }

    public function createCustomPayment( $productName, $amount, $callbackUrl )
    {
        $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );

        $callbackUrl .= strpos( $callbackUrl, '?' ) !== false ? '&' : '?';

        $this->resetItems();

        $this->addItem( $amount, Helper::getOption('currency', 'USD') , $productName, '' );
        $this->setSuccessURL( $callbackUrl . 'bkntc_stripe_custom_status=success&bkntc_stripe_custom_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param );
        $this->setCancelURL( $callbackUrl . 'bkntc_stripe_custom_status=cancel&bkntc_stripe_custom_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param );

        try {
            $stripeSession = $this->createPaymentRequest();

            $data = [
                'url' => $stripeSession->url
            ];
        } catch ( \Exception $e ) {
            $data = [
                'error_msg' => bkntc__( 'Payment failed:' ) . ' ' . htmlspecialchars( $e->getMessage() )
            ];
        }

        return (object)[
            'status'    =>  empty( $data[ 'error_msg' ] ),
            'data'      =>  $data
        ];
    }

    public function checkCustomPaymentStatus( $paymentId )
    {
        $sessionId				    = Helper::_get('bkntc_stripe_custom_session_id', '', 'string');
        $bookneticStripeStatus      = Helper::_get('bkntc_stripe_custom_status', false, 'string', ['success', 'cancel']);

        if ( empty( $sessionId ) || empty( $bookneticStripeStatus ) )
            return false;

        try
        {
            $sessionInf = \Stripe\Checkout\Session::retrieve( $sessionId );
        }
        catch (ApiErrorException $e)
        {
            return false;
        }

        if (
            $bookneticStripeStatus == 'success' &&
            isset( $sessionInf->payment_status ) && $sessionInf->payment_status == 'paid' &&
            isset( $sessionInf->metadata->payment_id ) && $sessionInf->metadata->payment_id == $paymentId
        )
        {
            return true;
        }

        return false;
    }

    public function createPaymentLink( $appointments )
    {
        $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );

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
        }
        $this->setSuccessURL(site_url() . '/?bkntc_stripe_status=success&bkntc_stripe_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);
        $this->setCancelURL(site_url() . '/?bkntc_stripe_status=cancel&bkntc_stripe_session_id={CHECKOUT_SESSION_ID}' . $tenant_id_param);

        try {
            $stripeSession = $this->createPaymentRequest();

            $data = [
                'url' => $stripeSession->url
            ];
        } catch ( \Exception $e ) {
            $data = [
                'error_msg' => bkntc__( 'Payment failed:' ) . ' ' . htmlspecialchars( $e->getMessage() )
            ];
        }

        return  (object) [
            'status'    => empty( $data[ 'error_msg' ] ),
            'data'      => $data
        ];
    }

}