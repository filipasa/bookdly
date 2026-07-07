<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\PaypalPaymentGateway\PaypalAddon;
use function BookneticAddon\PaypalPaymentGateway\bkntc__;

/**
 * @var mixed $parameters
 */

?>

<div class="form-group text-center p-0 col-md-12" style="">
    <div class="card-body paypal_split_verify_container">
        <div class="alert alert-warning"><?php echo bkntc__('Activation pending'); ?></div>
        <h5 class="card-title"><?php echo $parameters['tenant_message']; ?></h5>
    </div>
</div>
