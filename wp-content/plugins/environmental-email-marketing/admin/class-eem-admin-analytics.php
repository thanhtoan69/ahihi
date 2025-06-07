<?php
/**
 * Environmental Email Marketing - Analytics Admin Interface
 *
 * Admin interface for analytics and reporting
 *
 * @package Environmental_Email_Marketing
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Admin_Analytics {

    /**
     * Analytics tracker instance
     *
     * @var EEM_Analytics_Tracker
     */
    private $analytics_tracker;

    /**
     * Constructor
     */
    public function __construct() {
        $this->analytics_tracker = new EEM_Analytics_Tracker();
        
        add_action('wp_ajax_eem_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_eem_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_eem_update_analytics', array($this, 'ajax_update_analytics'));
    }

    /**
     * Render analytics page
     */
    public function render_page() {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'overview';
        $date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';

        ?>
        <div class="wrap eem-analytics">
            <h1><?php _e('Email Marketing Analytics', 'environmental-email-marketing'); ?></h1>

            <!-- Analytics Navigation -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo admin_url('admin.php?page=eem-analytics&view=overview'); ?>" 
                   class="nav-tab <?php echo $view === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Overview', 'environmental-email-marketing'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=eem-analytics&view=campaigns'); ?>" 
                   class="nav-tab <?php echo $view === 'campaigns' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Campaigns', 'environmental-email-marketing'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=eem-analytics&view=subscribers'); ?>" 
                   class="nav-tab <?php echo $view === 'subscribers' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Subscribers', 'environmental-email-marketing'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=eem-analytics&view=environmental'); ?>" 
                   class="nav-tab <?php echo $view === 'environmental' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Environmental Impact', 'environmental-email-marketing'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=eem-analytics&view=automations'); ?>" 
                   class="nav-tab <?php echo $view === 'automations' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Automations', 'environmental-email-marketing'); ?>
                </a>
            </nav>

            <!-- Date Range Filter -->
            <div class="eem-analytics-filters">
                <form method="get" id="eem-analytics-filter">
                    <input type="hidden" name="page" value="eem-analytics">
                    <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
                    
                    <select name="date_range" id="date_range">
                        <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'environmental-email-marketing'); ?></option>
                        <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'environmental-email-marketing'); ?></option>
                        <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'environmental-email-marketing'); ?></option>
                        <option value="365" <?php selected($date_range, '365'); ?>><?php _e('Last year', 'environmental-email-marketing'); ?></option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Apply Filter', 'environmental-email-marketing'); ?>">
                    <button type="button" id="eem-export-analytics" class="button">
                        <?php _e('Export Report', 'environmental-email-marketing'); ?>
                    </button>
                    <button type="button" id="eem-refresh-analytics" class="button">
                        <?php _e('Refresh Data', 'environmental-email-marketing'); ?>
                    </button>
                </form>
            </div>

            <!-- Analytics Content -->
            <div class="eem-analytics-content">
                <?php
                switch ($view) {
                    case 'campaigns':
                        $this->render_campaigns_analytics($date_range);
                        break;
                    case 'subscribers':
                        $this->render_subscribers_analytics($date_range);
                        break;
                    case 'environmental':
                        $this->render_environmental_analytics($date_range);
                        break;
                    case 'automations':
                        $this->render_automations_analytics($date_range);
                        break;
                    default:
                        $this->render_overview_analytics($date_range);
                        break;
                }
                ?>
            </div>
        </div>

        <style>
        .eem-analytics {
            margin: 20px 0;
        }
        
        .eem-analytics-filters {
            background: #fff;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .eem-analytics-content {
            margin-top: 20px;
        }
        
        .eem-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .eem-stat-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .eem-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #46b450;
            display: block;
        }
        
        .eem-stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .eem-stat-change {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .eem-stat-change.positive {
            color: #46b450;
        }
        
        .eem-stat-change.negative {
            color: #dc3232;
        }
        
        .eem-chart-container {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .eem-chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .eem-analytics-table {
            width: 100%;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .eem-analytics-table th,
        .eem-analytics-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .eem-analytics-table th {
            background: #f9f9f9;
            font-weight: bold;
        }
        
        .eem-env-impact {
            background: linear-gradient(135deg, #46b450, #00a32a);
            color: white;
        }
        
        .eem-env-impact .eem-stat-number {
            color: white;
        }
        </style>
        <?php
    }

    /**
     * Render overview analytics
     */
    private function render_overview_analytics($date_range) {
        $overview_data = $this->analytics_tracker->get_overview_stats($date_range);
        ?>
        
        <!-- Key Metrics -->
        <div class="eem-stats-grid">
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($overview_data['total_subscribers']); ?></span>
                <div class="eem-stat-label"><?php _e('Total Subscribers', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change <?php echo $overview_data['subscriber_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $overview_data['subscriber_change'] >= 0 ? '+' : ''; ?><?php echo number_format($overview_data['subscriber_change']); ?>
                    <?php _e('this period', 'environmental-email-marketing'); ?>
                </div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($overview_data['emails_sent']); ?></span>
                <div class="eem-stat-label"><?php _e('Emails Sent', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change <?php echo $overview_data['email_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $overview_data['email_change'] >= 0 ? '+' : ''; ?><?php echo number_format($overview_data['email_change']); ?>
                    <?php _e('from last period', 'environmental-email-marketing'); ?>
                </div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($overview_data['avg_open_rate'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Average Open Rate', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change <?php echo $overview_data['open_rate_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $overview_data['open_rate_change'] >= 0 ? '+' : ''; ?><?php echo number_format($overview_data['open_rate_change'], 1); ?>%
                    <?php _e('change', 'environmental-email-marketing'); ?>
                </div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($overview_data['avg_click_rate'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Average Click Rate', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change <?php echo $overview_data['click_rate_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $overview_data['click_rate_change'] >= 0 ? '+' : ''; ?><?php echo number_format($overview_data['click_rate_change'], 1); ?>%
                    <?php _e('change', 'environmental-email-marketing'); ?>
                </div>
            </div>
            
            <div class="eem-stat-card eem-env-impact">
                <span class="eem-stat-number"><?php echo number_format($overview_data['environmental_actions']); ?></span>
                <div class="eem-stat-label"><?php _e('Environmental Actions', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change">
                    <?php echo number_format($overview_data['carbon_offset'], 2); ?> kg CO₂ offset
                </div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($overview_data['active_automations']); ?></span>
                <div class="eem-stat-label"><?php _e('Active Automations', 'environmental-email-marketing'); ?></div>
                <div class="eem-stat-change">
                    <?php echo number_format($overview_data['automation_emails']); ?> <?php _e('emails sent', 'environmental-email-marketing'); ?>
                </div>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Email Performance Over Time', 'environmental-email-marketing'); ?></h3>
            <canvas id="eem-performance-chart" width="400" height="200"></canvas>
        </div>

        <!-- Recent Campaigns -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Recent Campaign Performance', 'environmental-email-marketing'); ?></h3>
            <table class="eem-analytics-table">
                <thead>
                    <tr>
                        <th><?php _e('Campaign', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Sent', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Delivered', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Opens', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Clicks', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Environmental Score', 'environmental-email-marketing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overview_data['recent_campaigns'] as $campaign): ?>
                        <tr>
                            <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                            <td><?php echo number_format($campaign->emails_sent); ?></td>
                            <td><?php echo number_format($campaign->delivered_count); ?></td>
                            <td><?php echo number_format($campaign->open_count); ?> (<?php echo number_format($campaign->open_rate, 1); ?>%)</td>
                            <td><?php echo number_format($campaign->click_count); ?> (<?php echo number_format($campaign->click_rate, 1); ?>%)</td>
                            <td><?php echo number_format($campaign->environmental_score, 1); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Initialize performance chart
            var ctx = document.getElementById('eem-performance-chart').getContext('2d');
            var chartData = <?php echo json_encode($overview_data['performance_chart']); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: '<?php _e('Open Rate', 'environmental-email-marketing'); ?>',
                        data: chartData.open_rates,
                        borderColor: '#46b450',
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        tension: 0.4
                    }, {
                        label: '<?php _e('Click Rate', 'environmental-email-marketing'); ?>',
                        data: chartData.click_rates,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render campaigns analytics
     */
    private function render_campaigns_analytics($date_range) {
        $campaigns_data = $this->analytics_tracker->get_campaigns_stats($date_range);
        ?>
        
        <!-- Campaign Performance Summary -->
        <div class="eem-stats-grid">
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($campaigns_data['total_campaigns']); ?></span>
                <div class="eem-stat-label"><?php _e('Total Campaigns', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($campaigns_data['avg_open_rate'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Average Open Rate', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($campaigns_data['avg_click_rate'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Average Click Rate', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($campaigns_data['total_revenue'], 2); ?>$</span>
                <div class="eem-stat-label"><?php _e('Revenue Generated', 'environmental-email-marketing'); ?></div>
            </div>
        </div>

        <!-- Top Performing Campaigns -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Top Performing Campaigns', 'environmental-email-marketing'); ?></h3>
            <table class="eem-analytics-table">
                <thead>
                    <tr>
                        <th><?php _e('Campaign Name', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Sent Date', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Recipients', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Open Rate', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Click Rate', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Unsubscribe Rate', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Environmental Impact', 'environmental-email-marketing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns_data['top_campaigns'] as $campaign): ?>
                        <tr>
                            <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                            <td><?php echo mysql2date('M j, Y', $campaign->sent_at); ?></td>
                            <td><?php echo number_format($campaign->emails_sent); ?></td>
                            <td><?php echo number_format($campaign->open_rate, 1); ?>%</td>
                            <td><?php echo number_format($campaign->click_rate, 1); ?>%</td>
                            <td><?php echo number_format($campaign->unsubscribe_rate, 2); ?>%</td>
                            <td><?php echo number_format($campaign->environmental_actions); ?> actions</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Campaign Performance Chart -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Campaign Performance Comparison', 'environmental-email-marketing'); ?></h3>
            <canvas id="eem-campaigns-chart" width="400" height="200"></canvas>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var ctx = document.getElementById('eem-campaigns-chart').getContext('2d');
            var chartData = <?php echo json_encode($campaigns_data['performance_chart']); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: '<?php _e('Open Rate (%)', 'environmental-email-marketing'); ?>',
                        data: chartData.open_rates,
                        backgroundColor: 'rgba(70, 180, 80, 0.7)',
                        borderColor: '#46b450',
                        borderWidth: 1
                    }, {
                        label: '<?php _e('Click Rate (%)', 'environmental-email-marketing'); ?>',
                        data: chartData.click_rates,
                        backgroundColor: 'rgba(0, 163, 42, 0.7)',
                        borderColor: '#00a32a',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render environmental analytics
     */
    private function render_environmental_analytics($date_range) {
        $env_data = $this->analytics_tracker->get_environmental_stats($date_range);
        ?>
        
        <!-- Environmental Impact Summary -->
        <div class="eem-stats-grid">
            <div class="eem-stat-card eem-env-impact">
                <span class="eem-stat-number"><?php echo number_format($env_data['total_actions']); ?></span>
                <div class="eem-stat-label"><?php _e('Environmental Actions Triggered', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card eem-env-impact">
                <span class="eem-stat-number"><?php echo number_format($env_data['carbon_offset'], 2); ?></span>
                <div class="eem-stat-label"><?php _e('CO₂ Offset (kg)', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card eem-env-impact">
                <span class="eem-stat-number"><?php echo number_format($env_data['trees_equivalent'], 4); ?></span>
                <div class="eem-stat-label"><?php _e('Trees Equivalent', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card eem-env-impact">
                <span class="eem-stat-number"><?php echo number_format($env_data['avg_env_score'], 1); ?></span>
                <div class="eem-stat-label"><?php _e('Average Environmental Score', 'environmental-email-marketing'); ?></div>
            </div>
        </div>

        <!-- Environmental Actions Breakdown -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Environmental Actions by Type', 'environmental-email-marketing'); ?></h3>
            <canvas id="eem-env-actions-chart" width="400" height="200"></canvas>
        </div>

        <!-- Top Environmental Campaigns -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Most Impactful Campaigns', 'environmental-email-marketing'); ?></h3>
            <table class="eem-analytics-table">
                <thead>
                    <tr>
                        <th><?php _e('Campaign', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Environmental Actions', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('CO₂ Impact', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Petition Signatures', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Green Purchases', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Impact Score', 'environmental-email-marketing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($env_data['top_campaigns'] as $campaign): ?>
                        <tr>
                            <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                            <td><?php echo number_format($campaign->environmental_actions); ?></td>
                            <td><?php echo number_format($campaign->carbon_impact, 2); ?> kg</td>
                            <td><?php echo number_format($campaign->petition_signatures); ?></td>
                            <td><?php echo number_format($campaign->green_purchases); ?></td>
                            <td><?php echo number_format($campaign->impact_score, 1); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var ctx = document.getElementById('eem-env-actions-chart').getContext('2d');
            var chartData = <?php echo json_encode($env_data['actions_chart']); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.values,
                        backgroundColor: [
                            '#46b450',
                            '#00a32a',
                            '#005a1a',
                            '#7fb069',
                            '#3d8f47'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render subscribers analytics
     */
    private function render_subscribers_analytics($date_range) {
        $subscribers_data = $this->analytics_tracker->get_subscribers_stats($date_range);
        ?>
        
        <!-- Subscriber Stats -->
        <div class="eem-stats-grid">
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($subscribers_data['total_subscribers']); ?></span>
                <div class="eem-stat-label"><?php _e('Total Subscribers', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($subscribers_data['new_subscribers']); ?></span>
                <div class="eem-stat-label"><?php _e('New Subscribers', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($subscribers_data['unsubscribes']); ?></span>
                <div class="eem-stat-label"><?php _e('Unsubscribes', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($subscribers_data['growth_rate'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Growth Rate', 'environmental-email-marketing'); ?></div>
            </div>
        </div>

        <!-- Subscriber Growth Chart -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Subscriber Growth Over Time', 'environmental-email-marketing'); ?></h3>
            <canvas id="eem-growth-chart" width="400" height="200"></canvas>
        </div>

        <!-- Subscription Sources -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Subscription Sources', 'environmental-email-marketing'); ?></h3>
            <canvas id="eem-sources-chart" width="400" height="200"></canvas>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Growth Chart
            var growthCtx = document.getElementById('eem-growth-chart').getContext('2d');
            var growthData = <?php echo json_encode($subscribers_data['growth_chart']); ?>;
            
            new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: growthData.labels,
                    datasets: [{
                        label: '<?php _e('Total Subscribers', 'environmental-email-marketing'); ?>',
                        data: growthData.totals,
                        borderColor: '#46b450',
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        tension: 0.4
                    }, {
                        label: '<?php _e('New Subscribers', 'environmental-email-marketing'); ?>',
                        data: growthData.new_subscribers,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Sources Chart
            var sourcesCtx = document.getElementById('eem-sources-chart').getContext('2d');
            var sourcesData = <?php echo json_encode($subscribers_data['sources_chart']); ?>;
            
            new Chart(sourcesCtx, {
                type: 'pie',
                data: {
                    labels: sourcesData.labels,
                    datasets: [{
                        data: sourcesData.values,
                        backgroundColor: [
                            '#46b450',
                            '#00a32a',
                            '#005a1a',
                            '#7fb069',
                            '#3d8f47',
                            '#2e7d2e'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render automations analytics
     */
    private function render_automations_analytics($date_range) {
        $automations_data = $this->analytics_tracker->get_automations_stats($date_range);
        ?>
        
        <!-- Automation Stats -->
        <div class="eem-stats-grid">
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($automations_data['total_automations']); ?></span>
                <div class="eem-stat-label"><?php _e('Total Automations', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($automations_data['active_automations']); ?></span>
                <div class="eem-stat-label"><?php _e('Active Automations', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($automations_data['automation_emails']); ?></span>
                <div class="eem-stat-label"><?php _e('Emails Sent', 'environmental-email-marketing'); ?></div>
            </div>
            
            <div class="eem-stat-card">
                <span class="eem-stat-number"><?php echo number_format($automations_data['avg_conversion'], 1); ?>%</span>
                <div class="eem-stat-label"><?php _e('Average Conversion', 'environmental-email-marketing'); ?></div>
            </div>
        </div>

        <!-- Top Automations -->
        <div class="eem-chart-container">
            <h3 class="eem-chart-title"><?php _e('Top Performing Automations', 'environmental-email-marketing'); ?></h3>
            <table class="eem-analytics-table">
                <thead>
                    <tr>
                        <th><?php _e('Automation Name', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Trigger', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Emails Sent', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Open Rate', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Click Rate', 'environmental-email-marketing'); ?></th>
                        <th><?php _e('Conversion Rate', 'environmental-email-marketing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($automations_data['top_automations'] as $automation): ?>
                        <tr>
                            <td><strong><?php echo esc_html($automation->name); ?></strong></td>
                            <td><?php echo esc_html($automation->trigger_type); ?></td>
                            <td><?php echo number_format($automation->emails_sent); ?></td>
                            <td><?php echo number_format($automation->open_rate, 1); ?>%</td>
                            <td><?php echo number_format($automation->click_rate, 1); ?>%</td>
                            <td><?php echo number_format($automation->conversion_rate, 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $view = sanitize_text_field($_POST['view']);
        $date_range = intval($_POST['date_range']);

        $data = array();

        switch ($view) {
            case 'overview':
                $data = $this->analytics_tracker->get_overview_stats($date_range);
                break;
            case 'campaigns':
                $data = $this->analytics_tracker->get_campaigns_stats($date_range);
                break;
            case 'subscribers':
                $data = $this->analytics_tracker->get_subscribers_stats($date_range);
                break;
            case 'environmental':
                $data = $this->analytics_tracker->get_environmental_stats($date_range);
                break;
            case 'automations':
                $data = $this->analytics_tracker->get_automations_stats($date_range);
                break;
        }

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for exporting analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $view = sanitize_text_field($_POST['view']);
        $date_range = intval($_POST['date_range']);

        $data = $this->analytics_tracker->export_analytics_data($view, $date_range);

        $filename = "eem_analytics_{$view}_" . date('Y-m-d_H-i-s') . '.csv';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $this->generate_csv_report($data, $filepath);

        wp_send_json_success(array(
            'download_url' => wp_upload_dir()['url'] . '/' . $filename,
            'filename' => $filename
        ));
    }

    /**
     * Generate CSV report
     */
    private function generate_csv_report($data, $filepath) {
        $fp = fopen($filepath, 'w');
        
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
    }

    /**
     * AJAX handler for updating analytics
     */
    public function ajax_update_analytics() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $result = $this->analytics_tracker->refresh_all_stats();

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Analytics data updated successfully.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
}
