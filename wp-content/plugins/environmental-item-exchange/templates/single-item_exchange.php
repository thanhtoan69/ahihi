<?php
/**
 * Single Exchange Template
 * 
 * Template for displaying individual exchange items
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-single-exchange-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <div class="ep-exchange-wrapper">
            
            <!-- Exchange Header -->
            <div class="ep-exchange-header">
                <div class="ep-breadcrumb">
                    <a href="<?php echo get_post_type_archive_link('item_exchange'); ?>"><?php _e('All Exchanges', 'environmental-item-exchange'); ?></a>
                    <span class="ep-breadcrumb-separator">></span>
                    <span class="ep-current-page"><?php the_title(); ?></span>
                </div>
                
                <h1 class="ep-exchange-title"><?php the_title(); ?></h1>
                
                <div class="ep-exchange-meta">
                    <?php
                    $exchange_type = get_post_meta(get_the_ID(), '_exchange_type', true);
                    $item_condition = get_post_meta(get_the_ID(), '_item_condition', true);
                    $location = get_post_meta(get_the_ID(), '_exchange_location', true);
                    $posted_date = get_the_date();
                    $author_id = get_the_author_meta('ID');
                    $author_name = get_the_author();
                    $author_rating = get_user_meta($author_id, '_exchange_rating', true) ?: 0;
                    ?>
                    
                    <div class="ep-meta-item">
                        <span class="ep-meta-label"><?php _e('Type:', 'environmental-item-exchange'); ?></span>
                        <span class="ep-exchange-type ep-type-<?php echo esc_attr($exchange_type); ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $exchange_type))); ?>
                        </span>
                    </div>
                    
                    <div class="ep-meta-item">
                        <span class="ep-meta-label"><?php _e('Condition:', 'environmental-item-exchange'); ?></span>
                        <span class="ep-condition"><?php echo esc_html(ucfirst($item_condition)); ?></span>
                    </div>
                    
                    <div class="ep-meta-item">
                        <span class="ep-meta-label"><?php _e('Location:', 'environmental-item-exchange'); ?></span>
                        <span class="ep-location"><?php echo esc_html($location); ?></span>
                    </div>
                    
                    <div class="ep-meta-item">
                        <span class="ep-meta-label"><?php _e('Posted:', 'environmental-item-exchange'); ?></span>
                        <span class="ep-date"><?php echo esc_html($posted_date); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Exchange Content -->
            <div class="ep-exchange-content">
                
                <!-- Left Column: Images & Details -->
                <div class="ep-exchange-main">
                    
                    <!-- Image Gallery -->
                    <div class="ep-exchange-gallery">
                        <?php
                        $images = get_post_meta(get_the_ID(), '_exchange_images', true);
                        if (!empty($images) && is_array($images)) :
                        ?>
                            <div class="ep-main-image">
                                <img src="<?php echo esc_url(wp_get_attachment_url($images[0])); ?>" alt="<?php the_title(); ?>" id="ep-main-image">
                            </div>
                            
                            <?php if (count($images) > 1) : ?>
                            <div class="ep-thumbnail-gallery">
                                <?php foreach ($images as $index => $image_id) : ?>
                                    <img src="<?php echo esc_url(wp_get_attachment_url($image_id)); ?>" 
                                         alt="<?php the_title(); ?>" 
                                         class="ep-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                         data-main-src="<?php echo esc_url(wp_get_attachment_url($image_id)); ?>">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php elseif (has_post_thumbnail()) : ?>
                            <div class="ep-main-image">
                                <?php the_post_thumbnail('large'); ?>
                            </div>
                        <?php else : ?>
                            <div class="ep-no-image">
                                <div class="ep-no-image-placeholder">
                                    <span class="ep-no-image-icon">📦</span>
                                    <span class="ep-no-image-text"><?php _e('No image available', 'environmental-item-exchange'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <div class="ep-exchange-description">
                        <h3><?php _e('Description', 'environmental-item-exchange'); ?></h3>
                        <div class="ep-description-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                    
                    <!-- Additional Details -->
                    <div class="ep-exchange-details">
                        <h3><?php _e('Details', 'environmental-item-exchange'); ?></h3>
                        
                        <div class="ep-details-grid">
                            <?php
                            $estimated_value = get_post_meta(get_the_ID(), '_estimated_value', true);
                            $carbon_footprint = get_post_meta(get_the_ID(), '_carbon_footprint_saved', true);
                            $pickup_available = get_post_meta(get_the_ID(), '_pickup_available', true);
                            $delivery_available = get_post_meta(get_the_ID(), '_delivery_available', true);
                            ?>
                            
                            <?php if ($estimated_value) : ?>
                            <div class="ep-detail-item">
                                <span class="ep-detail-label"><?php _e('Estimated Value:', 'environmental-item-exchange'); ?></span>
                                <span class="ep-detail-value">$<?php echo esc_html(number_format($estimated_value, 2)); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($carbon_footprint) : ?>
                            <div class="ep-detail-item">
                                <span class="ep-detail-label"><?php _e('CO₂ Saved:', 'environmental-item-exchange'); ?></span>
                                <span class="ep-detail-value"><?php echo esc_html(number_format($carbon_footprint, 1)); ?> kg</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="ep-detail-item">
                                <span class="ep-detail-label"><?php _e('Pickup Available:', 'environmental-item-exchange'); ?></span>
                                <span class="ep-detail-value">
                                    <?php echo $pickup_available ? __('Yes', 'environmental-item-exchange') : __('No', 'environmental-item-exchange'); ?>
                                </span>
                            </div>
                            
                            <div class="ep-detail-item">
                                <span class="ep-detail-label"><?php _e('Delivery Available:', 'environmental-item-exchange'); ?></span>
                                <span class="ep-detail-value">
                                    <?php echo $delivery_available ? __('Yes', 'environmental-item-exchange') : __('No', 'environmental-item-exchange'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categories & Tags -->
                    <div class="ep-exchange-taxonomy">
                        <?php
                        $categories = get_the_terms(get_the_ID(), 'exchange_type');
                        if ($categories && !is_wp_error($categories)) :
                        ?>
                        <div class="ep-categories">
                            <h4><?php _e('Categories:', 'environmental-item-exchange'); ?></h4>
                            <div class="ep-category-list">
                                <?php foreach ($categories as $category) : ?>
                                    <a href="<?php echo get_term_link($category); ?>" class="ep-category-tag">
                                        <?php echo esc_html($category->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Owner & Actions -->
                <div class="ep-exchange-sidebar">
                    
                    <!-- Owner Information -->
                    <div class="ep-owner-card">
                        <h3><?php _e('Exchange Owner', 'environmental-item-exchange'); ?></h3>
                        
                        <div class="ep-owner-info">
                            <div class="ep-owner-avatar">
                                <?php echo get_avatar($author_id, 60); ?>
                            </div>
                            
                            <div class="ep-owner-details">
                                <h4 class="ep-owner-name"><?php echo esc_html($author_name); ?></h4>
                                
                                <div class="ep-owner-rating">
                                    <div class="ep-stars">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <span class="ep-star <?php echo $i <= $author_rating ? 'active' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="ep-rating-text">(<?php echo number_format($author_rating, 1); ?>)</span>
                                </div>
                                
                                <div class="ep-owner-stats">
                                    <?php
                                    $user_exchanges = wp_count_posts('item_exchange');
                                    $user_exchanges = isset($user_exchanges->publish) ? $user_exchanges->publish : 0;
                                    ?>
                                    <span class="ep-stat-item"><?php printf(__('%d exchanges', 'environmental-item-exchange'), $user_exchanges); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="ep-exchange-actions">
                        <?php if (is_user_logged_in() && get_current_user_id() != $author_id) : ?>
                            
                            <button class="ep-btn ep-btn-primary ep-contact-owner" 
                                    data-exchange-id="<?php the_ID(); ?>" 
                                    data-owner-name="<?php echo esc_attr($author_name); ?>">
                                <span class="ep-btn-icon">💬</span>
                                <?php _e('Contact Owner', 'environmental-item-exchange'); ?>
                            </button>
                            
                            <button class="ep-btn ep-btn-outline ep-save-exchange" 
                                    data-exchange-id="<?php the_ID(); ?>">
                                <span class="ep-btn-icon">❤️</span>
                                <span class="ep-btn-text"><?php _e('Save', 'environmental-item-exchange'); ?></span>
                            </button>
                            
                            <a href="<?php echo get_post_type_archive_link('item_exchange'); ?>?similar=<?php the_ID(); ?>" 
                               class="ep-btn ep-btn-secondary">
                                <span class="ep-btn-icon">🔍</span>
                                <?php _e('Find Similar', 'environmental-item-exchange'); ?>
                            </a>
                            
                        <?php elseif (is_user_logged_in() && get_current_user_id() == $author_id) : ?>
                            
                            <a href="<?php echo get_edit_post_link(); ?>" class="ep-btn ep-btn-primary">
                                <span class="ep-btn-icon">✏️</span>
                                <?php _e('Edit Exchange', 'environmental-item-exchange'); ?>
                            </a>
                            
                        <?php else : ?>
                            
                            <div class="ep-login-prompt">
                                <p><?php _e('Please log in to contact the owner or save this exchange.', 'environmental-item-exchange'); ?></p>
                                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="ep-btn ep-btn-primary">
                                    <?php _e('Log In', 'environmental-item-exchange'); ?>
                                </a>
                            </div>
                            
                        <?php endif; ?>
                    </div>
                    
                    <!-- Environmental Impact -->
                    <div class="ep-environmental-impact">
                        <h3><?php _e('Environmental Impact', 'environmental-item-exchange'); ?></h3>
                        
                        <div class="ep-impact-stats">
                            <?php if ($carbon_footprint) : ?>
                            <div class="ep-impact-item">
                                <div class="ep-impact-icon">🌱</div>
                                <div class="ep-impact-details">
                                    <span class="ep-impact-number"><?php echo number_format($carbon_footprint, 1); ?> kg</span>
                                    <span class="ep-impact-label"><?php _e('CO₂ Saved', 'environmental-item-exchange'); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="ep-impact-item">
                                <div class="ep-impact-icon">♻️</div>
                                <div class="ep-impact-details">
                                    <span class="ep-impact-number">1</span>
                                    <span class="ep-impact-label"><?php _e('Item Diverted', 'environmental-item-exchange'); ?></span>
                                </div>
                            </div>
                            
                            <div class="ep-impact-message">
                                <p><?php _e('By participating in this exchange, you\'re helping reduce waste and promote circular economy!', 'environmental-item-exchange'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Share Options -->
                    <div class="ep-share-exchange">
                        <h3><?php _e('Share This Exchange', 'environmental-item-exchange'); ?></h3>
                        
                        <div class="ep-share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                               target="_blank" class="ep-share-btn ep-share-facebook">
                                Facebook
                            </a>
                            
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                               target="_blank" class="ep-share-btn ep-share-twitter">
                                Twitter
                            </a>
                            
                            <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" 
                               class="ep-share-btn ep-share-email">
                                Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Exchanges -->
            <div class="ep-related-exchanges">
                <h3><?php _e('Related Exchanges', 'environmental-item-exchange'); ?></h3>
                
                <div class="ep-related-grid">
                    <?php
                    $related_args = array(
                        'post_type' => 'item_exchange',
                        'post_status' => 'publish',
                        'posts_per_page' => 4,
                        'post__not_in' => array(get_the_ID()),
                        'meta_query' => array(
                            array(
                                'key' => '_exchange_status',
                                'value' => 'active',
                                'compare' => '='
                            )
                        )
                    );
                    
                    // Try to get related by category first
                    if ($categories && !is_wp_error($categories)) {
                        $related_args['tax_query'] = array(
                            array(
                                'taxonomy' => 'exchange_type',
                                'field' => 'term_id',
                                'terms' => wp_list_pluck($categories, 'term_id')
                            )
                        );
                    }
                    
                    $related_query = new WP_Query($related_args);
                    
                    if ($related_query->have_posts()) :
                        while ($related_query->have_posts()) : $related_query->the_post();
                    ?>
                        <div class="ep-related-item">
                            <div class="ep-related-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                <?php else : ?>
                                    <div class="ep-no-image-small">
                                        <span>📦</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ep-related-content">
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <div class="ep-related-meta">
                                    <?php
                                    $rel_type = get_post_meta(get_the_ID(), '_exchange_type', true);
                                    $rel_location = get_post_meta(get_the_ID(), '_exchange_location', true);
                                    ?>
                                    <span class="ep-related-type ep-type-<?php echo esc_attr($rel_type); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $rel_type))); ?>
                                    </span>
                                    <span class="ep-related-location"><?php echo esc_html($rel_location); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                    ?>
                        <p class="ep-no-related"><?php _e('No related exchanges found.', 'environmental-item-exchange'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    <?php endwhile; ?>
</div>

<!-- Contact Owner Modal -->
<div id="ep-contact-modal" class="ep-modal">
    <div class="ep-modal-content">
        <div class="ep-modal-header">
            <h3><?php _e('Contact Exchange Owner', 'environmental-item-exchange'); ?></h3>
            <button class="ep-modal-close">&times;</button>
        </div>
        
        <form id="ep-contact-form">
            <input type="hidden" name="exchange_id" value="">
            
            <div class="ep-form-group">
                <label class="ep-form-label"><?php _e('Message to:', 'environmental-item-exchange'); ?> <span class="ep-owner-name"></span></label>
                <textarea name="message" class="ep-form-textarea" rows="5" 
                          placeholder="<?php _e('Write your message here...', 'environmental-item-exchange'); ?>" required></textarea>
            </div>
            
            <div class="ep-modal-footer">
                <button type="button" class="ep-btn ep-btn-outline ep-modal-close"><?php _e('Cancel', 'environmental-item-exchange'); ?></button>
                <button type="submit" class="ep-btn ep-btn-primary ep-submit-btn"><?php _e('Send Message', 'environmental-item-exchange'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
/* Single Exchange Styles */
.ep-single-exchange-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ep-exchange-wrapper {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.ep-breadcrumb {
    margin-bottom: 10px;
    font-size: 14px;
    color: #6c757d;
}

.ep-breadcrumb a {
    color: #4caf50;
    text-decoration: none;
}

.ep-breadcrumb-separator {
    margin: 0 8px;
}

.ep-exchange-header {
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid #e9ecef;
}

.ep-exchange-title {
    margin: 0 0 20px 0;
    font-size: 32px;
    color: #333;
    font-weight: 600;
}

.ep-exchange-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.ep-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ep-meta-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 14px;
}

