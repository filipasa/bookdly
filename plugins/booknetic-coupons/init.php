<?php
/*
 * Plugin Name: Coupons for Booknetic
 * Description: Coupons add-on allows you to create coupons for your customers.
 * Version: 1.2.0
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-coupons
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Coupons\CouponsAddon::getAddonSlug() ] = new \BookneticAddon\Coupons\CouponsAddon();
    return $addons;
});
