<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Coupons\bkntc__;
?>

<div class="form-group col-md-12">
    <div class="form-control-checkbox">
        <label for="input_hide_coupon_section"><?php echo bkntc__('Hide coupon section') ?>:</label>
        <div class="fs_onoffswitch">
            <input type="checkbox" class="fs_onoffswitch-checkbox bkntc_confirm_details_checkbox"  data-slug="hide_coupon_section"
                   id="input_hide_coupon_section"<?php echo Helper::getOption('hide_coupon_section', 'off') == 'on' ? ' checked' : '' ?>>
            <label class="fs_onoffswitch-label" for="input_hide_coupon_section"></label>
        </div>
    </div>
</div>