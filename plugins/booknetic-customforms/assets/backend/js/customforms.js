(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.dataTable.onLoad( function () {
			$('#fs_data_table_div').find('table tr').each(function () {
				if( $(this).children('td:last-child').children('a.btn').length === 0 && $(this).children('td:last-child').find('.actions_btn').length !== 0)
				{
					let edit_link = 'admin.php?page=' + BACKEND_SLUG + '&module=customforms&action=edit&form_id=' + $(this).closest('tr').data('id');
					$(this).children('td:last-child').prepend('<a href="'+edit_link+'" class="btn btn-light-success">'+ booknetic.__('Edit') +'</a>');
				}
			});
		})

		$(document).on('click', '#addBtn', function ()
		{
			location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=customforms&action=edit&form_id=0';
		});

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=customforms&action=edit&form_id=' + ids[0];
		}

	});

})(jQuery);