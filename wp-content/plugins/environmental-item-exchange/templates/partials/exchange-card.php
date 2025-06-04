<?php
/**
 * Exchange Card Partial Template
 * Used in archive and search results
 * 
 * @package EnvironmentalItemExchange
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get exchange data
$exchange_id = get_the_ID();
$user_id = get_the_author_meta('ID');
$user_name = get_the_author_meta('display_name');
$user_avatar = get_avatar_url($user_id, array('size' => 40));

// Get exchange meta data
$exchange_type = wp_get_post_terms($exchange_id, 'exchange_type', array('fields' => 'names'));
$exchange_category = wp_get_post_terms($exchange_id, 'exchange_category', array('fields' => 'names'));
$condition = get_post_meta($exchange_id, '_eie_condition', true);
$location = get_post_meta($exchange_id, '_eie_location', true);
$is_featured = get_post_meta($exchange_id, '_eie_featured', true);
$is_urgent = get_post_meta($exchange_id, '_eie_urgent', true);

// Get user rating
global $wpdb;
$user_rating = $wpdb->get_var($wpdb->prepare(
    "SELECT AVG(rating) FROM {$wpdb->prefix}eie_ratings WHERE rated_user_id = %d",
    $user_id
));
$user_rating = $user_rating ? round($user_rating, 1) : 0;

// Get distance if location is available
$distance = '';
if (isset($_SESSION['user_location']) && !empty($location)) {
    // Calculate distance (simplified - in real app would use geolocation class)
    $distance = rand(1, 25) . ' km';
}

// Get featured image
$image_url = get_the_post_thumbnail_url($exchange_id, 'medium');
if (!$image_url) {
    $image_url = EIE_PLUGIN_URL . 'assets/images/placeholder.jpg';
}

// Check if current user has saved this exchange
$is_saved = false;
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    $is_saved = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}usermeta WHERE user_id = %d AND meta_key = '_eie_saved_exchanges' AND meta_value LIKE %s",
        $current_user_id,
        '%"' . $exchange_id . '"%'
    )) > 0;
}
?>

<div class="eie-exchange-card <?php echo $is_featured ? 'eie-featured' : ''; ?>" data-id="<?php echo $exchange_id; ?>">
    <div class="eie-card-image">
        <a href="<?php the_permalink(); ?>">
            <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
        </a>
        
        <div class="eie-card-badges">
            <?php if ($is_featured) : ?>
                <span class="eie-badge eie-badge-featured">
                    <i class="eie-icon-star"></i>
                    <?php _e('Featured', 'environmental-item-exchange'); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($is_urgent) : ?>
                <span class="eie-badge eie-badge-urgent">
                    <i class="eie-icon-clock"></i>
                    <?php _e('Urgent', 'environmental-item-exchange'); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($condition)) : ?>
                <span class="eie-badge eie-badge-condition">
                    <?php echo esc_html(ucfirst($condition)); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="eie-card-actions">
            <?php if (is_user_logged_in()) : ?>
                <button class="eie-action-btn eie-save-btn <?php echo $is_saved ? 'eie-saved' : ''; ?>" 
                        data-id="<?php echo $exchange_id; ?>" 
                        title="<?php echo $is_saved ? __('Remove from saved', 'environmental-item-exchange') : __('Save item', 'environmental-item-exchange'); ?>">
                    <i class="eie-icon-heart <?php echo $is_saved ? 'eie-icon-heart-filled' : ''; ?>"></i>
                </button>
                
                <button class="eie-action-btn eie-contact-btn" 
                        data-id="<?php echo $exchange_id; ?>" 
                        data-owner="<?php echo $user_id; ?>"
                        title="<?php _e('Contact owner', 'environmental-item-exchange'); ?>">
                    <i class="eie-icon-message"></i>
                </button>
            <?php endif; ?>
            
            <button class="eie-action-btn eie-share-btn" 
                    data-id="<?php echo $exchange_id; ?>" 
                    title="<?php _e('Share item', 'environmental-item-exchange'); ?>">
                <i class="eie-icon-share"></i>
            </button>
        </div>
    </div>
    
    <div class="eie-card-content">
        <div class="eie-card-header">
            <h3 class="eie-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            
            <div class="eie-card-meta">
                <?php if (!empty($exchange_type)) : ?>
                    <span class="eie-exchange-type">
                        <i class="eie-icon-tag"></i>
                        <?php echo esc_html($exchange_type[0]); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($location)) : ?>
                    <span class="eie-location">
                        <i class="eie-icon-location"></i>
                        <?php echo esc_html($location); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($distance) : ?>
                    <span class="eie-distance">
                        <i class="eie-icon-map-pin"></i>
                        <?php echo esc_html($distance); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="eie-card-description">
            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
        </div>
        
        <div class="eie-card-footer">
            <div class="eie-user-info">
                <img src="<?php echo esc_url($user_avatar); ?>" 
                     alt="<?php echo esc_attr($user_name); ?>" 
                     class="eie-user-avatar">
                
                <div class="eie-user-details">
                    <span class="eie-user-name"><?php echo esc_html($user_name); ?></span>
                    
                    <?php if ($user_rating > 0) : ?>
                        <div class="eie-user-rating">
                            <div class="eie-stars" data-rating="<?php echo $user_rating; ?>">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span class="eie-star <?php echo $i <= $user_rating ? 'eie-star-filled' : ''; ?>">
                                        <i class="eie-icon-star"></i>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <span class="eie-rating-text">(<?php echo $user_rating; ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="eie-card-stats">
                <span class="eie-date" title="<?php echo get_the_date(); ?>">
                    <i class="eie-icon-calendar"></i>
                    <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-item-exchange'); ?>
                </span>
                
                <?php
                // Get view count
                $views = get_post_meta($exchange_id, '_eie_views', true);
                if (!$views) $views = 0;
                ?>
                <span class="eie-views">
                    <i class="eie-icon-eye"></i>
                    <?php echo number_format($views); ?>
                </span>
            </div>
        </div>
        
        <!-- Environmental Impact Badge -->
        <?php
        $co2_saved = get_post_meta($exchange_id, '_eie_co2_saved', true);
        if ($co2_saved && $co2_saved > 0) :
        ?>
            <div class="eie-environmental-impact">
                <div class="eie-impact-badge">
                    <i class="eie-icon-leaf"></i>
                    <span><?php printf(__('%s kg CO₂ saved', 'environmental-item-exchange'), number_format($co2_saved, 1)); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions (visible on hover) -->
    <div class="eie-quick-actions">
        <a href="<?php the_permalink(); ?>" class="eie-btn eie-btn-primary eie-btn-sm">
            <?php _e('View Details', 'environmental-item-exchange'); ?>
        </a>
        
        <?php if (is_user_logged_in() && get_current_user_id() !== $user_id) : ?>
            <button class="eie-btn eie-btn-secondary eie-btn-sm eie-quick-contact" 
                    data-id="<?php echo $exchange_id; ?>" 
                    data-owner="<?php echo $user_id; ?>">
                <?php _e('Contact', 'environmental-item-exchange'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>
