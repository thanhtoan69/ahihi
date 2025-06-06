<?php
/**
 * Environmental Platform theme functions and definitions
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme version
define('ENVIRONMENTAL_PLATFORM_VERSION', '1.0.0');

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function environmental_platform_setup() {
    // Make theme available for translation
    load_theme_textdomain('environmental-platform', get_template_directory() . '/languages');

    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');

    // Set default thumbnail sizes
    set_post_thumbnail_size(1200, 675, true);
    add_image_size('environmental-hero', 1920, 800, true);
    add_image_size('environmental-card', 600, 400, true);
    add_image_size('environmental-thumb', 300, 200, true);

    // Switch default core markup for search form, comment form, and comments to output valid HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Set up the WordPress core custom background feature
    add_theme_support('custom-background', apply_filters('environmental_platform_custom_background_args', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    )));

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for core custom logo
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'environmental-platform'),
        'footer'  => esc_html__('Footer Menu', 'environmental-platform'),
        'environmental' => esc_html__('Environmental Actions Menu', 'environmental-platform'),
        'quick-links' => esc_html__('Quick Links Menu', 'environmental-platform'),
    ));

    // Add support for wide alignment
    add_theme_support('align-wide');

    // Add support for editor color palette
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => esc_html__('Primary Green', 'environmental-platform'),
            'slug'  => 'primary-green',
            'color' => '#2E7D4A',
        ),
        array(
            'name'  => esc_html__('Secondary Green', 'environmental-platform'),
            'slug'  => 'secondary-green',
            'color' => '#4CAF50',
        ),
        array(
            'name'  => esc_html__('Accent Green', 'environmental-platform'),
            'slug'  => 'accent-green',
            'color' => '#8BC34A',
        ),
        array(
            'name'  => esc_html__('Earth Brown', 'environmental-platform'),
            'slug'  => 'earth-brown',
            'color' => '#8D6E63',
        ),
        array(
            'name'  => esc_html__('Sky Blue', 'environmental-platform'),
            'slug'  => 'sky-blue',
            'color' => '#2196F3',
        ),
        array(
            'name'  => esc_html__('Forest Dark', 'environmental-platform'),
            'slug'  => 'forest-dark',
            'color' => '#263238',
        ),
    ));

    // Add support for responsive embedded content
    add_theme_support('responsive-embeds');
}
add_action('after_setup_theme', 'environmental_platform_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function environmental_platform_content_width() {
    $GLOBALS['content_width'] = apply_filters('environmental_platform_content_width', 1200);
}
add_action('after_setup_theme', 'environmental_platform_content_width', 0);

/**
 * Enqueue scripts and styles.
 */
