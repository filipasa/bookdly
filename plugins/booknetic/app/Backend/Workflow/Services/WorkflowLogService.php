<?php

namespace BookneticApp\Backend\Workflow\Services;

use BookneticApp\Backend\Workflow\DTOs\Response\WorkflowLogDetailsResponse;
use BookneticApp\Backend\Workflow\Repositories\WorkflowLogRepository;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Date;

class WorkflowLogService
{
    private WorkflowEventsManager $workflowEventsManager;
    private WorkflowLogRepository $logRepository;

    public function __construct(WorkflowEventsManager $workflowEventsManager, WorkflowLogRepository $logRepository)
    {
        $this->workflowEventsManager = $workflowEventsManager;
        $this->logRepository = $logRepository;
    }

    public function getLogsDataTableQuery(): QueryBuilder
    {
        return $this->logRepository->getLogsWithWorkflowQuery();
    }

    public function retry(int $id): void
    {
        $log = $this->logRepository->get($id);

        if ($log === null) {
            throw new \RuntimeException(bkntc__('Log entry not found.'));
        }

        if (empty($log->event_data)) {
            throw new \RuntimeException(bkntc__('Cannot retry: event data is not available for this log entry.'));
        }

        $driver = $this->workflowEventsManager->getDriverManager()->get($log->driver);

        if ($driver === null) {
            throw new \RuntimeException(bkntc__('Driver not found: %s', [$log->driver]));
        }

        $eventData = json_decode($log->event_data, true) ?: [];

        $actionSettings = new Collection([
            'data'        => $log->data,
            'when'        => $log->when,
            'workflow_id' => $log->workflow_id,
        ]);

        $status = 'success';
        $errorMessage = null;

        try {
            $driver->handle($eventData, $actionSettings, $this->workflowEventsManager->getShortcodeService());
        } catch (\Exception $e) {
            $status = 'error';
            $errorMessage = $e->getMessage();
        }

        $this->logRepository->insert([
            'workflow_id'   => $log->workflow_id,
            'when'          => $log->when,
            'driver'        => $log->driver,
            'date_time'     => Date::dateTimeSQL(),
            'data'          => $log->data,
            'event_data'    => $log->event_data,
            'status'        => $status,
            'error_message' => $errorMessage,
        ]);

        if ($status === 'error') {
            throw new \RuntimeException($errorMessage);
        }
    }

    public function deleteByIds(array $ids): void
    {
        $this->logRepository->delete($ids);
    }

    public function getDetails(int $id): WorkflowLogDetailsResponse
    {
        $log = $this->logRepository->getWithWorkflow($id);

        if ($log === null) {
            throw new \RuntimeException(bkntc__('Log entry not found.'));
        }

        $eventTitle = bkntc__('Test');

        if ($log->when !== 'send_test') {
            $event = $this->workflowEventsManager->get($log->when);
            $eventTitle = $event ? $event->getTitle() : $log->when;
        }

        $driver = $this->workflowEventsManager->getDriverManager()->get($log->driver);
        $driverName = $driver !== null ? $driver->getName() : $log->driver;

        return (new WorkflowLogDetailsResponse())
            ->setId((int)$log->id)
            ->setWorkflowName($log->workflow_name ?: '-')
            ->setDateTime(Date::dateTime($log->date_time))
            ->setEventTitle($eventTitle)
            ->setDriverName($driverName)
            ->setStatus($log->status ?? 'success')
            ->setErrorMessage($log->error_message)
            ->setEventData(json_decode($log->event_data, true) ?: [])
            ->setActionData(json_decode($log->data, true) ?: [])
            ->setCanRetry(!empty($log->event_data));
    }
}
