<?php

namespace BookneticAddon\Customforms\Helpers;

use BookneticAddon\Customforms\CustomFormsAddon;
use BookneticAddon\Customforms\Model\FormInputChoice;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Customforms\bkntc__;

class FormElements
{

	public static function formElement( $preId, $type, $label, $isRequired = false, $helptext = '', $value = '', $inputId = '', $options = '', $file_name ='', $isBackend = false )
	{
		$optionsArr = json_decode($options, true);
		$placeholder = isset( $optionsArr['placeholder'] ) && is_string( $optionsArr['placeholder'] ) ? $optionsArr['placeholder'] : '';
		$minLength = isset( $optionsArr['min_length'] ) && is_string( $optionsArr['min_length'] ) ? $optionsArr['min_length'] : '';
		$maxLength = isset( $optionsArr['max_length'] ) && is_string( $optionsArr['max_length'] ) ? $optionsArr['max_length'] : '';
		$colMd = isset( $optionsArr['col-md'] ) && is_numeric( $optionsArr['col-md'] ) && $optionsArr['col-md'] > 0 && $optionsArr['col-md'] <= 12 ? (int)$optionsArr['col-md'] : 12;
		$visibility = isset( $optionsArr['visibility'] ) && is_string( $optionsArr['visibility'] ) ? $optionsArr['visibility'] : 'visible';

        $return = '';

		if ( $type == 'label' )
		{
			$return = self::formLabel( $label, $helptext, $inputId );
		}
		else if( $type == 'text' )
		{
			$return = self::formText( $label, $isRequired, $helptext, $placeholder, $value, $inputId, 'text', $minLength, $maxLength );
		}
		else if( $type == 'textarea' )
		{
			$return = self::formTextarea( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $minLength, $maxLength );
		}
		else if( $type == 'number' )
		{
			$return = self::formNumber( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $minLength, $maxLength );
		}
		else if( $type == 'date' )
		{
			$return = self::formDate( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'time' )
		{
			$return = self::formTime( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'select' )
		{
			$return = self::formSelect( $label, $isRequired, $helptext, $placeholder, $value, $inputId );
		}
		else if( $type == 'checkbox' )
		{
			$return = self::formCheckbox( $preId, $label, $isRequired, $helptext, $inputId, $value );
		}
		else if( $type == 'radio' )
		{
			$return = self::formRadio( $preId, $label, $isRequired, $helptext, $inputId, $value );
		}
		else if( $type == 'file' )
		{
			$return = self::formFile( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $file_name );
		}
		else if( $type == 'link' )
		{
			$return = self::formLink( $label, $helptext, $options, $inputId );
		}
        else if( $type == 'email' )
        {
            $return = self::formText( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $inputType = 'email', $minLength, $maxLength );
        }
        else if( $type == 'phone' )
        {
            $return = self::formText( $label, $isRequired, $helptext, $placeholder, $value, $inputId, $inputType='phone', $minLength, $maxLength );
        }

        $visibility = $visibility == 'hidden' || (!$isBackend && $visibility == 'visible_only_admin') ? ' style="display: none;"' : '';

        return '<div class="form-group col-md-'.$colMd.'"'.$visibility.'>' . $return . '</div>';
	}

	public static function formLabel( $label = '', $helptext = '', $inputId = '' )
	{
		return '<div class="form-control-plaintext" data-label="true" data-input-id="' . (int)$inputId .'">' . strip_tags($label, '<a><b><i><u>') . '</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formText( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '', $inputType = 'text', $minLength='', $maxLength='' )
	{
		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<input data-type="'.$inputType.'" minlength="'. $minLength .'" maxlength="'. $maxLength .'"  placeholder="' . htmlspecialchars($placeholder) . '" type="text" class="form-control" data-input-id="' . (int)$inputId .'" value="' . htmlspecialchars($value) . '">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formTextarea( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '', $minLength='', $maxLength=''  )
	{
		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<textarea minlength="'. $minLength .'" maxlength="'. $maxLength .'"   placeholder="' . htmlspecialchars($placeholder) . '" class="form-control" data-input-id="' . (int)$inputId .'">' . htmlspecialchars($value) . '</textarea>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formNumber( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '', $minLength='', $maxLength='' )
	{
		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<input minlength="'. $minLength .'" maxlength="'. $maxLength .'"   placeholder="' . htmlspecialchars($placeholder) . '" type="number" class="form-control" value="' . htmlspecialchars($value) . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

    public static function formDate( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
    {
        $format = Date::identifyDateFormat( htmlspecialchars( $value ) );

        if ( ! empty( $format ) )
        {
            $value = \DateTime::createFromFormat( $format, htmlspecialchars( $value ) )->getTimestamp();
            $value = Date::datee( $value );
        }
        else
        {
            $value = '';
        }

        return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<input data-type="date" placeholder="' . htmlspecialchars($placeholder) . '" class="custom-forms-date-input form-control" value="' . $value . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formTime( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '' )
	{
		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<input placeholder="' . htmlspecialchars($placeholder) . '" type="time" class="form-control" value="' . htmlspecialchars($value) . '" data-input-id="' . (int)$inputId .'">
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formSelect( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = null, $inputId = '' )
	{
        $optionsHTML = '<option></option>';

        if( !empty( $inputId ) && $inputId > 0 )
        {
            $getOptions = FormInputChoice::where('form_input_id', $inputId)->orderBy('order_number')->withTranslations()->fetchAll();

            foreach ( $getOptions AS $option )
            {
                $isChecked = $option['id'] == $value ? ' selected' : '';

                $optionsHTML .= '<option value="' . (int)$option['id'] . '"' . $isChecked . '>' . htmlspecialchars( $option['title'] ) . '</option>';
            }
        }

        return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<select class="form-control custom-input-select2" data-placeholder="' . htmlspecialchars( $placeholder ) . '" data-input-id="' . (int)$inputId .'">
					' . $optionsHTML . '
				</select>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formCheckbox( $preId, $label = '', $isRequired = false, $helptext = '', $inputId = '', $value = null )
	{
		$value = explode(',', $value);
		$choicesHTML = '';

		if( $inputId == -1 )
		{
			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_checkbox_' . $preId . '_1" type="checkbox" data-input-id="' . (int)$inputId .'" value="1">
						<label for="custom_checkbox_'.$preId.'_1"> '.bkntc__('Choice 1').'</label>
					</div>';

			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_checkbox_' . $preId . '_2" type="checkbox" data-input-id="' . (int)$inputId .'" value="2">
						<label for="custom_checkbox_'.$preId.'_2"> '.bkntc__('Choice 2').'</label>
					</div>';
		}
		else if( !empty( $inputId ) )
		{
			$getChoices = FormInputChoice::where('form_input_id', $inputId)->orderBy('order_number')->withTranslations()->fetchAll();

			foreach ( $getChoices AS $choice )
			{
				$isChecked = in_array( $choice['id'], $value ) ? ' checked' : '';

				$choicesHTML .= '
					<div style="display: flex">
						<input id="custom_checkbox_' . $preId . '_' . (int)$choice['id'] . '" type="checkbox" data-input-id="' . (int)$inputId .'" value="' . (int)$choice['id'] . '"' . $isChecked . '>
						<label for="custom_checkbox_' . $preId . '_' . (int)$choice['id'] . '"> ' . htmlspecialchars( $choice['title'] ) . '</label>
					</div>';
			}
		}

		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<div>
					' . $choicesHTML . '
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formRadio( $preId, $label = '', $isRequired = false, $helptext = '', $inputId = '', $value = null )
	{
		$value = explode(',', $value);
		$choicesHTML = '';

		if( $inputId == -1 )
		{
			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_radio_' . $preId . '_1" type="radio" name="custom_field_'.$preId.'_1" data-input-id="' . (int)$inputId .'" value="1">
						<label for="custom_radio_'.$preId.'_1"> '.bkntc__('Choice 1').'</label>
					</div>';

			$preId = rand(1,99999);
			$choicesHTML .= '
					<div>
						<input id="custom_radio_' . $preId . '_2" type="radio" name="custom_field_'.$preId.'_2" data-input-id="' . (int)$inputId .'" value="2">
						<label for="custom_radio_'.$preId.'_2"> '.bkntc__('Choice 2').'</label>
					</div>';
		}
		else if( !empty( $inputId ) )
		{
			$getChoices = FormInputChoice::where('form_input_id', $inputId)->orderBy('order_number')->withTranslations()->fetchAll();

			foreach ( $getChoices AS $choice )
			{
				$isChecked = in_array( $choice['id'], $value ) ? ' checked' : '';

				$choicesHTML .= '
					<div style="display: flex">
						<input id="custom_radio_'.$preId.'_' . (int)$choice['id'] . '" type="radio" name="custom_field_'.$preId.'_' . (int)$inputId . '" data-input-id="' . (int)$inputId .'" value="' . (int)$choice['id'] . '"' . $isChecked . '>
						<label for="custom_radio_'.$preId.'_' . (int)$choice['id'] . '"> ' . htmlspecialchars( $choice['title'] ) . '</label>
					</div>';
			}
		}

		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<div>
					' . $choicesHTML . '
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formFile( $label = '', $isRequired = false, $helptext = '', $placeholder = '', $value = '', $inputId = '', $file_name = '' )
	{
        $randId = md5(uniqid());
		return '<label data-label="true" data-required="' . ( $isRequired ? 'true' : 'false' ) . '">' . strip_tags($label, '<a><b><i><u>') . '</label>
				<input id="'.$randId.'" placeholder="' . htmlspecialchars($placeholder) . '" type="file" class="form-control" data-input-id="' . (int)$inputId .'">
				<label for="'.$randId.'"  class="form-control" data-label="'.bkntc__('BROWSE').'" data-has-label="true">' . (empty( $file_name ) ? htmlspecialchars($placeholder) : '<img src="' . Helper::assets('icons/unsuccess.svg') . '" class="remove_custom_file_btn" data-placeholder="' . htmlspecialchars($placeholder) . '" data-save-custom-data="' . (int)$inputId . '"> <a href="' . Helper::uploadedFileURL( $value, 'Customforms' ) . '" target="_blank">'.htmlspecialchars($file_name) . '</a>' ) . '</label>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

	public static function formLink( $label = '', $helptext = '', $options = '', $inputId = '' )
	{
		$options = json_decode($options, true);

		$url = isset( $options['url'] ) && is_string($options['url']) ? $options['url'] : '#';

		return '<div class="form-control-plain">
					<a href="' . $url . '"  class="custom-form-link"  target="_blank" data-label="true" data-input-id="' . (int)$inputId .'">' . strip_tags($label, '<a><b><i><u>') . '</a>
				</div>
				<span class="help-text">' . htmlspecialchars($helptext) . '</span>';
	}

}
