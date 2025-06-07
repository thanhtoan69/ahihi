<?php
/**
 * Recommendation Display Class
 * 
 * Handles the frontend display of recommendations including HTML generation,
 * widget rendering, and various display formats for different contexts.
 * 
 * @package Environmental_Content_Recommendation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ECR_Recommendation_Display {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Recommendation engine instance
     */
    private $recommendation_engine;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->recommendation_engine = ECR_Recommendation_Engine::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_display_assets'));
        add_action('wp_footer', array($this, 'inject_recommendation_styles'));
        add_shortcode('ecr_recommendations', array($this, 'recommendations_shortcode'));
        add_shortcode('ecr_similar_content', array($this, 'similar_content_shortcode'));
        add_shortcode('ecr_trending', array($this, 'trending_content_shortcode'));
        add_shortcode('ecr_environmental', array($this, 'environmental_content_shortcode'));
        
        // Widget areas
        add_action('widgets_init', array($this, 'register_recommendation_widgets'));
        
        // Auto-injection hooks
        add_filter('the_content', array($this, 'auto_inject_recommendations'));
        add_action('wp_ajax_ecr_load_recommendations', array($this, 'ajax_load_recommendations'));
        add_action('wp_ajax_nopriv_ecr_load_recommendations', array($this, 'ajax_load_recommendations'));
    }
    
    /**
     * Enqueue display assets
     */
    public function enqueue_display_assets() {
        wp_enqueue_style(
            'ecr-recommendations',
            ECR_PLUGIN_URL . 'assets/css/recommendations.css',
            array(),
            ECR_VERSION
        );
        
        wp_enqueue_script(
            'ecr-recommendations',
            ECR_PLUGIN_URL . 'assets/js/recommendations.js',
            array('jquery'),
            ECR_VERSION,
            true
        );
        
        wp_localize_script('ecr-recommendations', 'ecr_display', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecr_recommendations_nonce'),
            'loading_text' => __('Loading recommendations...', 'environmental-content-recommendation'),
            'error_text' => __('Failed to load recommendations', 'environmental-content-recommendation'),
            'user_id' => get_current_user_id()
        ));
    }
    
    /**
     * Inject additional styles
     */
    public function inject_recommendation_styles() {
        $custom_css = get_option('ecr_custom_css', '');
        if (!empty($custom_css)) {
            echo '<style type="text/css">' . wp_strip_all_tags($custom_css) . '</style>';
        }
    }
    
    /**
     * Main recommendations shortcode
     */
    public function recommendations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'personalized',
            'count' => 5,
            'layout' => 'grid',
            'show_excerpts' => 'true',
            'show_images' => 'true',
            'show_environmental_score' => 'true',
            'user_id' => get_current_user_id(),
            'content_types' => 'post,page,product',
            'class' => 'ecr-recommendations'
        ), $atts, 'ecr_recommendations');
        
        $user_id = intval($atts['user_id']);
        $count = intval($atts['count']);
        $content_types = explode(',', $atts['content_types']);
        
        // Get recommendations based on type
        switch ($atts['type']) {
            case 'personalized':
                $recommendations = $this->recommendation_engine->get_personalized_recommendations($user_id, $count);
                break;
            case 'similar':
                global $post;
                $recommendations = $this->recommendation_engine->get_similar_content($post->ID, $count);
                break;
            case 'trending':
                $recommendations = $this->recommendation_engine->get_trending_recommendations($count);
                break;
            case 'environmental':
                $recommendations = $this->recommendation_engine->get_environmental_recommendations($count);
                break;
            default:
                $recommendations = $this->recommendation_engine->get_personalized_recommendations($user_id, $count);
                break;
        }
        
        if (empty($recommendations)) {
            return $this->render_no_recommendations_message();
        }
        
        return $this->render_recommendations($recommendations, $atts);
    }
    
    /**
     * Similar content shortcode
     */
    public function similar_content_shortcode($atts) {
        global $post;
        
        if (!$post) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'count' => 5,
            'layout' => 'list',
            'show_excerpts' => 'false',
            'show_images' => 'true',
            'class' => 'ecr-similar-content'
        ), $atts, 'ecr_similar_content');
        
        $count = intval($atts['count']);
        $similar_content = $this->recommendation_engine->get_similar_content($post->ID, $count);
        
        if (empty($similar_content)) {
            return '';
        }
        
        return $this->render_recommendations($similar_content, $atts);
    }
    
    /**
     * Trending content shortcode
     */
    public function trending_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'layout' => 'grid',
            'days' => 7,
            'show_excerpts' => 'true',
            'show_images' => 'true',
            'class' => 'ecr-trending-content'
        ), $atts, 'ecr_trending');
        
        $count = intval($atts['count']);
        $trending = $this->recommendation_engine->get_trending_recommendations($count);
        
        if (empty($trending)) {
            return $this->render_no_recommendations_message(__('No trending content found', 'environmental-content-recommendation'));
        }
        
        return $this->render_recommendations($trending, $atts);
    }
    
    /**
     * Environmental content shortcode
     */
    public function environmental_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'layout' => 'grid',
            'min_score' => 70,
            'show_excerpts' => 'true',
            'show_images' => 'true',
            'show_environmental_score' => 'true',
            'class' => 'ecr-environmental-content'
        ), $atts, 'ecr_environmental');
        
        $count = intval($atts['count']);
        $environmental = $this->recommendation_engine->get_environmental_recommendations($count);
        
        if (empty($environmental)) {
            return $this->render_no_recommendations_message(__('No environmental content found', 'environmental-content-recommendation'));
        }
        
        return $this->render_recommendations($environmental, $atts);
    }
    
    /**
     * Render recommendations HTML
     */
    public function render_recommendations($recommendations, $atts = array()) {
        $defaults = array(
            'layout' => 'grid',
            'show_excerpts' => 'true',
            'show_images' => 'true',
            'show_environmental_score' => 'false',
            'class' => 'ecr-recommendations',
            'title' => ''
        );
        
        $atts = array_merge($defaults, $atts);
        
        ob_start();
        
        $container_class = 'ecr-recommendations-container ecr-layout-' . esc_attr($atts['layout']);
        if (!empty($atts['class'])) {
            $container_class .= ' ' . esc_attr($atts['class']);
        }
        
        echo '<div class="' . $container_class . '">';
        
        if (!empty($atts['title'])) {
            echo '<h3 class="ecr-recommendations-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        echo '<div class="ecr-recommendations-list">';
        
        foreach ($recommendations as $recommendation) {
            echo $this->render_single_recommendation($recommendation, $atts);
        }
        
        echo '</div>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render single recommendation item
     */
    private function render_single_recommendation($recommendation, $atts) {
        $post_id = is_array($recommendation) ? $recommendation['content_id'] : $recommendation->content_id;
        $post = get_post($post_id);
        
        if (!$post) {
            return '';
        }
        
        $score = is_array($recommendation) ? 
            (isset($recommendation['score']) ? $recommendation['score'] : 0) : 
            (isset($recommendation->score) ? $recommendation->score : 0);
            
        $environmental_score = is_array($recommendation) ? 
            (isset($recommendation['environmental_score']) ? $recommendation['environmental_score'] : 0) : 
            (isset($recommendation->environmental_score) ? $recommendation->environmental_score : 0);
        
        ob_start();
        ?>
        <div class="ecr-recommendation-item" data-post-id="<?php echo esc_attr($post_id); ?>" data-score="<?php echo esc_attr($score); ?>">
            <?php if ($atts['show_images'] === 'true' && has_post_thumbnail($post_id)): ?>
                <div class="ecr-recommendation-image">
                    <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                        <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'ecr-recommendation-thumbnail')); ?>
                    </a>
                    <?php if ($atts['show_environmental_score'] === 'true' && $environmental_score > 0): ?>
                        <div class="ecr-environmental-badge" title="Environmental Impact Score: <?php echo esc_attr($environmental_score); ?>%">
                            <span class="ecr-eco-icon">🌱</span>
                            <span class="ecr-eco-score"><?php echo esc_html(round($environmental_score)); ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="ecr-recommendation-content">
                <h4 class="ecr-recommendation-title">
                    <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </a>
                </h4>
                
                <?php if ($atts['show_excerpts'] === 'true'): ?>
                    <div class="ecr-recommendation-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt($post_id), 20); ?>
                    </div>
                <?php endif; ?>
                
                <div class="ecr-recommendation-meta">
                    <span class="ecr-recommendation-date">
                        <?php echo esc_html(get_the_date('', $post_id)); ?>
                    </span>
                    
                    <?php if ($post->post_type === 'product' && function_exists('wc_get_product')): ?>
                        <?php $product = wc_get_product($post_id); ?>
                        <?php if ($product): ?>
                            <span class="ecr-recommendation-price">
                                <?php echo $product->get_price_html(); ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($score > 0): ?>
                        <span class="ecr-recommendation-score" title="Recommendation Score">
                            <?php echo esc_html(round($score * 100)); ?>% match
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php
                $categories = get_the_category($post_id);
                if (!empty($categories)):
                ?>
                    <div class="ecr-recommendation-categories">
                        <?php foreach (array_slice($categories, 0, 3) as $category): ?>
                            <span class="ecr-category-tag">
                                <?php echo esc_html($category->name); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render no recommendations message
     */
    private function render_no_recommendations_message($message = '') {
        if (empty($message)) {
            $message = __('No recommendations available at this time.', 'environmental-content-recommendation');
        }
        
        ob_start();
        ?>
        <div class="ecr-no-recommendations">
            <div class="ecr-no-recommendations-icon">🌱</div>
            <div class="ecr-no-recommendations-message">
                <?php echo esc_html($message); ?>
            </div>
            <div class="ecr-no-recommendations-suggestion">
                <?php _e('Browse our content to help us learn your preferences!', 'environmental-content-recommendation'); ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Auto-inject recommendations into content
     */
    public function auto_inject_recommendations($content) {
        if (!is_single() || is_admin()) {
            return $content;
        }
        
        $auto_inject = get_option('ecr_auto_inject_recommendations', false);
        if (!$auto_inject) {
            return $content;
        }
        
        global $post;
        $position = get_option('ecr_injection_position', 'after_content');
        $count = get_option('ecr_injection_count', 3);
        
        $recommendations_html = $this->similar_content_shortcode(array(
            'count' => $count,
            'layout' => 'list',
            'show_excerpts' => 'true'
        ));
        
        if (empty($recommendations_html)) {
            return $content;
        }
        
        $recommendations_section = '<div class="ecr-auto-injected">';
        $recommendations_section .= '<h3>' . __('You might also like', 'environmental-content-recommendation') . '</h3>';
        $recommendations_section .= $recommendations_html;
        $recommendations_section .= '</div>';
        
        switch ($position) {
            case 'before_content':
                return $recommendations_section . $content;
            case 'after_content':
            default:
                return $content . $recommendations_section;
        }
    }
    
    /**
     * AJAX handler for loading recommendations
     */
    public function ajax_load_recommendations() {
        check_ajax_referer('ecr_recommendations_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type']);
        $count = intval($_POST['count']) ?: 5;
        $user_id = intval($_POST['user_id']) ?: get_current_user_id();
        $content_id = intval($_POST['content_id']) ?: 0;
        
        $recommendations = array();
        
        switch ($type) {
            case 'personalized':
                $recommendations = $this->recommendation_engine->get_personalized_recommendations($user_id, $count);
                break;
            case 'similar':
                if ($content_id > 0) {
                    $recommendations = $this->recommendation_engine->get_similar_content($content_id, $count);
                }
                break;
            case 'trending':
                $recommendations = $this->recommendation_engine->get_trending_recommendations($count);
                break;
            case 'environmental':
                $recommendations = $this->recommendation_engine->get_environmental_recommendations($count);
                break;
        }
        
        if (empty($recommendations)) {
            wp_send_json_error(__('No recommendations found', 'environmental-content-recommendation'));
        }
        
        $atts = array(
            'layout' => sanitize_text_field($_POST['layout']) ?: 'grid',
            'show_excerpts' => sanitize_text_field($_POST['show_excerpts']) ?: 'true',
            'show_images' => sanitize_text_field($_POST['show_images']) ?: 'true',
            'show_environmental_score' => sanitize_text_field($_POST['show_environmental_score']) ?: 'false'
        );
        
        $html = $this->render_recommendations($recommendations, $atts);
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => count($recommendations),
            'type' => $type
        ));
    }
    
    /**
     * Register recommendation widgets
     */
    public function register_recommendation_widgets() {
        register_widget('ECR_Recommendations_Widget');
        register_widget('ECR_Similar_Content_Widget');
        register_widget('ECR_Trending_Widget');
        register_widget('ECR_Environmental_Widget');
    }
    
    /**
     * Get widget HTML
     */
    public function get_widget_html($type, $args = array()) {
        $defaults = array(
            'count' => 5,
            'show_images' => true,
            'show_excerpts' => false,
            'layout' => 'list'
        );
        
        $args = array_merge($defaults, $args);
        
        switch ($type) {
            case 'personalized':
                $recommendations = $this->recommendation_engine->get_personalized_recommendations(get_current_user_id(), $args['count']);
                break;
            case 'trending':
                $recommendations = $this->recommendation_engine->get_trending_recommendations($args['count']);
                break;
            case 'environmental':
                $recommendations = $this->recommendation_engine->get_environmental_recommendations($args['count']);
                break;
            default:
                return '';
        }
        
        if (empty($recommendations)) {
            return '';
        }
        
        $atts = array(
            'layout' => $args['layout'],
            'show_excerpts' => $args['show_excerpts'] ? 'true' : 'false',
            'show_images' => $args['show_images'] ? 'true' : 'false',
            'class' => 'ecr-widget-recommendations'
        );
        
        return $this->render_recommendations($recommendations, $atts);
    }
    
    /**
     * Generate recommendation schema markup
     */
    public function generate_schema_markup($recommendations) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => __('Recommended Content', 'environmental-content-recommendation'),
            'description' => __('Personalized content recommendations', 'environmental-content-recommendation'),
            'itemListElement' => array()
        );
        
        foreach ($recommendations as $index => $recommendation) {
            $post_id = is_array($recommendation) ? $recommendation['content_id'] : $recommendation->content_id;
            $post = get_post($post_id);
            
            if ($post) {
                $schema['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => array(
                        '@type' => 'Article',
                        'name' => get_the_title($post_id),
                        'url' => get_permalink($post_id),
                        'description' => wp_trim_words(get_the_excerpt($post_id), 20),
                        'datePublished' => get_the_date('c', $post_id),
                        'author' => array(
                            '@type' => 'Person',
                            'name' => get_the_author_meta('display_name', $post->post_author)
                        )
                    )
                );
            }
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Get recommendation display statistics
     */
    public function get_display_statistics($days = 30) {
        global $wpdb;
        $behavior_table = $wpdb->prefix . 'ecr_user_behavior';
        
        $since_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = array();
        
        // Recommendation views
        $stats['recommendation_views'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$behavior_table} 
             WHERE event_type = 'recommendation_view' AND timestamp >= %s",
            $since_date
        ));
        
        // Recommendation clicks
        $stats['recommendation_clicks'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$behavior_table} 
             WHERE event_type = 'recommendation_click' AND timestamp >= %s",
            $since_date
        ));
        
        // Click-through rate
        $stats['ctr'] = $stats['recommendation_views'] > 0 ? 
            ($stats['recommendation_clicks'] / $stats['recommendation_views']) * 100 : 0;
        
        // Popular recommendation types
        $stats['popular_types'] = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_EXTRACT(event_data, '$.recommendation_type') as type,
                COUNT(*) as count
             FROM {$behavior_table} 
             WHERE event_type = 'recommendation_view' AND timestamp >= %s
             GROUP BY type
             ORDER BY count DESC
             LIMIT 5",
            $since_date
        ));
        
        return $stats;
    }
}

