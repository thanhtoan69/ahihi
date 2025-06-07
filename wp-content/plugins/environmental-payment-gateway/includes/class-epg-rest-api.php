<?php
/**
 * REST API Endpoints for Environmental Payment Gateway
 * 
 * Provides REST API endpoints for mobile apps and external integrations
 * to interact with payment gateways and analytics.
 * 
 * @package EnvironmentalPaymentGateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Handler Class
 */
class EPG_REST_API {
    
    /**
     * API version
     */
    const API_VERSION = 'v1';
    
    /**
     * API namespace
     */
    const API_NAMESPACE = 'epg/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Payment Gateway Routes
        register_rest_route(self::API_NAMESPACE, '/gateways', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_gateways'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'active_only' => array(
                    'description' => 'Return only active gateways',
                    'type' => 'boolean',
                    'default' => false,
                ),
                'currency' => array(
                    'description' => 'Filter by supported currency',
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/gateways/(?P<gateway_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_gateway_details'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'gateway_id' => array(
                    'description' => 'Gateway identifier',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/gateways/(?P<gateway_id>[a-zA-Z0-9_-]+)/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_gateway_status'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'gateway_id' => array(
                    'description' => 'Gateway identifier',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
        
        // Payment Routes
        register_rest_route(self::API_NAMESPACE, '/payment/process', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_payment'),
            'permission_callback' => array($this, 'check_payment_permissions'),
            'args' => array(
                'order_id' => array(
                    'description' => 'WooCommerce order ID',
                    'type' => 'integer',
                    'required' => true,
                ),
                'gateway_id' => array(
                    'description' => 'Payment gateway ID',
                    'type' => 'string',
                    'required' => true,
                ),
                'payment_data' => array(
                    'description' => 'Gateway-specific payment data',
                    'type' => 'object',
                    'required' => true,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/payment/(?P<transaction_id>[a-zA-Z0-9_-]+)/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_status'),
            'permission_callback' => array($this, 'check_payment_permissions'),
            'args' => array(
                'transaction_id' => array(
                    'description' => 'Transaction ID',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/payment/(?P<transaction_id>[a-zA-Z0-9_-]+)/refund', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_refund'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'transaction_id' => array(
                    'description' => 'Transaction ID',
                    'type' => 'string',
                    'required' => true,
                ),
                'amount' => array(
                    'description' => 'Refund amount',
                    'type' => 'number',
                    'required' => false,
                ),
                'reason' => array(
                    'description' => 'Refund reason',
                    'type' => 'string',
                    'required' => false,
                ),
            ),
        ));
        
        // Analytics Routes
        register_rest_route(self::API_NAMESPACE, '/analytics/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'period' => array(
                    'description' => 'Time period (7d, 30d, 90d, 1y)',
                    'type' => 'string',
                    'default' => '30d',
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/analytics/transactions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_transactions'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'page' => array(
                    'description' => 'Page number',
                    'type' => 'integer',
                    'default' => 1,
                ),
                'per_page' => array(
                    'description' => 'Items per page',
                    'type' => 'integer',
                    'default' => 20,
                ),
                'gateway' => array(
                    'description' => 'Filter by gateway',
                    'type' => 'string',
                    'default' => '',
                ),
                'status' => array(
                    'description' => 'Filter by status',
                    'type' => 'string',
                    'default' => '',
                ),
                'date_from' => array(
                    'description' => 'Start date (Y-m-d)',
                    'type' => 'string',
                    'default' => '',
                ),
                'date_to' => array(
                    'description' => 'End date (Y-m-d)',
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/analytics/export', array(
            'methods' => 'POST',
            'callback' => array($this, 'export_analytics'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => array(
                'type' => array(
                    'description' => 'Export type (csv, json)',
                    'type' => 'string',
                    'required' => true,
                ),
                'period' => array(
                    'description' => 'Time period',
                    'type' => 'string',
                    'default' => '30d',
                ),
                'filters' => array(
                    'description' => 'Export filters',
                    'type' => 'object',
                    'default' => array(),
                ),
            ),
        ));
        
        // Invoice Routes
        register_rest_route(self::API_NAMESPACE, '/invoices', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_invoices'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'page' => array(
                    'description' => 'Page number',
                    'type' => 'integer',
                    'default' => 1,
                ),
                'per_page' => array(
                    'description' => 'Items per page',
                    'type' => 'integer',
                    'default' => 20,
                ),
                'customer_id' => array(
                    'description' => 'Filter by customer ID',
                    'type' => 'integer',
                    'default' => 0,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/invoices/(?P<invoice_id>[0-9]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_invoice'),
            'permission_callback' => array($this, 'check_invoice_permissions'),
            'args' => array(
                'invoice_id' => array(
                    'description' => 'Invoice ID',
                    'type' => 'integer',
                    'required' => true,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/invoices/(?P<invoice_id>[0-9]+)/download', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_invoice'),
            'permission_callback' => array($this, 'check_invoice_permissions'),
            'args' => array(
                'invoice_id' => array(
                    'description' => 'Invoice ID',
                    'type' => 'integer',
                    'required' => true,
                ),
            ),
        ));
        
        // Currency Routes
        register_rest_route(self::API_NAMESPACE, '/currency/rates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_currency_rates'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'from' => array(
                    'description' => 'Source currency code',
                    'type' => 'string',
                    'required' => true,
                ),
                'to' => array(
                    'description' => 'Target currency codes (comma-separated)',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
        
        register_rest_route(self::API_NAMESPACE, '/currency/convert', array(
            'methods' => 'POST',
            'callback' => array($this, 'convert_currency'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'amount' => array(
                    'description' => 'Amount to convert',
                    'type' => 'number',
                    'required' => true,
                ),
                'from' => array(
                    'description' => 'Source currency code',
                    'type' => 'string',
                    'required' => true,
                ),
                'to' => array(
                    'description' => 'Target currency code',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
        
        // Webhook Routes
        register_rest_route(self::API_NAMESPACE, '/webhook/(?P<gateway_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Webhook authentication handled internally
            'args' => array(
                'gateway_id' => array(
                    'description' => 'Gateway identifier',
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
    }
    
    /**
     * Get available payment gateways
     */
    public function get_available_gateways($request) {
        $active_only = $request->get_param('active_only');
        $currency = $request->get_param('currency');
        
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $result = array();
        
        foreach ($gateways as $gateway_id => $gateway) {
            if (!($gateway instanceof EPG_Gateway_Base)) {
                continue;
            }
            
            if ($active_only && 'yes' !== $gateway->enabled) {
                continue;
            }
            
            if ($currency && !in_array($currency, $gateway->get_supported_currencies())) {
                continue;
            }
            
            $result[] = array(
                'id' => $gateway_id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'enabled' => 'yes' === $gateway->enabled,
                'supports' => $gateway->supports,
                'supported_currencies' => $gateway->get_supported_currencies(),
                'icon' => $gateway->get_icon(),
                'has_fields' => $gateway->has_fields,
                'method_title' => $gateway->get_method_title(),
            );
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get gateway details
     */
    public function get_gateway_details($request) {
        $gateway_id = $request->get_param('gateway_id');
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return new WP_Error('gateway_not_found', 'Payment gateway not found', array('status' => 404));
        }
        
        $gateway = $gateways[$gateway_id];
        
        if (!($gateway instanceof EPG_Gateway_Base)) {
            return new WP_Error('invalid_gateway', 'Not an EPG gateway', array('status' => 400));
        }
        
        $details = array(
            'id' => $gateway_id,
            'title' => $gateway->get_title(),
            'description' => $gateway->get_description(),
            'enabled' => 'yes' === $gateway->enabled,
            'supports' => $gateway->supports,
            'supported_currencies' => $gateway->get_supported_currencies(),
            'form_fields' => $gateway->get_form_fields(),
            'settings' => $gateway->settings,
            'test_mode' => 'yes' === $gateway->get_option('test_mode'),
            'capabilities' => $gateway->get_gateway_capabilities(),
        );
        
        return rest_ensure_response($details);
    }
    
    /**
     * Get gateway status
     */
    public function get_gateway_status($request) {
        $gateway_id = $request->get_param('gateway_id');
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return new WP_Error('gateway_not_found', 'Payment gateway not found', array('status' => 404));
        }
        
        $gateway = $gateways[$gateway_id];
        
        if (!($gateway instanceof EPG_Gateway_Base)) {
            return new WP_Error('invalid_gateway', 'Not an EPG gateway', array('status' => 400));
        }
        
        // Check gateway health
        $health_check = $gateway->health_check();
        
        $status = array(
            'id' => $gateway_id,
            'enabled' => 'yes' === $gateway->enabled,
            'available' => $gateway->is_available(),
            'test_mode' => 'yes' === $gateway->get_option('test_mode'),
            'health' => $health_check,
            'last_transaction' => $this->get_last_transaction($gateway_id),
        );
        
        return rest_ensure_response($status);
    }
    
    /**
     * Process payment via API
     */
    public function process_payment($request) {
        $order_id = $request->get_param('order_id');
        $gateway_id = $request->get_param('gateway_id');
        $payment_data = $request->get_param('payment_data');
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', 'Order not found', array('status' => 404));
        }
        
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        if (!isset($gateways[$gateway_id])) {
            return new WP_Error('gateway_not_found', 'Payment gateway not found', array('status' => 404));
        }
        
        $gateway = $gateways[$gateway_id];
        
        if (!($gateway instanceof EPG_Gateway_Base)) {
            return new WP_Error('invalid_gateway', 'Not an EPG gateway', array('status' => 400));
        }
        
        // Set payment data
        $_POST = array_merge($_POST, $payment_data);
        
        // Process payment
        $result = $gateway->process_payment($order_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get payment status
     */
    public function get_payment_status($request) {
        $transaction_id = $request->get_param('transaction_id');
        
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'epg_payment_analytics';
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$analytics_table} WHERE transaction_id = %s",
            $transaction_id
        ));
        
        if (!$transaction) {
            return new WP_Error('transaction_not_found', 'Transaction not found', array('status' => 404));
        }
        
        $status = array(
            'transaction_id' => $transaction->transaction_id,
            'order_id' => $transaction->order_id,
            'gateway' => $transaction->gateway,
            'status' => $transaction->status,
            'amount' => floatval($transaction->amount),
            'currency' => $transaction->currency,
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
        );
        
        return rest_ensure_response($status);
    }
    
    /**
     * Process refund via API
     */
    public function process_refund($request) {
        $transaction_id = $request->get_param('transaction_id');
        $amount = $request->get_param('amount');
        $reason = $request->get_param('reason');
        
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'epg_payment_analytics';
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$analytics_table} WHERE transaction_id = %s",
            $transaction_id
        ));
        
        if (!$transaction) {
            return new WP_Error('transaction_not_found', 'Transaction not found', array('status' => 404));
        }
        
        $order = wc_get_order($transaction->order_id);
        if (!$order) {
            return new WP_Error('order_not_found', 'Order not found', array('status' => 404));
        }
        
        $gateway = $order->get_payment_method();
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (!isset($gateways[$gateway])) {
            return new WP_Error('gateway_not_found', 'Payment gateway not found', array('status' => 404));
        }
        
        $gateway_instance = $gateways[$gateway];
        
        if (!($gateway_instance instanceof EPG_Gateway_Base)) {
            return new WP_Error('invalid_gateway', 'Not an EPG gateway', array('status' => 400));
        }
        
        // Process refund
        $result = $gateway_instance->process_refund($transaction->order_id, $amount, $reason);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'refund_amount' => $amount ?: $transaction->amount,
            'refund_reason' => $reason,
            'transaction_id' => $transaction_id,
        ));
    }
    
    /**
     * Get dashboard analytics data
     */
    public function get_dashboard_data($request) {
        $period = $request->get_param('period');
        
        $analytics = new EPG_Payment_Analytics();
        $data = $analytics->get_dashboard_data($period);
        
        return rest_ensure_response($data);
    }
    
    /**
     * Get transactions list
     */
    public function get_transactions($request) {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $gateway = $request->get_param('gateway');
        $status = $request->get_param('status');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $analytics = new EPG_Payment_Analytics();
        $filters = array(
            'gateway' => $gateway,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to,
        );
        
        $transactions = $analytics->get_transactions($page, $per_page, $filters);
        
        return rest_ensure_response($transactions);
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics($request) {
        $type = $request->get_param('type');
        $period = $request->get_param('period');
        $filters = $request->get_param('filters');
        
        $analytics = new EPG_Payment_Analytics();
        $export_data = $analytics->export_data($type, $period, $filters);
        
        if (is_wp_error($export_data)) {
            return $export_data;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'download_url' => $export_data['url'],
            'filename' => $export_data['filename'],
            'size' => $export_data['size'],
        ));
    }
    
    /**
     * Get invoices list
     */
    public function get_invoices($request) {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $customer_id = $request->get_param('customer_id');
        
        // If customer_id is provided and user is not admin, verify they can only see their own invoices
        if ($customer_id && !current_user_can('manage_woocommerce')) {
            $current_user_id = get_current_user_id();
            if ($customer_id != $current_user_id) {
                return new WP_Error('unauthorized', 'Unauthorized access', array('status' => 403));
            }
        }
        
        global $wpdb;
        $invoices_table = $wpdb->prefix . 'epg_invoices';
        
        $where = array('1=1');
        $values = array();
        
        if ($customer_id) {
            $where[] = 'customer_id = %d';
            $values[] = $customer_id;
        }
        
        $offset = ($page - 1) * $per_page;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$invoices_table} 
             WHERE " . implode(' AND ', $where) . "
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            array_merge($values, array($per_page, $offset))
        );
        
        $invoices = $wpdb->get_results($query);
        
        // Get total count
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$invoices_table} WHERE " . implode(' AND ', $where),
            $values
        );
        $total = $wpdb->get_var($count_query);
        
        return rest_ensure_response(array(
            'invoices' => $invoices,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ));
    }
    
    /**
     * Get single invoice
     */
    public function get_invoice($request) {
        $invoice_id = $request->get_param('invoice_id');
        
        global $wpdb;
        $invoices_table = $wpdb->prefix . 'epg_invoices';
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$invoices_table} WHERE id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            return new WP_Error('invoice_not_found', 'Invoice not found', array('status' => 404));
        }
        
        return rest_ensure_response($invoice);
    }
    
