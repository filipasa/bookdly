<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Backend\Base\Repository\DataRepository;
use BookneticApp\Backend\Base\Repository\TranslationRepository;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class StaffRepository
{
    private DataRepository $dataRepository;
    private TranslationRepository $translationRepository;

    public function __construct(DataRepository $dataRepository, TranslationRepository $translationRepository)
    {
        $this->dataRepository = $dataRepository;
        $this->translationRepository = $translationRepository;
    }

    /**
     * Insert a new staff record.
     *
     * @param array $data
     * @return int inserted ID
     */
    public function insert(array $data): int
    {
        $data['is_active'] = $data['is_active'] ?? 1;

        Staff::query()
            ->insert($data);

        return DB::lastInsertedId();
    }

    /**
     * Update existing staff.
     *
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        Staff::query()
            ->whereId($id)->update($data);
    }

    /**
     * Delete staff by ID.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        Staff::query()
            ->whereId($id)->delete();
    }

    /**
     * @param int $id
     * @return Staff|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return Staff::query()->whereId($id)->fetch();
    }

    /**
     * Delegates translation handling to the model trait.
     *
     * @param int   $staffId
     * @param array|string $translations
     */
    public function handleTranslation(int $staffId, $translations): void
    {
        if (is_array($translations)) {
            $translations = json_encode($translations);
        }

        Staff::handleTranslation($staffId, $translations);
    }

    /**
     * @param string $search
     * @param int $location
     * @param int $service
     * @return Staff[]
     */
    public function getAll(string $search = '', int $location = 0, int $service = 0): array
    {
        $staff = Staff::query()->where('is_active', 1)
            ->where('name', 'like', "%$search%");

        if (!empty($location)) {
            $staff->whereFindInSet('locations', $location);
        }

        if (!empty($service)) {
            $serviceStaffSubQuery = ServiceStaff::query()->where('service_id', $service)->select('staff_id');
            $staff->where('id', 'IN', $serviceStaffSubQuery);
        }

        return $staff->fetchAll();
    }

    public function duplicateTranslations(int $oldId, int $newId): void
    {
        $this->translationRepository->duplicateForTable(Staff::getTableName(), $oldId, $newId);
    }

    public function duplicateServiceStaff(int $oldId, int $newId): void
    {
        $records = ServiceStaff::query()->where('staff_id', $oldId)->fetchAll();

        foreach ($records as $record) {
            ServiceStaff::query()->insert([
                'staff_id'     => $newId,
                'service_id'   => $record->service_id,
                'price'        => $record->price,
                'deposit'      => $record->deposit,
                'deposit_type' => $record->deposit_type,
            ]);
        }
    }

    public function duplicateTimesheet(int $oldId, int $newId): void
    {
        $timesheets = Timesheet::query()->where('staff_id', $oldId)->fetchAll();

        foreach ($timesheets as $ts) {
            Timesheet::query()->insert([
                'staff_id'   => $newId,
                'service_id' => $ts->service_id,
                'timesheet'  => $ts->timesheet,
            ]);
        }
    }

    public function duplicateData(int $oldId, int $newId): void
    {
        $this->dataRepository->duplicateForTable(Staff::getTableName(), $oldId, $newId);
    }

    public function count(): int
    {
        return Staff::query()->count();
    }

    /**
     * @param array $ids
     * @return Staff[]
     */
    public function getStaffInArray(array $ids): array // TODO: refactor name to getAllByIds as in ServiceRepository
    {
        return Staff::query()->where('id', 'in', $ids)->fetchAll();
    }
}
