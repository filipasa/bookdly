<?php

namespace BookneticApp\Backend\Staff\Controllers;

use BookneticApp\Backend\Staff\DTOs\Request\GetAllStaffRequest;
use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Providers\Router\Attributes\ApiController;
use BookneticApp\Providers\Router\Attributes\FromQuery;
use BookneticApp\Providers\Router\Attributes\Route;
use BookneticApp\Providers\Router\Attributes\RouteGet;

#[ApiController]
#[Route('/staffs')]
class StaffRestController
{
    private StaffService $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    #[RouteGet]
    public function getAllActive(
        #[FromQuery]
        GetAllStaffRequest $request
    ): array {
        $staffList = $this->staffService->getStaffList(
            $request->search,
            $request->location,
            $request->service
        );

        return [
            'data' => $staffList,
        ];
    }
}
