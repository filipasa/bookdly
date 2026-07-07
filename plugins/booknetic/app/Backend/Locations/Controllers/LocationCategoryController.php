<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\Exceptions\HasLocationInThisCategoryException;
use BookneticApp\Backend\Locations\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Locations\Services\LocationCategoryService;
use BookneticApp\Models\LocationCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\IoC\Attributes\Component;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\DataTableUI;

#[Component]
class LocationCategoryController extends Controller
{
    private LocationCategoryService $service;

    public function __construct(LocationCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws CapabilitiesException
     */
    public function index(): void
    {
        Capabilities::must('location_categories');

        $query = $this->service->getTenantQuery();

        $dataTable = new DataTableUI($query);

        $dataTable
            ->setIdFieldForQuery(LocationCategory::getField('id'))
            ->setTitle(bkntc__('Location Categories'))
            ->addColumns(bkntc__('ID'), 'id')
            ->addColumns(bkntc__('Category Name'), 'name')
            ->addNewBtn(bkntc__('ADD CATEGORY'))
            ->addAction('edit', bkntc__('Edit'))
            ->addAction('delete', bkntc__('Delete'), [ $this, '_delete' ]);

        $dataTable->searchBy([LocationCategory::getField('name')]);

        $this->view('location-category', [
            'table' => $dataTable->renderHTML()
        ]);
    }

    /**
     * @throws NoCategorySelectedException
     * @throws CapabilitiesException
     * @throws HasLocationInThisCategoryException
     */
    public function _delete()
    {
        Capabilities::must('locations_delete_category');

        $ids = Post::array('ids');

        $this->service->delete($ids);
    }
}
