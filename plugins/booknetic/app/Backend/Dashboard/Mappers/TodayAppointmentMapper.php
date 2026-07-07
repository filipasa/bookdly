<?php

namespace BookneticApp\Backend\Dashboard\Mappers;

use BookneticApp\Backend\Dashboard\DTOs\Response\TodayAppointmentResponse;
use BookneticApp\Backend\Dashboard\DTOs\Response\UpcomingAppointmentsResponse;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class TodayAppointmentMapper
{
    private ?array $statuses = null;

    private function getStatuses(): array
    {
        if ($this->statuses === null) {
            $this->statuses = Helper::getAppointmentStatuses();
        }

        return $this->statuses;
    }

    /**
     * @param Collection $appointment
     * @return TodayAppointmentResponse
     */
    public function toResponse(Collection $appointment): TodayAppointmentResponse
    {
        $statuses = $this->getStatuses();
        $status   = $statuses[$appointment->status] ?? null;
        $duration = (int)$appointment->ends_at - (int)$appointment->starts_at;

        $dto = new TodayAppointmentResponse();

        $dto->setId((int)$appointment->id)
            ->setIsDayBased($duration >= 86400)
            ->setTimeStart(Date::time($appointment->starts_at))
            ->setTimeEnd(Date::time($appointment->ends_at))
            ->setCustomerName(htmlspecialchars($appointment->customer_first_name . ' ' . $appointment->customer_last_name))
            ->setCustomerProfile(Helper::profileImage($appointment->customer_profile_image, 'Customers'))
            ->setServiceName(htmlspecialchars($appointment->service_name ?? ''))
            ->setStaffName(htmlspecialchars($appointment->staff_name ?? ''))
            ->setStaffProfile(Helper::profileImage($appointment->staff_profile_image, 'Staff'))
            ->setStatus($appointment->status)
            ->setStatusTitle($status ? $status['title'] : $appointment->status)
            ->setStatusColor($status ? $status['color'] : '#828f9a')
            ->setStatusIcon($status ? $status['icon'] : '')
            ->setDuration(Helper::secFormat($duration));

        return $dto;
    }

    /**
     * @param array $appointments
     * @return array<TodayAppointmentResponse>
     */
    public function toListResponse(array $appointments): array
    {
        return array_map([$this, 'toResponse'], $appointments);
    }

    public function toUpcomingResponse(array $appointmentDtos, int $totalCount, string $countText, bool $hasMore): UpcomingAppointmentsResponse
    {
        $dto = new UpcomingAppointmentsResponse();

        $dto->setAppointments($appointmentDtos)
            ->setTotalCount($totalCount)
            ->setCountText($countText)
            ->setHasMore($hasMore);

        return $dto;
    }
}
