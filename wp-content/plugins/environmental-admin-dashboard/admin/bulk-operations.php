<?php
/**
 * Environmental Admin Dashboard - Bulk Operations Interface
 *
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-admin-dashboard'));
}

// Handle bulk operations
if (isset($_POST['bulk_action']) && wp_verify_nonce($_POST['_wpnonce'], 'environmental_bulk_operations')) {
    $action = sanitize_text_field($_POST['bulk_action']);
    $selected_items = isset($_POST['selected_items']) ? array_map('intval', $_POST['selected_items']) : array();
    
    if (!empty($selected_items)) {
        $results = environmental_process_bulk_operation($action, $selected_items);
        
        if ($results['success']) {
            add_action('admin_notices', function() use ($results) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($results['message']) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($results) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($results['message']) . '</p></div>';
            });
        }
    }
}

// Get data for bulk operations
$activities = environmental_get_activities();
$goals = environmental_get_goals();
$users = environmental_get_platform_users();
?>

<div class="wrap environmental-bulk-operations">
    <h1><?php _e('Bulk Operations', 'environmental-admin-dashboard'); ?></h1>
    
    <div class="bulk-operations-container">
        <!-- Activities Bulk Operations -->
        <div class="bulk-section">
            <h2><?php _e('Activities Management', 'environmental-admin-dashboard'); ?></h2>
            
            <form method="post" action="" class="bulk-form">
                <?php wp_nonce_field('environmental_bulk_operations'); ?>
                
                <div class="bulk-controls">
                    <select name="bulk_action" class="bulk-action-select">
                        <option value=""><?php _e('Select Action', 'environmental-admin-dashboard'); ?></option>
                        <option value="activate_activities"><?php _e('Activate Activities', 'environmental-admin-dashboard'); ?></option>
                        <option value="deactivate_activities"><?php _e('Deactivate Activities', 'environmental-admin-dashboard'); ?></option>
                        <option value="delete_activities"><?php _e('Delete Activities', 'environmental-admin-dashboard'); ?></option>
                        <option value="reset_progress"><?php _e('Reset Progress', 'environmental-admin-dashboard'); ?></option>
                        <option value="export_activities"><?php _e('Export Activities', 'environmental-admin-dashboard'); ?></option>
                    </select>
                    
                    <button type="submit" class="button button-primary bulk-apply"><?php _e('Apply', 'environmental-admin-dashboard'); ?></button>
                </div>
                
                <div class="bulk-items-container">
                    <div class="bulk-select-all">
                        <label>
                            <input type="checkbox" class="select-all-checkbox" data-target="activities">
                            <?php _e('Select All Activities', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="bulk-items-list activities-list">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="bulk-item">
                                    <label>
                                        <input type="checkbox" name="selected_items[]" value="<?php echo esc_attr($activity->id); ?>" class="item-checkbox activities-checkbox">
                                        <span class="item-title"><?php echo esc_html($activity->title); ?></span>
                                        <span class="item-status status-<?php echo esc_attr($activity->status); ?>">
                                            <?php echo esc_html(ucfirst($activity->status)); ?>
                                        </span>
                                        <span class="item-meta">
                                            <?php printf(__('Participants: %d', 'environmental-admin-dashboard'), $activity->participant_count); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-items"><?php _e('No activities found.', 'environmental-admin-dashboard'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Goals Bulk Operations -->
        <div class="bulk-section">
            <h2><?php _e('Goals Management', 'environmental-admin-dashboard'); ?></h2>
            
            <form method="post" action="" class="bulk-form">
                <?php wp_nonce_field('environmental_bulk_operations'); ?>
                
                <div class="bulk-controls">
                    <select name="bulk_action" class="bulk-action-select">
                        <option value=""><?php _e('Select Action', 'environmental-admin-dashboard'); ?></option>
                        <option value="activate_goals"><?php _e('Activate Goals', 'environmental-admin-dashboard'); ?></option>
                        <option value="complete_goals"><?php _e('Mark as Complete', 'environmental-admin-dashboard'); ?></option>
                        <option value="reset_goals"><?php _e('Reset Goals', 'environmental-admin-dashboard'); ?></option>
                        <option value="delete_goals"><?php _e('Delete Goals', 'environmental-admin-dashboard'); ?></option>
                        <option value="export_goals"><?php _e('Export Goals', 'environmental-admin-dashboard'); ?></option>
                    </select>
                    
                    <button type="submit" class="button button-primary bulk-apply"><?php _e('Apply', 'environmental-admin-dashboard'); ?></button>
                </div>
                
                <div class="bulk-items-container">
                    <div class="bulk-select-all">
                        <label>
                            <input type="checkbox" class="select-all-checkbox" data-target="goals">
                            <?php _e('Select All Goals', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="bulk-items-list goals-list">
                        <?php if (!empty($goals)): ?>
                            <?php foreach ($goals as $goal): ?>
                                <div class="bulk-item">
                                    <label>
                                        <input type="checkbox" name="selected_items[]" value="<?php echo esc_attr($goal->id); ?>" class="item-checkbox goals-checkbox">
                                        <span class="item-title"><?php echo esc_html($goal->title); ?></span>
                                        <span class="item-progress">
                                            <?php printf(__('%d%% Complete', 'environmental-admin-dashboard'), $goal->progress); ?>
                                        </span>
                                        <span class="item-meta">
                                            <?php printf(__('Target: %s', 'environmental-admin-dashboard'), esc_html($goal->target_date)); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-items"><?php _e('No goals found.', 'environmental-admin-dashboard'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Users Bulk Operations -->
        <div class="bulk-section">
            <h2><?php _e('Users Management', 'environmental-admin-dashboard'); ?></h2>
            
            <form method="post" action="" class="bulk-form">
                <?php wp_nonce_field('environmental_bulk_operations'); ?>
                
                <div class="bulk-controls">
                    <select name="bulk_action" class="bulk-action-select">
                        <option value=""><?php _e('Select Action', 'environmental-admin-dashboard'); ?></option>
                        <option value="send_notification"><?php _e('Send Notification', 'environmental-admin-dashboard'); ?></option>
                        <option value="update_role"><?php _e('Update Role', 'environmental-admin-dashboard'); ?></option>
                        <option value="reset_progress"><?php _e('Reset User Progress', 'environmental-admin-dashboard'); ?></option>
                        <option value="export_users"><?php _e('Export User Data', 'environmental-admin-dashboard'); ?></option>
                    </select>
                    
                    <button type="submit" class="button button-primary bulk-apply"><?php _e('Apply', 'environmental-admin-dashboard'); ?></button>
                </div>
                
                <div class="bulk-items-container">
                    <div class="bulk-select-all">
                        <label>
                            <input type="checkbox" class="select-all-checkbox" data-target="users">
                            <?php _e('Select All Users', 'environmental-admin-dashboard'); ?>
                        </label>
                    </div>
                    
                    <div class="bulk-items-list users-list">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <div class="bulk-item">
                                    <label>
                                        <input type="checkbox" name="selected_items[]" value="<?php echo esc_attr($user->ID); ?>" class="item-checkbox users-checkbox">
                                        <span class="item-title"><?php echo esc_html($user->display_name); ?></span>
                                        <span class="item-email"><?php echo esc_html($user->user_email); ?></span>
                                        <span class="item-meta">
                                            <?php printf(__('Activities: %d', 'environmental-admin-dashboard'), $user->activity_count); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-items"><?php _e('No users found.', 'environmental-admin-dashboard'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Action Modal -->
    <div id="bulk-action-modal" class="bulk-modal" style="display: none;">
        <div class="bulk-modal-content">
            <div class="bulk-modal-header">
                <h3 id="bulk-modal-title"><?php _e('Confirm Bulk Action', 'environmental-admin-dashboard'); ?></h3>
                <button type="button" class="bulk-modal-close">&times;</button>
            </div>
            
            <div class="bulk-modal-body">
                <p id="bulk-modal-message"></p>
                
                <div id="bulk-modal-options" style="display: none;">
                    <!-- Dynamic options based on action -->
                </div>
            </div>
            
            <div class="bulk-modal-footer">
                <button type="button" class="button button-secondary bulk-modal-cancel"><?php _e('Cancel', 'environmental-admin-dashboard'); ?></button>
                <button type="button" class="button button-primary bulk-modal-confirm"><?php _e('Confirm', 'environmental-admin-dashboard'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.environmental-bulk-operations {
    max-width: 1200px;
}

.bulk-operations-container {
    display: grid;
    gap: 30px;
    margin-top: 20px;
}

.bulk-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 6px;
    padding: 20px;
}

.bulk-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e1e1e1;
}

.bulk-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}

.bulk-action-select {
    min-width: 200px;
}

.bulk-items-container {
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    overflow: hidden;
}

.bulk-select-all {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #e1e1e1;
}

.bulk-items-list {
    max-height: 300px;
    overflow-y: auto;
}

.bulk-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.bulk-item:hover {
    background-color: #f8f9fa;
}

.bulk-item:last-child {
    border-bottom: none;
}

.bulk-item label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    margin: 0;
}

.item-title {
    font-weight: 600;
    flex: 1;
}

.item-status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.item-progress,
.item-email,
.item-meta {
    font-size: 12px;
    color: #666;
}

.no-items {
    padding: 20px;
    text-align: center;
    color: #666;
    font-style: italic;
}

/* Modal Styles */
.bulk-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bulk-modal-content {
    background: #fff;
    border-radius: 6px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.bulk-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
}

