<?php

use BookneticApp\Backend\Mobile\DTOs\Response\MobileSubscriptionResponse;
use BookneticApp\Providers\Helpers\Helper;

defined('ABSPATH') or die();

/**
 * @var array $parameters
 * @var MobileSubscriptionResponse|null $subscription
 */
$subscription = $parameters['subscription'];
?>

<section class="billing-container overflow-auto">
    <?php if ($subscription !== null):?>
        <div class="billing-table">
            <div class="billing-table-header">
                <h2 class="m-0 p-0"><?php echo bkntc__('Billing table') ?></h2>
            </div>
            <div class="billing-table-container">
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current Plan:') ?></p>
                    <span><?php echo htmlspecialchars($subscription->getPlan()->getName()) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current additional seats:') ?></p>
                    <span><?php echo $subscription->getSeatCount() ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Additional seats on renewal:') ?></p>
                    <span><?php echo $subscription->getExtraSeatCountOnRenewal() ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next Billing Date:') ?></p>
                    <span><?php echo htmlspecialchars($subscription->getNextBillingDate()) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next Payment Amount:') ?></p>
                    <span>
                        <?php echo htmlspecialchars($subscription->getNextBillingAmount()) ?>
                        $
                    </span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Payment Method:') ?></p>
                    <span>
 <?php if ($subscription->hasPaymentMethodLabel()): ?>
     <?php echo htmlspecialchars($subscription->getPaymentMethodLabel()); ?>
 <?php endif; ?>

                    </span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-end pb-0">
                    <?php if ($subscription->isCancelAtPeriodEnd()) : ?>
                        <button class="btn-outline-secondary button undo-subscription-btn">
                            <?php echo bkntc__('Restore subscription') ?>
                        </button>
                    <?php else : ?>
                        <button class="btn-outline-secondary button cancel-subscription-btn">
                            <?php echo bkntc__('Cancel subscription') ?>
                        </button>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif;?>
    <div class="terms-and-conditions d-flex align-items-center">
        <a href="https://www.booknetic.com/terms-and-conditions"
           target="_blank"><?php echo bkntc__('Terms condition') ?></a>
        <div class="vertical-line"></div>
        <a href="https://www.booknetic.com/privacy-policy" target="_blank"><?php echo bkntc__('Privacy policy') ?></a>
    </div>
</section>

<div class="booknetic-modal payment-cancel-subscription d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Cancel subscription') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>

    <div class="modal-body">
        <p class="m-0 p-0"><?php echo bkntc__('If you cancel your subscription, you\'ll still be able to use the mobile app until your next billing date. No further payments will be charged after that since you\'ve canceled. You can reactivate your subscription anytime. Are you sure you want to cancel?') ?></p>
    </div>

    <div class="modal-footer d-flex justify-content-end">
        <button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Close') ?></button>
        <button class="modal-confirm button btn-primary m-0"><?php echo bkntc__('Confirm cancellation') ?></button>
    </div>
</div>
