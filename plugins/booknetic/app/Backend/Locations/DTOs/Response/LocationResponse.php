<?php

namespace BookneticApp\Backend\Locations\DTOs\Response;

use JsonSerializable;

class LocationResponse implements JsonSerializable
{
    private int $id;

    private string $name;

    private string $image;

    private string $address;

    private string $phoneNumber;

    private string $notes;

    private string $latitude;

    private string $longitude;

    private bool $isActive;

    private int $categoryId = 0;

    private array $categories = [];

    public static function createEmpty(): LocationResponse
    {
        $instance = new self();

        $instance->setId(0);
        $instance->setName('');
        $instance->setImage('');
        $instance->setAddress('');
        $instance->setPhoneNumber('');
        $instance->setNotes('');
        $instance->setLatitude('');
        $instance->setLongitude('');
        $instance->setIsActive(false);
        $instance->setCategoryId(0);
        $instance->setCategories([]);

        return $instance;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): LocationResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): LocationResponse
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): LocationResponse
    {
        $this->image = $image;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): LocationResponse
    {
        $this->address = $address;

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): LocationResponse
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): LocationResponse
    {
        $this->notes = $notes;

        return $this;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): LocationResponse
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): LocationResponse
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): LocationResponse
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): LocationResponse
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): LocationResponse
    {
        $this->categories = $categories;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'image' => $this->getImage(),
            'address' => $this->getAddress(),
            'phone_number' => $this->getPhoneNumber(),
            'notes' => $this->getNotes(),
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
            'is_active' => $this->isActive(),
            'category_id' => $this->getCategoryId(),
        ];
    }
}
