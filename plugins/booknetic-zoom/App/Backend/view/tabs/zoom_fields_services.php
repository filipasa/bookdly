<?php

defined( 'ABSPATH' ) or die();

use function BookneticAddon\Zoom\bkntc__;

?>

<div class="form-row">

    <div class="form-group col-md-6">
        <div class="form-control-checkbox">
            <label for="activate_zoom"><?php echo bkntc__('Activate Zoom for the service')?></label>
            <div class="fs_onoffswitch">
                <input type="checkbox" class="fs_onoffswitch-checkbox" id="activate_zoom" <?php echo $parameters['service']->getData( 'activate_zoom' ) == 1 ? 'checked' : ''; ?>>
                <label class="fs_onoffswitch-label" for="activate_zoom"></label>
            </div>
        </div>
    </div>

</div>