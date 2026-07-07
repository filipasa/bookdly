<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Coupons\CouponsAddon;
use function BookneticAddon\Coupons\bkntc__;

?>

<script type="application/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/appointments_modal.js')?>"></script>

<div id="coupons-edit-tab">
    <div class="text-secondary font-size-14 text-center">
        <?php echo bkntc__( 'No coupons found' ); ?>
    </div>
</div>
