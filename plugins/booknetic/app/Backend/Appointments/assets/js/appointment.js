(function ($)
{
	"use strict";

	var stagedCustomFiles = {};

	$(document).ready(function()
	{
		var openAppointmentId = new URLSearchParams(window.location.search).get('open_appointment');
		if (openAppointmentId) {
			setTimeout(function() {
				openAppointmentFullPage(openAppointmentId, 'view');
			}, 100);
		}
		window.openChangeStatusFullPage = function (ids)
		{
			booknetic.ajax('appointments.get_change_status_fullpage_view', { ids: ids }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_appointment_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_appointment_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				container.html(booknetic.htmlspecialchars_decode(res.html)).show();
				window.scrollTo(0, 0);
			});
		};

		window.openAppointmentFullPage = function (id, mode)
		{
			booknetic.ajax('appointments.get_fullpage_view', { id: id, mode: mode || 'view' }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_appointment_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_appointment_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				container.html(booknetic.htmlspecialchars_decode(res.html)).show();
				window.scrollTo(0, 0);

				if (mode === 'edit' || mode === 'add') {
					initializeEditFields(id);
				}
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
			openChangeStatusFullPage(ids);
		};

		$(document).on('click', '#addBtn', function ()
		{
			openAppointmentFullPage(0, 'add');
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
			$('#wf-hide-appointments-style').remove();
			$('#booknetic_appointment_fullpage_container').hide().empty();
			$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
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
					$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .table-wrap').show();
					booknetic.dataTable.reload();
				});
			});
		});

		// --- EDIT MODE OPERATIONAL FUNCTIONS ---

		function initializeEditFields(id)
		{
			var container = $('#booknetic_appointment_fullpage_container');
			if (!container.find('#input_location').length) {
				return; // Not in edit mode
			}

			// 1. Initialize Select2 for Location
			booknetic.select2Ajax(container.find("#input_location"), 'appointments.get_locations');

			// 2. Initialize Select2 for Service Categories
			booknetic.select2Ajax(container.find(".input_category"), 'appointments.get_service_categories', function(select) {
				var prevSelect = select.parent().prev().find('select.input_category');
				if (prevSelect.length === 0) {
					prevSelect = select.parent().prev().children('select.input_category');
				}
				return {
					category: prevSelect.length > 0 ? prevSelect.val() : 0
				}
			});

			// 3. Initialize Select2 for Services
			booknetic.select2Ajax(container.find("#input_service"), 'appointments.get_services', function() {
				return {
					category: container.find(".input_category:eq(-1)").val()
				}
			});

			// 4. Initialize Select2 for Staff
			booknetic.select2Ajax(container.find("#input_staff"), 'appointments.get_staff', function() {
				var location = container.find("#input_location").val(),
					service = container.find("#input_service").val();
				return {
					location: location,
					service: service
				}
			});

			// 5. Initialize Date Picker
			var format = typeof dateFormat !== 'undefined' ? dateFormat : 'YYYY-MM-DD';
			var weekStart = typeof weekStartsOn !== 'undefined' && weekStartsOn === 'sunday' ? 0 : 1;
			container.find("#input_date").datepicker({
				autoclose: true,
				format: format.replace('YYYY','Y').replace('Y', 'yyyy')
					.replace('MM', 'm').replace('m', 'mm')
					.replace('DD','d').replace('d', 'dd'),
				weekStart: weekStart
			});

			// 6. Initialize Select2 for Time slots
			booknetic.select2Ajax(container.find("#input_time"), 'appointments.get_available_times', function() {
				var service = container.find("#input_service").val(),
					location = container.find("#input_location").val(),
					staff = container.find("#input_staff").val(),
					date = container.find("#input_date").val();
				return {
					id: id,
					service: service,
					location: location,
					staff: staff,
					date: date
				}
			});

			// 7. Initialize Select2 for Customer
			booknetic.select2Ajax(container.find(".input_customer"), 'appointments.get_customers');

			// 8. Event listeners for form changes
			container.off('change', '.input_category').on('change', '.input_category', function() {
				var categId = $(this).val();
				while ($(this).parent().next().children('select').length > 0) {
					$(this).parent().next().remove();
				}
				if (categId > 0 && $(this).select2('data')[0].have_sub_categ > 0) {
					$(this).parent().after('<div class="mt-2"><select class="form-control input_category"></select></div>');
					booknetic.select2Ajax($(this).parent().next().children('select'), 'appointments.get_service_categories', function(select) {
						var prevSelect = select.parent().prev().find('select.input_category');
						if (prevSelect.length === 0) {
							prevSelect = select.parent().prev().children('select.input_category');
						}
						return {
							category: prevSelect.length > 0 ? prevSelect.val() : 0
						}
					});
				}
				container.find("#input_service").select2('val', false);
			});

			container.off('change', '#input_location').on('change', '#input_location', function() {
				container.find("#input_staff").select2('val', false);
			});

			container.off('change', '#input_service').on('change', '#input_service', function() {
				container.find("#input_staff").select2('val', false);
				loadServiceExtrasEdit();
				loadCustomFieldsEdit();
			});

			container.off('change', '#input_staff').on('change', '#input_staff', function() {
				container.find("#input_date").attr('disabled', (!$(this).val()));
				container.find("#input_time").attr('disabled', (!$(this).val()));
				container.find("#input_date").val('');
				container.find("#input_time").empty().trigger('change');
				loadCouponsEdit();
			});

			container.off('change', '#input_date').on('change', '#input_date', function() {
				container.find("#input_time").select2('val', false);
				container.find("#input_time").trigger('change');
			});

			container.off('change', '.input_customer').on('change', '.input_customer', function() {
				loadCouponsEdit();
			});

			// Status Selection Click Handler
			container.off('click', '.wf-status-option').on('click', '.wf-status-option', function() {
				container.find('.wf-status-option').removeClass('active-status');
				$(this).addClass('active-status');
			});

			// Custom Fields Files Upload Change Listeners
			stagedCustomFiles = {};
			container.off('change', '#tab_custom_fields_edit .form-control[type="file"]').on('change', '#tab_custom_fields_edit .form-control[type="file"]', function(e)
			{
				var isMultiple = $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple';
				if (isMultiple) {
					var inputId = $(this).data('input-id');
					var files = e.target.files;
					if (typeof stagedCustomFiles[inputId] === 'undefined') {
						stagedCustomFiles[inputId] = [];
					}
					for (var i = 0; i < files.length; i++) {
						var exists = false;
						for (var j = 0; j < stagedCustomFiles[inputId].length; j++) {
							if (stagedCustomFiles[inputId][j].name === files[i].name && stagedCustomFiles[inputId][j].size === files[i].size) {
								exists = true;
								break;
							}
						}
						if (!exists) {
							stagedCustomFiles[inputId].push(files[i]);
						}
					}
					renderStagedFiles($(this));
					$(this).val('');
				}
			});

			container.off('click', '#tab_custom_fields_edit .remove_staged_file_btn').on('click', '#tab_custom_fields_edit .remove_staged_file_btn', function() {
				var index = $(this).data('index');
				var inputEl = $(this).closest('.form-group').find('.form-control[type="file"]');
				var inputId = inputEl.data('input-id');
				
				if (stagedCustomFiles[inputId]) {
					stagedCustomFiles[inputId].splice(index, 1);
					renderStagedFiles(inputEl);
				}
			});

			// Load Initial Addons Tabs Content
			loadServiceExtrasEdit();
			loadCouponsEdit();
			loadCustomFieldsEdit();
		}

		// Helper to render staged upload files in Custom Fields
		function renderStagedFiles(inputEl) {
			var inputId = inputEl.data('input-id');
			var container = inputEl.parent().find('.staged-files-container');
			container.empty();
			
			var files = stagedCustomFiles[inputId] || [];
			for (var i = 0; i < files.length; i++) {
				var fileItem = $('<div class="custom-file-item staged-file-item" style="margin-top: 5px; display: inline-flex; align-items: center; gap: 6px;"></div>');
				var removeBtn = $('<span class="remove_staged_file_btn" data-index="' + i + '" style="cursor: pointer; color: #ff5c75; font-weight: bold; font-size: 16px; margin-right: 5px;">&times;</span>');
				var fileNameLink = $('<span style="font-weight: 500; font-size: 13px;">' + files[i].name + '</span>');
				
				fileItem.append(removeBtn).append(fileNameLink);
				container.append(fileItem);
			}
			
			var browseLabel = inputEl.parent().find('label[data-has-label="true"]');
			var totalFilesCount = files.length;
			if (totalFilesCount > 0) {
				browseLabel.text(totalFilesCount + " file(s) staged");
			} else {
				browseLabel.text(inputEl.attr('placeholder') || "Browse");
			}
		}

		// Helper to load Service Extras in Edit Mode
		function loadServiceExtrasEdit()
		{
			var container = $('#booknetic_appointment_fullpage_container');
			var tabExtras = container.find('#tab_extras');
			if (!tabExtras.length) return;

			var service_id = container.find('#input_service').val();
			var appointment_id = container.find('.wf-btn-save-changes').attr('data-id') || 0;

			booknetic.ajax('appointments.get_service_extras', { appointment_id: appointment_id, service_id: service_id }, function (result) {
				tabExtras.html(booknetic.htmlspecialchars_decode(result['html']));
			});
		}

		// Helper to load Coupons Edit Form
		function loadCouponsEdit()
		{
			var container = $('#booknetic_appointment_fullpage_container');
			var cpnTab = container.find('#coupons-edit-tab');
			if (!cpnTab.length) return;

			var service_id = container.find('#input_service').val();
			var staff_id = container.find('#input_staff').val();
			var customer_id = container.find('.input_customer').val();
			var appointment_id = container.find('.wf-btn-save-changes').attr('data-id') || 0;

			booknetic.ajax('Coupons.load_edit_tab_content', {
				appointment: appointment_id,
				service: service_id,
				staff: staff_id,
				customer: customer_id
			}, function (result) {
				cpnTab.html(booknetic.htmlspecialchars_decode(result['html']));
			});
		}

		// Helper to load Custom Fields Edit Form
		function loadCustomFieldsEdit()
		{
			var container = $('#booknetic_appointment_fullpage_container');
			var customFieldsDiv = container.find('#custom_fields');
			if (!customFieldsDiv.length) return;

			var service_id = container.find('#input_service').val();
			var appointment_id = container.find('.wf-btn-save-changes').attr('data-id') || 0;

			booknetic.ajax('Customforms.appointment_load_custom_fields', {
				appointment_id: appointment_id,
				service_id: service_id,
			}, function (result) {
				customFieldsDiv.html(booknetic.htmlspecialchars_decode(result['html']));
			});
		}

		// Save Changes Event Handler
		$(document).on('click', '#booknetic_appointment_fullpage_container .wf-btn-save-changes', function(e)
		{
			e.preventDefault();
			var container = $('#booknetic_appointment_fullpage_container');
			var appointment_id = $(this).attr('data-id');

			var location = container.find("#input_location").val(),
				service = container.find("#input_service").val(),
				staff = container.find("#input_staff").val(),
				date = container.find("#input_date").val(),
				time = container.find("#input_time").val(),
				note = container.find("#input_note").val(),
				customer_id = container.find(".input_customer").val(),
				status = container.find(".wf-status-option.active-status").attr('data-status'),
				weight = 1,
				run_workflows = container.find('#input_run_workflows').is(':checked') ? 1 : 0,
				extras = [];

			// Collect extras
			container.find('#tab_extras div[data-extra-id]').each(function ()
			{
				var extra_id = $(this).data('extra-id'),
					quantity = $(this).find('.extra_quantity').val();

				if (quantity > 0) {
					extras.push({
						extra: extra_id,
						quantity: quantity
					});
				}
			});

			if (staff == '' || service == '' || customer_id == '') {
				booknetic.toast('Please fill all required fields!', 'unsuccess');
				return;
			}

			var data = new FormData();
			var obj = {};

			obj['id'] = appointment_id;
			obj['location'] = location;
			obj['service'] = service;
			obj['staff'] = staff;
			obj['date'] = date;
			obj['time'] = booknetic.reformatTimeFromCustomFormat(time);
			obj['note'] = note;
			obj['customer_id'] = customer_id;
			obj['status'] = status;
			obj['weight'] = weight;
			obj['service_extras'] = extras;

			data.append('run_workflows', run_workflows);
			data.append('current', 0);

			// --- COLLECT CUSTOM FIELDS ---
			var customFields = {};
			container.find("#tab_custom_fields_edit [data-input-id][type!='checkbox'][type!='radio']").each(function()
			{
				var inputId = $(this).data('input-id'),
					inputVal = $(this).val();

				if (inputVal === null) inputVal = '';

				if ($(this).attr('type') === 'file')
				{
					let isMultiple = $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple';
					if (isMultiple)
					{
						let remainingFiles = [];
						$(this).parent().find('.uploaded-files-container .remove_custom_file_btn').each(function() {
							remainingFiles.push({
								path: $(this).attr('data-file-path'),
								name: $(this).parent().find('a').text()
							});
						});

						let files = stagedCustomFiles[inputId] || [];
						let fileList = [];
						for (let i = 0; i < files.length; i++)
						{
							var uniqueId = Math.random().toString(36).substring(2, 9);
							data.append('custom_files[' + uniqueId + ']', files[i]);
							fileList.push({
								id: uniqueId,
								name: files[i].name
							});
						}
						
						customFields[inputId] = {
							multiple: "true",
							remaining: remainingFiles,
							new_files: fileList
						};
					}
					else
					{
						let hasNotRemoveButton = $(this).parent().find('.remove_custom_file_btn').length === 0;
						if (hasNotRemoveButton)
						{
							var uniqueId = Math.random().toString(36).substring(2, 9);
							data.append('custom_files[' + uniqueId + ']', $(this)[0].files[0] !== undefined ? $(this)[0].files[0] : '-1');
							customFields[inputId] = uniqueId;
						}
					}
				}
				else
				{
					customFields[inputId] = inputVal;
				}
			});

			container.find("#tab_custom_fields_edit [data-input-id][type='checkbox']").each(function ()
			{
				var inputId = $(this).data('input-id'),
					inputVal = $(this).val(),
					checked = $(this).is(':checked');

				if (checked)
				{
					if (typeof customFields[inputId] == 'undefined')
						customFields[inputId] = inputVal;
					else
						customFields[inputId] += "," + inputVal;
				}
			});

			container.find("#tab_custom_fields_edit [data-input-id][type='radio']").each(function ()
			{
				var inputId = $(this).data('input-id'),
					inputVal = $(this).val(),
					checked = $(this).is(':checked');

				if (checked)
				{
					customFields[inputId] = inputVal;
				}
			});

			obj['custom_fields'] = customFields;

			// Apply the filter for custom fields (from Custom Forms addon)
			obj = booknetic.doFilter('appointments.save_edited_appointment.cart', obj, data);
			data.append('cart', JSON.stringify([obj]));

			// Apply the filter for coupons (from Coupons addon)
			if (container.find('.input_coupon').length > 0) {
				data.append('coupon', container.find('.input_coupon').val());
			}
			data = booknetic.doFilter('ajax_appointments.save_edited_appointment', data);

			var action = (appointment_id == '0' || !appointment_id) ? 'appointments.create_appointment' : 'appointments.save_edited_appointment';
			booknetic.ajax(action, data, function(result)
			{
				booknetic.toast(booknetic.__('changes_saved') || 'Changes have been saved!', 'success');
				// Go back to the table list cleanly and show all search/filters
				$('#booknetic_appointment_fullpage_container').hide().empty();
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
				booknetic.dataTable.reload();
			});
		});

		// Remove Coupon Click Handler
		$(document).on('click', '#booknetic_appointment_fullpage_container .wf-remove-coupon-btn', function(e)
		{
			e.preventDefault();
			var container = $('#booknetic_appointment_fullpage_container');
			var couponSelect = container.find('.input_coupon');
			if (couponSelect.length > 0) {
				couponSelect.val('-1').trigger('change');
				booknetic.toast('Coupon removed. Save changes to apply.', 'success');
			}
			// Visually hide/remove the applied coupon box immediately
			$(this).closest('div[style*="background: #f0fdf4"]').slideUp(200, function() {
				$(this).remove();
			});
		});

		// Back to table / Cancel Add Click Handler
		$(document).on('click', '#booknetic_appointment_fullpage_container .wf-back-to-table, #booknetic_appointment_fullpage_container .wf-cancel-add', function(e)
		{
			e.preventDefault();
			$('#wf-hide-appointments-style').remove();
			$('#booknetic_appointment_fullpage_container').hide().empty();
			$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
			booknetic.dataTable.reload();
		});

		// Change Status Card selection click handler
		$(document).on('click', '#booknetic_appointment_fullpage_container .cs-status-card', function(e)
		{
			e.preventDefault();
			var container = $('#booknetic_appointment_fullpage_container');
			container.find('.cs-status-card').removeClass('active');
			$(this).addClass('active');

			var status = $(this).attr('data-status');
			var color = $(this).attr('data-color');
			var title = $(this).attr('data-title');

			var badge = container.find('#cs-preview-badge');
			badge.attr('class', 'cs-preview-badge ' + status);
			badge.css({
				'background': color + '22',
				'color': color
			});
			badge.find('.cs-preview-dot').css('background', color);
			container.find('#cs-preview-text').text(title);
		});

		// Apply Status save action handler
		$(document).on('click', '#booknetic_appointment_fullpage_container .wf-btn-apply-status', function(e)
		{
			e.preventDefault();
			var container = $('#booknetic_appointment_fullpage_container');
			var activeCard = container.find('.cs-status-card.active');
			if (!activeCard.length) {
				booknetic.toast('Please select a status first.', 'unsuccess');
				return;
			}

			var status = activeCard.attr('data-status');
			var idsStr = $(this).attr('data-ids');
			var ids = idsStr.split(',');
			var run_workflows = container.find('#input_run_workflows_cs').is(':checked') ? 1 : 0;

			booknetic.ajax('appointments.change_status_save', {
				ids: ids,
				status: status,
				run_workflows: run_workflows
			}, function() {
				booknetic.toast('Status applied successfully!', 'success');
				container.hide().empty();
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
				booknetic.dataTable.reload();
			});
		});

	});

})(jQuery);
