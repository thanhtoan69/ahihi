<?php
/**
 * Environmental Email Marketing Subscription Widget
 * 
 * WordPress widget for displaying subscription forms
 * with environmental themes and customization options.
 *
 * @package     EnvironmentalEmailMarketing
 * @subpackage  Widgets
 * @version     1.0.0
 * @author      Environmental Email Marketing Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Subscription_Widget extends WP_Widget {
    
    /**
     * Widget constructor
     */
    public function __construct() {
        parent::__construct(
            'eem_subscription_widget',
            __('Environmental Email Subscription', 'environmental-email-marketing'),
            array(
                'description' => __('Display an environmental email subscription form', 'environmental-email-marketing'),
                'classname' => 'eem-subscription-widget'
            )
        );
    }

    /**
     * Widget output
     */
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title'] ?? '');
        $description = $instance['description'] ?? '';
        $style = $instance['style'] ?? 'default';
        $lists = !empty($instance['lists']) ? explode(',', $instance['lists']) : array();
        $show_name = !empty($instance['show_name']);
        $show_interests = !empty($instance['show_interests']);
        $button_text = $instance['button_text'] ?? __('Subscribe', 'environmental-email-marketing');
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        ?>
        <div class="eem-widget-subscription eem-style-<?php echo esc_attr($style); ?>">
            <?php if (!empty($description)): ?>
            <p class="eem-widget-description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            
            <form class="eem-subscription-form eem-widget-form" data-lists="<?php echo esc_attr(implode(',', $lists)); ?>">
                <?php wp_nonce_field('eem_frontend_nonce', 'eem_nonce'); ?>
                
                <div class="eem-form-fields">
                    <?php if ($show_name): ?>
                    <div class="eem-field-group">
                        <input type="text" name="first_name" placeholder="<?php esc_attr_e('First Name', 'environmental-email-marketing'); ?>" class="eem-input eem-input-small">
                    </div>
                    <div class="eem-field-group">
                        <input type="text" name="last_name" placeholder="<?php esc_attr_e('Last Name', 'environmental-email-marketing'); ?>" class="eem-input eem-input-small">
                    </div>
                    <?php endif; ?>

                    <div class="eem-field-group">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Your Email', 'environmental-email-marketing'); ?>" class="eem-input eem-input-small" required>
                    </div>

                    <?php if ($show_interests): ?>
                    <div class="eem-field-group">
                        <select name="interests[]" class="eem-select eem-select-small" multiple>
                            <option value="climate_change"><?php esc_html_e('Climate Change', 'environmental-email-marketing'); ?></option>
                            <option value="renewable_energy"><?php esc_html_e('Renewable Energy', 'environmental-email-marketing'); ?></option>
                            <option value="conservation"><?php esc_html_e('Conservation', 'environmental-email-marketing'); ?></option>
                            <option value="sustainable_living"><?php esc_html_e('Sustainable Living', 'environmental-email-marketing'); ?></option>
                        </select>
                        <small class="eem-help-text"><?php esc_html_e('Hold Ctrl/Cmd to select multiple', 'environmental-email-marketing'); ?></small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="eem-form-actions">
                    <button type="submit" class="eem-submit-button eem-button-small">
                        <span class="eem-button-text"><?php echo esc_html($button_text); ?></span>
                        <span class="eem-loading-spinner" style="display: none;">⟳</span>
                    </button>
                </div>

                <div class="eem-form-messages">
                    <div class="eem-success-message" style="display: none;"></div>
                    <div class="eem-error-message" style="display: none;"></div>
                </div>

                <div class="eem-widget-footer">
                    <small class="eem-privacy-notice">
                        <?php esc_html_e('We respect your privacy. Unsubscribe anytime.', 'environmental-email-marketing'); ?>
                    </small>
                </div>
            </form>
        </div>
        <?php
        
        echo $args['after_widget'];
    }

    /**
     * Widget form in admin
     */
    public function form($instance) {
        $title = $instance['title'] ?? __('Join Our Environmental Newsletter', 'environmental-email-marketing');
        $description = $instance['description'] ?? __('Stay updated on environmental news and action opportunities', 'environmental-email-marketing');
        $style = $instance['style'] ?? 'default';
        $lists = $instance['lists'] ?? '';
        $show_name = !empty($instance['show_name']);
        $show_interests = !empty($instance['show_interests']);
        $button_text = $instance['button_text'] ?? __('Subscribe', 'environmental-email-marketing');
        
        // Get available lists
        global $wpdb;
        $available_lists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}eem_lists ORDER BY name ASC", ARRAY_A);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Title:', 'environmental-email-marketing'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('description'); ?>"><?php esc_html_e('Description:', 'environmental-email-marketing'); ?></label>
            <textarea class="widefat" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>" rows="3"><?php echo esc_textarea($description); ?></textarea>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php esc_html_e('Style:', 'environmental-email-marketing'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <option value="default" <?php selected($style, 'default'); ?>><?php esc_html_e('Default', 'environmental-email-marketing'); ?></option>
                <option value="compact" <?php selected($style, 'compact'); ?>><?php esc_html_e('Compact', 'environmental-email-marketing'); ?></option>
                <option value="minimal" <?php selected($style, 'minimal'); ?>><?php esc_html_e('Minimal', 'environmental-email-marketing'); ?></option>
                <option value="eco-green" <?php selected($style, 'eco-green'); ?>><?php esc_html_e('Eco Green', 'environmental-email-marketing'); ?></option>
                <option value="earth-blue" <?php selected($style, 'earth-blue'); ?>><?php esc_html_e('Earth Blue', 'environmental-email-marketing'); ?></option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('lists'); ?>"><?php esc_html_e('Email Lists (comma-separated IDs):', 'environmental-email-marketing'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('lists'); ?>" name="<?php echo $this->get_field_name('lists'); ?>" type="text" value="<?php echo esc_attr($lists); ?>">
            <small><?php esc_html_e('Available lists:', 'environmental-email-marketing'); ?>
                <?php 
                $list_names = array();
                foreach ($available_lists as $list) {
                    $list_names[] = $list['name'] . ' (ID: ' . $list['id'] . ')';
                }
                echo implode(', ', $list_names);
                ?>
            </small>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('button_text'); ?>"><?php esc_html_e('Button Text:', 'environmental-email-marketing'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('button_text'); ?>" name="<?php echo $this->get_field_name('button_text'); ?>" type="text" value="<?php echo esc_attr($button_text); ?>">
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_name); ?> id="<?php echo $this->get_field_id('show_name'); ?>" name="<?php echo $this->get_field_name('show_name'); ?>" value="1">
            <label for="<?php echo $this->get_field_id('show_name'); ?>"><?php esc_html_e('Show name fields', 'environmental-email-marketing'); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_interests); ?> id="<?php echo $this->get_field_id('show_interests'); ?>" name="<?php echo $this->get_field_name('show_interests'); ?>" value="1">
            <label for="<?php echo $this->get_field_id('show_interests'); ?>"><?php esc_html_e('Show interests selection', 'environmental-email-marketing'); ?></label>
        </p>
        <?php
    }

    /**
     * Update widget instance
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['description'] = !empty($new_instance['description']) ? sanitize_textarea_field($new_instance['description']) : '';
        $instance['style'] = !empty($new_instance['style']) ? sanitize_text_field($new_instance['style']) : 'default';
        $instance['lists'] = !empty($new_instance['lists']) ? sanitize_text_field($new_instance['lists']) : '';
        $instance['button_text'] = !empty($new_instance['button_text']) ? sanitize_text_field($new_instance['button_text']) : __('Subscribe', 'environmental-email-marketing');
        $instance['show_name'] = !empty($new_instance['show_name']);
        $instance['show_interests'] = !empty($new_instance['show_interests']);

        return $instance;
    }
}

/**
 * Newsletter popup widget for exit-intent and timed display
 */
