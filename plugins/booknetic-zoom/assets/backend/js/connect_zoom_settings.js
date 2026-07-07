(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		var fadeSpeed = 0;

		$('#booknetic_settings_area').on('click', '#disconnect_zoom', function ()
		{
			booknetic.ajax('Zoom.disconnect_zoom', { }, function ()
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
		});


	});

})(jQuery);