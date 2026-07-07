<?php

namespace BookneticApp\Backend\Dashboard;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Dashboard\Mappers\TodayAppointmentMapper;
use BookneticApp\Backend\Dashboard\Repositories\DashboardRepository;
use BookneticApp\Backend\Dashboard\Services\DashboardService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;

class DashboardModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            DashboardRepository::class,
            TodayAppointmentMapper::class,
            DashboardService::class,
            Controller::class,
            Ajax::class,
        ]);
    }

    public static function registerRoutes(): void
    {
        if (!Capabilities::tenantCan('dashboard')) {
            return;
        }

        Route::get('dashboard', Container::get(Controller::class));
        Route::post('dashboard', Container::get(Ajax::class));
    }
}
