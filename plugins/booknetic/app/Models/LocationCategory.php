<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Auditable;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\Translation\Translator;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $tenant_id
 */
class LocationCategory extends Model
{
    use MultiTenant, Translator, Auditable {
        MultiTenant::booted as private tenantBoot;
        Auditable::booted as private audBoot;
    }

    protected static $tableName = 'location_categories';

    protected static $translations = [ 'name' ];

    public static function booted()
    {
        self::tenantBoot();
        self::audBoot();
    }
}
