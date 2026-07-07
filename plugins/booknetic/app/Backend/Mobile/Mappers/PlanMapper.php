<?php

namespace BookneticApp\Backend\Mobile\Mappers;

use BookneticApp\Backend\Mobile\DTOs\Response\PlanResponse;

class PlanMapper
{
    /**
     * @return PlanResponse[]
     */
    public function toListResponse(array $data): array
    {
        return array_map([$this, 'toResponse'], $data);
    }

    public function toResponse(array $data): PlanResponse
    {
        return (new PlanResponse())
            ->setId($data['id'] ?? 0)
            ->setName($data['name'] ?? '')
            ->setDescription($data['description'] ?? '')
            ->setSlug($data['slug'] ?? '')
            ->setBadgeText($data['badge_text'] ?? '')
            ->setPrice($data['price'] ?? 0)
            ->setCurrency($data['currency'] ?? '')
            ->setDiscountPrice($data['discount_price'] ?? 0)
            ->setExtraSeatPrice($data['extra_seat_price'] ?? 0)
            ->setExtraSeatLimit($data['extra_seat_limit'] ?? 0)
            ->setSeatCount($data['seat_count'] ?? 0)
            ->setFeatures($data['features'] ?? [])
            ->setOrderBy($data['order_by'] ?? 0);
    }
}
