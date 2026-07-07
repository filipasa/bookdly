<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\DB\DB;

class TimesheetRepository
{
    public function getForStaffOrDefault(int $staffId): array
    {
        $row = DB::DB()->get_row(
            DB::DB()->prepare(
                'SELECT staff_id, timesheet FROM ' . DB::table('timesheet') . '
             WHERE ((service_id IS NULL AND staff_id IS NULL) OR (staff_id=%d))
             ' . DB::tenantFilter() . ' ORDER BY staff_id DESC LIMIT 1',
                [$staffId]
            ),
            ARRAY_A
        );

        if (empty($row['timesheet'])) {
            $default = array_fill(0, 7, [
                'day_off' => 0, 'start' => '00:00', 'end' => '24:00', 'breaks' => [],
            ]);

            return ['schedule' => $default, 'hasSpecific' => false];
        }

        return [
            'schedule'    => json_decode($row['timesheet'], true) ?: [],
            'hasSpecific' => $row['staff_id'] > 0,
        ];
    }

    /**
     * Insert or replace a staff timesheet record.
     *
     * @param int $staffId
     * @param array $weeklySchedule
     * @return void
     */
    public function saveForStaff(int $staffId, array $weeklySchedule): void
    {
        Timesheet::query()->insert([
            'staff_id'  => $staffId,
            'timesheet' => json_encode($weeklySchedule),
        ]);
    }

    /**
     * Delete all timesheets belonging to a specific staff member.
     *
     * @param int $staffId
     * @return void
     */
    public function deleteByStaffId(int $staffId): void
    {
        Timesheet::query()->where('staff_id', $staffId)->delete();
    }
}
