<?php
/**
 * Chatbot System Class
 * 
 * Handles automated responses, AI integration, and chatbot functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Chatbot_System {
    
    private static $instance = null;
    private $table_sessions;
    private $table_messages;
    private $chatbot_responses;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_sessions = $wpdb->prefix . 'elc_chat_sessions';
        $this->table_messages = $wpdb->prefix . 'elc_chat_messages';
        
        $this->load_chatbot_responses();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('elc_new_message', array($this, 'process_visitor_message'), 10, 3);
        add_action('wp_ajax_elc_chatbot_response', array($this, 'get_chatbot_response'));
        add_action('wp_ajax_nopriv_elc_chatbot_response', array($this, 'get_chatbot_response'));
        
        add_action('wp_ajax_elc_train_chatbot', array($this, 'train_chatbot'));
        add_action('wp_ajax_elc_update_responses', array($this, 'update_responses'));
    }
    
    /**
     * Load chatbot responses from options
     */
    private function load_chatbot_responses() {
        $default_responses = array(
            'greeting' => array(
                'patterns' => array('hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'),
                'responses' => array(
                    'Hello! Welcome to our environmental platform. How can I help you today?',
                    'Hi there! I\'m here to help with any questions about our environmental services.',
                    'Welcome! How can I assist you with your environmental needs today?'
                )
            ),
            'about_services' => array(
                'patterns' => array('services', 'what do you do', 'help with', 'environmental services'),
                'responses' => array(
                    'We offer comprehensive environmental services including waste management, air quality monitoring, water treatment, and sustainability consulting. Which area interests you most?',
                    'Our platform provides environmental solutions for businesses and individuals. We specialize in monitoring, reporting, and improving environmental impact. What specific service are you looking for?'
                )
            ),
            'waste_management' => array(
                'patterns' => array('waste', 'garbage', 'recycling', 'disposal', 'trash'),
                'responses' => array(
                    'Our waste management services include recycling programs, waste audits, and sustainable disposal solutions. Would you like to know more about any specific service?',
                    'We help businesses and communities implement effective waste reduction and recycling programs. What type of waste management support do you need?'
                )
            ),
            'air_quality' => array(
                'patterns' => array('air quality', 'pollution', 'emissions', 'monitoring'),
                'responses' => array(
                    'We provide comprehensive air quality monitoring and emissions tracking services. Our systems help identify pollution sources and track improvement over time.',
                    'Our air quality services include real-time monitoring, compliance reporting, and recommendations for improving air quality. What specific information do you need?'
                )
            ),
            'water_treatment' => array(
                'patterns' => array('water', 'treatment', 'purification', 'quality'),
                'responses' => array(
                    'Our water treatment solutions include quality testing, purification systems, and treatment facility management. How can we help with your water needs?',
                    'We offer water quality assessment, treatment system design, and ongoing monitoring services. What type of water treatment are you interested in?'
                )
            ),
            'sustainability' => array(
                'patterns' => array('sustainability', 'green', 'eco', 'carbon footprint', 'renewable'),
                'responses' => array(
                    'We help organizations develop and implement comprehensive sustainability strategies, including carbon footprint reduction and renewable energy adoption.',
                    'Our sustainability consulting covers energy efficiency, carbon management, and green certification programs. What sustainability goals are you working toward?'
                )
            ),
            'contact' => array(
                'patterns' => array('contact', 'phone', 'email', 'address', 'location'),
                'responses' => array(
                    'You can reach us by phone, email, or through this chat system. Would you like me to connect you with a human representative for more detailed assistance?',
                    'I can help you get in touch with our team. Would you prefer to speak with someone directly, or can I continue helping you here?'
                )
            ),
            'pricing' => array(
                'patterns' => array('price', 'cost', 'fee', 'pricing', 'how much'),
                'responses' => array(
                    'Our pricing varies based on the specific services and scope of work. I\'d be happy to connect you with our sales team for a customized quote. What services are you interested in?',
                    'We offer competitive pricing for all our environmental services. Let me connect you with a specialist who can provide detailed pricing information for your needs.'
                )
            ),
            'emergency' => array(
                'patterns' => array('emergency', 'urgent', 'spill', 'contamination', 'leak'),
                'responses' => array(
                    'For environmental emergencies, please call our 24/7 emergency hotline immediately. I\'m also connecting you with our emergency response team right now.',
                    'This sounds like an urgent situation. I\'m immediately escalating your chat to our emergency response team. Please stay on the line.'
                )
            ),
            'goodbye' => array(
                'patterns' => array('bye', 'goodbye', 'thanks', 'thank you', 'that\'s all'),
                'responses' => array(
                    'Thank you for contacting us! If you need any additional help, feel free to start another chat or contact our support team.',
                    'You\'re welcome! We\'re here whenever you need environmental expertise. Have a great day!',
                    'Glad I could help! Don\'t hesitate to reach out if you have more questions about our environmental services.'
                )
            ),
            'fallback' => array(
                'patterns' => array(),
                'responses' => array(
                    'I want to make sure I understand your question correctly. Could you rephrase that, or would you like me to connect you with a human specialist?',
                    'I\'m not sure I fully understand. Let me connect you with one of our environmental experts who can provide more detailed assistance.',
                    'That\'s a great question that might be best answered by our specialists. Would you like me to transfer you to a human representative?'
                )
            )
        );
        
        $this->chatbot_responses = get_option('elc_chatbot_responses', $default_responses);
    }
    
    /**
     * Process visitor message and potentially respond with chatbot
     */
    public function process_visitor_message($session_id, $message_id, $sender_type) {
        if ($sender_type !== 'visitor') {
            return;
        }
        
        // Check if chatbot is enabled and no operator is assigned
        $chatbot_enabled = get_option('elc_chatbot_enabled', true);
        if (!$chatbot_enabled) {
            return;
        }
        
        $session = $this->get_session($session_id);
        if (!$session || $session->operator_id) {
            return; // Don't respond if human operator is present
        }
        
        // Get the message
        global $wpdb;
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_messages} WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            return;
        }
        
        // Generate chatbot response
        $response = $this->generate_response($message->message, $session_id);
        
        if ($response) {
            // Add small delay to make it feel more natural
            sleep(1);
            
            // Save chatbot response
            $wpdb->insert(
                $this->table_messages,
                array(
                    'session_id' => $session_id,
                    'sender_type' => 'chatbot',
                    'sender_name' => 'Environmental Assistant',
                    'message' => $response,
                    'sent_at' => current_time('mysql')
                )
            );
            
            // Check if response indicates need for human operator
            if ($this->requires_human_operator($message->message, $response)) {
                $this->request_human_operator($session_id);
            }
        }
    }
    
    /**
     * Generate chatbot response based on message content
     */
    private function generate_response($message, $session_id = null) {
        $message = strtolower(trim($message));
        
        // Remove common punctuation
        $message = preg_replace('/[^\w\s]/', ' ', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        
        $best_match = null;
        $best_score = 0;
        
        // Check each response category
        foreach ($this->chatbot_responses as $category => $data) {
            if (empty($data['patterns'])) {
                continue;
            }
            
            $score = $this->calculate_match_score($message, $data['patterns']);
            
            if ($score > $best_score && $score > 0.3) { // Minimum threshold
                $best_match = $category;
                $best_score = $score;
            }
        }
        
        // Return response based on best match
        if ($best_match && isset($this->chatbot_responses[$best_match]['responses'])) {
            $responses = $this->chatbot_responses[$best_match]['responses'];
            $response = $responses[array_rand($responses)];
            
            // Log successful match for analytics
            $this->log_chatbot_interaction($session_id, $message, $best_match, $best_score);
            
            return $response;
        }
        
        // Fallback response
        $fallback_responses = $this->chatbot_responses['fallback']['responses'];
        return $fallback_responses[array_rand($fallback_responses)];
    }
    
    /**
     * Calculate match score between message and patterns
     */
    private function calculate_match_score($message, $patterns) {
        $message_words = explode(' ', $message);
        $total_score = 0;
        $max_possible_score = 0;
        
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            $pattern_words = explode(' ', $pattern);
            $max_possible_score += count($pattern_words);
            
            // Exact phrase match gets high score
            if (strpos($message, $pattern) !== false) {
                $total_score += count($pattern_words) * 2;
                continue;
            }
            
            // Individual word matches
            foreach ($pattern_words as $pattern_word) {
                if (in_array($pattern_word, $message_words)) {
                    $total_score += 1;
                }
                
                // Partial word match
                foreach ($message_words as $message_word) {
                    if (strlen($pattern_word) > 3 && strpos($message_word, $pattern_word) !== false) {
                        $total_score += 0.5;
                    }
                }
            }
        }
        
        return $max_possible_score > 0 ? $total_score / $max_possible_score : 0;
    }
    
    /**
     * Check if response indicates need for human operator
     */
    private function requires_human_operator($message, $response) {
        $escalation_keywords = array(
            'emergency', 'urgent', 'complaint', 'problem', 'issue', 'technical',
            'billing', 'refund', 'cancel', 'pricing', 'quote', 'contract'
        );
        
        $message = strtolower($message);
        
        foreach ($escalation_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        // Check if response contains escalation phrases
        $escalation_phrases = array(
            'connect you with', 'transfer you to', 'human representative',
            'emergency response', 'specialist'
        );
        
        $response = strtolower($response);
        
        foreach ($escalation_phrases as $phrase) {
            if (strpos($response, $phrase) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Request human operator for session
     */
    private function request_human_operator($session_id) {
        global $wpdb;
        
        // Update session to request operator
        $wpdb->update(
            $this->table_sessions,
            array(
                'status' => 'waiting',
                'chatbot_escalated' => 1,
                'last_activity' => current_time('mysql')
            ),
            array('id' => $session_id)
        );
        
        // Send notification message
        $wpdb->insert(
            $this->table_messages,
            array(
                'session_id' => $session_id,
                'sender_type' => 'system',
                'sender_name' => 'System',
                'message' => 'I\'m connecting you with one of our specialists who can better assist you. Please wait a moment.',
                'sent_at' => current_time('mysql')
            )
        );
        
        // Notify available operators
        do_action('elc_chatbot_escalation', $session_id);
    }
    
    /**
     * AJAX endpoint for direct chatbot response
     */
    public function get_chatbot_response() {
        check_ajax_referer('elc_chat_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = intval($_POST['session_id'] ?? 0);
        
        if (empty($message)) {
            wp_send_json_error(array('message' => __('Message is required.', 'environmental-live-chat')));
        }
        
        $response = $this->generate_response($message, $session_id);
        
        wp_send_json_success(array(
            'response' => $response,
            'requires_human' => $this->requires_human_operator($message, $response)
        ));
    }
    
    /**
     * Train chatbot with new patterns and responses
     */
    public function train_chatbot() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        $patterns = array_map('sanitize_text_field', $_POST['patterns'] ?? array());
        $responses = array_map('sanitize_textarea_field', $_POST['responses'] ?? array());
        
        if (empty($category) || empty($patterns) || empty($responses)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'environmental-live-chat')));
        }
        
        // Update chatbot responses
        $this->chatbot_responses[$category] = array(
            'patterns' => array_filter($patterns),
            'responses' => array_filter($responses)
        );
        
        update_option('elc_chatbot_responses', $this->chatbot_responses);
        
        wp_send_json_success(array('message' => __('Chatbot trained successfully.', 'environmental-live-chat')));
    }
    
    /**
     * Update existing chatbot responses
     */
    public function update_responses() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        $responses = $_POST['responses'] ?? array();
        
        foreach ($responses as $category => $data) {
            if (isset($this->chatbot_responses[$category])) {
                $this->chatbot_responses[$category]['patterns'] = array_map('sanitize_text_field', $data['patterns'] ?? array());
                $this->chatbot_responses[$category]['responses'] = array_map('sanitize_textarea_field', $data['responses'] ?? array());
            }
        }
        
        update_option('elc_chatbot_responses', $this->chatbot_responses);
        
        wp_send_json_success(array('message' => __('Responses updated successfully.', 'environmental-live-chat')));
    }
    
    /**
     * Get session data
     */
    private function get_session($session_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_sessions} WHERE id = %d",
            $session_id
        ));
    }
    
    /**
     * Log chatbot interaction for analytics
     */
    private function log_chatbot_interaction($session_id, $message, $category, $confidence_score) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'elc_analytics';
        
        $wpdb->insert(
            $analytics_table,
            array(
                'metric_type' => 'chatbot_interaction',
                'metric_value' => $confidence_score,
                'metadata' => json_encode(array(
                    'session_id' => $session_id,
                    'message' => substr($message, 0, 255),
                    'category' => $category,
                    'confidence' => $confidence_score
                )),
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get chatbot analytics
     */
    public function get_chatbot_analytics($date_from = null, $date_to = null) {
        global $wpdb;
        
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        $analytics_table = $wpdb->prefix . 'elc_analytics';
        
        // Total chatbot interactions
        $total_interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$analytics_table}
             WHERE metric_type = 'chatbot_interaction'
             AND DATE(recorded_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Average confidence score
        $avg_confidence = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(metric_value) FROM {$analytics_table}
             WHERE metric_type = 'chatbot_interaction'
             AND DATE(recorded_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Most common categories
        $category_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT JSON_EXTRACT(metadata, '$.category') as category, COUNT(*) as count
             FROM {$analytics_table}
             WHERE metric_type = 'chatbot_interaction'
             AND DATE(recorded_at) BETWEEN %s AND %s
             GROUP BY JSON_EXTRACT(metadata, '$.category')
             ORDER BY count DESC
             LIMIT 10",
            $date_from, $date_to
        ));
        
        // Escalation rate
        $escalated_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT id) FROM {$this->table_sessions}
             WHERE chatbot_escalated = 1
             AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        $total_chatbot_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT id) FROM {$this->table_sessions}
             WHERE operator_id IS NULL
             AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        $escalation_rate = $total_chatbot_sessions > 0 ? ($escalated_sessions / $total_chatbot_sessions) * 100 : 0;
        
        return array(
            'total_interactions' => $total_interactions,
            'avg_confidence' => round($avg_confidence, 2),
            'category_stats' => $category_stats,
            'escalation_rate' => round($escalation_rate, 2),
            'total_sessions' => $total_chatbot_sessions,
            'escalated_sessions' => $escalated_sessions
        );
    }
    
    /**
     * Get current chatbot responses for editing
     */
    public function get_responses() {
        return $this->chatbot_responses;
    }
    
    /**
     * Import responses from file
     */
    public function import_responses($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $content = file_get_contents($file_path);
        $responses = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($responses)) {
            $this->chatbot_responses = array_merge($this->chatbot_responses, $responses);
            update_option('elc_chatbot_responses', $this->chatbot_responses);
            return true;
        }
        
        return false;
    }
    
    /**
     * Export responses to file
     */
    public function export_responses() {
        return json_encode($this->chatbot_responses, JSON_PRETTY_PRINT);
    }
}

// Initialize the chatbot system
Environmental_Chatbot_System::get_instance();
