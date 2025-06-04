<?php
/**
 * Archive Template for Environmental Reports
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-reports-archive">
    <!-- Archive Header -->
    <header class="ep-archive-header">
        <div class="ep-container">
            <h1 class="ep-archive-title">
                <?php _e('Environmental Reports', 'environmental-platform-core'); ?>
            </h1>
            <p class="ep-archive-description">
                <?php _e('Explore comprehensive environmental research reports, studies, and assessments from leading organizations and researchers.', 'environmental-platform-core'); ?>
            </p>
        </div>
    </header>
    
    <!-- Filters & Search -->
    <div class="ep-archive-filters">
        <div class="ep-container">
            <form class="ep-filter-form" method="get">
                <div class="ep-filter-row">
                    <div class="ep-filter-group">
                        <label for="report_type"><?php _e('Report Type', 'environmental-platform-core'); ?></label>
                        <select name="report_type" id="report_type">
                            <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                            <option value="research" <?php selected(get_query_var('report_type'), 'research'); ?>><?php _e('Research', 'environmental-platform-core'); ?></option>
                            <option value="policy" <?php selected(get_query_var('report_type'), 'policy'); ?>><?php _e('Policy', 'environmental-platform-core'); ?></option>
                            <option value="assessment" <?php selected(get_query_var('report_type'), 'assessment'); ?>><?php _e('Assessment', 'environmental-platform-core'); ?></option>
                            <option value="monitoring" <?php selected(get_query_var('report_type'), 'monitoring'); ?>><?php _e('Monitoring', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <div class="ep-filter-group">
                        <label for="topic"><?php _e('Topic', 'environmental-platform-core'); ?></label>
                        <select name="topic" id="topic">
                            <option value=""><?php _e('All Topics', 'environmental-platform-core'); ?></option>
                            <?php
                            $topics = get_terms(array(
                                'taxonomy' => 'environmental_topic',
                                'hide_empty' => true
                            ));
                            foreach ($topics as $topic):
                            ?>
                                <option value="<?php echo $topic->slug; ?>" <?php selected(get_query_var('topic'), $topic->slug); ?>>
                                    <?php echo esc_html($topic->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ep-filter-group">
                        <label for="year"><?php _e('Year', 'environmental-platform-core'); ?></label>
                        <select name="year" id="year">
                            <option value=""><?php _e('All Years', 'environmental-platform-core'); ?></option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 10; $year--):
                            ?>
                                <option value="<?php echo $year; ?>" <?php selected(get_query_var('year'), $year); ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="ep-filter-group">
                        <label for="search"><?php _e('Search', 'environmental-platform-core'); ?></label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="<?php echo esc_attr(get_search_query()); ?>" 
                               placeholder="<?php _e('Search reports...', 'environmental-platform-core'); ?>">
                    </div>
                    
                    <div class="ep-filter-group">
                        <button type="submit" class="ep-btn ep-btn-primary">
                            <?php _e('Filter', 'environmental-platform-core'); ?>
                        </button>
                        <a href="<?php echo get_post_type_archive_link('env_report'); ?>" class="ep-btn ep-btn-secondary">
                            <?php _e('Clear', 'environmental-platform-core'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reports Grid -->
    <div class="ep-archive-content">
        <div class="ep-container">
            <div class="ep-row">
                <div class="ep-col-9">
                    
                    <!-- Results Info -->
                    <div class="ep-results-info">
                        <p class="ep-results-count">
                            <?php
                            global $wp_query;
                            printf(
                                _n(
                                    'Showing %d report',
                                    'Showing %d reports',
                                    $wp_query->found_posts,
                                    'environmental-platform-core'
                                ),
                                $wp_query->found_posts
                            );
                            ?>
                        </p>
                        
                        <div class="ep-view-options">
                            <button class="ep-view-btn active" data-view="grid">
                                <i class="ep-icon-grid"></i>
                            </button>
                            <button class="ep-view-btn" data-view="list">
                                <i class="ep-icon-list"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Reports List -->
                    <?php if (have_posts()): ?>
                        <div class="ep-reports-grid" id="reports-container">
                            <?php while (have_posts()): the_post(); ?>
                                <article class="ep-report-card">
                                    
                                    <!-- Report Thumbnail -->
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="ep-report-thumbnail">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', array('class' => 'ep-report-image')); ?>
                                            </a>
                                            
                                            <?php
                                            $report_file = get_post_meta(get_the_ID(), '_ep_report_file', true);
                                            if ($report_file):
                                            ?>
                                                <a href="<?php echo esc_url($report_file); ?>" 
                                                   class="ep-download-overlay" 
                                                   target="_blank">
                                                    <i class="ep-icon-download"></i>
                                                    <span><?php _e('Download', 'environmental-platform-core'); ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Report Content -->
                                    <div class="ep-report-content">
                                        
                                        <!-- Report Meta -->
                                        <div class="ep-report-meta">
                                            <?php
                                            $report_type = get_post_meta(get_the_ID(), '_ep_report_type', true);
                                            $publication_date = get_post_meta(get_the_ID(), '_ep_publication_date', true);
                                            ?>
                                            
                                            <?php if ($report_type): ?>
                                                <span class="ep-report-type ep-badge-<?php echo esc_attr(sanitize_title($report_type)); ?>">
                                                    <?php echo esc_html($report_type); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <time class="ep-report-date" datetime="<?php echo esc_attr($publication_date ?: get_the_date('c')); ?>">
                                                <?php echo $publication_date ? date_i18n(get_option('date_format'), strtotime($publication_date)) : get_the_date(); ?>
                                            </time>
                                        </div>
                                        
                                        <!-- Report Title -->
                                        <h2 class="ep-report-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        
                                        <!-- Report Excerpt -->
                                        <div class="ep-report-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 30, '...'); ?>
                                        </div>
                                        
                                        <!-- Report Author -->
                                        <?php
                                        $author_organization = get_post_meta(get_the_ID(), '_ep_author_organization', true);
                                        if ($author_organization):
                                        ?>
                                            <div class="ep-report-author">
                                                <i class="ep-icon-organization"></i>
                                                <span><?php echo esc_html($author_organization); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Report Topics -->
                                        <?php
                                        $topics = get_the_terms(get_the_ID(), 'environmental_topic');
                                        if ($topics && !is_wp_error($topics)):
                                        ?>
                                            <div class="ep-report-topics">
                                                <?php foreach (array_slice($topics, 0, 3) as $topic): ?>
                                                    <a href="<?php echo get_term_link($topic); ?>" class="ep-topic-tag">
                                                        <?php echo esc_html($topic->name); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Report Actions -->
                                        <div class="ep-report-actions">
                                            <a href="<?php the_permalink(); ?>" class="ep-btn ep-btn-primary ep-btn-sm">
                                                <?php _e('Read More', 'environmental-platform-core'); ?>
                                            </a>
                                            
                                            <?php if ($report_file): ?>
                                                <a href="<?php echo esc_url($report_file); ?>" 
                                                   class="ep-btn ep-btn-secondary ep-btn-sm" 
                                                   target="_blank">
                                                    <i class="ep-icon-download"></i>
                                                    <?php _e('Download', 'environmental-platform-core'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                </article>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="ep-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $wp_query->max_num_pages,
                                'current' => max(1, get_query_var('paged')),
                                'prev_text' => __('← Previous', 'environmental-platform-core'),
                                'next_text' => __('Next →', 'environmental-platform-core'),
                                'mid_size' => 2
                            ));
                            ?>
                        </div>
                        
                    <?php else: ?>
                        
                        <!-- No Results -->
                        <div class="ep-no-results">
                            <div class="ep-no-results-content">
                                <h2><?php _e('No reports found', 'environmental-platform-core'); ?></h2>
                                <p><?php _e('Try adjusting your search criteria or browse all reports.', 'environmental-platform-core'); ?></p>
                                <a href="<?php echo get_post_type_archive_link('env_report'); ?>" class="ep-btn ep-btn-primary">
                                    <?php _e('View All Reports', 'environmental-platform-core'); ?>
                                </a>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
                
                <!-- Sidebar -->
                <div class="ep-col-3">
                    <aside class="ep-archive-sidebar">
                        
                        <!-- Featured Report -->
                        <?php
                        $featured_report = get_posts(array(
                            'post_type' => 'env_report',
                            'posts_per_page' => 1,
                            'meta_query' => array(
                                array(
                                    'key' => '_ep_featured_report',
                                    'value' => '1',
                                    'compare' => '='
                                )
                            )
                        ));
                        
                        if ($featured_report):
                            $report = $featured_report[0];
                        ?>
                            <div class="ep-widget ep-featured-report">
                                <h3><?php _e('Featured Report', 'environmental-platform-core'); ?></h3>
                                <div class="ep-featured-content">
                                    <?php if (has_post_thumbnail($report->ID)): ?>
                                        <div class="ep-featured-thumb">
                                            <a href="<?php echo get_permalink($report->ID); ?>">
                                                <?php echo get_the_post_thumbnail($report->ID, 'medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <h4>
                                        <a href="<?php echo get_permalink($report->ID); ?>">
                                            <?php echo get_the_title($report->ID); ?>
                                        </a>
                                    </h4>
                                    <p><?php echo wp_trim_words(get_the_excerpt($report->ID), 20); ?></p>
                                    <a href="<?php echo get_permalink($report->ID); ?>" class="ep-read-more">
                                        <?php _e('Read Full Report', 'environmental-platform-core'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Popular Topics -->
                        <?php
                        $popular_topics = get_terms(array(
                            'taxonomy' => 'environmental_topic',
                            'orderby' => 'count',
                            'order' => 'DESC',
                            'number' => 8,
                            'hide_empty' => true
                        ));
                        
                        if ($popular_topics):
                        ?>
                            <div class="ep-widget ep-popular-topics">
                                <h3><?php _e('Popular Topics', 'environmental-platform-core'); ?></h3>
                                <div class="ep-topic-cloud">
                                    <?php foreach ($popular_topics as $topic): ?>
                                        <a href="<?php echo get_term_link($topic); ?>" 
                                           class="ep-topic-link"
                                           data-count="<?php echo $topic->count; ?>">
                                            <?php echo esc_html($topic->name); ?>
                                            <span class="ep-topic-count">(<?php echo $topic->count; ?>)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Recent Reports -->
                        <?php
                        $recent_reports = get_posts(array(
                            'post_type' => 'env_report',
                            'posts_per_page' => 5,
                            'post__not_in' => array(get_the_ID())
                        ));
                        
                        if ($recent_reports):
                        ?>
                            <div class="ep-widget ep-recent-reports">
                                <h3><?php _e('Recent Reports', 'environmental-platform-core'); ?></h3>
                                <ul class="ep-recent-list">
                                    <?php foreach ($recent_reports as $recent): ?>
                                        <li class="ep-recent-item">
                                            <a href="<?php echo get_permalink($recent->ID); ?>" class="ep-recent-link">
                                                <h4><?php echo get_the_title($recent->ID); ?></h4>
                                                <span class="ep-recent-date">
                                                    <?php echo get_the_date('', $recent->ID); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Report Statistics -->
                        <div class="ep-widget ep-report-stats">
                            <h3><?php _e('Report Statistics', 'environmental-platform-core'); ?></h3>
                            <div class="ep-stats-grid">
                                <?php
                                $total_reports = wp_count_posts('env_report')->publish;
                                $research_reports = get_posts(array(
                                    'post_type' => 'env_report',
                                    'meta_query' => array(
                                        array(
                                            'key' => '_ep_report_type',
                                            'value' => 'research',
                                            'compare' => '='
                                        )
                                    ),
                                    'fields' => 'ids'
                                ));
                                ?>
                                
                                <div class="ep-stat-item">
                                    <span class="ep-stat-number"><?php echo $total_reports; ?></span>
                                    <span class="ep-stat-label"><?php _e('Total Reports', 'environmental-platform-core'); ?></span>
                                </div>
                                
                                <div class="ep-stat-item">
                                    <span class="ep-stat-number"><?php echo count($research_reports); ?></span>
                                    <span class="ep-stat-label"><?php _e('Research Reports', 'environmental-platform-core'); ?></span>
                                </div>
                                
                                <div class="ep-stat-item">
                                    <span class="ep-stat-number"><?php echo count($popular_topics); ?></span>
                                    <span class="ep-stat-label"><?php _e('Topics Covered', 'environmental-platform-core'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                    </aside>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ep-reports-archive {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ep-archive-header {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.ep-archive-title {
    font-size: 2.5em;
    font-weight: 700;
    margin-bottom: 20px;
}

.ep-archive-description {
    font-size: 1.1em;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.ep-archive-filters {
    background-color: #f8f9fa;
    padding: 30px 0;
    border-bottom: 1px solid #e9ecef;
}

.ep-filter-row {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.ep-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.ep-filter-group label {
    font-weight: 600;
    font-size: 0.9em;
    color: #495057;
}

.ep-filter-group select,
.ep-filter-group input {
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    font-size: 0.9em;
}

.ep-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.ep-btn-primary {
    background-color: #4a7c59;
    color: white;
}

.ep-btn-secondary {
    background-color: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.ep-btn-sm {
    padding: 8px 16px;
    font-size: 0.9em;
}

.ep-archive-content {
    padding: 60px 0;
}

.ep-results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.ep-view-options {
    display: flex;
    gap: 10px;
}

.ep-view-btn {
    padding: 8px 12px;
    border: 1px solid #e9ecef;
    background-color: white;
    cursor: pointer;
    border-radius: 5px;
}

.ep-view-btn.active {
    background-color: #4a7c59;
    color: white;
}

.ep-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.ep-report-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.ep-report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.ep-report-thumbnail {
    position: relative;
    overflow: hidden;
}

.ep-report-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.ep-report-card:hover .ep-report-image {
    transform: scale(1.05);
}

.ep-download-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(74, 124, 89, 0.9);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ep-report-card:hover .ep-download-overlay {
    opacity: 1;
}

.ep-report-content {
    padding: 25px;
}

.ep-report-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.ep-report-type {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: 600;
    text-transform: uppercase;
}

.ep-badge-research { background-color: #007cba; color: white; }
.ep-badge-policy { background-color: #d63384; color: white; }
.ep-badge-assessment { background-color: #fd7e14; color: white; }
.ep-badge-monitoring { background-color: #6f42c1; color: white; }

.ep-report-date {
    font-size: 0.9em;
    color: #6c757d;
}

.ep-report-title {
    margin: 0 0 15px 0;
    font-size: 1.2em;
    line-height: 1.3;
}

.ep-report-title a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.ep-report-title a:hover {
    color: #4a7c59;
}

.ep-report-excerpt {
    color: #6c757d;
    margin-bottom: 15px;
    line-height: 1.5;
}

.ep-report-author {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #495057;
}

.ep-report-topics {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.ep-topic-tag {
    background-color: #e9ecef;
    color: #495057;
    padding: 3px 8px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.8em;
    transition: all 0.3s ease;
}

.ep-topic-tag:hover {
    background-color: #4a7c59;
    color: white;
}

.ep-report-actions {
    display: flex;
    gap: 10px;
}

.ep-pagination {
    text-align: center;
    margin-top: 40px;
}

.ep-pagination .page-numbers {
    display: inline-block;
    padding: 8px 16px;
    margin: 0 5px;
    border: 1px solid #e9ecef;
    color: #495057;
    text-decoration: none;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.ep-pagination .page-numbers:hover,
.ep-pagination .page-numbers.current {
    background-color: #4a7c59;
    color: white;
    border-color: #4a7c59;
}

.ep-no-results {
    text-align: center;
    padding: 60px 20px;
}

.ep-no-results-content h2 {
    color: #6c757d;
    margin-bottom: 20px;
}

.ep-archive-sidebar {
    padding-left: 30px;
}

.ep-widget {
    background-color: white;
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
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.ep-featured-thumb {
    margin-bottom: 15px;
}

.ep-featured-thumb img {
    width: 100%;
    border-radius: 5px;
}

.ep-featured-content h4 {
    margin-bottom: 10px;
}

.ep-featured-content h4 a {
    color: #2c3e50;
    text-decoration: none;
}

.ep-read-more {
    color: #4a7c59;
    text-decoration: none;
    font-weight: 600;
}

.ep-topic-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.ep-topic-link {
    background-color: #f8f9fa;
    padding: 5px 10px;
    border-radius: 15px;
    text-decoration: none;
    color: #495057;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.ep-topic-link:hover {
    background-color: #4a7c59;
    color: white;
}

.ep-topic-count {
    font-size: 0.8em;
    opacity: 0.7;
}

.ep-recent-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-recent-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.ep-recent-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.ep-recent-link {
    text-decoration: none;
    color: #333;
}

.ep-recent-link h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
    color: #4a7c59;
}

.ep-recent-date {
    font-size: 0.8em;
    color: #6c757d;
}

.ep-stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.ep-stat-item {
    text-align: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.ep-stat-number {
    display: block;
    font-size: 1.5em;
    font-weight: 700;
    color: #4a7c59;
}

.ep-stat-label {
    font-size: 0.9em;
    color: #6c757d;
}

@media (max-width: 768px) {
    .ep-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ep-filter-group {
        width: 100%;
    }
    
    .ep-results-info {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .ep-reports-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-archive-sidebar {
        padding-left: 0;
        margin-top: 40px;
    }
}
</style>

<script>
// View toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.ep-view-btn');
    const reportsContainer = document.getElementById('reports-container');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            
            // Update active button
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update container class
            reportsContainer.className = view === 'list' ? 'ep-reports-list' : 'ep-reports-grid';
        });
    });
});
</script>

<?php get_footer(); ?>
