<?php
/**
 * Voucher Manager Class
 * 
 * Handles voucher generation, validation, and management
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Voucher_Manager {
    
    private static $instance = null;
    private $db_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db_manager = EVR_Database_Manager::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_evr_validate_voucher', array($this, 'ajax_validate_voucher'));
        add_action('wp_ajax_nopriv_evr_validate_voucher', array($this, 'ajax_validate_voucher'));
        add_action('wp_ajax_evr_apply_voucher', array($this, 'ajax_apply_voucher'));
        add_action('wp_ajax_nopriv_evr_apply_voucher', array($this, 'ajax_apply_voucher'));
        
        // WooCommerce hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_cart_calculate_fees', array($this, 'apply_auto_vouchers'));
            add_filter('woocommerce_coupon_get_amount', array($this, 'calculate_voucher_discount'), 10, 2);
        }
    }
    
    /**
     * Generate unique voucher code
     */
    public function generate_voucher_code($length = 8, $prefix = '') {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = $prefix;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Ensure uniqueness
        if ($this->db_manager->get_voucher_by_code($code)) {
            return $this->generate_voucher_code($length, $prefix);
        }
        
        return $code;
    }
    
    /**
     * Create new voucher
     */
    public function create_voucher($data) {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array('voucher_name', 'discount_type', 'discount_value', 'valid_from', 'valid_until');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf('Field %s is required', $field));
            }
        }
        
        // Generate voucher code if not provided
        if (empty($data['voucher_code'])) {
            $data['voucher_code'] = $this->generate_voucher_code();
        }
        
        // Set defaults
        $defaults = array(
            'voucher_status' => 'active',
            'created_by' => get_current_user_id(),
            'min_order_amount' => 0,
            'usage_limit_per_user' => 1,
            'auto_apply' => false,
            'eco_score_requirement' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Insert voucher
        $result = $wpdb->insert(
            $wpdb->prefix . 'vouchers',
            array(
                'campaign_id' => $data['campaign_id'] ?? null,
                'voucher_code' => $data['voucher_code'],
                'voucher_name' => $data['voucher_name'],
                'description' => $data['description'] ?? '',
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'min_order_amount' => $data['min_order_amount'],
                'total_usage_limit' => $data['total_usage_limit'] ?? null,
                'usage_limit_per_user' => $data['usage_limit_per_user'],
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'],
                'auto_apply' => $data['auto_apply'],
                'eco_score_requirement' => $data['eco_score_requirement'],
                'voucher_status' => $data['voucher_status'],
                'created_by' => $data['created_by']
            )
        );
        
        if ($result) {
            $voucher_id = $wpdb->insert_id;
            
            // Trigger action for voucher creation
            do_action('evr_voucher_created', $voucher_id, $data);
            
            return $voucher_id;
        }
        
        return new WP_Error('creation_failed', 'Failed to create voucher');
    }
    
    /**
     * Validate voucher code
     */
    public function validate_voucher($code, $user_id = null, $cart_total = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Get voucher by code
        $voucher = $this->db_manager->get_voucher_by_code($code);
        
        if (!$voucher) {
            return array(
                'valid' => false,
                'message' => 'Voucher code not found or expired'
            );
        }
        
        // Check eligibility
        $eligibility = $this->db_manager->check_voucher_eligibility($voucher->voucher_id, $user_id, $cart_total);
        
        if (!$eligibility['eligible']) {
            return array(
                'valid' => false,
                'message' => $eligibility['message']
            );
        }
        
        // Calculate discount
        $discount = $this->calculate_discount($voucher, $cart_total);
        
        return array(
            'valid' => true,
            'voucher' => $voucher,
            'discount_amount' => $discount,
            'message' => sprintf('Voucher valid! You will save %s', wc_price($discount))
        );
    }
    
    /**
     * Calculate discount amount
     */
    public function calculate_discount($voucher, $cart_total) {
        $discount = 0;
        
        switch ($voucher->discount_type) {
            case 'percentage':
                $discount = ($cart_total * $voucher->discount_value) / 100;
                if ($voucher->max_discount_amount && $discount > $voucher->max_discount_amount) {
                    $discount = $voucher->max_discount_amount;
                }
                break;
                
            case 'fixed_amount':
                $discount = min($voucher->discount_value, $cart_total);
                break;
                
            case 'free_shipping':
                // This would be handled by WooCommerce shipping calculation
                $discount = 0;
                break;
                
            case 'green_points_multiplier':
                // This affects points earning, not direct discount
                $discount = 0;
                break;
        }
        
        return $discount;
    }
    
    /**
     * Apply voucher to order
     */
    public function apply_voucher($voucher_code, $user_id, $order_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        
        // Validate voucher
        $cart_total = 0;
        if (class_exists('WooCommerce') && WC()->cart) {
            $cart_total = WC()->cart->get_subtotal();
        }
        
        $validation = $this->validate_voucher($voucher_code, $user_id, $cart_total);
        
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }
        
        $voucher = $validation['voucher'];
        $discount = $validation['discount_amount'];
        
        // Record usage
        if ($order_id) {
            $this->db_manager->apply_voucher_usage(
                $voucher->voucher_id,
                $user_id,
                $order_id,
                $discount,
                $cart_total
            );
        }
        
        // Add WooCommerce coupon if needed
        if (class_exists('WooCommerce') && WC()->cart) {
            $this->add_wc_coupon($voucher, $discount);
        }
        
        return array(
            'success' => true,
            'discount' => $discount,
            'message' => sprintf('Voucher applied successfully! Discount: %s', wc_price($discount))
        );
    }
    
    /**
     * Add WooCommerce coupon
     */
    private function add_wc_coupon($voucher, $discount) {
        // Create temporary WooCommerce coupon
        $coupon_data = array(
            'discount_type' => $voucher->discount_type === 'percentage' ? 'percent' : 'fixed_cart',
            'amount' => $voucher->discount_type === 'percentage' ? $voucher->discount_value : $discount,
            'individual_use' => false,
            'usage_limit' => 1,
            'expiry_date' => $voucher->valid_until,
            'minimum_amount' => $voucher->min_order_amount
        );
        
        // Create coupon post
        $coupon_id = wp_insert_post(array(
            'post_title' => $voucher->voucher_code,
            'post_content' => $voucher->description,
            'post_status' => 'publish',
            'post_type' => 'shop_coupon'
        ));
        
        if ($coupon_id) {
            foreach ($coupon_data as $key => $value) {
                update_post_meta($coupon_id, $key, $value);
            }
            
            // Apply coupon to cart
            if (!WC()->cart->has_discount($voucher->voucher_code)) {
                WC()->cart->apply_coupon($voucher->voucher_code);
            }
        }
    }
    
    /**
     * Get auto-applicable vouchers
     */
    public function get_auto_vouchers($user_id, $cart_total = 0) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT v.* FROM {$wpdb->prefix}vouchers v
            WHERE v.auto_apply = 1
            AND v.voucher_status = 'active'
            AND v.valid_from <= NOW()
            AND v.valid_until >= NOW()
            AND v.min_order_amount <= %f
            AND (v.total_usage_limit IS NULL OR v.current_usage < v.total_usage_limit)
            ORDER BY v.auto_apply_priority ASC, v.discount_value DESC
        ", $cart_total);
        
        $vouchers = $wpdb->get_results($sql);
        $applicable_vouchers = array();
        
        foreach ($vouchers as $voucher) {
            $eligibility = $this->db_manager->check_voucher_eligibility($voucher->voucher_id, $user_id, $cart_total);
            if ($eligibility['eligible']) {
                $applicable_vouchers[] = $voucher;
            }
        }
        
        return $applicable_vouchers;
    }
    
    /**
     * Apply auto vouchers to WooCommerce cart
     */
    public function apply_auto_vouchers() {
        if (!is_user_logged_in() || !WC()->cart) {
            return;
        }
        
        $user_id = get_current_user_id();
        $cart_total = WC()->cart->get_subtotal();
        
        $auto_vouchers = $this->get_auto_vouchers($user_id, $cart_total);
        
        foreach ($auto_vouchers as $voucher) {
            if (!WC()->cart->has_discount($voucher->voucher_code)) {
                $this->add_wc_coupon($voucher, $this->calculate_discount($voucher, $cart_total));
            }
        }
    }
    
    /**
     * AJAX: Validate voucher
     */
    public function ajax_validate_voucher() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $cart_total = floatval($_POST['cart_total'] ?? 0);
        
        if (empty($code)) {
            wp_send_json_error('Voucher code is required');
        }
        
        $result = $this->validate_voucher($code, get_current_user_id(), $cart_total);
        
        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Apply voucher
     */
    public function ajax_apply_voucher() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error('Voucher code is required');
        }
        
        $result = $this->apply_voucher($code, get_current_user_id());
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Get voucher usage statistics
     */
    public function get_voucher_stats($voucher_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT 
                COUNT(*) as total_uses,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(discount_amount) as total_discount,
                AVG(discount_amount) as avg_discount,
                MAX(used_at) as last_used
            FROM {$wpdb->prefix}voucher_usage
            WHERE voucher_id = %d AND usage_type = 'used'
        ", $voucher_id);
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Expire voucher
     */
    public function expire_voucher($voucher_id) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'vouchers',
            array('voucher_status' => 'expired'),
            array('voucher_id' => $voucher_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Clone voucher
     */
    public function clone_voucher($voucher_id, $new_code = null) {
        global $wpdb;
        
        $original = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vouchers WHERE voucher_id = %d
        ", $voucher_id), ARRAY_A);
        
        if (!$original) {
            return false;
        }
        
        // Remove ID and modify for new voucher
        unset($original['voucher_id']);
        $original['voucher_code'] = $new_code ?: $this->generate_voucher_code();
        $original['current_usage'] = 0;
        $original['created_at'] = current_time('mysql');
        $original['voucher_status'] = 'draft';
        
        $result = $wpdb->insert($wpdb->prefix . 'vouchers', $original);
        
        return $result ? $wpdb->insert_id : false;
    }
}
