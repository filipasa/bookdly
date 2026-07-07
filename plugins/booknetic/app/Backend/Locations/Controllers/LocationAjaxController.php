<?php

namespace BookneticApp\Backend\Locations\Controllers;

use BookneticApp\Backend\Locations\DTOs\Request\CreateLocationRequest;
use BookneticApp\Backend\Locations\DTOs\Request\UpdateLocationRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Backend\Locations\Exceptions\InvalidLocationIdException;
use BookneticApp\Backend\Locations\Exceptions\LocationLimitExceededException;
use BookneticApp\Backend\Locations\Exceptions\LocationNotFoundException;
use BookneticApp\Backend\Locations\Exceptions\NameRequiredException;
use BookneticApp\Backend\Locations\Services\LocationCategoryService;
use BookneticApp\Backend\Locations\Services\LocationService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Attributes\Component;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\TabUI;

#[Component]
class LocationAjaxController extends Controller
{
    private LocationService $service;
    private LocationCategoryService $categoryService;

    public function __construct(LocationService $service, LocationCategoryService $categoryService)
    {
        $this->service = $service;
        $this->categoryService = $categoryService;
    }

    /**
     * @throws CapabilitiesException
     * @throws LocationNotFoundException
     * @throws InvalidLocationIdException
     */
    public function add_new()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('locations_edit');

            $location = $this->service->get($id);
        } else {
            Capabilities::must('locations_add');

            try {
                $this->service->ensureLimitNotExceeded();
            } catch (LocationLimitExceededException $e) {
                $view = Helper::renderView('Base.view.modal.permission_denied', [
                    'text' => $e->getMessage()
                ]);

                return $this->response(true, [ 'html' => $view ]);
            }

            $location = LocationResponse::createEmpty();
        }

        $categories = $this->categoryService->getAll();
        $location->setCategories($categories);

        TabUI::get('locations_add_new')
             ->item('details')
             ->setTitle(bkntc__('Location Details'))
             ->addView(__DIR__ . '/view/tab/add_new_location_details.php')
             ->setPriority(1);

        return $this->modalView('add_new', $location);
    }

    /**
     * @throws CapabilitiesException
     * @throws NameRequiredException
     * @throws LocationLimitExceededException
     */
    public function create()
    {
        Capabilities::must('locations_add');

        $dto = new CreateLocationRequest();
        $dto->name      = Post::string('location_name');
        $dto->address           = Post::string('address');
        $dto->phone             = Post::string('phone');
        $dto->note              = Post::string('note');
        $dto->latitude          = Post::string('latitude');
        $dto->longitude         = Post::string('longitude');
        $dto->addressComponents = Post::string('address_components');
        $dto->categoryId        = Post::int('category_id');
        $dto->translations      = Post::string('translations');

        if (empty($dto->name)) {
            throw new NameRequiredException();
        }

        $id = $this->service->create($dto);

        $this->service->updateImage($id, $_FILES['image'] ?? []);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws CapabilitiesException
     * @throws NameRequiredException
     * @throws LocationNotFoundException
     */
    public function update()
    {
        Capabilities::must('locations_edit');

        $id = Post::int('id');

        $dto = new UpdateLocationRequest();
        $dto->name      = Post::string('location_name');
        $dto->address           = Post::string('address');
        $dto->phone             = Post::string('phone');
        $dto->note              = Post::string('note');
        $dto->latitude          = Post::string('latitude');
        $dto->longitude         = Post::string('longitude');
        $dto->addressComponents = Post::string('address_components');
        $dto->categoryId        = Post::int('category_id');
        $dto->translations      = Post::string('translations');

        if (empty($dto->name)) {
            throw new NameRequiredException();
        }

        $id = $this->service->update($id, $dto);

        $this->service->updateImage($id, $_FILES['image'] ?? []);

        return $this->response(true, [
            'id' => $id
        ]);
    }

    /**
     * @throws CapabilitiesException
     * @throws LocationNotFoundException|InvalidLocationIdException
     */
    public function toggleVisibility()
    {
        Capabilities::must('locations_edit');

        $id = Post::int('id');

        $this->service->toggleVisibility($id);

        return $this->response(true);
    }
}
