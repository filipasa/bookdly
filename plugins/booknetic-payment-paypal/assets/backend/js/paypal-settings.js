(function ( $ )
{
    'use strict';

    $( document ).ready( function ()
    {

        booknetic.addFilter( 'ajax_settings.save_payment_gateways_settings', function ( params )
        {
            params.append('paypal_client_id', $( '#input_paypal_client_id' ).val())
            params.append('paypal_client_secret', $( '#input_paypal_client_secret' ).val())
            params.append('paypal_mode', $( '#input_paypal_mode' ).val())

            return params;
        } );
    } );
} )( jQuery );