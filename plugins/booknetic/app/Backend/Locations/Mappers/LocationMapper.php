<?php

namespace BookneticApp\Backend\Locations\Mappers;

use BookneticApp\Backend\Locations\DTOs\Response\LocationResponse;
use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;

class LocationMapper
{
    /**
     * @param Location|Collection $location
     * @param string              $imageUrl  Pre-resolved image URL (from MediaService::getUrl)
     *
     * @return LocationResponse
     */
    public static function toResponse(Collection $location, string $imageUrl): LocationResponse
    {
        $dto = new LocationResponse();

        $dto->setId($location->id)
            ->setName($location->name)
            ->setImage($imageUrl)
            ->setAddress($location->address ?? '')
            ->setPhoneNumber($location->phone_number ?? '')
            ->setNotes($location->notes ?? '')
            ->setLatitude($location->latitude ?? '')
            ->setLongitude($location->longitude ?? '')
            ->setIsActive((bool)$location->is_active)
            ->setCategoryId((int)($location->category_id ?? 0));

        return $dto;
    }
}
