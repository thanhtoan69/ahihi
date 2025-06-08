<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Environmental Live Chat & Customer Support</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            margin: 20px 0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #2c5530;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .shortcode-test {
            border: 1px dashed #ccc;
            padding: 15px;
            margin: 10px 0;
            background-color: #f9f9f9;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-active { background-color: #4CAF50; }
        .status-inactive { background-color: #f44336; }
        .status-pending { background-color: #ff9800; }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .feature-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .feature-card h3 {
            margin-top: 0;
            color: #2c5530;
        }
        .feature-list {
            list-style-type: none;
            padding: 0;
        }
        .feature-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✓ ";
            color: #4CAF50;
            font-weight: bold;
        }
        .admin-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .admin-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: #2c5530;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .admin-link:hover {
            background-color: #1e3a21;
        }
        .alert {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <header>
        <h1>🌱 Environmental Platform - Live Chat & Customer Support Test</h1>
        <p><strong>Phase 56 Implementation</strong> - Comprehensive Customer Support System</p>
    </header>

    <div class="alert alert-info">
        <strong>📋 Test Instructions:</strong>
        <ol>
            <li>Verify that all plugin components are loaded correctly</li>
            <li>Test each shortcode functionality</li>
            <li>Check admin dashboard accessibility</li>
            <li>Verify real-time chat functionality</li>
            <li>Test support ticket system</li>
            <li>Validate FAQ and knowledge base features</li>
        </ol>
    </div>

    <div class="test-section">
        <h2>🔧 Plugin Status Check</h2>
        <div id="plugin-status">
            <?php
            // WordPress bootstrap for testing
            if (file_exists('wp-config.php')) {
                require_once 'wp-config.php';
                require_once ABSPATH . 'wp-settings.php';
                
                echo '<p><span class="status-indicator status-active"></span><strong>WordPress Environment:</strong> ✓ Loaded</p>';
                
                // Check if plugin is active
                $active_plugins = get_option('active_plugins', array());
                $plugin_path = 'environmental-live-chat/environmental-live-chat.php';
                
                if (in_array($plugin_path, $active_plugins)) {
                    echo '<p><span class="status-indicator status-active"></span><strong>Plugin Status:</strong> ✓ Active</p>';
                } else {
                    echo '<p><span class="status-indicator status-inactive"></span><strong>Plugin Status:</strong> ✗ Inactive</p>';
                }
                
                // Check main class
                if (class_exists('Environmental_Live_Chat')) {
                    echo '<p><span class="status-indicator status-active"></span><strong>Main Class:</strong> ✓ Loaded</p>';
                } else {
                    echo '<p><span class="status-indicator status-inactive"></span><strong>Main Class:</strong> ✗ Not Found</p>';
                }
                
                // Check database tables
                global $wpdb;
                $tables_exist = 0;
                $tables_total = 7;
                $table_names = [
                    'env_chat_sessions', 'env_chat_messages', 'env_support_tickets',
                    'env_ticket_replies', 'env_faq', 'env_chat_analytics', 'env_chat_operators'
                ];
                
                foreach ($table_names as $table) {
                    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$table'")) {
                        $tables_exist++;
                    }
                }
                
                if ($tables_exist == $tables_total) {
                    echo '<p><span class="status-indicator status-active"></span><strong>Database Tables:</strong> ✓ All ' . $tables_total . ' tables exist</p>';
                } else {
                    echo '<p><span class="status-indicator status-pending"></span><strong>Database Tables:</strong> ⚠ ' . $tables_exist . '/' . $tables_total . ' tables exist</p>';
                }
            } else {
                echo '<p><span class="status-indicator status-inactive"></span><strong>WordPress Environment:</strong> ✗ Not Available</p>';
            }
            ?>
        </div>
    </div>

    <div class="test-section">
        <h2>💬 Live Chat Widget Test</h2>
        <p>The chat widget should appear in the bottom-right corner of the page.</p>
        <div class="shortcode-test">
            <h4>Shortcode: [env_chat_widget]</h4>
            <?php
            if (function_exists('do_shortcode')) {
                echo do_shortcode('[env_chat_widget]');
            } else {
                echo '<p style="color: red;">WordPress shortcode function not available</p>';
            }
            ?>
        </div>
        <div class="alert alert-success">
            <strong>Features to Test:</strong>
            <ul>
                <li>Chat button visibility and positioning</li>
                <li>Pre-chat form functionality</li>
                <li>Real-time messaging</li>
                <li>File upload capability</li>
                <li>Chat rating system</li>
                <li>Business hours detection</li>
                <li>Operator availability status</li>
            </ul>
        </div>
    </div>

    <div class="test-section">
        <h2>❓ FAQ Widget Test</h2>
        <div class="shortcode-test">
            <h4>Shortcode: [env_faq_widget]</h4>
            <?php
            if (function_exists('do_shortcode')) {
                echo do_shortcode('[env_faq_widget]');
            } else {
                echo '<p style="color: red;">WordPress shortcode function not available</p>';
            }
            ?>
        </div>
    </div>

    <div class="test-section">
        <h2>🎫 Support Form Test</h2>
        <div class="shortcode-test">
            <h4>Shortcode: [env_support_form]</h4>
            <?php
            if (function_exists('do_shortcode')) {
                echo do_shortcode('[env_support_form]');
            } else {
                echo '<p style="color: red;">WordPress shortcode function not available</p>';
            }
            ?>
        </div>
    </div>

    <div class="test-section">
        <h2>📚 Knowledge Base Test</h2>
        <div class="shortcode-test">
            <h4>Shortcode: [env_knowledge_base]</h4>
            <?php
            if (function_exists('do_shortcode')) {
                echo do_shortcode('[env_knowledge_base]');
            } else {
                echo '<p style="color: red;">WordPress shortcode function not available</p>';
            }
            ?>
        </div>
    </div>

    <div class="test-section">
        <h2>🔗 Admin Dashboard Links</h2>
        <p>Access the administrative interfaces for managing the live chat and support system:</p>
        <div class="admin-links">
            <?php
            if (function_exists('admin_url')) {
                echo '<a href="' . admin_url('admin.php?page=env-chat-dashboard') . '" class="admin-link">📊 Dashboard</a>';
                echo '<a href="' . admin_url('admin.php?page=env-chat-live-chat') . '" class="admin-link">💬 Live Chat</a>';
                echo '<a href="' . admin_url('admin.php?page=env-chat-tickets') . '" class="admin-link">🎫 Tickets</a>';
                echo '<a href="' . admin_url('admin.php?page=env-chat-faq') . '" class="admin-link">❓ FAQ</a>';
                echo '<a href="' . admin_url('admin.php?page=env-chat-analytics') . '" class="admin-link">📈 Analytics</a>';
                echo '<a href="' . admin_url('admin.php?page=env-chat-settings') . '" class="admin-link">⚙️ Settings</a>';
            } else {
                echo '<p>Admin links not available - WordPress not loaded</p>';
            }
            ?>
        </div>
    </div>

    <div class="test-grid">
        <div class="feature-card">
            <h3>🚀 Core Features</h3>
            <ul class="feature-list">
                <li>Real-time live chat system</li>
                <li>Multi-operator support</li>
                <li>Business hours management</li>
                <li>File attachment support</li>
                <li>Chat rating & feedback</li>
                <li>Visitor tracking</li>
                <li>Mobile responsive design</li>
            </ul>
        </div>

        <div class="feature-card">
            <h3>🤖 AI & Automation</h3>
            <ul class="feature-list">
                <li>Intelligent chatbot responses</li>
                <li>Environmental services knowledge</li>
                <li>Auto-escalation to humans</li>
                <li>Pattern matching system</li>
                <li>Training & learning capabilities</li>
                <li>Confidence scoring</li>
                <li>Response analytics</li>
            </ul>
        </div>

        <div class="feature-card">
            <h3>🎫 Support System</h3>
            <ul class="feature-list">
                <li>Complete ticket lifecycle</li>
                <li>Auto-generated ticket numbers</li>
                <li>Priority & status management</li>
                <li>Agent assignment system</li>
                <li>Email notifications</li>
                <li>File attachments</li>
                <li>SLA tracking</li>
            </ul>
        </div>

        <div class="feature-card">
            <h3>📚 Knowledge Base</h3>
            <ul class="feature-list">
                <li>Advanced FAQ search</li>
                <li>Category management</li>
                <li>Rating system</li>
                <li>View count tracking</li>
                <li>Import/Export functionality</li>
                <li>Tag system</li>
                <li>Popular content identification</li>
            </ul>
        </div>

        <div class="feature-card">
            <h3>📊 Analytics & Reporting</h3>
            <ul class="feature-list">
                <li>Real-time dashboard metrics</li>
                <li>Performance analytics</li>
                <li>Agent workload tracking</li>
                <li>Satisfaction reporting</li>
                <li>Response time analysis</li>
                <li>Trend identification</li>
                <li>Export capabilities</li>
            </ul>
        </div>

        <div class="feature-card">
            <h3>🔧 Technical Features</h3>
            <ul class="feature-list">
                <li>REST API endpoints</li>
                <li>Mobile app integration</li>
                <li>Security & validation</li>
                <li>Caching optimization</li>
                <li>Database optimization</li>
                <li>Error handling</li>
                <li>Multi-language support</li>
            </ul>
        </div>
    </div>

    <div class="test-section">
        <h2>🔍 Testing Checklist</h2>
        <div class="alert alert-info">
            <h4>Manual Testing Steps:</h4>
            <ol>
                <li><strong>Chat Widget:</strong> Click the chat button and test the pre-chat form</li>
                <li><strong>Live Chat:</strong> Send messages and verify real-time functionality</li>
                <li><strong>File Upload:</strong> Test file attachment capability</li>
                <li><strong>Support Form:</strong> Submit a support ticket</li>
                <li><strong>FAQ Search:</strong> Search for environmental topics</li>
                <li><strong>Admin Dashboard:</strong> Access and navigate admin interfaces</li>
                <li><strong>Operator Interface:</strong> Test live chat management</li>
                <li><strong>Analytics:</strong> Review dashboard metrics and reports</li>
                <li><strong>Settings:</strong> Configure chat widget and business hours</li>
                <li><strong>Mobile Testing:</strong> Test on mobile devices</li>
            </ol>
        </div>
    </div>

    <footer style="text-align: center; margin-top: 40px; padding: 20px; border-top: 2px solid #4CAF50;">
        <p><strong>Environmental Platform</strong> - Phase 56: Live Chat & Customer Support</p>
        <p>🌱 Comprehensive customer support system for environmental services</p>
        <p><em>Test completed on <?php echo date('Y-m-d H:i:s'); ?></em></p>
    </footer>

    <?php
    // Include WordPress scripts and styles if available
    if (function_exists('wp_head')) {
        wp_head();
    }
    if (function_exists('wp_footer')) {
        wp_footer();
    }
    ?>
</body>
</html>
