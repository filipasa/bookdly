<?php

namespace BookneticApp\Backend\Workflow\DTOs\Response;

class WorkflowLogDetailsResponse
{
    private int $id;

    private string $workflowName;

    private string $dateTime;

    private string $eventTitle;

    private string $driverName;

    private string $status;

    private ?string $errorMessage;

    private array $eventData;

    private array $actionData;

    private bool $canRetry;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WorkflowLogDetailsResponse
    {
        $this->id = $id;

        return $this;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function setWorkflowName(string $workflowName): WorkflowLogDetailsResponse
    {
        $this->workflowName = $workflowName;

        return $this;
    }

    public function getDateTime(): string
    {
        return $this->dateTime;
    }

    public function setDateTime(string $dateTime): WorkflowLogDetailsResponse
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getEventTitle(): string
    {
        return $this->eventTitle;
    }

    public function setEventTitle(string $eventTitle): WorkflowLogDetailsResponse
    {
        $this->eventTitle = $eventTitle;

        return $this;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function setDriverName(string $driverName): WorkflowLogDetailsResponse
    {
        $this->driverName = $driverName;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): WorkflowLogDetailsResponse
    {
        $this->status = $status;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): WorkflowLogDetailsResponse
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }

    public function setEventData(array $eventData): WorkflowLogDetailsResponse
    {
        $this->eventData = $eventData;

        return $this;
    }

    public function getActionData(): array
    {
        return $this->actionData;
    }

    public function setActionData(array $actionData): WorkflowLogDetailsResponse
    {
        $this->actionData = $actionData;

        return $this;
    }

    public function canRetry(): bool
    {
        return $this->canRetry;
    }

    public function setCanRetry(bool $canRetry): WorkflowLogDetailsResponse
    {
        $this->canRetry = $canRetry;

        return $this;
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
