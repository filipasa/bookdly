<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters;
 */

use BookneticAddon\StripePaymentGateway\StripeAddon;

use function BookneticAddon\StripePaymentGateway\bkntc__;

?>

<script type="text/javascript" src="<?php echo StripeAddon::loadAsset('assets/backend/js/connect_setup_settings.js' )?>"></script>

<div class="form-group col-md-12 text-center p-0">

    <div class="card-body stripe_connect_setup_container">
        <div class="alert alert-dark"><?php echo bkntc__('Not activated') ?></div>
        <h5 class="card-title"><?php echo bkntc__('Setup your platform'); ?></h5>
        <p class="card p-2"><?php echo bkntc__('Platform fee') . ': ' . $parameters['pricing'][0] . $parameters['pricing'][1] ?></p>
        <p><?php echo bkntc__('Click the button to start the registration process for Stripe Connect'); ?></p>
        <h6 style="color: rebeccapurple;"><?php echo bkntc__('By clicking the register button you agree to our'); ?> <a href="<?php echo $parameters['tos_page']; ?>"> <?php echo bkntc__('terms and services'); ?></a></h6>
        <a href="#" class="btn btn-primary stripe_connect_register_btn"><?php echo bkntc__('Register'); ?></a>
    </div>
</div>
