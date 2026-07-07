<?php

defined( 'ABSPATH' ) or die();

/**
 * @var mixed $parameters
 */
use BookneticAddon\Coupons\CouponsAddon;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Coupons\bkntc__;

?>
<script type="text/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/coupons_add_new_details_tab.js')?>" id="add_new_details_tab_JS" data-coupon-id="<?php echo (int)$parameters['coupon']['id']?>"></script>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_code"><?php echo bkntc__('Code')?></label>
        <input type="text" class="form-control" id="input_code" value="<?php echo mb_strtoupper( htmlspecialchars( $parameters[ 'coupon' ][ 'code' ] ) ); ?>">
    </div>
    <div class="form-group col-md-6">
        <label for="input_discount"><?php echo bkntc__('Discount')?></label>
        <div class="input-group">
            <input type="text" class="form-control" id="input_discount" value="<?php echo htmlspecialchars($parameters['coupon']['discount'])?>">
            <select id="input_discount_type" class="form-control col-md-6 m-0">
                <option value="percent"<?php echo $parameters['coupon']['discount_type']=='percent'?' selected':''?>>%</option>
                <option value="price"<?php echo $parameters['coupon']['discount_type']=='price'?' selected':''?>><?php echo htmlspecialchars(Helper::currencySymbol())?></option>
            </select>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_start_date"><?php echo bkntc__('Applies date from')?></label>
        <input data-date-format="<?php echo (htmlspecialchars(Helper::getOption('date_format', 'Y-m-d')))?>" type="text" class="form-control" id="input_start_date" value="<?php echo empty($parameters['coupon']['start_date']) ? '' :  Date::convertDateFormat( $parameters['coupon']['start_date'] ) ?>" placeholder="<?php echo bkntc__('Life time')?>">
    </div>
    <div class="form-group col-md-6">
        <label for="input_end_date"><?php echo bkntc__('Applies date to')?></label>
        <input type="text" class="form-control" id="input_end_date" value="<?php echo empty($parameters['coupon']['end_date']) ? '' :  Date::convertDateFormat( $parameters['coupon']['end_date'] ) ?>" placeholder="<?php echo bkntc__('Life time')?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-5">
        <label for="input_usage_limit"><?php echo bkntc__('Usage limit')?></label>
        <input type="text" class="form-control" id="input_usage_limit" value="<?php echo htmlspecialchars($parameters['coupon']['usage_limit'])?>" placeholder="<?php echo bkntc__('No limit')?>">
    </div>
    <div class="form-group col-md-7">
        <label for="input_once_per_select"><?php echo bkntc__('Once per') ?><i class="fa fa-info-circle help-icon do_tooltip" data-content="<?php echo bkntc__('Customer - Each customer can use the coupon only once. <br> Booking - Coupon will be applied only to a single appointment' )?>"></i></label>
            <select class="form-control" id="input_once_per_select" multiple>
                <option value="once_per_customer" <?php echo $parameters['coupon']['once_per_customer']? 'selected': '' ?>><?php echo bkntc__('Customer' ) ?></option>
                <option value="once_per_booking" <?php echo $parameters['coupon']['once_per_booking']? 'selected': '' ?>><?php echo bkntc__('Booking' ) ?></option>
            </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_services"><?php echo bkntc__('Services filter')?></label>
        <select class="form-control" id="input_services" multiple>
            <?php
            foreach ( $parameters['services'] AS $service )
            {
                echo '<option value="' . (int)$service[0] . '" selected>' . htmlspecialchars($service[1]) . '</option>';
            }
            ?>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_staff"><?php echo bkntc__('Staff filter')?></label>
        <select class="form-control" id="input_staff" multiple>
            <?php
            foreach ( $parameters['staff'] AS $staff )
            {
                echo '<option value="' . (int)$staff[0] . '" selected>' . htmlspecialchars($staff[1]) . '</option>';
            }
            ?>
        </select>
    </div>
</div>