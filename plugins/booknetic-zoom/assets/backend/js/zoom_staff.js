(function ($)
{
    "use strict";

    $(document).ready(function()
    {

        if( $('#zoom_user_select').length )
        {
            booknetic.select2Ajax( $('#zoom_user_select'), 'Zoom.fetch_zoom_users', { staff_id: $(".fs-modal #add_new_JS").data('staff-id') } );
        }
    });

})(jQuery);