<?php

namespace BookneticAddon\Customforms\Backend;

use BookneticAddon\Customforms\Model\Form;
use BookneticAddon\Customforms\Model\FormInput;
use BookneticAddon\Customforms\Model\FormInputChoice;
use BookneticApp\Models\Service;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Customforms\bkntc__;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		$dataTable = new DataTableUI( new Form() );

        $dataTable->addAction('edit', bkntc__('Edit'));
        if (Capabilities::userCan('custom_forms_delete'))
        {
            $dataTable->addAction('delete', bkntc__('Delete'), [static::class , '_delete'], DataTableUI::ACTION_FLAG_BULK_SINGLE );
        }
		$dataTable->setTitle(bkntc__('Custom forms'));
		$dataTable->addNewBtn(bkntc__('CREATE NEW FORM'));

		$dataTable->searchBy( [ 'name' ] );

		$dataTable->addColumns(bkntc__('NAME'), 'name');
        $dataTable->addColumns(bkntc__('ELEMENTS'), function ( $info )
        {
            return FormInput::where('form_id', $info->id)->count();
        });
        $dataTable->addColumns(bkntc__('CONDITIONS'), function ( $info )
        {
            $conditions = json_decode( $info->conditions, true );
            return count( is_array( $conditions ) ? $conditions : [] );
        });

		$table = $dataTable->renderHTML();

        add_filter('bkntc_localization' , function ($localization) {
            $localization['Edit'] = bkntc__('Edit');
            return $localization;
        });

		$this->view( 'index', ['table' => $table] );
	}

	public function edit()
	{
		$formId = Helper::_get('form_id', null, 'int');

		if( $formId > 0 )
		{
		    Capabilities::must('custom_forms_edit');
			$formInf = Form::where('id', $formId)->fetch();

			if( !$formInf )
			{
				header('Location: admin.php?page=' . Helper::getSlugName() . '&module=customforms');
				exit();
			}

			$formInputs = FormInput::where('form_id', $formId)->orderBy('order_number')->fetchAll();

			foreach ( $formInputs AS $fKey => $formInput )
			{
				$formInputs[ $fKey ] = $formInputs[ $fKey ]->toArray();

				if( in_array( $formInput['type'], ['select', 'checkbox', 'radio'] ) )
				{
					$choicesList = FormInputChoice::where('form_input_id', (int)$formInput['id'])->orderBy('order_number')->fetchAll();

					$formInputs[ $fKey ]['choices'] = [];

					foreach( $choicesList AS $choiceInf )
					{
						$formInputs[ $fKey ]['choices'][] = [ (int)$choiceInf['id'], htmlspecialchars( $choiceInf['title'] ) ];
					}
				}
			}

            $formInf['conditions'] = json_decode( $formInf['conditions'], true );
            if( empty( $formInf['conditions'] ) || ! is_array( $formInf['conditions'] ) )
            {
                $formInf['conditions'] = [];
            }
		}
		else
		{
            Capabilities::must('custom_forms_add');

            $formInf	= [
				'id'            =>  null,
				'name'          =>  null,
				'service_ids'   =>  null,
                'conditions'    =>  []
			];
			$formInputs	= [];
		}

		$services = Service::fetchAll();

		$this->view( 'edit_form', [
			'id'		=>	$formId,
			'form'		=>	$formInf,
			'inputs'	=>	$formInputs,
			'services'	=>	$services
		] );
	}

    public static function _delete( $deleteIDs )
    {
        foreach ( $deleteIDs as $id )
        {
            DB::DB()->query("DELETE FROM `".DB::table('appointment_custom_data')."` WHERE form_input_id IN (SELECT id FROM `".DB::table('form_inputs')."` WHERE form_id = $id)");
            DB::DB()->query("DELETE FROM `".DB::table('form_input_choices')."` WHERE form_input_id IN (SELECT id FROM `".DB::table('form_inputs')."` WHERE form_id = $id)");

            FormInput::where( 'form_id', $id )->delete();

            Form::where('id', $id)->delete();
        }
	}

}
