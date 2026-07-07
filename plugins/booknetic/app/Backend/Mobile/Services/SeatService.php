<?php

namespace BookneticApp\Backend\Mobile\Services;

use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\DTOs\Response\SeatAssignResponse;
use BookneticApp\Backend\Mobile\Exceptions\AppPasswordCreatingException;
use BookneticApp\Backend\Mobile\Exceptions\NeedToSubscribeException;
use BookneticApp\Backend\Mobile\Exceptions\SeatAvailabilityException;
use BookneticApp\Backend\Mobile\Exceptions\UserNotFoundException;
use BookneticApp\Providers\Helpers\Helper;
use WP_Application_Passwords;

class SeatService
{
    private const appName = 'booknetic_mobile_app';

    private FSCodeMobileAppClient $client;

    public function __construct(FSCodeMobileAppClient $client)
    {
        $this->client = $client;
    }

    public function getAll(): array
    {
        $response = $this->client->getSeats();

        if (empty($response->getAssignedSeats())) {
            return [
                'availableSeats' => $response->getAvailableSeatCount(),
                'users' => [],
            ];
        }

        $assignedUsers = [];

        foreach ($response->getAssignedSeats() as $item) {
            $username = $item['username'];
            $user = get_user_by('login', $username);

            $fullName = $user ? trim($user->first_name . ' ' . $user->last_name) : 'Deleted User';
            if (empty($fullName)) {
                $fullName = $user->display_name;
            }

            $assignedUsers[] = [
                'seatId' => $item['id'],
                'username' => $username,
                'image' => $user ? get_avatar_url($user->ID) : null,
                'full_name' => $fullName,
                'isLoggedIn' => $item['isLoggedIn'],
                'loggedInDevice' => $item['loggedInDevice'],
                'isDisabledOnRenewal' => $item['isDisabledOnRenewal']
            ];
        }

        return [
            'availableSeats' => $response->getAvailableSeatCount(),
            'users' => $assignedUsers,
        ];
    }

    /**
     * @throws AppPasswordCreatingException
     * @throws UserNotFoundException
     */
    public function assign(int $id): SeatAssignResponse
    {
        $user = get_user_by('id', $id);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $response = $this->client->assignSeat($user->user_login ?? '');

        if (empty($response->getData()) || !isset($response->getData()['id'])) {
            throw new AppPasswordCreatingException();
        }

        $seatId = $response->getData()['id'];

        $this->deleteUnusedAppPasswords($seatId);

        $appPassword = $this->createAppPassword($seatId, $user->ID);

        return new SeatAssignResponse($user->user_login, $appPassword);
    }

    public function getWpUsers(string $q): array
    {
        $args = [
            'search'         => '*' . $q . '*',
            'search_columns' => ['user_login'],
            'role__in'       => [
                'booknetic_staff',
                'administrator',
                'booknetic_saas_tenant'
            ]
        ];
        $users = get_users($args);

        return array_map(static fn ($user) => [
            'id'   => $user->ID,
            'text' => $user->user_login,
        ], $users);
    }

    /**
     * @throws UserNotFoundException
     * @throws AppPasswordCreatingException
     */
    public function regenerateAppPassword(string $username, int $seatId): string
    {
        $user = get_user_by('login', $username);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->deleteUnusedAppPasswords($seatId);

        $this->logout($seatId);

        return $this->createAppPassword($seatId, $user->ID);
    }

    /**
     * @param int $seatId
     * @return void
     */
    public function logout(int $seatId): void
    {
        $this->client->logoutSeat($seatId);
    }

    public function unassign(int $seatId): void
    {
        $this->client->unassignSeat($seatId);
        $this->deleteUnusedAppPasswords($seatId);
    }

    /**
     * @throws NeedToSubscribeException
     * @throws SeatAvailabilityException
     */
    public function hasAvailable(): void
    {
        $response = $this->client->getActiveSubscription();
        $type = $response['type'] ?? 'none';
        $subscription = $response['subscription'] ?? null;

        if ($type === 'none') {
            throw new NeedToSubscribeException();
        }

        // For product subscriptions, seats are managed by the product
        // The API handles seat availability at the seats endpoint level
        if ($type === 'product') {
            return;
        }

        if ($subscription === null) {
            throw new NeedToSubscribeException();
        }

        if ($subscription['totalSeatCount'] - $subscription['assignedSeatCount'] <= 0) {
            throw new SeatAvailabilityException();
        }
    }

    private function deleteUnusedAppPasswords(int $seatId): void
    {
        $appPasswordOption = Helper::getOption('app_password', []);

        foreach ($appPasswordOption as $index => $password) {
            if ($password['seat_id'] !== $seatId) {
                continue;
            }

            WP_Application_Passwords::delete_application_password($password['user_id'], $password['uuid']);

            unset($appPasswordOption[$index]);
        }
    }

    /**
     * @throws AppPasswordCreatingException
     */
    private function createAppPassword(int $seatId, int $userId)
    {
        $result = WP_Application_Passwords::create_new_application_password($userId, [ 'name' => self::appName ]);

        if (empty($result) || is_wp_error($result)) {
            throw new AppPasswordCreatingException();
        }

        $appPasswordOption[] = [
            'seat_id' => $seatId,
            'uuid' =>  $result[1]['uuid'],
            'user_id' => $userId,
        ];

        Helper::setOption('app_password', $appPasswordOption);

        return $result[0];
    }
}
