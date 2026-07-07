<?php

namespace BookneticAddon\Tenantdirectory\Model;

use BookneticApp\Providers\DB\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $sort_number
 * @property string $created_at
 */
class BusinessType extends Model
{
    protected static $tableName = 'business_types';
}
?>
