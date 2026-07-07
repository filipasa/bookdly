<?php

namespace BookneticAddon\Customforms\Backend;

use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticAddon\Customforms\Model\AppointmentCustomData;
use BookneticAddon\Customforms\Model\Form;
use BookneticAddon\Customforms\Model\FormInput;
use BookneticAddon\Customforms\Model\FormInputChoice;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Customforms\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function save_form ()
	{
		$id			= Helper::_post('id', 0, 'int');
		$name		= Helper::_post('name', '', 'string');
		$services	= Helper::_post('services', '', 'string');
		$conditions	= Helper::_post('conditions', '', 'string');
		$inputs		= Helper::_post('inputs', '', 'string');

        if ( $id > 0 )
        {
            Capabilities::must( 'custom_forms_edit' );
        }
        else
        {
            Capabilities::must( 'custom_forms_add' );
        }

		if ( $id < 0 || empty( $name ) )
		{
			return $this->response( false, bkntc__( 'Please fill in all required fields correctly!' ) );
		}

        $conditions = json_decode( $conditions, true );
		$inputs = json_decode( $inputs, true );

		if ( empty( $inputs ) || ! is_array( $inputs ) )
		{
			return $this->response( false, bkntc__( 'Please fill in all required fields correctly!' ) );
		}

        if( empty( $conditions ) || ! is_array( $conditions ) )
        {
            $conditions = [];
        }

		$services       = explode( ',', $services );
		$servicesArr    = [];

		foreach ( $services AS $service )
		{
			if ( is_numeric( $service ) && $service > 0 )
			{
				$servicesArr[] = (int) $service;
			}
		}

		$services = implode(',', $servicesArr);

		$isEdit = $id > 0;

		if ( $isEdit )
		{
		    Capabilities::must('custom_forms_edit');
			Form::where( 'id', $id )->update( [
				'name'          => $name,
				'service_ids'   => $services,
                'conditions'    => json_encode( $conditions )
			] );
		}
		else
		{
            Capabilities::must('custom_forms_add');
            Form::insert( [
				'name'          => $name,
				'service_ids'   => $services,
                'conditions'    => json_encode( $conditions )
			] );

			$id = DB::lastInsertedId();
		}

		$saveIDs    = [];
        $order      = 1;
        $replaceRandomIDs = [];

        foreach ( $inputs AS $input )
		{
			if (
				! (
					is_array( $input )
					&& isset( $input['id'] ) && ( is_numeric( $input['id'] ) || is_string( $input['id'] ) )
					&& isset( $input['type'] ) && is_string($input['type']) && in_array( $input['type'], ['label', 'text', 'textarea', 'number', 'date', 'time', 'select', 'checkbox', 'radio', 'file', 'link', 'email', 'phone'] )
				)
			)
			{
				continue;
			}

			$inputId		= $input['id'];
			$inputType		= $input['type'];
			$label			= isset($input['label']) ? $input['label'] : '';
			$help_text		= isset($input['help_text']) ? $input['help_text'] : '';
			$is_required	= isset($input['is_required']) && $input['is_required'] ? 1 : 0;
			$choices		= isset($input['choices']) && is_array( $input['choices'] ) ? $input['choices'] : [];
            $translations   = isset( $input[ 'translations' ] ) && ! empty( $input[ 'translations' ] ) ? $input[ 'translations' ] : false;

			if ( ! in_array( $inputType, [ 'select', 'checkbox', 'radio' ] ) )
			{
				$choices = [];
			}

			if ( mb_strlen( $label, 'utf-8' ) > 255 )
			{
				$label = mb_substr( $label, 0, 255, 'UTF-8' );
			}

			if ( mb_strlen( $help_text, 'utf-8' ) > 500 )
			{
				$help_text = mb_substr( $help_text, 0, 500, 'UTF-8' );
			}

			$allowedOptions = [ 'placeholder', 'col-md', 'visibility', 'min_length', 'max_length', 'url', 'allowed_file_formats' ];

			foreach ( $input AS $inputKey => $inputValue )
			{
				if ( !in_array( $inputKey, $allowedOptions ) )
				{
					unset( $input[ $inputKey ] );
				}

				if ( ($inputKey == 'placeholder' || $inputKey == 'url') && mb_strlen( $inputValue, 'utf-8' ) > 200 )
				{
					$input[ $inputKey ] = mb_substr($inputValue, 0, 200, 'UTF-8');
				}
			}

			$sqlData = [
				'label'			=>	$label,
				'help_text'		=>	$help_text,
				'is_required'	=>	$is_required,
				'order_number'	=>	$order,
				'options'		=>	json_encode( $input )
			];

			$isNewInput = is_numeric( $inputId ) && $inputId > 0 ? false : true;

			if ( ! $isNewInput )
			{
				FormInput::where('id', $inputId)->where('form_id', $id)->where('type', $inputType)->update( $sqlData );
            }
			else
			{
				$sqlData['form_id']	= $id;
				$sqlData['type']	= $inputType;

				FormInput::insert( $sqlData );
                $insertedId = DB::lastInsertedId();

                $replaceRandomIDs[ $inputId ] = $insertedId;
				$inputId = $insertedId;
			}

            if ( $translations ) {
                FormInput::handleTranslation( $inputId, $translations );
            }

			$saveIDs[] = $inputId;

			$choiceOrder = 1;
			$saveChoiceIDs = [];

			foreach ( $choices AS $choice )
			{
				if (
					isset( $choice[0] ) && ( is_numeric( $choice[0] ) || is_string( $choice[0] ) )
					&& isset( $choice[1] ) && is_string( $choice[1] )
				)
				{
					$choiceId       = $choice[0];
					$choiceTitle    = (string)$choice[1];

					if ( is_numeric( $choiceId ) && $choiceId > 0 )
					{
						FormInputChoice::where('id', $choiceId)->where('form_input_id', $inputId)->update( [
							'title'			=>	$choiceTitle,
							'order_number'	=>	$choiceOrder

						] );
					}
					else
					{
						FormInputChoice::insert( [
							'form_input_id'	=>	$inputId,
							'title'			=>	$choiceTitle,
							'order_number'	=>	$choiceOrder
						] );
                        $insertedId = DB::lastInsertedId();

						$replaceRandomIDs[ $choiceId ] = $insertedId;
						$choiceId = $insertedId;
					}

                    if ( isset( $choice[ 2 ] ) && ! empty( $choice[ 2 ] ) ) {
                        FormInputChoice::handleTranslation( $choiceId, $choice[ 2 ] );
                    }

					$saveChoiceIDs[] = $choiceId;

					$choiceOrder++;
				}
			}

			if ( ! $isNewInput )
			{
				$saveChoiceIDs = empty( $saveChoiceIDs ) ? '' : " AND id NOT IN ('" . implode( "', '", $saveChoiceIDs ) . "')";

				DB::DB()->query("DELETE FROM `" . DB::table('form_input_choices') . "` WHERE form_input_id='" . (int)$inputId . "' " . $saveChoiceIDs);
			}

			$order++;
		}

        if( ! empty( $replaceRandomIDs ) )
        {
            $conditions = json_encode( $conditions );

            foreach ( $replaceRandomIDs as $randomId => $realId )
            {
                $conditions = str_replace( $randomId, $realId, $conditions );
            }

            Form::where('id', $id)->update([
                'conditions' => $conditions
            ]);
        }

		if ( $isEdit )
		{
			$saveIDs = empty( $saveIDs ) ? '' : " AND id NOT IN ('" . implode( "', '", $saveIDs ) . "')";
			
			DB::DB()->query("DELETE FROM `" . DB::table('appointment_custom_data') . "` WHERE form_input_id IN (SELECT `id` FROM `" . DB::table('form_inputs') . "` WHERE form_id='" . (int)$id . "' " . $saveIDs . ")");
			DB::DB()->query("DELETE FROM `" . DB::table('form_input_choices') . "` WHERE form_input_id IN (SELECT `id` FROM `" . DB::table('form_inputs') . "` WHERE form_id='" . (int)$id . "' " . $saveIDs . ")");
			DB::DB()->query("DELETE FROM `" . DB::table('form_inputs') . "` WHERE form_id='" . (int)$id . "' " . $saveIDs);
		}

		return $this->response( true, [ 'id' => $id ] );
	}

	public function appointment_load_custom_fields()
	{
        Capabilities::must('appointments_customforms_tab');
        $appointmentId	    = Helper::_post('appointment_id', '0', 'integer');
		$serviceId		    = Helper::_post('service_id', '0', 'integer');

        $values = AppointmentCustomData::where('appointment_id', $appointmentId)->fetchAll();
        $values = Helper::assocByKey($values, 'form_input_id');

        $forms = Form::whereFindInSet(Form::getField('service_ids'), $serviceId)->orWhere( Form::getField( 'service_ids' ), '' )->fetchAll();

        foreach ($forms as $formInf)
        {
            $formInf->inputs = FormInput::where('form_id', $formInf->id)->orderBy('order_number')->fetchAll();
            foreach ($formInf->inputs as $input)
            {
                $input->value = array_key_exists($input->id, $values) ? $values[$input->id] : null;
            }
        }

        return $this->modalView( 'custom_form', [
            'forms'     => $forms
        ] );
	}

    public function set_conditions()
    {
        return $this->modalView('set_conditions', [
            'services'  => Service::select( [ 'id', 'name'] )->fetchAll(),
            'staff'     => Staff::select( [ 'id', 'name'] )->fetchAll(),
            'locations' => Location::select( [ 'id', 'name'] )->fetchAll()
        ]);
    }

    public function get_input_choices()
    {
        $search		= Helper::_post('q', '', 'string');
        $formId     = Helper::_post('form_id', '', 'int');

        $forms = FormInputChoice::where( 'form_input_id', $formId )->where( 'title', 'LIKE', '%' . $search . '%' )->fetchAll();
        $data       = [];

        foreach ( $forms AS $form )
        {
            $data[] = [
                'id'    =>	(int)$form['id'],
                'text'  =>	htmlspecialchars( $form['title'] )
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

}
