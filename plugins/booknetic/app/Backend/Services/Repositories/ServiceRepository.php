<?php

namespace BookneticApp\Backend\Services\Repositories;

use BookneticApp\Backend\Base\Repository\DataRepository;
use BookneticApp\Backend\Base\Repository\TranslationRepository;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class ServiceRepository
{
    private DataRepository $dataRepository;
    private TranslationRepository $translationRepository;

    public function __construct(DataRepository $dataRepository, TranslationRepository $translationRepository)
    {
        $this->dataRepository = $dataRepository;
        $this->translationRepository = $translationRepository;
    }

    /**
     * @param string $search
     * @param int $category
     * @return array
     */
    public function getServices(string $search, int $category = 0): array
    {
        $allowedStaffIDs = array_column(Staff::query()->fetchAll(), 'id');

        $services = Service::query()->where('is_active', 1);

        if (! empty($category)) {
            $services = $services->where('category_id', $category);
        }

        if (! empty($search)) {
            $services = $services->like('name', $search);
        }

        $data = [];

        foreach ($services->fetchAll() as $service) {
            $isAllowedServiceForStaff = ServiceStaff::query()->where('staff_id', $allowedStaffIDs)->where('service_id', $service->id)->count();

            if ($isAllowedServiceForStaff == 0) {
                continue;
            }

            $data[] = [
                'id'				=>	(int)$service['id'],
                'text'				=>	htmlspecialchars($service['name']),
                'repeatable'		=>	(int)$service['is_recurring'],
                'repeat_type'		=>	htmlspecialchars((string)$service['repeat_type']),
                'repeat_frequency'	=>	htmlspecialchars((string)$service['repeat_frequency']),
                'full_period_type'	=>	htmlspecialchars((string)$service['full_period_type']),
                'full_period_value'	=>	(int)$service['full_period_value'],
                'max_capacity'		=>	(int)$service['max_capacity'],
                'date_based'		=>	$service['duration'] >= 1440
            ];
        }

        return $data;
    }

    public function getAllByIds(array $ids): array
    {
        return Service::query()->where('id', 'in', $ids)->fetchAll();
    }

    /**
     * @param int $id
     * @return Collection|Service|null
     */
    public function get(int $id): ?Collection
    {
        return Service::query()->get($id);
    }

    public function count(): int
    {
        return Service::query()->count();
    }

    public function create(array $data): int
    {
        Service::query()->insert($data);

        return DB::lastInsertedId();
    }

    public function duplicateServiceStaff(int $oldId, int $newId): void
    {
        $records = ServiceStaff::query()->where('service_id', $oldId)->fetchAll();

        foreach ($records as $record) {
            ServiceStaff::query()->insert([
                'staff_id'     => $record->staff_id,
                'service_id'   => $newId,
                'price'        => $record->price,
                'deposit'      => $record->deposit,
                'deposit_type' => $record->deposit_type,
            ]);
        }
    }

    public function duplicateExtras(int $oldId, int $newId): void
    {
        $extras = ServiceExtra::query()->where('service_id', $oldId)->fetchAll();

        foreach ($extras as $extra) {
            $newImage = $extra->image;

            if (! empty($extra->image)) {
                $oldPath = Helper::uploadedFile($extra->image, 'Services');

                if (is_file($oldPath)) {
                    $extension = pathinfo($extra->image, PATHINFO_EXTENSION);
                    $newImage  = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
                    $newPath   = Helper::uploadedFile($newImage, 'Services');

                    copy($oldPath, $newPath);
                }
            }

            ServiceExtra::query()->insert([
                'service_id'    => $newId,
                'name'          => $extra->name,
                'price'         => $extra->price,
                'hide_price'    => $extra->hide_price,
                'duration'      => $extra->duration,
                'hide_duration' => $extra->hide_duration,
                'is_active'     => $extra->is_active,
                'min_quantity'  => $extra->min_quantity,
                'max_quantity'  => $extra->max_quantity,
                'image'         => $newImage,
                'category_id'   => $extra->category_id,
                'notes'         => $extra->notes,
            ]);

            $newExtraId = DB::lastInsertedId();

            $this->translationRepository->duplicateForTable(ServiceExtra::getTableName(), $extra->id, $newExtraId);
        }
    }

    public function duplicateTimesheet(int $oldId, int $newId): void
    {
        $timesheets = Timesheet::query()->where('service_id', $oldId)->fetchAll();

        foreach ($timesheets as $ts) {
            Timesheet::query()->insert([
                'service_id' => $newId,
                'staff_id'   => $ts->staff_id,
                'timesheet'  => $ts->timesheet,
            ]);
        }
    }

    public function duplicateTranslations(int $oldId, int $newId): void
    {
        $this->translationRepository->duplicateForTable(Service::getTableName(), $oldId, $newId);
    }

    public function duplicateData(int $oldId, int $newId): void
    {
        $this->dataRepository->duplicateForTable(Service::getTableName(), $oldId, $newId);
    }

    public function duplicateSpecialDays(int $oldId, int $newId): void
    {
        $specialDays = SpecialDay::query()->where('service_id', $oldId)->fetchAll();

        foreach ($specialDays as $sd) {
            SpecialDay::query()->insert([
                'service_id' => $newId,
                'staff_id'   => $sd->staff_id,
                'date'       => $sd->date,
                'timesheet'  => $sd->timesheet,
            ]);
        }
    }
}
