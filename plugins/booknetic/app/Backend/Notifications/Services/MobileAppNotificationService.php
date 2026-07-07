<?php

namespace BookneticApp\Backend\Notifications\Services;

use BookneticApp\Backend\Notifications\DTOs\Request\MobileAppNotificationRequest;
use BookneticApp\Backend\Notifications\Mappers\NotificationMapper;
use BookneticApp\Backend\Notifications\Repositories\MobileAppNotificationRepository;

class MobileAppNotificationService
{
    private MobileAppNotificationRepository $repository;
    private NotificationMapper $mapper;

    public function __construct(MobileAppNotificationRepository $repository, NotificationMapper $mapper)
    {
        $this->repository = $repository;
        $this->mapper = $mapper;
    }

    public function create(MobileAppNotificationRequest $request): void
    {
        $data = [
            'user_id' => $request->getUserId(),
            'type' => $request->getType(),
            'title' => $request->getTitle(),
            'message' => $request->getMessage()
        ];

        $this->repository->create($data);

        // Send FCM system push notification
        $userId = $request->getUserId();
        $token = get_user_meta($userId, 'booknetic_mobile_push_token', true);

        if (!empty($token)) {
            $this->sendPushNotification($token, $request->getTitle(), $request->getMessage());
        }
    }

    public function getNotificationList(int $page, int $rowsPerPage): array
    {
        $notificationData = $this->repository->getAll($page, $rowsPerPage);

        $count = $notificationData['count'];
        $notifications = $notificationData['data'];

        $notifications = $this->mapper->toListResponse($notifications);

        foreach ($notifications as $notification) {
            $user = get_userdata($notification->getUserId());
            $notification->setUserLogin($user->user_login ?? null);
        }

        return [
            'count' => $count,
            'data' => $notifications
        ];
    }

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

    public function markAllAsRead(): void
    {
        $this->repository->markAllAsRead();
    }

    public function clear(): void
    {
        $this->repository->deleteAll();
    }

    public function delete(int $notificationId): void
    {
        if ($notificationId <= 0) {
            throw new \RuntimeException(bkntc__('Invalid notification ID'));
        }

        $notification = $this->repository->get($notificationId);

        if (!$notification) {
            throw new \RuntimeException(bkntc__('Notification not found'));
        }

        $this->repository->delete($notificationId);
    }

    public function getUnreadCount(): int
    {
        return $this->repository->getUnreadCount();
    }

    public function registerToken(int $userId, string $token): void
    {
        update_user_meta($userId, 'booknetic_mobile_push_token', $token);
    }

    private function sendPushNotification(string $token, string $title, string $message): void
    {
        $serverKey = get_option('booknetic_fcm_server_key', '');
        
        if (empty($serverKey)) {
            return;
        }

        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = [
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ];

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
            ],
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];

        wp_remote_post($url, [
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => json_encode($payload),
            'timeout' => 5,
        ]);
    }
}
