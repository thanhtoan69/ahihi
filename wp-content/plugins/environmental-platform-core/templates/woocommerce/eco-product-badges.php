<?php
/**
 * Eco-Friendly Product Badge Template
 * 
 * Template for displaying eco-friendly badges on products
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

global $product;

$is_eco_friendly = get_post_meta($product->get_id(), '_is_eco_friendly', true);
$sustainability_score = get_post_meta($product->get_id(), '_sustainability_score', true);
$eco_rating = get_post_meta($product->get_id(), '_eco_rating', true);
$carbon_footprint = get_post_meta($product->get_id(), '_carbon_footprint_kg', true);
$is_recyclable = get_post_meta($product->get_id(), '_is_recyclable', true);
$is_biodegradable = get_post_meta($product->get_id(), '_is_biodegradable', true);

// Only show if product has eco-friendly features
if ($is_eco_friendly !== 'yes' && empty($sustainability_score) && empty($eco_rating)) {
    return;
}
?>

<div class="ep-eco-badges-container">
    <?php if ($is_eco_friendly === 'yes'): ?>
        <div class="ep-eco-badge ep-eco-friendly">
            <span class="badge-icon">🌱</span>
            <span class="badge-text"><?php _e('Eco-Friendly', 'environmental-platform-core'); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($eco_rating)): ?>
        <div class="ep-eco-badge ep-eco-rating rating-<?php echo esc_attr(strtolower(str_replace('+', 'plus', $eco_rating))); ?>">
            <span class="badge-icon">⭐</span>
            <span class="badge-text"><?php echo esc_html($eco_rating); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($sustainability_score)): ?>
        <div class="ep-eco-badge ep-sustainability-score">
            <span class="badge-icon">📊</span>
            <span class="badge-text"><?php echo esc_html($sustainability_score); ?>/100</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($carbon_footprint)): ?>
        <div class="ep-eco-badge ep-carbon-footprint">
            <span class="badge-icon">🌿</span>
            <span class="badge-text"><?php echo number_format($carbon_footprint, 1); ?> kg CO₂</span>
        </div>
    <?php endif; ?>

    <div class="ep-eco-features">
        <?php if ($is_recyclable === 'yes'): ?>
            <span class="ep-feature-badge recyclable" title="<?php _e('Recyclable', 'environmental-platform-core'); ?>">♻️</span>
        <?php endif; ?>

        <?php if ($is_biodegradable === 'yes'): ?>
            <span class="ep-feature-badge biodegradable" title="<?php _e('Biodegradable', 'environmental-platform-core'); ?>">🍃</span>
        <?php endif; ?>
    </div>
</div>

<style>
.ep-eco-badges-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 10px 0;
    align-items: center;
}

.ep-eco-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ep-eco-badge .badge-icon {
    margin-right: 3px;
    font-size: 10px;
}

.ep-eco-friendly {
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    color: white;
}

.ep-eco-rating {
    background: linear-gradient(135deg, #2196F3, #42A5F5);
    color: white;
}

.ep-eco-rating.rating-aplus {
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
}

.ep-eco-rating.rating-a {
    background: linear-gradient(135deg, #8BC34A, #9CCC65);
}

.ep-eco-rating.rating-b {
    background: linear-gradient(135deg, #CDDC39, #D4E157);
    color: #333;
}

.ep-eco-rating.rating-c {
    background: linear-gradient(135deg, #FFC107, #FFCA28);
    color: #333;
}

.ep-eco-rating.rating-d {
    background: linear-gradient(135deg, #FF9800, #FFB74D);
    color: white;
}

.ep-eco-rating.rating-e {
    background: linear-gradient(135deg, #F44336, #E57373);
    color: white;
}

.ep-sustainability-score {
    background: linear-gradient(135deg, #009688, #26A69A);
    color: white;
}

.ep-carbon-footprint {
    background: linear-gradient(135deg, #795548, #8D6E63);
    color: white;
}

.ep-eco-features {
    display: flex;
    gap: 3px;
}

.ep-feature-badge {
    font-size: 14px;
    cursor: help;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.ep-feature-badge:hover {
    opacity: 1;
}

/* Responsive design */
@media (max-width: 768px) {
    .ep-eco-badges-container {
        justify-content: center;
    }
    
    .ep-eco-badge {
        font-size: 11px;
        padding: 3px 6px;
    }
}
</style>