.bulk-modal-header h3 {
    margin: 0;
}

.bulk-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.bulk-modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.bulk-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e1e1e1;
    background: #f8f9fa;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all functionality
    $('.select-all-checkbox').on('change', function() {
        const target = $(this).data('target');
        const checked = $(this).is(':checked');
        $(`.${target}-checkbox`).prop('checked', checked);
    });
    
    // Individual checkbox change
    $('.item-checkbox').on('change', function() {
        const section = $(this).closest('.bulk-section');
        const allCheckboxes = section.find('.item-checkbox');
        const checkedCheckboxes = section.find('.item-checkbox:checked');
        const selectAllCheckbox = section.find('.select-all-checkbox');
        
        if (checkedCheckboxes.length === 0) {
            selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
        } else {
            selectAllCheckbox.prop('indeterminate', true);
        }
    });
    
    // Bulk form submission with confirmation
    $('.bulk-form').on('submit', function(e) {
        const form = $(this);
        const action = form.find('.bulk-action-select').val();
        const selectedItems = form.find('.item-checkbox:checked');
        
        if (!action) {
            e.preventDefault();
            alert('<?php _e('Please select an action.', 'environmental-admin-dashboard'); ?>');
            return;
        }
        
        if (selectedItems.length === 0) {
            e.preventDefault();
            alert('<?php _e('Please select items to process.', 'environmental-admin-dashboard'); ?>');
            return;
        }
        
        // Show confirmation modal
        e.preventDefault();
        showBulkConfirmationModal(action, selectedItems.length, form);
    });
    
    function showBulkConfirmationModal(action, itemCount, form) {
        const modal = $('#bulk-action-modal');
        const actionText = form.find('.bulk-action-select option:selected').text();
        const message = `<?php _e('Are you sure you want to perform', 'environmental-admin-dashboard'); ?> "${actionText}" <?php _e('on', 'environmental-admin-dashboard'); ?> ${itemCount} <?php _e('items? This action cannot be undone.', 'environmental-admin-dashboard'); ?>`;
        
        $('#bulk-modal-title').text('<?php _e('Confirm Bulk Action', 'environmental-admin-dashboard'); ?>');
        $('#bulk-modal-message').text(message);
        
        modal.show();
        
        // Confirm button click
        $('.bulk-modal-confirm').off('click').on('click', function() {
            modal.hide();
            form.off('submit').submit();
        });
    }
    
    // Modal close functionality
    $('.bulk-modal-close, .bulk-modal-cancel').on('click', function() {
        $('#bulk-action-modal').hide();
    });
    
    // Close modal on outside click
    $(document).on('click', function(e) {
        if ($(e.target).is('.bulk-modal')) {
            $('.bulk-modal').hide();
        }
    });
});
</script>
