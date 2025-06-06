<?php
/**
 * Plugin Name: Environmental Platform SEO
 * Plugin URI: https://environmentalplatform.com
 * Description: Advanced SEO optimization specifically designed for Environmental Platform with eco-friendly content optimization, environmental keywords, and sustainability-focused schema markup.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: environmental-seo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENV_SEO_VERSION', '1.0.0');
define('ENV_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENV_SEO_PLUGIN_PATH', plugin_dir_path(__FILE__));

class EnvironmentalPlatformSEO {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'add_environmental_meta_tags'));
        add_action('wp_head', array($this, 'add_environmental_schema'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('save_post', array($this, 'save_seo_data'));
        add_action('add_meta_boxes', array($this, 'add_seo_meta_boxes'));
        
        // Add environmental sitemap
        add_action('init', array($this, 'setup_environmental_sitemap'));
        
        // Add environmental breadcrumbs
        add_action('wp_head', array($this, 'add_breadcrumb_schema'));
        
        // Hook into WordPress SEO if Yoast is active
        if (class_exists('WPSEO_Options')) {
            add_filter('wpseo_metadesc', array($this, 'optimize_environmental_description'));
            add_filter('wpseo_title', array($this, 'optimize_environmental_title'));
        }
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('environmental-seo', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Set up environmental SEO options
        $this->setup_default_options();
        
        // Add environmental post types to sitemap
        $this->register_environmental_post_types_for_seo();
    }
    
    public function add_environmental_meta_tags() {
        global $post;
        
        // Get environmental SEO settings
        $env_seo_options = get_option('environmental_seo_options', array());
        
        // Basic environmental meta tags
        echo '<meta name="environmental-platform" content="true">' . "\n";
        echo '<meta name="sustainability-focused" content="eco-friendly,green-living,climate-action">' . "\n";
        
        // Environmental-specific Open Graph tags
        if (is_singular()) {
            $sustainability_score = get_post_meta($post->ID, 'sustainability_score', true);
            if ($sustainability_score) {
                echo '<meta property="environmental:sustainability_score" content="' . esc_attr($sustainability_score) . '">' . "\n";
            }
            
            $carbon_impact = get_post_meta($post->ID, 'carbon_impact', true);
            if ($carbon_impact) {
                echo '<meta property="environmental:carbon_impact" content="' . esc_attr($carbon_impact) . '">' . "\n";
            }
            
            $eco_category = get_post_meta($post->ID, 'eco_category', true);
            if ($eco_category) {
                echo '<meta property="environmental:category" content="' . esc_attr($eco_category) . '">' . "\n";
            }
        }
        
        // Environmental keywords based on page type
        $environmental_keywords = $this->get_environmental_keywords();
        if (!empty($environmental_keywords)) {
            echo '<meta name="keywords" content="' . esc_attr(implode(', ', $environmental_keywords)) . '">' . "\n";
        }
        
        // Environmental site verification
        if (!empty($env_seo_options['google_site_verification'])) {
            echo '<meta name="google-site-verification" content="' . esc_attr($env_seo_options['google_site_verification']) . '">' . "\n";
        }
    }
    
    public function add_environmental_schema() {
        global $post;
        
        if (is_front_page()) {
            $this->output_organization_schema();
        } elseif (is_singular('product')) {
            $this->output_eco_product_schema($post);
        } elseif (is_singular('event')) {
            $this->output_environmental_event_schema($post);
        } elseif (is_singular('petition')) {
            $this->output_petition_schema($post);
        } elseif (is_singular('post')) {
            $this->output_environmental_article_schema($post);
        }
    }
    
    private function output_organization_schema() {
        $env_seo_options = get_option('environmental_seo_options', array());
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'description' => 'Environmental Platform - Community for sustainable living and eco-friendly initiatives',
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => !empty($env_seo_options['logo_url']) ? $env_seo_options['logo_url'] : get_site_icon_url()
            ),
            'contactPoint' => array(
                '@type' => 'ContactPoint',
                'contactType' => 'Environmental Support',
                'email' => !empty($env_seo_options['contact_email']) ? $env_seo_options['contact_email'] : get_option('admin_email')
            ),
            'areaServed' => 'Worldwide',
            'knowsAbout' => array(
                'Environmental Protection',
                'Sustainability',
                'Climate Change',
                'Renewable Energy',
                'Eco-friendly Products',
                'Green Living'
            )
        );
        
        if (!empty($env_seo_options['social_facebook'])) {
            $schema['sameAs'][] = $env_seo_options['social_facebook'];
        }
        if (!empty($env_seo_options['social_twitter'])) {
            $schema['sameAs'][] = $env_seo_options['social_twitter'];
        }
        if (!empty($env_seo_options['social_instagram'])) {
            $schema['sameAs'][] = $env_seo_options['social_instagram'];
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    private function output_eco_product_schema($post) {
        $sustainability_score = get_post_meta($post->ID, 'sustainability_score', true);
        $carbon_footprint = get_post_meta($post->ID, 'carbon_footprint', true);
        $eco_certifications = get_post_meta($post->ID, 'eco_certifications', true);
        $price = get_post_meta($post->ID, '_price', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => get_the_excerpt($post) ?: wp_trim_words(get_the_content($post), 30),
            'image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'url' => get_permalink($post->ID),
            'category' => 'Eco-friendly Products',
            'brand' => array(
                '@type' => 'Brand',
                'name' => 'Environmental Platform'
            )
        );
        
        if ($sustainability_score) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Sustainability Score',
                'value' => $sustainability_score,
                'minValue' => 1,
                'maxValue' => 10
            );
        }
        
        if ($carbon_footprint) {
            $schema['additionalProperty'][] = array(
                '@type' => 'PropertyValue',
                'name' => 'Carbon Footprint',
                'value' => $carbon_footprint,
                'unitText' => 'kg CO2'
            );
        }
        
        if ($eco_certifications) {
            $schema['certification'] = $eco_certifications;
        }
        
        if ($price) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    private function output_environmental_event_schema($post) {
        $event_date = get_post_meta($post->ID, 'event_date', true);
        $event_location = get_post_meta($post->ID, 'event_location', true);
        $event_organizer = get_post_meta($post->ID, 'event_organizer', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => get_the_title($post->ID),
            'description' => get_the_excerpt($post) ?: wp_trim_words(get_the_content($post), 50),
            'image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'url' => get_permalink($post->ID),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'about' => array(
                '@type' => 'Thing',
                'name' => 'Environmental Conservation'
            )
        );
        
        if ($event_date) {
            $schema['startDate'] = date('c', strtotime($event_date));
        }
        
        if ($event_location) {
            $schema['location'] = array(
                '@type' => 'Place',
                'name' => $event_location
            );
        }
        
        if ($event_organizer) {
            $schema['organizer'] = array(
                '@type' => 'Organization',
                'name' => $event_organizer
            );
        } else {
            $schema['organizer'] = array(
                '@type' => 'Organization',
                'name' => 'Environmental Platform'
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    private function output_petition_schema($post) {
        $petition_goal = get_post_meta($post->ID, 'petition_goal', true);
        $signatures_count = get_post_meta($post->ID, 'signatures_count', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Action',
            'name' => get_the_title($post->ID),
            'description' => get_the_excerpt($post) ?: wp_trim_words(get_the_content($post), 50),
            'url' => get_permalink($post->ID),
            'actionStatus' => 'https://schema.org/ActiveActionStatus',
            'object' => array(
                '@type' => 'Thing',
                'name' => 'Environmental Protection'
            )
        );
        
        if ($petition_goal && $signatures_count) {
            $schema['result'] = array(
                '@type' => 'Result',
                'name' => 'Petition Signatures',
                'value' => $signatures_count,
                'maxValue' => $petition_goal
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    private function output_environmental_article_schema($post) {
        $author = get_the_author_meta('display_name', $post->post_author);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post->ID),
            'description' => get_the_excerpt($post) ?: wp_trim_words(get_the_content($post), 30),
            'image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => array(
                '@type' => 'Person',
                'name' => $author
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => 'Environmental Platform',
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID)
            ),
            'about' => array(
                '@type' => 'Thing',
                'name' => 'Environmental Education'
            )
        );
        
        // Add environmental topics
        $tags = get_the_tags($post->ID);
        if ($tags) {
            $schema['keywords'] = array();
            foreach ($tags as $tag) {
                $schema['keywords'][] = $tag->name;
            }
            $schema['keywords'] = implode(', ', $schema['keywords']);
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
    
    public function add_breadcrumb_schema() {
        if (is_singular() && !is_front_page()) {
            global $post;
            
            $breadcrumbs = array();
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => home_url()
            );
            
            $position = 2;
            
            // Add category or post type
            if (is_singular('product')) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => 'Eco Products',
                    'item' => home_url('/products/')
                );
            } elseif (is_singular('event')) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => 'Environmental Events',
                    'item' => home_url('/events/')
                );
            } elseif (is_singular('petition')) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => 'Environmental Petitions',
                    'item' => home_url('/petitions/')
                );
            }
            
            // Add current page
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title($post->ID),
                'item' => get_permalink($post->ID)
            );
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs
            );
            
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }
    }
    
    private function get_environmental_keywords() {
        global $post;
        
        $base_keywords = array(
            'environment', 'sustainability', 'eco-friendly', 'green living',
            'climate change', 'renewable energy', 'conservation', 'environmental protection'
        );
        
        if (is_singular()) {
            $post_keywords = array();
            
            // Add keywords based on post type
            if (is_singular('product')) {
                $post_keywords = array_merge($post_keywords, array(
                    'eco products', 'sustainable shopping', 'green marketplace', 'environmentally friendly'
                ));
            } elseif (is_singular('event')) {
                $post_keywords = array_merge($post_keywords, array(
                    'environmental events', 'climate action', 'sustainability workshops', 'green events'
                ));
            } elseif (is_singular('petition')) {
                $post_keywords = array_merge($post_keywords, array(
                    'environmental petition', 'climate activism', 'environmental advocacy', 'green policy'
                ));
            } elseif (is_singular('exchange')) {
                $post_keywords = array_merge($post_keywords, array(
                    'item exchange', 'sustainable sharing', 'circular economy', 'green marketplace'
                ));
            }
            
            // Add custom keywords from post meta
            $custom_keywords = get_post_meta($post->ID, 'environmental_keywords', true);
            if ($custom_keywords) {
                $custom_keywords_array = array_map('trim', explode(',', $custom_keywords));
                $post_keywords = array_merge($post_keywords, $custom_keywords_array);
            }
            
            // Add category-based keywords
            $categories = get_the_category($post->ID);
            if ($categories) {
                foreach ($categories as $category) {
                    $post_keywords[] = $category->name;
                }
            }
            
            // Add tag-based keywords
            $tags = get_the_tags($post->ID);
            if ($tags) {
                foreach ($tags as $tag) {
                    $post_keywords[] = $tag->name;
                }
            }
            
            return array_unique(array_merge($base_keywords, $post_keywords));
        }
        
        return $base_keywords;
    }
    
    public function setup_environmental_sitemap() {
        // Add custom sitemap for environmental content
        add_action('wp_head', function() {
            if (is_front_page()) {
                echo '<link rel="sitemap" type="application/xml" title="Environmental Sitemap" href="' . home_url('/environmental-sitemap.xml') . '">' . "\n";
            }
        });
        
        // Handle sitemap request
        add_action('template_redirect', function() {
            if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/environmental-sitemap.xml') {
                $this->generate_environmental_sitemap();
                exit;
            }
        });
    }
    
    private function generate_environmental_sitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add environmental post types
        $post_types = array('product', 'event', 'petition', 'exchange');
        
        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            foreach ($posts as $post) {
                echo '<url>' . "\n";
                echo '<loc>' . get_permalink($post->ID) . '</loc>' . "\n";
                echo '<lastmod>' . get_the_modified_date('c', $post->ID) . '</lastmod>' . "\n";
                echo '<changefreq>weekly</changefreq>' . "\n";
                echo '<priority>0.8</priority>' . "\n";
                echo '</url>' . "\n";
            }
        }
        
        echo '</urlset>';
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Environmental SEO Settings',
            'Environmental SEO',
            'manage_options',
            'environmental-seo',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $options = array(
                'google_site_verification' => sanitize_text_field($_POST['google_site_verification']),
                'logo_url' => esc_url_raw($_POST['logo_url']),
                'contact_email' => sanitize_email($_POST['contact_email']),
                'social_facebook' => esc_url_raw($_POST['social_facebook']),
                'social_twitter' => esc_url_raw($_POST['social_twitter']),
                'social_instagram' => esc_url_raw($_POST['social_instagram']),
                'enable_schema' => isset($_POST['enable_schema']),
                'enable_breadcrumbs' => isset($_POST['enable_breadcrumbs'])
            );
            
            update_option('environmental_seo_options', $options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $options = get_option('environmental_seo_options', array());
        ?>
        <div class="wrap">
            <h1>Environmental Platform SEO Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Google Site Verification</th>
                        <td><input type="text" name="google_site_verification" value="<?php echo esc_attr($options['google_site_verification'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Logo URL</th>
                        <td><input type="url" name="logo_url" value="<?php echo esc_attr($options['logo_url'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Contact Email</th>
                        <td><input type="email" name="contact_email" value="<?php echo esc_attr($options['contact_email'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Facebook URL</th>
                        <td><input type="url" name="social_facebook" value="<?php echo esc_attr($options['social_facebook'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Twitter URL</th>
                        <td><input type="url" name="social_twitter" value="<?php echo esc_attr($options['social_twitter'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Instagram URL</th>
                        <td><input type="url" name="social_instagram" value="<?php echo esc_attr($options['social_instagram'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Schema Markup</th>
                        <td><input type="checkbox" name="enable_schema" <?php checked($options['enable_schema'] ?? true); ?> /> Enable environmental schema markup</td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Breadcrumbs</th>
                        <td><input type="checkbox" name="enable_breadcrumbs" <?php checked($options['enable_breadcrumbs'] ?? true); ?> /> Enable breadcrumb navigation</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function add_seo_meta_boxes() {
        $post_types = array('post', 'page', 'product', 'event', 'petition', 'exchange');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'environmental-seo-meta',
                'Environmental SEO Settings',
                array($this, 'seo_meta_box_callback'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    public function seo_meta_box_callback($post) {
        wp_nonce_field('environmental_seo_meta_box', 'environmental_seo_meta_box_nonce');
        
        $sustainability_score = get_post_meta($post->ID, 'sustainability_score', true);
        $carbon_impact = get_post_meta($post->ID, 'carbon_impact', true);
        $eco_category = get_post_meta($post->ID, 'eco_category', true);
        $environmental_keywords = get_post_meta($post->ID, 'environmental_keywords', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">Sustainability Score (1-10)</th>
                <td><input type="number" name="sustainability_score" value="<?php echo esc_attr($sustainability_score); ?>" min="1" max="10" /></td>
            </tr>
            <tr>
                <th scope="row">Carbon Impact (kg CO2)</th>
                <td><input type="text" name="carbon_impact" value="<?php echo esc_attr($carbon_impact); ?>" /></td>
            </tr>
            <tr>
                <th scope="row">Eco Category</th>
                <td>
                    <select name="eco_category">
                        <option value="">Select Category</option>
                        <option value="renewable-energy" <?php selected($eco_category, 'renewable-energy'); ?>>Renewable Energy</option>
                        <option value="sustainable-products" <?php selected($eco_category, 'sustainable-products'); ?>>Sustainable Products</option>
                        <option value="waste-reduction" <?php selected($eco_category, 'waste-reduction'); ?>>Waste Reduction</option>
                        <option value="climate-action" <?php selected($eco_category, 'climate-action'); ?>>Climate Action</option>
                        <option value="conservation" <?php selected($eco_category, 'conservation'); ?>>Conservation</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Environmental Keywords</th>
                <td><textarea name="environmental_keywords" rows="3" cols="50" placeholder="Enter keywords separated by commas"><?php echo esc_textarea($environmental_keywords); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    public function save_seo_data($post_id) {
        if (!isset($_POST['environmental_seo_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['environmental_seo_meta_box_nonce'], 'environmental_seo_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('sustainability_score', 'carbon_impact', 'eco_category', 'environmental_keywords');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    public function optimize_environmental_description($description) {
        global $post;
        
        if (is_singular()) {
            $sustainability_score = get_post_meta($post->ID, 'sustainability_score', true);
            if ($sustainability_score) {
                $description .= ' Sustainability Score: ' . $sustainability_score . '/10.';
            }
            
            $eco_category = get_post_meta($post->ID, 'eco_category', true);
            if ($eco_category) {
                $description .= ' Category: ' . ucwords(str_replace('-', ' ', $eco_category)) . '.';
            }
        }
        
        return $description;
    }
    
    public function optimize_environmental_title($title) {
        global $post;
        
        if (is_singular('product')) {
            $sustainability_score = get_post_meta($post->ID, 'sustainability_score', true);
            if ($sustainability_score >= 8) {
                $title = '🌱 ' . $title . ' - Eco-Friendly';
            }
        } elseif (is_singular('event')) {
            $title = '🌍 ' . $title . ' - Environmental Event';
        } elseif (is_singular('petition')) {
            $title = '✊ ' . $title . ' - Environmental Petition';
        }
        
        return $title;
    }
    
    private function setup_default_options() {
        $default_options = array(
            'google_site_verification' => '',
            'logo_url' => '',
            'contact_email' => get_option('admin_email'),
            'social_facebook' => '',
            'social_twitter' => '',
            'social_instagram' => '',
            'enable_schema' => true,
            'enable_breadcrumbs' => true
        );
        
        if (!get_option('environmental_seo_options')) {
            update_option('environmental_seo_options', $default_options);
        }
    }
    
    private function register_environmental_post_types_for_seo() {
        // Ensure environmental post types are included in SEO
        add_filter('wpseo_sitemap_exclude_post_type', function($excluded, $post_type) {
            $environmental_post_types = array('product', 'event', 'petition', 'exchange');
            if (in_array($post_type, $environmental_post_types)) {
                return false; // Don't exclude environmental post types
            }
            return $excluded;
        }, 10, 2);
    }
}

// Initialize the plugin
new EnvironmentalPlatformSEO();

// Activation hook
register_activation_hook(__FILE__, function() {
    // Set default SEO options
    $default_options = array(
        'google_site_verification' => '',
        'logo_url' => '',
        'contact_email' => get_option('admin_email'),
        'social_facebook' => '',
        'social_twitter' => '',
        'social_instagram' => '',
        'enable_schema' => true,
        'enable_breadcrumbs' => true
    );
    
    update_option('environmental_seo_options', $default_options);
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up if needed
    flush_rewrite_rules();
});

?>
