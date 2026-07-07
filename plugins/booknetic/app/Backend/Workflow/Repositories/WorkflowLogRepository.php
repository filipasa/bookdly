<?php

namespace BookneticApp\Backend\Workflow\Repositories;

use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;

class WorkflowLogRepository
{
    public function getLogsWithWorkflowQuery(): QueryBuilder
    {
        return WorkflowLog::query()->leftJoin('workflow', ['name']);
    }

    /**
     * @param int $id
     * @return WorkflowLog|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return WorkflowLog::query()->get($id);
    }

    /**
     * @param int $id
     * @return WorkflowLog|Collection|null
     */
    public function getWithWorkflow(int $id): ?Collection
    {
        return WorkflowLog::query()->leftJoin('workflow', ['name'])
            ->where(WorkflowLog::getField('id'), $id)
            ->fetch();
    }

    public function insert(array $data): void
    {
        WorkflowLog::query()->insert($data);
    }

    public function delete(array $ids): void
    {
        WorkflowLog::query()
            ->where('id', $ids)
            ->delete();
    }
}
