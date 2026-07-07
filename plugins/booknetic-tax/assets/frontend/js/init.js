(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        bookneticHooks.addAction('loaded_step_service', function() {
            let taxText = $('div[data-step-id="service"] .bkntc_tax_top');
            taxText.insertBefore( $(".bkntc_service_list") );
            taxText.addClass('accordion');
        })

        bookneticHooks.addAction('loaded_step_service_extras', function() {
            let taxText = $('div[data-step-id="service_extras"] .bkntc_tax_top');
            taxText.insertBefore( $(".bkntc_service_extras_list") );
            taxText.addClass('accordion');
        })

    });

})(jQuery);