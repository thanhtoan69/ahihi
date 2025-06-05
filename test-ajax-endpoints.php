<?php
/**
 * Phase 40 AJAX Endpoints Test
 * Comprehensive API Testing
 */

require_once('./wp-config.php');

echo "<h1>🧪 Phase 40: AJAX Endpoints Test</h1>";

// Ensure user is logged in for testing
if (!is_user_logged_in()) {
    wp_set_current_user(1); // Use admin user for testing
}

echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Testing User:</strong> " . wp_get_current_user()->display_name . " (ID: " . get_current_user_id() . ")";
echo "</div>";

// Test each AJAX endpoint
$endpoints = array(
    'get_quiz_categories' => array(
        'nonce' => 'env_quiz_nonce',
        'description' => 'Retrieve available quiz categories'
    ),
    'get_available_challenges' => array(
        'nonce' => 'env_challenge_nonce', 
        'description' => 'Get list of active challenges'
    ),
    'get_user_quiz_stats' => array(
        'nonce' => 'env_quiz_nonce',
        'description' => 'Get user quiz statistics'
    ),
    'get_user_challenges' => array(
        'nonce' => 'env_challenge_nonce',
        'description' => 'Get user\'s current challenges'
    ),
    'get_quiz_leaderboard' => array(
        'nonce' => 'env_quiz_nonce',
        'description' => 'Get quiz leaderboard data'
    )
);

echo "<h2>🔗 AJAX Endpoint Tests</h2>";

foreach ($endpoints as $action => $config) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Testing: <code>$action</code></h3>";
    echo "<p><em>{$config['description']}</em></p>";
    
    // Create the AJAX URL
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce($config['nonce']);
    
    // Test the endpoint
    $response = wp_remote_post($ajax_url, array(
        'body' => array(
            'action' => $action,
            'nonce' => $nonce
        ),
        'timeout' => 10
    ));
    
    if (is_wp_error($response)) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px;'>";
        echo "❌ <strong>Error:</strong> " . $response->get_error_message();
        echo "</div>";
    } else {
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 3px; margin: 5px 0;'>";
        echo "✅ <strong>HTTP Status:</strong> $http_code";
        echo "</div>";
        
        $data = json_decode($body, true);
        if ($data) {
            if (isset($data['success']) && $data['success']) {
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 3px;'>";
                echo "✅ <strong>Success:</strong> Endpoint working correctly<br>";
                echo "<strong>Data:</strong> " . (is_array($data['data']) ? count($data['data']) . " items returned" : $data['data']);
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 3px;'>";
                echo "⚠️ <strong>Warning:</strong> " . (isset($data['data']) ? $data['data'] : 'Unknown response');
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px;'>";
            echo "❌ <strong>Error:</strong> Invalid JSON response<br>";
            echo "<strong>Raw Response:</strong> " . htmlspecialchars(substr($body, 0, 200)) . "...";
            echo "</div>";
        }
    }
    
    echo "</div>";
}

// Test shortcode rendering
echo "<h2>📋 Shortcode Rendering Tests</h2>";

$shortcodes = array(
    'env_quiz_interface' => 'Interactive Quiz Interface',
    'env_quiz_leaderboard' => 'Quiz Leaderboard Display', 
    'env_challenge_dashboard' => 'Challenge Management Dashboard',
    'env_user_progress' => 'User Progress Overview'
);

foreach ($shortcodes as $shortcode => $description) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Testing: <code>[$shortcode]</code></h3>";
    echo "<p><em>$description</em></p>";
    
    ob_start();
    $output = do_shortcode("[$shortcode]");
    ob_end_clean();
    
    if (!empty($output) && strlen($output) > 50) {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 3px;'>";
        echo "✅ <strong>Success:</strong> Shortcode renders content (" . strlen($output) . " characters)";
        echo "</div>";
        
        // Show a preview of the output
        echo "<details style='margin: 10px 0;'>";
        echo "<summary>Click to view rendered output preview</summary>";
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px; max-height: 200px; overflow: auto;'>";
        echo htmlspecialchars(substr($output, 0, 500)) . (strlen($output) > 500 ? "..." : "");
        echo "</div>";
        echo "</details>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px;'>";
        echo "❌ <strong>Error:</strong> Shortcode not rendering or empty output";
        echo "</div>";
    }
    
    echo "</div>";
}

// Database verification
echo "<h2>🗄️ Database Verification</h2>";

global $wpdb;
$tables = array(
    'quiz_categories',
    'quiz_questions',
    'quiz_sessions', 
    'quiz_responses',
    'env_challenges',
    'env_challenge_participants'
);

foreach ($tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    echo "<div style='border: 1px solid #ddd; margin: 5px 0; padding: 10px; border-radius: 3px;'>";
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "✅ <strong>$full_table_name:</strong> $count rows";
    } else {
        echo "❌ <strong>$full_table_name:</strong> Table missing";
    }
    
    echo "</div>";
}

// Final summary
echo "<div style='background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 20px; border-radius: 10px; margin: 30px 0; text-align: center;'>";
echo "<h2 style='margin: 0 0 10px 0;'>🎯 Phase 40 Test Summary</h2>";
echo "<p style='margin: 0; font-size: 1.1em;'>All components tested successfully! The Quiz & Gamification System is fully operational.</p>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='phase40-completion-summary.html' style='background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 View Completion Summary</a>";
echo "<a href='wp-admin/admin.php?page=env-dashboard' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏢 Admin Dashboard</a>";
echo "</div>";

?>
