<?php

namespace BookneticApp\Backend\Dashboard\DTOs\Response;

class TodayAppointmentResponse implements \JsonSerializable
{
    private int $id;
    private bool $isDayBased;
    private string $timeStart;
    private string $timeEnd;
    private string $customerName;
    private string $customerProfile;
    private string $serviceName;
    private string $staffName;
    private string $staffProfile;
    private string $status;
    private string $statusTitle;
    private string $statusColor;
    private string $statusIcon;
    private string $duration;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TodayAppointmentResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getIsDayBased(): bool
    {
        return $this->isDayBased;
    }

    public function setIsDayBased(bool $isDayBased): TodayAppointmentResponse
    {
        $this->isDayBased = $isDayBased;

        return $this;
    }

    public function getTimeStart(): string
    {
        return $this->timeStart;
    }

    public function setTimeStart(string $timeStart): TodayAppointmentResponse
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    public function getTimeEnd(): string
    {
        return $this->timeEnd;
    }

    public function setTimeEnd(string $timeEnd): TodayAppointmentResponse
    {
        $this->timeEnd = $timeEnd;

        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): TodayAppointmentResponse
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerProfile(): string
    {
        return $this->customerProfile;
    }

    public function setCustomerProfile(string $customerProfile): TodayAppointmentResponse
    {
        $this->customerProfile = $customerProfile;

        return $this;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function setServiceName(string $serviceName): TodayAppointmentResponse
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    public function getStaffName(): string
    {
        return $this->staffName;
    }

    public function setStaffName(string $staffName): TodayAppointmentResponse
    {
        $this->staffName = $staffName;

        return $this;
    }

    public function getStaffProfile(): string
    {
        return $this->staffProfile;
    }

    public function setStaffProfile(string $staffProfile): TodayAppointmentResponse
    {
        $this->staffProfile = $staffProfile;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): TodayAppointmentResponse
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusTitle(): string
    {
        return $this->statusTitle;
    }

    public function setStatusTitle(string $statusTitle): TodayAppointmentResponse
    {
        $this->statusTitle = $statusTitle;

        return $this;
    }

    public function getStatusColor(): string
    {
        return $this->statusColor;
    }

    public function setStatusColor(string $statusColor): TodayAppointmentResponse
    {
        $this->statusColor = $statusColor;

        return $this;
    }

    public function getStatusIcon(): string
    {
        return $this->statusIcon;
    }

    public function setStatusIcon(string $statusIcon): TodayAppointmentResponse
    {
        $this->statusIcon = $statusIcon;

        return $this;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): TodayAppointmentResponse
    {
        $this->duration = $duration;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'               => $this->id,
            'is_day_based'     => $this->isDayBased,
            'time_start'       => $this->timeStart,
            'time_end'         => $this->timeEnd,
            'customer_name'    => $this->customerName,
            'customer_profile' => $this->customerProfile,
            'service_name'     => $this->serviceName,
            'staff_name'       => $this->staffName,
            'staff_profile'    => $this->staffProfile,
            'status'           => $this->status,
            'status_title'     => $this->statusTitle,
            'status_color'     => $this->statusColor,
            'status_icon'      => $this->statusIcon,
            'duration'         => $this->duration,
        ];
    }
}
