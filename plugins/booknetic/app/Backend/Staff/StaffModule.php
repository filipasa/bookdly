<?php

namespace BookneticApp\Backend\Staff;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Staff\Controllers\StaffAjaxController;
use BookneticApp\Backend\Staff\Controllers\StaffController;
use BookneticApp\Providers\Core\Route;

class StaffModule implements IModule
{
    public static function registerDependencies(): void
    {
        // Services are now registered via #[Service], #[Repository], and #[Component] attributes on each class.
        // See: build-di-cache.php for cache generation.
    }

    public static function registerRoutes(): void
    {
        Route::get('staff', StaffController::class);
        Route::post('staff', StaffAjaxController::class);
    }

    public static function registerRestRoutes(): void
    {
        // Routes are now registered via #[ApiController] attributes on StaffRestController.
        // See: app/Backend/Staff/Controllers/StaffRestController.php
    }
}
