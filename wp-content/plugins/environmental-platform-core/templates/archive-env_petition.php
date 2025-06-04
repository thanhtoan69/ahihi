<?php
/**
 * Archive Environmental Petitions Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="environmental-petitions-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-bullhorn"></i>
                        <?php _e('Environmental Petitions', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Make your voice heard! Join ongoing environmental campaigns and petitions to create positive change for our planet.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Petition Impact Statistics -->
                <div class="petition-impact-stats">
                    <?php
                    $total_signatures = $wpdb->get_var("
                        SELECT SUM(CAST(meta_value AS UNSIGNED)) 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = '_current_signatures'
                    ");
                    
                    $active_petitions = new WP_Query(array(
                        'post_type' => 'env_petition',
                        'meta_query' => array(
                            array(
                                'key' => '_petition_status',
                                'value' => 'active',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    
                    $successful_petitions = new WP_Query(array(
                        'post_type' => 'env_petition',
                        'meta_query' => array(
                            array(
                                'key' => '_petition_status',
                                'value' => 'successful',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    
                    $recent_victories = new WP_Query(array(
                        'post_type' => 'env_petition',
                        'meta_query' => array(
                            array(
                                'key' => '_petition_status',
                                'value' => 'successful',
                                'compare' => '='
                            ),
                            array(
                                'key' => '_victory_date',
                                'value' => date('Y-m-d', strtotime('-30 days')),
                                'compare' => '>='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    ?>
                    
                    <div class="impact-overview">
                        <div class="impact-stat">
                            <i class="fa fa-signature"></i>
                            <span class="stat-number"><?php echo number_format($total_signatures ?: 0); ?></span>
                            <span class="stat-label"><?php _e('Total Signatures', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="impact-stat">
                            <i class="fa fa-file-signature"></i>
                            <span class="stat-number"><?php echo $active_petitions->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Active Petitions', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="impact-stat">
                            <i class="fa fa-trophy"></i>
                            <span class="stat-number"><?php echo $successful_petitions->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Victories', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="impact-stat">
                            <i class="fa fa-calendar-check"></i>
                            <span class="stat-number"><?php echo $recent_victories->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Recent Wins', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="action-buttons">
                        <a href="#create-petition" class="btn btn-primary">
                            <i class="fa fa-plus"></i>
                            <?php _e('Start a Petition', 'environmental-platform-core'); ?>
                        </a>
                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#shareModal">
                            <i class="fa fa-share"></i>
                            <?php _e('Share Campaigns', 'environmental-platform-core'); ?>
                        </button>
                        <a href="#urgent-petitions" class="btn btn-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php _e('Urgent Actions', 'environmental-platform-core'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status-filter"><?php _e('Status:', 'environmental-platform-core'); ?></label>
                            <select id="status-filter" class="form-control">
                                <option value=""><?php _e('All Status', 'environmental-platform-core'); ?></option>
                                <option value="active" <?php selected(isset($_GET['status']) && $_GET['status'] == 'active'); ?>><?php _e('Active', 'environmental-platform-core'); ?></option>
                                <option value="urgent" <?php selected(isset($_GET['status']) && $_GET['status'] == 'urgent'); ?>><?php _e('Urgent', 'environmental-platform-core'); ?></option>
                                <option value="successful" <?php selected(isset($_GET['status']) && $_GET['status'] == 'successful'); ?>><?php _e('Successful', 'environmental-platform-core'); ?></option>
                                <option value="closed" <?php selected(isset($_GET['status']) && $_GET['status'] == 'closed'); ?>><?php _e('Closed', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="category-filter"><?php _e('Category:', 'environmental-platform-core'); ?></label>
                            <select id="category-filter" class="form-control">
                                <option value=""><?php _e('All Categories', 'environmental-platform-core'); ?></option>
                                <option value="climate-action" <?php selected(isset($_GET['category']) && $_GET['category'] == 'climate-action'); ?>><?php _e('Climate Action', 'environmental-platform-core'); ?></option>
                                <option value="wildlife-protection" <?php selected(isset($_GET['category']) && $_GET['category'] == 'wildlife-protection'); ?>><?php _e('Wildlife Protection', 'environmental-platform-core'); ?></option>
                                <option value="pollution-control" <?php selected(isset($_GET['category']) && $_GET['category'] == 'pollution-control'); ?>><?php _e('Pollution Control', 'environmental-platform-core'); ?></option>
                                <option value="conservation" <?php selected(isset($_GET['category']) && $_GET['category'] == 'conservation'); ?>><?php _e('Conservation', 'environmental-platform-core'); ?></option>
                                <option value="renewable-energy" <?php selected(isset($_GET['category']) && $_GET['category'] == 'renewable-energy'); ?>><?php _e('Renewable Energy', 'environmental-platform-core'); ?></option>
                                <option value="policy-change" <?php selected(isset($_GET['category']) && $_GET['category'] == 'policy-change'); ?>><?php _e('Policy Change', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="location-filter"><?php _e('Location:', 'environmental-platform-core'); ?></label>
                            <select id="location-filter" class="form-control">
                                <option value=""><?php _e('All Locations', 'environmental-platform-core'); ?></option>
                                <option value="local" <?php selected(isset($_GET['location']) && $_GET['location'] == 'local'); ?>><?php _e('Local', 'environmental-platform-core'); ?></option>
                                <option value="national" <?php selected(isset($_GET['location']) && $_GET['location'] == 'national'); ?>><?php _e('National', 'environmental-platform-core'); ?></option>
                                <option value="international" <?php selected(isset($_GET['location']) && $_GET['location'] == 'international'); ?>><?php _e('International', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="sort-group">
                            <label for="sort-by"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                            <select id="sort-by" class="form-control">
                                <option value="date" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'date'); ?>><?php _e('Most Recent', 'environmental-platform-core'); ?></option>
                                <option value="signatures" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'signatures'); ?>><?php _e('Most Signatures', 'environmental-platform-core'); ?></option>
                                <option value="deadline" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'deadline'); ?>><?php _e('Closing Soon', 'environmental-platform-core'); ?></option>
                                <option value="progress" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'progress'); ?>><?php _e('Most Progress', 'environmental-platform-core'); ?></option>
                                <option value="title" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'title'); ?>><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Petitions List -->
                <div class="petitions-container">
                    <div class="petitions-list">
                        <?php
                        // Build query args based on filters
                        $query_args = array(
                            'post_type' => 'env_petition',
                            'posts_per_page' => 10,
                            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                        );
                        
                        $meta_query = array('relation' => 'AND');
                        
                        if (isset($_GET['status']) && !empty($_GET['status'])) {
                            $meta_query[] = array(
                                'key' => '_petition_status',
                                'value' => sanitize_text_field($_GET['status']),
                                'compare' => '='
                            );
                        }
                        
                        if (isset($_GET['category']) && !empty($_GET['category'])) {
                            $meta_query[] = array(
                                'key' => '_petition_category',
                                'value' => sanitize_text_field($_GET['category']),
                                'compare' => '='
                            );
                        }
                        
                        if (isset($_GET['location']) && !empty($_GET['location'])) {
                            $meta_query[] = array(
                                'key' => '_petition_scope',
                                'value' => sanitize_text_field($_GET['location']),
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
                                case 'signatures':
                                    $query_args['meta_key'] = '_current_signatures';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                                case 'deadline':
                                    $query_args['meta_key'] = '_deadline_date';
                                    $query_args['orderby'] = 'meta_value';
                                    $query_args['order'] = 'ASC';
                                    break;
                                case 'progress':
                                    $query_args['meta_key'] = '_progress_percentage';
                                    $query_args['orderby'] = 'meta_value_num';
                                    $query_args['order'] = 'DESC';
                                    break;
                            }
                        }
                        
                        $petitions_query = new WP_Query($query_args);
                        
                        if ($petitions_query->have_posts()) :
                            while ($petitions_query->have_posts()) : $petitions_query->the_post();
                                $status = get_post_meta(get_the_ID(), '_petition_status', true);
                                $category = get_post_meta(get_the_ID(), '_petition_category', true);
                                $target_signatures = get_post_meta(get_the_ID(), '_target_signatures', true);
                                $current_signatures = get_post_meta(get_the_ID(), '_current_signatures', true) ?: 0;
                                $deadline = get_post_meta(get_the_ID(), '_deadline_date', true);
                                $target_authority = get_post_meta(get_the_ID(), '_target_authority', true);
                                $location = get_post_meta(get_the_ID(), '_petition_location', true);
                                $urgency_level = get_post_meta(get_the_ID(), '_urgency_level', true);
                                
                                $progress_percentage = $target_signatures ? min(100, ($current_signatures / $target_signatures) * 100) : 0;
                                $days_left = $deadline ? max(0, ceil((strtotime($deadline) - current_time('timestamp')) / DAY_IN_SECONDS)) : null;
                        ?>
                            <article class="petition-card" data-status="<?php echo esc_attr($status); ?>" data-category="<?php echo esc_attr($category); ?>">
                                <div class="petition-card-inner">
                                    <div class="petition-header">
                                        <div class="petition-badges">
                                            <?php if ($status): ?>
                                                <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                                    <?php
                                                    $status_labels = array(
                                                        'active' => __('Active', 'environmental-platform-core'),
                                                        'urgent' => __('Urgent', 'environmental-platform-core'),
                                                        'successful' => __('Successful', 'environmental-platform-core'),
                                                        'closed' => __('Closed', 'environmental-platform-core')
                                                    );
                                                    echo $status_labels[$status] ?? ucfirst($status);
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($urgency_level === 'high'): ?>
                                                <span class="urgency-badge">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <?php _e('High Priority', 'environmental-platform-core'); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($days_left !== null && $days_left <= 7 && $days_left > 0): ?>
                                                <span class="deadline-badge">
                                                    <i class="fa fa-clock"></i>
                                                    <?php printf(__('%d days left', 'environmental-platform-core'), $days_left); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($category): ?>
                                                <span class="category-badge">
                                                    <?php echo esc_html(str_replace('-', ' ', ucwords($category, '-'))); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h3 class="petition-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <?php if ($target_authority): ?>
                                            <div class="petition-target">
                                                <i class="fa fa-bullseye"></i>
                                                <?php _e('Target:', 'environmental-platform-core'); ?>
                                                <strong><?php echo esc_html($target_authority); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($location): ?>
                                            <div class="petition-location">
                                                <i class="fa fa-map-marker-alt"></i>
                                                <?php echo esc_html($location); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="petition-content">
                                        <div class="petition-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                                        </div>
                                        
                                        <?php if ($target_signatures): ?>
                                            <div class="petition-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                                </div>
                                                <div class="progress-stats">
                                                    <span class="current-signatures">
                                                        <strong><?php echo number_format($current_signatures); ?></strong> 
                                                        <?php _e('signatures', 'environmental-platform-core'); ?>
                                                    </span>
                                                    <span class="target-signatures">
                                                        <?php printf(__('Goal: %s', 'environmental-platform-core'), number_format($target_signatures)); ?>
                                                    </span>
                                                    <span class="progress-percentage">
                                                        <?php echo round($progress_percentage, 1); ?>%
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="petition-meta">
                                            <span class="petition-author">
                                                <?php _e('Started by', 'environmental-platform-core'); ?>
                                                <strong><?php the_author(); ?></strong>
                                            </span>
                                            <span class="petition-date">
                                                <i class="fa fa-calendar"></i>
                                                <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?>
                                            </span>
                                            <?php if ($deadline): ?>
                                                <span class="petition-deadline">
                                                    <i class="fa fa-flag-checkered"></i>
                                                    <?php _e('Ends:', 'environmental-platform-core'); ?>
                                                    <?php echo date_i18n(get_option('date_format'), strtotime($deadline)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="petition-actions">
                                        <?php if ($status === 'active' || $status === 'urgent'): ?>
                                            <a href="<?php the_permalink(); ?>" class="btn btn-primary sign-btn">
                                                <i class="fa fa-signature"></i>
                                                <?php _e('Sign Petition', 'environmental-platform-core'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php the_permalink(); ?>" class="btn btn-secondary">
                                                <?php _e('View Details', 'environmental-platform-core'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-outline-secondary share-btn" data-petition-id="<?php the_ID(); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" data-url="<?php echo esc_url(get_permalink()); ?>">
                                            <i class="fa fa-share"></i>
                                            <?php _e('Share', 'environmental-platform-core'); ?>
                                        </button>
                                        
                                        <?php if (is_user_logged_in()): ?>
                                            <button type="button" class="btn btn-outline-secondary bookmark-btn" data-petition-id="<?php the_ID(); ?>">
                                                <i class="fa fa-bookmark"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php
                            endwhile;
                        else :
                        ?>
                            <div class="no-petitions-found">
                                <i class="fa fa-bullhorn"></i>
                                <h3><?php _e('No petitions found', 'environmental-platform-core'); ?></h3>
                                <p><?php _e('Try adjusting your filters or start a new petition for your cause.', 'environmental-platform-core'); ?></p>
                                <a href="#create-petition" class="btn btn-primary">
                                    <?php _e('Start a Petition', 'environmental-platform-core'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($petitions_query->max_num_pages > 1): ?>
                        <div class="archive-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $petitions_query->max_num_pages,
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
                    <!-- Success Stories -->
                    <div class="sidebar-widget success-stories">
                        <h3 class="widget-title"><?php _e('Recent Victories', 'environmental-platform-core'); ?></h3>
                        <div class="success-list">
                            <?php
                            $success_query = new WP_Query(array(
                                'post_type' => 'env_petition',
                                'posts_per_page' => 3,
                                'meta_query' => array(
                                    array(
                                        'key' => '_petition_status',
                                        'value' => 'successful',
                                        'compare' => '='
                                    )
                                ),
                                'orderby' => 'meta_value',
                                'meta_key' => '_victory_date',
                                'order' => 'DESC'
                            ));
                            
                            while ($success_query->have_posts()) : $success_query->the_post();
                                $signatures = get_post_meta(get_the_ID(), '_current_signatures', true);
                                $victory_date = get_post_meta(get_the_ID(), '_victory_date', true);
                            ?>
                                <div class="success-item">
                                    <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                    <div class="success-stats">
                                        <span class="signatures"><?php echo number_format($signatures ?: 0); ?> <?php _e('signatures', 'environmental-platform-core'); ?></span>
                                        <?php if ($victory_date): ?>
                                            <span class="victory-date"><?php echo date_i18n('M Y', strtotime($victory_date)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    
                    <!-- Trending Petitions -->
                    <div class="sidebar-widget trending-petitions">
                        <h3 class="widget-title"><?php _e('Trending Now', 'environmental-platform-core'); ?></h3>
                        <div class="trending-list">
                            <?php
                            $trending_query = new WP_Query(array(
                                'post_type' => 'env_petition',
                                'posts_per_page' => 5,
                                'meta_query' => array(
                                    array(
                                        'key' => '_petition_status',
                                        'value' => array('active', 'urgent'),
                                        'compare' => 'IN'
                                    )
                                ),
                                'meta_key' => '_daily_signatures',
                                'orderby' => 'meta_value_num',
                                'order' => 'DESC'
                            ));
                            
                            while ($trending_query->have_posts()) : $trending_query->the_post();
                                $current_signatures = get_post_meta(get_the_ID(), '_current_signatures', true);
                                $daily_signatures = get_post_meta(get_the_ID(), '_daily_signatures', true);
                            ?>
                                <div class="trending-item">
                                    <a href="<?php the_permalink(); ?>" class="trending-title"><?php the_title(); ?></a>
                                    <div class="trending-stats">
                                        <span class="current"><?php echo number_format($current_signatures ?: 0); ?></span>
                                        <span class="daily">+<?php echo number_format($daily_signatures ?: 0); ?> <?php _e('today', 'environmental-platform-core'); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    
                    <!-- Petition Tips -->
                    <div class="sidebar-widget petition-tips">
                        <h3 class="widget-title"><?php _e('Petition Tips', 'environmental-platform-core'); ?></h3>
                        <div class="tips-content">
                            <div class="tip-item">
                                <i class="fa fa-lightbulb"></i>
                                <div>
                                    <strong><?php _e('Be Specific', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Clearly state what action you want taken and by whom.', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <i class="fa fa-users"></i>
                                <div>
                                    <strong><?php _e('Build Support', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Share your petition with friends, family, and social networks.', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            <div class="tip-item">
                                <i class="fa fa-chart-line"></i>
                                <div>
                                    <strong><?php _e('Track Progress', 'environmental-platform-core'); ?></strong>
                                    <p><?php _e('Keep supporters updated with regular campaign updates.', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Newsletter Signup -->
                    <div class="sidebar-widget newsletter-signup">
                        <h3 class="widget-title"><?php _e('Campaign Updates', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Stay informed about new petitions and campaign victories.', 'environmental-platform-core'); ?></p>
                        <form class="newsletter-form" id="petition-newsletter-form">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="<?php _e('Your email address', 'environmental-platform-core'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php _e('Get Updates', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<style>
.environmental-petitions-archive {
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

.petition-impact-stats {
    margin-bottom: 2rem;
}

.impact-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 12px;
    padding: 2rem;
    color: white;
}

.impact-stat {
    text-align: center;
}

.impact-stat i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
    opacity: 0.9;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.quick-actions {
    margin-bottom: 2rem;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
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

.petitions-list {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.petition-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.petition-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.petition-card-inner {
    padding: 2rem;
}

.petition-header {
    margin-bottom: 1.5rem;
}

.petition-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.status-badge,
.urgency-badge,
.deadline-badge,
.category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.status-active {
    background: #d4edda;
    color: #155724;
}

.status-badge.status-urgent {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.status-successful {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.status-closed {
    background: #f8f9fa;
    color: #6c757d;
}

.urgency-badge {
    background: #fff3cd;
    color: #856404;
}

.deadline-badge {
    background: #ffeaa7;
    color: #2d3436;
}

.category-badge {
    background: #e3f2fd;
    color: #1976d2;
}

.petition-title {
    font-size: 1.5rem;
    line-height: 1.3;
    margin-bottom: 1rem;
}

.petition-title a {
    color: #333;
    text-decoration: none;
}

.petition-title a:hover {
    color: #28a745;
}

.petition-target,
.petition-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.petition-target i,
.petition-location i {
    width: 16px;
    text-align: center;
    color: #28a745;
}

.petition-content {
    margin-bottom: 1.5rem;
}

.petition-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.petition-progress {
    margin-bottom: 1rem;
}

.progress-bar {
    background: #e9ecef;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    background: linear-gradient(90deg, #28a745, #20c997);
    height: 100%;
    transition: width 0.3s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.current-signatures {
    color: #28a745;
    font-weight: 600;
}

.target-signatures {
    color: #666;
}

.progress-percentage {
    color: #333;
    font-weight: 600;
}

.petition-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: #666;
}

.petition-meta i {
    margin-right: 0.25rem;
}

.petition-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.sign-btn {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    color: white;
    font-weight: 600;
}

.sign-btn:hover {
    background: linear-gradient(45deg, #218838, #1ea085);
    color: white;
}

.no-petitions-found {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-petitions-found i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ddd;
}

.sidebar-widget {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.widget-title {
    margin-bottom: 1rem;
    color: #2c5c3e;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.success-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.success-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.success-item h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.success-item a {
    color: #333;
    text-decoration: none;
}

.success-item a:hover {
    color: #28a745;
}

.success-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.signatures {
    color: #28a745;
    font-weight: 600;
}

.victory-date {
    color: #666;
}

.success-item p {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
    line-height: 1.4;
}

.trending-item {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.trending-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.trending-title {
    display: block;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
    line-height: 1.3;
}

.trending-title:hover {
    color: #28a745;
}

.trending-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
}

.trending-stats .current {
    color: #28a745;
    font-weight: 600;
}

.trending-stats .daily {
    color: #666;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.tip-item:last-child {
    margin-bottom: 0;
}

.tip-item i {
    color: #28a745;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.tip-item strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #333;
}

.tip-item p {
    font-size: 0.875rem;
    color: #666;
    margin: 0;
    line-height: 1.4;
}

.newsletter-form .form-group {
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 2rem;
    }
    
    .impact-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group,
    .sort-group {
        min-width: auto;
    }
    
    .petition-card-inner {
        padding: 1.5rem;
    }
    
    .petition-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .petition-actions {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filters = document.querySelectorAll('#status-filter, #category-filter, #location-filter, #sort-by');
    
    // Filter changes
    filters.forEach(filter => {
        filter.addEventListener('change', updateUrl);
    });
    
    // Share functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.share-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.share-btn');
            const title = btn.dataset.title;
            const url = btn.dataset.url;
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: '<?php _e("Support this environmental petition:", "environmental-platform-core"); ?> ' + title,
                    url: url
                });
            } else {
                // Fallback to copying URL
                navigator.clipboard.writeText(url).then(() => {
                    alert('<?php _e("Petition link copied to clipboard!", "environmental-platform-core"); ?>');
                });
            }
        }
    });
    
    // Bookmark functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.bookmark-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.bookmark-btn');
            const petitionId = btn.dataset.petitionId;
            
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
    const newsletterForm = document.getElementById('petition-newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Here you would typically make an AJAX call to subscribe
            alert('<?php _e("Thank you for subscribing to campaign updates!", "environmental-platform-core"); ?>');
            this.reset();
        });
    }
    
    // Quick action smooth scrolling
    document.addEventListener('click', function(e) {
        if (e.target.closest('a[href^="#"]')) {
            e.preventDefault();
            const target = e.target.closest('a').getAttribute('href');
            const element = document.querySelector(target);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
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
