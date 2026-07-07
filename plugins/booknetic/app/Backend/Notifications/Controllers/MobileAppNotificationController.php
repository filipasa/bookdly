<?php

namespace BookneticApp\Backend\Notifications\Controllers;

use BookneticApp\Backend\Notifications\Services\MobileAppNotificationService;
use BookneticApp\Providers\Core\RestRequest;

class MobileAppNotificationController
{
    private MobileAppNotificationService $service;

    public function __construct(MobileAppNotificationService $service)
    {
        $this->service = $service;
    }

    public function getAll(RestRequest $request): array
    {
        $page = $request->param('page', 1, RestRequest::TYPE_INTEGER);
        $rowsPerPage = $request->param('rows_count', 10, RestRequest::TYPE_INTEGER);

        $notificationData = $this->service->getNotificationList($page, $rowsPerPage);

        return [
            'notifications' => $notificationData['data'],
            'count' => (int)$notificationData['count'],
        ];
    }

    public function getUnreadCount(): array
    {
        $count = $this->service->getUnreadCount();

        return [
            'count' => $count,
        ];
    }

    public function markAsRead(RestRequest $request): array
    {
        $notificationId = $request->param('id', 0, RestRequest::TYPE_INTEGER);

        $this->service->markAsRead($notificationId);

        return [];
    }

    public function markAllAsRead(): array
    {
        $this->service->markAllAsRead();

        return [];
    }

    public function delete(RestRequest $request): array
    {
        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);

        $this->service->delete($id);

        return [];
    }

    public function clear(): array
    {
        $this->service->clear();

        return [];
    }

    public function registerToken(RestRequest $request): array
    {
        $pushToken = $request->param('push_token', '', RestRequest::TYPE_STRING);
        $userId = get_current_user_id();

        if ($userId > 0 && !empty($pushToken)) {
            $this->service->registerToken($userId, $pushToken);
        }

        return [];
    }
}
