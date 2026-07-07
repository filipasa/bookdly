<?php

namespace BookneticAddon\Customforms\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

class Form extends Model
{
	use MultiTenant;

	public static $relations = [
		'inputs'    =>  [ FormInput::class, 'form_id', 'id' ]
	];

}