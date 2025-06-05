<?php
/**
 * User Management Endpoints for Environmental Mobile API
 *
 * @package Environmental_Mobile_API
 * @subpackage Endpoints
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Environmental_Mobile_API_User_Endpoints
 */
class Environmental_Mobile_API_User_Endpoints {
    
    private $auth_manager;
    private $cache_manager;
    private $rate_limiter;
    private $security;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $environmental_mobile_api;
        
        $this->auth_manager = $environmental_mobile_api->get_auth_manager();
        $this->cache_manager = $environmental_mobile_api->get_cache_manager();
        $this->rate_limiter = $environmental_mobile_api->get_rate_limiter();
        $this->security = $environmental_mobile_api->get_security();
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        $namespace = 'environmental-mobile-api/v1';
        
        // Get user profile
        register_rest_route($namespace, '/user/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_profile'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Update user profile
        register_rest_route($namespace, '/user/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_profile'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'first_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'display_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'description' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'url' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw',
                ),
                'location' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'interests' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        ));
        
        // Upload profile avatar
        register_rest_route($namespace, '/user/avatar', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_avatar'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Delete profile avatar
        register_rest_route($namespace, '/user/avatar', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_avatar'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Get user preferences
        register_rest_route($namespace, '/user/preferences', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_preferences'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Update user preferences
        register_rest_route($namespace, '/user/preferences', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_preferences'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'notifications' => array(
                    'type' => 'object',
                ),
                'privacy' => array(
                    'type' => 'object',
                ),
                'app_settings' => array(
                    'type' => 'object',
                ),
            ),
        ));
        
        // Get user activity feed
        register_rest_route($namespace, '/user/activity', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_activity'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 20,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
        ));
        
        // Get user statistics
        register_rest_route($namespace, '/user/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_stats'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Get user devices
        register_rest_route($namespace, '/user/devices', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_devices'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Remove device
        register_rest_route($namespace, '/user/devices/(?P<device_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_device'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'device_id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Delete user account
        register_rest_route($namespace, '/user/account', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_account'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'confirmation' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
    }
    
    /**
     * Get user profile
     */
    public function get_profile($request) {
        try {
            $user_id = get_current_user_id();
            $cache_key = "user_profile_{$user_id}";
            
            // Try to get from cache first
            $profile = $this->cache_manager->get($cache_key);
            
            if ($profile === false) {
                $user = get_user_by('ID', $user_id);
                
                if (!$user) {
                    return new WP_Error('user_not_found', 'User not found.', array('status' => 404));
                }
                
                $profile = $this->get_user_profile_data($user);
                
                // Cache for 15 minutes
                $this->cache_manager->set($cache_key, $profile, 900);
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $profile,
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Profile Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Update user profile
     */
    public function update_profile($request) {
        try {
            $user_id = get_current_user_id();
            $user = get_user_by('ID', $user_id);
            
            if (!$user) {
                return new WP_Error('user_not_found', 'User not found.', array('status' => 404));
            }
            
            $updated_fields = array();
            
            // Update user fields
            $user_data = array('ID' => $user_id);
            
            if ($request->has_param('display_name')) {
                $display_name = $request->get_param('display_name');
                if (!empty($display_name)) {
                    $user_data['display_name'] = $display_name;
                    $updated_fields[] = 'display_name';
                }
            }
            
            if ($request->has_param('url')) {
                $url = $request->get_param('url');
                $user_data['user_url'] = $url;
                $updated_fields[] = 'url';
            }
            
            // Update user if there are changes
            if (count($user_data) > 1) {
                $result = wp_update_user($user_data);
                if (is_wp_error($result)) {
                    return new WP_Error('update_failed', 'Failed to update user profile.', array('status' => 500));
                }
            }
            
            // Update user meta fields
            $meta_fields = array('first_name', 'last_name', 'description', 'location');
            
            foreach ($meta_fields as $field) {
                if ($request->has_param($field)) {
                    $value = $request->get_param($field);
                    update_user_meta($user_id, $field, $value);
                    $updated_fields[] = $field;
                }
            }
            
            // Update interests
            if ($request->has_param('interests')) {
                $interests = $request->get_param('interests');
                if (is_array($interests)) {
                    update_user_meta($user_id, 'environmental_interests', $interests);
                    $updated_fields[] = 'interests';
                }
            }
            
            // Clear cache
            $this->cache_manager->delete("user_profile_{$user_id}");
            
            // Get updated profile
            $updated_user = get_user_by('ID', $user_id);
            $profile = $this->get_user_profile_data($updated_user);
            
            // Log profile update
            $this->security->log_security_event('profile_updated', array(
                'user_id' => $user_id,
                'updated_fields' => $updated_fields,
                'ip' => $this->security->get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $profile,
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Update Profile Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Upload profile avatar
     */
    public function upload_avatar($request) {
        try {
            $user_id = get_current_user_id();
            
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            $files = $request->get_file_params();
            
            if (empty($files['avatar'])) {
                return new WP_Error('no_file', 'No avatar file provided.', array('status' => 400));
            }
            
            $file = $files['avatar'];
            
            // Validate file type
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
            if (!in_array($file['type'], $allowed_types)) {
                return new WP_Error('invalid_file_type', 'Only JPEG, PNG, and GIF files are allowed.', array('status' => 400));
            }
            
            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                return new WP_Error('file_too_large', 'File size must be less than 2MB.', array('status' => 400));
            }
            
            $upload_overrides = array(
                'test_form' => false,
                'unique_filename_callback' => function($dir, $name, $ext) use ($user_id) {
                    return "avatar_{$user_id}_" . time() . $ext;
                }
            );
            
            $uploaded_file = wp_handle_upload($file, $upload_overrides);
            
            if (!empty($uploaded_file['error'])) {
                return new WP_Error('upload_failed', $uploaded_file['error'], array('status' => 500));
            }
            
            // Save avatar URL to user meta
            update_user_meta($user_id, 'custom_avatar', $uploaded_file['url']);
            
            // Clear cache
            $this->cache_manager->delete("user_profile_{$user_id}");
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => array(
                    'avatar_url' => $uploaded_file['url'],
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Upload Avatar Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Delete profile avatar
     */
    public function delete_avatar($request) {
        try {
            $user_id = get_current_user_id();
            
            // Get current avatar URL
            $avatar_url = get_user_meta($user_id, 'custom_avatar', true);
            
            if ($avatar_url) {
                // Delete the file
                $upload_dir = wp_upload_dir();
                $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $avatar_url);
                
                if (file_exists($file_path)) {
                    wp_delete_file($file_path);
                }
                
                // Remove from user meta
                delete_user_meta($user_id, 'custom_avatar');
            }
            
            // Clear cache
            $this->cache_manager->delete("user_profile_{$user_id}");
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Avatar deleted successfully',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Delete Avatar Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get user preferences
     */
    public function get_preferences($request) {
        try {
            $user_id = get_current_user_id();
            
            $preferences = array(
                'notifications' => array(
                    'push_enabled' => get_user_meta($user_id, 'push_notifications_enabled', true) ?: false,
                    'email_enabled' => get_user_meta($user_id, 'email_notifications_enabled', true) ?: true,
                    'petition_updates' => get_user_meta($user_id, 'notify_petition_updates', true) ?: true,
                    'event_reminders' => get_user_meta($user_id, 'notify_event_reminders', true) ?: true,
                    'social_interactions' => get_user_meta($user_id, 'notify_social_interactions', true) ?: true,
                ),
                'privacy' => array(
                    'profile_visibility' => get_user_meta($user_id, 'profile_visibility', true) ?: 'public',
                    'activity_visibility' => get_user_meta($user_id, 'activity_visibility', true) ?: 'public',
                    'contact_sharing' => get_user_meta($user_id, 'contact_sharing', true) ?: false,
                ),
                'app_settings' => array(
                    'language' => get_user_meta($user_id, 'app_language', true) ?: 'en',
                    'theme' => get_user_meta($user_id, 'app_theme', true) ?: 'light',
                    'auto_sync' => get_user_meta($user_id, 'auto_sync_enabled', true) ?: true,
                    'offline_mode' => get_user_meta($user_id, 'offline_mode_enabled', true) ?: false,
                ),
            );
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $preferences,
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Preferences Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Update user preferences
     */
    public function update_preferences($request) {
        try {
            $user_id = get_current_user_id();
            
            // Update notification preferences
            if ($request->has_param('notifications')) {
                $notifications = $request->get_param('notifications');
                
                if (isset($notifications['push_enabled'])) {
                    update_user_meta($user_id, 'push_notifications_enabled', (bool) $notifications['push_enabled']);
                }
                if (isset($notifications['email_enabled'])) {
                    update_user_meta($user_id, 'email_notifications_enabled', (bool) $notifications['email_enabled']);
                }
                if (isset($notifications['petition_updates'])) {
                    update_user_meta($user_id, 'notify_petition_updates', (bool) $notifications['petition_updates']);
                }
                if (isset($notifications['event_reminders'])) {
                    update_user_meta($user_id, 'notify_event_reminders', (bool) $notifications['event_reminders']);
                }
                if (isset($notifications['social_interactions'])) {
                    update_user_meta($user_id, 'notify_social_interactions', (bool) $notifications['social_interactions']);
                }
            }
            
            // Update privacy preferences
            if ($request->has_param('privacy')) {
                $privacy = $request->get_param('privacy');
                
                if (isset($privacy['profile_visibility'])) {
                    $visibility = in_array($privacy['profile_visibility'], array('public', 'private', 'friends')) 
                        ? $privacy['profile_visibility'] : 'public';
                    update_user_meta($user_id, 'profile_visibility', $visibility);
                }
                if (isset($privacy['activity_visibility'])) {
                    $visibility = in_array($privacy['activity_visibility'], array('public', 'private', 'friends')) 
                        ? $privacy['activity_visibility'] : 'public';
                    update_user_meta($user_id, 'activity_visibility', $visibility);
                }
                if (isset($privacy['contact_sharing'])) {
                    update_user_meta($user_id, 'contact_sharing', (bool) $privacy['contact_sharing']);
                }
            }
            
            // Update app settings
            if ($request->has_param('app_settings')) {
                $app_settings = $request->get_param('app_settings');
                
                if (isset($app_settings['language'])) {
                    update_user_meta($user_id, 'app_language', sanitize_text_field($app_settings['language']));
                }
                if (isset($app_settings['theme'])) {
                    $theme = in_array($app_settings['theme'], array('light', 'dark', 'auto')) 
                        ? $app_settings['theme'] : 'light';
                    update_user_meta($user_id, 'app_theme', $theme);
                }
                if (isset($app_settings['auto_sync'])) {
                    update_user_meta($user_id, 'auto_sync_enabled', (bool) $app_settings['auto_sync']);
                }
                if (isset($app_settings['offline_mode'])) {
                    update_user_meta($user_id, 'offline_mode_enabled', (bool) $app_settings['offline_mode']);
                }
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Preferences updated successfully',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Update Preferences Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get user activity feed
     */
    public function get_activity($request) {
        try {
            $user_id = get_current_user_id();
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $offset = ($page - 1) * $per_page;
            
            global $wpdb;
            
            // Get user activities from various sources
            $activities = array();
            
            // Get petition signatures
            $petition_signatures = $wpdb->get_results($wpdb->prepare("
                SELECT 'petition_signed' as activity_type, petition_id as object_id, created_at as activity_date
                FROM {$wpdb->prefix}environmental_petition_signatures 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d
            ", $user_id, $per_page, $offset));
            
            foreach ($petition_signatures as $signature) {
                $petition = get_post($signature->object_id);
                if ($petition) {
                    $activities[] = array(
                        'type' => 'petition_signed',
                        'title' => 'Signed a petition',
                        'description' => $petition->post_title,
                        'object_id' => $signature->object_id,
                        'date' => $signature->activity_date,
                        'meta' => array(
                            'petition_title' => $petition->post_title,
                            'petition_url' => get_permalink($petition->ID),
                        ),
                    );
                }
            }
            
            // Get item exchange activities
            $exchange_activities = $wpdb->get_results($wpdb->prepare("
                SELECT 'item_posted' as activity_type, post_id as object_id, created_at as activity_date
                FROM {$wpdb->prefix}posts p
                WHERE post_author = %d AND post_type = 'environmental_item' AND post_status = 'publish'
                ORDER BY post_date DESC 
                LIMIT %d OFFSET %d
            ", $user_id, $per_page, $offset));
            
            foreach ($exchange_activities as $activity) {
                $item = get_post($activity->object_id);
                if ($item) {
                    $activities[] = array(
                        'type' => 'item_posted',
                        'title' => 'Posted an item for exchange',
                        'description' => $item->post_title,
                        'object_id' => $activity->object_id,
                        'date' => $activity->activity_date,
                        'meta' => array(
                            'item_title' => $item->post_title,
                            'item_url' => get_permalink($item->ID),
                        ),
                    );
                }
            }
            
            // Sort activities by date
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // Limit to requested per_page
            $activities = array_slice($activities, 0, $per_page);
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'activities' => $activities,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => count($activities),
                    ),
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Activity Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get user statistics
     */
    public function get_user_stats($request) {
        try {
            $user_id = get_current_user_id();
            $cache_key = "user_stats_{$user_id}";
            
            // Try to get from cache first
            $stats = $this->cache_manager->get($cache_key);
            
            if ($stats === false) {
                global $wpdb;
                
                // Calculate statistics
                $stats = array(
                    'petitions_signed' => (int) $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*) FROM {$wpdb->prefix}environmental_petition_signatures 
                        WHERE user_id = %d
                    ", $user_id)),
                    'items_posted' => (int) get_user_meta($user_id, 'items_posted_count', true),
                    'items_exchanged' => (int) get_user_meta($user_id, 'items_exchanged_count', true),
                    'environmental_points' => (int) get_user_meta($user_id, 'environmental_points', true),
                    'impact_score' => (int) get_user_meta($user_id, 'environmental_impact_score', true),
                    'badges_earned' => count(get_user_meta($user_id, 'environmental_badges', true) ?: array()),
                    'member_since' => get_userdata($user_id)->user_registered,
                );
                
                // Cache for 1 hour
                $this->cache_manager->set($cache_key, $stats, 3600);
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $stats,
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get User Stats Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get user devices
     */
    public function get_devices($request) {
        try {
            $user_id = get_current_user_id();
            
            global $wpdb;
            $table_tokens = $wpdb->prefix . 'environmental_mobile_api_tokens';
            
            $devices = $wpdb->get_results($wpdb->prepare("
                SELECT device_id, device_name, device_info, last_used, created_at
                FROM {$table_tokens}
                WHERE user_id = %d AND status = 'active'
                ORDER BY last_used DESC
            ", $user_id));
            
            $formatted_devices = array();
            
            foreach ($devices as $device) {
                $device_info = json_decode($device->device_info, true);
                $formatted_devices[] = array(
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'platform' => $device_info['platform'] ?? 'Unknown',
                    'app_version' => $device_info['app_version'] ?? 'Unknown',
                    'last_used' => $device->last_used,
                    'registered_date' => $device->created_at,
                    'is_current' => $device->device_id === $this->get_current_device_id(),
                );
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => $formatted_devices,
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Devices Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Remove device
     */
    public function remove_device($request) {
        try {
            $user_id = get_current_user_id();
            $device_id = $request->get_param('device_id');
            
            // Don't allow removing current device
            if ($device_id === $this->get_current_device_id()) {
                return new WP_Error('cannot_remove_current_device', 'Cannot remove the current device.', array('status' => 400));
            }
            
            global $wpdb;
            $table_tokens = $wpdb->prefix . 'environmental_mobile_api_tokens';
            
            $result = $wpdb->update(
                $table_tokens,
                array('status' => 'revoked'),
                array(
                    'user_id' => $user_id,
                    'device_id' => $device_id,
                ),
                array('%s'),
                array('%d', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('remove_failed', 'Failed to remove device.', array('status' => 500));
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Device removed successfully',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Remove Device Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Delete user account
     */
    public function delete_account($request) {
        try {
            $user_id = get_current_user_id();
            $password = $request->get_param('password');
            $confirmation = $request->get_param('confirmation');
            
            if ($confirmation !== 'DELETE_MY_ACCOUNT') {
                return new WP_Error('invalid_confirmation', 'Invalid confirmation text.', array('status' => 400));
            }
            
            $user = get_user_by('ID', $user_id);
            
            if (!$user) {
                return new WP_Error('user_not_found', 'User not found.', array('status' => 404));
            }
            
            // Verify password
            if (!wp_check_password($password, $user->user_pass, $user_id)) {
                return new WP_Error('invalid_password', 'Password is incorrect.', array('status' => 400));
            }
            
            // Log account deletion
            $this->security->log_security_event('account_deleted', array(
                'user_id' => $user_id,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'ip' => $this->security->get_client_ip(),
            ));
            
            // Revoke all tokens
            $this->auth_manager->revoke_all_user_tokens($user_id);
            
            // Delete user account
            if (!wp_delete_user($user_id)) {
                return new WP_Error('delete_failed', 'Failed to delete user account.', array('status' => 500));
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Account deleted successfully',
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Delete Account Error: ' . $e->getMessage());
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
     * Get user profile data
     */
    private function get_user_profile_data($user) {
        $custom_avatar = get_user_meta($user->ID, 'custom_avatar', true);
        $avatar_url = $custom_avatar ?: get_avatar_url($user->ID);
        
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'description' => get_user_meta($user->ID, 'description', true),
            'url' => $user->user_url,
            'location' => get_user_meta($user->ID, 'location', true),
            'avatar_url' => $avatar_url,
            'interests' => get_user_meta($user->ID, 'environmental_interests', true) ?: array(),
            'roles' => $user->roles,
            'registered_date' => $user->user_registered,
            'profile_completion' => $this->calculate_profile_completion($user),
            'environmental_points' => (int) get_user_meta($user->ID, 'environmental_points', true),
            'impact_score' => (int) get_user_meta($user->ID, 'environmental_impact_score', true),
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
            'location' => get_user_meta($user->ID, 'location', true),
            'interests' => get_user_meta($user->ID, 'environmental_interests', true),
            'avatar' => get_user_meta($user->ID, 'custom_avatar', true),
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
    
    /**
     * Get current device ID from token
     */
    private function get_current_device_id() {
        $token = $this->get_bearer_token();
        if (!$token) {
            return null;
        }
        
        global $wpdb;
        $table_tokens = $wpdb->prefix . 'environmental_mobile_api_tokens';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT device_id FROM {$table_tokens} 
            WHERE access_token = %s AND status = 'active'
        ", hash('sha256', $token)));
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
}

// Initialize the user endpoints
new Environmental_Mobile_API_User_Endpoints();
