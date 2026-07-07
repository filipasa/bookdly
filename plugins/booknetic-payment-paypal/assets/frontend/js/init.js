(function($)
{
    "use strict";

    $(document).ready(function()
    {
        bookneticHooks.addAction( 'ajax_before_confirm', function( params, booknetic )
        {
            if( params.get('payment_method') == 'paypal' )
            {
                bookneticPaymentStatus = booknetic.paymentFinished;
                booknetic.paymentWindow = window.open( '', 'booknetic_payment_window', 'width=1000,height=700' );
                booknetic.waitPaymentFinish();
            }
        });

        bookneticHooks.addAction( 'ajax_after_confirm_success', function( booknetic, data, result )
        {
            if( data.get('payment_method') == 'paypal' )
            {
                if( result['status'] == 'error' )
                {
                    booknetic.toast( result['error_msg'], 'unsuccess'  );
                    booknetic.paymentWindow.close();
                    return;
                }

                if( !booknetic.paymentWindow.closed )
                {
                    booknetic.loading(1);
                    booknetic.paymentWindow.location.href = result['url'];
                    return;
                }
            }
        });

        bookneticHooks.addAction( 'ajax_after_confirm_error', function( booknetic, data, result )
        {
            if( data.get('payment_method') == 'paypal' )
            {
                booknetic.paymentWindow.close();
            }
        });
    });
})(jQuery);