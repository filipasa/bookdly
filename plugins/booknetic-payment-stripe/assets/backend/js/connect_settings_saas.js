(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        booknetic.addFilter( 'ajax_settings.save_payment_split_payments_settings', function ( params )
        {
            params[ 'stripe_connect_client_id' ]         = $("#input_stripe_connect_client_id").val()
            params[ 'stripe_connect_client_secret' ]     = $("#input_stripe_connect_client_secret").val()
            params[ 'stripe_connect_platform_fee' ]      = $("#input_stripe_connect_platform_fee").val()
            params[ 'stripe_connect_fee_type' ]          = $("#input_stripe_connect_fee_type").val()
            params[ 'stripe_connect_terms_page' ]        = $("#input_stripe_connect_terms_page").val()

            return params;
        } );

        $("#input_stripe_connect_fee_type").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: false,
        });

        $('#manage_connected_tenants').on( 'click', function()
        {
            booknetic.ajax( 'stripe_connect_settings.connected_tenants_saas', {}, function(res) {
                booknetic.modal(booknetic.htmlspecialchars_decode(res.html), { 'type' : 'center', 'width': '650px' })
            })
        })

    });

})(jQuery);
