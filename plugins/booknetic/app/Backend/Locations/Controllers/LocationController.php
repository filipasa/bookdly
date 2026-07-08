<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\DTOs\Request\DisableLocationsRequest;
use BookneticApp\Backend\Locations\DTOs\Request\EnableLocationsRequest;
use BookneticApp\Backend\Locations\Exceptions\LocationHasAppointmentsException;
use BookneticApp\Backend\Locations\Exceptions\LocationHasStaffMembersException;
use BookneticApp\Backend\Locations\Services\LocationService;
use BookneticApp\Models\Location;
use BookneticApp\Models\LocationCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Attributes\Component;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Request\Post;

#[Component]
class LocationController extends Controller
{
    private LocationService $service;

    public function __construct(LocationService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws CapabilitiesException
     */
    public function index()
    {
        Capabilities::must('locations');

        // Intercept DataTable actions manually since we bypass DataTableUI
        if (isset($_POST['fs-data-table-action'])) {
            $action = Post::string('fs-data-table-action');
            $ids = Post::array('ids');
            if ($action === 'delete') {
                $this->_delete($ids);
                return $this->response(true);
            }
            if ($action === 'duplicate') {
                $this->duplicate($ids);
                return $this->response(true);
            }
            if ($action === 'enable') {
                $this->enable($ids);
                return $this->response(true);
            }
            if ($action === 'disable') {
                $this->disable($ids);
                return $this->response(true);
            }
        }

        add_filter('bkntc_localization', function ($localization) {
            $localization['link_copied'] = bkntc__('Link copied!');
            return $localization;
        });

        $categorySubQuery = LocationCategory::query()
            ->where('id', '=', DB::field('category_id', Location::getTableName()))
            ->select('name as category_name', true)
            ->limit(1);

        $locations = Location::query()
            ->select('*')
            ->selectSubQuery($categorySubQuery, 'category_name')
            ->fetchAll();

        // Build category map for filter dropdown [id => name]
        $categoriesRaw = LocationCategory::fetchAll();
        $categories = [];
        foreach ($categoriesRaw as $cat) {
            $categories[$cat['id']] = $cat['name'];
        }

        $this->view('index', [
            'locations'  => $locations,
            'categories' => $categories,
        ]);
    }

    /**
     * @param int[] $ids
     *
     * @throws CapabilitiesException
     * @throws LocationHasAppointmentsException
     * @throws LocationHasStaffMembersException
     */
    public function _delete(array $ids)
    {
        Capabilities::must('locations_delete');

        $this->service->deleteAll($ids);
    }

    /**
     * @param int[] $ids
     *
     * @throws CapabilitiesException
     */
    public function duplicate(array $ids)
    {
        Capabilities::must('locations_add');

        if (empty($ids)) {
            return;
        }

        $this->service->duplicate((int) $ids[0]);
    }

    /**
     * @param int[] $ids
     *
     * @throws CapabilitiesException
     */
    public function enable(array $ids)
    {
        Capabilities::must('locations');

        $dto = new EnableLocationsRequest();
        $dto->ids = $ids;

        $this->service->enable($dto);
    }

    /**
     * @param int[] $ids
     *
     * @throws CapabilitiesException
     */
    public function disable(array $ids)
    {
        Capabilities::must('locations');

        $dto = new DisableLocationsRequest();
        $dto->ids = $ids;

        $this->service->disable($dto);
    }
}
