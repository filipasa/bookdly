<?php

namespace BookneticApp\Backend\Locations\DTOs\Response;

class LocationCategoryViewResponse
{
    private LocationCategoryResponse $locationCategory;

    /**
     * @return LocationCategoryResponse
     */
    public function getLocationCategory(): LocationCategoryResponse
    {
        return $this->locationCategory;
    }

    /**
     * @param LocationCategoryResponse $locationCategory
     * @return void
     */
    public function setLocationCategory(LocationCategoryResponse $locationCategory): void
    {
        $this->locationCategory = $locationCategory;
    }
}
