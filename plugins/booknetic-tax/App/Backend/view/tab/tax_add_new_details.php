<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Tax\bkntc__;

/**
 * @var mixed $parameters
 */
?>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="input_name"> <?php echo bkntc__('Name')?></label>
        <input type="text" data-multilang="true" data-multilang-fk="<?php echo $parameters[ 'tax' ]['id'] ?>" class="form-control" id="input_name" value="<?php echo htmlspecialchars($parameters['tax']['name'])?>">
    </div>
    <div class="form-group col-md-6">
        <label for="input_value"><?php echo bkntc__('Amount')?></label>
        <div class="input-group">
            <input type="text" class="form-control" id="input_value" value="<?php echo Math::floor( $parameters['tax']['value'], 2 ); ?>">
            <select id="input_type" class="form-control col-md-6 m-0">
                <option value="percent"<?php echo $parameters['tax']['type']=='percent'?' selected':''?>>%</option>
                <option value="absolute"<?php echo $parameters['tax']['type']=='absolute'?' selected':''?>><?php echo htmlspecialchars(Helper::currencySymbol())?></option>
            </select>
        </div>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="input_locations"><?php echo bkntc__('Locations filter')?></label>
        <select class="form-control" id="input_locations" multiple>
            <?php
            foreach ( $parameters['locations'] AS $location )
            {
                echo '<option value="' . (int)$location[0] . '" selected>' . htmlspecialchars($location[1]) . '</option>';
            }
            ?>
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
