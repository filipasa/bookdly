<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Workflow\Services\WorkflowLogService;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Common\WorkflowDriversManager;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class LogsController extends Controller
{
    private WorkflowEventsManager $workflowEventsManager;
    private WorkflowDriversManager $workflowDriversManager;
    private WorkflowLogService $logService;

    public function __construct(WorkflowEventsManager $workflowEventsManager, WorkflowLogService $logService)
    {
        $this->workflowEventsManager = $workflowEventsManager;
        $this->workflowDriversManager = $workflowEventsManager->getDriverManager();
        $this->logService = $logService;
    }

    /**
     * @throws CapabilitiesException
     */
    public function index()
    {
        Capabilities::must('workflow_logs');

        $dataTable = new DataTableUI($this->logService->getLogsDataTableQuery());

        $dataTable->setIdFieldForQuery(WorkflowLog::getField('id'));

        $dataTable->setTitle(bkntc__('Workflow Logs'));

        $dataTable->addColumns(bkntc__('ID'), 'id');

        $dataTable->addColumns(bkntc__('DATE & TIME', [], false), function ($row) {
            return Date::dateTime($row['date_time']);
        }, ['order_by_field' => 'date_time']);

        $dataTable->addColumns(bkntc__('WORKFLOW NAME'), 'workflow_name');

        $dataTable->addColumns(bkntc__('EVENT'), function ($row) {
            if ($row['when'] === 'send_test') {
                return bkntc__('Test');
            }

            $event = $this->workflowEventsManager->get($row['when']);

            return $event ? $event->getTitle() : $row['when'];
        });

        $dataTable->addColumns(bkntc__('ACTION'), function ($row) {
            $driver = $this->workflowDriversManager->get($row['driver']);

            return $driver ? $driver->getName() : $row['driver'];
        });

        $dataTable->addColumns(bkntc__('STATUS'), function ($row) {
            $status = $row['status'];

            if ($status === 'error') {
                return '<button type="button" class="btn btn-xs btn-light-danger" style="cursor: initial">' . bkntc__('Failed') . '</button>';
            }

            return '<button type="button" class="btn btn-xs btn-light-success" style="cursor: initial">' . bkntc__('Success') . '</button>';
        }, ['is_html' => true]);

        $dataTable->addAction('details', bkntc__('Details'));
        $dataTable->addAction('retry', bkntc__('Retry'));
        $dataTable->addAction('delete', bkntc__('Delete'), function ($IDs) {
            $this->logService->deleteByIds($IDs);
        }, AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK);

        $dataTable->searchBy([
            WorkflowLog::getField('id'),
            Workflow::getField('name'),
        ]);

        $table = $dataTable->renderHTML();

        add_filter('bkntc_localization', static function ($localization) {
            $localization['Are you sure you want to retry this workflow?'] = bkntc__('Are you sure you want to retry this workflow?');
            $localization['Retry'] = bkntc__('Retry');
            $localization['Success'] = bkntc__('Success');

            return $localization;
        });

        $this->view('logs', ['table' => $table]);
    }
}
