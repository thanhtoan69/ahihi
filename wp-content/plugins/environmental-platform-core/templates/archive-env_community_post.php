<?php
/**
 * Archive Community Posts Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="community-posts-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-users"></i>
                        <?php _e('Community Forum', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Join the conversation and connect with fellow environmental enthusiasts in our community forum.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Community Statistics -->
                <div class="community-stats">
                    <?php
                    $total_posts = wp_count_posts('env_community_post')->publish;
                    $total_members = get_users(array('count_total' => true));
                    
                    $recent_activity = new WP_Query(array(
                        'post_type' => 'env_community_post',
                        'posts_per_page' => 1,
                        'orderby' => 'modified',
                        'order' => 'DESC'
                    ));
                    
                    $last_activity = '';
                    if ($recent_activity->have_posts()) {
                        $recent_activity->the_post();
                        $last_activity = human_time_diff(get_the_modified_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core');
                        wp_reset_postdata();
                    }
                    
                    // Count posts by topic
                    $topics = array(
                        'general-discussion' => __('General Discussion', 'environmental-platform-core'),
                        'climate-action' => __('Climate Action', 'environmental-platform-core'),
                        'sustainable-living' => __('Sustainable Living', 'environmental-platform-core'),
                        'conservation' => __('Conservation', 'environmental-platform-core'),
                        'renewable-energy' => __('Renewable Energy', 'environmental-platform-core'),
                        'waste-reduction' => __('Waste Reduction', 'environmental-platform-core')
                    );
                    ?>
                    
                    <div class="stats-overview">
                        <div class="stat-item">
                            <i class="fa fa-comments"></i>
                            <span class="stat-number"><?php echo number_format($total_posts); ?></span>
                            <span class="stat-label"><?php _e('Posts', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-users"></i>
                            <span class="stat-number"><?php echo number_format($total_members); ?></span>
                            <span class="stat-label"><?php _e('Members', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-clock"></i>
                            <span class="stat-number"><?php echo $last_activity; ?></span>
                            <span class="stat-label"><?php _e('Last Activity', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-tags"></i>
                            <span class="stat-number"><?php echo count($topics); ?></span>
                            <span class="stat-label"><?php _e('Topics', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Post Form -->
                <?php if (is_user_logged_in()): ?>
                    <div class="quick-post-form">
                        <h3><?php _e('Start a Discussion', 'environmental-platform-core'); ?></h3>
                        <form id="community-post-form" class="post-form">
                            <div class="form-group">
                                <input type="text" class="form-control" name="post_title" placeholder="<?php _e('What would you like to discuss?', 'environmental-platform-core'); ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <select name="post_topic" class="form-control" required>
                                        <option value=""><?php _e('Select Topic', 'environmental-platform-core'); ?></option>
                                        <?php foreach ($topics as $topic_key => $topic_name): ?>
                                            <option value="<?php echo esc_attr($topic_key); ?>"><?php echo $topic_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <select name="post_priority" class="form-control">
                                        <option value="normal"><?php _e('Normal Priority', 'environmental-platform-core'); ?></option>
                                        <option value="urgent"><?php _e('Urgent Discussion', 'environmental-platform-core'); ?></option>
                                        <option value="question"><?php _e('Question', 'environmental-platform-core'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" name="post_content" rows="4" placeholder="<?php _e('Share your thoughts, ask questions, or start a discussion...', 'environmental-platform-core'); ?>" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-plus"></i>
                                <?php _e('Start Discussion', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><?php _e('Join our community to start discussions and connect with other environmental enthusiasts.', 'environmental-platform-core'); ?></p>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">
                            <?php _e('Login to Participate', 'environmental-platform-core'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="topic-filter"><?php _e('Topic:', 'environmental-platform-core'); ?></label>
                            <select id="topic-filter" class="form-control">
                                <option value=""><?php _e('All Topics', 'environmental-platform-core'); ?></option>
                                <?php foreach ($topics as $topic_key => $topic_name): ?>
                                    <option value="<?php echo esc_attr($topic_key); ?>" <?php selected(isset($_GET['topic']) && $_GET['topic'] == $topic_key); ?>><?php echo $topic_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="priority-filter"><?php _e('Priority:', 'environmental-platform-core'); ?></label>
                            <select id="priority-filter" class="form-control">
                                <option value=""><?php _e('All Priorities', 'environmental-platform-core'); ?></option>
                                <option value="urgent" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'urgent'); ?>><?php _e('Urgent', 'environmental-platform-core'); ?></option>
                                <option value="question" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'question'); ?>><?php _e('Questions', 'environmental-platform-core'); ?></option>
                                <option value="normal" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'normal'); ?>><?php _e('Normal', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="author-filter"><?php _e('Author:', 'environmental-platform-core'); ?></label>
                            <select id="author-filter" class="form-control">
                                <option value=""><?php _e('All Authors', 'environmental-platform-core'); ?></option>
                                <?php if (is_user_logged_in()): ?>
                                    <option value="<?php echo get_current_user_id(); ?>" <?php selected(isset($_GET['author']) && $_GET['author'] == get_current_user_id()); ?>><?php _e('My Posts', 'environmental-platform-core'); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="sort-group">
                            <label for="sort-by"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                            <select id="sort-by" class="form-control">
                                <option value="date" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'date'); ?>><?php _e('Most Recent', 'environmental-platform-core'); ?></option>
                                <option value="popular" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'popular'); ?>><?php _e('Most Popular', 'environmental-platform-core'); ?></option>
                                <option value="replies" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'replies'); ?>><?php _e('Most Replies', 'environmental-platform-core'); ?></option>
                                <option value="title" <?php selected(isset($_GET['sort']) && $_GET['sort'] == 'title'); ?>><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Posts List -->
                <div class="community-posts-list">
                    <?php
                    // Build query args based on filters
                    $query_args = array(
                        'post_type' => 'env_community_post',
                        'posts_per_page' => 10,
                        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                    );
                    
                    $meta_query = array('relation' => 'AND');
                    
                    if (isset($_GET['topic']) && !empty($_GET['topic'])) {
                        $meta_query[] = array(
                            'key' => '_community_topic',
                            'value' => sanitize_text_field($_GET['topic']),
                            'compare' => '='
                        );
                    }
                    
                    if (isset($_GET['priority']) && !empty($_GET['priority'])) {
                        $meta_query[] = array(
                            'key' => '_post_priority',
                            'value' => sanitize_text_field($_GET['priority']),
                            'compare' => '='
                        );
                    }
                    
                    if (!empty($meta_query)) {
                        $query_args['meta_query'] = $meta_query;
                    }
                    
                    if (isset($_GET['author']) && !empty($_GET['author'])) {
                        $query_args['author'] = intval($_GET['author']);
                    }
                    
                    // Handle sorting
                    if (isset($_GET['sort'])) {
                        switch ($_GET['sort']) {
                            case 'title':
                                $query_args['orderby'] = 'title';
                                $query_args['order'] = 'ASC';
                                break;
                            case 'popular':
                                $query_args['meta_key'] = '_post_likes';
                                $query_args['orderby'] = 'meta_value_num';
                                $query_args['order'] = 'DESC';
                                break;
                            case 'replies':
                                $query_args['meta_key'] = '_reply_count';
                                $query_args['orderby'] = 'meta_value_num';
                                $query_args['order'] = 'DESC';
                                break;
                        }
                    }
                    
                    $posts_query = new WP_Query($query_args);
                    
                    if ($posts_query->have_posts()) :
                        while ($posts_query->have_posts()) : $posts_query->the_post();
                            $topic = get_post_meta(get_the_ID(), '_community_topic', true);
                            $priority = get_post_meta(get_the_ID(), '_post_priority', true);
                            $likes = get_post_meta(get_the_ID(), '_post_likes', true) ?: 0;
                            $reply_count = get_post_meta(get_the_ID(), '_reply_count', true) ?: 0;
                            $is_solved = get_post_meta(get_the_ID(), '_is_solved', true);
                            $last_reply_date = get_post_meta(get_the_ID(), '_last_reply_date', true);
                    ?>
                        <article class="community-post-item" data-topic="<?php echo esc_attr($topic); ?>" data-priority="<?php echo esc_attr($priority); ?>">
                            <div class="post-item-inner">
                                <div class="post-meta-sidebar">
                                    <div class="author-avatar">
                                        <?php echo get_avatar(get_the_author_meta('ID'), 50); ?>
                                    </div>
                                    <div class="post-stats">
                                        <div class="stat-item likes">
                                            <i class="fa fa-heart"></i>
                                            <span><?php echo $likes; ?></span>
                                        </div>
                                        <div class="stat-item replies">
                                            <i class="fa fa-reply"></i>
                                            <span><?php echo $reply_count; ?></span>
                                        </div>
                                        <div class="stat-item views">
                                            <i class="fa fa-eye"></i>
                                            <span><?php echo get_post_meta(get_the_ID(), '_view_count', true) ?: 0; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="post-content">
                                    <div class="post-header">
                                        <div class="post-badges">
                                            <?php if ($topic): ?>
                                                <span class="topic-badge topic-<?php echo esc_attr($topic); ?>">
                                                    <?php echo esc_html($topics[$topic] ?? $topic); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($priority && $priority !== 'normal'): ?>
                                                <span class="priority-badge priority-<?php echo esc_attr($priority); ?>">
                                                    <?php 
                                                    $priority_labels = array(
                                                        'urgent' => __('Urgent', 'environmental-platform-core'),
                                                        'question' => __('Question', 'environmental-platform-core')
                                                    );
                                                    echo $priority_labels[$priority] ?? $priority;
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($is_solved): ?>
                                                <span class="solved-badge">
                                                    <i class="fa fa-check"></i>
                                                    <?php _e('Solved', 'environmental-platform-core'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h3 class="post-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        
                                        <div class="post-meta">
                                            <span class="author">
                                                <?php _e('by', 'environmental-platform-core'); ?>
                                                <strong><?php the_author(); ?></strong>
                                            </span>
                                            <span class="date">
                                                <i class="fa fa-clock"></i>
                                                <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?>
                                            </span>
                                            <?php if ($last_reply_date): ?>
                                                <span class="last-reply">
                                                    <i class="fa fa-reply"></i>
                                                    <?php echo human_time_diff(strtotime($last_reply_date), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="post-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
                                    </div>
                                    
                                    <div class="post-actions">
                                        <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-primary">
                                            <?php _e('View Discussion', 'environmental-platform-core'); ?>
                                        </a>
                                        
                                        <?php if (is_user_logged_in()): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary like-btn" data-post-id="<?php the_ID(); ?>">
                                                <i class="fa fa-heart"></i>
                                                <?php _e('Like', 'environmental-platform-core'); ?>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-secondary share-btn" data-post-id="<?php the_ID(); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" data-url="<?php echo esc_url(get_permalink()); ?>">
                                                <i class="fa fa-share"></i>
                                                <?php _e('Share', 'environmental-platform-core'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php
                        endwhile;
                    else :
                    ?>
                        <div class="no-posts-found">
                            <i class="fa fa-comments"></i>
                            <h3><?php _e('No discussions found', 'environmental-platform-core'); ?></h3>
                            <p><?php _e('Be the first to start a discussion in our community!', 'environmental-platform-core'); ?></p>
                            <?php if (is_user_logged_in()): ?>
                                <button type="button" class="btn btn-primary" onclick="document.querySelector('.quick-post-form').scrollIntoView()">
                                    <?php _e('Start Discussion', 'environmental-platform-core'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Pagination -->
                    <?php if ($posts_query->max_num_pages > 1): ?>
                        <div class="archive-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $posts_query->max_num_pages,
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
                    <!-- Active Members -->
                    <div class="sidebar-widget active-members">
                        <h3 class="widget-title"><?php _e('Active Members', 'environmental-platform-core'); ?></h3>
                        <div class="members-list">
                            <?php
                            $active_members = get_users(array(
                                'meta_key' => 'last_activity',
                                'orderby' => 'meta_value',
                                'order' => 'DESC',
                                'number' => 5
                            ));
                            
                            foreach ($active_members as $member):
                                $last_activity = get_user_meta($member->ID, 'last_activity', true);
                                $post_count = count_user_posts($member->ID, 'env_community_post');
                            ?>
                                <div class="member-item">
                                    <div class="member-avatar">
                                        <?php echo get_avatar($member->ID, 40); ?>
                                    </div>
                                    <div class="member-info">
                                        <strong><?php echo $member->display_name; ?></strong>
                                        <span class="member-stats"><?php echo $post_count; ?> <?php _e('posts', 'environmental-platform-core'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Popular Topics -->
                    <div class="sidebar-widget popular-topics">
                        <h3 class="widget-title"><?php _e('Popular Topics', 'environmental-platform-core'); ?></h3>
                        <div class="topics-list">
                            <?php
                            foreach ($topics as $topic_key => $topic_name):
                                $topic_count = new WP_Query(array(
                                    'post_type' => 'env_community_post',
                                    'meta_key' => '_community_topic',
                                    'meta_value' => $topic_key,
                                    'posts_per_page' => -1
                                ));
                                $count = $topic_count->found_posts;
                                wp_reset_postdata();
                                
                                if ($count > 0):
                            ?>
                                <div class="topic-item" data-topic="<?php echo esc_attr($topic_key); ?>">
                                    <span class="topic-name"><?php echo $topic_name; ?></span>
                                    <span class="topic-count"><?php echo $count; ?></span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="sidebar-widget recent-activity">
                        <h3 class="widget-title"><?php _e('Recent Activity', 'environmental-platform-core'); ?></h3>
                        <div class="activity-list">
                            <?php
                            $recent_posts = new WP_Query(array(
                                'post_type' => 'env_community_post',
                                'posts_per_page' => 5,
                                'orderby' => 'modified',
                                'order' => 'DESC'
                            ));
                            
                            while ($recent_posts->have_posts()) : $recent_posts->the_post();
                            ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <?php echo get_avatar(get_the_author_meta('ID'), 30); ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <strong><?php the_author(); ?></strong>
                                            <?php _e('updated', 'environmental-platform-core'); ?>
                                            <a href="<?php the_permalink(); ?>"><?php echo wp_trim_words(get_the_title(), 5); ?></a>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo human_time_diff(get_the_modified_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    
                    <!-- Community Guidelines -->
                    <div class="sidebar-widget community-guidelines">
                        <h3 class="widget-title"><?php _e('Community Guidelines', 'environmental-platform-core'); ?></h3>
                        <div class="guidelines-content">
                            <ul>
                                <li><?php _e('Be respectful and constructive', 'environmental-platform-core'); ?></li>
                                <li><?php _e('Stay on topic and relevant', 'environmental-platform-core'); ?></li>
                                <li><?php _e('Share knowledge and experiences', 'environmental-platform-core'); ?></li>
                                <li><?php _e('No spam or promotional content', 'environmental-platform-core'); ?></li>
                                <li><?php _e('Use clear and descriptive titles', 'environmental-platform-core'); ?></li>
                            </ul>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <?php _e('Read Full Guidelines', 'environmental-platform-core'); ?>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<style>
.community-posts-archive {
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

.community-stats {
    margin-bottom: 2rem;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
}

.stat-item i {
    font-size: 2rem;
    color: #28a745;
    margin-bottom: 0.5rem;
    display: block;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.quick-post-form {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.quick-post-form h3 {
    margin-bottom: 1rem;
    color: #2c5c3e;
}

.login-prompt {
    background: #e8f4fd;
    border: 2px solid #bee5eb;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
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

.community-posts-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.community-post-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.community-post-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.post-item-inner {
    display: flex;
    padding: 1.5rem;
    gap: 1rem;
}

.post-meta-sidebar {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.author-avatar img {
    border-radius: 50%;
    width: 50px;
    height: 50px;
}

.post-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.post-stats .stat-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #666;
}

.post-stats .stat-item i {
    width: 12px;
    text-align: center;
}

.post-content {
    flex: 1;
}

.post-header {
    margin-bottom: 1rem;
}

.post-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.topic-badge,
.priority-badge,
.solved-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.topic-badge {
    background: #e3f2fd;
    color: #1976d2;
}

.priority-badge.priority-urgent {
    background: #ffebee;
    color: #d32f2f;
}

.priority-badge.priority-question {
    background: #fff3e0;
    color: #f57c00;
}

.solved-badge {
    background: #e8f5e8;
    color: #2e7d32;
}

.post-title {
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
    line-height: 1.3;
}

.post-title a {
    color: #333;
    text-decoration: none;
}

.post-title a:hover {
    color: #28a745;
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 1rem;
}

.post-meta i {
    margin-right: 0.25rem;
}

.post-excerpt {
    color: #666;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.post-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.no-posts-found {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-posts-found i {
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

.member-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.member-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.member-avatar img {
    border-radius: 50%;
    width: 40px;
    height: 40px;
}

.member-info strong {
    display: block;
    color: #333;
    margin-bottom: 0.25rem;
}

.member-stats {
    font-size: 0.875rem;
    color: #666;
}

.topic-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.topic-item:hover {
    background: #e9ecef;
}

.topic-name {
    font-weight: 500;
}

.topic-count {
    background: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.activity-avatar img {
    border-radius: 50%;
    width: 30px;
    height: 30px;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.activity-text a {
    color: #28a745;
    text-decoration: none;
}

.activity-text a:hover {
    text-decoration: underline;
}

.activity-time {
    font-size: 0.75rem;
    color: #999;
}

.guidelines-content ul {
    list-style: none;
    padding: 0;
    margin-bottom: 1rem;
}

.guidelines-content li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
    position: relative;
    padding-left: 1.5rem;
}

.guidelines-content li:before {
    content: '✓';
    color: #28a745;
    font-weight: bold;
    position: absolute;
    left: 0;
}

.guidelines-content li:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 2rem;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group,
    .sort-group {
        min-width: auto;
    }
    
    .post-item-inner {
        flex-direction: column;
    }
    
    .post-meta-sidebar {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .post-stats {
        flex-direction: row;
    }
    
    .post-actions {
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filters = document.querySelectorAll('#topic-filter, #priority-filter, #author-filter, #sort-by');
    const topicItems = document.querySelectorAll('.topic-item');
    
    // Topic item clicks
    topicItems.forEach(item => {
        item.addEventListener('click', function() {
            const topic = this.dataset.topic;
            document.getElementById('topic-filter').value = topic;
            updateUrl();
        });
    });
    
    // Filter changes
    filters.forEach(filter => {
        filter.addEventListener('change', updateUrl);
    });
    
    // Quick post form
    const postForm = document.getElementById('community-post-form');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Here you would typically make an AJAX call to create the post
            alert('<?php _e("Your discussion has been posted!", "environmental-platform-core"); ?>');
            this.reset();
            
            // Refresh the page to show the new post
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    }
    
    // Like functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.like-btn')) {
            e.preventDefault();
            const btn = e.target.closest('.like-btn');
            const postId = btn.dataset.postId;
            
            btn.classList.toggle('active');
            
            // Here you would typically make an AJAX call to like/unlike
            const icon = btn.querySelector('i');
            if (btn.classList.contains('active')) {
                icon.style.color = '#dc3545';
                btn.innerHTML = '<i class="fa fa-heart"></i> <?php _e("Liked", "environmental-platform-core"); ?>';
            } else {
                icon.style.color = '';
                btn.innerHTML = '<i class="fa fa-heart"></i> <?php _e("Like", "environmental-platform-core"); ?>';
            }
        }
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
                    url: url
                });
            } else {
                // Fallback to copying URL
                navigator.clipboard.writeText(url).then(() => {
                    alert('<?php _e("Link copied to clipboard!", "environmental-platform-core"); ?>');
                });
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
