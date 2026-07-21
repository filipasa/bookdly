<?php

/*
 * Plugin Name: Booknetic
 * Description: WordPress Appointment Booking and Scheduling system
 * Version: 5.2.2
 * Author: FS-Code
 * Author URI: https://www.booknetic.com
 * License: Commercial
 * Requires PHP: 7.4
 * Text Domain: booknetic
 */

defined('ABSPATH') or exit;

require_once __DIR__ . '/vendor/autoload.php';

new \BookneticApp\Providers\Core\Bootstrap();

// Status History - BEFORE MUTATION Hook (Initializes log with current/old status if empty)
add_action('bkntc_appointment_before_mutation', function($appointmentId) {
    if (!$appointmentId) return;
    try {
        global $wpdb;
        $appointment = $wpdb->get_row($wpdb->prepare("SELECT status, created_at, tenant_id FROM " . $wpdb->prefix . "bkntc_appointments WHERE id = %d", $appointmentId), ARRAY_A);
        if (!$appointment) return;
        
        $currentStatus = $appointment['status'];
        $tenantId = isset($appointment['tenant_id']) ? (int)$appointment['tenant_id'] : null;
        
        $historyRow = $wpdb->get_row($wpdb->prepare("SELECT id, data_value FROM " . $wpdb->prefix . "bkntc_data WHERE data_key = 'status_history' AND row_id = %d AND table_name = 'appointments'", $appointmentId), ARRAY_A);
        
        if (!$historyRow || empty($historyRow['data_value'])) {
            $history = [
                [
                    'status' => $currentStatus,
                    'time' => !empty($appointment['created_at']) ? (int)$appointment['created_at'] : time()
                ]
            ];
            
            if ($historyRow) {
                $wpdb->update($wpdb->prefix . 'bkntc_data', ['data_value' => json_encode($history)], ['id' => $historyRow['id']]);
            } else {
                $insertData = [
                    'table_name' => 'appointments',
                    'row_id' => $appointmentId,
                    'data_key' => 'status_history',
                    'data_value' => json_encode($history)
                ];
                if ($tenantId !== null) {
                    $insertData['tenant_id'] = $tenantId;
                }
                $wpdb->insert($wpdb->prefix . 'bkntc_data', $insertData);
            }
        }
    } catch (\Throwable $e) {}
}, 10, 1);

// Status History - AFTER MUTATION Hook (Appends the new status)
add_action('bkntc_appointment_after_mutation', function($appointmentId) {
    if (!$appointmentId) return;
    try {
        global $wpdb;
        $appointment = $wpdb->get_row($wpdb->prepare("SELECT status FROM " . $wpdb->prefix . "bkntc_appointments WHERE id = %d", $appointmentId), ARRAY_A);
        if (!$appointment) return;
        
        $currentStatus = $appointment['status'];
        
        $historyRow = $wpdb->get_row($wpdb->prepare("SELECT id, data_value FROM " . $wpdb->prefix . "bkntc_data WHERE data_key = 'status_history' AND row_id = %d AND table_name = 'appointments'", $appointmentId), ARRAY_A);
        
        if ($historyRow && !empty($historyRow['data_value'])) {
            $history = json_decode($historyRow['data_value'], true);
            if (is_array($history)) {
                $lastEntry = end($history);
                if ($lastEntry && $lastEntry['status'] !== $currentStatus) {
                    $history[] = [
                        'status' => $currentStatus,
                        'time' => time()
                    ];
                    $wpdb->update($wpdb->prefix . 'bkntc_data', ['data_value' => json_encode($history)], ['id' => $historyRow['id']]);
                }
            }
        }
    } catch (\Throwable $e) {}
}, 10, 1);
