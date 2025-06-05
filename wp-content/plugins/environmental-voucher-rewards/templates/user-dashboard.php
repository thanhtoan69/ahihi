<?php
/**
 * Template for displaying user dashboard
 * 
 * @package Environmental_Voucher_Rewards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    echo '<p>' . __('Please log in to access your dashboard.', 'env-voucher-rewards') . '</p>';
    return;
}

// Get user data
$db_manager = EVR_Database_Manager::get_instance();
$loyalty_program = EVR_Loyalty_Program::get_instance();
$user_dashboard = EVR_User_Dashboard::get_instance();

$user_stats = $user_dashboard->get_user_statistics($user_id);
$user_tier = $loyalty_program->get_user_tier($user_id);
$recent_activities = $db_manager->get_user_recent_activities($user_id, 5);
$achievements = $user_dashboard->get_user_achievements($user_id);
?>

<div class="evr-user-dashboard-container" id="user-dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="user-welcome">
            <h2><?php printf(__('Welcome back, %s!', 'env-voucher-rewards'), wp_get_current_user()->display_name); ?></h2>
            <p><?php _e('Track your environmental impact and rewards progress', 'env-voucher-rewards'); ?></p>
        </div>
        
        <div class="user-tier-display">
            <div class="tier-badge tier-<?php echo esc_attr(strtolower($user_tier['name'])); ?>">
                <span class="tier-icon"><?php echo esc_html($user_tier['icon']); ?></span>
                <span class="tier-name"><?php echo esc_html($user_tier['name']); ?></span>
            </div>
            
            <?php if ($user_tier['next_tier']): ?>
                <div class="tier-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr($user_tier['progress_percent']); ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?php printf(__('%d points to %s', 'env-voucher-rewards'), 
                            $user_tier['points_to_next'], 
                            $user_tier['next_tier']
                        ); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats Cards -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($user_stats['total_points']); ?></div>
                <div class="stat-label"><?php _e('Total Points', 'env-voucher-rewards'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎫</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($user_stats['active_vouchers']); ?></div>
                <div class="stat-label"><?php _e('Active Vouchers', 'env-voucher-rewards'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🌱</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($user_stats['carbon_saved'], 1); ?>kg</div>
                <div class="stat-label"><?php _e('CO₂ Saved', 'env-voucher-rewards'); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">♻️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($user_stats['waste_classified']); ?></div>
                <div class="stat-label"><?php _e('Items Classified', 'env-voucher-rewards'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Main Dashboard Content -->
    <div class="dashboard-content">
        <!-- Left Column -->
        <div class="dashboard-left">
            <!-- Environmental Impact Chart -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3><?php _e('Environmental Impact', 'env-voucher-rewards'); ?></h3>
                    <div class="widget-controls">
                        <select id="impact-period">
                            <option value="7"><?php _e('Last 7 days', 'env-voucher-rewards'); ?></option>
                            <option value="30" selected><?php _e('Last 30 days', 'env-voucher-rewards'); ?></option>
                            <option value="90"><?php _e('Last 3 months', 'env-voucher-rewards'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="widget-content">
                    <canvas id="impact-chart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Points History -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3><?php _e('Points Earned', 'env-voucher-rewards'); ?></h3>
                </div>
                <div class="widget-content">
                    <canvas id="points-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="dashboard-right">
            <!-- Recent Activities -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3><?php _e('Recent Activities', 'env-voucher-rewards'); ?></h3>
                    <a href="<?php echo home_url('/my-activities'); ?>" class="view-all-link">
                        <?php _e('View All', 'env-voucher-rewards'); ?>
                    </a>
                </div>
                <div class="widget-content">
                    <?php if (empty($recent_activities)): ?>
                        <div class="no-activities">
                            <p><?php _e('No recent activities. Start earning points by completing environmental actions!', 'env-voucher-rewards'); ?></p>
                            <a href="<?php echo home_url('/environmental-activities'); ?>" class="btn btn-primary btn-small">
                                <?php _e('Start Activities', 'env-voucher-rewards'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="activities-list">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php echo $activity->type === 'quiz' ? '🧠' : ($activity->type === 'waste' ? '♻️' : '🌱'); ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo esc_html($activity->title); ?></div>
                                        <div class="activity-meta">
                                            <span class="activity-points">+<?php echo esc_html($activity->points); ?> points</span>
                                            <span class="activity-date"><?php echo esc_html(human_time_diff(strtotime($activity->created_at))); ?> ago</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Achievements -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3><?php _e('Recent Achievements', 'env-voucher-rewards'); ?></h3>
                    <a href="<?php echo home_url('/my-achievements'); ?>" class="view-all-link">
                        <?php _e('View All', 'env-voucher-rewards'); ?>
                    </a>
                </div>
                <div class="widget-content">
                    <?php if (empty($achievements)): ?>
                        <div class="no-achievements">
                            <p><?php _e('No achievements yet. Keep up the great environmental work!', 'env-voucher-rewards'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="achievements-grid">
                            <?php foreach (array_slice($achievements, 0, 6) as $achievement): ?>
                                <div class="achievement-badge <?php echo $achievement['earned'] ? 'earned' : 'locked'; ?>">
                                    <div class="achievement-icon"><?php echo esc_html($achievement['icon']); ?></div>
                                    <div class="achievement-name"><?php echo esc_html($achievement['name']); ?></div>
                                    <?php if ($achievement['earned']): ?>
                                        <div class="achievement-date">
                                            <?php echo date('M j', strtotime($achievement['earned_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-actions">
        <h3><?php _e('Quick Actions', 'env-voucher-rewards'); ?></h3>
        <div class="actions-grid">
            <a href="<?php echo home_url('/environmental-quiz'); ?>" class="action-card">
                <div class="action-icon">🧠</div>
                <div class="action-content">
                    <h4><?php _e('Take Quiz', 'env-voucher-rewards'); ?></h4>
                    <p><?php _e('Test your environmental knowledge', 'env-voucher-rewards'); ?></p>
                </div>
                <div class="action-reward">+50-100 pts</div>
            </a>
            
            <a href="<?php echo home_url('/waste-classification'); ?>" class="action-card">
                <div class="action-icon">♻️</div>
                <div class="action-content">
                    <h4><?php _e('Classify Waste', 'env-voucher-rewards'); ?></h4>
                    <p><?php _e('Help sort waste correctly', 'env-voucher-rewards'); ?></p>
                </div>
                <div class="action-reward">+20-30 pts</div>
            </a>
            
            <a href="<?php echo home_url('/carbon-tracker'); ?>" class="action-card">
                <div class="action-icon">🌱</div>
                <div class="action-content">
                    <h4><?php _e('Track Carbon', 'env-voucher-rewards'); ?></h4>
                    <p><?php _e('Log your carbon savings', 'env-voucher-rewards'); ?></p>
                </div>
                <div class="action-reward">+10-50 pts</div>
            </a>
            
            <a href="<?php echo home_url('/reward-center'); ?>" class="action-card">
                <div class="action-icon">🎁</div>
                <div class="action-content">
                    <h4><?php _e('Redeem Rewards', 'env-voucher-rewards'); ?></h4>
                    <p><?php _e('Use points for vouchers', 'env-voucher-rewards'); ?></p>
                </div>
                <div class="action-reward">Spend pts</div>
            </a>
        </div>
    </div>
</div>

<script>
// Initialize dashboard when document is ready
jQuery(document).ready(function($) {
    if (typeof UserDashboard !== 'undefined') {
        UserDashboard.init();
    }
});
</script>
