<?php
/*
 * Plugin Name: Reports for Booknetic
 * Description: Get reports on a daily, weekly, monthly, and yearly basis.
 * Version: 1.0.7
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-reports
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Reports\ReportsAddon::getAddonSlug() ] = new \BookneticAddon\Reports\ReportsAddon();
    return $addons;
});
