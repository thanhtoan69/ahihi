<?php
/**
 * Payment Analytics Engine for Environmental Platform
 *
 * @package EnvironmentalPaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Analytics Class
 */
class EPG_Payment_Analytics {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Database table name
     */
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'epg_payment_analytics';
        
        add_action('woocommerce_payment_complete', array($this, 'record_successful_payment'));
        add_action('woocommerce_order_status_failed', array($this, 'record_failed_payment'));
        add_action('woocommerce_order_status_refunded', array($this, 'record_refund'));
        add_action('woocommerce_order_refunded', array($this, 'record_partial_refund'), 10, 2);
        
        // Schedule cleanup of old analytics data
        if (!wp_next_scheduled('epg_cleanup_analytics')) {
            wp_schedule_event(time(), 'weekly', 'epg_cleanup_analytics');
        }
        
        add_action('epg_cleanup_analytics', array($this, 'cleanup_old_data'));
    }
    
    /**
     * Record successful payment
     */
    public function record_successful_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $this->record_payment_data($order, 'completed');
    }
    
    /**
     * Record failed payment
     */
    public function record_failed_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $this->record_payment_data($order, 'failed');
    }
    
    /**
     * Record refund
     */
    public function record_refund($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $this->record_payment_data($order, 'refunded');
    }
    
    /**
     * Record partial refund
     */
    public function record_partial_refund($order_id, $refund_id) {
        $order = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);
        
        if (!$order || !$refund) {
            return;
        }
        
        $this->record_payment_data($order, 'partial_refund', array(
            'refund_amount' => abs($refund->get_amount()),
            'refund_reason' => $refund->get_reason()
        ));
    }
    
    /**
     * Record payment data
     */
    private function record_payment_data($order, $status, $additional_data = array()) {
        global $wpdb;
        
        $payment_method = $order->get_payment_method();
        $gateway = WC()->payment_gateways()->payment_gateways()[$payment_method] ?? null;
        
        $fee_amount = 0;
        $fee_percentage = 0;
        
        // Calculate fees if gateway supports it
        if ($gateway && method_exists($gateway, 'get_eds_processor')) {
            $processor = $gateway->get_eds_processor();
            if (isset($processor['fee_structure'])) {
                if ($processor['fee_structure']['type'] === 'percentage') {
                    $is_domestic = $this->is_domestic_payment($order);
                    $fee_percentage = $is_domestic ? 
                        $processor['fee_structure']['domestic'] : 
                        $processor['fee_structure']['international'];
                    $fee_amount = ($order->get_total() * $fee_percentage) / 100;
                }
            }
        }
        
        $data = array(
            'order_id' => $order->get_id(),
            'gateway_id' => $payment_method,
            'gateway_name' => $gateway ? $gateway->get_title() : $payment_method,
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => $status,
            'transaction_id' => $order->get_transaction_id(),
            'customer_id' => $order->get_customer_id(),
            'customer_email' => $order->get_billing_email(),
            'customer_country' => $order->get_billing_country(),
            'fee_amount' => $fee_amount,
            'fee_percentage' => $fee_percentage,
            'payment_date' => current_time('mysql'),
            'additional_data' => json_encode($additional_data),
        );
        
        $wpdb->insert($this->table_name, $data);
        
        // Log the event
        $this->log_analytics_event($order, $status, $data);
    }
    
    /**
     * Check if payment is domestic
     */
    private function is_domestic_payment($order) {
        $store_country = wc_get_base_location()['country'];
        $customer_country = $order->get_billing_country();
        
        return $store_country === $customer_country;
    }
    
    /**
     * Log analytics event
     */
    private function log_analytics_event($order, $status, $data) {
        $message = sprintf(
            'Payment analytics recorded: Order #%d, Gateway: %s, Status: %s, Amount: %s %s',
            $order->get_id(),
            $data['gateway_name'],
            $status,
            $data['amount'],
            $data['currency']
        );
        
        error_log('[EPG Analytics] ' . $message);
    }
    
    /**
     * Get payment statistics
     */
    public function get_payment_statistics($period = '30 days', $gateway_id = null) {
        global $wpdb;
        
        $where_clause = "WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)";
        $params = array($period);
        
        if ($gateway_id) {
            $where_clause .= " AND gateway_id = %s";
            $params[] = $gateway_id;
        }
        
        $query = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status IN ('refunded', 'partial_refund') THEN 1 ELSE 0 END) as refunds,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(fee_amount) as total_fees,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as avg_transaction_amount,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM {$this->table_name} 
            {$where_clause}
        ", $params);
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        // Calculate success rate
        if ($result['total_transactions'] > 0) {
            $result['success_rate'] = ($result['successful_payments'] / $result['total_transactions']) * 100;
        } else {
            $result['success_rate'] = 0;
        }
        
        return $result;
    }
    
    /**
     * Get gateway performance comparison
     */
    public function get_gateway_performance($period = '30 days') {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                gateway_id,
                gateway_name,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(fee_amount) as total_fees,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as avg_transaction_amount,
                (SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
            FROM {$this->table_name} 
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            GROUP BY gateway_id, gateway_name
            ORDER BY total_revenue DESC
        ", $period);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get revenue trends
     */
    public function get_revenue_trends($period = '30 days', $interval = 'day') {
        global $wpdb;
        
        $date_format = '%Y-%m-%d';
        $interval_clause = 'DAY';
        
        switch ($interval) {
            case 'hour':
                $date_format = '%Y-%m-%d %H:00:00';
                $interval_clause = 'HOUR';
                break;
            case 'week':
                $date_format = '%Y-%u';
                $interval_clause = 'WEEK';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $interval_clause = 'MONTH';
                break;
        }
        
        $query = $wpdb->prepare("
            SELECT 
                DATE_FORMAT(payment_date, %s) as period,
                COUNT(*) as transactions,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                SUM(fee_amount) as fees
            FROM {$this->table_name} 
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            GROUP BY DATE_FORMAT(payment_date, %s)
            ORDER BY period ASC
        ", $date_format, $period, $date_format);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get customer analytics
     */
    public function get_customer_analytics($period = '30 days') {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                customer_country,
                COUNT(*) as transactions,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                COUNT(DISTINCT customer_id) as unique_customers,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as avg_order_value
            FROM {$this->table_name} 
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            AND customer_country IS NOT NULL
            GROUP BY customer_country
            ORDER BY revenue DESC
            LIMIT 20
        ", $period);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get top customers
     */
    public function get_top_customers($period = '30 days', $limit = 10) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                customer_id,
                customer_email,
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as avg_order_value,
                MAX(payment_date) as last_purchase_date
            FROM {$this->table_name} 
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            AND customer_id > 0
            GROUP BY customer_id, customer_email
            ORDER BY total_spent DESC
            LIMIT %d
        ", $period, $limit);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get failure analysis
     */
    public function get_failure_analysis($period = '30 days') {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                gateway_id,
                gateway_name,
                COUNT(*) as failed_transactions,
                SUM(amount) as failed_amount,
                DATE_FORMAT(payment_date, '%%Y-%%m-%%d') as failure_date
            FROM {$this->table_name} 
            WHERE status = 'failed'
            AND payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            GROUP BY gateway_id, gateway_name, DATE_FORMAT(payment_date, '%%Y-%%m-%%d')
            ORDER BY failure_date DESC, failed_transactions DESC
        ", $period);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get refund analysis
     */
    public function get_refund_analysis($period = '30 days') {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                gateway_id,
                gateway_name,
                COUNT(*) as refund_count,
                SUM(amount) as refunded_amount,
                AVG(amount) as avg_refund_amount
            FROM {$this->table_name} 
            WHERE status IN ('refunded', 'partial_refund')
            AND payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            GROUP BY gateway_id, gateway_name
            ORDER BY refunded_amount DESC
        ", $period);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics_data($period = '30 days', $format = 'csv') {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                order_id,
                gateway_name,
                amount,
                currency,
                status,
                transaction_id,
                customer_email,
                customer_country,
                fee_amount,
                fee_percentage,
                payment_date
            FROM {$this->table_name} 
            WHERE payment_date >= DATE_SUB(NOW(), INTERVAL %s)
            ORDER BY payment_date DESC
        ", $period);
        
        $data = $wpdb->get_results($query, ARRAY_A);
        
        if ($format === 'csv') {
            return $this->export_to_csv($data);
        } elseif ($format === 'json') {
            return json_encode($data);
        }
        
        return $data;
    }
    
    /**
     * Export data to CSV
     */
    private function export_to_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Get real-time dashboard data
     */
    public function get_dashboard_data() {
        return array(
            'today' => $this->get_payment_statistics('1 DAY'),
            'this_week' => $this->get_payment_statistics('1 WEEK'),
            'this_month' => $this->get_payment_statistics('1 MONTH'),
            'gateway_performance' => $this->get_gateway_performance('7 days'),
            'recent_failures' => $this->get_failure_analysis('1 DAY'),
            'top_countries' => $this->get_customer_analytics('7 days'),
        );
    }
    
    /**
     * Cleanup old analytics data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Keep data for 2 years by default
        $retention_period = apply_filters('epg_analytics_retention_period', '2 YEAR');
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->table_name} 
            WHERE payment_date < DATE_SUB(NOW(), INTERVAL %s)
        ", $retention_period));
        
        if ($deleted) {
            error_log("[EPG Analytics] Cleaned up {$deleted} old analytics records");
        }
    }
    
    /**
     * Schedule analytics report
     */
    public function schedule_analytics_report($email, $frequency = 'weekly') {
        $hook = 'epg_send_analytics_report';
        
        // Clear existing schedule
        wp_clear_scheduled_hook($hook, array($email));
        
        // Schedule new report
        wp_schedule_event(time(), $frequency, $hook, array($email));
    }
    
    /**
     * Send analytics report via email
     */
    public function send_analytics_report($email) {
        $data = $this->get_dashboard_data();
        
        $subject = sprintf(__('Payment Analytics Report - %s', 'environmental-payment-gateway'), get_bloginfo('name'));
        
        $message = $this->generate_report_email($data);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Generate report email content
     */
    private function generate_report_email($data) {
        ob_start();
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
                .stats { display: flex; flex-wrap: wrap; margin: 20px 0; }
                .stat-box { background: #f9f9f9; margin: 10px; padding: 15px; border-left: 4px solid #2c5aa0; flex: 1; min-width: 200px; }
                .stat-number { font-size: 24px; font-weight: bold; color: #2c5aa0; }
                .stat-label { color: #666; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f5f5f5; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo get_bloginfo('name'); ?> - Payment Analytics Report</h1>
                <p>Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            
            <h2>Today's Performance</h2>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($data['today']['total_transactions']); ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo wc_price($data['today']['total_revenue']); ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($data['today']['success_rate'], 1); ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
            
            <h2>This Month's Performance</h2>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($data['this_month']['total_transactions']); ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo wc_price($data['this_month']['total_revenue']); ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo wc_price($data['this_month']['total_fees']); ?></div>
                    <div class="stat-label">Total Fees</div>
                </div>
            </div>
            
            <?php if (!empty($data['gateway_performance'])): ?>
            <h2>Gateway Performance (Last 7 Days)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Gateway</th>
                        <th>Transactions</th>
                        <th>Revenue</th>
                        <th>Success Rate</th>
                        <th>Avg. Transaction</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['gateway_performance'] as $gateway): ?>
                    <tr>
                        <td><?php echo esc_html($gateway['gateway_name']); ?></td>
                        <td><?php echo number_format($gateway['total_transactions']); ?></td>
                        <td><?php echo wc_price($gateway['total_revenue']); ?></td>
                        <td><?php echo number_format($gateway['success_rate'], 1); ?>%</td>
                        <td><?php echo wc_price($gateway['avg_transaction_amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
                <p>This report was automatically generated by the Environmental Payment Gateway plugin.</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
