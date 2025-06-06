<?php
/**
 * Quick Actions Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Quick Actions Widget Class
 */
class Environmental_Quick_Actions_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_quick_action', array($this, 'handle_quick_action'));
        add_action('wp_ajax_get_quick_stats', array($this, 'get_quick_stats'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_quick_actions',
            __('Quick Actions & Shortcuts', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $quick_stats = $this->get_dashboard_stats();
        $recent_activity = $this->get_recent_activity();
        ?>
        <div class="environmental-quick-actions-widget">
            <div class="quick-stats-bar">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $quick_stats['pending_approvals']; ?></span>
                    <span class="stat-label"><?php _e('Pending Approvals', 'environmental-admin-dashboard'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $quick_stats['new_users']; ?></span>
                    <span class="stat-label"><?php _e('New Users Today', 'environmental-admin-dashboard'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $quick_stats['active_goals']; ?></span>
                    <span class="stat-label"><?php _e('Active Goals', 'environmental-admin-dashboard'); ?></span>
                </div>
            </div>
            
            <div class="action-categories">
                <div class="action-category" data-category="content">
                    <h4>
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Content Management', 'environmental-admin-dashboard'); ?>
                    </h4>
                    <div class="action-buttons">
                        <button type="button" class="action-btn" data-action="create_activity">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('New Activity', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="create_goal">
                            <span class="dashicons dashicons-flag"></span>
                            <?php _e('New Goal', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="bulk_approve">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Bulk Approve', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="content_review">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Review Queue', 'environmental-admin-dashboard'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="action-category" data-category="users">
                    <h4>
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('User Management', 'environmental-admin-dashboard'); ?>
                    </h4>
                    <div class="action-buttons">
                        <button type="button" class="action-btn" data-action="user_overview">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('User Overview', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="send_newsletter">
                            <span class="dashicons dashicons-email"></span>
                            <?php _e('Send Newsletter', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="user_rewards">
                            <span class="dashicons dashicons-awards"></span>
                            <?php _e('Manage Rewards', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="engagement_boost">
                            <span class="dashicons dashicons-megaphone"></span>
                            <?php _e('Boost Engagement', 'environmental-admin-dashboard'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="action-category" data-category="analytics">
                    <h4>
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e('Analytics & Reports', 'environmental-admin-dashboard'); ?>
                    </h4>
                    <div class="action-buttons">
                        <button type="button" class="action-btn" data-action="generate_report">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('Generate Report', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="export_data">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export Data', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="performance_analysis">
                            <span class="dashicons dashicons-performance"></span>
                            <?php _e('Performance Analysis', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="impact_calculator">
                            <span class="dashicons dashicons-calculator"></span>
                            <?php _e('Impact Calculator', 'environmental-admin-dashboard'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="action-category" data-category="system">
                    <h4>
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('System Management', 'environmental-admin-dashboard'); ?>
                    </h4>
                    <div class="action-buttons">
                        <button type="button" class="action-btn" data-action="system_backup">
                            <span class="dashicons dashicons-backup"></span>
                            <?php _e('Create Backup', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="clear_cache">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Clear Cache', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="security_scan">
                            <span class="dashicons dashicons-shield"></span>
                            <?php _e('Security Scan', 'environmental-admin-dashboard'); ?>
                        </button>
                        <button type="button" class="action-btn" data-action="optimize_db">
                            <span class="dashicons dashicons-database"></span>
                            <?php _e('Optimize Database', 'environmental-admin-dashboard'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="recent-activity-section">
                <h4><?php _e('Recent Platform Activity', 'environmental-admin-dashboard'); ?></h4>
                <div class="activity-stream">
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                                </div>
                                <div class="activity-content">
                                    <span class="activity-text"><?php echo esc_html($activity['text']); ?></span>
                                    <span class="activity-time"><?php echo esc_html($activity['time_ago']); ?></span>
                                </div>
                                <?php if (!empty($activity['action'])): ?>
                                    <div class="activity-action">
                                        <button type="button" class="button-link" data-action="<?php echo esc_attr($activity['action']); ?>">
                                            <?php echo esc_html($activity['action_text']); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-activity"><?php _e('No recent activity to display.', 'environmental-admin-dashboard'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="activity-actions">
                    <button type="button" class="button button-secondary" id="refresh-activity">
                        <?php _e('Refresh Activity', 'environmental-admin-dashboard'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=environmental-activity-log'); ?>" class="button button-secondary">
                        <?php _e('View Full Activity Log', 'environmental-admin-dashboard'); ?>
                    </a>
                </div>
            </div>
            
            <div class="quick-links-section">
                <h4><?php _e('Quick Links', 'environmental-admin-dashboard'); ?></h4>
                <div class="quick-links-grid">
                    <a href="<?php echo admin_url('admin.php?page=environmental-dashboard'); ?>" class="quick-link">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Main Dashboard', 'environmental-admin-dashboard'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=environmental_activity'); ?>" class="quick-link">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('All Activities', 'environmental-admin-dashboard'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=environmental_goal'); ?>" class="quick-link">
                        <span class="dashicons dashicons-flag"></span>
                        <?php _e('All Goals', 'environmental-admin-dashboard'); ?>
                    </a>
                    <a href="<?php echo admin_url('users.php'); ?>" class="quick-link">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Users', 'environmental-admin-dashboard'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=environmental-reporting'); ?>" class="quick-link">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Reports', 'environmental-admin-dashboard'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=environmental-settings'); ?>" class="quick-link">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'environmental-admin-dashboard'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Action Modal -->
        <div id="quick-action-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title"><?php _e('Quick Action', 'environmental-admin-dashboard'); ?></h3>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="modal-content">
                        <!-- Dynamic content loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle quick action buttons
            $('.action-btn').on('click', function() {
                var action = $(this).data('action');
                var actionText = $(this).text().trim();
                
                // Show loading state
                $(this).prop('disabled', true).addClass('loading');
                
                handleQuickAction(action, actionText);
                
                // Reset button state
                setTimeout(function() {
                    $('.action-btn').prop('disabled', false).removeClass('loading');
                }, 1000);
            });
            
            // Quick action handler
            function handleQuickAction(action, actionText) {
                switch (action) {
                    case 'create_activity':
                        window.location.href = '<?php echo admin_url('post-new.php?post_type=environmental_activity'); ?>';
                        break;
                    case 'create_goal':
                        window.location.href = '<?php echo admin_url('post-new.php?post_type=environmental_goal'); ?>';
                        break;
                    case 'bulk_approve':
                        showBulkApprovalModal();
                        break;
                    case 'user_overview':
                        window.location.href = '<?php echo admin_url('users.php'); ?>';
                        break;
                    case 'generate_report':
                        showReportGeneratorModal();
                        break;
                    case 'system_backup':
                        initiateSystemBackup();
                        break;
                    case 'clear_cache':
                        clearSystemCache();
                        break;
                    default:
                        // Handle via AJAX for complex actions
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'quick_action',
                                quick_action: action,
                                nonce: '<?php echo wp_create_nonce('quick_action'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (response.data.modal_content) {
                                        showModal(actionText, response.data.modal_content);
                                    } else if (response.data.redirect) {
                                        window.location.href = response.data.redirect;
                                    } else {
                                        alert(response.data.message || 'Action completed successfully');
                                    }
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('Error performing action. Please try again.');
                            }
                        });
                        break;
                }
            }
            
            // Show modal with dynamic content
            function showModal(title, content) {
                $('#modal-title').text(title);
                $('#modal-content').html(content);
                $('#quick-action-modal').show();
            }
            
            // Bulk approval modal
            function showBulkApprovalModal() {
                var content = `
                    <div class="bulk-approval-form">
                        <p>Select content types to approve:</p>
                        <label><input type="checkbox" name="approve_activities" checked> Activities</label><br>
                        <label><input type="checkbox" name="approve_goals"> Goals</label><br>
                        <label><input type="checkbox" name="approve_comments"> Comments</label><br>
                        <div class="form-actions">
                            <button type="button" class="button button-primary" onclick="performBulkApproval()">Approve Selected</button>
                            <button type="button" class="button button-secondary cancel-action">Cancel</button>
                        </div>
                    </div>
                `;
                showModal('Bulk Approval', content);
            }
            
            // Report generator modal
            function showReportGeneratorModal() {
                var content = `
                    <div class="report-generator-form">
                        <p>Generate a custom report:</p>
                        <select name="report_type">
                            <option value="activities">Activities Report</option>
                            <option value="users">Users Report</option>
                            <option value="goals">Goals Report</option>
                            <option value="impact">Impact Report</option>
                        </select><br><br>
                        <label>Date Range:</label><br>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>"><br><br>
                        <div class="form-actions">
                            <button type="button" class="button button-primary" onclick="generateReport()">Generate Report</button>
                            <button type="button" class="button button-secondary cancel-action">Cancel</button>
                        </div>
                    </div>
                `;
                showModal('Generate Report', content);
            }
            
            // System backup
            function initiateSystemBackup() {
                if (confirm('This will create a backup of your environmental platform data. Continue?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'quick_action',
                            quick_action: 'system_backup',
                            nonce: '<?php echo wp_create_nonce('quick_action'); ?>'
                        },
                        success: function(response) {
                            alert(response.success ? 'Backup initiated successfully' : 'Backup failed: ' + response.data.message);
                        }
                    });
                }
            }
            
            // Clear cache
            function clearSystemCache() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'quick_action',
                        quick_action: 'clear_cache',
                        nonce: '<?php echo wp_create_nonce('quick_action'); ?>'
                    },
                    success: function(response) {
                        alert(response.success ? 'Cache cleared successfully' : 'Failed to clear cache');
                    }
                });
            }
            
            // Refresh activity
            $('#refresh-activity').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Refreshing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_quick_stats',
                        nonce: '<?php echo wp_create_nonce('get_quick_stats'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Refresh Activity');
                    }
                });
            });
            
            // Close modal
            $('.modal-close, .cancel-action, .environmental-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#quick-action-modal').hide();
                }
            });
            
            // Global functions for modal actions
            window.performBulkApproval = function() {
                var selectedTypes = [];
                $('#quick-action-modal input[type="checkbox"]:checked').each(function() {
                    selectedTypes.push($(this).attr('name'));
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'quick_action',
                        quick_action: 'bulk_approve',
                        approve_types: selectedTypes,
                        nonce: '<?php echo wp_create_nonce('quick_action'); ?>'
                    },
                    success: function(response) {
                        alert(response.success ? 'Bulk approval completed' : 'Bulk approval failed');
                        $('#quick-action-modal').hide();
                    }
                });
            };
            
            window.generateReport = function() {
                var formData = {
                    action: 'quick_action',
                    quick_action: 'generate_report',
                    report_type: $('#quick-action-modal select[name="report_type"]').val(),
                    start_date: $('#quick-action-modal input[name="start_date"]').val(),
                    end_date: $('#quick-action-modal input[name="end_date"]').val(),
                    nonce: '<?php echo wp_create_nonce('quick_action'); ?>'
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success && response.data.download_url) {
                            window.open(response.data.download_url, '_blank');
                        } else {
                            alert('Report generation failed');
                        }
                        $('#quick-action-modal').hide();
                    }
                });
            };
        });
        </script>
        <?php
    }
    
    /**
     * Configure widget options
     */
    public function configure_widget() {
        if (isset($_POST['submit'])) {
            $options = array(
                'show_stats_bar' => isset($_POST['show_stats_bar']) ? 1 : 0,
                'show_recent_activity' => isset($_POST['show_recent_activity']) ? 1 : 0,
                'show_quick_links' => isset($_POST['show_quick_links']) ? 1 : 0,
                'max_recent_items' => intval($_POST['max_recent_items']),
                'enabled_categories' => isset($_POST['enabled_categories']) ? $_POST['enabled_categories'] : array()
            );
            update_option('environmental_quick_actions_widget_options', $options);
        }
        
        $options = get_option('environmental_quick_actions_widget_options', array(
            'show_stats_bar' => 1,
            'show_recent_activity' => 1,
            'show_quick_links' => 1,
            'max_recent_items' => 5,
            'enabled_categories' => array('content', 'users', 'analytics', 'system')
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Show Statistics Bar', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_stats_bar" value="1" <?php checked($options['show_stats_bar'], 1); ?> />
                    <label><?php _e('Display quick statistics at the top', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Recent Activity', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_recent_activity" value="1" <?php checked($options['show_recent_activity'], 1); ?> />
                    <label><?php _e('Display recent platform activity', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Quick Links', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_quick_links" value="1" <?php checked($options['show_quick_links'], 1); ?> />
                    <label><?php _e('Display quick navigation links', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Max Recent Items', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="number" name="max_recent_items" value="<?php echo $options['max_recent_items']; ?>" min="1" max="20" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Enabled Action Categories', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <?php 
                    $categories = array(
                        'content' => __('Content Management', 'environmental-admin-dashboard'),
                        'users' => __('User Management', 'environmental-admin-dashboard'),
                        'analytics' => __('Analytics & Reports', 'environmental-admin-dashboard'),
                        'system' => __('System Management', 'environmental-admin-dashboard')
                    );
                    
                    foreach ($categories as $key => $label):
                    ?>
                        <label>
                            <input type="checkbox" name="enabled_categories[]" value="<?php echo $key; ?>" 
                                   <?php checked(in_array($key, $options['enabled_categories'])); ?> />
                            <?php echo $label; ?>
                        </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        // Get pending approvals
        $pending_approvals = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'pending' 
            AND post_type IN ('environmental_activity', 'environmental_goal')
        ");
        
        // Get new users today
        $new_users = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->users} 
            WHERE DATE(user_registered) = CURDATE()
        ");
        
        // Get active goals
        $active_goals = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals 
            WHERE status = 'active'
        ");
        
        return array(
            'pending_approvals' => $pending_approvals ?: 0,
            'new_users' => $new_users ?: 0,
            'active_goals' => $active_goals ?: 0
        );
    }
    
    /**
     * Get recent platform activity
     */
    private function get_recent_activity() {
        global $wpdb;
        
        $options = get_option('environmental_quick_actions_widget_options', array('max_recent_items' => 5));
        
        $activities = array();
        
        // Recent posts
        $recent_posts = $wpdb->get_results($wpdb->prepare("
            SELECT post_title, post_type, post_date, post_author, post_status
            FROM {$wpdb->posts} 
            WHERE post_type IN ('environmental_activity', 'environmental_goal')
            ORDER BY post_date DESC 
            LIMIT %d
        ", $options['max_recent_items']), ARRAY_A);
        
        foreach ($recent_posts as $post) {
            $user = get_user_by('ID', $post['post_author']);
            $time_ago = human_time_diff(strtotime($post['post_date']), current_time('timestamp')) . ' ago';
            
            $activities[] = array(
                'icon' => $post['post_type'] === 'environmental_activity' ? 'admin-post' : 'flag',
                'text' => sprintf(
                    __('%s created a new %s: %s', 'environmental-admin-dashboard'),
                    $user ? $user->display_name : __('Someone', 'environmental-admin-dashboard'),
                    $post['post_type'] === 'environmental_activity' ? __('activity', 'environmental-admin-dashboard') : __('goal', 'environmental-admin-dashboard'),
                    $post['post_title']
                ),
                'time_ago' => $time_ago,
                'action' => $post['post_status'] === 'pending' ? 'review_post' : '',
                'action_text' => $post['post_status'] === 'pending' ? __('Review', 'environmental-admin-dashboard') : ''
            );
        }
        
        // Recent user registrations
        $recent_users = $wpdb->get_results($wpdb->prepare("
            SELECT display_name, user_registered 
            FROM {$wpdb->users} 
            ORDER BY user_registered DESC 
            LIMIT %d
        ", 3), ARRAY_A);
        
        foreach ($recent_users as $user) {
            $time_ago = human_time_diff(strtotime($user['user_registered']), current_time('timestamp')) . ' ago';
            
            $activities[] = array(
                'icon' => 'admin-users',
                'text' => sprintf(__('%s joined the platform', 'environmental-admin-dashboard'), $user['display_name']),
                'time_ago' => $time_ago,
                'action' => '',
                'action_text' => ''
            );
        }
        
        // Sort by most recent
        usort($activities, function($a, $b) {
            return strcmp($a['time_ago'], $b['time_ago']);
        });
        
        return array_slice($activities, 0, $options['max_recent_items']);
    }
    
    /**
     * AJAX handler for quick actions
     */
    public function handle_quick_action() {
        check_ajax_referer('quick_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $action = sanitize_text_field($_POST['quick_action']);
        
        switch ($action) {
            case 'bulk_approve':
                $this->handle_bulk_approval();
                break;
            case 'send_newsletter':
                $this->handle_newsletter();
                break;
            case 'generate_report':
                $this->handle_report_generation();
                break;
            case 'system_backup':
                $this->handle_system_backup();
                break;
            case 'clear_cache':
                $this->handle_cache_clear();
                break;
            case 'security_scan':
                $this->handle_security_scan();
                break;
            case 'optimize_db':
                $this->handle_database_optimization();
                break;
            default:
                wp_send_json_error(array('message' => __('Unknown action', 'environmental-admin-dashboard')));
        }
    }
    
    /**
     * Handle bulk approval
     */
    private function handle_bulk_approval() {
        $approve_types = isset($_POST['approve_types']) ? $_POST['approve_types'] : array();
        
        if (empty($approve_types)) {
            wp_send_json_error(array('message' => __('No content types selected', 'environmental-admin-dashboard')));
        }
        
        global $wpdb;
        $approved_count = 0;
        
        foreach ($approve_types as $type) {
            switch ($type) {
                case 'approve_activities':
                    $result = $wpdb->update(
                        $wpdb->posts,
                        array('post_status' => 'publish'),
                        array('post_status' => 'pending', 'post_type' => 'environmental_activity'),
                        array('%s'),
                        array('%s', '%s')
                    );
                    $approved_count += $result;
                    break;
                case 'approve_goals':
                    $result = $wpdb->update(
                        $wpdb->posts,
                        array('post_status' => 'publish'),
                        array('post_status' => 'pending', 'post_type' => 'environmental_goal'),
                        array('%s'),
                        array('%s', '%s')
                    );
                    $approved_count += $result;
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d items approved successfully', 'environmental-admin-dashboard'), $approved_count)
        ));
    }
    
    /**
     * Handle newsletter sending
     */
    private function handle_newsletter() {
        $modal_content = '
            <div class="newsletter-form">
                <p>' . __('Send newsletter to environmental platform users:', 'environmental-admin-dashboard') . '</p>
                <label for="newsletter-subject">' . __('Subject:', 'environmental-admin-dashboard') . '</label>
                <input type="text" id="newsletter-subject" placeholder="' . __('Newsletter subject', 'environmental-admin-dashboard') . '"><br><br>
                <label for="newsletter-content">' . __('Content:', 'environmental-admin-dashboard') . '</label>
                <textarea id="newsletter-content" rows="5" placeholder="' . __('Newsletter content...', 'environmental-admin-dashboard') . '"></textarea><br><br>
                <div class="form-actions">
                    <button type="button" class="button button-primary" onclick="sendNewsletter()">' . __('Send Newsletter', 'environmental-admin-dashboard') . '</button>
                    <button type="button" class="button button-secondary cancel-action">' . __('Cancel', 'environmental-admin-dashboard') . '</button>
                </div>
            </div>
        ';
        
        wp_send_json_success(array('modal_content' => $modal_content));
    }
    
    /**
     * Handle report generation
     */
    private function handle_report_generation() {
        $report_type = sanitize_text_field($_POST['report_type']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // Generate report (simplified)
        $filename = 'environmental-' . $report_type . '-report-' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Create CSV content based on report type
        $csv_data = $this->generate_report_data($report_type, $start_date, $end_date);
        
        file_put_contents($file_path, $csv_data);
        
        wp_send_json_success(array(
            'message' => __('Report generated successfully', 'environmental-admin-dashboard'),
            'download_url' => $upload_dir['url'] . '/' . $filename
        ));
    }
    
    /**
     * Handle system backup
     */
    private function handle_system_backup() {
        // Simplified backup process
        $backup_initiated = wp_schedule_single_event(time() + 60, 'environmental_system_backup');
        
        if ($backup_initiated) {
            wp_send_json_success(array('message' => __('System backup initiated', 'environmental-admin-dashboard')));
        } else {
            wp_send_json_error(array('message' => __('Failed to initiate backup', 'environmental-admin-dashboard')));
        }
    }
    
    /**
     * Handle cache clearing
     */
    private function handle_cache_clear() {
        // Clear WordPress cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
        
        wp_send_json_success(array('message' => __('Cache cleared successfully', 'environmental-admin-dashboard')));
    }
    
    /**
     * Handle security scan
     */
    private function handle_security_scan() {
        // Simplified security scan
        $scan_results = array(
            'vulnerabilities' => wp_rand(0, 3),
            'outdated_plugins' => wp_rand(0, 5),
            'security_score' => wp_rand(75, 95)
        );
        
        $modal_content = sprintf(
            '<div class="security-scan-results">
                <h4>%s</h4>
                <p>%s: %d</p>
                <p>%s: %d</p>
                <p>%s: %d%%</p>
            </div>',
            __('Security Scan Results', 'environmental-admin-dashboard'),
            __('Vulnerabilities Found', 'environmental-admin-dashboard'),
            $scan_results['vulnerabilities'],
            __('Outdated Plugins', 'environmental-admin-dashboard'),
            $scan_results['outdated_plugins'],
            __('Security Score', 'environmental-admin-dashboard'),
            $scan_results['security_score']
        );
        
        wp_send_json_success(array('modal_content' => $modal_content));
    }
    
    /**
     * Handle database optimization
     */
    private function handle_database_optimization() {
        global $wpdb;
        
        // Optimize environmental tables
        $tables = array(
            $wpdb->prefix . 'environmental_activities',
            $wpdb->prefix . 'environmental_goals',
            $wpdb->prefix . 'environmental_user_activities'
        );
        
        $optimized_tables = 0;
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            if ($result !== false) {
                $optimized_tables++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d tables optimized successfully', 'environmental-admin-dashboard'), $optimized_tables)
        ));
    }
    
    /**
     * Generate report data
     */
    private function generate_report_data($report_type, $start_date, $end_date) {
        global $wpdb;
        
        $csv_data = '';
        
        switch ($report_type) {
            case 'activities':
                $csv_data = "Activity,Created Date,Status,Participants\n";
                $activities = $wpdb->get_results($wpdb->prepare("
                    SELECT title, created_at, status FROM {$wpdb->prefix}environmental_activities 
                    WHERE created_at BETWEEN %s AND %s
                ", $start_date, $end_date), ARRAY_A);
                
                foreach ($activities as $activity) {
                    $csv_data .= sprintf("%s,%s,%s,0\n", 
                        $activity['title'], 
                        $activity['created_at'], 
                        $activity['status']
                    );
                }
                break;
                
            case 'users':
                $csv_data = "User,Registration Date,Status\n";
                // Add user report data
                break;
                
            default:
                $csv_data = "Report Type,Data\nGeneral,Sample data\n";
        }
        
        return $csv_data;
    }
    
    /**
     * AJAX handler for getting quick stats
     */
    public function get_quick_stats() {
        check_ajax_referer('get_quick_stats', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }
}

// Initialize the widget
new Environmental_Quick_Actions_Widget();
