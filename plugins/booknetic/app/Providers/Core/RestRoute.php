<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Appointments\AppointmentsModule;
use BookneticApp\Backend\Base\Controllers\LoginRestController;
use BookneticApp\Backend\Customers\CustomerModule;
use BookneticApp\Backend\Locations\LocationsModule;
use BookneticApp\Backend\Notifications\NotificationsModule;
use BookneticApp\Backend\Payments\PaymentsModule;
use BookneticApp\Backend\Services\ServiceModule;
use BookneticApp\Backend\Staff\StaffModule;
use BookneticApp\Providers\Core\Dto\ParameterResolver;
use BookneticApp\Providers\Core\Exceptions\ValidationException;
use BookneticApp\Providers\Helpers\Helper;
use ReflectionException;
use BookneticApp\Providers\IoC\Container;
use WP_Application_Passwords;
use WP_Error;
use WP_REST_Request;
use WP_User;

class RestRoute
{
    private const API_VER = 'v1';
    private const API_PREFIX = 'booknetic';

    /**
     * @throws ReflectionException
     */
    public static function init(): void
    {
        // Allow CORS for Capacitor native platforms and localhost development origins
        add_filter('rest_pre_serve_request', function ($value) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (!empty($origin) && (str_contains($origin, 'localhost') || str_starts_with($origin, 'capacitor://') || str_starts_with($origin, 'http://localhost'))) {
                header("Access-Control-Allow-Origin: " . esc_url_raw($origin));
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
                header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With");
                header("Access-Control-Allow-Credentials: true");
            }
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                status_header(200);
                exit;
            }
            return $value;
        }, 10, 1);

        Container::add(LoginRestController::class);

        self::post('login', [Container::get(LoginRestController::class), 'login']);
        CustomerModule::registerRestRoutes();
        AppointmentsModule::registerRestRoutes();
        LocationsModule::registerRestRoutes();
        StaffModule::registerRestRoutes();
        PaymentsModule::registerRestRoutes();
        ServiceModule::registerRestRoutes();
        NotificationsModule::registerRestRoutes();
    }

    public static function get($route, $fn, $args = []): void
    {
        self::addRoute('GET', $route, $fn, $args);
    }

    public static function post($route, $fn, $args = []): void
    {
        self::addRoute('POST', $route, $fn, $args);
    }

    public static function put($route, $fn, $args = []): void
    {
        self::addRoute('PUT', $route, $fn, $args);
    }

    public static function delete($route, $fn, $args = []): void
    {
        self::addRoute('DELETE', $route, $fn, $args);
    }

    private static function getNamespace(): string
    {
        return self::API_PREFIX . '/' . self::API_VER;
    }

    private static function addRoute($method, $route, $fn, $args): void
    {
        add_action('rest_api_init', function () use ($method, $route, $fn, $args) {
            register_rest_route(self::getNamespace(), $route, [
                'methods'             => $method,
                'callback'            => function (WP_REST_Request $request) use ($fn) {
                    try {
                        Permission::setAsBackEnd();
                        Permission::setIsMobile(self::isAppPasswordRequest());
                        $restRequest = new RestRequest($request);

                        $resolvedArgs = ParameterResolver::resolve($fn, $restRequest);

                        if ($resolvedArgs !== null) {
                            $res = call_user_func_array($fn, $resolvedArgs);
                        } else {
                            $res = $fn($restRequest);
                        }

                        return is_array($res) || is_object($res) ? $res : [ 'error_msg' => bkntc__('Error') ];
                    } catch (ValidationException $e) {
                        return new WP_Error(
                            422,
                            $e->getMessage(),
                            [
                                'status' => 422,
                                'errors' => $e->getErrors(),
                            ]
                        );
                    } catch (\Exception $e) {
                        $statusCode = self::getStatusCode($e->getCode());

                        return new WP_Error(
                            $statusCode,
                            $e->getMessage(),
                            ['status' => $statusCode]
                        );
                    }
                },
                'args'                => $args,
                'permission_callback' => function () {
                    return current_user_can('read');
                },
            ]);
        });
    }

    private static function getStatusCode(int $code): int
    {
        switch ($code) {
            case 404:
                $statusCode = 404;
                break;
            case 400:
                $statusCode = 400;
                break;
            case 422:
                $statusCode = 422;
                break;
            case 409:
                $statusCode = 409;
                break;
            case 500:
                $statusCode = 500;
                break;
            default:
                $statusCode = 400;
        }

        return $statusCode;
    }

    public static function isAppPasswordRequest(): bool
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$header || stripos($header, 'Basic ') !== 0) {
            return false;
        }

        $decoded = base64_decode(substr($header, 6));

        if (!$decoded || !str_contains($decoded, ':')) {
            return false;
        }

        [$username, $password] = explode(':', $decoded, 2);

        $user = get_user_by('login', $username) ?: get_user_by('email', $username);

        if (!$user) {
            return false;
        }

        $passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);

        $appPasswordUuids = array_map(static fn ($item) => $item['uuid'], Helper::getOption('app_password', []));

        foreach ($passwords as $item) {
            if (!in_array($item['uuid'], $appPasswordUuids, true)) {
                continue;
            }

            $result = wp_authenticate_application_password($user, $username, $password);

            if ($result instanceof WP_User) {
                return true;
            }
        }

        return false;
    }
}
