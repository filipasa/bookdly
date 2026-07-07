<?php
/*
 * Plugin Name: Tenant Business Directory for Booknetic
 * Description: Enable searchable and categorizable public business directory of approved tenants for Booknetic SaaS.
 * Version: 1.0.3
 * Author: Antigravity Team
 * Author URI: https://bookdly.co.uk
 * License: Commercial
 * Text Domain: booknetic-tenant-directory
 */

defined( 'ABSPATH' ) or exit;

// Custom PSR-4 Autoloader mapping BookneticAddon\Tenantdirectory namespace to App/ directory
spl_autoload_register(function ($class) {
    $prefix = 'BookneticAddon\\Tenantdirectory\\';
    $base_dir = __DIR__ . '/App/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

add_filter( 'bkntc_addons_load', function ( $addons ) {
    $addons[ \BookneticAddon\Tenantdirectory\TenantDirectoryAddon::getAddonSlug() ] = new \BookneticAddon\Tenantdirectory\TenantDirectoryAddon();
    return $addons;
} );
?>
