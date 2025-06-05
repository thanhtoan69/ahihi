<?php
/**
 * Environmental Social Viral Public Controller
 * 
 * Handles all frontend functionality including shortcode rendering,
 * user interactions, and public-facing features of the social viral system.
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Social_Viral_Public_Controller {
    
    private static $instance = null;
    private $sharing_manager;
    private $viral_engine;
    private $referral_system;
    private $analytics;
    private $content_generator;
    
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
        // Initialize core components
        $this->sharing_manager = Environmental_Social_Viral_Sharing_Manager::get_instance();
        $this->viral_engine = Environmental_Social_Viral_Engine::get_instance();
        $this->referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        $this->analytics = Environmental_Social_Viral_Analytics::get_instance();
        $this->content_generator = Environmental_Social_Viral_Content_Generator::get_instance();
        
        // Public hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('wp_footer', array($this, 'add_tracking_scripts'));
        
        // AJAX handlers for logged-in and non-logged-in users
        add_action('wp_ajax_env_track_share', array($this, 'ajax_track_share'));
        add_action('wp_ajax_nopriv_env_track_share', array($this, 'ajax_track_share'));
        add_action('wp_ajax_env_process_referral', array($this, 'ajax_process_referral'));
        add_action('wp_ajax_nopriv_env_process_referral', array($this, 'ajax_process_referral'));
        add_action('wp_ajax_env_get_viral_content', array($this, 'ajax_get_viral_content'));
        add_action('wp_ajax_nopriv_env_get_viral_content', array($this, 'ajax_get_viral_content'));
        add_action('wp_ajax_env_save_user_preference', array($this, 'ajax_save_user_preference'));
        
        // Shortcode handlers
        add_action('init', array($this, 'register_shortcodes'));
        
        // Content filters
        add_filter('the_content', array($this, 'auto_add_sharing_buttons'), 20);
        add_filter('wp_head', array($this, 'add_open_graph_meta'));
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // Only enqueue on relevant pages
        if ($this->should_enqueue_assets()) {
            wp_enqueue_script(
                'env-social-viral-frontend',
                ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ENV_SOCIAL_VIRAL_VERSION,
                true
            );
            
            wp_enqueue_style(
                'env-social-viral-frontend',
                ENV_SOCIAL_VIRAL_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                ENV_SOCIAL_VIRAL_VERSION
            );
            
            // Localize script with data
            wp_localize_script('env-social-viral-frontend', 'envSocialViral', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('env_social_viral_nonce'),
                'user_id' => get_current_user_id(),
                'site_url' => site_url(),
                'plugin_url' => ENV_SOCIAL_VIRAL_PLUGIN_URL,
                'current_post_id' => get_queried_object_id(),
                'sharing_platforms' => $this->get_enabled_platforms(),
                'viral_threshold' => get_option('env_social_viral_viral_threshold', 0.3),
                'strings' => array(
                    'sharing_success' => __('Content shared successfully!', 'environmental-social-viral'),
                    'sharing_error' => __('Error sharing content. Please try again.', 'environmental-social-viral'),
                    'copy_success' => __('Link copied to clipboard!', 'environmental-social-viral'),
                    'copy_error' => __('Unable to copy link. Please copy manually.', 'environmental-social-viral'),
                    'loading' => __('Loading...', 'environmental-social-viral'),
                    'referral_applied' => __('Referral code applied successfully!', 'environmental-social-viral'),
                    'invalid_referral' => __('Invalid referral code.', 'environmental-social-viral')
                )
            ));
        }
    }
    
    /**
     * Check if assets should be enqueued
     */
    private function should_enqueue_assets() {
        global $post;
        
        // Always enqueue on pages with shortcodes
        if ($post && (
            has_shortcode($post->post_content, 'env_sharing_buttons') ||
            has_shortcode($post->post_content, 'env_viral_dashboard') ||
            has_shortcode($post->post_content, 'env_referral_link') ||
            has_shortcode($post->post_content, 'env_sharing_stats') ||
            has_shortcode($post->post_content, 'env_viral_content')
        )) {
            return true;
        }
        
        // Enqueue on single posts/pages if auto-sharing is enabled
        if (is_singular() && get_option('env_social_viral_auto_sharing', true)) {
            return true;
        }
        
        // Enqueue on specific post types
        $enabled_post_types = get_option('env_social_viral_post_types', array('post', 'page'));
        if (is_singular($enabled_post_types)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get enabled sharing platforms
     */
    private function get_enabled_platforms() {
        $platforms = get_option('env_social_viral_platforms', array());
        $enabled = array();
        
        foreach ($platforms as $platform => $settings) {
            if (!empty($settings['enabled'])) {
                $enabled[$platform] = $settings;
            }
        }
        
        return $enabled;
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('env_sharing_buttons', array($this, 'render_sharing_buttons'));
        add_shortcode('env_viral_dashboard', array($this, 'render_viral_dashboard'));
        add_shortcode('env_referral_link', array($this, 'render_referral_link'));
        add_shortcode('env_sharing_stats', array($this, 'render_sharing_stats'));
        add_shortcode('env_viral_content', array($this, 'render_viral_content'));
    }
    
    /**
     * Render sharing buttons shortcode
     */
    public function render_sharing_buttons($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'style' => 'default',
            'size' => 'medium',
            'platforms' => '',
            'show_counts' => 'true'
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            return '';
        }
        
        $platforms = !empty($atts['platforms']) 
            ? explode(',', $atts['platforms']) 
            : array_keys($this->get_enabled_platforms());
        
        $sharing_data = $this->sharing_manager->get_sharing_data($post_id);
        
        ob_start();
        ?>
        <div class="env-sharing-buttons env-style-<?php echo esc_attr($atts['style']); ?> env-size-<?php echo esc_attr($atts['size']); ?>" 
             data-post-id="<?php echo esc_attr($post_id); ?>">
            
            <?php if ($atts['show_counts'] === 'true'): ?>
            <div class="env-sharing-counts">
                <span class="env-total-shares">
                    <?php echo number_format($sharing_data['total_shares']); ?> 
                    <?php _e('shares', 'environmental-social-viral'); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="env-sharing-platforms">
                <?php foreach ($platforms as $platform): ?>
                    <?php $platform = trim($platform); ?>
                    <?php if ($this->is_platform_enabled($platform)): ?>
                        <button class="env-share-button env-share-<?php echo esc_attr($platform); ?>" 
                                data-platform="<?php echo esc_attr($platform); ?>"
                                data-url="<?php echo esc_url(get_permalink($post)); ?>"
                                data-title="<?php echo esc_attr($post->post_title); ?>">
                            
                            <span class="env-share-icon"><?php echo $this->get_platform_icon($platform); ?></span>
                            <span class="env-share-label"><?php echo esc_html($this->get_platform_label($platform)); ?></span>
                            
                            <?php if ($atts['show_counts'] === 'true'): ?>
                            <span class="env-share-count">
                                <?php echo number_format($sharing_data['platforms'][$platform] ?? 0); ?>
                            </span>
                            <?php endif; ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Copy link button -->
                <button class="env-share-button env-share-copy" 
                        data-url="<?php echo esc_url(get_permalink($post)); ?>">
                    <span class="env-share-icon">🔗</span>
                    <span class="env-share-label"><?php _e('Copy Link', 'environmental-social-viral'); ?></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render viral dashboard shortcode
     */
    public function render_viral_dashboard($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'period' => '30',
            'show_charts' => 'true'
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>' . __('Please log in to view your viral dashboard.', 'environmental-social-viral') . '</p>';
        }
        
        $user_stats = $this->viral_engine->get_user_viral_stats($atts['user_id'], $atts['period']);
        $viral_content = $this->viral_engine->get_user_viral_content($atts['user_id'], 5);
        
        ob_start();
        ?>
        <div class="env-viral-dashboard" data-user-id="<?php echo esc_attr($atts['user_id']); ?>">
            <h3><?php _e('Your Viral Performance', 'environmental-social-viral'); ?></h3>
            
            <div class="env-stats-grid">
                <div class="env-stat-item">
                    <div class="env-stat-number"><?php echo number_format($user_stats['total_shares']); ?></div>
                    <div class="env-stat-label"><?php _e('Total Shares', 'environmental-social-viral'); ?></div>
                </div>
                
                <div class="env-stat-item">
                    <div class="env-stat-number"><?php echo number_format($user_stats['viral_coefficient'], 3); ?></div>
                    <div class="env-stat-label"><?php _e('Viral Coefficient', 'environmental-social-viral'); ?></div>
                </div>
                
                <div class="env-stat-item">
                    <div class="env-stat-number"><?php echo number_format($user_stats['viral_content_count']); ?></div>
                    <div class="env-stat-label"><?php _e('Viral Content', 'environmental-social-viral'); ?></div>
                </div>
                
                <div class="env-stat-item">
                    <div class="env-stat-number"><?php echo number_format($user_stats['total_reach']); ?></div>
                    <div class="env-stat-label"><?php _e('Total Reach', 'environmental-social-viral'); ?></div>
                </div>
            </div>
            
            <?php if ($atts['show_charts'] === 'true'): ?>
            <div class="env-viral-charts">
                <canvas id="viral-performance-chart" width="400" height="200"></canvas>
            </div>
            <?php endif; ?>
            
            <div class="env-viral-content-list">
                <h4><?php _e('Your Top Viral Content', 'environmental-social-viral'); ?></h4>
                <?php if (!empty($viral_content)): ?>
                    <div class="env-content-items">
                        <?php foreach ($viral_content as $content): ?>
                        <div class="env-content-item">
                            <h5><a href="<?php echo esc_url(get_permalink($content['post_id'])); ?>">
                                <?php echo esc_html($content['title']); ?>
                            </a></h5>
                            <div class="env-content-stats">
                                <span class="env-shares"><?php echo number_format($content['shares']); ?> <?php _e('shares', 'environmental-social-viral'); ?></span>
                                <span class="env-coefficient"><?php _e('Coefficient:', 'environmental-social-viral'); ?> <?php echo number_format($content['viral_coefficient'], 3); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?php _e('No viral content yet. Keep sharing!', 'environmental-social-viral'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render referral link shortcode
     */
    public function render_referral_link($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'post_id' => get_the_ID(),
            'style' => 'default'
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>' . __('Please log in to generate referral links.', 'environmental-social-viral') . '</p>';
        }
        
        $referral_code = $this->referral_system->get_user_referral_code($atts['user_id']);
        $referral_url = $this->referral_system->generate_referral_link($atts['user_id'], $atts['post_id']);
        $referral_stats = $this->referral_system->get_user_referral_stats($atts['user_id']);
        
        ob_start();
        ?>
        <div class="env-referral-widget env-style-<?php echo esc_attr($atts['style']); ?>">
            <h4><?php _e('Share & Earn Rewards', 'environmental-social-viral'); ?></h4>
            
            <div class="env-referral-stats">
                <div class="env-referral-stat">
                    <span class="env-stat-number"><?php echo number_format($referral_stats['total_referrals']); ?></span>
                    <span class="env-stat-label"><?php _e('Referrals', 'environmental-social-viral'); ?></span>
                </div>
                <div class="env-referral-stat">
                    <span class="env-stat-number">$<?php echo number_format($referral_stats['total_earnings'], 2); ?></span>
                    <span class="env-stat-label"><?php _e('Earned', 'environmental-social-viral'); ?></span>
                </div>
            </div>
            
            <div class="env-referral-link-section">
                <label for="referral-link"><?php _e('Your Referral Link:', 'environmental-social-viral'); ?></label>
                <div class="env-link-input-group">
                    <input type="text" id="referral-link" class="env-referral-input" 
                           value="<?php echo esc_url($referral_url); ?>" readonly>
                    <button class="env-copy-button" data-target="referral-link">
                        <?php _e('Copy', 'environmental-social-viral'); ?>
                    </button>
                </div>
                
                <div class="env-referral-code">
                    <?php _e('Your Code:', 'environmental-social-viral'); ?> 
                    <strong><?php echo esc_html($referral_code); ?></strong>
                </div>
            </div>
            
            <div class="env-social-share-referral">
                <p><?php _e('Share on social media:', 'environmental-social-viral'); ?></p>
                <div class="env-referral-share-buttons">
                    <?php foreach ($this->get_enabled_platforms() as $platform => $settings): ?>
                    <button class="env-referral-share-button env-share-<?php echo esc_attr($platform); ?>" 
                            data-platform="<?php echo esc_attr($platform); ?>"
                            data-url="<?php echo esc_url($referral_url); ?>"
                            data-text="<?php echo esc_attr($this->get_referral_share_text()); ?>">
                        <?php echo $this->get_platform_icon($platform); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render sharing stats shortcode
     */
    public function render_sharing_stats($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'period' => '30',
            'style' => 'compact'
        ), $atts);
        
        $stats = $this->analytics->get_content_sharing_stats($atts['post_id'], $atts['period']);
        
        ob_start();
        ?>
        <div class="env-sharing-stats env-style-<?php echo esc_attr($atts['style']); ?>">
            <?php if ($atts['style'] === 'detailed'): ?>
            <h4><?php _e('Sharing Statistics', 'environmental-social-viral'); ?></h4>
            
            <div class="env-detailed-stats">
                <div class="env-stat-row">
                    <span class="env-stat-label"><?php _e('Total Shares:', 'environmental-social-viral'); ?></span>
                    <span class="env-stat-value"><?php echo number_format($stats['total_shares']); ?></span>
                </div>
                <div class="env-stat-row">
                    <span class="env-stat-label"><?php _e('Viral Coefficient:', 'environmental-social-viral'); ?></span>
                    <span class="env-stat-value"><?php echo number_format($stats['viral_coefficient'], 3); ?></span>
                </div>
                <div class="env-stat-row">
                    <span class="env-stat-label"><?php _e('Estimated Reach:', 'environmental-social-viral'); ?></span>
                    <span class="env-stat-value"><?php echo number_format($stats['estimated_reach']); ?></span>
                </div>
            </div>
            
            <div class="env-platform-breakdown">
                <h5><?php _e('By Platform:', 'environmental-social-viral'); ?></h5>
                <?php foreach ($stats['platforms'] as $platform => $count): ?>
                <div class="env-platform-stat">
                    <span class="env-platform-icon"><?php echo $this->get_platform_icon($platform); ?></span>
                    <span class="env-platform-name"><?php echo esc_html($this->get_platform_label($platform)); ?></span>
                    <span class="env-platform-count"><?php echo number_format($count); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            <!-- Compact style -->
            <div class="env-compact-stats">
                <span class="env-shares-count"><?php echo number_format($stats['total_shares']); ?></span>
                <span class="env-shares-label"><?php _e('shares', 'environmental-social-viral'); ?></span>
                
                <?php if ($stats['viral_coefficient'] >= get_option('env_social_viral_viral_threshold', 0.3)): ?>
                <span class="env-viral-badge"><?php _e('Viral!', 'environmental-social-viral'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render viral content shortcode
     */
    public function render_viral_content($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'period' => '7',
            'post_type' => 'post',
            'show_stats' => 'true'
        ), $atts);
        
        $viral_content = $this->viral_engine->get_trending_content(
            intval($atts['limit']), 
            intval($atts['period']), 
            $atts['post_type']
        );
        
        ob_start();
        ?>
        <div class="env-viral-content-widget">
            <h4><?php _e('Trending Content', 'environmental-social-viral'); ?></h4>
            
            <?php if (!empty($viral_content)): ?>
            <div class="env-viral-content-list">
                <?php foreach ($viral_content as $index => $content): ?>
                <div class="env-viral-content-item">
                    <div class="env-content-rank"><?php echo ($index + 1); ?></div>
                    
                    <div class="env-content-info">
                        <h5><a href="<?php echo esc_url(get_permalink($content['post_id'])); ?>">
                            <?php echo esc_html($content['title']); ?>
                        </a></h5>
                        
                        <?php if ($atts['show_stats'] === 'true'): ?>
                        <div class="env-content-stats">
                            <span class="env-shares"><?php echo number_format($content['total_shares']); ?> <?php _e('shares', 'environmental-social-viral'); ?></span>
                            <span class="env-coefficient"><?php _e('Viral Score:', 'environmental-social-viral'); ?> <?php echo number_format($content['viral_coefficient'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="env-viral-indicator">
                        <?php if ($content['viral_coefficient'] >= get_option('env_social_viral_viral_threshold', 0.3)): ?>
                        <span class="env-viral-badge">🔥</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="env-no-viral-content"><?php _e('No viral content found for this period.', 'environmental-social-viral'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Auto-add sharing buttons to content
     */
    public function auto_add_sharing_buttons($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $auto_sharing = get_option('env_social_viral_auto_sharing', true);
        $enabled_post_types = get_option('env_social_viral_post_types', array('post', 'page'));
        
        if ($auto_sharing && in_array(get_post_type(), $enabled_post_types)) {
            $position = get_option('env_social_viral_button_position', 'bottom');
            $sharing_buttons = $this->render_sharing_buttons(array());
            
            if ($position === 'top') {
                $content = $sharing_buttons . $content;
            } else {
                $content = $content . $sharing_buttons;
            }
        }
        
        return $content;
    }
    
    /**
     * Add Open Graph meta tags
     */
    public function add_open_graph_meta() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        $sharing_data = $this->sharing_manager->get_sharing_data($post->ID);
        
        echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(wp_trim_words($post->post_content, 30)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post)) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        
        if (has_post_thumbnail($post)) {
            echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post, 'large')) . '">' . "\n";
        }
        
        // Add viral-specific meta
        if ($sharing_data['viral_coefficient'] >= get_option('env_social_viral_viral_threshold', 0.3)) {
            echo '<meta property="env:viral" content="true">' . "\n";
            echo '<meta property="env:viral_coefficient" content="' . esc_attr($sharing_data['viral_coefficient']) . '">' . "\n";
        }
    }
    
    /**
     * Add tracking scripts to footer
     */
    public function add_tracking_scripts() {
        if (!$this->should_enqueue_assets()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        // Referral tracking
        if (envSocialViral && envSocialViral.user_id) {
            var urlParams = new URLSearchParams(window.location.search);
            var refCode = urlParams.get('ref') || urlParams.get('referral');
            
            if (refCode) {
                // Track referral visit
                jQuery.post(envSocialViral.ajax_url, {
                    action: 'env_process_referral',
                    referral_code: refCode,
                    post_id: envSocialViral.current_post_id,
                    nonce: envSocialViral.nonce
                });
            }
        }
        
        // Track page views for viral analysis
        jQuery(document).ready(function($) {
            if (envSocialViral.current_post_id) {
                $.post(envSocialViral.ajax_url, {
                    action: 'env_track_view',
                    post_id: envSocialViral.current_post_id,
                    nonce: envSocialViral.nonce
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Track share
     */
    public function ajax_track_share() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $platform = sanitize_text_field($_POST['platform']);
        $user_id = get_current_user_id();
        
        $result = $this->sharing_manager->track_share($post_id, $platform, $user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Share tracked successfully', 'environmental-social-viral'),
                'new_count' => $this->sharing_manager->get_share_count($post_id, $platform)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to track share', 'environmental-social-viral')
            ));
        }
    }
    
    /**
     * AJAX: Process referral
     */
    public function ajax_process_referral() {
        $referral_code = sanitize_text_field($_POST['referral_code']);
        $post_id = intval($_POST['post_id']);
        $user_id = get_current_user_id();
        
        $result = $this->referral_system->process_referral($referral_code, $user_id, $post_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Referral processed successfully', 'environmental-social-viral')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Invalid referral code', 'environmental-social-viral')
            ));
        }
    }
    
    /**
     * AJAX: Get viral content
     */
    public function ajax_get_viral_content() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $limit = intval($_POST['limit'] ?? 10);
        $period = intval($_POST['period'] ?? 7);
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        
        $viral_content = $this->viral_engine->get_trending_content($limit, $period, $post_type);
        
        wp_send_json_success($viral_content);
    }
    
    /**
     * AJAX: Save user preference
     */
    public function ajax_save_user_preference() {
        check_ajax_referer('env_social_viral_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'environmental-social-viral')));
        }
        
        $preference = sanitize_text_field($_POST['preference']);
        $value = sanitize_text_field($_POST['value']);
        
        $result = update_user_meta($user_id, 'env_social_viral_' . $preference, $value);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Preference saved', 'environmental-social-viral')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save preference', 'environmental-social-viral')));
        }
    }
    
    /**
     * Helper: Check if platform is enabled
     */
    private function is_platform_enabled($platform) {
        $platforms = get_option('env_social_viral_platforms', array());
        return !empty($platforms[$platform]['enabled']);
    }
    
    /**
     * Helper: Get platform icon
     */
    private function get_platform_icon($platform) {
        $icons = array(
            'facebook' => '📘',
            'twitter' => '🐦',
            'linkedin' => '💼',
            'pinterest' => '📌',
            'instagram' => '📷',
            'tiktok' => '🎵',
            'whatsapp' => '💬',
            'telegram' => '✈️',
            'email' => '📧'
        );
        
        return $icons[$platform] ?? '🔗';
    }
    
    /**
     * Helper: Get platform label
     */
    private function get_platform_label($platform) {
        $labels = array(
            'facebook' => __('Facebook', 'environmental-social-viral'),
            'twitter' => __('Twitter', 'environmental-social-viral'),
            'linkedin' => __('LinkedIn', 'environmental-social-viral'),
            'pinterest' => __('Pinterest', 'environmental-social-viral'),
            'instagram' => __('Instagram', 'environmental-social-viral'),
            'tiktok' => __('TikTok', 'environmental-social-viral'),
            'whatsapp' => __('WhatsApp', 'environmental-social-viral'),
            'telegram' => __('Telegram', 'environmental-social-viral'),
            'email' => __('Email', 'environmental-social-viral')
        );
        
        return $labels[$platform] ?? ucfirst($platform);
    }
    
    /**
     * Helper: Get referral share text
     */
    private function get_referral_share_text() {
        return get_option(
            'env_social_viral_referral_text', 
            __('Check out this amazing environmental content!', 'environmental-social-viral')
        );
    }
}
