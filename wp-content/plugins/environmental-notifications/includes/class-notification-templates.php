<?php
/**
 * Notification Templates Class
 * 
 * Manages email and notification templates with customizable designs,
 * variables replacement, and multi-language support.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Notification_Templates {
    
    private static $instance = null;
    private $template_cache = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init_templates'));
        add_filter('environmental_notification_email_content', array($this, 'apply_email_template'), 10, 3);
        add_filter('environmental_notification_push_content', array($this, 'apply_push_template'), 10, 3);
        add_action('wp_ajax_en_preview_template', array($this, 'preview_template'));
        add_action('wp_ajax_en_save_template', array($this, 'save_template'));
    }
    
    /**
     * Initialize default templates
     */
    public function init_templates() {
        $this->register_default_templates();
        $this->create_templates_table();
    }
    
    /**
     * Register default notification templates
     */
    private function register_default_templates() {
        $default_templates = array(
            'waste_report_submitted' => array(
                'name' => __('Waste Report Submitted', 'environmental-notifications'),
                'type' => 'notification',
                'category' => 'waste_management',
                'email_subject' => __('New Waste Report Submitted', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('waste_report'),
                'push_template' => $this->get_default_push_template('waste_report'),
                'variables' => array('user_name', 'report_title', 'location', 'report_url', 'date'),
                'supports_html' => true
            ),
            'event_reminder' => array(
                'name' => __('Event Reminder', 'environmental-notifications'),
                'type' => 'notification',
                'category' => 'events',
                'email_subject' => __('Reminder: {{event_title}} - {{event_date}}', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('event_reminder'),
                'push_template' => $this->get_default_push_template('event_reminder'),
                'variables' => array('user_name', 'event_title', 'event_date', 'event_location', 'event_url'),
                'supports_html' => true
            ),
            'achievement_earned' => array(
                'name' => __('Achievement Earned', 'environmental-notifications'),
                'type' => 'notification',
                'category' => 'achievements',
                'email_subject' => __('🏆 Congratulations! You earned: {{achievement_name}}', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('achievement'),
                'push_template' => $this->get_default_push_template('achievement'),
                'variables' => array('user_name', 'achievement_name', 'achievement_description', 'points_earned', 'badge_url'),
                'supports_html' => true
            ),
            'forum_post_reply' => array(
                'name' => __('Forum Post Reply', 'environmental-notifications'),
                'type' => 'notification',
                'category' => 'forum',
                'email_subject' => __('New reply to your post: {{post_title}}', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('forum_reply'),
                'push_template' => $this->get_default_push_template('forum_reply'),
                'variables' => array('user_name', 'post_title', 'reply_author', 'reply_excerpt', 'post_url'),
                'supports_html' => true
            ),
            'weekly_digest' => array(
                'name' => __('Weekly Digest', 'environmental-notifications'),
                'type' => 'digest',
                'category' => 'digest',
                'email_subject' => __('Your Environmental Impact This Week', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('weekly_digest'),
                'push_template' => $this->get_default_push_template('weekly_digest'),
                'variables' => array(
                    'user_name', 'week_start', 'week_end', 'reports_count', 
                    'events_attended', 'points_earned', 'achievements_list', 'top_contributors'
                ),
                'supports_html' => true
            ),
            'system_maintenance' => array(
                'name' => __('System Maintenance', 'environmental-notifications'),
                'type' => 'system',
                'category' => 'system',
                'email_subject' => __('Scheduled Maintenance: {{maintenance_date}}', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('maintenance'),
                'push_template' => $this->get_default_push_template('maintenance'),
                'variables' => array('maintenance_date', 'start_time', 'end_time', 'affected_services'),
                'supports_html' => true
            ),
            'new_message' => array(
                'name' => __('New Message', 'environmental-notifications'),
                'type' => 'message',
                'category' => 'messaging',
                'email_subject' => __('New message from {{sender_name}}', 'environmental-notifications'),
                'email_template' => $this->get_default_email_template('new_message'),
                'push_template' => $this->get_default_push_template('new_message'),
                'variables' => array('recipient_name', 'sender_name', 'message_preview', 'conversation_url'),
                'supports_html' => true
            )
        );
        
        foreach ($default_templates as $template_id => $template_data) {
            $this->register_template($template_id, $template_data);
        }
    }
    
    /**
     * Register a template
     */
    public function register_template($template_id, $template_data) {
        $this->template_cache[$template_id] = $template_data;
        
        // Save to database if not exists
        $this->save_template_to_db($template_id, $template_data);
    }
    
    /**
     * Get template by ID
     */
    public function get_template($template_id) {
        // Check cache first
        if (isset($this->template_cache[$template_id])) {
            return $this->template_cache[$template_id];
        }
        
        // Load from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'en_notification_templates';
        
        $template = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} WHERE template_id = %s AND active = 1
        ", $template_id));
        
        if ($template) {
            $template_data = array(
                'name' => $template->name,
                'type' => $template->type,
                'category' => $template->category,
                'email_subject' => $template->email_subject,
                'email_template' => $template->email_template,
                'push_template' => $template->push_template,
                'variables' => json_decode($template->variables, true),
                'supports_html' => (bool) $template->supports_html,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at
            );
            
            $this->template_cache[$template_id] = $template_data;
            return $template_data;
        }
        
        return null;
    }
    
    /**
     * Apply email template
     */
    public function apply_email_template($content, $template_id, $variables = array()) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return $content;
        }
        
        // Replace variables in subject and content
        $subject = $this->replace_variables($template['email_subject'], $variables);
        $email_content = $this->replace_variables($template['email_template'], $variables);
        
        // Apply email wrapper
        $final_content = $this->apply_email_wrapper($email_content, $subject, $variables);
        
        return array(
            'subject' => $subject,
            'content' => $final_content,
            'headers' => $this->get_email_headers($template)
        );
    }
    
    /**
     * Apply push notification template
     */
    public function apply_push_template($content, $template_id, $variables = array()) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return $content;
        }
        
        $push_content = $this->replace_variables($template['push_template'], $variables);
        
        // Parse push template JSON
        $push_data = json_decode($push_content, true);
        
        if (!$push_data) {
            // Fallback to simple text
            return array(
                'title' => $variables['title'] ?? 'Notification',
                'body' => $push_content,
                'icon' => $this->get_notification_icon($template['category']),
                'data' => $variables
            );
        }
        
        return $push_data;
    }
    
    /**
     * Replace variables in template content
     */
    private function replace_variables($content, $variables) {
        if (empty($variables)) {
            return $content;
        }
        
        foreach ($variables as $key => $value) {
            // Handle arrays (like achievements_list)
            if (is_array($value)) {
                $value = $this->format_array_variable($key, $value);
            }
            
            // Replace {{variable}} format
            $content = str_replace('{{' . $key . '}}', $value, $content);
            
            // Replace {variable} format
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        // Clean up any remaining unreplaced variables
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        $content = preg_replace('/\{[^}]+\}/', '', $content);
        
        return $content;
    }
    
    /**
     * Format array variables for templates
     */
    private function format_array_variable($key, $array) {
        switch ($key) {
            case 'achievements_list':
                return '<ul>' . implode('', array_map(function($achievement) {
                    return '<li>' . esc_html($achievement['name']) . ' - ' . esc_html($achievement['points']) . ' points</li>';
                }, $array)) . '</ul>';
                
            case 'top_contributors':
                return '<ol>' . implode('', array_map(function($contributor) {
                    return '<li>' . esc_html($contributor['name']) . ' (' . esc_html($contributor['points']) . ' points)</li>';
                }, $array)) . '</ol>';
                
            case 'affected_services':
                return '<ul>' . implode('', array_map(function($service) {
                    return '<li>' . esc_html($service) . '</li>';
                }, $array)) . '</ul>';
                
            default:
                return implode(', ', $array);
        }
    }
    
    /**
     * Apply email wrapper template
     */
    private function apply_email_wrapper($content, $subject, $variables) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $logo_url = $this->get_site_logo_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($subject); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background-color: #2c5530; color: white; padding: 20px; text-align: center; }
                .header img { max-height: 50px; margin-bottom: 10px; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .content h2 { color: #2c5530; margin-top: 0; }
                .content p { margin-bottom: 15px; }
                .button { display: inline-block; background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 15px 0; }
                .footer { background-color: #f8f8f8; padding: 20px; text-align: center; font-size: 14px; color: #666; }
                .footer a { color: #2c5530; text-decoration: none; }
                .social-links { margin: 15px 0; }
                .social-links a { margin: 0 10px; }
                .unsubscribe { margin-top: 20px; font-size: 12px; }
                @media only screen and (max-width: 600px) {
                    .container { width: 100% !important; }
                    .content { padding: 20px !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
                    <?php endif; ?>
                    <h1><?php echo esc_html($site_name); ?></h1>
                    <p><?php _e('Environmental Platform', 'environmental-notifications'); ?></p>
                </div>
                
                <div class="content">
                    <?php echo $content; ?>
                </div>
                
                <div class="footer">
                    <div class="social-links">
                        <a href="<?php echo esc_url($site_url); ?>"><?php _e('Visit Website', 'environmental-notifications'); ?></a> |
                        <a href="<?php echo esc_url($site_url . '/contact'); ?>"><?php _e('Contact Us', 'environmental-notifications'); ?></a> |
                        <a href="<?php echo esc_url($site_url . '/privacy'); ?>"><?php _e('Privacy Policy', 'environmental-notifications'); ?></a>
                    </div>
                    
                    <p><?php printf(__('You are receiving this email because you are a member of %s.', 'environmental-notifications'), $site_name); ?></p>
                    
                    <div class="unsubscribe">
                        <a href="<?php echo esc_url($site_url . '/wp-admin/profile.php#environmental-notifications'); ?>"><?php _e('Manage email preferences', 'environmental-notifications'); ?></a> |
                        <a href="<?php echo esc_url($site_url . '/unsubscribe'); ?>"><?php _e('Unsubscribe from all emails', 'environmental-notifications'); ?></a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers($template) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );
        
        return $headers;
    }
    
    /**
     * Get notification icon based on category
     */
    private function get_notification_icon($category) {
        $icons = array(
            'waste_management' => plugins_url('assets/icons/waste.png', dirname(__FILE__)),
            'events' => plugins_url('assets/icons/calendar.png', dirname(__FILE__)),
            'achievements' => plugins_url('assets/icons/trophy.png', dirname(__FILE__)),
            'forum' => plugins_url('assets/icons/forum.png', dirname(__FILE__)),
            'messaging' => plugins_url('assets/icons/message.png', dirname(__FILE__)),
            'system' => plugins_url('assets/icons/system.png', dirname(__FILE__)),
            'default' => plugins_url('assets/icons/notification.png', dirname(__FILE__))
        );
        
        return $icons[$category] ?? $icons['default'];
    }
    
    /**
     * Get site logo URL
     */
    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            return $logo_data ? $logo_data[0] : null;
        }
        
        return null;
    }
    
    /**
     * Get all available templates
     */
    public function get_all_templates() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'en_notification_templates';
        
        return $wpdb->get_results("
            SELECT * FROM {$table_name} 
            WHERE active = 1 
            ORDER BY category, name
        ");
    }
    
    /**
     * Get templates by category
     */
    public function get_templates_by_category($category) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'en_notification_templates';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE category = %s AND active = 1 
            ORDER BY name
        ", $category));
    }
    
    /**
     * Save template to database
     */
    private function save_template_to_db($template_id, $template_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'en_notification_templates';
        
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$table_name} WHERE template_id = %s
        ", $template_id));
        
        $data = array(
            'template_id' => $template_id,
            'name' => $template_data['name'],
            'type' => $template_data['type'],
            'category' => $template_data['category'],
            'email_subject' => $template_data['email_subject'],
            'email_template' => $template_data['email_template'],
            'push_template' => $template_data['push_template'],
            'variables' => wp_json_encode($template_data['variables']),
            'supports_html' => $template_data['supports_html'] ? 1 : 0,
            'active' => 1
        );
        
        if ($existing) {
            $data['updated_at'] = current_time('mysql');
            $wpdb->update($table_name, $data, array('template_id' => $template_id));
        } else {
            $data['created_at'] = current_time('mysql');
            $data['updated_at'] = current_time('mysql');
            $wpdb->insert($table_name, $data);
        }
    }
    
    /**
     * Preview template AJAX handler
     */
    public function preview_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        $variables = $_POST['variables'] ?? array();
        
        if (!$template_id) {
            wp_send_json_error('Template ID required');
        }
        
        $template = $this->get_template($template_id);
        
        if (!$template) {
            wp_send_json_error('Template not found');
        }
        
        // Generate sample variables if none provided
        if (empty($variables)) {
            $variables = $this->get_sample_variables($template_id);
        }
        
        $email_content = $this->apply_email_template('', $template_id, $variables);
        $push_content = $this->apply_push_template('', $template_id, $variables);
        
        wp_send_json_success(array(
            'email' => $email_content,
            'push' => $push_content,
            'variables' => $variables
        ));
    }
    
    /**
     * Save template AJAX handler
     */
    public function save_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        $template_data = $_POST['template_data'] ?? array();
        
        if (!$template_id || empty($template_data)) {
            wp_send_json_error('Invalid template data');
        }
        
        $this->save_template_to_db($template_id, $template_data);
        
        // Clear cache
        unset($this->template_cache[$template_id]);
        
        wp_send_json_success('Template saved successfully');
    }
    
    /**
     * Get sample variables for template preview
     */
    private function get_sample_variables($template_id) {
        $samples = array(
            'waste_report_submitted' => array(
                'user_name' => 'John Doe',
                'report_title' => 'Illegal Dumping on Oak Street',
                'location' => '123 Oak Street, Sample City',
                'report_url' => home_url('/reports/123'),
                'date' => current_time('F j, Y')
            ),
            'event_reminder' => array(
                'user_name' => 'Jane Smith',
                'event_title' => 'Community Tree Planting',
                'event_date' => date('F j, Y', strtotime('+7 days')),
                'event_location' => 'Central Park',
                'event_url' => home_url('/events/tree-planting')
            ),
            'achievement_earned' => array(
                'user_name' => 'Mike Johnson',
                'achievement_name' => 'Eco Warrior',
                'achievement_description' => 'Submitted 10 environmental reports',
                'points_earned' => '250',
                'badge_url' => plugins_url('assets/badges/eco-warrior.png', dirname(__FILE__))
            ),
            'weekly_digest' => array(
                'user_name' => 'Sarah Wilson',
                'week_start' => date('F j', strtotime('-7 days')),
                'week_end' => date('F j, Y'),
                'reports_count' => '3',
                'events_attended' => '2',
                'points_earned' => '150',
                'achievements_list' => array(
                    array('name' => 'Report Master', 'points' => 100),
                    array('name' => 'Event Participant', 'points' => 50)
                ),
                'top_contributors' => array(
                    array('name' => 'Alice Green', 'points' => 500),
                    array('name' => 'Bob Earth', 'points' => 450),
                    array('name' => 'Carol Nature', 'points' => 400)
                )
            )
        );
        
        return $samples[$template_id] ?? array(
            'user_name' => 'Sample User',
            'title' => 'Sample Notification',
            'message' => 'This is a sample notification message.',
            'date' => current_time('F j, Y'),
            'url' => home_url()
        );
    }
    
    /**
     * Create templates table
     */
    private function create_templates_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'en_notification_templates';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            category varchar(50) NOT NULL,
            email_subject text,
            email_template longtext,
            push_template text,
            variables longtext,
            supports_html tinyint(1) DEFAULT 1,
            active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY template_id (template_id),
            KEY type (type),
            KEY category (category),
            KEY active (active)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Default email templates
     */
    private function get_default_email_template($type) {
        switch ($type) {
            case 'waste_report':
                return '<h2>' . __('New Waste Report Submitted', 'environmental-notifications') . '</h2>
                        <p>' . __('Hello {{user_name}},', 'environmental-notifications') . '</p>
                        <p>' . __('A new waste report has been submitted:', 'environmental-notifications') . '</p>
                        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;">
                            <h3>{{report_title}}</h3>
                            <p><strong>' . __('Location:', 'environmental-notifications') . '</strong> {{location}}</p>
                            <p><strong>' . __('Date:', 'environmental-notifications') . '</strong> {{date}}</p>
                        </div>
                        <p><a href="{{report_url}}" class="button">' . __('View Report', 'environmental-notifications') . '</a></p>
                        <p>' . __('Thank you for helping keep our environment clean!', 'environmental-notifications') . '</p>';
                        
            case 'event_reminder':
                return '<h2>' . __('Event Reminder', 'environmental-notifications') . '</h2>
                        <p>' . __('Hello {{user_name}},', 'environmental-notifications') . '</p>
                        <p>' . __('This is a reminder about the upcoming event:', 'environmental-notifications') . '</p>
                        <div style="background-color: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0;">
                            <h3>{{event_title}}</h3>
                            <p><strong>' . __('Date:', 'environmental-notifications') . '</strong> {{event_date}}</p>
                            <p><strong>' . __('Location:', 'environmental-notifications') . '</strong> {{event_location}}</p>
                        </div>
                        <p><a href="{{event_url}}" class="button">' . __('View Event Details', 'environmental-notifications') . '</a></p>
                        <p>' . __('We look forward to seeing you there!', 'environmental-notifications') . '</p>';
                        
            case 'achievement':
                return '<h2>' . __('🏆 Congratulations!', 'environmental-notifications') . '</h2>
                        <p>' . __('Hello {{user_name}},', 'environmental-notifications') . '</p>
                        <p>' . __('You have earned a new achievement:', 'environmental-notifications') . '</p>
                        <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; text-align: center;">
                            <h3>{{achievement_name}}</h3>
                            <p>{{achievement_description}}</p>
                            <p><strong>+{{points_earned}} ' . __('points', 'environmental-notifications') . '</strong></p>
                        </div>
                        <p>' . __('Keep up the great work making a positive environmental impact!', 'environmental-notifications') . '</p>';
                        
            case 'weekly_digest':
                return '<h2>' . __('Your Weekly Environmental Impact', 'environmental-notifications') . '</h2>
                        <p>' . __('Hello {{user_name}},', 'environmental-notifications') . '</p>
                        <p>' . __('Here\'s a summary of your environmental activities from {{week_start}} to {{week_end}}:', 'environmental-notifications') . '</p>
                        <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0;">
                            <h3>' . __('Your Impact This Week', 'environmental-notifications') . '</h3>
                            <ul>
                                <li>' . __('Reports submitted: {{reports_count}}', 'environmental-notifications') . '</li>
                                <li>' . __('Events attended: {{events_attended}}', 'environmental-notifications') . '</li>
                                <li>' . __('Points earned: {{points_earned}}', 'environmental-notifications') . '</li>
                            </ul>
                            <h4>' . __('Achievements Earned:', 'environmental-notifications') . '</h4>
                            {{achievements_list}}
                        </div>
                        <div style="background-color: #e8f5e8; padding: 15px; margin: 20px 0;">
                            <h3>' . __('Top Contributors This Week', 'environmental-notifications') . '</h3>
                            {{top_contributors}}
                        </div>
                        <p>' . __('Thank you for being part of our environmental community!', 'environmental-notifications') . '</p>';
                        
            default:
                return '<h2>{{title}}</h2>
                        <p>' . __('Hello {{user_name}},', 'environmental-notifications') . '</p>
                        <p>{{message}}</p>
                        <p>' . __('Thank you for being part of our environmental platform!', 'environmental-notifications') . '</p>';
        }
    }
    
    /**
     * Default push notification templates
     */
    private function get_default_push_template($type) {
        switch ($type) {
            case 'waste_report':
                return wp_json_encode(array(
                    'title' => __('New Waste Report', 'environmental-notifications'),
                    'body' => '{{report_title}} at {{location}}',
                    'icon' => $this->get_notification_icon('waste_management'),
                    'actions' => array(
                        array('action' => 'view', 'title' => __('View Report', 'environmental-notifications')),
                        array('action' => 'dismiss', 'title' => __('Dismiss', 'environmental-notifications'))
                    ),
                    'data' => array(
                        'url' => '{{report_url}}',
                        'type' => 'waste_report'
                    )
                ));
                
            case 'event_reminder':
                return wp_json_encode(array(
                    'title' => '📅 {{event_title}}',
                    'body' => '{{event_date}} at {{event_location}}',
                    'icon' => $this->get_notification_icon('events'),
                    'actions' => array(
                        array('action' => 'view', 'title' => __('View Event', 'environmental-notifications')),
                        array('action' => 'dismiss', 'title' => __('Dismiss', 'environmental-notifications'))
                    ),
                    'data' => array(
                        'url' => '{{event_url}}',
                        'type' => 'event_reminder'
                    )
                ));
                
            case 'achievement':
                return wp_json_encode(array(
                    'title' => '🏆 ' . __('Achievement Earned!', 'environmental-notifications'),
                    'body' => '{{achievement_name}} (+{{points_earned}} ' . __('points', 'environmental-notifications') . ')',
                    'icon' => $this->get_notification_icon('achievements'),
                    'badge' => '{{points_earned}}',
                    'actions' => array(
                        array('action' => 'view', 'title' => __('View Achievement', 'environmental-notifications')),
                        array('action' => 'share', 'title' => __('Share', 'environmental-notifications'))
                    ),
                    'data' => array(
                        'type' => 'achievement',
                        'achievement_id' => '{{achievement_id}}'
                    )
                ));
                
            default:
                return wp_json_encode(array(
                    'title' => '{{title}}',
                    'body' => '{{message}}',
                    'icon' => $this->get_notification_icon('default'),
                    'data' => array(
                        'type' => 'general'
                    )
                ));
        }
    }
}
