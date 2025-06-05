<?php
/**
 * Partner Integration Class
 * 
 * Handles third-party partner discount integration and API management
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Partner_Integration {
    
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
        add_action('wp_ajax_evr_get_partner_discounts', array($this, 'ajax_get_partner_discounts'));
        add_action('wp_ajax_evr_redeem_partner_discount', array($this, 'ajax_redeem_partner_discount'));
        
        // Cron hooks
        add_action('evr_sync_partner_data', array($this, 'sync_partner_data'));
        
        // Schedule partner data sync
        if (!wp_next_scheduled('evr_sync_partner_data')) {
            wp_schedule_event(time(), 'twicedaily', 'evr_sync_partner_data');
        }
    }
    
    /**
     * Get active partner discounts
     */
    public function get_partner_discounts($user_id = null) {
        return $this->db_manager->get_active_partner_discounts($user_id);
    }
    
    /**
     * Register new partner
     */
    public function register_partner($partner_data) {
        global $wpdb;
        
        $required_fields = array('partner_name', 'contact_email', 'discount_type');
        foreach ($required_fields as $field) {
            if (empty($partner_data[$field])) {
                return new WP_Error('missing_field', sprintf('Field %s is required', $field));
            }
        }
        
        $defaults = array(
            'status' => 'pending',
            'api_enabled' => false,
            'auto_sync' => false,
            'commission_rate' => 0,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($partner_data, $defaults);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'partners',
            array(
                'partner_name' => $data['partner_name'],
                'partner_slug' => sanitize_title($data['partner_name']),
                'description' => $data['description'] ?? '',
                'website_url' => $data['website_url'] ?? '',
                'contact_email' => $data['contact_email'],
                'phone_number' => $data['phone_number'] ?? '',
                'address' => $data['address'] ?? '',
                'partner_type' => $data['partner_type'] ?? 'retailer',
                'category' => $data['category'] ?? '',
                'status' => $data['status'],
                'api_enabled' => $data['api_enabled'],
                'api_key' => $this->generate_api_key(),
                'commission_rate' => $data['commission_rate'],
                'created_at' => $data['created_at']
            )
        );
        
        if ($result) {
            $partner_id = $wpdb->insert_id;
            
            // Create initial discount if provided
            if (!empty($data['initial_discount'])) {
                $this->create_partner_discount($partner_id, $data['initial_discount']);
            }
            
            do_action('evr_partner_registered', $partner_id, $data);
            
            return $partner_id;
        }
        
        return new WP_Error('creation_failed', 'Failed to register partner');
    }
    
    /**
     * Create partner discount
     */
    public function create_partner_discount($partner_id, $discount_data) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'discount_type' => 'percentage',
            'min_user_level' => 1,
            'max_redemptions' => null,
            'valid_days' => 30
        );
        
        $data = wp_parse_args($discount_data, $defaults);
        
        $valid_until = date('Y-m-d H:i:s', strtotime('+' . $data['valid_days'] . ' days'));
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'partner_discounts',
            array(
                'partner_id' => $partner_id,
                'discount_name' => $data['discount_name'],
                'description' => $data['description'] ?? '',
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'min_user_level' => $data['min_user_level'],
                'min_eco_score' => $data['min_eco_score'] ?? 0,
                'max_redemptions' => $data['max_redemptions'],
                'terms_conditions' => $data['terms_conditions'] ?? '',
                'redemption_url' => $data['redemption_url'] ?? '',
                'promo_code' => $data['promo_code'] ?? '',
                'valid_from' => current_time('mysql'),
                'valid_until' => $valid_until,
                'status' => $data['status']
            )
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Redeem partner discount
     */
    public function redeem_partner_discount($user_id, $discount_id) {
        global $wpdb;
        
        // Get discount details
        $discount = $wpdb->get_row($wpdb->prepare("
            SELECT pd.*, p.partner_name, p.redemption_url
            FROM {$wpdb->prefix}partner_discounts pd
            LEFT JOIN {$wpdb->prefix}partners p ON pd.partner_id = p.partner_id
            WHERE pd.discount_id = %d AND pd.status = 'active'
        ", $discount_id));
        
        if (!$discount) {
            return array('success' => false, 'message' => 'Discount not found or inactive');
        }
        
        // Check eligibility
        $eligibility = $this->check_discount_eligibility($user_id, $discount);
        if (!$eligibility['eligible']) {
            return array('success' => false, 'message' => $eligibility['message']);
        }
        
        // Check redemption limits
        if ($discount->max_redemptions) {
            $current_redemptions = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}partner_redemptions
                WHERE discount_id = %d
            ", $discount_id));
            
            if ($current_redemptions >= $discount->max_redemptions) {
                return array('success' => false, 'message' => 'Discount redemption limit reached');
            }
        }
        
        // Record redemption
        $redemption_id = $wpdb->insert(
            $wpdb->prefix . 'partner_redemptions',
            array(
                'user_id' => $user_id,
                'discount_id' => $discount_id,
                'partner_id' => $discount->partner_id,
                'redemption_code' => $this->generate_redemption_code(),
                'discount_amount' => $discount->discount_value,
                'status' => 'pending',
                'redeemed_at' => current_time('mysql')
            )
        );
        
        if ($redemption_id) {
            $redemption_code = $wpdb->get_var($wpdb->prepare("
                SELECT redemption_code FROM {$wpdb->prefix}partner_redemptions
                WHERE redemption_id = %d
            ", $redemption_id));
            
            // Deduct points if required
            if ($discount->points_required > 0) {
                $current_points = get_user_meta($user_id, 'green_points', true) ?: 0;
                update_user_meta($user_id, 'green_points', $current_points - $discount->points_required);
                
                // Record transaction
                $this->db_manager->add_reward_transaction(
                    $user_id,
                    'redeemed',
                    -$discount->points_required,
                    sprintf('Partner discount redeemed: %s', $discount->discount_name),
                    $redemption_id
                );
            }
            
            do_action('evr_partner_discount_redeemed', $user_id, $discount_id, $redemption_id);
            
            return array(
                'success' => true,
                'message' => 'Discount redeemed successfully',
                'redemption_code' => $redemption_code,
                'redemption_url' => $this->build_redemption_url($discount, $redemption_code)
            );
        }
        
        return array('success' => false, 'message' => 'Failed to redeem discount');
    }
    
    /**
     * Check discount eligibility
     */
    private function check_discount_eligibility($user_id, $discount) {
        // Check user level
        $user_level = get_user_meta($user_id, 'user_level', true) ?: 1;
        if ($user_level < $discount->min_user_level) {
            return array(
                'eligible' => false,
                'message' => sprintf('Requires user level %d or higher', $discount->min_user_level)
            );
        }
        
        // Check eco score
        if ($discount->min_eco_score > 0) {
            $eco_score = get_user_meta($user_id, 'eco_score', true) ?: 0;
            if ($eco_score < $discount->min_eco_score) {
                return array(
                    'eligible' => false,
                    'message' => sprintf('Requires eco score of %d or higher', $discount->min_eco_score)
                );
            }
        }
        
        // Check points requirement
        if ($discount->points_required > 0) {
            $user_points = get_user_meta($user_id, 'green_points', true) ?: 0;
            if ($user_points < $discount->points_required) {
                return array(
                    'eligible' => false,
                    'message' => sprintf('Requires %d green points', $discount->points_required)
                );
            }
        }
        
        // Check if user already redeemed
        global $wpdb;
        $previous_redemption = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}partner_redemptions
            WHERE user_id = %d AND discount_id = %d
        ", $user_id, $discount->discount_id));
        
        if ($previous_redemption > 0 && !$discount->allow_multiple_redemptions) {
            return array('eligible' => false, 'message' => 'You have already redeemed this discount');
        }
        
        return array('eligible' => true);
    }
    
    /**
     * Build redemption URL
     */
    private function build_redemption_url($discount, $redemption_code) {
        if (empty($discount->redemption_url)) {
            return '';
        }
        
        $url = $discount->redemption_url;
        $url = str_replace('{code}', $redemption_code, $url);
        $url = str_replace('{promo}', $discount->promo_code, $url);
        
        return $url;
    }
    
    /**
     * Generate API key for partner
     */
    private function generate_api_key() {
        return 'evr_' . wp_generate_password(32, false);
    }
    
    /**
     * Generate redemption code
     */
    private function generate_redemption_code() {
        return 'RED' . strtoupper(wp_generate_password(8, false));
    }
    
    /**
     * Sync partner data
     */
    public function sync_partner_data() {
        global $wpdb;
        
        // Get partners with API enabled
        $partners = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}partners
            WHERE api_enabled = 1 AND auto_sync = 1 AND status = 'active'
        ");
        
        foreach ($partners as $partner) {
            $this->sync_single_partner($partner);
        }
    }
    
    /**
     * Sync single partner data
     */
    private function sync_single_partner($partner) {
        if (empty($partner->api_endpoint)) {
            return;
        }
        
        $response = wp_remote_get($partner->api_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $partner->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('EVR Partner Sync Error for ' . $partner->partner_name . ': ' . $response->get_error_message());
            return;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data && isset($data['discounts'])) {
            $this->update_partner_discounts($partner->partner_id, $data['discounts']);
        }
        
        // Update last sync time
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'partners',
            array('last_sync' => current_time('mysql')),
            array('partner_id' => $partner->partner_id)
        );
    }
    
    /**
     * Update partner discounts from API
     */
    private function update_partner_discounts($partner_id, $discounts_data) {
        global $wpdb;
        
        foreach ($discounts_data as $discount_data) {
            // Check if discount exists
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT discount_id FROM {$wpdb->prefix}partner_discounts
                WHERE partner_id = %d AND external_id = %s
            ", $partner_id, $discount_data['id']));
            
            if ($existing) {
                // Update existing discount
                $wpdb->update(
                    $wpdb->prefix . 'partner_discounts',
                    array(
                        'discount_name' => $discount_data['name'],
                        'description' => $discount_data['description'],
                        'discount_value' => $discount_data['value'],
                        'valid_until' => $discount_data['expires_at'],
                        'status' => $discount_data['status']
                    ),
                    array('discount_id' => $existing)
                );
            } else {
                // Create new discount
                $wpdb->insert(
                    $wpdb->prefix . 'partner_discounts',
                    array(
                        'partner_id' => $partner_id,
                        'external_id' => $discount_data['id'],
                        'discount_name' => $discount_data['name'],
                        'description' => $discount_data['description'],
                        'discount_type' => $discount_data['type'],
                        'discount_value' => $discount_data['value'],
                        'valid_from' => current_time('mysql'),
                        'valid_until' => $discount_data['expires_at'],
                        'status' => $discount_data['status']
                    )
                );
            }
        }
    }
    
    /**
     * Get partner performance stats
     */
    public function get_partner_stats($partner_id = null) {
        global $wpdb;
        
        $where = $partner_id ? $wpdb->prepare("WHERE p.partner_id = %d", $partner_id) : "";
        
        $sql = "
            SELECT 
                p.partner_name,
                COUNT(DISTINCT pd.discount_id) as total_discounts,
                COUNT(pr.redemption_id) as total_redemptions,
                SUM(pr.discount_amount) as total_discount_value,
                COUNT(DISTINCT pr.user_id) as unique_users
            FROM {$wpdb->prefix}partners p
            LEFT JOIN {$wpdb->prefix}partner_discounts pd ON p.partner_id = pd.partner_id
            LEFT JOIN {$wpdb->prefix}partner_redemptions pr ON pd.discount_id = pr.discount_id
            {$where}
            GROUP BY p.partner_id
        ";
        
        return $partner_id ? $wpdb->get_row($sql) : $wpdb->get_results($sql);
    }
    
    /**
     * AJAX: Get partner discounts
     */
    public function ajax_get_partner_discounts() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        $discounts = $this->get_partner_discounts($user_id);
        
        if ($category) {
            $discounts = array_filter($discounts, function($discount) use ($category) {
                return $discount->category === $category;
            });
        }
        
        wp_send_json_success($discounts);
    }
    
    /**
     * AJAX: Redeem partner discount
     */
    public function ajax_redeem_partner_discount() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $discount_id = intval($_POST['discount_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$discount_id || !$user_id) {
            wp_send_json_error('Invalid discount or user');
        }
        
        $result = $this->redeem_partner_discount($user_id, $discount_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Get user redemption history
     */
    public function get_user_redemptions($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT pr.*, pd.discount_name, p.partner_name
            FROM {$wpdb->prefix}partner_redemptions pr
            LEFT JOIN {$wpdb->prefix}partner_discounts pd ON pr.discount_id = pd.discount_id
            LEFT JOIN {$wpdb->prefix}partners p ON pr.partner_id = p.partner_id
            WHERE pr.user_id = %d
            ORDER BY pr.redeemed_at DESC
        ", $user_id));
    }
    
    /**
     * Process partner webhook
     */
    public function process_webhook($partner_id, $webhook_data) {
        global $wpdb;
        
        // Verify partner
        $partner = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}partners WHERE partner_id = %d
        ", $partner_id));
        
        if (!$partner) {
            return false;
        }
        
        // Process different webhook types
        switch ($webhook_data['event']) {
            case 'discount.created':
                $this->create_partner_discount($partner_id, $webhook_data['data']);
                break;
                
            case 'discount.updated':
                $this->update_partner_discount($webhook_data['data']);
                break;
                
            case 'redemption.confirmed':
                $this->confirm_redemption($webhook_data['data']);
                break;
        }
        
        return true;
    }
    
    /**
     * Confirm redemption
     */
    private function confirm_redemption($redemption_data) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'partner_redemptions',
            array('status' => 'confirmed'),
            array('redemption_code' => $redemption_data['code'])
        );
    }
}
