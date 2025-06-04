<?php
/**
 * Environmental Platform User Management System
 * 
 * Phase 31: User Management & Authentication
 * Extends WordPress user system with environmental platform features
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EP_User_Management {
    
    private $db_manager;
    private $custom_roles;
    
    public function __construct() {
        global $wpdb;
        $this->db_manager = new EP_Database_Manager();
        
        // Initialize user management hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_user_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // User registration and login hooks
        add_action('wp_ajax_nopriv_ep_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_ep_login_user', array($this, 'ajax_login_user'));
        add_action('wp_ajax_ep_update_user_profile', array($this, 'ajax_update_user_profile'));
        add_action('wp_ajax_ep_get_user_dashboard_data', array($this, 'ajax_get_user_dashboard_data'));
        
        // WordPress user hooks
        add_action('user_register', array($this, 'sync_new_wp_user_to_ep'));
        add_action('profile_update', array($this, 'sync_wp_user_update_to_ep'));
        add_action('delete_user', array($this, 'handle_user_deletion'));
        
        // Custom user profile fields
        add_action('show_user_profile', array($this, 'add_environmental_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_environmental_profile_fields'));
        add_action('personal_options_update', array($this, 'save_environmental_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_environmental_profile_fields'));
        
        // Login/logout hooks
        add_action('wp_login', array($this, 'handle_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'handle_user_logout'));
        
        // Custom login/registration page hooks
        add_action('wp_loaded', array($this, 'handle_custom_forms'));
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
        
        // Define custom environmental roles
        $this->custom_roles = array(
            'environmental_admin' => array(
                'display_name' => __('Environmental Administrator', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                    'publish_posts' => true,
                    'upload_files' => true,
                    'manage_environmental_data' => true,
                    'manage_environmental_users' => true,
                    'view_environmental_analytics' => true,
                    'moderate_environmental_content' => true,
                )
            ),
            'environmental_moderator' => array(
                'display_name' => __('Environmental Moderator', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                    'publish_posts' => true,
                    'moderate_environmental_content' => true,
                    'view_environmental_data' => true,
                )
            ),
            'content_creator' => array(
                'display_name' => __('Environmental Content Creator', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_own_posts' => true,
                    'publish_posts' => true,
                    'upload_files' => true,
                    'create_environmental_content' => true,
                )
            ),
            'business_partner' => array(
                'display_name' => __('Business Partner', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_posts' => true,
                    'publish_business_content' => true,
                    'manage_business_profile' => true,
                    'view_business_analytics' => true,
                )
            ),
            'organization_member' => array(
                'display_name' => __('Organization Member', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_posts' => true,
                    'create_organization_content' => true,
                    'manage_organization_profile' => true,
                )
            ),
            'eco_user' => array(
                'display_name' => __('Eco User', 'environmental-platform-core'),
                'capabilities' => array(
                    'read' => true,
                    'edit_own_posts' => true,
                    'participate_in_activities' => true,
                    'submit_environmental_data' => true,
                    'earn_green_points' => true,
                )
            )
        );
    }
    
    /**
     * Initialize user management system
     */
    public function init() {
        // Register custom roles
        $this->register_custom_roles();
        
        // Add custom user meta fields
        $this->setup_user_meta_fields();
        
        // Register shortcodes for user interface
        $this->register_user_shortcodes();
    }
    
    /**
     * Register custom environmental user roles
     */
    public function register_custom_roles() {
        foreach ($this->custom_roles as $role_slug => $role_data) {
            if (!get_role($role_slug)) {
                add_role($role_slug, $role_data['display_name'], $role_data['capabilities']);
            }
        }
    }
    
    /**
     * Setup custom user meta fields
     */
    public function setup_user_meta_fields() {
        // These will be synchronized with the environmental platform database
        $meta_fields = array(
            'ep_user_id',
            'ep_green_points',
            'ep_level',
            'ep_total_environmental_score',
            'ep_carbon_footprint_kg',
            'ep_exchange_rating',
            'ep_total_exchanges',
            'ep_preferred_exchange_radius',
            'ep_notification_preferences',
            'ep_privacy_settings',
            'ep_environmental_interests',
            'ep_location_city',
            'ep_location_district',
            'ep_location_coordinates',
            'ep_bio',
            'ep_avatar_url',
            'ep_cover_image_url',
            'ep_is_verified',
            'ep_verification_badges'
        );
        
        foreach ($meta_fields as $field) {
            register_meta('user', $field, array(
                'type' => 'string',
                'description' => sprintf(__('Environmental Platform %s', 'environmental-platform-core'), $field),
                'single' => true,
                'show_in_rest' => true,
            ));
        }
    }
    
    /**
     * Register user interface shortcodes
     */
    public function register_user_shortcodes() {
        add_shortcode('ep_user_registration', array($this, 'render_registration_form'));
        add_shortcode('ep_user_login', array($this, 'render_login_form'));
        add_shortcode('ep_user_profile', array($this, 'render_user_profile'));
        add_shortcode('ep_user_dashboard', array($this, 'render_user_dashboard'));
        add_shortcode('ep_user_leaderboard', array($this, 'render_user_leaderboard'));
    }
    
    /**
     * Enqueue user management scripts and styles
     */
    public function enqueue_user_scripts() {
        wp_enqueue_script(
            'ep-user-management',
            EP_CORE_PLUGIN_URL . 'assets/js/user-management.js',
            array('jquery'),
            EP_CORE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ep-user-management-css',
            EP_CORE_PLUGIN_URL . 'assets/css/user-management.css',
            array(),
            EP_CORE_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('ep-user-management', 'ep_user_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_user_nonce'),
            'messages' => array(
                'registration_success' => __('Registration successful! Please check your email for verification.', 'environmental-platform-core'),
                'login_success' => __('Login successful! Redirecting...', 'environmental-platform-core'),
                'profile_updated' => __('Profile updated successfully!', 'environmental-platform-core'),
                'error_general' => __('An error occurred. Please try again.', 'environmental-platform-core'),
                'error_validation' => __('Please check your input and try again.', 'environmental-platform-core'),
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'user-edit.php' || $hook === 'profile.php' || $hook === 'user-new.php') {
            wp_enqueue_script(
                'ep-admin-user',
                EP_CORE_PLUGIN_URL . 'assets/js/admin-user.js',
                array('jquery'),
                EP_CORE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ep-admin-user-css',
                EP_CORE_PLUGIN_URL . 'assets/css/admin-user.css',
                array(),
                EP_CORE_VERSION
            );
        }
    }
    
    /**
     * AJAX handler for user registration
     */
    public function ajax_register_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_user_nonce')) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        // Sanitize input data
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $user_type = sanitize_text_field($_POST['user_type']);
        $environmental_interests = isset($_POST['environmental_interests']) ? 
            array_map('sanitize_text_field', $_POST['environmental_interests']) : array();
        
        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'environmental-platform-core')
            ));
        }
        
        // Check if user already exists
        if (username_exists($username) || email_exists($email)) {
            wp_send_json_error(array(
                'message' => __('Username or email already exists.', 'environmental-platform-core')
            ));
        }
        
        // Create WordPress user
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => $this->map_user_type_to_role($user_type)
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
        }
        
        // Save additional environmental platform data
        update_user_meta($user_id, 'ep_environmental_interests', $environmental_interests);
        update_user_meta($user_id, 'ep_green_points', 100); // Welcome bonus
        update_user_meta($user_id, 'ep_level', 1);
        update_user_meta($user_id, 'ep_total_environmental_score', 0);
        update_user_meta($user_id, 'ep_carbon_footprint_kg', 0);
        update_user_meta($user_id, 'ep_is_verified', false);
        
        // Create user in environmental platform database
        $this->create_ep_user($user_id, $user_data, $user_type, $environmental_interests);
        
        // Send welcome email with verification link
        $this->send_welcome_email($user_id);
        
        wp_send_json_success(array(
            'message' => __('Registration successful! Please check your email for verification instructions.', 'environmental-platform-core'),
            'user_id' => $user_id
        ));
    }
    
    /**
     * AJAX handler for user login
     */
    public function ajax_login_user() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ep_user_nonce')) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        // Attempt to authenticate user
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array(
                'message' => __('Invalid username or password.', 'environmental-platform-core')
            ));
        }
        
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Update last login time in EP database
        $this->update_user_last_login($user->ID);
        
        // Get user dashboard data
        $dashboard_data = $this->get_user_dashboard_data($user->ID);
        
        wp_send_json_success(array(
            'message' => __('Login successful!', 'environmental-platform-core'),
            'redirect_url' => $this->get_user_dashboard_url($user->ID),
            'user_data' => $dashboard_data
        ));
    }
    
    /**
     * AJAX handler for updating user profile
     */
    public function ajax_update_user_profile() {
        // Verify nonce and user authentication
        if (!wp_verify_nonce($_POST['nonce'], 'ep_user_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        $user_id = get_current_user_id();
        $updated_fields = array();
        
        // Update basic WordPress user data
        $user_data = array('ID' => $user_id);
        
        if (isset($_POST['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($_POST['first_name']);
            $updated_fields[] = 'first_name';
        }
        
        if (isset($_POST['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($_POST['last_name']);
            $updated_fields[] = 'last_name';
        }
        
        if (isset($_POST['bio'])) {
            $user_data['description'] = sanitize_textarea_field($_POST['bio']);
            update_user_meta($user_id, 'ep_bio', sanitize_textarea_field($_POST['bio']));
            $updated_fields[] = 'bio';
        }
        
        // Update WordPress user
        if (count($user_data) > 1) {
            wp_update_user($user_data);
        }
        
        // Update environmental platform specific data
        $ep_fields = array(
            'ep_environmental_interests' => 'environmental_interests',
            'ep_location_city' => 'location_city',
            'ep_location_district' => 'location_district',
            'ep_notification_preferences' => 'notification_preferences',
            'ep_privacy_settings' => 'privacy_settings',
            'ep_preferred_exchange_radius' => 'preferred_exchange_radius'
        );
        
        foreach ($ep_fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                $value = $_POST[$post_key];
                if (is_array($value)) {
                    $value = array_map('sanitize_text_field', $value);
                } else {
                    $value = sanitize_text_field($value);
                }
                update_user_meta($user_id, $meta_key, $value);
                $updated_fields[] = $post_key;
            }
        }
        
        // Sync updates to environmental platform database
        $this->sync_wp_user_update_to_ep($user_id);
        
        wp_send_json_success(array(
            'message' => __('Profile updated successfully!', 'environmental-platform-core'),
            'updated_fields' => $updated_fields
        ));
    }
    
    /**
     * AJAX handler for getting user dashboard data
     */
    public function ajax_get_user_dashboard_data() {
        // Verify nonce and user authentication
        if (!wp_verify_nonce($_POST['nonce'], 'ep_user_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        $user_id = get_current_user_id();
        $dashboard_data = $this->get_user_dashboard_data($user_id);
        
        wp_send_json_success($dashboard_data);
    }
    
    /**
     * Map user type to WordPress role
     */
    private function map_user_type_to_role($user_type) {
        $type_role_map = array(
            'admin' => 'environmental_admin',
            'moderator' => 'environmental_moderator',
            'content_creator' => 'content_creator',
            'business_partner' => 'business_partner',
            'organization' => 'organization_member',
            'regular_user' => 'eco_user',
            'eco_enthusiast' => 'eco_user'
        );
        
        return isset($type_role_map[$user_type]) ? $type_role_map[$user_type] : 'eco_user';
    }
    
    /**
     * Create user in environmental platform database
     */
    private function create_ep_user($wp_user_id, $user_data, $user_type, $environmental_interests) {
        global $wpdb;
        
        // Insert into environmental platform users table
        $ep_user_data = array(
            'username' => $user_data['user_login'],
            'email' => $user_data['user_email'],
            'password_hash' => wp_hash_password($user_data['user_pass']),
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'full_name' => $user_data['display_name'],
            'user_type' => $user_type,
            'green_points' => 100,
            'level' => 1,
            'total_environmental_score' => 0,
            'interests' => json_encode($environmental_interests),
            'notification_preferences' => json_encode(array(
                'email' => true,
                'push' => true,
                'sms' => false
            )),
            'privacy_settings' => json_encode(array(
                'profile_public' => true,
                'show_location' => false
            )),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $wpdb->insert('users', $ep_user_data);
        $ep_user_id = $wpdb->insert_id;
        
        // Store EP user ID in WordPress user meta
        update_user_meta($wp_user_id, 'ep_user_id', $ep_user_id);
        
        return $ep_user_id;
    }
    
    /**
     * Sync new WordPress user to environmental platform
     */
    public function sync_new_wp_user_to_ep($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        // Check if already synced
        if (get_user_meta($user_id, 'ep_user_id', true)) {
            return;
        }
        
        // Create in EP database
        $this->create_ep_user($user_id, array(
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_pass' => '', // Password already hashed in WP
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name
        ), 'regular_user', array());
    }
    
    /**
     * Sync WordPress user update to environmental platform
     */
    public function sync_wp_user_update_to_ep($user_id) {
        global $wpdb;
        
        $ep_user_id = get_user_meta($user_id, 'ep_user_id', true);
        if (!$ep_user_id) return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        // Prepare update data
        $update_data = array(
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->display_name,
            'bio' => get_user_meta($user_id, 'ep_bio', true),
            'interests' => json_encode(get_user_meta($user_id, 'ep_environmental_interests', true) ?: array()),
            'updated_at' => current_time('mysql')
        );
        
        // Update in EP database
        $wpdb->update('users', $update_data, array('user_id' => $ep_user_id));
    }
    
    /**
     * Handle user deletion
     */
    public function handle_user_deletion($user_id) {
        global $wpdb;
        
        $ep_user_id = get_user_meta($user_id, 'ep_user_id', true);
        if ($ep_user_id) {
            // Mark as deleted in EP database instead of hard delete to preserve data integrity
            $wpdb->update('users', 
                array('status' => 'deleted', 'updated_at' => current_time('mysql')), 
                array('user_id' => $ep_user_id)
            );
        }
    }
    
    /**
     * Handle user login
     */
    public function handle_user_login($user_login, $user) {
        $this->update_user_last_login($user->ID);
    }
    
    /**
     * Handle user logout
     */
    public function handle_user_logout() {
        $user_id = get_current_user_id();
        if ($user_id) {
            // Update last logout time in EP database
            global $wpdb;
            $ep_user_id = get_user_meta($user_id, 'ep_user_id', true);
            if ($ep_user_id) {
                $wpdb->update('users', 
                    array('last_active' => current_time('mysql')), 
                    array('user_id' => $ep_user_id)
                );
            }
        }
    }
    
    /**
     * Update user last login time
     */
    private function update_user_last_login($user_id) {
        global $wpdb;
        
        $ep_user_id = get_user_meta($user_id, 'ep_user_id', true);
        if ($ep_user_id) {
            $wpdb->update('users', 
                array('last_active' => current_time('mysql')), 
                array('user_id' => $ep_user_id)
            );
        }
    }
    
    /**
     * Get user dashboard data
     */
    public function get_user_dashboard_data($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) return false;
        
        $ep_user_id = get_user_meta($user_id, 'ep_user_id', true);
        
        // Get environmental platform data
        $ep_data = array();
        if ($ep_user_id) {
            $ep_data = $wpdb->get_row($wpdb->prepare(
                "SELECT green_points, level, total_environmental_score, carbon_footprint_kg, 
                        exchange_rating, total_exchanges, waste_reports_count, quiz_completions_count,
                        articles_count, environmental_activities_count
                 FROM users 
                 WHERE user_id = %d",
                $ep_user_id
            ), ARRAY_A);
        }
        
        // Get recent activities
        $recent_activities = array();
        if ($ep_user_id) {
            $recent_activities = $wpdb->get_results($wpdb->prepare(
                "SELECT activity_type, description, points_earned, created_at
                 FROM user_activities 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                $ep_user_id
            ), ARRAY_A);
        }
        
        // Get achievements
        $achievements = array();
        if ($ep_user_id) {
            $achievements = $wpdb->get_results($wpdb->prepare(
                "SELECT a.achievement_id, a.name, a.description, a.badge_icon, ua.earned_at
                 FROM user_achievements ua
                 JOIN achievements a ON ua.achievement_id = a.achievement_id
                 WHERE ua.user_id = %d
                 ORDER BY ua.earned_at DESC",
                $ep_user_id
            ), ARRAY_A);
        }
        
        return array(
            'user_info' => array(
                'wp_user_id' => $user_id,
                'ep_user_id' => $ep_user_id,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'bio' => get_user_meta($user_id, 'ep_bio', true),
                'avatar_url' => get_avatar_url($user_id, array('size' => 150)),
                'role' => $user->roles[0] ?? 'eco_user'
            ),
            'environmental_data' => $ep_data ?: array(
                'green_points' => 0,
                'level' => 1,
                'total_environmental_score' => 0,
                'carbon_footprint_kg' => 0,
                'exchange_rating' => 0,
                'total_exchanges' => 0,
                'waste_reports_count' => 0,
                'quiz_completions_count' => 0,
                'articles_count' => 0,
                'environmental_activities_count' => 0
            ),
            'recent_activities' => $recent_activities,
            'achievements' => $achievements,
            'progress' => $this->calculate_user_progress($ep_data),
            'next_level_requirements' => $this->get_next_level_requirements($ep_data['level'] ?? 1)
        );
    }
    
    /**
     * Calculate user progress for next level
     */
    private function calculate_user_progress($ep_data) {
        if (!$ep_data || !isset($ep_data['level'])) {
            return array('percentage' => 0, 'points_needed' => 500);
        }
        
        $current_level = intval($ep_data['level']);
        $current_points = intval($ep_data['green_points']);
        
        // Calculate points needed for next level (exponential growth)
        $next_level_points = $current_level * 500 + pow($current_level, 2) * 100;
        $current_level_points = ($current_level - 1) * 500 + pow($current_level - 1, 2) * 100;
        
        $points_in_current_level = $current_points - $current_level_points;
        $points_needed_for_next = $next_level_points - $current_level_points;
        
        $percentage = $points_needed_for_next > 0 ? 
            min(100, ($points_in_current_level / $points_needed_for_next) * 100) : 100;
        
        return array(
            'percentage' => round($percentage, 1),
            'points_needed' => max(0, $next_level_points - $current_points),
            'current_level_points' => $current_level_points,
            'next_level_points' => $next_level_points
        );
    }
    
    /**
     * Get next level requirements
     */
    private function get_next_level_requirements($current_level) {
        $requirements = array(
            'green_points' => $current_level * 500 + pow($current_level, 2) * 100,
            'activities' => array(),
            'badges' => array()
        );
        
        // Define level-specific requirements
        switch ($current_level) {
            case 1:
                $requirements['activities'] = array(
                    __('Complete your profile', 'environmental-platform-core'),
                    __('Submit your first environmental data', 'environmental-platform-core'),
                    __('Participate in a quiz', 'environmental-platform-core')
                );
                break;
            case 2:
                $requirements['activities'] = array(
                    __('Share 3 environmental tips', 'environmental-platform-core'),
                    __('Complete 5 quizzes', 'environmental-platform-core'),
                    __('Make your first item exchange', 'environmental-platform-core')
                );
                break;
            default:
                $requirements['activities'] = array(
                    sprintf(__('Earn %d more green points', 'environmental-platform-core'), $requirements['green_points']),
                    __('Continue participating in environmental activities', 'environmental-platform-core')
                );
        }
        
        return $requirements;
    }
    
    /**
     * Get user dashboard URL
     */
    private function get_user_dashboard_url($user_id) {
        $user = get_userdata($user_id);
        $role = $user->roles[0] ?? 'eco_user';
        
        // Custom dashboard URLs based on user role
        switch ($role) {
            case 'environmental_admin':
                return admin_url('admin.php?page=environmental-platform');
            case 'environmental_moderator':
                return admin_url('admin.php?page=ep-content-moderation');
            case 'business_partner':
                return home_url('/business-dashboard/');
            case 'organization_member':
                return home_url('/organization-dashboard/');
            default:
                return home_url('/user-dashboard/');
        }
    }
    
    /**
     * Send welcome email to new user
     */
    private function send_welcome_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = sprintf(__('Welcome to %s - Environmental Platform', 'environmental-platform-core'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Hello %s,

Welcome to our Environmental Platform! We\'re excited to have you join our community of eco-conscious individuals working together to make a positive impact on our planet.

Your account details:
Username: %s
Email: %s

To get started:
1. Complete your profile with your environmental interests
2. Take our environmental impact quiz
3. Start earning green points by participating in activities
4. Connect with other members in our community

Visit your dashboard: %s

If you have any questions, please don\'t hesitate to contact our support team.

Best regards,
The Environmental Platform Team', 'environmental-platform-core'),
            $user->display_name,
            $user->user_login,
            $user->user_email,
            $this->get_user_dashboard_url($user_id)
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Handle custom form submissions
     */
    public function handle_custom_forms() {
        // Handle custom registration form
        if (isset($_POST['ep_register_submit'])) {
            $this->handle_registration_form_submission();
        }
        
        // Handle custom login form  
        if (isset($_POST['ep_login_submit'])) {
            $this->handle_login_form_submission();
        }
    }
    
    /**
     * Handle registration form submission
     */
    private function handle_registration_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['ep_register_nonce'], 'ep_register_form')) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        // Process registration (similar to AJAX handler but with page redirect)
        // Implementation would be similar to ajax_register_user but with different response handling
    }
    
    /**
     * Handle login form submission
     */
    private function handle_login_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['ep_login_nonce'], 'ep_login_form')) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        // Process login (similar to AJAX handler but with page redirect)
        // Implementation would be similar to ajax_login_user but with different response handling
    }
    
    /**
     * Custom login redirect
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        if (is_wp_error($user)) {
            return $redirect_to;
        }
        
        return $this->get_user_dashboard_url($user->ID);
    }
    
    /**
     * Add environmental profile fields to WordPress user profile
     */
    public function add_environmental_profile_fields($user) {
        $ep_user_id = get_user_meta($user->ID, 'ep_user_id', true);
        $green_points = get_user_meta($user->ID, 'ep_green_points', true) ?: 0;
        $level = get_user_meta($user->ID, 'ep_level', true) ?: 1;
        $environmental_interests = get_user_meta($user->ID, 'ep_environmental_interests', true) ?: array();
        $bio = get_user_meta($user->ID, 'ep_bio', true);
        $location_city = get_user_meta($user->ID, 'ep_location_city', true);
        $carbon_footprint = get_user_meta($user->ID, 'ep_carbon_footprint_kg', true) ?: 0;
        $is_verified = get_user_meta($user->ID, 'ep_is_verified', true);
        
        ?>
        <h2><?php _e('Environmental Platform Profile', 'environmental-platform-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="ep_user_id"><?php _e('EP User ID', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="text" name="ep_user_id" id="ep_user_id" value="<?php echo esc_attr($ep_user_id); ?>" class="regular-text" readonly />
                    <p class="description"><?php _e('Environmental Platform User ID (read-only)', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_green_points"><?php _e('Green Points', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="number" name="ep_green_points" id="ep_green_points" value="<?php echo esc_attr($green_points); ?>" class="regular-text" />
                    <p class="description"><?php _e('User\'s earned green points', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_level"><?php _e('Environmental Level', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="number" name="ep_level" id="ep_level" value="<?php echo esc_attr($level); ?>" class="regular-text" min="1" />
                    <p class="description"><?php _e('User\'s environmental level', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_bio"><?php _e('Environmental Bio', 'environmental-platform-core'); ?></label></th>
                <td>
                    <textarea name="ep_bio" id="ep_bio" rows="5" cols="30"><?php echo esc_textarea($bio); ?></textarea>
                    <p class="description"><?php _e('Tell us about your environmental interests and activities', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_location_city"><?php _e('City', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="text" name="ep_location_city" id="ep_location_city" value="<?php echo esc_attr($location_city); ?>" class="regular-text" />
                    <p class="description"><?php _e('City for local environmental activities', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_carbon_footprint"><?php _e('Carbon Footprint (kg CO2)', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="number" name="ep_carbon_footprint" id="ep_carbon_footprint" value="<?php echo esc_attr($carbon_footprint); ?>" class="regular-text" step="0.01" />
                    <p class="description"><?php _e('Monthly carbon footprint in kilograms', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ep_is_verified"><?php _e('Verified User', 'environmental-platform-core'); ?></label></th>
                <td>
                    <input type="checkbox" name="ep_is_verified" id="ep_is_verified" value="1" <?php checked($is_verified, true); ?> />
                    <label for="ep_is_verified"><?php _e('Mark as verified environmental contributor', 'environmental-platform-core'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="ep_environmental_interests"><?php _e('Environmental Interests', 'environmental-platform-core'); ?></label></th>
                <td>
                    <?php
                    $all_interests = array(
                        'renewable_energy' => __('Renewable Energy', 'environmental-platform-core'),
                        'waste_reduction' => __('Waste Reduction', 'environmental-platform-core'),
                        'recycling' => __('Recycling', 'environmental-platform-core'),
                        'sustainable_transport' => __('Sustainable Transportation', 'environmental-platform-core'),
                        'organic_farming' => __('Organic Farming', 'environmental-platform-core'),
                        'climate_change' => __('Climate Change', 'environmental-platform-core'),
                        'conservation' => __('Wildlife Conservation', 'environmental-platform-core'),
                        'green_technology' => __('Green Technology', 'environmental-platform-core'),
                        'eco_products' => __('Eco-friendly Products', 'environmental-platform-core'),
                        'water_conservation' => __('Water Conservation', 'environmental-platform-core')
                    );
                    
                    foreach ($all_interests as $key => $label) {
                        $checked = in_array($key, $environmental_interests) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="ep_environmental_interests[]" value="' . esc_attr($key) . '" ' . $checked . ' /> ';
                        echo esc_html($label);
                        echo '</label>';
                    }
                    ?>
                    <p class="description"><?php _e('Select areas of environmental interest', 'environmental-platform-core'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save environmental profile fields
     */
    public function save_environmental_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        // Save environmental platform fields
        $fields = array(
            'ep_green_points' => 'intval',
            'ep_level' => 'intval', 
            'ep_bio' => 'sanitize_textarea_field',
            'ep_location_city' => 'sanitize_text_field',
            'ep_carbon_footprint' => 'floatval',
            'ep_is_verified' => 'boolval'
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $value = $sanitize_func($_POST[$field]);
                update_user_meta($user_id, $field, $value);
            }
        }
        
        // Handle environmental interests array
        if (isset($_POST['ep_environmental_interests'])) {
            $interests = array_map('sanitize_text_field', $_POST['ep_environmental_interests']);
            update_user_meta($user_id, 'ep_environmental_interests', $interests);
        } else {
            update_user_meta($user_id, 'ep_environmental_interests', array());
        }
        
        // Sync changes to environmental platform database
        $this->sync_wp_user_update_to_ep($user_id);
    }
    
    // Shortcode render methods will be implemented in the next part...
    
    /**
     * Render registration form shortcode
     */
    public function render_registration_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_social' => 'true',
            'user_type_selection' => 'true'
        ), $atts);
        
        ob_start();
        include EP_CORE_PLUGIN_DIR . 'templates/user/registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render login form shortcode
     */
    public function render_login_form($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_social' => 'true'
        ), $atts);
        
        ob_start();
        include EP_CORE_PLUGIN_DIR . 'templates/user/login-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render user profile shortcode
     */
    public function render_user_profile($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your profile.', 'environmental-platform-core') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id()
        ), $atts);
        
        ob_start();
        include EP_CORE_PLUGIN_DIR . 'templates/user/user-profile.php';
        return ob_get_clean();
    }
    
    /**
     * Render user dashboard shortcode
     */
    public function render_user_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access your dashboard.', 'environmental-platform-core') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id()
        ), $atts);
        
        ob_start();
        include EP_CORE_PLUGIN_DIR . 'templates/user/user-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Render user leaderboard shortcode
     */
    public function render_user_leaderboard($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'type' => 'green_points'
        ), $atts);
        
        ob_start();
        include EP_CORE_PLUGIN_DIR . 'templates/user/user-leaderboard.php';
        return ob_get_clean();
    }
}

// Initialize the user management system
new EP_User_Management();
