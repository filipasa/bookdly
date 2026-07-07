<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Customforms\Helpers\FormElements;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\Customforms\bkntc__;

$customFieldsAreaId = 'custom_fields_area_' . rand(100,9999);

if (empty($parameters['forms'])):
    echo '<div class="text-secondary font-size-14 text-center">' . bkntc__( 'No custom fields found' ) . '</div>';
else:
?>
    <div id="<?php echo $customFieldsAreaId?>">
        <?php foreach ($parameters['forms'] AS $form): ?>
            <?php
            $conditions = json_decode($form->conditions, true);
            $conditionsAttr = '';

            if( ! empty( $conditions ) && is_array( $conditions ) )
            {
                $conditionsAttr = ' data-conditions="' . htmlspecialchars( json_encode( $conditions ) ) . '"';
            }
            ?>
            <div class="customer-fields-area dashed-border booknetic_custom_form" data-form-id="<?php echo (int)$form->id?>"<?php echo $conditionsAttr;?>>
                <div class="d-flex justify-content-center user_visit_card">
                    <?php echo htmlspecialchars($form->name) ?>
                </div>
                <div class="form-row">
                <?php
                foreach ( $form->inputs AS $input ):
                    echo FormElements::formElement( 0, $input->type, strip_tags( $input->label ), $input->is_required, $input->help_text, !empty($input->value) ? $input->value->input_value : '', $input->id, $input->options, !empty($input->value) ? $input->value->input_file_name : '', true );
                    endforeach;
                ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>

        $(document).ready(function ()
        {
            $("#<?php echo $customFieldsAreaId?> .custom-input-select2").select2({
                theme: 'bootstrap',
                allowClear: true
            });

            $( '#<?php echo $customFieldsAreaId?> .custom-forms-date-input' ).each( function () {
                $( this ).datepicker( {
                    autoclose: true,
                    format: dateFormat.replace( 'YYYY','Y' )
                        .replace( 'Y', 'yyyy' )
                        .replace( 'MM', 'm' )
                        .replace( 'm', 'mm' )
                        .replace( 'DD', 'd' )
                        .replace( 'd', 'dd' ),
                    weekStart: weekStartsOn == 'sunday' ? 0 : 1
                });
            } );

            $("#<?php echo $customFieldsAreaId?>").on('click', '.remove_custom_file_btn', function()
            {
                var placeholder = $(this).data('placeholder');
                if ($(this).closest('.uploaded-files-container').length > 0) {
                    $(this).parent().remove();
                } else {
                    $(this).parent().text( placeholder );
                }
            });

            let divEl = $("#<?php echo $customFieldsAreaId?>");
            bookneticInitFormConditions( booknetic, divEl, true );
        });

    </script>
<?php endif; ?>