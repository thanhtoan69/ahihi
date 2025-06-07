<?php
/**
 * Test script for Environmental Multi-language Support Plugin
 * Phase 50 Integration Test
 */

// Change to WordPress directory
chdir('c:\\xampp\\htdocs\\moitruong');

// Load WordPress
require_once('wp-load.php');

echo "=== Environmental Multi-language Support Plugin Test ===\n\n";

// Test 1: Check if plugin files exist
echo "1. Checking plugin files...\n";
$plugin_path = WP_PLUGIN_DIR . '/environmental-multilang-support/environmental-multilang-support.php';
if (file_exists($plugin_path)) {
    echo "✓ Main plugin file exists\n";
} else {
    echo "✗ Main plugin file missing\n";
    exit(1);
}

// Check component files
$components = array(
    'class-language-switcher.php',
    'class-translation-manager.php',
    'class-rtl-support.php',
    'class-seo-optimizer.php',
    'class-user-preferences.php',
    'class-admin-interface.php',
    'class-language-detector.php',
    'class-url-manager.php',
    'class-content-duplicator.php',
    'class-translation-api.php'
);

foreach ($components as $component) {
    $component_path = WP_PLUGIN_DIR . '/environmental-multilang-support/includes/' . $component;
    if (file_exists($component_path)) {
        echo "✓ Component $component exists\n";
    } else {
        echo "✗ Component $component missing\n";
    }
}

// Test 2: Check asset files
echo "\n2. Checking asset files...\n";
$assets = array(
    'assets/css/admin.css',
    'assets/css/frontend.css',
    'assets/js/admin.js',
    'assets/js/frontend.js'
);

foreach ($assets as $asset) {
    $asset_path = WP_PLUGIN_DIR . '/environmental-multilang-support/' . $asset;
    if (file_exists($asset_path)) {
        echo "✓ Asset $asset exists\n";
    } else {
        echo "✗ Asset $asset missing\n";
    }
}

// Test 3: Check flag images
echo "\n3. Checking flag images...\n";
$flags = array('vi.svg', 'en.svg', 'zh.svg', 'ja.svg', 'ko.svg', 'th.svg', 'ar.svg', 'he.svg', 'fr.svg', 'es.svg');

foreach ($flags as $flag) {
    $flag_path = WP_PLUGIN_DIR . '/environmental-multilang-support/assets/images/flags/' . $flag;
    if (file_exists($flag_path)) {
        echo "✓ Flag $flag exists\n";
    } else {
        echo "✗ Flag $flag missing\n";
    }
}

// Test 4: Check if plugin can be activated
echo "\n4. Testing plugin activation...\n";
if (is_plugin_active('environmental-multilang-support/environmental-multilang-support.php')) {
    echo "✓ Plugin is already active\n";
} else {
    $result = activate_plugin('environmental-multilang-support/environmental-multilang-support.php');
    if (is_wp_error($result)) {
        echo "✗ Plugin activation failed: " . $result->get_error_message() . "\n";
    } else {
        echo "✓ Plugin activated successfully\n";
    }
}

// Test 5: Check if plugin classes are loaded
echo "\n5. Testing plugin classes...\n";
if (class_exists('Environmental_Multilang_Support')) {
    echo "✓ Main plugin class loaded\n";
    
    $plugin = Environmental_Multilang_Support::get_instance();
    if ($plugin) {
        echo "✓ Plugin instance created\n";
        
        // Test available languages
        $languages = $plugin->get_available_languages();
        if (is_array($languages) && count($languages) > 0) {
            echo "✓ Languages loaded: " . count($languages) . " languages\n";
            foreach ($languages as $code => $lang) {
                echo "  - $code: {$lang['native_name']}\n";
            }
        } else {
            echo "✗ No languages loaded\n";
        }
        
    } else {
        echo "✗ Plugin instance failed\n";
    }
} else {
    echo "✗ Main plugin class not loaded\n";
}

// Test 6: Check component classes
echo "\n6. Testing component classes...\n";
$component_classes = array(
    'EMS_Language_Switcher',
    'EMS_Translation_Manager',
    'EMS_RTL_Support',
    'EMS_SEO_Optimizer',
    'EMS_User_Preferences',
    'EMS_Admin_Interface',
    'EMS_Language_Detector',
    'EMS_URL_Manager',
    'EMS_Content_Duplicator',
    'EMS_Translation_API'
);

foreach ($component_classes as $class) {
    if (class_exists($class)) {
        echo "✓ Component class $class loaded\n";
    } else {
        echo "✗ Component class $class not loaded\n";
    }
}

// Test 7: Check database tables
echo "\n7. Checking database tables...\n";
global $wpdb;
$table_name = $wpdb->prefix . 'ems_translations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "✓ Translation table exists\n";
} else {
    echo "✗ Translation table missing\n";
}

// Test 8: Check WordPress hooks
echo "\n8. Testing WordPress hooks...\n";
$hooks_to_check = array(
    'wp_enqueue_scripts',
    'admin_enqueue_scripts',
    'wp_head',
    'admin_menu',
    'init'
);

foreach ($hooks_to_check as $hook) {
    if (has_action($hook)) {
        echo "✓ Hook $hook has actions\n";
    } else {
        echo "- Hook $hook has no actions (may be normal)\n";
    }
}

// Test 9: Check language functions
echo "\n9. Testing helper functions...\n";
$functions_to_check = array(
    'ems_get_current_language',
    'ems_get_available_languages',
    'ems_is_rtl_language',
    'ems_get_translation_url'
);

foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo "✓ Function $function exists\n";
    } else {
        echo "✗ Function $function missing\n";
    }
}

// Test 10: Integration with existing Environmental Platform
echo "\n10. Testing Environmental Platform integration...\n";

// Check if Environmental Platform core exists
if (class_exists('Environmental_Platform')) {
    echo "✓ Environmental Platform core detected\n";
} else {
    echo "- Environmental Platform core not detected (may be normal)\n";
}

// Check if other Environmental plugins exist
$env_plugins = array(
    'environmental-social-viral/environmental-social-viral.php',
    'environmental-performance-monitor/environmental-performance-monitor.php'
);

foreach ($env_plugins as $plugin) {
    if (is_plugin_active($plugin)) {
        echo "✓ Environmental plugin active: $plugin\n";
    } else {
        echo "- Environmental plugin inactive: $plugin\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Phase 50: Multi-language Support Plugin Test Completed\n";
echo "Check results above for any issues that need to be addressed.\n";
?>
