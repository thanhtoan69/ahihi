<?php
/**
 * Post Types & Taxonomies Management Admin Page
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle actions
if (isset($_POST['create_sample_content']) && wp_verify_nonce($_POST['_wpnonce'], 'ep_create_sample')) {
    $created = create_sample_content();
    if ($created) {
        echo '<div class="notice notice-success"><p>Sample content created successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to create sample content.</p></div>';
    }
}

if (isset($_POST['create_default_terms']) && wp_verify_nonce($_POST['_wpnonce'], 'ep_create_terms')) {
    $created = create_default_taxonomy_terms();
    if ($created) {
        echo '<div class="notice notice-success"><p>Default taxonomy terms created successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to create default terms.</p></div>';
    }
}

// Get post type statistics
$post_types = array(
    'env_article' => 'Environmental Articles',
    'env_report' => 'Environmental Reports',
    'env_alert' => 'Environmental Alerts',
    'env_event' => 'Environmental Events',
    'env_project' => 'Environmental Projects',
    'eco_product' => 'Eco Products',
    'community_post' => 'Community Posts',
    'edu_resource' => 'Educational Resources',
    'waste_class' => 'Waste Classifications',
    'env_petition' => 'Environmental Petitions',
    'item_exchange' => 'Item Exchanges'
);

$taxonomies = array(
    'env_category' => 'Environmental Categories',
    'env_tag' => 'Environmental Tags',
    'impact_level' => 'Impact Levels',
    'region' => 'Regions',
    'sustainability_level' => 'Sustainability Levels',
    'project_status' => 'Project Status',
    'product_type' => 'Product Types',
    'event_type' => 'Event Types',
    'report_type' => 'Report Types',
    'alert_type' => 'Alert Types'
);

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ep-admin-header">
        <div class="ep-admin-tabs">
            <a href="#post-types" class="nav-tab nav-tab-active">Post Types</a>
            <a href="#taxonomies" class="nav-tab">Taxonomies</a>
            <a href="#sample-content" class="nav-tab">Sample Content</a>
            <a href="#settings" class="nav-tab">Settings</a>
        </div>
    </div>

    <!-- Post Types Tab -->
    <div id="post-types" class="tab-content active">
        <div class="ep-dashboard-grid">
            <div class="ep-card">
                <h3>📋 Custom Post Types Overview</h3>
                <div class="ep-post-types-grid">
                    <?php foreach ($post_types as $post_type => $label): ?>
                    <?php
                    $count = wp_count_posts($post_type);
                    $total = $count->publish + $count->draft + $count->pending;
                    $post_type_object = get_post_type_object($post_type);
                    ?>
                    <div class="ep-post-type-item">
                        <div class="ep-post-type-header">
                            <div class="ep-post-type-icon">
                                <?php
                                $icons = array(
                                    'env_article' => '📄',
                                    'env_report' => '📋',
                                    'env_alert' => '⚠️',
                                    'env_event' => '📅',
                                    'env_project' => '🚀',
                                    'eco_product' => '🛍️',
                                    'community_post' => '💬',
                                    'edu_resource' => '📚',
                                    'waste_class' => '♻️',
                                    'env_petition' => '✊',
                                    'item_exchange' => '🔄'
                                );
                                echo $icons[$post_type] ?? '📄';
                                ?>
                            </div>
                            <div class="ep-post-type-info">
                                <h4><?php echo $label; ?></h4>
                                <span class="ep-post-type-slug"><?php echo $post_type; ?></span>
                            </div>
                        </div>
                        
                        <div class="ep-post-type-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $total; ?></span>
                                <span class="stat-label">Total</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $count->publish; ?></span>
                                <span class="stat-label">Published</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $count->draft; ?></span>
                                <span class="stat-label">Draft</span>
                            </div>
                        </div>
                        
                        <div class="ep-post-type-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=' . $post_type); ?>" 
                               class="button button-primary">➕ Add New</a>
                            <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>" 
                               class="button button-secondary">📝 Manage</a>
                        </div>
                        
                        <div class="ep-post-type-features">
                            <strong>Features:</strong>
                            <?php if ($post_type_object && $post_type_object->public): ?>
                                <span class="feature-badge">Public</span>
                            <?php endif; ?>
                            <?php if ($post_type_object && $post_type_object->has_archive): ?>
                                <span class="feature-badge">Archive</span>
                            <?php endif; ?>
                            <?php if ($post_type_object && $post_type_object->show_in_rest): ?>
                                <span class="feature-badge">REST API</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Taxonomies Tab -->
    <div id="taxonomies" class="tab-content">
        <div class="ep-dashboard-grid">
            <div class="ep-card">
                <h3>🏷️ Custom Taxonomies Overview</h3>
                <div class="ep-taxonomies-grid">
                    <?php foreach ($taxonomies as $taxonomy => $label): ?>
                    <?php
                    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                    $term_count = is_array($terms) ? count($terms) : 0;
                    $taxonomy_object = get_taxonomy($taxonomy);
                    ?>
                    <div class="ep-taxonomy-item">
                        <div class="ep-taxonomy-header">
                            <div class="ep-taxonomy-icon">🏷️</div>
                            <div class="ep-taxonomy-info">
                                <h4><?php echo $label; ?></h4>
                                <span class="ep-taxonomy-slug"><?php echo $taxonomy; ?></span>
                            </div>
                        </div>
                        
                        <div class="ep-taxonomy-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $term_count; ?></span>
                                <span class="stat-label">Terms</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">
                                    <?php 
                                    if ($taxonomy_object && !empty($taxonomy_object->object_type)) {
                                        echo count($taxonomy_object->object_type);
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                                <span class="stat-label">Post Types</span>
                            </div>
                        </div>
                        
                        <div class="ep-taxonomy-actions">
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=' . $taxonomy); ?>" 
                               class="button button-primary">🏷️ Manage Terms</a>
                        </div>
                        
                        <div class="ep-taxonomy-features">
                            <strong>Type:</strong>
                            <?php if ($taxonomy_object && $taxonomy_object->hierarchical): ?>
                                <span class="feature-badge">Hierarchical</span>
                            <?php else: ?>
                                <span class="feature-badge">Flat</span>
                            <?php endif; ?>
                            <?php if ($taxonomy_object && $taxonomy_object->show_in_rest): ?>
                                <span class="feature-badge">REST API</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($terms) && is_array($terms)): ?>
                        <div class="ep-taxonomy-terms-preview">
                            <strong>Sample Terms:</strong>
                            <?php 
                            $sample_terms = array_slice($terms, 0, 3);
                            $term_names = array_map(function($term) { return $term->name; }, $sample_terms);
                            echo implode(', ', $term_names);
                            if (count($terms) > 3) {
                                echo '... +' . (count($terms) - 3) . ' more';
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Content Tab -->
    <div id="sample-content" class="tab-content">
        <div class="ep-dashboard-grid">
            <div class="ep-card">
                <h3>🎯 Create Sample Content</h3>
                <p>Generate sample environmental content to test your post types and taxonomies.</p>
                
                <form method="post" class="ep-sample-form">
                    <?php wp_nonce_field('ep_create_sample'); ?>
                    
                    <div class="ep-sample-section">
                        <h4>📄 Sample Articles</h4>
                        <label>
                            <input type="checkbox" name="create_articles" value="1" checked>
                            Create 10 sample environmental articles
                        </label>
                    </div>
                    
                    <div class="ep-sample-section">
                        <h4>📅 Sample Events</h4>
                        <label>
                            <input type="checkbox" name="create_events" value="1" checked>
                            Create 5 sample environmental events
                        </label>
                    </div>
                    
                    <div class="ep-sample-section">
                        <h4>🚀 Sample Projects</h4>
                        <label>
                            <input type="checkbox" name="create_projects" value="1" checked>
                            Create 3 sample environmental projects
                        </label>
                    </div>
                    
                    <div class="ep-sample-section">
                        <h4>🛍️ Sample Products</h4>
                        <label>
                            <input type="checkbox" name="create_products" value="1" checked>
                            Create 8 sample eco products
                        </label>
                    </div>
                    
                    <div class="ep-sample-actions">
                        <button type="submit" name="create_sample_content" class="button button-primary button-large">
                            🎯 Create Sample Content
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="ep-card">
                <h3>🏷️ Create Default Terms</h3>
                <p>Create default taxonomy terms for environmental categories and classifications.</p>
                
                <form method="post" class="ep-terms-form">
                    <?php wp_nonce_field('ep_create_terms'); ?>
                    
                    <div class="ep-terms-preview">
                        <h4>Environmental Categories:</h4>
                        <ul>
                            <li>Climate Change</li>
                            <li>Renewable Energy</li>
                            <li>Waste Management</li>
                            <li>Conservation</li>
                            <li>Pollution Control</li>
                            <li>Sustainable Development</li>
                        </ul>
                        
                        <h4>Impact Levels:</h4>
                        <ul>
                            <li>Low Impact</li>
                            <li>Medium Impact</li>
                            <li>High Impact</li>
                            <li>Critical Impact</li>
                        </ul>
                        
                        <h4>Sustainability Levels:</h4>
                        <ul>
                            <li>Highly Sustainable</li>
                            <li>Sustainable</li>
                            <li>Moderately Sustainable</li>
                            <li>Low Sustainability</li>
                        </ul>
                    </div>
                    
                    <div class="ep-terms-actions">
                        <button type="submit" name="create_default_terms" class="button button-primary button-large">
                            🏷️ Create Default Terms
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="settings" class="tab-content">
        <div class="ep-card">
            <h3>⚙️ Post Types & Taxonomies Settings</h3>
            
            <form method="post" action="options.php">
                <?php settings_fields('ep_post_types_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Archives</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ep_enable_post_type_archives" value="1" 
                                           <?php checked(get_option('ep_enable_post_type_archives', 1)); ?>>
                                    Enable archive pages for custom post types
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">REST API Support</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ep_enable_rest_api" value="1" 
                                           <?php checked(get_option('ep_enable_rest_api', 1)); ?>>
                                    Enable REST API endpoints for custom post types
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Search Integration</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ep_include_in_search" value="1" 
                                           <?php checked(get_option('ep_include_in_search', 1)); ?>>
                                    Include custom post types in WordPress search
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Archive Page Slugs</th>
                        <td>
                            <fieldset>
                                <label for="ep_article_slug">
                                    Articles Archive Slug:
                                    <input type="text" id="ep_article_slug" name="ep_article_slug" 
                                           value="<?php echo esc_attr(get_option('ep_article_slug', 'environmental-articles')); ?>" 
                                           class="regular-text">
                                </label>
                                <br><br>
                                <label for="ep_events_slug">
                                    Events Archive Slug:
                                    <input type="text" id="ep_events_slug" name="ep_events_slug" 
                                           value="<?php echo esc_attr(get_option('ep_events_slug', 'environmental-events')); ?>" 
                                           class="regular-text">
                                </label>
                                <br><br>
                                <label for="ep_projects_slug">
                                    Projects Archive Slug:
                                    <input type="text" id="ep_projects_slug" name="ep_projects_slug" 
                                           value="<?php echo esc_attr(get_option('ep_projects_slug', 'environmental-projects')); ?>" 
                                           class="regular-text">
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
</div>

<style>
.ep-dashboard-grid {
    margin-top: 20px;
}

.ep-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-post-types-grid, .ep-taxonomies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-post-type-item, .ep-taxonomy-item {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    border-left: 4px solid #2e7d32;
}

.ep-post-type-header, .ep-taxonomy-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.ep-post-type-icon, .ep-taxonomy-icon {
    font-size: 24px;
    margin-right: 15px;
}

.ep-post-type-info h4, .ep-taxonomy-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.ep-post-type-slug, .ep-taxonomy-slug {
    font-size: 12px;
    color: #666;
    font-family: monospace;
    background: #e0e0e0;
    padding: 2px 6px;
    border-radius: 3px;
}

.ep-post-type-stats, .ep-taxonomy-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #2e7d32;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.ep-post-type-actions, .ep-taxonomy-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.ep-post-type-features, .ep-taxonomy-features {
    font-size: 12px;
    margin-top: 10px;
}

.feature-badge {
    background: #2e7d32;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    margin-left: 5px;
}

.ep-taxonomy-terms-preview {
    margin-top: 10px;
    font-size: 12px;
}

.ep-sample-form, .ep-terms-form {
    margin-top: 20px;
}

.ep-sample-section, .ep-terms-preview {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.ep-sample-section h4 {
    margin-bottom: 10px;
}

.ep-sample-actions, .ep-terms-actions {
    text-align: center;
    margin-top: 20px;
}

.ep-terms-preview ul {
    list-style-type: disc;
    margin-left: 20px;
}

.ep-terms-preview li {
    margin-bottom: 5px;
}

.tab-content {
    display: none;
    margin-top: 20px;
}

.tab-content.active {
    display: block;
}

.nav-tab.nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>

<?php

// Helper functions for creating sample content and terms
function create_sample_content() {
    $created = 0;
    
    // Sample Articles
    if (isset($_POST['create_articles'])) {
        $articles = array(
            array(
                'title' => 'Climate Change: The Global Challenge of Our Time',
                'content' => 'Climate change represents one of the most pressing environmental challenges facing humanity today. This comprehensive article explores the causes, effects, and potential solutions to global warming.',
                'environmental_score' => 85,
                'carbon_impact' => 2.5
            ),
            array(
                'title' => 'Renewable Energy: Powering a Sustainable Future',
                'content' => 'Solar, wind, and hydroelectric power are revolutionizing how we generate electricity. Learn about the latest developments in renewable energy technology.',
                'environmental_score' => 92,
                'carbon_impact' => 0.8
            ),
            array(
                'title' => 'Ocean Plastic Pollution: A Growing Crisis',
                'content' => 'Every year, millions of tons of plastic waste enter our oceans, threatening marine life and ecosystems. Discover the scale of the problem and potential solutions.',
                'environmental_score' => 75,
                'carbon_impact' => 3.2
            )
        );
        
        foreach ($articles as $article) {
            $post_id = wp_insert_post(array(
                'post_title' => $article['title'],
                'post_content' => $article['content'],
                'post_type' => 'env_article',
                'post_status' => 'publish',
                'meta_input' => array(
                    '_environmental_score' => $article['environmental_score'],
                    '_carbon_impact' => $article['carbon_impact'],
                    '_sample_content' => true
                )
            ));
            
            if ($post_id) {
                $created++;
            }
        }
    }
    
    // Sample Events
    if (isset($_POST['create_events'])) {
        $events = array(
            array(
                'title' => 'Earth Day Community Cleanup',
                'content' => 'Join us for our annual Earth Day community cleanup event. Help make our local environment cleaner and greener.',
                'event_date' => date('Y-m-d', strtotime('+30 days')),
                'event_time' => '09:00',
                'location' => 'Central Park'
            ),
            array(
                'title' => 'Sustainable Living Workshop',
                'content' => 'Learn practical tips for living a more sustainable lifestyle. Topics include waste reduction, energy conservation, and eco-friendly shopping.',
                'event_date' => date('Y-m-d', strtotime('+45 days')),
                'event_time' => '14:00',
                'location' => 'Community Center'
            )
        );
        
        foreach ($events as $event) {
            $post_id = wp_insert_post(array(
                'post_title' => $event['title'],
                'post_content' => $event['content'],
                'post_type' => 'env_event',
                'post_status' => 'publish',
                'meta_input' => array(
                    '_event_date' => $event['event_date'],
                    '_event_time' => $event['event_time'],
                    '_event_location' => $event['location'],
                    '_sample_content' => true
                )
            ));
            
            if ($post_id) {
                $created++;
            }
        }
    }
    
    return $created > 0;
}

function create_default_taxonomy_terms() {
    $created = 0;
    
    // Environmental Categories
    $categories = array(
        'Climate Change' => 'Content related to global warming and climate change',
        'Renewable Energy' => 'Solar, wind, hydroelectric and other renewable energy sources',
        'Waste Management' => 'Recycling, waste reduction, and disposal methods',
        'Conservation' => 'Wildlife and habitat conservation efforts',
        'Pollution Control' => 'Air, water, and soil pollution prevention and cleanup',
        'Sustainable Development' => 'Sustainable practices and green development'
    );
    
    foreach ($categories as $name => $description) {
        $term = wp_insert_term($name, 'env_category', array(
            'description' => $description,
            'slug' => sanitize_title($name)
        ));
        
        if (!is_wp_error($term)) {
            $created++;
        }
    }
    
    // Impact Levels
    $impact_levels = array(
        'Low Impact' => 'Minimal environmental impact',
        'Medium Impact' => 'Moderate environmental impact',
        'High Impact' => 'Significant environmental impact',
        'Critical Impact' => 'Severe environmental impact requiring immediate attention'
    );
    
    foreach ($impact_levels as $name => $description) {
        $term = wp_insert_term($name, 'impact_level', array(
            'description' => $description,
            'slug' => sanitize_title($name)
        ));
        
        if (!is_wp_error($term)) {
            $created++;
        }
    }
    
    // Sustainability Levels
    $sustainability_levels = array(
        'Highly Sustainable' => 'Extremely environmentally friendly practices',
        'Sustainable' => 'Good environmental practices',
        'Moderately Sustainable' => 'Some environmental considerations',
        'Low Sustainability' => 'Limited environmental benefits'
    );
    
    foreach ($sustainability_levels as $name => $description) {
        $term = wp_insert_term($name, 'sustainability_level', array(
            'description' => $description,
            'slug' => sanitize_title($name)
        ));
        
        if (!is_wp_error($term)) {
            $created++;
        }
    }
    
    return $created > 0;
}

?>
