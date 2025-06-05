<?php
/**
 * User Dashboard Frontend Class
 * 
 * Handles user-facing dashboard functionality for vouchers and rewards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EVR_User_Dashboard {
    
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
        
        // AJAX handlers
        add_action('wp_ajax_evr_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_evr_update_profile_preferences', array($this, 'ajax_update_preferences'));
        add_action('wp_ajax_evr_share_achievement', array($this, 'ajax_share_achievement'));
        
        // User profile integration
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        
        // Shortcodes
        add_shortcode('evr_user_dashboard', array($this, 'render_user_dashboard'));
        add_shortcode('evr_user_progress', array($this, 'render_user_progress'));
        add_shortcode('evr_achievement_showcase', array($this, 'render_achievement_showcase'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register custom post type for achievements
        $this->register_achievement_post_type();
    }
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_style(
            'evr-dashboard-style',
            EVR_PLUGIN_URL . 'assets/css/user-dashboard.css',
            array(),
            EVR_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'evr-dashboard-script',
            EVR_PLUGIN_URL . 'assets/js/user-dashboard.js',
            array('jquery', 'chart-js'),
            EVR_PLUGIN_VERSION,
            true
        );
        
        // Localize dashboard script
        wp_localize_script('evr-dashboard-script', 'evr_dashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_dashboard_nonce'),
            'user_id' => get_current_user_id(),
            'messages' => array(
                'loading' => __('Loading...', 'env-voucher-rewards'),
                'error' => __('Error loading data', 'env-voucher-rewards'),
                'shared' => __('Achievement shared successfully!', 'env-voucher-rewards'),
                'saved' => __('Preferences saved successfully!', 'env-voucher-rewards')
            )
        ));
    }
    
    /**
     * Register achievement post type
     */
    private function register_achievement_post_type() {
        register_post_type('evr_achievement', array(
            'labels' => array(
                'name' => __('Achievements', 'env-voucher-rewards'),
                'singular_name' => __('Achievement', 'env-voucher-rewards'),
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor', 'thumbnail')
        ));
    }
    
    /**
     * Render main user dashboard
     */
    public function render_user_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="evr-login-notice">' . 
                   '<p>' . __('Please log in to access your dashboard.', 'env-voucher-rewards') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="button">' . __('Login', 'env-voucher-rewards') . '</a>' .
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_vouchers' => true,
            'show_rewards' => true,
            'show_progress' => true,
            'show_achievements' => true,
            'show_stats' => true
        ), $atts);
        
        $user_id = get_current_user_id();
        $dashboard_data = $this->get_dashboard_data($user_id);
        
        ob_start();
        $this->render_dashboard_template($dashboard_data, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render user progress widget
     */
    public function render_user_progress($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'type' => 'circular', // circular, linear, detailed
            'show_level' => true,
            'show_next_milestone' => true
        ), $atts);
        
        $user_id = get_current_user_id();
        $progress_data = $this->get_user_progress($user_id);
        
        ob_start();
        $this->render_progress_template($progress_data, $atts);
        return ob_get_clean();
    }
    
    /**
     * Render achievement showcase
     */
    public function render_achievement_showcase($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'limit' => 6,
            'show_locked' => true,
            'layout' => 'grid' // grid, list, carousel
        ), $atts);
        
        $user_id = get_current_user_id();
        $achievements = $this->get_user_achievements($user_id, $atts['limit'], $atts['show_locked']);
        
        ob_start();
        $this->render_achievements_template($achievements, $atts);
        return ob_get_clean();
    }
    
    /**
     * Get dashboard data for user
     */
    private function get_dashboard_data($user_id) {
        global $wpdb;
        
        // Get voucher statistics
        $voucher_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_vouchers,
                SUM(CASE WHEN voucher_status = 'active' THEN 1 ELSE 0 END) as active_vouchers,
                SUM(CASE WHEN voucher_status = 'used' THEN 1 ELSE 0 END) as used_vouchers,
                SUM(CASE WHEN voucher_status = 'expired' THEN 1 ELSE 0 END) as expired_vouchers,
                SUM(CASE WHEN voucher_status = 'used' THEN discount_amount ELSE 0 END) as total_savings
            FROM vouchers 
            WHERE user_id = %d
        ", $user_id));
        
        // Get reward statistics
        $reward_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(SUM(points_earned), 0) as total_points,
                COALESCE(SUM(CASE WHEN reward_status = 'claimed' THEN points_earned ELSE 0 END), 0) as claimed_points,
                COUNT(*) as total_rewards
            FROM user_rewards 
            WHERE user_id = %d
        ", $user_id));
        
        // Get loyalty program data
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $loyalty_data = $loyalty_program->get_user_loyalty_data($user_id);
        
        // Get recent activity
        $recent_activity = $this->get_recent_activity($user_id, 10);
        
        // Get upcoming voucher expirations
        $expiring_vouchers = $wpdb->get_results($wpdb->prepare("
            SELECT voucher_code, expiry_date, discount_amount, discount_type
            FROM vouchers 
            WHERE user_id = %d 
            AND voucher_status = 'active' 
            AND expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            ORDER BY expiry_date ASC
            LIMIT 5
        ", $user_id));
        
        return array(
            'voucher_stats' => $voucher_stats,
            'reward_stats' => $reward_stats,
            'loyalty_data' => $loyalty_data,
            'recent_activity' => $recent_activity,
            'expiring_vouchers' => $expiring_vouchers,
            'achievements' => $this->get_user_achievements($user_id, 5, false),
            'milestones' => $this->get_next_milestones($user_id)
        );
    }
    
    /**
     * Get user progress data
     */
    private function get_user_progress($user_id) {
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $loyalty_data = $loyalty_program->get_user_loyalty_data($user_id);
        
        $current_points = $loyalty_data['current_points'] ?? 0;
        $current_tier = $loyalty_data['current_tier'] ?? 'bronze';
        $next_tier_points = $loyalty_data['next_tier_points'] ?? 1000;
        
        $progress_percentage = $next_tier_points > 0 ? 
            min(100, ($current_points / $next_tier_points) * 100) : 100;
        
        return array(
            'current_points' => $current_points,
            'current_tier' => $current_tier,
            'next_tier' => $loyalty_data['next_tier'] ?? null,
            'next_tier_points' => $next_tier_points,
            'progress_percentage' => $progress_percentage,
            'tier_benefits' => $loyalty_data['tier_benefits'] ?? array(),
            'achievements_unlocked' => count($this->get_user_achievements($user_id, -1, false)),
            'environmental_impact' => $this->get_environmental_impact($user_id)
        );
    }
    
    /**
     * Get user achievements
     */
    private function get_user_achievements($user_id, $limit = -1, $include_locked = true) {
        global $wpdb;
        
        $achievements = array();
        
        // Define achievement types and criteria
        $achievement_types = array(
            'first_voucher' => array(
                'title' => __('First Steps', 'env-voucher-rewards'),
                'description' => __('Earned your first voucher', 'env-voucher-rewards'),
                'icon' => '🌱',
                'criteria' => 'voucher_count >= 1'
            ),
            'eco_warrior' => array(
                'title' => __('Eco Warrior', 'env-voucher-rewards'),
                'description' => __('Earned 10 vouchers', 'env-voucher-rewards'),
                'icon' => '🌿',
                'criteria' => 'voucher_count >= 10'
            ),
            'quiz_master' => array(
                'title' => __('Quiz Master', 'env-voucher-rewards'),
                'description' => __('Completed 5 environmental quizzes', 'env-voucher-rewards'),
                'icon' => '🎓',
                'criteria' => 'quiz_completions >= 5'
            ),
            'waste_expert' => array(
                'title' => __('Waste Classification Expert', 'env-voucher-rewards'),
                'description' => __('Correctly classified 50 waste items', 'env-voucher-rewards'),
                'icon' => '♻️',
                'criteria' => 'waste_classifications >= 50'
            ),
            'carbon_saver' => array(
                'title' => __('Carbon Footprint Reducer', 'env-voucher-rewards'),
                'description' => __('Saved 100kg of CO2', 'env-voucher-rewards'),
                'icon' => '🌍',
                'criteria' => 'co2_saved >= 100'
            ),
            'loyalty_gold' => array(
                'title' => __('Gold Member', 'env-voucher-rewards'),
                'description' => __('Reached Gold loyalty tier', 'env-voucher-rewards'),
                'icon' => '🏆',
                'criteria' => 'loyalty_tier = gold'
            )
        );
        
        // Get user statistics
        $user_stats = $this->get_user_statistics($user_id);
        
        foreach ($achievement_types as $key => $achievement) {
            $is_unlocked = $this->check_achievement_criteria($achievement['criteria'], $user_stats);
            
            if ($is_unlocked || $include_locked) {
                $achievements[] = array(
                    'id' => $key,
                    'title' => $achievement['title'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'unlocked' => $is_unlocked,
                    'unlock_date' => $is_unlocked ? $this->get_achievement_unlock_date($user_id, $key) : null
                );
            }
        }
        
        if ($limit > 0) {
            $achievements = array_slice($achievements, 0, $limit);
        }
        
        return $achievements;
    }
    
    /**
     * Get user statistics
     */
    private function get_user_statistics($user_id) {
        global $wpdb;
        
        $stats = array(
            'voucher_count' => 0,
            'quiz_completions' => 0,
            'waste_classifications' => 0,
            'co2_saved' => 0,
            'loyalty_tier' => 'bronze'
        );
        
        // Get voucher count
        $voucher_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM vouchers WHERE user_id = %d
        ", $user_id));
        $stats['voucher_count'] = intval($voucher_count);
        
        // Get loyalty tier
        $loyalty_program = EVR_Loyalty_Program::get_instance();
        $loyalty_data = $loyalty_program->get_user_loyalty_data($user_id);
        $stats['loyalty_tier'] = $loyalty_data['current_tier'] ?? 'bronze';
        
        // Get activity counts from user meta or activity logs
        $stats['quiz_completions'] = intval(get_user_meta($user_id, 'evr_quiz_completions', true));
        $stats['waste_classifications'] = intval(get_user_meta($user_id, 'evr_waste_classifications', true));
        $stats['co2_saved'] = floatval(get_user_meta($user_id, 'evr_co2_saved', true));
        
        return $stats;
    }
    
    /**
     * Check achievement criteria
     */
    private function check_achievement_criteria($criteria, $stats) {
        // Parse criteria string and evaluate against stats
        if (strpos($criteria, '>=') !== false) {
            list($field, $value) = explode('>=', $criteria);
            $field = trim($field);
            $value = trim($value);
            return isset($stats[$field]) && $stats[$field] >= intval($value);
        } elseif (strpos($criteria, '=') !== false) {
            list($field, $value) = explode('=', $criteria);
            $field = trim($field);
            $value = trim($value);
            return isset($stats[$field]) && $stats[$field] == $value;
        }
        
        return false;
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity($user_id, $limit = 10) {
        global $wpdb;
        
        $activities = array();
        
        // Get recent vouchers
        $recent_vouchers = $wpdb->get_results($wpdb->prepare("
            SELECT 'voucher_earned' as type, voucher_code as item, created_at as date, discount_amount as value
            FROM vouchers 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $user_id, $limit));
        
        // Get recent rewards
        $recent_rewards = $wpdb->get_results($wpdb->prepare("
            SELECT 'reward_earned' as type, reward_type as item, earned_at as date, points_earned as value
            FROM user_rewards 
            WHERE user_id = %d 
            ORDER BY earned_at DESC 
            LIMIT %d
        ", $user_id, $limit));
        
        // Merge and sort activities
        $all_activities = array_merge($recent_vouchers, $recent_rewards);
        usort($all_activities, function($a, $b) {
            return strtotime($b->date) - strtotime($a->date);
        });
        
        return array_slice($all_activities, 0, $limit);
    }
    
    /**
     * Get next milestones
     */
    private function get_next_milestones($user_id) {
        $stats = $this->get_user_statistics($user_id);
        $milestones = array();
        
        // Voucher milestones
        $voucher_milestones = array(1, 5, 10, 25, 50, 100);
        foreach ($voucher_milestones as $milestone) {
            if ($stats['voucher_count'] < $milestone) {
                $milestones[] = array(
                    'type' => 'vouchers',
                    'target' => $milestone,
                    'current' => $stats['voucher_count'],
                    'progress' => ($stats['voucher_count'] / $milestone) * 100,
                    'title' => sprintf(__('Earn %d vouchers', 'env-voucher-rewards'), $milestone)
                );
                break;
            }
        }
        
        // CO2 saving milestones
        $co2_milestones = array(10, 25, 50, 100, 250, 500);
        foreach ($co2_milestones as $milestone) {
            if ($stats['co2_saved'] < $milestone) {
                $milestones[] = array(
                    'type' => 'co2_saved',
                    'target' => $milestone,
                    'current' => $stats['co2_saved'],
                    'progress' => ($stats['co2_saved'] / $milestone) * 100,
                    'title' => sprintf(__('Save %dkg of CO2', 'env-voucher-rewards'), $milestone)
                );
                break;
            }
        }
        
        return array_slice($milestones, 0, 3);
    }
    
    /**
     * Get environmental impact data
     */
    private function get_environmental_impact($user_id) {
        $co2_saved = floatval(get_user_meta($user_id, 'evr_co2_saved', true));
        $waste_classified = intval(get_user_meta($user_id, 'evr_waste_classifications', true));
        $vouchers_used = intval(get_user_meta($user_id, 'evr_vouchers_used', true));
        
        return array(
            'co2_saved' => $co2_saved,
            'waste_classified' => $waste_classified,
            'vouchers_used' => $vouchers_used,
            'equivalent_trees' => round($co2_saved / 21.8, 1), // 1 tree absorbs ~21.8kg CO2/year
            'equivalent_miles' => round($co2_saved * 2.31, 1) // 1kg CO2 = ~2.31 miles driven
        );
    }
    
    /**
     * Get achievement unlock date
     */
    private function get_achievement_unlock_date($user_id, $achievement_id) {
        return get_user_meta($user_id, "evr_achievement_{$achievement_id}_unlocked", true);
    }
    
    /**
     * Add user profile fields
     */
    public function add_user_profile_fields($user) {
        ?>
        <h3><?php _e('Environmental Rewards Preferences', 'env-voucher-rewards'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="evr_email_notifications"><?php _e('Email Notifications', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <input type="checkbox" name="evr_email_notifications" id="evr_email_notifications" 
                           value="1" <?php checked(get_user_meta($user->ID, 'evr_email_notifications', true), 1); ?> />
                    <span class="description"><?php _e('Receive email notifications for vouchers and rewards', 'env-voucher-rewards'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="evr_dashboard_display"><?php _e('Dashboard Display', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <select name="evr_dashboard_display" id="evr_dashboard_display">
                        <option value="compact" <?php selected(get_user_meta($user->ID, 'evr_dashboard_display', true), 'compact'); ?>>
                            <?php _e('Compact View', 'env-voucher-rewards'); ?>
                        </option>
                        <option value="detailed" <?php selected(get_user_meta($user->ID, 'evr_dashboard_display', true), 'detailed'); ?>>
                            <?php _e('Detailed View', 'env-voucher-rewards'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="evr_privacy_level"><?php _e('Privacy Level', 'env-voucher-rewards'); ?></label></th>
                <td>
                    <select name="evr_privacy_level" id="evr_privacy_level">
                        <option value="public" <?php selected(get_user_meta($user->ID, 'evr_privacy_level', true), 'public'); ?>>
                            <?php _e('Public (Show on leaderboard)', 'env-voucher-rewards'); ?>
                        </option>
                        <option value="private" <?php selected(get_user_meta($user->ID, 'evr_privacy_level', true), 'private'); ?>>
                            <?php _e('Private (Hide from leaderboard)', 'env-voucher-rewards'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        
        <h3><?php _e('Environmental Statistics', 'env-voucher-rewards'); ?></h3>
        <table class="form-table">
            <?php 
            $stats = $this->get_user_statistics($user->ID);
            $impact = $this->get_environmental_impact($user->ID);
            ?>
            <tr>
                <th><?php _e('Total Vouchers Earned', 'env-voucher-rewards'); ?></th>
                <td><?php echo $stats['voucher_count']; ?></td>
            </tr>
            <tr>
                <th><?php _e('CO2 Saved', 'env-voucher-rewards'); ?></th>
                <td><?php echo $impact['co2_saved']; ?>kg (<?php echo $impact['equivalent_trees']; ?> trees equivalent)</td>
            </tr>
            <tr>
                <th><?php _e('Waste Items Classified', 'env-voucher-rewards'); ?></th>
                <td><?php echo $impact['waste_classified']; ?></td>
            </tr>
            <tr>
                <th><?php _e('Current Loyalty Tier', 'env-voucher-rewards'); ?></th>
                <td><span class="evr-tier-badge tier-<?php echo $stats['loyalty_tier']; ?>"><?php echo ucfirst($stats['loyalty_tier']); ?></span></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        update_user_meta($user_id, 'evr_email_notifications', isset($_POST['evr_email_notifications']) ? 1 : 0);
        update_user_meta($user_id, 'evr_dashboard_display', sanitize_text_field($_POST['evr_dashboard_display']));
        update_user_meta($user_id, 'evr_privacy_level', sanitize_text_field($_POST['evr_privacy_level']));
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('evr_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $dashboard_data = $this->get_dashboard_data($user_id);
        wp_die(json_encode(array('success' => true, 'data' => $dashboard_data)));
    }
    
    /**
     * AJAX: Update user preferences
     */
    public function ajax_update_preferences() {
        check_ajax_referer('evr_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not logged in')));
        }
        
        $preferences = $_POST['preferences'];
        foreach ($preferences as $key => $value) {
            update_user_meta($user_id, 'evr_' . $key, sanitize_text_field($value));
        }
        
        wp_die(json_encode(array('success' => true, 'message' => 'Preferences updated')));
    }
    
    /**
     * AJAX: Share achievement
     */
    public function ajax_share_achievement() {
        check_ajax_referer('evr_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $achievement_id = sanitize_text_field($_POST['achievement_id']);
        
        if (!$user_id || !$achievement_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'Invalid request')));
        }
        
        // Generate sharing content
        $user = get_user_by('ID', $user_id);
        $achievement_title = sanitize_text_field($_POST['achievement_title']);
        
        $share_content = sprintf(
            __('🎉 I just unlocked the "%s" achievement on the Environmental Platform! Join me in making a difference for our planet! #EcoWarrior #Sustainability', 'env-voucher-rewards'),
            $achievement_title
        );
        
        // Save sharing activity
        update_user_meta($user_id, 'evr_last_shared_achievement', array(
            'achievement_id' => $achievement_id,
            'shared_at' => current_time('mysql'),
            'content' => $share_content
        ));
        
        wp_die(json_encode(array(
            'success' => true, 
            'share_content' => $share_content,
            'share_url' => get_site_url()
        )));
    }
    
    /**
     * Render dashboard template
     */
    private function render_dashboard_template($data, $atts) {
        ?>
        <div class="evr-user-dashboard">
            <div class="evr-dashboard-header">
                <h2><?php _e('My Environmental Dashboard', 'env-voucher-rewards'); ?></h2>
                <div class="evr-user-stats-summary">
                    <div class="evr-stat-item">
                        <span class="evr-stat-number"><?php echo $data['voucher_stats']->active_vouchers ?? 0; ?></span>
                        <span class="evr-stat-label"><?php _e('Active Vouchers', 'env-voucher-rewards'); ?></span>
                    </div>
                    <div class="evr-stat-item">
                        <span class="evr-stat-number"><?php echo number_format($data['reward_stats']->total_points ?? 0); ?></span>
                        <span class="evr-stat-label"><?php _e('Total Points', 'env-voucher-rewards'); ?></span>
                    </div>
                    <div class="evr-stat-item">
                        <span class="evr-stat-number"><?php echo ucfirst($data['loyalty_data']['current_tier'] ?? 'Bronze'); ?></span>
                        <span class="evr-stat-label"><?php _e('Loyalty Tier', 'env-voucher-rewards'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="evr-dashboard-grid">
                <?php if ($atts['show_vouchers']): ?>
                <div class="evr-dashboard-widget evr-vouchers-widget">
                    <h3><?php _e('My Vouchers', 'env-voucher-rewards'); ?></h3>
                    <div class="evr-voucher-stats">
                        <div class="evr-voucher-stat">
                            <strong><?php echo $data['voucher_stats']->total_savings ?? 0; ?>$</strong>
                            <span><?php _e('Total Savings', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="evr-voucher-stat">
                            <strong><?php echo $data['voucher_stats']->used_vouchers ?? 0; ?></strong>
                            <span><?php _e('Used', 'env-voucher-rewards'); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($data['expiring_vouchers'])): ?>
                        <div class="evr-expiring-vouchers">
                            <h4><?php _e('Expiring Soon', 'env-voucher-rewards'); ?></h4>
                            <?php foreach ($data['expiring_vouchers'] as $voucher): ?>
                                <div class="evr-expiring-voucher">
                                    <span class="voucher-code"><?php echo $voucher->voucher_code; ?></span>
                                    <span class="voucher-value">$<?php echo $voucher->discount_amount; ?></span>
                                    <span class="expiry-date"><?php echo date('M j', strtotime($voucher->expiry_date)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_achievements']): ?>
                <div class="evr-dashboard-widget evr-achievements-widget">
                    <h3><?php _e('Recent Achievements', 'env-voucher-rewards'); ?></h3>
                    <div class="evr-achievements-grid">
                        <?php foreach (array_slice($data['achievements'], 0, 4) as $achievement): ?>
                            <div class="evr-achievement-item <?php echo $achievement['unlocked'] ? 'unlocked' : 'locked'; ?>">
                                <div class="achievement-icon"><?php echo $achievement['icon']; ?></div>
                                <div class="achievement-info">
                                    <h4><?php echo $achievement['title']; ?></h4>
                                    <p><?php echo $achievement['description']; ?></p>
                                    <?php if ($achievement['unlocked']): ?>
                                        <button class="evr-share-achievement" data-achievement="<?php echo $achievement['id']; ?>" data-title="<?php echo esc_attr($achievement['title']); ?>">
                                            <?php _e('Share', 'env-voucher-rewards'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_progress']): ?>
                <div class="evr-dashboard-widget evr-progress-widget">
                    <h3><?php _e('Progress to Next Level', 'env-voucher-rewards'); ?></h3>
                    <?php echo $this->render_user_progress(array('type' => 'detailed')); ?>
                </div>
                <?php endif; ?>
                
                <div class="evr-dashboard-widget evr-activity-widget">
                    <h3><?php _e('Recent Activity', 'env-voucher-rewards'); ?></h3>
                    <div class="evr-activity-feed">
                        <?php foreach ($data['recent_activity'] as $activity): ?>
                            <div class="evr-activity-item">
                                <span class="activity-icon">
                                    <?php echo $activity->type == 'voucher_earned' ? '🎫' : '⭐'; ?>
                                </span>
                                <div class="activity-content">
                                    <p>
                                        <?php 
                                        if ($activity->type == 'voucher_earned') {
                                            printf(__('Earned voucher %s worth $%s', 'env-voucher-rewards'), 
                                                   $activity->item, $activity->value);
                                        } else {
                                            printf(__('Earned %d points for %s', 'env-voucher-rewards'), 
                                                   $activity->value, $activity->item);
                                        }
                                        ?>
                                    </p>
                                    <span class="activity-date"><?php echo human_time_diff(strtotime($activity->date)); ?> ago</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render progress template
     */
    private function render_progress_template($data, $atts) {
        ?>
        <div class="evr-user-progress <?php echo $atts['type']; ?>">
            <?php if ($atts['type'] == 'circular'): ?>
                <div class="evr-circular-progress">
                    <svg class="progress-ring" width="120" height="120">
                        <circle class="progress-ring-circle" stroke="#e6e6e6" stroke-width="8" fill="transparent" r="52" cx="60" cy="60"/>
                        <circle class="progress-ring-progress" stroke="#4CAF50" stroke-width="8" fill="transparent" r="52" cx="60" cy="60" 
                                style="stroke-dasharray: <?php echo 2 * 3.14159 * 52; ?>; stroke-dashoffset: <?php echo 2 * 3.14159 * 52 * (1 - $data['progress_percentage'] / 100); ?>;"/>
                    </svg>
                    <div class="progress-text">
                        <span class="current-points"><?php echo number_format($data['current_points']); ?></span>
                        <span class="tier-name"><?php echo ucfirst($data['current_tier']); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="evr-detailed-progress">
                    <div class="current-tier">
                        <h4><?php printf(__('Current Tier: %s', 'env-voucher-rewards'), ucfirst($data['current_tier'])); ?></h4>
                        <div class="tier-progress-bar">
                            <div class="progress-fill" style="width: <?php echo $data['progress_percentage']; ?>%;"></div>
                        </div>
                        <p><?php printf(__('%s / %s points to %s', 'env-voucher-rewards'), 
                                       number_format($data['current_points']), 
                                       number_format($data['next_tier_points']), 
                                       ucfirst($data['next_tier'] ?? 'Max Level')); ?></p>
                    </div>
                    
                    <div class="environmental-impact">
                        <h4><?php _e('Environmental Impact', 'env-voucher-rewards'); ?></h4>
                        <div class="impact-stats">
                            <div class="impact-stat">
                                <span class="impact-number"><?php echo $data['environmental_impact']['co2_saved']; ?>kg</span>
                                <span class="impact-label"><?php _e('CO2 Saved', 'env-voucher-rewards'); ?></span>
                            </div>
                            <div class="impact-stat">
                                <span class="impact-number"><?php echo $data['environmental_impact']['equivalent_trees']; ?></span>
                                <span class="impact-label"><?php _e('Trees Equivalent', 'env-voucher-rewards'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render achievements template
     */
    private function render_achievements_template($achievements, $atts) {
        ?>
        <div class="evr-achievements-showcase <?php echo $atts['layout']; ?>">
            <?php foreach ($achievements as $achievement): ?>
                <div class="evr-achievement-card <?php echo $achievement['unlocked'] ? 'unlocked' : 'locked'; ?>">
                    <div class="achievement-icon"><?php echo $achievement['icon']; ?></div>
                    <div class="achievement-details">
                        <h4><?php echo $achievement['title']; ?></h4>
                        <p><?php echo $achievement['description']; ?></p>
                        <?php if ($achievement['unlocked'] && $achievement['unlock_date']): ?>
                            <span class="unlock-date"><?php echo date('M j, Y', strtotime($achievement['unlock_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

// Initialize the class
EVR_User_Dashboard::get_instance();
