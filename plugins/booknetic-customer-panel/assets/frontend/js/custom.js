(function ($) {

    $(function () {


        bookneticHooks.addAction('customer_panel_loaded', ( booknetic ) =>
        {
            booknetic.panel_js.find('.booknetic-cp-tab-item').click(function () {
                var $dataID = $(this).data('target');

                if (!$(this).hasClass('active')) {
                    booknetic.panel_js.find('.booknetic-cp-tab-item').removeClass('active');
                    booknetic.panel_js.find('.booknetic-cp-tab-item[data-target="' + $dataID + '"]').addClass('active');

                    booknetic.panel_js.find('.booknetic-cp-tab').stop(true, false, true).fadeOut(0).removeClass('show');
                    booknetic.panel_js.find($dataID).stop(true, false, true).fadeIn().addClass('show');
                }
            });

            // Flat date picker
            booknetic.panel_js.find("#booknetic_input_birthdate").flatpickr(
                {
                    altInput: true,
                    altFormat: "Y-m-d",
                    dateFormat: "Y-m-d",
                    monthSelectorType: 'dropdown',
                    locale: {
                        firstDayOfWeek: BookneticData.week_starts_on === 'sunday' ? 0 : 1
                    },
                    onOpen: function (selectedDates, dateStr, instance) {
                        booknetic.panel_js.find('.flatpickr-calendar').css("max-width", $(instance.input).next().outerWidth());
                    }
                }
            );

        })
    });

})(jQuery);