/**
 * Base Recommendation Widget Class
 */
class ECR_Base_Widget extends WP_Widget {
    
    protected $widget_type = 'recommendations';
    protected $widget_name = 'Environmental Recommendations';
    protected $widget_description = 'Display environmental content recommendations';
    
    public function __construct() {
        parent::__construct(
            'ecr_' . $this->widget_type . '_widget',
            $this->widget_name,
            array('description' => $this->widget_description)
        );
    }
    
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $count = !empty($instance['count']) ? intval($instance['count']) : 5;
        $show_images = !empty($instance['show_images']);
        $show_excerpts = !empty($instance['show_excerpts']);
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        $display = ECR_Recommendation_Display::get_instance();
        echo $display->get_widget_html($this->widget_type, array(
            'count' => $count,
            'show_images' => $show_images,
            'show_excerpts' => $show_excerpts
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $count = !empty($instance['count']) ? $instance['count'] : 5;
        $show_images = !empty($instance['show_images']);
        $show_excerpts = !empty($instance['show_excerpts']);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'environmental-content-recommendation'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>"><?php _e('Number of items:', 'environmental-content-recommendation'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('count')); ?>" name="<?php echo esc_attr($this->get_field_name('count')); ?>" type="number" value="<?php echo esc_attr($count); ?>" min="1" max="20">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_images); ?> id="<?php echo esc_attr($this->get_field_id('show_images')); ?>" name="<?php echo esc_attr($this->get_field_name('show_images')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_images')); ?>"><?php _e('Show images', 'environmental-content-recommendation'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_excerpts); ?> id="<?php echo esc_attr($this->get_field_id('show_excerpts')); ?>" name="<?php echo esc_attr($this->get_field_name('show_excerpts')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_excerpts')); ?>"><?php _e('Show excerpts', 'environmental-content-recommendation'); ?></label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['count'] = (!empty($new_instance['count'])) ? intval($new_instance['count']) : 5;
        $instance['show_images'] = !empty($new_instance['show_images']);
        $instance['show_excerpts'] = !empty($new_instance['show_excerpts']);
        return $instance;
    }
}

/**
 * Personalized Recommendations Widget
 */
class ECR_Recommendations_Widget extends ECR_Base_Widget {
    protected $widget_type = 'personalized';
    protected $widget_name = 'Personalized Recommendations';
    protected $widget_description = 'Display personalized content recommendations based on user behavior';
}

/**
 * Similar Content Widget
 */
class ECR_Similar_Content_Widget extends ECR_Base_Widget {
    protected $widget_type = 'similar';
    protected $widget_name = 'Similar Content';
    protected $widget_description = 'Display content similar to the current post';
}

/**
 * Trending Content Widget  
 */
class ECR_Trending_Widget extends ECR_Base_Widget {
    protected $widget_type = 'trending';
    protected $widget_name = 'Trending Content';
    protected $widget_description = 'Display currently trending content';
}

/**
 * Environmental Content Widget
 */
class ECR_Environmental_Widget extends ECR_Base_Widget {
    protected $widget_type = 'environmental';
    protected $widget_name = 'Environmental Content';
    protected $widget_description = 'Display high-impact environmental content';
}
