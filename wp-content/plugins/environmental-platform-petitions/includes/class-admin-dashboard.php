<?php
/**
 * Admin Dashboard Class
 * 
 * Handles the admin dashboard interface for petition management
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_Admin_Dashboard {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Campaign manager instance
     */
    private $campaign_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->analytics = new Environmental_Platform_Petitions_Analytics();
        $this->campaign_manager = new Environmental_Platform_Petitions_Campaign_Manager();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_export_petition_data', array($this, 'ajax_export_petition_data'));
        
        // Add meta boxes to petition edit screen
        add_action('add_meta_boxes', array($this, 'add_petition_meta_boxes'));
        add_action('save_post', array($this, 'save_petition_meta'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'Petition Dashboard',
            'Petitions',
            'manage_options',
            'petition-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-megaphone',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'petition-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'petition-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'petition-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'petition-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'petition-dashboard',
            'Signatures',
            'Signatures',
            'manage_options',
            'petition-signatures',
            array($this, 'render_signatures_page')
        );
        
        add_submenu_page(
            'petition-dashboard',
            'Campaigns',
            'Campaigns',
            'manage_options',
            'petition-campaigns',
            array($this, 'render_campaigns_page')
        );
        
        add_submenu_page(
            'petition-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'petition-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'petition-') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        wp_enqueue_script(
            'petition-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'petition-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('petition-admin-js', 'petitionAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('petition_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-platform-petitions'),
                'loading' => __('Loading...', 'environmental-platform-petitions'),
                'error' => __('An error occurred. Please try again.', 'environmental-platform-petitions')
            )
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $stats = $this->get_dashboard_statistics();
        $recent_petitions = $this->get_recent_petitions();
        $top_petitions = $this->get_top_performing_petitions();
        
        ?>
        <div class="wrap petition-dashboard">
            <h1 class="wp-heading-inline">Petition Dashboard</h1>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_petitions']); ?></div>
                    <div class="stat-label">Total Petitions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_signatures']); ?></div>
                    <div class="stat-label">Total Signatures</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['active_campaigns']); ?></div>
                    <div class="stat-label">Active Campaigns</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['avg_conversion_rate']; ?>%</div>
                    <div class="stat-label">Avg. Conversion Rate</div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="dashboard-charts">
                <div class="chart-container">
                    <h3>Signatures Over Time</h3>
                    <canvas id="signatures-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Top Performing Petitions</h3>
                    <canvas id="performance-chart"></canvas>
                </div>
            </div>
            
            <!-- Tables Row -->
            <div class="dashboard-tables">
                <div class="table-container">
                    <h3>Recent Petitions</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Signatures</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_petitions as $petition): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo get_edit_post_link($petition->ID); ?>">
                                                <?php echo esc_html($petition->post_title); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo number_format($petition->signature_count); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($petition->post_date)); ?></td>
                                    <td>
                                        <span class="status status-<?php echo esc_attr($petition->post_status); ?>">
                                            <?php echo ucfirst($petition->post_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($petition->ID); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo get_permalink($petition->ID); ?>" class="button button-small" target="_blank">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-container">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=env_petition'); ?>" class="button button-primary button-large">
                            <span class="dashicons dashicons-plus"></span>
                            Create New Petition
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-analytics'); ?>" class="button button-secondary button-large">
                            <span class="dashicons dashicons-chart-area"></span>
                            View Analytics
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-signatures'); ?>" class="button button-secondary button-large">
                            <span class="dashicons dashicons-list-view"></span>
                            Manage Signatures
                        </a>
                        
                        <button id="export-data" class="button button-secondary button-large">
                            <span class="dashicons dashicons-download"></span>
                            Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize charts
            initializeDashboardCharts();
            
            // Handle export
            $('#export-data').on('click', function() {
                exportDashboardData();
            });
        });
        
        function initializeDashboardCharts() {
            // Signatures over time chart
            const signaturesCtx = document.getElementById('signatures-chart').getContext('2d');
            new Chart(signaturesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo wp_json_encode(array_column($stats['signatures_over_time'], 'period')); ?>,
                    datasets: [{
                        label: 'Signatures',
                        data: <?php echo wp_json_encode(array_column($stats['signatures_over_time'], 'count')); ?>,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
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
            
            // Top performing petitions chart
            const performanceCtx = document.getElementById('performance-chart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo wp_json_encode(array_column($top_petitions, 'title')); ?>,
                    datasets: [{
                        label: 'Signatures',
                        data: <?php echo wp_json_encode(array_column($top_petitions, 'signature_count')); ?>,
                        backgroundColor: [
                            '#2271b1',
                            '#135e96',
                            '#0a4b78',
                            '#003859',
                            '#00263d'
                        ]
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
        }
        
        function exportDashboardData() {
            $.post(ajaxurl, {
                action: 'export_petition_data',
                nonce: petitionAdmin.nonce,
                type: 'dashboard'
            }, function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'petition-dashboard-' + new Date().toISOString().split('T')[0] + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('Export failed: ' + response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        $petitions = $this->get_all_petitions();
        $selected_petition = isset($_GET['petition_id']) ? absint($_GET['petition_id']) : ($petitions[0]->ID ?? 0);
        
        if ($selected_petition) {
            $analytics_data = $this->analytics->generate_report($selected_petition);
        }
        
        ?>
        <div class="wrap petition-analytics">
            <h1>Petition Analytics</h1>
            
            <div class="analytics-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="petition-analytics">
                    
                    <select name="petition_id" id="petition-select">
                        <option value="">Select a petition...</option>
                        <?php foreach ($petitions as $petition): ?>
                            <option value="<?php echo $petition->ID; ?>" <?php selected($selected_petition, $petition->ID); ?>>
                                <?php echo esc_html($petition->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="date_range" id="date-range">
                        <option value="7 days">Last 7 days</option>
                        <option value="30 days" selected>Last 30 days</option>
                        <option value="90 days">Last 90 days</option>
                        <option value="1 year">Last year</option>
                    </select>
                    
                    <input type="submit" class="button" value="Update">
                </form>
            </div>
            
            <?php if ($selected_petition && isset($analytics_data)): ?>
                <!-- Analytics content -->
                <div class="analytics-content">
                    <h2><?php echo esc_html($analytics_data['petition_title']); ?></h2>
                    
                    <!-- Overview Stats -->
                    <div class="analytics-overview">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($analytics_data['overview']['signatures']['total_signatures']); ?></div>
                            <div class="stat-label">Total Signatures</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($analytics_data['overview']['shares']['total_shares']); ?></div>
                            <div class="stat-label">Total Shares</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $analytics_data['overview']['conversion_rates']['overall_conversion']; ?>%</div>
                            <div class="stat-label">Conversion Rate</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($analytics_data['overview']['events']['page_view']['unique_users'] ?? 0); ?></div>
                            <div class="stat-label">Unique Visitors</div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="analytics-charts">
                        <div class="chart-container">
                            <h3>Signature Trend</h3>
                            <canvas id="signature-trend-chart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Traffic Sources</h3>
                            <canvas id="traffic-sources-chart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Conversion Funnel</h3>
                            <canvas id="funnel-chart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Device Breakdown</h3>
                            <canvas id="device-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Data Tables -->
                    <div class="analytics-tables">
                        <div class="table-container">
                            <h3>Top Referrers</h3>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Visitors</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics_data['demographics']['referrers'] as $referrer): ?>
                                        <tr>
                                            <td><?php echo esc_html($referrer->source); ?></td>
                                            <td><?php echo number_format($referrer->count); ?></td>
                                            <td><?php echo round(($referrer->count / array_sum(array_column($analytics_data['demographics']['referrers'], 'count'))) * 100, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <script>
                // Initialize analytics charts
                const analyticsData = <?php echo wp_json_encode($analytics_data); ?>;
                initializeAnalyticsCharts(analyticsData);
                </script>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>Please select a petition to view analytics.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render signatures page
     */
    public function render_signatures_page() {
        $signatures = $this->get_recent_signatures();
        $verification_stats = $this->get_verification_statistics();
        
        ?>
        <div class="wrap petition-signatures">
            <h1>Signature Management</h1>
            
            <!-- Verification Stats -->
            <div class="verification-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($verification_stats['total']); ?></div>
                    <div class="stat-label">Total Signatures</div>
                </div>
                
                <div class="stat-card verified">
                    <div class="stat-number"><?php echo number_format($verification_stats['verified']); ?></div>
                    <div class="stat-label">Verified</div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo number_format($verification_stats['pending']); ?></div>
                    <div class="stat-label">Pending Verification</div>
                </div>
                
                <div class="stat-card rejected">
                    <div class="stat-number"><?php echo number_format($verification_stats['rejected']); ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            
            <!-- Signature List -->
            <div class="signatures-list">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select id="bulk-action-selector">
                            <option value="">Bulk Actions</option>
                            <option value="verify">Verify</option>
                            <option value="reject">Reject</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button class="button action" id="doaction">Apply</button>
                    </div>
                    
                    <div class="alignright actions">
                        <button class="button" id="export-signatures">Export Signatures</button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Petition</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signatures as $signature): ?>
                            <tr data-signature-id="<?php echo $signature->id; ?>">
                                <th class="check-column">
                                    <input type="checkbox" name="signature[]" value="<?php echo $signature->id; ?>">
                                </th>
                                <td><?php echo esc_html($signature->first_name . ' ' . $signature->last_name); ?></td>
                                <td><?php echo esc_html($signature->user_email); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($signature->petition_id); ?>">
                                        <?php echo esc_html(get_the_title($signature->petition_id)); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($signature->created_at)); ?></td>
                                <td>
                                    <span class="status status-<?php echo $signature->is_verified ? 'verified' : 'pending'; ?>">
                                        <?php echo $signature->is_verified ? 'Verified' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$signature->is_verified): ?>
                                        <button class="button button-small verify-signature" data-id="<?php echo $signature->id; ?>">Verify</button>
                                    <?php endif; ?>
                                    <button class="button button-small button-link-delete delete-signature" data-id="<?php echo $signature->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_statistics() {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        $campaigns_table = $this->database->get_table_name('campaigns');
        $analytics_table = $this->database->get_table_name('analytics');
        
        // Basic counts
        $total_petitions = wp_count_posts('env_petition')->publish;
        $total_signatures = $wpdb->get_var("SELECT COUNT(*) FROM {$signatures_table}");
        $active_campaigns = $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table} WHERE status = 'active'");
        
        // Conversion rate calculation
        $total_page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$analytics_table} WHERE event_type = 'page_view'");
        $total_signature_success = $wpdb->get_var("SELECT COUNT(*) FROM {$analytics_table} WHERE event_type = 'signature_success'");
        $avg_conversion_rate = $total_page_views > 0 ? round(($total_signature_success / $total_page_views) * 100, 2) : 0;
        
        // Signatures over time (last 30 days)
        $signatures_over_time = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as period,
                COUNT(*) as count
            FROM {$signatures_table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY period ASC"
        );
        
        return array(
            'total_petitions' => $total_petitions,
            'total_signatures' => $total_signatures,
            'active_campaigns' => $active_campaigns,
            'avg_conversion_rate' => $avg_conversion_rate,
            'signatures_over_time' => $signatures_over_time
        );
    }
    
    /**
     * Get recent petitions
     */
    private function get_recent_petitions($limit = 10) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        
        $petitions = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, 
                    COALESCE(s.signature_count, 0) as signature_count
            FROM {$wpdb->posts} p
            LEFT JOIN (
                SELECT petition_id, COUNT(*) as signature_count
                FROM {$signatures_table}
                GROUP BY petition_id
            ) s ON p.ID = s.petition_id
            WHERE p.post_type = 'env_petition' 
            AND p.post_status IN ('publish', 'draft')
            ORDER BY p.post_date DESC
            LIMIT %d",
            $limit
        ));
        
        return $petitions;
    }
    
    /**
     * Get top performing petitions
     */
    private function get_top_performing_petitions($limit = 5) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_title as title, COUNT(s.id) as signature_count
            FROM {$wpdb->posts} p
            INNER JOIN {$signatures_table} s ON p.ID = s.petition_id
            WHERE p.post_type = 'env_petition' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY signature_count DESC
            LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get all petitions for dropdown
     */
    private function get_all_petitions() {
        return get_posts(array(
            'post_type' => 'env_petition',
            'post_status' => array('publish', 'draft'),
            'numberposts' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC'
        ));
    }
    
    /**
     * Get recent signatures
     */
    private function get_recent_signatures($limit = 50) {
        global $wpdb;
        
        $table = $this->database->get_table_name('signatures');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get verification statistics
     */
    private function get_verification_statistics() {
        global $wpdb;
        
        $table = $this->database->get_table_name('signatures');
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified,
                COUNT(CASE WHEN is_verified = 0 THEN 1 END) as pending,
                COUNT(CASE WHEN is_verified = -1 THEN 1 END) as rejected
            FROM {$table}"
        );
        
        return (array) $stats;
    }
    
    /**
     * Add petition meta boxes
     */
    public function add_petition_meta_boxes() {
        add_meta_box(
            'petition_analytics_overview',
            'Analytics Overview',
            array($this, 'render_analytics_meta_box'),
            'env_petition',
            'side',
            'high'
        );
        
        add_meta_box(
            'petition_signature_settings',
            'Signature Settings',
            array($this, 'render_signature_settings_meta_box'),
            'env_petition',
            'normal',
            'high'
        );
    }
    
    /**
     * Render analytics meta box
     */
    public function render_analytics_meta_box($post) {
        if ($post->post_status === 'auto-draft') {
            echo '<p>Analytics will be available after the petition is published.</p>';
            return;
        }
        
        $overview = $this->analytics->get_petition_overview($post->ID, '30 days');
        
        ?>
        <div class="petition-analytics-overview">
            <div class="analytics-stat">
                <strong><?php echo number_format($overview['signatures']['total_signatures'] ?? 0); ?></strong>
                <span>Total Signatures</span>
            </div>
            
            <div class="analytics-stat">
                <strong><?php echo number_format($overview['shares']['total_shares'] ?? 0); ?></strong>
                <span>Total Shares</span>
            </div>
            
            <div class="analytics-stat">
                <strong><?php echo $overview['conversion_rates']['overall_conversion'] ?? 0; ?>%</strong>
                <span>Conversion Rate</span>
            </div>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=petition-analytics&petition_id=' . $post->ID); ?>" class="button">
                    View Detailed Analytics
                </a>
            </p>
        </div>
        
        <style>
        .petition-analytics-overview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .analytics-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            min-width: 80px;
        }
        
        .analytics-stat strong {
            display: block;
            font-size: 18px;
            color: #2271b1;
        }
        
        .analytics-stat span {
            font-size: 12px;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Render signature settings meta box
     */
    public function render_signature_settings_meta_box($post) {
        wp_nonce_field('petition_signature_settings', 'petition_signature_settings_nonce');
        
        $settings = get_post_meta($post->ID, 'petition_signature_settings', true) ?: array();
        $default_settings = array(
            'target_signatures' => 1000,
            'allow_anonymous' => false,
            'require_verification' => true,
            'verification_method' => 'email',
            'auto_add_share_buttons' => true,
            'share_message' => '',
            'hashtags' => '',
            'milestone_notifications' => true
        );
        
        $settings = wp_parse_args($settings, $default_settings);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="target_signatures">Target Signatures</label></th>
                <td>
                    <input type="number" id="target_signatures" name="petition_settings[target_signatures]" 
                           value="<?php echo esc_attr($settings['target_signatures']); ?>" min="1" class="small-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="allow_anonymous">Allow Anonymous Signatures</label></th>
                <td>
                    <input type="checkbox" id="allow_anonymous" name="petition_settings[allow_anonymous]" 
                           value="1" <?php checked($settings['allow_anonymous']); ?>>
                    <p class="description">Allow signatures without requiring email addresses.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="require_verification">Require Email Verification</label></th>
                <td>
                    <input type="checkbox" id="require_verification" name="petition_settings[require_verification]" 
                           value="1" <?php checked($settings['require_verification']); ?>>
                    <p class="description">Require email verification for signatures to be counted.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="verification_method">Verification Method</label></th>
                <td>
                    <select id="verification_method" name="petition_settings[verification_method]">
                        <option value="email" <?php selected($settings['verification_method'], 'email'); ?>>Email Only</option>
                        <option value="email_phone" <?php selected($settings['verification_method'], 'email_phone'); ?>>Email + Phone</option>
                        <option value="email_id" <?php selected($settings['verification_method'], 'email_id'); ?>>Email + ID Verification</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="auto_add_share_buttons">Auto-add Share Buttons</label></th>
                <td>
                    <input type="checkbox" id="auto_add_share_buttons" name="petition_settings[auto_add_share_buttons]" 
                           value="1" <?php checked($settings['auto_add_share_buttons']); ?>>
                    <p class="description">Automatically add share buttons to the petition content.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="share_message">Custom Share Message</label></th>
                <td>
                    <textarea id="share_message" name="petition_settings[share_message]" rows="3" class="large-text"><?php echo esc_textarea($settings['share_message']); ?></textarea>
                    <p class="description">Custom message for social media sharing (leave blank for default).</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="hashtags">Hashtags</label></th>
                <td>
                    <input type="text" id="hashtags" name="petition_settings[hashtags]" 
                           value="<?php echo esc_attr($settings['hashtags']); ?>" class="regular-text">
                    <p class="description">Comma-separated hashtags for social media sharing.</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="milestone_notifications">Milestone Notifications</label></th>
                <td>
                    <input type="checkbox" id="milestone_notifications" name="petition_settings[milestone_notifications]" 
                           value="1" <?php checked($settings['milestone_notifications']); ?>>
                    <p class="description">Send notifications when signature milestones are reached.</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save petition meta
     */
    public function save_petition_meta($post_id) {
        if (!isset($_POST['petition_signature_settings_nonce']) || 
            !wp_verify_nonce($_POST['petition_signature_settings_nonce'], 'petition_signature_settings')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['petition_settings'])) {
            $settings = $_POST['petition_settings'];
            
            // Sanitize settings
            $sanitized_settings = array(
                'target_signatures' => absint($settings['target_signatures']),
                'allow_anonymous' => isset($settings['allow_anonymous']),
                'require_verification' => isset($settings['require_verification']),
                'verification_method' => sanitize_text_field($settings['verification_method']),
                'auto_add_share_buttons' => isset($settings['auto_add_share_buttons']),
                'share_message' => sanitize_textarea_field($settings['share_message']),
                'hashtags' => sanitize_text_field($settings['hashtags']),
                'milestone_notifications' => isset($settings['milestone_notifications'])
            );
            
            update_post_meta($post_id, 'petition_signature_settings', $sanitized_settings);
        }
    }
    
    /**
     * AJAX: Get dashboard stats
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $stats = $this->get_dashboard_statistics();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Export petition data
     */
    public function ajax_export_petition_data() {
        check_ajax_referer('petition_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'dashboard');
        
        switch ($type) {
            case 'dashboard':
                $data = $this->export_dashboard_data();
                break;
            case 'signatures':
                $data = $this->export_signatures_data();
                break;
            default:
                wp_send_json_error('Invalid export type');
                return;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Export dashboard data as CSV
     */
    private function export_dashboard_data() {
        $petitions = $this->get_recent_petitions(100);
        
        $csv_data = "Title,Signatures,Created,Status,URL\n";
        
        foreach ($petitions as $petition) {
            $csv_data .= sprintf(
                '"%s",%d,"%s","%s","%s"' . "\n",
                str_replace('"', '""', $petition->post_title),
                $petition->signature_count,
                date('Y-m-d H:i:s', strtotime($petition->post_date)),
                $petition->post_status,
                get_permalink($petition->ID)
            );
        }
        
        return $csv_data;
    }
    
    /**
     * Export signatures data as CSV
     */
    private function export_signatures_data() {
        $signatures = $this->get_recent_signatures(1000);
        
        $csv_data = "Name,Email,Petition,Date,Verified,Location\n";
        
        foreach ($signatures as $signature) {
            $csv_data .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $signature->first_name . ' ' . $signature->last_name),
                $signature->user_email,
                str_replace('"', '""', get_the_title($signature->petition_id)),
                date('Y-m-d H:i:s', strtotime($signature->created_at)),
                $signature->is_verified ? 'Yes' : 'No',
                $signature->user_location ?: ''
            );
        }
        
        return $csv_data;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_action($type, $action, $items) {
        $result = array('success' => false, 'message' => '');
        
        try {
            switch ($type) {
                case 'signatures':
                    $result = $this->handle_signature_bulk_action($action, $items);
                    break;
                    
                case 'verification':
                    $result = $this->handle_verification_bulk_action($action, $items);
                    break;
                    
                default:
                    $result['message'] = __('Unknown bulk action type', 'environmental-platform-petitions');
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Handle signature bulk actions
     */
    private function handle_signature_bulk_action($action, $signature_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'petition_signatures';
        $count = 0;
        
        switch ($action) {
            case 'verify':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET is_verified = 1 WHERE id IN (" . implode(',', array_fill(0, count($signature_ids), '%d')) . ")",
                    ...$signature_ids
                ));
                break;
                
            case 'unverify':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET is_verified = 0 WHERE id IN (" . implode(',', array_fill(0, count($signature_ids), '%d')) . ")",
                    ...$signature_ids
                ));
                break;
                
            case 'delete':
                $count = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN (" . implode(',', array_fill(0, count($signature_ids), '%d')) . ")",
                    ...$signature_ids
                ));
                break;
                
            default:
                return array('success' => false, 'message' => __('Unknown signature action', 'environmental-platform-petitions'));
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d signatures processed', 'environmental-platform-petitions'), $count)
        );
    }
    
    /**
     * Handle verification bulk actions
     */
    private function handle_verification_bulk_action($action, $verification_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'petition_signatures';
        $count = 0;
        
        switch ($action) {
            case 'approve':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET is_verified = 1, verification_status = 'verified' WHERE id IN (" . implode(',', array_fill(0, count($verification_ids), '%d')) . ")",
                    ...$verification_ids
                ));
                break;
                
            case 'reject':
                $count = $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET verification_status = 'rejected' WHERE id IN (" . implode(',', array_fill(0, count($verification_ids), '%d')) . ")",
                    ...$verification_ids
                ));
                break;
                
            case 'resend':
                // Resend verification emails
                $verification_system = new EPP_Verification_System();
                foreach ($verification_ids as $id) {
                    $verification_system->resend_verification($id);
                    $count++;
                }
                break;
                
            default:
                return array('success' => false, 'message' => __('Unknown verification action', 'environmental-platform-petitions'));
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d verifications processed', 'environmental-platform-petitions'), $count)
        );
    }
    
    /**
     * Export data in specified format
     */
    public function export_data($type, $format, $filters = array()) {
        switch ($type) {
            case 'signatures':
                $this->export_signatures($format, $filters);
                break;
                
            case 'analytics':
                $this->export_analytics($format, $filters);
                break;
                
            case 'petitions':
                $this->export_petitions($format, $filters);
                break;
                
            default:
                wp_die(__('Unknown export type', 'environmental-platform-petitions'));
        }
    }
    
    /**
     * Export signatures
     */
    private function export_signatures($format, $filters) {
        global $wpdb;
        
        $query = "SELECT s.*, p.post_title as petition_title 
                  FROM {$wpdb->prefix}petition_signatures s 
                  LEFT JOIN {$wpdb->posts} p ON s.petition_id = p.ID 
                  WHERE 1=1";
        
        $params = array();
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $query .= " AND s.created_at >= %s";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND s.created_at <= %s";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'verified') {
                $query .= " AND s.is_verified = 1";
            } elseif ($filters['status'] === 'unverified') {
                $query .= " AND s.is_verified = 0";
            }
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        if (!empty($params)) {
            $signatures = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $signatures = $wpdb->get_results($query);
        }
        
        if ($format === 'csv') {
            $this->output_csv_signatures($signatures);
        } elseif ($format === 'json') {
            $this->output_json_signatures($signatures);
        }
    }
    
    /**
     * Output signatures as CSV
     */
    private function output_csv_signatures($signatures) {
        $filename = 'petition-signatures-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'ID', 'Name', 'Email', 'Phone', 'Location', 'Petition', 
            'Comment', 'Verified', 'Anonymous', 'Date', 'IP Address'
        ));
        
        // Data
        foreach ($signatures as $signature) {
            fputcsv($output, array(
                $signature->id,
                $signature->first_name . ' ' . $signature->last_name,
                $signature->user_email,
                $signature->user_phone,
                $signature->user_location,
                $signature->petition_title,
                $signature->user_comment,
                $signature->is_verified ? 'Yes' : 'No',
                $signature->is_anonymous ? 'Yes' : 'No',
                $signature->created_at,
                $signature->user_ip
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Output signatures as JSON
     */
    private function output_json_signatures($signatures) {
        $filename = 'petition-signatures-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        echo json_encode($signatures, JSON_PRETTY_PRINT);
        exit;
    }
}

// Create alias for backward compatibility
if (!class_exists('EPP_Admin_Dashboard')) {
    class_alias('Environmental_Platform_Petitions_Admin_Dashboard', 'EPP_Admin_Dashboard');
}
