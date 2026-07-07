(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'ajax_staff.save_staff', function ( params ) {
            var zoom_user       		= {
                id: $("#zoom_user_select").val() ? $("#zoom_user_select").val() : '',
                name: $("#zoom_user_select").val() ? $("#zoom_user_select :selected").text().trim() : ''
            };
            params.append('zoom_user', JSON.stringify( zoom_user ));
            return params;

        } );
    });

})(jQuery);