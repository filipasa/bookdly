<?php

namespace BookneticApp\Backend\Dashboard\DTOs\Response;

class UpcomingAppointmentsResponse implements \JsonSerializable
{
    private array $appointments;
    private int $totalCount;
    private string $countText;
    private bool $hasMore;

    public function getAppointments(): array
    {
        return $this->appointments;
    }

    public function setAppointments(array $appointments): UpcomingAppointmentsResponse
    {
        $this->appointments = $appointments;

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function setTotalCount(int $totalCount): UpcomingAppointmentsResponse
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    public function getCountText(): string
    {
        return $this->countText;
    }

    public function setCountText(string $countText): UpcomingAppointmentsResponse
    {
        $this->countText = $countText;

        return $this;
    }

    public function getHasMore(): bool
    {
        return $this->hasMore;
    }

    public function setHasMore(bool $hasMore): UpcomingAppointmentsResponse
    {
        $this->hasMore = $hasMore;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'appointments' => $this->appointments,
            'total_count'  => $this->totalCount,
            'count_text'   => $this->countText,
            'has_more'     => $this->hasMore,
        ];
    }
}
