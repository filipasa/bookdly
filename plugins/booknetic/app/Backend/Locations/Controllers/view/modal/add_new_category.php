<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Locations\DTOs\Response\LocationCategoryViewResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var LocationCategoryViewResponse $parameters
 */
?>

<script type="application/javascript" src="<?php echo Helper::assets('js/add_new_category.js', 'Locations') ?>"
        id="add_new_category_JS"
        data-category-id="<?php echo $parameters->getLocationCategory()->getId() ?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-plus"></i></div>
    <div class="title-text"><?php echo bkntc__('Add Category') ?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addLocationCategoryForm">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="new_category_name"><?php echo bkntc__('Category name') ?> <span
                                class="required-star">*</span></label>
                    <input type="text" class="form-control"
                           value="<?php echo htmlspecialchars($parameters->getLocationCategory()->getName()) ?>" data-multilang="true"
                           data-multilang-fk="<?php echo $parameters->getLocationCategory()->getId() ?>" id="new_category_name">
                </div>
            </div>

        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE') ?></button>
    <button type="button" class="btn btn-lg btn-primary" id="save_new_category"><?php echo bkntc__('SAVE') ?></button>
</div>
