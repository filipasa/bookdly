<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\ServiceStaff;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class ServiceStaffRepository
{
    /**
     * @param int $staffId
     * @param int $serviceId
     * @return void
     */
    public function delete(int $staffId, int $serviceId): void
    {
        ServiceStaff::query()
            ->where('staff_id', $staffId)
            ->where('service_id', $serviceId)
            ->delete();
    }

    /**
     * @param int $staffId
     * @param int $serviceId
     * @param float $price
     * @param float $deposit
     * @param string $depositType
     * @return int
     */
    public function insert(int $staffId, int $serviceId, float $price = -1, float $deposit = -1, string $depositType = 'percent'): int
    {
        ServiceStaff::query()->insert([
            'staff_id' => $staffId,
            'service_id' => $serviceId,
            'price' => $price,
            'deposit' => $deposit,
            'deposit_type' => $depositType,
        ]);

        return ServiceStaff::lastId();
    }

    /**
     * @param int $staffId
     * @return int[]
     */
    public function getIdsByStaffId(int $staffId): array
    {
        return array_map(
            static fn ($s) => (int)$s->service_id,
            ServiceStaff::query()->select(['service_id'])->where('staff_id', $staffId)->fetchAll()
        );
    }

    public function getByStaffId(int $staffId): array
    {
        return ServiceStaff::query()
            ->where('staff_id', $staffId)
            ->fetchAllAsArray();
    }
}
