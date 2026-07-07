<?php
/*
 * Plugin Name: Twilio SMS action for Booknetic workflows
 * Description: This add-on allows you to send SMS notifications via Twilio.
 * Version: 1.1.0
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-workflow-twilio-sms
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\TwilioSMS\TwilioSMSAddon::getAddonSlug() ] = new \BookneticAddon\TwilioSMS\TwilioSMSAddon();
    return $addons;
});
