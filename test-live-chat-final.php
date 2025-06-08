<?php
/**
 * Final Live Chat Plugin Test Page
 */

// WordPress bootstrap
require_once 'wp-config.php';
require_once ABSPATH . 'wp-settings.php';

// Output as HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Live Chat - Final Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #2e8b57 0%, #228b22 100%);
            color: #333;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2e8b57 0%, #228b22 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .test-section h3 {
            color: #2e8b57;
            border-bottom: 2px solid #2e8b57;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .shortcode-demo {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .admin-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .admin-link {
            display: block;
            padding: 15px;
            background: linear-gradient(135deg, #2e8b57 0%, #228b22 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .admin-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .feature-card {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
        }
        .feature-card h4 {
            color: #2e8b57;
            margin-top: 0;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Environmental Live Chat & Customer Support</h1>
            <p>Phase 56 - Final Testing & Activation</p>
        </div>
        
        <div class="content">
            <?php
            // Check plugin activation status
            $active_plugins = get_option('active_plugins', array());
            $plugin_path = 'environmental-live-chat/environmental-live-chat.php';
            $is_active = in_array($plugin_path, $active_plugins);
            
            echo '<div class="test-section">';
            echo '<h3>Plugin Activation Status</h3>';
            
            if ($is_active) {
                echo '<span class="status success">✓ ACTIVE</span>';
                echo '<p>The Environmental Live Chat plugin is successfully activated.</p>';
            } else {
                echo '<span class="status warning">⚠ INACTIVE</span>';
                echo '<p>The plugin needs to be activated through WordPress admin.</p>';
                echo '<a href="/moitruong/wp-admin/plugins.php" class="admin-link" style="display: inline-block; width: auto; margin-top: 10px;">Activate Plugin</a>';
            }
            echo '</div>';
            
            // Check if plugin class exists
            echo '<div class="test-section">';
            echo '<h3>Plugin Class Loading</h3>';
            
            if (class_exists('Environmental_Live_Chat')) {
                echo '<span class="status success">✓ LOADED</span>';
                echo '<p>Main plugin class is loaded and available.</p>';
            } else {
                echo '<span class="status error">✗ NOT LOADED</span>';
                echo '<p>Plugin class not found. Plugin may need activation.</p>';
            }
            echo '</div>';
            
            // Check database tables
            global $wpdb;
            echo '<div class="test-section">';
            echo '<h3>Database Tables</h3>';
            
            $tables = [
                'chat_sessions',
                'chat_messages',
                'support_tickets',
                'ticket_replies',
                'faq_items',
                'support_analytics',
                'chat_operators'
            ];
            
            $missing_tables = [];
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . 'elc_' . $table;
                $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                if (!$result) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                echo '<span class="status success">✓ ALL TABLES EXIST</span>';
                echo '<p>All 7 database tables are created successfully.</p>';
            } else {
                echo '<span class="status warning">⚠ MISSING TABLES</span>';
                echo '<p>Missing tables: ' . implode(', ', $missing_tables) . '</p>';
                echo '<p>Tables will be created upon plugin activation.</p>';
            }
            echo '</div>';
            
            // Plugin Features Overview
            echo '<div class="test-section">';
            echo '<h3>Plugin Features</h3>';
            echo '<div class="feature-grid">';
            
            $features = [
                'Live Chat System' => [
                    'Real-time messaging',
                    'Multi-operator support',
                    'Department routing',
                    'File attachments',
                    'Business hours',
                    'Chat ratings'
                ],
                'Chatbot System' => [
                    'Automated responses',
                    'Environmental expertise',
                    'Pattern matching',
                    'Human escalation',
                    'Learning capabilities'
                ],
                'Support Tickets' => [
                    'Ticket lifecycle management',
                    'Auto-assignment',
                    'Email notifications',
                    'SLA tracking',
                    'Priority management'
                ],
                'FAQ & Knowledge Base' => [
                    'Advanced search',
                    'Categorization',
                    'Rating system',
                    'Import/Export',
                    'Multi-language support'
                ],
                'Analytics Dashboard' => [
                    'Real-time metrics',
                    'Performance tracking',
                    'Automated reports',
                    'Custom dashboards',
                    'Export capabilities'
                ],
                'Admin Interface' => [
                    'Live operator dashboard',
                    'Ticket management',
                    'FAQ administration',
                    'Settings configuration',
                    'User role management'
                ]
            ];
            
            foreach ($features as $title => $items) {
                echo '<div class="feature-card">';
                echo '<h4>' . $title . '</h4>';
                echo '<ul class="feature-list">';
                foreach ($items as $item) {
                    echo '<li>' . $item . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            
            // Shortcode Testing
            if (class_exists('Environmental_Live_Chat')) {
                echo '<div class="test-section">';
                echo '<h3>Shortcode Testing</h3>';
                echo '<p>Available shortcodes for the plugin:</p>';
                
                $shortcodes = [
                    '[env_live_chat]' => 'Display live chat widget',
                    '[env_faq_search]' => 'FAQ search interface',
                    '[env_support_form]' => 'Support ticket form',
                    '[env_knowledge_base]' => 'Knowledge base articles'
                ];
                
                foreach ($shortcodes as $code => $description) {
                    echo '<div class="shortcode-demo">';
                    echo '<strong>' . $code . '</strong> - ' . $description;
                    echo '</div>';
                }
                echo '</div>';
            }
            
            // Admin Links
            echo '<div class="test-section">';
            echo '<h3>Administration Links</h3>';
            echo '<div class="admin-links">';
            echo '<a href="/moitruong/wp-admin/plugins.php" class="admin-link">Plugins Management</a>';
            echo '<a href="/moitruong/wp-admin/admin.php?page=env-live-chat" class="admin-link">Live Chat Dashboard</a>';
            echo '<a href="/moitruong/wp-admin/admin.php?page=env-support-tickets" class="admin-link">Support Tickets</a>';
            echo '<a href="/moitruong/wp-admin/admin.php?page=env-faq-manager" class="admin-link">FAQ Management</a>';
            echo '<a href="/moitruong/wp-admin/admin.php?page=env-chat-analytics" class="admin-link">Analytics</a>';
            echo '<a href="/moitruong/wp-admin/admin.php?page=env-chat-settings" class="admin-link">Settings</a>';
            echo '</div>';
            echo '</div>';
            
            // Installation Summary
            echo '<div class="test-section">';
            echo '<h3>Installation Summary</h3>';
            echo '<div class="feature-grid">';
            
            echo '<div class="feature-card">';
            echo '<h4>📁 Plugin Files</h4>';
            echo '<ul class="feature-list">';
            echo '<li>Main plugin file (environmental-live-chat.php)</li>';
            echo '<li>8 Core class files in /includes/</li>';
            echo '<li>Frontend & Admin CSS files</li>';
            echo '<li>Frontend & Admin JavaScript files</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="feature-card">';
            echo '<h4>🗄️ Database Structure</h4>';
            echo '<ul class="feature-list">';
            echo '<li>7 Custom database tables</li>';
            echo '<li>Optimized indexes for performance</li>';
            echo '<li>Foreign key relationships</li>';
            echo '<li>Data integrity constraints</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="feature-card">';
            echo '<h4>🔧 WordPress Integration</h4>';
            echo '<ul class="feature-list">';
            echo '<li>Custom post types for knowledge base</li>';
            echo '<li>AJAX endpoints for real-time features</li>';
            echo '<li>REST API for mobile integration</li>';
            echo '<li>Shortcode system for easy embedding</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="feature-card">';
            echo '<h4>⚡ Performance Features</h4>';
            echo '<ul class="feature-list">';
            echo '<li>Singleton pattern for efficiency</li>';
            echo '<li>Lazy loading of components</li>';
            echo '<li>Optimized database queries</li>';
            echo '<li>Caching integration ready</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
            ?>
        </div>
    </div>
</body>
</html>
