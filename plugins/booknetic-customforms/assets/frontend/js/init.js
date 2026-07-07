(function($)
{

    "use strict";

    $(document).ready(function()
    {
        console.log("Custom Forms init.js Version 2.1.3 loaded!");

        bookneticHooks.addFilter('ajax_confirm' , function (params,booknetic)
        {
           if( booknetic.customFiles !== undefined )
           {
               for (let i = 0; i < booknetic.customFiles.length ; i++) {
                   params.append('custom_files[' + booknetic.customFiles[i].id + ']' , booknetic.customFiles[i].file);
               }
           }
           return params;
        });

        bookneticHooks.addAction( 'loaded_step_information', function( booknetic )
        {
            console.log("Custom Forms loaded_step_information action triggered!");
            var booking_panel_js = booknetic.panel_js;

            booking_panel_js.find(".booknetic_custom_form .custom-forms-date-input").each(function()
            {
                $(this).attr('type', 'text').data('isdatepicker', true);
    
                booknetic.initDatepicker( $(this) );
            });

            booking_panel_js.find(".booknetic_custom_form .custom-input-select2").select2({
                theme: 'bootstrap',
                allowClear: true
            });

            $(document).on('click', '.booknetic_custom_form .remove_custom_file_btn', function()
            {
                var placeholder = $(this).data('placeholder');
    
                $(this).parent().text( placeholder );
            });

            function renderStagedFiles(inputEl) {
                var inputId = inputEl.data('input-id');
                var container = inputEl.parent().find('.staged-files-container');
                container.empty();
                
                if (typeof booknetic.stagedCustomFiles === 'undefined') {
                    booknetic.stagedCustomFiles = {};
                }
                var files = booknetic.stagedCustomFiles[inputId] || [];
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

            $(document).on('change', '.booknetic_custom_form input[type="file"]', function (e)
            {
                console.log("File input change triggered! Multiple:", $(this).attr('multiple'), "Type:", $(this).data('type'), "Files count:", e.target.files.length);
                var isMultiple = $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple';
                if (isMultiple) {
                    var inputId = $(this).data('input-id');
                    var files = e.target.files;
                    if (typeof booknetic.stagedCustomFiles === 'undefined') {
                        booknetic.stagedCustomFiles = {};
                    }
                    if (typeof booknetic.stagedCustomFiles[inputId] === 'undefined') {
                        booknetic.stagedCustomFiles[inputId] = [];
                    }
                    for (var i = 0; i < files.length; i++) {
                        var exists = false;
                        for (var j = 0; j < booknetic.stagedCustomFiles[inputId].length; j++) {
                            if (booknetic.stagedCustomFiles[inputId][j].name === files[i].name && booknetic.stagedCustomFiles[inputId][j].size === files[i].size) {
                                exists = true;
                                break;
                            }
                        }
                        if (!exists) {
                            booknetic.stagedCustomFiles[inputId].push(files[i]);
                        }
                    }
                    renderStagedFiles($(this));
                    $(this).val(''); // clear so change fires again
                } else {
                    var fileName = e.target.files[0].name;
                    $(this).next().text( fileName );
                }
            });

            $(document).on('change', '[data-step-id=\'information\'] .booknetic_custom_form input, [data-step-id=\'information\'] .booknetic_custom_form select, [data-step-id=\'information\']  .booknetic_custom_form textarea', function ()
            {
                if( $(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio' )
                {
                    $(this).parent().parent().find('.booknetic_input_error').removeClass('booknetic_input_error');
                }
                else if( $(this).attr('type') == 'file' )
                {
                    $(this).next().removeClass('booknetic_input_error');
                }
                else if( $(this).is('select') )
                {
                    $(this).next().find('.booknetic_input_error').removeClass('booknetic_input_error');
                }
                else
                {
                    $(this).removeClass('booknetic_input_error');
                }
            });

            $(document).on('click', '.remove_staged_file_btn', function() {
                var index = $(this).data('index');
                var inputEl = $(this).closest('.form-group').find('input[type="file"]');
                var inputId = inputEl.data('input-id');
                
                if (booknetic.stagedCustomFiles && booknetic.stagedCustomFiles[inputId]) {
                    booknetic.stagedCustomFiles[inputId].splice(index, 1);
                    renderStagedFiles(inputEl);
                }
            });

            bookneticInitFormConditions( booknetic, booking_panel_js, false );
        });

        bookneticHooks.addAction('step_end_information' , function (booknetic){
            var customFields = {};
            var booking_panel_js  = booknetic.panel_js;
            var index = booknetic.cartCurrentIndex;
            var cart = booknetic.cartArr;
            var params = cart[index];
            var form = booking_panel_js.find(".booknetic_appointment_container_body [data-step-id=\"information\"]");

            form.find(".booknetic_custom_form [data-input-id][type!='checkbox'][type!='radio'], .booknetic_custom_form [data-input-id][type='checkbox']:checked, .booknetic_custom_form [data-input-id][type='radio']:checked").each(function()
            {
                var inputId		= $(this).data('input-id'),
                    inputVal	= $(this).val();

                if( !inputVal )
                {
                    inputVal = '';
                }

                if( inputVal != '' && $(this).data('isdatepicker') )
                {
                    inputVal = inputVal.replace(/\s+/g, '')
                    inputVal = booknetic.convertDate( inputVal, booknetic.datePickerFormat(), 'Y-m-d' );
                }

                if( $(this).attr('type') == 'file' )
                {
                    if ( $(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple' )
                    {
                        let files = (booknetic.stagedCustomFiles && booknetic.stagedCustomFiles[inputId]) ? booknetic.stagedCustomFiles[inputId] : [];
                        if (files.length > 0)
                        {
                            let fileList = [];
                            for (let i = 0; i < files.length; i++)
                            {
                                var uniqueId = Math.random().toString(36).substring(2, 9);
                                if( booknetic.customFiles === undefined)
                                {
                                    booknetic.customFiles = [];
                                }
                                booknetic.customFiles.push({
                                    id : uniqueId,
                                    file : files[i]
                                });
                                fileList.push({
                                    id: uniqueId,
                                    name: files[i].name
                                });
                            }
                            customFields[ inputId ] = {
                                multiple: true,
                                files: fileList
                            };
                        }
                    }
                    else if( $(this)[0].files[0] )
                    {
                        var uniqueId = Math.random().toString(36).substring(2, 9);
                        if( booknetic.customFiles === undefined)
                        {
                            booknetic.customFiles = [];
                        }
                        booknetic.customFiles.push({
                            id : uniqueId,
                            file : $(this)[0].files[0]
                        })
                        customFields[ inputId ] = {
                            id: uniqueId,
                            name: $(this)[0].files[0].name
                        } ;
                    }
                }
                else
                {
                    if( typeof customFields[ inputId ] == 'undefined' )
                    {
                        customFields[ inputId ] = inputVal;
                    }
                    else
                    {
                        customFields[ inputId ] += ',' + inputVal;
                    }
                }
            });

            params['custom_fields'] = customFields;
        });

        bookneticHooks.addFilter('step_validation_information' , function ( params , booknetic ) {
            console.log("Step validation info triggered!");
            let status = params.status;
            let errorMsg = params.errorMsg;
            let hasError = false;

            booknetic.panel_js.find(".booknetic_appointment_container_body [data-step-id='information'] > .booknetic_custom_form label").each(function()
            {
                let el = $(this).next();
                let required = $(this).is('[data-required="true"]');
                let isMinLength = el.is('[minlength]');
                let isMaxLength = el.is('[maxlength]');
                let minLength = el.attr('minlength');
                let maxLength = el.attr('maxlength');
                let dataType  = el.attr('data-type');
                let visible   = el.closest( '.form-group' ).css( 'display' ) !== 'none';

                if( el.is('div.iti') )
                {
                    el = el.find('input');
                }

                if( el.is('input[type=text], input[type=file], input[type=number], input[type=date], input[type=time], textarea, select') )
                {
                    let value        = el.val();
                    let valueIsEmpty = ! value || value.trim() == '';

                    if ( el.attr('multiple') !== undefined || el.data('type') === 'file_multiple' )
                    {
                        let inputId = el.data('input-id');
                        let uploadedFilesCount = el.parent().find('.uploaded-files-container .custom-file-item').length;
                        let stagedFilesCount = (booknetic.stagedCustomFiles && booknetic.stagedCustomFiles[inputId]) ? booknetic.stagedCustomFiles[inputId].length : 0;
                        valueIsEmpty = (uploadedFilesCount + stagedFilesCount) === 0;
                        console.log("File field validation:", inputId, "uploaded:", uploadedFilesCount, "staged:", stagedFilesCount, "valueIsEmpty:", valueIsEmpty);
                    }

                    console.log("Field validation check:", el.attr('name') || el.data('input-id'), "Type:", el.attr('type') || el.prop('tagName'), "Required:", required, "ValueIsEmpty:", valueIsEmpty, "Visible:", visible);

                    if ( ! visible ) //do not validate hidden fields
                        return;

                    if ( valueIsEmpty && ! required ) //do not validate empty, optional fields
                        return;

                    if( dataType === 'email' )
                    {
                        if ( ! booknetic.validateEmail( value ) )
                        {
                            el.addClass('booknetic_input_error');
                            hasError = booknetic.__('email_is_not_valid');
                        }
                    }
                    else if( dataType === 'phone' )
                    {
                        if ( ! booknetic.validatePhone( value ) )
                        {
                            el.addClass('booknetic_input_error');
                            hasError = booknetic.__('phone_is_not_valid');
                        }
                    }
                    else if ( dataType === 'date' )
                    {
                        if ( ! booknetic.validateDate ( value ) )
                        {
                            el.addClass('booknetic_input_error');
                            hasError = booknetic.__('Select date');
                        }
                    }

                    if( required && valueIsEmpty )
                    {
                        if( el.is('select') )
                        {
                            el.next().find('.select2-selection').addClass('booknetic_input_error');
                        }
                        else if( el.is('input[type="file"]') )
                        {
                            el.next().addClass('booknetic_input_error');
                        }
                        else
                        {
                            el.addClass('booknetic_input_error');
                        }

                        hasError = booknetic.__('fill_all_required');
                    }

                    if( required && isMinLength && parseInt(minLength) && value.length < minLength ){
                        el.addClass('booknetic_input_error');
                        hasError = booknetic.__('min_length').replace(/%s/, el.prev().text()).replace(/%d/, minLength);
                    }else if(isMaxLength && parseInt(maxLength) && value.toString().length > maxLength){
                        el.addClass('booknetic_input_error');
                        hasError = booknetic.__('max_length').replace(/%s/, el.prev().text()).replace(/%d/, maxLength);
                    }
                }
                else if( el.is('div') ) // checkboxes or radios
                {
                    // let type = el.find('input').first().attr('type');
                    let condition = el.find('input:checked').length === 0;
                    // if(type === 'radio')
                    // {
                    //     condition = el.find('input:checked').length === 0;
                    // }
                    // else if(type === 'checkbox')
                    // {
                    //     condition = el.find('input:checked').length !== el.find('input').length;
                    // }

                    if( visible && required && condition )
                    {
                        el.find('input').addClass('booknetic_input_error');
                        hasError = booknetic.__('fill_all_required');
                    }
                }

            });

            if( hasError )
            {
                status      = false;
                errorMsg    = hasError;
            }

            return {
                status: status,
                errorMsg: errorMsg
            };

        });

    });
    

})(jQuery);