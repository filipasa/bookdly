<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Backend\Appointments\Exceptions\StatusNotFoundException;
use BookneticApp\Backend\Appointments\Services\AppointmentService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\RestRequest;
use Exception;

class AppointmentRestController
{
    private AppointmentService $appointmentService;
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * @throws CapabilitiesException
     */
    public function getAll(RestRequest $request): array
    {
        Capabilities::must('appointments');

        $skip = $request->param('skip', 0, RestRequest::TYPE_INTEGER);
        $limit = $request->param('limit', 12, RestRequest::TYPE_INTEGER);
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $orderByField = $request->param(
            'orderByField',
            '',
            RestRequest::TYPE_STRING,
            ['starts_at', 'customer_name', 'staff_name', 'service_name', 'duration', 'created_at']
        );

        $orderDirection = $request->param('orderDirection', 'DESC', RestRequest::TYPE_STRING, ['ASC', 'DESC']);

        $startsAt = $request->param('startsAt', null, RestRequest::TYPE_STRING);
        $endsAt = $request->param('endsAt', null, RestRequest::TYPE_STRING);
        $serviceId = $request->param('serviceId', null, RestRequest::TYPE_INTEGER);
        $customerId = $request->param('customerId', null, RestRequest::TYPE_INTEGER);
        $staffId = $request->param('staffId', null, RestRequest::TYPE_INTEGER);
        $status = $request->param('status', null, RestRequest::TYPE_STRING);
        $isFinished = $request->param('isFinished', null, RestRequest::TYPE_INTEGER, [0, 1]);

        $appointments = $this->appointmentService->getAppointments([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'service_id' => $serviceId,
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'status' => $status,
            'isFinished' => $isFinished,
        ], [
            'field' => $orderByField,
            'type' => $orderDirection,
        ], $search, $skip, $limit);

        return [
            'data' => $appointments['data'],
            'meta' => [
                'total' => $appointments['total'],
                'limit' => $limit,
                'skip' => $skip,
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function get(RestRequest $request): array
    {
        Capabilities::must('appointments');

        $id = $request->require('id', RestRequest::TYPE_INTEGER);

        $appointment = $this->appointmentService->getAppointment($id);

        return [
            'data' => $appointment,
        ];
    }

    public function create(RestRequest $request): array
    {
        Capabilities::must('appointments_add');

        $customerId = $request->require('customer_id', RestRequest::TYPE_INTEGER);
        $serviceName = $request->require('service_name', RestRequest::TYPE_STRING);
        $locationName = $request->require('location', RestRequest::TYPE_STRING);
        $staffName = $request->require('staff_name', RestRequest::TYPE_STRING);
        $date = $request->require('date', RestRequest::TYPE_STRING); 
        $time = $request->require('time', RestRequest::TYPE_STRING); 
        
        $note = $request->param('note', '', RestRequest::TYPE_STRING);
        $status = $request->param('status', 'pending', RestRequest::TYPE_STRING);

        // Resolve Service ID
        $service = \BookneticApp\Models\Service::where('name', $serviceName)->fetch();
        $serviceId = $service ? $service->id : 0;

        // Resolve Location ID
        $location = \BookneticApp\Models\Location::where('name', $locationName)->fetch();
        $locationId = $location ? $location->id : 0;

        // Resolve Staff ID
        $staff = \BookneticApp\Models\Staff::where('name', $staffName)->fetch();
        $staffId = $staff ? $staff->id : 0;

        // Fallback IDs if not found, let's select first active one
        if (!$serviceId) {
            $firstService = \BookneticApp\Models\Service::fetch();
            $serviceId = $firstService ? $firstService->id : 1;
        }
        if (!$locationId) {
            $firstLocation = \BookneticApp\Models\Location::fetch();
            $locationId = $firstLocation ? $firstLocation->id : 1;
        }
        if (!$staffId) {
            $firstStaff = \BookneticApp\Models\Staff::fetch();
            $staffId = $firstStaff ? $firstStaff->id : 1;
        }

        // Parse time range
        $timeParts = explode('-', $time);
        $startTimeStr = trim($timeParts[0]);
        $endTimeStr = isset($timeParts[1]) ? trim($timeParts[1]) : '';

        $startsAt = strtotime($date . ' ' . $startTimeStr);
        
        if (!empty($endTimeStr)) {
            $endsAt = strtotime($date . ' ' . $endTimeStr);
        } else {
            // Default to service duration if ends_at is empty
            $duration = $service ? (int)$service->duration : 30;
            $endsAt = $startsAt + ($duration * 60);
        }

        if (!$endsAt || $endsAt <= $startsAt) {
            $endsAt = $startsAt + 1800;
        }

        $bufferBefore = $service ? (int)$service->buffer_before : 0;
        $bufferAfter = $service ? (int)$service->buffer_after : 0;

        $busyFrom = $startsAt - ($bufferBefore * 60);
        $busyTo = $endsAt + ($bufferAfter * 60);

        do_action('bkntc_appointment_before_mutation', null);

        \BookneticApp\Models\Appointment::insert([
            'location_id' => $locationId,
            'service_id'  => $serviceId,
            'staff_id'    => $staffId,
            'customer_id' => $customerId,
            'status'      => $status,
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'busy_from'   => $busyFrom,
            'busy_to'     => $busyTo,
            'note'        => $note,
            'created_at'  => time(),
        ]);

        $newId = \BookneticApp\Providers\DB\DB::lastInsertedId();

        do_action('bkntc_appointment_after_mutation', $newId);

        $appointment = \BookneticApp\Models\Appointment::query()->get($newId);
        $customer = \BookneticApp\Models\Customer::query()->get($appointment->customer_id);

        \BookneticApp\Config::getWorkflowEventsManager()->trigger('booking_new', [
            'appointment_id' => $newId,
            'location_id' => $appointment->location_id,
            'service_id' => $appointment->service_id,
            'staff_id' => $appointment->staff_id,
            'customer_id' => $appointment->customer_id
        ], function ($event) use ($appointment, $customer) {
            if (empty($event['data'])) {
                return true;
            }

            $eventData = \BookneticApp\Providers\Data\WorkflowEventFilterData::fromArray(json_decode($event['data'], true));

            if ($eventData->hasLocale() && $eventData->getLocale() !== $appointment->locale) {
                return false;
            }

            if ($eventData->hasLocations() && !$eventData->matchesLocation($appointment->location_id)) {
                return false;
            }

            if ($eventData->hasCategories() && !$eventData->matchesCategory($customer->category_id)) {
                return false;
            }

            if ($eventData->hasServices() && !$eventData->matchesService($appointment->service_id)) {
                return false;
            }

            if ($eventData->hasStaffs() && !$eventData->matchesStaff($appointment->staff_id)) {
                return false;
            }

            if ($eventData->hasStatuses() && !$eventData->matchesStatus($appointment->status)) {
                return false;
            }

            if ($eventData->hasLocationCategories()) {
                $location = \BookneticApp\Models\Location::query()->select(['category_id'])->get($appointment->location_id);
                if (!$location || !$eventData->matchesLocationCategory((int)$location->category_id)) {
                    return false;
                }
            }

            if ($eventData->hasCalledFrom()) {
                if ($eventData->isCalledFromBackend() && !\BookneticApp\Providers\Core\Permission::isBackEnd()) {
                    return false;
                }
                if ($eventData->isCalledFromFrontend() && \BookneticApp\Providers\Core\Permission::isBackEnd()) {
                    return false;
                }
            }

            return true;
        });

        $newId = \BookneticApp\Providers\DB\DB::lastInsertedId();
        $newAppointment = $this->appointmentService->getAppointment($newId);

        return [
            'status' => true,
            'data'   => $newAppointment,
        ];
    }

    public function update(RestRequest $request): array
    {
        Capabilities::must('appointments_edit');

        $id = $request->require('id', RestRequest::TYPE_INTEGER);
        
        $customerId = $request->require('customer_id', RestRequest::TYPE_INTEGER);
        $serviceName = $request->require('service_name', RestRequest::TYPE_STRING);
        $locationName = $request->require('location', RestRequest::TYPE_STRING);
        $staffName = $request->require('staff_name', RestRequest::TYPE_STRING);
        $date = $request->require('date', RestRequest::TYPE_STRING); 
        $time = $request->require('time', RestRequest::TYPE_STRING);
        
        $note = $request->param('note', '', RestRequest::TYPE_STRING);
        $status = $request->param('status', 'pending', RestRequest::TYPE_STRING);

        // Resolve Service ID
        $service = \BookneticApp\Models\Service::where('name', $serviceName)->fetch();
        $serviceId = $service ? $service->id : 0;

        // Resolve Location ID
        $location = \BookneticApp\Models\Location::where('name', $locationName)->fetch();
        $locationId = $location ? $location->id : 0;

        // Resolve Staff ID
        $staff = \BookneticApp\Models\Staff::where('name', $staffName)->fetch();
        $staffId = $staff ? $staff->id : 0;

        $currentApp = \BookneticApp\Models\Appointment::query()->get($id);
        if (!$currentApp) {
            throw new Exception('Appointment not found');
        }

        if (!$serviceId) $serviceId = $currentApp->service_id;
        if (!$locationId) $locationId = $currentApp->location_id;
        if (!$staffId) $staffId = $currentApp->staff_id;

        // Parse time range
        $timeParts = explode('-', $time);
        $startTimeStr = trim($timeParts[0]);
        $endTimeStr = isset($timeParts[1]) ? trim($timeParts[1]) : '';

        $startsAt = strtotime($date . ' ' . $startTimeStr);
        
        if (!empty($endTimeStr)) {
            $endsAt = strtotime($date . ' ' . $endTimeStr);
        } else {
            $duration = $service ? (int)$service->duration : 30;
            $endsAt = $startsAt + ($duration * 60);
        }

        if (!$endsAt || $endsAt <= $startsAt) {
            $endsAt = $startsAt + 1800;
        }

        $bufferBefore = $service ? (int)$service->buffer_before : 0;
        $bufferAfter = $service ? (int)$service->buffer_after : 0;

        $busyFrom = $startsAt - ($bufferBefore * 60);
        $busyTo = $endsAt + ($bufferAfter * 60);

        do_action('bkntc_appointment_before_mutation', $id);

        \BookneticApp\Models\Appointment::where('id', $id)->update([
            'location_id' => $locationId,
            'service_id'  => $serviceId,
            'staff_id'    => $staffId,
            'customer_id' => $customerId,
            'status'      => $status,
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'busy_from'   => $busyFrom,
            'busy_to'     => $busyTo,
            'note'        => $note,
        ]);

        do_action('bkntc_appointment_after_mutation', $id);

        $updatedAppointment = $this->appointmentService->getAppointment($id);

        return [
            'status' => true,
            'data'   => $updatedAppointment,
        ];
    }

    /**
     * @throws Exception
     */
    public function delete(RestRequest $request): array
    {
        Capabilities::must('appointments_delete');

        $id = $request->require('id', RestRequest::TYPE_INTEGER);
        $this->appointmentService->delete($id);

        return [
            'status' => true,
        ];
    }

    public function getStatuses(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $statuses = $this->appointmentService->getAppointmentStatuses($search);

        return [
            'data' => $statuses,
        ];
    }

    /**
     * @throws StatusNotFoundException
     * @throws CapabilitiesException
     */
    public function changeStatus(RestRequest $request): array
    {
        Capabilities::must('appointments_change_status');

        $runWorkflows = $request->param('run_workflows', 1, RestRequest::TYPE_INTEGER, [0, 1]);

        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);
        $status = $request->param('status', '', RestRequest::TYPE_STRING);

        $this->appointmentService->changeStatus($id, $status, $runWorkflows);

        return [
            'status' => true,
        ];
    }

    /**
     * @throws Exception
     */
    public function getAvailableTimes(RestRequest $request): array
    {
        $id				= $request->param('id', -1, RestRequest::TYPE_INTEGER);
        $search			= $request->param('q', '', RestRequest::TYPE_STRING);
        $date			= $request->param('date', '', RestRequest::TYPE_STRING);
        $location		= $request->param('location', 0, RestRequest::TYPE_INTEGER);
        $service		= $request->param('service', 0, RestRequest::TYPE_INTEGER);
        $staff			= $request->param('staff', 0, RestRequest::TYPE_INTEGER);
        $serviceExtras	= $request->param('service_extras', '[]', RestRequest::TYPE_STRING);

        $availableTimes = $this->appointmentService->getAvailableTimes(
            $id,
            $search,
            $date,
            $location,
            $service,
            $staff,
            $serviceExtras,
        );

        return [
            'data' => $availableTimes,
        ];
    }

    public function getServices(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $data = $this->appointmentService->getServices($search);

        return ['data' => $data];
    }

    public function getStaff(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $data = $this->appointmentService->getStaff($search);

        return ['data' => $data];
    }

    public function getCustomers(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $data = $this->appointmentService->getCustomers($search);

        return ['data' => $data];
    }

    public function getLocations(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $data = $this->appointmentService->getLocations($search);

        return ['data' => $data];
    }

    public function getFilters(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $data = $this->appointmentService->getFilters($search);

        return ['data' => $data];
    }
}
