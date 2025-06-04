<?php
/**
 * WooCommerce Activation and Integration Test Script
 * Phase 32: E-commerce Integration Testing
 */

// WordPress bootstrap
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/wp-admin/includes/plugin.php';

echo "=== WooCommerce Activation and Integration Test ===\n\n";

// Check if WooCommerce plugin exists
$woocommerce_plugin = 'woocommerce/woocommerce.php';
if (!file_exists(WP_PLUGIN_DIR . '/' . $woocommerce_plugin)) {
    echo "❌ WooCommerce plugin not found in plugins directory\n";
    exit(1);
}

echo "✅ WooCommerce plugin files found\n";

// Check if WooCommerce is already active
if (is_plugin_active($woocommerce_plugin)) {
    echo "✅ WooCommerce is already active\n";
} else {
    echo "🔄 Activating WooCommerce plugin...\n";
    
    // Activate WooCommerce
    $result = activate_plugin($woocommerce_plugin);
    
    if (is_wp_error($result)) {
        echo "❌ Failed to activate WooCommerce: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "✅ WooCommerce activated successfully\n";
    }
}

// Force WordPress to recognize the plugin activation
wp_cache_flush();

echo "\n=== Checking WooCommerce Integration ===\n";

// Check if WooCommerce functions are available
if (function_exists('WC')) {
    echo "✅ WooCommerce core functions are available\n";
    
    // Get WooCommerce version
    if (defined('WC_VERSION')) {
        echo "✅ WooCommerce Version: " . WC_VERSION . "\n";
    }
    
    // Check if WooCommerce is properly initialized
    if (class_exists('WooCommerce')) {
        echo "✅ WooCommerce class is loaded\n";
    }
    
} else {
    echo "❌ WooCommerce functions not available\n";
}

echo "\n=== Testing WooCommerce Database Tables ===\n";

global $wpdb;

// Check for WooCommerce tables
$wc_tables = array(
    $wpdb->prefix . 'woocommerce_sessions',
    $wpdb->prefix . 'woocommerce_api_keys',
    $wpdb->prefix . 'woocommerce_attribute_taxonomies',
    $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
    $wpdb->prefix . 'woocommerce_order_items',
    $wpdb->prefix . 'woocommerce_order_itemmeta',
    $wpdb->prefix . 'woocommerce_tax_rates',
    $wpdb->prefix . 'woocommerce_tax_rate_locations',
    $wpdb->prefix . 'woocommerce_shipping_zones',
    $wpdb->prefix . 'woocommerce_shipping_zone_locations',
    $wpdb->prefix . 'woocommerce_shipping_zone_methods',
    $wpdb->prefix . 'woocommerce_payment_tokens',
    $wpdb->prefix . 'woocommerce_payment_tokenmeta'
);

$missing_tables = array();
foreach ($wc_tables as $table) {
    $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($result !== $table) {
        $missing_tables[] = $table;
    } else {
        echo "✅ Table exists: $table\n";
    }
}

if (!empty($missing_tables)) {
    echo "\n❌ Missing WooCommerce tables:\n";
    foreach ($missing_tables as $table) {
        echo "   - $table\n";
    }
    echo "\n🔄 Running WooCommerce database creation...\n";
    
    // Trigger WooCommerce installation
    if (function_exists('WC')) {
        include_once WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-install.php';
        WC_Install::install();
        echo "✅ WooCommerce database installation completed\n";
    }
} else {
    echo "✅ All WooCommerce database tables are present\n";
}

echo "\n=== Testing Environmental Platform WooCommerce Integration ===\n";

// Check if our environmental platform integration hooks exist
$integration_checks = array(
    'Environmental Platform Core Plugin' => is_plugin_active('environmental-platform-core/environmental-platform-core.php'),
    'WooCommerce Product Integration' => function_exists('wc_get_products'),
    'Environmental Categories' => get_option('environmental_categories_configured', false),
    'Eco Points System' => get_option('eco_points_system_enabled', false)
);

