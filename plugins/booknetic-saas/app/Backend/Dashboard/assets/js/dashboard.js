(function ($)
{
	"use strict";

	const doc = $(document);

	doc.ready(function () {
		doc.on('click', '.boostore-chip', function (e) {
			if ($(e.target).hasClass('boostore-chip-close')) {
				return;
			}

			const slug = $(this).data('slug');
			const url = new URL(window.location.href);

			url.searchParams.set('module', 'boostore');
			url.searchParams.set('action', 'details');
			url.searchParams.set('slug', slug);

			window.location.href = url.href;
		})
			.on('click', '.boostore-chip-close', function (e) {
				e.stopPropagation();

				const chip = $(this).closest('.boostore-chip');
				const slug = chip.data('slug');

				booknetic.ajax('dismiss_notification', {slug}, () => {
					chip.fadeOut(200, function () {
						$(this).remove();

						if ($('.boostore-announcements-chips .boostore-chip').length === 0) {
							$('.boostore-announcements').fadeOut(250, function () {
								$(this).remove();
							});
						}
					});
				});
			})
			.on('click', '.boostore-dismiss-all', function () {
				booknetic.ajax('dismiss_all_notifications', {}, () => {
					$('.boostore-announcements').fadeOut(250, function () {
						$(this).remove();
					});
				});
			});
	});

})(jQuery);
