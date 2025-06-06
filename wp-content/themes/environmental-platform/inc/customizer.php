<?php
/**
 * Environmental Platform Theme Customizer
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function environmental_platform_customize_register($wp_customize) {
    
    // Enable live preview for existing settings
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport = 'postMessage';
    $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';

    // Custom Logo Section (if not already supported)
    if (isset($wp_customize->selective_refresh)) {
        $wp_customize->selective_refresh->add_partial('blogname', array(
            'selector'        => '.site-title',
            'render_callback' => function() {
                return get_bloginfo('name');
            },
        ));
        $wp_customize->selective_refresh->add_partial('blogdescription', array(
            'selector'        => '.site-description',
            'render_callback' => function() {
                return get_bloginfo('description');
            },
        ));
    }

    // Environmental Platform Settings Panel
    $wp_customize->add_panel('environmental_platform_panel', array(
        'title'       => __('Environmental Platform Settings', 'environmental-platform'),
        'description' => __('Customize your environmental platform appearance and features.', 'environmental-platform'),
        'priority'    => 30,
    ));

    // Hero Section Settings
    $wp_customize->add_section('hero_section', array(
        'title'    => __('Hero Section', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 10,
    ));

    // Hero Background Image
    $wp_customize->add_setting('hero_background_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'hero_background_image', array(
        'label'    => __('Hero Background Image', 'environmental-platform'),
        'section'  => 'hero_section',
        'settings' => 'hero_background_image',
    )));

    // Hero Title
    $wp_customize->add_setting('hero_title', array(
        'default'           => __('Join the Environmental Movement', 'environmental-platform'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('hero_title', array(
        'label'   => __('Hero Title', 'environmental-platform'),
        'section' => 'hero_section',
        'type'    => 'text',
    ));

    // Hero Subtitle
    $wp_customize->add_setting('hero_subtitle', array(
        'default'           => __('Track your environmental impact, learn sustainable practices, and connect with like-minded eco-warriors.', 'environmental-platform'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('hero_subtitle', array(
        'label'   => __('Hero Subtitle', 'environmental-platform'),
        'section' => 'hero_section',
        'type'    => 'textarea',
    ));

    // Hero Primary Button Text
    $wp_customize->add_setting('hero_button_primary_text', array(
        'default'           => __('Start Your Journey', 'environmental-platform'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('hero_button_primary_text', array(
        'label'   => __('Primary Button Text', 'environmental-platform'),
        'section' => 'hero_section',
        'type'    => 'text',
    ));

    // Hero Primary Button URL
    $wp_customize->add_setting('hero_button_primary_url', array(
        'default'           => '#',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('hero_button_primary_url', array(
        'label'   => __('Primary Button URL', 'environmental-platform'),
        'section' => 'hero_section',
        'type'    => 'url',
    ));

    // Environmental Features Section
    $wp_customize->add_section('environmental_features', array(
        'title'    => __('Environmental Features', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 20,
    ));

    // Show Environmental Stats
    $wp_customize->add_setting('show_environmental_stats', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('show_environmental_stats', array(
        'label'   => __('Show Environmental Statistics', 'environmental-platform'),
        'section' => 'environmental_features',
        'type'    => 'checkbox',
    ));

    // Enable Dark Mode Toggle
    $wp_customize->add_setting('enable_dark_mode', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('enable_dark_mode', array(
        'label'   => __('Enable Dark Mode Toggle', 'environmental-platform'),
        'section' => 'environmental_features',
        'type'    => 'checkbox',
    ));

    // Environmental Alert Banner
    $wp_customize->add_setting('environmental_alert_text', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('environmental_alert_text', array(
        'label'       => __('Environmental Alert Banner Text', 'environmental-platform'),
        'description' => __('Display an urgent environmental message at the top of the site.', 'environmental-platform'),
        'section'     => 'environmental_features',
        'type'        => 'text',
    ));

    // Color Scheme Section
    $wp_customize->add_section('environmental_colors', array(
        'title'    => __('Environmental Colors', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 30,
    ));

    // Primary Green Color
    $wp_customize->add_setting('primary_green_color', array(
        'default'           => '#2E7D32',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_green_color', array(
        'label'   => __('Primary Green Color', 'environmental-platform'),
        'section' => 'environmental_colors',
    )));

    // Secondary Green Color
    $wp_customize->add_setting('secondary_green_color', array(
        'default'           => '#4CAF50',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_green_color', array(
        'label'   => __('Secondary Green Color', 'environmental-platform'),
        'section' => 'environmental_colors',
    )));

    // Earth Brown Color
    $wp_customize->add_setting('earth_brown_color', array(
        'default'           => '#8D6E63',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'earth_brown_color', array(
        'label'   => __('Earth Brown Color', 'environmental-platform'),
        'section' => 'environmental_colors',
    )));

    // Social Media Section
    $wp_customize->add_section('social_media_settings', array(
        'title'    => __('Social Media', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 40,
    ));

    $social_platforms = array(
        'facebook'  => __('Facebook', 'environmental-platform'),
        'twitter'   => __('Twitter', 'environmental-platform'),
        'instagram' => __('Instagram', 'environmental-platform'),
        'youtube'   => __('YouTube', 'environmental-platform'),
        'linkedin'  => __('LinkedIn', 'environmental-platform'),
        'tiktok'    => __('TikTok', 'environmental-platform'),
    );

    foreach ($social_platforms as $platform => $label) {
        $wp_customize->add_setting($platform . '_url', array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($platform . '_url', array(
            'label'   => sprintf(__('%s URL', 'environmental-platform'), $label),
            'section' => 'social_media_settings',
            'type'    => 'url',
        ));
    }

    // Footer Section
    $wp_customize->add_section('footer_settings', array(
        'title'    => __('Footer Settings', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 50,
    ));

    // Footer About Text
    $wp_customize->add_setting('footer_about_text', array(
        'default'           => __('Join our community of eco-warriors working together to create a sustainable future. Track your environmental impact, learn from experts, and make a difference.', 'environmental-platform'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('footer_about_text', array(
        'label'   => __('Footer About Text', 'environmental-platform'),
        'section' => 'footer_settings',
        'type'    => 'textarea',
    ));

    // Show Environmental Impact in Footer
    $wp_customize->add_setting('show_footer_impact', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('show_footer_impact', array(
        'label'   => __('Show Daily Environmental Impact in Footer', 'environmental-platform'),
        'section' => 'footer_settings',
        'type'    => 'checkbox',
    ));

    // Newsletter Section in Footer
    $wp_customize->add_setting('show_newsletter_signup', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('show_newsletter_signup', array(
        'label'   => __('Show Newsletter Signup in Footer', 'environmental-platform'),
        'section' => 'footer_settings',
        'type'    => 'checkbox',
    ));

    // Typography Section
    $wp_customize->add_section('environmental_typography', array(
        'title'    => __('Typography', 'environmental-platform'),
        'panel'    => 'environmental_platform_panel',
        'priority' => 60,
    ));

    // Google Fonts Selection
    $wp_customize->add_setting('google_font_family', array(
        'default'           => 'Inter',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('google_font_family', array(
        'label'   => __('Google Font Family', 'environmental-platform'),
        'section' => 'environmental_typography',
        'type'    => 'select',
        'choices' => array(
            'Inter'        => 'Inter',
            'Roboto'       => 'Roboto',
            'Open Sans'    => 'Open Sans',
            'Lato'         => 'Lato',
            'Poppins'      => 'Poppins',
            'Montserrat'   => 'Montserrat',
            'Source Sans Pro' => 'Source Sans Pro',
        ),
    ));

    // Base Font Size
    $wp_customize->add_setting('base_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('base_font_size', array(
        'label'       => __('Base Font Size (px)', 'environmental-platform'),
        'section'     => 'environmental_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));
}
add_action('customize_register', 'environmental_platform_customize_register');

/**
 * Bind JS handlers to instantly live-preview changes.
 */
