<?php

namespace BookneticApp\Backend\Locations\DTOs\Request;

use BookneticApp\Backend\Locations\Exceptions\NameRequiredException;

class LocationCategoryRequest
{
    private string $name;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     * @throws NameRequiredException
     */
    public function setName(string $name): LocationCategoryRequest
    {
        if ($name === '') {
            throw new NameRequiredException();
        }

        $this->name = $name;

        return $this;
    }
}
