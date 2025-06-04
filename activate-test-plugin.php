<?php
// Activate test plugin
define('WP_USE_THEMES', false);
require_once('wp-load.php');

$test_plugin = 'petition-system-tester.php';
$active_plugins = get_option('active_plugins', array());

if (!in_array($test_plugin, $active_plugins)) {
    $active_plugins[] = $test_plugin;
    update_option('active_plugins', $active_plugins);
    echo "✅ Test plugin activated\n";
} else {
    echo "✅ Test plugin already active\n";
}

echo "🌐 Test page URL: http://localhost/moitruong/wp-admin/tools.php?page=petition-system-test\n";
?>
