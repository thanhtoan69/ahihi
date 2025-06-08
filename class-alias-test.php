<?php
/**
 * Class Alias Verification Test
 * 
 * Tests all plugin class aliases to ensure they're working correctly
 */

// Set up WordPress environment
require_once('wp-config.php');
require_once(ABSPATH . 'wp-settings.php');

echo "<h1>Environmental Platform Petitions - Class Alias Verification</h1>\n";
echo "<pre>\n";

// Test class existence and aliases
$classes_to_test = array(
    'Environmental_Platform_Petitions_Database' => 'EPP_Database',
    'Environmental_Platform_Petitions_Signature_Manager' => 'EPP_Signature_Manager',
    'Environmental_Platform_Petitions_Verification_System' => 'EPP_Verification_System',
    'Environmental_Platform_Petitions_Campaign_Manager' => 'EPP_Campaign_Manager',
    'Environmental_Platform_Petitions_Share_Manager' => 'EPP_Share_Manager',
    'Environmental_Platform_Petitions_Admin_Dashboard' => 'EPP_Admin_Dashboard',
    'Environmental_Platform_Petitions_Analytics' => 'EPP_Analytics',
    'Environmental_Platform_Petitions_Email_Notifications' => 'EPP_Email_Notifications',
    'Environmental_Platform_Petitions_REST_API' => 'EPP_REST_API'
);

echo "=== CLASS EXISTENCE CHECK ===\n\n";

foreach ($classes_to_test as $full_class => $alias_class) {
    $full_exists = class_exists($full_class);
    $alias_exists = class_exists($alias_class);
    
    echo sprintf("%-50s: %s\n", $full_class, $full_exists ? 'EXISTS' : 'MISSING');
    echo sprintf("%-50s: %s\n", $alias_class, $alias_exists ? 'EXISTS' : 'MISSING');
    
    if ($full_exists && $alias_exists) {
        $same_class = ($full_class === $alias_class || is_a($alias_class, $full_class, true));
        echo sprintf("%-50s: %s\n", "Alias relationship", $same_class ? 'CORRECT' : 'INCORRECT');
    }
    echo "\n";
}

echo "\n=== PLUGIN STATUS ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'environmental-platform-petitions/environmental-platform-petitions.php';
$is_active = in_array($plugin_file, $active_plugins);

echo "Plugin Status: " . ($is_active ? 'ACTIVE' : 'INACTIVE') . "\n";

if ($is_active) {
    echo "\n=== TRYING TO INSTANTIATE CLASSES ===\n\n";
    
    $instantiation_results = array();
    
    foreach ($classes_to_test as $full_class => $alias_class) {
        if (class_exists($alias_class)) {
            try {
                $instance = new $alias_class();
                $instantiation_results[$alias_class] = 'SUCCESS';
                echo "$alias_class: INSTANTIATED SUCCESSFULLY\n";
            } catch (Exception $e) {
                $instantiation_results[$alias_class] = 'ERROR: ' . $e->getMessage();
                echo "$alias_class: ERROR - " . $e->getMessage() . "\n";
            } catch (Error $e) {
                $instantiation_results[$alias_class] = 'FATAL ERROR: ' . $e->getMessage();
                echo "$alias_class: FATAL ERROR - " . $e->getMessage() . "\n";
            }
        } else {
            $instantiation_results[$alias_class] = 'CLASS NOT FOUND';
            echo "$alias_class: CLASS NOT FOUND\n";
        }
    }
}

echo "\n=== SUMMARY ===\n\n";

$all_classes_exist = true;
$all_aliases_work = true;

foreach ($classes_to_test as $full_class => $alias_class) {
    if (!class_exists($full_class)) {
        $all_classes_exist = false;
        echo "MISSING: $full_class\n";
    }
    if (!class_exists($alias_class)) {
        $all_aliases_work = false;
        echo "MISSING ALIAS: $alias_class\n";
    }
}

if ($all_classes_exist && $all_aliases_work) {
    echo "✅ ALL CLASSES AND ALIASES WORKING CORRECTLY!\n";
} else {
    echo "❌ Some classes or aliases are missing\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
echo "</pre>\n";
?>
