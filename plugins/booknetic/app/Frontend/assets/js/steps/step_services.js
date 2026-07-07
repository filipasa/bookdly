(function($)
{

    bookneticHooks.addAction('booking_panel_loaded', function ( booknetic )
    {
        let booking_panel_js = booknetic.panel_js;

        booking_panel_js.on('click', '.booknetic_service_card', booknetic.throttle(function(e)
        {
            // If view more button is clicked inside services card
            if ( $(e.target).is( ".booknetic_view_more_service_notes_button" ) ) {
                $( this ).find( '.booknetic_service_card_description_wrapped, .booknetic_view_more_service_notes_button' ).css( 'display', 'none' );
                $( this ).find( '.booknetic_service_card_description_fulltext, .booknetic_view_less_service_notes_button' ).css( 'display', 'inline' );
                booknetic.handleScroll();
                return
            } else if ( $(e.target).is( '.booknetic_view_less_service_notes_button' ) ) {
                $( this ).find( '.booknetic_service_card_description_wrapped, .booknetic_view_more_service_notes_button' ).css( 'display', 'inline' );
                $( this ).find( '.booknetic_service_card_description_fulltext, .booknetic_view_less_service_notes_button' ).css( 'display', 'none' );
                booknetic.handleScroll();
                return
            }

            $(this).parents('.bkntc_service_list').find('.booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
            $(this).addClass('booknetic_service_card_selected');

            booknetic.stepManager.goForward();
        }));

        booking_panel_js.on('click', '.booknetic_service_category', function(e)
        {
            let category = $(this);
            let wrapper = category.closest('.booknetic_service_category_wrapper');
            let cardsContainer = wrapper.find('.booknetic_service_cards_container');
            let isCollapsed = wrapper.hasClass('collapsed');

            if (isCollapsed) {
                wrapper.removeClass('collapsed');
                category.removeClass('collapsed');
                cardsContainer.hide().slideDown(200, function() {
                    booknetic.handleScroll();
                });
            } else {
                cardsContainer.slideUp(200, function() {
                    wrapper.addClass('collapsed');
                    category.addClass('collapsed');
                    booknetic.handleScroll();
                });
            }
        });
    });

    bookneticHooks.addAction('before_step_loading', function( booknetic, new_step_id, old_step_id )
    {
        if( new_step_id !== 'service' )
            return;

        booknetic.stepManager.loadStandartSteps( new_step_id, old_step_id );
    });

    bookneticHooks.addAction('loaded_step', function( booknetic, new_step_id )
    {
        if( new_step_id !== 'service' )
            return;

        // Initialize collapsed states for categories
        booknetic.panel_js.find('.booknetic_service_category_wrapper').each(function() {
            let wrapper = $(this);
            let category = wrapper.find('.booknetic_service_category');
            let cardsContainer = wrapper.find('.booknetic_service_cards_container');
            
            if (wrapper.hasClass('collapsed')) {
                category.addClass('collapsed');
                cardsContainer.hide();
            } else {
                category.removeClass('collapsed');
                cardsContainer.show();
            }
        });

        let searchInput = booknetic.panel_js.find('.booknetic_service_search_input');

        if ( searchInput.length )
        {
            let debounceTimer;

            searchInput.off('input').on('input', function ()
            {
                clearTimeout(debounceTimer);

                debounceTimer = setTimeout(function ()
                {
                    let query = searchInput.val().toLowerCase().trim();
                    let serviceList = booknetic.panel_js.find('.bkntc_service_list');

                    if ( query === '' )
                    {
                        serviceList.find('.booknetic_service_card, .booknetic_service_category, .booknetic_service_category_wrapper, .booknetic_service_cards_container').removeAttr('style');
                        
                        // Re-initialize default states based on CSS classes
                        serviceList.find('.booknetic_service_category_wrapper').each(function() {
                            let wrapper = $(this);
                            let category = wrapper.find('.booknetic_service_category');
                            let cardsContainer = wrapper.find('.booknetic_service_cards_container');
                            
                            wrapper.removeClass('collapsed');
                            category.removeClass('collapsed');
                            cardsContainer.removeClass('booknetic_category_accordion_hidden').show();

                            if (wrapper.hasClass('collapsed') || cardsContainer.hasClass('booknetic_category_accordion_hidden')) {
                                wrapper.addClass('collapsed');
                                category.addClass('collapsed');
                                cardsContainer.addClass('booknetic_category_accordion_hidden').hide();
                            }
                        });

                        return;
                    }

                    serviceList.find('.booknetic_service_category_wrapper').each(function() {
                        let wrapper = $(this);
                        let category = wrapper.find('.booknetic_service_category');
                        let cardsContainer = wrapper.find('.booknetic_service_cards_container');
                        let categoryName = category.text().toLowerCase();

                        let categoryMatches = categoryName.indexOf(query) !== -1;
                        let anyCardMatches = false;

                        wrapper.find('.booknetic_service_card').each(function() {
                            let card = $(this);
                            let title = card.find('.booknetic_service_title_span').text().toLowerCase();
                            let description = card.find('.booknetic_service_card_description_fulltext').text().toLowerCase();

                            if (categoryMatches || title.indexOf(query) !== -1 || description.indexOf(query) !== -1) {
                                card.show();
                                anyCardMatches = true;
                            } else {
                                card.hide();
                            }
                        });

                        if (anyCardMatches) {
                            wrapper.show().removeClass('collapsed');
                            category.removeClass('collapsed');
                            cardsContainer.removeClass('booknetic_category_accordion_hidden').show();
                        } else {
                            wrapper.hide();
                        }
                    });
                }, 200);
            });
        }
    });

    bookneticHooks.addFilter('step_validation_service' , function ( result , booknetic )
    {
        if( !( booknetic.getSelected.service() > 0 ) )
        {
            return {
                status: false,
                errorMsg: booknetic.__('select_service')
            };
        }

        return result
    });

})(jQuery);