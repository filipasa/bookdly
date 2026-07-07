<?php

namespace BookneticAddon\Customforms;

use BookneticAddon\Customforms\Helpers\ConditionalFields;
use BookneticAddon\Customforms\Helpers\FormElements;
use BookneticAddon\Customforms\Helpers\CheckConditions;
use BookneticAddon\Customforms\Model\AppointmentCustomData;
use BookneticAddon\Customforms\Model\Form;
use BookneticAddon\Customforms\Model\FormInput;
use BookneticAddon\Customforms\Model\FormInputChoice;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use Exception;

class Listener
{

	public static function frontend_render_ui( $service )
	{
        $forms = Form::whereFindInSet(Form::getField('service_ids'), $service)->orWhere('service_ids', '')->fetchAll();

        foreach ( $forms AS $formInf )
        {
            $conditions = json_decode($formInf->conditions, true);
            $conditionsAttr = '';

            if( ! empty( $conditions ) && is_array( $conditions ) )
            {
                $conditionsAttr = ' data-conditions="' . htmlspecialchars( json_encode( $conditions ) ) . '"';
            }

            $custom_inputs = FormInput::where('form_id', $formInf->id)->orderBy('order_number')->withTranslations()->fetchAll();

            if( !empty($custom_inputs) )
            {
                echo '<div class="booknetic_custom_form" data-form-id="' . (int)$formInf->id . '"'.$conditionsAttr.'>';
                echo '<div class="form-row">';
                foreach ( $custom_inputs AS $custom_data )
                {
                   echo FormElements::formElement( 1, $custom_data['type'], $custom_data['label'], $custom_data['is_required'], $custom_data['help_text'], '', $custom_data['id'], $custom_data['options'] );
                }
                echo '</div>';
                echo '</div>';
            }
        }
	}

