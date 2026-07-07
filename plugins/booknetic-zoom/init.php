<?php
/*
 * Plugin Name: Zoom integration for Booknetic
 * Description: Create Zoom meetings automatically for your appointments and send the link via notifications.
 * Version: 1.1.1
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-zoom
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Zoom\ZoomAddon::getAddonSlug() ] = new \BookneticAddon\Zoom\ZoomAddon();
    return $addons;
});
