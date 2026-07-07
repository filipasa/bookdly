<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Tax\TaxAddon;
use BookneticApp\Providers\UI\TabUI;
use function BookneticAddon\Tax\bkntc__;

?>

<script type="text/javascript" src="<?php echo TaxAddon::loadAsset( 'assets/backend/js/add_new.js' )?>" id="add_new_JS" data-tax-id="<?php echo (int)$parameters['tax']['id']?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo $parameters['tax']['id'] > 0 ? bkntc__( 'Edit Tax' ) : bkntc__('New Tax')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form>

            <ul class="nav nav-tabs nav-light" data-tab-group="tax_add_new">
                <?php foreach ( TabUI::get( 'tax_add_new' )->getSubItems() as $tab ): ?>
                    <li class="nav-item"><a class="nav-link" data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content mt-5">
                <?php foreach ( TabUI::get( 'tax_add_new' )->getSubItems() as $tab ): ?>
                    <div class="tab-pane" data-tab-content="tax_add_new_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
                <?php endforeach; ?>
            </div>

        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <div class="footer_left_action">
        <input type="checkbox" id="input_is_active" <?php echo $parameters['tax']['is_active'] ? 'checked' : ''?>  >
        <label for="input_is_active" class="font-size-14 text-secondary"><?php echo bkntc__('Enabled')?></label>
    </div>

    <button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addTaxSave"><?php echo $parameters['tax']['id'] > 0 ? bkntc__( 'SAVE' ) : bkntc__('CREATE')?></button>
</div>
