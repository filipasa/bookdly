<?php

namespace BookneticApp\Backend\Boostore\DTOs\Response;

class SubscriptionResponse
{
    private string $planSlug;
    private string $status;
    private bool $cancelAtPeriodEnd;
    private ?string $canceledAt;

    public function __construct(string $planSlug, string $status, bool $cancelAtPeriodEnd, ?string $canceledAt)
    {
        $this->planSlug = $planSlug;
        $this->status = $status;
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        $this->canceledAt = $canceledAt;
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        return new self(
            (string) ($data['planSlug'] ?? ''),
            (string) ($data['status'] ?? ''),
            (bool) ($data['cancelAtPeriodEnd'] ?? false),
            $data['canceledAt'] ?? null
        );
    }

    public function getPlanSlug(): string
    {
        return $this->planSlug;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function getCanceledAt(): ?string
    {
        return $this->canceledAt;
    }

    public function hasPlanSlug(): bool
    {
        return ! empty($this->planSlug);
    }
}
