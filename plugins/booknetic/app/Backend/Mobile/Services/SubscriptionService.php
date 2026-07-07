<?php

namespace BookneticApp\Backend\Mobile\Services;

use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\DTOs\Response\ActiveSubscriptionResponse;
use BookneticApp\Backend\Mobile\Exceptions\ApiException;
use BookneticApp\Backend\Mobile\Exceptions\InvalidSeatCountException;

class SubscriptionService
{
    private FSCodeMobileAppClient $client;

    public function __construct(FSCodeMobileAppClient $client)
    {
        $this->client = $client;
    }

    public function getActive(): ActiveSubscriptionResponse
    {
        $response = $this->client->getActiveSubscription();

        $type = $response['type'] ?? 'none';
        $subscription = $response['subscription'] ?? null;

        if ($subscription !== null) {
            $subscription['paymentMethodLabel'] = $this->getPaymentMethodLabel($subscription);
        }

        return new ActiveSubscriptionResponse($type, $subscription);
    }

    /**
     * TODO bu method-u structurlu data gondercek shekilde update etmeliyik. card, last4 kimi field-leri
     *  front-a gonderib orda qurmaliyiq
     */
    private function getPaymentMethodLabel(array $subscription): string
    {
        $paymentMethod = $subscription['paymentMethod'] ?? null;

        if (empty($paymentMethod) || !is_array($paymentMethod)) {
            return bkntc__('No payment method');
        }

        $type = $paymentMethod['type'] ?? '';

        if (!isset($paymentMethod[$type])) {
            return bkntc__('No payment method details');
        }

        $label = ucfirst(implode(' ', explode('_', $type)));

        if ($type === 'south_korea_local_card') {
            $label .= ' - ' . $paymentMethod[$type]['type'] . ' ****' . $paymentMethod[$type]['last4'];
        }

        if ($type === 'card') {
            $card = $paymentMethod['card'] ?? [];

            if (!empty($card)) {
                $label .= ' - '
                    . ($card['type'] ?? '')
                    . ' ****'
                    . ($card['last4'] ?? '')
                    . ' '
                    . str_pad($card['expiryMonth'] ?? '', 2, '0', STR_PAD_LEFT)
                    . '/'
                    . ($card['expiryYear'] ?? '')
                    . ' '
                    . ($card['cardholderName'] ?? '');
            }
        }

        return $label;
    }

    /**
     * @throws InvalidSeatCountException
     * @throws ApiException
     */
    public function createPaymentLink(int $planId, int $extraSeatCount): string
    {
        if ($extraSeatCount < 0) {
            throw new InvalidSeatCountException();
        }

        $response = $this->client->subscribe($planId, $extraSeatCount);

        if ($response->isError()) {
            throw new ApiException($response->getErrorMessage());
        }

        $response = $response->getData();

        if (
            isset($response['url']) && filter_var($response['url'], FILTER_VALIDATE_URL)
        ) {
            return $response['url'];
        }

        throw new ApiException("Unable to create checkout url");
    }

    /**
     * @return void
     */
    public function cancelSubscription(): void
    {
        $this->client->unsubscribe();
    }

    /**
     * @return void
     */
    public function undoCancellation(): void
    {
        $this->client->undoCancellation();
    }

    /**
     * @throws InvalidSeatCountException
     */
    public function getPreview(int $seatCount): array
    {
        if ($seatCount < 0) {
            throw new InvalidSeatCountException();
        }

        $subscription = $this->getActive();

        if ($subscription->isProduct()) {
            throw new \RuntimeException(bkntc__('Your subscription is managed through your Product, please go to the billing in settings to update it'));
        }

        $response = $this->client->previewSubscription($seatCount);

        if ($response->isError()) {
            throw new \RuntimeException($response->getErrorMessage() ?? bkntc__('An error occurred while processing your request'));
        }

        return $response->getData();
    }

    /**
     * @throws InvalidSeatCountException
     */
    public function update(int $seatCount): void
    {
        if ($seatCount < 0) {
            throw new InvalidSeatCountException();
        }

        $subscription = $this->getActive();

        if ($subscription->isProduct()) {
            throw new \RuntimeException(bkntc__('Your subscription is managed through your Product, please go to the billing in settings to update it'));
        }

        $response = $this->client->updateSubscription($seatCount);

        if (!$response->getStatus()) {
            throw new \RuntimeException($response->getErrorMessage() ?? bkntc__('An error occurred while processing your request'));
        }
    }
}
