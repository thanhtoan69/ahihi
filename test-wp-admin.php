<?php
/**
 * Test WordPress Admin Access
 * This script tests if WordPress loads properly without errors
 */

// Turn on error reporting to catch any issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>WordPress Admin Access Test</h2>\n";
echo "<p>Testing WordPress core loading...</p>\n";

// Test if WordPress can load
try {
    // Change to WordPress directory
    chdir(__DIR__);
    
    // Include WordPress
    if (file_exists('wp-load.php')) {
        echo "<p>✓ wp-load.php found</p>\n";
        
        // Capture any output/errors during WordPress loading
        ob_start();
        $error_occurred = false;
        
        // Set error handler to catch any issues
        set_error_handler(function($severity, $message, $filename, $lineno) use (&$error_occurred) {
            $error_occurred = true;
            echo "<p>❌ Error: $message in $filename on line $lineno</p>\n";
        });
        
        require_once 'wp-load.php';
        
        // Restore error handler
        restore_error_handler();
        
        $output = ob_get_clean();
        
        if ($error_occurred) {
            echo "<p>❌ WordPress loading encountered errors:</p>\n";
            echo "<pre>$output</pre>\n";
        } else {
            echo "<p>✓ WordPress loaded successfully!</p>\n";
            
            // Test if WordPress functions are available
            if (function_exists('wp_is_mobile')) {
                echo "<p>✓ wp_is_mobile() function available</p>\n";
            } else {
                echo "<p>❌ wp_is_mobile() function not available</p>\n";
            }
            
            if (function_exists('is_user_logged_in')) {
                echo "<p>✓ is_user_logged_in() function available</p>\n";
            } else {
                echo "<p>❌ is_user_logged_in() function not available</p>\n";
            }
            
            if (function_exists('sanitize_text_field')) {
                echo "<p>✓ sanitize_text_field() function available</p>\n";
            } else {
                echo "<p>❌ sanitize_text_field() function not available</p>\n";
            }
            
            // Test admin access
            if (is_admin()) {
                echo "<p>✓ Admin context detected</p>\n";
            } else {
                echo "<p>ℹ️ Not in admin context (normal for this test)</p>\n";
            }
            
            // Test database connection
            global $wpdb;
            if ($wpdb && $wpdb->check_connection()) {
                echo "<p>✓ Database connection successful</p>\n";
            } else {
                echo "<p>❌ Database connection failed</p>\n";
            }
        }
        
    } else {
        echo "<p>❌ wp-load.php not found</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exception occurred: " . $e->getMessage() . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ Fatal error occurred: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Test completed.</strong></p>\n";
echo "<p>If no errors appear above, you can now try accessing: <a href='wp-admin/'>WordPress Admin</a></p>\n";
?>
