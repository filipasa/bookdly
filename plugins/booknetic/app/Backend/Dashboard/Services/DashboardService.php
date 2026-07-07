<?php

namespace BookneticApp\Backend\Dashboard\Services;

use BookneticApp\Backend\Dashboard\DTOs\Response\UpcomingAppointmentsResponse;
use BookneticApp\Backend\Dashboard\Mappers\TodayAppointmentMapper;
use BookneticApp\Backend\Dashboard\Repositories\DashboardRepository;
use BookneticApp\Providers\Helpers\Date;

class DashboardService
{
    private DashboardRepository $repository;
    private TodayAppointmentMapper $mapper;

    public function __construct(DashboardRepository $repository, TodayAppointmentMapper $mapper)
    {
        $this->repository = $repository;
        $this->mapper     = $mapper;
    }

    public function getUpcomingAppointments(bool $showAll, int $offset, string $type = 'today', string $customStart = '', string $customEnd = ''): UpcomingAppointmentsResponse
    {
        $dateRange = $this->getDateRange($type, $customStart, $customEnd);

        $limit        = 10;
        $appointments = $this->repository->getAppointmentsByDateRange($showAll, $offset, $limit, $dateRange['start'], $dateRange['end']);
        $totalCount   = $this->repository->getAppointmentCountByDateRange($showAll, $dateRange['start'], $dateRange['end']);

        $result      = $this->mapper->toListResponse($appointments);
        $loadedSoFar = $offset + count($result);

        return $this->mapper->toUpcomingResponse(
            $result,
            $totalCount,
            bkntc__('%d of %d', [$loadedSoFar, $totalCount]),
            $loadedSoFar < $totalCount
        );
    }

    private function getDateRange(string $type, string $customStart, string $customEnd): array
    {
        switch ($type) {
            case 'yesterday':
                $start = Date::epoch('yesterday');
                $end   = Date::epoch('today');
                break;

            case 'tomorrow':
                $start = Date::epoch('tomorrow');
                $end   = Date::epoch('tomorrow', '+1 day');
                break;

            case 'this_week':
                $start = Date::epoch('monday this week');
                $end   = Date::epoch('monday next week');
                break;

            case 'last_week':
                $start = Date::epoch('monday previous week');
                $end   = Date::epoch('monday this week');
                break;

            case 'this_month':
                $start = Date::epoch(Date::format('Y-m-01'));
                $end   = Date::epoch(Date::format('Y-m-t'), '+1 day');
                break;

            case 'this_year':
                $start = Date::epoch(Date::format('Y-01-01'));
                $end   = Date::epoch(Date::format('Y-12-31'), '+1 day');
                break;

            case 'custom':
                if (empty($customStart) || empty($customEnd)) {
                    $start = Date::epoch('today');
                    $end   = Date::epoch('today', '+1 day');
                    break;
                }

                $start = Date::epoch(Date::reformatDateFromCustomFormat($customStart));
                $end   = Date::epoch(Date::reformatDateFromCustomFormat($customEnd), '+1 day');
                break;

            default: // 'today'
                $start = Date::epoch('today');
                $end   = Date::epoch('today', '+1 day');
                break;
        }

        return ['start' => $start, 'end' => $end];
    }
}
