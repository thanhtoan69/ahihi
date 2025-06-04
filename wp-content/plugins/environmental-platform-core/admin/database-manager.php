<?php
/**
 * Database Manager Admin Page Template
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

$db_manager = EP_Database_Manager::get_instance();
$connected_tables = $db_manager->get_table_mapping();
$sync_stats = $db_manager->get_sync_stats();
?>

<div class="wrap">
    <h1><?php _e('Database Manager', 'environmental-platform-core'); ?></h1>
    
    <div class="ep-admin-dashboard">
        <!-- Database Status -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Database Connection Status', 'environmental-platform-core'); ?></h2>
            <div class="ep-status-grid">
                <div class="ep-status-card">
                    <h3><?php _e('WordPress Database', 'environmental-platform-core'); ?></h3>
                    <div class="status-indicator status-connected">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Connected', 'environmental-platform-core'); ?>
                    </div>
                </div>
                <div class="ep-status-card">
                    <h3><?php _e('Environmental Platform Database', 'environmental-platform-core'); ?></h3>
                    <div class="status-indicator status-connected">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Connected', 'environmental-platform-core'); ?>
                    </div>
                    <p><?php printf(__('%d tables mapped', 'environmental-platform-core'), count($connected_tables)); ?></p>
                </div>
            </div>
        </div>

        <!-- Table Mapping -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Table Mapping Overview', 'environmental-platform-core'); ?></h2>
            <div class="ep-table-mapping">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('WordPress Table', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Environmental Platform Table', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Records', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Last Sync', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Actions', 'environmental-platform-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connected_tables as $wp_table => $ep_table): ?>
                        <tr>
                            <td><strong><?php echo esc_html($wp_table); ?></strong></td>
                            <td><?php echo esc_html($ep_table); ?></td>
                            <td>
                                <?php 
                                $count = $db_manager->get_table_record_count($ep_table);
                                echo number_format($count);
                                ?>
                            </td>
                            <td>
                                <?php 
                                $last_sync = $sync_stats[$ep_table]['last_sync'] ?? __('Never', 'environmental-platform-core');
                                echo esc_html($last_sync);
                                ?>
                            </td>
                            <td>
                                <button type="button" class="button sync-table-btn" data-table="<?php echo esc_attr($ep_table); ?>">
                                    <?php _e('Sync Now', 'environmental-platform-core'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sync Operations -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Sync Operations', 'environmental-platform-core'); ?></h2>
            <div class="ep-sync-controls">
                <div class="ep-sync-card">
                    <h3><?php _e('Full Database Sync', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Synchronize all data between WordPress and Environmental Platform databases.', 'environmental-platform-core'); ?></p>
                    <button type="button" id="full-sync-btn" class="button button-primary">
                        <?php _e('Run Full Sync', 'environmental-platform-core'); ?>
                    </button>
                </div>
                
                <div class="ep-sync-card">
                    <h3><?php _e('Selective Sync', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Choose specific tables to synchronize.', 'environmental-platform-core'); ?></p>
                    <select id="selective-sync-tables" multiple="multiple">
                        <?php foreach ($connected_tables as $wp_table => $ep_table): ?>
                        <option value="<?php echo esc_attr($ep_table); ?>"><?php echo esc_html($ep_table); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="selective-sync-btn" class="button">
                        <?php _e('Sync Selected', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sync Log -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Recent Sync Activity', 'environmental-platform-core'); ?></h2>
            <div id="sync-log" class="ep-sync-log">
                <?php 
                $recent_logs = $db_manager->get_recent_sync_logs(10);
                if ($recent_logs): 
                ?>
                    <ul class="ep-log-list">
                        <?php foreach ($recent_logs as $log): ?>
                        <li class="log-entry log-<?php echo esc_attr($log['status']); ?>">
                            <span class="log-time"><?php echo esc_html($log['timestamp']); ?></span>
                            <span class="log-message"><?php echo esc_html($log['message']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('No sync activity recorded yet.', 'environmental-platform-core'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ep-admin-dashboard {
    max-width: 1200px;
}

.ep-dashboard-section {
    margin-bottom: 30px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.ep-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.ep-status-card, .ep-sync-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
}

.status-indicator {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: 600;
    margin-top: 10px;
}

.status-connected {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-indicator .dashicons {
    margin-right: 5px;
}

.ep-sync-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.ep-table-mapping {
    margin-top: 15px;
}

.sync-table-btn {
    padding: 4px 8px;
    font-size: 12px;
}

#selective-sync-tables {
    width: 100%;
    height: 100px;
    margin: 10px 0;
}

.ep-sync-log {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 15px;
}

.ep-log-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.log-entry {
    padding: 8px 12px;
    margin-bottom: 5px;
    border-radius: 3px;
    background: #fff;
    border-left: 4px solid #ddd;
}

.log-entry.log-success {
    border-left-color: #28a745;
}

.log-entry.log-error {
    border-left-color: #dc3545;
}

.log-entry.log-warning {
    border-left-color: #ffc107;
}

.log-time {
    font-size: 11px;
    color: #666;
    margin-right: 10px;
}

.log-message {
    font-size: 13px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Full sync handler
    $('#full-sync-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to run a full database sync? This may take several minutes.', 'environmental-platform-core'); ?>')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Syncing...', 'environmental-platform-core'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_full_sync',
                nonce: '<?php echo wp_create_nonce('ep_database_sync'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Full sync completed successfully!', 'environmental-platform-core'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Sync failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Sync request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Run Full Sync', 'environmental-platform-core'); ?>');
            }
        });
    });

    // Selective sync handler
    $('#selective-sync-btn').on('click', function() {
        var selectedTables = $('#selective-sync-tables').val();
        if (!selectedTables || selectedTables.length === 0) {
            alert('<?php _e('Please select at least one table to sync.', 'environmental-platform-core'); ?>');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Syncing...', 'environmental-platform-core'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_selective_sync',
                tables: selectedTables,
                nonce: '<?php echo wp_create_nonce('ep_database_sync'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Selected tables synced successfully!', 'environmental-platform-core'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Sync failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Sync request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Sync Selected', 'environmental-platform-core'); ?>');
            }
        });
    });

    // Individual table sync handlers
    $('.sync-table-btn').on('click', function() {
        var table = $(this).data('table');
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('<?php _e('Syncing...', 'environmental-platform-core'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_sync_table',
                table: table,
                nonce: '<?php echo wp_create_nonce('ep_database_sync'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Table synced successfully!', 'environmental-platform-core'); ?>');
                } else {
                    alert('<?php _e('Sync failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Sync request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Sync Now', 'environmental-platform-core'); ?>');
            }
        });
    });
});
</script>
