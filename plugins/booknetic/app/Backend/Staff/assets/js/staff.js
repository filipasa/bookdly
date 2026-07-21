(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		window.onerror = function(message, source, lineno, colno, error) {
			var errorData = {
				message: "JS Runtime Error: " + message,
				source: source,
				lineno: lineno,
				colno: colno,
				stack: error ? error.stack : ''
			};
			$.ajax({
				type: 'POST',
				url: '/wp-content/log_error.php',
				data: {
					data: JSON.stringify(errorData)
				},
				async: false
			});
			return false;
		};

		$(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
			var errorData = {
				message: "AJAX Error: " + thrownError,
				url: ajaxSettings.url,
				type: ajaxSettings.type,
				data: ajaxSettings.data,
				status: jqXHR.status,
				responseText: jqXHR.responseText ? jqXHR.responseText.substring(0, 1000) : ''
			};
			$.ajax({
				type: 'POST',
				url: '/wp-content/log_error.php',
				data: {
					data: JSON.stringify(errorData)
				},
				async: false
			});
		});

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			openStaffFullPage(ids[0]);
		}

		booknetic.dataTable.actionCallbacks['delete'] = function (ids)
		{
			let d = booknetic.can_delete_associated_account ? '<div class="mt-3"> <input type="checkbox" id="input_delete_staff_wp_user"><label for="input_delete_staff_wp_user">'+booknetic.__('delete_associated_wordpress_account')+'</label> </div>' : '';

			booknetic.confirm([ booknetic.__('are_you_sure_want_to_delete'), d], 'danger', 'trash', function(modal)
			{
				let ajaxData = {
					'delete_wp_user': booknetic.can_delete_associated_account ? (modal.find('#input_delete_staff_wp_user').is(':checked') ? 1 : 0) : 0
				};

				booknetic.dataTable.doAction('delete', ids, ajaxData, function ()
				{
					booknetic.toast(booknetic.__('Deleted'), 'success', 2000);
				});
			});
		}

		booknetic.dataTable.actionCallbacks['share'] = function (ids)
		{
			booknetic.loadModal('Base.direct_link', {'staff_id': ids[0]} , { type:'center' });
		}

		$(document).on('click', '#addBtn', function ()
		{
			openStaffFullPage(0);
		});

		window.openStaffFullPage = function(id)
		{
			var ajaxAction = id > 0 ? 'edit' : 'add_new';
			booknetic.ajax(ajaxAction, { id: id || 0, _t: Date.now() }, function (res)
			{
				$('.m_header, .bkc-page-container').hide();
				var container = $('#booknetic_staff_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_staff_fullpage_container" class="fs-modal"></div>');
					$('.bkc-page-container').first().parent().append(container);
				}
				var decodedHtml = booknetic.htmlspecialchars_decode(res.html);
				container.html(decodedHtml).show();
				window.scrollTo(0, 0);
			});
		};

		// Back to Staff List click handler
		$(document).on('click', '#booknetic_staff_fullpage_container .back-link, #booknetic_staff_fullpage_container [data-dismiss="modal"]', function(e)
		{
			e.preventDefault();
			$('#booknetic_staff_fullpage_container').hide().empty();
			$('.m_header, .bkc-page-container').show();
			if (booknetic.dataTable) {
				booknetic.dataTable.reload();
			}
		});

		var js_parameters = $('#staff-js12394610');

		if( js_parameters.data('edit') > 0 )
		{
			openStaffFullPage(js_parameters.data('edit'));
		}

	});

})(jQuery);