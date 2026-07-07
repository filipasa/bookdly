(function ($)
{
    "use strict";

    $(document).ready(function()
    {

        function loadCustomForm()
        {
            booknetic.ajax('Customforms.appointment_load_custom_fields', {
                service_id: $('#input_service').val(),
            }, function ( result )
            {
                $("#tab_custom_fields_add > #custom_fields").html( booknetic.htmlspecialchars_decode( result['html'] ) );
            });
        }


        $(".fs-modal").on('change', '#input_service', loadCustomForm);

        loadCustomForm();


        booknetic.addFilter( 'appointments.create_appointment.cart', function ( cart ,data)
        {
            var customFields      = {};

            $("#tab_custom_fields_add [data-input-id][type!='checkbox'][type!='radio']").each(function()
            {
                var inputId		= $(this).data('input-id'),
                    inputVal	= $(this).val();

                if (inputVal === null) inputVal = '';

                if( $(this).attr('type') === 'file' )
                {
                    let isMultiple = $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple';
                    if (isMultiple)
                    {
                        let files = $(this)[0].files;
                        if (files.length > 0)
                        {
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
                            customFields[ inputId ] = {
                                multiple: true,
                                new_files: fileList
                            };
                        }
                    }
                    else
                    {
                        let hasNotRemoveButton = $(this).parent().find('.remove_custom_file_btn').length === 0;
                        if (hasNotRemoveButton)
                        {
                            var uniqueId = Math.random().toString(36).substring(2, 9);
                            data.append('custom_files[' + uniqueId + ']' , $(this)[0].files[0] !== undefined ? $(this)[0].files[0] : '' );
                            customFields[ inputId ] = uniqueId ;
                        }
                    }
                }
                else
                {
                    customFields[inputId] = inputVal;
                }

            });

            $("#tab_custom_fields_add [data-input-id][type='checkbox']").each(function ()
            {
                var inputId		= $(this).data('input-id'),
                    inputVal	= $(this).val(),
                    checked 	= $(this).is(':checked');

                if (checked)
                {
                    if (typeof customFields[inputId] == 'undefined')
                        customFields[inputId] = inputVal;
                    else
                        customFields[inputId] += "," + inputVal;
                }
            });

            $("#tab_custom_fields_add [data-input-id][type='radio']").each(function ()
            {
                var inputId		= $(this).data('input-id'),
                    inputVal	= $(this).val(),
                    checked 	= $(this).is(':checked');

                if (checked)
                {
                    customFields[inputId] = inputVal;
                }
            });

            cart['custom_fields'] = customFields;

            return cart;

        }, 'addon-custom-forms');

    });

})(jQuery);