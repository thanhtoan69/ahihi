<?php
/**
 * Validator Utility Class
 *
 * Provides comprehensive validation functionality for the Environmental Email Marketing plugin
 * including email validation, data sanitization, and business rule validation.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Validator {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Validation rules
     */
    private $rules = array();

    /**
     * Error messages
     */
    private $errors = array();

    /**
     * Custom validation messages
     */
    private $custom_messages = array();

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
        $this->logger = EEM_Logger::get_instance();
        $this->init_custom_messages();
    }

    /**
     * Initialize custom validation messages
     */
    private function init_custom_messages() {
        $this->custom_messages = array(
            'required' => 'The %s field is required.',
            'email' => 'The %s field must be a valid email address.',
            'min_length' => 'The %s field must be at least %d characters.',
            'max_length' => 'The %s field must not exceed %d characters.',
            'numeric' => 'The %s field must be numeric.',
            'integer' => 'The %s field must be an integer.',
            'positive' => 'The %s field must be a positive number.',
            'url' => 'The %s field must be a valid URL.',
            'date' => 'The %s field must be a valid date.',
            'regex' => 'The %s field format is invalid.',
            'in' => 'The %s field must be one of: %s.',
            'unique_email' => 'The email address is already subscribed.',
            'environmental_score' => 'The environmental score must be between 0 and 100.',
            'campaign_name' => 'Campaign name must be unique.',
            'template_name' => 'Template name must be unique.',
            'list_name' => 'List name must be unique.',
            'strong_password' => 'Password must be at least 8 characters with uppercase, lowercase, and number.',
            'phone' => 'The %s field must be a valid phone number.',
            'postal_code' => 'The %s field must be a valid postal code.',
            'json' => 'The %s field must be valid JSON.',
            'array' => 'The %s field must be an array.',
            'boolean' => 'The %s field must be true or false.',
            'hex_color' => 'The %s field must be a valid hex color.',
            'timezone' => 'The %s field must be a valid timezone.'
        );
    }

    /**
     * Validate data against rules
     */
    public function validate($data, $rules, $custom_messages = array()) {
        $this->errors = array();
        $this->rules = $rules;
        
        // Merge custom messages
        if (!empty($custom_messages)) {
            $this->custom_messages = array_merge($this->custom_messages, $custom_messages);
        }

        foreach ($rules as $field => $field_rules) {
            $this->validate_field($field, $data, $field_rules);
        }

        return empty($this->errors);
    }

    /**
     * Validate single field
     */
    private function validate_field($field, $data, $rules) {
        $value = isset($data[$field]) ? $data[$field] : null;
        $rules_array = is_string($rules) ? explode('|', $rules) : $rules;

        foreach ($rules_array as $rule) {
            $this->apply_rule($field, $value, $rule, $data);
        }
    }

    /**
     * Apply validation rule
     */
    private function apply_rule($field, $value, $rule, $all_data) {
        // Parse rule and parameters
        $rule_parts = explode(':', $rule);
        $rule_name = $rule_parts[0];
        $parameters = isset($rule_parts[1]) ? explode(',', $rule_parts[1]) : array();

        // Skip validation if field is not required and empty
        if ($rule_name !== 'required' && $this->is_empty($value)) {
            return;
        }

        $method_name = 'validate_' . $rule_name;
        
        if (method_exists($this, $method_name)) {
            $result = call_user_func(array($this, $method_name), $field, $value, $parameters, $all_data);
            
            if (!$result) {
                $this->add_error($field, $rule_name, $parameters);
            }
        } else {
            $this->logger->warning("Unknown validation rule: {$rule_name}");
        }
    }

    /**
     * Add validation error
     */
    private function add_error($field, $rule, $parameters = array()) {
        $message = $this->get_error_message($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = array();
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message
     */
    private function get_error_message($field, $rule, $parameters = array()) {
        $template = isset($this->custom_messages[$rule]) ? $this->custom_messages[$rule] : "The {$field} field is invalid.";
        
        // Replace placeholders
        $message = sprintf($template, $this->format_field_name($field), ...$parameters);
        
        return $message;
    }

    /**
     * Format field name for display
     */
    private function format_field_name($field) {
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Check if value is empty
     */
    private function is_empty($value) {
        return is_null($value) || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function get_first_error() {
        if (empty($this->errors)) {
            return null;
        }
        
        $first_field_errors = reset($this->errors);
        return is_array($first_field_errors) ? $first_field_errors[0] : $first_field_errors;
    }

    /**
     * Get errors for specific field
     */
    public function get_field_errors($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : array();
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }

    // Validation Rules

    /**
     * Validate required field
     */
    protected function validate_required($field, $value, $parameters, $all_data) {
        return !$this->is_empty($value);
    }

    /**
     * Validate email
     */
    protected function validate_email($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return is_email($value);
    }

    /**
     * Validate minimum length
     */
    protected function validate_min_length($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        $min_length = isset($parameters[0]) ? intval($parameters[0]) : 0;
        return strlen($value) >= $min_length;
    }

    /**
     * Validate maximum length
     */
    protected function validate_max_length($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        $max_length = isset($parameters[0]) ? intval($parameters[0]) : 255;
        return strlen($value) <= $max_length;
    }

    /**
     * Validate numeric value
     */
    protected function validate_numeric($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return is_numeric($value);
    }

    /**
     * Validate integer value
     */
    protected function validate_integer($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate positive number
     */
    protected function validate_positive($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return is_numeric($value) && $value > 0;
    }

    /**
     * Validate URL
     */
    protected function validate_url($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate date
     */
    protected function validate_date($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        $format = isset($parameters[0]) ? $parameters[0] : 'Y-m-d';
        $date = DateTime::createFromFormat($format, $value);
        
        return $date && $date->format($format) === $value;
    }

    /**
     * Validate regex pattern
     */
    protected function validate_regex($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        $pattern = isset($parameters[0]) ? $parameters[0] : '';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate value is in array
     */
    protected function validate_in($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return in_array($value, $parameters);
    }

    /**
     * Validate unique email
     */
    protected function validate_unique_email($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'eem_subscribers';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND status != 'unsubscribed'",
            $value
        ));
        
        return $existing == 0;
    }

    /**
     * Validate environmental score
     */
    protected function validate_environmental_score($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return is_numeric($value) && $value >= 0 && $value <= 100;
    }

    /**
     * Validate campaign name uniqueness
     */
    protected function validate_campaign_name($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'eem_campaigns';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE name = %s";
        $params = array($value);
        
        // Exclude current campaign if updating
        if (isset($all_data['campaign_id'])) {
            $query .= " AND id != %d";
            $params[] = $all_data['campaign_id'];
        }
        
        $existing = $wpdb->get_var($wpdb->prepare($query, $params));
        return $existing == 0;
    }

    /**
     * Validate strong password
     */
    protected function validate_strong_password($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        // At least 8 characters, one uppercase, one lowercase, one number
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate phone number
     */
    protected function validate_phone($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        // Basic phone validation - digits, spaces, hyphens, parentheses, plus
        $pattern = '/^[\+]?[0-9\s\-\(\)]{7,15}$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate postal code
     */
    protected function validate_postal_code($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        $country = isset($parameters[0]) ? $parameters[0] : 'US';
        
        switch (strtoupper($country)) {
            case 'US':
                return preg_match('/^\d{5}(-\d{4})?$/', $value) === 1;
            case 'CA':
                return preg_match('/^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/', $value) === 1;
            case 'UK':
                return preg_match('/^[A-Za-z]{1,2}\d[A-Za-z\d]?\s\d[A-Za-z]{2}$/', $value) === 1;
            default:
                return preg_match('/^[A-Za-z0-9\s\-]{3,10}$/', $value) === 1;
        }
    }

    /**
     * Validate JSON
     */
    protected function validate_json($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate array
     */
    protected function validate_array($field, $value, $parameters, $all_data) {
        return is_array($value);
    }

    /**
     * Validate boolean
     */
    protected function validate_boolean($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return in_array($value, array(true, false, 1, 0, '1', '0', 'true', 'false'), true);
    }

    /**
     * Validate hex color
     */
    protected function validate_hex_color($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) === 1;
    }

    /**
     * Validate timezone
     */
    protected function validate_timezone($field, $value, $parameters, $all_data) {
        if ($this->is_empty($value)) {
            return true;
        }
        
        return in_array($value, timezone_identifiers_list());
    }

    // Sanitization Methods

    /**
     * Sanitize input data
     */
    public function sanitize($data, $rules) {
        $sanitized = array();
        
        foreach ($data as $field => $value) {
            $field_rules = isset($rules[$field]) ? $rules[$field] : array();
            $sanitized[$field] = $this->sanitize_field($value, $field_rules);
        }
        
        return $sanitized;
    }

    /**
     * Sanitize single field
     */
    private function sanitize_field($value, $rules) {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        foreach ($rules as $rule) {
            $value = $this->apply_sanitization($value, $rule);
        }
        
        return $value;
    }

    /**
     * Apply sanitization rule
     */
    private function apply_sanitization($value, $rule) {
        switch ($rule) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'text':
                return sanitize_text_field($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'html':
                return wp_kses_post($value);
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'boolean':
                return (bool) $value;
            case 'slug':
                return sanitize_title($value);
            case 'filename':
                return sanitize_file_name($value);
            case 'hex_color':
                return sanitize_hex_color($value);
            case 'key':
                return sanitize_key($value);
            case 'user':
                return sanitize_user($value);
            case 'strip_tags':
                return strip_tags($value);
            case 'trim':
                return trim($value);
            case 'lowercase':
                return strtolower($value);
            case 'uppercase':
                return strtoupper($value);
            default:
                return $value;
        }
    }

    // Validation Helpers

    /**
     * Validate email subscription data
     */
    public function validate_subscription($data) {
        $rules = array(
            'email' => 'required|email|unique_email',
            'name' => 'required|max_length:255',
            'preferences' => 'array',
            'source' => 'max_length:100',
            'environmental_interests' => 'array'
        );
        
        return $this->validate($data, $rules);
    }

    /**
     * Validate campaign data
     */
    public function validate_campaign($data) {
        $rules = array(
            'name' => 'required|max_length:255|campaign_name',
            'subject' => 'required|max_length:255',
            'content' => 'required',
            'sender_name' => 'required|max_length:255',
            'sender_email' => 'required|email',
            'reply_to' => 'email',
            'list_ids' => 'required|array',
            'send_date' => 'date',
            'environmental_theme' => 'in:nature_green,earth_blue,climate_red,sustainable_brown,clean_white'
        );
        
        return $this->validate($data, $rules);
    }

    /**
     * Validate template data
     */
    public function validate_template($data) {
        $rules = array(
            'name' => 'required|max_length:255',
            'subject' => 'required|max_length:255',
            'content' => 'required',
            'type' => 'required|in:email,automation,newsletter',
            'category' => 'max_length:100',
            'variables' => 'json'
        );
        
        return $this->validate($data, $rules);
    }

    /**
     * Validate automation data
     */
    public function validate_automation($data) {
        $rules = array(
            'name' => 'required|max_length:255',
            'trigger_type' => 'required|in:welcome,environmental_action,purchase,quiz_completion,event_registration',
            'conditions' => 'json',
            'actions' => 'required|json',
            'delay_value' => 'integer|positive',
            'delay_unit' => 'in:minutes,hours,days,weeks',
            'is_active' => 'boolean'
        );
        
        return $this->validate($data, $rules);
    }

    /**
     * Validate settings data
     */
    public function validate_settings($data) {
        $rules = array(
            'api_key' => 'max_length:255',
            'sender_name' => 'max_length:255',
            'sender_email' => 'email',
            'reply_to_email' => 'email',
            'unsubscribe_page_id' => 'integer',
            'double_opt_in' => 'boolean',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'environmental_scoring' => 'boolean',
            'carbon_offset_tracking' => 'boolean'
        );
        
        return $this->validate($data, $rules);
    }

    /**
     * Validate API credentials
     */
    public function validate_api_credentials($provider, $credentials) {
        $rules = array();
        
        switch ($provider) {
            case 'mailchimp':
                $rules = array(
                    'api_key' => 'required|regex:/^[a-f0-9]{32}-[a-z]{2,3}\d+$/',
                    'server' => 'required|max_length:10'
                );
                break;
                
            case 'sendgrid':
                $rules = array(
                    'api_key' => 'required|regex:/^SG\.[\w\-]{22}\.[\w\-]{43}$/'
                );
                break;
                
            case 'mailgun':
                $rules = array(
                    'api_key' => 'required|min_length:32',
                    'domain' => 'required|url'
                );
                break;
        }
        
        return $this->validate($credentials, $rules);
    }

    /**
     * Quick validation methods
     */
    public function is_valid_email($email) {
        return $this->validate(array('email' => $email), array('email' => 'required|email'));
    }

    public function is_valid_url($url) {
        return $this->validate(array('url' => $url), array('url' => 'required|url'));
    }

    public function is_valid_environmental_score($score) {
        return $this->validate(array('score' => $score), array('score' => 'required|environmental_score'));
    }

    public function is_valid_json($json) {
        return $this->validate(array('json' => $json), array('json' => 'required|json'));
    }

    /**
     * Custom validation rule registration
     */
    public function add_rule($name, $callback, $message = null) {
        if (is_callable($callback)) {
            $method_name = 'validate_' . $name;
            $this->$method_name = $callback;
            
            if ($message) {
                $this->custom_messages[$name] = $message;
            }
        }
    }

    /**
     * Get validation summary
     */
    public function get_validation_summary() {
        $summary = array(
            'passed' => $this->passes(),
            'error_count' => 0,
            'field_count' => 0,
            'errors' => $this->errors
        );
        
        foreach ($this->errors as $field => $field_errors) {
            $summary['field_count']++;
            $summary['error_count'] += count($field_errors);
        }
        
        return $summary;
    }
}

// Initialize the validator
EEM_Validator::get_instance();
