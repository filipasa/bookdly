<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\DTOs\Request\CreateLocationRequest;
use BookneticApp\Backend\Locations\DTOs\Request\DisableLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\EnableLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\GetAllLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\UpdateLocationRequest;
use BookneticApp\Backend\Locations\Exceptions\InvalidLocationIdException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasAppointmentsException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasStaffMembersException;
use BookneticApp\Backend\Locations\Exceptions\LocationLimitExceededException;
use BookneticApp\Backend\Locations\Exceptions\LocationNotFoundException;
use BookneticApp\Backend\Locations\Services\LocationService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Router\Attributes\ApiController;
use BookneticApp\Providers\Router\Attributes\FromBody;
use BookneticApp\Providers\Router\Attributes\FromForm;
use BookneticApp\Providers\Router\Attributes\FromQuery;
use BookneticApp\Providers\Router\Attributes\FromRoute;
use BookneticApp\Providers\Router\Attributes\Route;
use BookneticApp\Providers\Router\Attributes\RouteDelete;
use BookneticApp\Providers\Router\Attributes\RouteGet;
use BookneticApp\Providers\Router\Attributes\RoutePost;
use BookneticApp\Providers\Router\Attributes\RoutePut;

#[ApiController]
#[Route('/locations')]
class LocationRestController
{
    private LocationService $service;

    public function __construct(LocationService $locationService)
    {
        $this->service = $locationService;
    }

    #[RouteGet]
    public function getAll(
        #[FromQuery]
        GetAllLocationsRequest $request
    ): array {
        Capabilities::must('locations');

        $locations = $this->service->getMyAllEnabledLocations($request->search);

        return [
            'data' => $locations,
        ];
    }

    #[RoutePost('/enable')]
    public function enable(
        #[FromBody]
        EnableLocationsRequest $request
    ): array {
        $this->service->enable($request);

        return [];
    }

    #[RoutePost('/disable')]
    public function disable(
        #[FromBody]
        DisableLocationsRequest $request
    ): array {
        $this->service->disable($request);

        return [];
    }

    /**
     * @throws LocationNotFoundException
     * @throws InvalidLocationIdException
     */
    #[RouteGet('/{id}')]
    public function get(
        #[FromRoute('id')]
        int $id
    ): array {
        $location = $this->service->get($id);

        return [
            'data' => $location,
        ];
    }

    /**
     * @throws LocationLimitExceededException
     * @throws CapabilitiesException
     */
    #[RoutePost]
    public function create(
        #[FromBody]
        CreateLocationRequest $request
    ): array {
        Capabilities::must('locations_add');

        $id = $this->service->create($request);

        return [
            'id' => $id,
        ];
    }

    /**
     * @throws LocationNotFoundException
     * @throws CapabilitiesException
     */
    #[RoutePut('/{id}')]
    public function update(
        #[FromRoute('id')]
        int $id,
        #[FromBody]
        UpdateLocationRequest $request
    ): array {
        Capabilities::must('locations_edit');

        $id = $this->service->update($id, $request);

        return [
            'id' => $id,
        ];
    }

    /**
     * @throws LocationNotFoundException
     * @throws CapabilitiesException/
     */
    #[RoutePost('/{id}/image')]
    public function uploadImage(
        #[FromRoute('id')]
        int $id,
        #[FromForm('image')]
        array $image = []
    ): array {
        Capabilities::must('locations_edit');

        $imageName = $this->service->updateImage($id, $image);

        return [
            'image' => $imageName,
        ];
    }

    /**
     * @throws InvalidLocationIdException
     * @throws LocationNotFoundException
     * @throws CapabilitiesException
     */
    #[RoutePost('/{id}/toggle-visibility')]
    public function toggleVisibility(
        #[FromRoute('id')]
        int $id
    ): array {
        Capabilities::must('locations_edit');

        $this->service->toggleVisibility($id);

        return [];
    }

    /**
     * @throws LocationHasStaffMembersException
     * @throws LocationHasAppointmentsException
     * @throws LocationNotFoundException
     */
    #[RouteDelete('/{id}')]
    public function delete(
        #[FromRoute('id')]
        int $id
    ): array {
        Capabilities::must('locations_delete');

        $this->service->delete($id);

        return [];
    }
}
