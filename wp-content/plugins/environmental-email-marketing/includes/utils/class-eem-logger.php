<?php
/**
 * Logger Utility Class
 *
 * Provides comprehensive logging functionality for the Environmental Email Marketing plugin
 * with multiple log levels, file rotation, and performance monitoring.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Logger {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Log directory
     */
    private $log_dir;

    /**
     * Maximum log file size (in bytes)
     */
    private $max_file_size = 10485760; // 10MB

    /**
     * Maximum number of log files to keep
     */
    private $max_files = 5;

    /**
     * Current log level
     */
    private $log_level;

    /**
     * Log levels hierarchy
     */
    private $levels = array(
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7
    );

    /**
     * Get instance
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
        $this->init();
    }

    /**
     * Initialize logger
     */
    private function init() {
        // Set log directory
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/environmental-email-marketing/logs';

        // Create log directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
            
            // Add .htaccess to protect log files
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($this->log_dir . '/.htaccess', $htaccess_content);
        }

        // Set log level from settings
        $this->log_level = get_option('eem_log_level', self::WARNING);

        // Schedule log cleanup
        if (!wp_next_scheduled('eem_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'eem_cleanup_logs');
        }
        add_action('eem_cleanup_logs', array($this, 'cleanup_old_logs'));
    }

    /**
     * Log emergency message
     */
    public function emergency($message, $context = array()) {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     */
    public function alert($message, $context = array()) {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical($message, $context = array()) {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = array()) {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = array()) {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     */
    public function notice($message, $context = array()) {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     */
    public function info($message, $context = array()) {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = array()) {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Main logging method
     */
    public function log($level, $message, $context = array()) {
        // Check if we should log this level
        if (!$this->should_log($level)) {
            return;
        }

        try {
            // Get log file path
            $log_file = $this->get_log_file_path($level);

            // Check if log rotation is needed
            $this->rotate_log_if_needed($log_file);

            // Format log entry
            $log_entry = $this->format_log_entry($level, $message, $context);

            // Write to file
            $this->write_to_file($log_file, $log_entry);

            // Also log to WordPress debug.log if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log($log_entry);
            }

            // Send critical logs via email if configured
            if (in_array($level, array(self::EMERGENCY, self::ALERT, self::CRITICAL))) {
                $this->send_critical_log_email($level, $message, $context);
            }

        } catch (Exception $e) {
            // Fallback to error_log if our logging fails
            error_log('EEM Logger failed: ' . $e->getMessage());
            error_log('Original message: ' . $message);
        }
    }

    /**
     * Check if we should log this level
     */
    private function should_log($level) {
        if (!isset($this->levels[$level]) || !isset($this->levels[$this->log_level])) {
            return false;
        }
        
        return $this->levels[$level] <= $this->levels[$this->log_level];
    }

    /**
     * Get log file path for given level
     */
    private function get_log_file_path($level) {
        $date = date('Y-m-d');
        $filename = "eem-{$level}-{$date}.log";
        return $this->log_dir . '/' . $filename;
    }

    /**
     * Format log entry
     */
    private function format_log_entry($level, $message, $context = array()) {
        $timestamp = date('Y-m-d H:i:s');
        $memory_usage = $this->format_bytes(memory_get_usage());
        $memory_peak = $this->format_bytes(memory_get_peak_usage());
        
        // Get context information
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI';
        $user_id = get_current_user_id();
        $user_ip = $this->get_client_ip();
        
        // Build context string
        $context_str = '';
        if (!empty($context)) {
            $context_str = ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        // Build log entry
        $log_entry = sprintf(
            "[%s] [%s] [Memory: %s/%s] [User: %d] [IP: %s] [URI: %s] %s%s\n",
            $timestamp,
            strtoupper($level),
            $memory_usage,
            $memory_peak,
            $user_id,
            $user_ip,
            $request_uri,
            $message,
            $context_str
        );

        return $log_entry;
    }

    /**
     * Write to log file
     */
    private function write_to_file($file_path, $content) {
        if (!is_writable(dirname($file_path))) {
            throw new Exception('Log directory is not writable: ' . dirname($file_path));
        }

        $result = file_put_contents($file_path, $content, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            throw new Exception('Failed to write to log file: ' . $file_path);
        }
    }

    /**
     * Rotate log file if needed
     */
    private function rotate_log_if_needed($file_path) {
        if (!file_exists($file_path)) {
            return;
        }

        if (filesize($file_path) >= $this->max_file_size) {
            $this->rotate_log_file($file_path);
        }
    }

    /**
     * Rotate log file
     */
    private function rotate_log_file($file_path) {
        $pathinfo = pathinfo($file_path);
        $basename = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
        $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';

        // Move existing rotated files
        for ($i = $this->max_files - 1; $i > 0; $i--) {
            $old_file = $basename . '.' . $i . $extension;
            $new_file = $basename . '.' . ($i + 1) . $extension;
            
            if (file_exists($old_file)) {
                if ($i === $this->max_files - 1) {
                    unlink($old_file); // Delete oldest file
                } else {
                    rename($old_file, $new_file);
                }
            }
        }

        // Move current file to .1
        $rotated_file = $basename . '.1' . $extension;
        rename($file_path, $rotated_file);
    }

    /**
     * Cleanup old log files
     */
    public function cleanup_old_logs() {
        $files = glob($this->log_dir . '/eem-*.log*');
        $cutoff_time = time() - (30 * 24 * 60 * 60); // 30 days

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }

    /**
     * Send critical log email
     */
    private function send_critical_log_email($level, $message, $context) {
        $admin_email = get_option('eem_critical_log_email', get_option('admin_email'));
        
        if (!$admin_email || !get_option('eem_send_critical_log_emails', false)) {
            return;
        }

        $subject = sprintf('[%s] Critical Issue - %s', get_bloginfo('name'), strtoupper($level));
        
        $body = "A critical issue has been logged on your website:\n\n";
        $body .= "Level: " . strtoupper($level) . "\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $body .= "Message: " . $message . "\n";
        
        if (!empty($context)) {
            $body .= "Context: " . print_r($context, true) . "\n";
        }
        
        $body .= "\nURL: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI') . "\n";
        $body .= "User: " . get_current_user_id() . "\n";
        $body .= "IP: " . $this->get_client_ip() . "\n";
        
        wp_mail($admin_email, $subject, $body);
    }

    /**
     * Get recent logs
     */
    public function get_recent_logs($level = null, $limit = 100) {
        $logs = array();
        
        if ($level) {
            $files = glob($this->log_dir . "/eem-{$level}-*.log");
        } else {
            $files = glob($this->log_dir . '/eem-*.log');
        }

        // Sort files by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $count = 0;
        foreach ($files as $file) {
            if ($count >= $limit) {
                break;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Newest first

            foreach ($lines as $line) {
                if ($count >= $limit) {
                    break;
                }
                
                $logs[] = $this->parse_log_line($line);
                $count++;
            }
        }

        return $logs;
    }

    /**
     * Parse log line
     */
    private function parse_log_line($line) {
        $pattern = '/\[([^\]]+)\] \[([^\]]+)\] \[Memory: ([^\]]+)\] \[User: ([^\]]+)\] \[IP: ([^\]]+)\] \[URI: ([^\]]+)\] (.+)/';
        
        if (preg_match($pattern, $line, $matches)) {
            return array(
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'memory' => $matches[3],
                'user_id' => $matches[4],
                'ip' => $matches[5],
                'uri' => $matches[6],
                'message' => $matches[7],
                'raw' => $line
            );
        }

        return array(
            'timestamp' => '',
            'level' => '',
            'memory' => '',
            'user_id' => '',
            'ip' => '',
            'uri' => '',
            'message' => $line,
            'raw' => $line
        );
    }

    /**
     * Get log statistics
     */
    public function get_log_statistics($days = 7) {
        $stats = array();
        $cutoff_time = time() - ($days * 24 * 60 * 60);

        foreach (array_keys($this->levels) as $level) {
            $stats[$level] = 0;
        }

        $files = glob($this->log_dir . '/eem-*.log');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                continue;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $parsed = $this->parse_log_line($line);
                $timestamp = strtotime($parsed['timestamp']);
                
                if ($timestamp >= $cutoff_time) {
                    $level = strtolower($parsed['level']);
                    if (isset($stats[$level])) {
                        $stats[$level]++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Clear all logs
     */
    public function clear_logs($level = null) {
        if ($level) {
            $files = glob($this->log_dir . "/eem-{$level}-*.log*");
        } else {
            $files = glob($this->log_dir . '/eem-*.log*');
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return count($files);
    }

    /**
     * Export logs
     */
    public function export_logs($level = null, $days = 7) {
        $logs = $this->get_recent_logs($level, 1000);
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        $filtered_logs = array_filter($logs, function($log) use ($cutoff_time) {
            return strtotime($log['timestamp']) >= $cutoff_time;
        });

        return $filtered_logs;
    }

    /**
     * Set log level
     */
    public function set_log_level($level) {
        if (isset($this->levels[$level])) {
            $this->log_level = $level;
            update_option('eem_log_level', $level);
        }
    }

    /**
     * Get log level
     */
    public function get_log_level() {
        return $this->log_level;
    }

    /**
     * Get available log levels
     */
    public function get_available_levels() {
        return array_keys($this->levels);
    }

    /**
     * Log API request
     */
    public function log_api_request($provider, $endpoint, $method, $response_code, $execution_time, $request_data = null, $response_data = null) {
        $context = array(
            'provider' => $provider,
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $response_code,
            'execution_time' => $execution_time . 'ms'
        );

        if ($request_data) {
            $context['request'] = $request_data;
        }

        if ($response_data) {
            $context['response'] = $response_data;
        }

        $message = "API Request: {$provider} {$method} {$endpoint} - {$response_code} ({$execution_time}ms)";
        
        if ($response_code >= 400) {
            $this->error($message, $context);
        } else {
            $this->info($message, $context);
        }
    }

    /**
     * Log email sent
     */
    public function log_email_sent($to, $subject, $provider, $campaign_id = null, $success = true) {
        $context = array(
            'to' => $to,
            'subject' => $subject,
            'provider' => $provider,
            'success' => $success
        );

        if ($campaign_id) {
            $context['campaign_id'] = $campaign_id;
        }

        $message = "Email " . ($success ? 'sent' : 'failed') . " via {$provider} to {$to}";
        
        if ($success) {
            $this->info($message, $context);
        } else {
            $this->error($message, $context);
        }
    }

    /**
     * Log automation trigger
     */
    public function log_automation_trigger($trigger_type, $email, $automation_id, $data = array()) {
        $context = array(
            'trigger_type' => $trigger_type,
            'email' => $email,
            'automation_id' => $automation_id,
            'data' => $data
        );

        $message = "Automation triggered: {$trigger_type} for {$email}";
        $this->info($message, $context);
    }

    /**
     * Log database operation
     */
    public function log_database_operation($operation, $table, $success, $affected_rows = 0, $query_time = 0) {
        $context = array(
            'operation' => $operation,
            'table' => $table,
            'success' => $success,
            'affected_rows' => $affected_rows,
            'query_time' => $query_time . 'ms'
        );

        $message = "Database {$operation} on {$table} - " . ($success ? 'Success' : 'Failed') . " ({$affected_rows} rows, {$query_time}ms)";
        
        if ($success) {
            $this->debug($message, $context);
        } else {
            $this->error($message, $context);
        }
    }

    /**
     * Format bytes
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
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
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    /**
     * Get log file size
     */
    public function get_log_file_size($level = null) {
        if ($level) {
            $files = glob($this->log_dir . "/eem-{$level}-*.log*");
        } else {
            $files = glob($this->log_dir . '/eem-*.log*');
        }

        $total_size = 0;
        foreach ($files as $file) {
            $total_size += filesize($file);
        }

        return $total_size;
    }

    /**
     * Get log directory info
     */
    public function get_log_directory_info() {
        $files = glob($this->log_dir . '/eem-*.log*');
        $total_size = 0;
        $file_count = count($files);

        foreach ($files as $file) {
            $total_size += filesize($file);
        }

        return array(
            'directory' => $this->log_dir,
            'file_count' => $file_count,
            'total_size' => $total_size,
            'total_size_formatted' => $this->format_bytes($total_size),
            'writable' => is_writable($this->log_dir)
        );
    }
}

// Initialize the logger
EEM_Logger::get_instance();
