<?php
/**
 * Simple Plugin Load Test
 * 
 * Tests if the main plugin file can be loaded without errors
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Plugin Load Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Plugin Load Test</h1>
    
    <?php
    echo '<div class="info">Starting plugin load test...</div>';
    
    $plugin_file = WP_PLUGIN_DIR . '/environmental-item-exchange/environmental-item-exchange.php';
    
    if (file_exists($plugin_file)) {
        echo '<div class="success">✓ Plugin file exists</div>';
        
        // Try to include the plugin file
        ob_start();
        $error = null;
        
        try {
            include_once($plugin_file);
            echo '<div class="success">✓ Plugin file loaded without fatal errors</div>';
            
            // Check if main class exists
            if (class_exists('EnvironmentalItemExchange')) {
                echo '<div class="success">✓ Main plugin class exists</div>';
                
                // Try to get instance
                try {
                    $instance = EnvironmentalItemExchange::get_instance();
                    echo '<div class="success">✓ Plugin instance created successfully</div>';
                } catch (Exception $e) {
                    echo '<div class="error">✗ Failed to create plugin instance: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="error">✗ Main plugin class not found</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Plugin load failed: ' . $e->getMessage() . '</div>';
        } catch (ParseError $e) {
            echo '<div class="error">✗ PHP Parse Error: ' . $e->getMessage() . '</div>';
        } catch (Error $e) {
            echo '<div class="error">✗ PHP Fatal Error: ' . $e->getMessage() . '</div>';
        }
        
        $output = ob_get_clean();
        if (!empty($output)) {
            echo '<div class="info">Plugin output:</div>';
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
        }
        
    } else {
        echo '<div class="error">✗ Plugin file not found: ' . $plugin_file . '</div>';
    }
    
    // Check individual class files
    echo '<h2>Individual Class File Tests</h2>';
    
    $class_files = array(
        'class-database-setup.php' => 'EIE_Database_Setup',
        'class-frontend-templates.php' => 'Environmental_Item_Exchange_Frontend_Templates',
        'class-database-manager.php' => 'EIE_Database_Manager',
        'class-geolocation.php' => 'EIE_Geolocation'
    );
    
    foreach ($class_files as $file => $class_name) {
        $file_path = WP_PLUGIN_DIR . '/environmental-item-exchange/includes/' . $file;
        
        if (file_exists($file_path)) {
            echo "<div class='info'>Testing $file...</div>";
            
            try {
                require_once($file_path);
                
                if (class_exists($class_name)) {
                    echo "<div class='success'>✓ $class_name class loaded successfully</div>";
                } else {
                    echo "<div class='error'>✗ $class_name class not found after loading</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Failed to load $file: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>✗ File not found: $file</div>";
        }
    }
    
    // Check WordPress integration
    echo '<h2>WordPress Integration Test</h2>';
    
    // Check if post type exists
    if (post_type_exists('item_exchange')) {
        echo '<div class="success">✓ item_exchange post type exists</div>';
        
        $post_type_object = get_post_type_object('item_exchange');
        echo '<div class="info">Post type labels: ' . $post_type_object->labels->name . '</div>';
    } else {
        echo '<div class="error">✗ item_exchange post type not registered</div>';
    }
    
    // Check active plugins
    $active_plugins = get_option('active_plugins', array());
    $plugin_active = in_array('environmental-item-exchange/environmental-item-exchange.php', $active_plugins);
    
    if ($plugin_active) {
        echo '<div class="success">✓ Plugin is active in WordPress</div>';
    } else {
        echo '<div class="error">✗ Plugin is not active in WordPress</div>';
        echo '<div class="info">Try activating through: <a href="wp-admin/plugins.php">Plugins Page</a></div>';
    }
    
    echo '<div class="info">Test completed at: ' . date('Y-m-d H:i:s') . '</div>';
    ?>
    
</body>
</html>
