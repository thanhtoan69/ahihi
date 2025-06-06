<?php
require_once 'wp-config.php';
require_once 'wp-load.php';
require_once 'wp-admin/includes/plugin.php';

echo "Checking installed plugins...\n";
$plugins = get_plugins();
$security_plugins = ['wordfence', 'updraftplus', 'two-factor', 'limit-login'];

echo "Total plugins installed: " . count($plugins) . "\n\n";

echo "Security Plugins Status:\n";
echo "========================\n";

foreach($plugins as $path => $data) {
    foreach($security_plugins as $security) {
        if(strpos($path, $security) !== false) {
            echo "Found: " . $data['Name'] . " (" . $path . ")\n";
            echo "Active: " . (is_plugin_active($path) ? 'Yes' : 'No') . "\n";
            echo "Version: " . $data['Version'] . "\n";
            echo "---\n";
        }
    }
}

// Check if WordPress mu-plugins directory exists
$mu_plugins_dir = WPMU_PLUGIN_DIR;
echo "\nMU-Plugins Directory: " . $mu_plugins_dir . "\n";
echo "Exists: " . (is_dir($mu_plugins_dir) ? 'Yes' : 'No') . "\n";

if (is_dir($mu_plugins_dir)) {
    $mu_files = scandir($mu_plugins_dir);
    echo "MU-Plugin files:\n";
    foreach($mu_files as $file) {
        if($file != '.' && $file != '..') {
            echo "- " . $file . "\n";
        }
    }
}
?>
