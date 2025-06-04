<?php
/**
 * Archive Eco Products Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="eco-products-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-leaf"></i>
                        <?php _e('Eco-Friendly Products', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Discover sustainable and eco-friendly products that help reduce your environmental footprint.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Product Categories Overview -->
                <div class="product-categories-overview">
                    <?php
                    $categories = array(
                        'home-garden' => __('Home & Garden', 'environmental-platform-core'),
                        'personal-care' => __('Personal Care', 'environmental-platform-core'),
                        'clothing' => __('Sustainable Clothing', 'environmental-platform-core'),
                        'food-beverage' => __('Food & Beverage', 'environmental-platform-core'),
                        'electronics' => __('Green Electronics', 'environmental-platform-core'),
                        'transportation' => __('Transportation', 'environmental-platform-core')
                    );
                    
                    $category_stats = array();
                    foreach ($categories as $cat_key => $cat_name) {
                        $cat_query = new WP_Query(array(
                            'post_type' => 'eco_product',
                            'meta_query' => array(
                                array(
                                    'key' => '_ep_product_category',
                                    'value' => $cat_key,
                                    'compare' => '='
                                )
                            ),
                            'posts_per_page' => -1
                        ));
                        $category_stats[$cat_key] = $cat_query->found_posts;
                        wp_reset_postdata();
                    }
                    ?>
                    
                    <div class="category-grid">
                        <?php foreach ($categories as $cat_key => $cat_name): ?>
                            <div class="category-item" data-category="<?php echo esc_attr($cat_key); ?>">
                                <div class="category-icon">
                                    <?php
                                    $icons = array(
                                        'home-garden' => 'fa-home',
                                        'personal-care' => 'fa-spa',
                                        'clothing' => 'fa-tshirt',
                                        'food-beverage' => 'fa-apple-alt',
                                        'electronics' => 'fa-laptop',
                                        'transportation' => 'fa-bicycle'
                                    );
                                    ?>
                                    <i class="fa <?php echo $icons[$cat_key]; ?>"></i>
                                </div>
                                <span class="category-name"><?php echo $cat_name; ?></span>
                                <span class="category-count"><?php echo $category_stats[$cat_key]; ?> <?php _e('products', 'environmental-platform-core'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="category-filter"><?php _e('Category:', 'environmental-platform-core'); ?></label>
                            <select id="category-filter" class="form-control">
                                <option value=""><?php _e('All Categories', 'environmental-platform-core'); ?></option>
                                <?php foreach ($categories as $cat_key => $cat_name): ?>
                                    <option value="<?php echo esc_attr($cat_key); ?>" <?php selected(isset($_GET['category']) && $_GET['category'] == $cat_key); ?>><?php echo $cat_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="rating-filter"><?php _e('Sustainability Rating:', 'environmental-platform-core'); ?></label>
                            <select id="rating-filter" class="form-control">
                                <option value=""><?php _e('All Ratings', 'environmental-platform-core'); ?></option>
                                <option value="5" <?php selected(isset($_GET['rating']) && $_GET['rating'] == '5'); ?>><?php _e('5 Stars', 'environmental-platform-core'); ?></option>
                                <option value="4" <?php selected(isset($_GET['rating']) && $_GET['rating'] == '4'); ?>><?php _e('4+ Stars', 'environmental-platform-core'); ?></option>
                                <option value="3" <?php selected(isset($_GET['rating']) && $_GET['rating'] == '3'); ?>><?php _e('3+ Stars', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="price-filter"><?php _e('Price Range:', 'environmental-platform-core'); ?></label>
                            <select id="price-filter" class="form-control">
                                <option value=""><?php _e('All Prices', 'environmental-platform-core'); ?></option>
                                <option value="0-25" <?php selected(isset($_GET['price']) && $_GET['price'] == '0-25'); ?>><?php _e('Under $25', 'environmental-platform-core'); ?></option>
                                <option value="25-50" <?php selected(isset($_GET['price']) && $_GET['price'] == '25-50'); ?>><?php _e('$25 - $50', 'environmental-platform-core'); ?></option>
                                <option value="50-100" <?php selected(isset($_GET['price']) && $_GET['price'] == '50-100'); ?>><?php _e('$50 - $100', 'environmental-platform-core'); ?></option>
                                <option value="100+" <?php selected(isset($_GET['price']) && $_GET['price'] == '100+'); ?>><?php _e('$100+', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="availability-filter"><?php _e('Availability:', 'environmental-platform-core'); ?></label>
                            <select id="availability-filter" class="form-control">
                                <option value=""><?php _e('All', 'environmental-platform-core'); ?></option>
                                <option value="in-stock" <?php selected(isset($_GET['availability']) && $_GET['availability'] == 'in-stock'); ?>><?php _e('In Stock', 'environmental-platform-core'); ?></option>
                                <option value="pre-order" <?php selected(isset($_GET['availability']) && $_GET['availability'] == 'pre-order'); ?>><?php _e('Pre-Order', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="sort-group">
                            <label for="sort-by"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                            <select id="sort-by" class="form-control">
                                <option value="date" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'date'); ?>><?php _e('Newest First', 'environmental-platform-core'); ?></option>
                                <option value="title" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'title'); ?>><?php _e('Name A-Z', 'environmental-platform-core'); ?></option>
                                <option value="rating" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'rating'); ?>><?php _e('Highest Rated', 'environmental-platform-core'); ?></option>
                                <option value="price-low" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'price-low'); ?>><?php _e('Price: Low to High', 'environmental-platform-core'); ?></option>
                                <option value="price-high" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'price-high'); ?>><?php _e('Price: High to Low', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="view-toggle">
                            <button type="button" class="view-btn grid-view active" data-view="grid">
                                <i class="fa fa-th"></i>
                            </button>
                            <button type="button" class="view-btn list-view" data-view="list">
                                <i class="fa fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="products-container">
                    <div class="products-grid grid-layout" id="products-grid">
                        <?php
                        // Build query args based on filters
                        $query_args = array(
                            'post_type' => 'eco_product',
                            'posts_per_page' => 12,
                            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                        );
                        
                        $meta_query = array('relation' => 'AND');
                        
                        if (isset($_GET['category']) && !empty($_GET['category'])) {
                            $meta_query[] = array(
                                'key' => '_ep_product_category',
                                'value' => sanitize_text_field($_GET['category']),
                                'compare' => '='
                            );
                        }
                        
                        if (isset($_GET['rating']) && !empty($_GET['rating'])) {
                            $rating = intval($_GET['rating']);
                            $meta_query[] = array(
                                'key' => '_ep_sustainability_rating',
                                'value' => $rating,
                                'compare' => '>='
                            );
                        }
                        
                        if (isset($_GET['availability']) && !empty($_GET['availability'])) {
                            $meta_query[] = array(
                                'key' => '_ep_availability',
                                'value' => sanitize_text_field($_GET['availability']),
                                'compare' => '='
                            );
                        }
                        
                        if (!empty($meta_query)) {
                            $query_args['meta_query'] = $meta_query;
                        }
                        
                        // Handle sorting
                        if (isset($_GET['sort'])) {
                            switch ($_GET['sort']) {
                                case 'title':
                                    $query_args['orderby'] = 'title';
                                    $query_args['order'] = 'ASC';
                                    break;
                                case 'rating':
                                    $query_args['meta_key'] = '_ep_sustainability_rating';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                                case 'price-low':
                                    $query_args['meta_key'] = '_ep_price_numeric';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'ASC';
                                    break;
                                case 'price-high':
                                    $query_args['meta_key'] = '_ep_price_numeric';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                            }
                        }
                        
                        $products_query = new WP_Query($query_args);
                        
                        if ($products_query->have_posts()) :
                            while ($products_query->have_posts()) : $products_query->the_post();
                                $product_category = get_post_meta(get_the_ID(), '_ep_product_category', true);
                                $sustainability_rating = get_post_meta(get_the_ID(), '_ep_sustainability_rating', true);
                                $price = get_post_meta(get_the_ID(), '_ep_price', true);
                                $availability = get_post_meta(get_the_ID(), '_ep_availability', true);
                                $eco_certifications = get_post_meta(get_the_ID(), '_ep_eco_certifications', true);
                        ?>
                            <article class="product-card" data-category="<?php echo esc_attr($product_category); ?>" data-rating="<?php echo esc_attr($sustainability_rating); ?>">
                                <div class="product-card-inner">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="product-image">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                                            </a>
                                            
                                            <?php if ($availability === 'out-of-stock'): ?>
                                                <div class="availability-badge out-of-stock">
                                                    <?php _e('Out of Stock', 'environmental-platform-core'); ?>
                                                </div>
                                            <?php elseif ($availability === 'limited'): ?>
                                                <div class="availability-badge limited">
                                                    <?php _e('Limited Stock', 'environmental-platform-core'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($eco_certifications): ?>
                                                <div class="eco-badges">
                                                    <?php 
                                                    $certifications = explode(',', $eco_certifications);
                                                    foreach ($certifications as $cert):
                                                        $cert = trim($cert);
                                                    ?>
                                                        <span class="eco-badge"><?php echo esc_html($cert); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-content">
                                        <?php if ($product_category): ?>
                                            <span class="product-category">
                                                <?php echo esc_html($categories[$product_category] ?? $product_category); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <h3 class="product-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <?php if ($sustainability_rating): ?>
                                            <div class="sustainability-rating">
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $sustainability_rating ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="rating-text"><?php echo $sustainability_rating; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                        </div>
                                        
                                        <div class="product-meta">
                                            <?php if ($price): ?>
                                                <span class="product-price"><?php echo esc_html($price); ?></span>
                                            <?php endif; ?>
                                            
                                            <div class="product-actions">
                                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                                    <?php _e('View Details', 'environmental-platform-core'); ?>
                                                </a>
                                                <button type="button" class="btn btn-secondary wishlist-btn" data-product-id="<?php the_ID(); ?>">
                                                    <i class="fa fa-heart"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php
                            endwhile;
                        else :
                        ?>
                            <div class="no-products-found">
                                <i class="fa fa-search"></i>
                                <h3><?php _e('No products found', 'environmental-platform-core'); ?></h3>
                                <p><?php _e('Try adjusting your filters or browse all categories.', 'environmental-platform-core'); ?></p>
                                <button type="button" class="btn btn-primary" id="clear-filters">
                                    <?php _e('Clear Filters', 'environmental-platform-core'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($products_query->max_num_pages > 1): ?>
                        <div class="archive-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $products_query->max_num_pages,
                                'current' => max(1, get_query_var('paged')),
                                'format' => '?paged=%#%',
                                'show_all' => false,
                                'type' => 'plain',
                                'end_size' => 2,
                                'mid_size' => 1,
                                'prev_text' => __('← Previous', 'environmental-platform-core'),
                                'next_text' => __('Next →', 'environmental-platform-core'),
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php wp_reset_postdata(); ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <aside class="archive-sidebar">
                    <!-- Featured Products -->
                    <div class="sidebar-widget featured-products">
                        <h3 class="widget-title"><?php _e('Featured Products', 'environmental-platform-core'); ?></h3>
                        <div class="featured-products-list">
                            <?php
                            $featured_query = new WP_Query(array(
                                'post_type' => 'eco_product',
                                'posts_per_page' => 3,
                                'meta_query' => array(
                                    array(
                                        'key' => '_ep_featured',
                                        'value' => '1',
                                        'compare' => '='
                                    )
                                )
                            ));
                            
                            while ($featured_query->have_posts()) : $featured_query->the_post();
                                $price = get_post_meta(get_the_ID(), '_ep_price', true);
                                $rating = get_post_meta(get_the_ID(), '_ep_sustainability_rating', true);
                            ?>
                                <div class="featured-product-item">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="featured-product-image">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="featured-product-content">
                                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                        <?php if ($rating): ?>
                                            <div class="rating-mini">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($price): ?>
                                            <span class="price"><?php echo esc_html($price); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    
                    <!-- Sustainability Guide -->
                    <div class="sidebar-widget sustainability-guide">
                        <h3 class="widget-title"><?php _e('Sustainability Guide', 'environmental-platform-core'); ?></h3>
                        <div class="guide-content">
                            <div class="guide-item">
                                <i class="fa fa-star"></i>
                                <div>
                                    <strong><?php _e('5 Stars', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Exceptional sustainability with minimal environmental impact', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            <div class="guide-item">
                                <i class="fa fa-star"></i>
                                <div>
                                    <strong><?php _e('4 Stars', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Very good sustainability practices and eco-friendly materials', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            <div class="guide-item">
                                <i class="fa fa-star"></i>
                                <div>
                                    <strong><?php _e('3 Stars', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Good sustainability with some environmental benefits', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Newsletter Signup -->
                    <div class="sidebar-widget newsletter-signup">
                        <h3 class="widget-title"><?php _e('Eco Product Updates', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Get notified about new sustainable products and eco-friendly deals.', 'environmental-platform-core'); ?></p>
                        <form class="newsletter-form" id="product-newsletter-form">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="<?php _e('Your email address', 'environmental-platform-core'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php _e('Subscribe', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Product Statistics -->
                    <div class="sidebar-widget product-stats">
                        <h3 class="widget-title"><?php _e('Product Statistics', 'environmental-platform-core'); ?></h3>
                        <?php
                        $total_products = wp_count_posts('eco_product')->publish;
                        $avg_rating_query = $wpdb->get_var("
                            SELECT AVG(CAST(meta_value AS DECIMAL(2,1))) 
                            FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_ep_sustainability_rating' 
                            AND meta_value != ''
                        ");
                        $avg_rating = round($avg_rating_query, 1);
                        ?>
                        <div class="stats-list">
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Total Products:', 'environmental-platform-core'); ?></span>
                                <span class="stat-value"><?php echo $total_products; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Average Rating:', 'environmental-platform-core'); ?></span>
                                <span class="stat-value"><?php echo $avg_rating; ?>/5</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Categories:', 'environmental-platform-core'); ?></span>
                                <span class="stat-value"><?php echo count($categories); ?></span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Product Share Modal -->
<div class="modal fade" id="shareProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _e('Share Product', 'environmental-platform-core'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="share-buttons">
                    <a href="#" class="share-btn facebook" data-platform="facebook">
                        <i class="fab fa-facebook-f"></i>
                        <?php _e('Share on Facebook', 'environmental-platform-core'); ?>
                    </a>
                    <a href="#" class="share-btn twitter" data-platform="twitter">
                        <i class="fab fa-twitter"></i>
                        <?php _e('Share on Twitter', 'environmental-platform-core'); ?>
                    </a>
                    <a href="#" class="share-btn email" data-platform="email">
                        <i class="fa fa-envelope"></i>
                        <?php _e('Share via Email', 'environmental-platform-core'); ?>
                    </a>
                </div>
                <div class="share-link">
                    <input type="text" class="form-control" id="share-url" readonly>
                    <button type="button" class="btn btn-primary" id="copy-link">
                        <?php _e('Copy Link', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.eco-products-archive {
    padding: 2rem 0;
}

.archive-header {
    text-align: center;
    margin-bottom: 3rem;
}

.archive-title {
    font-size: 2.5rem;
    color: #2c5c3e;
    margin-bottom: 1rem;
}

.archive-title i {
    margin-right: 0.5rem;
}

.archive-description {
    font-size: 1.1rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.product-categories-overview {
    margin-bottom: 2rem;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.category-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-item:hover,
.category-item.active {
    border-color: #28a745;
    background: #e8f5e8;
}

.category-icon {
    font-size: 2rem;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.category-name {
    display: block;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.category-count {
    font-size: 0.9rem;
    color: #666;
}

.archive-filters {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: end;
    margin-bottom: 1rem;
}

.filter-row:last-child {
    margin-bottom: 0;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.sort-group {
    flex: 1;
    min-width: 200px;
}

.view-toggle {
    display: flex;
    gap: 0.5rem;
}

.view-btn {
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 4px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-btn.active {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.products-grid {
    display: grid;
    gap: 2rem;
    margin-bottom: 2rem;
}

.grid-layout {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.list-layout {
    grid-template-columns: 1fr;
}

.list-layout .product-card {
    display: flex;
    align-items: center;
}

.list-layout .product-image {
    flex-shrink: 0;
    width: 200px;
    margin-right: 1.5rem;
}

.product-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image {
    position: relative;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.availability-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.availability-badge.out-of-stock {
    background: #dc3545;
    color: white;
}

.availability-badge.limited {
    background: #ffc107;
    color: #212529;
}

.eco-badges {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.eco-badge {
    background: #28a745;
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 12px;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
}

.product-content {
    padding: 1.5rem;
}

.product-category {
    color: #28a745;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-title {
    margin: 0.5rem 0;
    font-size: 1.25rem;
    line-height: 1.3;
}

.product-title a {
    color: #333;
    text-decoration: none;
}

.product-title a:hover {
    color: #28a745;
}

.sustainability-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.75rem 0;
}

.rating-stars {
    display: flex;
    gap: 0.125rem;
}

.star {
    color: #ddd;
    font-size: 1rem;
}

.star.filled {
    color: #ffc107;
}

.rating-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: #666;
}

.product-excerpt {
    color: #666;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #28a745;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.wishlist-btn {
    padding: 0.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-products-found {
    text-align: center;
    padding: 3rem;
    color: #666;
    grid-column: 1 / -1;
}

.no-products-found i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ddd;
}

.sidebar-widget {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.widget-title {
    margin-bottom: 1rem;
    color: #2c5c3e;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.featured-product-item {
    display: flex;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.featured-product-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.featured-product-image {
    flex-shrink: 0;
    width: 60px;
    margin-right: 1rem;
}

.featured-product-image img {
    width: 100%;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.featured-product-content h4 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.featured-product-content a {
    color: #333;
    text-decoration: none;
}

.featured-product-content a:hover {
    color: #28a745;
}

.rating-mini .star {
    font-size: 0.75rem;
}

.price {
    color: #28a745;
    font-weight: 600;
    font-size: 0.9rem;
}

.guide-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.guide-item i {
    color: #ffc107;
    margin-right: 0.75rem;
    margin-top: 0.25rem;
}

.guide-item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #333;
}

.guide-item p {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
}

.newsletter-form .form-group {
    margin-bottom: 1rem;
}

.stats-list .stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.stats-list .stat-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.stat-label {
    color: #666;
}

.stat-value {
    font-weight: 600;
    color: #333;
}

.share-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.share-btn {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-weight: 600;
}

.share-btn i {
    margin-right: 0.5rem;
}

.share-btn.facebook { background: #3b5998; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.email { background: #666; }

.share-link {
    display: flex;
    gap: 0.5rem;
}

.share-link input {
    flex: 1;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 2rem;
    }
    
    .category-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group,
    .sort-group {
        min-width: auto;
    }
    
    .products-grid.grid-layout {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .list-layout .product-card {
        flex-direction: column;
    }
    
    .list-layout .product-image {
        width: 100%;
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .product-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filters = document.querySelectorAll('#category-filter, #rating-filter, #price-filter, #availability-filter, #sort-by');
    const categoryItems = document.querySelectorAll('.category-item');
    const viewButtons = document.querySelectorAll('.view-btn');
    const productsGrid = document.getElementById('products-grid');
    const clearFiltersBtn = document.getElementById('clear-filters');
    
    // Category item clicks
    categoryItems.forEach(item => {
        item.addEventListener('click', function() {
            const category = this.dataset.category;
            document.getElementById('category-filter').value = category;
            updateUrl();
            categoryItems.forEach(cat => cat.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Filter changes
    filters.forEach(filter => {
        filter.addEventListener('change', updateUrl);
    });
    
    // View toggle
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            productsGrid.className = productsGrid.className.replace(/\b(grid|list)-layout\b/g, '');
            productsGrid.classList.add(`${view}-layout`);
        });
    });
    
    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            filters.forEach(filter => filter.value = '');
            categoryItems.forEach(cat => cat.classList.remove('active'));
            updateUrl();
        });
    }
    
    // Newsletter subscription
    const newsletterForm = document.getElementById('product-newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Here you would typically make an AJAX call to subscribe
            alert('<?php _e("Thank you for subscribing!", "environmental-platform-core"); ?>');
            this.reset();
        });
    }
    
    // Wishlist functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.wishlist-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.wishlist-btn');
            const productId = btn.dataset.productId;
            
            btn.classList.toggle('active');
            
            // Here you would typically make an AJAX call to add/remove from wishlist
            const icon = btn.querySelector('i');
            if (btn.classList.contains('active')) {
                icon.className = 'fa fa-heart';
                icon.style.color = '#dc3545';
            } else {
                icon.className = 'fa fa-heart';
                icon.style.color = '';
            }
        }
    });
    
    function updateUrl() {
        const params = new URLSearchParams();
        
        filters.forEach(filter => {
            if (filter.value) {
                const paramName = filter.id.replace('-filter', '').replace('-by', '');
                params.set(paramName, filter.value);
            }
        });
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }
});
</script>

<?php get_footer(); ?>
