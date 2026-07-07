<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$prefix = $wpdb->prefix . 'bkntc_';

$wpdb->query("DROP TABLE IF EXISTS {$prefix}staff_busy_slots");

delete_option('bkntc_addon_booknetic-google-calendar_version');
