<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

$busySlot = $parameters['busySlot'];
?>

<div class="fs-modal-title">
    <div class="title-icon"><img src="<?php echo Helper::icon('edit.svg')?>"></div>
    <div class="title-text"><?php echo bkntc__('Edit Busy Slot')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="editBusySlotForm">
            <input type="hidden" id="input_id" value="<?php echo (int)$busySlot->id?>">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_staff"><?php echo bkntc__('Staff')?> <span class="required-star">*</span></label>
                    <select class="form-control" id="input_staff">
                        <option value=""><?php echo bkntc__('Select Staff...')?></option>
                        <?php foreach ($parameters['staff'] as $staffMember): ?>
                            <option value="<?php echo (int)$staffMember['id']?>" <?php echo $staffMember['id'] == $busySlot->staff_id ? 'selected' : ''?>><?php echo htmlspecialchars($staffMember['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_date"><?php echo bkntc__('Date')?> <span class="required-star">*</span></label>
                    <div class="inner-addon left-addon">
                        <i><img src="<?php echo Helper::icon('calendar.svg')?>"/></i>
                        <input class="form-control" id="input_date" value="<?php echo Date::datee($busySlot->date)?>" placeholder="<?php echo bkntc__('Select Date...')?>">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="input_time"><?php echo bkntc__('Start Time')?> <span class="required-star">*</span></label>
                    <div class="inner-addon left-addon">
                        <i><img src="<?php echo Helper::icon('time.svg')?>"/></i>
                        <input class="form-control" id="input_time" value="<?php echo htmlspecialchars($parameters['formattedTime'])?>" placeholder="<?php echo bkntc__('HH:MM')?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_duration"><?php echo bkntc__('Duration (minutes)')?> <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="input_duration" value="<?php echo (int)$busySlot->duration?>" min="1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_notes"><?php echo bkntc__('Note')?></label>
                    <textarea id="input_notes" class="form-control" rows="3"><?php echo htmlspecialchars($busySlot->notes)?></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addBusySlotSave"><?php echo bkntc__('SAVE')?></button>
</div>
