<?php
/**
 * Single Environmental Alert Template
 *
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-alert-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-alert-single'); ?>>
            
            <!-- Alert Header -->
            <header class="ep-alert-header">
                <div class="ep-alert-priority">
                    <?php
                    $priority = get_post_meta(get_the_ID(), '_ep_priority_level', true);
                    $priority_class = 'priority-' . strtolower($priority);
                    ?>
                    <span class="ep-priority-badge <?php echo esc_attr($priority_class); ?>">
                        <?php echo esc_html($priority ?: 'Medium'); ?>
                    </span>
                </div>
                
                <h1 class="ep-alert-title"><?php the_title(); ?></h1>
                
                <div class="ep-alert-meta">
                    <div class="ep-alert-date">
                        <i class="dashicons dashicons-calendar-alt"></i>
                        <span><?php echo get_the_date(); ?></span>
                    </div>
                    
                    <div class="ep-alert-author">
                        <i class="dashicons dashicons-admin-users"></i>
                        <span><?php the_author(); ?></span>
                    </div>
                    
                    <?php
                    $alert_type = get_the_terms(get_the_ID(), 'alert_type');
                    if ($alert_type && !is_wp_error($alert_type)) :
                    ?>
                        <div class="ep-alert-type">
                            <i class="dashicons dashicons-warning"></i>
                            <span><?php echo esc_html($alert_type[0]->name); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Alert Content -->
            <div class="ep-alert-content">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="ep-alert-thumbnail">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="ep-alert-description">
                    <?php the_content(); ?>
                </div>

                <!-- Alert Details -->
                <div class="ep-alert-details">
                    <?php
                    $affected_area = get_post_meta(get_the_ID(), '_ep_affected_area', true);
                    $severity = get_post_meta(get_the_ID(), '_ep_severity', true);
                    $expiry_date = get_post_meta(get_the_ID(), '_ep_expiry_date', true);
                    $contact_info = get_post_meta(get_the_ID(), '_ep_contact_info', true);
                    ?>
                    
                    <?php if ($affected_area): ?>
                        <div class="ep-detail-item">
                            <h3><?php _e('Affected Area', 'environmental-platform-core'); ?></h3>
                            <p><?php echo wp_kses_post($affected_area); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($severity): ?>
                        <div class="ep-detail-item">
                            <h3><?php _e('Severity Level', 'environmental-platform-core'); ?></h3>
                            <div class="ep-severity-indicator severity-<?php echo esc_attr(strtolower($severity)); ?>">
                                <?php echo esc_html($severity); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($expiry_date): ?>
                        <div class="ep-detail-item">
                            <h3><?php _e('Valid Until', 'environmental-platform-core'); ?></h3>
                            <p><?php echo date('F j, Y g:i A', strtotime($expiry_date)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($contact_info): ?>
                        <div class="ep-detail-item">
                            <h3><?php _e('Contact Information', 'environmental-platform-core'); ?></h3>
                            <div class="ep-contact-info">
                                <?php echo wp_kses_post($contact_info); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Steps -->
                <?php
                $action_steps = get_post_meta(get_the_ID(), '_ep_action_steps', true);
                if ($action_steps):
                ?>
                    <section class="ep-action-steps">
                        <h2><?php _e('Recommended Actions', 'environmental-platform-core'); ?></h2>
                        <div class="ep-steps-content">
                            <?php echo wp_kses_post($action_steps); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Emergency Contacts -->
                <?php
                $emergency_contacts = get_post_meta(get_the_ID(), '_ep_emergency_contacts', true);
                if ($emergency_contacts && is_array($emergency_contacts)):
                ?>
                    <section class="ep-emergency-contacts">
                        <h2><?php _e('Emergency Contacts', 'environmental-platform-core'); ?></h2>
                        <div class="ep-contacts-grid">
                            <?php foreach ($emergency_contacts as $contact): ?>
                                <div class="ep-contact-card">
                                    <h4><?php echo esc_html($contact['name']); ?></h4>
                                    <p class="ep-contact-role"><?php echo esc_html($contact['role']); ?></p>
                                    <div class="ep-contact-details">
                                        <?php if (!empty($contact['phone'])): ?>
                                            <p><strong><?php _e('Phone:', 'environmental-platform-core'); ?></strong> 
                                               <a href="tel:<?php echo esc_attr($contact['phone']); ?>"><?php echo esc_html($contact['phone']); ?></a></p>
                                        <?php endif; ?>
                                        <?php if (!empty($contact['email'])): ?>
                                            <p><strong><?php _e('Email:', 'environmental-platform-core'); ?></strong> 
                                               <a href="mailto:<?php echo esc_attr($contact['email']); ?>"><?php echo esc_html($contact['email']); ?></a></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Share Alert -->
                <section class="ep-alert-share">
                    <h3><?php _e('Share This Alert', 'environmental-platform-core'); ?></h3>
                    <div class="ep-share-buttons">
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(get_the_title() . ' - ' . get_permalink()); ?>" 
                           class="ep-share-btn ep-share-twitter" target="_blank" rel="noopener">
                            <i class="dashicons dashicons-twitter"></i>
                            <?php _e('Twitter', 'environmental-platform-core'); ?>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                           class="ep-share-btn ep-share-facebook" target="_blank" rel="noopener">
                            <i class="dashicons dashicons-facebook"></i>
                            <?php _e('Facebook', 'environmental-platform-core'); ?>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" 
                           class="ep-share-btn ep-share-email">
                            <i class="dashicons dashicons-email"></i>
                            <?php _e('Email', 'environmental-platform-core'); ?>
                        </a>
                    </div>
                </section>

                <!-- Taxonomies -->
                <div class="ep-alert-taxonomies">
                    <?php
                    $regions = get_the_terms(get_the_ID(), 'region');
                    if ($regions && !is_wp_error($regions)):
                    ?>
                        <div class="ep-taxonomy-section">
                            <strong><?php _e('Regions:', 'environmental-platform-core'); ?></strong>
                            <?php foreach ($regions as $region): ?>
                                <a href="<?php echo get_term_link($region); ?>" class="ep-tag">
                                    <?php echo esc_html($region->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $categories = get_the_terms(get_the_ID(), 'env_category');
                    if ($categories && !is_wp_error($categories)):
                    ?>
                        <div class="ep-taxonomy-section">
                            <strong><?php _e('Categories:', 'environmental-platform-core'); ?></strong>
                            <?php foreach ($categories as $category): ?>
                                <a href="<?php echo get_term_link($category); ?>" class="ep-tag">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Alerts -->
            <?php
            $related_alerts = new WP_Query(array(
                'post_type' => 'env_alert',
                'posts_per_page' => 3,
                'post__not_in' => array(get_the_ID()),
                'meta_query' => array(
                    array(
                        'key' => '_ep_priority_level',
                        'compare' => 'EXISTS'
                    )
                )
            ));
            ?>
            
            <?php if ($related_alerts->have_posts()) : ?>
                <section class="ep-related-alerts">
                    <h2><?php _e('Related Alerts', 'environmental-platform-core'); ?></h2>
                    <div class="ep-related-grid">
                        <?php while ($related_alerts->have_posts()) : $related_alerts->the_post(); ?>
                            <div class="ep-related-alert-card">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="ep-related-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ep-related-content">
                                    <div class="ep-related-priority">
                                        <?php
                                        $related_priority = get_post_meta(get_the_ID(), '_ep_priority_level', true);
                                        $priority_class = 'priority-' . strtolower($related_priority);
                                        ?>
                                        <span class="ep-priority-badge <?php echo esc_attr($priority_class); ?>">
                                            <?php echo esc_html($related_priority ?: 'Medium'); ?>
                                        </span>
                                    </div>
                                    
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <p class="ep-related-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                    <span class="ep-related-date"><?php echo get_the_date(); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>

        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-alert-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ep-alert-single {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ep-alert-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 30px;
    text-align: center;
}

.ep-priority-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 15px;
}

.priority-high { background: #dc3545; }
.priority-medium { background: #ffc107; color: #000; }
.priority-low { background: #28a745; }
.priority-critical { background: #6f42c1; animation: pulse 2s infinite; }

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.ep-alert-title {
    font-size: 2.5em;
    margin: 0 0 20px 0;
    font-weight: bold;
}

.ep-alert-meta {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.ep-alert-meta > div {
    display: flex;
    align-items: center;
    gap: 5px;
}

.ep-alert-content {
    padding: 40px;
}

.ep-alert-thumbnail {
    margin-bottom: 30px;
    text-align: center;
}

.ep-alert-thumbnail img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.ep-alert-details {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    margin: 30px 0;
}

.ep-detail-item {
    margin-bottom: 25px;
}

.ep-detail-item:last-child {
    margin-bottom: 0;
}

.ep-detail-item h3 {
    color: #495057;
    margin-bottom: 10px;
    font-size: 1.1em;
}

.ep-severity-indicator {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
}

.severity-low { background: #d4edda; color: #155724; }
.severity-medium { background: #fff3cd; color: #856404; }
.severity-high { background: #f8d7da; color: #721c24; }
.severity-critical { background: #d1ecf1; color: #0c5460; }

.ep-action-steps,
.ep-emergency-contacts {
    margin: 40px 0;
    padding: 30px;
    background: #e9ecef;
    border-radius: 8px;
}

.ep-contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-contact-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.ep-contact-card h4 {
    margin: 0 0 5px 0;
    color: #dc3545;
}

.ep-contact-role {
    color: #6c757d;
    font-style: italic;
    margin-bottom: 15px;
}

.ep-alert-share {
    text-align: center;
    margin: 40px 0;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 8px;
}

.ep-share-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.ep-share-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    transition: all 0.3s ease;
}

.ep-share-twitter { background: #1da1f2; }
.ep-share-facebook { background: #3b5998; }
.ep-share-email { background: #6c757d; }

.ep-share-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.ep-alert-taxonomies {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #dee2e6;
}

.ep-taxonomy-section {
    margin-bottom: 15px;
}

.ep-tag {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    text-decoration: none;
    font-size: 0.9em;
    margin: 2px;
    transition: background 0.3s ease;
}

.ep-tag:hover {
    background: #005a87;
    color: white;
}

.ep-related-alerts {
    margin-top: 50px;
    padding: 40px;
    background: #f8f9fa;
}

.ep-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-related-alert-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.ep-related-alert-card:hover {
    transform: translateY(-5px);
}

.ep-related-content {
    padding: 20px;
}

.ep-related-content h3 {
    margin: 10px 0;
}

.ep-related-content h3 a {
    color: #495057;
    text-decoration: none;
}

.ep-related-content h3 a:hover {
    color: #dc3545;
}

.ep-related-date {
    color: #6c757d;
    font-size: 0.9em;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ep-alert-container {
        padding: 10px;
    }
    
    .ep-alert-header,
    .ep-alert-content {
        padding: 20px;
    }
    
    .ep-alert-title {
        font-size: 2em;
    }
    
    .ep-alert-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .ep-contacts-grid,
    .ep-related-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-share-buttons {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>