    /**
     * @throws Exception
     */
    public static function frontend_validate(AppointmentRequestData $appointment )
    {
        $fields = CheckConditions::Calculate( $appointment );

        foreach ( $fields as $field )
		{
            $options  = $field[ 'options' ];
            $required = $field[ 'required' ];
            $value    = $field[ 'value' ];

            if ( $options[ 'visibility' ] !== 'visible' )
            {
                if ( ! empty( $value ) )
                {
                    throw new Exception( bkntc__('Please fill in all required fields correctly!') );
                }

                continue;
            }

            if( $field[ 'type' ] == 'file' || $field[ 'type' ] == 'file_multiple' )
			{
                if ( $field[ 'type' ] == 'file_multiple' )
                {
                    $files = [];
                    if ( is_array( $value ) )
                    {
                        $files = isset( $value[ 'files' ] ) ? $value[ 'files' ] : $value;
                    }
                    else if ( is_string( $value ) && ! empty( $value ) )
                    {
                        $decoded = json_decode( $value, true );
                        if ( is_array( $decoded ) )
                        {
                            $files = isset( $decoded[ 'files' ] ) ? $decoded[ 'files' ] : $decoded;
                        }
                    }

                    if ( $required && empty( $files ) )
                    {
                        throw new Exception( bkntc__('Please fill in all required fields correctly!') );
                    }

                    if ( isset( $options[ 'allowed_file_formats' ] ) && ! empty( $options[ 'allowed_file_formats' ] ) && is_string( $options[ 'allowed_file_formats' ] ) )
                    {
                        $allowedFileFormats = Helper::secureFileFormats( explode( ',', str_replace( ' ', '', $options[ 'allowed_file_formats' ] ) ) );
                    }
                    else
                    {
                        $allowedFileFormats = [ 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'zip', 'rar', 'csv', 'mov' ];
                    }

                    foreach ( $files as $file )
                    {
                        $fileName = isset( $file[ 'name' ] ) ? $file[ 'name' ] : '';
                        if ( empty( $fileName ) )
                        {
                            if ( $required )
                            {
                                throw new Exception( bkntc__('Please fill in all required fields correctly!') );
                            }
                            continue;
                        }
                        $extension = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );
                        if( ! in_array( $extension, $allowedFileFormats ) )
                        {
                            throw new Exception( bkntc__( "File extension '.%s' is not allowed!", [ $extension ] ) );
                        }
                    }
                }
                else
                {
                    if ( $required && ( ! isset( $value[ 'id' ] ) || ! isset( $value[ 'name' ] ) ) )
                    {
                        throw new Exception( bkntc__('Please fill in all required fields correctly!') );
                    }

                    if ( isset( $options[ 'allowed_file_formats' ] ) && ! empty( $options[ 'allowed_file_formats' ] ) && is_string( $options[ 'allowed_file_formats' ] ) )
                    {
                        $allowedFileFormats = Helper::secureFileFormats( explode( ',', str_replace( ' ', '', $options[ 'allowed_file_formats' ] ) ) );
                    }
                    else
                    {
                        $allowedFileFormats = [ 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'zip', 'rar', 'csv', 'mov' ];
                    }

                    $extension = strtolower( pathinfo( $value[ 'name' ], PATHINFO_EXTENSION ) );

                    if( ! in_array( $extension, $allowedFileFormats ) )
                    {
                        throw new Exception( bkntc__( "File extension '.%s' is not allowed!", [ $extension ] ) );
                    }
                }

				continue;
			}

            if( ! is_string( $value ) )
            {
                throw new Exception( bkntc__('Please fill in all required fields correctly!') );
            }

            $label = $field[ 'label' ];

            if( $required && empty( $value ) && strlen( trim( $value ) ) === 0 )
			{
				throw new Exception( bkntc__('Please fill in all required fields correctly!', [ $label ] ) );
			}

            $min = isset( $options[ 'min_length' ] ) ? $options[ 'min_length' ] : 0;
            $max = isset( $options[ 'max_length' ] ) ? $options[ 'max_length' ] : 0;

			if( $required && is_numeric( $min ) && $min > 0 && ! empty( $value ) && mb_strlen( $value, 'UTF-8' ) < $min )
			{
				throw new Exception( bkntc__( 'Minimum length of "%s" field is %d!', [ $label , (int) $min ] ) );
			}

			if( is_numeric( $max ) && $max > 0 && mb_strlen( $value, 'UTF-8' ) > $max )
			{
				throw new Exception( bkntc__('Maximum length of "%s" field is %d!', [ $label , (int) $max ] ) );
			}
		}
    }

    public static function frontend_on_appointment_created( AppointmentRequestData $appointmentObj )
    {
        $custom_fields  =	$appointmentObj->getData('custom_fields', [], 'arr');
        $fileIds = FormInput::where('type', 'IN', ['file', 'file_multiple'])->select(['id'] ,true)->fetchAll();

        $fileIds = array_map( function ($item){
            return $item->id;
        } , $fileIds);

        foreach( $appointmentObj->createdAppointments as $appointmentId )
        {
            foreach( $custom_fields AS $customFieldId => $customFieldValue )
            {
                if( in_array( $customFieldId , $fileIds ) )
                {
                    if (is_array($customFieldValue) && isset($customFieldValue['multiple']) && $customFieldValue['multiple'] == 'true')
                    {
                        $uploadedFiles = [];
                        if (isset($customFieldValue['files']) && is_array($customFieldValue['files']))
                        {
                            foreach ($customFieldValue['files'] as $fileRef)
                            {
                                $fileId = $fileRef['id'];
                                if(! isset($_FILES['custom_files']['name'][ $fileId ])) continue;
                                $customFileName = $_FILES['custom_files']['name'][ $fileId ];

                                $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                                $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                                $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $fileId ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                                if( $_FILES['custom_files']['type'][ $fileId ] === 'image/svg+xml' )
                                {
                                    Helper::svgRemoveScriptTags(Helper::uploadedFile($newFileName,'CustomForms') );
                                }

                                if( $result01 )
                                {
                                    $uploadedFiles[] = [
                                        'path' => $newFileName,
                                        'name' => $customFileName
                                    ];
                                }
                            }
                        }
                        if (!empty($uploadedFiles))
                        {
                            AppointmentCustomData::insert([
                                'appointment_id'    =>  $appointmentId,
                                'form_input_id'		=>	$customFieldId,
                                'input_value'		=>	json_encode($uploadedFiles),
                                'input_file_name'	=>	'multiple_files'
                            ]);
                        }
                    }
                    else
                    {
                        $fileId = is_array($customFieldValue) && isset($customFieldValue['id']) ? $customFieldValue['id'] : $customFieldValue;
                        if(! isset($_FILES['custom_files']['name'][ $fileId ])) continue;
                        $customFileName = $_FILES['custom_files']['name'][ $fileId ];

                        $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                        $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                        $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $fileId ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                        if( $_FILES['custom_files']['type'][ $fileId ] === 'image/svg+xml' )
                        {
                            Helper::svgRemoveScriptTags(Helper::uploadedFile($newFileName,'CustomForms') );
                        }

                        if( $result01 )
                        {
                            AppointmentCustomData::insert([
                                'appointment_id'    =>  $appointmentId,
                                'form_input_id'		=>	$customFieldId,
                                'input_value'		=>	$newFileName,
                                'input_file_name'	=>	$customFileName
                            ]);
                        }
                    }

                    continue;
                }
                AppointmentCustomData::insert([
                    'appointment_id'    =>  $appointmentId,
                    'form_input_id'		=>	$customFieldId,
                    'input_value'		=>	$customFieldValue
                ]);
            }
        }
    }

	public static function backend_add_info_tab( $appointmentId )
	{
        $fields = AppointmentCustomData::where('appointment_id', $appointmentId)
            ->leftJoin('form_input', ['label', 'type', 'form_id'])
            ->fetchAll();

        $forms = [];

        foreach ($fields as $field)
        {
            $formId = $field['form_input_form_id'];
            if (!array_key_exists($formId, $forms))
            {
                $forms[$formId] = [
                    'form' => Form::get($formId),
                    'inputs' => []
                ];
            }

            $forms[$formId]['inputs'][] = $field;
        }

        return $forms;
	}

    public static function formsExportCsv( $data, $dataTable )
    {
        if( $dataTable->getModule() !== 'appointments' || $dataTable->getExportCSV() !== true )
            return $data;

        $offset = count( $data['thead'] ) - 1;

        $rawFields = AppointmentCustomData::where( 'appointment_id', 'IN' , array_column( $data[ 'tbody' ], 'id' ) )
            ->leftJoin('form_input', [ 'label', 'type', 'form_id' ] )
            ->fetchAll();

        $fields = Helper::assocByKey( $rawFields, 'appointment_id', true );

        $thead = [];
        $dataToReplace = [];
        foreach ( $rawFields AS $field )
        {
            if ( !isset( $thead[ $field[ 'form_input_id' ] ] ) )
            {
                $thead[ $field[ 'form_input_id' ] ] = [
                    'name' => $field[ 'form_input_label' ],
                    'is_sortable' => true,
                    'order_by_field' => strtolower( $field[ 'form_input_label' ] ),
                ];

                $dataToReplace[ $field[ 'form_input_id' ] ] = [
                    'content'=> '-' ,
                    'attributes'=>[]
                ];
            }
        }
        array_splice($data['thead'] , $offset , 0 , $thead );


        foreach ( $data['tbody'] as $key => $row )
        {
            $replacedData = $dataToReplace;

            if ( isset( $fields[ $row[ 'id' ] ] ) )
            {
                foreach ( $fields[ $row[ 'id' ] ] as $field )
                    {
                        switch ( $field['form_input_type'] )
                        {
                            case 'file':
                                $value = $field['input_file_name'];
                                break;
                            case in_array( $field['form_input_type'], ['select', 'checkbox', 'radio'] ):
                                $value = FormInputChoice::whereFindInSet('id', explode(',', $field[ 'input_value' ] ) )->select('group_concat(title separator \', \') as titles', true)->fetch()[ 'titles' ];
                                break;
                            case 'date':
                                $value = Date::datee( Date::reformatDateFromCustomFormat( htmlspecialchars( $field[ 'input_value' ] ) ) );
                                break;
                            case 'time':
                                $value = Date::time( htmlspecialchars( $field[ 'input_value' ] ) );
                                break;
                            default:
                                $value = $field[ 'input_value' ];
                        }

                        $replacedData[ $field[ 'form_input_id' ] ][ 'content' ] = $value;
                    }
            }

            array_splice( $data[ 'tbody' ][ $key ][ 'data' ] , $offset,0, array_values( $replacedData ) );
        }

        return $data;
    }

    public static function backend_validate()
	{
        //todo:// burada ne bash verir arashdirmaq lazimdi

        // post'dan birbasa fieldlari goturur, multibookingde bele olmayacaq
        // frontend_validate ile de birlesdirmek lazimdir bu funksiyani
        return;

		$customers			=	Helper::_post('customers', '', 'string');
		$customers			=	json_decode($customers, true);

		$customFields		=	Helper::_post('custom_fields', [], 'array');
		$save_custom_data	=	Helper::_post('save_custom_data', '', 'string');

		$customFiles        = isset($_FILES['custom_fields']) ? $_FILES['custom_fields']['tmp_name'] : [];

		$save_custom_data_ids_concat = [];
		$save_custom_data = json_decode( $save_custom_data, true );

		$service			=	Helper::_post('service', 0, 'integer');
		foreach ( $save_custom_data AS $custom_datum )
		{
			if( is_array( $custom_datum )
				&& isset($custom_datum[0]) && is_numeric($custom_datum[0]) && $custom_datum[0] > 0
				&& isset($custom_datum[1]) && is_numeric($custom_datum[1]) && $custom_datum[1] > 0
			)
			{
				$save_custom_data_ids_concat[] = (int)$custom_datum[1] . ':' . (int)$custom_datum[0];
			}
		}

		$getFormId = DB::DB()->get_row( DB::DB()->prepare( 'SELECT id FROM `'.DB::table('forms').'` WHERE FIND_IN_SET(%d, service_ids) '.DB::tenantFilter().' LIMIT 0,1', [ $service ] ), ARRAY_A );

		if( $getFormId )
		{
			$curFormId = (int)$getFormId['id'];

			$getRequiredFilesFields = FormInput::where('is_required', '1')->where('form_id', $curFormId)->where('type', 'file')->fetchAll();

			foreach ( $getRequiredFilesFields AS $fieldInf )
			{
				foreach ( $customers AS $customerInf )
				{
					if(  in_array( $customerInf[ 'id' ] . ':' . $fieldInf[ 'id' ], $save_custom_data_ids_concat ) )
					{
						continue;
					}

					if( !isset( $customFiles[ $customerInf['id'] ][ $fieldInf['id'] ] ) && !in_array( $customerInf['id'] . ':' . (string)$fieldInf['id'], $save_custom_data_ids_concat ) )
					{
						throw new Exception( bkntc__('%s can not be empty, because it\'s a required field!', [ $fieldInf['label'] ]) );
					}
				}
			}

            $getRequiredCheckboxFields = FormInput::where('is_required', '1')->where('form_id', $curFormId)->where('type', 'checkbox')->fetchAll();

            foreach ( $getRequiredCheckboxFields AS $fieldInf )
            {
                foreach ( $customers AS $customerInf )
                {
                    if(  in_array( $customerInf[ 'id' ] . ':' . $fieldInf[ 'id' ], $save_custom_data_ids_concat ) )
                    {
                        continue;
                    }

                    if( !isset( $customFields[ $customerInf['id'] ][ $fieldInf['id'] ] ) )
                    {
	                    throw new Exception( bkntc__('%s can not be empty, because it\'s a required field!', [ $fieldInf['label'] ]) );
                    }
                }
            }

		}

		foreach( $customFields AS $customerId => $customFieldData )
		{
			if( !is_numeric( $customerId ) || !is_array( $customFieldData ) )
			{
				throw new Exception( bkntc__('Please fill custom fields form correctly!') );
			}


			foreach ( $customFieldData AS $customFieldId => $customFieldValue )
			{
				if( !( is_numeric($customFieldId) && $customFieldId > 0 && is_string( $customFieldValue ) ) )
				{
					throw new Exception( bkntc__('Please fill custom fields form correctly!') );
				}

				$customFieldInf = FormInput::get( $customFieldId );

				if( !$customFieldInf )
				{
					throw new Exception( bkntc__('Selected custom field not found!') );
				}

				if( $customFieldInf['type'] == 'file' )
				{
					continue;
				}

				$isRequired = (int)$customFieldInf['is_required'];

				if( $isRequired && empty( $customFieldValue ) )
				{
					throw new Exception( bkntc__('"%s" can not be empty, because it\'s a required field!', [ $customFieldInf['label'] ]) );
				}

				$options = $customFieldInf['options'];
				$options = json_decode( $options, true );

				if( $isRequired && isset( $options['min_length'] ) && is_numeric( $options['min_length'] ) && $options['min_length'] > 0 && !empty( $customFieldValue ) && mb_strlen( $customFieldValue, 'UTF-8' ) < $options['min_length'] )
				{
					throw new Exception( bkntc__('Minimum length of "%s" field is %d!', [ $customFieldInf['label'] , (int)$options['min_length'] ]) );
				}

				if( isset( $options['max_length'] ) && is_numeric( $options['max_length'] ) && $options['max_length'] > 0 && mb_strlen( $customFieldValue, 'UTF-8' ) > $options['max_length'] )
				{
					throw new Exception( bkntc__('Maximum length of "%s" field is %d!', [ $customFieldInf['label'] , (int)$options['max_length'] ]) );
				}
			}
		}

		foreach( $customFiles AS $customerId => $customFieldData )
		{
			if( !is_numeric( $customerId ) || !is_array( $customFieldData ) )
			{
				throw new Exception( bkntc__('Please fill custom fields form correctly!') );
			}

			foreach( $customFieldData AS $customFieldId => $customFieldValue )
			{
				if( in_array( $customerId . ':' . $customFieldId, $save_custom_data_ids_concat ) )
				{
					continue;
				}

				if( !( is_numeric($customFieldId) && $customFieldId > 0 && is_string( $customFieldValue ) ) )
				{
					throw new Exception( bkntc__('Please fill custom fields form correctly!') );
				}

				$customFieldInf = FormInput::get( $customFieldId );

				if( !$customFieldInf || ( $customFieldInf['type'] != 'file' && $customFieldInf['type'] != 'file_multiple' ) )
				{
					throw new Exception( bkntc__('Selected custom field not found!') );
				}

				$isRequired = (int)$customFieldInf['is_required'];
				$options = json_decode( $customFieldInf['options'], true );

				if( isset( $options['allowed_file_formats'] ) && !empty( $options['allowed_file_formats'] ) && is_string( $options['allowed_file_formats'] ) )
				{
					$allowedFileFormats = Helper::secureFileFormats( explode(',', str_replace(' ', '', $options['allowed_file_formats'])) );
				}
				else
				{
					$allowedFileFormats = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'zip', 'rar', 'csv'];
				}

				if( $isRequired && empty( $customFieldValue ) )
				{
					throw new Exception( bkntc__('%s can not be empty, because it\'s a required field!', [ $customFieldInf['label'] ]) );
				}

                if ($customFieldInf['type'] == 'file_multiple')
                {
                    $fileKey = isset($_FILES['custom_fields']['name'][ $customerId ][ $customFieldId ]) ? 'custom_fields' : (isset($_FILES['custom_files']['name'][ $customFieldId ]) ? 'custom_files' : null);
                    if ($fileKey !== null)
                    {
                        $fileNames = $fileKey == 'custom_fields' ? $_FILES['custom_fields']['name'][ $customerId ][ $customFieldId ] : $_FILES['custom_files']['name'][ $customFieldId ];
                        if (!is_array($fileNames)) {
                            $fileNames = [$fileNames];
                        }
                        foreach ($fileNames as $customFileName) {
                            $extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                            if( !in_array( $extension, $allowedFileFormats ) )
                            {
                                throw new Exception( bkntc__('File extension is not allowed!') );
                            }
                        }
                    }
                }
                else
                {
                    $customFileName = isset($_FILES['custom_fields']['name'][ $customerId ][ $customFieldId ]) ? $_FILES['custom_fields']['name'][ $customerId ][ $customFieldId ] : (isset($_FILES['custom_files']['name'][ $customFieldValue ]) ? $_FILES['custom_files']['name'][ $customFieldValue ] : '');
                    if (!empty($customFileName)) {
                        $extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                        if( !in_array( $extension, $allowedFileFormats ) )
                        {
                            throw new Exception( bkntc__('File extension is not allowed!') );
                        }
                    }
                }
			}
		}
	}

	public static function backend_on_appointment_edited( AppointmentRequestData $appointmentObj )
	{
		$customFields		= $appointmentObj->getData('custom_fields');
        $fileIds = FormInput::where('type', 'IN', ['file', 'file_multiple'])->select(['id'] ,true)->fetchAll();

        $fileIds = array_map( function ($item){
            return $item->id;
        } , $fileIds);

        if (empty($customFields) || !is_array($customFields))
            return;

		foreach( $customFields AS $customFieldId => $customFieldValue )
		{
            $row = AppointmentCustomData::where('appointment_id', $appointmentObj->appointmentId)->where('form_input_id', $customFieldId)->fetch();
            if ($row)
            {
                if ( in_array($row->form_input_id, $fileIds) )
                {
                    $files = json_decode($row['input_value'], true);
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            @unlink(Helper::uploadedFile( $file['path'], 'CustomForms' ));
                        }
                    } else {
                        @unlink(Helper::uploadedFile( $row['input_value'], 'CustomForms' ));
                    }
                }
                AppointmentCustomData::where('id', $row->id)->delete();
            }

            if( in_array($customFieldId,$fileIds))
            {
                if (is_array($customFieldValue) && isset($customFieldValue['multiple']) && $customFieldValue['multiple'] == 'true')
                {
                    $uploadedFiles = isset($customFieldValue['remaining']) && is_array($customFieldValue['remaining']) ? $customFieldValue['remaining'] : [];
                    if (isset($customFieldValue['new_files']) && is_array($customFieldValue['new_files']))
                    {
                        foreach ($customFieldValue['new_files'] as $fileRef)
                        {
                            $fileId = $fileRef['id'];
                            if(! isset($_FILES['custom_files']['name'][ $fileId ])) continue;
                            $customFileName = $_FILES['custom_files']['name'][ $fileId ];

                            $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                            $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                            $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $fileId ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                            if( $result01 )
                            {
                                $uploadedFiles[] = [
                                    'path' => $newFileName,
                                    'name' => $customFileName
                                ];
                            }
                        }
                    }
                    if (!empty($uploadedFiles))
                    {
                        AppointmentCustomData::insert([
                            'appointment_id'    =>  $appointmentObj->appointmentId,
                            'form_input_id'		=>	$customFieldId,
                            'input_value'		=>	json_encode($uploadedFiles),
                            'input_file_name'	=>	'multiple_files'
                        ]);
                    }
                }
                else
                {
                    if(! isset($_FILES['custom_files']['name'][ $customFieldValue ])) continue;
                    $customFileName = $_FILES['custom_files']['name'][ $customFieldValue ];

                    $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                    $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                    $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $customFieldValue ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                    if( $result01 )
                    {
                        AppointmentCustomData::insert([
                            'appointment_id'    =>  $appointmentObj->appointmentId,
                            'form_input_id'		=>	$customFieldId,
                            'input_value'		=>	$newFileName,
                            'input_file_name'	=>	$customFileName
                        ]);
                    }
                }

                continue;
            }

            AppointmentCustomData::insert([
                'appointment_id'    =>  $appointmentObj->appointmentId,
                'form_input_id'		=>	$customFieldId,
                'input_value'		=>	$customFieldValue
            ]);
		}

	}

    public static function backend_on_appointment_created( AppointmentRequestData $appointmentObj )
    {
        $customFields		= $appointmentObj->getData('custom_fields');
        $fileIds = FormInput::where('type', 'IN', ['file', 'file_multiple'])->select(['id'] ,true)->fetchAll();

        $fileIds = array_map( function ($item){
            return $item->id;
        } , $fileIds);

        if (empty($customFields) || !is_array($customFields))
            return;

        foreach( $customFields AS $customFieldId => $customFieldValue )
        {
            if( in_array( $customFieldId , $fileIds ) )
            {
                if (is_array($customFieldValue) && isset($customFieldValue['multiple']) && $customFieldValue['multiple'] == 'true')
                {
                    $uploadedFiles = [];
                    if (isset($customFieldValue['new_files']) && is_array($customFieldValue['new_files']))
                    {
                        foreach ($customFieldValue['new_files'] as $fileRef)
                        {
                            $fileId = $fileRef['id'];
                            if(! isset($_FILES['custom_files']['name'][ $fileId ])) continue;
                            $customFileName = $_FILES['custom_files']['name'][ $fileId ];

                            $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                            $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                            $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $fileId ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                            if( $result01 )
                            {
                                $uploadedFiles[] = [
                                    'path' => $newFileName,
                                    'name' => $customFileName
                                ];
                            }
                        }
                    }
                    if (!empty($uploadedFiles))
                    {
                        AppointmentCustomData::insert([
                            'appointment_id'    =>  $appointmentObj->appointmentId,
                            'form_input_id'		=>	$customFieldId,
                            'input_value'		=>	json_encode($uploadedFiles),
                            'input_file_name'	=>	'multiple_files'
                        ]);
                    }
                }
                else
                {
                    if(! isset($_FILES['custom_files']['name'][ $customFieldValue ])) continue;
                    $customFileName = $_FILES['custom_files']['name'][ $customFieldValue ];

                    $extension      = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
                    $newFileName    = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

                    $result01       = move_uploaded_file( $_FILES['custom_files']['tmp_name'][ $customFieldValue ], Helper::uploadedFile( $newFileName, 'CustomForms' ) );

                    if( $result01 )
                    {
                        AppointmentCustomData::insert([
                            'appointment_id'    =>  $appointmentObj->appointmentId,
                            'form_input_id'		=>	$customFieldId,
                            'input_value'		=>	$newFileName,
                            'input_file_name'	=>	$customFileName
                        ]);
                    }
                }

                continue;
            }

            AppointmentCustomData::insert([
                'appointment_id'    =>  $appointmentObj->appointmentId,
                'form_input_id'		=>	$customFieldId,
                'input_value'		=>	$customFieldValue
            ]);
        }
    }

    public static function shared_on_appointment_deleted( $appointmentId )
    {
        $filesToUnlink = AppointmentCustomData::innerJoin(FormInput::class, ['type'], AppointmentCustomData::getField('form_input_id'), FormInput::getField('id'))
            ->where(AppointmentCustomData::getField('appointment_id'), $appointmentId)
            ->where(FormInput::getField('type'), 'IN', ['file', 'file_multiple'])
            ->select(AppointmentCustomData::getField('input_value'), true)
            ->fetchAll();

        foreach ($filesToUnlink as $fileToUnlink)
        {
            $files = json_decode($fileToUnlink['input_value'], true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    @unlink(Helper::uploadedFile( $file['path'], 'CustomForms' ));
                }
            } else {
                @unlink(Helper::uploadedFile( $fileToUnlink['input_value'], 'CustomForms' ));
            }
        }

        AppointmentCustomData::where('appointment_id', $appointmentId)->delete();
    }

	public static function replace_short_code_text( $text, $data )
	{
		if( ! isset( $data['appointment_id'] ) )
			return $text;

		$text = preg_replace_callback('/{appointment_custom_field_([0-9]+)}/', function ( $found ) use ( $data )
		{

			if( !isset( $found[1] ) )
				return $found[0];

			return self::getCustomFieldValue( $found[1], false, $data );

		}, $text);

		$text = preg_replace_callback('/{appointment_custom_field_([0-9]+)_url}/', function ( $found ) use ( $data )
		{

			if( !isset( $found[1] ) )
				return $found[0];

			return self::getCustomFieldValue( $found[1], 'url', $data );

		}, $text);

        $text = preg_replace_callback('/{appointment_custom_field_([0-9]+)_path}/', function ( $found ) use ( $data )
        {

            if( !isset( $found[1] ) )
                return $found[0];

            return self::getCustomFieldValue( $found[1], 'path', $data );

        }, $text);

		return $text;
	}

    public static function registerShortCodes($shortCodeService)
    {
        $forms = Form::fetchAll();

        foreach ($forms as $form)
        {
            $formInputs = FormInput::where('form_id', $form['id'])
                ->where('type', 'not in', ['label', 'link'])
                ->fetchAll();

            foreach ($formInputs as $formInput)
            {
                $shortCodeService->registerShortCode( 'appointment_custom_field_' . $formInput->id, [
                    'name'      =>  bkntc__('Custom field') . ': ' . $form->name . ' > ' . $formInput->label,
                    'category'  =>  'appointment_info',
                    'depends'   =>  'appointment_id',
                    'kind'      =>  in_array($formInput->type, ['email', 'phone']) ? $formInput->type : ''
                ]);

                if( $formInput->type == 'file' || $formInput->type == 'file_multiple' )
                {
                    $shortCodeService->registerShortCode( 'appointment_custom_field_' . $formInput->id . '_url', [
                        'name'      =>  bkntc__('Custom field') . ': ' . $form->name . ' > ' . $formInput->label . ' [URL]',
                        'category'  =>  'appointment_info',
                        'depends'   =>  'appointment_id',
                        'kind'      =>  'url'
                    ]);

                    $shortCodeService->registerShortCode( 'appointment_custom_field_' . $formInput->id . '_path', [
                        'name'      =>  bkntc__('Custom field') . ': ' . $form->name . ' > ' . $formInput->label . ' [PATH]',
                        'category'  =>  'appointment_info',
                        'depends'   =>  'appointment_id',
                        'kind'      =>  'file'
                    ]);
                }
            }
        }

    }

	private static function getCustomFieldValue( $cf_id, $fileUrl, $data )
	{
		$appointmentSo = AppointmentSmartObject::load( $data['appointment_id'] );

        $appointmentId = $appointmentSo->getInfo()->id;

        $val = AppointmentCustomData::where('appointment_id', $appointmentId)->where('form_input_id', $cf_id)->fetch();

		if( empty($val) )
		{
			return '';
		}

        $form_input = FormInput::where('id', $cf_id)->fetch();

        if( $form_input['type'] == 'file' || $form_input['type'] == 'file_multiple' )
		{
            $files = json_decode($val['input_value'], true);
            if (is_array($files)) {
                $result = [];
                foreach ($files as $file) {
                    if( $fileUrl == 'url' )
                    {
                        $result[] = Helper::uploadedFileURL( htmlspecialchars($file['path']), 'CustomForms');
                    }
                    else if ($fileUrl == 'path')
                    {
                        $result[] = Helper::uploadedFile($file['path'], 'CustomForms');
                    }
                    else
                    {
                        $result[] = $file['name'];
                    }
                }
                return implode(', ', $result);
            } else {
                if( $fileUrl == 'url' )
                {
                    return Helper::uploadedFileURL( htmlspecialchars($val['input_value']), 'CustomForms');
                }
                else if ($fileUrl == 'path')
                {
                    return Helper::uploadedFile($val['input_value'], 'CustomForms');
                }
                else
                {
                    return $val['input_file_name'];
                }
            }
		}
        else if ( $form_input[ 'type' ] == 'date' )
        {
            return Date::datee( $val[ 'input_value' ] );
        }
        else if ( $form_input[ 'type' ] == 'time' )
        {
            return Date::time( $val[ 'input_value' ] );
        }
        else if ( in_array( $form_input[ 'type' ], ['select', 'checkbox', 'radio'] ))
        {
            $realValues = FormInputChoice::whereFindInSet('id', explode(',', $val['input_value']))->select('group_concat(title separator \', \') as titles', true)->fetch();
            return $realValues['titles'];
        }
		else
		{
			return $val['input_value'];
		}
	}

    public static function beforeTenantDelete($tenantId)
    {
        // Info: foreign key on delete cascade is used
        Form::noTenant()->where('tenant_id', $tenantId)->delete();
    }

    public static function initConditionalFields( $fields )
    {
        $formInputs = FormInput::select( [ 'id', 'type', 'label' ] )
            ->where( 'form_id', Form::select( 'id' ) )
            ->fetchAll();

        foreach( $formInputs as $formInput )
        {
            if ( in_array( $formInput[ 'type' ], [ 'radio', 'checkbox', 'select' ] ) )
            {
                $fields[ 'custom_field_' . $formInput[ 'id' ] ] = [
                    'text' => 'Custom Field ' . $formInput[ 'label' ],
                    'type' => 'multiselect',
                    'fetch' => [ ConditionalFields::class, 'fetchChoiceFields' ],
                    'appointmentValue' => function( $appointmentObj ) use ( $formInput )
                    {
                        $customFields = $appointmentObj->getData( 'custom_fields' );

                        if ( ! isset( $customFields[ $formInput[ 'id' ] ] ) )
                        {
                            return false;
                        }

                        return trim( $customFields[ $formInput[ 'id' ] ] );
                    }
                    ];
            }
            else
            {
                $fields[ 'custom_field_' . $formInput[ 'id' ] ] = [
                    'text' => 'Custom Field ' . $formInput[ 'label' ],
                    'type' => 'input',
                    'fetch' => [ ConditionalFields::class, 'fetchInputFields' ],
                    'appointmentValue' => function( $appointmentObj ) use ( $formInput )
                    {
                        $customFields = $appointmentObj->getData( 'custom_fields' );

                        if ( ! isset( $customFields[ $formInput[ 'id' ] ] ) )
                        {
                            return false;
                        }

                        $result = $customFields[ $formInput[ 'id' ] ];

                        if ( ! is_array( $customFields[ $formInput[ 'id' ] ] ) )
                        {
                            $result = trim( $result );
                        }

                        return $result;
                    },
                ];
            }
        }

        return $fields;

    }

}
