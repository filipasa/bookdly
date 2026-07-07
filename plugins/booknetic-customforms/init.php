<?php
/*
 * Plugin Name: Custom forms for Booknetic
 * Description: Request additional information from your customers by using the Custom Forms module.
 * Version: 2.1.3
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-customforms
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter( 'bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Customforms\CustomFormsAddon::getAddonSlug() ] = new \BookneticAddon\Customforms\CustomFormsAddon();
    return $addons;
});
