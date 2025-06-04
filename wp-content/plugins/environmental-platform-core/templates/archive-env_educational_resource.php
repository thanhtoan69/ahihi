<?php
/**
 * Archive Educational Resources Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="educational-resources-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-graduation-cap"></i>
                        <?php _e('Educational Resources', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Expand your environmental knowledge with our comprehensive collection of educational materials, guides, and learning resources.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Resource Categories -->
                <div class="resource-categories-overview">
                    <?php
                    $categories = array(
                        'climate-change' => array(
                            'name' => __('Climate Change', 'environmental-platform-core'),
                            'icon' => 'fa-thermometer-three-quarters',
                            'description' => __('Understanding climate science and impacts', 'environmental-platform-core')
                        ),
                        'conservation' => array(
                            'name' => __('Conservation', 'environmental-platform-core'),
                            'icon' => 'fa-leaf',
                            'description' => __('Wildlife and habitat preservation', 'environmental-platform-core')
                        ),
                        'renewable-energy' => array(
                            'name' => __('Renewable Energy', 'environmental-platform-core'),
                            'icon' => 'fa-solar-panel',
                            'description' => __('Sustainable energy solutions', 'environmental-platform-core')
                        ),
                        'waste-management' => array(
                            'name' => __('Waste Management', 'environmental-platform-core'),
                            'icon' => 'fa-recycle',
                            'description' => __('Reducing, reusing, and recycling', 'environmental-platform-core')
                        ),
                        'sustainable-living' => array(
                            'name' => __('Sustainable Living', 'environmental-platform-core'),
                            'icon' => 'fa-home',
                            'description' => __('Eco-friendly lifestyle choices', 'environmental-platform-core')
                        ),
                        'pollution' => array(
                            'name' => __('Pollution Prevention', 'environmental-platform-core'),
                            'icon' => 'fa-industry',
                            'description' => __('Air, water, and soil protection', 'environmental-platform-core')
                        )
                    );
                    
                    $category_stats = array();
                    foreach ($categories as $cat_key => $cat_info) {
                        $cat_query = new WP_Query(array(
                            'post_type' => 'env_educational_resource',
                            'meta_query' => array(
                                array(
                                    'key' => '_resource_category',
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
                    
                    <div class="categories-grid">
                        <?php foreach ($categories as $cat_key => $cat_info): ?>
                            <div class="category-card" data-category="<?php echo esc_attr($cat_key); ?>">
                                <div class="category-icon">
                                    <i class="fa <?php echo $cat_info['icon']; ?>"></i>
                                </div>
                                <h3 class="category-name"><?php echo $cat_info['name']; ?></h3>
                                <p class="category-description"><?php echo $cat_info['description']; ?></p>
                                <span class="resource-count"><?php echo $category_stats[$cat_key]; ?> <?php _e('resources', 'environmental-platform-core'); ?></span>
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
                                <?php foreach ($categories as $cat_key => $cat_info): ?>
                                    <option value="<?php echo esc_attr($cat_key); ?>" <?php selected(isset($_GET['category']) && $_GET['category'] == $cat_key); ?>><?php echo $cat_info['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="type-filter"><?php _e('Resource Type:', 'environmental-platform-core'); ?></label>
                            <select id="type-filter" class="form-control">
                                <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                                <option value="guide" <?php selected(isset($_GET['type']) && $_GET['type'] == 'guide'); ?>><?php _e('Guides', 'environmental-platform-core'); ?></option>
                                <option value="video" <?php selected(isset($_GET['type']) && $_GET['type'] == 'video'); ?>><?php _e('Videos', 'environmental-platform-core'); ?></option>
                                <option value="infographic" <?php selected(isset($_GET['type']) && $_GET['type'] == 'infographic'); ?>><?php _e('Infographics', 'environmental-platform-core'); ?></option>
                                <option value="research" <?php selected(isset($_GET['type']) && $_GET['type'] == 'research'); ?>><?php _e('Research Papers', 'environmental-platform-core'); ?></option>
                                <option value="course" <?php selected(isset($_GET['type']) && $_GET['type'] == 'course'); ?>><?php _e('Courses', 'environmental-platform-core'); ?></option>
                                <option value="worksheet" <?php selected(isset($_GET['type']) && $_GET['type'] == 'worksheet'); ?>><?php _e('Worksheets', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="level-filter"><?php _e('Difficulty Level:', 'environmental-platform-core'); ?></label>
                            <select id="level-filter" class="form-control">
                                <option value=""><?php _e('All Levels', 'environmental-platform-core'); ?></option>
                                <option value="beginner" <?php selected(isset($_GET['level']) && $_GET['level'] == 'beginner'); ?>><?php _e('Beginner', 'environmental-platform-core'); ?></option>
                                <option value="intermediate" <?php selected(isset($_GET['level']) && $_GET['level'] == 'intermediate'); ?>><?php _e('Intermediate', 'environmental-platform-core'); ?></option>
                                <option value="advanced" <?php selected(isset($_GET['level']) && $_GET['level'] == 'advanced'); ?>><?php _e('Advanced', 'environmental-platform-core'); ?></option>
                                <option value="expert" <?php selected(isset($_GET['level']) && $_GET['level'] == 'expert'); ?>><?php _e('Expert', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="duration-filter"><?php _e('Duration:', 'environmental-platform-core'); ?></label>
                            <select id="duration-filter" class="form-control">
                                <option value=""><?php _e('Any Duration', 'environmental-platform-core'); ?></option>
                                <option value="0-15" <?php selected(isset($_GET['duration']) && $_GET['duration'] == '0-15'); ?>><?php _e('0-15 minutes', 'environmental-platform-core'); ?></option>
                                <option value="15-30" <?php selected(isset($_GET['duration']) && $_GET['duration'] == '15-30'); ?>><?php _e('15-30 minutes', 'environmental-platform-core'); ?></option>
                                <option value="30-60" <?php selected(isset($_GET['duration']) && $_GET['duration'] == '30-60'); ?>><?php _e('30-60 minutes', 'environmental-platform-core'); ?></option>
                                <option value="60+" <?php selected(isset($_GET['duration']) && $_GET['duration'] == '60+'); ?>><?php _e('1+ hours', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="sort-group">
                            <label for="sort-by"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                            <select id="sort-by" class="form-control">
                                <option value="date" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'date'); ?>><?php _e('Newest First', 'environmental-platform-core'); ?></option>
                                <option value="title" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'title'); ?>><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                                <option value="popular" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'popular'); ?>><?php _e('Most Popular', 'environmental-platform-core'); ?></option>
                                <option value="rating" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'rating'); ?>><?php _e('Highest Rated', 'environmental-platform-core'); ?></option>
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
                
                <!-- Resources Grid -->
                <div class="resources-container">
                    <div class="resources-grid grid-layout" id="resources-grid">
                        <?php
                        // Build query args based on filters
                        $query_args = array(
                            'post_type' => 'env_educational_resource',
                            'posts_per_page' => 12,
                            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                        );
                        
                        $meta_query = array('relation' => 'AND');
                        
                        if (isset($_GET['category']) && !empty($_GET['category'])) {
                            $meta_query[] = array(
                                'key' => '_resource_category',
                                'value' => sanitize_text_field($_GET['category']),
                                'compare' => '='
                            );
                        }
                        
                        if (isset($_GET['type']) && !empty($_GET['type'])) {
                            $meta_query[] = array(
                                'key' => '_resource_type',
                                'value' => sanitize_text_field($_GET['type']),
                                'compare' => '='
                            );
                        }
                        
                        if (isset($_GET['level']) && !empty($_GET['level'])) {
                            $meta_query[] = array(
                                'key' => '_difficulty_level',
                                'value' => sanitize_text_field($_GET['level']),
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
                                case 'popular':
                                    $query_args['meta_key'] = '_view_count';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                                case 'rating':
                                    $query_args['meta_key'] = '_average_rating';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                            }
                        }
                        
                        $resources_query = new WP_Query($query_args);
                        
                        if ($resources_query->have_posts()) :
                            while ($resources_query->have_posts()) : $resources_query->the_post();
                                $category = get_post_meta(get_the_ID(), '_resource_category', true);
                                $type = get_post_meta(get_the_ID(), '_resource_type', true);
                                $level = get_post_meta(get_the_ID(), '_difficulty_level', true);
                                $duration = get_post_meta(get_the_ID(), '_estimated_duration', true);
                                $downloads = get_post_meta(get_the_ID(), '_download_count', true) ?: 0;
                                $rating = get_post_meta(get_the_ID(), '_average_rating', true) ?: 0;
                                $is_premium = get_post_meta(get_the_ID(), '_is_premium', true);
                                $file_size = get_post_meta(get_the_ID(), '_file_size', true);
                        ?>
                            <article class="resource-card" data-category="<?php echo esc_attr($category); ?>" data-type="<?php echo esc_attr($type); ?>" data-level="<?php echo esc_attr($level); ?>">
                                <div class="resource-card-inner">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="resource-image">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                                            </a>
                                            
                                            <?php if ($type): ?>
                                                <div class="resource-type-badge">
                                                    <?php
                                                    $type_icons = array(
                                                        'guide' => 'fa-book',
                                                        'video' => 'fa-play',
                                                        'infographic' => 'fa-image',
                                                        'research' => 'fa-file-alt',
                                                        'course' => 'fa-graduation-cap',
                                                        'worksheet' => 'fa-file-pdf'
                                                    );
                                                    ?>
                                                    <i class="fa <?php echo $type_icons[$type] ?? 'fa-file'; ?>"></i>
                                                    <?php echo ucfirst($type); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($is_premium): ?>
                                                <div class="premium-badge">
                                                    <i class="fa fa-crown"></i>
                                                    <?php _e('Premium', 'environmental-platform-core'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($level): ?>
                                                <div class="difficulty-badge level-<?php echo esc_attr($level); ?>">
                                                    <?php echo ucfirst($level); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="resource-content">
                                        <?php if ($category): ?>
                                            <span class="resource-category">
                                                <?php echo esc_html($categories[$category]['name'] ?? $category); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <h3 class="resource-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <?php if ($rating > 0): ?>
                                            <div class="resource-rating">
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="rating-text"><?php echo $rating; ?>/5</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="resource-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                        </div>
                                        
                                        <div class="resource-meta">
                                            <?php if ($duration): ?>
                                                <span class="meta-item duration">
                                                    <i class="fa fa-clock"></i>
                                                    <?php echo esc_html($duration); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($file_size): ?>
                                                <span class="meta-item file-size">
                                                    <i class="fa fa-download"></i>
                                                    <?php echo esc_html($file_size); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <span class="meta-item downloads">
                                                <i class="fa fa-download"></i>
                                                <?php echo number_format($downloads); ?> <?php _e('downloads', 'environmental-platform-core'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="resource-actions">
                                            <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                                <?php _e('View Resource', 'environmental-platform-core'); ?>
                                            </a>
                                            
                                            <?php if (!$is_premium || (is_user_logged_in() && current_user_can('access_premium'))): ?>
                                                <button type="button" class="btn btn-secondary download-btn" data-resource-id="<?php the_ID(); ?>">
                                                    <i class="fa fa-download"></i>
                                                    <?php _e('Download', 'environmental-platform-core'); ?>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-outline-secondary bookmark-btn" data-resource-id="<?php the_ID(); ?>">
                                                <i class="fa fa-bookmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php
                            endwhile;
                        else :
                        ?>
                            <div class="no-resources-found">
                                <i class="fa fa-search"></i>
                                <h3><?php _e('No resources found', 'environmental-platform-core'); ?></h3>
                                <p><?php _e('Try adjusting your filters or browse all categories.', 'environmental-platform-core'); ?></p>
                                <button type="button" class="btn btn-primary" id="clear-filters">
                                    <?php _e('Clear Filters', 'environmental-platform-core'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($resources_query->max_num_pages > 1): ?>
                        <div class="archive-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $resources_query->max_num_pages,
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
                    <!-- Featured Resources -->
                    <div class="sidebar-widget featured-resources">
                        <h3 class="widget-title"><?php _e('Featured Resources', 'environmental-platform-core'); ?></h3>
                        <div class="featured-resources-list">
                            <?php
                            $featured_query = new WP_Query(array(
                                'post_type' => 'env_educational_resource',
                                'posts_per_page' => 3,
                                'meta_query' => array(
                                    array(
                                        'key' => '_is_featured',
                                        'value' => '1',
                                        'compare' => '='
                                    )
                                )
                            ));
                            
                            while ($featured_query->have_posts()) : $featured_query->the_post();
                                $type = get_post_meta(get_the_ID(), '_resource_type', true);
                                $rating = get_post_meta(get_the_ID(), '_average_rating', true);
                            ?>
                                <div class="featured-resource-item">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="featured-resource-image">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="featured-resource-content">
                                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                        <?php if ($type): ?>
                                            <span class="resource-type"><?php echo ucfirst($type); ?></span>
                                        <?php endif; ?>
                                        <?php if ($rating): ?>
                                            <div class="rating-mini">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    
                    <!-- Learning Paths -->
                    <div class="sidebar-widget learning-paths">
                        <h3 class="widget-title"><?php _e('Learning Paths', 'environmental-platform-core'); ?></h3>
                        <div class="paths-list">
                            <div class="path-item">
                                <h4><?php _e('Climate Science Basics', 'environmental-platform-core'); ?></h4>
                                <p><?php _e('Start with fundamentals of climate change science', 'environmental-platform-core'); ?></p>
                                <span class="path-progress">5 <?php _e('resources', 'environmental-platform-core'); ?></span>
                            </div>
                            <div class="path-item">
                                <h4><?php _e('Sustainable Living Guide', 'environmental-platform-core'); ?></h4>
                                <p><?php _e('Learn practical steps for eco-friendly living', 'environmental-platform-core'); ?></p>
                                <span class="path-progress">8 <?php _e('resources', 'environmental-platform-core'); ?></span>
                            </div>
                            <div class="path-item">
                                <h4><?php _e('Renewable Energy 101', 'environmental-platform-core'); ?></h4>
                                <p><?php _e('Understanding clean energy technologies', 'environmental-platform-core'); ?></p>
                                <span class="path-progress">6 <?php _e('resources', 'environmental-platform-core'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resource Statistics -->
                    <div class="sidebar-widget resource-stats">
                        <h3 class="widget-title"><?php _e('Resource Statistics', 'environmental-platform-core'); ?></h3>
                        <?php
                        $total_resources = wp_count_posts('env_educational_resource')->publish;
                        $total_downloads = $wpdb->get_var("
                            SELECT SUM(CAST(meta_value AS UNSIGNED)) 
                            FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_download_count'
                        ");
                        $avg_rating_query = $wpdb->get_var("
                            SELECT AVG(CAST(meta_value AS DECIMAL(2,1))) 
                            FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_average_rating' 
                            AND meta_value != ''
                        ");
                        $avg_rating = round($avg_rating_query, 1);
                        ?>
                        <div class="stats-list">
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Total Resources:', 'environmental-platform-core'); ?></span>
                                <span class="stat-value"><?php echo number_format($total_resources); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label"><?php _e('Total Downloads:', 'environmental-platform-core'); ?></span>
                                <span class="stat-value"><?php echo number_format($total_downloads ?: 0); ?></span>
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
                    
                    <!-- Newsletter Signup -->
                    <div class="sidebar-widget newsletter-signup">
                        <h3 class="widget-title"><?php _e('Educational Updates', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Get notified about new educational resources and learning opportunities.', 'environmental-platform-core'); ?></p>
                        <form class="newsletter-form" id="education-newsletter-form">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="<?php _e('Your email address', 'environmental-platform-core'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php _e('Subscribe', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<style>
.educational-resources-archive {
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

.resource-categories-overview {
    margin-bottom: 2rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.category-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-card:hover,
.category-card.active {
    border-color: #28a745;
    background: #f8fff8;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
}

.category-icon {
    font-size: 2.5rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.category-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.category-description {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.resource-count {
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
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

.filter-group,
.sort-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label,
.sort-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
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

.resources-grid {
    display: grid;
    gap: 2rem;
    margin-bottom: 2rem;
}

.grid-layout {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.list-layout {
    grid-template-columns: 1fr;
}

.list-layout .resource-card {
    display: flex;
    align-items: center;
}

.list-layout .resource-image {
    flex-shrink: 0;
    width: 200px;
    margin-right: 1.5rem;
}

.resource-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.resource-image {
    position: relative;
    overflow: hidden;
}

.resource-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.resource-card:hover .resource-image img {
    transform: scale(1.05);
}

.resource-type-badge {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    background: rgba(40, 167, 69, 0.9);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.premium-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #ffc107;
    color: #212529;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.difficulty-badge {
    position: absolute;
    bottom: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.difficulty-badge.level-beginner {
    background: #d4edda;
    color: #155724;
}

.difficulty-badge.level-intermediate {
    background: #fff3cd;
    color: #856404;
}

.difficulty-badge.level-advanced {
    background: #f8d7da;
    color: #721c24;
}

.difficulty-badge.level-expert {
    background: #d1ecf1;
    color: #0c5460;
}

.resource-content {
    padding: 1.5rem;
}

.resource-category {
    color: #28a745;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.resource-title {
    margin: 0.5rem 0;
    font-size: 1.25rem;
    line-height: 1.3;
}

.resource-title a {
    color: #333;
    text-decoration: none;
}

.resource-title a:hover {
    color: #28a745;
}

.resource-rating {
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

.resource-excerpt {
    color: #666;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.resource-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #666;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.resource-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.bookmark-btn {
    padding: 0.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-resources-found {
    text-align: center;
    padding: 3rem;
    color: #666;
    grid-column: 1 / -1;
}

.no-resources-found i {
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

.featured-resource-item {
    display: flex;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.featured-resource-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.featured-resource-image {
    flex-shrink: 0;
    width: 60px;
    margin-right: 1rem;
}

.featured-resource-image img {
    width: 100%;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.featured-resource-content h4 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.featured-resource-content a {
    color: #333;
    text-decoration: none;
}

.featured-resource-content a:hover {
    color: #28a745;
}

.resource-type {
    display: block;
    color: #666;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
}

.rating-mini .star {
    font-size: 0.75rem;
}

.path-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.path-item:last-child {
    margin-bottom: 0;
}

.path-item h4 {
    margin-bottom: 0.5rem;
    color: #2c5c3e;
}

.path-item p {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.path-progress {
    background: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
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

.newsletter-form .form-group {
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 2rem;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group,
    .sort-group {
        min-width: auto;
    }
    
    .resources-grid.grid-layout {
        grid-template-columns: 1fr;
    }
    
    .list-layout .resource-card {
        flex-direction: column;
    }
    
    .list-layout .resource-image {
        width: 100%;
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .resource-actions {
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filters = document.querySelectorAll('#category-filter, #type-filter, #level-filter, #duration-filter, #sort-by');
    const categoryCards = document.querySelectorAll('.category-card');
    const viewButtons = document.querySelectorAll('.view-btn');
    const resourcesGrid = document.getElementById('resources-grid');
    const clearFiltersBtn = document.getElementById('clear-filters');
    
    // Category card clicks
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            const category = this.dataset.category;
            document.getElementById('category-filter').value = category;
            updateUrl();
            categoryCards.forEach(c => c.classList.remove('active'));
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
            
            resourcesGrid.className = resourcesGrid.className.replace(/\b(grid|list)-layout\b/g, '');
            resourcesGrid.classList.add(`${view}-layout`);
        });
    });
    
    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            filters.forEach(filter => filter.value = '');
            categoryCards.forEach(card => card.classList.remove('active'));
            updateUrl();
        });
    }
    
    // Download functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.download-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.download-btn');
            const resourceId = btn.dataset.resourceId;
            
            // Here you would typically make an AJAX call to track download
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php _e("Downloading...", "environmental-platform-core"); ?>';
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fa fa-check"></i> <?php _e("Downloaded", "environmental-platform-core"); ?>';
                btn.disabled = true;
            }, 2000);
        }
    });
    
    // Bookmark functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.bookmark-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.bookmark-btn');
            const resourceId = btn.dataset.resourceId;
            
            btn.classList.toggle('active');
            
            const icon = btn.querySelector('i');
            if (btn.classList.contains('active')) {
                icon.className = 'fa fa-bookmark';
                icon.style.color = '#ffc107';
            } else {
                icon.className = 'fa fa-bookmark';
                icon.style.color = '';
            }
        }
    });
    
    // Newsletter subscription
    const newsletterForm = document.getElementById('education-newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Here you would typically make an AJAX call to subscribe
            alert('<?php _e("Thank you for subscribing!", "environmental-platform-core"); ?>');
            this.reset();
        });
    }
    
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
