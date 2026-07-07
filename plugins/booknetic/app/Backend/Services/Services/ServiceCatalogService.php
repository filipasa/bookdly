<?php

namespace BookneticApp\Backend\Services\Services;

use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Backend\Services\Repositories\ServiceRepository;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Services\MediaService;

class ServiceCatalogService
{
    private ServiceRepository $serviceRepository;
    private AppointmentExtraRepository $appointmentExtraRepository;
    private ServiceExtraRepository $serviceExtraRepository;

    private MediaService $mediaService;

    public function __construct(
        ServiceRepository $serviceRepository,
        AppointmentExtraRepository $appointmentExtraRepository,
        ServiceExtraRepository $serviceExtraRepository,
        MediaService $mediaService
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->appointmentExtraRepository = $appointmentExtraRepository;
        $this->serviceExtraRepository = $serviceExtraRepository;
        $this->mediaService = $mediaService;
    }
    public function getServices(string $search, int $category = 0): array
    {
        return $this->serviceRepository->getServices($search, $category);
    }

    public function getExtras(int $serviceId, int $appointmentId): array
    {
        $showAllExtras =  Helper::getOption('show_all_service_extras', 'on');

        if ($showAllExtras == 'on') {
            $extras = $this->serviceExtraRepository->getAll();
        } else {
            $extras = $this->serviceExtraRepository->getAll($serviceId);
        }

        $appointmentExtras = $this->appointmentExtraRepository->getAllExtrasByAppointmentId($appointmentId);
        $appointmentExtras = Helper::assocByKey($appointmentExtras, 'extra_id');

        foreach ($extras as $extra) {
            $extra->quantity = array_key_exists($extra->id, $appointmentExtras) ? $appointmentExtras[$extra->id]->quantity : 0;
        }

        return $extras;
    }

    public function duplicate(int $id): int
    {
        $allowedLimit = Capabilities::getLimit('services_allowed_max_number');

        if ($allowedLimit > -1 && $this->serviceRepository->count() >= $allowedLimit) {
            throw new \RuntimeException(
                bkntc__('You can\'t add more than %d Service. Please upgrade your plan to add more Service.', [$allowedLimit])
            );
        }

        $service = $this->serviceRepository->get($id);

        if (!$service) {
            throw new \RuntimeException(bkntc__('Service not found!'));
        }

        $newImage = $this->mediaService->copy((string) $service->image, 'Services');

        $data = [
            'name'                   => $service->name . ' (2)',
            'price'                  => $service->price,
            'category_id'            => $service->category_id,
            'is_visible'             => $service->is_visible,
            'duration'               => $service->duration,
            'timeslot_length'        => $service->timeslot_length,
            'buffer_before'          => $service->buffer_before,
            'buffer_after'           => $service->buffer_after,
            'notes'                  => $service->notes,
            'image'                  => $newImage,
            'is_recurring'           => $service->is_recurring,
            'full_period_type'       => $service->full_period_type,
            'full_period_value'      => $service->full_period_value,
            'repeat_type'            => $service->repeat_type,
            'recurring_payment_type' => $service->recurring_payment_type,
            'repeat_frequency'       => $service->repeat_frequency,
            'max_capacity'           => $service->max_capacity,
            'color'                  => $service->color,
            'deposit_type'           => $service->deposit_type,
            'deposit'                => $service->deposit,
            'is_active'              => $service->is_active,
            'hide_price'             => $service->hide_price,
            'hide_duration'          => $service->hide_duration,
        ];

        $newId = $this->serviceRepository->create($data);

        $this->serviceRepository->duplicateServiceStaff($id, $newId);
        $this->serviceRepository->duplicateExtras($id, $newId);
        $this->serviceRepository->duplicateTimesheet($id, $newId);
        $this->serviceRepository->duplicateSpecialDays($id, $newId);

        $this->serviceRepository->duplicateTranslations($id, $newId);
        $this->serviceRepository->duplicateData($id, $newId);

        return $newId;
    }

    public function getServicesForSelect(string $search): array
    {
        $services = $this->serviceRepository->getServices($search);

        $data = [];

        foreach ($services as $service) {
            $data[] = [
                'id'				=>	(int)$service['id'],
                'text'				=>	htmlspecialchars($service['text'])
            ];
        }

        return $data;
    }
}
