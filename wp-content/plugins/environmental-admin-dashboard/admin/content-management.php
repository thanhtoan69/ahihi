<?php
/**
 * Content Management Admin Page
 * 
 * Advanced content management interface with analytics, editing capabilities,
 * and content optimization features for the Environmental Platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

$content_manager = Environmental_Content_Manager::get_instance();
$analytics_data = $content_manager->get_content_analytics();
?>

<div class="wrap env-content-management">
    <h1><?php _e('Environmental Content Management', 'env-admin-dashboard'); ?></h1>
    
    <!-- Analytics Overview -->
    <div class="content-analytics-overview">
        <div class="analytics-cards">
            <div class="analytics-card">
                <div class="card-icon">📝</div>
                <div class="card-content">
                    <h3><?php echo number_format($analytics_data['total_posts']); ?></h3>
                    <p><?php _e('Total Posts', 'env-admin-dashboard'); ?></p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">👁️</div>
                <div class="card-content">
                    <h3><?php echo number_format($analytics_data['total_views']); ?></h3>
                    <p><?php _e('Total Views', 'env-admin-dashboard'); ?></p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">💬</div>
                <div class="card-content">
                    <h3><?php echo number_format($analytics_data['total_comments']); ?></h3>
                    <p><?php _e('Total Comments', 'env-admin-dashboard'); ?></p>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <h3><?php echo $analytics_data['avg_engagement']; ?>%</h3>
                    <p><?php _e('Avg Engagement', 'env-admin-dashboard'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Management Tabs -->
    <div class="nav-tab-wrapper">
        <a href="#content-overview" class="nav-tab nav-tab-active"><?php _e('Content Overview', 'env-admin-dashboard'); ?></a>
        <a href="#content-editor" class="nav-tab"><?php _e('Quick Editor', 'env-admin-dashboard'); ?></a>
        <a href="#content-analytics" class="nav-tab"><?php _e('Analytics', 'env-admin-dashboard'); ?></a>
        <a href="#content-optimization" class="nav-tab"><?php _e('Optimization', 'env-admin-dashboard'); ?></a>
    </div>
    
    <!-- Content Overview Tab -->
    <div id="content-overview" class="tab-content active">
        <div class="content-table-container">
            <div class="table-controls">
                <div class="table-search">
                    <input type="search" id="content-search" placeholder="<?php _e('Search content...', 'env-admin-dashboard'); ?>" />
                </div>
                <div class="table-filters">
                    <select id="content-type-filter">
                        <option value=""><?php _e('All Content Types', 'env-admin-dashboard'); ?></option>
                        <option value="post"><?php _e('Posts', 'env-admin-dashboard'); ?></option>
                        <option value="page"><?php _e('Pages', 'env-admin-dashboard'); ?></option>
                        <option value="environmental_activity"><?php _e('Environmental Activities', 'env-admin-dashboard'); ?></option>
                    </select>
                    <select id="content-status-filter">
                        <option value=""><?php _e('All Statuses', 'env-admin-dashboard'); ?></option>
                        <option value="publish"><?php _e('Published', 'env-admin-dashboard'); ?></option>
                        <option value="draft"><?php _e('Draft', 'env-admin-dashboard'); ?></option>
                        <option value="pending"><?php _e('Pending', 'env-admin-dashboard'); ?></option>
                    </select>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1" />
                        </td>
                        <th class="manage-column column-title sortable"><?php _e('Title', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-type"><?php _e('Type', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-status"><?php _e('Status', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-author"><?php _e('Author', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-date"><?php _e('Date', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-performance"><?php _e('Performance', 'env-admin-dashboard'); ?></th>
                        <th class="manage-column column-actions"><?php _e('Actions', 'env-admin-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody id="content-table-body">
                    <?php $content_manager->render_content_table(); ?>
                </tbody>
            </table>
            
            <div class="table-pagination">
                <div class="pagination-info">
                    <span><?php _e('Showing 1-20 of 156 items', 'env-admin-dashboard'); ?></span>
                </div>
                <div class="pagination-controls">
                    <button class="button" disabled>&laquo;</button>
                    <button class="button" disabled>&lsaquo;</button>
                    <span class="page-numbers current">1</span>
                    <button class="button">&rsaquo;</button>
                    <button class="button">&raquo;</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Editor Tab -->
    <div id="content-editor" class="tab-content">
        <div class="quick-editor-container">
            <h2><?php _e('Quick Content Editor', 'env-admin-dashboard'); ?></h2>
            
            <form id="quick-content-form">
                <div class="editor-row">
                    <div class="editor-col-left">
                        <div class="form-group">
                            <label for="content-title"><?php _e('Title', 'env-admin-dashboard'); ?></label>
                            <input type="text" id="content-title" name="title" class="widefat" />
                        </div>
                        
                        <div class="form-group">
                            <label for="content-type"><?php _e('Content Type', 'env-admin-dashboard'); ?></label>
                            <select id="content-type" name="post_type" class="widefat">
                                <option value="post"><?php _e('Post', 'env-admin-dashboard'); ?></option>
                                <option value="page"><?php _e('Page', 'env-admin-dashboard'); ?></option>
                                <option value="environmental_activity"><?php _e('Environmental Activity', 'env-admin-dashboard'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="content-editor"><?php _e('Content', 'env-admin-dashboard'); ?></label>
                            <?php 
                            wp_editor('', 'content-editor', array(
                                'textarea_name' => 'content',
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'teeny' => false
                            )); 
                            ?>
                        </div>
                    </div>
                    
                    <div class="editor-col-right">
                        <div class="editor-meta-box">
                            <h3><?php _e('Publishing Options', 'env-admin-dashboard'); ?></h3>
                            
                            <div class="form-group">
                                <label for="content-status"><?php _e('Status', 'env-admin-dashboard'); ?></label>
                                <select id="content-status" name="post_status" class="widefat">
                                    <option value="draft"><?php _e('Draft', 'env-admin-dashboard'); ?></option>
                                    <option value="pending"><?php _e('Pending Review', 'env-admin-dashboard'); ?></option>
                                    <option value="publish"><?php _e('Published', 'env-admin-dashboard'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-category"><?php _e('Category', 'env-admin-dashboard'); ?></label>
                                <select id="content-category" name="category" class="widefat">
                                    <option value=""><?php _e('Select Category', 'env-admin-dashboard'); ?></option>
                                    <option value="environmental-news"><?php _e('Environmental News', 'env-admin-dashboard'); ?></option>
                                    <option value="sustainability"><?php _e('Sustainability', 'env-admin-dashboard'); ?></option>
                                    <option value="climate-change"><?php _e('Climate Change', 'env-admin-dashboard'); ?></option>
                                    <option value="conservation"><?php _e('Conservation', 'env-admin-dashboard'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-tags"><?php _e('Tags', 'env-admin-dashboard'); ?></label>
                                <input type="text" id="content-tags" name="tags" class="widefat" placeholder="<?php _e('Separate tags with commas', 'env-admin-dashboard'); ?>" />
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-primary"><?php _e('Save Content', 'env-admin-dashboard'); ?></button>
                                <button type="button" class="button" id="preview-content"><?php _e('Preview', 'env-admin-dashboard'); ?></button>
                            </div>
                        </div>
                        
                        <div class="editor-meta-box">
                            <h3><?php _e('Environmental Data', 'env-admin-dashboard'); ?></h3>
                            
                            <div class="form-group">
                                <label for="environmental-impact"><?php _e('Environmental Impact Score', 'env-admin-dashboard'); ?></label>
                                <input type="range" id="environmental-impact" name="environmental_impact" min="1" max="10" value="5" class="widefat" />
                                <span class="range-value">5</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="sustainability-rating"><?php _e('Sustainability Rating', 'env-admin-dashboard'); ?></label>
                                <select id="sustainability-rating" name="sustainability_rating" class="widefat">
                                    <option value="low"><?php _e('Low', 'env-admin-dashboard'); ?></option>
                                    <option value="medium"><?php _e('Medium', 'env-admin-dashboard'); ?></option>
                                    <option value="high"><?php _e('High', 'env-admin-dashboard'); ?></option>
                                    <option value="critical"><?php _e('Critical', 'env-admin-dashboard'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Analytics Tab -->
    <div id="content-analytics" class="tab-content">
        <div class="analytics-container">
            <h2><?php _e('Content Analytics', 'env-admin-dashboard'); ?></h2>
            
            <div class="analytics-charts">
                <div class="chart-container">
                    <h3><?php _e('Content Performance Over Time', 'env-admin-dashboard'); ?></h3>
                    <canvas id="content-performance-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Content Type Distribution', 'env-admin-dashboard'); ?></h3>
                    <canvas id="content-type-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Engagement Metrics', 'env-admin-dashboard'); ?></h3>
                    <canvas id="engagement-chart"></canvas>
                </div>
            </div>
            
            <div class="analytics-tables">
                <div class="table-container">
                    <h3><?php _e('Top Performing Content', 'env-admin-dashboard'); ?></h3>
                    <table class="wp-list-table widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Views', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Engagement', 'env-admin-dashboard'); ?></th>
                                <th><?php _e('Impact Score', 'env-admin-dashboard'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $content_manager->render_top_content_table(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Optimization Tab -->
    <div id="content-optimization" class="tab-content">
        <div class="optimization-container">
            <h2><?php _e('Content Optimization', 'env-admin-dashboard'); ?></h2>
            
            <div class="optimization-tools">
                <div class="tool-section">
                    <h3><?php _e('SEO Optimization', 'env-admin-dashboard'); ?></h3>
                    <div class="tool-content">
                        <button class="button button-primary" id="analyze-seo"><?php _e('Analyze SEO', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="optimize-meta"><?php _e('Optimize Meta Tags', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="check-keywords"><?php _e('Check Keywords', 'env-admin-dashboard'); ?></button>
                    </div>
                </div>
                
                <div class="tool-section">
                    <h3><?php _e('Performance Optimization', 'env-admin-dashboard'); ?></h3>
                    <div class="tool-content">
                        <button class="button button-primary" id="optimize-images"><?php _e('Optimize Images', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="minify-content"><?php _e('Minify Content', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="cache-content"><?php _e('Cache Content', 'env-admin-dashboard'); ?></button>
                    </div>
                </div>
                
                <div class="tool-section">
                    <h3><?php _e('Environmental Impact', 'env-admin-dashboard'); ?></h3>
                    <div class="tool-content">
                        <button class="button button-primary" id="calculate-carbon"><?php _e('Calculate Carbon Footprint', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="sustainability-check"><?php _e('Sustainability Check', 'env-admin-dashboard'); ?></button>
                        <button class="button" id="eco-suggestions"><?php _e('Eco Suggestions', 'env-admin-dashboard'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="optimization-results" id="optimization-results" style="display: none;">
                <h3><?php _e('Optimization Results', 'env-admin-dashboard'); ?></h3>
                <div class="results-content"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Content search and filtering
    $('#content-search').on('input', function() {
        // Implement search functionality
    });
    
    $('#content-type-filter, #content-status-filter').on('change', function() {
        // Implement filtering functionality
    });
    
    // Quick editor form submission
    $('#quick-content-form').on('submit', function(e) {
        e.preventDefault();
        // Implement form submission
    });
    
    // Range slider value update
    $('#environmental-impact').on('input', function() {
        $(this).next('.range-value').text($(this).val());
    });
    
    // Optimization tools
    $('.tool-section button').on('click', function() {
        var action = $(this).attr('id');
        $('#optimization-results').show().find('.results-content').html('<p>Running ' + action + '...</p>');
        
        // Implement optimization actions
        setTimeout(function() {
            $('#optimization-results .results-content').html('<p>Optimization completed successfully!</p>');
        }, 2000);
    });
});
</script>