function environmental_platform_scripts() {
    // Theme stylesheet
    wp_enqueue_style('environmental-platform-style', get_stylesheet_uri(), array(), ENVIRONMENTAL_PLATFORM_VERSION);

    // Component styles
    wp_enqueue_style('environmental-platform-components', get_template_directory_uri() . '/assets/css/components.css', array('environmental-platform-style'), ENVIRONMENTAL_PLATFORM_VERSION);

    // Theme JavaScript
    wp_enqueue_script('environmental-platform-script', get_template_directory_uri() . '/assets/js/theme.js', array('jquery'), ENVIRONMENTAL_PLATFORM_VERSION, true);

    // Admin styles and scripts (only on admin pages)
    if (is_admin()) {
        wp_enqueue_script('environmental-platform-admin', get_template_directory_uri() . '/js/admin-options.js', array('jquery', 'media-upload', 'thickbox'), ENVIRONMENTAL_PLATFORM_VERSION, true);
        wp_enqueue_style('thickbox');
    }

    // Customizer preview script
    if (is_customize_preview()) {
        wp_enqueue_script('environmental-platform-customizer', get_template_directory_uri() . '/js/customizer.js', array('jquery', 'customize-preview'), ENVIRONMENTAL_PLATFORM_VERSION, true);
    }

    // Localize script for AJAX and theme data
    wp_localize_script('environmental-platform-script', 'environmentalPlatform', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('environmental_platform_nonce'),
        'themeUrl' => get_template_directory_uri(),
        'isUserLoggedIn' => is_user_logged_in(),
        'userId' => get_current_user_id(),
        'strings' => array(
            'loading' => esc_html__('Loading...', 'environmental-platform'),
            'error' => esc_html__('An error occurred. Please try again.', 'environmental-platform'),
            'success' => esc_html__('Success!', 'environmental-platform'),
            'searchPlaceholder' => esc_html__('Search environmental topics...', 'environmental-platform'),
            'noResults' => esc_html__('No results found. Try different keywords.', 'environmental-platform'),
            'shareTitle' => esc_html__('Share this content', 'environmental-platform'),
            'copiedToClipboard' => esc_html__('Link copied to clipboard!', 'environmental-platform'),
        ),
    ));

    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    // Dark mode support
    if (get_theme_mod('enable_dark_mode', true)) {
        wp_add_inline_style('environmental-platform-style', '
            @media (prefers-color-scheme: dark) {
                :root {
                    --primary-color: #4CAF50;
                    --secondary-color: #81C784;
                    --background-color: #1a1a1a;
                    --text-color: #ffffff;
                    --card-background: #2d2d2d;
                    --border-color: #404040;
                }
            }
            
            body.dark-mode {
                --primary-color: #4CAF50;
                --secondary-color: #81C784;
                --background-color: #1a1a1a;
                --text-color: #ffffff;
                --card-background: #2d2d2d;
                --border-color: #404040;
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'environmental_platform_scripts');

/**
 * Register widget areas.
 */
function environmental_platform_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'environmental-platform'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer Widget Area 1', 'environmental-platform'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Add widgets here to appear in the first footer area.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer Widget Area 2', 'environmental-platform'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Add widgets here to appear in the second footer area.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Environmental Dashboard', 'environmental-platform'),
        'id'            => 'environmental-dashboard',
        'description'   => esc_html__('Add environmental tracking widgets here.', 'environmental-platform'),
        'before_widget' => '<div id="%1$s" class="environmental-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="environmental-widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'environmental_platform_widgets_init');

/**
 * Fallback menu for primary navigation
 */
function environmental_platform_fallback_menu() {
    echo '<ul class="nav-menu">';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/about')) . '">' . esc_html__('About', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/blog')) . '">' . esc_html__('Blog', 'environmental-platform') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/contact')) . '">' . esc_html__('Contact', 'environmental-platform') . '</a></li>';
    echo '</ul>';
}

/**
 * Get social media icon
 */
function environmental_platform_get_social_icon($platform) {
    $icons = array(
        'facebook' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'twitter' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
        'instagram' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        'youtube' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
    );
    
    return isset($icons[$platform]) ? $icons[$platform] : '';
}

/**
 * Customizer additions.
 */
function environmental_platform_customize_register($wp_customize) {
    // Environmental Settings Section
    $wp_customize->add_section('environmental_settings', array(
        'title'    => esc_html__('Environmental Platform Settings', 'environmental-platform'),
        'priority' => 120,
    ));

    // Environmental Alert
    $wp_customize->add_setting('environmental_alert_active', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
    ));

    $wp_customize->add_control('environmental_alert_active', array(
        'label'   => esc_html__('Show Environmental Alert Banner', 'environmental-platform'),
        'section' => 'environmental_settings',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_setting('environmental_alert_message', array(
        'default'           => esc_html__('Join our mission to save the planet! 🌍', 'environmental-platform'),
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('environmental_alert_message', array(
        'label'   => esc_html__('Environmental Alert Message', 'environmental-platform'),
        'section' => 'environmental_settings',
        'type'    => 'text',
    ));

    // Social Media Settings
    $wp_customize->add_section('social_media_settings', array(
        'title'    => esc_html__('Social Media Links', 'environmental-platform'),
        'priority' => 130,
    ));

    $social_platforms = array(
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
    );

    foreach ($social_platforms as $platform => $label) {
        $wp_customize->add_setting($platform . '_url', array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ));

        $wp_customize->add_control($platform . '_url', array(
            'label'   => sprintf(esc_html__('%s URL', 'environmental-platform'), $label),
            'section' => 'social_media_settings',
            'type'    => 'url',
        ));
    }
}
add_action('customize_register', 'environmental_platform_customize_register');

/**
 * Custom post excerpt length
 */
function environmental_platform_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'environmental_platform_excerpt_length');

/**
 * Custom excerpt more text
 */
function environmental_platform_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'environmental_platform_excerpt_more');

/**
 * Add custom body classes
 */
