<?php
namespace BookneticSaaS\Integrations\PaymentGateways;
use Stripe\Price;
use Stripe\Coupon;
use Stripe\Webhook;
use Stripe\Product;
use Stripe\Subscription;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Providers\Helpers\Helper;
class Stripe
{
    private $_paymentId;
    private $_price;
    private $_first_price;
    private $_currency;
    private $_payment_cycle;
    private $_plan;
    private $_successURL;
    private $_cancelURL;
    private $_email;
    private $_trialDays = 0;
    public static function webhookUrl()
    {
        return site_url() . '/?booknetic_saas_action=stripe_webhook';
    }
    public function __construct()
    {
        \Stripe\Stripe::setApiKey(Helper::getOption('stripe_client_secret'));
        \Stripe\Stripe::setApiVersion('2025-07-30.basil');
    }
    public function setId($paymentId)
    {
        $this->_paymentId = $paymentId;
        return $this;
    }
    public function setCycle($cycle)
    {
        $this->_payment_cycle = $cycle === 'monthly' ? 'month' : 'year';
        return $this;
    }
    public function setAmount($price, $first_price, $currency = 'USD')
    {
        $this->_price       = $price;
        $this->_first_price = $first_price;
        $this->_currency    = $currency;
        return $this;
    }
    public function setPlan($plan)
    {
        $this->_plan = $plan;
        return $this;
    }
    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }
    public function setSuccessURL($url)
    {
        $this->_successURL = $url;
        return $this;
    }
    public function setCancelURL($url)
    {
        $this->_cancelURL = $url;
        return $this;
    }
    public function setTrialDays($days)
    {
        $this->_trialDays = (int)$days;
        return $this;
    }
    public function createRecurringPayment()
    {
        if (isset($this->_plan->reset_stripe_data)) {
            $this->_plan->stripe_product_data = null;
        }
        try {
            $coupon = $this->getCoupon();
            $sessionArray = [
                'success_url'           => $this->_successURL,
                'cancel_url'            => $this->_cancelURL,
                'payment_method_types'  => [ 'card' ],
                'mode'                  => 'subscription',
                'line_items'            => [ [ 'price' => $this->getPriceId(), 'quantity' => 1 ] ],
                'subscription_data'     => [ 'metadata' => [ 'billing_id' => $this->_paymentId ] ],
                'customer_email'        => $this->_email
            ];
            if ($this->_trialDays > 0) {
                $sessionArray['subscription_data']['trial_period_days'] = $this->_trialDays;
            }
            if (!empty($coupon)) {
                $sessionArray['discounts'] = [ [ 'coupon' => $coupon ]] ;
            }
            $checkout_session = Session::create($sessionArray);
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }
        return [
            'status'    => true,
            'id'        => $checkout_session->id,
            'url'       => $checkout_session->url
        ];
    }
    public function checkSession($sessionId)
    {
        // VERSION MARKER v3
        self::log("[CS v3] checkSession called with sessionId=" . substr($sessionId, 0, 20));
        try {
            $sessionInf = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            self::log("[CS v3] Session retrieve EXCEPTION: " . $e->getMessage());
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }
        self::log("[CS v3] payment_status=" . ($sessionInf->payment_status ?? 'NULL') . ", mode=" . ($sessionInf->mode ?? 'NULL') . ", subscription=" . ($sessionInf->subscription ?? 'NULL'));
        $validPaymentStatuses = ['paid', 'no_payment_needed'];
        if (
            !(
                isset($sessionInf->payment_status, $sessionInf->mode, $sessionInf->subscription)
                && in_array($sessionInf->payment_status, $validPaymentStatuses, true)
                && $sessionInf->mode === 'subscription'
                && !empty($sessionInf->subscription)
                && is_string($sessionInf->subscription)
            )
        ) {
            return [
                'status'    => false,
                'error'     => 'Error! payment_status=' . ($sessionInf->payment_status ?? 'null')
            ];
        }
        try {
            $subscriptionInf = Subscription::retrieve($sessionInf->subscription);
        } catch (\Exception $e) {
            self::log("[CS v3] Subscription retrieve EXCEPTION: " . $e->getMessage());
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }
        self::log("[CS v3] subscriptionInf keys: " . json_encode(array_keys($subscriptionInf->toArray())));
        self::log("[CS v3] subscriptionInf raw: " . json_encode($subscriptionInf->toArray()));
        self::log("[CS v3] subscriptionInf->current_period_end=" . ($subscriptionInf->current_period_end ?? 'NULL') . ", status=" . ($subscriptionInf->status ?? 'NULL'));
        return [
            'status'        => true,
            'subscription'  => $sessionInf->subscription,
            'billing_id'    => $subscriptionInf->metadata->billing_id,
            'invoice_id'    => $subscriptionInf->latest_invoice,
            'expire_date'   => $this->getExpireDate($subscriptionInf)
        ];
    }
    public function cancelSubscription($subscriptionId)
    {
        try {
            $subscriptionInf = Subscription::retrieve($subscriptionId);
            $subscriptionInf->cancel();
        } catch (\Exception $e) {
            return [
                'status'    => false,
                'error'     => $e->getMessage()
            ];
        }
        return [ 'status' => true ];
    }
    public function webhook()
    {
        $payload = @file_get_contents("php://input");
        $endpoint_secret = Helper::getOption('stripe_webhook_secret', '');
        if (empty($endpoint_secret) || !isset($_SERVER["HTTP_STRIPE_SIGNATURE"])) {
            http_response_code(400);
            exit();
        }
        $sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"];
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }
        if ($event->type === 'invoice.paid') {
            $this->subscriptionPaid($event);
        } elseif ($event->type === 'customer.subscription.deleted') {
            if (isset($event->data->object->id) && is_string($event->data->object->id) && !empty($event->data->object->id)) {
                Tenant::unsubscribed($event->data->object->id);
            }
        }
        http_response_code(200);
    }
    private function subscriptionPaid($event): void
    {
        // VERSION MARKER v3
        self::log("[SP v3] subscriptionPaid called, event_type=" . ($event->type ?? 'NULL'));
        $invoice = $event->data->object;
        self::log("[SP v3] invoice status=" . ($invoice->status ?? 'NULL') . ", billing_reason=" . ($invoice->billing_reason ?? 'NULL') . ", amount_due=" . ($invoice->amount_due ?? 'NULL'));
        if (!isset($invoice->status) || $invoice->status !== 'paid') {
            http_response_code(400);
            exit;
        }
        if (isset($invoice->billing_reason) && $invoice->billing_reason === 'manual') {
            http_response_code(200);
            return;
        }
        $subscriptionId = null;
        if (isset($invoice->parent->subscription_details->subscription) && is_string($invoice->parent->subscription_details->subscription)) {
            $subscriptionId = $invoice->parent->subscription_details->subscription;
        } elseif (
            isset($invoice->lines->data[0]->parent->subscription_item_details->subscription) && is_array($invoice->lines->data) && !empty($invoice->lines->data) && is_string($invoice->lines->data[0]->parent->subscription_item_details->subscription)
        ) {
            $subscriptionId = $invoice->lines->data[0]->parent->subscription_item_details->subscription;
        } elseif (is_string($invoice->subscription) && !empty($invoice->subscription)) {
            $subscriptionId = $invoice->subscription;
        }
        if (empty($subscriptionId)) {
            http_response_code(400);
            exit;
        }
        $subscription = null;
        $billingId = $invoice->parent->subscription_details->metadata->billing_id ?? null;
        if (isset($invoice->lines->data) && empty($billingId) && is_array($invoice->lines->data) && !empty($invoice->lines->data)) {
            $firstLine = $invoice->lines->data[0];
            if (isset($firstLine->metadata->billing_id)) {
                $billingId = $firstLine->metadata->billing_id;
            }
        }
        if (empty($billingId)) {
            try {
                $subscription = Subscription::retrieve($subscriptionId);
            } catch (\Exception $e) {
                http_response_code(400);
                exit;
            }
            if (isset($subscription->metadata->billing_id) && !empty($subscription->metadata->billing_id)) {
                $billingId = $subscription->metadata->billing_id;
            }
        }
        if (empty($billingId)) {
            http_response_code(400);
            exit;
        }
        if (empty($subscription)) {
            try {
                $subscription = Subscription::retrieve($subscriptionId);
            } catch (\Exception $e) {
                // fallback to invoice item period if retrieve fails
            }
        }
        $invoiceItem = $invoice->lines->data[0];
        $invoiceId = isset($invoiceItem) ? $invoiceItem->invoice : null;
        $expireDate = !empty($subscription) ? $this->getExpireDate($subscription) : (isset($invoiceItem) ? $invoiceItem->period->end : null);
        if (!Tenant::paymentSucceded($subscriptionId, $billingId, $expireDate, $invoiceId)) {
            http_response_code(400);
            exit;
        }
    }
    public function getPriceData()
    {
        if (empty($this->_plan->stripe_product_data)) {
            $product = Product::create([ 'name' => $this->_plan->name ]);
            $monthly_price = Price::create([
                'product'       => $product->id,
                'unit_amount'   => $this->normalizePrice($this->_plan->monthly_price, $this->_currency),
                'currency'      => $this->_currency,
                'recurring'     => [ 'interval' => 'month' ]
            ]);
            $annually_price = Price::create([
                'product'       => $product->id,
                'unit_amount'   => $this->normalizePrice($this->_plan->annually_price, $this->_currency),
                'currency'      => $this->_currency,
                'recurring'     => [ 'interval' => 'year' ]
            ]);
            $stripe_product_data = [
                'id'            => $product->id,
                'month'         => $monthly_price->id,
                'year'          => $annually_price->id,
                'month_coupon'  => '',
                'year_coupon'   => ''
            ];
            if ($this->_plan->monthly_price_discount > 0 && $this->_plan->monthly_price_discount <= 100) {
                $coupon = Coupon::create([
                    'name'          => $this->_plan->monthly_price_discount . '% OFF',
                    'duration'      => 'once',
                    'percent_off'   => $this->_plan->monthly_price_discount
                ]);
                $stripe_product_data['month_coupon'] = $coupon->id;
            }
            if ($this->_plan->annually_price_discount > 0 && $this->_plan->annually_price_discount <= 100) {
                $coupon = Coupon::create([
                    'name'          => $this->_plan->annually_price_discount . '% OFF',
                    'duration'      => 'once',
                    'percent_off'   => $this->_plan->annually_price_discount
                ]);
                $stripe_product_data['year_coupon'] = $coupon->id;
            }
            $this->_plan->stripe_product_data = json_encode($stripe_product_data);
            Plan::where('id', $this->_plan->id)->update([ 'stripe_product_data' => $this->_plan->stripe_product_data ]);
        }
        return $this->_plan->stripe_product_data;
    }
    public function getPriceId()
    {
        $priceData = json_decode($this->getPriceData(), true);
        return $priceData[ $this->_payment_cycle ];
    }
    public function getCoupon()
    {
        $priceData = json_decode($this->getPriceData(), true);
        return $priceData[ $this->_payment_cycle . '_coupon' ];
    }
    private function normalizePrice($price, $currency)
    {
        $zeroDecimalCurrencies = [ 'BIF', 'DJF', 'JPY', 'KRW', 'PYG', 'VND', 'XAF', 'XPF', 'CLP', 'GNF', 'KMF', 'MGA', 'RWF', 'VUV', 'XOF' ];
        if (in_array($currency, $zeroDecimalCurrencies)) {
            return $price;
        }
        return round($price * 100);
    }
    public function updateProductName($id, $name)
    {
        try {
            Product::update($id, [ 'name' => $name ]);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    private function getExpireDate($subscription)
    {
        if (empty($subscription)) {
            return null;
        }
        if (!empty($subscription->trial_end)) {
            return $subscription->trial_end;
        }
        if (!empty($subscription->current_period_end)) {
            return $subscription->current_period_end;
        }
        if (isset($subscription->items->data[0]->current_period_end)) {
            return $subscription->items->data[0]->current_period_end;
        }
        return null;
    }
    private static function log($msg)
    {
        @file_put_contents(defined('ABSPATH') ? ABSPATH . 'booknetic_debug.log' : '/tmp/booknetic_debug.log', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
    }
}