<?php

namespace BookneticApp;

use BookneticApp\Backend\Appearance\AppearanceModule;
use BookneticApp\Backend\Base\Repository\DataRepository;
use BookneticApp\Backend\Base\Repository\HolidayRepository;
use BookneticApp\Backend\Base\Repository\ServiceRepository as BaseServiceRepository;
use BookneticApp\Backend\Base\Repository\SpecialDayRepository;
use BookneticApp\Backend\Base\Repository\TimesheetRepository;
use BookneticApp\Backend\Base\Repository\TranslationRepository;
use BookneticApp\Backend\Appointments\AppointmentsModule;
use BookneticApp\Backend\Appointments\Helpers\AppointmentChangeStatus;
use BookneticApp\Backend\Base\Services\TranslationService;
use BookneticApp\Backend\Customers\CustomerModule;
use BookneticApp\Backend\Dashboard\DashboardModule;
use BookneticApp\Backend\Locations\LocationsModule;
use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\MobileAppModule;
use BookneticApp\Backend\Notifications\NotificationsModule;
use BookneticApp\Backend\Payments\PaymentsModule;
use BookneticApp\Backend\Services\ServiceModule;
use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Backend\Settings\SettingAjax;
use BookneticApp\Backend\Staff\StaffModule;
use BookneticApp\Backend\Workflow\LogsAjax;
use BookneticApp\Backend\Workflow\LogsController;
use BookneticApp\Backend\Workflow\WorkflowModule;
use BookneticApp\Backend\Workflow\Actions\InAppNotification;
// use BookneticApp\Backend\Workflow\Actions\MobileAppNotification;
use BookneticApp\Backend\Workflow\Actions\SetBookingStatusAction;
use BookneticApp\Backend\Workflow\Actions\SetCustomerCategory;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\CoreStaffBusySlot;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Common\Divi\includes\BookneticDivi;
use BookneticApp\Providers\Common\Elementor\BookneticElementor;
use BookneticApp\Providers\Common\LocalPayment;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Common\PluginService;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Common\ShortCodeServiceImpl;
use BookneticApp\Providers\Common\WorkflowDriversManager;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Notifications;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Data\WorkflowEventFilterData;
use BookneticApp\Providers\Core\Tasks\LicenseSyncTask;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClientFactory;
use BookneticApp\Providers\FSCode\Services\FSCodeApiService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\IoC\ContainerLoader;
use BookneticApp\Providers\IoC\ServiceScanner;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\UI\TabUI;
use ReflectionException;

class Config
{
    /**
     * @var WorkflowDriversManager
     */
    private static $workflowDriversManager;

    /**
     * @var WorkflowEventsManager
     */
    private static $workflowEventsManager;

    /**
     * @var ShortCodeService
     */
    private static $shortCodeService;

    private static $capabilityCache;

    /**
     * @return WorkflowDriversManager
     */
    public static function getWorkflowDriversManager(): WorkflowDriversManager
    {
        return self::$workflowDriversManager;
    }

    /**
     * @return WorkflowEventsManager
     */
    public static function getWorkflowEventsManager(): WorkflowEventsManager
    {
        return self::$workflowEventsManager;
    }

    /**
     * @return ShortCodeService
     */
    public static function getShortCodeService(): ShortCodeService
    {
        return self::$shortCodeService;
    }

    public static function getCapabilityCache()
    {
        return self::$capabilityCache;
    }

    public static function setCapabilityCache($capabilityCache): void
    {
        self::$capabilityCache = $capabilityCache;
    }

