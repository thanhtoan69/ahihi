<?php
/**
 * Content Management Admin Page
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && wp_verify_nonce($_POST['_wpnonce'], 'ep_content_bulk_action')) {
    $action = sanitize_text_field($_POST['bulk_action']);
    $post_ids = array_map('intval', $_POST['post_ids']);
    
    switch ($action) {
        case 'assign_category':
            $category_id = intval($_POST['category_id']);
            foreach ($post_ids as $post_id) {
                wp_set_post_terms($post_id, array($category_id), 'env_category', true);
            }
            echo '<div class="notice notice-success"><p>' . count($post_ids) . ' posts updated with new category.</p></div>';
            break;
            
        case 'update_environmental_score':
            $score = intval($_POST['environmental_score']);
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_environmental_score', $score);
            }
            echo '<div class="notice notice-success"><p>' . count($post_ids) . ' posts updated with environmental score.</p></div>';
            break;
    }
}

// Get statistics
$post_types = array('env_article', 'env_report', 'env_alert', 'env_event', 'env_project', 'eco_product', 'community_post', 'edu_resource', 'waste_class', 'env_petition', 'item_exchange');
$stats = array();

foreach ($post_types as $post_type) {
    $count = wp_count_posts($post_type);
    $stats[$post_type] = array(
        'total' => $count->publish + $count->draft + $count->pending,
        'published' => $count->publish,
        'draft' => $count->draft,
        'pending' => $count->pending
    );
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ep-admin-header">
        <div class="ep-admin-tabs">
            <a href="#overview" class="nav-tab nav-tab-active">Overview</a>
            <a href="#bulk-actions" class="nav-tab">Bulk Actions</a>
            <a href="#content-relationships" class="nav-tab">Content Relationships</a>
            <a href="#analytics" class="nav-tab">Analytics</a>
        </div>
    </div>

    <!-- Overview Tab -->
    <div id="overview" class="tab-content active">
        <div class="ep-dashboard-grid">
            <div class="ep-card">
                <h3>📊 Content Statistics</h3>
                <div class="ep-stats-grid">
                    <?php foreach ($stats as $post_type => $stat): ?>
                    <div class="ep-stat-item">
                        <div class="ep-stat-icon">
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
                        <div class="ep-stat-content">
                            <div class="ep-stat-number"><?php echo $stat['total']; ?></div>
                            <div class="ep-stat-label"><?php echo ucwords(str_replace('_', ' ', $post_type)); ?></div>
                            <div class="ep-stat-breakdown">
                                Published: <?php echo $stat['published']; ?> | 
                                Draft: <?php echo $stat['draft']; ?> | 
                                Pending: <?php echo $stat['pending']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ep-card">
                <h3>🏷️ Taxonomy Statistics</h3>
                <div class="ep-taxonomy-stats">
                    <?php
                    $taxonomies = array('env_category', 'env_tag', 'impact_level', 'region', 'sustainability_level');
                    foreach ($taxonomies as $taxonomy):
                        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                        $count = is_array($terms) ? count($terms) : 0;
                    ?>
                    <div class="ep-taxonomy-item">
                        <strong><?php echo ucwords(str_replace('_', ' ', $taxonomy)); ?>:</strong>
                        <span><?php echo $count; ?> terms</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ep-card">
                <h3>🎯 Quick Actions</h3>
                <div class="ep-quick-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=env_article'); ?>" class="button button-primary">
                        ➕ New Article
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=env_event'); ?>" class="button button-primary">
                        📅 New Event
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=env_project'); ?>" class="button button-primary">
                        🚀 New Project
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=env_category'); ?>" class="button button-secondary">
                        🏷️ Manage Categories
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Tab -->
    <div id="bulk-actions" class="tab-content">
        <div class="ep-card">
            <h3>⚡ Bulk Content Operations</h3>
            
            <form method="post" id="bulk-content-form">
                <?php wp_nonce_field('ep_content_bulk_action'); ?>
                
                <div class="ep-bulk-section">
                    <h4>Select Content</h4>
                    <div class="ep-content-selector">
                        <label for="post_type_filter">Post Type:</label>
                        <select id="post_type_filter" name="post_type_filter">
                            <option value="">All Post Types</option>
                            <?php foreach ($post_types as $post_type): ?>
                            <option value="<?php echo $post_type; ?>"><?php echo ucwords(str_replace('_', ' ', $post_type)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="button" id="load-content" class="button">Load Content</button>
                    </div>
                    
                    <div id="content-list" class="ep-content-list">
                        <!-- Content will be loaded here via AJAX -->
                    </div>
                </div>
                
                <div class="ep-bulk-section">
                    <h4>Bulk Actions</h4>
                    <div class="ep-bulk-actions">
                        <label for="bulk_action">Action:</label>
                        <select name="bulk_action" id="bulk_action">
                            <option value="">Select Action</option>
                            <option value="assign_category">Assign Category</option>
                            <option value="update_environmental_score">Update Environmental Score</option>
                            <option value="duplicate_content">Duplicate Content</option>
                            <option value="export_content">Export Content</option>
                        </select>
                        
                        <div id="action-options" class="ep-action-options">
                            <!-- Action-specific options will appear here -->
                        </div>
                        
                        <button type="submit" class="button button-primary">Execute Action</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Content Relationships Tab -->
    <div id="content-relationships" class="tab-content">
        <div class="ep-card">
            <h3>🔗 Content Relationships</h3>
            
            <div class="ep-relationship-tools">
                <div class="ep-relationship-section">
                    <h4>Related Content Analysis</h4>
                    <p>Analyze and manage relationships between different content types.</p>
                    
                    <button type="button" id="analyze-relationships" class="button button-secondary">
                        🔍 Analyze Relationships
                    </button>
                    
                    <div id="relationship-results" class="ep-relationship-results">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
                
                <div class="ep-relationship-section">
                    <h4>Auto-Link Content</h4>
                    <p>Automatically create relationships based on categories, tags, and keywords.</p>
                    
                    <label for="auto_link_strength">Link Strength:</label>
                    <select id="auto_link_strength">
                        <option value="high">High (Exact matches)</option>
                        <option value="medium" selected>Medium (Similar content)</option>
                        <option value="low">Low (Related topics)</option>
                    </select>
                    
                    <button type="button" id="auto-link-content" class="button button-primary">
                        🤖 Auto-Link Content
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tab -->
    <div id="analytics" class="tab-content">
        <div class="ep-card">
            <h3>📈 Content Analytics</h3>
            
            <div class="ep-analytics-grid">
                <div class="ep-analytics-item">
                    <h4>📊 Popular Content</h4>
                    <div id="popular-content-chart">
                        <!-- Chart will be rendered here -->
                    </div>
                </div>
                
                <div class="ep-analytics-item">
                    <h4>🌱 Environmental Impact</h4>
                    <div id="environmental-impact-chart">
                        <!-- Chart will be rendered here -->
                    </div>
                </div>
                
                <div class="ep-analytics-item">
                    <h4>📅 Content Creation Trends</h4>
                    <div id="content-trends-chart">
                        <!-- Chart will be rendered here -->
                    </div>
                </div>
                
                <div class="ep-analytics-item">
                    <h4>🏷️ Category Distribution</h4>
                    <div id="category-distribution-chart">
                        <!-- Chart will be rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ep-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.ep-stat-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2e7d32;
}

.ep-stat-icon {
    font-size: 24px;
    margin-right: 15px;
}

.ep-stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2e7d32;
}

.ep-stat-label {
    font-weight: 500;
    color: #333;
}

.ep-stat-breakdown {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.ep-taxonomy-stats {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}

.ep-taxonomy-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.ep-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
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

.ep-bulk-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.ep-content-selector {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
}

.ep-content-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 10px;
}

.ep-bulk-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.ep-action-options {
    display: flex;
    gap: 10px;
    align-items: center;
}

.ep-relationship-tools {
    display: grid;
    gap: 30px;
    margin-top: 20px;
}

.ep-relationship-section {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.ep-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-analytics-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    text-align: center;
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
    
    // Load content for bulk actions
    $('#load-content').click(function() {
        var postType = $('#post_type_filter').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_load_content_for_bulk',
                post_type: postType,
                nonce: '<?php echo wp_create_nonce("ep_load_content"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#content-list').html(response.data.html);
                }
            }
        });
    });
    
    // Bulk action options
    $('#bulk_action').change(function() {
        var action = $(this).val();
        var optionsHtml = '';
        
        switch(action) {
            case 'assign_category':
                optionsHtml = '<select name="category_id"><?php
                $categories = get_terms(array("taxonomy" => "env_category", "hide_empty" => false));
                foreach ($categories as $category) {
                    echo "<option value=\"{$category->term_id}\">{$category->name}</option>";
                }
                ?></select>';
                break;
            case 'update_environmental_score':
                optionsHtml = '<input type="number" name="environmental_score" min="1" max="100" placeholder="Environmental Score (1-100)">';
                break;
        }
        
        $('#action-options').html(optionsHtml);
    });
    
    // Analyze relationships
    $('#analyze-relationships').click(function() {
        $(this).prop('disabled', true).text('Analyzing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_analyze_relationships',
                nonce: '<?php echo wp_create_nonce("ep_analyze_relationships"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#relationship-results').html(response.data.html);
                }
                $('#analyze-relationships').prop('disabled', false).text('🔍 Analyze Relationships');
            }
        });
    });
    
    // Auto-link content
    $('#auto-link-content').click(function() {
        var strength = $('#auto_link_strength').val();
        $(this).prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_auto_link_content',
                strength: strength,
                nonce: '<?php echo wp_create_nonce("ep_auto_link_content"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Auto-linking completed! ' + response.data.message);
                }
                $('#auto-link-content').prop('disabled', false).text('🤖 Auto-Link Content');
            }
        });
    });
});
</script>
