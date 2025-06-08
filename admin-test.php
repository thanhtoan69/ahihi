<?php
/**
 * WordPress Admin Access Verification
 * Quick test to verify WordPress admin is accessible
 */

echo "<h1>WordPress Admin Access Test</h1>";
echo "<p>Testing WordPress admin access...</p>";

// Test direct access to wp-admin
echo "<h2>Direct Admin Test</h2>";
echo "<p>Trying to access wp-admin directly...</p>";

// Test WordPress loading
if (file_exists('wp-load.php')) {
    try {
        // Load WordPress
        require_once 'wp-load.php';
        
        echo "<p>✅ WordPress core loaded successfully</p>";
        
        // Check if we can access admin functions
        if (function_exists('is_admin')) {
            echo "<p>✅ Admin functions available</p>";
        }
        
        // Check database connection
        global $wpdb;
        if ($wpdb && $wpdb->check_connection()) {
            echo "<p>✅ Database connection working</p>";
        }
        
        // Check if user can be logged in
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if ($user->ID) {
                echo "<p>✅ User logged in: " . $user->user_login . "</p>";
            } else {
                echo "<p>ℹ️ No user currently logged in</p>";
            }
        }
        
        echo "<hr>";
        echo "<h2>Admin Access Links</h2>";
        echo "<p><a href='wp-admin/' target='_blank'>WordPress Admin Dashboard</a></p>";
        echo "<p><a href='wp-login.php' target='_blank'>WordPress Login</a></p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error loading WordPress: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ wp-load.php not found</p>";
}

echo "<hr>";
echo "<p><strong>Test completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>
