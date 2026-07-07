<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\DTOs\Response\MobileSubscriptionResponse;
use BookneticApp\Backend\Mobile\Services\SeatService;
use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;

class MobileAppViewAjaxController extends Controller
{
    private SeatService $seatService;

    private SubscriptionService $subscriptionService;

    public function __construct(
        SeatService $seatService,
        SubscriptionService $subscriptionService
    ) {
        $this->seatService = $seatService;
        $this->subscriptionService = $subscriptionService;
    }

    public function manage_users()
    {
        $response = $this->seatService->getAll();

        $html = Helper::renderView('Mobile.Controllers.view.manage_users', [
            'availableSeats' => $response['availableSeats'],
            'users'          => $response['users'],
        ]);

        return $this->response(true, ['html' => $html]);
    }

    public function billing()
    {
        $subscriptionData = $this->subscriptionService->getActive();

        if ($subscriptionData->isProduct()) {
            return $this->response(false, bkntc__('Billing is not available for product subscriptions'));
        }

        $subscription = MobileSubscriptionResponse::fromArray($subscriptionData->getSubscription());

        $html = Helper::renderView('Mobile.Controllers.view.billing', [
            'subscription' => $subscription,
        ]);

        return $this->response(true, ['html' => $html]);
    }

    public function settings()
    {
        $allowStaffToRegenerateAppPassword = Helper::getOption('mobile_app_allow_staff_to_regenerate_app_password');

        $html = Helper::renderView('Mobile.Controllers.view.settings', [
            'allow_staff_to_regenerate_app_password' => $allowStaffToRegenerateAppPassword,
        ]);

        return $this->response(true, ['html' => $html]);
    }
}
