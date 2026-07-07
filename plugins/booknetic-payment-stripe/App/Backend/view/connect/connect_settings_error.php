<?php

defined( 'ABSPATH' ) or die();

use \BookneticAddon\StripePaymentGateway\StripeAddon;
use function \BookneticAddon\StripePaymentGateway\bkntc__;

?>

<div class="form-group col-md-12" style="text-align: center;">
        <img class="card-img-top" src="<?php echo StripeAddon::loadAsset('assets/backend/icons/broken-link.svg') ?>" alt="stripe_connect_error" style="height: 4rem;">
        <div class="card-body">
            <h5 class="card-title"><?php echo bkntc__('Something went wrong...'); ?></h5>
            <p class=""><?php echo bkntc__('Please contact your vendor provider.') ?></p>
        </div>
</div>
