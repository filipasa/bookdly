<?php

namespace BookneticApp\Backend\Appointments\Services;

use BookneticApp\Models\CoreStaffBusySlot;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class BusySlotsDataTableService
{
    private DataTableUI $dataTable;

    /**
     * @throws CapabilitiesException
     */
    public function delete($deleteIDs): bool
    {
        Capabilities::must('appointments_delete');

        $deleteIDs = is_array($deleteIDs) ? $deleteIDs : [ $deleteIDs ];

        foreach ($deleteIDs as $id) {
            CoreStaffBusySlot::where('id', $id)->delete();
        }

        return true;
    }

    /**
     * @throws CapabilitiesException
     */
    public function getTable(): DataTableUI
    {
        Capabilities::must('appointments');

        $busySlots = CoreStaffBusySlot::query()
            ->leftJoin('staff', ['name', 'profile_image', 'email']);

        $dataTable = new DataTableUI($busySlots);
        $this->dataTable = $dataTable;

        $dataTable->setIdFieldForQuery(CoreStaffBusySlot::getField('id'));
        $dataTable->setModule('busy_slots');
        $dataTable->setTitle(bkntc__('Busy Slots'));

        $this->setFilters();
        $this->setActions();
        $this->setButtons();

        $dataTable->searchBy([
            CoreStaffBusySlot::getField('notes'),
            'staff.name'
        ]);

        $this->setColumns();

        $dataTable->setRowsPerPage(12);

        return $dataTable;
    }

    private function setFilters(): void
    {
        $this->dataTable->addFilter(
            CoreStaffBusySlot::getField('date'),
            'date',
            bkntc__('Date'),
            fn ($val, $query) => $query->where('date', '=', Date::epoch($val))
        );

        $this->dataTable->addFilter(Staff::getField('id'), 'select', bkntc__('Staff'), '=', [ 'model' => new Staff() ]);
    }

    private function setActions(): void
    {
        $this->dataTable->addAction('edit', bkntc__('Edit'));
        $this->dataTable->addAction('delete', bkntc__('Delete'), [$this, 'delete'], AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK);
    }

    private function setButtons(): void
    {
        if (Capabilities::userCan('appointments_add')) {
            $this->dataTable->addNewBtn(bkntc__('ADD NEW BUSY SLOT'));
        }
    }

    private function setColumns(): void
    {
        $this->dataTable->addColumns(bkntc__('ID'), 'id');

        $this->dataTable->addColumns(bkntc__('STAFF'), fn ($row) => Helper::profileCard($row['staff_name'], $row['staff_profile_image'], $row['staff_email'], 'staff'), [ 'is_html' => true, 'order_by_field' => 'staff_name' ]);

        $this->dataTable->addColumns(bkntc__('DATE'), fn ($row) => Date::datee($row['date']), [ 'order_by_field' => 'date' ]);

        $this->dataTable->addColumns(bkntc__('START TIME'), fn ($row) => Date::time($row['date'] + $row['start_time']), [ 'order_by_field' => 'start_time' ]);

        $this->dataTable->addColumns(bkntc__('DURATION'), fn ($row) => Helper::secFormat($row['duration'] * 60), [ 'is_html' => true, 'order_by_field' => 'duration' ]);

        $this->dataTable->addColumns(bkntc__('NOTE'), 'notes');
    }
}
