<?php
/**
 * WooCommerce Integration Admin Page
 * 
 * Phase 32: E-commerce Integration (WooCommerce)
 * 
 * @package EnvironmentalPlatform
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if (isset($_POST['ep_wc_save_settings']) && wp_verify_nonce($_POST['ep_wc_nonce'], 'ep_wc_settings')) {
    $settings = array(
        'eco_scoring_enabled' => isset($_POST['eco_scoring_enabled']) ? 1 : 0,
        'carbon_footprint_tracking' => isset($_POST['carbon_footprint_tracking']) ? 1 : 0,
        'eco_points_system' => isset($_POST['eco_points_system']) ? 1 : 0,
        'green_shipping_enabled' => isset($_POST['green_shipping_enabled']) ? 1 : 0,
        'carbon_offset_checkout' => isset($_POST['carbon_offset_checkout']) ? 1 : 0,
        'eco_packaging_options' => isset($_POST['eco_packaging_options']) ? 1 : 0,
        'sustainability_emails' => isset($_POST['sustainability_emails']) ? 1 : 0,
        'eco_certification_display' => isset($_POST['eco_certification_display']) ? 1 : 0
    );
    
    update_option('ep_woocommerce_settings', $settings);
    echo '<div class="notice notice-success"><p>' . __('WooCommerce settings saved successfully!', 'environmental-platform-core') . '</p></div>';
}

// Get current settings
$current_settings = get_option('ep_woocommerce_settings', array(
    'eco_scoring_enabled' => 1,
    'carbon_footprint_tracking' => 1,
    'eco_points_system' => 1,
    'green_shipping_enabled' => 1,
    'carbon_offset_checkout' => 1,
    'eco_packaging_options' => 1,
    'sustainability_emails' => 1,
    'eco_certification_display' => 1
));

// Get WooCommerce status
$woocommerce_active = class_exists('WooCommerce');
$wc_version = $woocommerce_active ? WC()->version : 'Not installed';

// Get product statistics
global $wpdb;
$total_products = $woocommerce_active ? wp_count_posts('product')->publish : 0;
$eco_products = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID) 
    FROM {$wpdb->posts} p 
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'product' 
    AND p.post_status = 'publish'
    AND pm.meta_key = '_eco_score'
    AND pm.meta_value > 0
");

$orders_with_eco_data = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID) 
    FROM {$wpdb->posts} p 
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'shop_order' 
    AND pm.meta_key = '_order_environmental_impact'
");
?>

<div class="wrap">
    <h1><?php _e('WooCommerce Integration Settings', 'environmental-platform-core'); ?></h1>
    
    <!-- WooCommerce Status -->
    <div class="card">
        <h2><?php _e('WooCommerce Status', 'environmental-platform-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('WooCommerce Status', 'environmental-platform-core'); ?></th>
                <td>
                    <span class="dashicons <?php echo $woocommerce_active ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                    <?php echo $woocommerce_active ? __('Active', 'environmental-platform-core') : __('Inactive', 'environmental-platform-core'); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('WooCommerce Version', 'environmental-platform-core'); ?></th>
                <td><?php echo esc_html($wc_version); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Total Products', 'environmental-platform-core'); ?></th>
                <td><?php echo number_format($total_products); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Eco-Friendly Products', 'environmental-platform-core'); ?></th>
                <td><?php echo number_format($eco_products ?: 0); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Orders with Environmental Data', 'environmental-platform-core'); ?></th>
                <td><?php echo number_format($orders_with_eco_data ?: 0); ?></td>
            </tr>
        </table>
    </div>

    <?php if (!$woocommerce_active): ?>
        <div class="notice notice-warning">
            <p><?php _e('WooCommerce is not active. Please install and activate WooCommerce to use the e-commerce integration features.', 'environmental-platform-core'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <form method="post" action="">
        <?php wp_nonce_field('ep_wc_settings', 'ep_wc_nonce'); ?>
        
        <div class="card">
            <h2><?php _e('Eco-Friendly Features', 'environmental-platform-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Eco Scoring System', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="eco_scoring_enabled" value="1" <?php checked($current_settings['eco_scoring_enabled'], 1); ?> />
                            <?php _e('Show sustainability scores on products', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Displays eco-friendly ratings and sustainability scores on product pages and shop listings.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Carbon Footprint Tracking', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="carbon_footprint_tracking" value="1" <?php checked($current_settings['carbon_footprint_tracking'], 1); ?> />
                            <?php _e('Track and display carbon footprint data', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Shows carbon footprint information for products and calculates total environmental impact in cart/checkout.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Eco Points Reward System', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="eco_points_system" value="1" <?php checked($current_settings['eco_points_system'], 1); ?> />
                            <?php _e('Award eco points for sustainable purchases', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Customers earn eco points when purchasing eco-friendly products, integrated with the platform\'s point system.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Eco Certification Display', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="eco_certification_display" value="1" <?php checked($current_settings['eco_certification_display'], 1); ?> />
                            <?php _e('Show eco-certifications and badges', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Displays environmental certifications, organic labels, and eco-friendly badges on products.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2><?php _e('Checkout & Shipping Features', 'environmental-platform-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Green Shipping Options', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="green_shipping_enabled" value="1" <?php checked($current_settings['green_shipping_enabled'], 1); ?> />
                            <?php _e('Offer eco-friendly delivery options', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Provides carbon-neutral shipping, electric vehicle delivery, and consolidated shipping options.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Carbon Offset at Checkout', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="carbon_offset_checkout" value="1" <?php checked($current_settings['carbon_offset_checkout'], 1); ?> />
                            <?php _e('Allow customers to purchase carbon offsets', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Customers can optionally purchase carbon offsets to neutralize their order\'s environmental impact.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Eco Packaging Preferences', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="eco_packaging_options" value="1" <?php checked($current_settings['eco_packaging_options'], 1); ?> />
                            <?php _e('Provide sustainable packaging choices', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Customers can choose biodegradable, recyclable, or minimal packaging options during checkout.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2><?php _e('Communication & Reporting', 'environmental-platform-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Sustainability Email Information', 'environmental-platform-core'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sustainability_emails" value="1" <?php checked($current_settings['sustainability_emails'], 1); ?> />
                            <?php _e('Include environmental impact in order emails', 'environmental-platform-core'); ?>
                        </label>
                        <p class="description"><?php _e('Order confirmation and shipping emails will include environmental impact data and sustainability tips.', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Save WooCommerce Settings', 'environmental-platform-core'), 'primary', 'ep_wc_save_settings'); ?>
    </form>

    <!-- Quick Actions -->
    <div class="card">
        <h2><?php _e('Quick Actions', 'environmental-platform-core'); ?></h2>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" class="button">
                <?php _e('Manage Products', 'environmental-platform-core'); ?>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="button">
                <?php _e('View Orders', 'environmental-platform-core'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=wc-reports'); ?>" class="button">
                <?php _e('WooCommerce Reports', 'environmental-platform-core'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ep-analytics'); ?>" class="button">
                <?php _e('Environmental Analytics', 'environmental-platform-core'); ?>
            </a>
        </p>
    </div>

    <!-- Integration Status -->
    <div class="card">
        <h2><?php _e('Integration Features Status', 'environmental-platform-core'); ?></h2>
        <ul class="ep-integration-status">
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Product eco-fields integration', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Shop loop eco badges', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Cart environmental impact display', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Checkout eco-friendly options', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Order environmental tracking', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Database synchronization', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Eco points reward system', 'environmental-platform-core'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Custom email templates', 'environmental-platform-core'); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.ep-integration-status {
    list-style: none;
    padding: 0;
}

.ep-integration-status li {
    margin: 8px 0;
    display: flex;
    align-items: center;
}

.ep-integration-status .dashicons {
    margin-right: 8px;
    color: #46b450;
}

.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
}

.card h2 {
    margin-top: 0;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}
</style>