.ep-exchange-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    padding: 30px;
}

.ep-exchange-gallery {
    margin-bottom: 30px;
}

.ep-main-image {
    width: 100%;
    height: 400px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    margin-bottom: 15px;
}

.ep-main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ep-thumbnail-gallery {
    display: flex;
    gap: 10px;
    overflow-x: auto;
}

.ep-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.ep-thumbnail.active,
.ep-thumbnail:hover {
    opacity: 1;
}

.ep-no-image {
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
}

.ep-no-image-placeholder {
    text-align: center;
    color: #6c757d;
}

.ep-no-image-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 10px;
}

.ep-exchange-description,
.ep-exchange-details {
    margin-bottom: 30px;
}

.ep-exchange-description h3,
.ep-exchange-details h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
    color: #333;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 8px;
}

.ep-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.ep-detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.ep-detail-label {
    font-weight: 600;
    color: #6c757d;
}

.ep-detail-value {
    color: #333;
}

.ep-exchange-sidebar > div {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.ep-owner-card h3,
.ep-environmental-impact h3,
.ep-share-exchange h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}

.ep-owner-info {
    display: flex;
    gap: 15px;
    align-items: start;
}

.ep-owner-avatar {
    flex-shrink: 0;
}

.ep-owner-avatar img {
    border-radius: 50%;
}

