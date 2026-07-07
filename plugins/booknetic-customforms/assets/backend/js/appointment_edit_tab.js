(function ($)
{
    "use strict";

    $(document).ready(function()
    {

        var stagedCustomFiles = {};

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

        $("#tab_custom_fields_edit").on('change', '.form-control[type="file"]', function (e)
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

        $("#tab_custom_fields_edit").on('click', '.remove_staged_file_btn', function() {
            var index = $(this).data('index');
            var inputEl = $(this).closest('.form-group').find('.form-control[type="file"]');
            var inputId = inputEl.data('input-id');
            
            if (stagedCustomFiles[inputId]) {
                stagedCustomFiles[inputId].splice(index, 1);
                renderStagedFiles(inputEl);
            }
        });

        function loadCustomForm()
        {
            stagedCustomFiles = {};
            booknetic.ajax('Customforms.appointment_load_custom_fields', {
                appointment_id: $('#add_new_JS').data('appointment-id'),
                service_id: $('#input_service').val(),
            }, function ( result )
            {
                $("#tab_custom_fields_edit > #custom_fields").html( booknetic.htmlspecialchars_decode( result['html'] ) );
            });
        }


        $(".fs-modal").on('change', '#input_service', loadCustomForm);

        loadCustomForm();

        booknetic.addFilter( 'appointments.save_edited_appointment.cart', function ( cart ,data )
        {
            var customFields      = {};

            $("#tab_custom_fields_edit [data-input-id][type!='checkbox'][type!='radio']").each(function()
            {
                var inputId		= $(this).data('input-id'),
                    inputVal	= $(this).val();

                if (inputVal === null) inputVal = '';

                if( $(this).attr('type') === 'file' )
                {
                    let isMultiple = $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple';
                    if (isMultiple)
                    {
                        // find all remaining uploaded files
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
                        
                        customFields[ inputId ] = {
                            multiple: true,
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
                            data.append('custom_files[' + uniqueId + ']' , $(this)[0].files[0] !== undefined ? $(this)[0].files[0] : '-1' );
                            customFields[ inputId ] = uniqueId ;
                        }
                    }
                }
                else
                {
                    customFields[inputId] = inputVal;
                }

            });

            $("#tab_custom_fields_edit [data-input-id][type='checkbox']").each(function ()
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

            $("#tab_custom_fields_edit [data-input-id][type='radio']").each(function ()
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