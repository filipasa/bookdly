<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Mobile\DTOs\Response\MobileSubscriptionResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * @var MobileSubscriptionResponse|null $subscription
 */
$subscription = $parameters['subscription'];
$subscriptionType = $parameters['subscriptionType'] ?? 'mobile';
$initialView = $parameters['initialView'] ?? 'manage_users';
$isProductSubscription = $subscriptionType === 'product';
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/main.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/manage-users.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/password-generate-modal.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/add-member-modal.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/billing.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/settings.css', 'Mobile') ?>" type="text/css">

<script type="application/javascript" src="<?php echo Helper::assets('js/main.js', 'Mobile') ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/manage_users.js', 'Mobile') ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/billing.js', 'Mobile') ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/settings.js', 'Mobile') ?>"></script>

<script>
    var mobileAppInitialView = <?php echo json_encode($initialView) ?>;
    var mobileAppSubscriptionType = <?php echo json_encode($subscriptionType) ?>;
</script>

<section id="mobile-app" class="d-flex flex-column">
    <header>
        <h1 class="p-0 m-0 main-header"><?php echo bkntc__('Mobile App') ?></h1>
    </header>
    <div class="mobile-app-container d-flex justify-content-between">
        <div class="mobile-app-menu d-flex flex-column">
            <ul class="mobile-app-navigation d-flex flex-column m-0 p-0">
                <li>
                    <a href="#" data-view="manage_users"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/booking-steps-settings.svg', 'Settings') ?>"
                             alt="<?php echo bkntc__('Manage Users') ?>">
                        <span><?php echo bkntc__('Manage Users') ?></span>
                    </a>
                </li>
                <?php if (!$isProductSubscription): ?>
                <li>
                    <a href="#" data-view="billing"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/payments-settings.svg', 'Settings') ?>"
                             alt="<?php echo bkntc__('Billing & Plans') ?>">
                        <span><?php echo bkntc__('Billing') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="#" data-view="settings"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/general-settings.svg', 'Settings')?>"
                             alt="<?php echo bkntc__('Settings')?>">
                        <span><?php echo bkntc__('Settings')?></span>
                    </a>
                </li>
            </ul>
            <div class="gets-seats">
                <div class="d-flex align-items-center justify-content-between">
                    <label for="total-seats" class="m-0"><?php echo bkntc__('Seats') ?></label>
                    <p class="m-0">
                        <?php if ($subscription !== null): ?>
                            <?php echo bkntc__('%s of %s seats used', [$subscription->getAssignedSeatCount(), $subscription->getTotalSeatCount()]) ?>
                        <?php elseif ($isProductSubscription): ?>
                            <?php echo bkntc__('Managed by Product') ?>
                        <?php else: ?>
                            <?php echo bkntc__('No seats') ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="seats-bar">
                    <?php if ($subscription !== null): ?>
                        <progress id="total-seats" max="<?php echo $subscription->getTotalSeatCount() ?>"
                                  value="<?php echo $subscription->getAssignedSeatCount() ?>"
                                  class="w-100"></progress>
                    <?php else: ?>
                        <progress id="total-seats" max="0" value="0" class="w-100"></progress>
                    <?php endif; ?>
                </div>
                <div class="get-seats-button">
                    <?php if ($isProductSubscription): ?>
                        <span class="booknetic-mobile-button manage-seats-btn disabled"
                              title="<?php echo bkntc__('Your subscription is managed through your Product, please go to the billing in settings to update it') ?>"
                              style="cursor: not-allowed; opacity: 0.6; pointer-events: none;">
                            <?php echo bkntc__('Manage Seats') ?>
                        </span>
                    <?php else: ?>
                        <a href="#"
                           class="booknetic-mobile-button manage-seats-btn"><?php echo bkntc__('Manage Seats') ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="legal-badges mt-auto d-flex align-items-center justify-content-between">
                <a href="https://apps.apple.com/app/booknetic-admin-panel/id6755733387" target="_blank">
                    <img src="<?php echo Helper::assets('legal/app-store-badge.svg') ?>"
                         alt="<?php echo bkntc__('Apple App Store badge') ?>"/>
                </a>
                <a href="https://play.google.com/store/apps/details?id=fs.code.booknetic&hl=en" target="_blank">
                    <img src="<?php echo Helper::assets('legal/google-play-badge.png') ?>"
                         alt="<?php echo bkntc__('Google Play badge') ?>"/>
                </a>
            </div>
        </div>
        <div class="mobile-app-view-area nice-scrollbar-primary">
        </div>
    </div>
