<?php
/**
 * JWT Authentication Manager
 * 
 * Handles JWT token generation, validation, and refresh
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Mobile_API_Auth_Manager {
    
    private $jwt_secret;
    private $algorithm = 'HS256';
    
    public function __construct() {
        $this->jwt_secret = get_option('environmental_mobile_api_jwt_secret');
        
        if (!$this->jwt_secret) {
            $this->jwt_secret = wp_generate_password(64, true, true);
            update_option('environmental_mobile_api_jwt_secret', $this->jwt_secret);
        }
    }
    
    /**
     * Generate JWT token
     */
    public function generate_jwt_token($user_id, $device_id = null, $token_type = 'access') {
        $settings = get_option('environmental_mobile_api_settings', array());
        
        $expiration = $token_type === 'refresh' 
            ? (isset($settings['refresh_token_expiration']) ? $settings['refresh_token_expiration'] : 604800)
            : (isset($settings['jwt_expiration']) ? $settings['jwt_expiration'] : 3600);
        
        $issued_at = time();
        $expires_at = $issued_at + $expiration;
        
        $payload = array(
            'iss' => get_site_url(),
            'aud' => get_site_url(),
            'iat' => $issued_at,
            'exp' => $expires_at,
            'user_id' => $user_id,
            'token_type' => $token_type,
            'device_id' => $device_id,
            'jti' => wp_generate_uuid4()
        );
        
        $token = $this->jwt_encode($payload, $this->jwt_secret, $this->algorithm);
        
        // Store token in database
        $this->store_token($user_id, $token, $device_id, $expires_at, $token_type);
        
        return $token;
    }
    
    /**
     * Validate JWT token
     */
    public function validate_jwt_token($token) {
        try {
            $payload = $this->jwt_decode($token, $this->jwt_secret, array($this->algorithm));
            
            // Check if token exists in database and is not revoked
            if (!$this->is_token_valid($token)) {
                return new WP_Error('invalid_token', 'Token is invalid or revoked', array('status' => 401));
            }
            
            // Update last used timestamp
            $this->update_token_last_used($token);
            
            return $payload->user_id;
            
        } catch (Exception $e) {
            return new WP_Error('jwt_decode_error', $e->getMessage(), array('status' => 401));
        }
    }
    
    /**
     * Refresh JWT token
     */
    public function refresh_token($refresh_token) {
        try {
            $payload = $this->jwt_decode($refresh_token, $this->jwt_secret, array($this->algorithm));
            
            if ($payload->token_type !== 'refresh') {
                return new WP_Error('invalid_refresh_token', 'Invalid refresh token', array('status' => 401));
            }
            
            if (!$this->is_token_valid($refresh_token)) {
                return new WP_Error('invalid_refresh_token', 'Refresh token is invalid or revoked', array('status' => 401));
            }
            
            // Generate new access token
            $new_access_token = $this->generate_jwt_token($payload->user_id, $payload->device_id, 'access');
            
            // Optionally generate new refresh token
            $new_refresh_token = $this->generate_jwt_token($payload->user_id, $payload->device_id, 'refresh');
            
            // Revoke old refresh token
            $this->revoke_token($refresh_token);
            
            return array(
                'access_token' => $new_access_token,
                'refresh_token' => $new_refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => get_option('environmental_mobile_api_settings')['jwt_expiration'] ?? 3600
            );
            
        } catch (Exception $e) {
            return new WP_Error('refresh_token_error', $e->getMessage(), array('status' => 401));
        }
    }
    
    /**
     * Revoke token
     */
    public function revoke_token($token) {
        global $wpdb;
        
        $token_hash = hash('sha256', $token);
        
        return $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_tokens',
            array('is_revoked' => 1),
            array('token_hash' => $token_hash),
            array('%d'),
            array('%s')
        );
    }
    
    /**
     * Revoke all user tokens
     */
    public function revoke_user_tokens($user_id, $except_token = null) {
        global $wpdb;
        
        $where = array('user_id' => $user_id);
        $where_format = array('%d');
        
        if ($except_token) {
            $except_hash = hash('sha256', $except_token);
            $where['token_hash !='] = $except_hash;
            $where_format[] = '%s';
        }
        
        return $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_tokens',
            array('is_revoked' => 1),
            $where,
            array('%d'),
            $where_format
        );
    }
    
    /**
     * Store token in database
     */
    private function store_token($user_id, $token, $device_id, $expires_at, $token_type) {
        global $wpdb;
        
        $token_hash = hash('sha256', $token);
        
        return $wpdb->insert(
            $wpdb->prefix . 'environmental_mobile_api_tokens',
            array(
                'user_id' => $user_id,
                'token_type' => $token_type,
                'token_hash' => $token_hash,
                'device_id' => $device_id,
                'expires_at' => date('Y-m-d H:i:s', $expires_at),
                'created_at' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Check if token is valid
     */
    private function is_token_valid($token) {
        global $wpdb;
        
        $token_hash = hash('sha256', $token);
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}environmental_mobile_api_tokens 
             WHERE token_hash = %s AND expires_at > NOW() AND is_revoked = 0",
            $token_hash
        ));
        
        return $result !== null;
    }
    
    /**
     * Update token last used timestamp
     */
    private function update_token_last_used($token) {
        global $wpdb;
        
        $token_hash = hash('sha256', $token);
        
        return $wpdb->update(
            $wpdb->prefix . 'environmental_mobile_api_tokens',
            array('last_used_at' => current_time('mysql')),
            array('token_hash' => $token_hash),
            array('%s'),
            array('%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
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
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * JWT encode function
     */
    private function jwt_encode($payload, $key, $alg = 'HS256') {
        $header = json_encode(array('typ' => 'JWT', 'alg' => $alg));
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * JWT decode function
     */
    private function jwt_decode($jwt, $key, $allowed_algs) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Wrong number of segments');
        }
        
        list($base64Header, $base64Payload, $signature) = $parts;
        
        $header = json_decode($this->base64_url_decode($base64Header));
        $payload = json_decode($this->base64_url_decode($base64Payload));
        
        if (null === $header || null === $payload) {
            throw new Exception('Invalid encoding');
        }
        
        if (!isset($header->alg) || !in_array($header->alg, $allowed_algs)) {
            throw new Exception('Algorithm not allowed');
        }
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $expected_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));
        
        if (!hash_equals($signature, $expected_signature)) {
            throw new Exception('Signature verification failed');
        }
        
        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token has expired');
        }
        
        // Check not before
        if (isset($payload->nbf) && $payload->nbf > time()) {
            throw new Exception('Token not yet valid');
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL decode
     */
    private function base64_url_decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        
        return base64_decode(strtr($input, '-_', '+/'));
    }
    
    /**
     * Get user tokens
     */
    public function get_user_tokens($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT token_type, device_id, created_at, last_used_at, expires_at, ip_address, user_agent
             FROM {$wpdb->prefix}environmental_mobile_api_tokens 
             WHERE user_id = %d AND expires_at > NOW() AND is_revoked = 0
             ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        
        return $wpdb->query(
            "DELETE FROM {$wpdb->prefix}environmental_mobile_api_tokens 
             WHERE expires_at < NOW() OR is_revoked = 1"
        );
    }
    
    /**
     * Generate API key for server-to-server communication
     */
    public function generate_api_key($name, $permissions = array()) {
        $api_key = 'emapi_' . wp_generate_password(32, false);
        $api_secret = wp_generate_password(64, true, true);
        
        $api_keys = get_option('environmental_mobile_api_keys', array());
        $api_keys[$api_key] = array(
            'name' => $name,
            'secret' => hash('sha256', $api_secret),
            'permissions' => $permissions,
            'created_at' => current_time('mysql'),
            'last_used_at' => null,
            'is_active' => true
        );
        
        update_option('environmental_mobile_api_keys', $api_keys);
        
        return array(
            'api_key' => $api_key,
            'api_secret' => $api_secret
        );
    }
    
    /**
     * Validate API key
     */
    public function validate_api_key($api_key, $api_secret) {
        $api_keys = get_option('environmental_mobile_api_keys', array());
        
        if (!isset($api_keys[$api_key])) {
            return false;
        }
        
        $stored_key = $api_keys[$api_key];
        
        if (!$stored_key['is_active']) {
            return false;
        }
        
        if (!hash_equals($stored_key['secret'], hash('sha256', $api_secret))) {
            return false;
        }
        
        // Update last used
        $api_keys[$api_key]['last_used_at'] = current_time('mysql');
        update_option('environmental_mobile_api_keys', $api_keys);
        
        return $stored_key;
    }
}
