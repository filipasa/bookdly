<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\PaypalPaymentGateway\PaypalAddon;
use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */

?>

<?php echo '<script type="application/javascript" src="' . PaypalAddon::loadAsset('assets/backend/js/paypal-split-settings-saas.js') . '"></script>'; ?>



<div class="form-row">

    <div class="form-group col-md-12">
        <label for="input_paypal_split_mode"><?php echo bkntc__('Mode')?>:</label>
        <select class="form-control" id="input_paypal_split_mode">
            <option value="sandbox" <?php echo Helper::getOption('paypal_split_mode', 'sandbox')=='sandbox'?'selected':''?>><?php echo bkntc__('Sandbox')?></option>
            <option value="live" <?php echo Helper::getOption('paypal_split_mode', 'sandbox')=='live'?'selected':''?>><?php echo bkntc__('Live')?></option>
        </select>
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_webhook_url"><?php echo bkntcsaas__('Webhook URI')?>:</label>
        <input class="form-control" id="input_paypal_split_webhook_url" value="<?php echo \BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper::webhookURL()?>" readonly="">
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_webhook_id"><?php echo bkntcsaas__('Webhook ID')?>:</label>
        <input class="form-control" id="input_paypal_split_webhook_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_webhook_id', '') )?>">
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_client_id"><?php echo bkntc__('Publishable key')?>:</label>
        <input class="form-control" id="input_paypal_split_client_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_client_id', '') )?>">
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_client_secret"><?php echo bkntc__('Secret key')?>:</label>
        <input class="form-control" id="input_paypal_split_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_client_secret', '') )?>">
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_merchant_id"><?php echo bkntc__('Merchant ID')?>:</label>
        <input class="form-control" id="input_paypal_split_merchant_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_merchant_id', '') )?>">
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_bn"><?php echo bkntc__('BN Code')?>:</label>
        <input class="form-control" id="input_paypal_split_bn" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_bn', '') )?>">
    </div>

    <div class="form-group col-md-6">
        <label for="input_paypal_split_platform_fee"><?php echo bkntc__('Platform Fee')?>:</label>
        <input class="form-control" id="input_paypal_split_platform_fee" value="<?php echo Helper::getOption('paypal_split_platform_fee', '0') ?>">
    </div>

    <div class="form-group col-md-6">
        <label for="input_paypal_split_fee_type"><?php echo bkntc__('Charge as')?>:</label>
        <select class="form-control" id="input_paypal_split_fee_type">
            <option value="percent"<?php echo Helper::getOption('paypal_split_fee_type', 'percent')=='percent' ? ' selected':''?>>%</option>
            <option value="price"<?php echo Helper::getOption('paypal_split_fee_type', 'percent')=='price' ? ' selected' : ''?>><?php echo htmlspecialchars( Helper::currencySymbol() )?></option>
        </select>
    </div>

    <div class="form-group col-md-12">
        <label for="input_paypal_split_terms_page"><?php echo bkntc__('Terms and Conditions Page URL')?>:</label>
        <input class="form-control" id="input_paypal_split_terms_page" value="<?php echo htmlspecialchars( Helper::getOption('paypal_split_terms_page', '') )?>">
    </div>

</div>
