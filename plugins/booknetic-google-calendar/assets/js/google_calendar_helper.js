(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'ajax_staff.save', function ( params ) {
            var google_calendar_id		= $("#google_calendar_select").val();
            params.append('google_calendar_id', google_calendar_id ? google_calendar_id : '');
            return params;

        } );
    });

})(jQuery);