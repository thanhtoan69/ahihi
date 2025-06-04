<?php
/**
 * User Environmental Preferences and Settings Template
 * 
 * Phase 31: User Management & Authentication - Settings Interface
 * Advanced user preferences for environmental data and platform settings
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's environmental preferences
$preferences = array(
    'environmental_interests' => get_user_meta($user_id, 'ep_environmental_interests', true) ?: array(),
    'notification_preferences' => get_user_meta($user_id, 'ep_notification_preferences', true) ?: array(),
    'privacy_settings' => get_user_meta($user_id, 'ep_privacy_settings', true) ?: array(),
    'dashboard_settings' => get_user_meta($user_id, 'ep_dashboard_settings', true) ?: array(),
    'location_settings' => array(
        'city' => get_user_meta($user_id, 'ep_location_city', true),
        'district' => get_user_meta($user_id, 'ep_location_district', true),
        'coordinates' => get_user_meta($user_id, 'ep_location_coordinates', true),
        'share_location' => get_user_meta($user_id, 'ep_share_location', true) ?: 'city_only'
    ),
    'activity_preferences' => get_user_meta($user_id, 'ep_activity_preferences', true) ?: array(),
    'content_preferences' => get_user_meta($user_id, 'ep_content_preferences', true) ?: array()
);

// Available environmental interests
$environmental_interests = array(
    'waste_reduction' => __('Waste Reduction', 'environmental-platform-core'),
    'recycling' => __('Recycling & Upcycling', 'environmental-platform-core'),
    'energy_saving' => __('Energy Conservation', 'environmental-platform-core'),
    'water_conservation' => __('Water Conservation', 'environmental-platform-core'),
    'sustainable_transport' => __('Sustainable Transportation', 'environmental-platform-core'),
    'organic_gardening' => __('Organic Gardening', 'environmental-platform-core'),
    'renewable_energy' => __('Renewable Energy', 'environmental-platform-core'),
    'eco_products' => __('Eco-friendly Products', 'environmental-platform-core'),
    'climate_action' => __('Climate Action', 'environmental-platform-core'),
    'biodiversity' => __('Biodiversity Conservation', 'environmental-platform-core'),
    'air_quality' => __('Air Quality', 'environmental-platform-core'),
    'sustainable_food' => __('Sustainable Food', 'environmental-platform-core'),
    'green_building' => __('Green Building', 'environmental-platform-core'),
    'environmental_education' => __('Environmental Education', 'environmental-platform-core'),
    'community_action' => __('Community Environmental Action', 'environmental-platform-core')
);

// Get user statistics for display
global $wpdb;
$user_stats = $wpdb->get_row($wpdb->prepare(
    "SELECT green_points, level, total_environmental_score, carbon_footprint_kg 
     FROM users WHERE email = %s",
    $current_user->user_email
));
?>

<div class="ep-user-preferences-container">
    <div class="ep-preferences-header">
        <h1><?php _e('Environmental Preferences & Settings', 'environmental-platform-core'); ?></h1>
        <p class="ep-preferences-description">
            <?php _e('Customize your environmental platform experience and privacy settings.', 'environmental-platform-core'); ?>
        </p>
    </div>

    <!-- Navigation Tabs -->
    <div class="ep-preferences-tabs">
        <nav class="ep-tab-nav">
            <a href="#interests" class="ep-tab-link active" data-tab="interests">
                <i class="fas fa-leaf"></i>
                <?php _e('Environmental Interests', 'environmental-platform-core'); ?>
            </a>
            <a href="#notifications" class="ep-tab-link" data-tab="notifications">
                <i class="fas fa-bell"></i>
                <?php _e('Notifications', 'environmental-platform-core'); ?>
            </a>
            <a href="#privacy" class="ep-tab-link" data-tab="privacy">
                <i class="fas fa-shield-alt"></i>
                <?php _e('Privacy', 'environmental-platform-core'); ?>
            </a>
            <a href="#location" class="ep-tab-link" data-tab="location">
                <i class="fas fa-map-marker-alt"></i>
                <?php _e('Location', 'environmental-platform-core'); ?>
            </a>
            <a href="#dashboard" class="ep-tab-link" data-tab="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <?php _e('Dashboard', 'environmental-platform-core'); ?>
            </a>
            <a href="#activity" class="ep-tab-link" data-tab="activity">
                <i class="fas fa-chart-line"></i>
                <?php _e('Activity Tracking', 'environmental-platform-core'); ?>
            </a>
            <a href="#content" class="ep-tab-link" data-tab="content">
                <i class="fas fa-newspaper"></i>
                <?php _e('Content Preferences', 'environmental-platform-core'); ?>
            </a>
        </nav>
    </div>

    <form id="ep-preferences-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
        <?php wp_nonce_field('ep_save_preferences', 'ep_preferences_nonce'); ?>
        <input type="hidden" name="action" value="ep_save_user_preferences">

        <!-- Environmental Interests Tab -->
        <div class="ep-tab-content active" id="tab-interests">
            <div class="ep-preferences-section">
                <h2><?php _e('Your Environmental Interests', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Select the environmental topics you\'re most interested in. This helps us personalize your content and recommendations.', 'environmental-platform-core'); ?>
                </p>

                <div class="ep-interests-grid">
                    <?php foreach ($environmental_interests as $key => $label): ?>
                        <label class="ep-interest-item">
                            <input type="checkbox" 
                                   name="environmental_interests[]" 
                                   value="<?php echo esc_attr($key); ?>"
                                   <?php checked(in_array($key, $preferences['environmental_interests'])); ?>>
                            <div class="ep-interest-card">
                                <div class="ep-interest-icon">
                                    <i class="<?php echo esc_attr($this->get_interest_icon($key)); ?>"></i>
                                </div>
                                <div class="ep-interest-label"><?php echo esc_html($label); ?></div>
                                <div class="ep-interest-checkbox">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Interest Level Settings -->
                <div class="ep-interest-level">
                    <h3><?php _e('Engagement Level', 'environmental-platform-core'); ?></h3>
                    <div class="ep-radio-group">
                        <label>
                            <input type="radio" name="engagement_level" value="beginner" 
                                   <?php checked($preferences['activity_preferences']['engagement_level'] ?? 'beginner', 'beginner'); ?>>
                            <span><?php _e('Beginner - New to environmental action', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="engagement_level" value="intermediate" 
                                   <?php checked($preferences['activity_preferences']['engagement_level'] ?? 'beginner', 'intermediate'); ?>>
                            <span><?php _e('Intermediate - Some experience with eco-friendly practices', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="engagement_level" value="advanced" 
                                   <?php checked($preferences['activity_preferences']['engagement_level'] ?? 'beginner', 'advanced'); ?>>
                            <span><?php _e('Advanced - Actively engaged in environmental initiatives', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div class="ep-tab-content" id="tab-notifications">
            <div class="ep-preferences-section">
                <h2><?php _e('Notification Preferences', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Choose how and when you want to receive notifications about environmental activities and platform updates.', 'environmental-platform-core'); ?>
                </p>

                <!-- Email Notifications -->
                <div class="ep-notification-group">
                    <h3><?php _e('Email Notifications', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="notifications[email_daily_tips]" value="1" 
                                   <?php checked($preferences['notification_preferences']['email_daily_tips'] ?? false); ?>>
                            <span><?php _e('Daily environmental tips', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[email_weekly_summary]" value="1" 
                                   <?php checked($preferences['notification_preferences']['email_weekly_summary'] ?? true); ?>>
                            <span><?php _e('Weekly activity summary', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[email_achievement]" value="1" 
                                   <?php checked($preferences['notification_preferences']['email_achievement'] ?? true); ?>>
                            <span><?php _e('Achievement notifications', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[email_community]" value="1" 
                                   <?php checked($preferences['notification_preferences']['email_community'] ?? false); ?>>
                            <span><?php _e('Community updates and events', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[email_challenges]" value="1" 
                                   <?php checked($preferences['notification_preferences']['email_challenges'] ?? true); ?>>
                            <span><?php _e('New challenges and competitions', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Browser Notifications -->
                <div class="ep-notification-group">
                    <h3><?php _e('Browser Notifications', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="notifications[browser_reminders]" value="1" 
                                   <?php checked($preferences['notification_preferences']['browser_reminders'] ?? false); ?>>
                            <span><?php _e('Activity reminders', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[browser_achievements]" value="1" 
                                   <?php checked($preferences['notification_preferences']['browser_achievements'] ?? true); ?>>
                            <span><?php _e('Achievement unlocked notifications', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="notifications[browser_social]" value="1" 
                                   <?php checked($preferences['notification_preferences']['browser_social'] ?? false); ?>>
                            <span><?php _e('Social activity updates', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Frequency Settings -->
                <div class="ep-notification-group">
                    <h3><?php _e('Notification Frequency', 'environmental-platform-core'); ?></h3>
                    <div class="ep-frequency-settings">
                        <label>
                            <?php _e('Email frequency:', 'environmental-platform-core'); ?>
                            <select name="notifications[email_frequency]">
                                <option value="immediate" <?php selected($preferences['notification_preferences']['email_frequency'] ?? 'daily', 'immediate'); ?>><?php _e('Immediate', 'environmental-platform-core'); ?></option>
                                <option value="daily" <?php selected($preferences['notification_preferences']['email_frequency'] ?? 'daily', 'daily'); ?>><?php _e('Daily digest', 'environmental-platform-core'); ?></option>
                                <option value="weekly" <?php selected($preferences['notification_preferences']['email_frequency'] ?? 'daily', 'weekly'); ?>><?php _e('Weekly digest', 'environmental-platform-core'); ?></option>
                                <option value="monthly" <?php selected($preferences['notification_preferences']['email_frequency'] ?? 'daily', 'monthly'); ?>><?php _e('Monthly digest', 'environmental-platform-core'); ?></option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Privacy Tab -->
        <div class="ep-tab-content" id="tab-privacy">
            <div class="ep-preferences-section">
                <h2><?php _e('Privacy Settings', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Control who can see your environmental activities and personal information.', 'environmental-platform-core'); ?>
                </p>

                <!-- Profile Visibility -->
                <div class="ep-privacy-group">
                    <h3><?php _e('Profile Visibility', 'environmental-platform-core'); ?></h3>
                    <div class="ep-radio-group">
                        <label>
                            <input type="radio" name="privacy[profile_visibility]" value="public" 
                                   <?php checked($preferences['privacy_settings']['profile_visibility'] ?? 'public', 'public'); ?>>
                            <span><?php _e('Public - Anyone can see your profile', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="privacy[profile_visibility]" value="community" 
                                   <?php checked($preferences['privacy_settings']['profile_visibility'] ?? 'public', 'community'); ?>>
                            <span><?php _e('Community members only', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="privacy[profile_visibility]" value="private" 
                                   <?php checked($preferences['privacy_settings']['profile_visibility'] ?? 'public', 'private'); ?>>
                            <span><?php _e('Private - Only you can see your profile', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Activity Sharing -->
                <div class="ep-privacy-group">
                    <h3><?php _e('Activity Sharing', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="privacy[share_points]" value="1" 
                                   <?php checked($preferences['privacy_settings']['share_points'] ?? true); ?>>
                            <span><?php _e('Show my green points on leaderboards', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="privacy[share_achievements]" value="1" 
                                   <?php checked($preferences['privacy_settings']['share_achievements'] ?? true); ?>>
                            <span><?php _e('Share my achievements with the community', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="privacy[share_activities]" value="1" 
                                   <?php checked($preferences['privacy_settings']['share_activities'] ?? false); ?>>
                            <span><?php _e('Share my environmental activities', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="privacy[share_location]" value="1" 
                                   <?php checked($preferences['privacy_settings']['share_location'] ?? false); ?>>
                            <span><?php _e('Share my city/region for local connections', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Data Usage -->
                <div class="ep-privacy-group">
                    <h3><?php _e('Data Usage', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="privacy[analytics]" value="1" 
                                   <?php checked($preferences['privacy_settings']['analytics'] ?? true); ?>>
                            <span><?php _e('Allow usage analytics to improve the platform', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="privacy[personalization]" value="1" 
                                   <?php checked($preferences['privacy_settings']['personalization'] ?? true); ?>>
                            <span><?php _e('Use my data for personalized recommendations', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="privacy[marketing]" value="1" 
                                   <?php checked($preferences['privacy_settings']['marketing'] ?? false); ?>>
                            <span><?php _e('Send me marketing communications about environmental products', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Tab -->
        <div class="ep-tab-content" id="tab-location">
            <div class="ep-preferences-section">
                <h2><?php _e('Location Settings', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Set your location to get local environmental information and connect with nearby eco-warriors.', 'environmental-platform-core'); ?>
                </p>

                <div class="ep-location-form">
                    <div class="ep-form-row">
                        <div class="ep-form-group">
                            <label for="location-city"><?php _e('City/Province:', 'environmental-platform-core'); ?></label>
                            <input type="text" 
                                   id="location-city" 
                                   name="location[city]" 
                                   value="<?php echo esc_attr($preferences['location_settings']['city']); ?>"
                                   placeholder="<?php _e('Enter your city or province', 'environmental-platform-core'); ?>">
                        </div>
                        <div class="ep-form-group">
                            <label for="location-district"><?php _e('District/Area:', 'environmental-platform-core'); ?></label>
                            <input type="text" 
                                   id="location-district" 
                                   name="location[district]" 
                                   value="<?php echo esc_attr($preferences['location_settings']['district']); ?>"
                                   placeholder="<?php _e('Enter your district or area', 'environmental-platform-core'); ?>">
                        </div>
                    </div>

                    <!-- Location Sharing Level -->
                    <div class="ep-location-sharing">
                        <h3><?php _e('Location Sharing Level', 'environmental-platform-core'); ?></h3>
                        <div class="ep-radio-group">
                            <label>
                                <input type="radio" name="location[share_level]" value="none" 
                                       <?php checked($preferences['location_settings']['share_location'], 'none'); ?>>
                                <span><?php _e('Don\'t share location', 'environmental-platform-core'); ?></span>
                            </label>
                            <label>
                                <input type="radio" name="location[share_level]" value="city_only" 
                                       <?php checked($preferences['location_settings']['share_location'], 'city_only'); ?>>
                                <span><?php _e('Share city/province only', 'environmental-platform-core'); ?></span>
                            </label>
                            <label>
                                <input type="radio" name="location[share_level]" value="district" 
                                       <?php checked($preferences['location_settings']['share_location'], 'district'); ?>>
                                <span><?php _e('Share city and district', 'environmental-platform-core'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Auto-detect Location -->
                    <div class="ep-auto-location">
                        <button type="button" id="ep-detect-location" class="ep-btn ep-btn-secondary">
                            <i class="fas fa-crosshairs"></i>
                            <?php _e('Auto-detect Location', 'environmental-platform-core'); ?>
                        </button>
                        <p class="ep-location-help">
                            <?php _e('Click to automatically detect your location using your browser.', 'environmental-platform-core'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div class="ep-tab-content" id="tab-dashboard">
            <div class="ep-preferences-section">
                <h2><?php _e('Dashboard Customization', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Customize what information appears on your dashboard and how it\'s displayed.', 'environmental-platform-core'); ?>
                </p>

                <!-- Dashboard Widgets -->
                <div class="ep-dashboard-widgets">
                    <h3><?php _e('Dashboard Widgets', 'environmental-platform-core'); ?></h3>
                    <div class="ep-widget-grid">
                        <?php
                        $available_widgets = array(
                            'stats_overview' => __('Statistics Overview', 'environmental-platform-core'),
                            'recent_activities' => __('Recent Activities', 'environmental-platform-core'),
                            'achievements' => __('Achievements', 'environmental-platform-core'),
                            'leaderboard' => __('Leaderboard', 'environmental-platform-core'),
                            'challenges' => __('Active Challenges', 'environmental-platform-core'),
                            'tips' => __('Environmental Tips', 'environmental-platform-core'),
                            'community_feed' => __('Community Feed', 'environmental-platform-core'),
                            'progress_chart' => __('Progress Chart', 'environmental-platform-core'),
                            'calendar' => __('Environmental Calendar', 'environmental-platform-core'),
                            'local_events' => __('Local Events', 'environmental-platform-core')
                        );
                        
                        foreach ($available_widgets as $widget_key => $widget_name):
                        ?>
                            <label class="ep-widget-item">
                                <input type="checkbox" 
                                       name="dashboard[widgets][]" 
                                       value="<?php echo esc_attr($widget_key); ?>"
                                       <?php checked(in_array($widget_key, $preferences['dashboard_settings']['widgets'] ?? array_keys($available_widgets))); ?>>
                                <span><?php echo esc_html($widget_name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dashboard Layout -->
                <div class="ep-dashboard-layout">
                    <h3><?php _e('Layout Preferences', 'environmental-platform-core'); ?></h3>
                    <div class="ep-layout-options">
                        <label>
                            <input type="radio" name="dashboard[layout]" value="cards" 
                                   <?php checked($preferences['dashboard_settings']['layout'] ?? 'cards', 'cards'); ?>>
                            <span><?php _e('Card Layout', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="dashboard[layout]" value="list" 
                                   <?php checked($preferences['dashboard_settings']['layout'] ?? 'cards', 'list'); ?>>
                            <span><?php _e('List Layout', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="dashboard[layout]" value="compact" 
                                   <?php checked($preferences['dashboard_settings']['layout'] ?? 'cards', 'compact'); ?>>
                            <span><?php _e('Compact Layout', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Update Frequency -->
                <div class="ep-update-frequency">
                    <h3><?php _e('Data Update Frequency', 'environmental-platform-core'); ?></h3>
                    <select name="dashboard[update_frequency]">
                        <option value="realtime" <?php selected($preferences['dashboard_settings']['update_frequency'] ?? 'hourly', 'realtime'); ?>><?php _e('Real-time', 'environmental-platform-core'); ?></option>
                        <option value="hourly" <?php selected($preferences['dashboard_settings']['update_frequency'] ?? 'hourly', 'hourly'); ?>><?php _e('Every hour', 'environmental-platform-core'); ?></option>
                        <option value="daily" <?php selected($preferences['dashboard_settings']['update_frequency'] ?? 'hourly', 'daily'); ?>><?php _e('Daily', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Activity Tracking Tab -->
        <div class="ep-tab-content" id="tab-activity">
            <div class="ep-preferences-section">
                <h2><?php _e('Activity Tracking Preferences', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Control how your environmental activities are tracked and measured.', 'environmental-platform-core'); ?>
                </p>

                <!-- Tracking Options -->
                <div class="ep-tracking-options">
                    <h3><?php _e('What to Track', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="activity[track_waste]" value="1" 
                                   <?php checked($preferences['activity_preferences']['track_waste'] ?? true); ?>>
                            <span><?php _e('Waste reduction activities', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[track_energy]" value="1" 
                                   <?php checked($preferences['activity_preferences']['track_energy'] ?? true); ?>>
                            <span><?php _e('Energy consumption', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[track_transport]" value="1" 
                                   <?php checked($preferences['activity_preferences']['track_transport'] ?? true); ?>>
                            <span><?php _e('Transportation choices', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[track_water]" value="1" 
                                   <?php checked($preferences['activity_preferences']['track_water'] ?? true); ?>>
                            <span><?php _e('Water usage', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[track_purchases]" value="1" 
                                   <?php checked($preferences['activity_preferences']['track_purchases'] ?? false); ?>>
                            <span><?php _e('Eco-friendly purchases', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Goals and Targets -->
                <div class="ep-goals-settings">
                    <h3><?php _e('Personal Goals', 'environmental-platform-core'); ?></h3>
                    <div class="ep-goals-form">
                        <div class="ep-form-group">
                            <label><?php _e('Monthly Green Points Target:', 'environmental-platform-core'); ?></label>
                            <input type="number" name="activity[monthly_points_goal]" 
                                   value="<?php echo esc_attr($preferences['activity_preferences']['monthly_points_goal'] ?? 500); ?>" 
                                   min="100" max="10000" step="50">
                        </div>
                        <div class="ep-form-group">
                            <label><?php _e('Weekly Activity Goal:', 'environmental-platform-core'); ?></label>
                            <select name="activity[weekly_activity_goal]">
                                <option value="1" <?php selected($preferences['activity_preferences']['weekly_activity_goal'] ?? 3, 1); ?>><?php _e('1 activity per week', 'environmental-platform-core'); ?></option>
                                <option value="3" <?php selected($preferences['activity_preferences']['weekly_activity_goal'] ?? 3, 3); ?>><?php _e('3 activities per week', 'environmental-platform-core'); ?></option>
                                <option value="5" <?php selected($preferences['activity_preferences']['weekly_activity_goal'] ?? 3, 5); ?>><?php _e('5 activities per week', 'environmental-platform-core'); ?></option>
                                <option value="7" <?php selected($preferences['activity_preferences']['weekly_activity_goal'] ?? 3, 7); ?>><?php _e('Daily activities', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Reminders -->
                <div class="ep-reminders-settings">
                    <h3><?php _e('Activity Reminders', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="activity[daily_reminders]" value="1" 
                                   <?php checked($preferences['activity_preferences']['daily_reminders'] ?? false); ?>>
                            <span><?php _e('Daily activity reminders', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[goal_reminders]" value="1" 
                                   <?php checked($preferences['activity_preferences']['goal_reminders'] ?? true); ?>>
                            <span><?php _e('Goal progress reminders', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="activity[streak_reminders]" value="1" 
                                   <?php checked($preferences['activity_preferences']['streak_reminders'] ?? true); ?>>
                            <span><?php _e('Activity streak reminders', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Preferences Tab -->
        <div class="ep-tab-content" id="tab-content">
            <div class="ep-preferences-section">
                <h2><?php _e('Content Preferences', 'environmental-platform-core'); ?></h2>
                <p class="ep-section-description">
                    <?php _e('Customize the types of content and information you want to see.', 'environmental-platform-core'); ?>
                </p>

                <!-- Content Types -->
                <div class="ep-content-types">
                    <h3><?php _e('Preferred Content Types', 'environmental-platform-core'); ?></h3>
                    <div class="ep-checkbox-group">
                        <label>
                            <input type="checkbox" name="content[articles]" value="1" 
                                   <?php checked($preferences['content_preferences']['articles'] ?? true); ?>>
                            <span><?php _e('Environmental articles and news', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="content[tips]" value="1" 
                                   <?php checked($preferences['content_preferences']['tips'] ?? true); ?>>
                            <span><?php _e('Daily eco-tips', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="content[challenges]" value="1" 
                                   <?php checked($preferences['content_preferences']['challenges'] ?? true); ?>>
                            <span><?php _e('Environmental challenges', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="content[success_stories]" value="1" 
                                   <?php checked($preferences['content_preferences']['success_stories'] ?? true); ?>>
                            <span><?php _e('Community success stories', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="content[diy_guides]" value="1" 
                                   <?php checked($preferences['content_preferences']['diy_guides'] ?? true); ?>>
                            <span><?php _e('DIY and how-to guides', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="checkbox" name="content[scientific_updates]" value="1" 
                                   <?php checked($preferences['content_preferences']['scientific_updates'] ?? false); ?>>
                            <span><?php _e('Scientific research updates', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Content Difficulty -->
                <div class="ep-content-difficulty">
                    <h3><?php _e('Content Complexity', 'environmental-platform-core'); ?></h3>
                    <div class="ep-radio-group">
                        <label>
                            <input type="radio" name="content[difficulty]" value="beginner" 
                                   <?php checked($preferences['content_preferences']['difficulty'] ?? 'mixed', 'beginner'); ?>>
                            <span><?php _e('Beginner-friendly content', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="content[difficulty]" value="intermediate" 
                                   <?php checked($preferences['content_preferences']['difficulty'] ?? 'mixed', 'intermediate'); ?>>
                            <span><?php _e('Intermediate level content', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="content[difficulty]" value="advanced" 
                                   <?php checked($preferences['content_preferences']['difficulty'] ?? 'mixed', 'advanced'); ?>>
                            <span><?php _e('Advanced and technical content', 'environmental-platform-core'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="content[difficulty]" value="mixed" 
                                   <?php checked($preferences['content_preferences']['difficulty'] ?? 'mixed', 'mixed'); ?>>
                            <span><?php _e('Mixed difficulty levels', 'environmental-platform-core'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Language Preferences -->
                <div class="ep-language-preferences">
                    <h3><?php _e('Language Preferences', 'environmental-platform-core'); ?></h3>
                    <div class="ep-form-group">
                        <label><?php _e('Preferred content language:', 'environmental-platform-core'); ?></label>
                        <select name="content[language]">
                            <option value="vi" <?php selected($preferences['content_preferences']['language'] ?? 'vi', 'vi'); ?>><?php _e('Vietnamese', 'environmental-platform-core'); ?></option>
                            <option value="en" <?php selected($preferences['content_preferences']['language'] ?? 'vi', 'en'); ?>><?php _e('English', 'environmental-platform-core'); ?></option>
                            <option value="both" <?php selected($preferences['content_preferences']['language'] ?? 'vi', 'both'); ?>><?php _e('Both Vietnamese and English', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="ep-preferences-actions">
            <button type="submit" class="ep-btn ep-btn-primary ep-btn-large">
                <i class="fas fa-save"></i>
                <?php _e('Save All Preferences', 'environmental-platform-core'); ?>
            </button>
            <button type="button" id="ep-reset-preferences" class="ep-btn ep-btn-secondary">
                <i class="fas fa-undo"></i>
                <?php _e('Reset to Defaults', 'environmental-platform-core'); ?>
            </button>
        </div>
    </form>

    <!-- User Stats Summary -->
    <div class="ep-user-stats-summary">
        <h3><?php _e('Your Environmental Impact', 'environmental-platform-core'); ?></h3>
        <div class="ep-stats-grid">
            <div class="ep-stat-item">
                <div class="ep-stat-value"><?php echo number_format($user_stats->green_points ?? 0); ?></div>
                <div class="ep-stat-label"><?php _e('Green Points', 'environmental-platform-core'); ?></div>
            </div>
            <div class="ep-stat-item">
                <div class="ep-stat-value"><?php echo $user_stats->level ?? 1; ?></div>
                <div class="ep-stat-label"><?php _e('Level', 'environmental-platform-core'); ?></div>
            </div>
            <div class="ep-stat-item">
                <div class="ep-stat-value"><?php echo number_format($user_stats->total_environmental_score ?? 0); ?></div>
                <div class="ep-stat-label"><?php _e('Environmental Score', 'environmental-platform-core'); ?></div>
            </div>
            <div class="ep-stat-item">
                <div class="ep-stat-value"><?php echo number_format($user_stats->carbon_footprint_kg ?? 0, 1); ?>kg</div>
                <div class="ep-stat-label"><?php _e('Carbon Saved', 'environmental-platform-core'); ?></div>
            </div>
        </div>
    </div>
</div>

<?php
// Add the method to get interest icons
if (!function_exists('get_interest_icon')) {
    function get_interest_icon($interest_key) {
        $icons = array(
            'waste_reduction' => 'fas fa-trash-alt',
            'recycling' => 'fas fa-recycle',
            'energy_saving' => 'fas fa-bolt',
            'water_conservation' => 'fas fa-tint',
            'sustainable_transport' => 'fas fa-bicycle',
            'organic_gardening' => 'fas fa-seedling',
            'renewable_energy' => 'fas fa-solar-panel',
            'eco_products' => 'fas fa-leaf',
            'climate_action' => 'fas fa-globe-americas',
            'biodiversity' => 'fas fa-paw',
            'air_quality' => 'fas fa-wind',
            'sustainable_food' => 'fas fa-apple-alt',
            'green_building' => 'fas fa-building',
            'environmental_education' => 'fas fa-graduation-cap',
            'community_action' => 'fas fa-users'
        );
        
        return $icons[$interest_key] ?? 'fas fa-leaf';
    }
}
?>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.ep-tab-link').on('click', function(e) {
        e.preventDefault();
        
        const tab = $(this).data('tab');
        
        // Update active states
        $('.ep-tab-link').removeClass('active');
        $(this).addClass('active');
        
        $('.ep-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
        
        // Update URL hash
        window.location.hash = tab;
    });
    
    // Auto-detect location
    $('#ep-detect-location').on('click', function() {
        if ("geolocation" in navigator) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + ep_preferences_text.detecting);
            
            navigator.geolocation.getCurrentPosition(function(position) {
                // Use reverse geocoding to get location name
                // This would typically use a geocoding service
                reverseGeocode(position.coords.latitude, position.coords.longitude);
            }, function(error) {
                alert(ep_preferences_text.location_error);
                $('#ep-detect-location').prop('disabled', false).html('<i class="fas fa-crosshairs"></i> ' + ep_preferences_text.auto_detect);
            });
        } else {
            alert(ep_preferences_text.geolocation_not_supported);
        }
    });
    
    // Form submission
    $('#ep-preferences-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.ep-btn-primary').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + ep_preferences_text.saving);
            },
            success: function(response) {
                if (response.success) {
                    showNotification(ep_preferences_text.preferences_saved, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(ep_preferences_text.save_error, 'error');
            },
            complete: function() {
                $('.ep-btn-primary').prop('disabled', false).html('<i class="fas fa-save"></i> ' + ep_preferences_text.save_preferences);
            }
        });
    });
    
    // Reset preferences
    $('#ep-reset-preferences').on('click', function() {
        if (confirm(ep_preferences_text.confirm_reset)) {
            // Reset form to defaults
            location.reload();
        }
    });
    
    // Initialize from URL hash
    if (window.location.hash) {
        const tab = window.location.hash.substring(1);
        $('.ep-tab-link[data-tab="' + tab + '"]').click();
    }
    
    function reverseGeocode(lat, lng) {
        // This would typically use a service like Google Maps Geocoding API
        // For demonstration, we'll use a simple approximation
        
        $.ajax({
            url: ep_preferences_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ep_reverse_geocode',
                lat: lat,
                lng: lng,
                nonce: ep_preferences_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#location-city').val(response.data.city);
                    $('#location-district').val(response.data.district);
                }
            },
            complete: function() {
                $('#ep-detect-location').prop('disabled', false).html('<i class="fas fa-crosshairs"></i> ' + ep_preferences_text.auto_detect);
            }
        });
    }
    
    function showNotification(message, type) {
        const notification = $('<div class="ep-notification ep-notification-' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
</script>
