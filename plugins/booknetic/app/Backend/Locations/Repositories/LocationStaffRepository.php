<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class LocationStaffRepository
{
    public function getStaffCount(array $ids): int
    {
        return Staff::query()
            ->where('locations', 'in', $ids)
            ->count();
    }

    public function deleteLocations(array $ids): void
    {
        //todo://Bunu QueryBuilder ile etmek lazimdi
        foreach ($ids as $id) {
            $statement = DB::DB()->prepare("UPDATE `" . DB::table('staff') . "` SET locations=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`locations`,','),%s,',')) WHERE FIND_IN_SET(%d, `locations`)", [
                ",$id,",
                $id
            ]);

            DB::DB()->query($statement);
        }
    }
}
