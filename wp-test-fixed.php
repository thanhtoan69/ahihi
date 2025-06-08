<?php
/**
 * Test WordPress hoạt động sau khi fix object-cache conflict
 */

echo "<h1>WordPress Test - Object Cache Fix</h1>";
echo "<p>Kiểm tra WordPress sau khi fix conflict object-cache...</p>";

try {
    // Load WordPress
    require_once 'wp-load.php';
    
    echo "<p>✅ WordPress core đã tải thành công</p>";
    
    // Check admin functions
    if (function_exists('is_admin')) {
        echo "<p>✅ Admin functions có sẵn</p>";
    }
    
    // Check database
    global $wpdb;
    if ($wpdb && $wpdb->check_connection()) {
        echo "<p>✅ Kết nối database thành công</p>";
    }
    
    // Check object cache
    if (function_exists('wp_cache_get')) {
        echo "<p>✅ Object cache functions có sẵn</p>";
        
        // Test cache
        wp_cache_set('test_key', 'test_value');
        $cached_value = wp_cache_get('test_key');
        if ($cached_value === 'test_value') {
            echo "<p>✅ Object cache hoạt động bình thường</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Links để test:</h2>";
    echo "<p><a href='wp-admin/' target='_blank'>🔗 WordPress Admin Dashboard</a></p>";
    echo "<p><a href='wp-login.php' target='_blank'>🔗 WordPress Login</a></p>";
    echo "<p><a href='index.php' target='_blank'>🔗 Website Frontend</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi: " . $e->getMessage() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Fatal Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Test hoàn thành: " . date('Y-m-d H:i:s') . "</strong></p>";
?>
