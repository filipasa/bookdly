<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class LocationRepository // TODO: check with BookneticApp\Backend\Locations\Repositories\LocationRepository
{
    /**
     * @return array<Location|Collection>
     */
    public function getAllForCurrentUser(): array
    {
        return Location::my()->select([
            'id', 'name'
        ])->fetchAll();
    }
}