    public static function load(): void
    {
        self::registerDependencies();
        self::registerTasks();
        self::$shortCodeService = new ShortCodeService();
        self::$workflowDriversManager = new WorkflowDriversManager();

        self::$workflowEventsManager = new WorkflowEventsManager();
        self::$workflowEventsManager->setDriverManager(self::$workflowDriversManager);
        self::$workflowEventsManager->setShortcodeService(self::$shortCodeService);

        self::registerTextDomain();

        add_action('bkntc_init', [ self::class, 'init' ]);

        add_action('elementor/widgets/register', [BookneticElementor::class, 'registerWidgets']);
        add_action('activated_plugin', [self::class , 'detectPluginActivation'], 10, 2);
        add_action('divi_extensions_init', function () {
            new BookneticDivi();
        });

        add_action('template_include', function ($template) {
            if (isset($_GET['bkntc_preview']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $shortcode = Post::string('shortcode');
                echo do_shortcode($shortcode);
                print_late_styles();
                print_footer_scripts();
                exit;
            }

            return $template;
        });

        add_action('profile_update', [ self::class, 'detectUserUpdate' ], 10, 2);
        add_action('profile_update', [ self::class, 'detectProfileUpdate' ], 10, 1);
    }

    public static function init(): void
    {
        self::registerCoreUserCapabilities();
        self::registerCoreTenantCapabilities();
        self::registerCoreShortCodes();
        self::registerCoreWorkflowEvents();
        NotificationsModule::registerNotificationWorkflowEvents();
        self::registerCoreWorkflowActions();
        self::registerLocalPaymentGateway();
        self::registerCorePricesName();
        self::registerWPUserRoles();
        self::registerHardCodedUserRules();
        self::registerCronActions();

        add_action('bkntc_backend', [ self::class, 'registerCoreRoutes' ]);
        add_action('bkntc_backend', [ self::class, 'registerCoreMenus' ]);

        add_filter('bkntc_busy_slots', [ self::class, 'handleBusySlotsFilter' ], 10, 2);
        add_filter('bkntc_calendar_events', [ self::class, 'handleCalendarEventsFilter' ], 10, 4);

        add_filter('woocommerce_prevent_admin_access', function () {
            return false;
        });

        add_action('bkntcsaas_tenant_created', function ($tenantId) {
            $workflows_table = \BookneticApp\Providers\DB\DB::table('workflows');
            $workflow_actions_table = \BookneticApp\Providers\DB\DB::table('workflow_actions');

            $generate_html_body = function ($title, $greeting, $message, $rowsHtml, $has_cta = false, $cta_text = '', $cta_url = '') {
                $logo = "https://bookdly.co.uk/wp-content/uploads/booknetic/base/bookdly_logo.png";
                $cta_html = '';
                if ($has_cta) {
                    $cta_html = '<div style="margin: 30px 0; text-align: center;">
                        <a href="' . $cta_url . '" style="display: inline-block; padding: 12px 24px; background-color: #6c70dc; color: #ffffff !important; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px;">' . $cta_text . '</a>
                    </div>';
                }
                return '<div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e1e8ed; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
                <div style="background-color: #ffffff; padding: 30px 40px 20px 40px; text-align: center; border-bottom: 1px solid #f0f2f5;">
                    <img src="' . $logo . '" alt="Bookdly" style="height: 40px; max-height: 40px; border: 0;">
                </div>
                <div style="padding: 40px; color: #2e384d; line-height: 1.6;">
                    <h1 style="font-size: 20px; font-weight: 600; margin-top: 0; margin-bottom: 16px; color: #1e2530;">' . $title . '</h1>
                    <p style="margin-top: 0; margin-bottom: 24px; font-size: 15px; color: #2e384d;">' . $greeting . '</p>
                    <p style="margin-top: 0; margin-bottom: 24px; font-size: 15px; color: #2e384d;">' . $message . '</p>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; background-color: #f8fafc; border-radius: 6px; overflow: hidden; border: 1px solid #eef2f6;">
                        ' . $rowsHtml . '
                    </table>
                    ' . $cta_html . '
                    <p style="margin-top: 0; margin-bottom: 0; font-size: 15px; color: #2e384d;">Best regards,<br><strong>{company_name}</strong></p>
                </div>
                <div style="background-color: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #828f9a; border-top: 1px solid #eef2f6;">
                    <p style="margin: 0;">Powered by Bookdly</p>
                </div>
            </div>';
            };

            $make_row = function ($label, $val, $isLast = false) {
                $border = $isLast ? 'none' : '1px solid #eef2f6';
                return '<tr>
                    <td style="padding: 14px 18px; font-size: 14px; border-bottom: ' . $border . '; font-weight: 600; color: #828f9a; width: 35%;">' . $label . '</td>
                    <td style="padding: 14px 18px; font-size: 14px; border-bottom: ' . $border . '; color: #2e384d;">' . $val . '</td>
                </tr>';
            };

            $w1_rows = $make_row('Service', '{service_name}') .
                       $make_row('Date', '{appointment_date}') .
                       $make_row('Time', '{appointment_start_time}') .
                       $make_row('Staff', '{staff_name}', true);
            $w1_body = $generate_html_body('Appointment Confirmed', 'Dear {customer_full_name},', 'Thank you for your booking! Your appointment details are below:', $w1_rows);

            $w2_rows = $make_row('Customer', '{customer_full_name}') .
                       $make_row('Service', '{service_name}') .
                       $make_row('Date', '{appointment_date}') .
                       $make_row('Time', '{appointment_start_time}', true);
            $w2_body = $generate_html_body('New Booking Assigned', 'Hello {staff_name},', 'A new appointment has been booked for you:', $w2_rows);

            $w3_rows = $make_row('Service', '{service_name}') .
                       $make_row('Date', '{appointment_date}') .
                       $make_row('Time', '{appointment_start_time}', true);
            $w3_body = $generate_html_body('Status Updated', 'Dear {customer_full_name},', 'Your appointment status has been updated to <strong>{appointment_status}</strong>.', $w3_rows);

            $w4_rows = $make_row('Service', '{service_name}') .
                       $make_row('New Date', '{appointment_date}') .
                       $make_row('New Time', '{appointment_start_time}', true);
            $w4_body = $generate_html_body('Appointment Rescheduled', 'Dear {customer_full_name},', 'Your appointment has been rescheduled. Below are your new details:', $w4_rows);

            $w5_rows = $make_row('Service', '{service_name}') .
                       $make_row('Date', '{appointment_date}') .
                       $make_row('Amount Paid', '<span style="font-weight: 600; color: #53d56c;">{appointment_paid_price}</span>') .
                       $make_row('Payment Method', '<span style="text-transform: uppercase;">{appointment_payment_method}</span>', true);
            $w5_body = $generate_html_body('Payment Received', 'Dear {customer_full_name},', 'We have successfully received your payment for your appointment.', $w5_rows);

            $w6_rows = $make_row('Service', '{service_name}') .
                       $make_row('Date', '{appointment_date}') .
                       $make_row('Time', '{appointment_start_time}') .
                       $make_row('Staff', '{staff_name}', true);
            $w6_body = $generate_html_body('Upcoming Appointment Reminder', 'Dear {customer_full_name},', 'This is a friendly reminder that you have an upcoming appointment tomorrow:', $w6_rows);

            $default_workflows = [
                [
                    'name' => 'New booking confirmation (Customer)',
                    'when' => 'booking_new',
                    'data' => '{"locations":[],"services":[],"staffs":[],"statuses":[],"locale":"","called_from":"","categories":[],"location_categories":[]}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{customer_email}',
                                'subject' => 'Appointment Confirmation - {service_name}',
                                'body' => $w1_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ],
                [
                    'name' => 'New booking notification (Staff)',
                    'when' => 'booking_new',
                    'data' => '{"locations":[],"services":[],"staffs":[],"statuses":[],"locale":"","called_from":"","categories":[],"location_categories":[]}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{staff_email}',
                                'subject' => 'New Appointment Booked - {service_name}',
                                'body' => $w2_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ],
                [
                    'name' => 'Appointment Status Changed (Customer)',
                    'when' => 'booking_status_changed',
                    'data' => '{"statuses":[],"prev_statuses":[],"locations":[],"services":[],"staffs":[],"locale":"","called_from":"","categories":[],"location_categories":[]}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{customer_email}',
                                'subject' => 'Appointment Status Update: {appointment_status} - {service_name}',
                                'body' => $w3_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ],
                [
                    'name' => 'Appointment Rescheduled (Customer)',
                    'when' => 'booking_rescheduled',
                    'data' => '{"locations":[],"services":[],"staffs":[],"locale":"","for_each_customer":false,"called_from":"","categories":[],"location_categories":[]}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{customer_email}',
                                'subject' => 'Appointment Rescheduled - {service_name}',
                                'body' => $w4_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ],
                [
                    'name' => 'Appointment Paid Notification (Customer)',
                    'when' => 'appointment_paid',
                    'data' => '{"locale":""}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{customer_email}',
                                'subject' => 'Payment Confirmation - {service_name}',
                                'body' => $w5_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ],
                [
                    'name' => 'Appointment Reminder',
                    'when' => 'booking_starts',
                    'data' => '{"offset_sign":"before","offset_value":24,"offset_type":"hour","statuses":[],"locations":[],"services":[],"staffs":[],"locale":"","for_each_customer":true,"categories":[],"location_categories":[]}',
                    'actions' => [
                        [
                            'driver' => 'email',
                            'data' => json_encode([
                                'to' => '{customer_email}',
                                'subject' => 'Reminder: Your appointment is tomorrow - {service_name}',
                                'body' => $w6_body,
                                'attachments' => ''
                            ])
                        ]
                    ]
                ]
            ];


            global $wpdb;
            foreach ($default_workflows as $dw) {
                $wpdb->insert($workflows_table, [
                    'name' => $dw['name'],
                    'when' => $dw['when'],
                    'data' => $dw['data'],
                    'is_active' => 1,
                    'tenant_id' => $tenantId
                ]);
                $workflow_id = $wpdb->insert_id;

                foreach ($dw['actions'] as $da) {
                    $wpdb->insert($workflow_actions_table, [
                        'workflow_id' => $workflow_id,
                        'driver' => $da['driver'],
                        'data' => $da['data'],
                        'is_active' => 1
                    ]);
                }
            }
        });
    }


    public static function registerTextDomain(): void
    {
        add_action('plugins_loaded', function () {
            /**
             * SaaS versiyada Language Switcher olur tenantlar uchun (yuxari sag menyunun yaninda).
             * Orda sechilen locale save edilir sesssionda ve bu ashagidaki kod sechilen locale`ni aktivleshdirmey uchundur.
             */
            if (Helper::isSaaSVersion()) {
                $language = Session::get('active_language');
                LocalizationService::setLanguage($language);
            }

            LocalizationService::loadTextdomain();
        });
    }

    public static function registerCoreUserCapabilities(): void
    {
        LocationsModule::registerPermissions();
        PaymentsModule::registerPermissions();
        MobileAppModule::registerPermissions();

        Capabilities::register('dashboard', bkntc__('Dashboard module'));

        Capabilities::register('appointments', bkntc__('Appointments module'));
        Capabilities::register('appointments_add', bkntc__('Add new'), 'appointments');
        Capabilities::register('appointments_edit', bkntc__('Edit'), 'appointments');
        Capabilities::register('appointments_change_status', bkntc__('Change status'), 'appointments');
        Capabilities::register('appointments_delete', bkntc__('Delete'), 'appointments');

        Capabilities::register('busy_slots', bkntc__('Busy Slots module'));
        Capabilities::register('busy_slots_add', bkntc__('Add new'), 'busy_slots');
        Capabilities::register('busy_slots_edit', bkntc__('Edit'), 'busy_slots');
        Capabilities::register('busy_slots_delete', bkntc__('Delete'), 'busy_slots');

        Capabilities::register('appearance', bkntc__('Appearance module'));
        Capabilities::register('appearance_add', bkntc__('Add new'), 'appearance');
        Capabilities::register('appearance_edit', bkntc__('Edit'), 'appearance');
        Capabilities::register('appearance_delete', bkntc__('Delete'), 'appearance');
        Capabilities::register('appearance_select', bkntc__('Select'), 'appearance');

        Capabilities::register('calendar', bkntc__('Calendar module'));

        CustomerModule::registerPermissions();

        Capabilities::register('payments', bkntc__('Payments module'));
        Capabilities::register('payments_edit', bkntc__('Edit'), 'payments');

        Capabilities::register('workflow', bkntc__('Workflow module'));
        Capabilities::register('workflow_logs', bkntc__('Workflow logs'), 'workflow');
        Capabilities::register('workflow_add', bkntc__('Add new'), 'workflow');
        Capabilities::register('workflow_edit', bkntc__('Edit'), 'workflow');
        Capabilities::register('workflow_delete', bkntc__('Delete'), 'workflow');

        Capabilities::register('services', bkntc__('Services module'));
        Capabilities::register('services_add', bkntc__('Add new'), 'services');
        Capabilities::register('services_edit', bkntc__('Edit'), 'services');
        Capabilities::register('services_delete', bkntc__('Delete'), 'services');
        Capabilities::register('services_add_category', bkntc__('Add new category'), 'services');
        Capabilities::register('services_edit_category', bkntc__('Edit category'), 'services');
        Capabilities::register('services_delete_category', bkntc__('Delete category'), 'services');
        Capabilities::register('services_add_extra', bkntc__('Add new extra'), 'services');
        Capabilities::register('services_edit_extra', bkntc__('Edit extra'), 'services');
        Capabilities::register('services_delete_extra', bkntc__('Delete extra'), 'services');
        Capabilities::register('service_categories', bkntc__('Service Categories module'));

        Capabilities::register('staff', bkntc__('Staff module'));
        Capabilities::register('staff_edit', bkntc__('Edit'), 'staff');
        Capabilities::register('staff_add', bkntc__('Add new'), 'staff');
        Capabilities::register('staff_delete', bkntc__('Delete'), 'staff');
        Capabilities::register('staff_allow_to_login', bkntc__('Allow to login'), 'staff');
        Capabilities::register('staff_delete_wordpress_account', bkntc__('Allow to delete associated WordPress account'), 'staff');

        Capabilities::register('roles', bkntc__('Roles module'));
        Capabilities::register('roles_add', bkntc__('Add new'), 'roles');
        Capabilities::register('roles_edit', bkntc__('Edit'), 'roles');
        Capabilities::register('roles_delete', bkntc__('Delete'), 'roles');

        Capabilities::register('settings', bkntc__('Settings'));
        Capabilities::register('settings_general', bkntc__('General settings'), 'settings');
        Capabilities::register('settings_advanced', bkntc__('Advanced settings'), 'settings');
        Capabilities::register('settings_calendar', bkntc__('Calendar Settings'), 'settings');
        Capabilities::register('settings_booking_panel_steps', bkntc__('Booking Steps'), 'settings');
        Capabilities::register('settings_booking_panel_labels', bkntc__('Labels'), 'settings');
        Capabilities::register('page_settings', bkntc__('Pages'), 'settings');
        Capabilities::register('settings_payments', bkntc__('Payment settings'), 'settings');
        Capabilities::register('settings_payment_gateways', bkntc__('Payment methods'), 'settings');
        Capabilities::register('settings_deposit', bkntc__('Deposit settings'), 'settings');
        Capabilities::register('settings_company', bkntc__('Company details'), 'settings');
        Capabilities::register('settings_business_hours', bkntc__('Business Hours'), 'settings');
        Capabilities::register('settings_holidays', bkntc__('Holidays'), 'settings');
        Capabilities::register('settings_integrations_facebook_api', bkntc__('Continue with Facebook'), 'settings');
        Capabilities::register('settings_integrations_google_login', bkntc__('Continue with Google'), 'settings');
        Capabilities::register('settings_backup', bkntc__('Export & Import data'), 'settings');

        if (! Helper::isSaaSVersion()) {
            Capabilities::register('boostore', bkntc__('Boostore'));
            Capabilities::register('back_to_wordpress', bkntc__('Show Wordpress button'));
        }
    }

    public static function registerCoreTenantCapabilities(): void
    {
        LocationsModule::registerTenantPermissions();
        PaymentsModule::registerTenantPermissions();
        MobileAppModule::registerTenantPermissions();

        Capabilities::registerLimit('services_allowed_max_number', bkntc__('Allowed maximum Service'));
        Capabilities::registerLimit('staff_allowed_max_number', bkntc__('Allowed maximum Staff'));
        Capabilities::registerLimit('service_extras_allowed_max_number', bkntc__('Allowed maximum Service Extras'));

        Capabilities::registerTenantCapability('receive_appointments', bkntc__('Receive appointments'));
        Capabilities::registerTenantCapability('remove_branding', bkntc__('Remove branding'));
        Capabilities::registerTenantCapability('upload_logo_to_booking_panel', bkntc__('Upload a logo to the booking panel'));
        Capabilities::registerTenantCapability('dashboard', bkntc__('Dashboard module'));
        Capabilities::registerTenantCapability('appointments', bkntc__('Appointments module'));
        Capabilities::registerTenantCapability('busy_slots', bkntc__('Busy Slots module'));
        Capabilities::registerTenantCapability('appearance', bkntc__('Appearance module'));
        Capabilities::registerTenantCapability('calendar', bkntc__('Calendar module'));
        Capabilities::registerTenantCapability('customers', bkntc__('Customers module'));
        Capabilities::registerTenantCapability('payments', bkntc__('Payments module'));
        Capabilities::registerTenantCapability('workflow', bkntc__('Workflow module'));
        Capabilities::registerTenantCapability('workflow_logs', bkntc__('Workflow Logs'));
        Capabilities::registerTenantCapability('services', bkntc__('Services module'));
        Capabilities::registerTenantCapability('staff', bkntc__('Staff module'));
        Capabilities::registerTenantCapability('dynamic_translations', bkntc__('Dynamic translations'));
        Capabilities::registerTenantCapability('settings', bkntc__('Settings'));
        Capabilities::registerTenantCapability('settings_general', bkntc__('General settings'), 'settings');
        Capabilities::registerTenantCapability('settings_calendar', bkntc__('Calendar settings'), 'settings');
        Capabilities::registerTenantCapability('settings_booking_panel_steps', bkntc__('Booking Steps'), 'settings');
        Capabilities::registerTenantCapability('settings_booking_panel_labels', bkntc__('Labels'), 'settings');
        Capabilities::registerTenantCapability('page_settings', bkntc__('Pages'), 'settings');
        Capabilities::registerTenantCapability('settings_payments', bkntc__('Payment settings'), 'settings');
        Capabilities::registerTenantCapability('settings_payment_gateways', bkntc__('Payment methods'), 'settings');
        Capabilities::registerTenantCapability('settings_deposit', bkntc__('Deposit settings'), 'settings');
        Capabilities::registerTenantCapability('settings_company', bkntc__('Company details'), 'settings');
        Capabilities::registerTenantCapability('settings_business_hours', bkntc__('Business Hours'), 'settings');
        Capabilities::registerTenantCapability('settings_holidays', bkntc__('Holidays'), 'settings');
        Capabilities::registerTenantCapability('settings_integrations_facebook_api', bkntc__('Continue with Facebook'), 'settings');
        Capabilities::registerTenantCapability('settings_integrations_google_login', bkntc__('Continue with Google'), 'settings');
        Capabilities::registerTenantCapability('settings_profile_settings', bkntc__('Profile settings'), 'settings');

        Capabilities::registerTenantCapability('disable_deposit_payments', bkntc__('Disable deposit payments'));
    }

    /**
     * @throws ReflectionException
     */
    public static function registerCoreRoutes(): void
    {
        Container::addBulk([
            \BookneticApp\Backend\Base\Ajax::class,
            SettingAjax::class
        ]);

        Route::post('base', Container::get(\BookneticApp\Backend\Base\Ajax::class));

        DashboardModule::registerRoutes();

        if (Capabilities::tenantCan('appointments')) {
            AppointmentsModule::registerRoutes();
        }

        if (Capabilities::tenantCan('calendar')) {
            Route::get('calendar', \BookneticApp\Backend\Calendar\Controller::class);
            Route::post('calendar', \BookneticApp\Backend\Calendar\Ajax::class);
        }

        if (Capabilities::tenantCan('mobile-app')) {
            Route::post('mobile-app-billing', Backend\Mobile\Controllers\BillingAjaxController::class);
        }

        CustomerModule::registerRoutes();
        AppearanceModule::registerRoutes();
        LocationsModule::registerRoutes();
        PaymentsModule::registerRoutes();
        MobileAppModule::registerRoutes();

        if (Capabilities::tenantCan('services')) {
            Route::get('services', \BookneticApp\Backend\Services\Controller::class);
            Route::post('services', \BookneticApp\Backend\Services\Ajax::class);

            Route::get('service_categories', \BookneticApp\Backend\Services\Controllers\ServiceCategoryController::class);
            Route::post('service_categories', \BookneticApp\Backend\Services\Controllers\ServiceCategoryAjaxController::class);
        }

        if (Capabilities::tenantCan('staff')) {
            StaffModule::registerRoutes();
        }

        if (Capabilities::tenantCan('workflow')) {
            Route::get('workflow', new \BookneticApp\Backend\Workflow\Controller(self::getWorkflowEventsManager()));
            if (Capabilities::tenantCan('workflow_logs')) {
                Route::get('workflow_logs', Container::get(LogsController::class));
                Route::post('workflow_logs', Container::get(LogsAjax::class));
            }
            Route::post('workflow', new \BookneticApp\Backend\Workflow\Ajax(self::getWorkflowEventsManager()));
            Route::post('workflow_events', new \BookneticApp\Backend\Workflow\EventsAjax(self::getWorkflowEventsManager()));
            Route::post('workflow_actions', new \BookneticApp\Backend\Workflow\ActionsAjax(self::getWorkflowEventsManager()));
        }

        if (Capabilities::tenantCan('settings')) {
            Route::get('settings', \BookneticApp\Backend\Settings\Controller::class)->middleware(\BookneticApp\Backend\Settings\Middleware::class);
            Route::post('settings', Container::get(SettingAjax::class))->middleware(\BookneticApp\Backend\Settings\Middleware::class);
        }

        if (! Helper::isSaaSVersion() && Capabilities::userCan('boostore')) {
            Route::get('cart', \BookneticApp\Backend\Boostore\CartController::class);
            Route::get('boostore', \BookneticApp\Backend\Boostore\Controller::class);
            Route::post('boostore', \BookneticApp\Backend\Boostore\Ajax::class);
        }
    }

    public static function registerCoreMenus(): void
    {
        if (Capabilities::tenantCan('dashboard') && Capabilities::userCan('dashboard')) {
            MenuUI::get('dashboard')
                  ->setTitle(bkntc__('Dashboard'))
                  ->setIcon('fa fa-cube')
                  ->setPriority(100);
        }

        if (Capabilities::tenantCan('appointments') && Capabilities::userCan('appointments')) {
            MenuUI::get('appointments')
                  ->setTitle(bkntc__('Appointments'))
                  ->setIcon('fa fa-clock')
                  ->setPriority(200);
        }

        if (Capabilities::tenantCan('calendar') && Capabilities::userCan('calendar')) {
            MenuUI::get('calendar')
                  ->setTitle(bkntc__('Calendar'))
                  ->setIcon('fa fa-calendar-check')
                  ->setPriority(300);
        }

        CustomerModule::registerMenu();

        if (Capabilities::tenantCan('services') && Capabilities::userCan('services')) {
            MenuUI::get('services')
                ->setTitle(bkntc__('Services'))
                ->setIcon('fa fa-align-left')
                ->setPriority(600);

            MenuUI::get('services')
                ->subItem('service_categories')
                ->setTitle(bkntc__('Service Categories'))
                ->setIcon('fa fa-tags')
                ->setPriority(100);
        }

        if (Capabilities::tenantCan('staff') && Capabilities::userCan('staff')) {
            MenuUI::get('staff')
                  ->setTitle(bkntc__('Staff'))
                  ->setIcon('fa fa-user')
                  ->setPriority(700);
        }

        PaymentsModule::registerMenu();
        LocationsModule::registerMenu();
        MobileAppModule::registerMenu();

        if (Capabilities::tenantCan('workflow') && Capabilities::userCan('workflow')) {
            MenuUI::get('workflow')
                  ->setTitle(bkntc__('Workflow'))
                  ->setIcon('fa fa-project-diagram')
                  ->setPriority(900);

            if (Capabilities::tenantCan('workflow_logs') && Capabilities::userCan('workflow_logs')) {
                MenuUI::get('workflow')
                    ->subItem('workflow_logs')
                    ->setTitle(bkntc__('Workflow Logs'))
                    ->setIcon('fa fa-clipboard');
            }
        }

        if (Capabilities::tenantCan('appearance') && Capabilities::userCan('appearance')) {
            MenuUI::get('appearance')
                  ->setTitle(bkntc__('Appearance'))
                  ->setIcon('fa fa-paint-brush')
                  ->setPriority(1000);
        }

        if (Capabilities::tenantCan('settings') && Capabilities::userCan('settings')) {
            MenuUI::get('settings')
                  ->setTitle(bkntc__('Settings'))
                  ->setIcon('fa fa-cog')
                  ->setPriority(2000);
        }

        if (! Helper::isSaaSVersion()) {
            if (Capabilities::userCan('back_to_wordpress')) {
                MenuUI::get('back_to_wordpress', Providers\UI\Abstracts\AbstractMenuUI::MENU_TYPE_TOP_LEFT)
                      ->setTitle(bkntc__('WORDPRESS'))
                      ->setIcon('fa fa-angle-left')
                      ->setLink(admin_url())
                      ->setPriority(100);
            }

            if (Capabilities::userCan('boostore')) {
                MenuUI::get('boostore', Providers\UI\Abstracts\AbstractMenuUI::MENU_TYPE_BOOSTORE)
                      ->setTitle(bkntc__('Boostore'))
                      ->setIcon(Helper::icon('store.svg'))
                      ->setPriority(200);
            }
        }
    }

    /**
     * Staff ve Administrator rule`lari var bizde. Hazirda hard code yazilib.
     * Administrator butun modul ve actionlara accessi var.
     * Staff ise yalniz Dashboard, Appointments, Calendar, Customers, Payments
     */
    public static function registerHardCodedUserRules(): void
    {
        /** if Staff */
        if (! Permission::isAdministrator()) {
            add_filter('bkntc_user_capability_filter', [ self::class, 'userCapabilityFilter' ], 10, 2);
        }
    }

    public static function userCapabilityFilter($can, $capability): bool
    {
        $capabilityInf = Capabilities::get($capability);

        if (! empty($capabilityInf[ 'parent' ])) {
            $disabledCapabilites = [ 'staff_add', 'staff_delete', 'staff_allow_to_login', 'staff_delete_wordpress_account' ];

            if (in_array($capability, $disabledCapabilites)) {
                return false;
            }

            $capability = $capabilityInf[ 'parent' ];
        }

        if (in_array($capability, [ 'dashboard', 'appointments', 'calendar', 'customers', 'payments', 'staff', 'mobile_app' ])) {
            return true;
        }

        return false;
    }

    public static function registerCoreWorkflowEvents(): void
    {
        self::$workflowEventsManager->get('booking_new')
                                    ->setTitle(bkntc__('New booking'))
                                    ->setEditAction('workflow_events', 'event_new_booking')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('booking_rescheduled')
                                    ->setTitle(bkntc__('Booking rescheduled'))
                                    ->setEditAction('workflow_events', 'event_booking_rescheduled')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('booking_status_changed')
                                    ->setTitle(bkntc__('Booking status changed'))
                                    ->setEditAction('workflow_events', 'event_booking_status_changed')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('customer_birthday')
            ->setTitle(bkntc__('Customer birthday'))
            ->setEditAction('workflow_events', 'event_customer_birthday')
            ->setAvailableParams(['customer_id']);

        self::$workflowEventsManager->get('booking_starts')
                                    ->setTitle(bkntc__('Booking starts'))
                                    ->setEditAction('workflow_events', 'event_booking_starts')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('booking_ends')
                                    ->setTitle(bkntc__('Booking ends'))
                                    ->setEditAction('workflow_events', 'event_booking_ends')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('new_wp_user_customer_created')
              ->setTitle(bkntc__('New customer created'))
              ->setEditAction('workflow_events', 'event_customer_created_view')
              ->setAvailableParams(['customer_id', 'customer_password']);

        self::$workflowEventsManager
            ->get('customer_forgot_password')
            ->setTitle(bkntc__('Customer forgot password'))
            ->setAvailableParams([ 'customer_id' ]);

        self::$workflowEventsManager
            ->get('customer_reset_password')
            ->setTitle(bkntc__('Customer reset password'))
            ->setAvailableParams([ 'customer_id' ]);

        self::$workflowEventsManager->get('appointment_paid')
                                    ->setTitle(bkntc__('Appointment Paid'))
                                    ->setEditAction('workflow_events', 'event_appointment_paid_view')
                                    ->setAvailableParams(['appointment_id', 'location_id', 'service_id', 'staff_id', 'customer_id']);

        self::$workflowEventsManager->get('customer_signup')
            ->setTitle(bkntc__('Customer signs up'))
            ->setEditAction('workflow_events', 'event_customer_signup_view')
            ->setAvailableParams(['customer_id']);

        add_action('bkntc_customer_sign_up_confirm', function ($token, $customerId) {
            self::$shortCodeService->addReplacer(function ($text, $data) use ($token) {
                if (! isset($data['customer_id'])) {
                    return $text;
                }

                $page_link = get_page_link(Helper::getOption('regular_sign_up_page', '', false));

                $confirm_url = add_query_arg('activation_token', $token, $page_link);

                return str_replace('{url_to_complete_customer_signup}', $confirm_url, $text);
            });

            self::$workflowEventsManager->trigger('customer_signup', [
                'customer_id'   =>  $customerId
            ], function ($event) {
                if (empty($event['data'])) {
                    return true;
                }

                $data = json_decode($event['data'], true);

                return !(!empty($data['locale']) && $data['locale'] !== get_locale());
            });
        }, 10, 2);

        add_action('bkntc_customer_forgot_password', function ($token, $customerId) {
            self::$shortCodeService->addReplacer(function ($text, $data) use ($token) {
                if (! isset($data['customer_id'])) {
                    return $text;
                }

                $page_link = get_page_link(Helper::getOption('regular_forgot_password_page', '', false));

                $confirm_url = add_query_arg('reset_token', $token, $page_link);

                return str_replace('{url_to_reset_password}', $confirm_url, $text);
            });

            self::$workflowEventsManager->trigger('customer_forgot_password', [ 'customer_id' => $customerId ]);
        }, 10, 2);

        add_action('bkntc_customer_reset_password', function ($customerId) {
            self::$workflowEventsManager->trigger('customer_reset_password', [ 'customer_id' => $customerId ]);
        });

        add_action('bkntc_payment_confirmed', function ($appointmentId, $fromSource = '') {
            $appointment = Appointment::query()->get($appointmentId);
            $customer = Customer::query()->get($appointment->customer_id);

            if ($fromSource !== 'payment_link') {
                self::$workflowEventsManager->trigger('booking_new', [
                    'appointment_id' => $appointmentId,
                    'location_id' => $appointment->location_id,
                    'service_id' => $appointment->service_id,
                    'staff_id' => $appointment->staff_id,
                    'customer_id' => $appointment->customer_id
                ], function ($event) use ($appointment, $customer) {
                    if (empty($event['data'])) {
                        return true;
                    }

                    $eventData = WorkflowEventFilterData::fromArray(json_decode($event['data'], true));

                    if ($eventData->hasLocale() && $eventData->getLocale() !== $appointment->locale) {
                        return false;
                    }

                    if ($eventData->hasLocations() && !$eventData->matchesLocation($appointment->location_id)) {
                        return false;
                    }

                    if ($eventData->hasCategories() && !$eventData->matchesCategory($customer->category_id)) {
                        return false;
                    }

                    if ($eventData->hasServices() && !$eventData->matchesService($appointment->service_id)) {
                        return false;
                    }

                    if ($eventData->hasStaffs() && !$eventData->matchesStaff($appointment->staff_id)) {
                        return false;
                    }

                    if ($eventData->hasStatuses() && !$eventData->matchesStatus($appointment->status)) {
                        return false;
                    }

                    if ($eventData->hasLocationCategories()) {
                        $location = Location::query()->select(['category_id'])->get($appointment->location_id);
                        if (!$location || !$eventData->matchesLocationCategory((int)$location->category_id)) {
                            return false;
                        }
                    }

                    if ($eventData->hasCalledFrom()) {
                        if ($eventData->isCalledFromBackend() && !Permission::isBackEnd()) {
                            return false;
                        }
                        if ($eventData->isCalledFromFrontend() && Permission::isBackEnd()) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            if ($appointment->payment_method !== 'local' || $fromSource === 'payment_link') {
                self::$workflowEventsManager->trigger('appointment_paid', [
                    'appointment_id' => $appointmentId,
                    'location_id' => $appointment->location_id,
                    'service_id' => $appointment->service_id,
                    'staff_id' => $appointment->staff_id,
                    'customer_id' => $appointment->customer_id
                ], function ($event) {
                    if (empty($event['data'])) {
                        return true;
                    }

                    $data = json_decode($event['data'], true);

                    return !(!empty($data['locale']) && $data['locale'] !== get_locale());
                });
            }
        }, 1000, 2);

        $oldAppointmentInfObj = new \stdClass();
        $oldAppointmentInfObj->inf = null;

        add_action('bkntc_appointment_before_mutation', function ($id) use ($oldAppointmentInfObj) {
            $oldAppointmentInfObj->inf = is_null($id) ? null : Appointment::get($id);
        });

        add_action('bkntc_appointment_after_mutation', function ($id) use ($oldAppointmentInfObj) {
            $oldAppointmentInf = $oldAppointmentInfObj->inf;
            $newAppointmentInf = is_null($id) ? null : Appointment::query()->get($id);
            $customerInf = $newAppointmentInf === null ? null : Customer::query()->get($newAppointmentInf->customer_id);

            if (empty($oldAppointmentInf) || $newAppointmentInf === null) {
                return;
            }

            // status change
            if ($newAppointmentInf->status != $oldAppointmentInf->status) {
                self::$workflowEventsManager->trigger('booking_status_changed', [
                    'appointment_id' => $newAppointmentInf->id,
                    'location_id' => $newAppointmentInf->location_id,
                    'service_id' => $newAppointmentInf->service_id,
                    'staff_id' => $newAppointmentInf->staff_id,
                    'customer_id' => $newAppointmentInf->customer_id
                ], function ($event) use ($oldAppointmentInf, $newAppointmentInf, $customerInf) {
                    if (empty($event['data'])) {
                        return true;
                    }

                    $eventData = WorkflowEventFilterData::fromArray(json_decode($event['data'], true));

                    if ($eventData->hasLocale() && $eventData->getLocale() !== $newAppointmentInf->locale) {
                        return false;
                    }

                    if ($eventData->hasStatuses() && !$eventData->matchesStatus($newAppointmentInf->status)) {
                        return false;
                    }

                    if ($eventData->hasCategories() && !$eventData->matchesCategory($customerInf->category_id)) {
                        return false;
                    }

                    if ($eventData->hasPrevStatuses() && !$eventData->matchesPrevStatus($oldAppointmentInf->status)) {
                        return false;
                    }

                    if ($eventData->hasLocations() && !$eventData->matchesLocation($newAppointmentInf->location_id)) {
                        return false;
                    }

                    if ($eventData->hasServices() && !$eventData->matchesService($newAppointmentInf->service_id)) {
                        return false;
                    }

                    if ($eventData->hasStaffs() && !$eventData->matchesStaff($newAppointmentInf->staff_id)) {
                        return false;
                    }

                    if ($eventData->hasLocationCategories()) {
                        $location = Location::query()->select(['category_id'])->get($newAppointmentInf->location_id);
                        if (!$location || !$eventData->matchesLocationCategory((int)$location->category_id)) {
                            return false;
                        }
                    }

                    if ($eventData->hasCalledFrom()) {
                        if ($eventData->isCalledFromBackend() && !Permission::isBackEnd()) {
                            return false;
                        }
                        if ($eventData->isCalledFromFrontend() && Permission::isBackEnd()) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            // reschedule
            if ($newAppointmentInf->starts_at != $oldAppointmentInf->starts_at
                || $newAppointmentInf->location_id != $oldAppointmentInf->location_id
                || $newAppointmentInf->service_id != $oldAppointmentInf->service_id
                || $newAppointmentInf->staff_id != $oldAppointmentInf->staff_id
            ) {
                self::$workflowEventsManager->trigger('booking_rescheduled', [
                    'appointment_id' => $newAppointmentInf->id,
                    'location_id' => $newAppointmentInf->location_id,
                    'service_id' => $newAppointmentInf->service_id,
                    'staff_id' => $newAppointmentInf->staff_id,
                    'customer_id' => $newAppointmentInf->customer_id
                ], function ($event) use ($newAppointmentInf) {
                    if (empty($event['data'])) {
                        return true;
                    }

                    $eventData = WorkflowEventFilterData::fromArray(json_decode($event['data'], true));

                    if ($eventData->hasLocale() && $eventData->getLocale() !== $newAppointmentInf->locale) {
                        return false;
                    }

                    if ($eventData->hasLocations() && !$eventData->matchesLocation($newAppointmentInf->location_id)) {
                        return false;
                    }

                    if ($eventData->hasServices() && !$eventData->matchesService($newAppointmentInf->service_id)) {
                        return false;
                    }

                    if ($eventData->hasStaffs() && !$eventData->matchesStaff($newAppointmentInf->staff_id)) {
                        return false;
                    }

                    if ($eventData->hasLocationCategories()) {
                        $location = Location::query()->select(['category_id'])->get($newAppointmentInf->location_id);
                        if (!$location || !$eventData->matchesLocationCategory((int)$location->category_id)) {
                            return false;
                        }
                    }

                    if ($eventData->hasCalledFrom()) {
                        if ($eventData->isCalledFromBackend() && !Permission::isBackEnd()) {
                            return false;
                        }
                        if ($eventData->isCalledFromFrontend() && Permission::isBackEnd()) {
                            return false;
                        }
                    }

                    return true;
                });
            }
        }, 1000, 1);

        add_action('bkntc_customer_created', function ($id, $pass) {
            if (empty($id) || empty($pass)) {
                return;
            }

            self::$workflowEventsManager->trigger('new_wp_user_customer_created', [
                'customer_id'       => $id,
                'customer_password' => $pass
            ], function ($event) {
                if (empty($event[ 'data' ])) {
                    return true;
                }

                $data = json_decode($event[ 'data' ], true);

                return !(!empty($data['locale']) && $data['locale'] !== get_locale());
            });
        }, 10, 2);

        add_action('bkntc_appointment_after_mutation', function ($id) use ($oldAppointmentInfObj) {
            if (is_null($id)) {
                return;
            }

            $newAppointmentInf = Appointment::get($id);
            if (!empty($oldAppointmentInfObj->inf) && !empty($newAppointmentInf) && $oldAppointmentInfObj->inf->starts_at !== $newAppointmentInf->starts_at) {
                Appointment::deleteData($id, 'triggered_cronjob_workflows');
            }
        });
    }

    public static function registerCoreWorkflowActions(): void
    {
        $drivers = self::getWorkflowDriversManager();
        $drivers->register(new SetBookingStatusAction());
        $drivers->register(new SetCustomerCategory());
        $drivers->register(new InAppNotification());
        // $drivers->register(new MobileAppNotification());
    }

    public static function registerCoreShortCodes(): void
    {
        $shortCodeService = self::$shortCodeService;

        $shortCodeService->addReplacer([ShortCodeServiceImpl::class, 'replace']);
        $shortCodeService->addReplacer([ShortCodeServiceImpl::class, 'replacePaymentLink']);
        $shortCodeService->addReplacer([AppointmentChangeStatus::class, 'replaceShortCode']);

        $shortCodeService->registerCategory('appointment_info', bkntc__('Appointment Info'));
        $shortCodeService->registerCategory('service_info', bkntc__('Service Info'));
        $shortCodeService->registerCategory('customer_info', bkntc__('Customer Info'));
        $shortCodeService->registerCategory('staff_info', bkntc__('Staff Info'));
        $shortCodeService->registerCategory('others', bkntc__('Others'));

        LocationsModule::registerShortCodes($shortCodeService);

        $shortCodeService->registerShortCode('appointment_id', [
            'name'      =>  bkntc__('Appointment ID'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id',
            'kind'      =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_date', [
            'name'      =>  bkntc__('Appointment date'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_date', [
            'name'      =>  bkntc__('Appointment start date'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_date', [
            'name'      =>  bkntc__('Appointment end date'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_date_time', [
            'name'      =>  bkntc__('Appointment date-time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_date_time', [
            'name'      =>  bkntc__('Appointment start-date-time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_date_time', [
            'name'      =>  bkntc__('Appointment end-date-time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_time', [
            'name'      =>  bkntc__('Appointment start time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_time', [
            'name'      =>  bkntc__('Appointment end time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_date_client', [
            'name'      =>  bkntc__('Appointment date (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_date_client', [
            'name'      =>  bkntc__('Appointment start date (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_date_client', [
            'name'      =>  bkntc__('Appointment end date (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_date_time_client', [
            'name'      =>  bkntc__('Appointment date-time (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_date_time_client', [
            'name'      =>  bkntc__('Appointment start-date-time (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_date_time_client', [
            'name'      =>  bkntc__('Appointment end-date-time (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_start_time_client', [
            'name'      =>  bkntc__('Appointment start time (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_end_time_client', [
            'name'      =>  bkntc__('Appointment end time (customer timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_duration', [
            'name'      =>  bkntc__('Appointment duration'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_buffer_before', [
            'name'      =>  bkntc__('Appointment buffer before time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_buffer_after', [
            'name'      =>  bkntc__('Appointment buffer after time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_status', [
            'name'      =>  bkntc__('Appointment status'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_status_icon_code', [
            'name'      =>  bkntc__('Appointment status icon code'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_status_icon_color', [
            'name'      =>  bkntc__('Appointment status icon color'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_service_price', [
            'name'      =>  bkntc__('Service price'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_extras_price', [
            'name'      =>  bkntc__('Price of extra services'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_extras_list', [
            'name'      =>  bkntc__('List of extra services'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_discount_price', [
            'name'      =>  bkntc__('Discount'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_sum_price', [
            'name'      =>  bkntc__('Sum price'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointments_total_price', [
            'name'      =>  bkntc__('Sum price for recurring appointments'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);

        $shortCodeService->registerShortCode('recurring_appointments_date', [
            'name'      =>  bkntc__('Recurring appointments all dates'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('recurring_appointments_date_time', [
            'name'      =>  bkntc__('Recurring appointments all dates and times'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('recurring_appointments_date_client', [
            'name'      =>  bkntc__('Recurring appointments all dates and times (Client timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('recurring_appointments_date_time_client', [
            'name'      =>  bkntc__('Recurring appointments all dates and times (Client timezone)'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);

        $shortCodeService->registerShortCode('appointment_paid_price', [
            'name'      =>  bkntc__('Paid price'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_payment_method', [
            'name'      =>  bkntc__('Payment method'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_created_date', [
            'name'      =>  bkntc__('Appointment created date'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_created_time', [
            'name'      =>  bkntc__('Appointment created time'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_brought_people', [
            'name' => bkntc__('People brought to the appointment'),
            'category' => 'appointment_info',
            'depends' => 'appointment_id'
        ]);
        $shortCodeService->registerShortCode('appointment_total_attendees', [
            'name' => bkntc__('Total attendees count for one appointment '),
            'category' => 'appointment_info',
            'depends' => 'appointment_id'
        ]);
        $shortCodeService->registerShortCode('add_to_google_calendar_link', [
            'name'      =>  bkntc__('Add to google calendar'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);

        $shortCodeService->registerShortCode('appointment_notes', [
            'name'      =>  bkntc__('Appointment notes'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);

        $shortCodeService->registerShortCode('service_name', [
            'name'      =>  bkntc__('Service name'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_price', [
            'name'      =>  bkntc__('Service price'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_duration', [
            'name'      =>  bkntc__('Service duration'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_notes', [
            'name'      =>  bkntc__('Service notes'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_color', [
            'name'      =>  bkntc__('Service color'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_image_url', [
            'name'      =>  bkntc__('Service image URL'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_category_name', [
            'name'      =>  bkntc__('Service category'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);
        $shortCodeService->registerShortCode('service_max_capacity', [
            'name'      =>  bkntc__('Service max capacity'),
            'category'  =>  'service_info',
            'depends'   =>  'service_id'
        ]);

        $shortCodeService->registerShortCode('customer_full_name', [
            'name'      =>  bkntc__('Customer full name'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_first_name', [
            'name'      =>  bkntc__('Customer first name'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_last_name', [
            'name'      =>  bkntc__('Customer last name'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_phone', [
            'name'      =>  bkntc__('Customer phone number'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id',
            'kind'      =>  'phone'
        ]);
        $shortCodeService->registerShortCode('customer_email', [
            'name'      =>  bkntc__('Customer email'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id',
            'kind'      =>  'email'
        ]);
        $shortCodeService->registerShortCode('customer_birthday', [
            'name'      =>  bkntc__('Customer birthdate'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_notes', [
            'name'      =>  bkntc__('Customer notes'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);
        $shortCodeService->registerShortCode('customer_profile_image_url', [
            'name'      =>  bkntc__('Customer image URL'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);

        $shortCodeService->registerShortCode('customer_password', [
            'name'      =>  bkntc__('Customer password'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_password'
        ]);

        $shortCodeService->registerShortCode('customer_category', [
            'name'      =>  bkntc__('Customer category'),
            'category'  =>  'customer_info',
            'depends'   =>  'customer_id'
        ]);

        if (is_null(Permission::tenantId())) {
            $shortCodeService->registerShortCode('url_to_complete_customer_signup', [
                'name'      =>  bkntc__('URL to complete customer sign up'),
                'category'  =>  'customer_info',
                'depends'   =>  'customer_id',
            ]);
            $shortCodeService->registerShortCode('url_to_reset_password', [
                'name'      =>  bkntc__('URL to reset customer password'),
                'category'  =>  'customer_info',
                'depends'   =>  'customer_id'
            ]);
        }

        $shortCodeService->registerShortCode('staff_name', [
            'name'      =>  bkntc__('Staff name'),
            'category'  =>  'staff_info',
            'depends'   =>  'staff_id',
            'kind'   =>  'staff_id',
        ]);
        $shortCodeService->registerShortCode('staff_email', [
            'name'      =>  bkntc__('Staff email'),
            'category'  =>  'staff_info',
            'depends'   =>  'staff_id',
            'kind'      =>  'email'
        ]);
        $shortCodeService->registerShortCode('staff_phone', [
            'name'      =>  bkntc__('Staff phone number'),
            'category'  =>  'staff_info',
            'depends'   =>  'staff_id',
            'kind'      =>  'phone'
        ]);
        $shortCodeService->registerShortCode('staff_about', [
            'name'      =>  bkntc__('Staff about'),
            'category'  =>  'staff_info',
            'depends'   =>  'staff_id'
        ]);
        $shortCodeService->registerShortCode('staff_profile_image_url', [
            'name'      =>  bkntc__('Staff image URL'),
            'category'  =>  'staff_info',
            'depends'   =>  'staff_id'
        ]);

        $shortCodeService->registerShortCode('company_name', [
            'name'      =>  bkntc__('Company name'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('company_image_url', [
            'name'      =>  bkntc__('Company image URL'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('company_website', [
            'name'      =>  bkntc__('Company website'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('company_phone', [
            'name'      =>  bkntc__('Company phone number'),
            'category'  =>  'others',
            'kind'      =>  'phone'
        ]);
        $shortCodeService->registerShortCode('company_address', [
            'name'      =>  bkntc__('Company address'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('sign_in_page', [
            'name'      =>  bkntc__('Sign In Page'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('sign_up_page', [
            'name'      =>  bkntc__('Sign Up Page'),
            'category'  =>  'others'
        ]);
        $shortCodeService->registerShortCode('total_appointments_in_group', [
            'name'      =>  bkntc__('Total appointments in group'),
            'category'  =>  'others',
            'depends'   =>  'appointment_id',
        ]);

        foreach (Helper::getAppointmentStatuses() as $key => $status) {
            $shortCodeService->registerShortCode('link_to_change_appointment_status_to_' . $key, [
                'name'      =>  bkntc__('Link to change appointment status to') . ' ' . $status['title'],
                'category'  =>  'others',
                'depends'   =>  'appointment_id',
            ]);
        }
    }

    public static function registerPaymentShortCode(): void
    {
        foreach (PaymentGatewayService::getEnabledGatewayNames() as $slug) {
            $paymentGatewayService = PaymentGatewayService::find($slug);

            if (empty($paymentGatewayService)) {
                continue;
            }

            if (! property_exists($paymentGatewayService, 'createPaymentLink')) {
                continue;
            }

            self::getShortCodeService()->registerShortCode('appointment_payment_link_' . $slug, [
                'name'      =>  bkntc__('Payment Link ') . ' ' . $paymentGatewayService->getTitle(),
                'category'  =>  'others',
                'depends'   =>  'appointment_id',
            ]);
        }
    }

    public static function registerLocalPaymentGateway(): void
    {
        new LocalPayment();

        TabUI::get('payment_gateways_settings')
             ->item('local')
             ->setTitle(bkntc__('Local'));
    }

    public static function registerCorePricesName(): void
    {
        add_filter('bkntc_price_name', function ($key) {
            $names = [
                'service_price' => bkntc__('Service price'),
                'discount' => bkntc__('Discount'),
                'service_extra' => bkntc__('Extra Service price')
            ];

            if (array_key_exists($key, $names)) {
                return $names[$key];
            }

            return $key;
        });
    }

    public static function registerWPUserRoles(): void
    {
        add_role('booknetic_customer', bkntc__('Booknetic Customers'), [
            'read'         => false,
            'edit_posts'   => false,
            'upload_files' => false,
        ]);

        add_role('booknetic_staff', bkntc__('Booknetic Staff'), [
            'read'         => true,
            'edit_posts'   => false,
            'upload_files' => false
        ]);
    }

    private static function registerCronActions(): void
    {
        if (! Helper::isSaaSVersion()) {
            Notifications::init();
        }
    }

    public static function detectPluginActivation($plugin, $network_activation): void
    {
        Helper::deleteOption('transient_cache_booknetic', false);

        if (strpos($plugin, 'booknetic') !== false && strpos($plugin, 'booknetic') === 0) {
            self::bookneticAddonActivated($plugin);
        }
    }

    private static function bookneticAddonActivated(string $plugin): void
    {
        $pluginPath = str_replace('\\/', '/', $plugin);

        $slug = explode('/', $pluginPath)[0];

        $fullPluginPath = WP_PLUGIN_DIR . '/' . $pluginPath;

        $pluginData = get_plugin_data($fullPluginPath, false);

        Container::get(FSCodeApiService::class)->sync([
            $slug => $pluginData['Version'] ?? '0.0.0',
        ]);
    }

    public static function detectUserUpdate($user_id, $old_user_data): void
    {
        self::updateStaff($user_id);
    }

    public static function detectProfileUpdate($user_id): void
    {
        self::updateStaff($user_id);
    }

    private static function updateStaff($user_id): void
    {
        $updated_user_data = get_userdata($user_id);

        $sqlData = [ 'email' => $updated_user_data -> user_email ];

        $sqlData = apply_filters('staff_sql_data', $sqlData);

        Staff::query()
            ->where('user_id', $user_id)
            ->update($sqlData);
    }

    private static function registerDependencies(): void
    {
        // Load attribute-discovered services (#[Service]) from cache or runtime scan
        $cachePath = dirname(__DIR__) . '/cache/di_cache.php';

        if (file_exists($cachePath)) {
            ContainerLoader::loadFromCache(require $cachePath);
        } else {
            $scanner = new ServiceScanner(__DIR__);
            ContainerLoader::loadFromScan($scanner->scan());
        }

        // Manual registrations (legacy — will be migrated to #[Service] over time)
        Container::add(FSCodeAPIClient::class, [new FSCodeAPIClientFactory(), 'make']);

        Container::addBulk([
            TranslationRepository::class,
            TranslationService::class,
            DataRepository::class,
            TimesheetRepository::class,
            SpecialDayRepository::class,
            HolidayRepository::class,
            BaseServiceRepository::class,
            FSCodeApiService::class,
            PluginService::class,
            FSCodeMobileAppClient::class
        ]);

        CustomerModule::registerDependencies();
        AppointmentsModule::registerDependencies();
        DashboardModule::registerDependencies();
        // LocationsModule — now handled via #[Service] attributes
        StaffModule::registerDependencies();
        PaymentsModule::registerDependencies();
        ServiceModule::registerDependencies();
        NotificationsModule::registerDependencies();
        WorkflowModule::registerDependencies();
    }

    private static function registerTasks(): void
    {
        Container::addBulk([
            LicenseSyncTask::class,
        ]);
    }

    public static function handleBusySlotsFilter($busySlots, $calendarService)
    {
        $staffId = $calendarService->getStaffId();
        if (empty($staffId)) {
            return $busySlots;
        }

        $dateFromEpoch = is_numeric($calendarService->dateFrom) ? $calendarService->dateFrom : \BookneticApp\Providers\Helpers\Date::epoch($calendarService->dateFrom);
        $dateToEpoch = is_numeric($calendarService->dateTo) ? $calendarService->dateTo : \BookneticApp\Providers\Helpers\Date::epoch($calendarService->dateTo);

        $slots = CoreStaffBusySlot::where('staff_id', $staffId)
            ->where('date', '>=', $dateFromEpoch)
            ->where('date', '<=', $dateToEpoch)
            ->fetchAll();

        foreach ($slots as $slot) {
            $start = $slot->date + $slot->start_time;
            $end = $start + ($slot->duration * 60);
            $busySlots[] = [ $start, $end ];
        }

        return $busySlots;
    }

    public static function handleCalendarEventsFilter($events, $startTime, $endTime, $staffFilter)
    {
        $query = CoreStaffBusySlot::query()
            ->leftJoin('staff', ['name', 'profile_image'])
            ->where('date', '>=', \BookneticApp\Providers\Helpers\Date::epoch(\BookneticApp\Providers\Helpers\Date::dateSQL($startTime)))
            ->where('date', '<=', \BookneticApp\Providers\Helpers\Date::epoch(\BookneticApp\Providers\Helpers\Date::dateSQL($endTime)));

        if (!empty($staffFilter)) {
            $query->where('staff_id', 'IN', $staffFilter);
        }

        $busySlots = $query->fetchAll();

        foreach ($busySlots as $slot) {
            $startEpoch = $slot->date + $slot->start_time;
            $endEpoch = $startEpoch + ($slot->duration * 60);

            $notesShort = !empty($slot->notes) ? (mb_strlen($slot->notes, 'UTF-8') > 30 ? mb_substr($slot->notes, 0, 30, 'UTF-8') . '...' : $slot->notes) : bkntc__('Busy Slot');

            $event_content = '<div class="calendar_cart" style="color: #ffffff; background-color: #808080 !important; border-color: #808080 !important;">';
            $event_content .= '<p>' . \BookneticApp\Providers\Helpers\Date::time($startEpoch) . ' - ' . \BookneticApp\Providers\Helpers\Date::time($endEpoch) . '</p>';
            $event_content .= '<p>' . bkntc__('Busy Slot') . '</p>';
            $event_content .= '<div class="flex"><p>' . htmlspecialchars($notesShort) . '</p></div>';
            $event_content .= '<div class="margin-top flex no-gap">';
            $event_content .= '<div class="circle_image"><img src="' . Helper::profileImage($slot->staff_profile_image, 'Staff') . '" alt=""></div>';
            $event_content .= '<p>' . htmlspecialchars($slot->staff_name) . '</p>';
            $event_content .= '</div></div>';

            $events[] = [
                'id' => 'busy_' . $slot->id,
                'appointment_id' => 0,
                'busy_slot_id' => (int)$slot->id,
                'color' => '#808080',
                'text_color' => '#ffffff',
                'staff_id' => $slot->staff_id,
                'resourceId' => $slot->staff_id,
                'start' => \BookneticApp\Providers\Helpers\Date::format('Y-m-d\TH:i:s', $startEpoch),
                'end' => \BookneticApp\Providers\Helpers\Date::format('Y-m-d\TH:i:s', $endEpoch),
                'start_time' => \BookneticApp\Providers\Helpers\Date::time($startEpoch),
                'end_time' => \BookneticApp\Providers\Helpers\Date::time($endEpoch),
                'duration' => $slot->duration * 60,
                'staff_name' => htmlspecialchars($slot->staff_name),
                'staff_profile_image' => Helper::profileImage($slot->staff_profile_image, 'Staff'),
                'enableCustomCalendarCardContent' => true,
                'event_content' => $event_content
            ];
        }

        return $events;
    }
}
