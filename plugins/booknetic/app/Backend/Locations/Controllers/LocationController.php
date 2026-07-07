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

        $categorySubQuery = LocationCategory::query()
            ->where('id', '=', DB::field('category_id', Location::getTableName()))
            ->select('name as category_name', true)
            ->limit(1);

        $dataTable = new DataTableUI(
            Location::query()
                ->select('*')
                ->selectSubQuery($categorySubQuery, 'category_name')
        );

        $dataTable->addAction(
            'enable',
            bkntc__('Enable'),
            [ $this, 'enable' ],
            AbstractDataTableUI::ACTION_FLAG_BULK
        );
        $dataTable->addAction(
            'disable',
            bkntc__('Disable'),
            [ $this, 'disable' ],
            AbstractDataTableUI::ACTION_FLAG_BULK
        );

        $dataTable->addAction('edit', bkntc__('Edit'));

        $dataTable->addAction(
            'duplicate',
            bkntc__('Duplicate'),
            [ $this, 'duplicate' ],
            AbstractDataTableUI::ACTION_FLAG_SINGLE
        );

        $dataTable->addAction('share', bkntc__('Share'));

        $dataTable->addAction(
            'delete',
            bkntc__('Delete'),
            [ $this, '_delete' ],
            AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK
        );

        $dataTable->setTitle(bkntc__('Locations'));
        $dataTable->addNewBtn(bkntc__('ADD LOCATION'));
        $dataTable->activateExportBtn();

        $dataTable->searchBy([ "name", 'address', 'phone_number', 'notes' ]);

        $dataTable->addColumns(bkntc__('ID'), 'id');

        $dataTable->addColumns(bkntc__('NAME'), function ($location) {
            return Helper::profileCard($location['name'], $location['image'], '', 'Locations');
        }, [ 'is_html' => true, 'order_by_field' => "name" ]);

        $dataTable->addColumns(bkntc__('CATEGORY'), 'category_name');
        $dataTable->addColumns(bkntc__('PHONE'), 'phone_number');
        $dataTable->addColumns(bkntc__('ADDRESS'), 'address');

        add_filter('bkntc_localization', function ($localization) {
            $localization['link_copied'] = bkntc__('Link copied!');

            return $localization;
        });

        $table = $dataTable->renderHTML();

        $this->view('index', [ 'table' => $table ]);
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
