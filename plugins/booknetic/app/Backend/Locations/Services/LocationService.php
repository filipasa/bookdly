<?php

namespace BookneticApp\Backend\Locations\Services;

use BookneticApp\Backend\Locations\DTOs\Request\CreateLocationRequest;
use BookneticApp\Backend\Locations\DTOs\Request\DisableLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\EnableLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\UpdateLocationRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Backend\Locations\Exceptions\InvalidLocationIdException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasAppointmentsException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasStaffMembersException;
use BookneticApp\Backend\Locations\Exceptions\LocationLimitExceededException;
use BookneticApp\Backend\Locations\Exceptions\LocationNotFoundException;
use BookneticApp\Backend\Locations\Mappers\LocationMapper;
use BookneticApp\Backend\Locations\Repositories\LocationAppointmentRepository;
use BookneticApp\Backend\Locations\Repositories\LocationRepository;
use BookneticApp\Backend\Locations\Repositories\LocationStaffRepository;
use BookneticApp\Providers\IoC\Attributes\Service;
use BookneticApp\Providers\Services\CapabilityService;
use BookneticApp\Providers\Services\MediaService;

#[Service]
class LocationService
{
    private LocationRepository $repository;
    private LocationStaffRepository $staffRepository;
    private LocationAppointmentRepository $appointmentRepository;
    private MediaService $mediaService;
    private CapabilityService $capabilityService;

    public function __construct(
        LocationRepository $repository,
        LocationStaffRepository $staffRepository,
        LocationAppointmentRepository $appointmentRepository,
        CapabilityService $capabilityService,
        MediaService $mediaService
    ) {
        $this->repository = $repository;
        $this->staffRepository = $staffRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->capabilityService = $capabilityService;
        $this->mediaService = $mediaService;
    }

    /**
     * @param int[] $ids
     *
     * @throws LocationHasAppointmentsException
     * @throws LocationHasStaffMembersException
     */
    public function deleteAll(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $staffCount = $this->staffRepository->getStaffCount($ids);

        if ($staffCount > 0) {
            throw new LocationHasStaffMembersException();
        }

        $appointmentsCount = $this->appointmentRepository->getAppointmentCount($ids);

        if ($appointmentsCount > 0) {
            throw new LocationHasAppointmentsException();
        }

        $locations = $this->repository->getAllByIds($ids);

        foreach ($locations as $location) {
            if (! empty($location->image)) {
                $this->mediaService->deleteUploadedFile($location->image, 'Location');
            }
        }

        $this->staffRepository->deleteLocations($ids);
        $this->repository->deleteAll($ids);
    }

    public function enable(EnableLocationsRequest $request): void
    {
        if (empty($request->ids)) {
            return;
        }

        $this->repository->updateAll($request->ids, [
            'is_active' => 1
        ]);
    }

    public function disable(DisableLocationsRequest $request): void
    {
        if (empty($request->ids)) {
            return;
        }

        $this->repository->updateAll($request->ids, [
            'is_active' => 0
        ]);
    }

    /**
     * @param int $id
     * @return LocationResponse
     * @throws LocationNotFoundException
     * @throws InvalidLocationIdException
     */
    public function get(int $id): LocationResponse
    {
        if ($id <= 0) {
            throw new InvalidLocationIdException();
        }

        $location = $this->repository->get($id);

        if ($location === null) {
            throw new LocationNotFoundException($id);
        }

        $imageUrl = $this->mediaService->getUrl($location->image ?? '', 'Locations');

        return LocationMapper::toResponse($location, $imageUrl);
    }

    /**
     * @throws LocationLimitExceededException
     */
    public function ensureLimitNotExceeded(): void
    {
        $locationCount = $this->repository->count();
        $allowedLimit  = $this->capabilityService->getLimit('locations_allowed_max_number');

        if ($allowedLimit > - 1 && $locationCount >= $allowedLimit) {
            throw new LocationLimitExceededException($allowedLimit);
        }
    }

