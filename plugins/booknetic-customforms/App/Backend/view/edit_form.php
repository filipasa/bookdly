<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Customforms\CustomFormsAddon;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\Customforms\bkntc__;

/**
 * @var mixed $parameters
 */

$formInputTpls = [
	'label'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formLabel('%label%', '%helptext%', -1, '%col-md%' ),
	'text'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formText('%label%', false, '%helptext%', '%placeholder%', '', -1, 'text', '', '', '%col-md%' ),
	'textarea'	=>	\BookneticAddon\Customforms\Helpers\FormElements::formTextarea('%label%', false, '%helptext%', '%placeholder%', '', -1, '', '', '%col-md%' ),
	'number'	=>	\BookneticAddon\Customforms\Helpers\FormElements::formNumber('%label%', false, '%helptext%', '%placeholder%', '', -1, '', '', '%col-md%' ),
	'date'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formDate('%label%', false, '%helptext%', '', '', -1, '%col-md%' ),
	'time'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formTime('%label%', false, '%helptext%', '%placeholder%', '', -1, '%col-md%' ),
	'select'	=>	\BookneticAddon\Customforms\Helpers\FormElements::formSelect('%label%', false, '%helptext%', '%placeholder%', '', -1, '%col-md%' ),
	'checkbox'	=>	\BookneticAddon\Customforms\Helpers\FormElements::formCheckbox(0, '%label%', false, '%helptext%', -1, '', '%col-md%' ),
	'radio'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formRadio(0, '%label%', false, '%helptext%', -1, '', '%col-md%' ),
	'file'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formFile('%label%', false, '%helptext%', '%placeholder%', '', -1, '', '%col-md%' ),
	'file_multiple' => \BookneticAddon\Customforms\Helpers\FormElements::formFileMultiple('%label%', false, '%helptext%', '%placeholder%', '', -1, '', '%col-md%' ),
    'link'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formLink('%label%', '%helptext%', '', -1, '%col-md%' ),
    'email'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formText('%label%', false, '%helptext%', '%placeholder%', '', -1, 'text', '', '', '%col-md%' ),
    'phone'		=>	\BookneticAddon\Customforms\Helpers\FormElements::formText('%label%', false, '%helptext%', '%placeholder%', '', -1, 'text', '', '', '%col-md%' ),
];
?>

<script src="<?php echo CustomFormsAddon::loadAsset('assets/backend/js/edit.js')?>"></script>
<link rel="stylesheet" href="<?php echo CustomFormsAddon::loadAsset('assets/backend/css/edit.css')?>" type="text/css">

