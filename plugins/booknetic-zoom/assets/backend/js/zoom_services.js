(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'ajax_services.save_service', function ( params )
        {

           var activate_zoom           = $('.fs-modal #activate_zoom').is(':checked') ? 1 : 0;
           params.append('activate_zoom', activate_zoom);
            return params;
        });
    });

})(jQuery);

