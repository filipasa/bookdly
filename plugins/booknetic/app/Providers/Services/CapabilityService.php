<?php

namespace BookneticApp\Providers\Services;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Attributes\Service;

#[Service]
class CapabilityService
{
    private array $limits = [];
    public function getLimit(string $limit): int
    {
        if (! isset($this->limits[ $limit ])) {
            return -1;
        }

        if (Helper::isSaaSVersion() && !Permission::tenantInf()) {
            return -1;
        }

        return apply_filters('bkntc_capability_limit_filter', -1, $limit);
    }

    public function registerLimit($limit, $title): void
    {
        $this->limits[ $limit ] = [
            'title'     =>  $title
        ];
    }
}
