(function ( $ )
{
    'use strict';

    let getCartCookies = function()
    {
        let cookieName = 'addons='
        let cookies = document.cookie.split(';');

        for( var i=0; i < cookies.length; i++ )
        {
            var cookie = cookies[i];

            while (cookie.charAt(0) === ' ') cookie = cookie.substring(1,cookie.length);

            if (cookie.indexOf(cookieName) === 0) return JSON.parse(decodeURIComponent(cookie.substring(cookieName.length,cookie.length)));
        }

        return [];
    }

    let setCartCookies = function( addon )
    {
        let cartCookies = getCartCookies();

        if ( ! cartCookies.includes( addon ) )
        {
            cartCookies.push( addon )
        }

        let date = new Date();
        date.setTime(Date.now() + 30 * 24 * 60 * 60 * 1000);

        let name = 'addons='
        let expires = '; expires=' + date.toUTCString();

        document.cookie = name + ( JSON.stringify(cartCookies) || "" ) + expires + "; path=/"
    }

    let removeCartCookie = function ( addon )
    {
        let cartCookies = getCartCookies();

        cartCookies.splice( cartCookies.indexOf( addon ), 1 )

        let date = new Date();
        date.setTime(Date.now() + 30 * 24 * 60 * 60 * 1000);

        let name = 'addons='
        let expires = '; expires=' + date.toUTCString();

        document.cookie = name + ( JSON.stringify(cartCookies) || "" ) + expires + "; path=/"
    }


    let cartToaster = function( title )
    {
        $('#cartToaster').remove();

        $('#coupon_wrapper').after( `<label id="cartToaster" class="checkout_error">${title}</label>` )
    }

    let revertPrices = function ( addonsContent )
    {
        addonsContent
            .find('tbody')
            .children()
            .find('.coupon-used')
            .removeClass('cart-addon-old-price coupon-used')
            .addClass('cart-addon-current-price')
            .next()
            .remove();

        [
            ...addonsContent.find('.checkout_wrapper_prices').children(':not(.checkout_price_item_total)')
        ].forEach( addon =>
        {
            addon.getElementsByClassName('checkout_price_item__price')[0].textContent = '$' + addon.getAttribute('data-price')
        })

        let totalPriceEl = addonsContent.find('p[data-total-price]')
        totalPriceEl.text('$' + totalPriceEl.attr('data-total-price') );
    }

    $( document ).ready( function ()
    {
        // Handle hover over installed button
        $( document ).on( 'click', '.btn-install', function ()
        {
            let _this = $( this );

            booknetic.ajax( 'boostore.install', { addon_slug: _this.attr( 'data-addon' ) }, function ( res )
            {
                booknetic.toast( res[ 'message' ] );

                booknetic.boostore.onInstall( _this, res );
            } );
        } ).on( 'click', '.btn-uninstall', function ()
        {
            let _this = $( this );

            booknetic.confirm( booknetic.__( 'are_you_sure_want_to_delete' ), 'danger', 'trash', function ()
            {
                let addon = _this.attr( 'data-addon' );

                booknetic.ajax( 'boostore.uninstall', { addon }, function ( res )
                {
                    booknetic.toast( res[ 'message' ] );

                    booknetic.boostore.onUninstall( _this, res );
                } );
            } );
        } ).on( 'click', '.btn-install-all', function ()
        {
            let stepsCount = $('.btn-install').length;

            if (stepsCount === 0) {
                booknetic.toast('All addons are already installed', 'info');
                return;
            }

            $('#installModal').modal({ backdrop: 'static', keyboard: false });
            $('#modalTitle').text('You have ' + stepsCount + ' uninstalled addon' + (stepsCount > 1 ? 's' : ''));
            $('#progressBar').css('width', '0%');
            $('#closeBtn').prop('disabled', true);
            let currentStep = 0;

            function installNext() {
                if (currentStep === stepsCount) {
                    $('#modalTitle').text('All addons installed!');
                    $('#currentAddon').text('Finished!');
                    $('#progressText').text(stepsCount + '/' + stepsCount);
                    $('#closeBtn').prop('disabled', false);
                    return;
                }

                let nextAddon = $('.btn-install').first();
                let addon = nextAddon.attr('data-addon');
                let remaining = stepsCount - currentStep;

                $('#modalTitle').text('You have ' + remaining + ' uninstalled addon' + (remaining > 1 ? 's' : ''));
                $('#currentAddon').text('Installing...');
                $('#progressText').text(currentStep + '/' + stepsCount);

                booknetic.ajax('boostore.install', { addon_slug: addon }, function(res) {
                    currentStep++;

                    booknetic.boostore.onInstall(nextAddon, res);
                    let percent = (100 / stepsCount * currentStep);
                    $('#progressBar').css('width', percent + '%');
                    $('#progressText').text(currentStep + '/' + stepsCount);

                    installNext();
                }, function(error) {
                    currentStep++;

                    nextAddon.remove();

                    let percent = (100 / stepsCount * currentStep);
                    $('#progressBar').css('width', percent + '%');
                    $('#progressText').text(currentStep + '/' + stepsCount);

                    installNext();
                });
            }

            installNext();
        }).on( 'click', '.btn-add-to-cart', function ()
        {
            // setCartCookies( $(this).data('addon') )
            let button = this;
            let addon = $(this).attr('data-addon');

            booknetic.ajax( 'boostore.add_to_cart', { addon: addon }, function( res )
            {
                $(button).html(`<i class="fa fa-shopping-cart mr-2" aria-hidden="true"></i> <span>VIEW CART</span>`);
                $('#bkntc_cart_items_counter').text( parseInt($('#bkntc_cart_items_counter').text()) + 1 )
                button.className = 'btn btn-lg btn-warning view_cart_btn';

                button.onclick = function(){
                    location.href = '?page=' + BACKEND_SLUG + '&module=cart'
                }

                booknetic.toast( res.message, 'success' );
            })
        }).on( 'click', '.remove-cart-item', function ()
        {
            let _this = $( this );
            booknetic.confirm( 'Are you sure, you want to delete this item', 'danger', 'unsuccess', function ()
            {
                // removeCartCookie( _this.parents( 'tr' ).data( 'addon' ) )

                let addon = _this.parents( 'tr' ).attr( 'data-addon' )

                booknetic.ajax( 'boostore.remove_from_cart', { addon: addon }, function( res )
                {
                    $( `.checkout_price_item[data-addon="${ _this.parents( 'tr' ).data( 'addon' ) }"]` ).remove()
                    _this.parents( 'tr' ).first().remove();

                    if ( ! $('tbody').children().length > 0 || $( '#buy_all_discount' ).length )
                    {
                        location.reload()
                    }

                    $( '.checkout_price_item_total_price' )
                        .text('$' + res.prices.total_price)
                        .attr('data-total-price', res.prices.total_price);

                    booknetic.toast( res.message, 'success' );
                })

            })
        }).on( 'click', '#apply_discount', function()
        {
            $('#cartToaster').remove();

            let addons = []
            let coupon = $('#coupon').val();

            if ( ! coupon )
            {
                cartToaster( booknetic.__('Coupon cannot be empty!') );
                return;
            }

            $('.addons_card_wrapper tbody tr').each( function( key, item )
            {
                addons.push( item.dataset.addon )
            })

            if( ! addons )
            {
                cartToaster( booknetic.__( 'Your cart is empty.' ) );
                return;
            }

            let addonsContent = $('.addons_content');

            revertPrices( addonsContent );

            booknetic.ajax( 'boostore.apply_discount', { cart: JSON.stringify( addons ), coupon: coupon }, function( res )
            {
                res.discounted_addons.forEach( addon =>
                {
                    addonsContent.find( `tr[data-addon="${addon.slug}"]` )
                        .find('.cart-addon-current-price')
                        .removeClass('cart-addon-current-price')
                        .addClass('cart-addon-old-price coupon-used')
                        .after(`<span class="cart-addon-current-price coupon-applied"> $${addon.discounted_price}</span>`);

                    addonsContent
                        .find( `.checkout_price_item[data-addon="${addon.slug}"]` )
                        .find('.checkout_price_item__price')
                        .text(`$${addon.discounted_price}`);
                } )

                addonsContent.find('#checkout_total_price').text('$' + res.total_price);

                addonsContent.find('#coupon_wrapper').css('display', 'none');
                addonsContent.find('#coupon_applied_wrapper').css('display', '').find('#applied_coupon').text(coupon);

            })

        }).on( 'click', '#remove_discount', function()
        {
            let addonsContent = $('.addons_content');

            revertPrices( addonsContent );

            addonsContent.find('#coupon_applied_wrapper').css('display', 'none');

            addonsContent.find('#coupon_wrapper').css('display', '');

            $('#applied_coupon').text('');

        }).on( 'click', '#purchaseCart', function()
        {
            let w = window.open( 'about:blank', 'bkntc_boostore_purchase_window', 'width=900,height=600' );

            let addons = []
            let coupon = $('#applied_coupon').text();

            $('.addons_card_wrapper tbody tr').each( function( key, item )
            {
                addons.push( item.dataset.addon )
            })

            booknetic.ajax( 'boostore.purchase_cart', { cart: JSON.stringify( addons ), coupon: coupon }, function ( res )
            {
                w.location.href = res[ 'purchase_url' ];
            } );
        }).on( 'click', '#clearCart', function()
        {
            booknetic.ajax( 'boostore.clear_cart', {}, function( res )
            {
                booknetic.toast( res.message );
                location.reload();
            })
        }).on( 'click', '#buy_all', function () {

            booknetic.ajax( 'boostore.buy_all', {}, function( res )
            {
                booknetic.toast( res.message );
                location.href = '?page=' + BACKEND_SLUG + '&module=cart';
            });
        } );

        if ( $( '#buy_all_discount' ).length )
        {
            $('#cartToaster').remove();

            let addons = []

            $('.addons_card_wrapper tbody tr').each( function( key, item )
            {
                addons.push( item.dataset.addon )
            })

            let addonsContent = $('.addons_content');

            revertPrices( addonsContent );

            booknetic.ajax( 'boostore.apply_buy_all_discount', { cart: JSON.stringify( addons ) }, function( res )
            {
                res.discounted_addons.forEach( addon =>
                {
                    addonsContent.find( `tr[data-addon="${addon.slug}"]` )
                        .find('.cart-addon-current-price')
                        .removeClass('cart-addon-current-price')
                        .addClass('cart-addon-old-price coupon-used')
                        .after(`<span class="cart-addon-current-price coupon-applied"> $${addon.discounted_price}</span>`);

                    addonsContent
                        .find( `.checkout_price_item[data-addon="${addon.slug}"]` )
                        .find('.checkout_price_item__price')
                        .text(`$${addon.discounted_price}`);
                } )

                addonsContent.find('#checkout_total_price').text('$' + res.total_price);
            })
        }

        booknetic.boostore = {};

        booknetic.boostore.openUpgradeModal = function( url )
        {
            let modalHtml = `
                <div class="modal fade" id="subscriptionUpgradeModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document" style="max-width: 95vw; width: 95vw;">
                        <div class="modal-content" style="height: 95vh;">
                            <div class="modal-header">
                                <h5 class="modal-title">${booknetic.__('Upgrade Subscription')}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-0" style="height: calc(100% - 56px);">
                                <iframe id="subscriptionUpgradeIframe" src="${url}" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $( '#subscriptionUpgradeModal' ).remove();
            $( 'body' ).append( modalHtml );

            let $modal = $( '#subscriptionUpgradeModal' );
            let $iframe = $( '#subscriptionUpgradeIframe' );
            let handled = false;

            let openInNewTab = function()
            {
                if ( handled ) return;
                handled = true;

                $modal.remove();
                window.open( url, '_blank' );
            };

            $iframe.on( 'load', function()
            {
                if ( handled ) return;

                try {
                    let iframeDoc = this.contentDocument || this.contentWindow.document;
                    if ( ! iframeDoc || ! iframeDoc.body || iframeDoc.body.innerHTML === '' )
                    {
                        openInNewTab();
                        return;
                    }
                } catch ( e ) {
                    // Cross-origin: iframe loaded but content is not accessible.
                    // This is expected for external URLs — the page loaded successfully.
                }

                handled = true;
                $modal.modal( 'show' );
            } );

            $iframe.on( 'error', function()
            {
                openInNewTab();
            } );

            setTimeout( function()
            {
                if ( ! handled )
                {
                    openInNewTab();
                }
            }, 5000 );

            $modal.on( 'hidden.bs.modal', function()
            {
                $( this ).remove();
            } );

            window.addEventListener( 'message', function( event )
            {
                if ( event.data && event.data.type === 'subscription-upgraded' )
                {
                    $modal.modal( 'hide' );
                    location.reload();
                }
            } );
        };

        $( document ).on( 'click', '.btn-upgrade', function()
        {
            let $btn = $( this );
            let originalHTML = $btn.html();

            $btn.prop( 'disabled', true );
            $btn.html( '<span class="spinner-border spinner-border-sm" role="status"></span>' );

            booknetic.ajax( 'boostore.get_subscription_upgrade_url', { addon: $btn.data( 'addon' ) }, function( res )
            {
                $btn.prop( 'disabled', false ).html( originalHTML );
                booknetic.boostore.openUpgradeModal( res.url );
            }, function()
            {
                $btn.prop( 'disabled', false ).html( originalHTML );
            } );
        } );
    } );

})( jQuery );