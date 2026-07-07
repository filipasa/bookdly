(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		booknetic.initMultilangInput( $( '#formbuilder_options_label' ), 'form_inputs', 'label' );
		booknetic.initMultilangInput( $( '#formbuilder_options_help_text' ), 'form_inputs', 'help_text' );

		booknetic.setTranslatingFieldSelector( '#formbuilder_area > .active_element' );

		function formbuilderSaveChanges( el )
		{
			var type = el.data('type');

			var saveObj = {};

			$("#formbuilder_options > [data-for]").each(function()
			{
				var forList = ',' + $(this).data('for') + ',';

				if( forList.indexOf( ',' + type + ',' ) > -1 )
				{
					if( $(this).data('choices') == true )
					{
						var choices = [];

						$("#choices_area > .select_options").each(function()
						{
							var cId = $(this).data('id');
							var cTitle = $(this).find('input[type="text"]').val();

							var cTranslations = $( this ).find( 'input[type="text"]' ).data( 'translations' )
							cTranslations = cTranslations ? JSON.stringify({
								title: cTranslations
							} ) : '';

							choices.push( [ cId, cTitle, cTranslations ] );
						});

						saveObj['choices'] = choices;
					}
					else
					{
						$(this).find('[id]').each(function()
						{
							var id = $(this).attr('id');
							id = id.replace('formbuilder_options_', '');

							var val = $(this).attr('type') == 'checkbox' ? $(this).is(':checked') : $(this).val();

							saveObj[id]	= val;
						});
					}
				}
			});

			el.data('options', saveObj);
		}

		function formbuilderConstructElement( el )
		{
			var data = el.data('options');
			data = !data ? {} : data;

			var checkAttrs = ['placeholder', 'help_text', 'min_length', 'max_length', 'url', 'allowed_file_formats']

			for( var i in checkAttrs )
			{
				if( typeof data[ checkAttrs[i] ] == 'undefined' )
				{
					data[ checkAttrs[i] ] = '';
				}
			}

			if( typeof data['label'] == 'undefined' )
			{
				data['label'] = el.find('[data-label]').text().trim();
			}
			if( typeof data['is_required'] == 'undefined' )
			{
				data['is_required'] = false;
			}
			if( typeof data['col-md'] == 'undefined' )
			{
				data['col-md'] = 12;
			}
			if( typeof data['visibility'] == 'undefined' )
			{
				data['visibility'] = 'visible';
			}

			var type = el.data('type');

			$("#formbuilder_options > [data-for]").hide();
			$("#formbuilder_options > [data-for]").each(function()
			{
				var forList = ',' + $(this).data('for') + ',';

				if( forList.indexOf( ',' + type + ',' ) > -1 )
				{
					$(this).removeClass('hidden').show();
				}
			});
			$( '[data-multilang=true]' ).removeData( 'multilang-fk' );
			$("#choices_area").empty();

			for( var id in data )
			{
				if( id == 'choices' )
				{
					for( var choiceI in data[id] )
					{
						var choice_id	= data[id][choiceI][0],
							choice_val	= data[id][choiceI][1];

						$("#formbuilder_options_add_new_choice").click();

						$("#choices_area > .select_options:eq(-1)").data( 'id', choice_id );
						$("#choices_area > .select_options:eq(-1) input[type='text']").val( choice_val );
						$("#choices_area > .select_options:eq(-1) input[type='text']").data( 'multilang-fk', choice_id );
					}
				}
				else
				{
					var optEl = $("#formbuilder_options #formbuilder_options_" + id);

					if( optEl.attr('type') == 'checkbox' )
					{
						optEl.prop( 'checked', data[ id ] == 0 ? false : true ).trigger( 'change' );
					}
					else
					{
						optEl.data( 'multilang-fk', $( el ).data( 'id' ) );
						optEl.val( data[id] );
					}
				}
			}
		}

		function uniqueId()
		{
			return 'U' + Date.now().toString(36) + Math.random().toString(36).substr(2);
		}

		$( '.formbuilder_element' ).draggable({
			helper: function( event )
			{
				var el		= $(event.target).closest('.formbuilder_element'),
					title	= el.text().trim(),
					type	= el.data('type');

				return '<div class="helper_box" data-type="' + type + '">' + title + '</div>';
			},
			cursor: "move",
			connectToSortable: "#formbuilder_area",
			revert: "invalid"
		});

		$( '#formbuilder_area' ).sortable({
			revert: true,
			update: function( event, ui )
			{
				var item = ui.item;
				if( item.hasClass('helper_box') )
				{
					var type = item.data('type');

					item.removeClass('helper_box').addClass('form_element form-group col-md-12');

					var tpl = formInputTpls[ type ];

					tpl = tpl.replaceAll( '%label%', item.text() );
					tpl = tpl.replaceAll( '%helptext%', '' );
					tpl = tpl.replaceAll( '%placeholder%', '' );
					item.html( tpl + '<img class="remove-element-btn" src="'+currentModuleAssetsURL+'backend/icons/remove.svg">' );
					item.removeAttr('style');
					item.data('id', uniqueId());
					item.click();

					//todo: deprecated, after removing niceScroll. Removed at 3.4.2
					// $(".fs_portlet_content").getNiceScroll().resize();
				}
			}
		});

		$(document).on('click', '#formbuilder_area .form_element', function()
		{
			if( $(this).hasClass('active_element') )
				return;

			formbuilderSaveChanges( $("#formbuilder_area > .active_element") );

			$("#formbuilder_area > .active_element").removeClass('active_element').removeClass('dashed-border');

			$(this).addClass('active_element').addClass('dashed-border');

			formbuilderConstructElement( $(this) );
		}).on('click', '#formbuilder_area .remove-element-btn', function()
		{
			$(this).closest('.form_element').slideUp(200, function()
			{
				$(this).remove();
				//todo: deprecated, after removing niceScroll. Removed at 3.4.2
				// $(".fs_portlet_content").getNiceScroll().resize();
			});
		}).on('keyup', '#formbuilder_options_label', function ()
		{
			var label = $(this).val();

			$("#formbuilder_area > .active_element [data-label='true']").text( label );
		}).on('keyup', '#formbuilder_options_placeholder', function ()
		{
			var placeholder = $(this).val();

			var activeElement = $("#formbuilder_area > .active_element");
			activeElement.find('input, textarea').attr( 'placeholder', placeholder );
		}).on('keyup', '#formbuilder_options_help_text', function ()
		{
			var help_text = $(this).val();

			$("#formbuilder_area > .active_element .help-text").text( help_text );
		}).on('change', '#formbuilder_options_is_required', function ()
		{
			$("#formbuilder_area > .active_element [data-label='true']").attr('data-required', $(this).is(':checked'));
		}).on('change', '#formbuilder_options_col-md', function ()
		{
			let colMd = $(this).val();
			let activeEl = $("#formbuilder_area > .active_element");
			let currentClass = activeEl.attr('class').match('col-md-([0-9]+)');

			activeEl.removeClass('col-md-'+currentClass[1]).addClass('col-md-'+colMd);

		}).on('click', '#formbuilder_options_add_new_choice', function ()
		{
			$("#choices_area").append('<div class="row mb-1 select_options"><div class="col-sm-10"><input type="text" class="form-control" data-multilang="true" maxlength="255"></div><div class="col-sm-2 remove_choice"><img src="'+currentModuleAssetsURL+'/backend/icons/remove.svg"></div></div>');

			booknetic.initMultilangInput( $( '#choices_area .select_options' ).find( 'input' ).last(), 'form_input_choices', 'title' );

			$("#choices_area > .select_options:eq(-1)").data('id', uniqueId()).hide().slideDown(200, function ()
			{
				//todo: deprecated, after removing niceScroll. Removed at 3.4.2
				// $(".fs_portlet_content").getNiceScroll().resize();
			});
		}).on('click', '#choices_area .remove_choice', function ()
		{
			$(this).closest('.select_options').slideUp(200, function()
			{
				$(this).remove();
				//todo: deprecated, after removing niceScroll. Removed at 3.4.2
				// $(".fs_portlet_content").getNiceScroll().resize();
			});
		}).on('click', '#save-form-btn', function(e, data)
		{
			if( $("#formbuilder_area > .active_element").length )
			{
				formbuilderSaveChanges( $("#formbuilder_area > .active_element") );
			}

			var elements	= [],
				name		= $("#input_form_name").val(),
				services	= $("#input_form_services").val() == null ? '' : $("#input_form_services").val().join(','),
				conditions  = $('#set_form_conditions_btn').data('conditions');

			$("#formbuilder_area > .form_element").each(function()
			{
				var data = $(this).data('options');
				data = !data ? {} : data;

				data['id'] = $(this).data('id');
				data['type'] = $(this).data('type');

				data[ 'translations' ] = $( this ).data( 'translations' );

				elements.push( data );
			});

			booknetic.ajax('save_form', {
				id: currentFormID,
				name: name,
				services: services,
				conditions: JSON.stringify(conditions),
				inputs: JSON.stringify( elements )
			}, function( result )
			{
				booknetic.toast(booknetic.__('changes_saved'), 'success');
				location.href = 'admin.php?page=' + BACKEND_SLUG + '&module=customforms&action=edit&form_id=' + result.id;
			});
		}).on('click', '#set_form_conditions_btn', function ()
		{
			if( $("#formbuilder_area > .active_element").length )
			{
				formbuilderSaveChanges( $("#formbuilder_area > .active_element") );
			}
		});


		$("#input_form_services").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select_services'),
			allowClear: true
		});
		//todo: deprecated, after removing niceScroll. Removed at 3.4.2
		// $(".fs_portlet_content").niceScroll({cursorcolor: "#e4ebf4"});
		$(".fs_portlet_content").handleScrollBooknetic();

		booknetic.addFilter( 'modal_sizes', function ( modalSizes )
		{
			modalSizes['customforms.set_conditions'] = '800px';

			return modalSizes;
		} );

	});

})(jQuery);