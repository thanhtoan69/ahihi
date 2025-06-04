<?php
/**
 * Single Eco Product Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-product-single">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-product-article'); ?>>
            
            <!-- Product Header -->
            <header class="ep-product-header">
                <div class="ep-container">
                    <div class="ep-row">
                        <div class="ep-col-6">
                            <!-- Product Gallery -->
                            <div class="ep-product-gallery">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="ep-main-image">
                                        <?php the_post_thumbnail('large', array('class' => 'ep-product-image')); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                                $gallery_images = get_post_meta(get_the_ID(), '_ep_product_gallery', true);
                                if ($gallery_images && is_array($gallery_images)):
                                ?>
                                    <div class="ep-gallery-thumbnails">
                                        <?php foreach ($gallery_images as $image_id): ?>
                                            <div class="ep-thumb-item">
                                                <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, array('class' => 'ep-gallery-thumb')); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ep-col-6">
                            <!-- Product Information -->
                            <div class="ep-product-info">
                                <?php
                                $product_category = get_post_meta(get_the_ID(), '_ep_product_category', true);
                                $sustainability_rating = get_post_meta(get_the_ID(), '_ep_sustainability_rating', true);
                                $price = get_post_meta(get_the_ID(), '_ep_price', true);
                                $availability = get_post_meta(get_the_ID(), '_ep_availability', true);
                                ?>
                                
                                <?php if ($product_category): ?>
                                    <span class="ep-product-category">
                                        <?php echo esc_html($product_category); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <h1 class="ep-product-title"><?php the_title(); ?></h1>
                                
                                <!-- Sustainability Rating -->
                                <?php if ($sustainability_rating): ?>
                                    <div class="ep-sustainability-rating">
                                        <span class="ep-rating-label"><?php _e('Sustainability Score:', 'environmental-platform-core'); ?></span>
                                        <div class="ep-rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="ep-star <?php echo $i <= $sustainability_rating ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ep-rating-text"><?php echo $sustainability_rating; ?>/5</span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Price -->
                                <?php if ($price): ?>
                                    <div class="ep-product-price">
                                        <span class="ep-price"><?php echo esc_html($price); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Short Description -->
                                <?php if (has_excerpt()): ?>
                                    <div class="ep-product-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Key Features -->
                                <?php
                                $key_features = get_post_meta(get_the_ID(), '_ep_key_features', true);
                                if ($key_features):
                                ?>
                                    <div class="ep-key-features">
                                        <h3><?php _e('Key Features', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-features-list">
                                            <?php 
                                            $features = explode("\n", $key_features);
                                            foreach ($features as $feature):
                                                if (trim($feature)):
                                            ?>
                                                <li><?php echo esc_html(trim($feature)); ?></li>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Availability -->
                                <?php if ($availability): ?>
                                    <div class="ep-availability">
                                        <span class="ep-availability-label"><?php _e('Availability:', 'environmental-platform-core'); ?></span>
                                        <span class="ep-availability-status ep-status-<?php echo esc_attr(sanitize_title($availability)); ?>">
                                            <?php echo esc_html($availability); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="ep-product-actions">
                                    <?php
                                    $purchase_link = get_post_meta(get_the_ID(), '_ep_purchase_link', true);
                                    $vendor_contact = get_post_meta(get_the_ID(), '_ep_vendor_contact', true);
                                    ?>
                                    
                                    <?php if ($purchase_link): ?>
                                        <a href="<?php echo esc_url($purchase_link); ?>" 
                                           class="ep-btn ep-btn-primary ep-purchase-btn" 
                                           target="_blank" 
                                           rel="noopener">
                                            <i class="ep-icon-cart"></i>
                                            <?php _e('Buy Now', 'environmental-platform-core'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($vendor_contact): ?>
                                        <a href="mailto:<?php echo esc_attr($vendor_contact); ?>" 
                                           class="ep-btn ep-btn-secondary ep-contact-btn">
                                            <i class="ep-icon-email"></i>
                                            <?php _e('Contact Vendor', 'environmental-platform-core'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button class="ep-btn ep-btn-secondary ep-wishlist-btn" 
                                            onclick="epToggleWishlist(<?php echo get_the_ID(); ?>)">
                                        <i class="ep-icon-heart"></i>
                                        <?php _e('Add to Wishlist', 'environmental-platform-core'); ?>
                                    </button>
                                    
                                    <button class="ep-btn ep-btn-secondary ep-share-btn" 
                                            onclick="epShareProduct()">
                                        <i class="ep-icon-share"></i>
                                        <?php _e('Share', 'environmental-platform-core'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Product Details -->
            <div class="ep-product-content">
                <div class="ep-container">
                    <div class="ep-row">
                        <div class="ep-col-8">
                            
                            <!-- Product Tabs -->
                            <div class="ep-product-tabs">
                                <nav class="ep-tabs-nav">
                                    <button class="ep-tab-btn active" data-tab="description">
                                        <?php _e('Description', 'environmental-platform-core'); ?>
                                    </button>
                                    <button class="ep-tab-btn" data-tab="sustainability">
                                        <?php _e('Sustainability', 'environmental-platform-core'); ?>
                                    </button>
                                    <button class="ep-tab-btn" data-tab="certifications">
                                        <?php _e('Certifications', 'environmental-platform-core'); ?>
                                    </button>
                                    <button class="ep-tab-btn" data-tab="reviews">
                                        <?php _e('Reviews', 'environmental-platform-core'); ?>
                                    </button>
                                </nav>
                                
                                <!-- Description Tab -->
                                <div class="ep-tab-content active" id="tab-description">
                                    <div class="ep-product-description">
                                        <?php the_content(); ?>
                                    </div>
                                </div>
                                
                                <!-- Sustainability Tab -->
                                <div class="ep-tab-content" id="tab-sustainability">
                                    <?php
                                    $sustainability_info = get_post_meta(get_the_ID(), '_ep_sustainability_info', true);
                                    $carbon_footprint = get_post_meta(get_the_ID(), '_ep_carbon_footprint', true);
                                    $materials = get_post_meta(get_the_ID(), '_ep_materials', true);
                                    $packaging = get_post_meta(get_the_ID(), '_ep_packaging_info', true);
                                    ?>
                                    
                                    <div class="ep-sustainability-details">
                                        <h3><?php _e('Environmental Impact', 'environmental-platform-core'); ?></h3>
                                        
                                        <?php if ($sustainability_info): ?>
                                            <div class="ep-sustainability-section">
                                                <h4><?php _e('Sustainability Information', 'environmental-platform-core'); ?></h4>
                                                <p><?php echo wp_kses_post($sustainability_info); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($carbon_footprint): ?>
                                            <div class="ep-carbon-footprint">
                                                <h4><?php _e('Carbon Footprint', 'environmental-platform-core'); ?></h4>
                                                <div class="ep-carbon-display">
                                                    <span class="ep-carbon-value"><?php echo esc_html($carbon_footprint); ?></span>
                                                    <span class="ep-carbon-unit">kg CO₂e</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($materials): ?>
                                            <div class="ep-materials-section">
                                                <h4><?php _e('Materials', 'environmental-platform-core'); ?></h4>
                                                <p><?php echo wp_kses_post($materials); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($packaging): ?>
                                            <div class="ep-packaging-section">
                                                <h4><?php _e('Packaging', 'environmental-platform-core'); ?></h4>
                                                <p><?php echo wp_kses_post($packaging); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Certifications Tab -->
                                <div class="ep-tab-content" id="tab-certifications">
                                    <?php
                                    $certifications = get_the_terms(get_the_ID(), 'eco_certification');
                                    if ($certifications && !is_wp_error($certifications)):
                                    ?>
                                        <div class="ep-certifications">
                                            <h3><?php _e('Eco Certifications', 'environmental-platform-core'); ?></h3>
                                            <div class="ep-certifications-grid">
                                                <?php foreach ($certifications as $cert): ?>
                                                    <div class="ep-certification-item">
                                                        <h4><?php echo esc_html($cert->name); ?></h4>
                                                        <?php if ($cert->description): ?>
                                                            <p><?php echo esc_html($cert->description); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p><?php _e('No certifications listed for this product.', 'environmental-platform-core'); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Reviews Tab -->
                                <div class="ep-tab-content" id="tab-reviews">
                                    <?php if (comments_open() || get_comments_number()): ?>
                                        <div class="ep-product-reviews">
                                            <h3><?php _e('Customer Reviews', 'environmental-platform-core'); ?></h3>
                                            <?php comments_template(); ?>
                                        </div>
                                    <?php else: ?>
                                        <p><?php _e('No reviews yet. Be the first to review this product!', 'environmental-platform-core'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="ep-col-4">
                            <aside class="ep-product-sidebar">
                                
                                <!-- Vendor Information -->
                                <?php
                                $vendor_name = get_post_meta(get_the_ID(), '_ep_vendor_name', true);
                                $vendor_location = get_post_meta(get_the_ID(), '_ep_vendor_location', true);
                                $vendor_website = get_post_meta(get_the_ID(), '_ep_vendor_website', true);
                                if ($vendor_name):
                                ?>
                                    <div class="ep-widget ep-vendor-info">
                                        <h3><?php _e('Vendor Information', 'environmental-platform-core'); ?></h3>
                                        <div class="ep-vendor-details">
                                            <h4><?php echo esc_html($vendor_name); ?></h4>
                                            
                                            <?php if ($vendor_location): ?>
                                                <p class="ep-vendor-location">
                                                    <i class="ep-icon-location"></i>
                                                    <?php echo esc_html($vendor_location); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if ($vendor_website): ?>
                                                <p class="ep-vendor-website">
                                                    <a href="<?php echo esc_url($vendor_website); ?>" 
                                                       target="_blank" 
                                                       rel="noopener">
                                                        <?php _e('Visit Vendor Website', 'environmental-platform-core'); ?>
                                                    </a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Product Specifications -->
                                <?php
                                $specifications = get_post_meta(get_the_ID(), '_ep_specifications', true);
                                if ($specifications && is_array($specifications)):
                                ?>
                                    <div class="ep-widget ep-specifications">
                                        <h3><?php _e('Specifications', 'environmental-platform-core'); ?></h3>
                                        <dl class="ep-specs-list">
                                            <?php foreach ($specifications as $spec): ?>
                                                <dt><?php echo esc_html($spec['name']); ?></dt>
                                                <dd><?php echo esc_html($spec['value']); ?></dd>
                                            <?php endforeach; ?>
                                        </dl>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Environmental Benefits -->
                                <?php
                                $benefits = get_post_meta(get_the_ID(), '_ep_environmental_benefits', true);
                                if ($benefits):
                                ?>
                                    <div class="ep-widget ep-benefits">
                                        <h3><?php _e('Environmental Benefits', 'environmental-platform-core'); ?></h3>
                                        <div class="ep-benefits-content">
                                            <?php echo wp_kses_post($benefits); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Similar Products -->
                                <?php
                                $similar_products = get_posts(array(
                                    'post_type' => 'env_eco_product',
                                    'posts_per_page' => 3,
                                    'post__not_in' => array(get_the_ID()),
                                    'meta_query' => array(
                                        array(
                                            'key' => '_ep_product_category',
                                            'value' => $product_category,
                                            'compare' => '='
                                        )
                                    )
                                ));
                                
                                if ($similar_products):
                                ?>
                                    <div class="ep-widget ep-similar-products">
                                        <h3><?php _e('Similar Products', 'environmental-platform-core'); ?></h3>
                                        <div class="ep-similar-list">
                                            <?php foreach ($similar_products as $similar): ?>
                                                <div class="ep-similar-item">
                                                    <a href="<?php echo get_permalink($similar->ID); ?>" 
                                                       class="ep-similar-link">
                                                        <?php if (has_post_thumbnail($similar->ID)): ?>
                                                            <div class="ep-similar-thumb">
                                                                <?php echo get_the_post_thumbnail($similar->ID, 'thumbnail'); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="ep-similar-content">
                                                            <h4><?php echo get_the_title($similar->ID); ?></h4>
                                                            <span class="ep-similar-price">
                                                                <?php echo get_post_meta($similar->ID, '_ep_price', true); ?>
                                                            </span>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Categories -->
                                <?php
                                $categories = get_the_terms(get_the_ID(), 'product_category');
                                if ($categories && !is_wp_error($categories)):
                                ?>
                                    <div class="ep-widget ep-product-categories">
                                        <h3><?php _e('Categories', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-category-list">
                                            <?php foreach ($categories as $category): ?>
                                                <li>
                                                    <a href="<?php echo get_term_link($category); ?>" 
                                                       class="ep-category-link">
                                                        <?php echo esc_html($category->name); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
            
        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-product-single {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

.ep-product-header {
    background-color: #f8f9fa;
    padding: 60px 0;
}

.ep-product-gallery {
    text-align: center;
}

.ep-main-image {
    margin-bottom: 20px;
}

.ep-product-image {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.ep-gallery-thumbnails {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.ep-thumb-item {
    cursor: pointer;
    border-radius: 5px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: border-color 0.3s ease;
}

.ep-thumb-item:hover {
    border-color: #27ae60;
}

.ep-gallery-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
}

.ep-product-info {
    padding-left: 40px;
}

.ep-product-category {
    display: inline-block;
    background-color: #27ae60;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 600;
    margin-bottom: 15px;
}

.ep-product-title {
    font-size: 2.2em;
    font-weight: 700;
    margin: 15px 0;
    color: #2c3e50;
}

.ep-sustainability-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.ep-rating-label {
    font-weight: 600;
}

.ep-rating-stars {
    display: flex;
    gap: 2px;
}

.ep-star {
    font-size: 1.2em;
    color: #ddd;
    transition: color 0.3s ease;
}

.ep-star.filled {
    color: #f39c12;
}

.ep-rating-text {
    font-weight: 600;
    color: #27ae60;
}

.ep-product-price {
    margin: 20px 0;
}

.ep-price {
    font-size: 1.8em;
    font-weight: 700;
    color: #27ae60;
}

.ep-product-excerpt {
    font-size: 1.1em;
    color: #6c757d;
    margin: 20px 0;
    line-height: 1.6;
}

.ep-key-features {
    margin: 25px 0;
}

.ep-key-features h3 {
    font-size: 1.2em;
    margin-bottom: 15px;
    color: #2c3e50;
}

.ep-features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-features-list li {
    padding: 8px 0;
    padding-left: 25px;
    position: relative;
    border-bottom: 1px solid #e9ecef;
}

.ep-features-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #27ae60;
    font-weight: bold;
}

.ep-availability {
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-availability-label {
    font-weight: 600;
}

.ep-availability-status {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.9em;
    font-weight: 600;
}

.ep-status-in-stock { background-color: #d4edda; color: #155724; }
.ep-status-out-of-stock { background-color: #f8d7da; color: #721c24; }
.ep-status-limited { background-color: #fff3cd; color: #856404; }

.ep-product-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.ep-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95em;
}

.ep-btn-primary {
    background-color: #27ae60;
    color: white;
}

.ep-btn-primary:hover {
    background-color: #219a52;
    transform: translateY(-2px);
}

.ep-btn-secondary {
    background-color: transparent;
    color: #6c757d;
    border: 2px solid #6c757d;
}

.ep-btn-secondary:hover {
    background-color: #6c757d;
    color: white;
}

.ep-product-content {
    padding: 60px 0;
}

.ep-product-tabs {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.ep-tabs-nav {
    display: flex;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.ep-tab-btn {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background-color: transparent;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    border-radius: 8px 8px 0 0;
}

.ep-tab-btn:hover,
.ep-tab-btn.active {
    background-color: white;
    color: #27ae60;
}

.ep-tab-content {
    display: none;
    padding: 40px;
}

.ep-tab-content.active {
    display: block;
}

.ep-product-description {
    font-size: 1.1em;
    line-height: 1.8;
}

.ep-sustainability-details h3 {
    color: #27ae60;
    margin-bottom: 25px;
    font-size: 1.4em;
}

.ep-sustainability-section,
.ep-carbon-footprint,
.ep-materials-section,
.ep-packaging-section {
    margin-bottom: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
}

.ep-carbon-display {
    display: flex;
    align-items: baseline;
    gap: 5px;
    margin-top: 10px;
}

.ep-carbon-value {
    font-size: 2em;
    font-weight: 700;
    color: #27ae60;
}

.ep-carbon-unit {
    font-size: 1.1em;
    color: #6c757d;
}

.ep-certifications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-certification-item {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #e9ecef;
}

.ep-certification-item h4 {
    color: #27ae60;
    margin-bottom: 10px;
}

.ep-product-sidebar {
    padding-left: 30px;
}

.ep-widget {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.ep-widget h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.1em;
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.ep-vendor-details h4 {
    color: #27ae60;
    margin-bottom: 10px;
}

.ep-vendor-location,
.ep-vendor-website {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 8px 0;
}

.ep-vendor-website a {
    color: #27ae60;
    text-decoration: none;
}

.ep-specs-list dt {
    font-weight: 600;
    color: #495057;
    margin-top: 10px;
}

.ep-specs-list dd {
    margin-bottom: 10px;
    margin-left: 0;
    color: #6c757d;
}

.ep-similar-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ep-similar-item {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 15px;
}

.ep-similar-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ep-similar-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
    color: #333;
    transition: color 0.3s ease;
}

.ep-similar-link:hover {
    color: #27ae60;
}

.ep-similar-thumb {
    flex-shrink: 0;
}

.ep-similar-thumb img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.ep-similar-content h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
    line-height: 1.3;
}

.ep-similar-price {
    font-weight: 600;
    color: #27ae60;
    font-size: 0.9em;
}

.ep-category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-category-list li {
    margin-bottom: 8px;
}

.ep-category-link {
    color: #27ae60;
    text-decoration: none;
    font-weight: 500;
}

.ep-category-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .ep-product-info {
        padding-left: 0;
        margin-top: 30px;
    }
    
    .ep-product-title {
        font-size: 1.8em;
    }
    
    .ep-product-actions {
        flex-direction: column;
    }
    
    .ep-tabs-nav {
        flex-direction: column;
    }
    
    .ep-product-sidebar {
        padding-left: 0;
        margin-top: 40px;
    }
    
    .ep-certifications-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.ep-tab-btn');
    const tabContents = document.querySelectorAll('.ep-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById('tab-' + targetTab).classList.add('active');
        });
    });
    
    // Gallery functionality
    const galleryThumbs = document.querySelectorAll('.ep-gallery-thumb');
    const mainImage = document.querySelector('.ep-product-image');
    
    if (mainImage) {
        galleryThumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImage.src = this.src.replace('-150x150', '-large');
                
                // Update active thumbnail
                galleryThumbs.forEach(t => t.parentElement.classList.remove('active'));
                this.parentElement.classList.add('active');
            });
        });
    }
});

function epToggleWishlist(productId) {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=ep_toggle_wishlist&product_id=${productId}&nonce=<?php echo wp_create_nonce('ep_wishlist_nonce'); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector('.ep-wishlist-btn');
            if (data.data.added) {
                btn.innerHTML = '<i class="ep-icon-heart-filled"></i> <?php _e('Remove from Wishlist', 'environmental-platform-core'); ?>';
            } else {
                btn.innerHTML = '<i class="ep-icon-heart"></i> <?php _e('Add to Wishlist', 'environmental-platform-core'); ?>';
            }
        }
    });
}

function epShareProduct() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('<?php _e('Link copied to clipboard!', 'environmental-platform-core'); ?>');
        });
    }
}
</script>

<?php get_footer(); ?>
