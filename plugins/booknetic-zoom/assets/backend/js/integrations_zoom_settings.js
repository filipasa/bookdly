(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.initKeywordsInput( $( '#input_zoom_meeting_title' ), all_shortcodes_obj );
		booknetic.initKeywordsInput( $( '#input_zoom_meeting_agenda' ), all_shortcodes_obj );

		var fadeSpeed = 0;
		$('#booknetic_settings_area').on('click', '.settings-save-btn', function ()
		{
			var zoom_account_id		        = $("#input_zoom_account_id").val(),
				zoom_client_id		        = $("#input_zoom_client_id").val(),
				zoom_client_secret          = $("#input_zoom_client_secret").val(),
				zoom_meeting_title		    = $("#input_zoom_meeting_title").val(),
				zoom_meeting_agenda	        = $("#input_zoom_meeting_agenda").val(),
				zoom_enable				    = $('input[name="input_zoom_enable"]:checked').val(),
				zoom_set_random_password    = $("#input_zoom_set_random_password").is(':checked') ? 'on' : 'off';

			booknetic.ajax('zoom_settings.save_settings', {
				zoom_account_id: zoom_account_id,
				zoom_client_id: zoom_client_id,
				zoom_client_secret: zoom_client_secret,
				zoom_enable: zoom_enable,
				zoom_meeting_title: zoom_meeting_title,
				zoom_meeting_agenda: zoom_meeting_agenda,
				zoom_set_random_password: zoom_set_random_password
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});
		}).on('change', 'input[name="input_zoom_enable"]', function()
		{
			if( $('input[name="input_zoom_enable"]:checked').val() == 'on' )
			{
				$('#integrations_zoom_settings_area').slideDown(fadeSpeed);
			}
			else
			{
				$('#integrations_zoom_settings_area').slideUp(fadeSpeed);
			}
			fadeSpeed = 400;
		}).on('click', '#disconnect_zoom', function ()
		{
			booknetic.ajax('disconnect_zoom', { }, function ()
			{
				$('#disconnect_zoom_area').fadeOut( 200, function ()
				{
					$('#connect_zoom').fadeIn(200);
				})
			});
		}).on('click', '#connect_zoom', function ()
		{
			booknetic.ajax('Zoom.connect_zoom', { }, function ( result )
			{
				location.href = result['url'];
			});
		})

		$('input[name="input_zoom_enable"]').trigger('change');
	});

})(jQuery);