class EEM_Newsletter_Popup {
    
    /**
     * Initialize popup functionality
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'render_popup'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_popup_scripts'));
        add_action('wp_ajax_eem_dismiss_popup', array($this, 'handle_popup_dismissal'));
        add_action('wp_ajax_nopriv_eem_dismiss_popup', array($this, 'handle_popup_dismissal'));
    }

    /**
     * Enqueue popup scripts
     */
    public function enqueue_popup_scripts() {
        if ($this->should_show_popup()) {
            wp_enqueue_script(
                'eem-popup',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/popup.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('eem-popup', 'eem_popup', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eem_popup_nonce'),
                'settings' => $this->get_popup_settings()
            ));
        }
    }

    /**
     * Render popup HTML
     */
    public function render_popup() {
        if (!$this->should_show_popup()) {
            return;
        }

        $settings = $this->get_popup_settings();
        $template_engine = new EEM_Template_Engine();
        
        ?>
        <div id="eem-newsletter-popup" class="eem-popup-overlay" style="display: none;">
            <div class="eem-popup-container eem-style-<?php echo esc_attr($settings['style']); ?>">
                <div class="eem-popup-close">×</div>
                
                <div class="eem-popup-content">
                    <div class="eem-popup-header">
                        <?php if (!empty($settings['image'])): ?>
                        <div class="eem-popup-image">
                            <img src="<?php echo esc_url($settings['image']); ?>" alt="">
                        </div>
                        <?php endif; ?>
                        
                        <h3 class="eem-popup-title"><?php echo esc_html($settings['title']); ?></h3>
                        <p class="eem-popup-description"><?php echo esc_html($settings['description']); ?></p>
                    </div>
                    
                    <form class="eem-subscription-form eem-popup-form" data-lists="<?php echo esc_attr($settings['lists']); ?>">
                        <?php wp_nonce_field('eem_frontend_nonce', 'eem_nonce'); ?>
                        
                        <div class="eem-form-fields">
                            <div class="eem-field-group">
                                <input type="email" name="email" placeholder="<?php esc_attr_e('Enter your email address', 'environmental-email-marketing'); ?>" class="eem-input" required>
                            </div>
                            
                            <?php if ($settings['show_name']): ?>
                            <div class="eem-field-group eem-field-row">
                                <input type="text" name="first_name" placeholder="<?php esc_attr_e('First Name', 'environmental-email-marketing'); ?>" class="eem-input eem-input-half">
                                <input type="text" name="last_name" placeholder="<?php esc_attr_e('Last Name', 'environmental-email-marketing'); ?>" class="eem-input eem-input-half">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eem-form-actions">
                            <button type="submit" class="eem-submit-button eem-button-popup">
                                <span class="eem-button-text"><?php echo esc_html($settings['button_text']); ?></span>
                                <span class="eem-loading-spinner" style="display: none;">⟳</span>
                            </button>
                        </div>
                        
                        <div class="eem-form-messages">
                            <div class="eem-success-message" style="display: none;"></div>
                            <div class="eem-error-message" style="display: none;"></div>
                        </div>
                    </form>
                    
                    <div class="eem-popup-footer">
                        <label class="eem-checkbox-item">
                            <input type="checkbox" id="eem-no-more-popups">
                            <span><?php esc_html_e("Don't show this again", 'environmental-email-marketing'); ?></span>
                        </label>
                        
                        <small class="eem-privacy-notice">
                            <?php esc_html_e('We respect your privacy and never spam.', 'environmental-email-marketing'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Check if popup should be shown
     */
    private function should_show_popup() {
        // Don't show on admin pages
        if (is_admin()) {
            return false;
        }
        
        // Check if popup is enabled
        if (!get_option('eem_popup_enabled', 0)) {
            return false;
        }
        
        // Check if user has dismissed popup
        if (isset($_COOKIE['eem_popup_dismissed'])) {
            return false;
        }
        
        // Check page restrictions
        $show_on_pages = get_option('eem_popup_pages', array('all'));
        
        if (!in_array('all', $show_on_pages)) {
            $current_page_type = '';
            
            if (is_home() || is_front_page()) {
                $current_page_type = 'home';
            } elseif (is_single()) {
                $current_page_type = 'posts';
            } elseif (is_page()) {
                $current_page_type = 'pages';
            }
            
            if (!in_array($current_page_type, $show_on_pages)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get popup settings
     */
    private function get_popup_settings() {
        return array(
            'title' => get_option('eem_popup_title', __('Join Our Environmental Movement!', 'environmental-email-marketing')),
            'description' => get_option('eem_popup_description', __('Get the latest environmental news and action opportunities delivered to your inbox.', 'environmental-email-marketing')),
            'button_text' => get_option('eem_popup_button_text', __('Subscribe Now', 'environmental-email-marketing')),
            'image' => get_option('eem_popup_image', ''),
            'style' => get_option('eem_popup_style', 'eco-green'),
            'lists' => get_option('eem_popup_lists', '1'),
            'show_name' => get_option('eem_popup_show_name', 0),
            'trigger' => get_option('eem_popup_trigger', 'exit_intent'),
            'delay' => get_option('eem_popup_delay', 30),
            'scroll_percentage' => get_option('eem_popup_scroll_percentage', 50)
        );
    }

    /**
     * Handle popup dismissal
     */
    public function handle_popup_dismissal() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_popup_nonce')) {
            wp_die('Security check failed');
        }
        
        $permanent = !empty($_POST['permanent']);
        
        if ($permanent) {
            // Set cookie for 30 days
            setcookie('eem_popup_dismissed', '1', time() + (30 * DAY_IN_SECONDS), '/');
        } else {
            // Set session cookie
            setcookie('eem_popup_dismissed', '1', 0, '/');
        }
        
        wp_send_json_success();
    }
}

/**
 * Register widgets
 */
function eem_register_widgets() {
    register_widget('EEM_Subscription_Widget');
}
add_action('widgets_init', 'eem_register_widgets');

// Initialize newsletter popup
new EEM_Newsletter_Popup();
