<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

/**
 * @var $parameters
 */
$currentPlanName = htmlspecialchars($parameters[ 'current_plan' ]->name ?? '-');

?>

<?php
use BookneticSaaS\Models\TenantBilling;
use BookneticApp\Providers\Core\Permission;

$tenantInf = Permission::tenantInf();

$lastBilling = TenantBilling::where('tenant_id', Permission::tenantId())
    ->where('status', 'paid')
    ->orderBy('id', 'DESC')
    ->fetch();

$billingCycle = '-';
$planCost = '-';

if ($lastBilling) {
    $billingCycle = $lastBilling->payment_cycle === 'annually' ? bkntcsaas__('Annual') : bkntcsaas__('Monthly');
    $planCost = Helper::price($lastBilling->amount);
} else {
    // Fallback if no payment history is found yet
    $billingCycle = bkntcsaas__('Monthly');
    $planCost = Helper::price($parameters['current_plan']->monthly_price ?? 0);
}

$isSubscriptionActive = !empty($parameters['active_subscription']);
$isTrial = !empty($parameters['trial_info']['is_trial']);
$isExpired = empty($tenantInf->expires_in) || strtotime($tenantInf->expires_in) < time();

if (!$isSubscriptionActive && !$isTrial) {
    if (!$isExpired && !empty($parameters['current_plan'])) {
        $currentPlanName = htmlspecialchars($parameters['current_plan']->name ?? '-');
        $billingCycle = bkntcsaas__('Canceled');
    } else {
        $currentPlanName = bkntcsaas__('None');
        $billingCycle = bkntcsaas__('Canceled');
    }
}
?>



