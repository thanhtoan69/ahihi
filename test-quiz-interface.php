<?php
/**
 * Test Quiz Interface
 * Phase 40 Frontend Testing
 */

// Include WordPress
require_once('../../../wp-config.php');

// Ensure user is logged in for testing
if (!is_user_logged_in()) {
    wp_set_current_user(1); // Set admin user for testing
}

get_header();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Interface Test - Phase 40</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-header { background: #0073aa; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn { display: inline-block; padding: 10px 15px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #005a87; }
        .btn.secondary { background: #6c757d; }
        .btn.secondary:hover { background: #545b62; }
    </style>
    
    <?php
    // Enqueue quiz styles and scripts if plugin is active
    if (is_plugin_active('environmental-data-dashboard/environmental-data-dashboard.php')) {
        wp_enqueue_style('env-quiz-challenge-styles');
        wp_enqueue_script('env-quiz-interface');
        wp_enqueue_script('env-challenge-dashboard');
    }
    ?>
</head>
<body>

<div class="container">
    <div class="test-header">
        <h1>🧪 Phase 40: Quiz Interface Test</h1>
        <p>Testing the quiz and gamification system frontend components</p>
    </div>

    <?php
    // Check if plugin is active
    if (!is_plugin_active('environmental-data-dashboard/environmental-data-dashboard.php')) {
        echo '<div class="status error">❌ Environmental Data Dashboard plugin is not active!</div>';
        echo '<p><a href="' . admin_url('plugins.php') . '" class="btn">Activate Plugin</a></p>';
        echo '</div></body></html>';
        exit;
    }

    // Check if required tables exist
    global $wpdb;
    $required_tables = ['quiz_categories', 'quiz_questions', 'quiz_sessions', 'quiz_responses'];
    $missing_tables = array();
    
    foreach ($required_tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        if (!$wpdb->get_var("SHOW TABLES LIKE '$full_table_name'")) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo '<div class="status error">❌ Missing database tables: ' . implode(', ', $missing_tables) . '</div>';
        echo '<p><a href="force-create-tables.php" class="btn">Create Tables</a></p>';
    } else {
        echo '<div class="status success">✅ All required database tables exist</div>';
    }

    // Check for sample data
    $categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_categories");
    $questions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_questions");
    
    if ($categories_count == 0 || $questions_count == 0) {
        echo '<div class="status warning">⚠️ No sample data found. Categories: ' . $categories_count . ', Questions: ' . $questions_count . '</div>';
        echo '<p><a href="force-create-tables.php" class="btn">Insert Sample Data</a></p>';
    } else {
        echo '<div class="status success">✅ Sample data available. Categories: ' . $categories_count . ', Questions: ' . $questions_count . '</div>';
    }
    ?>

    <div class="test-section">
        <h2>📝 Quiz Interface Shortcode Test</h2>
        <p>Testing the [env_quiz_interface] shortcode:</p>
        
        <?php
        if (function_exists('do_shortcode')) {
            echo do_shortcode('[env_quiz_interface]');
        } else {
            echo '<div class="status error">❌ WordPress shortcode system not available</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🏆 Quiz Leaderboard Test</h2>
        <p>Testing the [env_quiz_leaderboard] shortcode:</p>
        
        <?php
        if (function_exists('do_shortcode')) {
            echo do_shortcode('[env_quiz_leaderboard]');
        } else {
            echo '<div class="status error">❌ WordPress shortcode system not available</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🎯 Challenge Dashboard Test</h2>
        <p>Testing the [env_challenge_dashboard] shortcode:</p>
        
        <?php
        if (function_exists('do_shortcode')) {
            echo do_shortcode('[env_challenge_dashboard]');
        } else {
            echo '<div class="status error">❌ WordPress shortcode system not available</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📊 User Progress Test</h2>
        <p>Testing the [env_user_progress] shortcode:</p>
        
        <?php
        if (function_exists('do_shortcode')) {
            echo do_shortcode('[env_user_progress]');
        } else {
            echo '<div class="status error">❌ WordPress shortcode system not available</div>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔧 AJAX Functionality Test</h2>
        <p>Testing AJAX endpoints:</p>
        
        <button onclick="testAjaxEndpoints()" class="btn">Test AJAX Endpoints</button>
        <div id="ajax-results"></div>
        
        <script>
        function testAjaxEndpoints() {
            const resultsDiv = document.getElementById('ajax-results');
            resultsDiv.innerHTML = '<p>Testing AJAX endpoints...</p>';
            
            // Test quiz categories endpoint
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_quiz_categories&nonce=<?php echo wp_create_nonce('env_quiz_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML += '<p><strong>Quiz Categories:</strong> ' + 
                    (data.success ? '✅ Success - ' + data.data.length + ' categories found' : '❌ Failed - ' + data.data) + '</p>';
            })
            .catch(error => {
                resultsDiv.innerHTML += '<p><strong>Quiz Categories:</strong> ❌ Error - ' + error + '</p>';
            });
            
            // Test available challenges endpoint
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_available_challenges&nonce=<?php echo wp_create_nonce('env_challenge_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML += '<p><strong>Available Challenges:</strong> ' + 
                    (data.success ? '✅ Success - ' + data.data.length + ' challenges found' : '❌ Failed - ' + data.data) + '</p>';
            })
            .catch(error => {
                resultsDiv.innerHTML += '<p><strong>Available Challenges:</strong> ❌ Error - ' + error + '</p>';
            });
        }
        </script>
    </div>

    <div class="test-section">
        <h2>📋 Test Actions</h2>
        <p>
            <a href="test-phase40-database.php" class="btn">Database Status</a>
            <a href="force-create-tables.php" class="btn secondary">Force Create Tables</a>
            <a href="<?php echo admin_url('admin.php?page=env-dashboard'); ?>" class="btn">Admin Dashboard</a>
            <a href="<?php echo admin_url('plugins.php'); ?>" class="btn secondary">Plugin Management</a>
        </p>
    </div>

    <div class="test-section">
        <h2>📈 Phase 40 Status</h2>
        <div class="status success">
            <h4>✅ Completed Components:</h4>
            <ul>
                <li>Database tables creation</li>
                <li>Quiz Manager class integration</li>
                <li>Challenge System class integration</li>
                <li>AJAX handlers implementation</li>
                <li>Shortcode registration</li>
                <li>Frontend JavaScript interfaces</li>
                <li>CSS styling</li>
                <li>Sample data insertion</li>
            </ul>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
