<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */

?>

<div class="form-group text-center p-0 col-md-12" style="">
    <div class="card-body paypal_split_container">
        <div class="alert alert-success">Verified</div>
        <p class="card p-2"><?php echo bkntc__('Platform fee') . ': ' . $parameters['platform_fee'] . $parameters['fee_type'] ?></p>

    </div>
</div>
