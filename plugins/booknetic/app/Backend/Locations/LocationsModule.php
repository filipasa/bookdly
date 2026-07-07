<?php

namespace BookneticApp\Backend\Locations;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Locations\Controllers\LocationAjaxController;
use BookneticApp\Backend\Locations\Controllers\LocationCategoryAjaxController;
use BookneticApp\Backend\Locations\Controllers\LocationCategoryController;
use BookneticApp\Backend\Locations\Controllers\LocationController;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\UI\MenuUI;

use function bkntc__;

class LocationsModule implements IModule
{
    public static function registerDependencies(): void
    {
        // Services are now registered via #[Service] attributes on each class.
        // See: build-di-cache.php for cache generation.
    }

    public static function registerRestRoutes(): void
    {
        // Routes are now registered via #[ApiController] attributes on LocationRestController.
        // See: app/Backend/Locations/Controllers/LocationRestController.php
    }

    public static function registerRoutes(): void
    {
        if (! Capabilities::tenantCan('locations')) {
            return;
        }

        Route::get('locations', Container::get(LocationController::class));
        Route::post('locations', Container::get(LocationAjaxController::class));

        if (Capabilities::tenantCan('location_categories')) {
            Route::get('location_categories', Container::get(LocationCategoryController::class));
            Route::post('location_categories', Container::get(LocationCategoryAjaxController::class));
        }
    }

    public static function registerPermissions(): void
    {
        Capabilities::register('locations', bkntc__('Locations module'));
        Capabilities::register('locations_add', bkntc__('Add new'), 'locations');
        Capabilities::register('locations_edit', bkntc__('Edit'), 'locations');
        Capabilities::register('locations_delete', bkntc__('Delete'), 'locations');
        Capabilities::register('locations_add_category', bkntc__('Add category'), 'locations');
        Capabilities::register('locations_edit_category', bkntc__('Edit category'), 'locations');
        Capabilities::register('locations_delete_category', bkntc__('Delete category'), 'locations');
        Capabilities::register('location_categories', bkntc__('Location Categories module'));
    }

    public static function registerTenantPermissions(): void
    {
        Capabilities::registerLimit('locations_allowed_max_number', bkntc__('Allowed maximum Locations'));
        Capabilities::registerTenantCapability('locations', bkntc__('Locations module'));
    }

    public static function registerMenu()
    {
        if (! Capabilities::tenantCan('locations') || ! Capabilities::userCan('locations')) {
            return;
        }

        MenuUI::get('locations')
              ->setTitle(bkntc__('Locations'))
              ->setIcon('fa fa-location-arrow')
              ->setPriority(800);

        if (Capabilities::tenantCan('location_categories') && Capabilities::userCan('location_categories')) {
            MenuUI::get('locations')
                  ->subItem('location_categories')
                  ->setTitle(bkntc__('Location Categories'))
                  ->setIcon('fa fa-tags')
                  ->setPriority(100);
        }
    }

    public static function registerShortCodes(ShortCodeService $shortCodeService)
    {
        $shortCodeService->registerCategory('location_info', bkntc__('Location Info'));

        $shortCodeService->registerShortCode('location_name', [
            'name'     => bkntc__('Location name'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
        $shortCodeService->registerShortCode('location_address', [
            'name'     => bkntc__('Location address'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
        $shortCodeService->registerShortCode('location_image_url', [
            'name'     => bkntc__('Location image URL'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
        $shortCodeService->registerShortCode('location_phone_number', [
            'name'     => bkntc__('Location phone'),
            'category' => 'location_info',
            'depends'  => 'location_id',
            'kind'     => 'phone'
        ]);
        $shortCodeService->registerShortCode('location_notes', [
            'name'     => bkntc__('Location notes'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
        $shortCodeService->registerShortCode('location_google_maps_url', [
            'name'     => bkntc__('Location Google Maps URL'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
        $shortCodeService->registerShortCode('location_category_name', [
            'name'     => bkntc__('Location category name'),
            'category' => 'location_info',
            'depends'  => 'location_id'
        ]);
    }
}
