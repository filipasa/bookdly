<?php

namespace BookneticApp\Backend\Locations\Services;

use BookneticApp\Backend\Locations\DTOs\Request\LocationCategoryRequest;
use BookneticApp\Backend\Locations\DTOs\Response\LocationCategoryResponse;
use BookneticApp\Backend\Locations\Exceptions\HasLocationInThisCategoryException;
use BookneticApp\Backend\Locations\Exceptions\LocationCategoryAlreadyExistException;
use BookneticApp\Backend\Locations\Exceptions\LocationCategoryNotFoundException;
use BookneticApp\Backend\Locations\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Locations\Mappers\LocationCategoryMapper;
use BookneticApp\Backend\Locations\Repositories\LocationCategoryRepository;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\IoC\Attributes\Service;

#[Service]
class LocationCategoryService
{
    private LocationCategoryRepository $repository;

    public function __construct(LocationCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @throws LocationCategoryNotFoundException
     */
    public function get($id): LocationCategoryResponse
    {
        $category = $this->repository->get($id);

        if ($category === null) {
            throw new LocationCategoryNotFoundException();
        }

        return LocationCategoryMapper::toResponse($category);
    }

    /**
     * @throws LocationCategoryAlreadyExistException
     */
    public function create(LocationCategoryRequest $request): int
    {
        $checkIfNameExist = $this->repository->checkIfNameExist($request->getName());

        if ($checkIfNameExist) {
            throw new LocationCategoryAlreadyExistException();
        }

        $data = [
            'name' => $request->getName(),
        ];

        return $this->repository->create($data);
    }

    /**
     * @throws LocationCategoryNotFoundException
     * @throws LocationCategoryAlreadyExistException
     */
    public function update(int $id, LocationCategoryRequest $request): void
    {
        $category = $this->repository->get($id);

        if ($category === null) {
            throw new LocationCategoryNotFoundException();
        }

        $checkIfNameExist = $this->repository->checkIfNameExist($request->getName(), $id);

        if ($checkIfNameExist) {
            throw new LocationCategoryAlreadyExistException();
        }

        $data = [
            'name' => $request->getName(),
        ];

        $this->repository->update($id, $data);
    }

    /**
     * @return QueryBuilder
     */
    public function getTenantQuery(): QueryBuilder
    {
        return $this->repository->getTenantQuery();
    }

    /**
     * @param array $ids
     * @return void
     * @throws HasLocationInThisCategoryException
     * @throws NoCategorySelectedException
     */
    public function delete(array $ids): void
    {
        if (empty($ids)) {
            throw new NoCategorySelectedException();
        }

        $locations = $this->repository->getLocationByCategory($ids);

        if ($locations !== 0) {
            throw new HasLocationInThisCategoryException();
        }

        $this->repository->delete($ids);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * @return array
     */
    public function getAllWithTranslations(): array
    {
        return $this->repository->getAllWithTranslations();
    }
}
