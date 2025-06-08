<?php
/**
 * Share Manager Class
 * 
 * Handles petition sharing across social media platforms and tracking
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_Share_Manager {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Supported social platforms
     */
    private $platforms = array(
        'facebook' => array(
            'name' => 'Facebook',
            'icon' => 'fab fa-facebook-f',
            'color' => '#1877f2',
            'url_template' => 'https://www.facebook.com/sharer/sharer.php?u={url}&quote={text}'
        ),
        'twitter' => array(
            'name' => 'Twitter',
            'icon' => 'fab fa-twitter',
            'color' => '#1da1f2',
            'url_template' => 'https://twitter.com/intent/tweet?url={url}&text={text}&hashtags={hashtags}'
        ),
        'linkedin' => array(
            'name' => 'LinkedIn',
            'icon' => 'fab fa-linkedin-in',
            'color' => '#0077b5',
            'url_template' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}'
        ),
        'whatsapp' => array(
            'name' => 'WhatsApp',
            'icon' => 'fab fa-whatsapp',
            'color' => '#25d366',
            'url_template' => 'https://wa.me/?text={text}%20{url}'
        ),
        'telegram' => array(
            'name' => 'Telegram',
            'icon' => 'fab fa-telegram-plane',
            'color' => '#0088cc',
            'url_template' => 'https://t.me/share/url?url={url}&text={text}'
        ),
        'email' => array(
            'name' => 'Email',
            'icon' => 'fas fa-envelope',
            'color' => '#666666',
            'url_template' => 'mailto:?subject={subject}&body={text}%20{url}'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_track_petition_share', array($this, 'ajax_track_share'));
        add_action('wp_ajax_nopriv_track_petition_share', array($this, 'ajax_track_share'));
        add_action('wp_ajax_get_petition_shares', array($this, 'ajax_get_shares'));
        add_action('wp_ajax_track_share_click', array($this, 'ajax_track_share_click'));
        add_action('wp_ajax_nopriv_track_share_click', array($this, 'ajax_track_share_click'));
        
        add_shortcode('petition_share_buttons', array($this, 'render_share_buttons_shortcode'));
        add_filter('the_content', array($this, 'auto_add_share_buttons'));
    }
    
    /**
     * Track a share
     */
    public function track_share($petition_id, $platform, $user_data = array()) {
        global $wpdb;
        
        $table = $this->database->get_table_name('shares');
        
        $share_data = array(
            'petition_id' => absint($petition_id),
            'platform' => sanitize_text_field($platform),
            'user_ip' => $this->get_user_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'referrer' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? ''),
            'share_url' => sanitize_url($user_data['share_url'] ?? ''),
            'share_text' => sanitize_text_field($user_data['share_text'] ?? ''),
            'user_location' => sanitize_text_field($user_data['location'] ?? ''),
            'device_type' => $this->detect_device_type(),
            'created_at' => current_time('mysql')
        );
        
        // Add user ID if logged in
        if (is_user_logged_in()) {
            $share_data['user_id'] = get_current_user_id();
        }
        
        $result = $wpdb->insert($table, $share_data);
        
        if ($result) {
            $share_id = $wpdb->insert_id;
            
            // Track analytics event
            $this->track_share_analytics($petition_id, $platform, $share_id);
            
            return $share_id;
        }
        
        return false;
    }
    
    /**
     * Track share click
     */
    public function track_share_click($share_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('shares');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET clicks = clicks + 1, last_clicked = %s WHERE id = %d",
            current_time('mysql'),
            $share_id
        ));
        
        // Get share details for analytics
        $share = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $share_id
        ));
        
        if ($share) {
            $this->track_share_click_analytics($share->petition_id, $share->platform, $share_id);
        }
    }
    
    /**
     * Get petition shares
     */
    public function get_petition_shares($petition_id, $limit = 50) {
        global $wpdb;
        
        $table = $this->database->get_table_name('shares');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE petition_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d",
            $petition_id,
            $limit
        ));
    }
    
    /**
     * Get share statistics
     */
    public function get_share_statistics($petition_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('shares');
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                platform,
                COUNT(*) as share_count,
                SUM(clicks) as total_clicks,
                AVG(clicks) as avg_clicks,
                MAX(created_at) as last_share
            FROM {$table} 
            WHERE petition_id = %d 
            GROUP BY platform 
            ORDER BY share_count DESC",
            $petition_id
        ));
        
        $total_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_shares,
                SUM(clicks) as total_clicks,
                COUNT(DISTINCT platform) as platforms_used,
                COUNT(DISTINCT user_ip) as unique_sharers
            FROM {$table} 
            WHERE petition_id = %d",
            $petition_id
        ));
        
        return array(
            'by_platform' => $stats,
            'totals' => $total_stats
        );
    }
    
    /**
     * Generate share URL for platform
     */
    public function generate_share_url($petition_id, $platform, $custom_text = '') {
        if (!isset($this->platforms[$platform])) {
            return false;
        }
        
        $petition = get_post($petition_id);
        if (!$petition) {
            return false;
        }
        
        $petition_url = get_permalink($petition_id);
        $petition_title = get_the_title($petition_id);
        $petition_excerpt = get_the_excerpt($petition_id);
        
        // Get custom share settings
        $share_settings = get_post_meta($petition_id, 'petition_share_settings', true);
        
        // Prepare share text
        $share_text = $custom_text ?: $share_settings['custom_message'] ?? $petition_title;
        if (empty($share_text)) {
            $share_text = sprintf('Sign this important petition: %s', $petition_title);
        }
        
        // Get hashtags
        $hashtags = $share_settings['hashtags'] ?? '';
        if (empty($hashtags)) {
            $petition_tags = wp_get_post_terms($petition_id, 'petition_type', array('fields' => 'names'));
            $hashtags = implode(',', array_map(function($tag) {
                return str_replace(' ', '', $tag);
            }, $petition_tags));
        }
        
        // Replace placeholders in URL template
        $share_url = $this->platforms[$platform]['url_template'];
        $replacements = array(
            '{url}' => urlencode($petition_url),
            '{text}' => urlencode($share_text),
            '{subject}' => urlencode($petition_title),
            '{hashtags}' => urlencode($hashtags)
        );
        
        foreach ($replacements as $placeholder => $value) {
            $share_url = str_replace($placeholder, $value, $share_url);
        }
        
        return $share_url;
    }
    
    /**
     * Render share buttons
     */
    public function render_share_buttons($petition_id, $options = array()) {
        $default_options = array(
            'platforms' => array_keys($this->platforms),
            'style' => 'default',
            'size' => 'medium',
            'label' => 'Share this petition:',
            'show_counts' => true,
            'custom_class' => ''
        );
        
        $options = wp_parse_args($options, $default_options);
        $petition_url = get_permalink($petition_id);
        $share_stats = $this->get_share_statistics($petition_id);
        
        ob_start();
        ?>
        <div class="petition-share-buttons <?php echo esc_attr($options['custom_class']); ?>" 
             data-petition-id="<?php echo esc_attr($petition_id); ?>"
             data-style="<?php echo esc_attr($options['style']); ?>"
             data-size="<?php echo esc_attr($options['size']); ?>">
            
            <?php if ($options['label']): ?>
                <div class="share-label"><?php echo esc_html($options['label']); ?></div>
            <?php endif; ?>
            
            <div class="share-buttons-container">
                <?php foreach ($options['platforms'] as $platform): ?>
                    <?php if (isset($this->platforms[$platform])): ?>
                        <?php
                        $platform_data = $this->platforms[$platform];
                        $share_url = $this->generate_share_url($petition_id, $platform);
                        $platform_stats = $this->get_platform_stats($share_stats['by_platform'], $platform);
                        ?>
                        
                        <a href="<?php echo esc_url($share_url); ?>" 
                           class="share-button share-<?php echo esc_attr($platform); ?>"
                           data-platform="<?php echo esc_attr($platform); ?>"
                           data-petition-id="<?php echo esc_attr($petition_id); ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           style="background-color: <?php echo esc_attr($platform_data['color']); ?>">
                            
                            <i class="<?php echo esc_attr($platform_data['icon']); ?>"></i>
                            <span class="platform-name"><?php echo esc_html($platform_data['name']); ?></span>
                            
                            <?php if ($options['show_counts'] && $platform_stats['share_count'] > 0): ?>
                                <span class="share-count"><?php echo number_format($platform_stats['share_count']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if ($options['show_counts']): ?>
                <div class="share-totals">
                    <span class="total-shares">
                        <?php printf('Total shares: %s', number_format($share_stats['totals']->total_shares)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .petition-share-buttons {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .share-label {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .share-buttons-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .share-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            color: white !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .share-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0.9;
        }
        
        .share-button i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .share-count {
            background: rgba(255,255,255,0.2);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 8px;
        }
        
        .share-totals {
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .share-buttons-container {
                justify-content: center;
            }
            
            .share-button .platform-name {
                display: none;
            }
            
            .share-button {
                padding: 12px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get platform statistics
     */
    private function get_platform_stats($stats, $platform) {
        foreach ($stats as $stat) {
            if ($stat->platform === $platform) {
                return (array) $stat;
            }
        }
        
        return array(
            'share_count' => 0,
            'total_clicks' => 0,
            'avg_clicks' => 0
        );
    }
    
    /**
     * Track share analytics
     */
    private function track_share_analytics($petition_id, $platform, $share_id) {
        global $wpdb;
        
        $analytics_table = $this->database->get_table_name('analytics');
        
        $wpdb->insert($analytics_table, array(
            'petition_id' => $petition_id,
            'user_id' => get_current_user_id() ?: null,
            'event_type' => 'share',
            'event_data' => wp_json_encode(array(
                'platform' => $platform,
                'share_id' => $share_id,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            )),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Track share click analytics
     */
    private function track_share_click_analytics($petition_id, $platform, $share_id) {
        global $wpdb;
        
        $analytics_table = $this->database->get_table_name('analytics');
        
        $wpdb->insert($analytics_table, array(
            'petition_id' => $petition_id,
            'user_id' => get_current_user_id() ?: null,
            'event_type' => 'share_click',
            'event_data' => wp_json_encode(array(
                'platform' => $platform,
                'share_id' => $share_id,
                'user_ip' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            )),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Auto-add share buttons to petition content
     */
    public function auto_add_share_buttons($content) {
        if (is_singular('env_petition') && in_the_loop() && is_main_query()) {
            $auto_add = get_post_meta(get_the_ID(), 'petition_auto_add_share_buttons', true);
            
            if ($auto_add !== 'no') {
                $share_buttons = $this->render_share_buttons(get_the_ID());
                $content .= $share_buttons;
            }
        }
        
        return $content;
    }
    
    /**
     * Share buttons shortcode
     */
    public function render_share_buttons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'petition_id' => get_the_ID(),
            'platforms' => 'facebook,twitter,linkedin,whatsapp,email',
            'style' => 'default',
            'size' => 'medium',
            'label' => 'Share this petition:',
            'show_counts' => 'true',
            'custom_class' => ''
        ), $atts);
        
        $options = array(
            'platforms' => explode(',', $atts['platforms']),
            'style' => $atts['style'],
            'size' => $atts['size'],
            'label' => $atts['label'],
            'show_counts' => $atts['show_counts'] === 'true',
            'custom_class' => $atts['custom_class']
        );
        
        return $this->render_share_buttons($atts['petition_id'], $options);
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return trim($ip);
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Detect device type
     */
    private function detect_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * AJAX: Track share
     */
    public function ajax_track_share() {
        check_ajax_referer('petition_nonce', 'nonce');
        
        $petition_id = absint($_POST['petition_id']);
        $platform = sanitize_text_field($_POST['platform']);
        $user_data = array(
            'share_url' => sanitize_url($_POST['share_url'] ?? ''),
            'share_text' => sanitize_text_field($_POST['share_text'] ?? ''),
            'location' => sanitize_text_field($_POST['location'] ?? '')
        );
        
        $share_id = $this->track_share($petition_id, $platform, $user_data);
        
        if ($share_id) {
            wp_send_json_success(array(
                'share_id' => $share_id,
                'message' => 'Share tracked successfully'
            ));
        } else {
            wp_send_json_error('Failed to track share');
        }
    }
    
    /**
     * AJAX: Track share click
     */
    public function ajax_track_share_click() {
        check_ajax_referer('petition_nonce', 'nonce');
        
        $share_id = absint($_POST['share_id']);
        $this->track_share_click($share_id);
        
        wp_send_json_success('Share click tracked');
    }
    
    /**
     * AJAX: Get petition shares
     */
    public function ajax_get_shares() {
        check_ajax_referer('petition_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $petition_id = absint($_POST['petition_id']);
        $shares = $this->get_petition_shares($petition_id);
        $statistics = $this->get_share_statistics($petition_id);
          wp_send_json_success(array(
            'shares' => $shares,
            'statistics' => $statistics
        ));
    }
}

// Create alias for backward compatibility
if (!class_exists('EPP_Share_Manager')) {
    class_alias('Environmental_Platform_Petitions_Share_Manager', 'EPP_Share_Manager');
}
