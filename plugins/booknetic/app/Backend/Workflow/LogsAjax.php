<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Workflow\Services\WorkflowLogService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;

class LogsAjax extends Controller
{
    private WorkflowLogService $logService;

    public function __construct(WorkflowLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * @throws CapabilitiesException
     */
    public function retry()
    {
        Capabilities::must('workflow_logs');

        $id = Post::int('id');

        try {
            $this->logService->retry($id);
        } catch (\Exception $e) {
            return $this->response(false, $e->getMessage());
        }

        return $this->response(true);
    }

    /**
     * @throws CapabilitiesException
     */
    public function details()
    {
        Capabilities::must('workflow_logs');

        $id = Post::int('id');

        try {
            $details = $this->logService->getDetails($id);
        } catch (\Exception $e) {
            return $this->response(false, $e->getMessage());
        }

        return $this->modalView('details', $details);
    }
}
