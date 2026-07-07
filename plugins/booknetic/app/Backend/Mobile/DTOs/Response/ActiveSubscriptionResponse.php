<?php

namespace BookneticApp\Backend\Mobile\DTOs\Response;

class ActiveSubscriptionResponse
{
    private string $type;

    private ?array $subscription;

    public function __construct(string $type, ?array $subscription)
    {
        $this->type = $type;
        $this->subscription = $subscription;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSubscription(): ?array
    {
        return $this->subscription;
    }

    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    public function isNone(): bool
    {
        return $this->type === 'none';
    }
}
