<?php
/**
 * Environmental Impact Email Template
 * 
 * Template for displaying environmental impact information in WooCommerce emails
 * 
 * Phase 32: E-commerce Integration (WooCommerce)
 * 
 * @package EnvironmentalPlatform
 * @subpackage Templates
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get order environmental data
$environmental_impact = get_post_meta($order->get_id(), '_order_environmental_impact', true);
$eco_points_earned = get_post_meta($order->get_id(), '_eco_points_earned', true);
$carbon_offset_purchased = get_post_meta($order->get_id(), '_carbon_offset_purchased', true);
$eco_packaging = get_post_meta($order->get_id(), '_eco_packaging_preference', true);
$green_shipping = get_post_meta($order->get_id(), '_green_shipping_selected', true);

if (!$environmental_impact && !$eco_points_earned) {
    return;
}
?>

<div style="margin: 20px 0; padding: 20px; border: 2px solid #4CAF50; border-radius: 8px; background-color: #f8fff8;">
    <h3 style="color: #2E7D32; margin-top: 0; display: flex; align-items: center;">
        🌱 <?php _e('Your Environmental Impact', 'environmental-platform-core'); ?>
    </h3>
    
    <?php if ($environmental_impact): ?>
        <?php
        $impact_data = maybe_unserialize($environmental_impact);
        $total_carbon = isset($impact_data['total_carbon_kg']) ? $impact_data['total_carbon_kg'] : 0;
        $total_score = isset($impact_data['total_eco_score']) ? $impact_data['total_eco_score'] : 0;
        ?>
        
        <div style="margin: 15px 0;">
            <h4 style="color: #388E3C; margin: 10px 0 5px 0;">
                📊 <?php _e('Order Environmental Metrics', 'environmental-platform-core'); ?>
            </h4>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <strong><?php _e('Total Carbon Footprint:', 'environmental-platform-core'); ?></strong>
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #d84315;">
                        <?php echo number_format($total_carbon, 2); ?> kg CO₂
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <strong><?php _e('Average Eco Score:', 'environmental-platform-core'); ?></strong>
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #2E7D32;">
                        <?php echo number_format($total_score, 1); ?>/10 ⭐
                    </td>
                </tr>
                
                <?php if ($carbon_offset_purchased): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <strong><?php _e('Carbon Offset Purchased:', 'environmental-platform-core'); ?></strong>
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #2E7D32;">
                        ✅ <?php echo number_format($total_carbon, 2); ?> kg CO₂ <?php _e('neutralized', 'environmental-platform-core'); ?>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if ($eco_packaging): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <strong><?php _e('Packaging Choice:', 'environmental-platform-core'); ?></strong>
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #2E7D32;">
                        ♻️ <?php echo esc_html(ucfirst($eco_packaging)); ?> <?php _e('packaging', 'environmental-platform-core'); ?>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if ($green_shipping): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <strong><?php _e('Shipping Method:', 'environmental-platform-core'); ?></strong>
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #2E7D32;">
                        🚚 <?php _e('Eco-friendly delivery selected', 'environmental-platform-core'); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if ($eco_points_earned): ?>
        <div style="margin: 15px 0; padding: 12px; background-color: #e8f5e8; border-radius: 5px;">
            <h4 style="color: #2E7D32; margin: 0 0 8px 0; display: flex; align-items: center;">
                🎉 <?php _e('Eco Points Earned', 'environmental-platform-core'); ?>
            </h4>
            <p style="margin: 0; font-size: 16px;">
                <?php printf(
                    __('Congratulations! You\'ve earned <strong>%d eco points</strong> for choosing sustainable products.', 'environmental-platform-core'),
                    intval($eco_points_earned)
                ); ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div style="margin: 15px 0; padding: 12px; background-color: #f0f9ff; border-radius: 5px; border-left: 4px solid #2196F3;">
        <h4 style="color: #1976D2; margin: 0 0 8px 0;">
            💡 <?php _e('Sustainability Tips', 'environmental-platform-core'); ?>
        </h4>
        <ul style="margin: 0; padding-left: 20px; color: #333;">
            <li><?php _e('Consider reusing or recycling the packaging materials', 'environmental-platform-core'); ?></li>
            <li><?php _e('Share your eco-friendly choices to inspire others', 'environmental-platform-core'); ?></li>
            <?php if (!$carbon_offset_purchased): ?>
            <li><?php _e('Consider purchasing carbon offsets for future orders', 'environmental-platform-core'); ?></li>
            <?php endif; ?>
            <li><?php _e('Check out more sustainable products in our eco-friendly collection', 'environmental-platform-core'); ?></li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 15px 0;">
        <a href="<?php echo esc_url(home_url('/shop/?filter_eco=1')); ?>" 
           style="display: inline-block; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
            🌿 <?php _e('Shop More Eco-Friendly Products', 'environmental-platform-core'); ?>
        </a>
    </div>
    
    <p style="font-size: 12px; color: #666; margin: 10px 0 0 0; text-align: center;">
        <?php _e('Thank you for making environmentally conscious choices! Together, we can make a difference.', 'environmental-platform-core'); ?>
    </p>
</div>
