<?php
/**
 * User Profile Template
 * Template for environmental platform user profile page
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user data
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user management instance for data retrieval
$user_management = new EP_User_Management();
$user_data = $user_management->get_user_dashboard_data($user_id);
$profile_data = $user_management->get_user_profile_data($user_id);
?>

<div class="ep-user-profile-container">
    <!-- Profile Header -->
    <div class="ep-profile-header">
        <div class="ep-profile-avatar">
            <?php echo get_avatar($user_id, 120, '', '', array('class' => 'ep-avatar-img')); ?>
            <button class="ep-change-avatar-btn" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="ep-icon-camera"></i>
                <?php _e('Change Photo', 'environmental-platform-core'); ?>
            </button>
        </div>
        
        <div class="ep-profile-info">
            <h1 class="ep-profile-name"><?php echo esc_html($profile_data['display_name']); ?></h1>
            <p class="ep-profile-role"><?php echo esc_html($profile_data['user_role_display']); ?></p>
            <p class="ep-profile-location">
                <i class="ep-icon-location"></i>
                <?php echo esc_html($profile_data['location'] ?: __('Location not set', 'environmental-platform-core')); ?>
            </p>
            <p class="ep-profile-joined">
                <i class="ep-icon-calendar"></i>
                <?php printf(__('Joined %s', 'environmental-platform-core'), date_i18n(get_option('date_format'), strtotime($profile_data['user_registered']))); ?>
            </p>
        </div>

        <div class="ep-profile-actions">
            <button class="ep-btn ep-btn-outline" id="ep-edit-profile-btn">
                <i class="ep-icon-edit"></i>
                <?php _e('Edit Profile', 'environmental-platform-core'); ?>
            </button>
            <button class="ep-btn ep-btn-outline" id="ep-profile-settings-btn">
                <i class="ep-icon-settings"></i>
                <?php _e('Settings', 'environmental-platform-core'); ?>
            </button>
        </div>
    </div>

    <!-- Profile Stats -->
    <div class="ep-profile-stats">
        <div class="ep-stat-card">
            <div class="ep-stat-icon ep-green-points">
                <i class="ep-icon-leaf"></i>
            </div>
            <div class="ep-stat-content">
                <h3><?php echo number_format_i18n($user_data['green_points']); ?></h3>
                <p><?php _e('Green Points', 'environmental-platform-core'); ?></p>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon ep-level">
                <i class="ep-icon-star"></i>
            </div>
            <div class="ep-stat-content">
                <h3><?php echo esc_html($user_data['current_level']); ?></h3>
                <p><?php _e('Current Level', 'environmental-platform-core'); ?></p>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon ep-achievements">
                <i class="ep-icon-trophy"></i>
            </div>
            <div class="ep-stat-content">
                <h3><?php echo count($user_data['achievements']); ?></h3>
                <p><?php _e('Achievements', 'environmental-platform-core'); ?></p>
            </div>
        </div>

        <div class="ep-stat-card">
            <div class="ep-stat-icon ep-activities">
                <i class="ep-icon-activity"></i>
            </div>
            <div class="ep-stat-content">
                <h3><?php echo number_format_i18n($user_data['total_activities']); ?></h3>
                <p><?php _e('Activities', 'environmental-platform-core'); ?></p>
            </div>
        </div>
    </div>

    <!-- Profile Navigation Tabs -->
    <div class="ep-profile-nav">
        <ul class="ep-nav-tabs">
            <li class="ep-nav-item active">
                <a href="#ep-overview" class="ep-nav-link"><?php _e('Overview', 'environmental-platform-core'); ?></a>
            </li>
            <li class="ep-nav-item">
                <a href="#ep-activities" class="ep-nav-link"><?php _e('Activities', 'environmental-platform-core'); ?></a>
            </li>
            <li class="ep-nav-item">
                <a href="#ep-achievements" class="ep-nav-link"><?php _e('Achievements', 'environmental-platform-core'); ?></a>
            </li>
            <li class="ep-nav-item">
                <a href="#ep-social" class="ep-nav-link"><?php _e('Social', 'environmental-platform-core'); ?></a>
            </li>
            <li class="ep-nav-item">
                <a href="#ep-settings" class="ep-nav-link"><?php _e('Settings', 'environmental-platform-core'); ?></a>
            </li>
        </ul>
    </div>

    <!-- Profile Content Panels -->
    <div class="ep-profile-content">
        
        <!-- Overview Panel -->
        <div id="ep-overview" class="ep-profile-panel active">
            <div class="ep-panel-grid">
                
                <!-- Progress Card -->
                <div class="ep-profile-card">
                    <h3><?php _e('Level Progress', 'environmental-platform-core'); ?></h3>
                    <div class="ep-level-progress">
                        <div class="ep-progress-bar">
                            <div class="ep-progress-fill" style="width: <?php echo esc_attr($user_data['level_progress']); ?>%"></div>
                        </div>
                        <p><?php printf(
                            __('%d / %d points to next level', 'environmental-platform-core'),
                            $user_data['current_level_points'],
                            $user_data['next_level_points']
                        ); ?></p>
                    </div>
                </div>

                <!-- Recent Achievements -->
                <div class="ep-profile-card">
                    <h3><?php _e('Recent Achievements', 'environmental-platform-core'); ?></h3>
                    <div class="ep-achievement-list">
                        <?php if (!empty($user_data['recent_achievements'])): ?>
                            <?php foreach (array_slice($user_data['recent_achievements'], 0, 3) as $achievement): ?>
                                <div class="ep-achievement-item">
                                    <div class="ep-achievement-icon">
                                        <i class="<?php echo esc_attr($achievement['icon']); ?>"></i>
                                    </div>
                                    <div class="ep-achievement-info">
                                        <h4><?php echo esc_html($achievement['title']); ?></h4>
                                        <p><?php echo esc_html($achievement['description']); ?></p>
                                        <small><?php echo human_time_diff(strtotime($achievement['earned_date'])); ?> <?php _e('ago', 'environmental-platform-core'); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="ep-no-data"><?php _e('No achievements yet. Start participating to earn your first badge!', 'environmental-platform-core'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Environmental Impact -->
                <div class="ep-profile-card">
                    <h3><?php _e('Environmental Impact', 'environmental-platform-core'); ?></h3>
                    <div class="ep-impact-stats">
                        <div class="ep-impact-item">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['co2_saved']); ?></span>
                            <span class="ep-impact-label"><?php _e('kg CO₂ Saved', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="ep-impact-item">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['waste_recycled']); ?></span>
                            <span class="ep-impact-label"><?php _e('kg Waste Recycled', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="ep-impact-item">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['trees_planted']); ?></span>
                            <span class="ep-impact-label"><?php _e('Trees Equivalent', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Interests -->
                <div class="ep-profile-card">
                    <h3><?php _e('Environmental Interests', 'environmental-platform-core'); ?></h3>
                    <div class="ep-interests-tags">
                        <?php if (!empty($profile_data['interests'])): ?>
                            <?php foreach ($profile_data['interests'] as $interest): ?>
                                <span class="ep-interest-tag"><?php echo esc_html($interest); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="ep-no-data"><?php _e('No interests selected yet.', 'environmental-platform-core'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Activities Panel -->
        <div id="ep-activities" class="ep-profile-panel">
            <div class="ep-activities-container">
                <div class="ep-activity-filters">
                    <select id="ep-activity-type-filter">
                        <option value=""><?php _e('All Activities', 'environmental-platform-core'); ?></option>
                        <option value="waste_classification"><?php _e('Waste Classification', 'environmental-platform-core'); ?></option>
                        <option value="recycling"><?php _e('Recycling', 'environmental-platform-core'); ?></option>
                        <option value="events"><?php _e('Events', 'environmental-platform-core'); ?></option>
                        <option value="education"><?php _e('Education', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
                <div id="ep-activities-list" class="ep-activities-list">
                    <!-- Activities will be loaded via AJAX -->
                    <div class="ep-loading"><?php _e('Loading activities...', 'environmental-platform-core'); ?></div>
                </div>
            </div>
        </div>

        <!-- Achievements Panel -->
        <div id="ep-achievements" class="ep-profile-panel">
            <div class="ep-achievements-grid">
                <?php if (!empty($user_data['achievements'])): ?>
                    <?php foreach ($user_data['achievements'] as $achievement): ?>
                        <div class="ep-achievement-card earned">
                            <div class="ep-achievement-icon">
                                <i class="<?php echo esc_attr($achievement['icon']); ?>"></i>
                            </div>
                            <h4><?php echo esc_html($achievement['title']); ?></h4>
                            <p><?php echo esc_html($achievement['description']); ?></p>
                            <small><?php printf(__('Earned %s', 'environmental-platform-core'), date_i18n(get_option('date_format'), strtotime($achievement['earned_date']))); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Available achievements -->
                <?php if (!empty($user_data['available_achievements'])): ?>
                    <?php foreach ($user_data['available_achievements'] as $achievement): ?>
                        <div class="ep-achievement-card available">
                            <div class="ep-achievement-icon">
                                <i class="<?php echo esc_attr($achievement['icon']); ?>"></i>
                            </div>
                            <h4><?php echo esc_html($achievement['title']); ?></h4>
                            <p><?php echo esc_html($achievement['description']); ?></p>
                            <div class="ep-achievement-progress">
                                <div class="ep-progress-bar">
                                    <div class="ep-progress-fill" style="width: <?php echo esc_attr($achievement['progress']); ?>%"></div>
                                </div>
                                <small><?php echo esc_html($achievement['progress_text']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Social Panel -->
        <div id="ep-social" class="ep-profile-panel">
            <div class="ep-social-content">
                <div class="ep-social-connections">
                    <h3><?php _e('Connected Social Accounts', 'environmental-platform-core'); ?></h3>
                    <?php echo do_shortcode('[ep_social_connections]'); ?>
                </div>
                
                <div class="ep-social-sharing">
                    <h3><?php _e('Share Your Progress', 'environmental-platform-core'); ?></h3>
                    <div class="ep-share-buttons">
                        <button class="ep-share-btn ep-share-facebook" data-share="facebook">
                            <i class="ep-icon-facebook"></i>
                            <?php _e('Share on Facebook', 'environmental-platform-core'); ?>
                        </button>
                        <button class="ep-share-btn ep-share-twitter" data-share="twitter">
                            <i class="ep-icon-twitter"></i>
                            <?php _e('Share on Twitter', 'environmental-platform-core'); ?>
                        </button>
                        <button class="ep-share-btn ep-share-linkedin" data-share="linkedin">
                            <i class="ep-icon-linkedin"></i>
                            <?php _e('Share on LinkedIn', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div id="ep-settings" class="ep-profile-panel">
            <div class="ep-settings-content">
                <form id="ep-profile-settings-form">
                    <?php wp_nonce_field('ep_profile_settings', 'ep_profile_settings_nonce'); ?>
                    
                    <div class="ep-settings-section">
                        <h3><?php _e('Profile Information', 'environmental-platform-core'); ?></h3>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group ep-half-width">
                                <label for="ep_settings_first_name"><?php _e('First Name', 'environmental-platform-core'); ?></label>
                                <input type="text" id="ep_settings_first_name" name="first_name" value="<?php echo esc_attr($profile_data['first_name']); ?>">
                            </div>
                            <div class="ep-form-group ep-half-width">
                                <label for="ep_settings_last_name"><?php _e('Last Name', 'environmental-platform-core'); ?></label>
                                <input type="text" id="ep_settings_last_name" name="last_name" value="<?php echo esc_attr($profile_data['last_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="ep-form-group">
                            <label for="ep_settings_bio"><?php _e('Bio', 'environmental-platform-core'); ?></label>
                            <textarea id="ep_settings_bio" name="bio" rows="4"><?php echo esc_textarea($profile_data['bio']); ?></textarea>
                        </div>
                        
                        <div class="ep-form-group">
                            <label for="ep_settings_location"><?php _e('Location', 'environmental-platform-core'); ?></label>
                            <input type="text" id="ep_settings_location" name="location" value="<?php echo esc_attr($profile_data['location']); ?>">
                        </div>
                    </div>

                    <div class="ep-settings-section">
                        <h3><?php _e('Privacy Settings', 'environmental-platform-core'); ?></h3>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="profile_public" value="1" <?php checked($profile_data['profile_public'], 1); ?>>
                                <?php _e('Make my profile public', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="show_achievements" value="1" <?php checked($profile_data['show_achievements'], 1); ?>>
                                <?php _e('Show my achievements publicly', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="show_leaderboard" value="1" <?php checked($profile_data['show_leaderboard'], 1); ?>>
                                <?php _e('Include me in leaderboards', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="ep-settings-section">
                        <h3><?php _e('Notification Preferences', 'environmental-platform-core'); ?></h3>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="email_notifications" value="1" <?php checked($profile_data['email_notifications'], 1); ?>>
                                <?php _e('Receive email notifications', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="achievement_notifications" value="1" <?php checked($profile_data['achievement_notifications'], 1); ?>>
                                <?php _e('Achievement notifications', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-group">
                            <label class="ep-checkbox-label">
                                <input type="checkbox" name="newsletter_subscription" value="1" <?php checked($profile_data['newsletter_subscription'], 1); ?>>
                                <?php _e('Environmental newsletter', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="ep-form-group ep-submit-section">
                        <button type="submit" class="ep-btn ep-btn-primary">
                            <?php _e('Save Settings', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize profile functionality
    if (typeof EP_UserManagement !== 'undefined') {
        EP_UserManagement.initProfile();
    }
});
</script>
