<?php

namespace BookneticApp\Backend\Boostore\DTOs\Response;

class AddonPriceResponse
{
    private float $old;
    private float $current;

    public function __construct(float $old, float $current)
    {
        $this->old = $old;
        $this->current = $current;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['old'] ?? 0),
            (float) ($data['current'] ?? 0)
        );
    }

    public function getOld(): float
    {
        return $this->old;
    }

    public function getCurrent(): float
    {
        return $this->current;
    }

    public function hasDiscount(): bool
    {
        return $this->current < $this->old;
    }

    public function isFree(): bool
    {
        return $this->current === 0.0;
    }
}
