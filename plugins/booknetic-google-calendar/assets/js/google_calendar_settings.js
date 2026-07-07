(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.initKeywordsInput( $( '#input_google_calendar_event_title' ), google_all_shortcodes_obj );
		booknetic.initKeywordsInput( $( '#input_google_calendar_event_description' ), google_all_shortcodes_obj );

		var fadeSpeed = 0;

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
		{
			var google_calendar_client_id			= $("#input_google_calendar_client_id").val(),
				google_calendar_client_secret		= $("#input_google_calendar_client_secret").val(),
				google_calendar_event_title			= $("#input_google_calendar_event_title").val(),
				google_calendar_event_description	= $("#input_google_calendar_event_description").val(),
				google_calendar_2way_sync			= $("#input_google_calendar_2way_sync").val(),
				google_calendar_sync_interval		= $("#input_google_calendar_sync_interval").val(),
				google_calendar_add_attendees		= $("#input_google_calendar_add_attendees").is(':checked') ? 'on' : 'off',
				google_calendar_send_notification	= $("#input_google_calendar_send_notification").is(':checked') ? 'on' : 'off',
				google_calendar_can_see_attendees	= $("#input_google_calendar_can_see_attendees").is(':checked') ? 'on' : 'off',
				google_calendar_enable				= $('input[name="input_google_calendar_enable"]:checked').val(),
				google_calendar_appointment_status				= $("#input_google_calendar_appointment_status").val();

			booknetic.ajax('googlecalendar_settings.settings_save', {
				google_calendar_enable: google_calendar_enable,
				google_calendar_client_id: google_calendar_client_id,
				google_calendar_client_secret: google_calendar_client_secret,
				google_calendar_event_title: google_calendar_event_title,
				google_calendar_event_description: google_calendar_event_description,
				google_calendar_2way_sync: google_calendar_2way_sync,
				google_calendar_sync_interval: google_calendar_sync_interval,
				google_calendar_add_attendees: google_calendar_add_attendees,
				google_calendar_send_notification: google_calendar_send_notification,
				google_calendar_can_see_attendees: google_calendar_can_see_attendees,
				google_calendar_appointment_status: google_calendar_appointment_status,
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		}).on('change', 'input[name="input_google_calendar_enable"]', function()
		{
			if( $('input[name="input_google_calendar_enable"]:checked').val() == 'on' )
			{
				$('#google_calendar_settings_area').slideDown(fadeSpeed);
			}
			else
			{
				$('#google_calendar_settings_area').slideUp(fadeSpeed);
			}
		}).on('change', '#input_google_calendar_2way_sync', function()
		{
			if( $(this).val() == 'on_background' )
			{
				$('#input_google_calendar_sync_interval').closest('.form-group').fadeIn(fadeSpeed);
			}
			else
			{
				$('#input_google_calendar_sync_interval').closest('.form-group').fadeOut(fadeSpeed);
			}
		}).on('change', '#input_google_calendar_add_attendees', function ()
		{
			if( $(this).is(':checked') )
			{
				$('#input_google_calendar_send_notification').closest('.form-group').fadeIn(fadeSpeed);
			}
			else
			{
				$('#input_google_calendar_send_notification').closest('.form-group').fadeOut(fadeSpeed);
			}
		});

		$('input[name="input_google_calendar_enable"]').trigger('change');
		$('#input_google_calendar_2way_sync').trigger('change');
		$('#input_google_calendar_add_attendees').trigger('change');
		fadeSpeed = 400;

		$("#input_google_calendar_2way_sync, #input_google_calendar_sync_interval, #input_google_calendar_appointment_status").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select')
		});
	});

})(jQuery);