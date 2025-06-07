<?php
/**
 * Environmental Email Marketing Petitions Integration
 * 
 * Integrates email marketing with environmental petitions
 * for signature tracking, follow-up campaigns, and activist engagement.
 *
 * @package     EnvironmentalEmailMarketing
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      Environmental Email Marketing Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Petitions_Integration {
    
    /**
     * Initialize petitions integration
     */
    public function __construct() {
        add_action('init', array($this, 'register_petition_post_type'));
        add_action('add_meta_boxes', array($this, 'add_petition_meta_boxes'));
        add_action('save_post', array($this, 'save_petition_meta'));
        add_action('wp_ajax_eem_sign_petition', array($this, 'handle_petition_signature'));
        add_action('wp_ajax_nopriv_eem_sign_petition', array($this, 'handle_petition_signature'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_petition_scripts'));
        add_shortcode('eem_petition_form', array($this, 'petition_form_shortcode'));
        add_shortcode('eem_petition_count', array($this, 'petition_count_shortcode'));
        add_action('eem_petition_signed', array($this, 'trigger_petition_automation'), 10, 2);
        add_filter('the_content', array($this, 'add_petition_form_to_content'));
        
        // Database setup
        add_action('init', array($this, 'create_petition_tables'));
    }

    /**
     * Register petition post type
     */
    public function register_petition_post_type() {
        $args = array(
            'labels' => array(
                'name' => __('Petitions', 'environmental-email-marketing'),
                'singular_name' => __('Petition', 'environmental-email-marketing'),
                'add_new' => __('Add New Petition', 'environmental-email-marketing'),
                'add_new_item' => __('Add New Petition', 'environmental-email-marketing'),
                'edit_item' => __('Edit Petition', 'environmental-email-marketing'),
                'new_item' => __('New Petition', 'environmental-email-marketing'),
                'view_item' => __('View Petition', 'environmental-email-marketing'),
                'search_items' => __('Search Petitions', 'environmental-email-marketing'),
                'not_found' => __('No petitions found', 'environmental-email-marketing'),
                'not_found_in_trash' => __('No petitions found in trash', 'environmental-email-marketing')
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 25,
            'has_archive' => true,
            'rewrite' => array('slug' => 'petitions')
        );
        
        register_post_type('eem_petition', $args);
    }

    /**
     * Create petition database tables
     */
    public function create_petition_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eem_petition_signatures';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            petition_id bigint(20) NOT NULL,
            email varchar(255) NOT NULL,
            first_name varchar(100) DEFAULT '',
            last_name varchar(100) DEFAULT '',
            country varchar(100) DEFAULT '',
            city varchar(100) DEFAULT '',
            zip_code varchar(20) DEFAULT '',
            comments text DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            confirmed tinyint(1) DEFAULT 0,
            confirmation_token varchar(100) DEFAULT '',
            PRIMARY KEY (id),
            KEY petition_id (petition_id),
            KEY email (email),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add petition meta boxes
     */
    public function add_petition_meta_boxes() {
        add_meta_box(
            'eem_petition_settings',
            __('Petition Settings', 'environmental-email-marketing'),
            array($this, 'render_petition_settings_meta_box'),
            'eem_petition',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eem_petition_email_settings',
            __('Email Marketing Settings', 'environmental-email-marketing'),
            array($this, 'render_petition_email_meta_box'),
            'eem_petition',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eem_petition_stats',
            __('Petition Statistics', 'environmental-email-marketing'),
            array($this, 'render_petition_stats_meta_box'),
            'eem_petition',
            'side',
            'high'
        );
    }

    /**
     * Render petition settings meta box
     */
    public function render_petition_settings_meta_box($post) {
        wp_nonce_field('eem_petition_settings_nonce', 'eem_petition_settings_nonce');
        
        $target_signatures = get_post_meta($post->ID, '_eem_target_signatures', true) ?: 1000;
        $deadline = get_post_meta($post->ID, '_eem_petition_deadline', true);
        $recipient = get_post_meta($post->ID, '_eem_petition_recipient', true);
        $category = get_post_meta($post->ID, '_eem_petition_category', true);
        $urgency_level = get_post_meta($post->ID, '_eem_urgency_level', true) ?: 'medium';
        $show_form_above = get_post_meta($post->ID, '_eem_show_form_above', true);
        $require_confirmation = get_post_meta($post->ID, '_eem_require_confirmation', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="eem_target_signatures"><?php esc_html_e('Target Signatures', 'environmental-email-marketing'); ?></label></th>
                <td><input type="number" id="eem_target_signatures" name="eem_target_signatures" value="<?php echo esc_attr($target_signatures); ?>" min="1" /></td>
            </tr>
            <tr>
                <th><label for="eem_petition_deadline"><?php esc_html_e('Deadline', 'environmental-email-marketing'); ?></label></th>
                <td><input type="date" id="eem_petition_deadline" name="eem_petition_deadline" value="<?php echo esc_attr($deadline); ?>" /></td>
            </tr>
            <tr>
                <th><label for="eem_petition_recipient"><?php esc_html_e('Petition Recipient', 'environmental-email-marketing'); ?></label></th>
                <td><input type="text" id="eem_petition_recipient" name="eem_petition_recipient" value="<?php echo esc_attr($recipient); ?>" class="regular-text" placeholder="e.g., Environmental Protection Agency" /></td>
            </tr>
            <tr>
                <th><label for="eem_petition_category"><?php esc_html_e('Category', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <select id="eem_petition_category" name="eem_petition_category">
                        <option value=""><?php esc_html_e('Select Category', 'environmental-email-marketing'); ?></option>
                        <option value="climate_change" <?php selected($category, 'climate_change'); ?>><?php esc_html_e('Climate Change', 'environmental-email-marketing'); ?></option>
                        <option value="conservation" <?php selected($category, 'conservation'); ?>><?php esc_html_e('Conservation', 'environmental-email-marketing'); ?></option>
                        <option value="pollution" <?php selected($category, 'pollution'); ?>><?php esc_html_e('Pollution', 'environmental-email-marketing'); ?></option>
                        <option value="renewable_energy" <?php selected($category, 'renewable_energy'); ?>><?php esc_html_e('Renewable Energy', 'environmental-email-marketing'); ?></option>
                        <option value="wildlife_protection" <?php selected($category, 'wildlife_protection'); ?>><?php esc_html_e('Wildlife Protection', 'environmental-email-marketing'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="eem_urgency_level"><?php esc_html_e('Urgency Level', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <select id="eem_urgency_level" name="eem_urgency_level">
                        <option value="low" <?php selected($urgency_level, 'low'); ?>><?php esc_html_e('Low', 'environmental-email-marketing'); ?></option>
                        <option value="medium" <?php selected($urgency_level, 'medium'); ?>><?php esc_html_e('Medium', 'environmental-email-marketing'); ?></option>
                        <option value="high" <?php selected($urgency_level, 'high'); ?>><?php esc_html_e('High', 'environmental-email-marketing'); ?></option>
                        <option value="urgent" <?php selected($urgency_level, 'urgent'); ?>><?php esc_html_e('Urgent', 'environmental-email-marketing'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="eem_show_form_above"><?php esc_html_e('Show Form Above Content', 'environmental-email-marketing'); ?></label></th>
                <td><input type="checkbox" id="eem_show_form_above" name="eem_show_form_above" value="1" <?php checked($show_form_above, 1); ?> /></td>
            </tr>
            <tr>
                <th><label for="eem_require_confirmation"><?php esc_html_e('Require Email Confirmation', 'environmental-email-marketing'); ?></label></th>
                <td><input type="checkbox" id="eem_require_confirmation" name="eem_require_confirmation" value="1" <?php checked($require_confirmation, 1); ?> /></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render petition email settings meta box
     */
    public function render_petition_email_meta_box($post) {
        $auto_subscribe = get_post_meta($post->ID, '_eem_auto_subscribe', true);
        $email_lists = get_post_meta($post->ID, '_eem_email_lists', true) ?: array();
        $follow_up_campaign = get_post_meta($post->ID, '_eem_follow_up_campaign', true);
        $thank_you_template = get_post_meta($post->ID, '_eem_thank_you_template', true);
        
        // Get available email lists
        global $wpdb;
        $lists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}eem_lists ORDER BY name ASC", ARRAY_A);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="eem_auto_subscribe"><?php esc_html_e('Auto-Subscribe Signers', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <input type="checkbox" id="eem_auto_subscribe" name="eem_auto_subscribe" value="1" <?php checked($auto_subscribe, 1); ?> />
                    <p class="description"><?php esc_html_e('Automatically subscribe petition signers to email lists', 'environmental-email-marketing'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="eem_email_lists"><?php esc_html_e('Email Lists', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <?php foreach ($lists as $list): ?>
                    <label>
                        <input type="checkbox" name="eem_email_lists[]" value="<?php echo esc_attr($list['id']); ?>" <?php checked(in_array($list['id'], $email_lists)); ?> />
                        <?php echo esc_html($list['name']); ?>
                    </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th><label for="eem_follow_up_campaign"><?php esc_html_e('Follow-up Campaign', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <select id="eem_follow_up_campaign" name="eem_follow_up_campaign">
                        <option value=""><?php esc_html_e('No follow-up', 'environmental-email-marketing'); ?></option>
                        <option value="petition_updates" <?php selected($follow_up_campaign, 'petition_updates'); ?>><?php esc_html_e('Petition Updates', 'environmental-email-marketing'); ?></option>
                        <option value="related_actions" <?php selected($follow_up_campaign, 'related_actions'); ?>><?php esc_html_e('Related Actions', 'environmental-email-marketing'); ?></option>
                        <option value="thank_you_series" <?php selected($follow_up_campaign, 'thank_you_series'); ?>><?php esc_html_e('Thank You Series', 'environmental-email-marketing'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="eem_thank_you_template"><?php esc_html_e('Thank You Email Template', 'environmental-email-marketing'); ?></label></th>
                <td>
                    <select id="eem_thank_you_template" name="eem_thank_you_template">
                        <option value=""><?php esc_html_e('Default Template', 'environmental-email-marketing'); ?></option>
                        <option value="petition_thank_you" <?php selected($thank_you_template, 'petition_thank_you'); ?>><?php esc_html_e('Petition Thank You', 'environmental-email-marketing'); ?></option>
                        <option value="urgent_action_thanks" <?php selected($thank_you_template, 'urgent_action_thanks'); ?>><?php esc_html_e('Urgent Action Thanks', 'environmental-email-marketing'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render petition statistics meta box
     */
    public function render_petition_stats_meta_box($post) {
        $stats = $this->get_petition_statistics($post->ID);
        ?>
        <div class="eem-petition-stats">
            <div class="stat-item">
                <strong><?php echo number_format($stats['total_signatures']); ?></strong>
                <span><?php esc_html_e('Total Signatures', 'environmental-email-marketing'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo number_format($stats['confirmed_signatures']); ?></strong>
                <span><?php esc_html_e('Confirmed', 'environmental-email-marketing'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo number_format($stats['today_signatures']); ?></strong>
                <span><?php esc_html_e('Today', 'environmental-email-marketing'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo $stats['completion_percentage']; ?>%</strong>
                <span><?php esc_html_e('Goal Progress', 'environmental-email-marketing'); ?></span>
            </div>
            <div class="stat-item">
                <strong><?php echo $stats['top_countries']; ?></strong>
                <span><?php esc_html_e('Top Countries', 'environmental-email-marketing'); ?></span>
            </div>
        </div>
        
        <style>
        .eem-petition-stats .stat-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #00a32a;
        }
        .eem-petition-stats .stat-item strong {
            display: block;
            font-size: 18px;
            color: #00a32a;
        }
        </style>
        <?php
    }

    /**
     * Save petition meta
     */
    public function save_petition_meta($post_id) {
        if (!isset($_POST['eem_petition_settings_nonce']) || !wp_verify_nonce($_POST['eem_petition_settings_nonce'], 'eem_petition_settings_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            'eem_target_signatures',
            'eem_petition_deadline',
            'eem_petition_recipient',
            'eem_petition_category',
            'eem_urgency_level',
            'eem_show_form_above',
            'eem_require_confirmation',
            'eem_auto_subscribe',
            'eem_email_lists',
            'eem_follow_up_campaign',
            'eem_thank_you_template'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'eem_email_lists') {
                    $value = array_map('intval', $_POST[$field]);
                } else {
                    $value = sanitize_text_field($_POST[$field]);
                }
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }
    }

    /**
     * Enqueue petition scripts
     */
    public function enqueue_petition_scripts() {
        if (is_singular('eem_petition')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'eem-petitions',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/petitions.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('eem-petitions', 'eem_petition_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('eem_petition_nonce'),
                'messages' => array(
                    'signing' => __('Signing petition...', 'environmental-email-marketing'),
                    'success' => __('Thank you for signing the petition!', 'environmental-email-marketing'),
                    'error' => __('There was an error signing the petition. Please try again.', 'environmental-email-marketing'),
                    'already_signed' => __('You have already signed this petition.', 'environmental-email-marketing'),
                    'confirmation_sent' => __('Please check your email to confirm your signature.', 'environmental-email-marketing')
                )
            ));
        }
    }

    /**
     * Handle petition signature
     */
    public function handle_petition_signature() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_petition_nonce')) {
            wp_die('Security check failed');
        }
        
        $petition_id = intval($_POST['petition_id']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $country = sanitize_text_field($_POST['country'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }
        
        // Check if already signed
        if ($this->has_signed($petition_id, $email)) {
            wp_send_json_error(array('message' => 'You have already signed this petition.'));
        }
        
        $require_confirmation = get_post_meta($petition_id, '_eem_require_confirmation', true);
        $confirmation_token = $require_confirmation ? wp_generate_password(32, false) : '';
        
        // Save signature
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'eem_petition_signatures',
            array(
                'petition_id' => $petition_id,
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'country' => $country,
                'city' => $city,
                'zip_code' => $zip_code,
                'comments' => $comments,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'confirmed' => $require_confirmation ? 0 : 1,
                'confirmation_token' => $confirmation_token,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            $signature_id = $wpdb->insert_id;
            
            // Send confirmation email if required
            if ($require_confirmation) {
                $this->send_confirmation_email($petition_id, $email, $first_name, $confirmation_token);
                wp_send_json_success(array('message' => 'Please check your email to confirm your signature.'));
            } else {
                // Process confirmed signature
                $this->process_confirmed_signature($petition_id, $email, $first_name, $last_name);
                wp_send_json_success(array('message' => 'Thank you for signing the petition!'));
            }
        } else {
            wp_send_json_error(array('message' => 'There was an error signing the petition.'));
        }
    }

    /**
     * Process confirmed signature
     */
    private function process_confirmed_signature($petition_id, $email, $first_name, $last_name) {
        // Auto-subscribe if enabled
        $auto_subscribe = get_post_meta($petition_id, '_eem_auto_subscribe', true);
        if ($auto_subscribe) {
            $this->subscribe_signer($petition_id, $email, $first_name, $last_name);
        }
        
        // Send thank you email
        $this->send_thank_you_email($petition_id, $email, $first_name);
        
        // Trigger automation
        do_action('eem_petition_signed', $petition_id, array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ));
        
        // Track event
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('petition_signed', array(
            'petition_id' => $petition_id,
            'email' => $email,
            'petition_title' => get_the_title($petition_id)
        ));
    }

    /**
     * Subscribe signer to email lists
     */
    private function subscribe_signer($petition_id, $email, $first_name, $last_name) {
        $email_lists = get_post_meta($petition_id, '_eem_email_lists', true) ?: array();
        
        if (!empty($email_lists)) {
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            $subscriber_data = array(
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => 'subscribed',
                'source' => 'petition_signature',
                'lists' => $email_lists,
                'environmental_score' => 10 // Base score for petition signing
            );
            
            $existing_subscriber = $subscriber_manager->get_subscriber_by_email($email);
            
            if ($existing_subscriber) {
                // Update existing subscriber
                $current_lists = maybe_unserialize($existing_subscriber['lists']) ?: array();
                $merged_lists = array_unique(array_merge($current_lists, $email_lists));
                
                $subscriber_manager->update_subscriber($existing_subscriber['id'], array(
                    'lists' => $merged_lists,
                    'environmental_score' => $existing_subscriber['environmental_score'] + 10
                ));
            } else {
                // Add new subscriber
                $subscriber_manager->add_subscriber($subscriber_data);
            }
        }
    }

    /**
     * Send confirmation email
     */
    private function send_confirmation_email($petition_id, $email, $first_name, $token) {
        $confirmation_url = add_query_arg(array(
            'eem_action' => 'confirm_petition',
            'petition_id' => $petition_id,
            'email' => urlencode($email),
            'token' => $token
        ), home_url());
        
        $petition_title = get_the_title($petition_id);
        $template_engine = new EEM_Template_Engine();
        
        $variables = array(
            'first_name' => $first_name,
            'petition_title' => $petition_title,
            'confirmation_url' => $confirmation_url,
            'site_name' => get_bloginfo('name')
        );
        
        $subject = sprintf(__('Please confirm your signature for: %s', 'environmental-email-marketing'), $petition_title);
        $message = $template_engine->render_template('petition_confirmation', $variables);
        
        wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Send thank you email
     */
    private function send_thank_you_email($petition_id, $email, $first_name) {
        $petition_title = get_the_title($petition_id);
        $template_name = get_post_meta($petition_id, '_eem_thank_you_template', true) ?: 'petition_thank_you';
        $template_engine = new EEM_Template_Engine();
        
        $variables = array(
            'first_name' => $first_name,
            'petition_title' => $petition_title,
            'petition_url' => get_permalink($petition_id),
            'signature_count' => $this->get_signature_count($petition_id),
            'share_url' => $this->get_share_url($petition_id),
            'related_actions' => $this->get_related_actions($petition_id)
        );
        
        $subject = sprintf(__('Thank you for signing: %s', 'environmental-email-marketing'), $petition_title);
        $message = $template_engine->render_template($template_name, $variables);
        
        wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Check if user has signed petition
     */
    private function has_signed($petition_id, $email) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_petition_signatures 
             WHERE petition_id = %d AND email = %s",
            $petition_id,
            $email
        ));
        
        return $count > 0;
    }

    /**
     * Get signature count
     */
    private function get_signature_count($petition_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_petition_signatures 
             WHERE petition_id = %d AND confirmed = 1",
            $petition_id
        ));
    }

    /**
     * Get petition statistics
     */
    private function get_petition_statistics($petition_id) {
        global $wpdb;
        
        $total_signatures = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_petition_signatures WHERE petition_id = %d",
            $petition_id
        ));
        
        $confirmed_signatures = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_petition_signatures WHERE petition_id = %d AND confirmed = 1",
            $petition_id
        ));
        
        $today_signatures = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eem_petition_signatures 
             WHERE petition_id = %d AND DATE(created_at) = CURDATE()",
            $petition_id
        ));
        
        $target = get_post_meta($petition_id, '_eem_target_signatures', true) ?: 1000;
        $completion_percentage = round(($confirmed_signatures / $target) * 100, 1);
        
        $top_countries = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count FROM {$wpdb->prefix}eem_petition_signatures 
             WHERE petition_id = %d AND country != '' AND confirmed = 1 
             GROUP BY country ORDER BY count DESC LIMIT 3",
            $petition_id
        ), ARRAY_A);
        
        $top_countries_list = array_column($top_countries, 'country');
        
        return array(
            'total_signatures' => $total_signatures,
            'confirmed_signatures' => $confirmed_signatures,
            'today_signatures' => $today_signatures,
            'completion_percentage' => $completion_percentage,
            'top_countries' => implode(', ', array_slice($top_countries_list, 0, 3))
        );
    }

    /**
     * Petition form shortcode
     */
    public function petition_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'style' => 'default',
            'show_comments' => 'true',
            'show_location' => 'true'
        ), $atts);
        
        $petition_id = intval($atts['petition_id']);
        if (!$petition_id) {
            return '';
        }
        
        $show_comments = $atts['show_comments'] === 'true';
        $show_location = $atts['show_location'] === 'true';
        
        $signature_count = $this->get_signature_count($petition_id);
        $target = get_post_meta($petition_id, '_eem_target_signatures', true) ?: 1000;
        $progress_percentage = min(100, ($signature_count / $target) * 100);
        
        ob_start();
        ?>
        <div class="eem-petition-form-wrapper eem-style-<?php echo esc_attr($atts['style']); ?>">
            <div class="eem-petition-progress">
                <div class="eem-progress-bar">
                    <div class="eem-progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <div class="eem-signature-count">
                    <strong><?php echo number_format($signature_count); ?></strong> <?php esc_html_e('signatures', 'environmental-email-marketing'); ?>
                    <span class="eem-target"><?php printf(__('of %s goal', 'environmental-email-marketing'), number_format($target)); ?></span>
                </div>
            </div>
            
            <form class="eem-petition-form" data-petition-id="<?php echo esc_attr($petition_id); ?>">
                <?php wp_nonce_field('eem_petition_nonce', 'eem_nonce'); ?>
                
                <div class="eem-form-header">
                    <h3><?php esc_html_e('Sign this petition', 'environmental-email-marketing'); ?></h3>
                </div>
                
                <div class="eem-form-fields">
                    <div class="eem-field-group">
                        <div class="eem-field-half">
                            <input type="text" name="first_name" placeholder="<?php esc_attr_e('First Name', 'environmental-email-marketing'); ?>" class="eem-input" required>
                        </div>
                        <div class="eem-field-half">
                            <input type="text" name="last_name" placeholder="<?php esc_attr_e('Last Name', 'environmental-email-marketing'); ?>" class="eem-input" required>
                        </div>
                    </div>
                    
                    <div class="eem-field-group">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Email Address', 'environmental-email-marketing'); ?>" class="eem-input" required>
                    </div>
                    
                    <?php if ($show_location): ?>
                    <div class="eem-field-group">
                        <div class="eem-field-half">
                            <input type="text" name="country" placeholder="<?php esc_attr_e('Country', 'environmental-email-marketing'); ?>" class="eem-input">
                        </div>
                        <div class="eem-field-quarter">
                            <input type="text" name="city" placeholder="<?php esc_attr_e('City', 'environmental-email-marketing'); ?>" class="eem-input">
                        </div>
                        <div class="eem-field-quarter">
                            <input type="text" name="zip_code" placeholder="<?php esc_attr_e('ZIP', 'environmental-email-marketing'); ?>" class="eem-input">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_comments): ?>
                    <div class="eem-field-group">
                        <textarea name="comments" placeholder="<?php esc_attr_e('Comments (optional)', 'environmental-email-marketing'); ?>" class="eem-textarea" rows="3"></textarea>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="eem-form-actions">
                    <button type="submit" class="eem-submit-button eem-button-large">
                        <span class="eem-button-text"><?php esc_html_e('Sign Petition', 'environmental-email-marketing'); ?></span>
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
     * Petition count shortcode
     */
    public function petition_count_shortcode($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'format' => 'number'
        ), $atts);
        
        $petition_id = intval($atts['petition_id']);
        $count = $this->get_signature_count($petition_id);
        
        if ($atts['format'] === 'progress') {
            $target = get_post_meta($petition_id, '_eem_target_signatures', true) ?: 1000;
            return sprintf('%s of %s signatures', number_format($count), number_format($target));
        }
        
        return number_format($count);
    }

    /**
     * Add petition form to content
     */
    public function add_petition_form_to_content($content) {
        if (is_singular('eem_petition')) {
            $show_form_above = get_post_meta(get_the_ID(), '_eem_show_form_above', true);
            $form = do_shortcode('[eem_petition_form]');
            
            if ($show_form_above) {
                $content = $form . $content;
            } else {
                $content = $content . $form;
            }
        }
        
        return $content;
    }

    /**
     * Trigger petition automation
     */
    public function trigger_petition_automation($petition_id, $signer_data) {
        $automation = new EEM_Automation_Engine();
        
        $trigger_data = array_merge($signer_data, array(
            'petition_id' => $petition_id,
            'petition_title' => get_the_title($petition_id),
            'petition_category' => get_post_meta($petition_id, '_eem_petition_category', true),
            'urgency_level' => get_post_meta($petition_id, '_eem_urgency_level', true)
        ));
        
        $automation->trigger_automation('petition_signed', $trigger_data);
    }

    /**
     * Get share URL
     */
    private function get_share_url($petition_id) {
        return add_query_arg(array(
            'utm_source' => 'email',
            'utm_medium' => 'share',
            'utm_campaign' => 'petition_' . $petition_id
        ), get_permalink($petition_id));
    }

    /**
     * Get related actions
     */
    private function get_related_actions($petition_id) {
        $category = get_post_meta($petition_id, '_eem_petition_category', true);
        
        if ($category) {
            $related_petitions = get_posts(array(
                'post_type' => 'eem_petition',
                'posts_per_page' => 3,
                'post__not_in' => array($petition_id),
                'meta_query' => array(
                    array(
                        'key' => '_eem_petition_category',
                        'value' => $category,
                        'compare' => '='
                    )
                )
            ));
            
            $actions = array();
            foreach ($related_petitions as $petition) {
                $actions[] = array(
                    'title' => $petition->post_title,
                    'url' => get_permalink($petition->ID),
                    'signature_count' => $this->get_signature_count($petition->ID)
                );
            }
            
            return $actions;
        }
        
        return array();
    }
}

// Initialize petitions integration
new EEM_Petitions_Integration();
