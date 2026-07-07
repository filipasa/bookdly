(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.dataTable.actionCallbacks['edit'] = function (ids)
        {
            booknetic.loadModal('busy_slots.edit', {'id': ids[0]});
        };

        $(document).on('click', '#addBtn', function()
        {
            booknetic.loadModal('busy_slots.add_new', {});
        });

        $(document).on('click', '#addBusySlotSave', function()
        {
            let id = $(".fs-modal #input_id").val() || 0;
            let staff_id = $(".fs-modal #input_staff").val();
            let date = $(".fs-modal #input_date").val();
            let time = $(".fs-modal #input_time").val();
            let duration = $(".fs-modal #input_duration").val();
            let notes = $(".fs-modal #input_notes").val();

            if (staff_id === '' || date === '' || time === '' || duration === '') {
                booknetic.toast(booknetic.__('fill_all_required'), 'unsuccess');
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

            booknetic.ajax('busy_slots.save', data, function(response)
            {
                booknetic.modalHide($(".fs-modal"));
                if (typeof reloadCalendarFn === 'function') {
                    reloadCalendarFn();
                } else if (typeof booknetic.dataTable === 'object' && typeof booknetic.dataTable.reload === 'function') {
                    booknetic.dataTable.reload( $("#fs_data_table_div") );
                }
            });
        });
    });

    $(document).on('modal-load', function()
    {
        if ($("#addBusySlotForm, #editBusySlotForm").length > 0) {
            if (typeof dateFormat !== 'undefined') {
                $("#input_date").datepicker({
                    autoclose: true,
                    format: dateFormat.replace('Y', 'yyyy').replace('m', 'mm').replace('d', 'dd'),
                    weekStart: weekStartsOn == 'sunday' ? 0 : 1
                });
            }
        }
    });

})(jQuery);
