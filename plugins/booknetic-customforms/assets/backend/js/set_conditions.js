(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        let row_condition_tpl = $(".group_conditions > .row_condition")[0].outerHTML;
        $(".group_conditions > .row_condition").remove();
        let row_do_tpl = $(".group_do > .row_do")[0].outerHTML;
        $(".group_do > .row_do").remove();
        let condition_tab_tpl = $(".condition_tabs_content > div")[0].outerHTML;
        let tab_num = 1;

        function uniqueId()
        {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        }

        function customFieldsList( return_type, addLabelAndLinkFields )
        {
            let list = [];
            $('#formbuilder_area > .form_element').each(function ()
            {
                if( addLabelAndLinkFields === false && ( $(this).data('type') == 'label' || $(this).data('type') == 'link' ) )
                    return;

                let element_id = $(this).data('id');
                let data = $(this).data('options');

                data = !data ? {} : data;
                data['element_id'] = element_id;
                data['type'] = $(this).data('type');

                list.push( data );
            });

            if( return_type == 'options' )
            {
                let options = '<option disabled selected></option>';

                for( let i in list )
                {
                    options += '<option value="' + list[i].element_id + '" data-type="' + list[i].type + '" data-choices="' + booknetic.htmlspecialchars( JSON.stringify(list[i].choices) ) + '">' + list[i].label + '</option>';
                }

                return options;
            }

            return list;
        }

        $(document).find('.fs-modal').on('click', '.new_condition_btn', function()
        {
            let current_tab_condtions_group = $(this).closest('div').children(".group_conditions");
            current_tab_condtions_group.append( row_condition_tpl );
            let new_condition = current_tab_condtions_group.children(":eq(-1)");

            new_condition.removeClass('hidden').hide().slideDown(200);

            let options = $('#default_field_options').html();
            options += customFieldsList('options', false);

            new_condition.find('.field_select_when').html( options );

            new_condition.find("select").select2({
                theme: 'bootstrap',
                placeholder: booknetic.__('select')
            });

            new_condition.find('.field_data_select_when').next('.select2').hide();
            new_condition.find('.value_select_when').next('.select2').hide();

        }).on('click', '.new_do_btn', function()
        {
            let current_tab_do_group = $(this).closest('div').children(".group_do");
            current_tab_do_group.append( row_do_tpl );
            let new_do = current_tab_do_group.children(":eq(-1)");

            new_do.removeClass('hidden').hide().slideDown(200);

            new_do.find('.field_select_do').html( customFieldsList('options') );

            new_do.find("select").select2({
                theme: 'bootstrap',
                placeholder: booknetic.__('select')
            });

        }).on('click', '.delete_condition_row', function ()
        {
            $(this).closest('.row_condition').slideUp(200, function ()
            {
                $(this).remove();
            });
        }).on('click', '.delete_do_row', function ()
        {
            $(this).closest('.row_do').slideUp(200, function ()
            {
                $(this).remove();
            });
        }).on('change', '.field_select_when', function ()
        {
            let selectedVal = $(this).val();
            let selectedOption = $(this).children(':selected');
            let row = $(this).closest('.form-row');
            let fieldType;

            row.find('.field_data_select_when').next('.select2').fadeIn(200);

            if( selectedVal == 'service_id' || selectedVal == 'staff_id' || selectedVal == 'location_id' )
            {
                fieldType = 'standart';
            }
            else
            {
                fieldType = selectedOption.data('type');
            }

            if( fieldType == 'select' || fieldType == 'radio' || fieldType == 'standart' )
            {
                row.find('.field_data_select_when > option[value="length"]').attr('disabled', 'disabled');
                if( row.find('.field_data_select_when > option[value="length"]').is(':selected') )
                {
                    row.find('.field_data_select_when').val('value');
                }
            }
            else
            {
                row.find('.field_data_select_when > option[value="length"]').removeAttr('disabled');
            }

            if( fieldType == 'file' )
            {
                row.find('.field_data_select_when > option[value="file_size"]').removeAttr('disabled');
            }
            else
            {
                row.find('.field_data_select_when > option[value="file_size"]').attr('disabled', 'disabled');
                if( row.find('.field_data_select_when > option[value="file_size"]').is(':selected') )
                {
                    row.find('.field_data_select_when').val('value');
                }
            }

            row.find('.field_data_select_when').trigger('change', 'stop');

            if( fieldType == 'select' || fieldType == 'radio' || ( fieldType == 'checkbox' && row.find('.field_data_select_when').val() == 'value' ) || fieldType == 'standart' )
            {
                row.find('.value_input_when').hide();
                row.find('.value_select_when').next('.select2').show();

                let options = '';

                if( fieldType == 'standart' )
                {
                    options += '<option></option>';
                    options += $('#' + selectedVal + '_options').html();
                }
                else
                {
                    let choices = selectedOption.data('choices');
                    for( let i in choices )
                    {
                        options += '<option value="' + choices[i][0] + '">' + booknetic.htmlspecialchars(choices[i][1]) + '</option>';
                    }
                }

                row.find('.value_select_when').html( options );

                if( fieldType == 'checkbox' )
                {
                    row.find('.value_select_when').attr('multiple', 'multiple');
                }
                else
                {
                    row.find('.value_select_when').removeAttr('multiple');
                }

                row.find('.value_select_when').select2("destroy");
                row.find('.value_select_when').select2({
                    theme: 'bootstrap',
                    placeholder: booknetic.__('select')
                });
            }
            else
            {
                row.find('.value_select_when').next('.select2').hide();
                row.find('.value_input_when').show();
            }

        }).on('change', '.field_data_select_when', function (e, data)
        {
            if( data !== 'stop' )
                $(this).closest('.form-row').find('.field_select_when').trigger('change');
        }).on('change', '.operator_select_when', function ()
        {
            if( $(this).val() == 'is_empty' || $(this).val() == 'is_not_empty' )
            {
                $(this).closest('.row_condition').find('.value_input_when').closest('.form-group').fadeOut(200);
            }
            else
            {
                $(this).closest('.row_condition').find('.value_input_when').closest('.form-group').fadeIn(200);
            }
        }).on('change', '.action_select_do', function ()
        {
            let row = $(this).closest('.row_do');
            let action = $(this).val();

            if( action == 'set_value' || action == 'throw_error' )
            {
                row.find('.value_input_do').closest('.form-group').removeClass('hidden').hide().fadeIn(200);
            }
            else
            {
                row.find('.value_input_do').closest('.form-group').fadeOut(200);
            }

            if( action == 'throw_error' )
            {
                row.find('.field_select_do').closest('.form-group').fadeOut(200)
                row.find('.error_message_label').show();
                row.find('.value_label').hide();

                row.find('.value_select_do').next('.select2').hide();
                row.find('.value_input_do').show();
            }
            else
            {
                row.find('.field_select_do').closest('.form-group').fadeIn(200);
                row.find('.field_select_do').trigger('change');
                row.find('.error_message_label').hide();
                row.find('.value_label').show();
            }

            if( ! row.find('.field_select_do').val() && action != 'throw_error' )
            {
                row.find('.value_input_do').hide();
            }

        }).on('change', '.field_select_do', function ()
        {
            let row = $(this).closest('.row_do');
            let selectedOption = $(this).children(':selected');
            let fieldType = selectedOption.data('type');

            if( fieldType == 'select' || fieldType == 'checkbox' || fieldType == 'radio' )
            {
                row.find('.value_select_do').next('.select2').show();
                row.find('.value_input_do').hide();

                let choices = selectedOption.data('choices');

                let options = '';
                for( let i in choices )
                {
                    options += '<option value="' + choices[i][0] + '">' + booknetic.htmlspecialchars(choices[i][1]) + '</option>';
                }

                row.find('.value_select_do').html( options );

                if( fieldType == 'checkbox' )
                {
                    row.find('.value_select_do').attr('multiple', 'multiple');
                }
                else
                {
                    row.find('.value_select_do').removeAttr('multiple');
                }

                row.find('.value_select_do').select2("destroy");
                row.find('.value_select_do').select2({
                    theme: 'bootstrap',
                    placeholder: booknetic.__('select')
                });
            }
            else
            {
                row.find('.value_select_do').next('.select2').hide();
                row.find('.value_input_do').show();
            }
        }).on('click', '.add_new_condition_tab', function ()
        {
            tab_num++;
            $(this).closest('li').before('<li class="nav-item"><a class="nav-link" data-tab="tab_'+tab_num+'" href="#">CONDITION ' + tab_num + ' <i class="fa fa-times delete_condition_tab"></i></a></li>');

            $('.condition_tabs_content').append( condition_tab_tpl );
            $('.condition_tabs_content > div:eq(-1)').attr('data-tab-content', 'conditions_tab_' + tab_num);

            $(this).closest('li').prev('li').children('a').click();

        }).on('click', '.delete_condition_tab', function ()
        {
            let tab_num = $(this).closest('a').attr('data-tab');
            let current_li = $(this).closest('li');
            let need_to_click;

            if( current_li.prev('li').length )
                need_to_click = current_li.prev('li').children('a');
            else
                need_to_click = current_li.next('li').children();

            current_li.remove();
            $('.condition_tabs_content > .tab-pane[data-tab-content="conditions_'+tab_num+'"]').remove();

            need_to_click.click();

        }).on('click', '#save_conditions_btn', function ()
        {
            let conditions = [];

            $('.condition_tabs_content > [data-tab-content]').each(function ()
            {
                let tab_condition = {
                    conditions: [],
                    events: []
                };

                $(this).find('.group_conditions > .row_condition').each(function ()
                {
                    let whenSelector = $( this ).find( '.field_select_when' );
                    let whenValue    = whenSelector.val();
                    let fieldType;
                    let value;

                    if( whenValue !== 'service_id' && whenValue !== 'staff_id' && whenValue !== 'location_id' )
                    {
                        fieldType = whenSelector.find( ':selected' ).data('type');
                        value     = $(this).find('.value_input_when').val();
                    }
                    else
                    {
                        fieldType = 'standart';
                        value     = $(this).find('.value_select_when').val()
                    }

                    if( fieldType === 'select' || fieldType === 'radio' || ( fieldType === 'checkbox' && $(this).find('.field_data_select_when').val() === 'value' ) )
                    {
                        value = $(this).find('.value_select_when').val();
                    }

                    tab_condition.conditions.push({
                        field: $(this).find('.field_select_when').val(),
                        field_data: $(this).find('.field_data_select_when').val(),
                        operator: $(this).find('.operator_select_when').val(),
                        value: value,
                        condition: $(this).find('.condition_and_or_select').val()
                    });
                });

                $(this).find('.group_do > .row_do').each(function ()
                {
                    let fieldType = $(this).find('.field_select_do > :selected').data('type');
                    let eAction = $(this).find('.action_select_do').val();
                    let eFieldId = $(this).find('.field_select_do').val();

                    let value = $(this).find('.value_input_do').val();
                    if( eAction !== 'throw_error' && ( fieldType == 'select' || fieldType == 'checkbox' || fieldType == 'radio' ) )
                    {
                        value = $(this).find('.value_select_do').val();
                    }

                    tab_condition.events.push({
                        action: eAction,
                        field_id: eFieldId,
                        value: value
                    });
                });

                if ( tab_condition.conditions.length === 0 || tab_condition.events.length === 0 )
                    return;

                conditions.push( tab_condition );
            });

            $('#set_form_conditions_btn').data('conditions', conditions);
            $('#close_conditions_btn').click();
            $('#conditions-count').text( conditions.length );
            $('#save-form-btn').trigger('click');
        });

        let currentConditions = $('#set_form_conditions_btn').data('conditions');
        for( let i in currentConditions )
        {
            if( i > 0 )
                $('.add_new_condition_tab').click();

            let currentTab = $('.condition_tabs_content > [data-tab-content]:eq(-1)');

            if( typeof currentConditions[i].conditions == 'undefined' || currentConditions[i].events == 'undefined' )
                continue;

            let conditions = currentConditions[i].conditions;
            let events = currentConditions[i].events;

            for( let c in conditions )
            {
                currentTab.find('.new_condition_btn').click();
                let conditionRow = currentTab.find('.row_condition:eq(-1)');
                conditionRow.find('.field_select_when').val( conditions[c].field ).trigger('change');
                conditionRow.find('.field_data_select_when').val( conditions[c].field_data ).trigger('change');
                conditionRow.find('.operator_select_when').val( conditions[c].operator ).trigger('change');
                conditionRow.find('.value_input_when').val( conditions[c].value );
                conditionRow.find('.value_select_when').val( conditions[c].value ).trigger('change');
                conditionRow.find('.condition_and_or_select').val( conditions[c].condition ).trigger('change');
            }

            for( let e in events )
            {
                currentTab.find('.new_do_btn').click();
                let doRow = currentTab.find('.row_do:eq(-1)');
                doRow.find('.field_select_do').val( events[e].field_id ).trigger('change');
                doRow.find('.action_select_do').val( events[e].action ).trigger('change');
                doRow.find('.value_input_do').val( events[e].value );
                doRow.find('.value_select_do').val( events[e].value ).trigger('change');
            }
        }
        $('.condition_tabs > li:eq(0) > a').click();

    });

})(jQuery);