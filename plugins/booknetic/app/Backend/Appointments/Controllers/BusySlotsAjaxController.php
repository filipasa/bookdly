<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Models\CoreStaffBusySlot;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

class BusySlotsAjaxController extends Controller
{
    /**
     * @throws CapabilitiesException
     */
    public function add_new()
    {
        Capabilities::must('appointments_add');

        $staff = Staff::where('is_active', 1)->fetchAll();

        return $this->modalView('busy_slots/add_new', [
            'staff' => $staff
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function edit()
    {
        Capabilities::must('appointments_edit');

        $id = Post::int('id');
        $busySlot = CoreStaffBusySlot::get($id);

        if (!$busySlot) {
            return $this->response(false, bkntc__('Busy slot not found!'));
        }

        $staff = Staff::where('is_active', 1)->fetchAll();

        // Convert start_time (seconds from midnight) to H:i
        $hours = floor($busySlot->start_time / 3600);
        $minutes = floor(($busySlot->start_time % 3600) / 60);
        $formattedTime = sprintf('%02d:%02d', $hours, $minutes);

        return $this->modalView('busy_slots/edit', [
            'busySlot' => $busySlot,
            'formattedTime' => $formattedTime,
            'staff' => $staff
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function save()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('appointments_edit');
            $busySlot = CoreStaffBusySlot::get($id);
            if (!$busySlot) {
                return $this->response(false, bkntc__('Busy slot not found!'));
            }
        } else {
            Capabilities::must('appointments_add');
            $busySlot = new CoreStaffBusySlot();
        }

        $staffId = Post::int('staff_id');
        $dateStr = Post::string('date');
        $timeStr = Post::string('time');
        $duration = Post::int('duration');
        $notes = Post::string('notes');

        if (empty($staffId) || empty($dateStr) || empty($timeStr) || empty($duration)) {
            return $this->response(false, bkntc__('Please fill in all required fields.'));
        }

        // Convert time string "HH:MM" or "HH:MM:SS" to seconds from midnight
        $timeParts = explode(':', $timeStr);
        if (count($timeParts) < 2) {
            return $this->response(false, bkntc__('Invalid time format.'));
        }
        $startTimeSeconds = ((int)$timeParts[0] * 3600) + ((int)$timeParts[1] * 60);

        // Convert date string "YYYY-MM-DD" to timestamp (day boundary)
        $dateFormatted = Date::reformatDateFromCustomFormat($dateStr);
        $dateTimestamp = Date::epoch($dateFormatted);

        $data = [
            'staff_id'   => $staffId,
            'date'       => $dateTimestamp,
            'start_time' => $startTimeSeconds,
            'duration'   => $duration,
            'notes'      => $notes
        ];

        if ($id > 0) {
            CoreStaffBusySlot::where('id', $id)->update($data);
        } else {
            CoreStaffBusySlot::insert($data);
        }

        return $this->response(true, [
            'message' => bkntc__('Busy slot saved successfully.')
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function delete()
    {
        Capabilities::must('appointments_delete');

        $id = Post::int('id');
        $busySlot = CoreStaffBusySlot::get($id);

        if (!$busySlot) {
            return $this->response(false, bkntc__('Busy slot not found!'));
        }

        CoreStaffBusySlot::where('id', $id)->delete();

        return $this->response(true, [
            'message' => bkntc__('Busy slot deleted successfully.')
        ]);
    }
}
