(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		window.openAppointmentFullPage = function (id, mode)
		{
			booknetic.ajax('appointments.get_fullpage_view', { id: id, mode: mode || 'view' }, function (res)
			{
				$('.m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_appointment_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_appointment_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				container.html(booknetic.htmlspecialchars_decode(res.html)).show();
				window.scrollTo(0, 0);
			});
		};

		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			openAppointmentFullPage(ids[0], 'view');
		};

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			openAppointmentFullPage(ids[0], 'edit');
		};

		booknetic.dataTable.actionCallbacks['change_status'] = function (ids)
		{
			booknetic.loadModal('change_status', {'ids': ids});
		};

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		});

		// Clicking anywhere on an appointment row opens the full-page wireframe view
		$(document).on('click', '.fs_data_table tbody tr', function (e)
		{
			if ($(e.target).closest('input, button, a, .datatable-actions').length) {
				return;
			}
			var id = $(this).attr('data-id');
			if (id && id !== 'undefined') {
				openAppointmentFullPage(id, 'view');
			}
		});

		// Back to Appointments Table
		$(document).on('click', '.wf-back-to-table', function (e)
		{
			e.preventDefault();
			$('#booknetic_appointment_fullpage_container').hide().empty();
			$('.m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
		});

		// Toggle View / Edit Mode
		$(document).on('click', '.wf-switch-mode', function (e)
		{
			e.preventDefault();
			var id = $(this).attr('data-id');
			var mode = $(this).attr('data-mode');
			if (id && mode) {
				openAppointmentFullPage(id, mode);
			}
		});

		// Tab Switcher inside Full-Page View
		$(document).on('click', '.wf-tab-btn', function (e)
		{
			e.preventDefault();
			var bar = $(this).closest('.wf-tab-bar');
			bar.find('.wf-tab-btn').removeClass('active');
			$(this).addClass('active');
			var tabId = $(this).attr('data-tab');
			var wrapper = $(this).closest('.wf-main-content');
			wrapper.find('.wf-tab-panel').removeClass('active').hide();
			wrapper.find('#' + tabId).addClass('active').show();
		});

		// Delete Appointment from Full-Page View
		$(document).on('click', '.wf-delete-appt', function (e)
		{
			e.preventDefault();
			var id = $(this).attr('data-id');
			booknetic.confirm(booknetic.__('are_you_sure'), 'danger', 'trash', function()
			{
				booknetic.ajax('appointments.delete', { ids: [id] }, function()
				{
					$('#booknetic_appointment_fullpage_container').hide().empty();
					$('.m_header, .fs_data_table_wrapper, .table-wrap').show();
					booknetic.dataTable.reload();
				});
			});
		});

	});

})(jQuery);