function environmental_platform_body_classes($classes) {
    // Add class for logged-in users
    if (is_user_logged_in()) {
        $classes[] = 'user-logged-in';
    }

    // Add class for environmental platform pages
    if (is_page_template('page-environmental-dashboard.php')) {
        $classes[] = 'environmental-dashboard-page';
    }

    // Add dark mode class
    if (environmental_platform_get_dark_mode_preference()) {
        $classes[] = 'dark-mode';
    }

    // Add environmental category classes
    if (is_single()) {
        $environmental_category = get_post_meta(get_the_ID(), '_environmental_category', true);
        if ($environmental_category) {
            $classes[] = 'environmental-category-' . $environmental_category;
        }
    }

    // Add device-specific classes
    if (wp_is_mobile()) {
        $classes[] = 'mobile-device';
    }

    // Add template-specific classes
    if (is_archive()) {
        $classes[] = 'archive-layout';
    }

    if (is_search()) {
        $classes[] = 'search-results-page';
    }

    if (is_404()) {
        $classes[] = 'error-404-page';
    }

    return $classes;
}
add_filter('body_class', 'environmental_platform_body_classes');

/**
 * Environmental Platform Custom Functions
 */

/**
 * Get user's environmental statistics
 */
function environmental_platform_get_user_stats($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    global $wpdb;
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }

    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            green_points,
            level,
            total_environmental_score,
            carbon_footprint_kg,
            waste_reports_count,
            quiz_completions_count
        FROM users 
        WHERE email = %s",
        $user->user_email
    ));

    return $stats;
}

/**
 * Get environmental progress for a user
 */
function environmental_platform_get_user_progress($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    global $wpdb;
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }

    // Get recent activities
    $activities = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            activity_type,
            carbon_impact_kg,
            environmental_score,
            created_at
        FROM user_activities_comprehensive 
        WHERE user_id = (SELECT user_id FROM users WHERE email = %s)
        ORDER BY created_at DESC 
        LIMIT 10",
        $user->user_email
    ));

    return $activities;
}

/**
 * Get top environmental contributors
 */
function environmental_platform_get_top_contributors($limit = 10) {
    global $wpdb;
    
    $contributors = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            u.username,
            u.green_points,
            u.level,
            u.total_environmental_score,
            COUNT(uac.activity_id) as total_activities
        FROM users u
        LEFT JOIN user_activities_comprehensive uac ON u.user_id = uac.user_id
        WHERE u.status = 'active'
        GROUP BY u.user_id
        ORDER BY u.total_environmental_score DESC, u.green_points DESC
        LIMIT %d",
        $limit
    ));

    return $contributors;
}

/**
 * Dark mode toggle functionality
 */
