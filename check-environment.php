<?php
/**
 * Plugin Activation and WordPress Integration Test
 */

// Define WordPress path
$wp_path = __DIR__;

// Check if WordPress files exist
$wp_files = [
    'wp-config.php',
    'wp-load.php',
    'wp-blog-header.php',
    'wp-admin/admin.php'
];

echo "<h1>WordPress Environment Check</h1>";

foreach ($wp_files as $file) {
    if (file_exists($wp_path . '/' . $file)) {
        echo "<p>✅ {$file} exists</p>";
    } else {
        echo "<p>❌ {$file} missing</p>";
    }
}

// Try to load WordPress
try {
    require_once($wp_path . '/wp-config.php');
    echo "<p>✅ wp-config.php loaded successfully</p>";
    
    // Check database connection
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        echo "<p>❌ Database connection failed: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p>✅ Database connection successful</p>";
        
        // Check if WordPress tables exist
        $result = $mysqli->query("SHOW TABLES LIKE 'wp_options'");
        if ($result && $result->num_rows > 0) {
            echo "<p>✅ WordPress tables exist</p>";
            
            // Check if our plugin is active
            $result = $mysqli->query("SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'");
            if ($result) {
                $row = $result->fetch_assoc();
                $active_plugins = unserialize($row['option_value']);
                
                if (in_array('environmental-platform-core/environmental-platform-core.php', $active_plugins)) {
                    echo "<p>✅ Environmental Platform Core plugin is active</p>";
                } else {
                    echo "<p>❌ Environmental Platform Core plugin is not active</p>";
                    echo "<p>Active plugins: " . implode(', ', $active_plugins) . "</p>";
                }
            }
        } else {
            echo "<p>❌ WordPress tables not found</p>";
        }
        
        $mysqli->close();
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error loading WordPress: " . $e->getMessage() . "</p>";
}

// Check plugin files
echo "<h2>Plugin Files Check</h2>";
$plugin_path = $wp_path . '/wp-content/plugins/environmental-platform-core';

$plugin_files = [
    'environmental-platform-core.php',
    'includes/class-user-management.php',
    'includes/class-social-auth.php',
    'admin/users.php',
    'templates/login-form.php',
    'templates/registration-form.php',
    'templates/user-profile.php',
    'templates/social-login.php'
];

foreach ($plugin_files as $file) {
    if (file_exists($plugin_path . '/' . $file)) {
        echo "<p>✅ {$file} exists</p>";
    } else {
        echo "<p>❌ {$file} missing</p>";
    }
}

echo "<h2>WordPress Admin Links</h2>";
echo "<p><a href='/moitruong/wp-admin/' target='_blank'>WordPress Admin</a></p>";
echo "<p><a href='/moitruong/wp-admin/plugins.php' target='_blank'>Plugins Page</a></p>";
echo "<p><a href='/moitruong/wp-admin/admin.php?page=environmental-users' target='_blank'>Environmental Users Admin</a></p>";
?>
