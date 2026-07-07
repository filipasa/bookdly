function setupCompleted( status, view )
{
    if ( status )
    {
        $('div[data-step="paypal_split"]').html( booknetic.htmlspecialchars_decode( view ) );
    }
}

(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#booknetic_settings_area').on('click', '.paypal_split_register_btn', function()
        {
            $(this).replaceWith(`<i class="fa fa-spinner fa-pulse fa-2x connect_loading"></i>`)

            let w = window.open( 'about:blank', 'bkntc_paypal_split_setup_window', 'width=950,height=650' );

            booknetic.ajax('paypal_split_settings.create_account', new FormData, function(result)
            {
                w.opener = null;
                w.location.href = result['url'];

                let bc = new BroadcastChannel('bkntc_split_communication');

                bc.onmessage = function (event)
                {
                    setupCompleted( event.data.status, event.data.view )
                }
            });

        });
    });

})(jQuery);