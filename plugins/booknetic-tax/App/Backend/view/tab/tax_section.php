<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Tax\bkntc__;
?>

<div class="form-row">
    <div class="form-group col-md-6">
        <label>&nbsp;</label>
        <div class="form-control-checkbox">
            <label for="hide_tax_excluded_text"><?php echo bkntc__('Hide "Price does not include taxes" on the services step')?>:
                <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntc__('If you enable this option, it allows to show "Price does not include taxes" on the services and extra services steps.')?>"></i>
            </label>
            <div class="fs_onoffswitch">
                <input type="checkbox" class="fs_onoffswitch-checkbox" id="hide_tax_excluded_text"<?php echo Helper::getOption('hide_tax_excluded_text', 'on')=='on'?' checked':''?>>
                <label class="fs_onoffswitch-label" for="hide_tax_excluded_text"></label>
            </div>
        </div>
    </div>
</div>
