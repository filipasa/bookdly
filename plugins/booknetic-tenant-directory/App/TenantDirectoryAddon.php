<?php

namespace BookneticAddon\Tenantdirectory;

use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticApp\Providers\UI\MenuUI;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, TenantDirectoryAddon::getAddonSlug() );
}

class TenantDirectoryAddon extends AddonLoader
{
    public function init()
    {
        // 1. Register plan permission capability
        Capabilities::registerTenantCapability('tenant_directory', bkntc__('Tenant Directory'));

        // 2. Register user backend capabilities
        Capabilities::register('tenant_directory_settings', bkntc__('Tenant Directory Settings'), 'settings');

        // 3. Register frontend shortcodes
        add_shortcode('booknetic-saas-tenant-list', [ShortcodesListener::class, 'renderTenantList']);
        add_shortcode('booknetic-saas-tenant-search-form', [ShortcodesListener::class, 'renderSearchForm']);
        add_shortcode('booknetic-saas-tenant-page', [ShortcodesListener::class, 'renderTenantPage']);
        add_shortcode('booknetic-saas-tenant-page-fixed', [ShortcodesListener::class, 'renderTenantPageFixed']);

        // 4. Register review submission AJAX hooks
        add_action('wp_ajax_bkntc_submit_review', [ShortcodesListener::class, 'submitReview']);
        add_action('wp_ajax_nopriv_bkntc_submit_review', [ShortcodesListener::class, 'submitReview']);

        // 4. Hook customer creation to send email with login details
        add_action('bkntc_customer_created', function($customerId, $password) {
            $customer = \BookneticApp\Models\Customer::noTenant()->get($customerId);
            if (!$customer || empty($customer->email)) {
                return;
            }
            
            $to = $customer->email;
            $subject = 'Welcome to Bookdly - Your Account Details';
            
            $message = "Hello " . $customer->first_name . ",\n\n";
            $message .= "Your customer account has been created on Bookdly!\n\n";
            $message .= "You can log in to view your bookings and manage your appointments:\n";
            $message .= "Login/Customer Panel: " . site_url('/customer-sign-in/') . "\n";
            $message .= "Email: " . $customer->email . "\n";
            $message .= "Temporary Password: " . $password . "\n\n";
            $message .= "Best regards,\nThe Bookdly Team";
            
            $headers = array('Content-Type: text/plain; charset=UTF-8');
            wp_mail($to, $subject, $message, $headers);
        }, 10, 2);

        // 4. Hook localizations
        add_filter('bkntc_localization', function ($lang) {
            return array_merge(
                [
                    'tenant_directory' => bkntc__('Tenant Directory')
                ],
                $lang
            );
        });

        // 4.1 Filter total service duration for multi-service cart bookings
        add_filter('bkntc_service_total_duration', function($duration, $calendarService) {
            $cartJson = \BookneticApp\Providers\Helpers\Helper::_post('cart', '', 'string');
            if (empty($cartJson)) {
                return $duration;
            }

            $cartArr = json_decode($cartJson, true);
            if (!is_array($cartArr) || count($cartArr) <= 1) {
                return $duration;
            }

            $totalDuration = 0;
            foreach ($cartArr as $item) {
                $serviceId = isset($item['service']) ? (int)$item['service'] : 0;
                if ($serviceId > 0) {
                    $serviceInf = \BookneticApp\Models\Service::get($serviceId);
                    if ($serviceInf) {
                        $itemDuration = (int)$serviceInf->duration;
                        if (isset($item['service_extras']) && !empty($item['service_extras'])) {
                            $extras = is_string($item['service_extras']) ? json_decode($item['service_extras'], true) : $item['service_extras'];
                            if (is_array($extras)) {
                                $itemDuration += \BookneticApp\Backend\Appointments\Helpers\ExtrasService::calcExtrasDuration($extras);
                            }
                        }
                        $itemDuration += (int)$serviceInf->buffer_before + (int)$serviceInf->buffer_after;
                        $totalDuration += $itemDuration;
                    }
                }
            }
            return $totalDuration;
        }, 10, 2);

        // 4.2 Intercept Google OAuth callback to authenticate/login tenants
        $this->handleGoogleOAuthCallback();

        // 5. Inject custom CSS and Chart.js overrides to Booknetic backend
        add_action('admin_head', function() {
            if (isset($_GET['page']) && strpos($_GET['page'], 'booknetic') !== false) {
                ?>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    /* Custom Dashboard Styling Overrides */
                    #statistic-boxes-area,
                    #today-appointments-area,
                    .card_list,
                    #date_buttons {
                        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif !important;
                    }
                    .statistic-boxes {
                        border: none !important;
                        border-radius: 16px !important;
                        background: #ffffff !important;
                        padding: 24px !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }
                    .statistic-boxes:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important;
                    }
                    .dashboard-card {
                        border: none !important;
                        border-radius: 16px !important;
                        background: #ffffff !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
                        overflow: hidden;
                    }
                    .dashboard-card-title {
                        font-size: 13px !important;
                        font-weight: 700 !important;
                        letter-spacing: 0.05em !important;
                        color: #64748B !important;
                        padding: 20px 24px 12px 24px !important;
                        border-bottom: none !important;
                        background: transparent !important;
                    }
                    .dashboard-card-body {
                        padding: 16px 24px 24px 24px !important;
                    }
                    .graph-body {
                        padding: 24px !important;
                    }
                    #date_buttons {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 24px;
                    }
                    #date_buttons .date_buttons_span {
                        background: #F1F5F9 !important;
                        padding: 4px !important;
                        border-radius: 10px !important;
                        display: inline-flex;
                        gap: 2px;
                        border: none !important;
                    }
                    #date_buttons .date_button,
                    .graph-btns .date_button {
                        background: transparent !important;
                        border: none !important;
                        color: #475569 !important;
                        font-weight: 600 !important;
                        font-size: 13px !important;
                        padding: 8px 16px !important;
                        border-radius: 8px !important;
                        transition: all 0.15s ease !important;
                    }
                    #date_buttons .date_button.active_btn,
                    #date_buttons .date_button:hover,
                    .graph-btns .date_button.active,
                    .graph-btns .date_button:hover {
                        background: #ffffff !important;
                        color: #0F172A !important;
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
                    }
                </style>
                <script>
                    window.booknetic_user_data = <?php echo json_encode($data); ?>;
                    window.booknetic_login_status = <?php echo json_encode($status); ?>;
                    window.booknetic_redirect_url = <?php echo json_encode($url); ?>;

