<?php

namespace BookneticApp\Backend\Workflow\Services;

use BookneticApp\Backend\Workflow\Repositories\WorkflowActionRepository;
use BookneticApp\Providers\DB\Collection;

class WorkflowActionService
{
    private WorkflowActionRepository $repository;
    public function __construct(WorkflowActionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function update(int $id, array $data): void
    {
        $this->repository->update($id, $data);
    }

    public function get(int $id): ?Collection
    {
        return $this->repository->get($id);
    }
}
