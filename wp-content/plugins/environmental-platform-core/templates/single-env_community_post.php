<?php
/**
 * Single Community Post Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-community-single">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-community-article'); ?>>
            
            <!-- Community Post Header -->
            <header class="ep-community-header">
                <div class="ep-container">
                    <div class="ep-post-meta">
                        <?php
                        $post_type = get_post_meta(get_the_ID(), '_ep_community_post_type', true);
                        $location = get_post_meta(get_the_ID(), '_ep_location', true);
                        $urgency_level = get_post_meta(get_the_ID(), '_ep_urgency_level', true);
                        ?>
                        
                        <div class="ep-meta-row">
                            <?php if ($post_type): ?>
                                <span class="ep-post-type-badge ep-badge-<?php echo esc_attr(sanitize_title($post_type)); ?>">
                                    <?php echo esc_html($post_type); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($urgency_level): ?>
                                <span class="ep-urgency-badge ep-urgency-<?php echo esc_attr(sanitize_title($urgency_level)); ?>">
                                    <?php echo esc_html($urgency_level); ?>
                                </span>
                            <?php endif; ?>
                            
                            <time class="ep-post-date" datetime="<?php echo get_the_date('c'); ?>">
                                <?php echo get_the_date(); ?>
                            </time>
                        </div>
                        
                        <?php if ($location): ?>
                            <div class="ep-location">
                                <i class="ep-icon-location"></i>
                                <span><?php echo esc_html($location); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="ep-community-title"><?php the_title(); ?></h1>
                    
                    <!-- Author Info -->
                    <div class="ep-author-info">
                        <div class="ep-author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 60); ?>
                        </div>
                        <div class="ep-author-details">
                            <h3 class="ep-author-name">
                                <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>">
                                    <?php the_author(); ?>
                                </a>
                            </h3>
                            <p class="ep-author-bio"><?php echo get_the_author_meta('description'); ?></p>
                            <div class="ep-author-stats">
                                <span class="ep-post-count">
                                    <?php echo count_user_posts(get_the_author_meta('ID'), 'env_community_post'); ?> 
                                    <?php _e('posts', 'environmental-platform-core'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Community Post Content -->
            <div class="ep-community-content">
                <div class="ep-container">
                    <div class="ep-row">
                        <div class="ep-col-8">
                            
                            <!-- Featured Media -->
                            <?php if (has_post_thumbnail()): ?>
                                <div class="ep-featured-media">
                                    <?php the_post_thumbnail('large', array('class' => 'ep-responsive-image')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Post Content -->
                            <div class="ep-post-content">
                                <?php the_content(); ?>
                            </div>
                            
                            <!-- Media Gallery -->
                            <?php
                            $gallery_images = get_post_meta(get_the_ID(), '_ep_gallery_images', true);
                            if ($gallery_images && is_array($gallery_images)):
                            ?>
                                <section class="ep-media-gallery">
                                    <h3><?php _e('Photo Gallery', 'environmental-platform-core'); ?></h3>
                                    <div class="ep-gallery-grid">
                                        <?php foreach ($gallery_images as $image_id): ?>
                                            <div class="ep-gallery-item">
                                                <a href="<?php echo wp_get_attachment_url($image_id); ?>" 
                                                   data-lightbox="community-gallery">
                                                    <?php echo wp_get_attachment_image($image_id, 'medium', false, array('class' => 'ep-gallery-thumb')); ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Call to Action -->
                            <?php
                            $call_to_action = get_post_meta(get_the_ID(), '_ep_call_to_action', true);
                            $action_link = get_post_meta(get_the_ID(), '_ep_action_link', true);
                            if ($call_to_action):
                            ?>
                                <section class="ep-call-to-action">
                                    <div class="ep-cta-content">
                                        <h3><?php _e('How You Can Help', 'environmental-platform-core'); ?></h3>
                                        <p><?php echo wp_kses_post($call_to_action); ?></p>
                                        <?php if ($action_link): ?>
                                            <a href="<?php echo esc_url($action_link); ?>" 
                                               class="ep-btn ep-btn-primary ep-cta-button">
                                                <?php _e('Take Action', 'environmental-platform-core'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Engagement Section -->
                            <section class="ep-engagement-section">
                                <div class="ep-engagement-actions">
                                    <button class="ep-action-btn ep-like-btn" onclick="epToggleLike(<?php echo get_the_ID(); ?>)">
                                        <i class="ep-icon-heart"></i>
                                        <span class="ep-like-count"><?php echo get_post_meta(get_the_ID(), '_ep_like_count', true) ?: 0; ?></span>
                                        <span class="ep-like-text"><?php _e('Support', 'environmental-platform-core'); ?></span>
                                    </button>
                                    
                                    <button class="ep-action-btn ep-share-btn" onclick="epSharePost()">
                                        <i class="ep-icon-share"></i>
                                        <span><?php _e('Share', 'environmental-platform-core'); ?></span>
                                    </button>
                                    
                                    <button class="ep-action-btn ep-follow-btn" onclick="epFollowUpdates(<?php echo get_the_ID(); ?>)">
                                        <i class="ep-icon-bell"></i>
                                        <span><?php _e('Follow Updates', 'environmental-platform-core'); ?></span>
                                    </button>
                                </div>
                                
                                <!-- Tags -->
                                <?php
                                $tags = get_the_terms(get_the_ID(), 'community_topic');
                                if ($tags && !is_wp_error($tags)):
                                ?>
                                    <div class="ep-post-tags">
                                        <h4><?php _e('Related Topics', 'environmental-platform-core'); ?></h4>
                                        <div class="ep-tag-list">
                                            <?php foreach ($tags as $tag): ?>
                                                <a href="<?php echo get_term_link($tag); ?>" class="ep-tag">
                                                    <?php echo esc_html($tag->name); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </section>
                            
                            <!-- Comments Section -->
                            <?php if (comments_open() || get_comments_number()): ?>
                                <section class="ep-comments-section">
                                    <h3><?php _e('Community Discussion', 'environmental-platform-core'); ?></h3>
                                    <?php comments_template(); ?>
                                </section>
                            <?php endif; ?>
                            
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="ep-col-4">
                            <aside class="ep-community-sidebar">
                                
                                <!-- Quick Actions -->
                                <div class="ep-widget ep-quick-actions">
                                    <h3><?php _e('Quick Actions', 'environmental-platform-core'); ?></h3>
                                    <div class="ep-action-buttons">
                                        <a href="<?php echo home_url('/submit-report/'); ?>" class="ep-action-link">
                                            <i class="ep-icon-report"></i>
                                            <?php _e('Report Issue', 'environmental-platform-core'); ?>
                                        </a>
                                        <a href="<?php echo home_url('/create-petition/'); ?>" class="ep-action-link">
                                            <i class="ep-icon-petition"></i>
                                            <?php _e('Start Petition', 'environmental-platform-core'); ?>
                                        </a>
                                        <a href="<?php echo home_url('/volunteer/'); ?>" class="ep-action-link">
                                            <i class="ep-icon-volunteer"></i>
                                            <?php _e('Volunteer', 'environmental-platform-core'); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Post Details -->
                                <div class="ep-widget ep-post-details">
                                    <h3><?php _e('Post Information', 'environmental-platform-core'); ?></h3>
                                    <dl class="ep-details-list">
                                        <dt><?php _e('Posted', 'environmental-platform-core'); ?></dt>
                                        <dd><?php echo get_the_date(); ?></dd>
                                        
                                        <?php if ($location): ?>
                                            <dt><?php _e('Location', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($location); ?></dd>
                                        <?php endif; ?>
                                        
                                        <dt><?php _e('Views', 'environmental-platform-core'); ?></dt>
                                        <dd><?php echo get_post_meta(get_the_ID(), '_ep_view_count', true) ?: 0; ?></dd>
                                        
                                        <dt><?php _e('Supporters', 'environmental-platform-core'); ?></dt>
                                        <dd><?php echo get_post_meta(get_the_ID(), '_ep_like_count', true) ?: 0; ?></dd>
                                    </dl>
                                </div>
                                
                                <!-- Contact Information -->
                                <?php
                                $contact_email = get_post_meta(get_the_ID(), '_ep_contact_email', true);
                                $contact_phone = get_post_meta(get_the_ID(), '_ep_contact_phone', true);
                                if ($contact_email || $contact_phone):
                                ?>
                                    <div class="ep-widget ep-contact-info">
                                        <h3><?php _e('Contact Information', 'environmental-platform-core'); ?></h3>
                                        <?php if ($contact_email): ?>
                                            <p>
                                                <strong><?php _e('Email:', 'environmental-platform-core'); ?></strong><br>
                                                <a href="mailto:<?php echo esc_attr($contact_email); ?>">
                                                    <?php echo esc_html($contact_email); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($contact_phone): ?>
                                            <p>
                                                <strong><?php _e('Phone:', 'environmental-platform-core'); ?></strong><br>
                                                <a href="tel:<?php echo esc_attr($contact_phone); ?>">
                                                    <?php echo esc_html($contact_phone); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Related Posts -->
                                <?php
                                $related_posts = get_posts(array(
                                    'post_type' => 'env_community_post',
                                    'posts_per_page' => 3,
                                    'post__not_in' => array(get_the_ID()),
                                    'meta_query' => array(
                                        array(
                                            'key' => '_ep_location',
                                            'value' => $location,
                                            'compare' => 'LIKE'
                                        )
                                    )
                                ));
                                
                                if ($related_posts):
                                ?>
                                    <div class="ep-widget ep-related-posts">
                                        <h3><?php _e('Nearby Posts', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-related-list">
                                            <?php foreach ($related_posts as $related): ?>
                                                <li class="ep-related-item">
                                                    <a href="<?php echo get_permalink($related->ID); ?>" 
                                                       class="ep-related-link">
                                                        <?php if (has_post_thumbnail($related->ID)): ?>
                                                            <div class="ep-related-thumb">
                                                                <?php echo get_the_post_thumbnail($related->ID, 'thumbnail'); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="ep-related-content">
                                                            <h4><?php echo get_the_title($related->ID); ?></h4>
                                                            <span class="ep-related-date">
                                                                <?php echo get_the_date('', $related->ID); ?>
                                                            </span>
                                                        </div>
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
.ep-community-single {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

.ep-community-header {
    background: linear-gradient(135deg, #16537e 0%, #2980b9 100%);
    color: white;
    padding: 40px 0;
}

.ep-post-meta {
    margin-bottom: 20px;
}

.ep-meta-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.ep-post-type-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.ep-badge-report { background-color: #dc3545; }
.ep-badge-question { background-color: #ffc107; color: #333; }
.ep-badge-discussion { background-color: #17a2b8; }
.ep-badge-success-story { background-color: #28a745; }

.ep-urgency-high { background-color: #dc3545; }
.ep-urgency-medium { background-color: #fd7e14; }
.ep-urgency-low { background-color: #28a745; }

.ep-location {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
    opacity: 0.9;
}

.ep-community-title {
    font-size: 2.2em;
    font-weight: 700;
    margin: 20px 0;
    line-height: 1.2;
}

.ep-author-info {
    display: flex;
    align-items: center;
    gap: 20px;
    background-color: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.ep-author-avatar img {
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.ep-author-name a {
    color: white;
    text-decoration: none;
    font-size: 1.1em;
    font-weight: 600;
}

.ep-author-bio {
    font-size: 0.9em;
    opacity: 0.8;
    margin: 5px 0;
}

.ep-author-stats {
    font-size: 0.8em;
    opacity: 0.7;
}

.ep-community-content {
    padding: 60px 0;
}

.ep-featured-media {
    margin-bottom: 40px;
}

.ep-responsive-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.ep-post-content {
    font-size: 1.1em;
    margin-bottom: 40px;
}

.ep-media-gallery {
    margin-bottom: 40px;
}

.ep-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.ep-gallery-item a {
    display: block;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.ep-gallery-item a:hover {
    transform: scale(1.05);
}

.ep-gallery-thumb {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.ep-call-to-action {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 40px;
    text-align: center;
}

.ep-call-to-action h3 {
    margin-bottom: 15px;
    font-size: 1.3em;
}

.ep-cta-button {
    margin-top: 20px;
    background-color: white;
    color: #28a745;
    padding: 12px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s ease;
}

.ep-cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.ep-engagement-section {
    background-color: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 40px;
}

.ep-engagement-actions {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.ep-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 2px solid #dee2e6;
    background-color: white;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9em;
    font-weight: 500;
}

.ep-action-btn:hover {
    border-color: #2980b9;
    color: #2980b9;
    transform: translateY(-2px);
}

.ep-like-btn.liked {
    background-color: #e74c3c;
    color: white;
    border-color: #e74c3c;
}

.ep-post-tags h4 {
    margin-bottom: 15px;
    font-size: 1em;
    color: #495057;
}

.ep-tag-list {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.ep-tag {
    background-color: #e9ecef;
    color: #495057;
    padding: 5px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.ep-tag:hover {
    background-color: #2980b9;
    color: white;
}

.ep-comments-section {
    margin-top: 50px;
    padding-top: 40px;
    border-top: 2px solid #e9ecef;
}

.ep-community-sidebar {
    padding-left: 30px;
}

.ep-widget {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
}

.ep-widget h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.1em;
    font-weight: 600;
}

.ep-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ep-action-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
}

.ep-action-link:hover {
    background-color: #e9ecef;
    color: #2980b9;
}

.ep-details-list dt {
    font-weight: 600;
    color: #495057;
    margin-top: 10px;
}

.ep-details-list dd {
    margin-bottom: 10px;
    margin-left: 0;
}

.ep-related-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-related-item {
    margin-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 15px;
}

.ep-related-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.ep-related-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
    color: #333;
}

.ep-related-thumb {
    flex-shrink: 0;
}

.ep-related-thumb img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.ep-related-content h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
    color: #2980b9;
    line-height: 1.3;
}

.ep-related-date {
    font-size: 0.8em;
    color: #6c757d;
}

@media (max-width: 768px) {
    .ep-community-title {
        font-size: 1.8em;
    }
    
    .ep-author-info {
        flex-direction: column;
        text-align: center;
    }
    
    .ep-engagement-actions {
        flex-direction: column;
    }
    
    .ep-community-sidebar {
        padding-left: 0;
        margin-top: 40px;
    }
    
    .ep-gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function epToggleLike(postId) {
    // AJAX call to toggle like
    const btn = document.querySelector('.ep-like-btn');
    const count = btn.querySelector('.ep-like-count');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=ep_toggle_like&post_id=${postId}&nonce=<?php echo wp_create_nonce('ep_like_nonce'); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            count.textContent = data.data.count;
            btn.classList.toggle('liked');
        }
    });
}

function epSharePost() {
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

function epFollowUpdates(postId) {
    // AJAX call to follow updates
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=ep_follow_updates&post_id=${postId}&nonce=<?php echo wp_create_nonce('ep_follow_nonce'); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('<?php _e('You will receive updates about this post!', 'environmental-platform-core'); ?>');
        }
    });
}
</script>

<?php get_footer(); ?>
