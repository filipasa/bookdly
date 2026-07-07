<?php

namespace BookneticApp\Backend\Appointments;

use BookneticApp\Backend\Appointments\Controllers\AppointmentAjaxController;
use BookneticApp\Backend\Appointments\Controllers\AppointmentController;
use BookneticApp\Backend\Appointments\Controllers\AppointmentRestController;
use BookneticApp\Backend\Appointments\Mappers\SelectOptionMapper;
use BookneticApp\Backend\Appointments\Middlewares\AppointmentMiddleware;
use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentPriceRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentRepository;
use BookneticApp\Backend\Appointments\Services\AppointmentDataTableService;
use BookneticApp\Backend\Appointments\Services\AppointmentService;
use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Backend\Appointments\Controllers\BusySlotsController;
use BookneticApp\Backend\Appointments\Controllers\BusySlotsAjaxController;
use BookneticApp\Backend\Appointments\Services\BusySlotsDataTableService;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class AppointmentsModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            ServiceExtraRepository::class,
            AppointmentRepository::class,
            AppointmentPriceRepository::class,
            AppointmentExtraRepository::class,
            AppointmentService::class,
            AppointmentDataTableService::class,
            AppointmentController::class,
            AppointmentAjaxController::class,
            AppointmentRestController::class,
            SelectOptionMapper::class,
            BusySlotsController::class,
            BusySlotsAjaxController::class,
            BusySlotsDataTableService::class,
        ]);
    }
    /**
     * @throws ReflectionException
     */
    public static function registerRoutes(): void
    {
        Route::get('appointments', Container::get(AppointmentController::class))->middleware(AppointmentMiddleware::class);
        Route::post('appointments', Container::get(AppointmentAjaxController::class))->middleware(AppointmentMiddleware::class);
        Route::get('busy_slots', Container::get(BusySlotsController::class))->middleware(AppointmentMiddleware::class);
        Route::post('busy_slots', Container::get(BusySlotsAjaxController::class))->middleware(AppointmentMiddleware::class);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('appointments');
        $controller = Container::get(AppointmentRestController::class);
        $router->get('', [$controller, 'getAll']);
        $router->get('(?P<id>\d+)', [$controller, 'get']);
        $router->post('', [$controller, 'create']);
        $router->put('(?P<id>\d+)', [$controller, 'update']);
        $router->delete('(?P<id>\d+)', [$controller, 'delete']);
        $router->put('(?P<id>\d+)/change-status', [$controller, 'changeStatus']);

        $router->get('statuses', [$controller, 'getStatuses']);

        $router->get('available-times', [$controller, 'getAvailableTimes']);
        $router->get('services', [$controller, 'getServices']);
        $router->get('staff', [$controller, 'getStaff']);
        $router->get('customers', [$controller, 'getCustomers']);
        $router->get('locations', [$controller, 'getLocations']);
        $router->get('filters', [$controller, 'getFilters']);
    }
}
