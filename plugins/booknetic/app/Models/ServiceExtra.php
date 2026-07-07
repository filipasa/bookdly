<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\Translation\Translator;

/**
 * @property-read int $id
 * @property-read int $service_id
 * @property-read string $name
 * @property-read string $image
 * @property-read float $price
 * @property-read int $duration
 * @property-read int $max_quantity
 * @property-read int $is_active
 * @property-read int $hide_duration
 * @property-read int $hide_price
 * @property-read int $min_quantity
 * @property-read string $notes
 * @property-read int $category_id
 * @property-read int $tenant_id
 */
class ServiceExtra extends Model
{
    use MultiTenant;
    use Translator;

    protected static $translations = [ 'name', 'notes' ];
}
