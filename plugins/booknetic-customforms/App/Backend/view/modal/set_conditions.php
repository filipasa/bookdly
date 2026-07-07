<?php

/**
 * @var array $parameters
 */

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Customforms\bkntc__;
?>
<link rel="stylesheet" href="<?php echo BookneticAddon\Customforms\CustomFormsAddon::loadAsset('assets/backend/css/set_conditions.css')?>">
<script type="text/javascript" src="<?php echo BookneticAddon\Customforms\CustomFormsAddon::loadAsset('assets/backend/js/set_conditions.js')?>"></script>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-bolt"></i></div>
    <div class="title-text"><?php echo bkntc__('Form conditions')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">

        <ul class="nav nav-tabs nav-light condition_tabs" data-tab-group="conditions">
            <li class="nav-item"><a class="nav-link" data-tab="tab_1" href="#"><?php echo bkntc__('CONDITION')?> 1 <i class="fa fa-times delete_condition_tab"></i></a></li>
            <li class="nav-item"><button type="button" class="btn btn-link add_new_condition_tab" title="<?php echo bkntc__('New Condition')?>"><i class="fa fa-plus-circle"></i></button></li>
        </ul>

        <div class="tab-content mt-5 condition_tabs_content">
            <div data-tab-content="conditions_tab_1" class="tab-pane">

                <div class="form-row">
                    <div class="col-md-12">
                        <label><?php echo bkntc__('When')?>:</label>
                    </div>
                </div>

                <div class="group_conditions">
                    <div class="row_condition hidden">

                        <div class="form-row mb-3 and_or_condition">
                            <div class="col-md-2">
                                <select class="form-control condition_and_or_select">
                                    <option value="AND"><?php echo bkntc__( 'AND' ) ?></option>
                                    <option value="OR"><?php echo bkntc__( 'OR' ) ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row bordered_row dashed-border">
                            <div class="form-group col-md-3">
                                <label class="text-primary"><?php echo bkntc__('Field')?>:</label>
                                <select class="form-control field_select_when"></select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>&nbsp;</label>
                                <select class="form-control field_data_select_when hidden">
                                    <option value="value"><?php echo bkntc__('Value')?></option>
                                    <option value="length"><?php echo bkntc__('Length')?></option>
                                    <option value="file_size"><?php echo bkntc__('File size (KB)')?></option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label><?php echo bkntc__('Operator')?>:</label>
                                <select class="form-control operator_select_when">
                                    <option value="=">=</option>
                                    <option value="!=">!=</option>
                                    <option value=">">></option>
                                    <option value=">=">>=</option>
                                    <option value="<"><</option>
                                    <option value="<="><=</option>
                                    <option value="is_empty"><?php echo bkntc__('is empty')?></option>
                                    <option value="is_not_empty"><?php echo bkntc__('is not empty')?></option>
                                    <option value="contains"><?php echo bkntc__('contains')?></option>
                                    <option value="!contains"><?php echo bkntc__('does not contains')?></option>
                                    <option value="regex"><?php echo bkntc__('regex')?></option>
                                    <option value="starts_with"><?php echo bkntc__('starts with')?></option>
                                    <option value="!starts_with"><?php echo bkntc__('does not starts with')?></option>
                                    <option value="ends_with"><?php echo bkntc__('ends with')?></option>
                                    <option value="!ends_with"><?php echo bkntc__('does not ends with')?></option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label><?php echo bkntc__('Value')?>:</label>
                                <input type="text" class="form-control value_input_when">
                                <select class="form-control value_select_when" multiple></select>
                            </div>
                            <div class="row_actions">
                                <img src="<?php echo Helper::icon('remove.svg', 'Services')?>" class="delete_condition_row">
                            </div>
                        </div>

                    </div>
                </div>

                <button type="button" class="btn btn-success new_condition_btn"><i class="fas fa-plus-circle"></i> <?php echo bkntc__('NEW CONDITION')?></button>

                <hr/>

                <div class="form-row">
                    <div class="col-md-12">
                        <label><?php echo bkntc__('Do')?>:</label>
                    </div>
                </div>

                <div class="group_do">

                    <div class="row_do hidden">
                        <div class="form-row bordered_row dashed-border">
                            <div class="form-group col-md-4">
                                <label class="text-primary"><?php echo bkntc__('Action')?>:</label>
                                <select class="form-control action_select_do">
                                    <option value="show"><?php echo bkntc__('Show')?></option>
                                    <option value="hide"><?php echo bkntc__('Hide')?></option>
                                    <option value="hide_for_customers"><?php echo bkntc__('Hide only for Customers')?></option>
                                    <option value="disable"><?php echo bkntc__('Disable')?></option>
                                    <option value="enable"><?php echo bkntc__('Enable')?></option>
                                    <option value="set_value"><?php echo bkntc__('Set value')?></option>
                                    <option value="throw_error"><?php echo bkntc__('Throw error')?></option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label><?php echo bkntc__('Field')?></label>
                                <select class="form-control field_select_do"></select>
                            </div>
                            <div class="form-group col-md-4 hidden">
                                <label class="value_label"><?php echo bkntc__('Value')?>:</label>
                                <label class="hidden error_message_label"><?php echo bkntc__('Error message')?>:</label>
                                <input type="text" class="form-control value_input_do">
                                <select class="form-control value_select_do"></select>
                            </div>
                            <div class="row_actions">
                                <img src="<?php echo Helper::icon('remove.svg', 'Services')?>" class="delete_do_row">
                            </div>
                        </div>
                    </div>

                </div>

                <button type="button" class="btn btn-success new_do_btn"><i class="fas fa-plus-circle"></i> <?php echo bkntc__('NEW ACTION')?></button>

            </div>
        </div>

    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal" id="close_conditions_btn"><?php echo bkntc__('CLOSE')?></button>
    <button type="button" class="btn btn-lg btn-success" id="save_conditions_btn"><?php echo bkntc__('SAVE')?></button>
</div>

<template class="hidden" id="default_field_options">
    <option value="service_id"><?php echo bkntc__('Service')?></option>
    <option value="staff_id"><?php echo bkntc__('Staff')?></option>
    <option value="location_id"><?php echo bkntc__('Location')?></option>
</template>

<template class="hidden" id="service_id_options">
    <?php foreach ( $parameters['services'] AS $serviceInf ):?>
    <option value="<?php echo (int)$serviceInf->id?>"><?php echo htmlspecialchars( $serviceInf->name )?></option>
    <?php endforeach;?>
</template>

<template class="hidden" id="staff_id_options">
    <?php foreach ( $parameters['staff'] AS $staff ):?>
        <option value="<?php echo (int)$staff->id?>"><?php echo htmlspecialchars( $staff->name )?></option>
    <?php endforeach;?>
</template>

<template class="hidden" id="location_id_options">
    <?php foreach ( $parameters['locations'] AS $locationInf ):?>
        <option value="<?php echo (int)$locationInf->id?>"><?php echo htmlspecialchars( $locationInf->name )?></option>
    <?php endforeach;?>
</template>