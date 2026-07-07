<?php

namespace BookneticApp\Backend\Mobile\Controllers;

use BookneticApp\Backend\Mobile\DTOs\Response\MobileSubscriptionResponse;
use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Backend\Mobile\Services\PlanService;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;

class MobileAppController extends Controller
{
    private PlanService $planService;

    private SubscriptionService $subscriptionService;

    public function __construct(
        PlanService $planService,
        SubscriptionService $subscriptionService
    ) {
        $this->planService = $planService;
        $this->subscriptionService = $subscriptionService;
    }

    public function index(): void
    {
        $subscriptionData = $this->subscriptionService->getActive();

        if ($subscriptionData->isNone()) {
            $plans = $this->planService->getAll();
            $this->view('plan', [
                'plans' => $plans
            ]);

            return;
        }

        $initialView = Helper::_get('view', 'manage_users', 'string', [
            'manage_users', 'billing', 'settings'
        ]);

        $subscription = MobileSubscriptionResponse::fromArray($subscriptionData->getSubscription());

        $this->view('index', [
            'subscription'     => $subscription,
            'subscriptionType' => $subscriptionData->getType(),
            'initialView'      => $initialView,
        ]);
    }
}