function environmental_platform_customize_preview_js() {
    wp_enqueue_script(
        'environmental-platform-customizer',
        get_template_directory_uri() . '/js/customizer.js',
        array('customize-preview'),
        ENVIRONMENTAL_PLATFORM_VERSION,
        true
    );
}
add_action('customize_preview_init', 'environmental_platform_customize_preview_js');

/**
 * Output custom CSS based on customizer settings
 */
function environmental_platform_customizer_css() {
    $primary_green = get_theme_mod('primary_green_color', '#2E7D32');
    $secondary_green = get_theme_mod('secondary_green_color', '#4CAF50');
    $earth_brown = get_theme_mod('earth_brown_color', '#8D6E63');
    $font_family = get_theme_mod('google_font_family', 'Inter');
    $font_size = get_theme_mod('base_font_size', '16');

    $css = "
    :root {
        --primary-green: {$primary_green};
        --secondary-green: {$secondary_green};
        --earth-brown: {$earth_brown};
        --font-family-primary: '{$font_family}', sans-serif;
        --font-size-base: {$font_size}px;
    }
    
    body {
        font-family: var(--font-family-primary);
        font-size: var(--font-size-base);
    }
    
    .btn-primary {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .btn-primary:hover {
        background-color: var(--secondary-green);
        border-color: var(--secondary-green);
    }
    
    .environmental-alert-banner {
        background-color: var(--earth-brown);
    }
    ";

    // Add Google Fonts
    if ($font_family && $font_family !== 'inherit') {
        wp_enqueue_style(
            'environmental-platform-google-fonts',
            'https://fonts.googleapis.com/css2?family=' . urlencode($font_family) . ':wght@300;400;500;600;700&display=swap',
            array(),
            ENVIRONMENTAL_PLATFORM_VERSION
        );
    }

    wp_add_inline_style('environmental-platform-style', $css);
}
add_action('wp_enqueue_scripts', 'environmental_platform_customizer_css');

/**
 * Get social media icon SVG
 */
function environmental_platform_get_social_icon($platform) {
    $icons = array(
        'facebook' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'twitter' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
        'instagram' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        'youtube' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'linkedin' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'tiktok' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
    );

    return isset($icons[$platform]) ? $icons[$platform] : '';
}
