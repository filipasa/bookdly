<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Coupons\bkntc__;

?>

<div class="customer-fields-area dashed-border pb-3" data-customer="0">

    <div class="row text-primary">
        <div class="col-md-4"><?php echo bkntc__('Coupon code') ?></div>
        <div class="col-md-3"><?php echo bkntc__('Discount') ?></div>
    </div>

    <?php if ( $parameters['coupon'] ): ?>
        <div class="row mt-1">
            <div class="col-md-4">
                <div class="form-control-plaintext"><span class="btn btn-xs btn-light-warning ml-2"><?php echo mb_strtoupper( htmlspecialchars( $parameters['coupon'][ 'code' ] ) ); ?></span></div>
            </div>
            <div class="col-md-3">
                <div class="form-control-plaintext"><?php echo $parameters['coupon']['discount_type'] == 'percent' ? Math::floor( $parameters['coupon'][ 'discount' ], 2 ) . '%' . (!empty($parameters['coupon_calculated_amount']) ? ' ('. Helper::price($parameters['coupon_calculated_amount']) . ')' : '') : Helper::price($parameters['coupon']['discount']); ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="row mt-1">
            <div class="col-md-4">
                <div class="form-control-plaintext"><span class="btn btn-xs btn-light-default ml-2">N/A</span></div>
            </div>
            <div class="col-md-3">
                <div class="form-control-plaintext">N/A</div>
            </div>
        </div>
    <?php endif; ?>
</div>
