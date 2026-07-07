<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class LocationAppointmentRepository
{
    public function getAppointmentCount(array $ids): int
    {
        return Appointment::query()
            ->where('location_id', 'in', $ids)
            ->count();
    }
}
