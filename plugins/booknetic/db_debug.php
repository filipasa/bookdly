<?php
/**
 * Booknetic Database Debugging Script
 * 
 * INSTRUCTIONS:
 * 1. Place this file in your WordPress ROOT directory (where wp-config.php is located).
 * 2. Access it via your browser: https://yourdomain.com/db_debug.php
 * 3. It will attempt to add a service category and print any underlying MySQL errors.
 */

define('WP_USE_THEMES', false);
require('./wp-load.php');

if (!current_user_can('manage_options')) {
    die("You must be logged in as an administrator to run this script.");
}

echo "<h1>Booknetic Database Debugging</h1>";

try {
    global $wpdb;
    
    // Enable errors
    $wpdb->show_errors();
    
    echo "<h2>Testing Service Category Insertion</h2>";
    
    $table_name = $wpdb->base_prefix . "bkntc_service_categories";
    
    echo "Attempting to insert into table: " . htmlspecialchars($table_name) . "<br><br>";
    
    // Test 1: Using Booknetic's internal method
    echo "<h3>Test 1: Booknetic QueryBuilder (with parent_id = 0)</h3>";
    if (class_exists('\\BookneticApp\\Models\\ServiceCategory')) {
        $data = [
            'name' => 'Debug Model Test 1 - ' . date('Y-m-d H:i:s'),
            'parent_id' => 0 // This is what Booknetic sends by default when no parent is selected
        ];
        
        $result1 = \BookneticApp\Models\ServiceCategory::query()->insert($data);
        $insertId1 = \BookneticApp\Providers\DB\DB::lastInsertedId();
        
        if ($result1 !== false && $insertId1 > 0) {
            echo "<p style='color: green;'>Success! Inserted category with ID: " . $insertId1 . "</p>";
        } else {
            echo "<p style='color: red;'>Failed to insert via Booknetic Model.</p>";
            echo "<pre>WPDB Error: " . htmlspecialchars($wpdb->last_error) . "</pre>";
            echo "<pre>WPDB Query: " . htmlspecialchars($wpdb->last_query) . "</pre>";
        }
    } else {
        echo "<p>Booknetic classes not loaded.</p>";
    }

    // Test 2: Fixing parent_id to NULL
    echo "<h3>Test 2: Booknetic QueryBuilder (with parent_id = null)</h3>";
    if (class_exists('\\BookneticApp\\Models\\ServiceCategory')) {
        $data2 = [
            'name' => 'Debug Model Test 2 - ' . date('Y-m-d H:i:s'),
            'parent_id' => null // Bypassing 0 to prevent strict mode or FK issues
        ];
        
        $result2 = \BookneticApp\Models\ServiceCategory::query()->insert($data2);
        $insertId2 = \BookneticApp\Providers\DB\DB::lastInsertedId();
        
        if ($result2 !== false && $insertId2 > 0) {
            echo "<p style='color: green;'>Success! Inserted category with ID: " . $insertId2 . "</p>";
        } else {
            echo "<p style='color: red;'>Failed to insert via Booknetic Model.</p>";
            echo "<pre>WPDB Error: " . htmlspecialchars($wpdb->last_error) . "</pre>";
            echo "<pre>WPDB Query: " . htmlspecialchars($wpdb->last_query) . "</pre>";
        }
    }
    
    echo "<h2>Testing Appointment Insertion</h2>";
    $app_table_name = $wpdb->base_prefix . "bkntc_appointments";
    echo "Attempting to insert into table: " . htmlspecialchars($app_table_name) . "<br><br>";
    
    if (class_exists('\\BookneticApp\\Models\\Appointment')) {
        $app_data = [
            'location_id'     => 1,
            'service_id'      => 1,
            'staff_id'        => 1,
            'customer_id'     => 1,
            'status'          => 'approved',
            'starts_at'       => time(),
            'ends_at'         => time() + 3600,
            'busy_from'       => time(),
            'busy_to'         => time() + 3600,
            'weight'          => 1,
            'paid_amount'     => 0,
            'payment_method'  => 'local',
            'payment_status'  => 'not_paid',
            'payment_id'      => null,
            'recurring_id'    => null,
            'note'            => 'Debug appointment',
            'locale'          => 'en_US',
            'client_timezone' => 'UTC',
            'created_at'      => time()
        ];
        
        $app_result = \BookneticApp\Models\Appointment::query()->insert($app_data);
        $app_insertId = \BookneticApp\Providers\DB\DB::lastInsertedId();
        
        if ($app_result !== false && $app_insertId > 0) {
            echo "<p style='color: green;'>Success! Inserted appointment with ID: " . $app_insertId . "</p>";
        } else {
            echo "<p style='color: red;'>Failed to insert appointment via Booknetic Model.</p>";
            echo "<pre>WPDB Error: " . htmlspecialchars($wpdb->last_error) . "</pre>";
            echo "<pre>WPDB Query: " . htmlspecialchars($wpdb->last_query) . "</pre>";
        }
    }
    
    echo "<h2>Testing Calendar Fetch Query</h2>";
    if (class_exists('\\BookneticApp\\Models\\Appointment')) {
        $start = time() - 30 * 24 * 3600; // 30 days ago
        $end = time() + 30 * 24 * 3600;   // 30 days ahead
        
        $appointmentQuery = \BookneticApp\Models\Appointment::query()
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start);
            
        $appointments = $appointmentQuery->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', [ 'name', 'color', 'max_capacity' ])
            ->leftJoin('customer', ['first_name', 'last_name'])
            ->fetchAll();
            
        if ($appointments === false) {
             echo "<p style='color: red;'>Calendar fetch failed.</p>";
             echo "<pre>WPDB Error: " . htmlspecialchars($wpdb->last_error) . "</pre>";
        } else {
             echo "<p style='color: green;'>Calendar fetch succeeded! Found " . count($appointments) . " appointments.</p>";
             if (count($appointments) > 0) {
                 echo "<pre>" . htmlspecialchars(print_r($appointments[0]->toArray(), true)) . "</pre>";
             }
        }
    }
    
    echo "<h2>Testing Timesheet Fetch</h2>";
    if (class_exists('\\BookneticApp\\Models\\Timesheet')) {
        $businessHours = \BookneticApp\Models\Timesheet::query()
            ->where('service_id', 'is', null)
            ->where('staff_id', 'is', null)
            ->fetch();
            
        if (!$businessHours) {
             echo "<p style='color: red;'>Timesheet fetch returned FALSE! The default business hours row is missing.</p>";
             echo "<pre>This will cause a fatal JS error in calendar.js: JSON.parse(result.businessHours.timesheet)</pre>";
        } else {
             echo "<p style='color: green;'>Timesheet fetch succeeded!</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Exception caught: " . $e->getMessage() . "</p>";
}

echo "<h2>Done</h2>";
