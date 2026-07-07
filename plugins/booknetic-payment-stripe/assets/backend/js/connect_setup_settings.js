
function setupCompleted( status, view )
{
    if ( status )
    {
        $('div[data-step="stripe_connect"]').html( booknetic.htmlspecialchars_decode( view ) );

    }
}


(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area').on('click', '.stripe_connect_register_btn', function()
        {

            $(this).replaceWith(`<i class="fa fa-spinner fa-pulse fa-2x connect_loading"></i>`)

            let w = window.open( 'about:blank', 'bkntc_stripe_connect_window', 'width=800,height=600' );


            booknetic.ajax('stripe_connect_settings.generate_register_link', new FormData, function(result)
            {
                w.location.href = result['url'];

                // $('.connect_loading').replaceWith(`<span class="" style="">Success!<i class="fa fa-check m-1" aria-hidden="true" style="color: #00d700;"></i></span>`)

                // window.open(result['url'], '_blank');
            });

        });
    });

})(jQuery);
