(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area').on('click', '.stripe_connect_btn', function()
        {

            let w = window.open( 'about:blank', 'bkntc_stripe_connect_window', 'width=800,height=600' );

            booknetic.ajax('stripe_connect_settings.generate_login_link', new FormData, function(result)
            {

                w.location.href = result['url'];

                $('stripe_connect_verify_btn').replaceWith(`<span class="" style="">Success!<i class="fa fa-check m-1" aria-hidden="true" style="color: #00d700;"></i></span>`)
                console.log(result);
            });

        });
    });

})(jQuery);
