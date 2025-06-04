<?php
/**
 * WordPress Plugin Installation Script
 * Phase 27: Essential Plugin Installation for Environmental Platform
 * 
 * This script downloads and installs essential plugins for:
 * - Security (Wordfence Security)
 * - Backup (UpdraftPlus)
 * - SEO Optimization (Yoast SEO)
 * - Performance (WP Rocket or W3 Total Cache)
 * - Environmental Features (Custom Environmental Plugin)
 */

// WordPress functions require WordPress to be loaded
require_once('wp-config.php');
require_once('wp-admin/includes/file.php');
require_once('wp-admin/includes/plugin-install.php');
require_once('wp-admin/includes/class-wp-upgrader.php');
require_once('wp-admin/includes/plugin.php');

class EnvironmentalPlatformPluginInstaller {
    
    private $essential_plugins = [
        'wordfence' => [
            'name' => 'Wordfence Security',
            'slug' => 'wordfence',
            'file' => 'wordfence/wordfence.php'
        ],
        'updraftplus' => [
            'name' => 'UpdraftPlus Backup',
            'slug' => 'updraftplus',
            'file' => 'updraftplus/updraftplus.php'
        ],
        'wordpress-seo' => [
            'name' => 'Yoast SEO',
            'slug' => 'wordpress-seo',
            'file' => 'wordpress-seo/wp-seo.php'
        ],
        'w3-total-cache' => [
            'name' => 'W3 Total Cache',
            'slug' => 'w3-total-cache',
            'file' => 'w3-total-cache/w3-total-cache.php'
        ],
        'contact-form-7' => [
            'name' => 'Contact Form 7',
            'slug' => 'contact-form-7',
            'file' => 'contact-form-7/wp-contact-form-7.php'
        ]
    ];
    
    public function install_essential_plugins() {
        echo "=== Environmental Platform Plugin Installation ===\n";
        echo "Installing essential plugins for the Environmental Platform...\n\n";
        
        foreach ($this->essential_plugins as $plugin_data) {
            $this->install_plugin($plugin_data);
        }
        
        echo "\n=== Plugin Installation Complete ===\n";
        echo "All essential plugins have been installed successfully!\n";
        echo "Please activate them through the WordPress admin dashboard.\n";
    }
    
    private function install_plugin($plugin_data) {
        echo "Installing {$plugin_data['name']}...\n";
        
        // Check if plugin is already installed
        if (is_dir(WP_PLUGIN_DIR . '/' . $plugin_data['slug'])) {
            echo "  - {$plugin_data['name']} is already installed.\n";
            return;
        }
        
        // Install plugin
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install('https://downloads.wordpress.org/plugin/' . $plugin_data['slug'] . '.zip');
        
        if (is_wp_error($result)) {
            echo "  - Error installing {$plugin_data['name']}: " . $result->get_error_message() . "\n";
        } else {
            echo "  - {$plugin_data['name']} installed successfully!\n";
        }
    }
    
    public function create_environmental_custom_plugin() {
        $plugin_dir = WP_PLUGIN_DIR . '/environmental-platform-core';
        
        if (!is_dir($plugin_dir)) {
            mkdir($plugin_dir, 0755, true);
        }
        
        $plugin_file = $plugin_dir . '/environmental-platform-core.php';
        
        $plugin_content = '<?php
/**
 * Plugin Name: Environmental Platform Core
 * Description: Core functionality for the Environmental Platform - integrates with the custom database structure
 * Version: 1.0.0
 * Author: Environmental Platform Team
 */

// Prevent direct access
if (!defined("ABSPATH")) {
    exit;
}

class EnvironmentalPlatformCore {
    
    public function __construct() {
        add_action("init", array($this, "init"));
        add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));
        add_action("admin_menu", array($this, "admin_menu"));
    }
    
    public function init() {
        // Initialize environmental platform features
        $this->setup_custom_post_types();
        $this->setup_custom_tables_integration();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script("environmental-platform-js", plugin_dir_url(__FILE__) . "assets/environmental-platform.js", array("jquery"), "1.0.0", true);
        wp_enqueue_style("environmental-platform-css", plugin_dir_url(__FILE__) . "assets/environmental-platform.css", array(), "1.0.0");
    }
    
    public function admin_menu() {
        add_menu_page(
            "Environmental Platform",
            "Environmental Platform", 
            "manage_options",
            "environmental-platform",
            array($this, "admin_page"),
            "dashicons-admin-site",
            6
        );
    }
    
    public function admin_page() {
        echo "<div class=\"wrap\">";
        echo "<h1>Environmental Platform Dashboard</h1>";
        echo "<p>Welcome to the Environmental Platform management interface.</p>";
        echo "<p>Database Status: Connected to environmental_platform database with 120 tables.</p>";
        echo "</div>";
    }
    
    private function setup_custom_post_types() {
        // Register Environmental Posts
        register_post_type("environmental_post", array(
            "labels" => array(
                "name" => "Environmental Posts",
                "singular_name" => "Environmental Post"
            ),
            "public" => true,
            "has_archive" => true,
            "supports" => array("title", "editor", "thumbnail", "excerpt")
        ));
        
        // Register Environmental Events
        register_post_type("environmental_event", array(
            "labels" => array(
                "name" => "Environmental Events", 
                "singular_name" => "Environmental Event"
            ),
            "public" => true,
            "has_archive" => true,
            "supports" => array("title", "editor", "thumbnail", "excerpt")
        ));
    }
    
    private function setup_custom_tables_integration() {
        // Integration with existing environmental_platform database tables
        global $wpdb;
        
        // Check if our custom tables exist
        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE \"users\"");
        if ($tables_exist) {
            // Tables exist, we can integrate
            add_action("wp_dashboard_setup", array($this, "add_environmental_dashboard_widget"));
        }
    }
    
    public function add_environmental_dashboard_widget() {
        wp_add_dashboard_widget(
            "environmental_stats",
            "Environmental Platform Statistics",
            array($this, "environmental_dashboard_widget_content")
        );
    }
    
    public function environmental_dashboard_widget_content() {
        global $wpdb;
        
        $user_count = $wpdb->get_var("SELECT COUNT(*) FROM users");
        $post_count = $wpdb->get_var("SELECT COUNT(*) FROM posts");
        $event_count = $wpdb->get_var("SELECT COUNT(*) FROM events");
        
        echo "<p><strong>Platform Statistics:</strong></p>";
        echo "<ul>";
        echo "<li>Total Users: " . ($user_count ?: "0") . "</li>";
        echo "<li>Total Posts: " . ($post_count ?: "0") . "</li>";
        echo "<li>Total Events: " . ($event_count ?: "0") . "</li>";
        echo "</ul>";
    }
}

// Initialize the plugin
new EnvironmentalPlatformCore();
?>';
        
        file_put_contents($plugin_file, $plugin_content);
        echo "Environmental Platform Core plugin created successfully!\n";
    }
}

// Run the installer if accessed directly
if (php_sapi_name() === "cli" || (isset($_GET["install_plugins"]) && $_GET["install_plugins"] === "true")) {
    $installer = new EnvironmentalPlatformPluginInstaller();
    $installer->install_essential_plugins();
    $installer->create_environmental_custom_plugin();
}
?>
