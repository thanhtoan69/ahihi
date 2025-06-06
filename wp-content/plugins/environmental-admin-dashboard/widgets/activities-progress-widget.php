<?php
/**
 * Activities Progress Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activities Progress Widget Class
 */
class Environmental_Activities_Progress_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_get_activity_details', array($this, 'get_activity_details'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_activities_progress',
            __('Environmental Activities Progress', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $activities_data = $this->get_activities_progress();
        ?>
        <div class="environmental-activities-widget">
            <div class="activities-summary">
                <div class="summary-item">
                    <span class="summary-number"><?php echo $activities_data['total_active']; ?></span>
                    <span class="summary-label"><?php _e('Active Activities', 'environmental-admin-dashboard'); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-number"><?php echo $activities_data['completed_today']; ?></span>
                    <span class="summary-label"><?php _e('Completed Today', 'environmental-admin-dashboard'); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-number"><?php echo number_format($activities_data['avg_participation'], 1); ?>%</span>
                    <span class="summary-label"><?php _e('Avg Participation', 'environmental-admin-dashboard'); ?></span>
                </div>
            </div>
            
            <div class="activities-list">
                <h4><?php _e('Recent Activity Progress', 'environmental-admin-dashboard'); ?></h4>
                <?php if (!empty($activities_data['recent_activities'])): ?>
                    <div class="activity-progress-list">
                        <?php foreach ($activities_data['recent_activities'] as $activity): ?>
                            <div class="activity-item" data-activity-id="<?php echo $activity['id']; ?>">
                                <div class="activity-info">
                                    <div class="activity-title">
                                        <strong><?php echo esc_html($activity['title']); ?></strong>
                                        <span class="activity-category"><?php echo esc_html($activity['category']); ?></span>
                                    </div>
                                    <div class="activity-stats">
                                        <span class="participants"><?php echo $activity['participants']; ?> <?php _e('participants', 'environmental-admin-dashboard'); ?></span>
                                        <span class="completion-rate"><?php echo $activity['completion_rate']; ?>% <?php _e('completion', 'environmental-admin-dashboard'); ?></span>
                                    </div>
                                </div>
                                <div class="activity-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $activity['completion_rate']; ?>%"></div>
                                    </div>
                                    <div class="progress-actions">
                                        <button type="button" class="button-link view-details" data-activity-id="<?php echo $activity['id']; ?>">
                                            <?php _e('View Details', 'environmental-admin-dashboard'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-activities"><?php _e('No recent activities found.', 'environmental-admin-dashboard'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="activities-categories">
                <h4><?php _e('Activity Categories Performance', 'environmental-admin-dashboard'); ?></h4>
                <div class="categories-chart">
                    <canvas id="activities-categories-chart" width="300" height="150"></canvas>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-content-management'); ?>" class="button button-primary">
                    <?php _e('Manage Activities', 'environmental-admin-dashboard'); ?>
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=environmental_activity'); ?>" class="button button-secondary">
                    <?php _e('Create New Activity', 'environmental-admin-dashboard'); ?>
                </a>
            </div>
        </div>
        
        <!-- Activity Details Modal -->
        <div id="activity-details-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Activity Details', 'environmental-admin-dashboard'); ?></h3>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="activity-details-content">
                        <!-- Content loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize categories chart
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('activities-categories-chart').getContext('2d');
                var categoriesData = <?php echo json_encode($this->get_categories_chart_data()); ?>;
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: categoriesData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 10
                                }
                            }
                        }
                    }
                });
            }
            
            // View activity details
            $('.view-details').on('click', function() {
                var activityId = $(this).data('activity-id');
                $('#activity-details-content').html('<div class="loading">Loading...</div>');
                $('#activity-details-modal').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_activity_details',
                        activity_id: activityId,
                        nonce: '<?php echo wp_create_nonce('get_activity_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#activity-details-content').html(response.data.html);
                        } else {
                            $('#activity-details-content').html('<p>Error loading activity details.</p>');
                        }
                    },
                    error: function() {
                        $('#activity-details-content').html('<p>Error loading activity details.</p>');
                    }
                });
            });
            
            // Close modal
            $('.modal-close, .environmental-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#activity-details-modal').hide();
                }
            });
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
                'max_activities' => intval($_POST['max_activities']),
                'show_categories_chart' => isset($_POST['show_categories_chart']) ? 1 : 0,
                'activity_status_filter' => sanitize_text_field($_POST['activity_status_filter'])
            );
            update_option('environmental_activities_widget_options', $options);
        }
        
        $options = get_option('environmental_activities_widget_options', array(
            'max_activities' => 5,
            'show_categories_chart' => 1,
            'activity_status_filter' => 'all'
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Max Activities to Show', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="number" name="max_activities" value="<?php echo $options['max_activities']; ?>" min="1" max="20" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Categories Chart', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_categories_chart" value="1" <?php checked($options['show_categories_chart'], 1); ?> />
                    <label><?php _e('Display activity categories performance chart', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Activity Status Filter', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="activity_status_filter">
                        <option value="all" <?php selected($options['activity_status_filter'], 'all'); ?>><?php _e('All Activities', 'environmental-admin-dashboard'); ?></option>
                        <option value="active" <?php selected($options['activity_status_filter'], 'active'); ?>><?php _e('Active Only', 'environmental-admin-dashboard'); ?></option>
                        <option value="draft" <?php selected($options['activity_status_filter'], 'draft'); ?>><?php _e('Draft Only', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get activities progress data
     */
    private function get_activities_progress() {
        global $wpdb;
        
        $options = get_option('environmental_activities_widget_options', array(
            'max_activities' => 5,
            'activity_status_filter' => 'all'
        ));
        
        // Get total active activities
        $total_active = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities 
            WHERE status = 'active'
        ");
        
        // Get activities completed today
        $completed_today = $wpdb->get_var("
            SELECT COUNT(DISTINCT ua.activity_id) 
            FROM {$wpdb->prefix}environmental_user_activities ua
            WHERE DATE(ua.completed_at) = CURDATE() AND ua.status = 'completed'
        ");
        
        // Calculate average participation rate
        $avg_participation = $wpdb->get_var("
            SELECT AVG(participation_rate) FROM (
                SELECT 
                    a.id,
                    (COUNT(ua.id) / a.max_participants * 100) as participation_rate
                FROM {$wpdb->prefix}environmental_activities a
                LEFT JOIN {$wpdb->prefix}environmental_user_activities ua ON a.id = ua.activity_id
                WHERE a.status = 'active' AND a.max_participants > 0
                GROUP BY a.id
            ) as rates
        ") ?: 0;
        
        // Get recent activities with progress
        $status_filter = '';
        if ($options['activity_status_filter'] !== 'all') {
            $status_filter = $wpdb->prepare(" AND a.status = %s", $options['activity_status_filter']);
        }
        
        $recent_activities = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.id,
                a.title,
                a.category,
                a.max_participants,
                COUNT(ua.id) as participants,
                CASE 
                    WHEN a.max_participants > 0 THEN (COUNT(ua.id) / a.max_participants * 100)
                    ELSE 0
                END as completion_rate
            FROM {$wpdb->prefix}environmental_activities a
            LEFT JOIN {$wpdb->prefix}environmental_user_activities ua ON a.id = ua.activity_id AND ua.status = 'completed'
            WHERE 1=1 {$status_filter}
            GROUP BY a.id
            ORDER BY a.created_at DESC
            LIMIT %d
        ", $options['max_activities']), ARRAY_A);
        
        return array(
            'total_active' => $total_active,
            'completed_today' => $completed_today,
            'avg_participation' => $avg_participation,
            'recent_activities' => $recent_activities
        );
    }
    
    /**
     * Get categories chart data
     */
    private function get_categories_chart_data() {
        global $wpdb;
        
        $categories_data = $wpdb->get_results("
            SELECT 
                a.category,
                COUNT(ua.id) as completions
            FROM {$wpdb->prefix}environmental_activities a
            LEFT JOIN {$wpdb->prefix}environmental_user_activities ua ON a.id = ua.activity_id AND ua.status = 'completed'
            WHERE a.status = 'active'
            GROUP BY a.category
            ORDER BY completions DESC
        ", ARRAY_A);
        
        $labels = array();
        $data = array();
        $colors = array('#2ecc71', '#3498db', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c');
        
        foreach ($categories_data as $index => $category) {
            $labels[] = ucfirst($category['category']);
            $data[] = $category['completions'];
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * AJAX handler for getting activity details
     */
    public function get_activity_details() {
        check_ajax_referer('get_activity_details', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $activity_id = intval($_POST['activity_id']);
        
        global $wpdb;
        $activity = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}environmental_activities WHERE id = %d
        ", $activity_id), ARRAY_A);
        
        if (!$activity) {
            wp_send_json_error(array('message' => __('Activity not found', 'environmental-admin-dashboard')));
        }
        
        // Get participation stats
        $participants = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ua.*,
                u.display_name
            FROM {$wpdb->prefix}environmental_user_activities ua
            JOIN {$wpdb->users} u ON ua.user_id = u.ID
            WHERE ua.activity_id = %d
            ORDER BY ua.created_at DESC
            LIMIT 10
        ", $activity_id), ARRAY_A);
        
        $completion_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_participants,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                AVG(CASE WHEN status = 'completed' THEN impact_score ELSE NULL END) as avg_impact
            FROM {$wpdb->prefix}environmental_user_activities
            WHERE activity_id = %d
        ", $activity_id), ARRAY_A);
        
        ob_start();
        ?>
        <div class="activity-details">
            <div class="activity-header">
                <h4><?php echo esc_html($activity['title']); ?></h4>
                <span class="activity-status status-<?php echo esc_attr($activity['status']); ?>">
                    <?php echo ucfirst($activity['status']); ?>
                </span>
            </div>
            
            <div class="activity-meta">
                <div class="meta-item">
                    <strong><?php _e('Category:', 'environmental-admin-dashboard'); ?></strong>
                    <?php echo esc_html(ucfirst($activity['category'])); ?>
                </div>
                <div class="meta-item">
                    <strong><?php _e('Impact Score:', 'environmental-admin-dashboard'); ?></strong>
                    <?php echo esc_html($activity['impact_score']); ?>
                </div>
                <div class="meta-item">
                    <strong><?php _e('Max Participants:', 'environmental-admin-dashboard'); ?></strong>
                    <?php echo esc_html($activity['max_participants']); ?>
                </div>
                <div class="meta-item">
                    <strong><?php _e('Created:', 'environmental-admin-dashboard'); ?></strong>
                    <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                </div>
            </div>
            
            <div class="activity-description">
                <strong><?php _e('Description:', 'environmental-admin-dashboard'); ?></strong>
                <p><?php echo wp_kses_post($activity['description']); ?></p>
            </div>
            
            <div class="participation-stats">
                <h5><?php _e('Participation Statistics', 'environmental-admin-dashboard'); ?></h5>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $completion_stats['total_participants']; ?></span>
                        <span class="stat-label"><?php _e('Total Participants', 'environmental-admin-dashboard'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $completion_stats['completed_count']; ?></span>
                        <span class="stat-label"><?php _e('Completed', 'environmental-admin-dashboard'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($completion_stats['avg_impact'], 1); ?></span>
                        <span class="stat-label"><?php _e('Avg Impact Score', 'environmental-admin-dashboard'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($participants)): ?>
            <div class="recent-participants">
                <h5><?php _e('Recent Participants', 'environmental-admin-dashboard'); ?></h5>
                <div class="participants-list">
                    <?php foreach ($participants as $participant): ?>
                        <div class="participant-item">
                            <span class="participant-name"><?php echo esc_html($participant['display_name']); ?></span>
                            <span class="participant-status status-<?php echo esc_attr($participant['status']); ?>">
                                <?php echo ucfirst($participant['status']); ?>
                            </span>
                            <span class="participant-date"><?php echo date('M j, Y', strtotime($participant['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="activity-actions">
                <a href="<?php echo admin_url('post.php?post=' . $activity['post_id'] . '&action=edit'); ?>" class="button button-primary">
                    <?php _e('Edit Activity', 'environmental-admin-dashboard'); ?>
                </a>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}

// Initialize the widget
new Environmental_Activities_Progress_Widget();
