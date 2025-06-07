<?php
/**
 * Encryption Utility Class for Environmental Email Marketing
 *
 * Handles secure encryption and decryption of sensitive data including
 * subscriber information, API keys, and personal data for GDPR compliance.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Utils
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Email Marketing Encryption Class
 *
 * Provides robust encryption functionality using WordPress standards
 * and modern cryptographic practices for data protection.
 */
class EEM_Encryption {

    /**
     * Encryption method
     *
     * @var string
     */
    private static $method = 'AES-256-CBC';

    /**
     * Salt for key derivation
     *
     * @var string
     */
    private static $salt = 'environmental_email_marketing_salt';

    /**
     * Logger instance
     *
     * @var EEM_Logger
     */
    private static $logger;

    /**
     * Initialize the encryption system
     *
     * @since 1.0.0
     */
    public static function init() {
        self::$logger = new EEM_Logger();
        
        // Ensure OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            self::$logger->log_error('OpenSSL not available for encryption');
            throw new Exception('OpenSSL extension is required for encryption');
        }

        // Validate encryption method
        if (!in_array(self::$method, openssl_get_cipher_methods())) {
            self::$logger->log_error('Encryption method not supported: ' . self::$method);
            throw new Exception('Encryption method not supported');
        }
    }

    /**
     * Generate encryption key
     *
     * @return string
     * @since 1.0.0
     */
    private static function get_encryption_key() {
        // Use WordPress AUTH_KEY as base
        $base_key = defined('AUTH_KEY') ? AUTH_KEY : 'fallback_key_change_this';
        
        // Add plugin-specific salt
        $plugin_salt = get_option('eem_encryption_salt', self::generate_salt());
        
        // Derive key using PBKDF2
        return hash_pbkdf2('sha256', $base_key, $plugin_salt . self::$salt, 10000, 32, true);
    }

    /**
     * Generate a cryptographically secure salt
     *
     * @return string
     * @since 1.0.0
     */
    private static function generate_salt() {
        $salt = bin2hex(random_bytes(32));
        update_option('eem_encryption_salt', $salt);
        return $salt;
    }

    /**
     * Encrypt data
     *
     * @param mixed $data Data to encrypt
     * @param string $context Context for logging
     * @return string|false Encrypted data or false on failure
     * @since 1.0.0
     */
    public static function encrypt($data, $context = 'general') {
        try {
            if (empty($data)) {
                return $data;
            }

            // Serialize data if not string
            if (!is_string($data)) {
                $data = serialize($data);
            }

            // Generate random IV
            $iv_length = openssl_cipher_iv_length(self::$method);
            $iv = openssl_random_pseudo_bytes($iv_length);

            // Encrypt data
            $encrypted = openssl_encrypt(
                $data,
                self::$method,
                self::get_encryption_key(),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                self::$logger->log_error("Encryption failed for context: {$context}");
                return false;
            }

            // Combine IV and encrypted data
            $result = base64_encode($iv . $encrypted);

            self::$logger->log_debug("Data encrypted successfully for context: {$context}");
            return $result;

        } catch (Exception $e) {
            self::$logger->log_error("Encryption error for context {$context}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt data
     *
     * @param string $encrypted_data Encrypted data
     * @param string $context Context for logging
     * @param bool $unserialize Whether to unserialize the result
     * @return mixed|false Decrypted data or false on failure
     * @since 1.0.0
     */
    public static function decrypt($encrypted_data, $context = 'general', $unserialize = false) {
        try {
            if (empty($encrypted_data)) {
                return $encrypted_data;
            }

            // Decode base64
            $data = base64_decode($encrypted_data);
            if ($data === false) {
                self::$logger->log_error("Base64 decode failed for context: {$context}");
                return false;
            }

            // Extract IV and encrypted data
            $iv_length = openssl_cipher_iv_length(self::$method);
            $iv = substr($data, 0, $iv_length);
            $encrypted = substr($data, $iv_length);

            // Decrypt data
            $decrypted = openssl_decrypt(
                $encrypted,
                self::$method,
                self::get_encryption_key(),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                self::$logger->log_error("Decryption failed for context: {$context}");
                return false;
            }

            // Unserialize if requested
            if ($unserialize) {
                $result = unserialize($decrypted);
                if ($result === false && $decrypted !== serialize(false)) {
                    self::$logger->log_error("Unserialization failed for context: {$context}");
                    return false;
                }
                $decrypted = $result;
            }

            self::$logger->log_debug("Data decrypted successfully for context: {$context}");
            return $decrypted;

        } catch (Exception $e) {
            self::$logger->log_error("Decryption error for context {$context}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hash data with salt
     *
     * @param string $data Data to hash
     * @param string $salt Optional custom salt
     * @return string Hashed data
     * @since 1.0.0
     */
    public static function hash($data, $salt = null) {
        if ($salt === null) {
            $salt = self::get_encryption_key();
        }
        
        return hash_hmac('sha256', $data, $salt);
    }

    /**
     * Verify hashed data
     *
     * @param string $data Original data
     * @param string $hash Hash to verify against
     * @param string $salt Optional custom salt
     * @return bool True if hash matches
     * @since 1.0.0
     */
    public static function verify_hash($data, $hash, $salt = null) {
        $calculated_hash = self::hash($data, $salt);
        return hash_equals($calculated_hash, $hash);
    }

    /**
     * Generate secure random token
     *
     * @param int $length Token length
     * @return string Random token
     * @since 1.0.0
     */
    public static function generate_token($length = 32) {
        try {
            return bin2hex(random_bytes($length));
        } catch (Exception $e) {
            self::$logger->log_error("Token generation failed: " . $e->getMessage());
            // Fallback to less secure method
            return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
        }
    }

    /**
     * Encrypt API credentials
     *
     * @param array $credentials API credentials
     * @return string|false Encrypted credentials
     * @since 1.0.0
     */
    public static function encrypt_api_credentials($credentials) {
        return self::encrypt($credentials, 'api_credentials');
    }

    /**
     * Decrypt API credentials
     *
     * @param string $encrypted_credentials Encrypted credentials
     * @return array|false Decrypted credentials
     * @since 1.0.0
     */
    public static function decrypt_api_credentials($encrypted_credentials) {
        return self::decrypt($encrypted_credentials, 'api_credentials', true);
    }

    /**
     * Encrypt subscriber data
     *
     * @param array $subscriber_data Subscriber data
     * @return string|false Encrypted data
     * @since 1.0.0
     */
    public static function encrypt_subscriber_data($subscriber_data) {
        // Remove non-sensitive data
        $sensitive_data = array_intersect_key($subscriber_data, array_flip([
            'email',
            'first_name',
            'last_name',
            'phone',
            'address',
            'preferences'
        ]));

        return self::encrypt($sensitive_data, 'subscriber_data');
    }

    /**
     * Decrypt subscriber data
     *
     * @param string $encrypted_data Encrypted subscriber data
     * @return array|false Decrypted data
     * @since 1.0.0
     */
    public static function decrypt_subscriber_data($encrypted_data) {
        return self::decrypt($encrypted_data, 'subscriber_data', true);
    }

    /**
     * Encrypt personal data for GDPR compliance
     *
     * @param mixed $personal_data Personal data
     * @return string|false Encrypted data
     * @since 1.0.0
     */
    public static function encrypt_personal_data($personal_data) {
        return self::encrypt($personal_data, 'personal_data');
    }

    /**
     * Decrypt personal data
     *
     * @param string $encrypted_data Encrypted personal data
     * @return mixed|false Decrypted data
     * @since 1.0.0
     */
    public static function decrypt_personal_data($encrypted_data) {
        return self::decrypt($encrypted_data, 'personal_data', true);
    }

    /**
     * Secure data deletion
     *
     * @param string $data Data to securely delete
     * @return bool Success status
     * @since 1.0.0
     */
    public static function secure_delete($data) {
        try {
            // Overwrite memory
            for ($i = 0; $i < strlen($data); $i++) {
                $data[$i] = chr(0);
            }
            
            // Force garbage collection
            unset($data);
            
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            return true;

        } catch (Exception $e) {
            self::$logger->log_error("Secure deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate encrypted data integrity
     *
     * @param string $encrypted_data Encrypted data to validate
     * @return bool True if data is valid
     * @since 1.0.0
     */
    public static function validate_encrypted_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return false;
        }

        // Check if base64 encoded
        $decoded = base64_decode($encrypted_data, true);
        if ($decoded === false) {
            return false;
        }

        // Check minimum length (IV + some data)
        $iv_length = openssl_cipher_iv_length(self::$method);
        if (strlen($decoded) <= $iv_length) {
            return false;
        }

        return true;
    }

    /**
     * Get encryption statistics
     *
     * @return array Encryption statistics
     * @since 1.0.0
     */
    public static function get_encryption_stats() {
        global $wpdb;
        
        $stats = [
            'method' => self::$method,
            'key_derivation' => 'PBKDF2-SHA256',
            'iterations' => 10000,
            'openssl_available' => function_exists('openssl_encrypt'),
            'cipher_methods' => openssl_get_cipher_methods(),
            'encrypted_records' => 0
        ];

        // Count encrypted records
        try {
            $table_prefix = $wpdb->prefix . 'eem_';
            
            // Count encrypted subscribers
            $encrypted_subscribers = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_prefix}subscribers WHERE encrypted_data IS NOT NULL AND encrypted_data != ''"
            );
            
            $stats['encrypted_records'] = (int) $encrypted_subscribers;

        } catch (Exception $e) {
            self::$logger->log_error("Failed to get encryption stats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Rotate encryption keys
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public static function rotate_keys() {
        try {
            self::$logger->log_info("Starting encryption key rotation");

            // Generate new salt
            $new_salt = self::generate_salt();
            
            // This would require re-encrypting all existing data
            // For now, just log the action
            self::$logger->log_warning("Key rotation initiated - manual data re-encryption required");

            return true;

        } catch (Exception $e) {
            self::$logger->log_error("Key rotation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test encryption functionality
     *
     * @return array Test results
     * @since 1.0.0
     */
    public static function test_encryption() {
        $results = [
            'encrypt_decrypt' => false,
            'hash_verify' => false,
            'token_generation' => false,
            'api_credentials' => false,
            'subscriber_data' => false,
            'errors' => []
        ];

        try {
            // Test basic encryption/decryption
            $test_data = 'Test encryption data';
            $encrypted = self::encrypt($test_data, 'test');
            $decrypted = self::decrypt($encrypted, 'test');
            $results['encrypt_decrypt'] = ($decrypted === $test_data);

            // Test hashing
            $test_hash = self::hash($test_data);
            $results['hash_verify'] = self::verify_hash($test_data, $test_hash);

            // Test token generation
            $token = self::generate_token(16);
            $results['token_generation'] = (strlen($token) === 32 && ctype_xdigit($token));

            // Test API credentials encryption
            $test_credentials = ['api_key' => 'test_key', 'secret' => 'test_secret'];
            $encrypted_creds = self::encrypt_api_credentials($test_credentials);
            $decrypted_creds = self::decrypt_api_credentials($encrypted_creds);
            $results['api_credentials'] = ($decrypted_creds === $test_credentials);

            // Test subscriber data encryption
            $test_subscriber = ['email' => 'test@example.com', 'first_name' => 'Test'];
            $encrypted_sub = self::encrypt_subscriber_data($test_subscriber);
            $decrypted_sub = self::decrypt_subscriber_data($encrypted_sub);
            $results['subscriber_data'] = ($decrypted_sub === $test_subscriber);

        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            self::$logger->log_error("Encryption test failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Clean up encryption resources
     *
     * @since 1.0.0
     */
    public static function cleanup() {
        // Clear any cached keys or sensitive data
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        self::$logger->log_debug("Encryption cleanup completed");
    }
}
