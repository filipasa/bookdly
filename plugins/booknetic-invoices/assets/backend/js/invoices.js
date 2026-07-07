(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(document).on('click', '#addBtn', function ()
		{
			location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=invoices&action=edit&invoice_id=0';
		});

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=invoices&action=edit&invoice_id=' + ids[0];
		}

	});

})(jQuery);