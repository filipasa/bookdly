<?php

namespace BookneticAddon\Customforms\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\Translation\Translator;

class FormInput extends Model
{

    use Translator;

    protected static $translations = [ 'label', 'help_text' ];

	public static $relations = [
		'choices'   =>  [ FormInputChoice::class ],
		'form'      =>  [ Form::class, 'id', 'form_id' ]
	];

}