<?php
/**
 * Create Test Petition
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "=== CREATING TEST PETITION ===\n";

// Create a test petition
$petition_data = array(
    'post_title'    => 'Save the Local Forest - Test Petition',
    'post_content'  => 'We call upon the local government to protect our precious forest from development. This area is home to diverse wildlife and provides clean air for our community.',
    'post_status'   => 'publish',
    'post_type'     => 'env_petition',
    'post_author'   => 1,
    'meta_input'    => array(
        'petition_goal' => 1000,
        'petition_description' => 'Protect our local forest ecosystem',
        'petition_target' => 'Local Government',
        'petition_category' => 'Conservation',
        'petition_deadline' => date('Y-m-d', strtotime('+30 days'))
    )
);

$petition_id = wp_insert_post($petition_data);

if ($petition_id && !is_wp_error($petition_id)) {
    echo "✅ Test petition created with ID: {$petition_id}\n";
    echo "📝 Title: " . get_the_title($petition_id) . "\n";
    echo "🔗 URL: " . get_permalink($petition_id) . "\n";
    
    // Add some test metadata
    update_post_meta($petition_id, 'petition_goal', 1000);
    update_post_meta($petition_id, 'petition_current_signatures', 0);
    update_post_meta($petition_id, 'petition_status', 'active');
    
    echo "✅ Petition metadata added\n";
    
} else {
    echo "❌ Failed to create test petition\n";
    if (is_wp_error($petition_id)) {
        echo "Error: " . $petition_id->get_error_message() . "\n";
    }
}

// Test shortcode rendering
echo "\n=== TESTING SHORTCODE ===\n";
$shortcode_content = do_shortcode("[petition_signature_form petition_id='{$petition_id}']");
if (!empty($shortcode_content)) {
    echo "✅ Signature form shortcode rendered (" . strlen($shortcode_content) . " characters)\n";
} else {
    echo "❌ Signature form shortcode returned empty\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
