<?php
/**
 * Content Migration Admin Page
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle migration actions
if (isset($_POST['start_migration']) && wp_verify_nonce($_POST['_wpnonce'], 'ep_start_migration')) {
    $migration_type = sanitize_text_field($_POST['migration_type']);
    
    // Initialize content migration
    $content_migration = new EP_Content_Migration();
    
    switch ($migration_type) {
        case 'all':
            $result = $content_migration->migrate_all_content();
            break;
        case 'articles':
            $result = $content_migration->migrate_articles();
            break;
        case 'events':
            $result = $content_migration->migrate_events();
            break;
        default:
            $result = false;
    }
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Migration completed successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Migration failed. Please check the logs.</p></div>';
    }
}

// Get migration statistics
$migration_stats = array();
$post_types = array(
    'env_article' => 'Environmental Articles',
    'env_event' => 'Environmental Events', 
    'env_report' => 'Environmental Reports',
    'community_post' => 'Community Posts',
    'eco_product' => 'Eco Products',
    'edu_resource' => 'Educational Resources',
    'waste_class' => 'Waste Classifications'
);

foreach ($post_types as $post_type => $label) {
    $count = wp_count_posts($post_type);
    $total = $count->publish + $count->draft + $count->pending;
    $migrated = get_posts(array(
        'post_type' => $post_type,
        'meta_key' => '_migrated_from_db',
        'meta_value' => true,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $migration_stats[$post_type] = array(
        'label' => $label,
        'total' => $total,
        'migrated' => count($migrated),
        'remaining' => $total - count($migrated)
    );
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="ep-admin-header">
        <div class="ep-admin-tabs">
            <a href="#migration-overview" class="nav-tab nav-tab-active">Migration Overview</a>
            <a href="#manual-migration" class="nav-tab">Manual Migration</a>
            <a href="#batch-migration" class="nav-tab">Batch Migration</a>
            <a href="#migration-logs" class="nav-tab">Migration Logs</a>
        </div>
    </div>

    <!-- Migration Overview Tab -->
    <div id="migration-overview" class="tab-content active">
        <div class="ep-migration-grid">
            <div class="ep-card">
                <h3>📊 Migration Status</h3>
                <div class="ep-migration-stats">
                    <?php foreach ($migration_stats as $post_type => $stat): ?>
                    <div class="ep-migration-item">
                        <div class="ep-migration-header">
                            <h4><?php echo $stat['label']; ?></h4>
                            <div class="ep-migration-badge">
                                <?php if ($stat['migrated'] == $stat['total'] && $stat['total'] > 0): ?>
                                    <span class="badge-complete">✅ Complete</span>
                                <?php elseif ($stat['migrated'] > 0): ?>
                                    <span class="badge-partial">🔄 Partial</span>
                                <?php else: ?>
                                    <span class="badge-pending">⏳ Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ep-migration-progress">
                            <div class="progress-bar">
                                <?php 
                                $percentage = $stat['total'] > 0 ? ($stat['migrated'] / $stat['total']) * 100 : 0;
                                ?>
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <?php echo $stat['migrated']; ?> / <?php echo $stat['total']; ?> 
                                (<?php echo round($percentage, 1); ?>%)
                            </div>
                        </div>
                        
                        <div class="ep-migration-actions">
                            <?php if ($stat['remaining'] > 0): ?>
                            <button type="button" class="button button-primary migrate-content" 
                                    data-type="<?php echo $post_type; ?>">
                                🚀 Migrate <?php echo $stat['remaining']; ?> Items
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ep-card">
                <h3>⚡ Quick Migration</h3>
                <div class="ep-quick-migration">
                    <p>Quickly migrate all content from your existing database to WordPress.</p>
                    
                    <div class="ep-migration-options">
                        <div class="migration-option">
                            <input type="radio" id="migrate-all" name="quick_migration" value="all" checked>
                            <label for="migrate-all">
                                <strong>🔄 Migrate Everything</strong>
                                <span>Migrate all content types at once</span>
                            </label>
                        </div>
                        
                        <div class="migration-option">
                            <input type="radio" id="migrate-articles" name="quick_migration" value="articles">
                            <label for="migrate-articles">
                                <strong>📄 Articles Only</strong>
                                <span>Migrate only environmental articles</span>
                            </label>
                        </div>
                        
                        <div class="migration-option">
                            <input type="radio" id="migrate-events" name="quick_migration" value="events">
                            <label for="migrate-events">
                                <strong>📅 Events Only</strong>
                                <span>Migrate only environmental events</span>
                            </label>
                        </div>
                    </div>
                    
                    <button type="button" id="start-quick-migration" class="button button-primary button-large">
                        🚀 Start Migration
                    </button>
                </div>
            </div>

            <div class="ep-card">
                <h3>📈 Migration Analytics</h3>
                <div class="ep-migration-analytics">
                    <div class="analytics-item">
                        <div class="analytics-number">
                            <?php echo array_sum(array_column($migration_stats, 'migrated')); ?>
                        </div>
                        <div class="analytics-label">Total Items Migrated</div>
                    </div>
                    
                    <div class="analytics-item">
                        <div class="analytics-number">
                            <?php echo array_sum(array_column($migration_stats, 'remaining')); ?>
                        </div>
                        <div class="analytics-label">Items Remaining</div>
                    </div>
                    
                    <div class="analytics-item">
                        <div class="analytics-number">
                            <?php 
                            $total_items = array_sum(array_column($migration_stats, 'total'));
                            $migrated_items = array_sum(array_column($migration_stats, 'migrated'));
                            echo $total_items > 0 ? round(($migrated_items / $total_items) * 100, 1) : 0;
                            ?>%
                        </div>
                        <div class="analytics-label">Completion Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Migration Tab -->
    <div id="manual-migration" class="tab-content">
        <div class="ep-card">
            <h3>🔧 Manual Migration Tools</h3>
            
            <form method="post" class="ep-migration-form">
                <?php wp_nonce_field('ep_start_migration'); ?>
                
                <div class="ep-form-section">
                    <h4>Select Migration Type</h4>
                    <select name="migration_type" id="migration_type" class="widefat">
                        <option value="all">All Content Types</option>
                        <option value="articles">Environmental Articles</option>
                        <option value="events">Environmental Events</option>
                        <option value="reports">Environmental Reports</option>
                        <option value="community">Community Posts</option>
                        <option value="products">Eco Products</option>
                        <option value="resources">Educational Resources</option>
                        <option value="waste">Waste Classifications</option>
                    </select>
                </div>
                
                <div class="ep-form-section">
                    <h4>Migration Options</h4>
                    <label>
                        <input type="checkbox" name="preserve_dates" value="1" checked>
                        Preserve original creation dates
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="migrate_categories" value="1" checked>
                        Migrate categories and taxonomies
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="create_redirects" value="1">
                        Create redirects from old URLs
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="backup_before_migration" value="1" checked>
                        Create backup before migration
                    </label>
                </div>
                
                <div class="ep-form-section">
                    <h4>Batch Settings</h4>
                    <label for="batch_size">Items per batch:</label>
                    <input type="number" name="batch_size" id="batch_size" value="50" min="10" max="500">
                    <p class="description">Process items in batches to prevent timeouts</p>
                </div>
                
                <div class="ep-form-actions">
                    <button type="submit" name="start_migration" class="button button-primary button-large">
                        🚀 Start Manual Migration
                    </button>
                    
                    <button type="button" id="preview-migration" class="button button-secondary">
                        👁️ Preview Migration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Migration Tab -->
    <div id="batch-migration" class="tab-content">
        <div class="ep-card">
            <h3>⚡ Batch Migration Control</h3>
            
            <div class="ep-batch-controls">
                <div class="batch-status">
                    <h4>Current Batch Status</h4>
                    <div id="batch-progress">
                        <div class="progress-info">
                            <span id="current-batch">0</span> / <span id="total-batches">0</span> batches completed
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div id="batch-progress-fill" class="progress-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="batch-actions">
                    <button type="button" id="start-batch-migration" class="button button-primary">
                        ▶️ Start Batch Migration
                    </button>
                    <button type="button" id="pause-batch-migration" class="button button-secondary" disabled>
                        ⏸️ Pause
                    </button>
                    <button type="button" id="stop-batch-migration" class="button button-secondary" disabled>
                        ⏹️ Stop
                    </button>
                </div>
            </div>
            
            <div class="ep-batch-log">
                <h4>Batch Log</h4>
                <div id="batch-log-content">
                    <p>No batch migration running...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration Logs Tab -->
    <div id="migration-logs" class="tab-content">
        <div class="ep-card">
            <h3>📋 Migration Logs</h3>
            
            <div class="ep-log-controls">
                <button type="button" id="refresh-logs" class="button button-secondary">
                    🔄 Refresh Logs
                </button>
                <button type="button" id="clear-logs" class="button button-secondary">
                    🗑️ Clear Logs
                </button>
                <button type="button" id="export-logs" class="button button-secondary">
                    📥 Export Logs
                </button>
            </div>
            
            <div class="ep-log-container">
                <div id="migration-log-content">
                    <p>Loading migration logs...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ep-migration-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ep-migration-stats {
    display: grid;
    gap: 20px;
    margin-top: 15px;
}

.ep-migration-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2e7d32;
}

.ep-migration-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.ep-migration-header h4 {
    margin: 0;
}

.ep-migration-badge .badge-complete {
    background: #4caf50;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.ep-migration-badge .badge-partial {
    background: #ff9800;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.ep-migration-badge .badge-pending {
    background: #9e9e9e;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.ep-migration-progress {
    margin: 10px 0;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #8bc34a);
    transition: width 0.3s ease;
}

.progress-text {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.ep-quick-migration {
    text-align: center;
    padding: 20px;
}

.ep-migration-options {
    margin: 20px 0;
    text-align: left;
}

.migration-option {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
}

.migration-option label {
    display: block;
    cursor: pointer;
}

.migration-option strong {
    display: block;
    margin-bottom: 5px;
}

.migration-option span {
    color: #666;
    font-size: 13px;
}

.ep-migration-analytics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.analytics-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.analytics-number {
    font-size: 28px;
    font-weight: bold;
    color: #2e7d32;
}

.analytics-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.ep-form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.ep-form-section h4 {
    margin-bottom: 10px;
    color: #333;
}

.ep-form-section label {
    display: block;
    margin-bottom: 8px;
}

.ep-form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.ep-batch-controls {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.batch-status h4 {
    margin-bottom: 10px;
}

.progress-info {
    margin-bottom: 10px;
    font-weight: 500;
}

.progress-bar-container {
    margin-bottom: 10px;
}

.batch-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ep-batch-log {
    margin-top: 20px;
}

.ep-log-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.ep-log-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    background: #f8f9fa;
    font-family: monospace;
}

.tab-content {
    display: none;
    margin-top: 20px;
}

.tab-content.active {
    display: block;
}

.nav-tab.nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Individual content type migration
    $('.migrate-content').click(function() {
        var contentType = $(this).data('type');
        var button = $(this);
        
        button.prop('disabled', true).text('Migrating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_migrate_content',
                type: contentType,
                nonce: '<?php echo wp_create_nonce("ep_migration_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Migration completed successfully!');
                    location.reload();
                } else {
                    alert('Migration failed: ' + response.data);
                }
            },
            error: function() {
                alert('Migration failed due to server error');
            },
            complete: function() {
                button.prop('disabled', false).text('🚀 Migrate Content');
            }
        });
    });
    
    // Quick migration
    $('#start-quick-migration').click(function() {
        var migrationType = $('input[name="quick_migration"]:checked').val();
        var button = $(this);
        
        if (!confirm('Are you sure you want to start the migration? This process may take several minutes.')) {
            return;
        }
        
        button.prop('disabled', true).text('Migrating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_migrate_content',
                type: migrationType,
                nonce: '<?php echo wp_create_nonce("ep_migration_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Migration completed successfully!');
                    location.reload();
                } else {
                    alert('Migration failed: ' + response.data);
                }
            },
            error: function() {
                alert('Migration failed due to server error');
            },
            complete: function() {
                button.prop('disabled', false).text('🚀 Start Migration');
            }
        });
    });
    
    // Preview migration
    $('#preview-migration').click(function() {
        var migrationType = $('#migration_type').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_preview_migration',
                type: migrationType,
                nonce: '<?php echo wp_create_nonce("ep_preview_migration"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Preview:\n' + response.data.preview);
                }
            }
        });
    });
    
    // Batch migration controls
    var batchRunning = false;
    var currentBatch = 0;
    var totalBatches = 0;
    
    $('#start-batch-migration').click(function() {
        if (!confirm('Start batch migration? This will process content in small batches.')) {
            return;
        }
        
        batchRunning = true;
        $(this).prop('disabled', true);
        $('#pause-batch-migration, #stop-batch-migration').prop('disabled', false);
        
        startBatchMigration();
    });
    
    function startBatchMigration() {
        if (!batchRunning) return;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_batch_migrate',
                batch: currentBatch,
                nonce: '<?php echo wp_create_nonce("ep_batch_migration"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    currentBatch = response.data.current_batch;
                    totalBatches = response.data.total_batches;
                    
                    updateBatchProgress();
                    
                    if (currentBatch < totalBatches && batchRunning) {
                        setTimeout(startBatchMigration, 2000); // 2 second delay between batches
                    } else {
                        completeBatchMigration();
                    }
                }
            }
        });
    }
    
    function updateBatchProgress() {
        $('#current-batch').text(currentBatch);
        $('#total-batches').text(totalBatches);
        
        var percentage = totalBatches > 0 ? (currentBatch / totalBatches) * 100 : 0;
        $('#batch-progress-fill').css('width', percentage + '%');
    }
    
    function completeBatchMigration() {
        batchRunning = false;
        $('#start-batch-migration').prop('disabled', false);
        $('#pause-batch-migration, #stop-batch-migration').prop('disabled', true);
        alert('Batch migration completed!');
    }
    
    // Log controls
    $('#refresh-logs').click(function() {
        loadMigrationLogs();
    });
    
    $('#clear-logs').click(function() {
        if (confirm('Clear all migration logs?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ep_clear_migration_logs',
                    nonce: '<?php echo wp_create_nonce("ep_clear_logs"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#migration-log-content').html('<p>Logs cleared.</p>');
                    }
                }
            });
        }
    });
    
    function loadMigrationLogs() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ep_get_migration_logs',
                nonce: '<?php echo wp_create_nonce("ep_get_logs"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#migration-log-content').html(response.data.logs);
                }
            }
        });
    }
    
    // Load logs on page load
    loadMigrationLogs();
});
</script>
