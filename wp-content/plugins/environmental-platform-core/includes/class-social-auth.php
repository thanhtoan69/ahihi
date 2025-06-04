<?php
/**
 * Social Media Authentication Integration
 * 
 * Phase 31: User Management & Authentication - Social Login
 * Handles Facebook, Google, Twitter, and other social media login integrations
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EP_Social_Auth {
    
    private $providers;
    private $settings;
    
    public function __construct() {
        // Initialize social auth hooks
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_social_scripts'));
        
        // AJAX handlers for social authentication
        add_action('wp_ajax_nopriv_ep_social_login', array($this, 'ajax_social_login'));
        add_action('wp_ajax_ep_social_connect', array($this, 'ajax_social_connect'));
        add_action('wp_ajax_ep_social_disconnect', array($this, 'ajax_social_disconnect'));
        
        // Social authentication callback handlers
        add_action('wp_loaded', array($this, 'handle_social_callbacks'));
        
        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Define supported social providers
        $this->providers = array(
            'facebook' => array(
                'name' => 'Facebook',
                'color' => '#1877F2',
                'icon' => 'fab fa-facebook-f',
                'enabled' => false
            ),
            'google' => array(
                'name' => 'Google',
                'color' => '#DB4437',
                'icon' => 'fab fa-google',
                'enabled' => false
            ),
            'twitter' => array(
                'name' => 'Twitter',
                'color' => '#1DA1F2',
                'icon' => 'fab fa-twitter',
                'enabled' => false
            ),
            'linkedin' => array(
                'name' => 'LinkedIn',
                'color' => '#0A66C2',
                'icon' => 'fab fa-linkedin-in',
                'enabled' => false
            ),
            'github' => array(
                'name' => 'GitHub',
                'color' => '#333333',
                'icon' => 'fab fa-github',
                'enabled' => false
            ),
            'discord' => array(
                'name' => 'Discord',
                'color' => '#5865F2',
                'icon' => 'fab fa-discord',
                'enabled' => false
            )
        );
        
        // Load settings
        $this->load_settings();
    }
    
    /**
     * Initialize social authentication
     */
    public function init() {
        // Register shortcodes
        add_shortcode('ep_social_login_buttons', array($this, 'render_social_login_buttons'));
        add_shortcode('ep_social_connect_buttons', array($this, 'render_social_connect_buttons'));
        
        // Add social login to WordPress login form
        add_action('login_form', array($this, 'add_social_login_to_wp_form'));
        add_action('register_form', array($this, 'add_social_login_to_wp_form'));
    }
    
    /**
     * Load social authentication settings
     */
    private function load_settings() {
        $this->settings = get_option('ep_social_auth_settings', array());
        
        // Update provider enabled status based on settings
        foreach ($this->providers as $provider => &$config) {
            $config['enabled'] = !empty($this->settings[$provider]['app_id']) && 
                                !empty($this->settings[$provider]['app_secret']);
            $config['app_id'] = $this->settings[$provider]['app_id'] ?? '';
            $config['app_secret'] = $this->settings[$provider]['app_secret'] ?? '';
        }
    }
    
    /**
     * Enqueue social authentication scripts
     */
    public function enqueue_social_scripts() {
        wp_enqueue_script(
            'ep-social-auth',
            EP_CORE_PLUGIN_URL . 'assets/js/social-auth.js',
            array('jquery'),
            EP_CORE_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ep-social-auth-css',
            EP_CORE_PLUGIN_URL . 'assets/css/social-auth.css',
            array(),
            EP_CORE_VERSION
        );
        
        // Include FontAwesome for social icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
        
        // Localize script with settings and URLs
        wp_localize_script('ep-social-auth', 'ep_social_auth', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_social_nonce'),
            'callback_url' => home_url('/ep-social-callback/'),
            'providers' => $this->get_enabled_providers(),
            'messages' => array(
                'connecting' => __('Connecting...', 'environmental-platform-core'),
                'success' => __('Successfully connected!', 'environmental-platform-core'),
                'error' => __('Connection failed. Please try again.', 'environmental-platform-core'),
                'login_success' => __('Login successful! Redirecting...', 'environmental-platform-core'),
                'already_connected' => __('This social account is already connected to another user.', 'environmental-platform-core')
            )
        ));
        
        // Load provider-specific SDKs
        $this->enqueue_provider_sdks();
    }
    
    /**
     * Enqueue provider-specific SDKs
     */
    private function enqueue_provider_sdks() {
        // Facebook SDK
        if ($this->providers['facebook']['enabled']) {
            wp_enqueue_script(
                'facebook-sdk',
                'https://connect.facebook.net/en_US/sdk.js',
                array(),
                null,
                true
            );
        }
        
        // Google SDK
        if ($this->providers['google']['enabled']) {
            wp_enqueue_script(
                'google-sdk',
                'https://apis.google.com/js/platform.js',
                array(),
                null,
                true
            );
        }
        
        // Twitter SDK (using OAuth 2.0)
        if ($this->providers['twitter']['enabled']) {
            // Twitter OAuth is handled server-side
        }
    }
    
    /**
     * Get enabled social providers
     */
    private function get_enabled_providers() {
        return array_filter($this->providers, function($provider) {
            return $provider['enabled'];
        });
    }
    
    /**
     * Handle social authentication callbacks
     */
    public function handle_social_callbacks() {
        // Check if this is a social auth callback
        $request_uri = $_SERVER['REQUEST_URI'];
        
        if (strpos($request_uri, '/ep-social-callback/') !== false) {
            $this->process_social_callback();
        }
    }
    
    /**
     * Process social authentication callback
     */
    private function process_social_callback() {
        $provider = sanitize_text_field($_GET['provider'] ?? '');
        $code = sanitize_text_field($_GET['code'] ?? '');
        $state = sanitize_text_field($_GET['state'] ?? '');
        $error = sanitize_text_field($_GET['error'] ?? '');
        
        if (empty($provider) || !isset($this->providers[$provider])) {
            wp_die(__('Invalid social provider', 'environmental-platform-core'));
        }
        
        if (!empty($error)) {
            $this->handle_callback_error($error, $provider);
            return;
        }
        
        if (empty($code)) {
            wp_die(__('Authorization code not received', 'environmental-platform-core'));
        }
        
        // Verify state parameter for CSRF protection
        if (!$this->verify_state($state)) {
            wp_die(__('Invalid state parameter', 'environmental-platform-core'));
        }
        
        try {
            // Exchange authorization code for access token
            $access_token = $this->exchange_code_for_token($provider, $code);
            
            if (!$access_token) {
                throw new Exception(__('Failed to obtain access token', 'environmental-platform-core'));
            }
            
            // Get user profile from social provider
            $social_user = $this->get_social_user_profile($provider, $access_token);
            
            if (!$social_user) {
                throw new Exception(__('Failed to get user profile', 'environmental-platform-core'));
            }
            
            // Process user login/registration
            $this->process_social_user($provider, $social_user, $access_token);
            
        } catch (Exception $e) {
            $this->handle_callback_error($e->getMessage(), $provider);
        }
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function exchange_code_for_token($provider, $code) {
        switch ($provider) {
            case 'facebook':
                return $this->facebook_exchange_token($code);
            case 'google':
                return $this->google_exchange_token($code);
            case 'twitter':
                return $this->twitter_exchange_token($code);
            case 'linkedin':
                return $this->linkedin_exchange_token($code);
            case 'github':
                return $this->github_exchange_token($code);
            default:
                return false;
        }
    }
    
    /**
     * Facebook token exchange
     */
    private function facebook_exchange_token($code) {
        $app_id = $this->providers['facebook']['app_id'];
        $app_secret = $this->providers['facebook']['app_secret'];
        $redirect_uri = home_url('/ep-social-callback/?provider=facebook');
        
        $url = 'https://graph.facebook.com/v12.0/oauth/access_token';
        $params = array(
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code
        );
        
        $response = wp_remote_post($url, array(
            'body' => $params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['access_token'] ?? false;
    }
    
    /**
     * Google token exchange
     */
    private function google_exchange_token($code) {
        $client_id = $this->providers['google']['app_id'];
        $client_secret = $this->providers['google']['app_secret'];
        $redirect_uri = home_url('/ep-social-callback/?provider=google');
        
        $url = 'https://oauth2.googleapis.com/token';
        $params = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
            'code' => $code
        );
        
        $response = wp_remote_post($url, array(
            'body' => $params,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['access_token'] ?? false;
    }
    
    /**
     * Get social user profile
     */
    private function get_social_user_profile($provider, $access_token) {
        switch ($provider) {
            case 'facebook':
                return $this->get_facebook_user_profile($access_token);
            case 'google':
                return $this->get_google_user_profile($access_token);
            case 'twitter':
                return $this->get_twitter_user_profile($access_token);
            case 'linkedin':
                return $this->get_linkedin_user_profile($access_token);
            case 'github':
                return $this->get_github_user_profile($access_token);
            default:
                return false;
        }
    }
    
    /**
     * Get Facebook user profile
     */
    private function get_facebook_user_profile($access_token) {
        $url = 'https://graph.facebook.com/v12.0/me?fields=id,name,email,first_name,last_name,picture&access_token=' . $access_token;
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $user_data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$user_data || isset($user_data['error'])) {
            return false;
        }
        
        return array(
            'provider' => 'facebook',
            'provider_id' => $user_data['id'],
            'email' => $user_data['email'] ?? '',
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'display_name' => $user_data['name'] ?? '',
            'avatar_url' => $user_data['picture']['data']['url'] ?? '',
            'profile_url' => 'https://facebook.com/' . $user_data['id']
        );
    }
    
    /**
     * Get Google user profile
     */
    private function get_google_user_profile($access_token) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $user_data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$user_data || isset($user_data['error'])) {
            return false;
        }
        
        return array(
            'provider' => 'google',
            'provider_id' => $user_data['id'],
            'email' => $user_data['email'] ?? '',
            'first_name' => $user_data['given_name'] ?? '',
            'last_name' => $user_data['family_name'] ?? '',
            'display_name' => $user_data['name'] ?? '',
            'avatar_url' => $user_data['picture'] ?? '',
            'profile_url' => $user_data['link'] ?? ''
        );
    }
    
    /**
     * Process social user login/registration
     */
    private function process_social_user($provider, $social_user, $access_token) {
        global $wpdb;
        
        // Check if social account is already connected
        $existing_connection = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM user_social_accounts 
             WHERE provider = %s AND provider_user_id = %s",
            $provider,
            $social_user['provider_id']
        ));
        
        if ($existing_connection) {
            // User exists, log them in
            $user_id = $existing_connection->user_id;
            $user = get_userdata($user_id);
            
            if ($user && $user->exists()) {
                $this->login_user($user, $provider);
                return;
            }
        }
        
        // Check if user exists by email
        $existing_user = get_user_by('email', $social_user['email']);
        
        if ($existing_user) {
            // Connect social account to existing user
            $this->connect_social_account($existing_user->ID, $provider, $social_user, $access_token);
            $this->login_user($existing_user, $provider);
        } else {
            // Create new user
            $new_user_id = $this->create_user_from_social($social_user);
            
            if (!is_wp_error($new_user_id)) {
                $this->connect_social_account($new_user_id, $provider, $social_user, $access_token);
                $new_user = get_userdata($new_user_id);
                $this->login_user($new_user, $provider);
            } else {
                throw new Exception($new_user_id->get_error_message());
            }
        }
    }
    
    /**
     * Create WordPress user from social profile
     */
    private function create_user_from_social($social_user) {
        // Generate unique username if email is not available
        $username = !empty($social_user['email']) ? 
            sanitize_user($social_user['email']) : 
            sanitize_user($social_user['display_name'] . '_' . $social_user['provider_id']);
        
        // Ensure username is unique
        if (username_exists($username)) {
            $username = $username . '_' . wp_rand(1000, 9999);
        }
        
        $user_data = array(
            'user_login' => $username,
            'user_email' => $social_user['email'],
            'user_pass' => wp_generate_password(12),
            'first_name' => $social_user['first_name'],
            'last_name' => $social_user['last_name'],
            'display_name' => $social_user['display_name'],
            'role' => 'eco_user'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (!is_wp_error($user_id)) {
            // Set initial environmental platform data
            update_user_meta($user_id, 'ep_green_points', 150); // Social signup bonus
            update_user_meta($user_id, 'ep_level', 1);
            update_user_meta($user_id, 'ep_total_environmental_score', 0);
            update_user_meta($user_id, 'ep_is_verified', false);
            
            // Save avatar URL if provided
            if (!empty($social_user['avatar_url'])) {
                update_user_meta($user_id, 'ep_avatar_url', $social_user['avatar_url']);
            }
            
            // Create user in environmental platform database
            $user_management = new EP_User_Management();
            // This would call the create_ep_user method from EP_User_Management
        }
        
        return $user_id;
    }
    
    /**
     * Connect social account to user
     */
    private function connect_social_account($user_id, $provider, $social_user, $access_token) {
        global $wpdb;
        
        // Store social account connection
        $wpdb->replace('user_social_accounts', array(
            'user_id' => $user_id,
            'provider' => $provider,
            'provider_user_id' => $social_user['provider_id'],
            'provider_email' => $social_user['email'],
            'provider_name' => $social_user['display_name'],
            'provider_avatar' => $social_user['avatar_url'],
            'provider_profile_url' => $social_user['profile_url'],
            'access_token' => wp_hash($access_token), // Store hashed token for security
            'connected_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        
        // Update user avatar if not set
        $current_avatar = get_user_meta($user_id, 'ep_avatar_url', true);
        if (empty($current_avatar) && !empty($social_user['avatar_url'])) {
            update_user_meta($user_id, 'ep_avatar_url', $social_user['avatar_url']);
        }
    }
    
    /**
     * Login user after social authentication
     */
    private function login_user($user, $provider) {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        // Update last login
        $user_management = new EP_User_Management();
        // This would call the handle_user_login method
        
        // Redirect to appropriate page
        $redirect_url = $this->get_social_login_redirect_url($user);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get redirect URL after social login
     */
    private function get_social_login_redirect_url($user) {
        // Check if there's a stored redirect URL
        $redirect = get_transient('ep_social_redirect_' . session_id());
        if ($redirect) {
            delete_transient('ep_social_redirect_' . session_id());
            return $redirect;
        }
        
        // Default redirect based on user role
        $role = $user->roles[0] ?? 'eco_user';
        
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
     * Handle callback errors
     */
    private function handle_callback_error($error, $provider) {
        $error_message = sprintf(
            __('Social login failed for %s: %s', 'environmental-platform-core'),
            $this->providers[$provider]['name'],
            $error
        );
        
        // Redirect to login page with error
        $login_url = wp_login_url();
        $redirect_url = add_query_arg(array(
            'ep_social_error' => urlencode($error_message)
        ), $login_url);
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Verify state parameter for CSRF protection
     */
    private function verify_state($state) {
        $stored_state = get_transient('ep_social_state_' . session_id());
        delete_transient('ep_social_state_' . session_id());
        
        return $state === $stored_state;
    }
    
    /**
     * Generate authorization URL for social provider
     */
    public function get_authorization_url($provider, $redirect_after = '') {
        if (!isset($this->providers[$provider]) || !$this->providers[$provider]['enabled']) {
            return false;
        }
        
        // Store redirect URL for after login
        if ($redirect_after) {
            set_transient('ep_social_redirect_' . session_id(), $redirect_after, HOUR_IN_SECONDS);
        }
        
        // Generate and store state for CSRF protection
        $state = wp_generate_password(32, false);
        set_transient('ep_social_state_' . session_id(), $state, HOUR_IN_SECONDS);
        
        switch ($provider) {
            case 'facebook':
                return $this->get_facebook_auth_url($state);
            case 'google':
                return $this->get_google_auth_url($state);
            case 'twitter':
                return $this->get_twitter_auth_url($state);
            case 'linkedin':
                return $this->get_linkedin_auth_url($state);
            case 'github':
                return $this->get_github_auth_url($state);
            default:
                return false;
        }
    }
    
    /**
     * Get Facebook authorization URL
     */
    private function get_facebook_auth_url($state) {
        $app_id = $this->providers['facebook']['app_id'];
        $redirect_uri = home_url('/ep-social-callback/?provider=facebook');
        $scope = 'email,public_profile';
        
        return 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query(array(
            'client_id' => $app_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'state' => $state,
            'response_type' => 'code'
        ));
    }
    
    /**
     * Get Google authorization URL
     */
    private function get_google_auth_url($state) {
        $client_id = $this->providers['google']['app_id'];
        $redirect_uri = home_url('/ep-social-callback/?provider=google');
        $scope = 'openid email profile';
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'state' => $state,
            'response_type' => 'code',
            'access_type' => 'offline'
        ));
    }
    
    /**
     * AJAX handler for social login
     */
    public function ajax_social_login() {
        if (!wp_verify_nonce($_POST['nonce'], 'ep_social_nonce')) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        $redirect_after = sanitize_url($_POST['redirect_after'] ?? '');
        
        $auth_url = $this->get_authorization_url($provider, $redirect_after);
        
        if ($auth_url) {
            wp_send_json_success(array(
                'auth_url' => $auth_url,
                'provider' => $provider
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Social provider not available', 'environmental-platform-core')
            ));
        }
    }
    
    /**
     * AJAX handler for connecting social account to existing user
     */
    public function ajax_social_connect() {
        if (!wp_verify_nonce($_POST['nonce'], 'ep_social_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        $auth_url = $this->get_authorization_url($provider);
        
        if ($auth_url) {
            wp_send_json_success(array(
                'auth_url' => $auth_url,
                'provider' => $provider
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Social provider not available', 'environmental-platform-core')
            ));
        }
    }
    
    /**
     * AJAX handler for disconnecting social account
     */
    public function ajax_social_disconnect() {
        if (!wp_verify_nonce($_POST['nonce'], 'ep_social_nonce') || !is_user_logged_in()) {
            wp_die(__('Security check failed', 'environmental-platform-core'));
        }
        
        global $wpdb;
        
        $provider = sanitize_text_field($_POST['provider']);
        $user_id = get_current_user_id();
        
        $deleted = $wpdb->delete('user_social_accounts', array(
            'user_id' => $user_id,
            'provider' => $provider
        ));
        
        if ($deleted) {
            wp_send_json_success(array(
                'message' => sprintf(__('%s account disconnected successfully', 'environmental-platform-core'), 
                    $this->providers[$provider]['name'])
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to disconnect social account', 'environmental-platform-core')
            ));
        }
    }
    
    /**
     * Render social login buttons shortcode
     */
    public function render_social_login_buttons($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
            'providers' => 'all',
            'style' => 'buttons'
        ), $atts);
        
        $enabled_providers = $this->get_enabled_providers();
        
        if (empty($enabled_providers)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="ep-social-login-container">
            <div class="ep-social-login-title">
                <?php _e('Or continue with:', 'environmental-platform-core'); ?>
            </div>
            <div class="ep-social-login-buttons">
                <?php foreach ($enabled_providers as $provider => $config): ?>
                    <button type="button" 
                            class="ep-social-login-btn ep-social-<?php echo esc_attr($provider); ?>"
                            data-provider="<?php echo esc_attr($provider); ?>"
                            data-redirect="<?php echo esc_attr($atts['redirect']); ?>"
                            style="background-color: <?php echo esc_attr($config['color']); ?>">
                        <i class="<?php echo esc_attr($config['icon']); ?>"></i>
                        <span><?php printf(__('Continue with %s', 'environmental-platform-core'), $config['name']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render social connect buttons for user profiles
     */
    public function render_social_connect_buttons($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        global $wpdb;
        
        $user_id = get_current_user_id();
        $connected_accounts = $wpdb->get_results($wpdb->prepare(
            "SELECT provider, provider_name, provider_avatar, connected_at 
             FROM user_social_accounts 
             WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        $connected_providers = array_column($connected_accounts, 'provider');
        
        ob_start();
        ?>
        <div class="ep-social-connect-container">
            <h3><?php _e('Connected Social Accounts', 'environmental-platform-core'); ?></h3>
            
            <?php foreach ($this->providers as $provider => $config): ?>
                <?php if (!$config['enabled']) continue; ?>
                
                <div class="ep-social-account-item">
                    <div class="ep-social-account-info">
                        <i class="<?php echo esc_attr($config['icon']); ?>" 
                           style="color: <?php echo esc_attr($config['color']); ?>"></i>
                        <span class="ep-social-account-name"><?php echo esc_html($config['name']); ?></span>
                    </div>
                    
                    <?php if (in_array($provider, $connected_providers)): ?>
                        <button type="button" 
                                class="ep-social-disconnect-btn"
                                data-provider="<?php echo esc_attr($provider); ?>">
                            <?php _e('Disconnect', 'environmental-platform-core'); ?>
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                class="ep-social-connect-btn"
                                data-provider="<?php echo esc_attr($provider); ?>">
                            <?php _e('Connect', 'environmental-platform-core'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add social login buttons to WordPress login form
     */
    public function add_social_login_to_wp_form() {
        echo $this->render_social_login_buttons(array());
    }
    
    /**
     * Add admin menu for social authentication settings
     */
    public function add_admin_menu() {
        add_submenu_page(
            'environmental-platform',
            __('Social Authentication', 'environmental-platform-core'),
            __('Social Auth', 'environmental-platform-core'),
            'manage_options',
            'ep-social-auth',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('ep_social_auth_settings', 'ep_social_auth_settings');
        
        add_settings_section(
            'ep_social_auth_providers',
            __('Social Authentication Providers', 'environmental-platform-core'),
            array($this, 'settings_section_callback'),
            'ep_social_auth_settings'
        );
        
        foreach ($this->providers as $provider => $config) {
            add_settings_field(
                "ep_social_auth_{$provider}",
                $config['name'],
                array($this, 'provider_settings_callback'),
                'ep_social_auth_settings',
                'ep_social_auth_providers',
                array('provider' => $provider, 'config' => $config)
            );
        }
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure social authentication providers for the Environmental Platform.', 'environmental-platform-core') . '</p>';
    }
    
    /**
     * Provider settings callback
     */
    public function provider_settings_callback($args) {
        $provider = $args['provider'];
        $config = $args['config'];
        $settings = $this->settings[$provider] ?? array();
        
        ?>
        <div class="ep-social-provider-settings">
            <h4 style="color: <?php echo esc_attr($config['color']); ?>">
                <i class="<?php echo esc_attr($config['icon']); ?>"></i>
                <?php echo esc_html($config['name']); ?>
            </h4>
            
            <table class="form-table">
                <tr>
                    <th><label for="<?php echo esc_attr($provider); ?>_app_id">App ID / Client ID</label></th>
                    <td>
                        <input type="text" 
                               id="<?php echo esc_attr($provider); ?>_app_id"
                               name="ep_social_auth_settings[<?php echo esc_attr($provider); ?>][app_id]" 
                               value="<?php echo esc_attr($settings['app_id'] ?? ''); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo esc_attr($provider); ?>_app_secret">App Secret / Client Secret</label></th>
                    <td>
                        <input type="password" 
                               id="<?php echo esc_attr($provider); ?>_app_secret"
                               name="ep_social_auth_settings[<?php echo esc_attr($provider); ?>][app_secret]" 
                               value="<?php echo esc_attr($settings['app_secret'] ?? ''); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th>Callback URL</th>
                    <td>
                        <code><?php echo esc_html(home_url("/ep-social-callback/?provider={$provider}")); ?></code>
                        <p class="description"><?php _e('Use this URL in your app settings', 'environmental-platform-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Social Authentication Settings', 'environmental-platform-core'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ep_social_auth_settings');
                do_settings_sections('ep_social_auth_settings');
                submit_button();
                ?>
            </form>
            
            <div class="ep-social-auth-help">
                <h2><?php _e('Setup Instructions', 'environmental-platform-core'); ?></h2>
                
                <div class="ep-social-auth-instructions">
                    <h3>Facebook</h3>
                    <ol>
                        <li>Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                        <li>Create a new app or use existing one</li>
                        <li>Add Facebook Login product</li>
                        <li>Set callback URL in OAuth redirect URIs</li>
                        <li>Copy App ID and App Secret</li>
                    </ol>
                    
                    <h3>Google</h3>
                    <ol>
                        <li>Go to <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a></li>
                        <li>Create a new project or select existing one</li>
                        <li>Enable Google+ API</li>
                        <li>Create OAuth 2.0 credentials</li>
                        <li>Add callback URL to authorized redirect URIs</li>
                        <li>Copy Client ID and Client Secret</li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize social authentication
new EP_Social_Auth();
