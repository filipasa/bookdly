<?php
/*
 * Plugin Name: Google Calendar integration for Booknetic
 * Description: Establish a real-time synchronization between Booknetic and Google Calendar with two-way sync.
 * Version: 1.2.3
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-google-calendar
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter( 'bkntc_addons_load', function ( $addons ) {
    $addons[ \BookneticAddon\Googlecalendar\GoogleCalendarAddon::getAddonSlug() ] = new \BookneticAddon\Googlecalendar\GoogleCalendarAddon();
    return $addons;
} );
