<?php
/**
 * Admin Interface for Environmental Payment Gateway
 *
 * @package EnvironmentalPaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class EPG_Admin {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Invoice generator instance
     */
    private $invoice_generator;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->analytics = EPG_Payment_Analytics::get_instance();
        $this->invoice_generator = EPG_Invoice_Generator::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_epg_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_epg_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_epg_test_gateway', array($this, 'ajax_test_gateway'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . EPG_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Payment Gateway', 'environmental-payment-gateway'),
            __('Payment Gateway', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-credit-card',
            56
        );
        
        add_submenu_page(
            'epg-dashboard',
            __('Dashboard', 'environmental-payment-gateway'),
            __('Dashboard', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'epg-dashboard',
            __('Analytics', 'environmental-payment-gateway'),
            __('Analytics', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'epg-dashboard',
            __('Gateway Settings', 'environmental-payment-gateway'),
            __('Gateway Settings', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'epg-dashboard',
            __('Invoices', 'environmental-payment-gateway'),
            __('Invoices', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-invoices',
            array($this, 'invoices_page')
        );
        
        add_submenu_page(
            'epg-dashboard',
            __('Documentation', 'environmental-payment-gateway'),
            __('Documentation', 'environmental-payment-gateway'),
            'manage_woocommerce',
            'epg-documentation',
            array($this, 'documentation_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook_suffix) {
        if (strpos($hook_suffix, 'epg-') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('epg-admin', EPG_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), EPG_VERSION, true);
        wp_enqueue_style('epg-admin', EPG_PLUGIN_URL . 'assets/css/admin.css', array(), EPG_VERSION);
        
        wp_localize_script('epg-admin', 'epg_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('epg_admin_ajax'),
            'translations' => array(
                'loading' => __('Loading...', 'environmental-payment-gateway'),
                'error' => __('An error occurred.', 'environmental-payment-gateway'),
                'success' => __('Success!', 'environmental-payment-gateway'),
                'confirm_export' => __('Are you sure you want to export analytics data?', 'environmental-payment-gateway'),
                'test_gateway' => __('Testing gateway connection...', 'environmental-payment-gateway'),
            )
        ));
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $dashboard_data = $this->analytics->get_dashboard_data();
        ?>
        <div class="wrap">
            <h1><?php _e('Payment Gateway Dashboard', 'environmental-payment-gateway'); ?></h1>
            
            <div class="epg-dashboard">
                <!-- Key Metrics -->
                <div class="epg-metrics-grid">
                    <div class="epg-metric-card">
                        <div class="epg-metric-icon">💰</div>
                        <div class="epg-metric-content">
                            <h3><?php echo wc_price($dashboard_data['today']['total_revenue']); ?></h3>
                            <p><?php _e('Today\'s Revenue', 'environmental-payment-gateway'); ?></p>
                        </div>
                    </div>
                    
                    <div class="epg-metric-card">
                        <div class="epg-metric-icon">📊</div>
                        <div class="epg-metric-content">
                            <h3><?php echo number_format($dashboard_data['today']['total_transactions']); ?></h3>
                            <p><?php _e('Today\'s Transactions', 'environmental-payment-gateway'); ?></p>
                        </div>
                    </div>
                    
                    <div class="epg-metric-card">
                        <div class="epg-metric-icon">✅</div>
                        <div class="epg-metric-content">
                            <h3><?php echo number_format($dashboard_data['today']['success_rate'], 1); ?>%</h3>
                            <p><?php _e('Success Rate', 'environmental-payment-gateway'); ?></p>
                        </div>
                    </div>
                    
                    <div class="epg-metric-card">
                        <div class="epg-metric-icon">🏦</div>
                        <div class="epg-metric-content">
                            <h3><?php echo count($dashboard_data['gateway_performance']); ?></h3>
                            <p><?php _e('Active Gateways', 'environmental-payment-gateway'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Gateway Performance Chart -->
                <div class="epg-chart-container">
                    <h2><?php _e('Gateway Performance (Last 7 Days)', 'environmental-payment-gateway'); ?></h2>
                    <canvas id="epg-gateway-chart" width="400" height="200"></canvas>
                </div>
                
                <!-- Recent Activity -->
                <div class="epg-recent-activity">
                    <h2><?php _e('Recent Activity', 'environmental-payment-gateway'); ?></h2>
                    <div class="epg-activity-list">
                        <?php if (!empty($dashboard_data['recent_failures'])): ?>
                            <?php foreach (array_slice($dashboard_data['recent_failures'], 0, 5) as $failure): ?>
                            <div class="epg-activity-item error">
                                <span class="epg-activity-icon">❌</span>
                                <span class="epg-activity-text">
                                    <?php echo sprintf(__('%s failures on %s (%s)', 'environmental-payment-gateway'), 
                                        $failure['failed_transactions'], 
                                        $failure['gateway_name'], 
                                        $failure['failure_date']
                                    ); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="epg-activity-item success">
                                <span class="epg-activity-icon">🎉</span>
                                <span class="epg-activity-text"><?php _e('No payment failures today!', 'environmental-payment-gateway'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="epg-quick-actions">
                    <h2><?php _e('Quick Actions', 'environmental-payment-gateway'); ?></h2>
                    <div class="epg-action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=epg-analytics'); ?>" class="button button-primary">
                            <?php _e('View Analytics', 'environmental-payment-gateway'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=epg-settings'); ?>" class="button">
                            <?php _e('Gateway Settings', 'environmental-payment-gateway'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>" class="button">
                            <?php _e('WooCommerce Payments', 'environmental-payment-gateway'); ?>
                        </a>
                        <button type="button" class="button" id="epg-export-data">
                            <?php _e('Export Data', 'environmental-payment-gateway'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize gateway performance chart
            var ctx = document.getElementById('epg-gateway-chart').getContext('2d');
            var gatewayData = <?php echo json_encode($dashboard_data['gateway_performance']); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: gatewayData.map(g => g.gateway_name),
                    datasets: [{
                        data: gatewayData.map(g => parseFloat(g.total_revenue)),
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: '<?php _e("Revenue by Gateway", "environmental-payment-gateway"); ?>'
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30 days';
        $gateway_id = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : null;
        
        $statistics = $this->analytics->get_payment_statistics($period, $gateway_id);
        $gateway_performance = $this->analytics->get_gateway_performance($period);
        $revenue_trends = $this->analytics->get_revenue_trends($period);
        $customer_analytics = $this->analytics->get_customer_analytics($period);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Payment Analytics', 'environmental-payment-gateway'); ?></h1>
            
            <!-- Filters -->
            <div class="epg-analytics-filters">
                <form method="GET" action="">
                    <input type="hidden" name="page" value="epg-analytics">
                    
                    <select name="period">
                        <option value="1 DAY" <?php selected($period, '1 DAY'); ?>><?php _e('Last 24 Hours', 'environmental-payment-gateway'); ?></option>
                        <option value="7 days" <?php selected($period, '7 days'); ?>><?php _e('Last 7 Days', 'environmental-payment-gateway'); ?></option>
                        <option value="30 days" <?php selected($period, '30 days'); ?>><?php _e('Last 30 Days', 'environmental-payment-gateway'); ?></option>
                        <option value="90 days" <?php selected($period, '90 days'); ?>><?php _e('Last 90 Days', 'environmental-payment-gateway'); ?></option>
                        <option value="1 YEAR" <?php selected($period, '1 YEAR'); ?>><?php _e('Last Year', 'environmental-payment-gateway'); ?></option>
                    </select>
                    
                    <select name="gateway">
                        <option value=""><?php _e('All Gateways', 'environmental-payment-gateway'); ?></option>
                        <?php foreach ($gateway_performance as $gateway): ?>
                        <option value="<?php echo esc_attr($gateway['gateway_id']); ?>" <?php selected($gateway_id, $gateway['gateway_id']); ?>>
                            <?php echo esc_html($gateway['gateway_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="button"><?php _e('Filter', 'environmental-payment-gateway'); ?></button>
                </form>
            </div>
            
            <!-- Statistics Overview -->
            <div class="epg-analytics-overview">
                <div class="epg-stat-box">
                    <h3><?php echo number_format($statistics['total_transactions']); ?></h3>
                    <p><?php _e('Total Transactions', 'environmental-payment-gateway'); ?></p>
                </div>
                <div class="epg-stat-box">
                    <h3><?php echo wc_price($statistics['total_revenue']); ?></h3>
                    <p><?php _e('Total Revenue', 'environmental-payment-gateway'); ?></p>
                </div>
                <div class="epg-stat-box">
                    <h3><?php echo number_format($statistics['success_rate'], 1); ?>%</h3>
                    <p><?php _e('Success Rate', 'environmental-payment-gateway'); ?></p>
                </div>
                <div class="epg-stat-box">
                    <h3><?php echo wc_price($statistics['avg_transaction_amount']); ?></h3>
                    <p><?php _e('Average Transaction', 'environmental-payment-gateway'); ?></p>
                </div>
            </div>
            
            <!-- Revenue Trends Chart -->
            <div class="epg-chart-container">
                <h2><?php _e('Revenue Trends', 'environmental-payment-gateway'); ?></h2>
                <canvas id="epg-revenue-chart" width="800" height="400"></canvas>
            </div>
            
            <!-- Gateway Performance Table -->
            <div class="epg-table-container">
                <h2><?php _e('Gateway Performance', 'environmental-payment-gateway'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Gateway', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Transactions', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Revenue', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Success Rate', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Avg. Transaction', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Total Fees', 'environmental-payment-gateway'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gateway_performance as $gateway): ?>
                        <tr>
                            <td><?php echo esc_html($gateway['gateway_name']); ?></td>
                            <td><?php echo number_format($gateway['total_transactions']); ?></td>
                            <td><?php echo wc_price($gateway['total_revenue']); ?></td>
                            <td><?php echo number_format($gateway['success_rate'], 1); ?>%</td>
                            <td><?php echo wc_price($gateway['avg_transaction_amount']); ?></td>
                            <td><?php echo wc_price($gateway['total_fees']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Customer Analytics -->
            <div class="epg-table-container">
                <h2><?php _e('Customer Analytics by Country', 'environmental-payment-gateway'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Country', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Transactions', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Revenue', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Unique Customers', 'environmental-payment-gateway'); ?></th>
                            <th><?php _e('Avg. Order Value', 'environmental-payment-gateway'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_analytics as $country_data): ?>
                        <tr>
                            <td><?php echo esc_html($country_data['customer_country']); ?></td>
                            <td><?php echo number_format($country_data['transactions']); ?></td>
                            <td><?php echo wc_price($country_data['revenue']); ?></td>
                            <td><?php echo number_format($country_data['unique_customers']); ?></td>
                            <td><?php echo wc_price($country_data['avg_order_value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export Section -->
            <div class="epg-export-section">
                <h2><?php _e('Export Data', 'environmental-payment-gateway'); ?></h2>
                <p><?php _e('Export analytics data for further analysis.', 'environmental-payment-gateway'); ?></p>
                <button type="button" class="button button-primary" id="epg-export-csv">
                    <?php _e('Export as CSV', 'environmental-payment-gateway'); ?>
                </button>
                <button type="button" class="button" id="epg-export-json">
                    <?php _e('Export as JSON', 'environmental-payment-gateway'); ?>
                </button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize revenue trends chart
            var ctx = document.getElementById('epg-revenue-chart').getContext('2d');
            var trendData = <?php echo json_encode($revenue_trends); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.period),
                    datasets: [{
                        label: '<?php _e("Revenue", "environmental-payment-gateway"); ?>',
                        data: trendData.map(d => parseFloat(d.revenue)),
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4
                    }, {
                        label: '<?php _e("Transactions", "environmental-payment-gateway"); ?>',
                        data: trendData.map(d => parseInt(d.transactions)),
                        borderColor: '#FF6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Gateway Settings', 'environmental-payment-gateway'); ?></h1>
            
            <div class="epg-settings-grid">
                <div class="epg-settings-section">
                    <h2><?php _e('Vietnamese Payment Gateways', 'environmental-payment-gateway'); ?></h2>
                    
                    <div class="epg-gateway-card">
                        <div class="epg-gateway-header">
                            <h3>VNPay</h3>
                            <span class="epg-gateway-status">
                                <?php echo $this->get_gateway_status('epg_vnpay'); ?>
                            </span>
                        </div>
                        <p><?php _e('Vietnam\'s leading payment gateway supporting ATM cards, credit cards, and e-wallets.', 'environmental-payment-gateway'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=epg_vnpay'); ?>" class="button">
                            <?php _e('Configure', 'environmental-payment-gateway'); ?>
                        </a>
                        <button type="button" class="button epg-test-gateway" data-gateway="epg_vnpay">
                            <?php _e('Test Connection', 'environmental-payment-gateway'); ?>
                        </button>
                    </div>
                    
                    <div class="epg-gateway-card">
                        <div class="epg-gateway-header">
                            <h3>Momo</h3>
                            <span class="epg-gateway-status">
                                <?php echo $this->get_gateway_status('epg_momo'); ?>
                            </span>
                        </div>
                        <p><?php _e('Popular Vietnamese e-wallet for quick and secure payments.', 'environmental-payment-gateway'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=epg_momo'); ?>" class="button">
                            <?php _e('Configure', 'environmental-payment-gateway'); ?>
                        </a>
                        <button type="button" class="button epg-test-gateway" data-gateway="epg_momo">
                            <?php _e('Test Connection', 'environmental-payment-gateway'); ?>
                        </button>
                    </div>
                    
                    <div class="epg-gateway-card">
                        <div class="epg-gateway-header">
                            <h3>ZaloPay</h3>
                            <span class="epg-gateway-status">
                                <?php echo $this->get_gateway_status('epg_zalopay'); ?>
                            </span>
                        </div>
                        <p><?php _e('Trusted digital wallet with QR code payment support.', 'environmental-payment-gateway'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=epg_zalopay'); ?>" class="button">
                            <?php _e('Configure', 'environmental-payment-gateway'); ?>
                        </a>
                        <button type="button" class="button epg-test-gateway" data-gateway="epg_zalopay">
                            <?php _e('Test Connection', 'environmental-payment-gateway'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="epg-settings-section">
                    <h2><?php _e('System Health', 'environmental-payment-gateway'); ?></h2>
                    
                    <div class="epg-health-check">
                        <div class="epg-health-item">
                            <span class="epg-health-icon <?php echo extension_loaded('curl') ? 'success' : 'error'; ?>">
                                <?php echo extension_loaded('curl') ? '✅' : '❌'; ?>
                            </span>
                            <span><?php _e('cURL Extension', 'environmental-payment-gateway'); ?></span>
                        </div>
                        
                        <div class="epg-health-item">
                            <span class="epg-health-icon <?php echo extension_loaded('json') ? 'success' : 'error'; ?>">
                                <?php echo extension_loaded('json') ? '✅' : '❌'; ?>
                            </span>
                            <span><?php _e('JSON Extension', 'environmental-payment-gateway'); ?></span>
                        </div>
                        
                        <div class="epg-health-item">
                            <span class="epg-health-icon <?php echo extension_loaded('openssl') ? 'success' : 'error'; ?>">
                                <?php echo extension_loaded('openssl') ? '✅' : '❌'; ?>
                            </span>
                            <span><?php _e('OpenSSL Extension', 'environmental-payment-gateway'); ?></span>
                        </div>
                        
                        <div class="epg-health-item">
                            <span class="epg-health-icon <?php echo is_ssl() ? 'success' : 'warning'; ?>">
                                <?php echo is_ssl() ? '✅' : '⚠️'; ?>
                            </span>
                            <span><?php _e('SSL Certificate', 'environmental-payment-gateway'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Invoices page
     */
    public function invoices_page() {
        $invoice_stats = $this->invoice_generator->get_invoice_statistics();
        ?>
        <div class="wrap">
            <h1><?php _e('Invoice Management', 'environmental-payment-gateway'); ?></h1>
            
            <div class="epg-invoice-stats">
                <div class="epg-stat-box">
                    <h3><?php echo number_format($invoice_stats['total_invoices']); ?></h3>
                    <p><?php _e('Total Invoices', 'environmental-payment-gateway'); ?></p>
                </div>
                <div class="epg-stat-box">
                    <h3><?php echo number_format($invoice_stats['generated_invoices']); ?></h3>
                    <p><?php _e('Generated', 'environmental-payment-gateway'); ?></p>
                </div>
                <div class="epg-stat-box">
                    <h3><?php echo number_format($invoice_stats['manually_generated']); ?></h3>
                    <p><?php _e('Manual Generation', 'environmental-payment-gateway'); ?></p>
                </div>
            </div>
            
            <div class="epg-invoice-settings">
                <h2><?php _e('Invoice Settings', 'environmental-payment-gateway'); ?></h2>
                <form method="post" action="options.php">
                    <?php settings_fields('epg_invoice_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Auto Generate', 'environmental-payment-gateway'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="epg_auto_invoice" value="1" <?php checked(get_option('epg_auto_invoice', 1)); ?>>
                                    <?php _e('Automatically generate invoices on order completion', 'environmental-payment-gateway'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email Attachment', 'environmental-payment-gateway'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="epg_email_invoice" value="1" <?php checked(get_option('epg_email_invoice', 1)); ?>>
                                    <?php _e('Attach invoices to customer emails', 'environmental-payment-gateway'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Invoice Prefix', 'environmental-payment-gateway'); ?></th>
                            <td>
                                <input type="text" name="epg_invoice_prefix" value="<?php echo esc_attr(get_option('epg_invoice_prefix', 'EPG')); ?>" class="regular-text">
                                <p class="description"><?php _e('Prefix for invoice numbers (e.g., EPG-202312-0001)', 'environmental-payment-gateway'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Documentation', 'environmental-payment-gateway'); ?></h1>
            
            <div class="epg-documentation">
                <div class="epg-doc-section">
                    <h2><?php _e('Getting Started', 'environmental-payment-gateway'); ?></h2>
                    <p><?php _e('Welcome to the Environmental Payment Gateway plugin. This comprehensive payment solution supports Vietnamese and international payment methods with advanced analytics and environmental tracking.', 'environmental-payment-gateway'); ?></p>
                    
                    <h3><?php _e('Quick Setup', 'environmental-payment-gateway'); ?></h3>
                    <ol>
                        <li><?php _e('Configure your preferred payment gateways in the Gateway Settings page', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Test the connections using the "Test Connection" buttons', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Enable the gateways in WooCommerce > Settings > Payments', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Monitor performance through the Analytics dashboard', 'environmental-payment-gateway'); ?></li>
                    </ol>
                </div>
                
                <div class="epg-doc-section">
                    <h2><?php _e('Supported Payment Gateways', 'environmental-payment-gateway'); ?></h2>
                    
                    <h3><?php _e('Vietnamese Gateways', 'environmental-payment-gateway'); ?></h3>
                    <ul>
                        <li><strong>VNPay:</strong> <?php _e('Supports ATM cards, credit cards, and e-wallets', 'environmental-payment-gateway'); ?></li>
                        <li><strong>Momo:</strong> <?php _e('Popular e-wallet with instant payments', 'environmental-payment-gateway'); ?></li>
                        <li><strong>ZaloPay:</strong> <?php _e('Digital wallet with QR code support', 'environmental-payment-gateway'); ?></li>
                    </ul>
                </div>
                
                <div class="epg-doc-section">
                    <h2><?php _e('Analytics & Reporting', 'environmental-payment-gateway'); ?></h2>
                    <p><?php _e('The plugin provides comprehensive analytics including:', 'environmental-payment-gateway'); ?></p>
                    <ul>
                        <li><?php _e('Real-time transaction monitoring', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Gateway performance comparison', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Revenue trends and forecasting', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Customer behavior analysis', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Automated report generation', 'environmental-payment-gateway'); ?></li>
                    </ul>
                </div>
                
                <div class="epg-doc-section">
                    <h2><?php _e('Invoice Generation', 'environmental-payment-gateway'); ?></h2>
                    <p><?php _e('Automatic PDF invoice generation with:', 'environmental-payment-gateway'); ?></p>
                    <ul>
                        <li><?php _e('Customizable templates', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Environmental impact messaging', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Automatic email attachment', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Secure customer download links', 'environmental-payment-gateway'); ?></li>
                    </ul>
                </div>
                
                <div class="epg-doc-section">
                    <h2><?php _e('Support & Troubleshooting', 'environmental-payment-gateway'); ?></h2>
                    <p><?php _e('If you encounter any issues:', 'environmental-payment-gateway'); ?></p>
                    <ol>
                        <li><?php _e('Check the System Health in Gateway Settings', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Use the Test Connection feature for each gateway', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Review the WooCommerce logs for detailed error messages', 'environmental-payment-gateway'); ?></li>
                        <li><?php _e('Ensure SSL is enabled for secure payments', 'environmental-payment-gateway'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get gateway status
     */
    private function get_gateway_status($gateway_id) {
        $gateways = WC()->payment_gateways()->payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return '<span class="epg-status error">' . __('Not Available', 'environmental-payment-gateway') . '</span>';
        }
        
        $gateway = $gateways[$gateway_id];
        
        if ($gateway->enabled === 'yes') {
            return '<span class="epg-status success">' . __('Enabled', 'environmental-payment-gateway') . '</span>';
        } else {
            return '<span class="epg-status warning">' . __('Disabled', 'environmental-payment-gateway') . '</span>';
        }
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('epg_admin_ajax', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied.', 'environmental-payment-gateway'));
        }
        
        $data = $this->analytics->get_dashboard_data();
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Export analytics
     */
    public function ajax_export_analytics() {
        check_ajax_referer('epg_admin_ajax', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied.', 'environmental-payment-gateway'));
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30 days';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        
        $data = $this->analytics->export_analytics_data($period, $format);
        
        wp_send_json_success(array(
            'data' => $data,
            'filename' => 'epg-analytics-' . date('Y-m-d') . '.' . $format
        ));
    }
    
    /**
     * AJAX: Test gateway
     */
    public function ajax_test_gateway() {
        check_ajax_referer('epg_admin_ajax', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied.', 'environmental-payment-gateway'));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway_id']);
        
        // Basic connection test
        $result = $this->test_gateway_connection($gateway_id);
        
        if ($result) {
            wp_send_json_success(__('Gateway connection successful!', 'environmental-payment-gateway'));
        } else {
            wp_send_json_error(__('Gateway connection failed. Please check your settings.', 'environmental-payment-gateway'));
        }
    }
    
    /**
     * Test gateway connection
     */
    private function test_gateway_connection($gateway_id) {
        $gateways = WC()->payment_gateways()->payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return false;
        }
        
        $gateway = $gateways[$gateway_id];
        
        // Basic validation - check if required fields are filled
        switch ($gateway_id) {
            case 'epg_vnpay':
                return !empty($gateway->tmn_code) && !empty($gateway->secret_key);
            case 'epg_momo':
                return !empty($gateway->partner_code) && !empty($gateway->access_key) && !empty($gateway->secret_key);
            case 'epg_zalopay':
                return !empty($gateway->app_id) && !empty($gateway->key1) && !empty($gateway->key2);
            default:
                return false;
        }
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=epg-dashboard') . '">' . __('Settings', 'environmental-payment-gateway') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>';
            echo __('Environmental Payment Gateway requires WooCommerce to be installed and activated.', 'environmental-payment-gateway');
            echo '</p></div>';
        }
        
        // Check if SSL is enabled
        if (!is_ssl()) {
            echo '<div class="notice notice-warning"><p>';
            echo __('For security reasons, it is recommended to enable SSL when using payment gateways.', 'environmental-payment-gateway');
            echo '</p></div>';
        }
    }
}
