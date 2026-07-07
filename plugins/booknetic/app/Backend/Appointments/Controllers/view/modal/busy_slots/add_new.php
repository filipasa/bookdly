<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
?>

<div class="fs-modal-title">
    <div class="title-icon"><img src="<?php echo Helper::icon('add-employee.svg')?>"></div>
    <div class="title-text"><?php echo bkntc__('Add New Busy Slot')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <form id="addBusySlotForm">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_staff"><?php echo bkntc__('Staff')?> <span class="required-star">*</span></label>
                    <select class="form-control" id="input_staff">
                        <option value=""><?php echo bkntc__('Select Staff...')?></option>
                        <?php foreach ($parameters['staff'] as $staffMember): ?>
                            <option value="<?php echo (int)$staffMember['id']?>"><?php echo htmlspecialchars($staffMember['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_date"><?php echo bkntc__('Date')?> <span class="required-star">*</span></label>
                    <div class="inner-addon left-addon">
                        <i><img src="<?php echo Helper::icon('calendar.svg')?>"/></i>
                        <input class="form-control" id="input_date" placeholder="<?php echo bkntc__('Select Date...')?>">
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label for="input_time"><?php echo bkntc__('Start Time')?> <span class="required-star">*</span></label>
                    <div class="inner-addon left-addon d-flex align-items-center">
                        <i><img src="<?php echo Helper::icon('time.svg')?>"/></i>
                        <select class="form-control" id="input_time">
                            <?php
                            $time_step = 15;
                            $timeFormat = Helper::getOption('time_format', 'H:i');
                            for ($hour = 0; $hour < 24; $hour++) {
                                for ($min = 0; $min < 60; $min += $time_step) {
                                    $val = sprintf('%02d:%02d', $hour, $min);
                                    $display = date($timeFormat, strtotime($val));
                                    echo '<option value="' . $val . '">' . $display . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_duration"><?php echo bkntc__('Duration (minutes)')?> <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="input_duration" value="60" min="1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_notes"><?php echo bkntc__('Note')?></label>
                    <textarea id="input_notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
    <button type="button" class="btn btn-lg btn-primary" id="addBusySlotSave"><?php echo bkntc__('SAVE')?></button>
</div>

<script type="text/javascript">
    (function($) {
        "use strict";
        if (typeof dateFormat !== "undefined") {
            $(".fs-modal #input_date").datepicker({
                autoclose: true,
                format: dateFormat.replace("Y", "yyyy").replace("m", "mm").replace("d", "dd"),
                weekStart: weekStartsOn == "sunday" ? 0 : 1
            });
        } else {
            $(".fs-modal #input_date").datepicker({
                autoclose: true,
                format: "yyyy-mm-dd"
            });
        }

        $(".fs-modal #input_staff, .fs-modal #input_time").select2({
            theme: "bootstrap",
            placeholder: booknetic.__("Select...")
        });

        // Handle save action directly from the modal view
        $(document).off("click", "#addBusySlotSave").on("click", "#addBusySlotSave", function() {
            console.log("Booknetic Busy Slots: Save button clicked.");
            
            let id = $(".fs-modal #input_id").val() || 0;
            let staff_id = $(".fs-modal #input_staff").val();
            let date = $(".fs-modal #input_date").val();
            let time = $(".fs-modal #input_time").val();
            let duration = $(".fs-modal #input_duration").val();
            let notes = $(".fs-modal #input_notes").val();

            console.log("Booknetic Busy Slots Save values:", { id, staff_id, date, time, duration, notes });

            if (!staff_id || !date || !time || !duration) {
                console.warn("Booknetic Busy Slots: Missing required fields.");
                booknetic.toast(booknetic.__("fill_all_required"), "unsuccess");
                return;
            }

            let data = {
                id: id,
                staff_id: staff_id,
                date: date,
                time: time,
                duration: duration,
                notes: notes
            };

            console.log("Booknetic Busy Slots: Sending AJAX request...", data);

            booknetic.ajax("busy_slots.save", data, function(response) {
                console.log("Booknetic Busy Slots: Save response received successfully:", response);
                booknetic.modalHide($(".fs-modal"));
                if (typeof reloadCalendarFn === "function") {
                    console.log("Booknetic Busy Slots: Reloading calendar...");
                    reloadCalendarFn();
                } else if (typeof booknetic.dataTable === "object") {
                    if (typeof booknetic.dataTable.reload === "function") {
                        console.log("Booknetic Busy Slots: Reloading datatable...");
                        booknetic.dataTable.reload($("#fs_data_table_div"));
                    }
                }
            });
        });
    })(jQuery);
</script>
