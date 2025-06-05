<?php
/**
 * QR Generator Class
 * 
 * Handles QR code generation for vouchers and rewards
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_QR_Generator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_evr_generate_voucher_qr', array($this, 'ajax_generate_voucher_qr'));
        add_action('wp_ajax_evr_scan_qr_code', array($this, 'ajax_scan_qr_code'));
    }
    
    /**
     * Generate QR code for voucher
     */
    public function generate_voucher_qr($voucher_code, $user_id = null) {
        $qr_data = array(
            'type' => 'voucher',
            'code' => $voucher_code,
            'user_id' => $user_id,
            'timestamp' => time(),
            'site_url' => home_url()
        );
        
        $qr_string = base64_encode(json_encode($qr_data));
        
        return $this->generate_qr_code($qr_string, array(
            'size' => 200,
            'margin' => 10,
            'format' => 'png'
        ));
    }
    
    /**
     * Generate QR code for reward redemption
     */
    public function generate_reward_qr($reward_id, $user_id) {
        $qr_data = array(
            'type' => 'reward',
            'reward_id' => $reward_id,
            'user_id' => $user_id,
            'timestamp' => time(),
            'site_url' => home_url()
        );
        
        $qr_string = base64_encode(json_encode($qr_data));
        
        return $this->generate_qr_code($qr_string, array(
            'size' => 150,
            'margin' => 8,
            'format' => 'png'
        ));
    }
    
    /**
     * Generate basic QR code using Google Charts API
     */
    private function generate_qr_code($data, $options = array()) {
        $defaults = array(
            'size' => 200,
            'margin' => 10,
            'format' => 'png',
            'encoding' => 'UTF-8'
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Use Google Charts API for QR generation
        $api_url = 'https://chart.googleapis.com/chart';
        $params = array(
            'chs' => $options['size'] . 'x' . $options['size'],
            'cht' => 'qr',
            'chl' => urlencode($data),
            'choe' => $options['encoding'],
            'chld' => 'M|' . $options['margin']
        );
        
        $url = $api_url . '?' . http_build_query($params);
        
        // Try to fetch QR code image
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_qr($data, $options);
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return $this->generate_fallback_qr($data, $options);
        }
        
        // Save QR code to uploads directory
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/qr-codes/';
        
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }
        
        $filename = 'qr_' . md5($data . time()) . '.png';
        $file_path = $qr_dir . $filename;
        $file_url = $upload_dir['baseurl'] . '/qr-codes/' . $filename;
        
        if (file_put_contents($file_path, $image_data)) {
            return array(
                'success' => true,
                'file_path' => $file_path,
                'file_url' => $file_url,
                'data' => $data
            );
        }
        
        return $this->generate_fallback_qr($data, $options);
    }
    
    /**
     * Generate fallback QR code using local library
     */
    private function generate_fallback_qr($data, $options) {
        // Simple text-based QR representation for fallback
        $qr_text = "QR Code Data: " . substr($data, 0, 50) . "...";
        
        return array(
            'success' => false,
            'fallback' => true,
            'text' => $qr_text,
            'data' => $data,
            'message' => 'QR code generation failed, using text fallback'
        );
    }
    
    /**
     * Validate and decode QR data
     */
    public function decode_qr_data($qr_string) {
        $decoded = base64_decode($qr_string);
        
        if (!$decoded) {
            return array('valid' => false, 'error' => 'Invalid QR data format');
        }
        
        $data = json_decode($decoded, true);
        
        if (!$data) {
            return array('valid' => false, 'error' => 'Failed to parse QR data');
        }
        
        // Validate required fields
        if (!isset($data['type']) || !isset($data['timestamp'])) {
            return array('valid' => false, 'error' => 'Missing required QR data fields');
        }
        
        // Check if QR code is not too old (24 hours)
        if (time() - $data['timestamp'] > 86400) {
            return array('valid' => false, 'error' => 'QR code has expired');
        }
        
        // Validate site URL
        if (isset($data['site_url']) && $data['site_url'] !== home_url()) {
            return array('valid' => false, 'error' => 'QR code is not valid for this site');
        }
        
        return array('valid' => true, 'data' => $data);
    }
    
    /**
     * Process QR code scan
     */
    public function process_qr_scan($qr_string, $scanner_user_id = null) {
        $decode_result = $this->decode_qr_data($qr_string);
        
        if (!$decode_result['valid']) {
            return array(
                'success' => false,
                'message' => $decode_result['error']
            );
        }
        
        $data = $decode_result['data'];
        
        switch ($data['type']) {
            case 'voucher':
                return $this->process_voucher_qr($data, $scanner_user_id);
                
            case 'reward':
                return $this->process_reward_qr($data, $scanner_user_id);
                
            default:
                return array(
                    'success' => false,
                    'message' => 'Unknown QR code type'
                );
        }
    }
    
    /**
     * Process voucher QR code scan
     */
    private function process_voucher_qr($data, $scanner_user_id) {
        $voucher_manager = EVR_Voucher_Manager::get_instance();
        
        // Validate voucher
        $validation = $voucher_manager->validate_voucher($data['code'], $scanner_user_id);
        
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }
        
        // Apply voucher
        $application = $voucher_manager->apply_voucher($data['code'], $scanner_user_id);
        
        return array(
            'success' => $application['success'],
            'message' => $application['message'],
            'type' => 'voucher',
            'voucher_code' => $data['code']
        );
    }
    
    /**
     * Process reward QR code scan
     */
    private function process_reward_qr($data, $scanner_user_id) {
        // Check if scanner is the reward owner
        if ($scanner_user_id !== $data['user_id']) {
            return array(
                'success' => false,
                'message' => 'This reward belongs to another user'
            );
        }
        
        $reward_engine = EVR_Reward_Engine::get_instance();
        
        // Claim reward
        $claim_result = $reward_engine->claim_reward($data['user_id'], $data['reward_id']);
        
        return array(
            'success' => $claim_result['success'],
            'message' => $claim_result['message'],
            'type' => 'reward',
            'reward_id' => $data['reward_id']
        );
    }
    
    /**
     * Generate QR code for partner redemption
     */
    public function generate_partner_qr($redemption_code, $partner_id, $user_id) {
        $qr_data = array(
            'type' => 'partner_redemption',
            'redemption_code' => $redemption_code,
            'partner_id' => $partner_id,
            'user_id' => $user_id,
            'timestamp' => time(),
            'site_url' => home_url()
        );
        
        $qr_string = base64_encode(json_encode($qr_data));
        
        return $this->generate_qr_code($qr_string, array(
            'size' => 250,
            'margin' => 12,
            'format' => 'png'
        ));
    }
    
    /**
     * Get QR code for display
     */
    public function get_qr_display_html($qr_result, $title = 'QR Code') {
        if (!$qr_result['success']) {
            return sprintf(
                '<div class="evr-qr-fallback">
                    <h4>%s</h4>
                    <p>%s</p>
                    <small>%s</small>
                </div>',
                esc_html($title),
                esc_html($qr_result['text'] ?? 'QR code unavailable'),
                esc_html($qr_result['message'] ?? '')
            );
        }
        
        return sprintf(
            '<div class="evr-qr-code">
                <h4>%s</h4>
                <img src="%s" alt="QR Code" class="qr-image" />
                <p><small>Scan with your phone camera</small></p>
            </div>',
            esc_html($title),
            esc_url($qr_result['file_url'])
        );
    }
    
    /**
     * Clean up old QR code files
     */
    public function cleanup_old_qr_files() {
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/qr-codes/';
        
        if (!file_exists($qr_dir)) {
            return;
        }
        
        $files = glob($qr_dir . 'qr_*.png');
        $current_time = time();
        
        foreach ($files as $file) {
            // Delete files older than 24 hours
            if ($current_time - filemtime($file) > 86400) {
                unlink($file);
            }
        }
    }
    
    /**
     * AJAX: Generate voucher QR
     */
    public function ajax_generate_voucher_qr() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $voucher_code = sanitize_text_field($_POST['voucher_code'] ?? '');
        $user_id = get_current_user_id();
        
        if (empty($voucher_code)) {
            wp_send_json_error('Voucher code is required');
        }
        
        $qr_result = $this->generate_voucher_qr($voucher_code, $user_id);
        $html = $this->get_qr_display_html($qr_result, 'Voucher QR Code');
        
        wp_send_json_success(array(
            'html' => $html,
            'qr_data' => $qr_result
        ));
    }
    
    /**
     * AJAX: Scan QR code
     */
    public function ajax_scan_qr_code() {
        check_ajax_referer('evr_nonce', 'nonce');
        
        $qr_data = sanitize_text_field($_POST['qr_data'] ?? '');
        $user_id = get_current_user_id();
        
        if (empty($qr_data)) {
            wp_send_json_error('QR data is required');
        }
        
        $result = $this->process_qr_scan($qr_data, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Get QR code scanner HTML
     */
    public function get_qr_scanner_html() {
        return '
        <div id="evr-qr-scanner" class="evr-qr-scanner">
            <h3>Scan QR Code</h3>
            <div class="scanner-container">
                <div id="qr-video-container">
                    <video id="qr-video" width="300" height="300"></video>
                    <div class="scanner-overlay">
                        <div class="scanner-line"></div>
                    </div>
                </div>
                <div class="scanner-controls">
                    <button id="start-scanner" class="btn btn-primary">Start Scanner</button>
                    <button id="stop-scanner" class="btn btn-secondary">Stop Scanner</button>
                </div>
                <div class="manual-input">
                    <h4>Or enter code manually:</h4>
                    <input type="text" id="manual-qr-code" placeholder="Enter QR code data" />
                    <button id="process-manual-qr" class="btn btn-success">Process</button>
                </div>
            </div>
            <div id="qr-result" class="qr-result"></div>
        </div>';
    }
}
