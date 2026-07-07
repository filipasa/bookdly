<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticAddon\Customforms\Model\FormInputChoice;
use function BookneticAddon\Customforms\bkntc__;

if ( empty( $parameters ) )
{
	echo '<div class="text-secondary font-size-14 text-center">' . bkntc__( 'No custom fields found' ) . '</div>';
}
else
{
	foreach ($parameters AS $form)
	{
		?>
		<div class="customer-fields-area dashed-border">

            <div class="d-flex justify-content-center user_visit_card">
                <?php echo htmlspecialchars($form['form']->name) ?>
            </div>

            <?php foreach ($form['inputs'] as $field): ?>
				<div class="form-row">
					<div class="form-group col-md-12">
						<label><?php echo htmlspecialchars( $field['form_input_label'] )?></label>
						<div class="form-control-plaintext">
							<?php
							if( $field['form_input_type'] == 'file' )
							{
								echo '<a href="' . Helper::uploadedFileURL(htmlspecialchars($field['input_value']), 'CustomForms') . '" target="_blank">' . htmlspecialchars( $field['input_file_name'] ) . '</a>';
							}
                            else if (in_array($field['form_input_type'], ['select', 'checkbox', 'radio']))
                            {
                                $realValues = FormInputChoice::whereFindInSet('id', explode(',', $field['input_value']))->select('group_concat(title separator \', \') as titles', true)->fetch();
                                echo $realValues['titles'];
                            }
                            else if ( $field[ 'form_input_type' ] == 'date' )
                            {
                                echo Date::datee( Date::reformatDateFromCustomFormat(htmlspecialchars( $field[ 'input_value' ] )) );
                            }
                            else if ( $field[ 'form_input_type' ] == 'time' )
                            {
                                echo Date::time( htmlspecialchars( $field[ 'input_value' ] ) );
                            }
							else
							{
								echo htmlspecialchars( $field['input_value'] );
							}
							?>
						</div>
					</div>
				</div>

        <?php endforeach; ?>

		</div>

		<?php
	}
}

?>