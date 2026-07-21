(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$('.m_head_actions').prepend('<button type="button" class="btn btn-primary btn-lg" id="addCategoryBtn"><i class="fa fa-plus"></i> '+booknetic.__('add_category')+'</button>');
		$('.m_head_actions').prepend('<a href="?page=' + BACKEND_SLUG + '&module=services&action=edit_order" type="button" class="btn btn-primary btn-lg" id="editOrderBtn"><i class="fa fa-arrows-alt mr-2" aria-hidden="true"></i> '+ booknetic.__( "edit_order" ) +'</a>');
		$('.m_head_actions').prepend('<a href="?page=' + BACKEND_SLUG + '&module=services&view=org" type="button" class="btn btn-outline-secondary btn-lg">'+booknetic.__('graphic_view')+'</a>');

		$(document).on('click', '#addBtn', function()
		{
			openServiceFullPage(0);
		}).on('click', '#addCategoryBtn', function()
		{
			booknetic.loadModal('service_categories.add_new', {'id': 0}, {type: 'center'});
		});

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			openServiceFullPage(ids[0]);
		}

		booknetic.dataTable.actionCallbacks['share'] = function (ids)
		{
			booknetic.loadModal('Base.direct_link', {'service_id': ids[0]} , { type:'center' });
		}

		window.openServiceFullPage = function(id, categoryId)
		{
			booknetic.ajax('services.get_fullpage_view', { id: id, category_id: categoryId || 0, _t: Date.now() }, function (res)
			{
				$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap, #services_map, #select_add_type').hide();
				var container = $('#booknetic_service_fullpage_container');
				if (!container.length) {
					container = $('<div id="booknetic_service_fullpage_container"></div>');
					$('.fs_data_table_wrapper, #services_map').first().parent().append(container);
				}
				var decodedHtml = booknetic.htmlspecialchars_decode(res.html);
				container.html(decodedHtml).show();
				window.scrollTo(0, 0);
			});
		};

		// Back to Services List click handler
		$(document).on('click', '#booknetic_service_fullpage_container .wf-back-to-table', function(e)
		{
			e.preventDefault();
			$('#booknetic_service_fullpage_container').hide().empty();
			$('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap, #services_map').show();
			if (booknetic.dataTable) {
				booknetic.dataTable.reload();
			}
		});
	});

})(jQuery);