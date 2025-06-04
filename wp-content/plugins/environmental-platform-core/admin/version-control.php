<?php
/**
 * Version Control Admin Page Template
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

$version_control = new EP_Database_Version_Control();
$current_version = get_option('ep_database_version', '0.0.0');
$available_versions = $version_control->get_version_history();
?>

<div class="wrap">
    <h1><?php _e('Database Version Control', 'environmental-platform-core'); ?></h1>
    
    <div class="ep-admin-dashboard">
        <!-- Current Version Status -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Current Database Version', 'environmental-platform-core'); ?></h2>
            <div class="version-status">
                <div class="current-version-card">
                    <h3><?php _e('Installed Version', 'environmental-platform-core'); ?></h3>
                    <div class="version-number"><?php echo esc_html($current_version); ?></div>
                    <div class="version-date">
                        <?php 
                        $install_date = get_option('ep_database_install_date', current_time('mysql'));
                        printf(__('Installed: %s', 'environmental-platform-core'), date('M j, Y g:i A', strtotime($install_date)));
                        ?>
                    </div>
                </div>
                
                <div class="latest-version-card">
                    <h3><?php _e('Latest Available', 'environmental-platform-core'); ?></h3>
                    <div class="version-number">
                        <?php 
                        $latest = $version_control->get_latest_version();
                        echo esc_html($latest);
                        ?>
                    </div>
                    <?php if (version_compare($current_version, $latest, '<')): ?>
                        <div class="update-available">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Update Available', 'environmental-platform-core'); ?>
                        </div>
                    <?php else: ?>
                        <div class="up-to-date">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Up to Date', 'environmental-platform-core'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Version History -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Version History', 'environmental-platform-core'); ?></h2>
            <div class="version-history">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Version', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Release Date', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Description', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Status', 'environmental-platform-core'); ?></th>
                            <th><?php _e('Actions', 'environmental-platform-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_versions as $version => $info): ?>
                        <tr class="<?php echo version_compare($version, $current_version, '<=') ? 'installed' : 'available'; ?>">
                            <td class="version-column">
                                <strong><?php echo esc_html($version); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($info['date']); ?>
                            </td>
                            <td>
                                <?php echo esc_html($info['description']); ?>
                                <?php if (!empty($info['features'])): ?>
                                    <details class="version-features">
                                        <summary><?php _e('View Features', 'environmental-platform-core'); ?></summary>
                                        <ul>
                                            <?php foreach ($info['features'] as $feature): ?>
                                                <li><?php echo esc_html($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (version_compare($version, $current_version, '=')): ?>
                                    <span class="status-badge status-current"><?php _e('Current', 'environmental-platform-core'); ?></span>
                                <?php elseif (version_compare($version, $current_version, '<')): ?>
                                    <span class="status-badge status-installed"><?php _e('Installed', 'environmental-platform-core'); ?></span>
                                <?php else: ?>
                                    <span class="status-badge status-available"><?php _e('Available', 'environmental-platform-core'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (version_compare($version, $current_version, '>')): ?>
                                    <button type="button" class="button update-btn" data-version="<?php echo esc_attr($version); ?>">
                                        <?php _e('Update to This Version', 'environmental-platform-core'); ?>
                                    </button>
                                <?php elseif (version_compare($version, $current_version, '<')): ?>
                                    <button type="button" class="button button-secondary rollback-btn" data-version="<?php echo esc_attr($version); ?>">
                                        <?php _e('Rollback to This Version', 'environmental-platform-core'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Update Actions -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Update Actions', 'environmental-platform-core'); ?></h2>
            <div class="update-actions">
                <?php if (version_compare($current_version, $latest, '<')): ?>
                    <div class="update-card">
                        <h3><?php _e('Automatic Update', 'environmental-platform-core'); ?></h3>
                        <p><?php printf(__('Update database from version %s to %s automatically.', 'environmental-platform-core'), $current_version, $latest); ?></p>
                        <button type="button" id="auto-update-btn" class="button button-primary">
                            <?php _e('Update to Latest Version', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="backup-card">
                    <h3><?php _e('Create Backup', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Create a backup of the current database state before making changes.', 'environmental-platform-core'); ?></p>
                    <button type="button" id="create-backup-btn" class="button">
                        <?php _e('Create Backup', 'environmental-platform-core'); ?>
                    </button>
                </div>
                
                <div class="check-updates-card">
                    <h3><?php _e('Check for Updates', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Check if there are any new database updates available.', 'environmental-platform-core'); ?></p>
                    <button type="button" id="check-updates-btn" class="button">
                        <?php _e('Check for Updates', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Update Progress -->
        <div class="ep-dashboard-section" id="update-progress" style="display: none;">
            <h2><?php _e('Update Progress', 'environmental-platform-core'); ?></h2>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="update-progress-fill"></div>
                </div>
                <div class="progress-text" id="update-progress-text">0%</div>
            </div>
            <div class="update-status" id="update-status">
                <?php _e('Preparing update...', 'environmental-platform-core'); ?>
            </div>
        </div>

        <!-- Update Log -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Update Log', 'environmental-platform-core'); ?></h2>
            <div id="update-log" class="update-log">
                <div class="log-controls">
                    <button type="button" id="refresh-update-log-btn" class="button">
                        <?php _e('Refresh Log', 'environmental-platform-core'); ?>
                    </button>
                    <button type="button" id="clear-update-log-btn" class="button">
                        <?php _e('Clear Log', 'environmental-platform-core'); ?>
                    </button>
                </div>
                <div class="log-content" id="update-log-content">
                    <?php 
                    $update_logs = get_option('ep_update_logs', array());
                    if (!empty($update_logs)):
                    ?>
                        <?php foreach (array_slice($update_logs, -20) as $log): ?>
                        <div class="log-entry log-<?php echo esc_attr($log['type']); ?>">
                            <span class="log-timestamp">[<?php echo esc_html($log['timestamp']); ?>]</span>
                            <span class="log-message"><?php echo esc_html($log['message']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No update activity recorded yet.', 'environmental-platform-core'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Version Control Configuration', 'environmental-platform-core'); ?></h2>
            <div class="config-form">
                <form id="version-config-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="auto_updates"><?php _e('Automatic Updates', 'environmental-platform-core'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto_updates" name="auto_updates" value="1" 
                                           <?php checked(get_option('ep_auto_updates', 0), 1); ?>>
                                    <?php _e('Enable automatic database updates', 'environmental-platform-core'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, database updates will be applied automatically when new versions are available.', 'environmental-platform-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="backup_before_update"><?php _e('Backup Before Updates', 'environmental-platform-core'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="backup_before_update" name="backup_before_update" value="1" 
                                           <?php checked(get_option('ep_backup_before_update', 1), 1); ?>>
                                    <?php _e('Create backup before applying updates', 'environmental-platform-core'); ?>
                                </label>
                                <p class="description"><?php _e('Recommended. Creates a backup of the database before applying any updates.', 'environmental-platform-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="update_notifications"><?php _e('Update Notifications', 'environmental-platform-core'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="update_notifications" name="update_notifications" value="1" 
                                           <?php checked(get_option('ep_update_notifications', 1), 1); ?>>
                                    <?php _e('Send email notifications for available updates', 'environmental-platform-core'); ?>
                                </label>
                                <p class="description"><?php _e('Sends notifications to administrators when new database updates are available.', 'environmental-platform-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Configuration', 'environmental-platform-core'); ?></button>
                    </p>
                </form>
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

.version-status {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.current-version-card, .latest-version-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
    text-align: center;
}

.current-version-card h3, .latest-version-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.version-number {
    font-size: 28px;
    font-weight: bold;
    color: #007cba;
    margin-bottom: 5px;
}

.version-date {
    font-size: 12px;
    color: #666;
}

.update-available {
    color: #d63384;
    font-weight: 600;
    margin-top: 5px;
}

.up-to-date {
    color: #198754;
    font-weight: 600;
    margin-top: 5px;
}

.update-available .dashicons, .up-to-date .dashicons {
    margin-right: 5px;
}

.version-history {
    margin-top: 15px;
}

.version-column {
    font-weight: 600;
}

.version-features {
    margin-top: 10px;
}

.version-features summary {
    cursor: pointer;
    font-size: 12px;
    color: #007cba;
}

.version-features ul {
    margin: 5px 0 0 15px;
    font-size: 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-current {
    background: #d1ecf1;
    color: #0c5460;
}

.status-installed {
    background: #d4edda;
    color: #155724;
}

.status-available {
    background: #fff3cd;
    color: #856404;
}

.update-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.update-card, .backup-card, .check-updates-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
}

.update-card h3, .backup-card h3, .check-updates-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.update-card p, .backup-card p, .check-updates-card p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
}

.progress-container {
    margin: 20px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background-color: #007cba;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 5px;
    font-weight: bold;
}

.update-status {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
    margin-top: 15px;
    font-style: italic;
}

.update-log {
    margin-top: 15px;
}

.log-controls {
    margin-bottom: 10px;
}

.log-controls .button {
    margin-right: 10px;
}

.log-content {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    padding: 15px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    line-height: 1.6;
}

.log-entry {
    margin-bottom: 5px;
    padding: 5px;
    border-radius: 3px;
}

.log-entry.log-success {
    background: #d4edda;
    color: #155724;
}

.log-entry.log-error {
    background: #f8d7da;
    color: #721c24;
}

.log-entry.log-warning {
    background: #fff3cd;
    color: #856404;
}

.log-entry.log-info {
    background: #d1ecf1;
    color: #0c5460;
}

.log-timestamp {
    color: #666;
    margin-right: 10px;
}

.config-form {
    margin-top: 15px;
}

tr.installed {
    background-color: #f8fff8;
}

tr.available {
    background-color: #fffcf8;
}
</style>

<script>
jQuery(document).ready(function($) {
    var updateInProgress = false;

    // Auto update button
    $('#auto-update-btn').on('click', function() {
        if (updateInProgress) {
            alert('<?php _e('An update is already in progress. Please wait for it to complete.', 'environmental-platform-core'); ?>');
            return;
        }

        if (!confirm('<?php _e('Are you sure you want to update the database to the latest version?', 'environmental-platform-core'); ?>')) {
            return;
        }

        var latestVersion = '<?php echo esc_js($latest); ?>';
        performUpdate(latestVersion);
    });

    // Individual version update buttons
    $('.update-btn').on('click', function() {
        if (updateInProgress) {
            alert('<?php _e('An update is already in progress. Please wait for it to complete.', 'environmental-platform-core'); ?>');
            return;
        }

        var version = $(this).data('version');
        if (!confirm('<?php _e('Are you sure you want to update the database to version', 'environmental-platform-core'); ?> ' + version + '?')) {
            return;
        }

        performUpdate(version);
    });

    // Rollback buttons
    $('.rollback-btn').on('click', function() {
        if (updateInProgress) {
            alert('<?php _e('An update is already in progress. Please wait for it to complete.', 'environmental-platform-core'); ?>');
            return;
        }

        var version = $(this).data('version');
        if (!confirm('<?php _e('WARNING: Rolling back the database may cause data loss. Are you sure you want to rollback to version', 'environmental-platform-core'); ?> ' + version + '?')) {
            return;
        }

        performRollback(version);
    });

    function performUpdate(version) {
        updateInProgress = true;
        $('.update-btn, .rollback-btn, #auto-update-btn').prop('disabled', true);
        $('#update-progress').show();
        $('#update-progress-fill').css('width', '0%');
        $('#update-progress-text').text('0%');
        $('#update-status').text('<?php _e('Starting database update...', 'environmental-platform-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_update_database',
                target_version: version,
                nonce: '<?php echo wp_create_nonce('ep_database_update'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#update-progress-fill').css('width', '100%');
                    $('#update-progress-text').text('100%');
                    $('#update-status').text('<?php _e('Database updated successfully!', 'environmental-platform-core'); ?>');
                    alert('<?php _e('Database updated successfully!', 'environmental-platform-core'); ?>');
                    location.reload();
                } else {
                    $('#update-status').text('<?php _e('Update failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                    alert('<?php _e('Update failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                $('#update-status').text('<?php _e('Update request failed. Please try again.', 'environmental-platform-core'); ?>');
                alert('<?php _e('Update request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                updateInProgress = false;
                $('.update-btn, .rollback-btn, #auto-update-btn').prop('disabled', false);
                setTimeout(function() {
                    $('#update-progress').hide();
                }, 5000);
            }
        });
    }

    function performRollback(version) {
        updateInProgress = true;
        $('.update-btn, .rollback-btn, #auto-update-btn').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_rollback_database',
                target_version: version,
                nonce: '<?php echo wp_create_nonce('ep_database_rollback'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Database rolled back successfully!', 'environmental-platform-core'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Rollback failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Rollback request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                updateInProgress = false;
                $('.update-btn, .rollback-btn, #auto-update-btn').prop('disabled', false);
            }
        });
    }

    // Create backup button
    $('#create-backup-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Creating backup...', 'environmental-platform-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_create_backup',
                nonce: '<?php echo wp_create_nonce('ep_database_backup'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Backup created successfully!', 'environmental-platform-core'); ?>');
                } else {
                    alert('<?php _e('Backup failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Backup request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Create Backup', 'environmental-platform-core'); ?>');
            }
        });
    });

    // Check updates button
    $('#check-updates-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Checking...', 'environmental-platform-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_check_updates',
                nonce: '<?php echo wp_create_nonce('ep_database_check_updates'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.updates_available) {
                        alert('<?php _e('New updates are available!', 'environmental-platform-core'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Your database is up to date.', 'environmental-platform-core'); ?>');
                    }
                } else {
                    alert('<?php _e('Check failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Check request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Check for Updates', 'environmental-platform-core'); ?>');
            }
        });
    });

    // Configuration form
    $('#version-config-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'ep_save_version_config',
            auto_updates: $('#auto_updates').is(':checked') ? 1 : 0,
            backup_before_update: $('#backup_before_update').is(':checked') ? 1 : 0,
            update_notifications: $('#update_notifications').is(':checked') ? 1 : 0,
            nonce: '<?php echo wp_create_nonce('ep_version_config'); ?>'
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Configuration saved successfully!', 'environmental-platform-core'); ?>');
                } else {
                    alert('<?php _e('Save failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Save request failed. Please try again.', 'environmental-platform-core'); ?>');
            }
        });
    });

    // Refresh log
    $('#refresh-update-log-btn').on('click', function() {
        location.reload();
    });

    // Clear log
    $('#clear-update-log-btn').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear the update log?', 'environmental-platform-core'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_clear_update_log',
                    nonce: '<?php echo wp_create_nonce('ep_clear_log'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#update-log-content').html('<p><?php _e('No update activity recorded yet.', 'environmental-platform-core'); ?></p>');
                    }
                }
            });
        }
    });
});
</script>
