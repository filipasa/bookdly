<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\QueryBuilder;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $action_type
 * @property string $action_data
 * @property string $read_at
 * @property string $created_at
 * @property string $updated_at
 */
class Notification extends Model
{
    use MultiTenant {
        MultiTenant::booted as private tenantBoot;
    }
    protected static bool $timeStamps = true;

    public static function booted(): void
    {
        self::tenantBoot();

        self::addGlobalScope('user_id', function (QueryBuilder $builder) {
            $builder->where('user_id', get_current_user_id());
        });
    }
}
