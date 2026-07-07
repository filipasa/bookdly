(function ($)
{
	"use strict";

	$(document).ready(function()
	{


		booknetic.summernote(
			$('#invoice_body'),
			[
				['style', ['style']],
				['style', ['bold', 'italic', 'underline', 'clear']],
				['fontsize', ['fontsize']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link', 'picture']],
				['view', ['codeview']],
				['height', ['height']]
			],
			invoice_all_shortcodes_obj,
			350
		);

		$(document).on('click', '#invoice_save_btn', function ()
		{
			var invoiceId = $('#invoice-script').data('id');
			var name = $('#input_name').val();
			var content = booknetic.summernoteReplace($('#invoice_body'));

			booknetic.ajax('save', {
				id: invoiceId,
				name: name,
				content: content
			}, function ()
			{
				booknetic.toast(booknetic.__('changes_saved'), 'success');

				location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=invoices';
			});
		}).on('click', '#download_preview', function ()
		{
			var invoiceId = $('#invoice-script').data('id');
			var name = $('#input_name').val();
			var content = booknetic.summernoteReplace($('#invoice_body'));

			booknetic.ajax('save', {
				id: invoiceId,
				name: name,
				content: content
			}, function ( result )
			{
				var id = result['id']
				booknetic.loading(1);

				location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=invoices&action=download&invoice_id=' + id;

				setTimeout(function ()
				{
					booknetic.loading(0);
				}, 4000);
			});
		});

	});

})(jQuery);
