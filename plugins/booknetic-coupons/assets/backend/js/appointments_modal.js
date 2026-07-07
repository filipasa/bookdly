(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        function reloadCouponTab()
        {
            booknetic.ajax('Coupons.load_edit_tab_content', {
                appointment: $('#add_new_JS').data('appointment-id'),
                service: $('#input_service').val(),
                staff: $('#input_staff').val(),
                customer: $(".fs-modal .input_customer").val()
            }, function ( result )
            {
                $("#coupons-edit-tab").html( booknetic.htmlspecialchars_decode( result['html'] ) );
            });
        }

        reloadCouponTab();

        $(".fs-modal").on('change', '#input_service,#input_staff', function ()
        {
            reloadCouponTab();
        });

        booknetic.addFilter( 'ajax_appointments.save_edited_appointment', function ( params )
        {
            params.append( 'coupon',$('.input_coupon').val());
            return params;
        }, 'addon-coupons');

        booknetic.addFilter( 'ajax_appointments.create_appointment', function ( params )
        {
            params.append( 'coupon',$('.input_coupon').val());
            return params;
        }, 'addon-coupons');
    });

})(jQuery);