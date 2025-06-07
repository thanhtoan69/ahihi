<?php
/**
 * Environmental Email Marketing Frontend Handler
 * 
 * Handles all frontend functionality including subscription forms,
 * unsubscribe pages, preference centers, and public interactions.
 *
 * @package     EnvironmentalEmailMarketing
 * @subpackage  Frontend
 * @version     1.0.0
 * @author      Environmental Email Marketing Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Frontend {
    
    /**
     * Initialize frontend functionality
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_eem_subscribe', array($this, 'handle_ajax_subscribe'));
        add_action('wp_ajax_nopriv_eem_subscribe', array($this, 'handle_ajax_subscribe'));
        add_action('wp_ajax_eem_unsubscribe', array($this, 'handle_ajax_unsubscribe'));
        add_action('wp_ajax_nopriv_eem_unsubscribe', array($this, 'handle_ajax_unsubscribe'));
        add_action('wp_ajax_eem_update_preferences', array($this, 'handle_ajax_update_preferences'));
        add_action('wp_ajax_nopriv_eem_update_preferences', array($this, 'handle_ajax_update_preferences'));
        add_action('init', array($this, 'handle_email_actions'));
        add_action('template_redirect', array($this, 'handle_special_pages'));
        add_shortcode('eem_subscription_form', array($this, 'subscription_form_shortcode'));
        add_shortcode('eem_unsubscribe_form', array($this, 'unsubscribe_form_shortcode'));
        add_shortcode('eem_preference_center', array($this, 'preference_center_shortcode'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'eem-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/frontend.js',
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_style(
            'eem-frontend',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/frontend.css',
            array(),
            '1.0.0'
        );

        // Localize script for AJAX
        wp_localize_script('eem-frontend', 'eem_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eem_frontend_nonce'),
            'messages' => array(
                'subscription_success' => __('Thank you for subscribing! Please check your email to confirm.', 'environmental-email-marketing'),
                'subscription_error' => __('There was an error processing your subscription. Please try again.', 'environmental-email-marketing'),
                'unsubscribe_success' => __('You have been successfully unsubscribed.', 'environmental-email-marketing'),
                'unsubscribe_error' => __('There was an error processing your unsubscription.', 'environmental-email-marketing'),
                'preferences_updated' => __('Your preferences have been updated successfully.', 'environmental-email-marketing'),
                'invalid_email' => __('Please enter a valid email address.', 'environmental-email-marketing'),
                'processing' => __('Processing...', 'environmental-email-marketing')
            )
        ));
    }

    /**
     * Handle AJAX subscription requests
     */
    public function handle_ajax_subscribe() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eem_frontend_nonce')) {
            wp_die('Security check failed');
        }

        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $lists = array_map('intval', $_POST['lists'] ?? array());
        $interests = array_map('sanitize_text_field', $_POST['interests'] ?? array());
        $source = sanitize_text_field($_POST['source'] ?? 'website');

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }

        $subscriber_manager = new EEM_Subscriber_Manager();
        
        // Check if subscriber already exists
        $existing_subscriber = $subscriber_manager->get_subscriber_by_email($email);
        
        if ($existing_subscriber) {
            if ($existing_subscriber['status'] === 'subscribed') {
                wp_send_json_error(array('message' => 'You are already subscribed to our newsletter.'));
            } else {
                // Reactivate subscription
                $result = $subscriber_manager->update_subscriber($existing_subscriber['id'], array(
                    'status' => 'pending',
                    'lists' => $lists,
                    'interests' => $interests
                ));
            }
        } else {
            // Create new subscriber
            $subscriber_data = array(
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => 'pending',
                'source' => $source,
                'lists' => $lists,
                'interests' => $interests,
                'environmental_score' => 0,
                'preferences' => array(
                    'frequency' => 'weekly',
                    'content_types' => $interests
                )
            );

            $result = $subscriber_manager->add_subscriber($subscriber_data);
        }

        if ($result) {
            // Send confirmation email
            $this->send_confirmation_email($email, $first_name);
            
            // Track subscription event
            $analytics = new EEM_Analytics_Tracker();
            $analytics->track_event('subscription', array(
                'email' => $email,
                'source' => $source,
                'lists' => $lists
            ));

            wp_send_json_success(array('message' => 'Subscription successful. Please check your email to confirm.'));
        } else {
            wp_send_json_error(array('message' => 'There was an error processing your subscription.'));
        }
    }

    /**
     * Handle AJAX unsubscribe requests
     */
    public function handle_ajax_unsubscribe() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eem_frontend_nonce')) {
            wp_die('Security check failed');
        }

        $email = sanitize_email($_POST['email']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }

        $subscriber_manager = new EEM_Subscriber_Manager();
        $result = $subscriber_manager->unsubscribe($email, $reason);

        if ($result) {
            // Track unsubscribe event
            $analytics = new EEM_Analytics_Tracker();
            $analytics->track_event('unsubscribe', array(
                'email' => $email,
                'reason' => $reason
            ));

            wp_send_json_success(array('message' => 'You have been successfully unsubscribed.'));
        } else {
            wp_send_json_error(array('message' => 'There was an error processing your unsubscription.'));
        }
    }

    /**
     * Handle AJAX preference updates
     */
    public function handle_ajax_update_preferences() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eem_frontend_nonce')) {
            wp_die('Security check failed');
        }

        $email = sanitize_email($_POST['email']);
        $token = sanitize_text_field($_POST['token']);
        $preferences = array();

        // Sanitize preferences
        if (isset($_POST['frequency'])) {
            $preferences['frequency'] = sanitize_text_field($_POST['frequency']);
        }
        if (isset($_POST['content_types'])) {
            $preferences['content_types'] = array_map('sanitize_text_field', $_POST['content_types']);
        }
        if (isset($_POST['interests'])) {
            $preferences['interests'] = array_map('sanitize_text_field', $_POST['interests']);
        }

        $subscriber_manager = new EEM_Subscriber_Manager();
        
        // Verify token
        if (!$subscriber_manager->verify_preferences_token($email, $token)) {
            wp_send_json_error(array('message' => 'Invalid or expired link.'));
        }

        $result = $subscriber_manager->update_preferences($email, $preferences);

        if ($result) {
            wp_send_json_success(array('message' => 'Your preferences have been updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'There was an error updating your preferences.'));
        }
    }

    /**
     * Handle email actions (confirm, unsubscribe, preferences)
     */
    public function handle_email_actions() {
        if (!isset($_GET['eem_action'])) {
            return;
        }

        $action = sanitize_text_field($_GET['eem_action']);
        $email = sanitize_email($_GET['email'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');

        $subscriber_manager = new EEM_Subscriber_Manager();

        switch ($action) {
            case 'confirm':
                if ($subscriber_manager->confirm_subscription($email, $token)) {
                    wp_redirect(add_query_arg('eem_message', 'confirmed', home_url()));
                } else {
                    wp_redirect(add_query_arg('eem_message', 'error', home_url()));
                }
                exit;
                break;

            case 'unsubscribe':
                if ($subscriber_manager->unsubscribe($email, '', $token)) {
                    wp_redirect(add_query_arg('eem_message', 'unsubscribed', home_url()));
                } else {
                    wp_redirect(add_query_arg('eem_message', 'error', home_url()));
                }
                exit;
                break;

            case 'preferences':
                // Redirect to preference center with token
                $preference_page = get_option('eem_preference_page_id');
                if ($preference_page) {
                    $url = get_permalink($preference_page);
                    $url = add_query_arg(array('email' => $email, 'token' => $token), $url);
                    wp_redirect($url);
                    exit;
                }
                break;
        }
    }

    /**
     * Handle special pages (unsubscribe, preferences)
     */
    public function handle_special_pages() {
        global $post;

        if (!$post) {
            return;
        }

        $unsubscribe_page = get_option('eem_unsubscribe_page_id');
        $preference_page = get_option('eem_preference_page_id');

        if ($post->ID == $unsubscribe_page || $post->ID == $preference_page) {
            // Add custom body class
            add_filter('body_class', function($classes) {
                $classes[] = 'eem-special-page';
                return $classes;
            });
        }
    }

    /**
     * Subscription form shortcode
     */
    public function subscription_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'lists' => '',
            'show_name' => 'true',
            'show_interests' => 'true',
            'title' => __('Subscribe to Our Newsletter', 'environmental-email-marketing'),
            'description' => __('Stay updated with environmental news and tips', 'environmental-email-marketing'),
            'button_text' => __('Subscribe', 'environmental-email-marketing')
        ), $atts);

        $lists = !empty($atts['lists']) ? explode(',', $atts['lists']) : array();
        $show_name = $atts['show_name'] === 'true';
        $show_interests = $atts['show_interests'] === 'true';

        ob_start();
        ?>
        <div class="eem-subscription-form-wrapper eem-style-<?php echo esc_attr($atts['style']); ?>">
            <form class="eem-subscription-form" data-lists="<?php echo esc_attr(implode(',', $lists)); ?>">
                <?php wp_nonce_field('eem_frontend_nonce', 'eem_nonce'); ?>
                
                <div class="eem-form-header">
                    <h3 class="eem-form-title"><?php echo esc_html($atts['title']); ?></h3>
                    <p class="eem-form-description"><?php echo esc_html($atts['description']); ?></p>
                </div>

                <div class="eem-form-fields">
                    <?php if ($show_name): ?>
                    <div class="eem-field-group">
                        <div class="eem-field-half">
                            <input type="text" name="first_name" placeholder="<?php esc_attr_e('First Name', 'environmental-email-marketing'); ?>" class="eem-input">
                        </div>
                        <div class="eem-field-half">
                            <input type="text" name="last_name" placeholder="<?php esc_attr_e('Last Name', 'environmental-email-marketing'); ?>" class="eem-input">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="eem-field-group">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Your Email Address', 'environmental-email-marketing'); ?>" class="eem-input" required>
                    </div>

                    <?php if ($show_interests): ?>
                    <div class="eem-field-group">
                        <label class="eem-field-label"><?php esc_html_e('Interests:', 'environmental-email-marketing'); ?></label>
                        <div class="eem-checkbox-group">
                            <label class="eem-checkbox-item">
                                <input type="checkbox" name="interests[]" value="climate_change">
                                <span><?php esc_html_e('Climate Change', 'environmental-email-marketing'); ?></span>
                            </label>
                            <label class="eem-checkbox-item">
                                <input type="checkbox" name="interests[]" value="renewable_energy">
                                <span><?php esc_html_e('Renewable Energy', 'environmental-email-marketing'); ?></span>
                            </label>
                            <label class="eem-checkbox-item">
                                <input type="checkbox" name="interests[]" value="conservation">
                                <span><?php esc_html_e('Conservation', 'environmental-email-marketing'); ?></span>
                            </label>
                            <label class="eem-checkbox-item">
                                <input type="checkbox" name="interests[]" value="sustainable_living">
                                <span><?php esc_html_e('Sustainable Living', 'environmental-email-marketing'); ?></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="eem-form-actions">
                    <button type="submit" class="eem-submit-button">
                        <span class="eem-button-text"><?php echo esc_html($atts['button_text']); ?></span>
                        <span class="eem-loading-spinner" style="display: none;">⟳</span>
                    </button>
                </div>

                <div class="eem-form-messages">
                    <div class="eem-success-message" style="display: none;"></div>
                    <div class="eem-error-message" style="display: none;"></div>
                </div>

                <div class="eem-form-footer">
                    <small class="eem-privacy-notice">
                        <?php esc_html_e('We respect your privacy. Unsubscribe at any time.', 'environmental-email-marketing'); ?>
                    </small>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Unsubscribe form shortcode
     */
    public function unsubscribe_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Unsubscribe', 'environmental-email-marketing'),
            'description' => __('We\'re sorry to see you go. Please enter your email to unsubscribe.', 'environmental-email-marketing')
        ), $atts);

        ob_start();
        ?>
        <div class="eem-unsubscribe-form-wrapper">
            <form class="eem-unsubscribe-form">
                <?php wp_nonce_field('eem_frontend_nonce', 'eem_nonce'); ?>
                
                <div class="eem-form-header">
                    <h3 class="eem-form-title"><?php echo esc_html($atts['title']); ?></h3>
                    <p class="eem-form-description"><?php echo esc_html($atts['description']); ?></p>
                </div>

                <div class="eem-form-fields">
                    <div class="eem-field-group">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Your Email Address', 'environmental-email-marketing'); ?>" class="eem-input" required>
                    </div>

                    <div class="eem-field-group">
                        <label class="eem-field-label"><?php esc_html_e('Reason for unsubscribing (optional):', 'environmental-email-marketing'); ?></label>
                        <select name="reason" class="eem-select">
                            <option value=""><?php esc_html_e('Select a reason...', 'environmental-email-marketing'); ?></option>
                            <option value="too_frequent"><?php esc_html_e('Too many emails', 'environmental-email-marketing'); ?></option>
                            <option value="not_relevant"><?php esc_html_e('Content not relevant', 'environmental-email-marketing'); ?></option>
                            <option value="not_interested"><?php esc_html_e('No longer interested', 'environmental-email-marketing'); ?></option>
                            <option value="other"><?php esc_html_e('Other', 'environmental-email-marketing'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="eem-form-actions">
                    <button type="submit" class="eem-submit-button eem-button-danger">
                        <span class="eem-button-text"><?php esc_html_e('Unsubscribe', 'environmental-email-marketing'); ?></span>
                        <span class="eem-loading-spinner" style="display: none;">⟳</span>
                    </button>
                </div>

                <div class="eem-form-messages">
                    <div class="eem-success-message" style="display: none;"></div>
                    <div class="eem-error-message" style="display: none;"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Preference center shortcode
     */
    public function preference_center_shortcode($atts) {
        $email = sanitize_email($_GET['email'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');

        if (empty($email) || empty($token)) {
            return '<p>' . __('Invalid or missing parameters.', 'environmental-email-marketing') . '</p>';
        }

        $subscriber_manager = new EEM_Subscriber_Manager();
        
        // Verify token
        if (!$subscriber_manager->verify_preferences_token($email, $token)) {
            return '<p>' . __('Invalid or expired link.', 'environmental-email-marketing') . '</p>';
        }

        // Get current preferences
        $subscriber = $subscriber_manager->get_subscriber_by_email($email);
        if (!$subscriber) {
            return '<p>' . __('Subscriber not found.', 'environmental-email-marketing') . '</p>';
        }

        $preferences = maybe_unserialize($subscriber['preferences']) ?: array();

        ob_start();
        ?>
        <div class="eem-preference-center-wrapper">
            <form class="eem-preference-form" data-email="<?php echo esc_attr($email); ?>" data-token="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field('eem_frontend_nonce', 'eem_nonce'); ?>
                
                <div class="eem-form-header">
                    <h3 class="eem-form-title"><?php esc_html_e('Email Preferences', 'environmental-email-marketing'); ?></h3>
                    <p class="eem-form-description"><?php esc_html_e('Customize how often you hear from us and what content you receive.', 'environmental-email-marketing'); ?></p>
                </div>

                <div class="eem-form-sections">
                    <div class="eem-form-section">
                        <h4><?php esc_html_e('Email Frequency', 'environmental-email-marketing'); ?></h4>
                        <div class="eem-radio-group">
                            <label class="eem-radio-item">
                                <input type="radio" name="frequency" value="daily" <?php checked($preferences['frequency'] ?? 'weekly', 'daily'); ?>>
                                <span><?php esc_html_e('Daily', 'environmental-email-marketing'); ?></span>
                            </label>
                            <label class="eem-radio-item">
                                <input type="radio" name="frequency" value="weekly" <?php checked($preferences['frequency'] ?? 'weekly', 'weekly'); ?>>
                                <span><?php esc_html_e('Weekly', 'environmental-email-marketing'); ?></span>
                            </label>
                            <label class="eem-radio-item">
                                <input type="radio" name="frequency" value="monthly" <?php checked($preferences['frequency'] ?? 'weekly', 'monthly'); ?>>
                                <span><?php esc_html_e('Monthly', 'environmental-email-marketing'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="eem-form-section">
                        <h4><?php esc_html_e('Content Types', 'environmental-email-marketing'); ?></h4>
                        <div class="eem-checkbox-group">
                            <?php
                            $content_types = array(
                                'news' => __('Environmental News', 'environmental-email-marketing'),
                                'tips' => __('Eco-friendly Tips', 'environmental-email-marketing'),
                                'events' => __('Environmental Events', 'environmental-email-marketing'),
                                'petitions' => __('Petitions & Action Alerts', 'environmental-email-marketing'),
                                'products' => __('Sustainable Products', 'environmental-email-marketing')
                            );
                            
                            $selected_types = $preferences['content_types'] ?? array();
                            
                            foreach ($content_types as $key => $label):
                            ?>
                            <label class="eem-checkbox-item">
                                <input type="checkbox" name="content_types[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_types)); ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="eem-form-actions">
                    <button type="submit" class="eem-submit-button">
                        <span class="eem-button-text"><?php esc_html_e('Save Preferences', 'environmental-email-marketing'); ?></span>
                        <span class="eem-loading-spinner" style="display: none;">⟳</span>
                    </button>
                </div>

                <div class="eem-form-messages">
                    <div class="eem-success-message" style="display: none;"></div>
                    <div class="eem-error-message" style="display: none;"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Send confirmation email
     */
    private function send_confirmation_email($email, $first_name = '') {
        $template_engine = new EEM_Template_Engine();
        
        $confirmation_url = add_query_arg(array(
            'eem_action' => 'confirm',
            'email' => urlencode($email),
            'token' => $this->generate_confirmation_token($email)
        ), home_url());

        $variables = array(
            'first_name' => $first_name,
            'email' => $email,
            'confirmation_url' => $confirmation_url,
            'site_name' => get_bloginfo('name')
        );

        $subject = sprintf(__('Please confirm your subscription to %s', 'environmental-email-marketing'), get_bloginfo('name'));
        $message = $template_engine->render_template('confirmation', $variables);

        wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Generate confirmation token
     */
    private function generate_confirmation_token($email) {
        return wp_hash($email . time());
    }
}

// Initialize frontend
new EEM_Frontend();
