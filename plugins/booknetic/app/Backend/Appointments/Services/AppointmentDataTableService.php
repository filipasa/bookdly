<?php

namespace BookneticApp\Backend\Appointments\Services;

use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentRepository;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class AppointmentDataTableService
{
    private DataTableUI $dataTable;

    private array $appointmentStatuses;

    private array $invoiceSvgs = [
        'paid' => 'invoice-paid',
        'paid_deposit' => 'invoice-paid-deposit',
        'pending' => 'invoice-pending',
        'canceled' => 'invoice-canceled',
        'not_paid' => 'invoice'
    ];

    private AppointmentService $appointmentService;

    private AppointmentRepository $appointmentRepository;
    private AppointmentExtraRepository $appointmentExtraRepository;

    public function __construct(AppointmentService $appointmentService, AppointmentRepository $appointmentRepository, AppointmentExtraRepository $appointmentExtraRepository)
    {
        $this->appointmentService = $appointmentService;
        $this->appointmentRepository = $appointmentRepository;
        $this->appointmentExtraRepository = $appointmentExtraRepository;
    }

    /**
     * @throws CapabilitiesException
     */
    public function delete($deleteIDs): bool
    {
        Capabilities::must('appointments_delete');

        $deleteIDs = is_array($deleteIDs) ? $deleteIDs : [ $deleteIDs ];

        $this->appointmentService->deleteBulk($deleteIDs);

        return false;
    }

    /**
     * @throws CapabilitiesException
     */
    public function getTable(): DataTableUI
    {
        Capabilities::must('appointments');

        $this->appointmentStatuses = Helper::getAppointmentStatuses();

        $appointments = $this->appointmentRepository->getAppointmentsQuery();

        $dataTable = new DataTableUI($appointments);
        $this->dataTable = $dataTable;

        $dataTable->setIdFieldForQuery(Appointment::getField('id'));
        $dataTable->setModule('appointments');
        $dataTable->setTitle(bkntc__('Appointments'));

        $this->setFilters();
        $this->setActions();
        $this->setButtons();

        $searchByColumns = $this->appointmentRepository->getSearchByColumns();

        $dataTable->searchBy($searchByColumns);

        $this->setColumns();

        $dataTable->setRowsPerPage(12);

        return $dataTable;
    }

    private function setFilters(): void
    {
        $this->dataTable->addFilter(
            Appointment::getField('date'),
            'date',
            bkntc__('Date'),
            fn ($val, $query) => $query
                ->where('starts_at', '<=', Date::epoch($val, '+1 day'))
                ->where('ends_at', '>=', Date::epoch($val))
        );
        $this->dataTable->addFilter(Service::getField('id'), 'select', bkntc__('Service'), '=', [ 'model' => new Service() ]);
        $this->dataTable->addFilter(Customer::getField('id'), 'select', bkntc__('Customer'), '=', [
            'model' => Customer::my(),
            'name_field' => 'CONCAT(`first_name`, \' \', last_name)'
        ]);

        $this->dataTable->addFilter(Staff::getField('id'), 'select', bkntc__('Staff'), '=', [ 'model' => new Staff() ]);

        $statusFilter = array_map(static fn ($v) => $v['title'], $this->appointmentStatuses);
        $this->dataTable->addFilter(Appointment::getField('status'), 'select', bkntc__('Status'), '=', [
            'list' => $statusFilter
        ], 1);

        $this->dataTable->addFilter(null, 'select', bkntc__('Filter'), function ($val, $query) {
            switch ($val) {
                case 0:
                    return $query->where(Appointment::getField('ends_at'), '<', Date::epoch());
                case 1:
                    return $query->where(Appointment::getField('starts_at'), '>', Date::epoch());
                default:
                    return $query;
            }
        }, [
            'list' => [ 0 => bkntc__('Finished'), 1 => bkntc__('Upcoming') ]
        ], 1);
    }

    private function setActions(): void
    {
        $this->dataTable->addAction('info', bkntc__('Info'));
        $this->dataTable->addAction('edit', bkntc__('Edit'));
        $this->dataTable->addAction('change_status', bkntc__('Change status'), null, AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK);
        $this->dataTable->addAction('delete', bkntc__('Delete'), [$this, 'delete'], AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK);
    }

    private function setButtons(): void
    {
        $this->dataTable->activateExportBtn();

        if (Capabilities::userCan('appointments_add')) {
            $this->dataTable->addNewBtn(bkntc__('NEW APPOINTMENT'));
        }
    }

    private function setColumns(): void
    {
        // $this->dataTable->addColumns(bkntc__('ID'), 'id');

        $this->dataTable->addColumns(bkntc__('START DATE'), function ($row) {
            $dateStr = Date::datee($row['starts_at']);
            $timeStr = Date::time($row['starts_at']);
            $durationSeconds = (int)$row['ends_at'] - (int)$row['starts_at'];

            if ($durationSeconds >= 24 * 3600) {
                $days = (int)round($durationSeconds / (24 * 3600));
                $durationStr = $days . ' ' . ($days > 1 ? bkntc__('days') : bkntc__('day'));
            } elseif ($durationSeconds > 0) {
                $minutes = (int)round($durationSeconds / 60);
                if ($minutes >= 60 && $minutes % 60 === 0) {
                    $durationStr = ($minutes / 60) . ' h';
                } elseif ($minutes > 60) {
                    $durationStr = floor($minutes / 60) . ' h ' . ($minutes % 60) . ' min';
                } else {
                    $durationStr = $minutes . ' min';
                }
            } else {
                $durationStr = '';
            }

            $metaStr = $timeStr . ($durationStr ? ' · ' . $durationStr : '');

            return '<strong>' . htmlspecialchars($dateStr) . '</strong><div class="text-muted" style="font-size:12px;color:var(--wf-text-3,#64748b);margin-top:2px;">' . htmlspecialchars($metaStr) . '</div>';
        }, [ 'is_html' => true, 'order_by_field' => 'starts_at' ]);

        $this->dataTable->addColumnsForExport(bkntc__('START DATE'), fn ($row) => Date::dateTime($row['starts_at']));

        $this->dataTable->addColumns(bkntc__('CUSTOMER'), function ($row) {
            if (array_key_exists($row[ 'status' ], $this->appointmentStatuses)) {
                $status = $this->appointmentStatuses[ $row[ 'status' ] ];
                $badge = '<div class="appointment-status-icon ml-3" style="background-color: ' . htmlspecialchars($status[ 'color' ]) . '2b">
                                    <i style="color: ' . htmlspecialchars($status[ 'color' ]) . '" class="' . htmlspecialchars($status[ 'icon' ]) . '"></i>
                                </div>';
            } else {
                $badge = '<span class="badge badge-dark">' . $row[ 'status' ] . '</span>';
            }

            $customerFullName = trim($row[ 'customer_first_name' ] . ' ' . $row[ 'customer_last_name' ]);
            if (empty($customerFullName)) {
                $customerFullName = 'Customer #' . $row[ 'customer_id' ];
            }
            $initials = '';
            $parts = explode(' ', $customerFullName);
            foreach ($parts as $part) {
                if (!empty($part)) {
                    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                }
            }
            $initials = substr($initials, 0, 2);

            if (!empty($row['customer_profile_image'])) {
                $avatarHtml = '<div class="circle_image"><img src="' . Helper::profileImage($row['customer_profile_image'], 'Customers') . '" alt=""></div>';
            } else {
                $colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];
                $colorIndex = abs(crc32($row['customer_email'] ?: $customerFullName)) % count($colors);
                $avatarBg = $colors[$colorIndex];
                $avatarHtml = '<div class="circle_image" style="background: ' . $avatarBg . '; color: #fff; font-weight: 700; font-size: 11px; align-items: center; justify-content: center; text-transform: uppercase;">' . htmlspecialchars($initials ?: 'CU') . '</div>';
            }

            $customerCardHtml = '<div class="user_visit_card">' . $avatarHtml . '
					<div class="user_visit_details">
						<span>' . htmlspecialchars($customerFullName) . '</span>
						<span>' . htmlspecialchars($row[ 'customer_email' ]) . '</span>
					</div>
				</div>';

            $customerHtml = $customerCardHtml . $badge;

            return '<div class="d-flex align-items-center justify-content-between">' . $customerHtml . '</div>';
        }, [ 'is_html' => true, 'order_by_field' => 'customer_first_name' ], true);

        $this->dataTable->addColumnsForExport(bkntc__('Customer'), fn ($appointment) => $appointment[ 'customer_first_name' ] . ' ' . $appointment[ 'customer_last_name' ]);

        $allExtras = $this->appointmentExtraRepository->getAllExtras();

        $extrasGroupedByAppointmentID = [];
        foreach ($allExtras as $extra) {
            if (! isset($extrasGroupedByAppointmentID[$extra->appointment_id])) {
                $extrasGroupedByAppointmentID[$extra->appointment_id] = [];
            }

            $extrasGroupedByAppointmentID[$extra->appointment_id][] = $extra;
        }

        $this->dataTable->addColumnsForExport(bkntc__('Service Extras'), function ($appointment) use ($extrasGroupedByAppointmentID) {
            $result = '';

            if (isset($extrasGroupedByAppointmentID[ $appointment->id ])) {
                foreach ($extrasGroupedByAppointmentID[ $appointment->id ] as $bookedExtras) {
                    if (! empty($result)) {
                        $result .= " ; ";
                    }

                    $result .= sprintf(
                        '%s [ %s %s | %s %s | %s %s ]',
                        htmlspecialchars($bookedExtras['extra_name']),
                        bkntc__('Quantity:'),
                        (int)$bookedExtras[ 'quantity' ],
                        bkntc__('Price:'),
                        Helper::price($bookedExtras[ 'price' ]),
                        bkntc__('Duration:'),
                        Helper::secFormat($bookedExtras[ 'duration' ] * 60)
                    );
                }
            }

            return $result;
        });

        $this->dataTable->addColumnsForExport(bkntc__('Customer Email'), 'customer_email');
        $this->dataTable->addColumnsForExport(bkntc__('Customer Phone Number'), 'customer_phone_number');

        $this->dataTable->addColumns(bkntc__('STAFF'), function ($appointment) {
            $staffFullName = trim($appointment[ 'staff_name' ]);
            $sparts = explode(' ', $staffFullName);
            $staffInitials = '';
            foreach ($sparts as $part) {
                if (!empty($part)) {
                    $staffInitials .= mb_strtoupper(mb_substr($part, 0, 1));
                }
            }
            $staffInitials = substr($staffInitials, 0, 2);

            if (!empty($appointment['staff_profile_image'])) {
                $avatarHtml = '<div class="circle_image"><img src="' . Helper::profileImage($appointment['staff_profile_image'], 'staff') . '" alt=""></div>';
            } else {
                $colors = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#22c55e', '#14b8a6', '#ef4444', '#f97316'];
                $colorIndex = (int)$appointment['staff_id'] % count($colors);
                $avatarBg = $colors[$colorIndex];
                $avatarHtml = '<div class="circle_image" style="background: ' . $avatarBg . '; color: #fff; font-weight: 700; font-size: 11px; align-items: center; justify-content: center; text-transform: uppercase;">' . htmlspecialchars($staffInitials ?: 'ST') . '</div>';
            }

            return '<div class="user_visit_card">' . $avatarHtml . '
					<div class="user_visit_details">
						<span>' . htmlspecialchars($staffFullName) . '</span>
					</div>
				</div>';
        }, [ 'is_html' => true, 'order_by_field' => 'staff_name' ]);

        $this->dataTable->addColumns(bkntc__('SERVICE'), 'service_name');
        $this->dataTable->addColumns(bkntc__('PAYMENT'), function ($row) {
            $svg = Helper::icon(($this->invoiceSvgs[ $row[ 'payment_status' ] ] ?? 'invoice') . '.svg');
            $badge = ' <img class="invoice-icon" data-load-modal="payments.info" data-parameter-id="' . (int) $row[ 'id' ] . '" src="' . $svg . '"> ';

            return '<div class="invoice-cell">' . Helper::price($row[ 'total_price' ]) . $badge . '</div>';
        }, [ 'is_html' => true ]);

        // $this->dataTable->addColumns(bkntc__('DURATION'), fn ($row) => Helper::secFormat(((int) $row[ 'ends_at' ] - (int) $row[ 'starts_at' ])), [ 'is_html' => true, 'order_by_field' => '( ends_at - starts_at )' ]);

        // $this->dataTable->addColumns(bkntc__('CREATED AT'), fn ($row) => Date::dateTime($row[ 'created_at' ]), ['order_by_field' => 'created_at']);
    }
}
