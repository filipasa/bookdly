<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\DTOs\Request\LocationCategoryRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationCategoryResponse;
use BookneticApp\Backend\Locations\DTOs\Response\LocationCategoryViewResponse;
use BookneticApp\Backend\Locations\Exceptions\HasLocationInThisCategoryException;
use BookneticApp\Backend\Locations\Exceptions\LocationCategoryAlreadyExistException;
use BookneticApp\Backend\Locations\Exceptions\LocationCategoryNotFoundException;
use BookneticApp\Backend\Locations\Exceptions\NameRequiredException;
use BookneticApp\Backend\Locations\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Locations\Services\LocationCategoryService;
use BookneticApp\Models\LocationCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\IoC\Attributes\Component;
use BookneticApp\Providers\Request\Post;

#[Component]
class LocationCategoryAjaxController extends Controller
{
    private LocationCategoryService $service;

    public function __construct(LocationCategoryService $service)
    {
        $this->service = $service;
    }

    public function add_new()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('locations_edit_category');

            $locationCategory = $this->service->get($id);
        } else {
            Capabilities::must('locations_add_category');

            $locationCategory = LocationCategoryResponse::createEmpty();
        }

        LocationCategory::handleTranslation($id);

        $viewResponse = new LocationCategoryViewResponse();

        $viewResponse->setLocationCategory($locationCategory);

        return $this->modalView('add_new_category', $viewResponse);
    }

    /**
     * @throws NameRequiredException
     * @throws LocationCategoryAlreadyExistException
     */
    public function create()
    {
        Capabilities::must('locations_add_category');

        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->create($request);

        LocationCategory::handleTranslation($id);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws LocationCategoryNotFoundException|LocationCategoryAlreadyExistException|NameRequiredException
     */
    public function update()
    {
        Capabilities::must('locations_edit_category');

        $id      = Post::int('id');
        $request = $this->prepareSaveRequestDTO();

        $this->service->update($id, $request);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws NameRequiredException
     */
    private function prepareSaveRequestDTO(): LocationCategoryRequest
    {
        $name = Post::string('name');

        $request = new LocationCategoryRequest();

        $request->setName($name);

        return $request;
    }

    /**
     * @throws NoCategorySelectedException
     * @throws HasLocationInThisCategoryException
     * @throws CapabilitiesException
     */
    public function delete()
    {
        Capabilities::must('locations_delete_category');

        $ids = Post::array('ids');

        $this->service->delete($ids);

        return $this->response(true);
    }
}
