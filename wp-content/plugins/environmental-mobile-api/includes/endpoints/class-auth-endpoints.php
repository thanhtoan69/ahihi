<?php
/**
 * Authentication API Endpoints
 *
 * @package EnvironmentalMobileAPI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Environmental_Mobile_API_Auth_Endpoints
 */
class Environmental_Mobile_API_Auth_Endpoints {
    
    /**
     * Auth manager instance
     */
    private $auth_manager;
    
    /**
     * Rate limiter instance
     */
    private $rate_limiter;
    
    /**
     * Security instance
     */
    private $security;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auth_manager = new Environmental_Mobile_API_Auth_Manager();
        $this->rate_limiter = new Environmental_Mobile_API_Rate_Limiter();
        $this->security = new Environmental_Mobile_API_Security();
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'environmental-mobile-api/v1';
        
        // Login endpoint
        register_rest_route($namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'device_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'device_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'app_version' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Register endpoint
        register_rest_route($namespace, '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'register'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_user',
                ),
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'first_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'device_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'device_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Refresh token endpoint
        register_rest_route($namespace, '/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh_token'),
            'permission_callback' => '__return_true',
            'args' => array(
                'refresh_token' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Logout endpoint
        register_rest_route($namespace, '/auth/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'logout'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Logout all devices endpoint
        register_rest_route($namespace, '/auth/logout-all', array(
            'methods' => 'POST',
            'callback' => array($this, 'logout_all'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Verify token endpoint
        register_rest_route($namespace, '/auth/verify', array(
            'methods' => 'GET',
            'callback' => array($this, 'verify_token'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Reset password endpoint
        register_rest_route($namespace, '/auth/reset-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'reset_password'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));
        
        // Change password endpoint
        register_rest_route($namespace, '/auth/change-password', array(
            'methods' => 'POST',
            'callback' => array($this, 'change_password'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'current_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'new_password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
    }
    
    /**
     * Login endpoint
     */
    public function login($request) {
        try {
            // Check rate limit
            $identifier = $this->security->get_client_ip();
            if (!$this->rate_limiter->check_rate_limit('auth_login', $identifier)) {
                return new WP_Error('rate_limit_exceeded', 'Too many login attempts. Please try again later.', array('status' => 429));
            }
            
            $username = $request->get_param('username');
            $password = $request->get_param('password');
            $device_id = $request->get_param('device_id');
            $device_name = $request->get_param('device_name');
            $app_version = $request->get_param('app_version');
            
            // Validate input
            if (!$this->security->validate_input($username) || !$this->security->validate_input($password)) {
                return new WP_Error('invalid_input', 'Invalid input detected.', array('status' => 400));
            }
            
            // Authenticate user
            $user = wp_authenticate($username, $password);
            
            if (is_wp_error($user)) {
                // Log failed attempt
                $this->security->log_security_event('login_failed', array(
                    'username' => $username,
                    'ip' => $this->security->get_client_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ));
                
                return new WP_Error('authentication_failed', 'Invalid username or password.', array('status' => 401));
            }
            
            // Check if user is allowed to use mobile API
            if (!user_can($user->ID, 'read')) {
                return new WP_Error('insufficient_permissions', 'You do not have permission to access the mobile API.', array('status' => 403));
            }
            
            // Generate JWT token
            $device_info = array(
                'device_id' => $device_id,
                'device_name' => $device_name ?: 'Unknown Device',
                'app_version' => $app_version,
                'ip_address' => $this->security->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            );
            
            $tokens = $this->auth_manager->generate_jwt_token($user->ID, $device_info);
            
            if (!$tokens) {
                return new WP_Error('token_generation_failed', 'Failed to generate authentication token.', array('status' => 500));
            }
            
            // Log successful login
            $this->security->log_security_event('login_success', array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'ip' => $this->security->get_client_ip(),
            ));
            
            // Get user profile data
            $user_data = $this->get_user_profile_data($user);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Login successful',
                'data' => array(
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_in' => $tokens['expires_in'],
                    'token_type' => 'Bearer',
                    'user' => $user_data,
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Login Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Register endpoint
     */
    public function register($request) {
        try {
            // Check rate limit
            $identifier = $this->security->get_client_ip();
            if (!$this->rate_limiter->check_rate_limit('auth_register', $identifier)) {
                return new WP_Error('rate_limit_exceeded', 'Too many registration attempts. Please try again later.', array('status' => 429));
            }
            
            $username = $request->get_param('username');
            $email = $request->get_param('email');
            $password = $request->get_param('password');
            $first_name = $request->get_param('first_name');
            $last_name = $request->get_param('last_name');
            $device_id = $request->get_param('device_id');
            $device_name = $request->get_param('device_name');
            
            // Validate input
            if (!$this->security->validate_input($username) || !$this->security->validate_input($email)) {
                return new WP_Error('invalid_input', 'Invalid input detected.', array('status' => 400));
            }
            
            // Check if registration is allowed
            if (!get_option('users_can_register')) {
                return new WP_Error('registration_disabled', 'User registration is currently disabled.', array('status' => 403));
            }
            
            // Validate email
            if (!is_email($email)) {
                return new WP_Error('invalid_email', 'Please provide a valid email address.', array('status' => 400));
            }
            
            // Check if username exists
            if (username_exists($username)) {
                return new WP_Error('username_exists', 'Username already exists.', array('status' => 409));
            }
            
            // Check if email exists
            if (email_exists($email)) {
                return new WP_Error('email_exists', 'Email address already exists.', array('status' => 409));
            }
            
            // Validate password strength
            if (strlen($password) < 8) {
                return new WP_Error('weak_password', 'Password must be at least 8 characters long.', array('status' => 400));
            }
            
            // Create user
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                return new WP_Error('registration_failed', 'Failed to create user account.', array('status' => 500));
            }
            
            // Update user meta
            if ($first_name) {
                update_user_meta($user_id, 'first_name', $first_name);
            }
            if ($last_name) {
                update_user_meta($user_id, 'last_name', $last_name);
            }
            
            // Set default role
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
            
            // Generate JWT token for auto-login
            $device_info = array(
                'device_id' => $device_id,
                'device_name' => $device_name ?: 'Unknown Device',
                'ip_address' => $this->security->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            );
            
            $tokens = $this->auth_manager->generate_jwt_token($user_id, $device_info);
            
            // Log successful registration
            $this->security->log_security_event('user_registered', array(
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email,
                'ip' => $this->security->get_client_ip(),
            ));
            
            // Get user profile data
            $user_data = $this->get_user_profile_data($user);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Registration successful',
                'data' => array(
                    'access_token' => $tokens['access_token'] ?? null,
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'expires_in' => $tokens['expires_in'] ?? null,
                    'token_type' => 'Bearer',
                    'user' => $user_data,
                ),
            ), 201);
            
        } catch (Exception $e) {
            error_log('Mobile API Registration Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Refresh token endpoint
     */
    public function refresh_token($request) {
        try {
            $refresh_token = $request->get_param('refresh_token');
            
            if (!$refresh_token) {
                return new WP_Error('missing_token', 'Refresh token is required.', array('status' => 400));
            }
            
            $new_tokens = $this->auth_manager->refresh_token($refresh_token);
            
            if (!$new_tokens) {
                return new WP_Error('invalid_token', 'Invalid or expired refresh token.', array('status' => 401));
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => array(
                    'access_token' => $new_tokens['access_token'],
                    'refresh_token' => $new_tokens['refresh_token'],
                    'expires_in' => $new_tokens['expires_in'],
                    'token_type' => 'Bearer',
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Token Refresh Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Logout endpoint
     */
    public function logout($request) {
        try {
            $user_id = get_current_user_id();
            $token = $this->get_bearer_token();
            
            if ($token) {
                $this->auth_manager->revoke_token($token);
            }
            
            // Log logout
            $this->security->log_security_event('user_logout', array(
                'user_id' => $user_id,
                'ip' => $this->security->get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Logout successful',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Logout Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Logout all devices endpoint
     */
    public function logout_all($request) {
        try {
            $user_id = get_current_user_id();
            
            $this->auth_manager->revoke_all_user_tokens($user_id);
            
            // Log logout all
            $this->security->log_security_event('user_logout_all', array(
                'user_id' => $user_id,
                'ip' => $this->security->get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Logout from all devices successful',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Logout All Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Verify token endpoint
     */
    public function verify_token($request) {
        try {
            $user_id = get_current_user_id();
            $user = get_user_by('ID', $user_id);
            
            if (!$user) {
                return new WP_Error('user_not_found', 'User not found.', array('status' => 404));
            }
            
            $user_data = $this->get_user_profile_data($user);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Token is valid',
                'data' => array(
                    'user' => $user_data,
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Token Verify Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Reset password endpoint
     */
    public function reset_password($request) {
        try {
            // Check rate limit
            $identifier = $this->security->get_client_ip();
            if (!$this->rate_limiter->check_rate_limit('password_reset', $identifier)) {
                return new WP_Error('rate_limit_exceeded', 'Too many password reset attempts. Please try again later.', array('status' => 429));
            }
            
            $email = $request->get_param('email');
            
            if (!is_email($email)) {
                return new WP_Error('invalid_email', 'Please provide a valid email address.', array('status' => 400));
            }
            
            $user = get_user_by('email', $email);
            
            if (!$user) {
                // Don't reveal if email exists or not
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'If the email address exists in our system, you will receive a password reset email.',
                ), 200);
            }
            
            // Generate reset key
            $reset_key = get_password_reset_key($user);
            
            if (is_wp_error($reset_key)) {
                return new WP_Error('reset_key_failed', 'Failed to generate password reset key.', array('status' => 500));
            }
            
            // Send reset email
            $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
            
            $subject = 'Password Reset Request';
            $message = "Hello {$user->display_name},\n\n";
            $message .= "You have requested a password reset for your account.\n\n";
            $message .= "Click the link below to reset your password:\n";
            $message .= $reset_url . "\n\n";
            $message .= "If you did not request this reset, please ignore this email.\n\n";
            $message .= "This link will expire in 24 hours.";
            
            wp_mail($email, $subject, $message);
            
            // Log password reset request
            $this->security->log_security_event('password_reset_requested', array(
                'user_id' => $user->ID,
                'email' => $email,
                'ip' => $this->security->get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'If the email address exists in our system, you will receive a password reset email.',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Password Reset Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Change password endpoint
     */
    public function change_password($request) {
        try {
            $user_id = get_current_user_id();
            $current_password = $request->get_param('current_password');
            $new_password = $request->get_param('new_password');
            
            $user = get_user_by('ID', $user_id);
            
            if (!$user) {
                return new WP_Error('user_not_found', 'User not found.', array('status' => 404));
            }
            
            // Verify current password
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                return new WP_Error('invalid_password', 'Current password is incorrect.', array('status' => 400));
            }
            
            // Validate new password strength
            if (strlen($new_password) < 8) {
                return new WP_Error('weak_password', 'New password must be at least 8 characters long.', array('status' => 400));
            }
            
            // Update password
            wp_set_password($new_password, $user_id);
            
            // Revoke all existing tokens to force re-login
            $this->auth_manager->revoke_all_user_tokens($user_id);
            
            // Log password change
            $this->security->log_security_event('password_changed', array(
                'user_id' => $user_id,
                'ip' => $this->security->get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Password changed successfully. Please log in again.',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Change Password Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Check authentication permission callback
     */
    public function check_authentication($request) {
        return $this->auth_manager->authenticate_request($request);
    }
    
    /**
     * Get bearer token from request
     */
    private function get_bearer_token() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get user profile data
     */
    private function get_user_profile_data($user) {
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'avatar_url' => get_avatar_url($user->ID),
            'roles' => $user->roles,
            'registered_date' => $user->user_registered,
            'profile_completion' => $this->calculate_profile_completion($user),
        );
    }
    
    /**
     * Calculate profile completion percentage
     */
    private function calculate_profile_completion($user) {
        $fields = array(
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'description' => get_user_meta($user->ID, 'description', true),
            'url' => $user->user_url,
        );
        
        $completed = 0;
        $total = count($fields);
        
        foreach ($fields as $field => $value) {
            if (!empty($value)) {
                $completed++;
            }
        }
        
        return round(($completed / $total) * 100);
    }
}

// Initialize the auth endpoints
new Environmental_Mobile_API_Auth_Endpoints();
