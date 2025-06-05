<?php
/**
 * Voucher Display Components Class
 * 
 * Handles display of vouchers, voucher widgets, and voucher-related UI components
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EVR_Voucher_Display {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
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
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Widget registration
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Shortcodes
        add_shortcode('evr_voucher_wallet', array($this, 'render_voucher_wallet'));
        add_shortcode('evr_voucher_card', array($this, 'render_voucher_card'));
        add_shortcode('evr_voucher_counter', array($this, 'render_voucher_counter'));
        add_shortcode('evr_voucher_qr', array($this, 'render_voucher_qr'));
        
        // AJAX handlers
        add_action('wp_ajax_evr_get_voucher_details', array($this, 'ajax_get_voucher_details'));
        add_action('wp_ajax_evr_apply_voucher_to_cart', array($this, 'ajax_apply_voucher_to_cart'));
        add_action('wp_ajax_evr_remove_voucher_from_cart', array($this, 'ajax_remove_voucher_from_cart'));
        
        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_cart_totals_after_order_total', array($this, 'display_available_vouchers'));
            add_action('woocommerce_checkout_after_order_review', array($this, 'display_checkout_vouchers'));
        }
        
        // Auto-insertion hooks
        add_filter('the_content', array($this, 'auto_insert_voucher_widgets'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register voucher post type for display
        $this->register_voucher_post_type();
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'evr-voucher-display',
            EVR_PLUGIN_URL . 'assets/css/voucher-display.css',
            array(),
            EVR_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'evr-voucher-display',
            EVR_PLUGIN_URL . 'assets/js/voucher-display.js',
            array('jquery'),
            EVR_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('evr-voucher-display', 'evr_voucher', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_voucher_nonce'),
            'messages' => array(
                'applied' => __('Voucher applied successfully!', 'env-voucher-rewards'),
                'removed' => __('Voucher removed successfully!', 'env-voucher-rewards'),
                'invalid' => __('Invalid voucher code', 'env-voucher-rewards'),
                'expired' => __('This voucher has expired', 'env-voucher-rewards'),
                'used' => __('This voucher has already been used', 'env-voucher-rewards'),
                'loading' => __('Processing...', 'env-voucher-rewards')
            )
        ));
    }
    
    /**
     * Register voucher post type for display purposes
     */
    private function register_voucher_post_type() {
        register_post_type('evr_voucher_display', array(
            'labels' => array(
                'name' => __('Voucher Templates', 'env-voucher-rewards'),
                'singular_name' => __('Voucher Template', 'env-voucher-rewards'),
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor', 'thumbnail')
        ));
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('EVR_Voucher_Widget');
        register_widget('EVR_Voucher_Counter_Widget');
    }
    
    /**
     * Render voucher wallet shortcode
     */
    public function render_voucher_wallet($atts) {
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . 
                   __('Please log in to view your voucher wallet.', 'env-voucher-rewards') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'status' => 'active',
            'limit' => 10,
            'layout' => 'grid', // grid, list, carousel
            'show_qr' => true,
            'show_expiry' => true,
            'show_stats' => true
        ), $atts);
        
        $user_id = get_current_user_id();
        $vouchers = $this->get_user_vouchers($user_id, $atts);
        $wallet_stats = $this->get_wallet_stats($user_id);
        
        ob_start();
        $this->render_wallet_template($vouchers, $wallet_stats, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render single voucher card shortcode
     */
    public function render_voucher_card($atts) {
        $atts = shortcode_atts(array(
            'voucher_id' => 0,
            'voucher_code' => '',
            'style' => 'default', // default, minimal, detailed
            'show_qr' => true,
            'show_actions' => true
        ), $atts);
        
        if ($atts['voucher_id']) {
            $voucher = $this->get_voucher_by_id($atts['voucher_id']);
        } elseif ($atts['voucher_code']) {
            $voucher = $this->get_voucher_by_code($atts['voucher_code']);
        } else {
            return '<div class="evr-error">Invalid voucher specified</div>';
        }
        
        if (!$voucher) {
            return '<div class="evr-error">Voucher not found</div>';
        }
        
        ob_start();
        $this->render_voucher_card_template($voucher, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render voucher counter shortcode
     */
    public function render_voucher_counter($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'type' => 'active', // active, used, expired, total
            'style' => 'badge', // badge, text, icon
            'show_label' => true
        ), $atts);
        
        $user_id = get_current_user_id();
        $count = $this->get_voucher_count($user_id, $atts['type']);
        
        ob_start();
        $this->render_counter_template($count, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render voucher QR code shortcode
     */
    public function render_voucher_qr($atts) {
        $atts = shortcode_atts(array(
            'voucher_code' => '',
            'size' => 200,
            'download' => false
        ), $atts);
        
        if (empty($atts['voucher_code'])) {
            return '<div class="evr-error">Voucher code required</div>';
        }
        
        $qr_generator = EVR_QR_Generator::get_instance();
        $qr_url = $qr_generator->generate_voucher_qr($atts['voucher_code'], $atts['size']);
        
        ob_start();
        ?>
        <div class="evr-voucher-qr">
            <img src="<?php echo esc_url($qr_url); ?>" alt="<?php echo esc_attr($atts['voucher_code']); ?>" />
            <?php if ($atts['download']): ?>
                <a href="<?php echo esc_url($qr_url); ?>" download="voucher-<?php echo $atts['voucher_code']; ?>.png" class="evr-qr-download">
                    <?php _e('Download QR Code', 'env-voucher-rewards'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get user vouchers
     */
    private function get_user_vouchers($user_id, $atts) {
        global $wpdb;
        
        $status_condition = '';
        if ($atts['status'] != 'all') {
            $status_condition = $wpdb->prepare(" AND voucher_status = %s", $atts['status']);
        }
        
        $limit_clause = '';
        if ($atts['limit'] > 0) {
            $limit_clause = $wpdb->prepare(" LIMIT %d", $atts['limit']);
        }
        
        $vouchers = $wpdb->get_results($wpdb->prepare("
            SELECT v.*, vc.campaign_name, vc.description as campaign_description
            FROM vouchers v
            LEFT JOIN voucher_campaigns vc ON v.campaign_id = vc.campaign_id
            WHERE v.user_id = %d
            {$status_condition}
            ORDER BY v.created_at DESC
            {$limit_clause}
        ", $user_id));
        
        // Add additional data to each voucher
        foreach ($vouchers as &$voucher) {
            $voucher->days_until_expiry = $this->get_days_until_expiry($voucher->expiry_date);
            $voucher->is_expiring_soon = $voucher->days_until_expiry <= 7 && $voucher->days_until_expiry > 0;
            $voucher->usage_locations = $this->get_voucher_usage_locations($voucher->voucher_id);
        }
        
        return $vouchers;
    }
    
    /**
     * Get wallet statistics
     */
    private function get_wallet_stats($user_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_vouchers,
                SUM(CASE WHEN voucher_status = 'active' THEN 1 ELSE 0 END) as active_vouchers,
                SUM(CASE WHEN voucher_status = 'used' THEN 1 ELSE 0 END) as used_vouchers,
                SUM(CASE WHEN voucher_status = 'expired' THEN 1 ELSE 0 END) as expired_vouchers,
                SUM(CASE WHEN voucher_status = 'active' THEN discount_amount ELSE 0 END) as total_value,
                SUM(CASE WHEN voucher_status = 'used' THEN discount_amount ELSE 0 END) as total_savings,
                COUNT(CASE WHEN voucher_status = 'active' AND expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 END) as expiring_soon
            FROM vouchers 
            WHERE user_id = %d
        ", $user_id));
        
        return $stats;
    }
    
    /**
     * Get voucher by ID
     */
    private function get_voucher_by_id($voucher_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT v.*, vc.campaign_name, vc.description as campaign_description
            FROM vouchers v
            LEFT JOIN voucher_campaigns vc ON v.campaign_id = vc.campaign_id
            WHERE v.voucher_id = %d
        ", $voucher_id));
    }
    
    /**
     * Get voucher by code
     */
    private function get_voucher_by_code($voucher_code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT v.*, vc.campaign_name, vc.description as campaign_description
            FROM vouchers v
            LEFT JOIN voucher_campaigns vc ON v.campaign_id = vc.campaign_id
            WHERE v.voucher_code = %s
        ", $voucher_code));
    }
    
    /**
     * Get voucher count
     */
    private function get_voucher_count($user_id, $type) {
        global $wpdb;
        
        $condition = '';
        switch ($type) {
            case 'active':
                $condition = " AND voucher_status = 'active'";
                break;
            case 'used':
                $condition = " AND voucher_status = 'used'";
                break;
            case 'expired':
                $condition = " AND voucher_status = 'expired'";
                break;
        }
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM vouchers 
            WHERE user_id = %d
            {$condition}
        ", $user_id));
    }
    
    /**
     * Get days until expiry
     */
    private function get_days_until_expiry($expiry_date) {
        $now = new DateTime();
        $expiry = new DateTime($expiry_date);
        $diff = $now->diff($expiry);
        
        if ($expiry < $now) {
            return -$diff->days; // Negative for expired
        }
        
        return $diff->days;
    }
    
    /**
     * Get voucher usage locations
     */
    private function get_voucher_usage_locations($voucher_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT partner_name, usage_location 
            FROM voucher_usage 
            WHERE voucher_id = %d
        ", $voucher_id));
    }
    
    /**
     * Render wallet template
     */
    private function render_wallet_template($vouchers, $stats, $atts) {
        ?>
        <div class="evr-voucher-wallet <?php echo $atts['layout']; ?>">
            <?php if ($atts['show_stats']): ?>
                <div class="evr-wallet-header">
                    <h3><?php _e('My Voucher Wallet', 'env-voucher-rewards'); ?></h3>
                    <div class="evr-wallet-stats">
                        <div class="evr-wallet-stat">
                            <span class="stat-number"><?php echo $stats->active_vouchers ?? 0; ?></span>
                            <span class="stat-label"><?php _e('Active', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="evr-wallet-stat">
                            <span class="stat-number">$<?php echo number_format($stats->total_value ?? 0, 2); ?></span>
                            <span class="stat-label"><?php _e('Total Value', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="evr-wallet-stat">
                            <span class="stat-number">$<?php echo number_format($stats->total_savings ?? 0, 2); ?></span>
                            <span class="stat-label"><?php _e('Total Saved', 'env-voucher-rewards'); ?></span>
                        </div>
                        <?php if ($stats->expiring_soon > 0): ?>
                            <div class="evr-wallet-stat warning">
                                <span class="stat-number"><?php echo $stats->expiring_soon; ?></span>
                                <span class="stat-label"><?php _e('Expiring Soon', 'env-voucher-rewards'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="evr-vouchers-container">
                <?php if (empty($vouchers)): ?>
                    <div class="evr-no-vouchers">
                        <p><?php _e('No vouchers found.', 'env-voucher-rewards'); ?></p>
                        <a href="<?php echo get_permalink(get_option('evr_rewards_page')); ?>" class="button">
                            <?php _e('Earn Your First Voucher', 'env-voucher-rewards'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($vouchers as $voucher): ?>
                        <?php $this->render_voucher_card_template($voucher, $atts); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['layout'] == 'carousel' && count($vouchers) > 3): ?>
                <div class="evr-carousel-controls">
                    <button class="evr-carousel-prev">&larr;</button>
                    <button class="evr-carousel-next">&rarr;</button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render voucher card template
     */
    private function render_voucher_card_template($voucher, $atts) {
        $status_class = 'status-' . $voucher->voucher_status;
        $expiry_class = $voucher->is_expiring_soon ? 'expiring-soon' : '';
        ?>
        <div class="evr-voucher-card <?php echo $status_class . ' ' . $expiry_class; ?>" data-voucher-id="<?php echo $voucher->voucher_id; ?>">
            <div class="voucher-header">
                <div class="voucher-brand">
                    <h4><?php echo esc_html($voucher->campaign_name ?? __('Environmental Voucher', 'env-voucher-rewards')); ?></h4>
                    <?php if ($voucher->voucher_status == 'active'): ?>
                        <span class="voucher-status active"><?php _e('Active', 'env-voucher-rewards'); ?></span>
                    <?php elseif ($voucher->voucher_status == 'used'): ?>
                        <span class="voucher-status used"><?php _e('Used', 'env-voucher-rewards'); ?></span>
                    <?php elseif ($voucher->voucher_status == 'expired'): ?>
                        <span class="voucher-status expired"><?php _e('Expired', 'env-voucher-rewards'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="voucher-body">
                <div class="voucher-value">
                    <?php if ($voucher->discount_type == 'percentage'): ?>
                        <span class="discount-amount"><?php echo $voucher->discount_amount; ?>%</span>
                        <span class="discount-label"><?php _e('OFF', 'env-voucher-rewards'); ?></span>
                    <?php else: ?>
                        <span class="discount-amount">$<?php echo number_format($voucher->discount_amount, 2); ?></span>
                        <span class="discount-label"><?php _e('OFF', 'env-voucher-rewards'); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="voucher-code">
                    <label><?php _e('Code:', 'env-voucher-rewards'); ?></label>
                    <span class="code-value"><?php echo $voucher->voucher_code; ?></span>
                    <button class="copy-code" data-code="<?php echo $voucher->voucher_code; ?>" title="<?php _e('Copy Code', 'env-voucher-rewards'); ?>">
                        📋
                    </button>
                </div>
                
                <?php if ($voucher->campaign_description): ?>
                    <div class="voucher-description">
                        <p><?php echo esc_html($voucher->campaign_description); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_expiry'] && $voucher->expiry_date): ?>
                    <div class="voucher-expiry">
                        <?php if ($voucher->days_until_expiry > 0): ?>
                            <span class="expiry-text">
                                <?php printf(__('Expires in %d days', 'env-voucher-rewards'), $voucher->days_until_expiry); ?>
                            </span>
                        <?php elseif ($voucher->days_until_expiry == 0): ?>
                            <span class="expiry-text expires-today">
                                <?php _e('Expires today!', 'env-voucher-rewards'); ?>
                            </span>
                        <?php else: ?>
                            <span class="expiry-text expired">
                                <?php _e('Expired', 'env-voucher-rewards'); ?>
                            </span>
                        <?php endif; ?>
                        <span class="expiry-date"><?php echo date('M j, Y', strtotime($voucher->expiry_date)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['show_actions']): ?>
                <div class="voucher-actions">
                    <?php if ($voucher->voucher_status == 'active'): ?>
                        <?php if (class_exists('WooCommerce')): ?>
                            <button class="evr-apply-voucher button" data-voucher-code="<?php echo $voucher->voucher_code; ?>">
                                <?php _e('Apply to Cart', 'env-voucher-rewards'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button class="evr-voucher-details button-secondary" data-voucher-id="<?php echo $voucher->voucher_id; ?>">
                            <?php _e('Details', 'env-voucher-rewards'); ?>
                        </button>
                        
                        <?php if ($atts['show_qr']): ?>
                            <button class="evr-show-qr button-secondary" data-voucher-code="<?php echo $voucher->voucher_code; ?>">
                                <?php _e('Show QR', 'env-voucher-rewards'); ?>
                            </button>
                        <?php endif; ?>
                    <?php elseif ($voucher->voucher_status == 'used' && !empty($voucher->usage_locations)): ?>
                        <div class="usage-info">
                            <small>
                                <?php printf(__('Used at %s', 'env-voucher-rewards'), 
                                           $voucher->usage_locations[0]->partner_name); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_qr'] && $voucher->voucher_status == 'active'): ?>
                <div class="voucher-qr" style="display: none;">
                    <?php echo $this->render_voucher_qr(array('voucher_code' => $voucher->voucher_code, 'size' => 150)); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render counter template
     */
    private function render_counter_template($count, $atts) {
        $labels = array(
            'active' => __('Active Vouchers', 'env-voucher-rewards'),
            'used' => __('Used Vouchers', 'env-voucher-rewards'),
            'expired' => __('Expired Vouchers', 'env-voucher-rewards'),
            'total' => __('Total Vouchers', 'env-voucher-rewards')
        );
        
        $icons = array(
            'active' => '🎫',
            'used' => '✅',
            'expired' => '⏰',
            'total' => '📊'
        );
        
        ?>
        <div class="evr-voucher-counter <?php echo $atts['style']; ?>">
            <?php if ($atts['style'] == 'badge'): ?>
                <span class="counter-badge <?php echo $atts['type']; ?>">
                    <?php if ($atts['show_label']): ?>
                        <span class="counter-label"><?php echo $labels[$atts['type']]; ?></span>
                    <?php endif; ?>
                    <span class="counter-number"><?php echo $count; ?></span>
                </span>
            <?php elseif ($atts['style'] == 'icon'): ?>
                <span class="counter-icon">
                    <span class="icon"><?php echo $icons[$atts['type']]; ?></span>
                    <span class="counter-number"><?php echo $count; ?></span>
                </span>
            <?php else: ?>
                <span class="counter-text">
                    <?php if ($atts['show_label']): ?>
                        <?php echo $labels[$atts['type']]; ?>: 
                    <?php endif; ?>
                    <strong><?php echo $count; ?></strong>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Display available vouchers in WooCommerce cart
     */
    public function display_available_vouchers() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $vouchers = $this->get_user_vouchers($user_id, array(
            'status' => 'active',
            'limit' => 5
        ));
        
        if (empty($vouchers)) {
            return;
        }
        
        ?>
        <tr class="evr-available-vouchers">
            <th><?php _e('Available Vouchers', 'env-voucher-rewards'); ?></th>
            <td>
                <div class="evr-cart-vouchers">
                    <?php foreach ($vouchers as $voucher): ?>
                        <div class="evr-cart-voucher">
                            <span class="voucher-info">
                                <?php echo $voucher->voucher_code; ?> - 
                                <?php if ($voucher->discount_type == 'percentage'): ?>
                                    <?php echo $voucher->discount_amount; ?>% OFF
                                <?php else: ?>
                                    $<?php echo $voucher->discount_amount; ?> OFF
                                <?php endif; ?>
                            </span>
                            <button class="evr-apply-voucher-cart" data-voucher-code="<?php echo $voucher->voucher_code; ?>">
                                <?php _e('Apply', 'env-voucher-rewards'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Display checkout vouchers
     */
    public function display_checkout_vouchers() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $applied_vouchers = WC()->session->get('evr_applied_vouchers', array());
        
        if (!empty($applied_vouchers)) {
            ?>
            <div class="evr-checkout-vouchers">
                <h4><?php _e('Applied Environmental Vouchers', 'env-voucher-rewards'); ?></h4>
                <?php foreach ($applied_vouchers as $voucher_code): ?>
                    <div class="evr-applied-voucher">
                        <span class="voucher-code"><?php echo $voucher_code; ?></span>
                        <button class="evr-remove-voucher" data-voucher-code="<?php echo $voucher_code; ?>">
                            <?php _e('Remove', 'env-voucher-rewards'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Auto-insert voucher widgets in content
     */
    public function auto_insert_voucher_widgets($content) {
        if (!is_single() || !is_user_logged_in()) {
            return $content;
        }
        
        $user_preferences = get_user_meta(get_current_user_id(), 'evr_auto_insert_vouchers', true);
        if (!$user_preferences) {
            return $content;
        }
        
        // Insert voucher counter after first paragraph
        if (strpos($content, '</p>') !== false) {
            $voucher_counter = $this->render_voucher_counter(array('type' => 'active', 'style' => 'badge'));
            $content = preg_replace('/(<\/p>)/', '$1' . $voucher_counter, $content, 1);
        }
        
        return $content;
    }
    
    /**
     * AJAX: Get voucher details
     */
    public function ajax_get_voucher_details() {
        check_ajax_referer('evr_voucher_nonce', 'nonce');
        
        $voucher_id = intval($_POST['voucher_id']);
        $voucher = $this->get_voucher_by_id($voucher_id);
        
        if (!$voucher) {
            wp_die(json_encode(array('success' => false, 'message' => 'Voucher not found')));
        }
        
        // Add usage history
        global $wpdb;
        $usage_history = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM voucher_usage 
            WHERE voucher_id = %d 
            ORDER BY used_at DESC
        ", $voucher_id));
        
        $voucher->usage_history = $usage_history;
        
        wp_die(json_encode(array('success' => true, 'voucher' => $voucher)));
    }
    
    /**
     * AJAX: Apply voucher to cart
     */
    public function ajax_apply_voucher_to_cart() {
        check_ajax_referer('evr_voucher_nonce', 'nonce');
        
        if (!class_exists('WooCommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'WooCommerce not active')));
        }
        
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        
        $result = $voucher_manager->apply_voucher_to_cart($voucher_code);
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Remove voucher from cart
     */
    public function ajax_remove_voucher_from_cart() {
        check_ajax_referer('evr_voucher_nonce', 'nonce');
        
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        
        $result = $voucher_manager->remove_voucher_from_cart($voucher_code);
        wp_die(json_encode($result));
    }
}

/**
 * Voucher Widget Class
 */
class EVR_Voucher_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'evr_voucher_widget',
            __('Environmental Vouchers', 'env-voucher-rewards'),
            array('description' => __('Display user vouchers in sidebar', 'env-voucher-rewards'))
        );
    }
    
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $display = EVR_Voucher_Display::get_instance();
        echo $display->render_voucher_wallet(array(
            'limit' => $instance['limit'] ?? 3,
            'layout' => 'list',
            'show_stats' => false
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Vouchers', 'env-voucher-rewards');
        $limit = !empty($instance['limit']) ? $instance['limit'] : 3;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'env-voucher-rewards'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of vouchers to show:', 'env-voucher-rewards'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($limit); ?>" size="3">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? absint($new_instance['limit']) : 3;
        return $instance;
    }
}

/**
 * Voucher Counter Widget Class
 */
class EVR_Voucher_Counter_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'evr_voucher_counter_widget',
            __('Voucher Counter', 'env-voucher-rewards'),
            array('description' => __('Display voucher count badge', 'env-voucher-rewards'))
        );
    }
    
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $display = EVR_Voucher_Display::get_instance();
        echo $display->render_voucher_counter(array(
            'type' => $instance['type'] ?? 'active',
            'style' => $instance['style'] ?? 'badge',
            'show_label' => !empty($instance['show_label'])
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $type = !empty($instance['type']) ? $instance['type'] : 'active';
        $style = !empty($instance['style']) ? $instance['style'] : 'badge';
        $show_label = !empty($instance['show_label']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'env-voucher-rewards'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Counter Type:', 'env-voucher-rewards'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <option value="active" <?php selected($type, 'active'); ?>><?php _e('Active Vouchers', 'env-voucher-rewards'); ?></option>
                <option value="used" <?php selected($type, 'used'); ?>><?php _e('Used Vouchers', 'env-voucher-rewards'); ?></option>
                <option value="total" <?php selected($type, 'total'); ?>><?php _e('Total Vouchers', 'env-voucher-rewards'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Display Style:', 'env-voucher-rewards'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <option value="badge" <?php selected($style, 'badge'); ?>><?php _e('Badge', 'env-voucher-rewards'); ?></option>
                <option value="text" <?php selected($style, 'text'); ?>><?php _e('Text', 'env-voucher-rewards'); ?></option>
                <option value="icon" <?php selected($style, 'icon'); ?>><?php _e('Icon', 'env-voucher-rewards'); ?></option>
            </select>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_label); ?> id="<?php echo $this->get_field_id('show_label'); ?>" name="<?php echo $this->get_field_name('show_label'); ?>">
            <label for="<?php echo $this->get_field_id('show_label'); ?>"><?php _e('Show Label', 'env-voucher-rewards'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = (!empty($new_instance['type'])) ? sanitize_text_field($new_instance['type']) : 'active';
        $instance['style'] = (!empty($new_instance['style'])) ? sanitize_text_field($new_instance['style']) : 'badge';
        $instance['show_label'] = !empty($new_instance['show_label']);
        return $instance;
    }
}

// Initialize the class
EVR_Voucher_Display::get_instance();
