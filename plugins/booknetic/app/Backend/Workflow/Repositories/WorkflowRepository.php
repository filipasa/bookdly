<?php

namespace BookneticApp\Backend\Workflow\Repositories;

use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\DB\Collection;

class WorkflowRepository
{
    /**
     * @param int $id
     * @return Workflow|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return Workflow::query()->get($id);
    }

    public function create(array $data): int
    {
        Workflow::query()->insert($data);

        return Workflow::lastId();
    }

    public function update(int $id, array $data): void
    {
        Workflow::query()
            ->where('id', $id)
            ->update($data);
    }

    public function getWorkflowData(int $id): array
    {
        $workflow = $this->get($id);

        if ($workflow === null) {
            return [];
        }

        if (empty($workflow['data'])) {
            return [];
        }

        return json_decode($workflow['data'], true) ?? [];
    }

    public function duplicate(int $id): int
    {
        $workflow = $this->get($id);

        if ($workflow === null) {
            throw new \RuntimeException(bkntc__('Workflow not found!'));
        }

        $newId = $this->create([
            'name'      => $workflow->name . ' (2)',
            'when'      => $workflow->when,
            'data'      => $workflow->data,
            'is_active' => $workflow->is_active,
        ]);

        $actions = WorkflowAction::query()->where('workflow_id', $id)->fetchAll();

        foreach ($actions as $action) {
            WorkflowAction::query()->insert([
                'workflow_id' => $newId,
                'driver'      => $action->driver,
                'data'        => $action->data,
                'is_active'   => $action->is_active,
            ]);
        }

        return $newId;
    }

    public function updateDataById(int $id, array $data): void
    {
        Workflow::query()
            ->where('id', $id)
            ->update([
                'data' => json_encode($data)
            ]);
    }
}
