<?php
/**
 * User Dashboard Template
 * Template for environmental platform user dashboard
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
?>

<div class="ep-user-dashboard-container">
    
    <!-- Dashboard Header -->
    <div class="ep-dashboard-header">
        <div class="ep-welcome-section">
            <h1><?php printf(__('Welcome back, %s!', 'environmental-platform-core'), esc_html($current_user->display_name)); ?></h1>
            <p class="ep-dashboard-subtitle"><?php _e('Continue your environmental journey and make a positive impact today.', 'environmental-platform-core'); ?></p>
        </div>
        
        <div class="ep-quick-stats">
            <div class="ep-quick-stat">
                <span class="ep-stat-number"><?php echo number_format_i18n($user_data['green_points']); ?></span>
                <span class="ep-stat-label"><?php _e('Green Points', 'environmental-platform-core'); ?></span>
            </div>
            <div class="ep-quick-stat">
                <span class="ep-stat-number"><?php echo esc_html($user_data['current_level']); ?></span>
                <span class="ep-stat-label"><?php _e('Level', 'environmental-platform-core'); ?></span>
            </div>
            <div class="ep-quick-stat">
                <span class="ep-stat-number"><?php echo count($user_data['achievements']); ?></span>
                <span class="ep-stat-label"><?php _e('Achievements', 'environmental-platform-core'); ?></span>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="ep-dashboard-grid">
        
        <!-- Level Progress Card -->
        <div class="ep-dashboard-card ep-progress-card">
            <div class="ep-card-header">
                <h3><?php _e('Level Progress', 'environmental-platform-core'); ?></h3>
                <span class="ep-current-level"><?php echo esc_html($user_data['current_level']); ?></span>
            </div>
            <div class="ep-card-content">
                <div class="ep-level-progress">
                    <div class="ep-progress-bar">
                        <div class="ep-progress-fill" style="width: <?php echo esc_attr($user_data['level_progress']); ?>%"></div>
                    </div>
                    <div class="ep-progress-info">
                        <span><?php echo number_format_i18n($user_data['current_level_points']); ?> / <?php echo number_format_i18n($user_data['next_level_points']); ?> points</span>
                        <span class="ep-next-level"><?php printf(__('Next: %s', 'environmental-platform-core'), esc_html($user_data['next_level'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="ep-dashboard-card ep-activities-card">
            <div class="ep-card-header">
                <h3><?php _e('Recent Activities', 'environmental-platform-core'); ?></h3>
                <a href="<?php echo esc_url(home_url('/profile/#ep-activities')); ?>" class="ep-view-all"><?php _e('View All', 'environmental-platform-core'); ?></a>
            </div>
            <div class="ep-card-content">
                <?php if (!empty($user_data['recent_activities'])): ?>
                    <div class="ep-activity-list">
                        <?php foreach (array_slice($user_data['recent_activities'], 0, 5) as $activity): ?>
                            <div class="ep-activity-item">
                                <div class="ep-activity-icon">
                                    <i class="<?php echo esc_attr($activity['icon']); ?>"></i>
                                </div>
                                <div class="ep-activity-content">
                                    <p class="ep-activity-title"><?php echo esc_html($activity['title']); ?></p>
                                    <p class="ep-activity-description"><?php echo esc_html($activity['description']); ?></p>
                                    <small class="ep-activity-time"><?php echo human_time_diff(strtotime($activity['created_at'])); ?> <?php _e('ago', 'environmental-platform-core'); ?></small>
                                </div>
                                <div class="ep-activity-points">
                                    +<?php echo number_format_i18n($activity['points']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ep-no-activities">
                        <i class="ep-icon-activity"></i>
                        <p><?php _e('No activities yet. Start participating to earn points!', 'environmental-platform-core'); ?></p>
                        <a href="<?php echo esc_url(home_url('/activities')); ?>" class="ep-btn ep-btn-primary"><?php _e('Explore Activities', 'environmental-platform-core'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Environmental Impact -->
        <div class="ep-dashboard-card ep-impact-card">
            <div class="ep-card-header">
                <h3><?php _e('Your Environmental Impact', 'environmental-platform-core'); ?></h3>
            </div>
            <div class="ep-card-content">
                <div class="ep-impact-grid">
                    <div class="ep-impact-item">
                        <div class="ep-impact-icon">
                            <i class="ep-icon-co2"></i>
                        </div>
                        <div class="ep-impact-data">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['co2_saved']); ?></span>
                            <span class="ep-impact-unit"><?php _e('kg CO₂ Saved', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                    <div class="ep-impact-item">
                        <div class="ep-impact-icon">
                            <i class="ep-icon-recycle"></i>
                        </div>
                        <div class="ep-impact-data">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['waste_recycled']); ?></span>
                            <span class="ep-impact-unit"><?php _e('kg Recycled', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                    <div class="ep-impact-item">
                        <div class="ep-impact-icon">
                            <i class="ep-icon-tree"></i>
                        </div>
                        <div class="ep-impact-data">
                            <span class="ep-impact-value"><?php echo esc_html($user_data['trees_planted']); ?></span>
                            <span class="ep-impact-unit"><?php _e('Tree Equivalent', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Achievements -->
        <div class="ep-dashboard-card ep-achievements-card">
            <div class="ep-card-header">
                <h3><?php _e('Recent Achievements', 'environmental-platform-core'); ?></h3>
                <a href="<?php echo esc_url(home_url('/profile/#ep-achievements')); ?>" class="ep-view-all"><?php _e('View All', 'environmental-platform-core'); ?></a>
            </div>
            <div class="ep-card-content">
                <?php if (!empty($user_data['recent_achievements'])): ?>
                    <div class="ep-achievement-list">
                        <?php foreach (array_slice($user_data['recent_achievements'], 0, 3) as $achievement): ?>
                            <div class="ep-achievement-item">
                                <div class="ep-achievement-icon">
                                    <i class="<?php echo esc_attr($achievement['icon']); ?>"></i>
                                </div>
                                <div class="ep-achievement-content">
                                    <h4><?php echo esc_html($achievement['title']); ?></h4>
                                    <p><?php echo esc_html($achievement['description']); ?></p>
                                    <small><?php echo human_time_diff(strtotime($achievement['earned_date'])); ?> <?php _e('ago', 'environmental-platform-core'); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ep-no-achievements">
                        <i class="ep-icon-trophy"></i>
                        <p><?php _e('No achievements yet. Complete activities to unlock badges!', 'environmental-platform-core'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="ep-dashboard-card ep-actions-card">
            <div class="ep-card-header">
                <h3><?php _e('Quick Actions', 'environmental-platform-core'); ?></h3>
            </div>
            <div class="ep-card-content">
                <div class="ep-quick-actions">
                    <a href="<?php echo esc_url(home_url('/waste-classification')); ?>" class="ep-action-button">
                        <i class="ep-icon-camera"></i>
                        <span><?php _e('Classify Waste', 'environmental-platform-core'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/events')); ?>" class="ep-action-button">
                        <i class="ep-icon-calendar"></i>
                        <span><?php _e('Join Events', 'environmental-platform-core'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/marketplace')); ?>" class="ep-action-button">
                        <i class="ep-icon-shop"></i>
                        <span><?php _e('Green Marketplace', 'environmental-platform-core'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/leaderboard')); ?>" class="ep-action-button">
                        <i class="ep-icon-leaderboard"></i>
                        <span><?php _e('Leaderboard', 'environmental-platform-core'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/reports')); ?>" class="ep-action-button">
                        <i class="ep-icon-report"></i>
                        <span><?php _e('Report Issues', 'environmental-platform-core'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/education')); ?>" class="ep-action-button">
                        <i class="ep-icon-education"></i>
                        <span><?php _e('Learn More', 'environmental-platform-core'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Leaderboard Preview -->
        <div class="ep-dashboard-card ep-leaderboard-card">
            <div class="ep-card-header">
                <h3><?php _e('Leaderboard', 'environmental-platform-core'); ?></h3>
                <a href="<?php echo esc_url(home_url('/leaderboard')); ?>" class="ep-view-all"><?php _e('View Full', 'environmental-platform-core'); ?></a>
            </div>
            <div class="ep-card-content">
                <?php echo do_shortcode('[ep_leaderboard limit="5" current_user="highlight"]'); ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="ep-dashboard-card ep-events-card">
            <div class="ep-card-header">
                <h3><?php _e('Upcoming Events', 'environmental-platform-core'); ?></h3>
                <a href="<?php echo esc_url(home_url('/events')); ?>" class="ep-view-all"><?php _e('View All', 'environmental-platform-core'); ?></a>
            </div>
            <div class="ep-card-content">
                <?php
                // Get upcoming events
                $upcoming_events = $user_management->get_upcoming_events(3);
                ?>
                <?php if (!empty($upcoming_events)): ?>
                    <div class="ep-events-list">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="ep-event-item">
                                <div class="ep-event-date">
                                    <span class="ep-event-day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                    <span class="ep-event-month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="ep-event-content">
                                    <h4><?php echo esc_html($event['title']); ?></h4>
                                    <p><?php echo esc_html($event['description']); ?></p>
                                    <small>
                                        <i class="ep-icon-location"></i>
                                        <?php echo esc_html($event['location']); ?>
                                    </small>
                                </div>
                                <div class="ep-event-action">
                                    <a href="<?php echo esc_url($event['url']); ?>" class="ep-btn ep-btn-small"><?php _e('Join', 'environmental-platform-core'); ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ep-no-events">
                        <i class="ep-icon-calendar"></i>
                        <p><?php _e('No upcoming events. Check back soon!', 'environmental-platform-core'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Challenges -->
        <div class="ep-dashboard-card ep-challenges-card">
            <div class="ep-card-header">
                <h3><?php _e('Daily Challenges', 'environmental-platform-core'); ?></h3>
            </div>
            <div class="ep-card-content">
                <?php
                // Get daily challenges
                $daily_challenges = $user_management->get_daily_challenges($user_id);
                ?>
                <?php if (!empty($daily_challenges)): ?>
                    <div class="ep-challenges-list">
                        <?php foreach ($daily_challenges as $challenge): ?>
                            <div class="ep-challenge-item <?php echo $challenge['completed'] ? 'completed' : ''; ?>">
                                <div class="ep-challenge-icon">
                                    <i class="<?php echo esc_attr($challenge['icon']); ?>"></i>
                                </div>
                                <div class="ep-challenge-content">
                                    <h4><?php echo esc_html($challenge['title']); ?></h4>
                                    <p><?php echo esc_html($challenge['description']); ?></p>
                                    <div class="ep-challenge-progress">
                                        <div class="ep-progress-bar">
                                            <div class="ep-progress-fill" style="width: <?php echo esc_attr($challenge['progress']); ?>%"></div>
                                        </div>
                                        <small><?php echo esc_html($challenge['progress_text']); ?></small>
                                    </div>
                                </div>
                                <div class="ep-challenge-reward">
                                    <span class="ep-points">+<?php echo number_format_i18n($challenge['points']); ?></span>
                                    <?php if ($challenge['completed']): ?>
                                        <i class="ep-icon-check ep-completed"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="ep-no-challenges">
                        <i class="ep-icon-trophy"></i>
                        <p><?php _e('No challenges available today. New challenges coming soon!', 'environmental-platform-core'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize dashboard functionality
    if (typeof EP_UserManagement !== 'undefined') {
        EP_UserManagement.initDashboard();
    }
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        if (typeof EP_UserManagement !== 'undefined') {
            EP_UserManagement.refreshDashboardData();
        }
    }, 300000); // 5 minutes
});
</script>
