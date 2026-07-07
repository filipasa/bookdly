<?php

namespace BookneticApp\Backend\Mobile\DTOs\Response;

class MobileSubscriptionPlanResponse
{
    private string $name = '';
    private float $seatPrice = 0;
    private int $extraSeatLimit = 0;

    public static function fromArray(?array $data): self
    {
        $instance = new self();

        if ($data === null) {
            return $instance;
        }

        $instance->name = (string) ($data['name'] ?? '');
        $instance->seatPrice = (float) ($data['seatPrice'] ?? 0);
        $instance->extraSeatLimit = (int) ($data['extraSeatLimit'] ?? 0);

        return $instance;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSeatPrice(): float
    {
        return $this->seatPrice;
    }

    public function getExtraSeatLimit(): int
    {
        return $this->extraSeatLimit;
    }

    public function hasExtraSeatLimit(): bool
    {
        return $this->extraSeatLimit > 0;
    }
}
