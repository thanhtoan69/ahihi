<?php
/**
 * Environmental Goals Dashboard Widget
 *
 * @package Environmental_Admin_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Goals Widget Class
 */
class Environmental_Goals_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_update_goal_status', array($this, 'update_goal_status'));
        add_action('wp_ajax_get_goal_progress', array($this, 'get_goal_progress'));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'environmental_goals',
            __('Environmental Goals Tracker', 'environmental-admin-dashboard'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }
    
    /**
     * Render widget content
     */
    public function render_widget() {
        $goals_data = $this->get_goals_data();
        ?>
        <div class="environmental-goals-widget">
            <div class="goals-summary">
                <div class="summary-stats">
                    <div class="stat-item active-goals">
                        <span class="stat-icon dashicons dashicons-flag"></span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo $goals_data['total_active']; ?></span>
                            <span class="stat-label"><?php _e('Active Goals', 'environmental-admin-dashboard'); ?></span>
                        </div>
                    </div>
                    <div class="stat-item completed-goals">
                        <span class="stat-icon dashicons dashicons-yes"></span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo $goals_data['total_completed']; ?></span>
                            <span class="stat-label"><?php _e('Completed', 'environmental-admin-dashboard'); ?></span>
                        </div>
                    </div>
                    <div class="stat-item progress-rate">
                        <span class="stat-icon dashicons dashicons-chart-line"></span>
                        <div class="stat-content">
                            <span class="stat-number"><?php echo number_format($goals_data['avg_progress'], 1); ?>%</span>
                            <span class="stat-label"><?php _e('Avg Progress', 'environmental-admin-dashboard'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="goals-list">
                <div class="goals-header">
                    <h4><?php _e('Current Active Goals', 'environmental-admin-dashboard'); ?></h4>
                    <div class="goals-filter">
                        <select id="goals-category-filter">
                            <option value="all"><?php _e('All Categories', 'environmental-admin-dashboard'); ?></option>
                            <option value="carbon"><?php _e('Carbon Reduction', 'environmental-admin-dashboard'); ?></option>
                            <option value="waste"><?php _e('Waste Management', 'environmental-admin-dashboard'); ?></option>
                            <option value="energy"><?php _e('Energy Efficiency', 'environmental-admin-dashboard'); ?></option>
                            <option value="water"><?php _e('Water Conservation', 'environmental-admin-dashboard'); ?></option>
                            <option value="biodiversity"><?php _e('Biodiversity', 'environmental-admin-dashboard'); ?></option>
                        </select>
                    </div>
                </div>
                
                <?php if (!empty($goals_data['active_goals'])): ?>
                    <div class="goals-grid">
                        <?php foreach ($goals_data['active_goals'] as $goal): ?>
                            <div class="goal-item" data-goal-id="<?php echo $goal['id']; ?>" data-category="<?php echo esc_attr($goal['category']); ?>">
                                <div class="goal-header">
                                    <div class="goal-title">
                                        <h5><?php echo esc_html($goal['title']); ?></h5>
                                        <span class="goal-category category-<?php echo esc_attr($goal['category']); ?>">
                                            <?php echo esc_html(ucfirst($goal['category'])); ?>
                                        </span>
                                    </div>
                                    <div class="goal-actions">
                                        <button type="button" class="button-link view-goal-details" data-goal-id="<?php echo $goal['id']; ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="goal-progress">
                                    <div class="progress-info">
                                        <span class="progress-text"><?php echo $goal['current_value']; ?> / <?php echo $goal['target_value']; ?> <?php echo esc_html($goal['unit']); ?></span>
                                        <span class="progress-percentage"><?php echo number_format($goal['progress_percentage'], 1); ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, $goal['progress_percentage']); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="goal-meta">
                                    <div class="goal-deadline">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php 
                                        $deadline = strtotime($goal['deadline']);
                                        $days_left = ceil(($deadline - time()) / (24 * 60 * 60));
                                        ?>
                                        <span class="deadline-text <?php echo $days_left < 7 ? 'urgent' : ($days_left < 30 ? 'warning' : ''); ?>">
                                            <?php 
                                            if ($days_left > 0) {
                                                printf(__('%d days left', 'environmental-admin-dashboard'), $days_left);
                                            } else {
                                                _e('Overdue', 'environmental-admin-dashboard');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="goal-participants">
                                        <span class="dashicons dashicons-groups"></span>
                                        <span><?php echo $goal['participants']; ?> <?php _e('participating', 'environmental-admin-dashboard'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="goal-quick-actions">
                                    <button type="button" class="button button-small update-progress" data-goal-id="<?php echo $goal['id']; ?>">
                                        <?php _e('Update Progress', 'environmental-admin-dashboard'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-goals">
                        <p><?php _e('No active goals found.', 'environmental-admin-dashboard'); ?></p>
                        <a href="<?php echo admin_url('post-new.php?post_type=environmental_goal'); ?>" class="button button-primary">
                            <?php _e('Create Your First Goal', 'environmental-admin-dashboard'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="goals-chart-container">
                <h4><?php _e('Goals Progress Overview', 'environmental-admin-dashboard'); ?></h4>
                <canvas id="goals-progress-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('edit.php?post_type=environmental_goal'); ?>" class="button button-primary">
                    <?php _e('Manage All Goals', 'environmental-admin-dashboard'); ?>
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=environmental_goal'); ?>" class="button button-secondary">
                    <?php _e('Create New Goal', 'environmental-admin-dashboard'); ?>
                </a>
            </div>
        </div>
        
        <!-- Goal Update Modal -->
        <div id="goal-update-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Update Goal Progress', 'environmental-admin-dashboard'); ?></h3>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="goal-update-form">
                        <div class="form-field">
                            <label for="goal-current-value"><?php _e('Current Progress Value:', 'environmental-admin-dashboard'); ?></label>
                            <input type="number" id="goal-current-value" name="current_value" step="0.01" required />
                        </div>
                        <div class="form-field">
                            <label for="goal-update-note"><?php _e('Progress Note (Optional):', 'environmental-admin-dashboard'); ?></label>
                            <textarea id="goal-update-note" name="progress_note" rows="3"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php _e('Update Progress', 'environmental-admin-dashboard'); ?></button>
                            <button type="button" class="button button-secondary cancel-update"><?php _e('Cancel', 'environmental-admin-dashboard'); ?></button>
                        </div>
                        <input type="hidden" id="goal-update-id" name="goal_id" />
                    </form>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize progress chart
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('goals-progress-chart').getContext('2d');
                var chartData = <?php echo json_encode($this->get_progress_chart_data()); ?>;
                
                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            
            // Category filter
            $('#goals-category-filter').on('change', function() {
                var selectedCategory = $(this).val();
                $('.goal-item').each(function() {
                    var goalCategory = $(this).data('category');
                    if (selectedCategory === 'all' || goalCategory === selectedCategory) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Update progress modal
            $('.update-progress').on('click', function() {
                var goalId = $(this).data('goal-id');
                $('#goal-update-id').val(goalId);
                $('#goal-update-modal').show();
            });
            
            // Submit progress update
            $('#goal-update-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'update_goal_status',
                    goal_id: $('#goal-update-id').val(),
                    current_value: $('#goal-current-value').val(),
                    progress_note: $('#goal-update-note').val(),
                    nonce: '<?php echo wp_create_nonce('update_goal_status'); ?>'
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating goal progress: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error updating goal progress. Please try again.');
                    }
                });
            });
            
            // Close modal
            $('.modal-close, .cancel-update, .environmental-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#goal-update-modal').hide();
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
                'max_goals' => intval($_POST['max_goals']),
                'show_chart' => isset($_POST['show_chart']) ? 1 : 0,
                'default_category' => sanitize_text_field($_POST['default_category'])
            );
            update_option('environmental_goals_widget_options', $options);
        }
        
        $options = get_option('environmental_goals_widget_options', array(
            'max_goals' => 6,
            'show_chart' => 1,
            'default_category' => 'all'
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Max Goals to Display', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="number" name="max_goals" value="<?php echo $options['max_goals']; ?>" min="1" max="20" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Show Progress Chart', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <input type="checkbox" name="show_chart" value="1" <?php checked($options['show_chart'], 1); ?> />
                    <label><?php _e('Display goals progress chart', 'environmental-admin-dashboard'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Default Category Filter', 'environmental-admin-dashboard'); ?></th>
                <td>
                    <select name="default_category">
                        <option value="all" <?php selected($options['default_category'], 'all'); ?>><?php _e('All Categories', 'environmental-admin-dashboard'); ?></option>
                        <option value="carbon" <?php selected($options['default_category'], 'carbon'); ?>><?php _e('Carbon Reduction', 'environmental-admin-dashboard'); ?></option>
                        <option value="waste" <?php selected($options['default_category'], 'waste'); ?>><?php _e('Waste Management', 'environmental-admin-dashboard'); ?></option>
                        <option value="energy" <?php selected($options['default_category'], 'energy'); ?>><?php _e('Energy Efficiency', 'environmental-admin-dashboard'); ?></option>
                        <option value="water" <?php selected($options['default_category'], 'water'); ?>><?php _e('Water Conservation', 'environmental-admin-dashboard'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Get goals data
     */
    private function get_goals_data() {
        global $wpdb;
        
        $options = get_option('environmental_goals_widget_options', array('max_goals' => 6));
        
        // Get totals
        $total_active = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals WHERE status = 'active'");
        $total_completed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}environmental_goals WHERE status = 'completed'");
        
        // Calculate average progress
        $avg_progress = $wpdb->get_var("
            SELECT AVG(CASE 
                WHEN target_value > 0 THEN (current_value / target_value * 100)
                ELSE 0 
            END)
            FROM {$wpdb->prefix}environmental_goals 
            WHERE status = 'active'
        ") ?: 0;
        
        // Get active goals with progress
        $active_goals = $wpdb->get_results($wpdb->prepare("
            SELECT 
                g.*,
                CASE 
                    WHEN g.target_value > 0 THEN (g.current_value / g.target_value * 100)
                    ELSE 0 
                END as progress_percentage,
                COUNT(DISTINCT ug.user_id) as participants
            FROM {$wpdb->prefix}environmental_goals g
            LEFT JOIN {$wpdb->prefix}environmental_user_goals ug ON g.id = ug.goal_id
            WHERE g.status = 'active'
            GROUP BY g.id
            ORDER BY g.created_at DESC
            LIMIT %d
        ", $options['max_goals']), ARRAY_A);
        
        return array(
            'total_active' => $total_active,
            'total_completed' => $total_completed,
            'avg_progress' => $avg_progress,
            'active_goals' => $active_goals
        );
    }
    
    /**
     * Get progress chart data
     */
    private function get_progress_chart_data() {
        global $wpdb;
        
        $chart_data = $wpdb->get_results("
            SELECT 
                g.title,
                CASE 
                    WHEN g.target_value > 0 THEN (g.current_value / g.target_value * 100)
                    ELSE 0 
                END as progress_percentage
            FROM {$wpdb->prefix}environmental_goals g
            WHERE g.status = 'active'
            ORDER BY progress_percentage DESC
            LIMIT 8
        ", ARRAY_A);
        
        $labels = array();
        $data = array();
        $colors = array();
        
        foreach ($chart_data as $goal) {
            $labels[] = strlen($goal['title']) > 20 ? substr($goal['title'], 0, 20) . '...' : $goal['title'];
            $data[] = round($goal['progress_percentage'], 1);
            
            // Color based on progress
            if ($goal['progress_percentage'] >= 80) {
                $colors[] = '#2ecc71'; // Green
            } elseif ($goal['progress_percentage'] >= 50) {
                $colors[] = '#f39c12'; // Orange
            } else {
                $colors[] = '#e74c3c'; // Red
            }
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Progress %', 'environmental-admin-dashboard'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1
                )
            )
        );
    }
    
    /**
     * AJAX handler for updating goal status
     */
    public function update_goal_status() {
        check_ajax_referer('update_goal_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $goal_id = intval($_POST['goal_id']);
        $current_value = floatval($_POST['current_value']);
        $progress_note = sanitize_textarea_field($_POST['progress_note']);
        
        global $wpdb;
        
        // Update goal progress
        $updated = $wpdb->update(
            $wpdb->prefix . 'environmental_goals',
            array(
                'current_value' => $current_value,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $goal_id),
            array('%f', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            wp_send_json_error(array('message' => __('Failed to update goal progress', 'environmental-admin-dashboard')));
        }
        
        // Log progress update
        if (!empty($progress_note)) {
            $wpdb->insert(
                $wpdb->prefix . 'environmental_goal_progress_log',
                array(
                    'goal_id' => $goal_id,
                    'user_id' => get_current_user_id(),
                    'previous_value' => 0, // Would need to track this
                    'new_value' => $current_value,
                    'note' => $progress_note,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%f', '%f', '%s', '%s')
            );
        }
        
        wp_send_json_success(array('message' => __('Goal progress updated successfully', 'environmental-admin-dashboard')));
    }
    
    /**
     * AJAX handler for getting goal progress details
     */
    public function get_goal_progress() {
        check_ajax_referer('get_goal_progress', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $goal_id = intval($_POST['goal_id']);
        
        global $wpdb;
        $progress_data = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}environmental_goal_progress_log
            WHERE goal_id = %d
            ORDER BY created_at DESC
            LIMIT 10
        ", $goal_id), ARRAY_A);
        
        wp_send_json_success(array('progress_data' => $progress_data));
    }
}

// Initialize the widget
new Environmental_Goals_Widget();
