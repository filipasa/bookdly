<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

header('Content-Type: text/plain');

try {
    require_once '/home/www/public/wp-load.php';

    $_mn = 1;

    ob_start();
    include '/home/www/public/wp-content/plugins/booknetic/app/Backend/Staff/Controllers/view/modal/add_new_v2.php';
    $html = ob_get_clean();

    echo "HTML length: " . strlen($html) . "\n";
    
    // Find all <script> tags and print their contents
    preg_match_all("/<script\b[^>]*>(.*?)<\/script>/is", $html, $matches);
    foreach ($matches[0] as $i => $s) {
        echo "=== SCRIPT TAG " . ($i + 1) . " ===\n";
        echo $s . "\n\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
unlink(__FILE__);