    /**
     * Download invoice PDF
     */
    public function download_invoice($request) {
        $invoice_id = $request->get_param('invoice_id');
        
        $invoice_generator = new EPG_Invoice_Generator();
        $download_url = $invoice_generator->get_download_url($invoice_id);
        
        if (is_wp_error($download_url)) {
            return $download_url;
        }
        
        return rest_ensure_response(array(
            'download_url' => $download_url,
        ));
    }
    
    /**
     * Get currency exchange rates
     */
    public function get_currency_rates($request) {
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        
        $currency_converter = new EPG_Currency_Converter();
        $to_currencies = explode(',', $to);
        
        $rates = array();
        foreach ($to_currencies as $to_currency) {
            $to_currency = trim($to_currency);
            $rate = $currency_converter->get_exchange_rate($from, $to_currency);
            $rates[$to_currency] = $rate;
        }
        
        return rest_ensure_response(array(
            'from' => $from,
            'rates' => $rates,
            'timestamp' => time(),
        ));
    }
    
    /**
     * Convert currency amount
     */
    public function convert_currency($request) {
        $amount = $request->get_param('amount');
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        
        $currency_converter = new EPG_Currency_Converter();
        $converted_amount = $currency_converter->convert($amount, $from, $to);
        
        if (is_wp_error($converted_amount)) {
            return $converted_amount;
        }
        
        return rest_ensure_response(array(
            'original_amount' => $amount,
            'original_currency' => $from,
            'converted_amount' => $converted_amount,
            'converted_currency' => $to,
            'exchange_rate' => $converted_amount / $amount,
            'timestamp' => time(),
        ));
    }
    
