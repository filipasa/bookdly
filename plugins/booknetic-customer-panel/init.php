<?php
/*
 * Plugin Name: Customer Panel for Booknetic
 * Description: Front-end Customer Panel for customers to manage their appointments.
 * Version: 1.3.1
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-customer-panel
 */


defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Customerpanel\CustomerPanelAddon::getAddonSlug() ] = new \BookneticAddon\Customerpanel\CustomerPanelAddon();
    return $addons;
});