                    jQuery(document).ready(function($) {
                        if (typeof Chart === 'undefined') return;

                        // Extend Chart.js v2 elements to draw rounded top corners on Bar charts
                        Chart.elements.Rectangle.prototype.draw = function() {
                            var ctx = this._chart.ctx;
                            var vm = this._view;
                            var left, right, top, bottom, signX, signY, borderSkipped;
                            var borderWidth = vm.borderWidth;
                            var cornerRadius = 6;

                            if (!vm.horizontal) {
                                left = vm.x - vm.width / 2;
                                right = vm.x + vm.width / 2;
                                top = vm.y;
                                bottom = vm.base;
                                signX = 1;
                                signY = bottom > top ? 1 : -1;
                                borderSkipped = vm.borderSkipped || 'bottom';
                            } else {
                                left = vm.base;
                                right = vm.x;
                                top = vm.y - vm.height / 2;
                                bottom = vm.y + vm.height / 2;
                                signX = right > left ? 1 : -1;
                                signY = 1;
                                borderSkipped = vm.borderSkipped || 'left';
                            }

                            ctx.beginPath();
                            ctx.fillStyle = vm.backgroundColor;
                            ctx.strokeStyle = vm.borderColor;
                            ctx.lineWidth = borderWidth;

                            var width = right - left;
                            var height = bottom - top;
                            var x = left;
                            var y = top;

                            ctx.moveTo(x + cornerRadius, y);
                            ctx.lineTo(x + width - cornerRadius, y);
                            ctx.quadraticCurveTo(x + width, y, x + width, y + cornerRadius);
                            ctx.lineTo(x + width, y + height);
                            ctx.lineTo(x, y + height);
                            ctx.lineTo(x, y + cornerRadius);
                            ctx.quadraticCurveTo(x, y, x + cornerRadius, y);
                            ctx.closePath();

                            ctx.fill();
                            if (borderWidth) {
                                ctx.stroke();
                            }
                        };

                        // Configure premium Chart.js defaults
                        Chart.defaults.global.defaultFontFamily = "'Plus Jakarta Sans', 'Inter', sans-serif";
                        Chart.defaults.global.defaultFontColor = '#64748B';
                        Chart.defaults.global.defaultFontSize = 12;
                        Chart.defaults.scale.gridLines.color = 'rgba(0, 0, 0, 0.03)';
                        Chart.defaults.scale.gridLines.zeroLineColor = 'rgba(0, 0, 0, 0.03)';
                        Chart.defaults.scale.gridLines.drawBorder = false;

                        // Intercept and rewrite chart initialize configs
                        var originalInit = Chart.prototype.initialize;
                        Chart.prototype.initialize = function() {
                            var chart = this;
                            var config = chart.config;
                            var canvas = chart.chart.canvas;
                            var ctx = canvas.getContext('2d');

                            if (config.options && config.options.scales) {
                                if (config.options.scales.xAxes) {
                                    config.options.scales.xAxes.forEach(function(axis) {
                                        axis.gridLines = axis.gridLines || {};
                                        axis.gridLines.display = false;
                                        axis.ticks = axis.ticks || {};
                                        axis.ticks.maxRotation = 0;
                                        axis.ticks.minRotation = 0;
                                        axis.ticks.autoSkip = true;
                                        axis.ticks.fontColor = '#94A3B8';
                                    });
                                }
                                if (config.options.scales.yAxes) {
                                    config.options.scales.yAxes.forEach(function(axis) {
                                        axis.gridLines = axis.gridLines || {};
                                        axis.gridLines.color = 'rgba(0, 0, 0, 0.03)';
                                        axis.gridLines.zeroLineColor = 'rgba(0, 0, 0, 0.03)';
                                        axis.gridLines.drawBorder = false;
                                        axis.ticks = axis.ticks || {};
                                        axis.ticks.fontColor = '#94A3B8';
                                    });
                                }
                            }

                            if (config.data && config.data.datasets) {
                                config.data.datasets.forEach(function(dataset) {
                                    if (config.type === 'bar') {
                                        dataset.backgroundColor = '#4F46E5';
                                        dataset.hoverBackgroundColor = '#4338CA';
                                        dataset.borderColor = 'transparent';
                                    } else if (config.type === 'line') {
                                        dataset.lineTension = 0.4;
                                        dataset.borderWidth = 3;
                                        dataset.borderColor = '#10B981';
                                        dataset.pointRadius = 0;
                                        dataset.pointHoverRadius = 6;
                                        dataset.pointBackgroundColor = '#10B981';
                                        dataset.pointHoverBackgroundColor = '#ffffff';
                                        dataset.pointHoverBorderColor = '#10B981';
                                        dataset.pointHoverBorderWidth = 3;

                                        var gradient = ctx.createLinearGradient(0, 0, 0, canvas.height || 300);
                                        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                                        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                                        dataset.backgroundColor = gradient;
                                        dataset.fill = true;
                                    }
                                });
                            }
                            originalInit.apply(this, arguments);
                        };
                    });
                </script>
                <?php
            }
        });
    }

    public function initBackend()
    {
        // Add routing for Tenant Workspace Landing Page settings
        if (Capabilities::tenantCan('tenant_directory')) {
            Route::get('landing_page', Backend\Controller::class);
            Route::post('tenant_directory_landing', Backend\Ajax::class, ['save_landing_page', 'upload_gallery_image']);
            
            // Add Tenant Dashboard Sidebar menu
            MenuUI::get('landing_page')
                ->setTitle(bkntc__('Landing Page'))
                ->setIcon('fa fa-file-alt')
                ->setPriority(800);
        }
    }

    public function initSaaSBackend()
    {
        // Add Super-Admin routes for Directory management
        \BookneticSaaS\Providers\Core\Route::post('tenant_directory_settings', Backend\Ajax::class, [
            'settings_view', 'settings_save',
            'business_types_save', 'business_types_delete',
            'keywords_save', 'keywords_delete',
            'review_directory_request'
        ]);

        // Initialize parent Settings menu item
        \BookneticSaaS\Providers\UI\SettingsMenuUI::get('tenant_directory')
            ->setTitle(bkntc__('Tenant Directory'))
            ->setDescription(bkntc__('Manage public listings, business types, keywords and tenant review requests.'))
            ->setIcon(Helper::icon('general-settings.svg', 'Settings'))
            ->setPriority(20);

        // Add Settings menu sub-item
        \BookneticSaaS\Providers\UI\SettingsMenuUI::get('tenant_directory')
            ->subItem('settings_view', 'tenant_directory_settings')
            ->setTitle(bkntc__('Tenant Directory'))
            ->setPriority(1);
    }

    public function handleGoogleOAuthCallback()
    {
        $logFile = WP_CONTENT_DIR . '/uploads/google_login_debug.log';
        $slug = \BookneticApp\Providers\Helpers\Helper::getSlugName();
        $action_key = $slug . '_action';
        
        // Match any variation of action parameter
        $action_val = '';
        if (isset($_GET[$action_key])) {
            $action_val = $_GET[$action_key];
        } elseif (isset($_GET['Bookdly_action'])) {
            $action_val = $_GET['Bookdly_action'];
        } elseif (isset($_GET['booknetic_action'])) {
            $action_val = $_GET['booknetic_action'];
        }

        // Only log if one of the relevant actions is present to avoid polluting the log file
        if ($action_val === 'google_login_callback' || $action_val === 'google_login') {
            file_put_contents($logFile, sprintf(
                "[%s] handleGoogleOAuthCallback called. Slug: %s. Action Key: %s. GET params: %s. Detected Action: %s\n",
                date('Y-m-d H:i:s'),
                $slug,
                $action_key,
                json_encode($_GET),
                $action_val
            ), FILE_APPEND);
        }

        if ($action_val === 'google_login_callback') {
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Matches google_login_callback! Proceeding...\n", FILE_APPEND);
            
            try {
                $data = \BookneticApp\Integrations\LoginButtons\GoogleLogin::getUserData();
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] GoogleLogin::getUserData() success: " . json_encode($data) . "\n", FILE_APPEND);
            } catch (\Exception $e) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] GoogleLogin::getUserData() Exception: " . $e->getMessage() . "\n", FILE_APPEND);
                echo '<!DOCTYPE html><html><body><div style="font-family:sans-serif; padding: 40px; text-align: center;">' . htmlspecialchars($e->getMessage()) . '</div></body></html>';
                exit;
            }

            if (empty($data) || empty($data['email'])) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Empty user data or email.\n", FILE_APPEND);
                echo '<!DOCTYPE html><html><body><div style="font-family:sans-serif; padding: 40px; text-align: center;">Unable to retrieve user information from Google. Please try again.</div></body></html>';
                exit;
            }

            $login_status = 'no_user';
            $redirect_url = '';

            $user = get_user_by('email', $data['email']);
            if ($user) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] User found: ID=" . $user->ID . ", Login=" . $user->user_login . ", Roles=" . json_encode($user->roles) . "\n", FILE_APPEND);
                if (in_array('booknetic_saas_tenant', $user->roles) || in_array('administrator', $user->roles)) {
                    wp_clear_auth_cookie();
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID, true);
                    do_action('wp_login', $user->user_login, $user);
                    $login_status = 'success';
                    
                    if (class_exists('\\BookneticSaaS\\Providers\\Helpers\\Helper')) {
                        $redirect_url = \BookneticSaaS\Providers\Helpers\Helper::getURLOfUsersDashboard($user);
                    } else {
                        $redirect_url = site_url() . '/wp-admin/admin.php?page=booknetic';
                    }
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Login success! Redirecting to: " . $redirect_url . "\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] User does not have booknetic_saas_tenant or administrator role.\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] User not found for email: " . $data['email'] . "\n", FILE_APPEND);
            }

            $data_json = json_encode($data);
            $login_status_json = json_encode($login_status);
            $redirect_url_json = json_encode($redirect_url);
            $signup_page_id = class_exists('\\BookneticSaaS\\Providers\\Helpers\\Helper') ? \BookneticSaaS\Providers\Helpers\Helper::getOption('sign_up_page') : 0;
            $signup_url = $signup_page_id ? get_permalink($signup_page_id) : site_url('/sign-up/');
            $signup_url_json = json_encode($signup_url);

            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Authenticating...</title></head><body style="font-family:sans-serif; text-align:center; padding-top:50px; background:#f9fafb; color:#374151;">';
            echo '<h3>Logging you in...</h3>';
            echo '<script>
                 window.booknetic_user_data = ' . $data_json . ';
                 window.booknetic_login_status = ' . $login_status_json . ';
                 window.booknetic_redirect_url = ' . $redirect_url_json . ';
             </script>';
            echo '</body></html>';
            exit;
        }
    }
}
?>
