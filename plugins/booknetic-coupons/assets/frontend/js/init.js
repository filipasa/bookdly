(function ( $ ) {
    'use strict';

    $( document ).ready( function () {
        bookneticHooks.addAction( 'loaded_step_confirm_details', function( booknetic )
        {
            $('.booknetic_panel_footer').append(`
                <div class="booknetic_add_coupon">
                    <input type="text" id="booknetic_coupon" placeholder="${BookneticData.localization.coupon}">
                    <div style="display: flex;">
                        <button type="button" class="booknetic_btn_success booknetic_coupon_ok_btn">${BookneticData.localization.coupon_ok_btn}</button>
                        <button type="button" class="booknetic_btn_cancel booknetic_coupon_cancel_btn" style="display: none;">${BookneticData.localization.coupon_cancel_btn}</button>                    
                    </div>
                </div>
            `);
        });

        bookneticHooks.addAction( 'loaded_step_confirm_details', function( booknetic )
        {
            let booking_panel_js = booknetic.panel_js;
            const coupon_ok_btn = booking_panel_js.find('.booknetic_btn_success.booknetic_coupon_ok_btn');
            const coupon_cancel_btn = booking_panel_js.find('.booknetic_btn_cancel.booknetic_coupon_cancel_btn');
            const button_anim_timeout = 250;
            const full_discount_anim_timeout = 300;
            const extra_delay = 50;

            const remove_full_discount = function ()
            {
                const hidden_local_payment= booking_panel_js.find('.booknetic_payment_method.booknetic_payment_method_selected[data-payment-type="local"][style*="display: none"]')

                if (hidden_local_payment.length === 0)
                    return;

                hidden_local_payment.removeClass('booknetic_payment_method_selected');

                booking_panel_js.find('.booknetic_payment_method:first').addClass('booknetic_payment_method_selected');
                coupon_ok_btn.removeClass('after-pseudo');

                if( $( window ).width() > 1000 )
                {
                    booking_panel_js.find('.booknetic_confirm_sum_body')
                        .animate({ width: '378px' }, full_discount_anim_timeout)
                        .removeAttr('style');
                }
                setTimeout(function () {
                    booking_panel_js.find('.booknetic_confirm_deposit_body').fadeIn(full_discount_anim_timeout);
                }, full_discount_anim_timeout);
            }

            const show_cancel_btn = function() {
                if( coupon_cancel_btn.attr('style') !== '')
                {
                    coupon_ok_btn.animate({ 'margin-right': '62px' }, button_anim_timeout);
                }

                setTimeout(function (){
                    coupon_ok_btn.removeAttr('style');
                    coupon_cancel_btn.fadeIn(button_anim_timeout);
                }, button_anim_timeout + extra_delay);

                booking_panel_js.find('.booknetic_add_coupon input, .booknetic_add_giftcard input')
                    .css('width', '50%');
            }

            const hide_cancel_btn = function () {
                if( coupon_cancel_btn.css('display') === 'none' )
                    return;

                coupon_cancel_btn.fadeOut(button_anim_timeout, function () {
                    coupon_ok_btn
                        .css('margin-right', '62px')
                        .animate({'margin-right': '0'}, button_anim_timeout);
                });

                setTimeout(function() {
                    coupon_ok_btn.removeAttr('style');
                }, button_anim_timeout + extra_delay);

                booking_panel_js.find('.booknetic_add_coupon input, .booknetic_add_giftcard input')
                    .removeAttr('style');
            }

            const update_prices = function (result) {
                booking_panel_js.find('.booknetic_prices_box').html(result['prices_html']);
                booking_panel_js.find('.booknetic_sum_price').text(result['sum_price_txt']);
                booking_panel_js.find('.booknetic_deposit_amount_txt').text(result['deposit_txt']);
            }

            booking_panel_js.off('click', '.booknetic_coupon_ok_btn' );
            booking_panel_js.on('click', '.booknetic_coupon_ok_btn', function ()
            {
                booknetic.ajax('summary_with_coupon', booknetic.ajaxParameters(), function ( result )
                {
                    if( booking_panel_js.find('#booknetic_coupon').val() === '' )
                    {
                        booking_panel_js.find('.booknetic_add_coupon').removeClass('booknetic_coupon_ok');

                        remove_full_discount();
                        hide_cancel_btn();
                    }
                    else
                    {
                        booking_panel_js.find('.booknetic_add_coupon').addClass('booknetic_coupon_ok');

                        show_cancel_btn();

                        if (result['sum_price'] <= 0) {

                            // Creating local_payment element for if someone tries to replace or remove 100%+ coupon.
                            // Because if service price after a coupon drops to 0 then payment method becomes local.
                            const local_payment = `
                                <div class="booknetic_payment_method booknetic_payment_method_selected" data-payment-type="local" style="display: none;"></div>
                            `;

                            booking_panel_js.find('.booknetic_payment_method_selected').removeClass('booknetic_payment_method_selected');
                            booking_panel_js.find('.booknetic_payment_methods').append(local_payment);
                            booking_panel_js.find('.booknetic_confirm_deposit_body').fadeOut(full_discount_anim_timeout, function () {
                                booking_panel_js.find('.booknetic_confirm_sum_body').animate({ width: '100%' }, full_discount_anim_timeout);
                            });
                            booking_panel_js.find('.booknetic_add_coupon input, .booknetic_add_giftcard input')
                                .css('width', '70%');
                        }
                        else
                        {
                            remove_full_discount();
                        }
                    }

                    update_prices(result);
                }, true, function ()
                {
                    booking_panel_js.find('.booknetic_add_coupon').removeClass('booknetic_coupon_ok');
                });

            });

            booking_panel_js.on('click', '.booknetic_coupon_cancel_btn', function() {
                booking_panel_js.find('#booknetic_coupon').val('');

                booknetic.ajax('summary_with_coupon', booknetic.ajaxParameters(), function ( result )
                {
                    booking_panel_js.find('.booknetic_add_coupon').removeClass('booknetic_coupon_ok');

                    remove_full_discount();
                    hide_cancel_btn();
                    update_prices(result);
                });
            });
        });


        bookneticHooks.addFilter('appointment_ajax_data', function ( data, booknetic ) {
            let booking_panel_js = booknetic.panel_js;
            data.append( 'coupon', booking_panel_js.find('#booknetic_coupon').val() || '' );
            return data;
        });

    } );
})( jQuery );