    /**
     * @throws LocationLimitExceededException
     * @throws LocationNotFoundException
     */
    public function duplicate(int $id): int
    {
        $this->ensureLimitNotExceeded();

        $location = $this->repository->get($id);

        if ($location === null) {
            throw new LocationNotFoundException($id);
        }

        $newImage = $this->mediaService->copy((string)$location->image, 'Locations');

        $data = [
            'name'               => $location->name . ' (2)',
            'address'            => $location->address,
            'phone_number'       => $location->phone_number,
            'notes'              => $location->notes,
            'image'              => $newImage,
            'latitude'           => $location->latitude,
            'longitude'          => $location->longitude,
            'address_components' => $location->address_components,
            'is_active'          => $location->is_active,
            'category_id'        => $location->category_id,
        ];

        $newId = $this->repository->create($data);

        $this->repository->duplicateTranslations($id, $newId);
        $this->repository->duplicateData($id, $newId);

        return $newId;
    }

    /**
     * @throws LocationLimitExceededException
     */
    public function create(CreateLocationRequest $request): int
    {
        $this->ensureLimitNotExceeded();

        $data = [
            'name'               => $request->name,
            'address'            => $request->address,
            'phone_number'       => $request->phone,
            'notes'              => $request->note,
            'image'              => '',
            'latitude'           => $request->latitude,
            'longitude'          => $request->longitude,
            'address_components' => $request->addressComponents,
            'is_active'          => 1,
            'category_id'        => $request->categoryId ?: null,
        ];

        return $this->repository->create($data, $request->translations);
    }

    /**
     * @throws LocationNotFoundException
     */
    public function update(int $id, UpdateLocationRequest $request): int
    {
        $location = $this->repository->get($id);

        if ($location === null) {
            throw new LocationNotFoundException($id);
        }

        $data = [
            'name'               => $request->name,
            'address'            => $request->address,
            'phone_number'       => $request->phone,
            'notes'              => $request->note,
            'latitude'           => $request->latitude,
            'longitude'          => $request->longitude,
            'address_components' => $request->addressComponents,
            'category_id'        => $request->categoryId ?: null,
        ];

        $this->repository->update($id, $data, $request->translations);

        return $id;
    }

    /**
     * @throws LocationNotFoundException
     */
    public function updateImage(int $id, array $image): string
    {
        $location = $this->repository->get($id);

        if ($location === null) {
            throw new LocationNotFoundException($id);
        }

        $newImage = $this->mediaService->handleImageUpload($image, 'Locations');

        if (empty($newImage)) {
            return '';
        }

        if (! empty($location->image)) {
            $this->mediaService->deleteUploadedFile($location->image, 'Location');
        }

        $this->repository->update($id, ['image' => $newImage]);

        return $newImage;
    }

    /**
     * @param int $id
     * @throws InvalidLocationIdException
     * @throws LocationNotFoundException
     */
    public function toggleVisibility(int $id): void
    {
        if (! ($id > 0)) {
            throw new InvalidLocationIdException();
        }

        $location = $this->repository->get($id);

        if (! $location) {
            throw new LocationNotFoundException($id);
        }

        $newStatus = $location->is_active == 1 ? 0 : 1;

        $this->repository->update($id, [ 'is_active' => $newStatus ]);
    }

    public function getMyAllEnabledLocations(string $search): array
    {
        $locations = $this->repository->getMyAllEnabledLocations($search);

        $data = [];

        foreach ($locations as $location) {
            $data[] = [
                'id'	=> (int)$location['id'],
                'text'	=> htmlspecialchars($location['name']),
                'is_active' => (int)$location['is_active']
            ];
        }

        return $data;
    }

    /**
     * @throws LocationHasStaffMembersException
     * @throws LocationHasAppointmentsException
     * @throws LocationNotFoundException
     */
    public function delete(int $id): void
    {
        $staffCount = $this->staffRepository->getStaffCount([$id]);

        if ($staffCount > 0) {
            throw new LocationHasStaffMembersException();
        }

        $appointmentsCount = $this->appointmentRepository->getAppointmentCount([$id]);

        if ($appointmentsCount > 0) {
            throw new LocationHasAppointmentsException();
        }

        $location = $this->repository->get($id);

        if ($location === null) {
            throw new LocationNotFoundException($id);
        }

        if (! empty($location->image)) {
            $this->mediaService->deleteUploadedFile($location->image, 'Location');
        }

        $this->staffRepository->deleteLocations([$id]);
        $this->repository->delete($id);
    }
}
