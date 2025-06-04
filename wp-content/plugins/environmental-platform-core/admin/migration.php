<?php
/**
 * Data Migration Admin Page Template
 * Phase 28: Custom Database Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

$migration_handler = new EP_Database_Migration();
$migration_stats = array(); // Will be loaded via AJAX
?>

<div class="wrap">
    <h1><?php _e('Data Migration', 'environmental-platform-core'); ?></h1>
    
    <div class="ep-admin-dashboard">
        <!-- Migration Overview -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Migration Overview', 'environmental-platform-core'); ?></h2>
            <div class="ep-migration-overview">
                <div class="migration-stats-grid">
                    <div class="stat-card">
                        <h3><?php _e('Users Migrated', 'environmental-platform-core'); ?></h3>
                        <div class="stat-number" id="users-migrated">-</div>
                    </div>
                    <div class="stat-card">
                        <h3><?php _e('Posts Migrated', 'environmental-platform-core'); ?></h3>
                        <div class="stat-number" id="posts-migrated">-</div>
                    </div>
                    <div class="stat-card">
                        <h3><?php _e('Terms Migrated', 'environmental-platform-core'); ?></h3>
                        <div class="stat-number" id="terms-migrated">-</div>
                    </div>
                    <div class="stat-card">
                        <h3><?php _e('Last Migration', 'environmental-platform-core'); ?></h3>
                        <div class="stat-text" id="last-migration">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Types -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Migration Options', 'environmental-platform-core'); ?></h2>
            <div class="migration-types-grid">
                <div class="migration-card">
                    <h3><?php _e('Full Database Migration', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate all data from Environmental Platform database to WordPress. This includes users, content, environmental data, and more.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button button-primary migration-btn" data-type="full">
                        <?php _e('Run Full Migration', 'environmental-platform-core'); ?>
                    </button>
                </div>

                <div class="migration-card">
                    <h3><?php _e('Users Only', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate only user accounts and profiles from the Environmental Platform.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button migration-btn" data-type="users">
                        <?php _e('Migrate Users', 'environmental-platform-core'); ?>
                    </button>
                </div>

                <div class="migration-card">
                    <h3><?php _e('Content Only', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate articles, categories, tags, and other content from the Environmental Platform.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button migration-btn" data-type="content">
                        <?php _e('Migrate Content', 'environmental-platform-core'); ?>
                    </button>
                </div>

                <div class="migration-card">
                    <h3><?php _e('Environmental Data', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate environmental monitoring data, events, and related information.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button migration-btn" data-type="environmental">
                        <?php _e('Migrate Environmental Data', 'environmental-platform-core'); ?>
                    </button>
                </div>

                <div class="migration-card">
                    <h3><?php _e('E-commerce Data', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate products, orders, and e-commerce related data.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button migration-btn" data-type="ecommerce">
                        <?php _e('Migrate E-commerce Data', 'environmental-platform-core'); ?>
                    </button>
                </div>

                <div class="migration-card">
                    <h3><?php _e('Community Data', 'environmental-platform-core'); ?></h3>
                    <p><?php _e('Migrate forums, discussions, and community-related content.', 'environmental-platform-core'); ?></p>
                    <button type="button" class="button migration-btn" data-type="community">
                        <?php _e('Migrate Community Data', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Table Migration -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Custom Table Migration', 'environmental-platform-core'); ?></h2>
            <div class="custom-migration">
                <p><?php _e('Select specific tables to migrate from the Environmental Platform database.', 'environmental-platform-core'); ?></p>
                <div class="table-selection">
                    <label for="custom-tables"><?php _e('Select Tables:', 'environmental-platform-core'); ?></label>
                    <select id="custom-tables" multiple="multiple">
                        <!-- Tables will be loaded via AJAX -->
                    </select>
                </div>
                <button type="button" class="button migration-btn" data-type="custom">
                    <?php _e('Migrate Selected Tables', 'environmental-platform-core'); ?>
                </button>
            </div>
        </div>

        <!-- Migration Progress -->
        <div class="ep-dashboard-section" id="migration-progress" style="display: none;">
            <h2><?php _e('Migration Progress', 'environmental-platform-core'); ?></h2>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0%</div>
            </div>
            <div class="migration-status" id="migration-status">
                <?php _e('Preparing migration...', 'environmental-platform-core'); ?>
            </div>
        </div>

        <!-- Migration Log -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Migration Log', 'environmental-platform-core'); ?></h2>
            <div id="migration-log" class="migration-log">
                <div class="log-controls">
                    <button type="button" id="refresh-log-btn" class="button">
                        <?php _e('Refresh Log', 'environmental-platform-core'); ?>
                    </button>
                    <button type="button" id="clear-log-btn" class="button">
                        <?php _e('Clear Log', 'environmental-platform-core'); ?>
                    </button>
                </div>
                <div class="log-content" id="log-content">
                    <p><?php _e('No migration activity recorded yet.', 'environmental-platform-core'); ?></p>
                </div>
            </div>
        </div>

        <!-- Rollback Section -->
        <div class="ep-dashboard-section">
            <h2><?php _e('Rollback Migration', 'environmental-platform-core'); ?></h2>
            <div class="rollback-section">
                <p class="description"><?php _e('Warning: This will remove all migrated data from WordPress and cannot be undone.', 'environmental-platform-core'); ?></p>
                <button type="button" id="rollback-btn" class="button button-secondary">
                    <?php _e('Rollback Migration', 'environmental-platform-core'); ?>
                </button>
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

.migration-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
}

.stat-text {
    font-size: 13px;
    color: #333;
}

.migration-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.migration-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
}

.migration-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.migration-card p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.custom-migration {
    margin-top: 15px;
}

.table-selection {
    margin: 15px 0;
}

.table-selection label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

#custom-tables {
    width: 100%;
    height: 120px;
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

.migration-status {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e1e5e9;
    margin-top: 15px;
    font-style: italic;
}

.migration-log {
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

.rollback-section {
    background: #fff5f5;
    border: 1px solid #fc8181;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
}

.rollback-section .description {
    color: #c53030;
    font-weight: 600;
    margin-bottom: 10px;
}

.migration-btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
jQuery(document).ready(function($) {
    var migrationInProgress = false;

    // Load migration stats on page load
    loadMigrationStats();
    loadAvailableTables();

    function loadMigrationStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_check_migration_status',
                nonce: '<?php echo wp_create_nonce('ep_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data.stats;
                    $('#users-migrated').text(stats.users || 0);
                    $('#posts-migrated').text(stats.posts || 0);
                    $('#terms-migrated').text(stats.terms || 0);
                    
                    var log = response.data.log;
                    if (log && log.length > 0) {
                        var lastEntry = log[log.length - 1];
                        $('#last-migration').text(lastEntry.timestamp || '-');
                        updateMigrationLog(log);
                    }
                }
            }
        });
    }

    function loadAvailableTables() {
        // This would load available tables from the EP database
        var tables = [
            'users', 'user_profiles', 'articles', 'categories', 'tags',
            'environmental_data', 'products', 'orders', 'forums', 'achievements'
        ];
        
        var $select = $('#custom-tables');
        $select.empty();
        tables.forEach(function(table) {
            $select.append('<option value="' + table + '">' + table + '</option>');
        });
    }

    function updateMigrationLog(log) {
        var $logContent = $('#log-content');
        if (log && log.length > 0) {
            var logHtml = '';
            log.forEach(function(entry) {
                logHtml += '<div class="log-entry">';
                logHtml += '<span class="log-timestamp">[' + entry.timestamp + ']</span> ';
                logHtml += '<span class="log-message">' + entry.message + '</span>';
                logHtml += '</div>';
            });
            $logContent.html(logHtml);
        }
    }

    // Migration button handlers
    $('.migration-btn').on('click', function() {
        if (migrationInProgress) {
            alert('<?php _e('A migration is already in progress. Please wait for it to complete.', 'environmental-platform-core'); ?>');
            return;
        }

        var migrationType = $(this).data('type');
        var tables = [];
        
        if (migrationType === 'custom') {
            tables = $('#custom-tables').val();
            if (!tables || tables.length === 0) {
                alert('<?php _e('Please select at least one table to migrate.', 'environmental-platform-core'); ?>');
                return;
            }
        }

        if (!confirm('<?php _e('Are you sure you want to start this migration? This process may take several minutes.', 'environmental-platform-core'); ?>')) {
            return;
        }

        startMigration(migrationType, tables);
    });

    function startMigration(type, tables) {
        migrationInProgress = true;
        $('.migration-btn').prop('disabled', true);
        $('#migration-progress').show();
        $('#progress-fill').css('width', '0%');
        $('#progress-text').text('0%');
        $('#migration-status').text('<?php _e('Starting migration...', 'environmental-platform-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_run_migration',
                migration_type: type,
                tables: tables,
                nonce: '<?php echo wp_create_nonce('ep_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#progress-fill').css('width', '100%');
                    $('#progress-text').text('100%');
                    $('#migration-status').text('<?php _e('Migration completed successfully!', 'environmental-platform-core'); ?>');
                    updateMigrationLog(response.data.log);
                    loadMigrationStats();
                    alert('<?php _e('Migration completed successfully!', 'environmental-platform-core'); ?>');
                } else {
                    $('#migration-status').text('<?php _e('Migration failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                    updateMigrationLog(response.data.log);
                    alert('<?php _e('Migration failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                $('#migration-status').text('<?php _e('Migration request failed. Please try again.', 'environmental-platform-core'); ?>');
                alert('<?php _e('Migration request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                migrationInProgress = false;
                $('.migration-btn').prop('disabled', false);
                setTimeout(function() {
                    $('#migration-progress').hide();
                }, 5000);
            }
        });
    }

    // Refresh log button
    $('#refresh-log-btn').on('click', function() {
        loadMigrationStats();
    });

    // Clear log button
    $('#clear-log-btn').on('click', function() {
        if (confirm('<?php _e('Are you sure you want to clear the migration log?', 'environmental-platform-core'); ?>')) {
            $('#log-content').html('<p><?php _e('No migration activity recorded yet.', 'environmental-platform-core'); ?></p>');
        }
    });

    // Rollback button
    $('#rollback-btn').on('click', function() {
        if (!confirm('<?php _e('WARNING: This will permanently delete all migrated data from WordPress. This action cannot be undone. Are you sure?', 'environmental-platform-core'); ?>')) {
            return;
        }

        if (!confirm('<?php _e('This is your final warning. All migrated users, posts, and data will be permanently deleted. Continue?', 'environmental-platform-core'); ?>')) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Rolling back...', 'environmental-platform-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_rollback_migration',
                nonce: '<?php echo wp_create_nonce('ep_migration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Migration rollback completed successfully.', 'environmental-platform-core'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Rollback failed: ', 'environmental-platform-core'); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('Rollback request failed. Please try again.', 'environmental-platform-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Rollback Migration', 'environmental-platform-core'); ?>');
            }
        });
    });
});
</script>
