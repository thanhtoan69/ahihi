<?php
/**
 * The header for Environmental Platform theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Environmental Platform Meta -->
    <meta name="theme-color" content="#2E7D4A">
    <meta name="description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
    
    <!-- Preload Critical Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php _e('Skip to content', 'environmental-platform'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-container">
                
                <!-- Site Logo and Title -->
                <div class="site-logo">
                    <?php if (has_custom_logo()) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <div class="logo-placeholder">
                            <span style="font-size: 2rem;">🌱</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="site-branding">
                        <?php if (is_front_page() && is_home()) : ?>
                            <h1 class="site-title">
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                    <?php bloginfo('name'); ?>
                                </a>
                            </h1>
                        <?php else : ?>
                            <p class="site-title">
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                    <?php bloginfo('name'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php
                        $description = get_bloginfo('description', 'display');
                        if ($description || is_customize_preview()) :
                        ?>
                            <p class="site-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Navigation -->
                <nav id="site-navigation" class="main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'menu_class'     => 'nav-menu',
                        'container'      => false,
                        'fallback_cb'    => 'environmental_platform_fallback_menu',
                    ));
                    ?>
                    
                    <!-- User Account Section -->
                    <div class="user-account-section">
                        <?php if (is_user_logged_in()) : ?>
                            <?php
                            $current_user = wp_get_current_user();
                            global $wpdb;
                            
                            // Get user data from custom database
                            $user_data = $wpdb->get_row($wpdb->prepare(
                                "SELECT green_points, level, total_environmental_score FROM users WHERE email = %s",
                                $current_user->user_email
                            ));
                            ?>
                            
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo get_avatar($current_user->ID, 32); ?>
                                </div>
                                <div class="user-details">
                                    <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                                    <?php if ($user_data) : ?>
                                        <div class="user-stats">
                                            <span class="green-points"><?php echo number_format($user_data->green_points); ?> 🌱</span>
                                            <span class="user-level-badge">Level <?php echo $user_data->level; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-menu">
                                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="btn btn-outline btn-sm">
                                        <?php _e('Profile', 'environmental-platform'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-outline btn-sm">
                                        <?php _e('Logout', 'environmental-platform'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="auth-links">
                                <a href="<?php echo esc_url(wp_login_url()); ?>" class="btn btn-outline">
                                    <?php _e('Login', 'environmental-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary">
                                    <?php _e('Join Us', 'environmental-platform'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                        <span class="hamburger">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                        <span class="screen-reader-text"><?php _e('Main Menu', 'environmental-platform'); ?></span>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Environmental Alert Banner (if active) -->
    <?php
    $environmental_alert = get_theme_mod('environmental_alert_message');
    $alert_active = get_theme_mod('environmental_alert_active', false);
    
    if ($alert_active && $environmental_alert) :
    ?>
        <div class="environmental-alert-banner">
            <div class="container">
                <div class="alert-content">
                    <span class="alert-icon">🚨</span>
                    <span class="alert-message"><?php echo esc_html($environmental_alert); ?></span>
                    <button class="alert-close" aria-label="<?php _e('Close alert', 'environmental-platform'); ?>">×</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="content" class="site-content">