<div class="m_header">
	<div class="row">
		<div class="m_head_title col-md-3"><?php echo bkntc__('Customize')?></div>
		<div class="col-md-6">
			<div class="row">
				<div class="col-sm-6 pl-3 pr-3 pt-2 pr-sm-0 p-md-0">
					<input type="text" class="form-control" value="<?php echo htmlspecialchars( $parameters['form']['name'] )?>" placeholder="<?php echo bkntc__('Form name')?>" id="input_form_name">
				</div>
				<div class="col-sm-6 pl-3 pr-3 pt-2 pl-sm-0 p-md-0">
					<select class="form-control" multiple id="input_form_services">
						<?php
						foreach( $parameters['services'] AS $service )
						{
							echo '<option value="' . (int)$service['id'] . '"' . ( in_array( (string)$service['id'], explode(',', $parameters['form']['service_ids'] ) ) ? ' selected' : '' ) . '>' . htmlspecialchars( $service['name'] ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div class="m_head_actions col-md-3 float-right">
			<button type="button" class="btn btn-lg btn-success float-right ml-1" id="save-form-btn"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE FORM')?></button>
            <button type="button" class="btn btn-lg btn-secondary float-right ml-1" data-load-modal="set_conditions" id="set_form_conditions_btn" data-conditions="<?php echo htmlspecialchars( json_encode( $parameters['form']['conditions'] ) )?>"><i class="fa fa-bolt pr-2"></i> <?php echo bkntc__('FORM CONDITIONS')?> (<span id="conditions-count"><?php echo count( $parameters['form']['conditions'] );?></span>)</button>
        </div>
	</div>
</div>

<div class="fs_separator"></div>

<div class="row m-4">

	<div class="col-xl-3 col-md-6 col-lg-5 p-3 pr-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntc__('Elements')?></div>
			<div class="fs_portlet_content p-0">

				<div class="row m-0 p-0">

					<div class="col-md-6 p-0 formbuilder_element" data-type="label">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/label.svg')?>">
						<span><?php echo bkntc__('Label')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="text">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/text.svg')?>">
						<span><?php echo bkntc__('Text input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="textarea">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/textarea.svg' )?>">
						<span><?php echo bkntc__('Textarea')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="number">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/number.svg' )?>">
						<span><?php echo bkntc__('Number input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="date">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/datepicker.svg' )?>">
						<span><?php echo bkntc__('Date input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="time">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/timepicker.svg' )?>">
						<span><?php echo bkntc__('Time input')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="select">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/select.svg' )?>">
						<span><?php echo bkntc__('Select')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="checkbox">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/checkbox.svg' )?>">
						<span><?php echo bkntc__('Check-box')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="radio">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/radio.svg' )?>">
						<span><?php echo bkntc__('Radio buttons')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="file">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/file.svg' )?>">
						<span><?php echo bkntc__('File')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="file_multiple">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/file.svg' )?>">
						<span><?php echo bkntc__('File (Multiple)')?></span>
					</div>

					<div class="col-md-6 p-0 formbuilder_element" data-type="link">
						<img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/link.svg' )?>">
						<span><?php echo bkntc__('Link')?></span>
					</div>

                    <div class="col-md-6 p-0 formbuilder_element" data-type="email">
                        <img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/email.svg' )?>">
                        <span><?php echo bkntc__('Email')?></span>
                    </div>

                    <div class="col-md-6 p-0 formbuilder_element" data-type="phone">
                        <img src="<?php echo CustomFormsAddon::loadAsset('assets/backend/icons/phone.svg' )?>">
                        <span><?php echo bkntc__('Phone')?></span>
                    </div>

				</div>

			</div>
		</div>
	</div>

	<div class="col-xl-6 col-md-6 col-lg-7 p-3 pr-md-3 pr-xl-1 pl-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntc__('Form')?></div>
			<div class="fs_portlet_content">
                <div class="form-row" id="formbuilder_area">
				<?php
				foreach ($parameters['inputs'] AS $input)
				{
					$type = $input['type'];

					if( !isset( $formInputTpls[ $type ] ) )
						continue;

					$tpl = $formInputTpls[ $type ];

					$options = json_decode( $input['options'], true );
					$options = is_array( $options ) ? $options : [];
					$options['label'] = $input['label'];
					$options['help_text'] = $input['help_text'];
					$options['is_required'] = $input['is_required'];

					if( isset( $input['choices'] ) )
					{
						$options['choices'] = $input['choices'];
					}

					$tpl = str_replace( [
						'%label%',
						'%helptext%',
						'%placeholder%',
						'data-required="false"',
					], [
						htmlspecialchars($input['label']),
						htmlspecialchars($input['help_text']),
						isset( $options['placeholder'] ) ? htmlspecialchars( $options['placeholder'] ) : '',
						'data-required="' . ($input['is_required'] ? 'true' : 'false') . '"',
					], $tpl );

                    $colMd = isset($options['col-md']) && is_numeric($options['col-md']) && $options['col-md'] > 0 && $options['col-md'] <= 12 ? (int)$options['col-md'] : 12;

					echo '<div class="form_element form-group col-md-'.$colMd.'" data-type="' . htmlspecialchars($type) . '" data-id="' . (int)$input['id'] . '" data-options="' . htmlspecialchars( json_encode( $options ) ) . '">' . $tpl . '<img class="remove-element-btn" src="' . CustomFormsAddon::loadAsset('assets/backend/icons/remove.svg') . '"></div>';

				}
				?>
			    </div>
			</div>
		</div>
	</div>

	<div class="col-xl-3 col-md-6 col-lg-5 p-3 pr-md-1 pr-xl-3 pl-xl-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntc__('Options')?></div>
			<div id="formbuilder_options" class="fs_portlet_content">

				<div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,file_multiple,link,email,phone">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_label"><?php echo bkntc__('Label')?></label>
						<input type="text" data-multilang="true" class="form-control" id="formbuilder_options_label" maxlength="255" placeholder="<?php echo bkntc__('Max: 255 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="text,textarea,number,date,time,file,file_multiple,select,email,phone">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_placeholder"><?php echo bkntc__('Placeholder')?></label>
						<input type="text" class="form-control" id="formbuilder_options_placeholder" maxlength="200" placeholder="<?php echo bkntc__('Max: 200 symbol')?>">
					</div>
				</div>

                <div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,file_multiple,link,email,phone">
                    <div class="form-group col-md-12">
                        <label for="formbuilder_options_col-md"><?php echo bkntc__('Width')?></label>
                        <select class="form-control" id="formbuilder_options_col-md">
                            <option value="12">col-md-12</option>
                            <option value="11">col-md-11</option>
                            <option value="10">col-md-10</option>
                            <option value="9">col-md-9</option>
                            <option value="8">col-md-8</option>
                            <option value="7">col-md-7</option>
                            <option value="6">col-md-6</option>
                            <option value="5">col-md-5</option>
                            <option value="5">col-md-5</option>
                            <option value="4">col-md-4</option>
                            <option value="3">col-md-3</option>
                            <option value="2">col-md-2</option>
                            <option value="1">col-md-1</option>
                        </select>
                    </div>
                </div>

                <div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,file_multiple,link,email,phone">
                    <div class="form-group col-md-12">
                        <label for="formbuilder_options_visibility"><?php echo bkntc__('Visibility')?></label>
                        <select class="form-control" id="formbuilder_options_visibility">
                            <option value="visible">Visible</option>
                            <option value="visible_only_admin">Visible (only admin panel)</option>
                            <option value="hidden">Hidden</option>
                        </select>
                    </div>
                </div>

				<div class="form-row hidden" data-for="text,textarea,number">
					<div class="form-group col-md-6">
						<label for="formbuilder_options_min_length"><?php echo bkntc__('Min length')?></label>
						<input type="text" class="form-control" id="formbuilder_options_min_length">
					</div>
					<div class="form-group col-md-6">
						<label for="formbuilder_options_max_length"><?php echo bkntc__('Max length')?></label>
						<input type="text" class="form-control" id="formbuilder_options_max_length">
					</div>
				</div>

				<div class="form-row hidden" data-for="label,text,textarea,number,date,time,select,checkbox,radio,file,file_multiple,link,email,phone">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_help_text"><?php echo bkntc__('Help text')?></label>
						<input type="text" data-multilang="true" class="form-control" id="formbuilder_options_help_text" maxlength="500" placeholder="<?php echo bkntc__('Max: 500 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="file,file_multiple">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_allowed_file_formats"><?php echo bkntc__('Allowed file formats')?></label>
						<input type="text" class="form-control" id="formbuilder_options_allowed_file_formats" maxlength="500" placeholder="doc,docx,xls,xlsx,jpg,jpeg,png,gif,mp4,zip,rar,csv">
					</div>
				</div>

				<div class="form-row hidden" data-for="text,textarea,number,date,time,select,checkbox,radio,file,file_multiple,email,phone">
					<div class="form-group col-md-12">
						<div class="form-control-plaintext">
							<input id="formbuilder_options_is_required" type="checkbox">
							<label for="formbuilder_options_is_required"><?php echo bkntc__('Is required')?></label>
						</div>
					</div>
				</div>

				<div class="form-row hidden" data-for="link">
					<div class="form-group col-md-12">
						<label for="formbuilder_options_url"><?php echo bkntc__('URL')?></label>
						<input type="text" class="form-control" id="formbuilder_options_url" maxlength="200" placeholder="<?php echo bkntc__('Max: 200 symbol')?>">
					</div>
				</div>

				<div class="form-row hidden" data-for="select,checkbox,radio" data-choices="true">
					<div class="form-group col-md-12">
						<div class="form-control-plaintext">
							<label><?php echo bkntc__('Choices')?></label>
							<div id="choices_area"></div>
							<div id="formbuilder_options_add_new_choice"><i class="fa fa-plus-circle"></i> <?php echo bkntc__('Add new')?></div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

</div>

<script>

	var formInputTpls			= <?php echo json_encode($formInputTpls)?>;
	var currentModuleAssetsURL	= "<?php echo CustomFormsAddon::loadAsset('assets/')?>";
	var currentFormID			= <?php echo $parameters['id']?>;

</script>