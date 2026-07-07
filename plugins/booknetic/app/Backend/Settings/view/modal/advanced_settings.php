<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/general_settings.css', 'Settings')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/advanced_settings.js', 'Settings')?>"></script>

<form id="advanced-settings" class="position-relative">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_flexible_timeslot"><?php echo bkntc__('Flexible Timeslots')?>:</label>
                    <select class="form-control" id="input_flexible_timeslot">
                        <option value="0"<?php echo $parameters['flexibleTimeslot'] == '0' ? ' selected' : ''?>><?php echo bkntc__('Disabled')?></option>
                        <option value="1"<?php echo $parameters['flexibleTimeslot'] == '1' ? ' selected' : ''?>><?php echo bkntc__('Enabled')?></option>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label for="time_priority"><?php echo bkntc__('Time Priority')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntc__('<b>Staff:</b> Staff timesheet is checked first. If the staff has no custom timesheet, the service timesheet is used. If neither exists, the global timesheet is used.<br><br><b>Service:</b> Service timesheet is checked first. If the service has no custom timesheet, the staff timesheet is used. If neither exists, the global timesheet is used.<br><br><b>Merge:</b> Calculates the intersection of staff and service timesheets. Only the overlapping hours are shown as available. Breaks from both timesheets are applied.')?>"></i></label>
                    <select class="form-control" id="time_priority">
                        <option value="staff"<?php echo $parameters['priority'] == 'staff' ? ' selected' : ''?>><?php echo bkntc__('Staff')?></option>
                        <option value="service"<?php echo $parameters['priority'] == 'service' ? ' selected' : ''?>><?php echo bkntc__('Service')?></option>
                        <option value="merge"<?php echo $parameters['priority'] == 'merge' ? ' selected' : ''?>><?php echo bkntc__('Merge')?></option>
                    </select>
                </div>

            </div>
</form>

