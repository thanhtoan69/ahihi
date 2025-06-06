<?php
/**
 * Google Analytics 4 Integration for Environmental Analytics
 * 
 * Handles GA4 tracking, custom events, and data synchronization
 * for environmental platform analytics.
 * 
 * @package Environmental_Analytics
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_GA4_Integration {
    
    private $ga4_measurement_id;
    private $ga4_api_secret;
    private $tracking_manager;
    private $debug_mode;
    
    /**
     * Constructor
     */
    public function __construct($tracking_manager) {
        $this->tracking_manager = $tracking_manager;
        $this->ga4_measurement_id = get_option('env_analytics_ga4_measurement_id');
        $this->ga4_api_secret = get_option('env_analytics_ga4_api_secret');
        $this->debug_mode = get_option('env_analytics_ga4_debug', false);
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Frontend tracking
        add_action('wp_head', array($this, 'output_ga4_script'));
        add_action('wp_footer', array($this, 'output_custom_events_script'));
        
        // Event tracking hooks
        add_action('env_track_event', array($this, 'send_event_to_ga4'), 10, 3);
        
        // Admin hooks
        add_action('wp_ajax_env_test_ga4_connection', array($this, 'ajax_test_ga4_connection'));
        add_action('wp_ajax_env_sync_ga4_data', array($this, 'ajax_sync_ga4_data'));
        
        // Settings hooks
        add_action('admin_init', array($this, 'register_ga4_settings'));
    }
    
    /**
     * Output GA4 tracking script in head
     */
    public function output_ga4_script() {
        if (empty($this->ga4_measurement_id)) {
            return;
        }
        
        // Skip tracking for admin users if option is set
        if (current_user_can('manage_options') && get_option('env_analytics_exclude_admins', true)) {
            return;
        }
        
        ?>
        <!-- Environmental Analytics - Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->ga4_measurement_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            
            gtag('config', '<?php echo esc_js($this->ga4_measurement_id); ?>', {
                <?php if ($this->debug_mode): ?>
                debug_mode: true,
                <?php endif; ?>
                custom_map: {
                    'custom_parameter_1': 'environmental_action_type',
                    'custom_parameter_2': 'user_engagement_level',
                    'custom_parameter_3': 'content_category'
                },
                user_properties: {
                    <?php if (is_user_logged_in()): ?>
                    user_type: 'logged_in',
                    user_id: '<?php echo esc_js(get_current_user_id()); ?>',
                    <?php else: ?>
                    user_type: 'anonymous',
                    <?php endif; ?>
                    platform_version: '<?php echo esc_js(ENV_ANALYTICS_VERSION); ?>'
                }
            });
            
            // Environmental Analytics custom tracking
            window.envAnalytics = {
                trackEvent: function(action, category, data) {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', action, {
                            event_category: category,
                            environmental_action_type: data.action_type || '',
                            user_engagement_level: data.engagement_level || '',
                            content_category: data.content_category || '',
                            value: data.value || 0,
                            custom_parameter_1: data.action_type || '',
                            custom_parameter_2: data.engagement_level || '',
                            custom_parameter_3: data.content_category || ''
                        });
                    }
                },
                
                trackConversion: function(goalName, value, data) {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'conversion', {
                            event_category: 'environmental_action',
                            goal_name: goalName,
                            value: value || 0,
                            currency: 'USD',
                            environmental_action_type: data.action_type || '',
                            custom_parameter_1: data.action_type || ''
                        });
                    }
                },
                
                trackPageView: function(pageTitle, pagePath) {
                    if (typeof gtag !== 'undefined') {
                        gtag('config', '<?php echo esc_js($this->ga4_measurement_id); ?>', {
                            page_title: pageTitle,
                            page_location: window.location.href,
                            page_path: pagePath || window.location.pathname
                        });
                    }
                }
            };
        </script>
        <?php
    }
    
    /**
     * Output custom events script in footer
     */
    public function output_custom_events_script() {
        if (empty($this->ga4_measurement_id)) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Track environmental actions
            $(document).on('click', '[data-env-track]', function() {
                var $this = $(this);
                var action = $this.data('env-track');
                var category = $this.data('env-category') || 'environmental_action';
                var value = $this.data('env-value') || 0;
                var actionType = $this.data('env-action-type') || '';
                
                if (window.envAnalytics) {
                    window.envAnalytics.trackEvent(action, category, {
                        action_type: actionType,
                        value: value,
                        element_id: $this.attr('id') || '',
                        element_class: $this.attr('class') || ''
                    });
                }
            });
            
            // Track form submissions
            $('form[data-env-form]').on('submit', function() {
                var $form = $(this);
                var formType = $form.data('env-form');
                
                if (window.envAnalytics) {
                    window.envAnalytics.trackEvent('form_submit', 'engagement', {
                        action_type: formType,
                        form_id: $form.attr('id') || '',
                        content_category: 'form_interaction'
                    });
                }
            });
            
            // Track scroll depth
            var scrollTracked = {};
            $(window).on('scroll', function() {
                var scrollPercent = Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100);
                
                if (scrollPercent >= 25 && !scrollTracked['25']) {
                    scrollTracked['25'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('scroll_depth', 'engagement', {
                            action_type: 'scroll_25',
                            content_category: 'page_engagement'
                        });
                    }
                }
                
                if (scrollPercent >= 50 && !scrollTracked['50']) {
                    scrollTracked['50'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('scroll_depth', 'engagement', {
                            action_type: 'scroll_50',
                            content_category: 'page_engagement'
                        });
                    }
                }
                
                if (scrollPercent >= 75 && !scrollTracked['75']) {
                    scrollTracked['75'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('scroll_depth', 'engagement', {
                            action_type: 'scroll_75',
                            content_category: 'page_engagement'
                        });
                    }
                }
                
                if (scrollPercent >= 90 && !scrollTracked['90']) {
                    scrollTracked['90'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('scroll_depth', 'engagement', {
                            action_type: 'scroll_90',
                            content_category: 'page_engagement'
                        });
                    }
                }
            });
            
            // Track time on page
            var timeOnPage = 0;
            var timeTracked = {};
            
            setInterval(function() {
                timeOnPage += 10; // Increment by 10 seconds
                
                if (timeOnPage >= 30 && !timeTracked['30']) {
                    timeTracked['30'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('time_on_page', 'engagement', {
                            action_type: 'time_30s',
                            content_category: 'page_engagement'
                        });
                    }
                }
                
                if (timeOnPage >= 60 && !timeTracked['60']) {
                    timeTracked['60'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('time_on_page', 'engagement', {
                            action_type: 'time_1m',
                            content_category: 'page_engagement'
                        });
                    }
                }
                
                if (timeOnPage >= 180 && !timeTracked['180']) {
                    timeTracked['180'] = true;
                    if (window.envAnalytics) {
                        window.envAnalytics.trackEvent('time_on_page', 'engagement', {
                            action_type: 'time_3m',
                            content_category: 'page_engagement'
                        });
                    }
                }
            }, 10000); // Check every 10 seconds
        });
        </script>
        <?php
    }
    
    /**
     * Send event to GA4 via Measurement Protocol
     */
    public function send_event_to_ga4($event_action, $event_category, $event_data = array()) {
        if (empty($this->ga4_measurement_id) || empty($this->ga4_api_secret)) {
            return false;
        }
        
        $client_id = $this->get_client_id();
        
        // Prepare event data
        $event_params = array(
            'event_category' => $event_category,
            'environmental_action_type' => $event_data['action_type'] ?? '',
            'user_engagement_level' => $event_data['engagement_level'] ?? '',
            'content_category' => $event_data['content_category'] ?? '',
            'value' => $event_data['value'] ?? 0
        );
        
        // Add custom parameters if available
        if (!empty($event_data['custom_params'])) {
            $event_params = array_merge($event_params, $event_data['custom_params']);
        }
        
        $payload = array(
            'client_id' => $client_id,
            'events' => array(
                array(
                    'name' => $event_action,
                    'params' => $event_params
                )
            )
        );
        
        // Add user properties if user is logged in
        if (is_user_logged_in()) {
            $payload['user_id'] = (string) get_current_user_id();
            $payload['user_properties'] = array(
                'user_type' => array('value' => 'logged_in'),
                'platform_version' => array('value' => ENV_ANALYTICS_VERSION)
            );
        }
        
        // Send to GA4
        $url = $this->debug_mode ? 
            'https://www.google-analytics.com/debug/mp/collect' : 
            'https://www.google-analytics.com/mp/collect';
        
        $url .= '?measurement_id=' . $this->ga4_measurement_id . '&api_secret=' . $this->ga4_api_secret;
        
        $response = wp_remote_post($url, array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log('Environmental Analytics GA4 Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($this->debug_mode) {
            $response_body = wp_remote_retrieve_body($response);
            error_log('Environmental Analytics GA4 Debug Response: ' . $response_body);
        }
        
        return $response_code === 204;
    }
    
    /**
     * Get or generate client ID for GA4
     */
    private function get_client_id() {
        // Try to get client ID from existing GA cookie
        if (isset($_COOKIE['_ga'])) {
            $ga_cookie = $_COOKIE['_ga'];
            $ga_parts = explode('.', $ga_cookie);
            if (count($ga_parts) >= 4) {
                return $ga_parts[2] . '.' . $ga_parts[3];
            }
        }
        
        // Generate new client ID
        return sprintf('%d.%d', 
            wp_rand(100000000, 999999999), 
            time()
        );
    }
    
    /**
     * Track environmental conversion to GA4
     */
    public function track_conversion($goal_name, $conversion_value, $conversion_data = array()) {
        $event_data = array(
            'action_type' => $conversion_data['action_type'] ?? '',
            'value' => $conversion_value,
            'custom_params' => array(
                'goal_name' => $goal_name,
                'currency' => 'USD'
            )
        );
        
        return $this->send_event_to_ga4('conversion', 'environmental_action', $event_data);
    }
    
    /**
     * Register GA4 settings
     */
    public function register_ga4_settings() {
        register_setting('env_analytics_settings', 'env_analytics_ga4_measurement_id');
        register_setting('env_analytics_settings', 'env_analytics_ga4_api_secret');
        register_setting('env_analytics_settings', 'env_analytics_ga4_debug');
        register_setting('env_analytics_settings', 'env_analytics_exclude_admins');
    }
    
    /**
     * Test GA4 connection
     */
    public function test_ga4_connection() {
        if (empty($this->ga4_measurement_id) || empty($this->ga4_api_secret)) {
            return array(
                'success' => false,
                'message' => 'GA4 Measurement ID and API Secret are required'
            );
        }
        
        // Send a test event
        $test_result = $this->send_event_to_ga4('test_connection', 'system', array(
            'action_type' => 'connection_test',
            'content_category' => 'system_test'
        ));
        
        return array(
            'success' => $test_result,
            'message' => $test_result ? 
                'Successfully connected to GA4' : 
                'Failed to connect to GA4. Check your credentials.'
        );
    }
    
    /**
     * Get GA4 configuration status
     */
    public function get_configuration_status() {
        return array(
            'has_measurement_id' => !empty($this->ga4_measurement_id),
            'has_api_secret' => !empty($this->ga4_api_secret),
            'debug_mode' => $this->debug_mode,
            'exclude_admins' => get_option('env_analytics_exclude_admins', true),
            'is_configured' => !empty($this->ga4_measurement_id) && !empty($this->ga4_api_secret)
        );
    }
    
    /**
     * Sync local data with GA4 (for reporting)
     */
    public function sync_ga4_data($date_from = null, $date_to = null) {
        // This would require GA4 Reporting API implementation
        // For now, we'll focus on sending data TO GA4
        // Future enhancement could pull data FROM GA4 for comparison
        
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-7 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        global $wpdb;
        
        // Get local events to sync
        $local_events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}env_analytics_events 
             WHERE DATE(event_date) BETWEEN %s AND %s
             AND synced_to_ga4 = 0
             ORDER BY event_date ASC
             LIMIT 1000", // Batch process to avoid timeouts
            $date_from, $date_to
        ));
        
        $synced_count = 0;
        $failed_count = 0;
        
        foreach ($local_events as $event) {
            $event_data = json_decode($event->event_data, true) ?: array();
            
            $success = $this->send_event_to_ga4(
                $event->event_action,
                $event->event_category,
                $event_data
            );
            
            if ($success) {
                // Mark as synced
                $wpdb->update(
                    $wpdb->prefix . 'env_analytics_events',
                    array('synced_to_ga4' => 1),
                    array('id' => $event->id),
                    array('%d'),
                    array('%d')
                );
                $synced_count++;
            } else {
                $failed_count++;
            }
        }
        
        return array(
            'synced' => $synced_count,
            'failed' => $failed_count,
            'total_processed' => count($local_events)
        );
    }
    
    /**
     * AJAX: Test GA4 connection
     */
    public function ajax_test_ga4_connection() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = $this->test_ga4_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Sync GA4 data
     */
    public function ajax_sync_ga4_data() {
        check_ajax_referer('env_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $result = $this->sync_ga4_data($date_from, $date_to);
        
        wp_send_json_success($result);
    }
    
    /**
     * Get enhanced ecommerce data for GA4
     */
    public function get_ecommerce_data($action, $item_data) {
        $ecommerce_data = array();
        
        switch ($action) {
            case 'donation_completed':
                $ecommerce_data = array(
                    'transaction_id' => $item_data['donation_id'] ?? uniqid(),
                    'value' => $item_data['amount'] ?? 0,
                    'currency' => 'USD',
                    'items' => array(
                        array(
                            'item_id' => 'donation_' . ($item_data['cause_id'] ?? 'general'),
                            'item_name' => 'Environmental Donation',
                            'item_category' => 'donation',
                            'item_category2' => $item_data['cause_category'] ?? 'environmental',
                            'price' => $item_data['amount'] ?? 0,
                            'quantity' => 1
                        )
                    )
                );
                break;
                
            case 'item_exchanged':
                $ecommerce_data = array(
                    'transaction_id' => $item_data['exchange_id'] ?? uniqid(),
                    'value' => $item_data['item_value'] ?? 0,
                    'currency' => 'USD',
                    'items' => array(
                        array(
                            'item_id' => 'exchange_' . ($item_data['item_id'] ?? 'unknown'),
                            'item_name' => $item_data['item_name'] ?? 'Exchange Item',
                            'item_category' => 'exchange',
                            'item_category2' => $item_data['item_category'] ?? 'environmental',
                            'price' => $item_data['item_value'] ?? 0,
                            'quantity' => 1
                        )
                    )
                );
                break;
        }
        
        return $ecommerce_data;
    }
}
