<?php

namespace BookneticApp\Backend\Locations\DTOs\Response;

use JsonSerializable;

class LocationCategoryResponse implements JsonSerializable
{
    private int $id;
    private string $name;

    /**
     * @return LocationCategoryResponse
     */
    public static function createEmpty(): LocationCategoryResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setName('');

        return $instance;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): LocationCategoryResponse
    {
        $this->id = $id;

        return $this;
    }

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
     */
    public function setName(string $name): LocationCategoryResponse
    {
        $this->name = $name;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
