<?php

namespace BookneticAddon\Tenantdirectory\Model;

use BookneticApp\Providers\DB\Model;

/**
 * @property int $directory_id
 * @property int $keyword_id
 */
class TenantDirectoryKeyword extends Model
{
    protected static $tableName = 'tenant_directory_keywords';

    protected static $isTblHeader = false;
}
?>
