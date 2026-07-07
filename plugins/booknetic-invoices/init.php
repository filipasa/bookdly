<?php
/*
 * Plugin Name: Invoices for Booknetic
 * Description: Create unique Invoices according to your branding.
 * Version: 1.1.0
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-invoices
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\Invoices\InvoicesAddon::getAddonSlug() ] = new \BookneticAddon\Invoices\InvoicesAddon();
    return $addons;
});
