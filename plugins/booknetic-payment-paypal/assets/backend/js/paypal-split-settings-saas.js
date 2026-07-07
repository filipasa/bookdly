(function ( $ )
{
    'use strict';

    $( document ).ready( function ()
    {

        booknetic.addFilter( 'ajax_settings.save_payment_split_payments_settings', function ( params )
        {
            params[ 'input_paypal_split_mode' ]           = $('#input_paypal_split_mode').val()
            params[ 'input_paypal_split_webhook_id' ]     = $('#input_paypal_split_webhook_id').val()
            params[ 'input_paypal_split_client_id' ]      = $('#input_paypal_split_client_id').val()
            params[ 'input_paypal_split_client_secret' ]  = $('#input_paypal_split_client_secret').val()
            params[ 'input_paypal_split_merchant_id' ]    = $('#input_paypal_split_merchant_id').val()
            params[ 'input_paypal_split_bn' ]             = $('#input_paypal_split_bn').val()
            params[ 'input_paypal_split_platform_fee' ]   = $('#input_paypal_split_platform_fee').val()
            params[ 'input_paypal_split_fee_type' ]       = $('#input_paypal_split_fee_type').val()
            params[ 'input_paypal_split_terms_page' ]     = $('#input_paypal_split_terms_page').val()

            return params;
        } );

        $('#input_paypal_split_fee_type, #input_paypal_split_mode').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: false
        });


    } );

})( jQuery );