<?php

use BookneticAddon\StripePaymentGateway\StripeAddon;

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */

use function BookneticAddon\StripePaymentGateway\bkntc__;
?>

<script type="text/javascript" src="<?php echo StripeAddon::loadAsset('assets/backend/js/connect_settings.js' )?>"></script>


<div class="form-group text-center p-0 col-md-12" style="">
    <div class="card-body stripe_connect_container">

        <div class="alert alert-success"><?php echo bkntc__( 'Verified' ) ?></div>

        <p class="card p-2"><?php echo bkntc__('Platform fee') . ': ' . $parameters['pricing'][0] . $parameters['pricing'][1] ?></p>
        <h5 class="card-title"><?php echo bkntc__( 'You can view your Dashboard here:' ) ?></h5>

        <a href="#" class="btn btn-primary stripe_connect_btn"><?php echo bkntc__( 'Login' ) ?></a>
    </div>
</div>
