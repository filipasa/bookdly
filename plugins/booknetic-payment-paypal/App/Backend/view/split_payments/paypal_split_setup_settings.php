<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticAddon\PaypalPaymentGateway\PaypalAddon;

use function BookneticAddon\PaypalPaymentGateway\bkntc__;

/**
 * @var mixed $parameters;
 */

?>


<script type="text/javascript" src="<?php echo PaypalAddon::loadAsset('assets/backend/js/paypal-split-setup-settings.js' )?>"></script>

<div class="form-group col-md-12 text-center p-0">

    <div class="card-body paypal_split_setup_container">
        <div class="alert alert-dark">Not activated</div>
        <h5 class="card-title">Setup your platform</h5>
        <p class="card p-2"><?php echo bkntc__('Platform fee') . ': ' . $parameters['platform_fee'] . $parameters['fee_type'] ?></p>
        <p class=""><?php echo bkntc__('Click the button to start the registration process for Paypal split'); ?></p>
        <h6 style="color: rebeccapurple;"><?php echo bkntc__('By clicking the register button you agree to our'); ?> <a target="_blank" href="<?php echo Helper::getOption( 'paypal_split_terms_page', '#', false ) ?>"><?php echo bkntc__('terms and services'); ?></a></h6>
        <a href="#" class="btn btn-primary paypal_split_register_btn"><?php echo bkntc__('Register'); ?></a>
    </div>
</div>
