<?php


defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */

use BookneticAddon\StripePaymentGateway\StripeAddon;
use function BookneticAddon\StripePaymentGateway\bkntc__;

?>

<script type="text/javascript" src="<?php echo StripeAddon::loadAsset('assets/backend/js/connect_register_settings.js' )?>"></script>


<div class="form-group text-center p-0 col-md-12" style="">
    <div class="card-body stripe_connect_register_container">
        <div class="alert alert-warning"><?php echo bkntc__('Activation pending'); ?></div>
        <h5 class="card-title"><?php echo bkntc__('Your account is under review'); ?></h5>
        <div class="form-group">
            <p class="card-title"><?php echo bkntc__('Please complete these verifications below to activate your account') ?>:</p>
            <ul class="list-group" style="text-align: initial;">
            <?php foreach ( $parameters['requirments'] as $requirment ): ?>
                <li class="list-group-item"><?php echo $requirment ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php if ( !empty( $parameters['reason'] ) ): ?>
            <a href="#" class="btn btn-primary stripe_connect_verify_btn"><?php echo bkntc__('Continue verifying'); ?></a>
        <?php endif; ?>
    </div>
</div>
