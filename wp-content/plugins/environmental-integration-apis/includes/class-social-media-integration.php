<?php
/**
 * Social Media Integration Class
 *
 * Handles integration with social media APIs (Facebook, Twitter, Instagram)
 * for environmental content sharing and social engagement.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EIA_Social_Media_Integration {
    
    private static $instance = null;
    private $api_keys = array();
    private $cache_duration = 1800; // 30 minutes
    
    // Social media platforms
    private $platforms = array(
        'facebook' => array(
            'name' => 'Facebook',
            'api_version' => 'v18.0',
            'base_url' => 'https://graph.facebook.com'
        ),
        'twitter' => array(
            'name' => 'Twitter',
            'api_version' => '2',
            'base_url' => 'https://api.twitter.com'
        ),
        'instagram' => array(
            'name' => 'Instagram',
            'api_version' => 'v18.0',
            'base_url' => 'https://graph.instagram.com'
        )
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load API keys from options
        $this->api_keys = get_option('eia_social_media_api_keys', array());
        
        // Register hooks
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_eia_share_to_social', array($this, 'ajax_share_to_social'));
        add_action('wp_ajax_eia_get_social_feed', array($this, 'ajax_get_social_feed'));
        add_action('wp_ajax_nopriv_eia_get_social_feed', array($this, 'ajax_get_social_feed'));
        add_action('wp_ajax_eia_schedule_social_post', array($this, 'ajax_schedule_social_post'));
        
        // Auto-sharing hooks
        add_action('publish_post', array($this, 'auto_share_post'));
        add_action('eia_process_scheduled_posts', array($this, 'process_scheduled_posts'));
        
        // Schedule social media tasks
        if (!wp_next_scheduled('eia_process_scheduled_posts')) {
            wp_schedule_event(time(), 'hourly', 'eia_process_scheduled_posts');
        }
    }
    
    /**
     * Register social media shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('env_social_share', array($this, 'social_share_shortcode'));
        add_shortcode('env_social_feed', array($this, 'social_feed_shortcode'));
        add_shortcode('env_social_embed', array($this, 'social_embed_shortcode'));
        add_shortcode('env_social_stats', array($this, 'social_stats_shortcode'));
    }
    
    /**
     * Share content to social media platform
     */
    public function share_to_platform($platform, $content, $options = array()) {
        // Log API request
        $request_id = $this->log_api_request('social_media', $platform, 'share', array(
            'platform' => $platform,
            'content_type' => $content['type'] ?? 'text',
            'options' => $options
        ));
        
        $share_result = null;
        $start_time = microtime(true);
        
        try {
            switch ($platform) {
                case 'facebook':
                    $share_result = $this->share_to_facebook($content, $options);
                    break;
                case 'twitter':
                    $share_result = $this->share_to_twitter($content, $options);
                    break;
                case 'instagram':
                    $share_result = $this->share_to_instagram($content, $options);
                    break;
                default:
                    throw new Exception('Unsupported social media platform: ' . $platform);
            }
            
            // Log successful response
            $this->log_api_response($request_id, 200, $share_result, microtime(true) - $start_time);
            
            return $share_result;
            
        } catch (Exception $e) {
            // Log error
            $this->log_api_response($request_id, 0, array('error' => $e->getMessage()), microtime(true) - $start_time);
            error_log('Social Media Share Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get social media feed
     */
    public function get_social_feed($platform, $options = array()) {
        // Check cache first
        $cache_key = 'social_feed_' . md5($platform . serialize($options));
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data) {
            return $cached_data;
        }
        
        // Log API request
        $request_id = $this->log_api_request('social_media', $platform, 'feed', $options);
        
        $feed_data = null;
        $start_time = microtime(true);
        
        try {
            switch ($platform) {
                case 'facebook':
                    $feed_data = $this->get_facebook_feed($options);
                    break;
                case 'twitter':
                    $feed_data = $this->get_twitter_feed($options);
                    break;
                case 'instagram':
                    $feed_data = $this->get_instagram_feed($options);
                    break;
                default:
                    throw new Exception('Unsupported social media platform: ' . $platform);
            }
            
            // Cache the result
            if ($feed_data) {
                $this->cache_data($cache_key, $feed_data, $this->cache_duration);
            }
            
            // Log successful response
            $this->log_api_response($request_id, 200, $feed_data, microtime(true) - $start_time);
            
            return $feed_data;
            
        } catch (Exception $e) {
            // Log error
            $this->log_api_response($request_id, 0, array('error' => $e->getMessage()), microtime(true) - $start_time);
            error_log('Social Media Feed Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Share to Facebook
     */
    private function share_to_facebook($content, $options) {
        if (empty($this->api_keys['facebook']['access_token'])) {
            throw new Exception('Facebook access token not configured');
        }
        
        $page_id = $this->api_keys['facebook']['page_id'] ?? null;
        if (!$page_id) {
            throw new Exception('Facebook page ID not configured');
        }
        
        $url = $this->platforms['facebook']['base_url'] . '/' . 
               $this->platforms['facebook']['api_version'] . '/' . 
               $page_id . '/feed';
        
        $post_data = array(
            'message' => $content['text'],
            'access_token' => $this->api_keys['facebook']['access_token']
        );
        
        // Add link if provided
        if (!empty($content['link'])) {
            $post_data['link'] = $content['link'];
        }
        
        // Add image if provided
        if (!empty($content['image'])) {
            // For images, use different endpoint
            $url = str_replace('/feed', '/photos', $url);
            $post_data['url'] = $content['image'];
            $post_data['caption'] = $content['text'];
            unset($post_data['message']);
        }
        
        $response = wp_remote_post($url, array(
            'body' => $post_data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception('Facebook API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return array(
            'platform' => 'facebook',
            'post_id' => $data['id'],
            'url' => "https://facebook.com/{$page_id}/posts/" . explode('_', $data['id'])[1],
            'success' => true
        );
    }
    
    /**
     * Share to Twitter
     */
    private function share_to_twitter($content, $options) {
        if (empty($this->api_keys['twitter']['bearer_token'])) {
            throw new Exception('Twitter bearer token not configured');
        }
        
        $url = $this->platforms['twitter']['base_url'] . '/2/tweets';
        
        $tweet_data = array(
            'text' => $content['text']
        );
        
        // Add media if provided
        if (!empty($content['image'])) {
            // First upload media
            $media_id = $this->upload_twitter_media($content['image']);
            if ($media_id) {
                $tweet_data['media'] = array('media_ids' => array($media_id));
            }
        }
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_keys['twitter']['bearer_token'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($tweet_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['errors'])) {
            throw new Exception('Twitter API error: ' . ($data['errors'][0]['message'] ?? 'Unknown error'));
        }
        
        return array(
            'platform' => 'twitter',
            'post_id' => $data['data']['id'],
            'url' => "https://twitter.com/user/status/{$data['data']['id']}",
            'success' => true
        );
    }
    
    /**
     * Share to Instagram
     */
    private function share_to_instagram($content, $options) {
        if (empty($this->api_keys['instagram']['access_token'])) {
            throw new Exception('Instagram access token not configured');
        }
        
        $account_id = $this->api_keys['instagram']['account_id'] ?? null;
        if (!$account_id) {
            throw new Exception('Instagram account ID not configured');
        }
        
        // Instagram requires image for posts
        if (empty($content['image'])) {
            throw new Exception('Instagram posts require an image');
        }
        
        // Step 1: Create media container
        $container_url = $this->platforms['instagram']['base_url'] . '/' . 
                        $this->platforms['instagram']['api_version'] . '/' . 
                        $account_id . '/media';
        
        $container_data = array(
            'image_url' => $content['image'],
            'caption' => $content['text'],
            'access_token' => $this->api_keys['instagram']['access_token']
        );
        
        $response = wp_remote_post($container_url, array(
            'body' => $container_data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception('Instagram API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        $creation_id = $data['id'];
        
        // Step 2: Publish the media
        $publish_url = $this->platforms['instagram']['base_url'] . '/' . 
                      $this->platforms['instagram']['api_version'] . '/' . 
                      $account_id . '/media_publish';
        
        $publish_data = array(
            'creation_id' => $creation_id,
            'access_token' => $this->api_keys['instagram']['access_token']
        );
        
        $response = wp_remote_post($publish_url, array(
            'body' => $publish_data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception('Instagram API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return array(
            'platform' => 'instagram',
            'post_id' => $data['id'],
            'url' => "https://instagram.com/p/{$data['id']}",
            'success' => true
        );
    }
    
    /**
     * Get Facebook feed
     */
    private function get_facebook_feed($options) {
        if (empty($this->api_keys['facebook']['access_token'])) {
            throw new Exception('Facebook access token not configured');
        }
        
        $page_id = $this->api_keys['facebook']['page_id'] ?? null;
        if (!$page_id) {
            throw new Exception('Facebook page ID not configured');
        }
        
        $limit = $options['limit'] ?? 10;
        $fields = 'id,message,created_time,full_picture,permalink_url,likes.summary(true),comments.summary(true),shares';
        
        $url = $this->platforms['facebook']['base_url'] . '/' . 
               $this->platforms['facebook']['api_version'] . '/' . 
               $page_id . '/posts';
        
        $url .= '?' . http_build_query(array(
            'fields' => $fields,
            'limit' => $limit,
            'access_token' => $this->api_keys['facebook']['access_token']
        ));
        
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            throw new Exception('HTTP request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['error'])) {
            throw new Exception('Facebook API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return $this->normalize_social_feed($data['data'], 'facebook');
    }
    
    /**
     * Normalize social media feed data
     */
    private function normalize_social_feed($data, $platform) {
        $normalized = array();
        
        foreach ($data as $post) {
            switch ($platform) {
                case 'facebook':
                    $normalized[] = array(
                        'id' => $post['id'],
                        'platform' => 'facebook',
                        'text' => $post['message'] ?? '',
                        'image' => $post['full_picture'] ?? null,
                        'url' => $post['permalink_url'] ?? '',
                        'created_at' => strtotime($post['created_time']),
                        'likes' => $post['likes']['summary']['total_count'] ?? 0,
                        'comments' => $post['comments']['summary']['total_count'] ?? 0,
                        'shares' => $post['shares']['count'] ?? 0
                    );
                    break;
                    
                case 'twitter':
                    $normalized[] = array(
                        'id' => $post['id'],
                        'platform' => 'twitter',
                        'text' => $post['text'] ?? '',
                        'image' => $post['attachments']['media_keys'][0] ?? null,
                        'url' => "https://twitter.com/user/status/{$post['id']}",
                        'created_at' => strtotime($post['created_at']),
                        'likes' => $post['public_metrics']['like_count'] ?? 0,
                        'comments' => $post['public_metrics']['reply_count'] ?? 0,
                        'shares' => $post['public_metrics']['retweet_count'] ?? 0
                    );
                    break;
                    
                case 'instagram':
                    $normalized[] = array(
                        'id' => $post['id'],
                        'platform' => 'instagram',
                        'text' => $post['caption'] ?? '',
                        'image' => $post['media_url'] ?? null,
                        'url' => $post['permalink'] ?? '',
                        'created_at' => strtotime($post['timestamp']),
                        'likes' => $post['like_count'] ?? 0,
                        'comments' => $post['comments_count'] ?? 0,
                        'shares' => 0 // Instagram doesn't have shares
                    );
                    break;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Social share shortcode
     */
    public function social_share_shortcode($atts) {
        $atts = shortcode_atts(array(
            'platforms' => 'facebook,twitter',
            'text' => '',
            'url' => '',
            'image' => '',
            'style' => 'buttons'
        ), $atts);
        
        $platforms = array_map('trim', explode(',', $atts['platforms']));
        
        ob_start();
        ?>
        <div class="eia-social-share" data-style="<?php echo esc_attr($atts['style']); ?>">
            <?php foreach ($platforms as $platform): ?>
                <?php if (isset($this->platforms[$platform])): ?>
                <button class="social-share-btn social-<?php echo esc_attr($platform); ?>" 
                        data-platform="<?php echo esc_attr($platform); ?>"
                        data-text="<?php echo esc_attr($atts['text']); ?>"
                        data-url="<?php echo esc_attr($atts['url']); ?>"
                        data-image="<?php echo esc_attr($atts['image']); ?>">
                    <span class="social-icon"></span>
                    <span class="social-label"><?php echo esc_html($this->platforms[$platform]['name']); ?></span>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Social feed shortcode
     */
    public function social_feed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'platform' => 'facebook',
            'limit' => '5',
            'layout' => 'list',
            'show_images' => 'true',
            'show_stats' => 'true'
        ), $atts);
        
        $feed = $this->get_social_feed($atts['platform'], array('limit' => intval($atts['limit'])));
        
        if (!$feed) {
            return '<div class="eia-social-feed-error">Unable to load social media feed</div>';
        }
        
        return $this->render_social_feed($feed, $atts);
    }
    
    /**
     * Render social media feed
     */
    private function render_social_feed($feed, $atts) {
        ob_start();
        ?>
        <div class="eia-social-feed <?php echo esc_attr($atts['layout']); ?>" data-platform="<?php echo esc_attr($atts['platform']); ?>">
            <?php foreach ($feed as $post): ?>
            <div class="social-post" data-post-id="<?php echo esc_attr($post['id']); ?>">
                <?php if ($atts['show_images'] === 'true' && !empty($post['image'])): ?>
                <div class="post-image">
                    <img src="<?php echo esc_url($post['image']); ?>" alt="Social media post image">
                </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <div class="post-text"><?php echo wp_kses_post(nl2br($post['text'])); ?></div>
                    
                    <div class="post-meta">
                        <span class="post-date"><?php echo date('M j, Y', $post['created_at']); ?></span>
                        <span class="post-platform"><?php echo esc_html(ucfirst($post['platform'])); ?></span>
                    </div>
                    
                    <?php if ($atts['show_stats'] === 'true'): ?>
                    <div class="post-stats">
                        <span class="stat-likes"><?php echo number_format($post['likes']); ?> likes</span>
                        <span class="stat-comments"><?php echo number_format($post['comments']); ?> comments</span>
                        <?php if ($post['shares'] > 0): ?>
                        <span class="stat-shares"><?php echo number_format($post['shares']); ?> shares</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="post-actions">
                        <a href="<?php echo esc_url($post['url']); ?>" target="_blank" class="view-post">View Post</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for social sharing
     */
    public function ajax_share_to_social() {
        check_ajax_referer('eia_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform'] ?? '');
        $content = array(
            'text' => sanitize_textarea_field($_POST['text'] ?? ''),
            'link' => esc_url_raw($_POST['url'] ?? ''),
            'image' => esc_url_raw($_POST['image'] ?? '')
        );
        
        if (empty($platform) || empty($content['text'])) {
            wp_send_json_error('Platform and text are required');
        }
        
        $result = $this->share_to_platform($platform, $content);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Failed to share to social media');
        }
    }
    
    /**
     * AJAX handler for social feed
     */
    public function ajax_get_social_feed() {
        $platform = sanitize_text_field($_GET['platform'] ?? '');
        $limit = intval($_GET['limit'] ?? 10);
        
        if (empty($platform)) {
            wp_send_json_error('Platform is required');
        }
        
        $feed = $this->get_social_feed($platform, array('limit' => $limit));
        
        if ($feed) {
            wp_send_json_success($feed);
        } else {
            wp_send_json_error('Unable to fetch social media feed');
        }
    }
    
    /**
     * Auto-share post to social media
     */
    public function auto_share_post($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        // Check if auto-sharing is enabled for this post
        $auto_share_settings = get_post_meta($post_id, '_eia_auto_share', true);
        if (empty($auto_share_settings)) {
            return;
        }
        
        $content = array(
            'text' => $this->generate_social_text($post),
            'link' => get_permalink($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'large')
        );
        
        foreach ($auto_share_settings as $platform) {
            if (isset($this->platforms[$platform])) {
                $this->share_to_platform($platform, $content);
            }
        }
    }
    
    /**
     * Generate social media text from post
     */
    private function generate_social_text($post) {
        $text = $post->post_title;
        
        // Add excerpt if available
        if (!empty($post->post_excerpt)) {
            $text .= "\n\n" . $post->post_excerpt;
        }
        
        // Add environmental hashtags
        $text .= "\n\n#Environment #EcoFriendly #Sustainability #GreenLiving";
        
        return $text;
    }
    
    /**
     * Helper methods for caching and logging
     */
    private function get_cached_data($key) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT data, expires_at FROM {$wpdb->prefix}eia_api_cache 
             WHERE cache_key = %s AND expires_at > %s",
            $key, current_time('mysql')
        ));
        
        return $result ? json_decode($result->data, true) : null;
    }
    
    private function cache_data($key, $data, $duration) {
        global $wpdb;
        
        $expires_at = date('Y-m-d H:i:s', time() + $duration);
        
        $wpdb->replace(
            $wpdb->prefix . 'eia_api_cache',
            array(
                'cache_key' => $key,
                'data' => json_encode($data),
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    private function log_api_request($service, $provider, $endpoint, $params) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'service' => $service,
                'provider' => $provider,
                'endpoint' => $endpoint,
                'request_data' => json_encode($params),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    private function log_api_response($request_id, $status_code, $response_data, $response_time) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'eia_api_logs',
            array(
                'status_code' => $status_code,
                'response_data' => json_encode($response_data),
                'response_time' => $response_time,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $request_id),
            array('%d', '%s', '%f', '%s'),
            array('%d')
        );
    }
}

// Initialize the class
EIA_Social_Media_Integration::get_instance();