function environmental_platform_toggle_dark_mode() {
    check_ajax_referer('environmental_platform_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $dark_mode = sanitize_text_field($_POST['dark_mode']);
    
    if ($user_id) {
        update_user_meta($user_id, 'environmental_dark_mode', $dark_mode === 'true');
    } else {
        // For non-logged-in users, we'll use localStorage on the frontend
        setcookie('environmental_dark_mode', $dark_mode, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
    }
    
    wp_send_json_success(array('dark_mode' => $dark_mode));
}
add_action('wp_ajax_toggle_dark_mode', 'environmental_platform_toggle_dark_mode');
add_action('wp_ajax_nopriv_toggle_dark_mode', 'environmental_platform_toggle_dark_mode');

/**
 * Get user's dark mode preference
 */
function environmental_platform_get_dark_mode_preference() {
    $user_id = get_current_user_id();
    
    if ($user_id) {
        return get_user_meta($user_id, 'environmental_dark_mode', true);
    } else {
        return isset($_COOKIE['environmental_dark_mode']) ? $_COOKIE['environmental_dark_mode'] === 'true' : false;
    }
}

/**
 * Environmental content search functionality
 */
function environmental_platform_search_content() {
    check_ajax_referer('environmental_platform_nonce', 'nonce');
    
    $search_term = sanitize_text_field($_POST['search_term']);
    $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
    $category = sanitize_text_field($_POST['category'] ?? '');
    
    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $search_term,
        'meta_query' => array()
    );
    
    if (!empty($category)) {
        $args['meta_query'][] = array(
            'key' => '_environmental_category',
            'value' => $category,
            'compare' => '='
        );
    }
    
    $query = new WP_Query($args);
    $results = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'excerpt' => get_the_excerpt(),
                'permalink' => get_permalink(),
                'featured_image' => get_the_post_thumbnail_url(get_the_ID(), 'environmental-thumb'),
                'environmental_score' => get_post_meta(get_the_ID(), '_environmental_score', true),
                'carbon_impact' => get_post_meta(get_the_ID(), '_carbon_impact', true),
                'date' => get_the_date(),
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_search_environmental_content', 'environmental_platform_search_content');
add_action('wp_ajax_nopriv_search_environmental_content', 'environmental_platform_search_content');

/**
 * Save user environmental preferences
 */
function environmental_platform_save_user_preferences() {
    check_ajax_referer('environmental_platform_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $preferences = array(
        'newsletter_frequency' => sanitize_text_field($_POST['newsletter_frequency'] ?? 'weekly'),
        'notification_types' => array_map('sanitize_text_field', $_POST['notification_types'] ?? array()),
        'environmental_goals' => array_map('sanitize_text_field', $_POST['environmental_goals'] ?? array()),
        'privacy_level' => sanitize_text_field($_POST['privacy_level'] ?? 'public'),
    );
    
    update_user_meta($user_id, 'environmental_preferences', $preferences);
    
    wp_send_json_success($preferences);
}
add_action('wp_ajax_save_environmental_preferences', 'environmental_platform_save_user_preferences');

/**
 * AJAX handler for environmental data
 */
function environmental_platform_ajax_get_environmental_data() {
    check_ajax_referer('environmental_platform_nonce', 'nonce');

    $type = sanitize_text_field($_POST['type']);
    $response = array();

    switch ($type) {
        case 'user_stats':
            $response = environmental_platform_get_user_stats();
            break;
        case 'user_progress':
            $response = environmental_platform_get_user_progress();
            break;
        case 'top_contributors':
            $response = environmental_platform_get_top_contributors();
            break;
        default:
            wp_die('Invalid request type');
    }

    wp_send_json_success($response);
}
add_action('wp_ajax_get_environmental_data', 'environmental_platform_ajax_get_environmental_data');
add_action('wp_ajax_nopriv_get_environmental_data', 'environmental_platform_ajax_get_environmental_data');

/**
 * Add custom meta fields for environmental content
 */
function environmental_platform_add_meta_boxes() {
    add_meta_box(
        'environmental-meta',
        esc_html__('Environmental Information', 'environmental-platform'),
        'environmental_platform_meta_box_callback',
        array('post', 'page'),
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'environmental_platform_add_meta_boxes');

/**
 * Meta box callback function
 */
function environmental_platform_meta_box_callback($post) {
    wp_nonce_field('environmental_platform_meta_box', 'environmental_platform_meta_box_nonce');
    
    $environmental_score = get_post_meta($post->ID, '_environmental_score', true);
    $carbon_impact = get_post_meta($post->ID, '_carbon_impact', true);
    $environmental_category = get_post_meta($post->ID, '_environmental_category', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th><label for="environmental_score"><?php esc_html_e('Environmental Score', 'environmental-platform'); ?></label></th>
            <td><input type="number" id="environmental_score" name="environmental_score" value="<?php echo esc_attr($environmental_score); ?>" min="0" max="100" step="0.1" /></td>
        </tr>
        <tr>
            <th><label for="carbon_impact"><?php esc_html_e('Carbon Impact (kg)', 'environmental-platform'); ?></label></th>
            <td><input type="number" id="carbon_impact" name="carbon_impact" value="<?php echo esc_attr($carbon_impact); ?>" step="0.1" /></td>
        </tr>
        <tr>
            <th><label for="environmental_category"><?php esc_html_e('Environmental Category', 'environmental-platform'); ?></label></th>
            <td>
                <select id="environmental_category" name="environmental_category">
                    <option value=""><?php esc_html_e('Select Category', 'environmental-platform'); ?></option>
                    <option value="climate_change" <?php selected($environmental_category, 'climate_change'); ?>><?php esc_html_e('Climate Change', 'environmental-platform'); ?></option>
                    <option value="waste_reduction" <?php selected($environmental_category, 'waste_reduction'); ?>><?php esc_html_e('Waste Reduction', 'environmental-platform'); ?></option>
                    <option value="renewable_energy" <?php selected($environmental_category, 'renewable_energy'); ?>><?php esc_html_e('Renewable Energy', 'environmental-platform'); ?></option>
                    <option value="sustainable_living" <?php selected($environmental_category, 'sustainable_living'); ?>><?php esc_html_e('Sustainable Living', 'environmental-platform'); ?></option>
                    <option value="conservation" <?php selected($environmental_category, 'conservation'); ?>><?php esc_html_e('Conservation', 'environmental-platform'); ?></option>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save meta box data
 */
function environmental_platform_save_meta_box($post_id) {
    if (!isset($_POST['environmental_platform_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['environmental_platform_meta_box_nonce'], 'environmental_platform_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['environmental_score'])) {
        update_post_meta($post_id, '_environmental_score', sanitize_text_field($_POST['environmental_score']));
    }

    if (isset($_POST['carbon_impact'])) {
        update_post_meta($post_id, '_carbon_impact', sanitize_text_field($_POST['carbon_impact']));
    }

    if (isset($_POST['environmental_category'])) {
        update_post_meta($post_id, '_environmental_category', sanitize_text_field($_POST['environmental_category']));
    }
}
add_action('save_post', 'environmental_platform_save_meta_box');

/**
 * Performance optimizations
 */
function environmental_platform_optimize_performance() {
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Optimize emoji scripts
    if (!is_admin()) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }
}
add_action('init', 'environmental_platform_optimize_performance');

/**
 * Add preload hints for critical resources
 */
function environmental_platform_add_preload_hints() {
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/css/components.css" as="style">' . "\n";
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/js/theme.js" as="script">' . "\n";
    
    // Preload Google Fonts if used
    if (get_theme_mod('google_fonts_enabled', false)) {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }
}
add_action('wp_head', 'environmental_platform_add_preload_hints', 1);

/**
 * Custom login page styling
 */
function environmental_platform_custom_login_style() {
    ?>
    <style type="text/css">
        body.login {
            background: linear-gradient(135deg, #2E7D4A 0%, #4CAF50 100%);
        }
        
        .login h1 a {
            background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/logo-white.png');
            background-size: contain;
            background-repeat: no-repeat;
            width: 200px;
            height: 80px;
        }
        
        .login form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .login .button-primary {
            background: #2E7D4A;
            border-color: #2E7D4A;
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .login .button-primary:hover {
            background: #4CAF50;
            border-color: #4CAF50;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'environmental_platform_custom_login_style');

/**
 * Add environmental data to REST API
 */
function environmental_platform_add_rest_api_fields() {
    register_rest_field('post', 'environmental_data', array(
        'get_callback' => function($post) {
            return array(
                'environmental_score' => get_post_meta($post['id'], '_environmental_score', true),
                'carbon_impact' => get_post_meta($post['id'], '_carbon_impact', true),
                'environmental_category' => get_post_meta($post['id'], '_environmental_category', true),
                'reading_time' => environmental_platform_get_reading_time($post['id']),
            );
        },
        'schema' => array(
            'description' => 'Environmental data for the post',
            'type' => 'object',
        ),
    ));
}
add_action('rest_api_init', 'environmental_platform_add_rest_api_fields');

/**
 * Schema.org structured data for environmental content
 */
function environmental_platform_add_structured_data() {
    if (is_single()) {
        global $post;
        
        $environmental_score = get_post_meta($post->ID, '_environmental_score', true);
        $carbon_impact = get_post_meta($post->ID, '_carbon_impact', true);
        $environmental_category = get_post_meta($post->ID, '_environmental_category', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => get_the_excerpt(),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            ),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'url' => get_permalink(),
            'mainEntityOfPage' => get_permalink(),
        );
        
        if ($environmental_category) {
            $schema['about'] = array(
                '@type' => 'Thing',
                'name' => ucfirst(str_replace('_', ' ', $environmental_category))
            );
        }
        
        if ($environmental_score) {
            $schema['additionalProperty'] = array(
                '@type' => 'PropertyValue',
                'name' => 'Environmental Score',
                'value' => $environmental_score
            );
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }
}
add_action('wp_head', 'environmental_platform_add_structured_data');

/**
 * Add environmental platform dashboard to admin bar
 */
function environmental_platform_admin_bar_menu($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_menu(array(
        'id'    => 'environmental-platform',
        'title' => '🌱 ' . esc_html__('Environmental Platform', 'environmental-platform'),
        'href'  => admin_url('admin.php?page=environmental-platform'),
    ));
}
add_action('admin_bar_menu', 'environmental_platform_admin_bar_menu', 100);

/**
 * Load required files
 */
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/widgets.php';
require get_template_directory() . '/inc/class-environmental-walker-nav-menu.php';
require get_template_directory() . '/inc/theme-options.php';
