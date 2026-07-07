(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        var date_format_js =$("#input_start_date").data('date-format').replace('Y','yyyy').replace('m','mm').replace('d','dd');

        $("#input_start_date, #input_end_date").datepicker({
            autoclose: true,
            format: date_format_js,
            weekStart: weekStartsOn == 'sunday' ? 0 : 1
        });
    });

})(jQuery);