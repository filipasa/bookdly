<?php
/*
 * Plugin Name: Tax add-on for Booknetic
 * Description: Add a Tax fee to your appointment prices.
 * Version: 1.1.9
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-tax
 */


defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Tax\TaxAddon::getAddonSlug() ] = new \BookneticAddon\Tax\TaxAddon();

    return $addons;
});
