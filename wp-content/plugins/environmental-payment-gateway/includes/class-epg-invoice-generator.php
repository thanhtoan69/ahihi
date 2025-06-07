<?php
/**
 * Invoice Generator for Environmental Platform
 *
 * @package EnvironmentalPaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include TCPDF if available, otherwise use a basic HTML to PDF approach
if (!class_exists('TCPDF')) {
    // Define a simple PDF interface
    interface EPG_PDF_Interface {
        public function generate_pdf($html, $filename);
    }
    
    // Basic HTML to PDF converter (fallback)
    class EPG_Basic_PDF implements EPG_PDF_Interface {
        public function generate_pdf($html, $filename) {
            // This would use a library like DomPDF or mPDF in production
            // For now, we'll create a simple HTML file
            return file_put_contents($filename . '.html', $html);
        }
    }
}

/**
 * Invoice Generator Class
 */
class EPG_Invoice_Generator {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Upload directory
     */
    private $upload_dir;
    
    /**
     * PDF generator
     */
    private $pdf_generator;
    
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
        $this->table_name = $wpdb->prefix . 'epg_invoices';
        
        // Set upload directory
        $upload = wp_upload_dir();
        $this->upload_dir = $upload['basedir'] . '/epg-invoices/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // Add .htaccess for security
            $htaccess = $this->upload_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
            }
        }
        
        // Initialize PDF generator
        $this->init_pdf_generator();
        
        // Hook into order completion
        add_action('woocommerce_order_status_completed', array($this, 'generate_invoice_on_completion'));
        add_action('woocommerce_payment_complete', array($this, 'generate_invoice_on_payment'));
        
        // Add admin actions
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_invoice_link'));
        add_action('wp_ajax_epg_download_invoice', array($this, 'download_invoice'));
        add_action('wp_ajax_epg_regenerate_invoice', array($this, 'regenerate_invoice'));
        
        // Add customer actions
        add_action('woocommerce_view_order', array($this, 'display_customer_invoice_link'));
        add_action('wp_ajax_nopriv_epg_download_customer_invoice', array($this, 'download_customer_invoice'));
        add_action('wp_ajax_epg_download_customer_invoice', array($this, 'download_customer_invoice'));
        
        // Email attachments
        add_action('woocommerce_email_before_order_table', array($this, 'maybe_attach_invoice_to_email'), 10, 4);
    }
    
    /**
     * Initialize PDF generator
     */
    private function init_pdf_generator() {
        if (class_exists('TCPDF')) {
            $this->pdf_generator = new EPG_TCPDF_Generator();
        } else {
            $this->pdf_generator = new EPG_Basic_PDF();
        }
    }
    
    /**
     * Generate invoice on order completion
     */
    public function generate_invoice_on_completion($order_id) {
        $this->generate_invoice($order_id, 'completed');
    }
    
    /**
     * Generate invoice on payment complete
     */
    public function generate_invoice_on_payment($order_id) {
        $this->generate_invoice($order_id, 'paid');
    }
    
    /**
     * Generate invoice
     */
    public function generate_invoice($order_id, $trigger = 'manual') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Order not found.', 'environmental-payment-gateway'));
        }
        
        // Check if invoice already exists
        $existing_invoice = $this->get_invoice_by_order($order_id);
        if ($existing_invoice && $trigger !== 'manual') {
            return $existing_invoice;
        }
        
        try {
            // Generate invoice number
            $invoice_number = $this->generate_invoice_number($order);
            
            // Generate invoice HTML
            $invoice_html = $this->generate_invoice_html($order, $invoice_number);
            
            // Create PDF
            $filename = $this->upload_dir . 'invoice-' . $invoice_number . '.pdf';
            $pdf_created = $this->pdf_generator->generate_pdf($invoice_html, $filename);
            
            if (!$pdf_created) {
                throw new Exception(__('Failed to create PDF invoice.', 'environmental-payment-gateway'));
            }
            
            // Save invoice record
            $invoice_data = array(
                'order_id' => $order_id,
                'invoice_number' => $invoice_number,
                'file_path' => $filename,
                'generated_date' => current_time('mysql'),
                'status' => 'generated',
                'trigger_event' => $trigger
            );
            
            global $wpdb;
            
            if ($existing_invoice) {
                // Update existing invoice
                $wpdb->update(
                    $this->table_name,
                    $invoice_data,
                    array('order_id' => $order_id)
                );
            } else {
                // Insert new invoice
                $wpdb->insert($this->table_name, $invoice_data);
            }
            
            // Add order note
            $order->add_order_note(sprintf(
                __('Invoice generated: %s', 'environmental-payment-gateway'),
                $invoice_number
            ));
            
            return $invoice_data;
            
        } catch (Exception $e) {
            error_log('[EPG Invoice] Error generating invoice: ' . $e->getMessage());
            return new WP_Error('invoice_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Generate invoice number
     */
    private function generate_invoice_number($order) {
        $prefix = apply_filters('epg_invoice_number_prefix', 'EPG');
        $year = date('Y');
        $month = date('m');
        
        // Get next sequential number for this month
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) + 1 
            FROM {$this->table_name} 
            WHERE generated_date >= %s
        ", $year . '-' . $month . '-01'));
        
        $invoice_number = sprintf('%s-%s%s-%04d', $prefix, $year, $month, $count);
        
        return apply_filters('epg_invoice_number', $invoice_number, $order);
    }
    
    /**
     * Generate invoice HTML
     */
    private function generate_invoice_html($order, $invoice_number) {
        ob_start();
        
        // Get company information
        $company_name = get_bloginfo('name');
        $company_address = get_option('woocommerce_store_address');
        $company_city = get_option('woocommerce_store_city');
        $company_country = get_option('woocommerce_default_country');
        $company_email = get_option('admin_email');
        
        // Get order information
        $order_id = $order->get_id();
        $order_date = $order->get_date_created();
        $customer_name = $order->get_formatted_billing_full_name();
        $customer_address = $order->get_formatted_billing_address();
        $customer_email = $order->get_billing_email();
        
        // Get payment information
        $payment_method = $order->get_payment_method_title();
        $transaction_id = $order->get_transaction_id();
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo sprintf(__('Invoice %s', 'environmental-payment-gateway'), $invoice_number); ?></title>
            <style>
                body {
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    border-bottom: 2px solid #2c5aa0;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-info {
                    float: left;
                    width: 50%;
                }
                .invoice-info {
                    float: right;
                    width: 45%;
                    text-align: right;
                }
                .company-name {
                    font-size: 24px;
                    font-weight: bold;
                    color: #2c5aa0;
                    margin-bottom: 10px;
                }
                .invoice-title {
                    font-size: 28px;
                    font-weight: bold;
                    color: #2c5aa0;
                    margin-bottom: 10px;
                }
                .invoice-number {
                    font-size: 16px;
                    color: #666;
                }
                .clearfix {
                    clear: both;
                }
                .billing-info {
                    margin: 30px 0;
                }
                .billing-section {
                    float: left;
                    width: 48%;
                    margin-right: 4%;
                }
                .section-title {
                    font-weight: bold;
                    font-size: 14px;
                    color: #2c5aa0;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .items-table th,
                .items-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                .items-table th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                    color: #2c5aa0;
                }
                .items-table .amount {
                    text-align: right;
                }
                .totals {
                    float: right;
                    width: 300px;
                    margin-top: 20px;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #eee;
                }
                .total-row.final {
                    font-weight: bold;
                    font-size: 16px;
                    border-top: 2px solid #2c5aa0;
                    border-bottom: 2px solid #2c5aa0;
                    margin-top: 10px;
                    color: #2c5aa0;
                }
                .payment-info {
                    margin-top: 40px;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #2c5aa0;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    color: #666;
                    font-size: 10px;
                }
                .environmental-message {
                    margin-top: 30px;
                    padding: 15px;
                    background-color: #e8f5e8;
                    border-left: 4px solid #28a745;
                    color: #155724;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-info">
                    <div class="company-name"><?php echo esc_html($company_name); ?></div>
                    <div><?php echo esc_html($company_address); ?></div>
                    <div><?php echo esc_html($company_city); ?></div>
                    <div><?php echo esc_html($company_country); ?></div>
                    <div><?php echo esc_html($company_email); ?></div>
                </div>
                <div class="invoice-info">
                    <div class="invoice-title"><?php _e('INVOICE', 'environmental-payment-gateway'); ?></div>
                    <div class="invoice-number"><?php echo esc_html($invoice_number); ?></div>
                    <div><?php echo $order_date->format('F j, Y'); ?></div>
                </div>
                <div class="clearfix"></div>
            </div>
            
            <div class="billing-info">
                <div class="billing-section">
                    <div class="section-title"><?php _e('Bill To:', 'environmental-payment-gateway'); ?></div>
                    <div><?php echo esc_html($customer_name); ?></div>
                    <div><?php echo nl2br(esc_html($customer_address)); ?></div>
                    <div><?php echo esc_html($customer_email); ?></div>
                </div>
                <div class="billing-section">
                    <div class="section-title"><?php _e('Order Details:', 'environmental-payment-gateway'); ?></div>
                    <div><strong><?php _e('Order ID:', 'environmental-payment-gateway'); ?></strong> #<?php echo $order_id; ?></div>
                    <div><strong><?php _e('Order Date:', 'environmental-payment-gateway'); ?></strong> <?php echo $order_date->format('F j, Y'); ?></div>
                    <div><strong><?php _e('Payment Method:', 'environmental-payment-gateway'); ?></strong> <?php echo esc_html($payment_method); ?></div>
                    <?php if ($transaction_id): ?>
                    <div><strong><?php _e('Transaction ID:', 'environmental-payment-gateway'); ?></strong> <?php echo esc_html($transaction_id); ?></div>
                    <?php endif; ?>
                </div>
                <div class="clearfix"></div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th><?php _e('Item', 'environmental-payment-gateway'); ?></th>
                        <th><?php _e('Quantity', 'environmental-payment-gateway'); ?></th>
                        <th class="amount"><?php _e('Price', 'environmental-payment-gateway'); ?></th>
                        <th class="amount"><?php _e('Total', 'environmental-payment-gateway'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order->get_items() as $item_id => $item): ?>
                    <tr>
                        <td>
                            <?php echo esc_html($item->get_name()); ?>
                            <?php if ($item->get_variation_id()): ?>
                            <br><small><?php echo wc_get_formatted_variation($item->get_product(), true); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($item->get_quantity()); ?></td>
                        <td class="amount"><?php echo wc_price($order->get_item_subtotal($item)); ?></td>
                        <td class="amount"><?php echo wc_price($order->get_line_total($item)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="totals">
                <div class="total-row">
                    <span><?php _e('Subtotal:', 'environmental-payment-gateway'); ?></span>
                    <span><?php echo wc_price($order->get_subtotal()); ?></span>
                </div>
                
                <?php if ($order->get_total_shipping() > 0): ?>
                <div class="total-row">
                    <span><?php _e('Shipping:', 'environmental-payment-gateway'); ?></span>
                    <span><?php echo wc_price($order->get_shipping_total()); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($order->get_total_tax() > 0): ?>
                <div class="total-row">
                    <span><?php _e('Tax:', 'environmental-payment-gateway'); ?></span>
                    <span><?php echo wc_price($order->get_total_tax()); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($order->get_total_discount() > 0): ?>
                <div class="total-row">
                    <span><?php _e('Discount:', 'environmental-payment-gateway'); ?></span>
                    <span>-<?php echo wc_price($order->get_total_discount()); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="total-row final">
                    <span><?php _e('Total:', 'environmental-payment-gateway'); ?></span>
                    <span><?php echo wc_price($order->get_total()); ?></span>
                </div>
            </div>
            <div class="clearfix"></div>
            
            <div class="payment-info">
                <div class="section-title"><?php _e('Payment Information', 'environmental-payment-gateway'); ?></div>
                <p><strong><?php _e('Payment Method:', 'environmental-payment-gateway'); ?></strong> <?php echo esc_html($payment_method); ?></p>
                <?php if ($transaction_id): ?>
                <p><strong><?php _e('Transaction ID:', 'environmental-payment-gateway'); ?></strong> <?php echo esc_html($transaction_id); ?></p>
                <?php endif; ?>
                <p><strong><?php _e('Payment Status:', 'environmental-payment-gateway'); ?></strong> <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></p>
            </div>
            
            <div class="environmental-message">
                <strong><?php _e('🌱 Environmental Impact Notice', 'environmental-payment-gateway'); ?></strong><br>
                <?php _e('Your purchase contributes to environmental protection initiatives. Thank you for supporting sustainable practices!', 'environmental-payment-gateway'); ?>
                <br><br>
                <?php if (class_exists('Environmental_Donation_System')): ?>
                <?php _e('A portion of this payment has been allocated to environmental projects through our donation system.', 'environmental-payment-gateway'); ?>
                <?php endif; ?>
            </div>
            
            <div class="footer">
                <p><?php echo esc_html($company_name); ?> - <?php _e('Generated on', 'environmental-payment-gateway'); ?> <?php echo date('F j, Y g:i A'); ?></p>
                <p><?php _e('This is an electronically generated invoice and does not require a signature.', 'environmental-payment-gateway'); ?></p>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get invoice by order ID
     */
    public function get_invoice_by_order($order_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table_name} WHERE order_id = %d
        ", $order_id), ARRAY_A);
    }
    
    /**
     * Display invoice link in admin
     */
    public function display_invoice_link($order) {
        $invoice = $this->get_invoice_by_order($order->get_id());
        
        echo '<div class="epg-invoice-actions" style="margin-top: 20px;">';
        echo '<h3>' . __('Invoice', 'environmental-payment-gateway') . '</h3>';
        
        if ($invoice) {
            echo '<p>';
            echo '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=epg_download_invoice&order_id=' . $order->get_id()), 'epg_download_invoice') . '" class="button button-primary">';
            echo __('Download Invoice', 'environmental-payment-gateway') . ' (' . $invoice['invoice_number'] . ')';
            echo '</a> ';
            
            echo '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=epg_regenerate_invoice&order_id=' . $order->get_id()), 'epg_regenerate_invoice') . '" class="button">';
            echo __('Regenerate Invoice', 'environmental-payment-gateway');
            echo '</a>';
            echo '</p>';
            
            echo '<p><small>' . sprintf(__('Generated: %s', 'environmental-payment-gateway'), $invoice['generated_date']) . '</small></p>';
        } else {
            echo '<p>';
            echo '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=epg_regenerate_invoice&order_id=' . $order->get_id()), 'epg_regenerate_invoice') . '" class="button button-primary">';
            echo __('Generate Invoice', 'environmental-payment-gateway');
            echo '</a>';
            echo '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Display invoice link for customers
     */
    public function display_customer_invoice_link($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || !in_array($order->get_status(), array('completed', 'processing'))) {
            return;
        }
        
        $invoice = $this->get_invoice_by_order($order_id);
        
        if ($invoice) {
            echo '<div class="woocommerce-customer-invoice" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6;">';
            echo '<h3>' . __('Invoice', 'environmental-payment-gateway') . '</h3>';
            echo '<p>';
            echo '<a href="' . wp_nonce_url(site_url('?epg_download_invoice=1&order_id=' . $order_id . '&key=' . $order->get_order_key()), 'epg_customer_invoice') . '" class="button alt">';
            echo __('Download Invoice', 'environmental-payment-gateway') . ' (' . $invoice['invoice_number'] . ')';
            echo '</a>';
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Download invoice (admin)
     */
    public function download_invoice() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'epg_download_invoice')) {
            wp_die(__('Security check failed.', 'environmental-payment-gateway'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied.', 'environmental-payment-gateway'));
        }
        
        $order_id = intval($_GET['order_id']);
        $this->serve_invoice_file($order_id);
    }
    
    /**
     * Regenerate invoice
     */
    public function regenerate_invoice() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'epg_regenerate_invoice')) {
            wp_die(__('Security check failed.', 'environmental-payment-gateway'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Access denied.', 'environmental-payment-gateway'));
        }
        
        $order_id = intval($_GET['order_id']);
        $result = $this->generate_invoice($order_id, 'manual');
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }
        
        // Redirect back to order
        wp_redirect(admin_url('post.php?post=' . $order_id . '&action=edit'));
        exit;
    }
    
    /**
     * Download customer invoice
     */
    public function download_customer_invoice() {
        if (!isset($_GET['epg_download_invoice']) || !isset($_GET['order_id']) || !isset($_GET['key'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'epg_customer_invoice')) {
            wp_die(__('Security check failed.', 'environmental-payment-gateway'));
        }
        
        $order_id = intval($_GET['order_id']);
        $order_key = sanitize_text_field($_GET['key']);
        
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die(__('Invalid order.', 'environmental-payment-gateway'));
        }
        
        $this->serve_invoice_file($order_id);
    }
    
    /**
     * Serve invoice file
     */
    private function serve_invoice_file($order_id) {
        $invoice = $this->get_invoice_by_order($order_id);
        
        if (!$invoice || !file_exists($invoice['file_path'])) {
            wp_die(__('Invoice not found.', 'environmental-payment-gateway'));
        }
        
        $filename = 'invoice-' . $invoice['invoice_number'] . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($invoice['file_path']));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($invoice['file_path']);
        exit;
    }
    
    /**
     * Maybe attach invoice to email
     */
    public function maybe_attach_invoice_to_email($order, $sent_to_admin, $plain_text, $email) {
        // Only attach to customer emails for completed orders
        if ($sent_to_admin || $plain_text || !in_array($email->id, array('customer_completed_order', 'customer_invoice'))) {
            return;
        }
        
        $invoice = $this->get_invoice_by_order($order->get_id());
        
        if (!$invoice || !file_exists($invoice['file_path'])) {
            return;
        }
        
        // Add attachment
        add_filter('woocommerce_email_attachments', function($attachments) use ($invoice) {
            $attachments[] = $invoice['file_path'];
            return $attachments;
        });
    }
    
    /**
     * Get invoice statistics
     */
    public function get_invoice_statistics($period = '30 days') {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                COUNT(CASE WHEN status = 'generated' THEN 1 END) as generated_invoices,
                COUNT(CASE WHEN trigger_event = 'completed' THEN 1 END) as completion_triggered,
                COUNT(CASE WHEN trigger_event = 'paid' THEN 1 END) as payment_triggered,
                COUNT(CASE WHEN trigger_event = 'manual' THEN 1 END) as manually_generated
            FROM {$this->table_name} 
            WHERE generated_date >= DATE_SUB(NOW(), INTERVAL %s)
        ", $period), ARRAY_A);
    }
}
