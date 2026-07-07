<?php
/*
 * Plugin Name: Paypal payment gateway for Booknetic
 * Description: Collect your revenues through PayPal.
 * Version: 1.1.7
 * Author: FS Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Text Domain: booknetic-payment-paypal
 */

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor/autoload.php';

add_filter('bkntc_addons_load', function ($addons)
{
    $addons[ \BookneticAddon\PaypalPaymentGateway\PaypalAddon::getAddonSlug() ] = new \BookneticAddon\PaypalPaymentGateway\PaypalAddon();
    return $addons;
});
