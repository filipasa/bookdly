<?php
/*
 * Plugin Name: Twilio Whatsapp action for Booknetic workflows
 * Description: This add-on allows you to send WhatsApp messages via Twilio.
 * Version: 1.1.0
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-workflow-twilio-whatsapp
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\TwilioWhatsapp\TwilioWhatsappAddon::getAddonSlug() ] = new \BookneticAddon\TwilioWhatsapp\TwilioWhatsappAddon();
    return $addons;
});
