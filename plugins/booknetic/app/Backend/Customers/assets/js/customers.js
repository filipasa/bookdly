(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		var openCustomerId = new URLSearchParams(window.location.search).get('open_customer');
		if (openCustomerId) {
			// Small timeout to ensure everything has initialized
			setTimeout(function() {
				openCustomerFullPageInfo(openCustomerId);
			}, 100);
		}

		$(document).on('click', '#addBtn', function ()
		{
			openCustomerFullPageEdit(0);
		}).on('click', '#importBtn', function ()
		{
			booknetic.loadModal('import', {}, {'type': 'center', 'width': '650px'});
		});

		// Add Category Click Handler
		$(document).on('click', '#wf-add-category-btn', function(e) {
			e.preventDefault();
			booknetic.loadModal('customer_categories.add_new', { id: 0 });
		});

		// Auto-reload categories when category modal closes
		$(document).on('hidden.bs.modal', '.fs-modal', function () {
			var select = $('#booknetic_customer_fullpage_container #input_category_id');
			if (select.length) {
				var oldCategoriesCount = select.find('option').length - 1;
				booknetic.ajax('customer_categories.get_categories', {}, function(result) {
					var selectedVal = select.val();
					select.empty().append('<option value="">' + booknetic.__('Select category') + '</option>');
					result.categories.forEach(function(cat) {
						var selectedAttr = (cat.id == selectedVal) ? ' selected' : '';
						select.append('<option value="' + cat.id + '"' + selectedAttr + '>' + cat.name + '</option>');
					});
					
					if (result.categories.length > oldCategoriesCount && result.categories.length > 0) {
						var newCat = result.categories[result.categories.length - 1];
						select.val(newCat.id).trigger('change');
					}
				});
			}
		});

		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			openCustomerFullPageInfo(ids[0]);
		}

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			openCustomerFullPageEdit(ids[0]);
		}

		booknetic.dataTable.actionCallbacks['delete'] = function (ids)
		{
			let d = booknetic.can_delete_associated_account ? '<div class="mt-3"> <input type="checkbox" id="input_delete_customer_wp_user" checked><label for="input_delete_customer_wp_user">'+booknetic.__('delete_associated_wordpress_account')+'</label> </div>' : '';
			d = (isSaaSVersion !== undefined && isSaaSVersion===true) ? '' : d;
			booknetic.confirm([ booknetic.__('are_you_sure_want_to_delete'), d], 'danger', 'trash', function(modal)
			{
				let ajaxData = {
					'delete_wp_user': booknetic.can_delete_associated_account ? ( modal.find('#input_delete_customer_wp_user').is(':checked') ? 1 : ((isSaaSVersion != undefined && isSaaSVersion===true) ? 1 : 0) ) : 0
				};

				booknetic.dataTable.doAction('delete', ids, ajaxData, function ()
				{
					booknetic.toast(booknetic.__('Deleted'), 'success', 2000);
				});
			});
		}

		$(document).on('click', '.fs_data_table tbody tr', function (e)
		{
			if ($(e.target).closest('input, button, a, .datatable-actions').length) {
				return;
			}
			var id = $(this).attr('data-id');
			if (id && id !== 'undefined') {
				openCustomerFullPageInfo(id);
			}
		});

		window.openCustomerFullPageInfo = function (id)
		{
			booknetic.ajax('customers.get_fullpage_info_view', { id: id, _t: Date.now() }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_customer_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_customer_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				container.html(booknetic.htmlspecialchars_decode(res.html)).show();
				window.scrollTo(0, 0);
			});
		};

		// Back to table Grid Click Handler
		$(document).on('click', '#booknetic_customer_fullpage_container .wf-back-to-table', function(e)
		{
			e.preventDefault();
			$('#booknetic_customer_fullpage_container').hide().empty();
			$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
			booknetic.dataTable.reload();
		});

		// Tab Switching Click Handler
		$(document).on('click', '#booknetic_customer_fullpage_container .wf-tab-btn', function(e)
		{
			e.preventDefault();
			var tabId = $(this).attr('data-tab');
			var container = $('#booknetic_customer_fullpage_container');
			container.find('.wf-tab-btn').removeClass('active');
			$(this).addClass('active');

			container.find('.wf-tab-pane').removeClass('active');
			container.find('#' + tabId).addClass('active');
		});

		// Save Customer Notes Handler
		$(document).on('click', '#booknetic_customer_fullpage_container .wf-btn-save-cust-notes', function(e)
		{
			e.preventDefault();
			var custId = $(this).attr('data-cust-id');
			var notes = $('#cust_notes_textarea').val();

			booknetic.ajax('customers.save_customer_notes', {
				id: custId,
				notes: notes
			}, function() {
				booknetic.toast(booknetic.__('changes_saved') || 'Notes have been saved!', 'success');
			});
		});

		// View Appointment from Customer Info Handler
		$(document).on('click', '#booknetic_customer_fullpage_container .wf-view-appt-btn', function(e)
		{
			e.preventDefault();
			var apptId = $(this).attr('data-appt-id');
			
			// Load appointments stylesheet dynamically if not already loaded
			var apptsCssUrl = $('#booknetic_customer_fullpage_container .wf-fullpage-container').attr('data-appointments-css');
			if (apptsCssUrl && !$('link[href="' + apptsCssUrl + '"]').length) {
				$('head').append('<link rel="stylesheet" href="' + apptsCssUrl + '">');
			}
			
			// Load appointment view layout directly inside our container
			booknetic.ajax('appointments.get_fullpage_view', { id: apptId, mode: 'view' }, function (res) {
				// Hide customer sidebar/crumbs, inject appointment full page
				$('#booknetic_customer_fullpage_container').html(booknetic.htmlspecialchars_decode(res.html));
			});
		});

		window.openCustomerFullPageEdit = function (id)
		{
			booknetic.ajax('customers.get_fullpage_edit_view', { id: id, _t: Date.now() }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').hide();
				var container = $('#booknetic_customer_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_customer_fullpage_container"></div>');
					$('.fs_data_table_wrapper').parent().append(container);
				}
				var decodedHtml = booknetic.htmlspecialchars_decode(res.html);
				decodedHtml = decodedHtml.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
				container.html(decodedHtml).show();
				window.scrollTo(0, 0);

				// Initialize Select2 & IntlTelInput directly (extracted from inline script to fix parsing bugs)
				if($.fn.select2) {
					$('#input_category_id').select2({ theme: 'bootstrap', placeholder: booknetic.__('select'), allowClear: true });
					$('#input_wp_user').select2({ theme: 'bootstrap', placeholder: booknetic.__('select'), allowClear: true });
				}
				
				let phone_input = $('#input_phone');
				if(phone_input.length && window.bookneticIntlTelInput) {
					var assetUrl = phone_input.closest('.wf-fullpage-container').data('tel-input-asset-url') || '';
					phone_input.data('iti', window.bookneticIntlTelInput(phone_input[0], {
						loadUtilsOnInit: assetUrl,
						initialCountry: phone_input.data('country-code') || 'US',
						separateDialCode: true,
					}));
				}

				// Allow to login toggle handlers
				$(document).off('change', '#input_allow_customer_to_login').on('change', '#input_allow_customer_to_login', function () {
					if( $(this).is(':checked') ) {
						$('[data-hide="allow_customer_to_login"]').slideDown(200);
						$('#input_wp_user_use_existing').trigger('change');
					} else {
						$('[data-hide="allow_customer_to_login"]').slideUp(200);
						$('[data-hide="existing_user"]').slideUp(200);
						$('[data-hide="create_password"]').slideUp(200);
						$('#input_email').removeAttr('readonly');
					}
				});

				$(document).off('change', '#input_wp_user_use_existing').on('change', '#input_wp_user_use_existing', function () {
					if( $(this).val() === 'yes' ) {
						$('[data-hide="existing_user"]').show();
						$('[data-hide="create_password"]').hide();
						$('#input_email').attr('readonly',true);
					} else {
						$('[data-hide="existing_user"]').hide();
						$('[data-hide="create_password"]').show();
						$('#input_email').removeAttr('readonly');
					}
				});

				$(document).off('change', '#input_wp_user').on('change', '#input_wp_user', function () {
					booknetic.ajax('getWpUserData', {id: $(this).val()}, function (result) {
						const email = $('#input_email');
						const firstName = $('#input_first_name');
						const lastName = $('#input_last_name');
						email.attr('readonly',true);
						email.val( result.email );
						firstName.val( result.firstName );
						lastName.val( result.lastName );
					});
				});

				$('#input_wp_user_use_existing').trigger('change');
				$('#input_allow_customer_to_login').trigger('change');

				$(document).off('change', '#input_image').on('change', '#input_image', function() {
					if (this.files) {
						if (this.files[0]) {
							var reader = new FileReader();
							reader.onload = function(e) {
								$('#wf-avatar-preview').html('<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">');
							};
							reader.readAsDataURL(this.files[0]);
						}
					}
				});
				
				$(document).off('click', '.wf-btn-save-customer').on('click', '.wf-btn-save-customer', function() {
					let iti = $("#input_phone").data('iti');
					let wp_user = $("#input_wp_user").val();
					let first_name = $("#input_first_name").val();
					let last_name = $("#input_last_name").val();
					let gender = $("#input_gender").val();
					let birthday = $("#input_birthday").val();
					let phone = iti ? iti.getNumber(bookneticIntlTelInput.utils.numberFormat.E164) : $("#input_phone").val();
					let email = $("#input_email").val();
					let allow_customer_to_login = $("#input_allow_customer_to_login").is(':checked') ? 1 : 0;
					let wp_user_use_existing = $("#input_wp_user_use_existing").val();
					let wp_user_password = $("#input_wp_user_password").val();
					let categoryId = $("#input_category_id").val();
					let note = $("#input_note").val();
					let image = $("#input_image")[0] ? $("#input_image")[0].files[0] : null;
					let run_workflows = $("#input_run_workflows").is(':checked') ? 1 : 0;
					
					const id = $('#booknetic_customer_fullpage_container .wf-fullpage-container').attr('data-customer-id') || 0;

					if (!first_name || !last_name) {
						booknetic.toast(booknetic.__('Please fill all required fields!'), "unsuccess");
						return;
					}

					if (phone && phone.length > 0 && iti) {
						if (!iti.isValidNumber()) {
							booknetic.toast(booknetic.__('phone_is_not_valid'), "unsuccess");
							return;
						}
					}

					let data = new FormData();
					data.append('id', id);
					if (allow_customer_to_login) {
						if (wp_user_use_existing === 'yes') {
							data.append('wp_user', wp_user);
						}
					}
					data.append('first_name', first_name);
					data.append('last_name', last_name);
					data.append('gender', gender);
					data.append('birthday', birthday);
					data.append('phone', phone);
					data.append('email', email);
					data.append('allow_customer_to_login', allow_customer_to_login);
					data.append('wp_user_use_existing', wp_user_use_existing);
					data.append('wp_user_password', wp_user_password);
					data.append('categoryId', categoryId);
					data.append('note', note);
					if (image) {
						data.append('image', image);
					}
					data.append('run_workflows', run_workflows);

					let ajaxUrl = id > 0 ? 'customers.update' : 'customers.create';

					booknetic.ajax(ajaxUrl, data, function(result) {
						booknetic.toast(booknetic.__('Saved') || 'Changes saved successfully!', 'success');
						// Go back to the table view
						$('#booknetic_customer_fullpage_container').hide().empty();
						$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap').show();
						booknetic.dataTable.reload();
					});
				});
			});
		};

	});

})(jQuery);