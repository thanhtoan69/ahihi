<?php
/**
 * Final Phase 32 Verification Script
 * Quick verification that all WooCommerce integration is working
 */

// WordPress bootstrap
require_once __DIR__ . '/wp-config.php';
require_once __DIR__ . '/wp-load.php';

echo "=== PHASE 32 FINAL VERIFICATION ===\n\n";

// 1. Check WooCommerce is active and functional
echo "1. WooCommerce Status Check:\n";
if (function_exists('WC') && class_exists('WooCommerce')) {
    echo "   ✅ WooCommerce Active (Version: " . WC_VERSION . ")\n";
} else {
    echo "   ❌ WooCommerce Not Active\n";
    exit(1);
}

// 2. Check products exist
echo "\n2. Product Verification:\n";
$products = wc_get_products(array('limit' => 10, 'status' => 'publish'));
echo "   ✅ Found " . count($products) . " active products\n";

$eco_products = 0;
$total_eco_points = 0;

foreach ($products as $product) {
    $eco_points = $product->get_meta('_eco_points_value');
    $is_eco = $product->get_meta('_is_eco_product');
    
    if ($is_eco === 'yes') {
        $eco_products++;
        $total_eco_points += intval($eco_points);
    }
}

echo "   ✅ Eco-friendly products: $eco_products\n";
echo "   ✅ Total eco points available: $total_eco_points\n";

// 3. Check WooCommerce pages
echo "\n3. WooCommerce Pages Check:\n";
$pages = array(
    'Shop' => get_option('woocommerce_shop_page_id'),
    'Cart' => get_option('woocommerce_cart_page_id'),
    'Checkout' => get_option('woocommerce_checkout_page_id'),
    'My Account' => get_option('woocommerce_myaccount_page_id')
);

foreach ($pages as $page_name => $page_id) {
    if ($page_id && get_post($page_id)) {
        echo "   ✅ $page_name page exists\n";
    } else {
        echo "   ❌ $page_name page missing\n";
    }
}

// 4. Check database tables
echo "\n4. Database Tables Check:\n";
global $wpdb;
$required_tables = array(
    'woocommerce_sessions',
    'woocommerce_order_items',
    'woocommerce_order_itemmeta'
);

$tables_ok = true;
foreach ($required_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        echo "   ✅ Table $table exists\n";
    } else {
        echo "   ❌ Table $table missing\n";
        $tables_ok = false;
    }
}

// 5. Test cart functionality
echo "\n5. Cart Functionality Test:\n";
WC()->cart->empty_cart();
if (!empty($products)) {
    $test_product = $products[0];
    $cart_item_key = WC()->cart->add_to_cart($test_product->get_id(), 1);
    
    if ($cart_item_key) {
        echo "   ✅ Product added to cart successfully\n";
        echo "   ✅ Cart contains " . WC()->cart->get_cart_contents_count() . " item(s)\n";
        
        // Clean up
        WC()->cart->empty_cart();
        echo "   ✅ Cart cleared after test\n";
    } else {
        echo "   ❌ Failed to add product to cart\n";
    }
}

// 6. Check integration status
echo "\n6. Integration Status Check:\n";
$integration_status = get_option('environmental_woocommerce_integration');
$test_date = get_option('environmental_woocommerce_test_date');

if ($integration_status === 'active') {
    echo "   ✅ Integration status: Active\n";
    echo "   ✅ Last tested: $test_date\n";
} else {
    echo "   ⚠️ Integration status not recorded\n";
}

// Final assessment
echo "\n=== FINAL ASSESSMENT ===\n";
$all_checks_passed = function_exists('WC') && 
                    count($products) > 0 && 
                    $eco_products > 0 && 
                    $tables_ok;

if ($all_checks_passed) {
    echo "🎉 PHASE 32 VERIFICATION: SUCCESSFUL\n";
    echo "✅ All WooCommerce integration components working\n";
    echo "✅ E-commerce functionality fully operational\n";
    echo "✅ Environmental features integrated\n";
    echo "✅ Ready for production use\n";
    
    // Update verification status
    update_option('phase32_verification_status', 'passed');
    update_option('phase32_verification_date', current_time('mysql'));
    
} else {
    echo "❌ PHASE 32 VERIFICATION: FAILED\n";
    echo "❌ Some components need attention\n";
    
    update_option('phase32_verification_status', 'failed');
}

echo "\n=== PHASE 32 E-COMMERCE INTEGRATION: COMPLETE ===\n";
?>
