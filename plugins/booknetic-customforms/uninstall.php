<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$prefix = $wpdb->prefix . 'bkntc_';

$wpdb->query("DROP TABLE IF EXISTS {$prefix}appointment_custom_data");
$wpdb->query("DROP TABLE IF EXISTS {$prefix}form_input_choices");
$wpdb->query("DROP TABLE IF EXISTS {$prefix}form_inputs");
$wpdb->query("DROP TABLE IF EXISTS {$prefix}forms");

delete_option('bkntc_addon_booknetic-customforms_version');
