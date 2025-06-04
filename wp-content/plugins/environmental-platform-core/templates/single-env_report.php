<?php
/**
 * Single Environmental Report Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-report-single">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-report-article'); ?>>
            
            <!-- Report Header -->
            <header class="ep-report-header">
                <div class="ep-container">
                    <div class="ep-report-meta">
                        <?php
                        $report_type = get_post_meta(get_the_ID(), '_ep_report_type', true);
                        $publication_date = get_post_meta(get_the_ID(), '_ep_publication_date', true);
                        $report_status = get_post_meta(get_the_ID(), '_ep_report_status', true);
                        ?>
                        
                        <?php if ($report_type): ?>
                            <span class="ep-report-type ep-badge ep-badge-<?php echo esc_attr(sanitize_title($report_type)); ?>">
                                <?php echo esc_html($report_type); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($report_status): ?>
                            <span class="ep-report-status ep-badge ep-badge-<?php echo esc_attr(sanitize_title($report_status)); ?>">
                                <?php echo esc_html($report_status); ?>
                            </span>
                        <?php endif; ?>
                        
                        <time class="ep-publication-date" datetime="<?php echo esc_attr($publication_date); ?>">
                            <?php 
                            if ($publication_date) {
                                echo date_i18n(get_option('date_format'), strtotime($publication_date));
                            }
                            ?>
                        </time>
                    </div>
                    
                    <h1 class="ep-report-title"><?php the_title(); ?></h1>
                    
                    <?php if (has_excerpt()): ?>
                        <div class="ep-report-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Report Actions -->
                    <div class="ep-report-actions">
                        <?php
                        $report_file = get_post_meta(get_the_ID(), '_ep_report_file', true);
                        if ($report_file):
                        ?>
                            <a href="<?php echo esc_url($report_file); ?>" 
                               class="ep-btn ep-btn-primary ep-download-report" 
                               target="_blank" 
                               rel="noopener">
                                <i class="ep-icon-download"></i>
                                <?php _e('Download Full Report', 'environmental-platform-core'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <button class="ep-btn ep-btn-secondary ep-share-report" 
                                onclick="epShareReport()">
                            <i class="ep-icon-share"></i>
                            <?php _e('Share Report', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Report Content -->
            <div class="ep-report-content">
                <div class="ep-container">
                    <div class="ep-row">
                        <div class="ep-col-8">
                            <!-- Featured Image -->
                            <?php if (has_post_thumbnail()): ?>
                                <div class="ep-report-featured-image">
                                    <?php the_post_thumbnail('large', array('class' => 'ep-responsive-image')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Report Body -->
                            <div class="ep-report-body">
                                <?php the_content(); ?>
                            </div>
                            
                            <!-- Key Findings -->
                            <?php
                            $key_findings = get_post_meta(get_the_ID(), '_ep_key_findings', true);
                            if ($key_findings):
                            ?>
                                <section class="ep-key-findings">
                                    <h2><?php _e('Key Findings', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-findings-content">
                                        <?php echo wp_kses_post($key_findings); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Data Visualizations -->
                            <?php
                            $data_charts = get_post_meta(get_the_ID(), '_ep_data_charts', true);
                            if ($data_charts):
                            ?>
                                <section class="ep-data-visualizations">
                                    <h2><?php _e('Data & Visualizations', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-charts-container">
                                        <?php echo wp_kses_post($data_charts); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Recommendations -->
                            <?php
                            $recommendations = get_post_meta(get_the_ID(), '_ep_recommendations', true);
                            if ($recommendations):
                            ?>
                                <section class="ep-recommendations">
                                    <h2><?php _e('Recommendations', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-recommendations-content">
                                        <?php echo wp_kses_post($recommendations); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Report Navigation -->
                            <nav class="ep-report-navigation">
                                <?php
                                $prev_post = get_previous_post();
                                $next_post = get_next_post();
                                ?>
                                
                                <?php if ($prev_post): ?>
                                    <div class="ep-nav-previous">
                                        <a href="<?php echo get_permalink($prev_post->ID); ?>" class="ep-nav-link">
                                            <span class="ep-nav-label"><?php _e('Previous Report', 'environmental-platform-core'); ?></span>
                                            <span class="ep-nav-title"><?php echo get_the_title($prev_post->ID); ?></span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($next_post): ?>
                                    <div class="ep-nav-next">
                                        <a href="<?php echo get_permalink($next_post->ID); ?>" class="ep-nav-link">
                                            <span class="ep-nav-label"><?php _e('Next Report', 'environmental-platform-core'); ?></span>
                                            <span class="ep-nav-title"><?php echo get_the_title($next_post->ID); ?></span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </nav>
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="ep-col-4">
                            <aside class="ep-report-sidebar">
                                
                                <!-- Report Details -->
                                <div class="ep-widget ep-report-details">
                                    <h3><?php _e('Report Details', 'environmental-platform-core'); ?></h3>
                                    <dl class="ep-details-list">
                                        <?php
                                        $author_organization = get_post_meta(get_the_ID(), '_ep_author_organization', true);
                                        $research_period = get_post_meta(get_the_ID(), '_ep_research_period', true);
                                        $report_pages = get_post_meta(get_the_ID(), '_ep_report_pages', true);
                                        $report_language = get_post_meta(get_the_ID(), '_ep_report_language', true);
                                        ?>
                                        
                                        <?php if ($author_organization): ?>
                                            <dt><?php _e('Organization', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($author_organization); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($research_period): ?>
                                            <dt><?php _e('Research Period', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($research_period); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($report_pages): ?>
                                            <dt><?php _e('Pages', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($report_pages); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($report_language): ?>
                                            <dt><?php _e('Language', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($report_language); ?></dd>
                                        <?php endif; ?>
                                        
                                        <dt><?php _e('Published', 'environmental-platform-core'); ?></dt>
                                        <dd><?php echo get_the_date(); ?></dd>
                                    </dl>
                                </div>
                                
                                <!-- Related Topics -->
                                <?php
                                $topics = get_the_terms(get_the_ID(), 'environmental_topic');
                                if ($topics && !is_wp_error($topics)):
                                ?>
                                    <div class="ep-widget ep-related-topics">
                                        <h3><?php _e('Related Topics', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-topic-list">
                                            <?php foreach ($topics as $topic): ?>
                                                <li>
                                                    <a href="<?php echo get_term_link($topic); ?>" class="ep-topic-link">
                                                        <?php echo esc_html($topic->name); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Research Methods -->
                                <?php
                                $methods = get_the_terms(get_the_ID(), 'research_method');
                                if ($methods && !is_wp_error($methods)):
                                ?>
                                    <div class="ep-widget ep-research-methods">
                                        <h3><?php _e('Research Methods', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-method-list">
                                            <?php foreach ($methods as $method): ?>
                                                <li>
                                                    <span class="ep-method-tag">
                                                        <?php echo esc_html($method->name); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Citations -->
                                <?php
                                $citations = get_post_meta(get_the_ID(), '_ep_citations', true);
                                if ($citations):
                                ?>
                                    <div class="ep-widget ep-citations">
                                        <h3><?php _e('How to Cite', 'environmental-platform-core'); ?></h3>
                                        <div class="ep-citation-box">
                                            <p class="ep-citation-text"><?php echo esc_html($citations); ?></p>
                                            <button class="ep-copy-citation" onclick="epCopyCitation()">
                                                <?php _e('Copy Citation', 'environmental-platform-core'); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Related Reports -->
                                <?php
                                $related_reports = get_posts(array(
                                    'post_type' => 'env_report',
                                    'posts_per_page' => 3,
                                    'post__not_in' => array(get_the_ID()),
                                    'meta_query' => array(
                                        array(
                                            'key' => '_ep_report_type',
                                            'value' => $report_type,
                                            'compare' => '='
                                        )
                                    )
                                ));
                                
                                if ($related_reports):
                                ?>
                                    <div class="ep-widget ep-related-reports">
                                        <h3><?php _e('Related Reports', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-related-list">
                                            <?php foreach ($related_reports as $related): ?>
                                                <li class="ep-related-item">
                                                    <a href="<?php echo get_permalink($related->ID); ?>" 
                                                       class="ep-related-link">
                                                        <h4><?php echo get_the_title($related->ID); ?></h4>
                                                        <span class="ep-related-date">
                                                            <?php echo get_the_date('', $related->ID); ?>
                                                        </span>
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
.ep-report-single {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

.ep-report-header {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: white;
    padding: 60px 0;
    position: relative;
}

.ep-report-meta {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.ep-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ep-badge-research { background-color: #007cba; }
.ep-badge-policy { background-color: #d63384; }
.ep-badge-assessment { background-color: #fd7e14; }
.ep-badge-published { background-color: #198754; }
.ep-badge-draft { background-color: #6c757d; }

.ep-report-title {
    font-size: 2.5em;
    font-weight: 700;
    margin: 20px 0;
    line-height: 1.2;
}

.ep-report-excerpt {
    font-size: 1.2em;
    opacity: 0.9;
    margin-bottom: 30px;
    max-width: 800px;
}

.ep-report-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.ep-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ep-btn-primary {
    background-color: #fff;
    color: #2c5530;
}

.ep-btn-primary:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
}

.ep-btn-secondary {
    background-color: transparent;
    color: white;
    border: 2px solid white;
}

.ep-btn-secondary:hover {
    background-color: white;
    color: #2c5530;
}

.ep-report-content {
    padding: 60px 0;
}

.ep-report-featured-image {
    margin-bottom: 40px;
}

.ep-responsive-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.ep-report-body {
    font-size: 1.1em;
    margin-bottom: 50px;
}

.ep-key-findings,
.ep-data-visualizations,
.ep-recommendations {
    margin-bottom: 50px;
    padding: 30px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #4a7c59;
}

.ep-key-findings h2,
.ep-data-visualizations h2,
.ep-recommendations h2 {
    color: #2c5530;
    margin-bottom: 20px;
    font-size: 1.5em;
}

.ep-report-navigation {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 60px;
    padding-top: 40px;
    border-top: 2px solid #e9ecef;
}

.ep-nav-link {
    display: block;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.ep-nav-link:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

.ep-nav-label {
    display: block;
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 5px;
}

.ep-nav-title {
    display: block;
    font-weight: 600;
}

.ep-report-sidebar {
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
    color: #2c5530;
    margin-bottom: 20px;
    font-size: 1.2em;
    font-weight: 600;
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

.ep-topic-list,
.ep-method-list,
.ep-related-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-topic-list li,
.ep-related-item {
    margin-bottom: 10px;
}

.ep-topic-link {
    color: #4a7c59;
    text-decoration: none;
    font-weight: 500;
}

.ep-topic-link:hover {
    text-decoration: underline;
}

.ep-method-tag {
    display: inline-block;
    background-color: #e9ecef;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
    margin: 2px;
}

.ep-citation-box {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #4a7c59;
}

.ep-citation-text {
    font-size: 0.9em;
    margin-bottom: 10px;
    font-style: italic;
}

.ep-copy-citation {
    background-color: #4a7c59;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
}

.ep-related-link {
    text-decoration: none;
    color: #333;
    display: block;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.ep-related-link:hover {
    background-color: #f8f9fa;
}

.ep-related-link h4 {
    margin: 0 0 5px 0;
    font-size: 1em;
    color: #4a7c59;
}

.ep-related-date {
    font-size: 0.9em;
    color: #6c757d;
}

@media (max-width: 768px) {
    .ep-report-title {
        font-size: 2em;
    }
    
    .ep-report-actions {
        flex-direction: column;
    }
    
    .ep-report-navigation {
        grid-template-columns: 1fr;
    }
    
    .ep-report-sidebar {
        padding-left: 0;
        margin-top: 40px;
    }
}
</style>

<script>
function epShareReport() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        // Fallback copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('<?php _e('Link copied to clipboard!', 'environmental-platform-core'); ?>');
        });
    }
}

function epCopyCitation() {
    const citationText = document.querySelector('.ep-citation-text').textContent;
    navigator.clipboard.writeText(citationText).then(function() {
        alert('<?php _e('Citation copied to clipboard!', 'environmental-platform-core'); ?>');
    });
}
</script>

<?php get_footer(); ?>
