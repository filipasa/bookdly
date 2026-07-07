<?php

defined( 'ABSPATH' ) or die();

use \BookneticAddon\PaypalPaymentGateway\PaypalAddon;
use function BookneticAddon\PaypalPaymentGateway\bkntc__;

?>

<div class="form-group col-md-12" style="text-align: center;">
    <img class="card-img-top" src="<?php echo PaypalAddon::loadAsset('assets/backend/icons/broken-link.svg') ?>" alt="broken-link" style="height: 4rem;">
    <div class="card-body">
        <h5 class="card-title"><?php echo bkntc__('Something went wrong...'); ?></h5>
        <p class=""><?php echo bkntc__('Please connect please contact your site administrator.'); ?></p>
    </div>
</div>