<div class="billing_grid_container">
    <!-- Left Card: Current Plan Summary -->
    <div class="billing_card_summary">
        <div class="billing_card_header">
            <span class="billing_card_title"><?php echo bkntcsaas__('Current Plan Summary')?></span>
            <div class="billing_card_actions">
                <?php if (!empty($parameters['active_subscription'])): ?>
                    <button type="button" class="btn_cancel_subscription" id="cancel_subscription_btn"><?php echo bkntcsaas__('Cancel')?></button>
                <?php endif; ?>
                <button type="button" class="btn_upgrade_billing" id="upgrade_plan_btn"><?php echo bkntcsaas__('Upgrade')?></button>
            </div>
        </div>
        <div class="billing_card_body_plan">
            <div class="plan_detail_col">
                <span class="plan_detail_label"><?php echo bkntcsaas__('PLAN NAME')?></span>
                <span class="plan_detail_val"><?php echo $currentPlanName ?></span>
            </div>
            <div class="plan_detail_col">
                <span class="plan_detail_label"><?php echo bkntcsaas__('BILLING CYCLE')?></span>
                <span class="plan_detail_val"><?php echo $billingCycle ?></span>
            </div>
            <?php if ($isSubscriptionActive || $isTrial): ?>
            <div class="plan_detail_col">
                <span class="plan_detail_label"><?php echo bkntcsaas__('PLAN COST')?></span>
                <span class="plan_detail_val"><?php echo $planCost ?></span>
            </div>
            <?php endif; ?>
            <?php if (!$isSubscriptionActive && !$isTrial && !$isExpired): ?>
            <div class="plan_detail_col">
                <span class="plan_detail_label"><?php echo bkntcsaas__('EXPIRES ON')?></span>
                <span class="plan_detail_val" style="color: #EF4444 !important;"><?php echo htmlspecialchars(substr($tenantInf->expires_in, 0, 10)) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($parameters['trial_info']) && $parameters['trial_info']['is_trial']): ?>
                <div class="plan_detail_col">
                    <span class="plan_detail_label"><?php echo bkntcsaas__('TRIAL PERIOD')?></span>
                    <span class="plan_detail_val" style="color: #F59E0B !important;"><?php echo bkntcsaas__('%d days left', [ $parameters['trial_info']['days_left'] ]) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Card: Payment Method -->
    <div class="billing_card_payment">
        <div class="billing_card_header">
            <span class="billing_card_title"><?php echo bkntcsaas__('Payment Method')?></span>
        </div>
        <div class="billing_card_body_payment">
            <?php if ($lastBilling && $lastBilling->payment_method === 'credit_card'): ?>
                <?php
                $cardBrand = 'Credit Card';
                $cardLast4 = '4002';
                $cardExpiry = '20/2026';
                if (!empty($parameters['card_info'])) {
                    $cardBrand = ucfirst($parameters['card_info']['brand']);
                    $cardLast4 = $parameters['card_info']['last4'];
                    $cardExpiry = $parameters['card_info']['exp_month'] . '/' . $parameters['card_info']['exp_year'];
                }
                ?>
                <div class="payment_method_pill">
                    <div class="payment_method_left">
                        <?php if (!empty($parameters['card_info']) && strtolower($parameters['card_info']['brand']) === 'visa'): ?>
                            <i class="fab fa-cc-visa" style="font-size: 28px; color: #1A1F71; margin-right: 12px; margin-top: 4px;"></i>
                        <?php elseif (!empty($parameters['card_info']) && strtolower($parameters['card_info']['brand']) === 'amex'): ?>
                            <i class="fab fa-cc-amex" style="font-size: 28px; color: #007bc1; margin-right: 12px; margin-top: 4px;"></i>
                        <?php elseif (!empty($parameters['card_info']) && strtolower($parameters['card_info']['brand']) === 'mastercard'): ?>
                            <!-- MasterCard logo SVG -->
                            <svg class="mastercard_logo" width="36" height="24" viewBox="0 0 36 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#EB001B" />
                                <circle cx="24" cy="12" r="10" fill="#F79E1B" fill-opacity="0.8" />
                            </svg>
                        <?php else: ?>
                            <i class="far fa-credit-card" style="font-size: 28px; color: #5B6BF8; margin-right: 12px; margin-top: 4px;"></i>
                        <?php endif; ?>
                        <div class="payment_card_info">
                            <span class="payment_card_brand"><?php echo bkntcsaas__($cardBrand)?></span>
                            <span class="payment_card_digits">**** **** **** <?php echo htmlspecialchars($cardLast4) ?></span>
                            <span class="payment_card_expiry"><?php echo bkntcsaas__('Expiry on %s', [ htmlspecialchars($cardExpiry) ])?></span>
                            <span class="payment_card_email">
                                <i class="far fa-envelope"></i> <?php echo htmlspecialchars($tenantInf->email) ?>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn_change_payment" id="change_plan_btn_shortcut"><?php echo bkntcsaas__('Change')?></button>
                </div>
            <?php elseif ($lastBilling && $lastBilling->payment_method === 'paypal'): ?>
                <div class="payment_method_pill">
                    <div class="payment_method_left">
                        <img src="<?php echo Helper::assets('images/paypal.svg', 'Billing')?>" style="height: 24px; width: auto; margin-right: 12px; vertical-align: middle;">
                        <div class="payment_card_info">
                            <span class="payment_card_brand"><?php echo bkntcsaas__('PayPal Account')?></span>
                            <span class="payment_card_email">
                                <i class="far fa-envelope"></i> <?php echo htmlspecialchars($tenantInf->email) ?>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn_change_payment" id="change_plan_btn_shortcut"><?php echo bkntcsaas__('Change')?></button>
                </div>
            <?php else: ?>
                <div class="payment_method_pill">
                    <div class="payment_method_left">
                        <i class="fas fa-wallet" style="font-size: 24px; color: #5B6BF8; margin-right: 12px; vertical-align: middle;"></i>
                        <div class="payment_card_info">
                            <span class="payment_card_brand"><?php echo bkntcsaas__('Wallet Balance')?></span>
                            <span class="payment_card_digits"><?php echo Helper::price($tenantInf->money_balance) ?></span>
                            <span class="payment_card_email">
                                <i class="far fa-envelope"></i> <?php echo htmlspecialchars($tenantInf->email) ?>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn_change_payment" id="change_plan_btn_shortcut"><?php echo bkntcsaas__('Change')?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        var $mHeader = $('.m_header');
        if ($mHeader.length) {
            $('.billing_grid_container').before($mHeader);
        }

        $(document).on('click', '#change_plan_btn_shortcut', function(e) {
            e.preventDefault();
            
            <?php if ($lastBilling && $lastBilling->payment_method === 'credit_card' && !empty($parameters['active_subscription']) && strpos($parameters['active_subscription'], 'sub_') === 0): ?>
                var btn = $(this);
                if (btn.hasClass('loading')) return;
                btn.addClass('loading').css('opacity', '0.6');
                
                booknetic.ajax('get_stripe_portal_url', {}, function(res) {
                    btn.removeClass('loading').css('opacity', '1');
                    if (res.status && res.url) {
                        window.location.href = res.url;
                    } else {
                        booknetic.toast(res.error || 'Failed to load Stripe Billing Portal.', 'unsuccess');
                    }
                });
            <?php else: ?>
                $('#upgrade_plan_btn').click();
            <?php endif; ?>
        });
    });
</script>