    /**
     * Handle webhook callbacks
     */
    public function handle_webhook($request) {
        $gateway_id = $request->get_param('gateway_id');
        
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        
        if (!isset($gateways[$gateway_id])) {
            return new WP_Error('gateway_not_found', 'Payment gateway not found', array('status' => 404));
        }
        
        $gateway = $gateways[$gateway_id];
        
        if (!($gateway instanceof EPG_Gateway_Base)) {
            return new WP_Error('invalid_gateway', 'Not an EPG gateway', array('status' => 400));
        }
        
        // Let the gateway handle the webhook
        $result = $gateway->handle_webhook();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array('success' => true));
    }
    
    /**
     * Check basic permissions
     */
    public function check_permissions($request) {
        return current_user_can('read');
    }
    
    /**
     * Check payment permissions
     */
    public function check_payment_permissions($request) {
        return current_user_can('edit_shop_orders');
    }
    
    /**
     * Check admin permissions
     */
    public function check_admin_permissions($request) {
        return current_user_can('manage_woocommerce');
    }
    
    /**
     * Check invoice permissions
     */
    public function check_invoice_permissions($request) {
        $invoice_id = $request->get_param('invoice_id');
        
        if (current_user_can('manage_woocommerce')) {
            return true;
        }
        
        // Check if user owns the invoice
        global $wpdb;
        $invoices_table = $wpdb->prefix . 'epg_invoices';
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT customer_id FROM {$invoices_table} WHERE id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            return false;
        }
        
        return $invoice->customer_id == get_current_user_id();
    }
    
    /**
     * Get last transaction for a gateway
     */
    private function get_last_transaction($gateway_id) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'epg_payment_analytics';
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$analytics_table} 
             WHERE gateway = %s 
             ORDER BY created_at DESC 
             LIMIT 1",
            $gateway_id
        ));
        
        if (!$transaction) {
            return null;
        }
        
        return array(
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'amount' => floatval($transaction->amount),
            'currency' => $transaction->currency,
            'created_at' => $transaction->created_at,
        );
    }
}

// Initialize REST API
new EPG_REST_API();
