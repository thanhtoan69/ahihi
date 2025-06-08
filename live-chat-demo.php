<?php
/**
 * Environmental Live Chat - Frontend Demo & Test Page
 */

// WordPress bootstrap
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

// Get plugin instance
$plugin_active = false;
$chat_instance = null;

if (class_exists('Environmental_Live_Chat')) {
    $chat_instance = Environmental_Live_Chat::get_instance();
    $plugin_active = true;
}

get_header();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Live Chat - Demo & Test</title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #2e8b57 0%, #228b22 100%);
            min-height: 100vh;
        }
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .demo-header {
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .demo-header h1 {
            color: #2e8b57;
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .demo-section {
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .demo-section h3 {
            color: #2e8b57;
            margin-top: 0;
            border-bottom: 2px solid #2e8b57;
            padding-bottom: 10px;
        }
        .shortcode-demo {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .shortcode-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .shortcode-code {
            background: #343a40;
            color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .status-indicator {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .test-button {
            background: #2e8b57;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
            transition: background 0.3s;
        }
        .test-button:hover {
            background: #228b22;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .feature-list li:before {
            content: "✅";
            margin-right: 10px;
        }
        .admin-panel {
            background: linear-gradient(135deg, #2e8b57 0%, #228b22 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        .admin-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .admin-link {
            display: block;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: background 0.3s;
        }
        .admin-link:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>🌱 Environmental Live Chat & Customer Support</h1>
            <p>Complete customer support solution for environmental services</p>
            
            <?php if ($plugin_active): ?>
                <span class="status-indicator status-active">✅ Plugin Active</span>
            <?php else: ?>
                <span class="status-indicator status-inactive">❌ Plugin Inactive</span>
                <p><a href="activate-live-chat-direct.php">Activate Plugin</a></p>
            <?php endif; ?>
        </div>

        <div class="demo-grid">
            <!-- Live Chat Widget Demo -->
            <div class="demo-section">
                <h3>🔵 Live Chat Widget</h3>
                <p>Real-time customer support chat with file attachment support.</p>
                
                <div class="shortcode-demo">
                    <div class="shortcode-title">Basic Chat Widget:</div>
                    <div class="shortcode-code">[env_live_chat]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_live_chat]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display widget</em></p>
                    <?php endif; ?>
                </div>

                <div class="shortcode-demo">
                    <div class="shortcode-title">Chat with Department:</div>
                    <div class="shortcode-code">[env_live_chat department="environmental"]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_live_chat department="environmental"]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display widget</em></p>
                    <?php endif; ?>
                </div>

                <ul class="feature-list">
                    <li>Real-time messaging</li>
                    <li>File attachment support</li>
                    <li>Multi-operator system</li>
                    <li>Department routing</li>
                    <li>Business hours integration</li>
                    <li>Chat history & ratings</li>
                </ul>
            </div>

            <!-- FAQ Search Demo -->
            <div class="demo-section">
                <h3>❓ FAQ & Knowledge Base</h3>
                <p>Searchable FAQ system with categorization and ratings.</p>
                
                <div class="shortcode-demo">
                    <div class="shortcode-title">FAQ Search Widget:</div>
                    <div class="shortcode-code">[env_faq_search]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_faq_search]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display widget</em></p>
                    <?php endif; ?>
                </div>

                <div class="shortcode-demo">
                    <div class="shortcode-title">FAQ by Category:</div>
                    <div class="shortcode-code">[env_faq_search category="environmental-services"]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_faq_search category="environmental-services"]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display widget</em></p>
                    <?php endif; ?>
                </div>

                <ul class="feature-list">
                    <li>Advanced search functionality</li>
                    <li>Category-based organization</li>
                    <li>User rating system</li>
                    <li>Import/Export capabilities</li>
                    <li>Multi-language support</li>
                </ul>
            </div>

            <!-- Support Ticket Demo -->
            <div class="demo-section">
                <h3>🎫 Support Ticket System</h3>
                <p>Complete ticket management with automated workflows.</p>
                
                <div class="shortcode-demo">
                    <div class="shortcode-title">Support Form:</div>
                    <div class="shortcode-code">[env_support_form]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_support_form]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display form</em></p>
                    <?php endif; ?>
                </div>

                <ul class="feature-list">
                    <li>Automated ticket creation</li>
                    <li>Priority-based assignment</li>
                    <li>Email notifications</li>
                    <li>SLA tracking</li>
                    <li>Status management</li>
                    <li>Agent assignment</li>
                </ul>
            </div>

            <!-- Knowledge Base Demo -->
            <div class="demo-section">
                <h3>📚 Knowledge Base</h3>
                <p>Comprehensive knowledge base with article management.</p>
                
                <div class="shortcode-demo">
                    <div class="shortcode-title">Knowledge Base:</div>
                    <div class="shortcode-code">[env_knowledge_base]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_knowledge_base]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display knowledge base</em></p>
                    <?php endif; ?>
                </div>

                <div class="shortcode-demo">
                    <div class="shortcode-title">Recent Articles:</div>
                    <div class="shortcode-code">[env_knowledge_base limit="5" order="recent"]</div>
                    <?php if ($plugin_active): ?>
                        <?php echo do_shortcode('[env_knowledge_base limit="5" order="recent"]'); ?>
                    <?php else: ?>
                        <p><em>Plugin must be active to display articles</em></p>
                    <?php endif; ?>
                </div>

                <ul class="feature-list">
                    <li>Article management system</li>
                    <li>Category organization</li>
                    <li>Search functionality</li>
                    <li>View tracking</li>
                    <li>User feedback</li>
                </ul>
            </div>

            <!-- Chatbot Demo -->
            <div class="demo-section">
                <h3>🤖 AI Chatbot</h3>
                <p>Intelligent chatbot for automated customer support.</p>
                
                <div class="shortcode-demo">
                    <div class="shortcode-title">Features:</div>
                    <ul class="feature-list">
                        <li>Environmental service expertise</li>
                        <li>Pattern matching responses</li>
                        <li>Confidence scoring</li>
                        <li>Human escalation</li>
                        <li>Learning capabilities</li>
                        <li>Multi-language support</li>
                    </ul>
                </div>

                <button class="test-button" onclick="testChatbot()">Test Chatbot</button>
            </div>

            <!-- Analytics Demo -->
            <div class="demo-section">
                <h3>📊 Analytics Dashboard</h3>
                <p>Comprehensive analytics and reporting system.</p>
                
                <ul class="feature-list">
                    <li>Real-time chat metrics</li>
                    <li>Ticket performance tracking</li>
                    <li>Customer satisfaction scores</li>
                    <li>Agent performance reports</li>
                    <li>Custom dashboard widgets</li>
                    <li>Automated report generation</li>
                </ul>
                
                <button class="test-button" onclick="viewAnalytics()">View Analytics</button>
            </div>
        </div>

        <?php if ($plugin_active): ?>
        <div class="admin-panel">
            <h2>🔧 Administration Panel</h2>
            <p>Access the complete administrative interface for managing your customer support system.</p>
            
            <div class="admin-links">
                <a href="/moitruong/wp-admin/admin.php?page=env-live-chat" class="admin-link">
                    💬 Live Chat Dashboard
                </a>
                <a href="/moitruong/wp-admin/admin.php?page=env-support-tickets" class="admin-link">
                    🎫 Manage Tickets
                </a>
                <a href="/moitruong/wp-admin/admin.php?page=env-faq-manager" class="admin-link">
                    ❓ FAQ Management
                </a>
                <a href="/moitruong/wp-admin/admin.php?page=env-chat-analytics" class="admin-link">
                    📊 Analytics
                </a>
                <a href="/moitruong/wp-admin/admin.php?page=env-chat-settings" class="admin-link">
                    ⚙️ Settings
                </a>
                <a href="/moitruong/wp-admin/plugins.php" class="admin-link">
                    🔌 All Plugins
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function testChatbot() {
        alert('Chatbot test functionality would be implemented here. The chatbot responds to environmental service questions automatically.');
    }

    function viewAnalytics() {
        window.open('/moitruong/wp-admin/admin.php?page=env-chat-analytics', '_blank');
    }

    // Initialize chat widget if plugin is active
    <?php if ($plugin_active): ?>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Environmental Live Chat plugin is active and ready!');
        
        // Test AJAX connectivity
        if (typeof ajaxurl !== 'undefined') {
            console.log('WordPress AJAX is available:', ajaxurl);
        }
    });
    <?php endif; ?>
    </script>

    <?php wp_footer(); ?>
</body>
</html>
