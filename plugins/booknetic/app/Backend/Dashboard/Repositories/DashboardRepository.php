<?php

namespace BookneticApp\Backend\Dashboard\Repositories;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\Helper;

class DashboardRepository
{
    public function getAppointmentsByDateRange(bool $showAll, int $offset, int $limit, int $start, int $end): array
    {
        $query = Appointment::query()
            ->leftJoin('customer', ['first_name', 'last_name', 'email', 'profile_image'])
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('service', ['name'])
            ->where(Appointment::getField('starts_at'), '>=', $start)
            ->where(Appointment::getField('starts_at'), '<', $end)
            ->orderBy(Appointment::getField('starts_at') . ' ASC');

        if (!$showAll) {
            $query->where(Appointment::getField('status'), 'IN', Helper::getBusyAppointmentStatuses());
        }

        $query->limit($limit);

        if ($offset > 0) {
            $query->offset($offset);
        }

        return $query->fetchAll();
    }

    public function getAppointmentCountByDateRange(bool $showAll, int $start, int $end): int
    {
        $countQuery = Appointment::where(Appointment::getField('starts_at'), '>=', $start)
            ->where(Appointment::getField('starts_at'), '<', $end);

        if (!$showAll) {
            $countQuery->where(Appointment::getField('status'), 'IN', Helper::getBusyAppointmentStatuses());
        }

        return $countQuery->count();
    }
}
