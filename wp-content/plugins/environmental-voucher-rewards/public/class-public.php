<?php
/**
 * Public Class
 * 
 * Handles frontend functionality and user-facing features
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Public {
    
    private static $instance = null;
    private $db_manager;
    private $voucher_manager;
    private $reward_engine;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db_manager = EVR_Database_Manager::get_instance();
        $this->voucher_manager = EVR_Voucher_Manager::get_instance();
        $this->reward_engine = EVR_Reward_Engine::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('evr_voucher_wallet', array($this, 'shortcode_voucher_wallet'));
        add_shortcode('evr_reward_center', array($this, 'shortcode_reward_center'));
        add_shortcode('evr_partner_discounts', array($this, 'shortcode_partner_discounts'));
        add_shortcode('evr_user_progress', array($this, 'shortcode_user_progress'));
        add_shortcode('evr_leaderboard', array($this, 'shortcode_leaderboard'));
        
        // AJAX handlers for logged-in users
        add_action('wp_ajax_evr_claim_voucher', array($this, 'ajax_claim_voucher'));
        add_action('wp_ajax_evr_redeem_voucher', array($this, 'ajax_redeem_voucher'));
        add_action('wp_ajax_evr_claim_reward', array($this, 'ajax_claim_reward'));
        add_action('wp_ajax_evr_get_user_stats', array($this, 'ajax_get_user_stats'));
        add_action('wp_ajax_evr_share_achievement', array($this, 'ajax_share_achievement'));
        
        // Frontend integration hooks
        add_action('wp_footer', array($this, 'add_voucher_popup'));
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'display_available_vouchers'));
        add_action('woocommerce_checkout_order_review', array($this, 'display_checkout_vouchers'));
        
        // User profile integration
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        
        // Login/logout hooks for rewards
        add_action('wp_login', array($this, 'handle_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'handle_user_logout'));
        
        // Content filters
        add_filter('the_content', array($this, 'auto_insert_voucher_widget'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts() {
        // Main frontend CSS
        wp_enqueue_style(
            'evr-public',
            EVR_PLUGIN_URL . 'assets/css/public.css',
            array(),
            EVR_VERSION
        );
        
        // Main frontend JavaScript
        wp_enqueue_script(
            'evr-public',
            EVR_PLUGIN_URL . 'assets/js/public.js',
            array('jquery', 'wp-util'),
            EVR_VERSION,
            true
        );
        
        // Chart.js for progress charts
        if (is_user_logged_in()) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
        
        // Localize script
        wp_localize_script('evr-public', 'evrPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_public_nonce'),
            'isLoggedIn' => is_user_logged_in(),
            'userId' => get_current_user_id(),
            'strings' => array(
                'loading' => __('Loading...', 'env-voucher-rewards'),
                'error' => __('An error occurred. Please try again.', 'env-voucher-rewards'),
                'success' => __('Success!', 'env-voucher-rewards'),
                'confirmClaim' => __('Are you sure you want to claim this voucher?', 'env-voucher-rewards'),
                'confirmRedeem' => __('Are you sure you want to redeem this reward?', 'env-voucher-rewards'),
                'loginRequired' => __('Please log in to access this feature.', 'env-voucher-rewards'),
                'voucherClaimed' => __('Voucher claimed successfully!', 'env-voucher-rewards'),
                'rewardClaimed' => __('Reward claimed successfully!', 'env-voucher-rewards'),
                'shareAchievement' => __('I just achieved a new milestone on the Environmental Platform!', 'env-voucher-rewards')
            ),
            'settings' => array(
                'enableNotifications' => get_option('evr_enable_notifications', true),
                'enableSounds' => get_option('evr_enable_sounds', false),
                'autoApplyVouchers' => get_option('evr_auto_apply_vouchers', true)
            )
        ));
        
        // QR Code scanner if needed
        if (get_option('evr_enable_qr_codes', true)) {
            wp_enqueue_script(
                'qr-scanner',
                'https://unpkg.com/qr-scanner@1.4.2/qr-scanner.min.js',
                array(),
                '1.4.2',
                true
            );
        }
    }
    
    /**
     * Shortcode: Voucher Wallet
     */
    public function shortcode_voucher_wallet($atts) {
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . 
                   sprintf(__('Please <a href="%s">log in</a> to view your voucher wallet.', 'env-voucher-rewards'), wp_login_url()) .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'status' => 'active',
            'limit' => 10,
            'show_expired' => false,
            'layout' => 'grid' // grid or list
        ), $atts);
        
        $user_id = get_current_user_id();
        $vouchers = $this->voucher_manager->get_user_vouchers($user_id, $atts['status'], intval($atts['limit']));
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/voucher-wallet.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Reward Center
     */
    public function shortcode_reward_center($atts) {
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . 
                   sprintf(__('Please <a href="%s">log in</a> to access the reward center.', 'env-voucher-rewards'), wp_login_url()) .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_progress' => true,
            'show_leaderboard' => false,
            'show_achievements' => true,
            'limit' => 20
        ), $atts);
        
        $user_id = get_current_user_id();
        $user_rewards = $this->reward_engine->get_user_reward_stats($user_id);
        $available_rewards = $this->reward_engine->get_available_rewards($user_id);
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/reward-center.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Partner Discounts
     */
    public function shortcode_partner_discounts($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 12,
            'show_expired' => false,
            'layout' => 'grid'
        ), $atts);
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $partner_integration = EVR_Partner_Integration::get_instance();
        $discounts = $partner_integration->get_partner_discounts($user_id);
        
        if (!empty($atts['category'])) {
            $discounts = array_filter($discounts, function($discount) use ($atts) {
                return $discount->category === $atts['category'];
            });
        }
        
        $discounts = array_slice($discounts, 0, intval($atts['limit']));
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/partner-discounts.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: User Progress
     */
    public function shortcode_user_progress($atts) {
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . 
                   __('Please log in to view your progress.', 'env-voucher-rewards') .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_points' => true,
            'show_level' => true,
            'show_badges' => true,
            'show_streak' => true,
            'compact' => false
        ), $atts);
        
        $user_id = get_current_user_id();
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $user_progress = $loyalty_program->get_user_progress($user_id);
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/user-progress.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Leaderboard
     */
    public function shortcode_leaderboard($atts) {
        $atts = shortcode_atts(array(
            'type' => 'points', // points, vouchers, achievements
            'period' => 'month', // week, month, year, all
            'limit' => 10,
            'show_current_user' => true
        ), $atts);
        
        $leaderboard_data = $this->get_leaderboard_data($atts['type'], $atts['period'], intval($atts['limit']));
        $current_user_position = null;
        
        if (is_user_logged_in() && $atts['show_current_user']) {
            $current_user_position = $this->get_user_leaderboard_position(get_current_user_id(), $atts['type'], $atts['period']);
        }
        
        ob_start();
        include EVR_PLUGIN_DIR . 'templates/leaderboard.php';
        return ob_get_clean();
    }
    
    /**
     * Add voucher popup to footer
     */
    public function add_voucher_popup() {
        if (!is_user_logged_in() || is_admin()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $pending_vouchers = get_user_meta($user_id, 'evr_pending_voucher_notifications', true);
        
        if (!empty($pending_vouchers)) {
            foreach ($pending_vouchers as $voucher_id) {
                $voucher = $this->voucher_manager->get_voucher($voucher_id);
                if ($voucher) {
                    ?>
                    <div class="evr-voucher-notification" data-voucher-id="<?php echo esc_attr($voucher_id); ?>">
                        <div class="evr-notification-content">
                            <div class="evr-notification-icon">
                                <span class="dashicons dashicons-tickets-alt"></span>
                            </div>
                            <div class="evr-notification-text">
                                <h4><?php _e('New Voucher Earned!', 'env-voucher-rewards'); ?></h4>
                                <p><?php echo esc_html($voucher->voucher_name); ?></p>
                            </div>
                            <div class="evr-notification-actions">
                                <button type="button" class="evr-btn evr-btn-primary claim-voucher" data-voucher-id="<?php echo esc_attr($voucher_id); ?>">
                                    <?php _e('Claim', 'env-voucher-rewards'); ?>
                                </button>
                                <button type="button" class="evr-btn evr-btn-secondary dismiss-notification">
                                    <?php _e('Dismiss', 'env-voucher-rewards'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            
            // Clear pending notifications
            delete_user_meta($user_id, 'evr_pending_voucher_notifications');
        }
    }
    
    /**
     * Display available vouchers on cart page
     */
    public function display_available_vouchers() {
        if (!is_user_logged_in() || !function_exists('WC')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $cart_total = WC()->cart->get_total('');
        $available_vouchers = $this->voucher_manager->get_applicable_vouchers($user_id, $cart_total);
        
        if (!empty($available_vouchers)) {
            ?>
            <tr class="evr-available-vouchers">
                <th><?php _e('Available Vouchers', 'env-voucher-rewards'); ?></th>
                <td>
                    <div class="evr-voucher-suggestions">
                        <?php foreach ($available_vouchers as $voucher) : ?>
                        <div class="evr-voucher-suggestion" data-voucher-code="<?php echo esc_attr($voucher->voucher_code); ?>">
                            <div class="voucher-info">
                                <span class="voucher-name"><?php echo esc_html($voucher->voucher_name); ?></span>
                                <span class="voucher-savings">
                                    <?php 
                                    $savings = $this->voucher_manager->calculate_discount($voucher, $cart_total);
                                    echo sprintf(__('Save %s', 'env-voucher-rewards'), wc_price($savings));
                                    ?>
                                </span>
                            </div>
                            <button type="button" class="evr-btn evr-btn-small apply-voucher" data-voucher-code="<?php echo esc_attr($voucher->voucher_code); ?>">
                                <?php _e('Apply', 'env-voucher-rewards'); ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php
        }
    }
    
    /**
     * Display vouchers on checkout
     */
    public function display_checkout_vouchers() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $active_vouchers = $this->voucher_manager->get_user_vouchers($user_id, 'active', 5);
        
        if (!empty($active_vouchers)) {
            ?>
            <div class="evr-checkout-vouchers">
                <h3><?php _e('Your Vouchers', 'env-voucher-rewards'); ?></h3>
                <div class="evr-voucher-list">
                    <?php foreach ($active_vouchers as $voucher) : ?>
                    <div class="evr-checkout-voucher" data-voucher-code="<?php echo esc_attr($voucher->voucher_code); ?>">
                        <div class="voucher-content">
                            <span class="voucher-name"><?php echo esc_html($voucher->voucher_name); ?></span>
                            <span class="voucher-value">
                                <?php 
                                if ($voucher->discount_type === 'percentage') {
                                    echo esc_html($voucher->discount_value) . '%';
                                } else {
                                    echo wc_price($voucher->discount_value);
                                }
                                ?>
                            </span>
                        </div>
                        <button type="button" class="evr-btn evr-btn-outline apply-checkout-voucher" data-voucher-code="<?php echo esc_attr($voucher->voucher_code); ?>">
                            <?php _e('Use', 'env-voucher-rewards'); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        $user_rewards = $this->reward_engine->get_user_reward_stats($user->ID);
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $user_tier = $loyalty_program->get_user_tier($user->ID);
        
        ?>
        <h3><?php _e('Environmental Rewards', 'env-voucher-rewards'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Green Points', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <span class="evr-profile-stat"><?php echo number_format($user_rewards['total_points'] ?? 0); ?></span>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Loyalty Tier', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <span class="evr-profile-tier evr-tier-<?php echo esc_attr(strtolower($user_tier['tier'])); ?>">
                        <?php echo esc_html($user_tier['tier']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Vouchers Earned', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <span class="evr-profile-stat"><?php echo number_format($user_rewards['total_vouchers'] ?? 0); ?></span>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Environmental Impact', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <div class="evr-impact-summary">
                        <div class="impact-item">
                            <span class="impact-value"><?php echo number_format($user_rewards['carbon_saved'] ?? 0, 2); ?> kg</span>
                            <span class="impact-label"><?php _e('CO₂ Saved', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="impact-item">
                            <span class="impact-value"><?php echo number_format($user_rewards['quizzes_completed'] ?? 0); ?></span>
                            <span class="impact-label"><?php _e('Quizzes Completed', 'env-voucher-rewards'); ?></span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Notification Preferences', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="evr_email_notifications" value="1" 
                                   <?php checked(get_user_meta($user->ID, 'evr_email_notifications', true), 1); ?>>
                            <?php _e('Email notifications for new vouchers', 'env-voucher-rewards'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="evr_achievement_notifications" value="1" 
                                   <?php checked(get_user_meta($user->ID, 'evr_achievement_notifications', true), 1); ?>>
                            <?php _e('Achievement notifications', 'env-voucher-rewards'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="evr_weekly_digest" value="1" 
                                   <?php checked(get_user_meta($user->ID, 'evr_weekly_digest', true), 1); ?>>
                            <?php _e('Weekly progress digest', 'env-voucher-rewards'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        update_user_meta($user_id, 'evr_email_notifications', isset($_POST['evr_email_notifications']) ? 1 : 0);
        update_user_meta($user_id, 'evr_achievement_notifications', isset($_POST['evr_achievement_notifications']) ? 1 : 0);
        update_user_meta($user_id, 'evr_weekly_digest', isset($_POST['evr_weekly_digest']) ? 1 : 0);
    }
    
    /**
     * Handle user login
     */
    public function handle_user_login($user_login, $user) {
        // Award login streak points
        $this->reward_engine->process_login_streak($user->ID);
        
        // Check for any achievements
        $this->reward_engine->check_user_milestones($user->ID);
    }
    
    /**
     * Handle user logout
     */
    public function handle_user_logout() {
        // Any cleanup needed on logout
    }
    
    /**
     * Auto-insert voucher widget in content
     */
    public function auto_insert_voucher_widget($content) {
        if (!is_single() || !is_user_logged_in() || is_admin()) {
            return $content;
        }
        
        $auto_insert = get_option('evr_auto_insert_voucher_widget', false);
        if (!$auto_insert) {
            return $content;
        }
        
        $user_id = get_current_user_id();
        $available_vouchers = $this->voucher_manager->get_user_vouchers($user_id, 'active', 3);
        
        if (!empty($available_vouchers)) {
            $widget_html = '<div class="evr-content-widget">';
            $widget_html .= '<h4>' . __('Your Available Vouchers', 'env-voucher-rewards') . '</h4>';
            $widget_html .= '<div class="evr-mini-vouchers">';
            
            foreach ($available_vouchers as $voucher) {
                $widget_html .= sprintf(
                    '<div class="evr-mini-voucher"><span class="voucher-name">%s</span><span class="voucher-value">%s</span></div>',
                    esc_html($voucher->voucher_name),
                    $voucher->discount_type === 'percentage' ? 
                        esc_html($voucher->discount_value) . '%' : 
                        wc_price($voucher->discount_value)
                );
            }
            
            $widget_html .= '</div>';
            $widget_html .= '<a href="' . get_permalink(get_option('evr_voucher_wallet_page')) . '" class="evr-btn evr-btn-small">' . __('View All Vouchers', 'env-voucher-rewards') . '</a>';
            $widget_html .= '</div>';
            
            $content .= $widget_html;
        }
        
        return $content;
    }
    
    /**
     * Get leaderboard data
     */
    private function get_leaderboard_data($type, $period, $limit) {
        global $wpdb;
        
        $date_clause = '';
        switch ($period) {
            case 'week':
                $date_clause = "WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_clause = "WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_clause = "WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
        }
        
        switch ($type) {
            case 'points':
                $sql = $wpdb->prepare("
                    SELECT u.ID, u.display_name, u.user_email, 
                           COALESCE(SUM(rt.points_involved), 0) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}reward_transactions rt ON u.ID = rt.user_id
                    {$date_clause}
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ", $limit);
                break;
                
            case 'vouchers':
                $sql = $wpdb->prepare("
                    SELECT u.ID, u.display_name, u.user_email, 
                           COUNT(v.voucher_id) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}vouchers v ON u.ID = v.user_id
                    {$date_clause}
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ", $limit);
                break;
                
            default:
                return array();
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get user leaderboard position
     */
    private function get_user_leaderboard_position($user_id, $type, $period) {
        $all_data = $this->get_leaderboard_data($type, $period, 1000); // Get more data to find position
        
        foreach ($all_data as $index => $user_data) {
            if ($user_data->ID == $user_id) {
                return $index + 1;
            }
        }
        
        return null;
    }
    
    /**
     * AJAX: Claim voucher
     */
    public function ajax_claim_voucher() {
        check_ajax_referer('evr_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to claim vouchers.', 'env-voucher-rewards'));
        }
        
        $voucher_id = intval($_POST['voucher_id']);
        $user_id = get_current_user_id();
        
        $result = $this->voucher_manager->claim_voucher($voucher_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Voucher claimed successfully!', 'env-voucher-rewards'),
                'voucher' => $result['voucher']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Redeem voucher
     */
    public function ajax_redeem_voucher() {
        check_ajax_referer('evr_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to redeem vouchers.', 'env-voucher-rewards'));
        }
        
        $voucher_code = sanitize_text_field($_POST['voucher_code']);
        $user_id = get_current_user_id();
        
        $result = $this->voucher_manager->apply_voucher($voucher_code, $user_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Voucher applied successfully!', 'env-voucher-rewards'),
                'discount' => $result['discount_amount']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Claim reward
     */
    public function ajax_claim_reward() {
        check_ajax_referer('evr_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to claim rewards.', 'env-voucher-rewards'));
        }
        
        $reward_id = intval($_POST['reward_id']);
        $user_id = get_current_user_id();
        
        $result = $this->reward_engine->claim_reward($reward_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Reward claimed successfully!', 'env-voucher-rewards'),
                'reward' => $result['reward']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Get user stats
     */
    public function ajax_get_user_stats() {
        check_ajax_referer('evr_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to view stats.', 'env-voucher-rewards'));
        }
        
        $user_id = get_current_user_id();
        $stats = $this->reward_engine->get_user_reward_stats($user_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Share achievement
     */
    public function ajax_share_achievement() {
        check_ajax_referer('evr_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to share achievements.', 'env-voucher-rewards'));
        }
        
        $achievement_type = sanitize_text_field($_POST['achievement_type']);
        $achievement_data = array_map('sanitize_text_field', $_POST['achievement_data']);
        
        // Generate share content
        $share_text = $this->generate_share_text($achievement_type, $achievement_data);
        
        wp_send_json_success(array(
            'share_text' => $share_text,
            'share_url' => home_url()
        ));
    }
    
    /**
     * Generate share text for achievements
     */
    private function generate_share_text($type, $data) {
        switch ($type) {
            case 'milestone':
                return sprintf(
                    __('I just reached %s points on the Environmental Platform! 🌱 #EcoWarrior #Sustainability', 'env-voucher-rewards'),
                    number_format($data['points'])
                );
                
            case 'tier_upgrade':
                return sprintf(
                    __('Level up! I just reached %s tier on the Environmental Platform! 🏆 #EcoChampion', 'env-voucher-rewards'),
                    $data['tier']
                );
                
            case 'badge_earned':
                return sprintf(
                    __('New badge unlocked: %s! 🎖️ Join me on the Environmental Platform! #EcoAchievement', 'env-voucher-rewards'),
                    $data['badge_name']
                );
                
            default:
                return __('I just achieved something amazing on the Environmental Platform! 🌍 #EcoWarrior', 'env-voucher-rewards');
        }
    }
}
