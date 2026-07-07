<?php

namespace BookneticAddon\Tenantdirectory\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property int $business_type_id
 * @property string $price_range_type
 * @property float $price_min
 * @property float $price_max
 * @property string $price_level
 * @property string $gallery
 * @property string $contact_email
 * @property string $contact_phone
 * @property string $social_links
 * @property string $description
 * @property string $status
 * @property string $review_notes
 * @property string $created_at
 * @property string $updated_at
 */
class TenantDirectory extends Model
{
    use MultiTenant;

    protected static $tableName = 'tenant_directory';

    protected static bool $timeStamps = true;

    public static $relations = [
        'business_type' => [ BusinessType::class, 'id', 'business_type_id' ]
    ];
}
?>
