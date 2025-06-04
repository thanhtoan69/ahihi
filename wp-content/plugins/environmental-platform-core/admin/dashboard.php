<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get database statistics
$db_stats = array(
    'total_tables' => $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'environmental_platform'"),
    'total_users' => $wpdb->get_var("SELECT COUNT(*) FROM users"),
    'total_posts' => $wpdb->get_var("SELECT COUNT(*) FROM posts"),
    'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM events"),
    'total_achievements' => $wpdb->get_var("SELECT COUNT(*) FROM achievements"),
    'total_categories' => $wpdb->get_var("SELECT COUNT(*) FROM categories")
);

// Get recent activity
$recent_users = $wpdb->get_results("SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_posts = $wpdb->get_results("SELECT title, created_at FROM posts ORDER BY created_at DESC LIMIT 5");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ep-admin-header">
        <h2><?php _e('Environmental Platform Dashboard', 'environmental-platform-core'); ?></h2>
        <p><?php _e('Welcome to the Environmental Platform management interface. This dashboard provides an overview of your environmental platform data and statistics.', 'environmental-platform-core'); ?></p>
    </div>
    
    <div class="ep-dashboard-grid">
        <!-- Database Status Card -->
        <div class="ep-card">
            <h3><?php _e('Database Status', 'environmental-platform-core'); ?></h3>
            <div class="ep-status-indicator ep-status-connected">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Connected', 'environmental-platform-core'); ?>
            </div>
            <p><?php printf(__('Database: %s', 'environmental-platform-core'), '<strong>environmental_platform</strong>'); ?></p>
            <p><?php printf(__('Total Tables: %s', 'environmental-platform-core'), '<strong>' . $db_stats['total_tables'] . '</strong>'); ?></p>
        </div>
        
        <!-- Platform Statistics -->
        <div class="ep-card">
            <h3><?php _e('Platform Statistics', 'environmental-platform-core'); ?></h3>
            <div class="ep-stats-grid">
                <div class="ep-stat-item">
                    <span class="ep-stat-number"><?php echo $db_stats['total_users'] ?: '0'; ?></span>
                    <span class="ep-stat-label"><?php _e('Total Users', 'environmental-platform-core'); ?></span>
                </div>
                <div class="ep-stat-item">
                    <span class="ep-stat-number"><?php echo $db_stats['total_posts'] ?: '0'; ?></span>
                    <span class="ep-stat-label"><?php _e('Total Posts', 'environmental-platform-core'); ?></span>
                </div>
                <div class="ep-stat-item">
                    <span class="ep-stat-number"><?php echo $db_stats['total_events'] ?: '0'; ?></span>
                    <span class="ep-stat-label"><?php _e('Total Events', 'environmental-platform-core'); ?></span>
                </div>
                <div class="ep-stat-item">
                    <span class="ep-stat-number"><?php echo $db_stats['total_achievements'] ?: '0'; ?></span>
                    <span class="ep-stat-label"><?php _e('Achievements', 'environmental-platform-core'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="ep-card">
            <h3><?php _e('Recent Users', 'environmental-platform-core'); ?></h3>
            <?php if ($recent_users): ?>
                <ul class="ep-recent-list">
                    <?php foreach ($recent_users as $user): ?>
                        <li>
                            <strong><?php echo esc_html($user->username); ?></strong>
                            <br>
                            <small><?php echo esc_html($user->email); ?></small>
                            <br>
                            <small><?php echo date('M j, Y', strtotime($user->created_at)); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No users found.', 'environmental-platform-core'); ?></p>
            <?php endif; ?>
            <p><a href="<?php echo admin_url('admin.php?page=ep-users'); ?>" class="button"><?php _e('Manage Users', 'environmental-platform-core'); ?></a></p>
        </div>
        
        <!-- Recent Posts -->
        <div class="ep-card">
            <h3><?php _e('Recent Environmental Posts', 'environmental-platform-core'); ?></h3>
            <?php if ($recent_posts): ?>
                <ul class="ep-recent-list">
                    <?php foreach ($recent_posts as $post): ?>
                        <li>
                            <strong><?php echo esc_html($post->title); ?></strong>
                            <br>
                            <small><?php echo date('M j, Y', strtotime($post->created_at)); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No posts found.', 'environmental-platform-core'); ?></p>
            <?php endif; ?>
            <p><a href="<?php echo admin_url('edit.php?post_type=environmental_post'); ?>" class="button"><?php _e('Manage Posts', 'environmental-platform-core'); ?></a></p>
        </div>
        
        <!-- System Information -->
        <div class="ep-card ep-card-full-width">
            <h3><?php _e('System Information', 'environmental-platform-core'); ?></h3>
            <div class="ep-system-info">
                <div class="ep-info-row">
                    <span class="ep-info-label"><?php _e('Plugin Version:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value"><?php echo EP_CORE_VERSION; ?></span>
                </div>
                <div class="ep-info-row">
                    <span class="ep-info-label"><?php _e('WordPress Version:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value"><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div class="ep-info-row">
                    <span class="ep-info-label"><?php _e('PHP Version:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="ep-info-row">
                    <span class="ep-info-label"><?php _e('Database Version:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value"><?php echo $wpdb->get_var("SELECT VERSION()"); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="ep-card ep-card-full-width">
            <h3><?php _e('Quick Actions', 'environmental-platform-core'); ?></h3>
            <div class="ep-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=environmental_post'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Environmental Post', 'environmental-platform-core'); ?>
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=environmental_event'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Add Environmental Event', 'environmental-platform-core'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ep-analytics'); ?>" class="button">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('View Analytics', 'environmental-platform-core'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ep-users'); ?>" class="button">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Manage Users', 'environmental-platform-core'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.ep-admin-header {
    background: #f1f1f1;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.ep-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-card-full-width {
    grid-column: 1 / -1;
}

.ep-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: 600;
}

.ep-status-connected {
    background: #d4edda;
    color: #155724;
}

.ep-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.ep-stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.ep-stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #0073aa;
}

.ep-stat-label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.ep-recent-list {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.ep-recent-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.ep-recent-list li:last-child {
    border-bottom: none;
}

.ep-system-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}

.ep-info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.ep-info-row:last-child {
    border-bottom: none;
}

.ep-info-label {
    font-weight: 600;
}

.ep-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.ep-quick-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
</style>
