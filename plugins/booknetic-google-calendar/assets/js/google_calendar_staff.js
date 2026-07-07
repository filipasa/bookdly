(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        $('.fs-modal').on('click', '#login_google_account', function ()
        {
            booknetic.ajax( 'Googlecalendar.login_google_account', { staff_id: $(".fs-modal #add_new_JS").data('staff-id') }, function ( result )
            {
                window.location.href = result['redirect'];
            })
        }).on('click', '#logout_google_account', function ()
        {
            booknetic.ajax( 'Googlecalendar.logout_google_account', { staff_id: $(".fs-modal #add_new_JS").data('staff-id') }, function ( result )
            {
                $('#logout_google_account').hide();
                $('#login_google_account').show();

                $("#google_calendar_select").select2('val', false);
                $("#google_calendar_select").attr('disabled', true);
            });
        });
        booknetic.select2Ajax( $('#google_calendar_select'), 'Googlecalendar.fetch_google_calendars', { staff_id: $(".fs-modal #add_new_JS").data('staff-id') } );
    });

})(jQuery);