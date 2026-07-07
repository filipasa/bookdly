<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property int         $id
 * @property string      $table_name
 * @property string      $column_name
 * @property int         $row_id
 * @property string|null $locale
 * @property string|null $value
 * @property int|null    $tenant_id
 */
class Translation extends Model
{
    use MultiTenant;
    protected static $tableName = "translations";
}
