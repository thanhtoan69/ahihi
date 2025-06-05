<?php
/**
 * Plugin Name: Environmental Voucher & Rewards System
 * Plugin URI: https://environmental-platform.com/plugins/voucher-rewards
 * Description: Comprehensive voucher and rewards management system for environmental actions and eco-friendly behavior incentivization.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * Author URI: https://environmental-platform.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: env-voucher-rewards
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EVR_PLUGIN_FILE', __FILE__);
define('EVR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EVR_PLUGIN_VERSION', '1.0.0');
define('EVR_DB_VERSION', '1.0');

/**
 * Main Environmental Voucher & Rewards System Class
 */
class Environmental_Voucher_Rewards {
    
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
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load core functionality
        $this->includes();
        $this->init_hooks();
    }
      /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes
        if (class_exists('EVR_Database_Manager')) {
            EVR_Database_Manager::get_instance();
        }
        
        if (class_exists('EVR_Voucher_Manager')) {
            EVR_Voucher_Manager::get_instance();
        }
        
        if (class_exists('EVR_Reward_Engine')) {
            EVR_Reward_Engine::get_instance();
        }
        
        if (class_exists('EVR_Loyalty_Program')) {
            EVR_Loyalty_Program::get_instance();
        }
        
        if (class_exists('EVR_QR_Generator')) {
            EVR_QR_Generator::get_instance();
        }
        
        if (class_exists('EVR_Analytics')) {
            EVR_Analytics::get_instance();
        }
        
        // Initialize admin classes
        if (is_admin()) {
            if (class_exists('EVR_Admin')) {
                EVR_Admin::get_instance();
            }
            
            if (class_exists('EVR_Voucher_Admin')) {
                EVR_Voucher_Admin::get_instance();
            }
            
            if (class_exists('EVR_Rewards_Dashboard')) {
                EVR_Rewards_Dashboard::get_instance();
            }
            
            if (class_exists('EVR_Partner_Admin')) {
                EVR_Partner_Admin::get_instance();
            }
        }
        
        // Initialize frontend classes
        if (!is_admin()) {
            if (class_exists('EVR_Public')) {
                EVR_Public::get_instance();
            }
            
            if (class_exists('EVR_User_Dashboard')) {
                EVR_User_Dashboard::get_instance();
            }
            
            if (class_exists('EVR_Voucher_Display')) {
                EVR_Voucher_Display::get_instance();
            }
        }
        
        // Register shortcodes
        $this->register_shortcodes();
    }
      /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once EVR_PLUGIN_DIR . 'includes/class-database-manager.php';
        require_once EVR_PLUGIN_DIR . 'includes/class-voucher-manager.php';
        require_once EVR_PLUGIN_DIR . 'includes/class-reward-engine.php';
        require_once EVR_PLUGIN_DIR . 'includes/class-loyalty-program.php';
        require_once EVR_PLUGIN_DIR . 'includes/class-qr-generator.php';
        require_once EVR_PLUGIN_DIR . 'includes/class-analytics.php';
        
        // Admin classes
        if (is_admin()) {
            require_once EVR_PLUGIN_DIR . 'admin/class-admin.php';
            require_once EVR_PLUGIN_DIR . 'admin/class-voucher-admin.php';
            require_once EVR_PLUGIN_DIR . 'admin/class-rewards-dashboard.php';
            require_once EVR_PLUGIN_DIR . 'admin/class-partner-admin.php';
        }
        
        // Frontend classes
        if (!is_admin()) {
            require_once EVR_PLUGIN_DIR . 'public/class-public.php';
            require_once EVR_PLUGIN_DIR . 'public/class-user-dashboard.php';
            require_once EVR_PLUGIN_DIR . 'public/class-voucher-display.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX hooks
        add_action('wp_ajax_evr_redeem_voucher', array($this, 'ajax_redeem_voucher'));
        add_action('wp_ajax_evr_get_user_rewards', array($this, 'ajax_get_user_rewards'));
        add_action('wp_ajax_evr_claim_reward', array($this, 'ajax_claim_reward'));
        
        // WooCommerce integration hooks
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_checkout_process', array($this, 'validate_voucher_on_checkout'));
            add_action('woocommerce_checkout_order_processed', array($this, 'process_voucher_usage'));
            add_filter('woocommerce_cart_totals_coupon_label', array($this, 'customize_coupon_label'), 10, 2);
        }
        
        // Environmental action hooks (integration with other plugins)
        add_action('environmental_quiz_completed', array($this, 'handle_quiz_completion'), 10, 2);
        add_action('environmental_waste_classified', array($this, 'handle_waste_classification'), 10, 2);
        add_action('environmental_carbon_saved', array($this, 'handle_carbon_saving'), 10, 2);
        
        // Cron jobs for automated rewards
        add_action('evr_daily_rewards_check', array($this, 'process_daily_rewards'));
        add_action('evr_voucher_expiry_check', array($this, 'check_voucher_expiry'));
        
        if (!wp_next_scheduled('evr_daily_rewards_check')) {
            wp_schedule_event(time(), 'daily', 'evr_daily_rewards_check');
        }
        
        if (!wp_next_scheduled('evr_voucher_expiry_check')) {
            wp_schedule_event(time(), 'hourly', 'evr_voucher_expiry_check');
        }
    }
      /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('evr_user_dashboard', array($this, 'shortcode_user_dashboard'));
        add_shortcode('evr_user_vouchers', array($this, 'shortcode_user_vouchers'));
        add_shortcode('evr_reward_center', array($this, 'shortcode_reward_center'));
        add_shortcode('evr_voucher_redeem', array($this, 'shortcode_voucher_redeem'));
        add_shortcode('evr_partner_offers', array($this, 'shortcode_partner_offers'));
        add_shortcode('evr_loyalty_status', array($this, 'shortcode_loyalty_status'));
        add_shortcode('evr_voucher_widget', array($this, 'shortcode_voucher_widget'));
        add_shortcode('evr_points_display', array($this, 'shortcode_points_display'));
    }
        add_shortcode('evr_partner_offers', array($this, 'shortcode_partner_offers'));
        add_shortcode('evr_loyalty_status', array($this, 'shortcode_loyalty_status'));
    }
      /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // User Dashboard Styles
        wp_enqueue_style(
            'evr-user-dashboard-styles',
            EVR_PLUGIN_URL . 'assets/css/user-dashboard.css',
            array(),
            EVR_PLUGIN_VERSION
        );
        
        // Voucher Display Styles
        wp_enqueue_style(
            'evr-voucher-display-styles',
            EVR_PLUGIN_URL . 'assets/css/voucher-display.css',
            array(),
            EVR_PLUGIN_VERSION
        );
        
        // User Dashboard Scripts
        wp_enqueue_script(
            'evr-user-dashboard-scripts',
            EVR_PLUGIN_URL . 'assets/js/user-dashboard.js',
            array('jquery', 'chart-js'),
            EVR_PLUGIN_VERSION,
            true
        );
        
        // Voucher Display Scripts
        wp_enqueue_script(
            'evr-voucher-display-scripts',
            EVR_PLUGIN_URL . 'assets/js/voucher-display.js',
            array('jquery'),
            EVR_PLUGIN_VERSION,
            true
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize scripts for AJAX
        wp_localize_script('evr-user-dashboard-scripts', 'dashboard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_nonce'),
            'messages' => array(
                'loading' => __('Loading...', 'env-voucher-rewards'),
                'error' => __('An error occurred. Please try again.', 'env-voucher-rewards'),
                'success' => __('Action completed successfully!', 'env-voucher-rewards')
            )
        ));
        
        wp_localize_script('evr-voucher-display-scripts', 'voucher_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_nonce'),
            'messages' => array(
                'voucher_applied' => __('Voucher applied successfully!', 'env-voucher-rewards'),
                'voucher_expired' => __('This voucher has expired.', 'env-voucher-rewards'),
                'reward_redeemed' => __('Reward redeemed successfully!', 'env-voucher-rewards'),
                'insufficient_points' => __('Insufficient points for this reward.', 'env-voucher-rewards'),
                'error' => __('An error occurred. Please try again.', 'env-voucher-rewards')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'evr_') === false) {
            return;
        }
        
        wp_enqueue_style(
            'evr-admin-styles',
            EVR_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            EVR_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'evr-admin-scripts',
            EVR_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery', 'wp-color-picker'),
            EVR_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'env-voucher-rewards',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables (already created via SQL)
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('evr_daily_rewards_check')) {
            wp_schedule_event(time(), 'daily', 'evr_daily_rewards_check');
        }
        
        if (!wp_next_scheduled('evr_voucher_expiry_check')) {
            wp_schedule_event(time(), 'hourly', 'evr_voucher_expiry_check');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('evr_daily_rewards_check');
        wp_clear_scheduled_hook('evr_voucher_expiry_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        // Tables are created via SQL file, this method for future migrations
        update_option('evr_db_version', EVR_DB_VERSION);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'evr_enable_vouchers' => true,
            'evr_enable_rewards' => true,
            'evr_enable_loyalty_program' => true,
            'evr_enable_partner_integration' => true,
            'evr_voucher_expiry_reminder_days' => 7,
            'evr_points_to_currency_ratio' => 100, // 100 points = $1
            'evr_enable_qr_codes' => true,
            'evr_enable_email_notifications' => true,
            'evr_default_voucher_validity_days' => 30,
            'evr_max_vouchers_per_user_per_day' => 5,
            'evr_enable_social_sharing_bonus' => true
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Handle quiz completion (integration with Phase 40)
     */
    public function handle_quiz_completion($user_id, $quiz_data) {
        if (!$user_id || !isset($quiz_data['score'])) {
            return;
        }
        
        // Check if user qualifies for voucher based on score
        if ($quiz_data['score'] >= 80) {
            $voucher_manager = EVR_Voucher_Manager::get_instance();
            $voucher_manager->generate_voucher_for_user($user_id, 'quiz_completion', $quiz_data);
        }
    }
    
    /**
     * Handle waste classification (integration with Phase 39)
     */
    public function handle_waste_classification($user_id, $classification_data) {
        if (!$user_id || !isset($classification_data['accuracy'])) {
            return;
        }
        
        // Award points and possibly voucher for accurate classification
        if ($classification_data['accuracy'] >= 90) {
            $reward_engine = EVR_Reward_Engine::get_instance();
            $reward_engine->award_action_reward($user_id, 'waste_classification', $classification_data);
        }
    }
    
    /**
     * Handle carbon saving actions
     */
    public function handle_carbon_saving($user_id, $carbon_data) {
        if (!$user_id || !isset($carbon_data['co2_saved'])) {
            return;
        }
        
        // Award rewards based on carbon saved
        $reward_engine = EVR_Reward_Engine::get_instance();
        $reward_engine->award_carbon_saving_reward($user_id, $carbon_data);
    }
    
    /**
     * AJAX: Redeem voucher
     */
    public function ajax_redeem_voucher() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        $result = $voucher_manager->redeem_voucher($voucher_code, $user_id);
        
        wp_die(json_encode($result));
    }
    
    /**
     * AJAX: Get user rewards
     */
    public function ajax_get_user_rewards() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $reward_engine = EVR_Reward_Engine::get_instance();
        $rewards = $reward_engine->get_user_rewards($user_id);
        
        wp_die(json_encode(array('success' => true, 'rewards' => $rewards)));
    }
    
    /**
     * Shortcode: User vouchers
     */
    public function shortcode_user_vouchers($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'status' => 'active'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . __('Please log in to view your vouchers.', 'env-voucher-rewards') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        $vouchers = $voucher_manager->get_user_vouchers($user_id, $atts['status'], $atts['limit']);
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/user-vouchers.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Reward center
     */    /**
     * User Dashboard Shortcode
     */
    public function shortcode_user_dashboard($atts) {
        $atts = shortcode_atts(array(
            'show_stats' => true,
            'show_activities' => true,
            'show_achievements' => true
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . __('Please log in to access your dashboard.', 'env-voucher-rewards') . '</div>';
        }
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/user-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * User Vouchers Shortcode
     */
    public function shortcode_user_vouchers($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'status' => 'all',
            'show_expired' => true
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . __('Please log in to view your vouchers.', 'env-voucher-rewards') . '</div>';
        }
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/user-vouchers.php';
        return ob_get_clean();
    }
    
    /**
     * Reward Center Shortcode
     */
    public function shortcode_reward_center($atts) {
        $atts = shortcode_atts(array(
            'show_progress' => true,
            'show_leaderboard' => false
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . __('Please log in to access the reward center.', 'env-voucher-rewards') . '</div>';
        }
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/reward-center.php';
        return ob_get_clean();
    }
    
    /**
     * Voucher Redeem Shortcode
     */
    public function shortcode_voucher_redeem($atts) {
        $atts = shortcode_atts(array(
            'voucher_id' => '',
            'redirect_after' => ''
        ), $atts);
        
        if (empty($atts['voucher_id'])) {
            return '<div class="evr-error">' . __('Voucher ID is required.', 'env-voucher-rewards') . '</div>';
        }
        
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        $voucher = $voucher_manager->get_voucher($atts['voucher_id']);
        
        if (!$voucher) {
            return '<div class="evr-error">' . __('Voucher not found.', 'env-voucher-rewards') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="evr-voucher-redeem">
            <div class="voucher-details">
                <h3><?php echo esc_html($voucher->title); ?></h3>
                <p><?php echo esc_html($voucher->description); ?></p>
                <div class="voucher-value">
                    <?php if ($voucher->discount_type === 'percentage'): ?>
                        <?php echo esc_html($voucher->discount_amount); ?>% Off
                    <?php else: ?>
                        $<?php echo esc_html($voucher->discount_amount); ?> Off
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary apply-voucher-btn" 
                    data-voucher-id="<?php echo esc_attr($voucher->id); ?>"
                    data-voucher-code="<?php echo esc_attr($voucher->code); ?>">
                Apply to Cart
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Partner Offers Shortcode
     */
    public function shortcode_partner_offers($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
            'partner_id' => '',
            'category' => ''
        ), $atts);
        
        $db_manager = EVR_Database_Manager::get_instance();
        $offers = $db_manager->get_partner_discounts($atts);
        
        ob_start();
        ?>
        <div class="evr-partner-offers">
            <?php if (empty($offers)): ?>
                <p><?php _e('No partner offers available at the moment.', 'env-voucher-rewards'); ?></p>
            <?php else: ?>
                <div class="offers-grid">
                    <?php foreach ($offers as $offer): ?>
                        <div class="offer-card">
                            <h4><?php echo esc_html($offer->title); ?></h4>
                            <p><?php echo esc_html($offer->description); ?></p>
                            <div class="offer-discount">
                                <?php echo esc_html($offer->discount_amount); ?>
                                <?php echo $offer->discount_type === 'percentage' ? '%' : '$'; ?> Off
                            </div>
                            <div class="partner-name"><?php echo esc_html($offer->partner_name); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Loyalty Status Shortcode
     */
    public function shortcode_loyalty_status($atts) {
        $atts = shortcode_atts(array(
            'show_progress' => true,
            'show_benefits' => true
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . __('Please log in to view your loyalty status.', 'env-voucher-rewards') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $user_tier = $loyalty_program->get_user_tier($user_id);
        
        ob_start();
        ?>
        <div class="evr-loyalty-status">
            <div class="current-tier">
                <div class="tier-badge tier-<?php echo esc_attr(strtolower($user_tier['name'])); ?>">
                    <span class="tier-icon"><?php echo esc_html($user_tier['icon']); ?></span>
                    <span class="tier-name"><?php echo esc_html($user_tier['name']); ?></span>
                </div>
            </div>
            
            <?php if ($atts['show_progress'] && $user_tier['next_tier']): ?>
                <div class="tier-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr($user_tier['progress_percent']); ?>%"></div>
                    </div>
                    <p><?php printf(__('%d points to reach %s', 'env-voucher-rewards'), 
                        $user_tier['points_to_next'], 
                        $user_tier['next_tier']
                    ); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_benefits']): ?>
                <div class="tier-benefits">
                    <h4><?php _e('Your Benefits', 'env-voucher-rewards'); ?></h4>
                    <ul>
                        <?php foreach ($user_tier['benefits'] as $benefit): ?>
                            <li><?php echo esc_html($benefit); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Voucher Widget Shortcode
     */
    public function shortcode_voucher_widget($atts) {
        $atts = shortcode_atts(array(
            'type' => 'count',
            'user_id' => get_current_user_id()
        ), $atts);
        
        if (!$atts['user_id']) {
            return '';
        }
        
        $db_manager = EVR_Database_Manager::get_instance();
        
        ob_start();
        ?>
        <div class="evr-voucher-widget">
            <?php if ($atts['type'] === 'count'): ?>
                <?php $count = $db_manager->get_user_voucher_count($atts['user_id'], 'active'); ?>
                <div class="voucher-count">
                    <span class="count-number"><?php echo number_format($count); ?></span>
                    <span class="count-label"><?php _e('Active Vouchers', 'env-voucher-rewards'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Points Display Shortcode
     */
    public function shortcode_points_display($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_tier' => false
        ), $atts);
        
        if (!$atts['user_id']) {
            return '';
        }
        
        $db_manager = EVR_Database_Manager::get_instance();
        $points = $db_manager->get_user_total_points($atts['user_id']);
        
        ob_start();
        ?>
        <div class="evr-points-display">
            <div class="points-balance">
                <span class="points-number"><?php echo number_format($points); ?></span>
                <span class="points-label"><?php _e('Points', 'env-voucher-rewards'); ?></span>
            </div>
            
            <?php if ($atts['show_tier']): ?>
                <?php 
                $loyalty_program = EVR_Loyalty_Program::get_instance();
                $user_tier = $loyalty_program->get_user_tier($atts['user_id']);
                ?>
                <div class="user-tier">
                    <span class="tier-badge tier-<?php echo esc_attr(strtolower($user_tier['name'])); ?>">
                        <?php echo esc_html($user_tier['name']); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Process daily rewards check
     */
    public function process_daily_rewards() {
        $reward_engine = EVR_Reward_Engine::get_instance();
        $reward_engine->process_daily_rewards();
    }
    
    /**
     * Check voucher expiry and send notifications
     */
    public function check_voucher_expiry() {
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        $voucher_manager->check_expiring_vouchers();
    }
}

// Initialize the plugin
function evr_init() {
    return Environmental_Voucher_Rewards::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'evr_init');

// Additional utility functions
function evr_get_user_voucher_count($user_id, $status = 'active') {
    $voucher_manager = EVR_Voucher_Manager::get_instance();
    return $voucher_manager->get_user_voucher_count($user_id, $status);
}

function evr_get_user_total_savings($user_id) {
    global $wpdb;
    
    $total_savings = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(used_amount) 
        FROM vouchers 
        WHERE user_id = %d AND voucher_status = 'used'
    ", $user_id));
    
    return $total_savings ? floatval($total_savings) : 0;
}

function evr_format_currency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

function evr_generate_voucher_code($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return 'EVR-' . $code;
}
