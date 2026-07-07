<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\IoC\Attributes\Repository;

#[Repository]
class AppointmentRepository
{
    public function hasAppointmentsByStaffId(int $staffId): bool
    {
        return Appointment::query()
            ->where('staff_id', $staffId)
            ->count() > 0;
    }
}