<div id="choose_plan_window">
    <div class="close_choose_plan_window_btn">
        <img src="<?php echo Helper::icon('cross.svg')?>">
    </div>

    <div class="choose_plan_title">
        <?php echo bkntcsaas__('Choose a plan')?>
    </div>

    <div class="choose_plan_subtitle">
        <?php echo bkntcsaas__('Upgrade your account')?>
    </div>

    <div class="choose_plan_payment_cycle">
        <div class="payment_cycle active_payment_cycle">
            <?php echo bkntcsaas__('Monthly')?>
        </div>

        <div class="payment_cycle_swicher">
            <input type="checkbox"
                   class="payment_cycle_swicher_checkbox"
                   id="input_payment_cycle_swicher"
                    <?php echo Helper::getOption('default_interval_on_pricing', 'monthly') === 'annual'
                            ? ' checked'
                            : '' ?>>
            <label class="payment_cycle_swicher_label" for="input_payment_cycle_swicher"></label>
        </div>

        <div class="payment_cycle position-relative">
            <?php echo bkntcsaas__('Annual')?>
            <?php if ($parameters['is_annual_plan_badge_enabled']):?>
                <span class="annual-badge position-absolute" style="background-color: <?php echo htmlspecialchars($parameters['annual_plan_badge_color'])?>"><?php echo htmlspecialchars($parameters['annual_plan_badge_text'])?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="plans_area">
        <?php foreach ($parameters['plans'] as $plan): ?>
            <?php $is_featured = ($plan->is_default == 1); ?>
            <div class="plan_card<?php echo $is_featured ? ' featured_card' : '' ?>" data-plan-id="<?php echo (int)$plan->id ?>">

                <?php if (!empty($plan->ribbon_text)): ?>
                    <div class="plan_ribbon">
                        <div><?php echo htmlspecialchars($plan->ribbon_text) ?></div>
                    </div>
                <?php endif; ?>

                <div class="plan_title">
                    <?php echo htmlspecialchars($plan->name) ?>
                </div>

                <?php 
                $description = $plan->description ?? '';
                $short_desc = '';
                $features_html = '';
                if (strpos($description, '<ul') !== false) {
                    list($short_desc, $features_html) = explode('<ul', $description, 2);
                    $features_html = '<ul' . $features_html;
                } else {
                    $short_desc = $description;
                }
                ?>

                <div class="plan_description">
                    <?php echo wp_kses_post($short_desc); ?>
                </div>

                <!-- MONTHLY PRICE -->
                <div class="plan_price" data-price="monthly">
                    <?php echo Helper::price(
                        $plan->monthly_price *
                        ($plan->monthly_price_discount > 0 && empty($plan->actual_monthly_discount)
                                ? (100 - $plan->monthly_price_discount) / 100
                                : 1)
                    ) ?>
                </div>

                <!-- ANNUAL PRICE -->
                <div class="plan_price hidden" data-price="annually">
                    <?php
                    echo Helper::price(
                        $parameters['show_monthly_breakdown_on_annual']
                                ? $plan->annual_monthly_breakdown
                                : (
                                    $plan->annually_price *
                                    ($plan->annually_price_discount > 0 && empty($plan->actual_annually_discount)
                                        ? (100 - $plan->annually_price_discount) / 100
                                        : 1)
                                )
                    );
                    ?>
                </div>

                <!-- MONTHLY SUBTITLE -->
                <div class="plan_subtitle" data-price="monthly">
                    <?php if ($plan->monthly_price_discount > 0 && empty($plan->actual_monthly_discount)): ?>
                        <div class="plan_subtitle_discount_line">
                            <?php echo bkntcsaas__(
                                '%d%% off ( Normally %s )',
                                [
                                        (int)$plan->monthly_price_discount,
                                        Helper::price($plan->monthly_price)
                                ]
                            ); ?>
                        </div>
                    <?php endif; ?>
                    <div><?php echo bkntcsaas__('per month') ?></div>
                </div>

                <!-- ANNUAL SUBTITLE -->
                <div class="plan_subtitle hidden" data-price="annually">
                    <?php if (!$parameters['show_monthly_breakdown_on_annual']
                            && $plan->annually_price_discount > 0
                            && empty($plan->actual_annually_discount)): ?>
                        <div class="plan_subtitle_discount_line">
                            <?php echo bkntcsaas__(
                                '%d%% off ( Normally %s )',
                                [
                                        (int)$plan->annually_price_discount,
                                        Helper::price($plan->annually_price)
                                ]
                            ); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <?php echo $parameters['show_monthly_breakdown_on_annual']
                                ? bkntcsaas__('per month')
                                : bkntcsaas__('per year'); ?>
                    </div>
                </div>

                <?php if (!empty($features_html)): ?>
                    <div class="plan_description_features" style="margin-top: 20px; flex: 1;">
                        <?php echo wp_kses_post($features_html); ?>
                    </div>
                <?php endif; ?>

                <div class="plan_footer">
                    <?php
                    $isSelectedPlan = $plan->id === $parameters['current_plan']->id && ($isSubscriptionActive || $isTrial);

                    if ($isSelectedPlan) {
                        $buttonText = bkntcsaas__('SELECTED');
                        $disabled = 'disabled';
                    } else {
                        $buttonText = bkntcsaas__('CHOOSE');
                        $disabled = '';
                    }
                    ?>

                    <button type="button"
                            class="btn btn-primary choose_plan_btn"
                            style="background: <?php echo htmlspecialchars($plan->color) ?> !important;"
                            <?php echo $disabled ?>>
                        <?php echo $buttonText ?>
                    </button>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</div>
