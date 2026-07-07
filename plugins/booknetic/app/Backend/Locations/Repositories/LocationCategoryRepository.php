<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Location;
use BookneticApp\Models\LocationCategory;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class LocationCategoryRepository
{
    /**
     * @param $id
     * @return LocationCategory|Collection|null
     */
    public function get($id): ?Collection
    {
        return LocationCategory::query()->get($id);
    }

    /**
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        LocationCategory::query()->insert($data);

        return LocationCategory::lastId();
    }

    /**
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        LocationCategory::query()
            ->where('id', $id)
            ->update($data);

        LocationCategory::handleTranslation($id);
    }

    /**
     * @param string $name
     * @param int|null $id
     * @return LocationCategory|Collection|null
     */
    public function checkIfNameExist(string $name, ?int $id = null): ?Collection
    {
        $query = LocationCategory::query()
            ->where('name', $name);

        if ($id !== null) {
            $query->where('id', '!=', $id);
        }

        return $query->fetch();
    }

    /**
     * @return QueryBuilder
     */
    public function getTenantQuery(): QueryBuilder
    {
        return LocationCategory::query()
            ->select([
                LocationCategory::getField('id'),
                LocationCategory::getField('name'),
            ]);
    }

    /**
     * @param array $ids
     * @return void
     */
    public function delete(array $ids): void
    {
        LocationCategory::query()
            ->where('id', 'IN', $ids)
            ->delete();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return LocationCategory::query()
            ->select(['id', 'name'])
            ->fetchAll();
    }

    /**
     * @return array
     */
    public function getAllWithTranslations(): array
    {
        return LocationCategory::query()
            ->select(['id', 'name'])
            ->withTranslations()
            ->fetchAll();
    }

    /**
     * @param array $ids
     * @return int
     */
    public function getLocationByCategory(array $ids): int
    {
        return Location::query()
            ->where('category_id', 'IN', $ids)
            ->count();
    }
}
