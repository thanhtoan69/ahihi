<?php
/**
 * AI Service Manager
 * 
 * Manages AI services integration including waste classification,
 * image processing, and AI training data collection.
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AI_Service_Manager {
    
    private static $instance = null;
    private $api_endpoints = array();
    private $supported_formats = array('jpg', 'jpeg', 'png', 'webp');
    private $max_file_size = 5242880; // 5MB
    
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
        $this->init_hooks();
        $this->init_api_endpoints();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_classify_waste_image', array($this, 'ajax_classify_waste_image'));
        add_action('wp_ajax_nopriv_classify_waste_image', array($this, 'ajax_classify_waste_image'));
        add_action('wp_ajax_submit_ai_feedback', array($this, 'ajax_submit_ai_feedback'));
        add_action('wp_ajax_nopriv_submit_ai_feedback', array($this, 'ajax_submit_ai_feedback'));
        add_action('wp_ajax_get_classification_history', array($this, 'ajax_get_classification_history'));
        add_action('wp_ajax_get_ai_stats', array($this, 'ajax_get_ai_stats'));
        add_action('wp_ajax_retrain_ai_model', array($this, 'ajax_retrain_ai_model'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Cron jobs for AI model training
        add_action('env_ai_daily_training', array($this, 'daily_model_training'));
        add_action('env_ai_cleanup_old_data', array($this, 'cleanup_old_training_data'));
    }
    
    /**
     * Initialize API endpoints
     */
    private function init_api_endpoints() {
        $this->api_endpoints = array(
            'classification' => array(
                'url' => get_option('env_ai_classification_endpoint', 'https://api.openai.com/v1/chat/completions'),
                'key' => get_option('env_ai_api_key', ''),
                'model' => get_option('env_ai_model', 'gpt-4-vision-preview')
            ),
            'training' => array(
                'url' => get_option('env_ai_training_endpoint', ''),
                'key' => get_option('env_ai_training_key', '')
            )
        );
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('environmental-ai/v1', '/classify', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_classify_waste'),
            'permission_callback' => '__return_true',
            'args' => array(
                'image' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_image_data')
                )
            )
        ));
        
        register_rest_route('environmental-ai/v1', '/feedback', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_submit_feedback'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('environmental-ai/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_ai_stats'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Classify waste from uploaded image
     */
    public function classify_waste_image($image_data, $user_id = 0) {
        try {
            // Validate image
            $validation = $this->validate_image($image_data);
            if (!$validation['valid']) {
                return array(
                    'success' => false,
                    'error' => $validation['error']
                );
            }
            
            // Process image for AI analysis
            $processed_image = $this->process_image_for_ai($image_data);
            
            // Call AI classification service
            $classification_result = $this->call_ai_classification_service($processed_image);
            
            if ($classification_result['success']) {
                // Store classification result
                $classification_id = $this->store_classification_result(
                    $user_id,
                    $processed_image,
                    $classification_result['data']
                );
                
                // Update user statistics
                $this->update_user_ai_stats($user_id, $classification_result['data']);
                
                return array(
                    'success' => true,
                    'classification_id' => $classification_id,
                    'result' => $classification_result['data'],
                    'confidence' => $classification_result['confidence'] ?? 0.85,
                    'categories' => $this->get_waste_categories(),
                    'recommendations' => $this->get_disposal_recommendations($classification_result['data'])
                );
            } else {
                return array(
                    'success' => false,
                    'error' => $classification_result['error'] ?? __('AI classification failed', 'env-data-dashboard')
                );
            }
            
        } catch (Exception $e) {
            error_log('AI Classification Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => __('Classification service temporarily unavailable', 'env-data-dashboard')
            );
        }
    }
    
    /**
     * Validate uploaded image
     */
    private function validate_image($image_data) {
        if (empty($image_data)) {
            return array('valid' => false, 'error' => __('No image data provided', 'env-data-dashboard'));
        }
        
        // Check file size
        if (strlen($image_data) > $this->max_file_size) {
            return array('valid' => false, 'error' => __('Image file too large (max 5MB)', 'env-data-dashboard'));
        }
        
        // Validate image format
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($image_data);
        
        if (!in_array($mime_type, array('image/jpeg', 'image/png', 'image/webp'))) {
            return array('valid' => false, 'error' => __('Unsupported image format', 'env-data-dashboard'));
        }
        
        return array('valid' => true);
    }
    
    /**
     * Process image for AI analysis
     */
    private function process_image_for_ai($image_data) {
        // Resize image if too large
        $image = imagecreatefromstring($image_data);
        if (!$image) {
            throw new Exception('Invalid image data');
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize if larger than 1024px on any side
        $max_size = 1024;
        if ($width > $max_size || $height > $max_size) {
            $ratio = min($max_size / $width, $max_size / $height);
            $new_width = intval($width * $ratio);
            $new_height = intval($height * $ratio);
            
            $resized = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            ob_start();
            imagejpeg($resized, null, 85);
            $processed_data = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($image);
            imagedestroy($resized);
            
            return $processed_data;
        }
        
        imagedestroy($image);
        return $image_data;
    }
    
    /**
     * Call AI classification service
     */
    private function call_ai_classification_service($image_data) {
        $endpoint = $this->api_endpoints['classification'];
        
        if (empty($endpoint['key'])) {
            return array(
                'success' => false,
                'error' => 'AI service not configured'
            );
        }
        
        // Convert image to base64
        $base64_image = base64_encode($image_data);
        
        // Prepare request data
        $request_data = array(
            'model' => $endpoint['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $this->get_classification_prompt()
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => 'data:image/jpeg;base64,' . $base64_image
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 300
        );
        
        // Make API request
        $response = wp_remote_post($endpoint['url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $endpoint['key'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['choices'][0]['message']['content'])) {
            return array(
                'success' => false,
                'error' => 'Invalid AI response'
            );
        }
        
        // Parse AI response
        $ai_response = $data['choices'][0]['message']['content'];
        $parsed_result = $this->parse_ai_classification_response($ai_response);
        
        return array(
            'success' => true,
            'data' => $parsed_result,
            'confidence' => $parsed_result['confidence'] ?? 0.85,
            'raw_response' => $ai_response
        );
    }
    
    /**
     * Get classification prompt for AI
     */
    private function get_classification_prompt() {
        return "Analyze this image and classify the waste items you see. Return a JSON response with the following structure:
{
  \"category\": \"one of: recyclable, organic, hazardous, electronic, general\",
  \"subcategory\": \"specific type like plastic_bottle, food_waste, battery, etc.\",
  \"confidence\": \"confidence score from 0.1 to 1.0\",
  \"description\": \"brief description of what you see\",
  \"disposal_method\": \"proper disposal recommendation\",
  \"environmental_impact\": \"brief impact statement\"
}

Focus on identifying the primary waste item in the image and provide accurate classification for proper disposal.";
    }
    
    /**
     * Parse AI classification response
     */
    private function parse_ai_classification_response($response) {
        // Try to extract JSON from response
        preg_match('/\{.*\}/s', $response, $matches);
        
        if (empty($matches[0])) {
            // Fallback parsing if no JSON found
            return array(
                'category' => 'general',
                'subcategory' => 'unknown',
                'confidence' => 0.5,
                'description' => 'Unable to classify clearly',
                'disposal_method' => 'Consult local waste management guidelines',
                'environmental_impact' => 'Proper disposal helps reduce environmental impact'
            );
        }
        
        $json_data = json_decode($matches[0], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'category' => 'general',
                'subcategory' => 'unknown',
                'confidence' => 0.5,
                'description' => 'Classification parsing error',
                'disposal_method' => 'Consult local waste management guidelines',
                'environmental_impact' => 'Proper disposal helps reduce environmental impact'
            );
        }
        
        // Validate and sanitize data
        $categories = array('recyclable', 'organic', 'hazardous', 'electronic', 'general');
        $category = in_array($json_data['category'] ?? '', $categories) ? $json_data['category'] : 'general';
        
        return array(
            'category' => $category,
            'subcategory' => sanitize_text_field($json_data['subcategory'] ?? 'unknown'),
            'confidence' => min(1.0, max(0.1, floatval($json_data['confidence'] ?? 0.5))),
            'description' => sanitize_text_field($json_data['description'] ?? ''),
            'disposal_method' => sanitize_text_field($json_data['disposal_method'] ?? ''),
            'environmental_impact' => sanitize_text_field($json_data['environmental_impact'] ?? '')
        );
    }
    
    /**
     * Store classification result in database
     */
    private function store_classification_result($user_id, $image_data, $classification_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_ai_classifications';
        
        // Store image in uploads directory
        $upload_dir = wp_upload_dir();
        $ai_dir = $upload_dir['basedir'] . '/environmental-ai/';
        
        if (!file_exists($ai_dir)) {
            wp_mkdir_p($ai_dir);
        }
        
        $image_filename = 'classification_' . time() . '_' . uniqid() . '.jpg';
        $image_path = $ai_dir . $image_filename;
        file_put_contents($image_path, $image_data);
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'image_path' => $image_filename,
                'category' => $classification_data['category'],
                'subcategory' => $classification_data['subcategory'],
                'confidence' => $classification_data['confidence'],
                'description' => $classification_data['description'],
                'disposal_method' => $classification_data['disposal_method'],
                'environmental_impact' => $classification_data['environmental_impact'],
                'created_at' => current_time('mysql'),
                'is_verified' => 0
            ),
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get waste categories
     */
    public function get_waste_categories() {
        return array(
            'recyclable' => array(
                'name' => __('Recyclable', 'env-data-dashboard'),
                'icon' => '♻️',
                'color' => '#28a745',
                'subcategories' => array(
                    'plastic_bottle' => __('Plastic Bottles', 'env-data-dashboard'),
                    'paper' => __('Paper', 'env-data-dashboard'),
                    'cardboard' => __('Cardboard', 'env-data-dashboard'),
                    'glass' => __('Glass', 'env-data-dashboard'),
                    'metal_can' => __('Metal Cans', 'env-data-dashboard')
                )
            ),
            'organic' => array(
                'name' => __('Organic', 'env-data-dashboard'),
                'icon' => '🌱',
                'color' => '#8bc34a',
                'subcategories' => array(
                    'food_waste' => __('Food Waste', 'env-data-dashboard'),
                    'garden_waste' => __('Garden Waste', 'env-data-dashboard'),
                    'compostable' => __('Compostable Materials', 'env-data-dashboard')
                )
            ),
            'hazardous' => array(
                'name' => __('Hazardous', 'env-data-dashboard'),
                'icon' => '⚠️',
                'color' => '#dc3545',
                'subcategories' => array(
                    'battery' => __('Batteries', 'env-data-dashboard'),
                    'chemicals' => __('Chemicals', 'env-data-dashboard'),
                    'paint' => __('Paint', 'env-data-dashboard'),
                    'fluorescent_bulb' => __('Fluorescent Bulbs', 'env-data-dashboard')
                )
            ),
            'electronic' => array(
                'name' => __('Electronic', 'env-data-dashboard'),
                'icon' => '📱',
                'color' => '#6f42c1',
                'subcategories' => array(
                    'phone' => __('Mobile Phones', 'env-data-dashboard'),
                    'computer' => __('Computers', 'env-data-dashboard'),
                    'appliance' => __('Small Appliances', 'env-data-dashboard')
                )
            ),
            'general' => array(
                'name' => __('General Waste', 'env-data-dashboard'),
                'icon' => '🗑️',
                'color' => '#6c757d',
                'subcategories' => array(
                    'mixed' => __('Mixed Waste', 'env-data-dashboard'),
                    'unknown' => __('Unknown', 'env-data-dashboard')
                )
            )
        );
    }
    
    /**
     * Get disposal recommendations
     */
    private function get_disposal_recommendations($classification_data) {
        $recommendations = array(
            'recyclable' => array(
                'action' => __('Place in recycling bin', 'env-data-dashboard'),
                'tips' => array(
                    __('Clean containers before recycling', 'env-data-dashboard'),
                    __('Remove caps and labels if required', 'env-data-dashboard'),
                    __('Check local recycling guidelines', 'env-data-dashboard')
                ),
                'impact' => __('Recycling saves energy and reduces landfill waste', 'env-data-dashboard')
            ),
            'organic' => array(
                'action' => __('Compost or organic waste bin', 'env-data-dashboard'),
                'tips' => array(
                    __('Create a home compost system', 'env-data-dashboard'),
                    __('Use for garden fertilization', 'env-data-dashboard'),
                    __('Avoid composting meat or dairy', 'env-data-dashboard')
                ),
                'impact' => __('Composting reduces methane emissions from landfills', 'env-data-dashboard')
            ),
            'hazardous' => array(
                'action' => __('Take to hazardous waste facility', 'env-data-dashboard'),
                'tips' => array(
                    __('Never put in regular trash', 'env-data-dashboard'),
                    __('Find local collection events', 'env-data-dashboard'),
                    __('Store safely until disposal', 'env-data-dashboard')
                ),
                'impact' => __('Proper disposal prevents soil and water contamination', 'env-data-dashboard')
            ),
            'electronic' => array(
                'action' => __('Electronic waste recycling center', 'env-data-dashboard'),
                'tips' => array(
                    __('Wipe personal data first', 'env-data-dashboard'),
                    __('Consider donation if still working', 'env-data-dashboard'),
                    __('Look for manufacturer take-back programs', 'env-data-dashboard')
                ),
                'impact' => __('E-waste recycling recovers valuable materials and prevents toxic pollution', 'env-data-dashboard')
            )
        );
        
        $category = $classification_data['category'] ?? 'general';
        return $recommendations[$category] ?? array(
            'action' => __('Dispose according to local guidelines', 'env-data-dashboard'),
            'tips' => array(__('Contact local waste management for guidance', 'env-data-dashboard')),
            'impact' => __('Proper disposal helps protect the environment', 'env-data-dashboard')
        );
    }
    
    /**
     * Submit user feedback on classification
     */
    public function submit_ai_feedback($classification_id, $is_correct, $correct_category = '', $user_comment = '') {
        global $wpdb;
        
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        $result = $wpdb->insert(
            $feedback_table,
            array(
                'classification_id' => $classification_id,
                'user_id' => get_current_user_id(),
                'is_correct' => $is_correct ? 1 : 0,
                'correct_category' => $correct_category,
                'user_comment' => $user_comment,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        // Update classification verification status
        if ($result) {
            $wpdb->update(
                $wpdb->prefix . 'env_ai_classifications',
                array('is_verified' => 1, 'updated_at' => current_time('mysql')),
                array('id' => $classification_id),
                array('%d', '%s'),
                array('%d')
            );
            
            // Update AI model accuracy metrics
            $this->update_ai_accuracy_metrics($is_correct);
        }
        
        return $result !== false;
    }
    
    /**
     * Update user AI statistics
     */
    private function update_user_ai_stats($user_id, $classification_data) {
        if (!$user_id) return;
        
        global $wpdb;
        
        $stats_table = $wpdb->prefix . 'env_ai_user_stats';
        
        // Check if user stats exist
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$stats_table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            // Update existing stats
            $wpdb->update(
                $stats_table,
                array(
                    'total_classifications' => $existing->total_classifications + 1,
                    'categories_data' => $this->update_category_stats($existing->categories_data, $classification_data['category']),
                    'last_classification' => current_time('mysql')
                ),
                array('user_id' => $user_id),
                array('%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new stats record
            $categories_data = json_encode(array($classification_data['category'] => 1));
            $wpdb->insert(
                $stats_table,
                array(
                    'user_id' => $user_id,
                    'total_classifications' => 1,
                    'categories_data' => $categories_data,
                    'first_classification' => current_time('mysql'),
                    'last_classification' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Update category statistics
     */
    private function update_category_stats($existing_data, $new_category) {
        $stats = json_decode($existing_data, true) ?: array();
        $stats[$new_category] = ($stats[$new_category] ?? 0) + 1;
        return json_encode($stats);
    }
    
    /**
     * Update AI accuracy metrics
     */
    private function update_ai_accuracy_metrics($is_correct) {
        $current_metrics = get_option('env_ai_accuracy_metrics', array(
            'total_feedback' => 0,
            'correct_predictions' => 0,
            'accuracy_rate' => 0.0,
            'last_updated' => current_time('mysql')
        ));
        
        $current_metrics['total_feedback']++;
        if ($is_correct) {
            $current_metrics['correct_predictions']++;
        }
        
        $current_metrics['accuracy_rate'] = $current_metrics['total_feedback'] > 0 
            ? $current_metrics['correct_predictions'] / $current_metrics['total_feedback'] 
            : 0.0;
        $current_metrics['last_updated'] = current_time('mysql');
        
        update_option('env_ai_accuracy_metrics', $current_metrics);
    }
    
    /**
     * Get user classification history
     */
    public function get_user_classification_history($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'env_ai_classifications';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
        
        foreach ($results as &$result) {
            $result->image_url = wp_upload_dir()['baseurl'] . '/environmental-ai/' . $result->image_path;
            $result->category_info = $this->get_waste_categories()[$result->category] ?? null;
        }
        
        return $results;
    }
    
    /**
     * Get AI service statistics
     */
    public function get_ai_statistics() {
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Total classifications
        $total_classifications = $wpdb->get_var("SELECT COUNT(*) FROM {$classifications_table}");
        
        // Classifications by category
        $category_stats = $wpdb->get_results(
            "SELECT category, COUNT(*) as count FROM {$classifications_table} GROUP BY category"
        );
        
        // Feedback stats
        $feedback_stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_feedback,
                SUM(is_correct) as correct_feedback,
                AVG(is_correct) as accuracy_rate
             FROM {$feedback_table}"
        );
        
        // Recent activity
        $recent_activity = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$classifications_table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at) 
             ORDER BY date DESC"
        );
        
        return array(
            'total_classifications' => intval($total_classifications),
            'category_distribution' => $category_stats,
            'accuracy_metrics' => $feedback_stats,
            'recent_activity' => $recent_activity,
            'waste_categories' => $this->get_waste_categories()
        );
    }
    
    /**
     * AJAX handler for waste image classification
     */
    public function ajax_classify_waste_image() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        if (!isset($_FILES['image'])) {
            wp_send_json_error(array('message' => __('No image uploaded', 'env-data-dashboard')));
        }
        
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
        $user_id = get_current_user_id();
        
        $result = $this->classify_waste_image($image_data, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for AI feedback submission
     */
    public function ajax_submit_ai_feedback() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $classification_id = intval($_POST['classification_id'] ?? 0);
        $is_correct = filter_var($_POST['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $correct_category = sanitize_text_field($_POST['correct_category'] ?? '');
        $user_comment = sanitize_textarea_field($_POST['user_comment'] ?? '');
        
        if (!$classification_id) {
            wp_send_json_error(array('message' => __('Invalid classification ID', 'env-data-dashboard')));
        }
        
        $result = $this->submit_ai_feedback($classification_id, $is_correct, $correct_category, $user_comment);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Feedback submitted successfully', 'env-data-dashboard')));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit feedback', 'env-data-dashboard')));
        }
    }
    
    /**
     * AJAX handler for classification history
     */
    public function ajax_get_classification_history() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'env-data-dashboard')));
        }
        
        $limit = intval($_POST['limit'] ?? 20);
        $offset = intval($_POST['offset'] ?? 0);
        
        $history = $this->get_user_classification_history($user_id, $limit, $offset);
        
        wp_send_json_success(array('history' => $history));
    }
    
    /**
     * AJAX handler for AI statistics
     */
    public function ajax_get_ai_stats() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $stats = $this->get_ai_statistics();
        
        wp_send_json_success($stats);
    }
    
    /**
     * REST API: Classify waste
     */
    public function rest_classify_waste($request) {
        $image_data = $request->get_param('image');
        $user_id = get_current_user_id();
        
        $result = $this->classify_waste_image($image_data, $user_id);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('classification_failed', $result['error'], array('status' => 400));
        }
    }
    
    /**
     * REST API: Submit feedback
     */
    public function rest_submit_feedback($request) {
        $classification_id = $request->get_param('classification_id');
        $is_correct = $request->get_param('is_correct');
        $correct_category = $request->get_param('correct_category');
        $user_comment = $request->get_param('user_comment');
        
        $result = $this->submit_ai_feedback($classification_id, $is_correct, $correct_category, $user_comment);
        
        return new WP_REST_Response(array('success' => $result), $result ? 200 : 400);
    }
    
    /**
     * REST API: Get AI statistics
     */
    public function rest_get_ai_stats($request) {
        $stats = $this->get_ai_statistics();
        return new WP_REST_Response($stats, 200);
    }
    
    /**
     * Validate image data for REST API
     */
    public function validate_image_data($value, $request, $param) {
        if (empty($value)) {
            return new WP_Error('invalid_image', __('Image data is required', 'env-data-dashboard'));
        }
        
        // Basic validation - could be enhanced
        return true;
    }
    
    /**
     * Daily AI model training (cron job)
     */
    public function daily_model_training() {
        // This would integrate with external AI training services
        // For now, we'll update internal metrics and prepare training data
        
        global $wpdb;
        
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Get recent feedback for training
        $training_data = $wpdb->get_results(
            "SELECT c.*, f.is_correct, f.correct_category 
             FROM {$wpdb->prefix}env_ai_classifications c
             JOIN {$feedback_table} f ON c.id = f.classification_id
             WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        if (count($training_data) > 10) {
            // Prepare training data export
            $this->prepare_training_data_export($training_data);
        }
        
        // Update model performance metrics
        $this->update_model_performance_metrics();
    }
    
    /**
     * Prepare training data for export
     */
    private function prepare_training_data_export($training_data) {
        $export_data = array();
        
        foreach ($training_data as $item) {
            $export_data[] = array(
                'image_path' => $item->image_path,
                'predicted_category' => $item->category,
                'actual_category' => $item->correct_category ?: $item->category,
                'is_correct' => $item->is_correct,
                'confidence' => $item->confidence,
                'timestamp' => $item->created_at
            );
        }
        
        // Save to training data directory
        $upload_dir = wp_upload_dir();
        $training_dir = $upload_dir['basedir'] . '/environmental-ai/training/';
        
        if (!file_exists($training_dir)) {
            wp_mkdir_p($training_dir);
        }
        
        $filename = 'training_data_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($training_dir . $filename, json_encode($export_data, JSON_PRETTY_PRINT));
        
        // Log training data preparation
        error_log("AI Training Data Prepared: {$filename} with " . count($export_data) . " samples");
    }
    
    /**
     * Update model performance metrics
     */
    private function update_model_performance_metrics() {
        global $wpdb;
        
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Calculate weekly accuracy
        $weekly_stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(is_correct) as correct,
                AVG(is_correct) as accuracy
             FROM {$feedback_table} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Update performance metrics
        $performance_data = get_option('env_ai_performance_history', array());
        $performance_data[date('Y-m-d')] = array(
            'accuracy' => floatval($weekly_stats->accuracy ?? 0),
            'total_feedback' => intval($weekly_stats->total ?? 0),
            'correct_predictions' => intval($weekly_stats->correct ?? 0)
        );
        
        // Keep only last 30 days
        $performance_data = array_slice($performance_data, -30, 30, true);
        update_option('env_ai_performance_history', $performance_data);
    }
    
    /**
     * Cleanup old training data (cron job)
     */
    public function cleanup_old_training_data() {
        // Clean up old images and training data
        $upload_dir = wp_upload_dir();
        $ai_dir = $upload_dir['basedir'] . '/environmental-ai/';
        
        if (is_dir($ai_dir)) {
            $files = scandir($ai_dir);
            $cutoff_time = time() - (90 * 24 * 60 * 60); // 90 days
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && $file !== 'training') {
                    $file_path = $ai_dir . $file;
                    if (is_file($file_path) && filemtime($file_path) < $cutoff_time) {
                        unlink($file_path);
                    }
                }
            }
        }
        
        // Clean up old database records
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Remove classifications older than 1 year without feedback
        $wpdb->query(
            "DELETE c FROM {$classifications_table} c
             LEFT JOIN {$feedback_table} f ON c.id = f.classification_id
             WHERE c.created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
             AND f.id IS NULL"
        );
    }
}

// Initialize the AI Service Manager
AI_Service_Manager::get_instance();
