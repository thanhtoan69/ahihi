<?php
/**
 * Environmental Social Viral Referral System
 * 
 * Handles referral tracking and rewards system
 */

class Environmental_Social_Viral_Referral_System {
    
    private static $instance = null;
    private $wpdb;
    private $tables;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $database = new Environmental_Social_Viral_Database();
        $this->tables = $database->get_all_tables();
        
        // Generate referral codes for existing users
        add_action('init', array($this, 'ensure_user_referral_codes'));
        
        // Track user registration for referral rewards
        add_action('user_register', array($this, 'process_referral_registration'), 10, 1);
    }
    
    /**
     * Generate referral code for user
     */
    public function generate_referral_code($user_id, $length = 8) {
        $existing_code = get_user_meta($user_id, 'env_referral_code', true);
        
        if (!empty($existing_code)) {
            return $existing_code;
        }
        
        // Generate unique code
        do {
            $code = $this->create_random_code($length);
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->tables['referrals']} WHERE referral_code = %s",
                    $code
                )
            );
        } while ($exists);
        
        // Store code in user meta
        update_user_meta($user_id, 'env_referral_code', $code);
        
        return $code;
    }
    
    /**
     * Create random referral code
     */
    private function create_random_code($length = 8) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing characters
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Ensure all users have referral codes
     */
    public function ensure_user_referral_codes() {
        // Only run once per day
        if (get_transient('env_referral_codes_checked')) {
            return;
        }
        
        // Get users without referral codes
        $users_without_codes = $this->wpdb->get_results(
            "SELECT u.ID 
             FROM {$this->wpdb->users} u
             LEFT JOIN {$this->wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'env_referral_code'
             WHERE um.meta_value IS NULL
             LIMIT 50"
        );
        
        foreach ($users_without_codes as $user) {
            $this->generate_referral_code($user->ID);
        }
        
        set_transient('env_referral_codes_checked', true, DAY_IN_SECONDS);
    }
    
    /**
     * Track referral visit
     */
    public function track_referral_visit($referral_code, $source_url = '', $landing_page = '') {
        // Validate referral code
        $referrer_id = $this->get_referrer_by_code($referral_code);
        
        if (!$referrer_id) {
            return false;
        }
        
        // Check if referral already exists
        $existing_referral = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['referrals']} 
                 WHERE referral_code = %s AND ip_address = %s 
                 AND first_visit_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $referral_code,
                $this->get_client_ip()
            )
        );
        
        if ($existing_referral) {
            // Update visit count
            $this->wpdb->update(
                $this->tables['referrals'],
                array(
                    'visit_count' => $existing_referral->visit_count + 1,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing_referral->id)
            );
            
            return $existing_referral->id;
        }
        
        // Create new referral record
        $referral_data = array(
            'referrer_id' => $referrer_id,
            'referral_code' => $referral_code,
            'source_url' => $source_url ?: ($_SERVER['HTTP_REFERER'] ?? ''),
            'landing_page' => $landing_page ?: $this->get_current_url(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'status' => 'visited',
            'first_visit_at' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->tables['referrals'], $referral_data);
        
        if ($result) {
            $referral_id = $this->wpdb->insert_id;
            
            // Award visit points to referrer
            $this->award_referral_points($referrer_id, 'visit', 1);
            
            // Trigger referral visit hook
            do_action('env_social_viral_referral_visit', $referral_id, $referrer_id, $referral_code);
            
            return $referral_id;
        }
        
        return false;
    }
    
    /**
     * Process referral registration
     */
    public function process_referral_registration($user_id, $referral_code = '') {
        if (empty($referral_code)) {
            // Try to get from session or cookie
            if (!session_id()) {
                session_start();
            }
            
            if (!empty($_SESSION['env_referral_code'])) {
                $referral_code = $_SESSION['env_referral_code'];
                unset($_SESSION['env_referral_code']);
            } elseif (!empty($_COOKIE['env_referral_code'])) {
                $referral_code = $_COOKIE['env_referral_code'];
                setcookie('env_referral_code', '', time() - 3600, '/');
            }
        }
        
        if (empty($referral_code)) {
            return false;
        }
        
        // Get referrer
        $referrer_id = $this->get_referrer_by_code($referral_code);
        
        if (!$referrer_id || $referrer_id == $user_id) {
            return false;
        }
        
        // Update referral record
        $referral_updated = $this->wpdb->update(
            $this->tables['referrals'],
            array(
                'referee_id' => $user_id,
                'status' => 'converted',
                'conversion_type' => 'registration',
                'conversion_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array(
                'referral_code' => $referral_code,
                'status' => 'visited'
            )
        );
        
        if (!$referral_updated) {
            // Create new referral record if visit wasn't tracked
            $referral_data = array(
                'referrer_id' => $referrer_id,
                'referee_id' => $user_id,
                'referral_code' => $referral_code,
                'status' => 'converted',
                'conversion_type' => 'registration',
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'first_visit_at' => current_time('mysql'),
                'conversion_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            );
            
            $this->wpdb->insert($this->tables['referrals'], $referral_data);
        }
        
        // Award referral rewards
        $this->process_registration_rewards($referrer_id, $user_id, $referral_code);
        
        // Generate referral code for new user
        $this->generate_referral_code($user_id);
        
        // Trigger referral conversion hook
        do_action('env_social_viral_referral_conversion', $referrer_id, $user_id, $referral_code);
        
        return true;
    }
    
    /**
     * Process referral action (for existing users)
     */
    public function process_referral_action($referral_code, $action_type, $user_id = null, $action_value = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $referrer_id = $this->get_referrer_by_code($referral_code);
        
        if (!$referrer_id || $referrer_id == $user_id) {
            return false;
        }
        
        // Check if this action was already processed for this user/referrer combo
        $existing_action = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['referrals']} 
                 WHERE referrer_id = %d AND referee_id = %d 
                 AND conversion_type = %s",
                $referrer_id,
                $user_id,
                $action_type
            )
        );
        
        if ($existing_action) {
            return false; // Already processed
        }
        
        // Create referral record for this action
        $referral_data = array(
            'referrer_id' => $referrer_id,
            'referee_id' => $user_id,
            'referral_code' => $referral_code,
            'status' => 'converted',
            'conversion_type' => $action_type,
            'conversion_value' => $action_value,
            'ip_address' => $this->get_client_ip(),
            'conversion_at' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->tables['referrals'], $referral_data);
        
        if ($result) {
            // Award referral rewards
            $this->process_action_rewards($referrer_id, $user_id, $action_type, $action_value);
            
            return array(
                'success' => true,
                'referral_id' => $this->wpdb->insert_id,
                'rewards_awarded' => true
            );
        }
        
        return false;
    }
    
    /**
     * Process registration rewards
     */
    private function process_registration_rewards($referrer_id, $referee_id, $referral_code) {
        $settings = get_option('env_social_viral_settings', array());
        
        // Referrer reward
        $referrer_reward = $settings['referral_reward_amount'] ?? 10;
        $this->award_referral_reward($referrer_id, 'referral_signup', $referrer_reward, $referral_code);
        
        // Referee reward
        $referee_reward = $settings['referee_reward_amount'] ?? 5;
        $this->award_referral_reward($referee_id, 'signup_bonus', $referee_reward, $referral_code);
        
        // Update referrer stats
        $this->update_referrer_stats($referrer_id);
    }
    
    /**
     * Process action rewards
     */
    private function process_action_rewards($referrer_id, $referee_id, $action_type, $action_value) {
        $settings = get_option('env_social_viral_settings', array());
        
        // Define reward amounts for different actions
        $action_rewards = array(
            'purchase' => $settings['referral_purchase_reward'] ?? 20,
            'donation' => $settings['referral_donation_reward'] ?? 15,
            'content_share' => $settings['referral_share_reward'] ?? 5,
            'event_attendance' => $settings['referral_event_reward'] ?? 10,
            'forum_post' => $settings['referral_forum_reward'] ?? 3
        );
        
        $reward_amount = $action_rewards[$action_type] ?? 5;
        
        // Apply value-based multiplier for purchases/donations
        if (in_array($action_type, array('purchase', 'donation')) && $action_value > 0) {
            $value_multiplier = min($action_value / 100, 2); // Max 2x multiplier
            $reward_amount = round($reward_amount * (1 + $value_multiplier));
        }
        
        // Award reward to referrer
        $this->award_referral_reward($referrer_id, "referral_{$action_type}", $reward_amount, '', $referee_id);
        
        // Update referrer stats
        $this->update_referrer_stats($referrer_id);
    }
    
    /**
     * Award referral reward
     */
    private function award_referral_reward($user_id, $reward_type, $amount, $referral_code = '', $related_user_id = null) {
        if ($amount <= 0) return false;
        
        $settings = get_option('env_social_viral_settings', array());
        $reward_currency = $settings['referral_reward_type'] ?? 'points';
        
        // Get referral ID if code provided
        $referral_id = null;
        if (!empty($referral_code)) {
            $referral = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->tables['referrals']} 
                     WHERE referral_code = %s 
                     ORDER BY created_at DESC LIMIT 1",
                    $referral_code
                )
            );
            $referral_id = $referral ? $referral->id : null;
        }
        
        // Create reward record
        $reward_data = array(
            'referral_id' => $referral_id,
            'user_id' => $user_id,
            'reward_type' => $reward_type,
            'reward_amount' => $amount,
            'reward_currency' => $reward_currency,
            'status' => 'pending',
            'metadata' => json_encode(array(
                'related_user_id' => $related_user_id,
                'referral_code' => $referral_code
            )),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert($this->tables['referral_rewards'], $reward_data);
        
        if ($result) {
            $reward_id = $this->wpdb->insert_id;
            
            // Process reward immediately
            $this->process_reward($reward_id);
            
            return $reward_id;
        }
        
        return false;
    }
    
    /**
     * Process pending reward
     */
    public function process_reward($reward_id) {
        $reward = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['referral_rewards']} WHERE id = %d",
                $reward_id
            )
        );
        
        if (!$reward || $reward->status !== 'pending') {
            return false;
        }
        
        // Award points/credits
        switch ($reward->reward_currency) {
            case 'points':
                $this->award_user_points($reward->user_id, $reward->reward_amount, $reward->reward_type);
                break;
            case 'credits':
                $this->award_user_credits($reward->user_id, $reward->reward_amount, $reward->reward_type);
                break;
            case 'vouchers':
                $this->award_user_voucher($reward->user_id, $reward->reward_amount, $reward->reward_type);
                break;
        }
        
        // Update reward status
        $this->wpdb->update(
            $this->tables['referral_rewards'],
            array(
                'status' => 'processed',
                'processed_at' => current_time('mysql')
            ),
            array('id' => $reward_id)
        );
        
        // Trigger reward processed hook
        do_action('env_social_viral_reward_processed', $reward_id, $reward);
        
        return true;
    }
    
    /**
     * Award user points
     */
    private function award_user_points($user_id, $amount, $reason) {
        // Check if voucher rewards plugin exists
        if (class_exists('Environmental_Voucher_Rewards_Engine')) {
            $reward_engine = new Environmental_Voucher_Rewards_Engine();
            $reward_engine->award_points($user_id, $amount, $reason);
        } else {
            // Fallback to user meta
            $current_points = get_user_meta($user_id, 'env_total_points', true) ?: 0;
            update_user_meta($user_id, 'env_total_points', $current_points + $amount);
        }
        
        // Update referral earnings
        $current_earnings = get_user_meta($user_id, 'env_referral_earnings', true) ?: 0;
        update_user_meta($user_id, 'env_referral_earnings', $current_earnings + $amount);
    }
    
    /**
     * Award user credits
     */
    private function award_user_credits($user_id, $amount, $reason) {
        $current_credits = get_user_meta($user_id, 'env_user_credits', true) ?: 0;
        update_user_meta($user_id, 'env_user_credits', $current_credits + $amount);
    }
    
    /**
     * Award user voucher
     */
    private function award_user_voucher($user_id, $amount, $reason) {
        // Check if voucher system exists
        if (class_exists('Environmental_Voucher_Manager')) {
            $voucher_manager = new Environmental_Voucher_Manager();
            $voucher_manager->create_reward_voucher($user_id, $amount, $reason);
        }
    }
    
    /**
     * Award referral points for various actions
     */
    public function award_referral_points($user_id, $action_type, $points) {
        $current_points = get_user_meta($user_id, 'env_referral_points', true) ?: 0;
        update_user_meta($user_id, 'env_referral_points', $current_points + $points);
        
        // Log the award
        $this->log_points_award($user_id, $action_type, $points);
    }
    
    /**
     * Update referrer statistics
     */
    private function update_referrer_stats($referrer_id) {
        // Get total referral stats
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_referrals,
                    COUNT(CASE WHEN status = 'converted' THEN 1 END) as successful_referrals,
                    SUM(conversion_value) as total_conversion_value
                 FROM {$this->tables['referrals']} 
                 WHERE referrer_id = %d",
                $referrer_id
            )
        );
        
        // Update user meta
        update_user_meta($referrer_id, 'env_total_referrals', $stats->total_referrals);
        update_user_meta($referrer_id, 'env_successful_referrals', $stats->successful_referrals);
        update_user_meta($referrer_id, 'env_referral_conversion_value', $stats->total_conversion_value);
        
        // Calculate referral rate
        $referral_rate = $stats->total_referrals > 0 ? $stats->successful_referrals / $stats->total_referrals : 0;
        update_user_meta($referrer_id, 'env_referral_conversion_rate', $referral_rate);
        
        // Award milestone rewards
        $this->check_referral_milestones($referrer_id, $stats->successful_referrals);
    }
    
    /**
     * Check and award referral milestones
     */
    private function check_referral_milestones($user_id, $successful_referrals) {
        $milestones = array(
            5 => array('points' => 50, 'title' => 'Referral Starter'),
            10 => array('points' => 100, 'title' => 'Referral Pro'),
            25 => array('points' => 250, 'title' => 'Referral Expert'),
            50 => array('points' => 500, 'title' => 'Referral Master'),
            100 => array('points' => 1000, 'title' => 'Referral Legend')
        );
        
        $achieved_milestones = get_user_meta($user_id, 'env_referral_milestones', true) ?: array();
        
        foreach ($milestones as $milestone => $reward) {
            if ($successful_referrals >= $milestone && !in_array($milestone, $achieved_milestones)) {
                // Award milestone reward
                $this->award_user_points($user_id, $reward['points'], 'referral_milestone');
                
                // Award title/badge
                update_user_meta($user_id, 'env_referral_title', $reward['title']);
                
                // Record milestone
                $achieved_milestones[] = $milestone;
                update_user_meta($user_id, 'env_referral_milestones', $achieved_milestones);
                
                // Trigger milestone hook
                do_action('env_social_viral_referral_milestone', $user_id, $milestone, $reward);
            }
        }
    }
    
    /**
     * Generate referral link
     */
    public function generate_referral_link($user_id, $page_url = '', $utm_params = array()) {
        $referral_code = $this->generate_referral_code($user_id);
        
        if (empty($page_url)) {
            $page_url = home_url();
        }
        
        // Base referral parameters
        $ref_params = array(
            'ref' => $referral_code,
            'utm_source' => 'referral',
            'utm_medium' => 'social',
            'utm_campaign' => 'user_referral',
            'utm_content' => $user_id
        );
        
        // Merge with additional UTM parameters
        $ref_params = array_merge($ref_params, $utm_params);
        
        return add_query_arg($ref_params, $page_url);
    }
    
    /**
     * Generate referral link HTML
     */
    public function generate_referral_link_html($atts) {
        $user_id = intval($atts['user_id']);
        $page_id = intval($atts['page_id']);
        $text = sanitize_text_field($atts['text']);
        
        if (!$user_id) {
            return '<p>' . __('Please log in to get your referral link.', 'environmental-social-viral') . '</p>';
        }
        
        $page_url = $page_id ? get_permalink($page_id) : '';
        $referral_link = $this->generate_referral_link($user_id, $page_url);
        $referral_code = $this->generate_referral_code($user_id);
        
        ob_start();
        ?>
        <div class="env-referral-link-widget">
            <h4><?php _e('Your Referral Link', 'environmental-social-viral'); ?></h4>
            <p><?php _e('Share this link to earn rewards when people sign up!', 'environmental-social-viral'); ?></p>
            
            <div class="referral-code">
                <strong><?php _e('Your Code:', 'environmental-social-viral'); ?></strong> 
                <code><?php echo esc_html($referral_code); ?></code>
            </div>
            
            <div class="referral-link">
                <input type="text" value="<?php echo esc_url($referral_link); ?>" readonly class="referral-url-input">
                <button type="button" class="copy-referral-link btn btn-primary">
                    <?php _e('Copy Link', 'environmental-social-viral'); ?>
                </button>
            </div>
            
            <div class="referral-stats">
                <?php echo $this->get_user_referral_stats_html($user_id); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get user referral statistics HTML
     */
    public function get_user_referral_stats_html($user_id) {
        $total_referrals = get_user_meta($user_id, 'env_total_referrals', true) ?: 0;
        $successful_referrals = get_user_meta($user_id, 'env_successful_referrals', true) ?: 0;
        $referral_earnings = get_user_meta($user_id, 'env_referral_earnings', true) ?: 0;
        $referral_title = get_user_meta($user_id, 'env_referral_title', true) ?: __('New Referrer', 'environmental-social-viral');
        
        ob_start();
        ?>
        <div class="referral-stats-summary">
            <h5><?php _e('Your Referral Stats', 'environmental-social-viral'); ?></h5>
            
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Referrals:', 'environmental-social-viral'); ?></span>
                <span class="stat-value"><?php echo esc_html($total_referrals); ?></span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label"><?php _e('Successful Conversions:', 'environmental-social-viral'); ?></span>
                <span class="stat-value"><?php echo esc_html($successful_referrals); ?></span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Earnings:', 'environmental-social-viral'); ?></span>
                <span class="stat-value"><?php echo esc_html($referral_earnings); ?> <?php _e('points', 'environmental-social-viral'); ?></span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label"><?php _e('Referral Status:', 'environmental-social-viral'); ?></span>
                <span class="stat-value referral-title"><?php echo esc_html($referral_title); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get referrer by code
     */
    private function get_referrer_by_code($referral_code) {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT user_id FROM {$this->wpdb->usermeta} 
                 WHERE meta_key = 'env_referral_code' AND meta_value = %s",
                $referral_code
            )
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Get current URL
     */
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Log points award
     */
    private function log_points_award($user_id, $action_type, $points) {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log("Environmental Social Viral: Awarded {$points} points to user {$user_id} for {$action_type}");
        }
    }
    
    /**
     * Get referral dashboard data
     */
    public function get_referral_dashboard_data($user_id, $period = '30days') {
        $period_condition = $this->get_period_condition($period);
        
        // Get referral stats
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_referrals,
                    COUNT(CASE WHEN status = 'converted' THEN 1 END) as conversions,
                    SUM(CASE WHEN status = 'visited' THEN visit_count ELSE 0 END) as total_visits,
                    SUM(conversion_value) as total_value
                 FROM {$this->tables['referrals']} 
                 WHERE referrer_id = %d {$period_condition}",
                $user_id
            )
        );
        
        // Get recent referrals
        $recent_referrals = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT r.*, u.display_name as referee_name
                 FROM {$this->tables['referrals']} r
                 LEFT JOIN {$this->wpdb->users} u ON r.referee_id = u.ID
                 WHERE r.referrer_id = %d {$period_condition}
                 ORDER BY r.created_at DESC
                 LIMIT 10",
                $user_id
            )
        );
        
        // Get rewards earned
        $rewards = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT reward_type, SUM(reward_amount) as total_amount, COUNT(*) as count
                 FROM {$this->tables['referral_rewards']} 
                 WHERE user_id = %d AND status = 'processed' {$period_condition}
                 GROUP BY reward_type
                 ORDER BY total_amount DESC",
                $user_id
            )
        );
        
        return array(
            'stats' => $stats,
            'recent_referrals' => $recent_referrals,
            'rewards' => $rewards,
            'referral_code' => $this->generate_referral_code($user_id)
        );
    }
    
    /**
     * Get period condition for SQL
     */
    private function get_period_condition($period) {
        switch ($period) {
            case '7days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90days':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1year':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }
}
