<?php
/**
 * Admin Analytics Page
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30';
$petition_filter = isset($_GET['petition']) ? intval($_GET['petition']) : 0;

$analytics = Environmental_Platform_Petitions_Analytics::get_comprehensive_analytics($date_range, $petition_filter);
$petitions = get_posts(['post_type' => 'env_petition', 'numberposts' => -1, 'post_status' => 'publish']);
?>

<div class="wrap petition-analytics">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Analytics Filters -->
    <div class="analytics-filters">
        <div class="filter-controls">
            <div class="filter-group">
                <label for="date-range">Date Range:</label>
                <select id="date-range" name="range">
                    <option value="7" <?php selected($date_range, '7'); ?>>Last 7 Days</option>
                    <option value="30" <?php selected($date_range, '30'); ?>>Last 30 Days</option>
                    <option value="90" <?php selected($date_range, '90'); ?>>Last 90 Days</option>
                    <option value="365" <?php selected($date_range, '365'); ?>>Last Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="petition-filter">Petition:</label>
                <select id="petition-filter" name="petition">
                    <option value="0">All Petitions</option>
                    <?php foreach ($petitions as $petition): ?>
                        <option value="<?php echo $petition->ID; ?>" <?php selected($petition_filter, $petition->ID); ?>>
                            <?php echo esc_html($petition->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="button" class="button" id="apply-filters">Apply Filters</button>
                <button type="button" class="button" id="export-analytics">Export Data</button>
            </div>
        </div>
        
        <div class="custom-date-range" id="custom-date-range" style="display: none;">
            <input type="date" id="start-date" name="start_date">
            <span>to</span>
            <input type="date" id="end-date" name="end_date">
        </div>
    </div>
    
    <!-- Key Metrics Overview -->
    <div class="analytics-overview">
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="metric-content">
                    <div class="metric-number"><?php echo number_format($analytics['total_signatures']); ?></div>
                    <div class="metric-label">Total Signatures</div>
                    <div class="metric-change <?php echo $analytics['signature_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($analytics['signature_growth'] >= 0 ? '+' : '') . number_format($analytics['signature_growth'], 1); ?>%
                        <span class="change-period">vs previous period</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="metric-content">
                    <div class="metric-number"><?php echo number_format($analytics['conversion_rate'], 2); ?>%</div>
                    <div class="metric-label">Conversion Rate</div>
                    <div class="metric-change <?php echo $analytics['conversion_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($analytics['conversion_change'] >= 0 ? '+' : '') . number_format($analytics['conversion_change'], 1); ?>%
                        <span class="change-period">vs previous period</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <span class="dashicons dashicons-share"></span>
                </div>
                <div class="metric-content">
                    <div class="metric-number"><?php echo number_format($analytics['total_shares']); ?></div>
                    <div class="metric-label">Total Shares</div>
                    <div class="metric-change <?php echo $analytics['share_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($analytics['share_growth'] >= 0 ? '+' : '') . number_format($analytics['share_growth'], 1); ?>%
                        <span class="change-period">vs previous period</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="metric-content">
                    <div class="metric-number"><?php echo number_format($analytics['verification_rate'], 1); ?>%</div>
                    <div class="metric-label">Verification Rate</div>
                    <div class="metric-change <?php echo $analytics['verification_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($analytics['verification_change'] >= 0 ? '+' : '') . number_format($analytics['verification_change'], 1); ?>%
                        <span class="change-period">vs previous period</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Analytics Content -->
    <div class="analytics-content">
        <div class="analytics-left">
            
            <!-- Signature Trends Chart -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Signature Trends</h3>
                    <div class="widget-controls">
                        <div class="chart-type-selector">
                            <button class="chart-type active" data-type="line">Line</button>
                            <button class="chart-type" data-type="bar">Bar</button>
                            <button class="chart-type" data-type="area">Area</button>
                        </div>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="chart-container">
                        <canvas id="signature-trends-chart" width="800" height="400"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Conversion Funnel -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Conversion Funnel</h3>
                </div>
                <div class="widget-content">
                    <div class="funnel-chart">
                        <div class="funnel-stage">
                            <div class="stage-bar" style="width: 100%;">
                                <div class="stage-label">Page Views</div>
                                <div class="stage-count"><?php echo number_format($analytics['page_views']); ?></div>
                            </div>
                        </div>
                        <div class="funnel-stage">
                            <div class="stage-bar" style="width: <?php echo ($analytics['form_views'] / $analytics['page_views']) * 100; ?>%;">
                                <div class="stage-label">Form Views</div>
                                <div class="stage-count"><?php echo number_format($analytics['form_views']); ?></div>
                            </div>
                        </div>
                        <div class="funnel-stage">
                            <div class="stage-bar" style="width: <?php echo ($analytics['signature_attempts'] / $analytics['page_views']) * 100; ?>%;">
                                <div class="stage-label">Signature Attempts</div>
                                <div class="stage-count"><?php echo number_format($analytics['signature_attempts']); ?></div>
                            </div>
                        </div>
                        <div class="funnel-stage">
                            <div class="stage-bar" style="width: <?php echo ($analytics['verified_signatures'] / $analytics['page_views']) * 100; ?>%;">
                                <div class="stage-label">Verified Signatures</div>
                                <div class="stage-count"><?php echo number_format($analytics['verified_signatures']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="funnel-insights">
                        <h4>Conversion Insights</h4>
                        <ul>
                            <li>
                                <strong><?php echo number_format(($analytics['form_views'] / $analytics['page_views']) * 100, 1); ?>%</strong>
                                of visitors view the signature form
                            </li>
                            <li>
                                <strong><?php echo number_format(($analytics['signature_attempts'] / $analytics['form_views']) * 100, 1); ?>%</strong>
                                of form viewers attempt to sign
                            </li>
                            <li>
                                <strong><?php echo number_format(($analytics['verified_signatures'] / $analytics['signature_attempts']) * 100, 1); ?>%</strong>
                                of signature attempts get verified
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Geographic Distribution -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Geographic Distribution</h3>
                </div>
                <div class="widget-content">
                    <div class="geo-chart-container">
                        <div id="geo-map" style="height: 400px;"></div>
                    </div>
                    
                    <div class="geo-stats">
                        <h4>Top Locations</h4>
                        <div class="location-list">
                            <?php foreach ($analytics['top_locations'] as $location): ?>
                                <div class="location-item">
                                    <div class="location-name"><?php echo esc_html($location['name']); ?></div>
                                    <div class="location-count"><?php echo number_format($location['count']); ?></div>
                                    <div class="location-percentage"><?php echo number_format($location['percentage'], 1); ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <div class="analytics-right">
            
            <!-- Top Performing Petitions -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Top Performing Petitions</h3>
                </div>
                <div class="widget-content">
                    <div class="petition-performance-list">
                        <?php foreach ($analytics['top_petitions'] as $petition): ?>
                            <div class="petition-performance-item">
                                <div class="petition-info">
                                    <div class="petition-title">
                                        <a href="<?php echo get_edit_post_link($petition['id']); ?>">
                                            <?php echo esc_html($petition['title']); ?>
                                        </a>
                                    </div>
                                    <div class="petition-stats">
                                        <span class="stat-item">
                                            <strong><?php echo number_format($petition['signatures']); ?></strong> signatures
                                        </span>
                                        <span class="stat-item">
                                            <strong><?php echo number_format($petition['conversion_rate'], 1); ?>%</strong> conversion
                                        </span>
                                        <span class="stat-item">
                                            <strong><?php echo number_format($petition['shares']); ?></strong> shares
                                        </span>
                                    </div>
                                </div>
                                <div class="petition-trend">
                                    <div class="trend-chart" data-values="<?php echo esc_attr(json_encode($petition['daily_trends'])); ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Traffic Sources -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Traffic Sources</h3>
                </div>
                <div class="widget-content">
                    <div class="traffic-sources-chart">
                        <canvas id="traffic-sources-chart" width="300" height="300"></canvas>
                    </div>
                    
                    <div class="traffic-sources-list">
                        <?php foreach ($analytics['traffic_sources'] as $source): ?>
                            <div class="traffic-source-item">
                                <div class="source-icon" style="background-color: <?php echo esc_attr($source['color']); ?>"></div>
                                <div class="source-info">
                                    <div class="source-name"><?php echo esc_html($source['name']); ?></div>
                                    <div class="source-stats">
                                        <span class="source-count"><?php echo number_format($source['count']); ?></span>
                                        <span class="source-percentage">(<?php echo number_format($source['percentage'], 1); ?>%)</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Device Breakdown -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Device Breakdown</h3>
                </div>
                <div class="widget-content">
                    <div class="device-stats">
                        <div class="device-item">
                            <div class="device-icon">
                                <span class="dashicons dashicons-desktop"></span>
                            </div>
                            <div class="device-info">
                                <div class="device-name">Desktop</div>
                                <div class="device-stats">
                                    <span class="device-count"><?php echo number_format($analytics['devices']['desktop']['count']); ?></span>
                                    <span class="device-percentage">(<?php echo number_format($analytics['devices']['desktop']['percentage'], 1); ?>%)</span>
                                </div>
                                <div class="device-conversion">
                                    <?php echo number_format($analytics['devices']['desktop']['conversion'], 1); ?>% conversion
                                </div>
                            </div>
                        </div>
                        
                        <div class="device-item">
                            <div class="device-icon">
                                <span class="dashicons dashicons-tablet"></span>
                            </div>
                            <div class="device-info">
                                <div class="device-name">Tablet</div>
                                <div class="device-stats">
                                    <span class="device-count"><?php echo number_format($analytics['devices']['tablet']['count']); ?></span>
                                    <span class="device-percentage">(<?php echo number_format($analytics['devices']['tablet']['percentage'], 1); ?>%)</span>
                                </div>
                                <div class="device-conversion">
                                    <?php echo number_format($analytics['devices']['tablet']['conversion'], 1); ?>% conversion
                                </div>
                            </div>
                        </div>
                        
                        <div class="device-item">
                            <div class="device-icon">
                                <span class="dashicons dashicons-smartphone"></span>
                            </div>
                            <div class="device-info">
                                <div class="device-name">Mobile</div>
                                <div class="device-stats">
                                    <span class="device-count"><?php echo number_format($analytics['devices']['mobile']['count']); ?></span>
                                    <span class="device-percentage">(<?php echo number_format($analytics['devices']['mobile']['percentage'], 1); ?>%)</span>
                                </div>
                                <div class="device-conversion">
                                    <?php echo number_format($analytics['devices']['mobile']['conversion'], 1); ?>% conversion
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Real-time Activity -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Real-time Activity</h3>
                    <div class="widget-controls">
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            Live
                        </div>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="realtime-stats">
                        <div class="realtime-item">
                            <div class="realtime-number" id="realtime-visitors">0</div>
                            <div class="realtime-label">Active Visitors</div>
                        </div>
                        <div class="realtime-item">
                            <div class="realtime-number" id="realtime-signatures">0</div>
                            <div class="realtime-label">Signatures (Last Hour)</div>
                        </div>
                    </div>
                    
                    <div class="recent-activity" id="recent-activity">
                        <!-- Real-time activity will be populated via JavaScript -->
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize analytics
    PetitionAnalytics.init({
        dateRange: '<?php echo $date_range; ?>',
        petitionFilter: <?php echo $petition_filter; ?>,
        analyticsData: <?php echo json_encode($analytics); ?>
    });
    
    // Initialize charts
    if (typeof Chart !== 'undefined') {
        PetitionAnalytics.initCharts();
    }
    
    // Real-time updates
    PetitionAnalytics.startRealTimeUpdates();
});
</script>
