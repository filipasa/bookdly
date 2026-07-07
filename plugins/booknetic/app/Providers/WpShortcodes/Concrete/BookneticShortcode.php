<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Models\Appearance;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Fonts\GoogleFontsImp;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\WpShortcodes\WpShortcode;

class BookneticShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        if (Helper::isSaaSVersion()) {
            if (isset($attrs['tenant_id']) && is_numeric($attrs['tenant_id'])) {
                \BookneticApp\Providers\Core\Permission::setTenantId((int)$attrs['tenant_id']);
            } else {
                $tenantIdFromRequest = Helper::_any('tenant_id', '', 'int');
                if (empty($tenantIdFromRequest)) {
                    $tenantIdFromRequest = Helper::_any('bkntc_page_id', '', 'int');
                }
                if (!empty($tenantIdFromRequest)) {
                    \BookneticApp\Providers\Core\Permission::setTenantId($tenantIdFromRequest);
                }
            }
        }

        if (Helper::isSaaSVersion()) {
            $tenantInf = \BookneticApp\Providers\Core\Permission::tenantInf();
            if ($tenantInf) {
                $isSubscriptionActive = !empty($tenantInf->active_subscription);
                $isExpired = empty($tenantInf->expires_in) || strtotime($tenantInf->expires_in) < time();
                if (!$isSubscriptionActive && $isExpired) {
                    return $this->renderPaywall($tenantInf);
                }
            }
        }

        if (Helper::getOption('only_registered_users_can_book', 'off') === 'on' && !is_user_logged_in()) {
            return '<script type="application/javascript">location.href="' . Helper::getRedirectURL() . '";</script>' . bkntc__('Redirecting...');
        }

        $attrs = empty($attrs) ? [] : $attrs;
        $info = [];
        $theme = null;

        if (isset($attrs['theme']) && is_numeric($attrs['theme']) && $attrs['theme'] > 0) {
            $theme = Appearance::get($attrs['theme']);
        }

        if ($theme === null) {
            $theme = Appearance::where('is_default', '1')->fetch();
        }

        $defaultFontFamily = 'Poppins';
        $fontFamily = $theme ? $theme['fontfamily'] : $defaultFontFamily;
        $themeId = $theme ? $theme['id'] : 0;
        $assetsHTML = '';

        if ($themeId > 0) {
            $themeCssFile = Theme::getThemeCss($themeId);

            /** @noinspection HttpUrlsUsage */
            $assetsHTML .= sprintf('<link rel="stylesheet" href="%s">', str_replace(['http://', 'https://'], '//', $themeCssFile));

            $font = new GoogleFontsImp($fontFamily);
            $assetsHTML .= sprintf('<link rel="stylesheet" href="%s">', $theme['use_local_font'] ? $font->getUrl() : $font->generateGoogleFontsUrl());
        }

        $company_phone_number = Helper::getOption('company_phone', '');

        $steps = [
            'service' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card2',
                'title' => bkntc__('Service'),
                'head_title' => bkntc__('Select service'),
                'attrs' => ' data-service-category="' . (isset($attrs['category']) && is_numeric($attrs['category']) && $attrs['category'] > 0 ? $attrs['category'] : '') . '"'
            ],
            'staff' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card1',
                'title' => bkntc__('Staff'),
                'head_title' => bkntc__('Select staff')
            ],
            'location' => [
                'value' => isset($select_location_id) && $select_location_id > 0 ? $select_location_id : '',
                'hidden' => false,
                'loader' => 'card1',
                'title' => bkntc__('Location'),
                'head_title' => bkntc__('Select location'),
                'attrs' => ' data-location-category="' . (isset($attrs['location_category']) && is_numeric($attrs['location_category']) && $attrs['location_category'] > 0 ? (int)$attrs['location_category'] : '') . '"'
            ],
            'service_extras' => [
                'value' => '',
                'hidden' => (Capabilities::tenantCan('services') == false) || Helper::getOption('show_step_service_extras', 'on') === 'off',
                'loader' => 'card2',
                'title' => bkntc__('Service Extras'),
                'head_title' => bkntc__('Select service extras')
            ],
            'information' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card3',
                'title' => bkntc__('Information'),
                'head_title' => bkntc__('Fill information')
            ],
            'cart' => [
                'value' => '',
                'hidden' => Helper::getOption('show_step_cart', 'on') === 'off',
                'loader' => 'card3',
                'title' => bkntc__('Cart'),
                'head_title' => bkntc__('Add to cart')
            ],
            'date_time' => [
                'value' => '',
                'hidden' => false,
                'loader' => 'card3',
                'title' => bkntc__('Date & Time'),
                'head_title' => bkntc__('Select Date & Time')
            ],
            'recurring_info' => [
                'value' => '',
                'hidden' => true,
                'loader' => 'card3',
                'title' => bkntc__('Recurring info'),
                'head_title' => bkntc__('Recurring info')
            ],
            'confirm_details' => [
                'value' => '',
                'hidden' => Helper::getOption('show_step_confirm_details', 'on') === 'off',
                'loader' => 'card3',
                'title' => bkntc__('Confirmation'),
                'head_title' => bkntc__('Confirm Details')
            ],
        ];

        $customStepsOrder = null;

        if (!empty($attrs['steps_order'])) {
            if (empty(array_diff(explode(',', $attrs['steps_order']), ['location', 'staff', 'service', 'service_extras', 'date_time', 'information']))) {
                $customStepsOrder = $attrs['steps_order'];
            }
        }

        $steps_order = Helper::getBookingStepsOrder(true, $customStepsOrder);

        if (!Capabilities::tenantCan('locations') || (Helper::getOption('show_step_location', 'on') == 'off') && ($location = Location::where('is_active', '1')->fetch())) {
            $steps['location']['hidden'] = true;
            $steps['location']['value'] = -1;
        }

        if (isset($_GET['location']) && is_numeric($_GET['location']) && $_GET['location'] > 0) {
            $attrs['location'] = $_GET['location'];
        }

        if (isset($attrs['location'])) {
            if (is_numeric($attrs['location']) && $attrs['location'] > 0) {
                $locationInfo = Location::get($attrs['location']);

                if ($locationInfo) {
                    $steps['location']['hidden'] = true;
                    $steps['location']['value'] = (int)$locationInfo['id'];
                }
            }

            if (is_string($attrs['location'])) {
                # Convert the 'location' string into an array of IDs
                $locationOptions = explode(",", $attrs['location']);

                # Map the array to ensure all IDs are valid integers greater than 0
                $locationOptions = array_filter(array_map(
                    fn ($id) => ($id > 0 && is_numeric($id)) ? (int)trim($id) : null,
                    $locationOptions,
                ));

                $field = implode(',', $locationOptions);
                # Query the Location model to get active location IDs where the ID exists in the filtered array
                $locationOptions = Location::where('id', 'IN', $locationOptions)
                    ->where('is_active', 1)
                    ->orderBy("FIELD(id, $field)")
                    ->select('id')
                    ->fetchAll();

                # Convert array<Location> to array<$locationID:int>
                $locationOptions = array_map(
                    fn ($location) => $location['id'],
                    $locationOptions
                );

                # If the options is empty do nothing
                if (!empty($locationOptions)) {
                    $steps['location']['options'] = $locationOptions;
                }
            }
        }

        if (!Capabilities::tenantCan('staff') || (Helper::getOption('show_step_staff', 'on') == 'off') && ($staff = Staff::where('is_active', '1')->fetch())) {
            $steps['staff']['hidden'] = true;
            $steps['staff']['value'] = -1;
        }

        if (isset($_GET['staff']) && is_numeric($_GET['staff']) && $_GET['staff'] > 0) {
            $attrs['staff'] = $_GET['staff'];
        }

        if (isset($attrs['staff'])) {
            if ($attrs['staff'] === 'any') {
                $steps['staff']['hidden'] = true;
                $steps['staff']['value'] = -1;
            } elseif (is_numeric($attrs['staff']) && $attrs['staff'] > 0) {
                $steps['staff']['hidden'] = true;
                $steps['staff']['value'] = $attrs['staff'];
            }
        }

        if (isset($attrs['limited_booking_days'])) {
            $info['limited_booking_days'] = ( int )$attrs['limited_booking_days'];
        }

        $serviceRecurringAttrs = '';
        if (
            (
                !Capabilities::tenantCan('services') ||
                (Helper::getOption('show_step_service', 'on') == 'off')
            )
            && ($service = Service::where('is_active', '1')->fetch())
        ) {
            $steps['service']['hidden'] = true;
            $steps['service']['value'] = $service['id'];
            $serviceRecurringAttrs = ' data-is-recurring="' . (int)$service['is_recurring'] . '"';

            if ($service['is_recurring'] == 1) {
                $steps['recurring_info']['hidden'] = false;
            }
        }

        if (isset($_GET['service']) && is_numeric($_GET['service']) && $_GET['service'] > 0) {
            $attrs['service'] = $_GET['service'];
        }

        if (isset($_GET['show_service']) && is_numeric($_GET['show_service']) && $_GET['show_service'] > 0) {
            $attrs['show_service'] = $_GET['show_service'];
        }

        if (isset($attrs['service']) && is_numeric($attrs['service']) && $attrs['service'] > 0) {
            $serviceInfo = Service::get($attrs['service']);

            if ($serviceInfo) {
                $steps['service']['hidden'] = empty($attrs['show_service']);
                $steps['service']['value'] = $serviceInfo['id'];
                $serviceRecurringAttrs = ' data-is-recurring="' . (int)$serviceInfo['is_recurring'] . '"';

                if ($serviceInfo['is_recurring'] == 1) {
                    $steps['recurring_info']['hidden'] = false;
                }
            }
        }
        $steps['service']['attrs'] .= $serviceRecurringAttrs;
        $hide_confirmation_number = Helper::getOption('hide_confirmation_number', 'off') == 'on';

        if (isset($_GET['category']) && is_numeric($_GET['category']) && $_GET['category'] > 0) {
            $result = ServiceCategory::get($_GET['category']);

            $attrs['category'] = $_GET['category'];

            if ($result) {
                $steps['service']['attrs'] = ' data-service-category="' . $result->id . '"';
            }
        }

        if (isset($_GET['location_category']) && is_numeric($_GET['location_category']) && $_GET['location_category'] > 0) {
            $attrs['location_category'] = $_GET['location_category'];
            $steps['location']['attrs'] = ' data-location-category="' . (int)$_GET['location_category'] . '"';
        }

        $info = Helper::encodeInfo($info);// doit bu nedi???

        $stepOrderNumber = 1;
        $stepsArr = [];
        foreach ($steps_order as $stepId) {
            if (!isset($steps[$stepId])) {
                continue;
            }

            $step = [];
            $step['id'] = $stepId;
            $step['order_number'] = $stepOrderNumber;
            $step = array_merge($step, $steps[$stepId]);

            /* view-da istifade edilir, silme! */
            $stepsArr[] = $step;
            $stepOrderNumber += 10;
        }

        ob_start();
        require self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . 'booking_panel/booknetic.php';
        do_action('bkntc_after_booking_panel_shortcode');
        $viewOutput = ob_get_clean();

        return $assetsHTML . $viewOutput;
    }

    private function renderPaywall($tenantInf): string
    {
        $companyName = htmlspecialchars($tenantInf->domain ?? $tenantInf->name ?? 'This business');
        $renewalUrl = site_url('/dashboard');
        
        $html = '
        <div class="bkntc_paywall_container" style="
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
            padding: 40px 20px;
            font-family: \'Outfit\', \'Inter\', \'Poppins\', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 12px;
            box-sizing: border-box;
            width: 100%;
        ">
            <div class="bkntc_paywall_card" style="
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.5);
                border-radius: 20px;
                padding: 40px 30px;
                max-width: 480px;
                width: 100%;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                box-sizing: border-box;
            ">
                <div class="bkntc_paywall_icon" style="
                    font-size: 64px;
                    margin-bottom: 20px;
                    animation: pulse 2s infinite;
                ">🔒</div>
                <h2 style="
                    color: #1e293b;
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 12px;
                    line-height: 1.3;
                ">' . bkntc__('Booking Page Suspended') . '</h2>
                <p style="
                    color: #64748b;
                    font-size: 15px;
                    line-height: 1.6;
                    margin-bottom: 28px;
                ">' . bkntc__('The booking page for <strong>%s</strong> is temporarily inactive because their subscription plan has expired.', [$companyName]) . '</p>
                <div style="
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 15px;
                    margin-bottom: 30px;
                    text-align: left;
                ">
                    <span style="
                        display: block;
                        font-weight: 600;
                        color: #475569;
                        font-size: 13px;
                        margin-bottom: 4px;
                    ">' . bkntc__('Are you the business owner?') . '</span>
                    <span style="
                        display: block;
                        color: #64748b;
                        font-size: 13px;
                        line-height: 1.5;
                    ">' . bkntc__('Please log in to your administration dashboard and renew your subscription to reactivate online bookings.') . '</span>
                </div>
                <a href="' . $renewalUrl . '" style="
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                    color: #ffffff;
                    font-weight: 600;
                    font-size: 14px;
                    text-decoration: none;
                    border-radius: 10px;
                    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
                    transition: transform 0.2s, box-shadow 0.2s;
                " onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(99, 102, 241, 0.45)\'" onmouseout="this.style.transform=\'none\'; this.style.boxShadow=\'0 4px 15px rgba(99, 102, 241, 0.35)\'">' . bkntc__('Go to Dashboard') . '</a>
            </div>
            <style>
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
            </style>
        </div>
        ';
        return $html;
    }
}