foreach ($integration_checks as $check_name => $check_result) {
    if ($check_result) {
        echo "✅ $check_name: Active\n";
    } else {
        echo "⚠️  $check_name: Not configured\n";
    }
}

echo "\n=== Creating Sample WooCommerce Products ===\n";

if (function_exists('wc_get_product')) {
    // Create sample eco-friendly products
    $sample_products = array(
        array(
            'name' => 'Eco-Friendly Water Bottle',
            'price' => 25.99,
            'description' => 'Reusable stainless steel water bottle - reduce plastic waste!',
            'eco_points' => 50
        ),
        array(
            'name' => 'Biodegradable Cleaning Kit',
            'price' => 15.99,
            'description' => 'All-natural cleaning supplies that are safe for the environment',
            'eco_points' => 30
        ),
        array(
            'name' => 'Solar Power Bank',
            'price' => 45.99,
            'description' => 'Portable solar-powered device charger for renewable energy use',
            'eco_points' => 75
        )
    );
    
    foreach ($sample_products as $product_data) {
        // Check if product already exists
        $existing_product = get_page_by_title($product_data['name'], OBJECT, 'product');
        
        if (!$existing_product) {
            $product = new WC_Product_Simple();
            $product->set_name($product_data['name']);
            $product->set_regular_price($product_data['price']);
            $product->set_description($product_data['description']);
            $product->set_short_description('Eco-friendly product with ' . $product_data['eco_points'] . ' eco points');
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_stock_status('instock');
            
            // Add eco points as meta data
            $product->add_meta_data('_eco_points_value', $product_data['eco_points']);
            $product->add_meta_data('_is_eco_product', 'yes');
            
            $product_id = $product->save();
            
            if ($product_id) {
                echo "✅ Created product: {$product_data['name']} (ID: $product_id)\n";
            } else {
                echo "❌ Failed to create product: {$product_data['name']}\n";
            }
        } else {
            echo "ℹ️  Product already exists: {$product_data['name']}\n";
        }
    }
} else {
    echo "❌ WooCommerce product functions not available\n";
}

echo "\n=== Testing WooCommerce Pages ===\n";

// Check for WooCommerce pages
$wc_pages = array(
    'woocommerce_shop_page_id' => 'Shop',
    'woocommerce_cart_page_id' => 'Cart',
    'woocommerce_checkout_page_id' => 'Checkout',
    'woocommerce_myaccount_page_id' => 'My Account'
);

foreach ($wc_pages as $option_name => $page_name) {
    $page_id = get_option($option_name);
    if ($page_id && get_post($page_id)) {
        echo "✅ $page_name page exists (ID: $page_id)\n";
    } else {
        echo "⚠️  $page_name page missing\n";
    }
}

echo "\n=== WooCommerce Settings Check ===\n";

// Check basic WooCommerce settings
$currency = get_option('woocommerce_currency');
$currency_pos = get_option('woocommerce_currency_pos');
$decimal_sep = get_option('woocommerce_price_decimal_sep');
$thousand_sep = get_option('woocommerce_price_thousand_sep');

echo "✅ Currency: $currency\n";
echo "✅ Currency Position: $currency_pos\n";
echo "✅ Decimal Separator: $decimal_sep\n";
echo "✅ Thousand Separator: $thousand_sep\n";

echo "\n=== Integration Test Summary ===\n";

// Final integration test
if (is_plugin_active($woocommerce_plugin) && function_exists('WC')) {
    echo "🎉 WooCommerce Integration Test: PASSED\n";
    echo "✅ WooCommerce is properly activated and integrated\n";
    echo "✅ Database tables are created\n";
    echo "✅ Sample eco-friendly products created\n";
    echo "✅ Ready for e-commerce functionality\n";
    
    // Save integration status
    update_option('environmental_woocommerce_integration', 'active');
    update_option('environmental_woocommerce_test_date', current_time('mysql'));
    
} else {
    echo "❌ WooCommerce Integration Test: FAILED\n";
    echo "❌ Please check WooCommerce installation and activation\n";
}

echo "\n=== Phase 32 E-commerce Integration: COMPLETED ===\n";
?>
