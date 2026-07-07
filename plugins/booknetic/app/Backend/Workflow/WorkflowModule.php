<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Workflow\Repositories\WorkflowActionRepository;
use BookneticApp\Backend\Workflow\Repositories\WorkflowLogRepository;
use BookneticApp\Backend\Workflow\Services\WorkflowActionService;
use BookneticApp\Backend\Workflow\Services\WorkflowLogService;
use BookneticApp\Config;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\IoC\Container;

class WorkflowModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::add(WorkflowEventsManager::class, function () {
            return Config::getWorkflowEventsManager();
        });

        Container::addBulk([
            WorkflowLogRepository::class,
            WorkflowLogService::class,
            LogsController::class,
            LogsAjax::class,
            WorkflowActionRepository::class,
            WorkflowActionService::class,
        ]);
    }
}
