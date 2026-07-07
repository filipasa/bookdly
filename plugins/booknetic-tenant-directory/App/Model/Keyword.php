<?php

namespace BookneticAddon\Tenantdirectory\Model;

use BookneticApp\Providers\DB\Model;

/**
 * @property int $id
 * @property string $name
 */
class Keyword extends Model
{
    protected static $tableName = 'tenant_keywords';
}
?>
