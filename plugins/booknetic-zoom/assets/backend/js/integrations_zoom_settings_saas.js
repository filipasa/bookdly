(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        var fadeSpeed = 0;

        $('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
        {
            var zoom_integration_method		= $("#input_zoom_integration_method").val(),
                zoom_api_key		        = $("#input_zoom_api_key").val(),
                zoom_api_secret		        = $("#input_zoom_api_secret").val(),
                zoom_enable				    = $('input[name="input_zoom_enable"]:checked').val();

            booknetic.ajax('zoom.save_settings_saas', {
                zoom_integration_method: zoom_integration_method,
                zoom_api_key: zoom_api_key,
                zoom_api_secret: zoom_api_secret,
                zoom_enable: zoom_enable
            }, function ()
            {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        }).on('change', 'input[name="input_zoom_enable"]', function()
        {
            if( $('input[name="input_zoom_enable"]:checked').val() == 'on' )
            {
                $('#integrations_zoom_settings_area').slideDown(fadeSpeed);
            }
            else
            {
                $('#integrations_zoom_settings_area').slideUp(fadeSpeed);
            }
            fadeSpeed = 400;
        }).on('change', '#input_zoom_integration_method', function ()
        {
            let method = $( this ).val();
            $( "#booknetic_settings_area [data-method]" ).fadeOut(200);
            if ( [ "oauth", "jwt" ].includes( method ) )
            {
                $( `#booknetic_settings_area [data-method="${ method }"]` ).fadeIn(200);
            }
        });

        $('input[name="input_zoom_enable"]').trigger('change');

        $('#input_zoom_integration_method').trigger('change');

    });

})(jQuery);