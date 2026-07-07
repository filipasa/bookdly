<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\PaypalPaymentGateway\bkntc__;
?>

<div class="form-group col-md-12">
    <label for="input_paypal_mode"><?php echo bkntc__('Mode')?>:</label>
    <select class="form-control" id="input_paypal_mode">
        <option value="sandbox" <?php echo Helper::getOption('paypal_mode', 'sandbox')=='sandbox'?'selected':''?>><?php echo bkntc__('Sandbox')?></option>
        <option value="live" <?php echo Helper::getOption('paypal_mode', 'sandbox')=='live'?'selected':''?>><?php echo bkntc__('Live')?></option>
    </select>
</div>

<div class="form-group col-md-12">
    <label for="input_paypal_client_id"><?php echo bkntc__('Client ID')?>:</label>
    <input class="form-control" id="input_paypal_client_id" value="<?php echo htmlspecialchars( Helper::getOption('paypal_client_id', '') )?>">
</div>

<div class="form-group col-md-12">
    <label for="input_paypal_client_secret"><?php echo bkntc__('Client Secret')?>:</label>
    <input class="form-control" id="input_paypal_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('paypal_client_secret', '') )?>">
</div>
