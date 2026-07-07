<?php

namespace BookneticApp\Backend\Notifications\Repositories;

use BookneticApp\Models\Notification;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Date;

class InAppNotificationRepository
{
    private function getInAppNotificationQuery(): QueryBuilder
    {
        return Notification::query()->where('type', 'in_app_notification');
    }

    /**
     * @param int $page
     * @param int $rowsPerPage
     * @return array
     */
    public function getAll(int $page = 0, int $rowsPerPage = 0): array
    {
        $inAppNotificationQuery = $this->getInAppNotificationQuery();

        $query = $inAppNotificationQuery->orderBy('id DESC');

        $count = $query->count();

        if (!empty($page)) {
            $query->offset(($page - 1) * $rowsPerPage);
        }

        if (!empty($rowsPerPage)) {
            $query->limit($rowsPerPage);
        }

        $notifications = $query->fetchAll();

        return [
            'data' => $notifications,
            'count' => $count
        ];
    }

    /**
     * @param int $id
     * @return Collection|null
     */
    public function get(int $id): ?Collection
    {
        $inAppNotificationQuery = $this->getInAppNotificationQuery();

        return $inAppNotificationQuery->where('id', $id)
            ->whereIsNull('read_at')
            ->fetch();
    }

    /**
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        $inAppNotificationQuery = $this->getInAppNotificationQuery();

        $inAppNotificationQuery->where('id', $notificationId)
            ->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @return void
     */
    public function markAllAsRead(): void
    {
        $inAppNotificationQuery = $this->getInAppNotificationQuery();

        $inAppNotificationQuery->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @return void
     */
    public function deleteAll(): void
    {
        $inAppNotificationQuery = $this->getInAppNotificationQuery();

        $inAppNotificationQuery->delete();
    }

    /**
     * @param array $data
     * @return void
     */
    public function create(array $data): void
    {
        Notification::query()->withoutGlobalScope('user_id')->insert($data);
    }
}
