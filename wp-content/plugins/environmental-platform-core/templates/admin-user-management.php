<?php
/**
 * Admin User Management Interface Template
 * 
 * Phase 31: User Management & Authentication - Admin Interface
 * Administrative interface for managing Environmental Platform users
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user and check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-platform-core'));
}

// Get users data
global $wpdb;
$paged = max(1, intval($_GET['paged'] ?? 1));
$per_page = 20;
$offset = ($paged - 1) * $per_page;
$search = sanitize_text_field($_GET['s'] ?? '');
$filter_role = sanitize_text_field($_GET['role'] ?? '');
$filter_level = intval($_GET['level'] ?? 0);

// Build query conditions
$where_conditions = array("wp_users.user_email = ep_users.email");
$params = array();

if (!empty($search)) {
    $where_conditions[] = "(wp_users.display_name LIKE %s OR wp_users.user_email LIKE %s)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($filter_role)) {
    $where_conditions[] = "wp_users.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND meta_value LIKE %s)";
    $params[] = '%' . $filter_role . '%';
}

if ($filter_level > 0) {
    $where_conditions[] = "ep_users.level = %d";
    $params[] = $filter_level;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total users count
$total_query = "SELECT COUNT(*) FROM {$wpdb->users} wp_users 
                INNER JOIN users ep_users ON wp_users.user_email = ep_users.email";
if (!empty($where_conditions)) {
    $total_query .= " WHERE " . $where_clause;
}

$total_users = $wpdb->get_var($wpdb->prepare($total_query, $params));

// Get users for current page
$users_query = "SELECT 
    wp_users.ID as wp_user_id,
    wp_users.user_login,
    wp_users.user_email,
    wp_users.display_name,
    wp_users.user_registered,
    ep_users.user_id as ep_user_id,
    ep_users.green_points,
    ep_users.level,
    ep_users.total_environmental_score,
    ep_users.carbon_footprint_kg,
    ep_users.is_active,
    ep_users.is_verified,
    ep_users.created_at as ep_created_at
FROM {$wpdb->users} wp_users 
INNER JOIN users ep_users ON wp_users.user_email = ep_users.email";

if (!empty($where_conditions)) {
    $users_query .= " WHERE " . $where_clause;
}

$users_query .= " ORDER BY ep_users.created_at DESC LIMIT %d OFFSET %d";
$params[] = $per_page;
$params[] = $offset;

$users = $wpdb->get_results($wpdb->prepare($users_query, $params));

// Get user statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_users,
    AVG(green_points) as avg_points,
    AVG(level) as avg_level,
    SUM(green_points) as total_points
FROM users";

$user_stats = $wpdb->get_row($stats_query);

// Calculate pagination
$total_pages = ceil($total_users / $per_page);
?>

<div class="wrap ep-admin-user-management">
    <h1 class="wp-heading-inline">
        <?php _e('Environmental Platform Users', 'environmental-platform-core'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=ep-user-export'); ?>" class="page-title-action">
        <?php _e('Export Users', 'environmental-platform-core'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- User Statistics Dashboard -->
    <div class="ep-admin-stats-dashboard">
        <div class="ep-stat-cards">
            <div class="ep-stat-card">
                <div class="ep-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="ep-stat-content">
                    <h3><?php echo number_format($user_stats->total_users); ?></h3>
                    <p><?php _e('Total Users', 'environmental-platform-core'); ?></p>
                </div>
            </div>
            
            <div class="ep-stat-card">
                <div class="ep-stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="ep-stat-content">
                    <h3><?php echo number_format($user_stats->active_users); ?></h3>
                    <p><?php _e('Active Users', 'environmental-platform-core'); ?></p>
                </div>
            </div>
            
            <div class="ep-stat-card">
                <div class="ep-stat-icon verified">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="ep-stat-content">
                    <h3><?php echo number_format($user_stats->verified_users); ?></h3>
                    <p><?php _e('Verified Users', 'environmental-platform-core'); ?></p>
                </div>
            </div>
            
            <div class="ep-stat-card">
                <div class="ep-stat-icon points">
                    <i class="fas fa-leaf"></i>
                </div>
                <div class="ep-stat-content">
                    <h3><?php echo number_format($user_stats->total_points); ?></h3>
                    <p><?php _e('Total Green Points', 'environmental-platform-core'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="ep-admin-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="ep-user-management">
            
            <div class="ep-filter-row">
                <div class="ep-filter-group">
                    <label for="user-search"><?php _e('Search Users:', 'environmental-platform-core'); ?></label>
                    <input type="search" 
                           id="user-search" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php _e('Search by name or email...', 'environmental-platform-core'); ?>">
                </div>
                
                <div class="ep-filter-group">
                    <label for="role-filter"><?php _e('User Role:', 'environmental-platform-core'); ?></label>
                    <select id="role-filter" name="role">
                        <option value=""><?php _e('All Roles', 'environmental-platform-core'); ?></option>
                        <option value="eco_user" <?php selected($filter_role, 'eco_user'); ?>><?php _e('Eco User', 'environmental-platform-core'); ?></option>
                        <option value="eco_moderator" <?php selected($filter_role, 'eco_moderator'); ?>><?php _e('Eco Moderator', 'environmental-platform-core'); ?></option>
                        <option value="eco_expert" <?php selected($filter_role, 'eco_expert'); ?>><?php _e('Eco Expert', 'environmental-platform-core'); ?></option>
                        <option value="eco_admin" <?php selected($filter_role, 'eco_admin'); ?>><?php _e('Eco Admin', 'environmental-platform-core'); ?></option>
                    </select>
                </div>
                
                <div class="ep-filter-group">
                    <label for="level-filter"><?php _e('Level:', 'environmental-platform-core'); ?></label>
                    <select id="level-filter" name="level">
                        <option value="0"><?php _e('All Levels', 'environmental-platform-core'); ?></option>
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($filter_level, $i); ?>><?php echo sprintf(__('Level %d', 'environmental-platform-core'), $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="ep-filter-actions">
                    <button type="submit" class="button"><?php _e('Filter', 'environmental-platform-core'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=ep-user-management'); ?>" class="button"><?php _e('Reset', 'environmental-platform-core'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="ep-admin-users-table">
        <table class="wp-list-table widefat fixed striped users">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </th>
                    <th scope="col" class="manage-column column-username"><?php _e('User', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-email"><?php _e('Email', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-role"><?php _e('Role', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-level"><?php _e('Level', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-points"><?php _e('Green Points', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-score"><?php _e('Env. Score', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-registered"><?php _e('Registered', 'environmental-platform-core'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'environmental-platform-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $wp_user = get_userdata($user->wp_user_id);
                        $user_roles = $wp_user->roles;
                        $primary_role = !empty($user_roles) ? $user_roles[0] : 'eco_user';
                        $avatar_url = get_user_meta($user->wp_user_id, 'ep_avatar_url', true);
                        ?>
                        <tr data-user-id="<?php echo esc_attr($user->wp_user_id); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="users[]" value="<?php echo esc_attr($user->wp_user_id); ?>" />
                            </th>
                            
                            <td class="column-username">
                                <div class="ep-user-info">
                                    <div class="ep-user-avatar">
                                        <?php if ($avatar_url): ?>
                                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>" />
                                        <?php else: ?>
                                            <div class="ep-avatar-placeholder">
                                                <?php echo strtoupper(substr($user->display_name, 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ep-user-details">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <div class="ep-user-login"><?php echo esc_html($user->user_login); ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-email">
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            
                            <td class="column-role">
                                <span class="ep-role-badge ep-role-<?php echo esc_attr($primary_role); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('eco_', '', $primary_role))); ?>
                                </span>
                            </td>
                            
                            <td class="column-level">
                                <div class="ep-level-display">
                                    <span class="ep-level-number"><?php echo esc_html($user->level); ?></span>
                                    <div class="ep-level-bar">
                                        <?php
                                        $level_progress = ($user->green_points % 1000) / 10; // Assuming 1000 points per level
                                        ?>
                                        <div class="ep-level-progress" style="width: <?php echo esc_attr($level_progress); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-points">
                                <span class="ep-points-value"><?php echo number_format($user->green_points); ?></span>
                            </td>
                            
                            <td class="column-score">
                                <span class="ep-score-value"><?php echo number_format($user->total_environmental_score); ?></span>
                            </td>
                            
                            <td class="column-status">
                                <div class="ep-status-indicators">
                                    <?php if ($user->is_active): ?>
                                        <span class="ep-status-badge active"><?php _e('Active', 'environmental-platform-core'); ?></span>
                                    <?php else: ?>
                                        <span class="ep-status-badge inactive"><?php _e('Inactive', 'environmental-platform-core'); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user->is_verified): ?>
                                        <span class="ep-status-badge verified"><?php _e('Verified', 'environmental-platform-core'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="column-registered">
                                <?php echo esc_html(mysql2date(get_option('date_format'), $user->user_registered)); ?>
                            </td>
                            
                            <td class="column-actions">
                                <div class="ep-user-actions">
                                    <a href="<?php echo esc_url(admin_url("user-edit.php?user_id={$user->wp_user_id}")); ?>" 
                                       class="button button-small">
                                        <?php _e('Edit', 'environmental-platform-core'); ?>
                                    </a>
                                    
                                    <button type="button" 
                                            class="button button-small ep-view-details" 
                                            data-user-id="<?php echo esc_attr($user->wp_user_id); ?>">
                                        <?php _e('Details', 'environmental-platform-core'); ?>
                                    </button>
                                    
                                    <?php if (!$user->is_verified): ?>
                                        <button type="button" 
                                                class="button button-small ep-verify-user" 
                                                data-user-id="<?php echo esc_attr($user->wp_user_id); ?>">
                                            <?php _e('Verify', 'environmental-platform-core'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="ep-no-users">
                            <?php _e('No users found.', 'environmental-platform-core'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bulk Actions -->
    <div class="ep-bulk-actions">
        <select id="bulk-action-selector">
            <option value=""><?php _e('Bulk Actions', 'environmental-platform-core'); ?></option>
            <option value="activate"><?php _e('Activate Users', 'environmental-platform-core'); ?></option>
            <option value="deactivate"><?php _e('Deactivate Users', 'environmental-platform-core'); ?></option>
            <option value="verify"><?php _e('Verify Users', 'environmental-platform-core'); ?></option>
            <option value="award_points"><?php _e('Award Points', 'environmental-platform-core'); ?></option>
            <option value="send_email"><?php _e('Send Email', 'environmental-platform-core'); ?></option>
            <option value="export"><?php _e('Export Selected', 'environmental-platform-core'); ?></option>
        </select>
        <button type="button" id="doaction" class="button"><?php _e('Apply', 'environmental-platform-core'); ?></button>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="ep-pagination">
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&laquo; ' . __('Previous', 'environmental-platform-core'),
                'next_text' => __('Next', 'environmental-platform-core') . ' &raquo;',
            );
            echo paginate_links($pagination_args);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- User Details Modal -->
<div id="ep-user-details-modal" class="ep-modal" style="display: none;">
    <div class="ep-modal-content">
        <div class="ep-modal-header">
            <h2><?php _e('User Details', 'environmental-platform-core'); ?></h2>
            <button type="button" class="ep-modal-close">&times;</button>
        </div>
        <div class="ep-modal-body">
            <div id="ep-user-details-content">
                <!-- User details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modals -->
<div id="ep-bulk-action-modal" class="ep-modal" style="display: none;">
    <div class="ep-modal-content">
        <div class="ep-modal-header">
            <h2 id="ep-bulk-action-title"><?php _e('Bulk Action', 'environmental-platform-core'); ?></h2>
            <button type="button" class="ep-modal-close">&times;</button>
        </div>
        <div class="ep-modal-body">
            <div id="ep-bulk-action-content">
                <!-- Bulk action form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.ep-admin-user-management {
    max-width: 100%;
}

.ep-admin-stats-dashboard {
    margin: 20px 0;
}

.ep-stat-cards {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.ep-stat-card {
    flex: 1;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2196F3;
    color: white;
    font-size: 20px;
}

.ep-stat-icon.active { background: #4CAF50; }
.ep-stat-icon.verified { background: #FF9800; }
.ep-stat-icon.points { background: #8BC34A; }

.ep-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.ep-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.ep-admin-filters {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.ep-filter-row {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.ep-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.ep-filter-group label {
    font-weight: 600;
    color: #333;
}

.ep-filter-group input,
.ep-filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
}

.ep-filter-actions {
    display: flex;
    gap: 10px;
}

.ep-admin-users-table {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ep-user-avatar img,
.ep-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.ep-avatar-placeholder {
    background: #8BC34A;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.ep-user-details strong {
    display: block;
    color: #333;
}

.ep-user-login {
    font-size: 12px;
    color: #666;
}

.ep-role-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.ep-role-eco_user { background: #E3F2FD; color: #1976D2; }
.ep-role-eco_moderator { background: #FFF3E0; color: #F57C00; }
.ep-role-eco_expert { background: #E8F5E8; color: #388E3C; }
.ep-role-eco_admin { background: #FCE4EC; color: #C2185B; }

.ep-level-display {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-level-number {
    font-weight: bold;
    color: #8BC34A;
}

.ep-level-bar {
    width: 60px;
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
}

.ep-level-progress {
    height: 100%;
    background: #8BC34A;
    transition: width 0.3s ease;
}

.ep-points-value,
.ep-score-value {
    font-weight: bold;
    color: #333;
}

.ep-status-indicators {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ep-status-badge {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
}

.ep-status-badge.active { background: #E8F5E8; color: #388E3C; }
.ep-status-badge.inactive { background: #FFEBEE; color: #D32F2F; }
.ep-status-badge.verified { background: #FFF3E0; color: #F57C00; }

.ep-user-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.ep-bulk-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.ep-pagination {
    text-align: center;
    margin: 20px 0;
}

.ep-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    border: 1px solid #ddd;
    text-decoration: none;
    border-radius: 4px;
}

.ep-pagination .page-numbers.current {
    background: #8BC34A;
    color: white;
    border-color: #8BC34A;
}

.ep-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ep-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.ep-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ep-modal-header h2 {
    margin: 0;
}

.ep-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.ep-modal-body {
    padding: 20px;
}

.ep-no-users {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .ep-stat-cards {
        flex-direction: column;
    }
    
    .ep-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ep-filter-group input,
    .ep-filter-group select {
        min-width: auto;
    }
    
    .ep-user-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="users[]"]').prop('checked', this.checked);
    });
    
    // Handle view user details
    $('.ep-view-details').on('click', function() {
        const userId = $(this).data('user-id');
        loadUserDetails(userId);
    });
    
    // Handle user verification
    $('.ep-verify-user').on('click', function() {
        const userId = $(this).data('user-id');
        verifyUser(userId);
    });
    
    // Handle bulk actions
    $('#doaction').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedUsers = $('input[name="users[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (!action) {
            alert(ep_admin_text.select_action);
            return;
        }
        
        if (selectedUsers.length === 0) {
            alert(ep_admin_text.select_users);
            return;
        }
        
        processBulkAction(action, selectedUsers);
    });
    
    // Handle modal close
    $('.ep-modal-close').on('click', function() {
        $(this).closest('.ep-modal').hide();
    });
    
    // Close modal when clicking outside
    $('.ep-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    function loadUserDetails(userId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_get_user_details',
                user_id: userId,
                nonce: ep_admin_nonce
            },
            beforeSend: function() {
                $('#ep-user-details-content').html('<div class="spinner is-active"></div>');
                $('#ep-user-details-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    $('#ep-user-details-content').html(response.data.html);
                } else {
                    $('#ep-user-details-content').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#ep-user-details-content').html('<p class="error">' + ep_admin_text.error_loading + '</p>');
            }
        });
    }
    
    function verifyUser(userId) {
        if (!confirm(ep_admin_text.confirm_verify)) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_verify_user',
                user_id: userId,
                nonce: ep_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(ep_admin_text.error_general);
            }
        });
    }
    
    function processBulkAction(action, userIds) {
        // Show appropriate modal or confirmation based on action
        switch (action) {
            case 'activate':
            case 'deactivate':
            case 'verify':
                if (confirm(ep_admin_text.confirm_bulk_action)) {
                    executeBulkAction(action, userIds);
                }
                break;
            case 'award_points':
                showAwardPointsModal(userIds);
                break;
            case 'send_email':
                showSendEmailModal(userIds);
                break;
            case 'export':
                exportUsers(userIds);
                break;
        }
    }
    
    function executeBulkAction(action, userIds) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_bulk_user_action',
                bulk_action: action,
                user_ids: userIds,
                nonce: ep_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(ep_admin_text.error_general);
            }
        });
    }
    
    function showAwardPointsModal(userIds) {
        const html = `
            <form id="ep-award-points-form">
                <div class="ep-form-group">
                    <label for="points-amount">${ep_admin_text.points_amount}:</label>
                    <input type="number" id="points-amount" name="points" min="1" max="10000" required>
                </div>
                <div class="ep-form-group">
                    <label for="points-reason">${ep_admin_text.reason}:</label>
                    <textarea id="points-reason" name="reason" rows="3" placeholder="${ep_admin_text.reason_placeholder}"></textarea>
                </div>
                <div class="ep-form-actions">
                    <button type="submit" class="button-primary">${ep_admin_text.award_points}</button>
                    <button type="button" class="button ep-modal-close">${ep_admin_text.cancel}</button>
                </div>
            </form>
        `;
        
        $('#ep-bulk-action-title').text(ep_admin_text.award_points_title);
        $('#ep-bulk-action-content').html(html);
        $('#ep-bulk-action-modal').show();
        
        $('#ep-award-points-form').on('submit', function(e) {
            e.preventDefault();
            const points = $('#points-amount').val();
            const reason = $('#points-reason').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_award_points',
                    user_ids: userIds,
                    points: points,
                    reason: reason,
                    nonce: ep_admin_nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#ep-bulk-action-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(ep_admin_text.error_general);
                }
            });
        });
    }
    
    function showSendEmailModal(userIds) {
        const html = `
            <form id="ep-send-email-form">
                <div class="ep-form-group">
                    <label for="email-subject">${ep_admin_text.email_subject}:</label>
                    <input type="text" id="email-subject" name="subject" required>
                </div>
                <div class="ep-form-group">
                    <label for="email-message">${ep_admin_text.email_message}:</label>
                    <textarea id="email-message" name="message" rows="8" required></textarea>
                </div>
                <div class="ep-form-actions">
                    <button type="submit" class="button-primary">${ep_admin_text.send_email}</button>
                    <button type="button" class="button ep-modal-close">${ep_admin_text.cancel}</button>
                </div>
            </form>
        `;
        
        $('#ep-bulk-action-title').text(ep_admin_text.send_email_title);
        $('#ep-bulk-action-content').html(html);
        $('#ep-bulk-action-modal').show();
        
        $('#ep-send-email-form').on('submit', function(e) {
            e.preventDefault();
            const subject = $('#email-subject').val();
            const message = $('#email-message').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_send_bulk_email',
                    user_ids: userIds,
                    subject: subject,
                    message: message,
                    nonce: ep_admin_nonce
                },
                beforeSend: function() {
                    $('#ep-send-email-form button[type="submit"]').prop('disabled', true).text(ep_admin_text.sending);
                },
                success: function(response) {
                    if (response.success) {
                        $('#ep-bulk-action-modal').hide();
                        alert(ep_admin_text.email_sent);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(ep_admin_text.error_general);
                },
                complete: function() {
                    $('#ep-send-email-form button[type="submit"]').prop('disabled', false).text(ep_admin_text.send_email);
                }
            });
        });
    }
    
    function exportUsers(userIds) {
        const form = $('<form>', {
            method: 'POST',
            action: admin_url + 'admin-post.php'
        });
        
        form.append($('<input>', {type: 'hidden', name: 'action', value: 'ep_export_users'}));
        form.append($('<input>', {type: 'hidden', name: 'user_ids', value: userIds.join(',')}));
        form.append($('<input>', {type: 'hidden', name: 'nonce', value: ep_admin_nonce}));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>