</section>

<?php if (!$isProductSubscription && $subscription !== null): ?>
<div class="booknetic-modal manage-seats-modal d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Manage seats') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center p-0">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>
    <div class="modal-body">
        <div class="d-flex justify-content-between manage-seats-container">
            <div class="seats-info d-flex flex-column">
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Seats from plan:') ?></p>
                    <span class="seats-from-plan"><?php echo $subscription->getSeatCount() ?></span>
                </div>
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current additional seats:') ?></p>
                    <span class="current-additional-seats"><?php echo $subscription->getExtraSeatCount() ?></span>
                </div>
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Assigned (used) seats:') ?></p>
                    <span class="used-seats"><?php echo $subscription->getAssignedSeatCount() ?></span>
                </div>
                <div class="price-per-additional-seat">
                    <div class="seat-row d-flex align-items-center justify-content-between">
                        <p class="m-0 p-0"><?php echo bkntc__('Price per additional seat:') ?></p>
                        <span class="price-additional-seat">
                         <?php echo $subscription->getPlan()->getSeatPrice() ?>
                         <?php echo htmlspecialchars($subscription->getCurrency()) ?>
                        </span>
                    </div>
                </div>
                <div class="new-additional-seat-container">
                    <div class="new-additional-seats d-flex align-items-center justify-content-between">
                        <p class="m-0 p-0"><?php echo bkntc__('Manage additional seats:') ?></p>
                        <div class="seat-input d-flex align-items-center justify-content-between">
                            <div class="seat-btn decrement-btn cursor-pointer" <?php echo $subscription->getExtraSeatCount() === 0 ? 'disabled' : '' ?>>
                                <img src="<?php echo Helper::assets('images/decrease-icon.svg', 'Mobile') ?>" alt=""
                                     width="20px" height="20px">
                            </div>
                            <input type="number" class="additional-seat-input text-center" min="<?php echo $subscription->getExtraSeatCount() ?>" max="<?php echo $subscription->getPlan()->getExtraSeatLimit() ?>" value="<?php echo $subscription->getExtraSeatCount() ?>"/>
                            <div class="seat-btn increment-btn cursor-pointer" <?php echo $subscription->getExtraSeatCount() >= $subscription->getPlan()->getExtraSeatLimit() ? 'disabled' : '' ?>>
                                <img src="<?php echo Helper::assets('images/increase-icon.svg', 'Mobile') ?>" alt=""
                                     width="20px" height="20px">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="billing-info d-flex flex-column">
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Billing interval:') ?></p>
                    <span><?php echo bkntc__('Annual') ?></span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next billing date:') ?></p>
                    <span class="next-billing-date"><?php echo htmlspecialchars($subscription->getNextBillingDate()) ?></span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Due today:') ?></p>
                    <span class="subtotal">
                        0
                        <?php echo htmlspecialchars($subscription->getCurrency()) ?>
                    </span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next billing payment:') ?></p>
                    <span class="next-billing-payment">
                        <?php echo htmlspecialchars($subscription->getNextBillingAmount()) ?>
                        <?php echo htmlspecialchars($subscription->getCurrency()) ?>
                    </span>
                </div>
                <button class="btn-primary button pay-and-activate-btn" <?php echo !$subscription->getPlan()->hasExtraSeatLimit() ? 'disabled' : '' ?>>
                    <?php echo bkntc__('Pay and activate') ?>
                </button>
            </div>
        </div>
        <div class="manage-seats-info">
            <?php echo bkntc__('When you purchase additional seats, the amount you pay will be prorated based on the remaining time until your next billing date, ensuring you\'re charged fairly.') ?>
            <br/>
            <br/>
            <?php echo bkntc__('Seat reductions do not apply immediately. You will retain access to your current number of seats until your next billing date. The downgrade will take effect after your subscription renews.') ?>
        </div>
    </div>
    <div class="terms-and-conditions d-flex align-items-center mt-0">
        <a href="https://www.booknetic.com/terms-and-conditions"
           target="_blank"><?php echo bkntc__('Terms condition') ?></a>
        <div class="vertical-line"></div>
        <a href="https://www.booknetic.com/privacy-policy" target="_blank"><?php echo bkntc__('Privacy policy') ?></a>
    </div>
</div>
<?php endif; ?>

<div id="modal-overlay" class="modal-overlay d-none"></div>
