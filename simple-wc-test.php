<?php
/**
 * Simple WooCommerce Test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Simple WooCommerce Test ===\n";

// WordPress bootstrap
if (file_exists(__DIR__ . '/wp-config.php')) {
    require_once __DIR__ . '/wp-config.php';
    require_once __DIR__ . '/wp-load.php';
    echo "✅ WordPress loaded\n";
} else {
    echo "❌ WordPress config not found\n";
    exit(1);
}

// Test WooCommerce
if (function_exists('WC')) {
    echo "✅ WooCommerce function available\n";
    echo "✅ WooCommerce Version: " . WC_VERSION . "\n";
} else {
    echo "❌ WooCommerce not available\n";
}

// Test products
$products = wc_get_products(array('limit' => 3));
echo "✅ Found " . count($products) . " products\n";

foreach ($products as $product) {
    echo "   - " . $product->get_name() . " (" . wc_price($product->get_price()) . ")\n";
}

echo "=== Test Complete ===\n";
?>
