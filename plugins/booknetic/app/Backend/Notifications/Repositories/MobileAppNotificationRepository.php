<?php

namespace BookneticApp\Backend\Notifications\Repositories;

use BookneticApp\Models\Notification;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Date;

class MobileAppNotificationRepository
{
    /**
     * @param array $data
     * @return void
     */
    public function create(array $data): void
    {
        Notification::query()->withoutGlobalScope('user_id')->insert($data);
    }

    /**
     * @return QueryBuilder
     */
    private function getMobileAppNotificationQuery(): QueryBuilder
    {
        return Notification::query()->where('type', 'mobile_app_notification');
    }

    /**
     * @param int $page
     * @param int $rowsPerPage
     * @return array
     */
    public function getAll(int $page = 0, int $rowsPerPage = 0): array
    {
        $inAppNotificationQuery = $this->getMobileAppNotificationQuery();

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
        return $this->getMobileAppNotificationQuery()
            ->where('id', $id)
            ->fetch();
    }

    /**
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        $this->getMobileAppNotificationQuery()
            ->where('id', $notificationId)
            ->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @return void
     */
    public function markAllAsRead(): void
    {
        $this->getMobileAppNotificationQuery()
            ->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @return void
     */
    public function deleteAll(): void
    {
        $this->getMobileAppNotificationQuery()
            ->delete();
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $this->getMobileAppNotificationQuery()
            ->where('id', $id)
            ->delete();
    }

    /**
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->getMobileAppNotificationQuery()
            ->whereIsNull('read_at')
            ->count();
    }
}
