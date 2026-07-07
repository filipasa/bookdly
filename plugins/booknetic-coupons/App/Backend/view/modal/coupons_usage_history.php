<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use function BookneticAddon\Coupons\bkntc__;
/**
 * @var mixed $parameters
 */
?>

<div class="fs-modal-title">
	<div class="title-icon"><img src="<?php echo Helper::icon('payment-appointment.svg' )?>"></div>
	<div class="title-text"><?php echo bkntc__('Usage history')?></div>
	<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
	<div class="fs-modal-body-inner">
        <ul class="nav nav-tabs nav-light" data-tab-group="coupons_usage_history">
            <?php foreach ( TabUI::get( 'coupons_usage_history' )->getSubItems() as $tab ): ?>
                <li class="nav-item"><a class="nav-link" data-tab="<?php echo $tab->getSlug(); ?>" href="#"><?php echo $tab->getTitle(); ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content mt-5">
            <?php foreach ( TabUI::get( 'coupons_usage_history' )->getSubItems() as $tab ): ?>
                <div class="tab-pane" data-tab-content="coupons_usage_history_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>"><?php echo $tab->getContent( $parameters ); ?></div>
            <?php endforeach; ?>
        </div>

	</div>
</div>

<div class="fs-modal-footer">
	<button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
</div>

