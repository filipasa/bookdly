<?php

namespace BookneticApp\Backend\Workflow\Actions;

use BookneticApp\Backend\Notifications\DTOs\Request\MobileAppNotificationRequest;
use BookneticApp\Backend\Notifications\Services\MobileAppNotificationService;
use BookneticApp\Config;
use BookneticApp\Providers\Common\WorkflowDriver;
use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class MobileAppNotification extends WorkflowDriver
{
    protected $driver = 'mobile_app_notification';

    public function __construct()
    {
        $this->setName(bkntc__('Mobile App Notification'));
        $this->setEditAction('workflow_actions', 'mobile_app_notification_view');
    }

    /**
     * @throws ReflectionException
     */
    public function handle($eventData, $actionSettings, $shortCodeService): void
    {
        $actionData = json_decode($actionSettings['data'], true);

        if (empty($actionData) || !isset($actionSettings['when'])) {
            return;
        }

        if (isset($actionData['run_workflows']) && !$actionData['run_workflows']) {
            return;
        }

        $to = $shortCodeService->replace($actionData['to'], $eventData);
        $title = $shortCodeService->replace($actionData['title'], $eventData);
        $message = $shortCodeService->replace($actionData['description'], $eventData);

        $ids = explode(',', $to);

        $ids = array_unique($ids);

        $service = Container::get(MobileAppNotificationService::class);
        $mobileClient = Container::get(FSCodeMobileAppClient::class);

        foreach ($ids as $id) {
            $user = get_user_by('ID', (int)$id);

            if ($user === false) {
                continue;
            }

            $request  = new MobileAppNotificationRequest();

            $request->setUserId((int)$id);
            $request->setType('mobile_app_notification');
            $request->setTitle($title);
            $request->setMessage($message);

            $service->create($request);

            try {
                $mobileClient->sendNotification($user->user_login, $title, $message);
            } catch (\Exception $e) {
            }
        }

        Config::getWorkflowEventsManager()->setEnabled(Config::getWorkflowEventsManager()->isEnabled());
    }
}
