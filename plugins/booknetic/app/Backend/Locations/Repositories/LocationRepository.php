<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Backend\Base\Repository\DataRepository;
use BookneticApp\Backend\Base\Repository\TranslationRepository;
use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class LocationRepository
{
    private DataRepository $dataRepository;
    private TranslationRepository $translationRepository;

    public function __construct(DataRepository $dataRepository, TranslationRepository $translationRepository)
    {
        $this->dataRepository = $dataRepository;
        $this->translationRepository = $translationRepository;
    }

    /**
     * @param int $id
     *
     * @return Location|Collection|null
     * */
    public function get(int $id): ?Collection
    {
        return Location::query()->get($id);
    }

    public function count(): int
    {
        return Location::query()->count();
    }

    public function deleteAll(array $ids): void
    {
        Location::query()->where('id', 'in', $ids)->delete();
    }

    public function delete(int $id): void
    {
        Location::query()->where('id', $id)->delete();
    }

    public function updateAll(array $ids, array $data)
    {
        Location::query()->where('id', 'in', $ids)
                ->update($data);
    }

    public function update(int $id, array $data, string $translations = ''): void
    {
        Location::query()->where('id', $id)
                ->update($data);

        if ($translations !== '') {
            Location::handleTranslation($id, $translations);
        }
    }

    public function create(array $data, string $translations = ''): int
    {
        Location::query()->insert($data);

        $id = Location::lastId();

        if ($translations !== '') {
            Location::handleTranslation($id, $translations);
        }

        return $id;
    }

    /**
     * @param array $ids
     * @return array<Location|Collection>
     */
    public function getAll(array $ids): array
    {
        return Location::query()->where('id', 'in', $ids)
                       ->fetchAll();
    }

    public function duplicateTranslations(int $oldId, int $newId): void
    {
        $this->translationRepository->duplicateForTable(Location::getTableName(), $oldId, $newId);
    }

    public function duplicateData(int $oldId, int $newId): void
    {
        $this->dataRepository->duplicateForTable(Location::getTableName(), $oldId, $newId);
    }

    /**
     * @param string $search
     * @return Location[]
     */
    public function getMyAllEnabledLocations(string $search): array
    {
        return Location::my()->where('is_active', 1)
        ->where('name', 'LIKE', '%' . $search . '%')
        ->fetchAll();
    }

    /**
     * @param   array  $ids
     *
     * @return array<Location|Collection>
     */
    public function getAllByIds(array $ids): array
    {
        return Location::query()->where('id', 'in', $ids)->fetchAll();
    }
}