.ep-owner-name {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #333;
}

.ep-owner-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.ep-stars {
    display: flex;
    gap: 2px;
}

.ep-star {
    color: #ddd;
    font-size: 14px;
}

.ep-star.active {
    color: #ffc107;
}

.ep-rating-text {
    font-size: 12px;
    color: #6c757d;
}

.ep-exchange-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ep-exchange-actions .ep-btn {
    justify-content: center;
    align-items: center;
    display: flex;
    gap: 8px;
}

.ep-impact-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ep-impact-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ep-impact-icon {
    font-size: 24px;
}

.ep-impact-number {
    font-size: 18px;
    font-weight: 600;
    color: #4caf50;
    display: block;
}

.ep-impact-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
}

.ep-impact-message {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.ep-impact-message p {
    margin: 0;
    font-size: 14px;
    color: #6c757d;
    font-style: italic;
}

.ep-share-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.ep-share-btn {
    padding: 8px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}

.ep-share-facebook { background-color: #1877f2; }
.ep-share-twitter { background-color: #1da1f2; }
.ep-share-email { background-color: #6c757d; }

.ep-related-exchanges {
    padding: 30px;
    border-top: 1px solid #e9ecef;
}

.ep-related-exchanges h3 {
    margin: 0 0 20px 0;
    font-size: 24px;
    color: #333;
}

.ep-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.ep-related-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.ep-related-item:hover {
    transform: translateY(-2px);
}

.ep-related-image {
    height: 150px;
    overflow: hidden;
}

.ep-related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ep-related-content {
    padding: 15px;
}

.ep-related-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.ep-related-content h4 a {
    color: #333;
    text-decoration: none;
}

.ep-related-content h4 a:hover {
    color: #4caf50;
}

.ep-related-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ep-related-type,
.ep-related-location {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
}

.ep-related-location {
    background: #f8f9fa;
    color: #6c757d;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ep-exchange-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .ep-exchange-meta {
        grid-template-columns: 1fr;
    }
    
    .ep-owner-info {
        flex-direction: column;
        text-align: center;
    }
    
    .ep-related-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Gallery functionality
jQuery(document).ready(function($) {
    // Thumbnail click handler
    $('.ep-thumbnail').on('click', function() {
        const mainSrc = $(this).data('main-src');
        $('#ep-main-image').attr('src', mainSrc);
        $('.ep-thumbnail').removeClass('active');
        $(this).addClass('active');
    });
});
</script>

<?php get_footer(); ?>
