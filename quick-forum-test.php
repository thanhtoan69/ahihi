<?php
echo "Phase 33 Forum Test\n";
echo "==================\n";

require_once 'wp-config.php';
require_once 'wp-load.php';

global $wpdb;

// Check forum data
$forums = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_forum'");
$topics = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_topic'"); 
$replies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'ep_reply'");

echo "WordPress Forum Posts:\n";
echo "Forums: $forums\n";
echo "Topics: $topics\n"; 
echo "Replies: $replies\n\n";

// Check plugin
$plugin_active = is_plugin_active('environmental-platform-forum/environmental-platform-forum.php');
echo "Plugin Active: " . ($plugin_active ? "YES" : "NO") . "\n";

// Check files
$main_file = file_exists('wp-content/plugins/environmental-platform-forum/environmental-platform-forum.php');
$css_file = file_exists('wp-content/plugins/environmental-platform-forum/assets/css/forum.css');
$js_file = file_exists('wp-content/plugins/environmental-platform-forum/assets/js/forum.js');

echo "Main Plugin File: " . ($main_file ? "EXISTS" : "MISSING") . "\n";
echo "CSS File: " . ($css_file ? "EXISTS" : "MISSING") . "\n";
echo "JS File: " . ($js_file ? "EXISTS" : "MISSING") . "\n";

echo "\nPhase 33 Status: ";
if ($plugin_active && $forums > 0 && $main_file && $css_file && $js_file) {
    echo "COMPLETED SUCCESSFULLY!\n";
} else {
    echo "NEEDS ATTENTION\n";
}

echo "\nTest completed.\n";
?>
