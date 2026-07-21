(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			openPaymentFullPageInfo(ids[0]);
		}

		$(document).on('click', '.fs_data_table tbody tr', function (e)
		{
			if ($(e.target).closest('input, button, a, .datatable-actions').length) {
				return;
			}
			var id = $(this).attr('data-id');
			if (id && id !== 'undefined') {
				openPaymentFullPageInfo(id);
			}
		});

		window.openPaymentFullPageInfo = function (id)
		{
			booknetic.ajax('payments.get_fullpage_info_view', { id: id, _t: Date.now() }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_payment_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_payment_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				// Strip scripts to prevent execution conflicts
				var decodedHtml = booknetic.htmlspecialchars_decode(res.html);
				decodedHtml = decodedHtml.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
				container.html(decodedHtml).show();
				window.scrollTo(0, 0);
			});
		};

		window.openPaymentFullPageEdit = function (id)
		{
			booknetic.ajax('payments.get_fullpage_edit_view', { id: id, _t: Date.now() }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_payment_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_payment_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				// Strip scripts
				var decodedHtml = booknetic.htmlspecialchars_decode(res.html);
				decodedHtml = decodedHtml.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
				container.html(decodedHtml).show();
				window.scrollTo(0, 0);
			});
		};

		// Back to table Grid Click Handler
		$(document).on('click', '#booknetic_payment_fullpage_container .wf-back-link, #booknetic_payment_fullpage_container #wf-edit-payment-cancel', function(e)
		{
			e.preventDefault();
			$('#booknetic_payment_fullpage_container').hide().empty();
			$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
			booknetic.dataTable.reload();
		});

		// Mode Switching Toggles
		$(document).on('click', '#booknetic_payment_fullpage_container #payModeViewBtn', function(e)
		{
			e.preventDefault();
			var id = $(this).attr('data-id') || $(this).closest('.wf-fullpage-container').data('payment-id');
			if(id) {
				openPaymentFullPageInfo(id);
			}
		});

		$(document).on('click', '#booknetic_payment_fullpage_container #payModeEditBtn', function(e)
		{
			e.preventDefault();
			var id = $(this).attr('data-id') || $(this).closest('.wf-fullpage-container').data('payment-id');
			if(id) {
				openPaymentFullPageEdit(id);
			}
		});

		// Redirect to Customers page
		$(document).on('click', '#booknetic_payment_fullpage_container .wf-customer-link', function(e)
		{
			e.preventDefault();
			var customerId = $(this).data('id');
			var pageParam = new URLSearchParams(window.location.search).get('page') || 'Booknetic';
			location.href = 'admin.php?page=' + pageParam + '&module=customers&open_customer=' + customerId;
		});

		// Redirect to Appointments page
		$(document).on('click', '#booknetic_payment_fullpage_container .wf-appointment-link', function(e)
		{
			e.preventDefault();
			var appointmentId = $(this).data('id');
			var pageParam = new URLSearchParams(window.location.search).get('page') || 'Booknetic';
			location.href = 'admin.php?page=' + pageParam + '&module=appointments&open_appointment=' + appointmentId;
		});

		// Complete Payment Action
		$(document).on('click', '#booknetic_payment_fullpage_container .wf-btn-complete', function(e)
		{
			e.preventDefault();
			var id = $(this).data('id') || $(this).closest('.wf-fullpage-container').data('payment-id');
			
			booknetic.ajax('payments.complete_payment', { id: id }, function() {
				booknetic.toast(booknetic.__('Payment completed successfully!'), 'success');
				openPaymentFullPageInfo(id);
			});
		});

		// Save Payment Changes Action
		$(document).on('click', '#booknetic_payment_fullpage_container #wf-edit-payment-save', function(e)
		{
			e.preventDefault();
			var id = $(this).data('id') || $(this).closest('.wf-fullpage-container').data('payment-id');
			var prices = {},
				paid_amount = $("#booknetic_payment_fullpage_container #input_paid_amount").val(),
				status = $("#booknetic_payment_fullpage_container #input_payment_status").val();

			if( paid_amount === '' )
			{
				booknetic.toast(booknetic.__('Please fill all required fields!'), 'unsuccess');
				return;
			}

			$('#booknetic_payment_fullpage_container .prices-section [data-price-id]').each(function ()
			{
				prices[ $(this).data('price-id') ] = $(this).val();
			});

			var data = new FormData();
			data.append('id', id);
			data.append('prices', JSON.stringify( prices ));
			data.append('paid_amount', paid_amount);
			data.append('status', status);

			booknetic.ajax( 'payments.save_payment', data, function()
			{
				booknetic.toast(booknetic.__('Payment saved successfully!'), 'success');
				openPaymentFullPageInfo(id);
			});
		});

	});

})(jQuery);