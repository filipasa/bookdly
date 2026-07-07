<?php

namespace BookneticApp\Backend\Mobile\DTOs\Response;

class PlanResponse
{
    private int $id = 0;

    private string $name = '';

    private string $description = '';

    private string $slug = '';

    private string $badgeText = '';

    private float $price = 0;

    private string $currency = '';

    private float $discountPrice = 0;

    private float $extraSeatPrice = 0;

    private int $extraSeatLimit = 0;

    private int $seatCount = 0;

    private array $features = [];

    private int $orderBy = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getBadgeText(): string
    {
        return $this->badgeText;
    }

    public function setBadgeText(string $badgeText): self
    {
        $this->badgeText = $badgeText;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getDiscountPrice(): float
    {
        return $this->discountPrice;
    }

    public function setDiscountPrice(float $discountPrice): self
    {
        $this->discountPrice = $discountPrice;

        return $this;
    }

    public function getExtraSeatPrice(): float
    {
        return $this->extraSeatPrice;
    }

    public function setExtraSeatPrice(float $extraSeatPrice): self
    {
        $this->extraSeatPrice = $extraSeatPrice;

        return $this;
    }

    public function getExtraSeatLimit(): int
    {
        return $this->extraSeatLimit;
    }

    public function setExtraSeatLimit(int $extraSeatLimit): self
    {
        $this->extraSeatLimit = $extraSeatLimit;

        return $this;
    }

    public function getSeatCount(): int
    {
        return $this->seatCount;
    }

    public function setSeatCount(int $seatCount): self
    {
        $this->seatCount = $seatCount;

        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): self
    {
        $this->features = $features;

        return $this;
    }

    public function getOrderBy(): int
    {
        return $this->orderBy;
    }

    public function setOrderBy(int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }
}
