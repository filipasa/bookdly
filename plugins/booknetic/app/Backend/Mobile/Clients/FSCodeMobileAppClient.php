<?php

namespace BookneticApp\Backend\Mobile\Clients;

use BookneticApp\Backend\Mobile\Clients\Models\MobileAppSeatsResponse;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\FSCode\DTOs\Response\ApiResponse;

class FSCodeMobileAppClient
{
    private FSCodeAPIClient $client;

    public function __construct(FSCodeAPIClient $client)
    {
        $this->client = $client;
    }

    public function getPlans(): ApiResponse
    {
        return $this->request('plans');
    }

    public function subscribe(int $planId, int $extraSeatCount): ApiResponse
    {
        return $this->request('subscription', 'POST', [
            'planId' => $planId,
            'extraSeatCount' => $extraSeatCount,
        ]);
    }

    public function unsubscribe(): void
    {
        $this->request('subscription', 'DELETE');
    }

    public function undoCancellation(): void
    {
        $this->request('subscription/undo-cancel', 'POST');
    }

    /**
     * @return array{type: string, subscription: array|null}
     */
    public function getActiveSubscription(): array
    {
        $response = $this->request('subscription/active');

        $data = $response->getData() ?? [];

        return [
            'type' => $data['type'] ?? 'none',
            'subscription' => $data['subscription'] ?? null,
        ];
    }

    public function updateSubscription(int $seatCount): ApiResponse
    {
        return $this->request('subscription', 'PATCH', ['extraSeatCount' => $seatCount]);
    }

    public function previewSubscription(int $seatCount): ApiResponse
    {
        return $this->request('subscription/preview', 'POST', ['extraSeatCount' => $seatCount]);
    }

    public function getSeats(): MobileAppSeatsResponse
    {
        $response = $this->request('seats');
        $data = $response->getData();

        return new MobileAppSeatsResponse(
            (array) ($data['assignedSeats'] ?? []),
            (int) ($data['availableSeats'] ?? 0)
        );
    }

    public function getSeatsByUsername(string $username): MobileAppSeatsResponse
    {
        $response = $this->request("seats", 'GET', ['username' => $username]);
        $data = $response->getData();

        return new MobileAppSeatsResponse(
            (array) ($data['assignedSeats'] ?? []),
            (int) ($data['availableSeats'] ?? 0)
        );
    }

    public function assignSeat(string $username): ApiResponse
    {
        return $this->request('seats', 'POST', ['username' => $username]);
    }

    public function logoutSeat(int $id): void
    {
        $this->request(sprintf('seats/%d/logout', $id), 'POST');
    }

    public function unassignSeat(int $id): void
    {
        $this->request(sprintf('seats/%d', $id), 'DELETE');
    }

    public function sendNotification(string $username, string $title, string $body, array $data = []): ApiResponse
    {
        $payload = [
            'username' => $username,
            'title'    => $title,
            'body'     => $body,
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        return $this->request('notifications/send', 'POST', $payload);
    }

    private function request(string $endpoint, string $method = 'GET', array $data = []): ApiResponse
    {
        return $this->client->requestNew(sprintf('mobile-app/admin/%s', $endpoint), $method, $data, 'v1');
    }
}
