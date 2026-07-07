<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Coupons\bkntc__;
?>

<div class="customer-fields-area dashed-border pb-2" data-customer="0">
    <div class="form-row">
        <div class="form-group col-md-12">
            <select class="form-control input_coupon">
                <option value="-1"><?php echo bkntc__('- none -') ?></option>

                <?php foreach ( $parameters['available_coupons'] as $cpn ): ?>
                    <option value="<?php echo (int)$cpn->id ?>" <?php echo $cpn->id == $parameters['coupon'] ? 'selected' : '' ?>><?php echo htmlspecialchars( $cpn->code ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
