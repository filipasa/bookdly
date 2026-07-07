<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Date;

class WorkflowEventsManager
{
    /**
     * @var WorkflowEvent[]
     */
    private $workflowEvents = [];

    /**
     * @var bool
     */
    private $isEnabled = true;

    /**
     * @var ShortCodeService
     */
    private $shortcodeService;

    /**
     * @var WorkflowDriversManager
     */
    private $driverManager;

    /**
     * Enable/disable all workflow events completely.
     * Returns previous state.
     * @param $enabled
     * @return bool
     */
    public function setEnabled($enabled)
    {
        $previousValue = $this->isEnabled();
        $this->isEnabled = $enabled;

        return $previousValue;
    }

    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @param $key
     * @param $instance
     * @return WorkflowEvent
     */
    public function register($key, $instance)
    {
        $this->workflowEvents[ $key ] = $instance;

        return $this->workflowEvents[ $key ];
    }

    /**
     * @return WorkflowEvent[]
     */
    public function getAll()
    {
        return $this->workflowEvents;
    }

    public function trigger($eventKey, $params, $filterClosure = false, $noTenant = false, $tenant_id = null, ?int $workflowId = null)
    {
        if ($this->isEnabled() === false) {
            return;
        }

        if (! array_key_exists($eventKey, $this->workflowEvents)) {
            return;
        }

        $workflows = Workflow::noTenant($noTenant)
            ->where('`when`', $eventKey)
            ->where('is_active', true);

        if ($tenant_id !== null) {
            $workflows->where('tenant_id', $tenant_id);
        }

        if (!empty($workflowId)) {
            $workflows->where('id', $workflowId);
        }

        $workflows = $workflows->fetchAll();

        if (is_callable($filterClosure)) {
            $workflows = array_filter($workflows, $filterClosure);
        }

        foreach ($workflows as $workflow) {
            /**
             * @var Workflow $workflow
             */
            $actions = $workflow->workflow_actions()->where('is_active', true)->fetchAll();

            foreach ($actions as $action) {
                $driver = $this->getDriverManager()->get($action[ 'driver' ]);

                if (empty($driver)) {
                    continue;
                }

                $status = 'success';
                $errorMessage = null;

                $action->when = $workflow->when;
                try {
                    $shouldSend = apply_filters('bkntc_workflow_should_execute_action', true, $params, $action, $workflow);

                    if ($shouldSend === false) {
                        continue;
                    }

                    $driver->handle($params, $action, $this->getShortcodeService());
                } catch (\Exception $exception) {
                    $status = 'error';
                    $errorMessage = $exception->getMessage();
                }

                WorkflowLog::query()->insert([
                    'workflow_id'   => $workflow->id,
                    'when'          => $eventKey,
                    'driver'        => $action['driver'],
                    'date_time'     => Date::dateTimeSQL(),
                    'data'          => $action['data'],
                    'event_data'    => json_encode($params),
                    'status'        => $status,
                    'error_message' => $errorMessage,
                ]);
            }
        }
    }

    /**
     * @param $key
     * @return WorkflowEvent
     */
    public function get($key)
    {
        if (! array_key_exists($key, $this->workflowEvents)) {
            $this->workflowEvents[ $key ] = new WorkflowEvent($key);
        }

        return $this->workflowEvents[ $key ];
    }

    /**
     * @return WorkflowDriversManager
     */
    public function getDriverManager()
    {
        return $this->driverManager;
    }

    /**
     * @param WorkflowDriversManager $driverManager
     */
    public function setDriverManager($driverManager)
    {
        $this->driverManager = $driverManager;
    }

    /**
     * @return ShortCodeService
     */
    public function getShortcodeService()
    {
        return $this->shortcodeService;
    }

    /**
     * @param ShortCodeService $shortcodeService
     */
    public function setShortcodeService($shortcodeService)
    {
        $this->shortcodeService = $shortcodeService;
    }

    public function handleTestSend(WorkflowDriver $driver, $actionSettings): void
    {
        $status = 'success';
        $errorMessage = null;

        try {
            $driver->handle(new Collection(), $actionSettings, $this->getShortcodeService());
        } catch (\Exception $e) {
            $status = 'error';
            $errorMessage = $e->getMessage();
            throw $e;
        } finally {
            WorkflowLog::insert([
                'workflow_id'   => $actionSettings['workflow_id'],
                'when'          => 'send_test',
                'driver'        => $driver->getDriver(),
                'date_time'     => Date::dateTimeSQL(),
                'data'          => is_string($actionSettings['data']) ? $actionSettings['data'] : json_encode($actionSettings['data']),
                'event_data'    => null,
                'status'        => $status,
                'error_message' => $errorMessage,
            ]);
        }
    }

    public function getList(): array
    {
        if (empty($this->workflowEvents)) {
            return [];
        }

        return array_keys($this->workflowEvents);
    }
}
