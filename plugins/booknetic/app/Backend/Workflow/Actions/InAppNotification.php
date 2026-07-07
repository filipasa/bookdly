<?php

namespace BookneticApp\Backend\Workflow\Actions;

use BookneticApp\Backend\Notifications\DTOs\Request\InAppNotificationRequest;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEventRegisterer;
use BookneticApp\Backend\Notifications\Services\InAppNotificationService;
use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvent;
use BookneticApp\Config;
use BookneticApp\Providers\Common\WorkflowDriver;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class InAppNotification extends WorkflowDriver
{
    protected $driver = 'in_app_notification';

    public function __construct()
    {
        $this->setName(bkntc__('In App Notification'));
        $this->setEditAction('workflow_actions', 'in_app_notification_view');
    }

    /**
     * @throws ReflectionException
     */
    public function handle($eventData, $actionSettings, $shortCodeService): void
    {
        $actionData = json_decode($actionSettings['data'], true);

        if (empty($actionData) || !isset($actionSettings['when'])) {
            throw new \RuntimeException(bkntc__('In-app notification action data is empty.'));
        }

        $to = $shortCodeService->replace($actionData['to'], $eventData);
        $title = $shortCodeService->replace($actionData['title'], $eventData);
        $message = $shortCodeService->replace($actionData[ 'message' ], $eventData);

        $action = $this->getAction($actionSettings['when']);

        if ($action === null) {
            throw new \RuntimeException(bkntc__('Notification event not found for: %s', [$actionSettings['when']]));
        }

        $actionData = [
            'url' => $action->getActionUrl(),
            'id' => $eventData[$action->getEntityName()] ?? '',
        ];

        $ids = array_unique(explode(',', $to));

        $service = Container::get(InAppNotificationService::class);

        foreach ($ids as $id) {
            if (get_user_by('ID', (int)$id) === false) {
                continue;
            }

            $request  = new InAppNotificationRequest();

            $request->setUserId($id);
            $request->setType('in_app_notification');
            $request->setTitle($title);
            $request->setMessage($message);
            $request->setActionType($action->getActionType());
            $request->setActionData(json_encode($actionData));

            $service->create($request);
        }

        Config::getWorkflowEventsManager()->setEnabled(Config::getWorkflowEventsManager()->isEnabled());
    }

    private function getAction(string $name): ?NotificationWorkflowEvent
    {
        return NotificationWorkflowEventRegisterer::getEventInstance($name);
    }
}
