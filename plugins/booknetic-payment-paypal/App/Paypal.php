<?php

namespace BookneticAddon\PaypalPaymentGateway;


use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Math;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

use function BookneticAddon\PaypalPaymentGateway\bkntc__;

class Paypal extends PaymentGatewayService
{

	protected $slug = 'paypal';

	private $_paymentId;
	private $_tax = 0;
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
		$clientId		= Helper::getOption('paypal_client_id' );
		$clientSecret	= Helper::getOption('paypal_client_secret' );
		$mode			= Helper::getOption('paypal_mode' ) == 'live' ? 'live' : 'sandbox';

		$this->_apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential( $clientId , $clientSecret )
		);

		$this->_apiContext->setConfig([ 'mode' => $mode ]);
	}

	public function setId( $paymentId )
	{
		$this->_paymentId = $paymentId;

		return $this;
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
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");


        $sumPrice = array_sum(array_map( function ($item){
            return $item->getPrice() * $item->getQuantity();
        },$this->_items));

		$itemList = new ItemList();
		$itemList->setItems( $this->_items );

		$details = new Details();
		$details->setShipping(0)
			->setTax($this->_tax)
			->setSubtotal($sumPrice);

		$amount = new Amount();
		$amount->setCurrency( $this->_currency )
			->setTotal($sumPrice + $this->_tax)
			->setDetails($details);


		$transaction = new Transaction();
		$transaction->setAmount( $amount )
			->setItemList( $itemList )
            ->setCustom( $this->_paymentId )
			->setDescription("Payment");

		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl($this->_successURL)->setCancelUrl($this->_cancelURL);

		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));

		try
		{
			$payment->create($this->_apiContext);

			$approvalUrl = $payment->getApprovalLink();

			return [
				'status'	=> true,
				'url'		=> $approvalUrl
			];
		}
		catch (\Exception $ex)
		{
			return [
				'status'	=> false,
				'error'		=> bkntc__('Couldn\'t create a payment!')
			];
		}
	}

	public function check( $payerId , $paymentId )
	{
		$payment = Payment::get( $paymentId, $this->_apiContext );

		$execution = new PaymentExecution();
		$execution->setPayerId( $payerId );

		try
		{
			$result = $payment->execute( $execution, $this->_apiContext );

			return $result;
		}
		catch (\PayPal\Exception\PayPalConnectionException $ex)
		{
			return null;
		}
		catch (\Exception $ex)
		{
			return null;
		}

        return null;
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

        $this->setTax( $totalTax );
		$this->setSuccessURL(site_url() . '/?bkntc_paypal_status=success' . $tenant_id_param );
		$this->setCancelURL(site_url() . '/?bkntc_paypal_status=cancel&bkntc_payment_id='. $this->_paymentId . $tenant_id_param );
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
	    $tenant_id_param = ( Helper::isSaaSVersion() ? '&tenant_id=' . Permission::tenantId() : '' );

        $appointmentIds = array_map(function ($appointment){
            return $appointment->id;
        },$appointments);
        $appointmentIds = base64_encode(implode(',',$appointmentIds));

		$this->setId( $appointmentIds );
        $this->setCurrency( Helper::getOption('currency', 'USD') );
        $totalTax = 0;

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

        $this->setTax( $totalTax );
		$this->setSuccessURL(site_url() . '/?bkntc_paypal_status=success&type=create_payment_link' . $tenant_id_param );
		$this->setCancelURL(site_url() . '/?bkntc_paypal_status=cancel&type=create_payment_link' . $tenant_id_param );
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