<div id="choose_payment_method_window">
    <div class="choose_payment_method_back_btn"><img src="<?php echo Helper::icon('arrow.svg')?>"> <?php echo bkntcsaas__('back')?></div>
    <div class="close_choose_payment_method_window_btn"><img src="<?php echo Helper::icon('cross.svg')?>"></div>
    <div class="choose_payment_method_title"><?php echo bkntcsaas__('Select payment method')?></div>
    <div class="choose_payment_method_subtitle"><?php echo bkntcsaas__('You have chosen %s plan', ['<span id="chosen_plan_name"></span>'], false)?></div>

    <div class="payment_methods_area">
        <?php $availablePaymentMethodsCount = 0;?>
        <?php foreach ($parameters['payment_gateways_order'] as $payment_gateway):?>
            <?php if ($payment_gateway == 'stripe' && Helper::getOption('stripe_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="credit_card">
                    <img src="<?php echo Helper::assets('images/credit_card.svg', 'Billing')?>">
                    <span class="payment_method_card_subtitle"><?php echo bkntcsaas__('Credit card')?></span>
                </div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
            <?php if ($payment_gateway == 'paypal' && Helper::getOption('paypal_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="paypal"><img src="<?php echo Helper::assets('images/paypal.svg', 'Billing')?>" class="paypal_img"></div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
            <?php if ($payment_gateway == 'woocommerce' && Helper::getOption('woocommerce_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="balance">
                    <img src="<?php echo Helper::assets('images/wallet.png', 'Billing')?>">
                    <span class="payment_method_card_subtitle"><?php echo bkntcsaas__('Balance')?></span>
                </div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
        <?php endforeach;?>
        <?php if (!$availablePaymentMethodsCount):?>
            <div><?php echo bkntcsaas__('No available payment methods!')?></div>
        <?php endif;?>
    </div>

</div>

<div id="payment_succeeded_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'success' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_succeeded_popup_body">
        <div class="payment_succeeded_img">
            <img src="<?php echo Helper::assets('images/payment_success.svg', 'Billing')?>">
        </div>
        <div class="payment_succeeded_title"><?php echo bkntcsaas__('Payment Successful')?></div>
        <div class="payment_succeeded_subtitle"><?php echo bkntcsaas__('It might take some time to activate your new plan.')?></div>
        <div class="payment_succeeded_footer">
            <button type="button" class="btn btn-primary close_payment_succeeded_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<div id="payment_canceled_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'cancel' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_canceled_popup_body">
        <div class="payment_canceled_img">
            <img src="<?php echo Helper::assets('images/payment_canceled.svg', 'Billing')?>">
        </div>
        <div class="payment_canceled_title"><?php echo bkntcsaas__('Payment Canceled')?></div>
        <div class="payment_canceled_subtitle"><?php echo bkntcsaas__("We aren't able to process your payment. Please try again.")?></div>
        <div class="payment_canceled_footer">
            <button type="button" class="btn btn-primary close_payment_canceled_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<div id="payment_pending_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'pending' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_pending_popup_body">
        <div class="payment_pending_img">
            <img src="<?php echo Helper::assets('images/payment_pending.jpg', 'Billing')?>">
        </div>
        <div class="payment_pending_title"><?php echo bkntcsaas__('Payment Pending')?></div>
        <div class="payment_pending_subtitle"><?php echo bkntcsaas__("We have received your payment request and it is currently pending")?></div>
        <div class="payment_pending_footer">
            <button type="button" class="btn btn-primary close_payment_pending_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<?php
echo $parameters['table'];
?>

<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/billing.css', 'Billing')?>" />
<script type="application/javascript" src="<?php echo Helper::assets('js/billing.js', 'Billing')?>"></script>
<script src="//js.stripe.com/v3/"></script>

<script type="application/javascript">
    localization['cancel_subscription_text'] = <?php echo json_encode(bkntcsaas__('Are you sure you want to cancel subscription?')) ?>;
    localization['YES'] = <?php echo json_encode(bkntcsaas__('YES'))?>;
    localization['NO'] = <?php echo json_encode(bkntcsaas__('NO'))?>;
    var stripe_client_id = <?php echo json_encode(htmlspecialchars(Helper::getOption('stripe_client_id', ''))) ?>;
</script>