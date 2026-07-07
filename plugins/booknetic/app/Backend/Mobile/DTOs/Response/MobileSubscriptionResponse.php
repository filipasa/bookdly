<?php

namespace BookneticApp\Backend\Mobile\DTOs\Response;

class MobileSubscriptionResponse
{
    private int $assignedSeatCount = 0;
    private int $totalSeatCount = 0;
    private int $seatCount = 0;
    private int $extraSeatCount = 0;
    private int $extraSeatCountOnRenewal = 0;
    private string $currency = '';
    private string $nextBillingDate = '';
    private string $nextBillingAmount = '';
    private bool $cancelAtPeriodEnd = false;
    private string $paymentMethodLabel = '';
    private MobileSubscriptionPlanResponse $plan;

    public function __construct()
    {
        $this->plan = MobileSubscriptionPlanResponse::fromArray(null);
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $instance = new self();

        $instance->assignedSeatCount = (int) ($data['assignedSeatCount'] ?? 0);
        $instance->totalSeatCount = (int) ($data['totalSeatCount'] ?? 0);
        $instance->seatCount = (int) ($data['seatCount'] ?? 0);
        $instance->extraSeatCount = (int) ($data['extraSeatCount'] ?? 0);
        $instance->extraSeatCountOnRenewal = (int) ($data['extraSeatCountOnRenewal'] ?? 0);
        $instance->currency = (string) ($data['currency'] ?? '');
        $instance->nextBillingDate = (string) ($data['nextBillingDate'] ?? '');
        $instance->nextBillingAmount = (string) ($data['nextBillingAmount'] ?? '');
        $instance->cancelAtPeriodEnd = (bool) ($data['cancelAtPeriodEnd'] ?? false);
        $instance->paymentMethodLabel = (string) ($data['paymentMethodLabel'] ?? '');
        $instance->plan = MobileSubscriptionPlanResponse::fromArray(
            is_array($data['plan'] ?? null) ? $data['plan'] : null
        );

        return $instance;
    }

    public function getAssignedSeatCount(): int
    {
        return $this->assignedSeatCount;
    }

    public function getTotalSeatCount(): int
    {
        return $this->totalSeatCount;
    }

    public function getSeatCount(): int
    {
        return $this->seatCount;
    }

    public function getExtraSeatCount(): int
    {
        return $this->extraSeatCount;
    }

    public function getExtraSeatCountOnRenewal(): int
    {
        return $this->extraSeatCountOnRenewal;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getNextBillingDate(): string
    {
        return $this->nextBillingDate;
    }

    public function getNextBillingAmount(): string
    {
        return $this->nextBillingAmount;
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function getPaymentMethodLabel(): string
    {
        return $this->paymentMethodLabel;
    }

    public function hasPaymentMethodLabel(): bool
    {
        return ! empty($this->paymentMethodLabel);
    }

    public function getPlan(): MobileSubscriptionPlanResponse
    {
        return $this->plan;
    }
}
