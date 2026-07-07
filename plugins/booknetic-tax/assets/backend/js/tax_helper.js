(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.addFilter( 'payment_settings.save_payments_settings', function ( data ) {
            data['hide_tax_excluded_text'] =  $("#hide_tax_excluded_text").is(':checked') ? 'on' : 'off';
            return data;
        } );
    });

})(jQuery);