<?php

namespace BookneticApp\Backend\Notifications\Services;

use BookneticApp\Backend\Notifications\DTOs\Request\InAppNotificationRequest;
use BookneticApp\Backend\Notifications\Mappers\NotificationMapper;
use BookneticApp\Backend\Notifications\Repositories\InAppNotificationRepository;

class InAppNotificationService
{
    private InAppNotificationRepository $repository;

    public function __construct(InAppNotificationRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getNotificationList(int $page, int $rowsPerPage): array
    {
        $notificationData = $this->repository->getAll($page, $rowsPerPage);

        $count = $notificationData['count'];
        $notifications = $notificationData['data'];

        $notifications = (new NotificationMapper())->toListResponse($notifications);

        array_map(function ($notification) {
            $user = get_userdata($notification->getUserId());
            $notification->setUserLogin($user->user_login ?? null);
        }, $notifications);

        return [
            'count' => $count,
            'data' => $notifications
        ];
    }

    /**
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        if ($notificationId <= 0) {
            throw new \RuntimeException(bkntc__('Invalid notification ID'));
        }

        $notification = $this->repository->get($notificationId);

        if (!$notification) {
            throw new \RuntimeException(bkntc__('Notification not found'));
        }

        $this->repository->markAsRead($notificationId);
    }

    /**
     * @return void
     */
    public function markAllAsRead(): void
    {
        $this->repository->markAllAsRead();
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->repository->deleteAll();
    }

    public function create(InAppNotificationRequest $request): void
    {
        $data = [
            'user_id' => $request->getUserId(),
            'type' => $request->getType(),
            'title' => $request->getTitle(),
            'message' => $request->getMessage(),
            'action_type' => $request->getActionType(),
            'action_data' => $request->getActionData(),
        ];

        $this->repository->create($data);
    }
}
