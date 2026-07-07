<?php

namespace BookneticAddon\Tax\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\Translation\Translator;

/**
 * @property int id
 * @property string name
 * @property string type
 * @property float value
 * @property string locations
 * @property int is_active
 * @property int tenant_id
 */
class Tax extends Model
{
	use MultiTenant, Translator;

    protected static $translations = [ 'name' ];
    static $tableName = 'taxes';

}
