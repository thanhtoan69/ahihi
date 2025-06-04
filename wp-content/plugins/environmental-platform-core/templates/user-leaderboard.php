<?php
/**
 * User Leaderboard Template
 * Template for environmental platform user leaderboard
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get leaderboard data
$user_management = new EP_User_Management();

// Get parameters
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'all_time';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : 'green_points';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

$leaderboard_data = $user_management->get_leaderboard_data($period, $category, $limit);
$current_user_rank = $user_management->get_user_rank(get_current_user_id(), $period, $category);
?>

<div class="ep-leaderboard-container">
    
    <!-- Leaderboard Header -->
    <div class="ep-leaderboard-header">
        <h1><?php _e('Environmental Champions Leaderboard', 'environmental-platform-core'); ?></h1>
        <p class="ep-leaderboard-subtitle"><?php _e('See who\'s making the biggest environmental impact in our community!', 'environmental-platform-core'); ?></p>
    </div>

    <!-- Leaderboard Filters -->
    <div class="ep-leaderboard-filters">
        <div class="ep-filter-group">
            <label for="ep-period-filter"><?php _e('Time Period:', 'environmental-platform-core'); ?></label>
            <select id="ep-period-filter" class="ep-filter-select">
                <option value="all_time" <?php selected($period, 'all_time'); ?>><?php _e('All Time', 'environmental-platform-core'); ?></option>
                <option value="this_year" <?php selected($period, 'this_year'); ?>><?php _e('This Year', 'environmental-platform-core'); ?></option>
                <option value="this_month" <?php selected($period, 'this_month'); ?>><?php _e('This Month', 'environmental-platform-core'); ?></option>
                <option value="this_week" <?php selected($period, 'this_week'); ?>><?php _e('This Week', 'environmental-platform-core'); ?></option>
            </select>
        </div>

        <div class="ep-filter-group">
            <label for="ep-category-filter"><?php _e('Category:', 'environmental-platform-core'); ?></label>
            <select id="ep-category-filter" class="ep-filter-select">
                <option value="green_points" <?php selected($category, 'green_points'); ?>><?php _e('Green Points', 'environmental-platform-core'); ?></option>
                <option value="activities" <?php selected($category, 'activities'); ?>><?php _e('Total Activities', 'environmental-platform-core'); ?></option>
                <option value="waste_classified" <?php selected($category, 'waste_classified'); ?>><?php _e('Waste Classified', 'environmental-platform-core'); ?></option>
                <option value="co2_saved" <?php selected($category, 'co2_saved'); ?>><?php _e('CO₂ Saved', 'environmental-platform-core'); ?></option>
                <option value="events_joined" <?php selected($category, 'events_joined'); ?>><?php _e('Events Joined', 'environmental-platform-core'); ?></option>
                <option value="achievements" <?php selected($category, 'achievements'); ?>><?php _e('Achievements', 'environmental-platform-core'); ?></option>
            </select>
        </div>

        <button id="ep-apply-filters" class="ep-btn ep-btn-primary">
            <i class="ep-icon-filter"></i>
            <?php _e('Apply Filters', 'environmental-platform-core'); ?>
        </button>
    </div>

    <!-- Current User Rank (if logged in) -->
    <?php if (is_user_logged_in() && $current_user_rank): ?>
        <div class="ep-current-user-rank">
            <div class="ep-rank-card">
                <h3><?php _e('Your Current Rank', 'environmental-platform-core'); ?></h3>
                <div class="ep-rank-details">
                    <div class="ep-rank-position">
                        <span class="ep-rank-number">#<?php echo number_format_i18n($current_user_rank['rank']); ?></span>
                        <span class="ep-rank-label"><?php _e('Position', 'environmental-platform-core'); ?></span>
                    </div>
                    <div class="ep-rank-score">
                        <span class="ep-score-number"><?php echo number_format_i18n($current_user_rank['score']); ?></span>
                        <span class="ep-score-label"><?php echo esc_html($current_user_rank['score_label']); ?></span>
                    </div>
                    <div class="ep-rank-progress">
                        <?php if ($current_user_rank['next_rank_difference'] > 0): ?>
                            <p><?php printf(
                                __('You need %s more %s to climb to rank #%d', 'environmental-platform-core'),
                                number_format_i18n($current_user_rank['next_rank_difference']),
                                $current_user_rank['score_label'],
                                $current_user_rank['rank'] - 1
                            ); ?></p>
                        <?php else: ?>
                            <p><?php _e('You\'re leading the pack! Keep up the great work!', 'environmental-platform-core'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Top 3 Podium -->
    <?php if (!empty($leaderboard_data) && count($leaderboard_data) >= 3): ?>
        <div class="ep-leaderboard-podium">
            <!-- Second Place -->
            <div class="ep-podium-position ep-second-place">
                <div class="ep-podium-rank">2</div>
                <div class="ep-podium-avatar">
                    <?php echo get_avatar($leaderboard_data[1]['user_id'], 80); ?>
                </div>
                <div class="ep-podium-info">
                    <h3><?php echo esc_html($leaderboard_data[1]['display_name']); ?></h3>
                    <p class="ep-podium-score"><?php echo number_format_i18n($leaderboard_data[1]['score']); ?></p>
                    <p class="ep-podium-level"><?php printf(__('Level %s', 'environmental-platform-core'), $leaderboard_data[1]['current_level']); ?></p>
                </div>
                <div class="ep-podium-medal">
                    <i class="ep-icon-medal-silver"></i>
                </div>
            </div>

            <!-- First Place -->
            <div class="ep-podium-position ep-first-place">
                <div class="ep-podium-rank">1</div>
                <div class="ep-podium-avatar">
                    <?php echo get_avatar($leaderboard_data[0]['user_id'], 100); ?>
                    <div class="ep-champion-crown">
                        <i class="ep-icon-crown"></i>
                    </div>
                </div>
                <div class="ep-podium-info">
                    <h3><?php echo esc_html($leaderboard_data[0]['display_name']); ?></h3>
                    <p class="ep-podium-score"><?php echo number_format_i18n($leaderboard_data[0]['score']); ?></p>
                    <p class="ep-podium-level"><?php printf(__('Level %s', 'environmental-platform-core'), $leaderboard_data[0]['current_level']); ?></p>
                </div>
                <div class="ep-podium-medal">
                    <i class="ep-icon-medal-gold"></i>
                </div>
            </div>

            <!-- Third Place -->
            <div class="ep-podium-position ep-third-place">
                <div class="ep-podium-rank">3</div>
                <div class="ep-podium-avatar">
                    <?php echo get_avatar($leaderboard_data[2]['user_id'], 70); ?>
                </div>
                <div class="ep-podium-info">
                    <h3><?php echo esc_html($leaderboard_data[2]['display_name']); ?></h3>
                    <p class="ep-podium-score"><?php echo number_format_i18n($leaderboard_data[2]['score']); ?></p>
                    <p class="ep-podium-level"><?php printf(__('Level %s', 'environmental-platform-core'), $leaderboard_data[2]['current_level']); ?></p>
                </div>
                <div class="ep-podium-medal">
                    <i class="ep-icon-medal-bronze"></i>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Full Leaderboard Table -->
    <div class="ep-leaderboard-table-container">
        <div class="ep-table-header">
            <h3><?php _e('Complete Rankings', 'environmental-platform-core'); ?></h3>
            <div class="ep-table-stats">
                <span><?php printf(__('Showing %d environmental champions', 'environmental-platform-core'), count($leaderboard_data)); ?></span>
            </div>
        </div>

        <?php if (!empty($leaderboard_data)): ?>
            <div class="ep-leaderboard-table">
                <table class="ep-table">
                    <thead>
                        <tr>
                            <th class="ep-rank-column"><?php _e('Rank', 'environmental-platform-core'); ?></th>
                            <th class="ep-user-column"><?php _e('User', 'environmental-platform-core'); ?></th>
                            <th class="ep-level-column"><?php _e('Level', 'environmental-platform-core'); ?></th>
                            <th class="ep-score-column"><?php _e('Score', 'environmental-platform-core'); ?></th>
                            <th class="ep-achievements-column"><?php _e('Achievements', 'environmental-platform-core'); ?></th>
                            <th class="ep-impact-column"><?php _e('Impact', 'environmental-platform-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard_data as $index => $user): ?>
                            <tr class="ep-leaderboard-row <?php echo (is_user_logged_in() && $user['user_id'] == get_current_user_id()) ? 'ep-current-user' : ''; ?> <?php echo ($index < 3) ? 'ep-top-three' : ''; ?>">
                                <td class="ep-rank-cell">
                                    <div class="ep-rank-display">
                                        <span class="ep-rank-number"><?php echo number_format_i18n($index + 1); ?></span>
                                        <?php if ($index < 3): ?>
                                            <i class="ep-rank-icon <?php echo $index == 0 ? 'ep-icon-medal-gold' : ($index == 1 ? 'ep-icon-medal-silver' : 'ep-icon-medal-bronze'); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="ep-user-cell">
                                    <div class="ep-user-info">
                                        <div class="ep-user-avatar">
                                            <?php echo get_avatar($user['user_id'], 40); ?>
                                        </div>
                                        <div class="ep-user-details">
                                            <h4 class="ep-user-name"><?php echo esc_html($user['display_name']); ?></h4>
                                            <p class="ep-user-location">
                                                <?php if (!empty($user['location'])): ?>
                                                    <i class="ep-icon-location"></i>
                                                    <?php echo esc_html($user['location']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="ep-level-cell">
                                    <div class="ep-level-display">
                                        <span class="ep-level-number"><?php echo esc_html($user['current_level']); ?></span>
                                        <div class="ep-level-progress">
                                            <div class="ep-mini-progress-bar">
                                                <div class="ep-progress-fill" style="width: <?php echo esc_attr($user['level_progress']); ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="ep-score-cell">
                                    <div class="ep-score-display">
                                        <span class="ep-score-number"><?php echo number_format_i18n($user['score']); ?></span>
                                        <span class="ep-score-unit"><?php echo esc_html($user['score_unit'] ?? 'points'); ?></span>
                                    </div>
                                </td>
                                
                                <td class="ep-achievements-cell">
                                    <div class="ep-achievements-display">
                                        <span class="ep-achievement-count"><?php echo number_format_i18n($user['achievement_count']); ?></span>
                                        <div class="ep-recent-achievements">
                                            <?php if (!empty($user['recent_achievements'])): ?>
                                                <?php foreach (array_slice($user['recent_achievements'], 0, 3) as $achievement): ?>
                                                    <i class="<?php echo esc_attr($achievement['icon']); ?>" title="<?php echo esc_attr($achievement['title']); ?>"></i>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="ep-impact-cell">
                                    <div class="ep-impact-summary">
                                        <div class="ep-impact-item">
                                            <i class="ep-icon-co2"></i>
                                            <span><?php echo esc_html($user['co2_saved']); ?>kg</span>
                                        </div>
                                        <div class="ep-impact-item">
                                            <i class="ep-icon-recycle"></i>
                                            <span><?php echo esc_html($user['waste_recycled']); ?>kg</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Load More Button -->
            <?php if (count($leaderboard_data) >= $limit): ?>
                <div class="ep-load-more-container">
                    <button id="ep-load-more-leaderboard" class="ep-btn ep-btn-outline" data-offset="<?php echo esc_attr($limit); ?>">
                        <i class="ep-icon-arrow-down"></i>
                        <?php _e('Load More Users', 'environmental-platform-core'); ?>
                    </button>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- No Data State -->
            <div class="ep-no-leaderboard-data">
                <div class="ep-no-data-icon">
                    <i class="ep-icon-leaderboard"></i>
                </div>
                <h3><?php _e('No Rankings Available', 'environmental-platform-core'); ?></h3>
                <p><?php _e('Be the first to start making an environmental impact! Complete activities to appear on the leaderboard.', 'environmental-platform-core'); ?></p>
                <a href="<?php echo esc_url(home_url('/activities')); ?>" class="ep-btn ep-btn-primary">
                    <?php _e('Start Your Journey', 'environmental-platform-core'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Leaderboard Information -->
    <div class="ep-leaderboard-info">
        <div class="ep-info-cards">
            <div class="ep-info-card">
                <h4><?php _e('How Rankings Work', 'environmental-platform-core'); ?></h4>
                <p><?php _e('Rankings are calculated based on your selected criteria and time period. Participate in activities, classify waste, join events, and earn achievements to climb the leaderboard!', 'environmental-platform-core'); ?></p>
            </div>
            
            <div class="ep-info-card">
                <h4><?php _e('Privacy Settings', 'environmental-platform-core'); ?></h4>
                <p><?php _e('You can control your leaderboard visibility in your profile settings. Choose whether to appear in public rankings while still tracking your personal progress.', 'environmental-platform-core'); ?></p>
            </div>
            
            <div class="ep-info-card">
                <h4><?php _e('Fair Play', 'environmental-platform-core'); ?></h4>
                <p><?php _e('Our platform promotes genuine environmental impact. All activities are verified to ensure fair and meaningful rankings that reflect real contributions to environmental protection.', 'environmental-platform-core'); ?></p>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize leaderboard functionality
    if (typeof EP_UserManagement !== 'undefined') {
        EP_UserManagement.initLeaderboard();
    }
    
    // Handle filter changes
    $('#ep-apply-filters').on('click', function() {
        const period = $('#ep-period-filter').val();
        const category = $('#ep-category-filter').val();
        
        // Update URL with new parameters
        const url = new URL(window.location);
        url.searchParams.set('period', period);
        url.searchParams.set('category', category);
        window.location.href = url.toString();
    });
    
    // Handle load more
    $('#ep-load-more-leaderboard').on('click', function() {
        const button = $(this);
        const offset = parseInt(button.data('offset'));
        
        if (typeof EP_UserManagement !== 'undefined') {
            EP_UserManagement.loadMoreLeaderboard(offset);
        }
    });
});
</script>
