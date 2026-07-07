<?php

namespace BookneticApp\Backend\Locations\Mappers;

use BookneticApp\Backend\Locations\DTOs\Response\LocationCategoryResponse;
use BookneticApp\Models\LocationCategory;
use BookneticApp\Providers\DB\Collection;

class LocationCategoryMapper
{
    /**
     * @param LocationCategory|Collection $category
     * @return LocationCategoryResponse
     */
    public static function toResponse(Collection $category): LocationCategoryResponse
    {
        $dto = new LocationCategoryResponse();

        $dto->setId($category->id);
        $dto->setName($category->name);

        return $dto;
    }
}
