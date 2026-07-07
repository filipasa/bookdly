booknetic.addFilter( 'conditional_prices_fields', function( conditions )
{
    const choices = [ 'radio', 'checkbox', 'select' ];
    /* global customConditionFields */
    if ( customConditionFields !== null )
    {
        customConditionFields.map( ( obj ) =>
        {
            conditions[ 'custom_field_' + obj.id ] = function( el, prev )
            {
                if ( choices.includes( obj.type ) )
                {
                    this.enableOperators( el, [ 'equals', 'is_empty', 'is_not_empty' ] );

                    if ( prev !== 'custom_field_' + obj.id && typeof( prev ) === 'undefined' )
                    {
                        const conditionValue = el.closest('.condition').find('.value_input_when')

                        this.destroySelect2( conditionValue );

                        el.closest('.condition').find('.operator_select_when').val('').trigger('change',true).prop('disabled', false)

                        conditionValue.replaceWith(`<select multiple class="form-control value_input_when"><option></option></select>`)
                    }

                    booknetic.select2Ajax( el.closest('.condition').find('.value_input_when'), 'customforms.get_input_choices', { 'form_id': el.closest('.condition').find('.field_select_when').val().substring(13) } )
                }
                else
                {
                    this.enableOperators( el, [ 'is_empty', 'is_not_empty' ] );

                    if ( prev !== 'custom_field_' + obj.id && typeof( prev ) === 'undefined' )
                    {
                        const conditionValue = el.closest('.condition').find('.value_input_when')

                        this.destroySelect2( conditionValue );

                        el.closest('.condition').find('.operator_select_when').val('').trigger('change',true).prop('disabled', false)

                        conditionValue.replaceWith(`<input class="form-control value_input_when">`)
                    }
                }

                this.handleBetweenOperator( el, prev );

                if ( [ 'is_empty', 'is_not_empty' ].includes( el.closest('.condition').find('.operator_select_when').val() ) )
                {
                    el.closest('.condition').find('.value_input_when').closest('.form-group').fadeOut();
                    el.closest('.condition').find('.value_input_when').val( '' ).trigger('change');
                }
                else
                {
                    el.closest('.condition').find('.value_input_when').closest('.form-group').fadeIn();
                }
            }
        });
    }

    return conditions;
});
