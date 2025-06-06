<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['save_performance_settings'])) {
    check_admin_referer('env_performance_settings');
    
    // Save CDN settings
    update_option('env_cdn_url', sanitize_url($_POST['cdn_url']));
    update_option('env_cdn_enabled', isset($_POST['cdn_enabled']) ? 1 : 0);
    
    // Save caching settings
    update_option('env_cache_enabled', isset($_POST['cache_enabled']) ? 1 : 0);
    update_option('env_cache_ttl', intval($_POST['cache_ttl']));
    update_option('env_object_cache_enabled', isset($_POST['object_cache_enabled']) ? 1 : 0);
    
    // Save image optimization settings
    update_option('env_lazy_loading_enabled', isset($_POST['lazy_loading_enabled']) ? 1 : 0);
    update_option('env_image_compression_enabled', isset($_POST['image_compression_enabled']) ? 1 : 0);
    update_option('env_webp_enabled', isset($_POST['webp_enabled']) ? 1 : 0);
    
    // Save database optimization settings
    update_option('env_db_cleanup_enabled', isset($_POST['db_cleanup_enabled']) ? 1 : 0);
    update_option('env_db_cleanup_frequency', sanitize_text_field($_POST['db_cleanup_frequency']));
    
    echo '<div class="notice notice-success"><p>Performance settings saved successfully!</p></div>';
}

// Get current settings
$cdn_url = get_option('env_cdn_url', '');
$cdn_enabled = get_option('env_cdn_enabled', 0);
$cache_enabled = get_option('env_cache_enabled', 1);
$cache_ttl = get_option('env_cache_ttl', 3600);
$object_cache_enabled = get_option('env_object_cache_enabled', 1);
$lazy_loading_enabled = get_option('env_lazy_loading_enabled', 1);
$image_compression_enabled = get_option('env_image_compression_enabled', 1);
$webp_enabled = get_option('env_webp_enabled', 1);
$db_cleanup_enabled = get_option('env_db_cleanup_enabled', 1);
$db_cleanup_frequency = get_option('env_db_cleanup_frequency', 'daily');

// Get performance metrics
global $wpdb;
$metrics_table = $wpdb->prefix . 'performance_metrics';
$recent_metrics = $wpdb->get_results("
    SELECT * FROM $metrics_table 
    ORDER BY timestamp DESC 
    LIMIT 10
");

$avg_metrics = $wpdb->get_row("
    SELECT 
        AVG(load_time) as avg_load_time,
        AVG(memory_usage) as avg_memory_usage,
        AVG(query_count) as avg_query_count,
        AVG(cache_hit_ratio) as avg_cache_hit_ratio
    FROM $metrics_table 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Performance Dashboard -->
    <div class="postbox-container" style="width: 100%;">
        <div class="meta-box-sortables">
            <div class="postbox">
                <h2 class="hndle"><span>Performance Dashboard</span></h2>
                <div class="inside">
                    <div class="performance-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div class="stat-box" style="text-align: center; padding: 20px; background: #f1f1f1; border-radius: 8px;">
                            <h3 style="margin: 0; color: #2e7d32;">Average Load Time</h3>
                            <p style="font-size: 24px; margin: 10px 0; font-weight: bold;">
                                <?php echo $avg_metrics ? number_format($avg_metrics->avg_load_time, 2) : '0.00'; ?>s
                            </p>
                        </div>
                        <div class="stat-box" style="text-align: center; padding: 20px; background: #f1f1f1; border-radius: 8px;">
                            <h3 style="margin: 0; color: #2e7d32;">Memory Usage</h3>
                            <p style="font-size: 24px; margin: 10px 0; font-weight: bold;">
                                <?php echo $avg_metrics ? number_format($avg_metrics->avg_memory_usage / (1024*1024), 1) : '0.0'; ?>MB
                            </p>
                        </div>
                        <div class="stat-box" style="text-align: center; padding: 20px; background: #f1f1f1; border-radius: 8px;">
                            <h3 style="margin: 0; color: #2e7d32;">Average Queries</h3>
                            <p style="font-size: 24px; margin: 10px 0; font-weight: bold;">
                                <?php echo $avg_metrics ? number_format($avg_metrics->avg_query_count) : '0'; ?>
                            </p>
                        </div>
                        <div class="stat-box" style="text-align: center; padding: 20px; background: #f1f1f1; border-radius: 8px;">
                            <h3 style="margin: 0; color: #2e7d32;">Cache Hit Ratio</h3>
                            <p style="font-size: 24px; margin: 10px 0; font-weight: bold;">
                                <?php echo $avg_metrics ? number_format($avg_metrics->avg_cache_hit_ratio, 1) : '0.0'; ?>%
                            </p>
                        </div>
                    </div>
                    
                    <!-- Performance Actions -->
                    <div class="performance-actions" style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <button type="button" class="button button-primary" onclick="clearAllCaches()">Clear All Caches</button>
                        <button type="button" class="button button-secondary" onclick="optimizeDatabase()">Optimize Database</button>
                        <button type="button" class="button button-secondary" onclick="runPerformanceTest()">Run Performance Test</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Form -->
    <form method="post" action="">
        <?php wp_nonce_field('env_performance_settings'); ?>
        
        <!-- CDN Settings -->
        <div class="postbox">
            <h2 class="hndle"><span>CDN Settings</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable CDN</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cdn_enabled" value="1" <?php checked($cdn_enabled, 1); ?>>
                                Enable Content Delivery Network
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">CDN URL</th>
                        <td>
                            <input type="url" name="cdn_url" value="<?php echo esc_attr($cdn_url); ?>" class="regular-text" placeholder="https://cdn.yourdomain.com">
                            <p class="description">Enter your CDN URL (e.g., CloudFront, CloudFlare)</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Caching Settings -->
        <div class="postbox">
            <h2 class="hndle"><span>Caching Settings</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Caching</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cache_enabled" value="1" <?php checked($cache_enabled, 1); ?>>
                                Enable page caching
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache TTL</th>
                        <td>
                            <input type="number" name="cache_ttl" value="<?php echo esc_attr($cache_ttl); ?>" min="300" max="86400">
                            <p class="description">Cache time-to-live in seconds (300-86400)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Object Cache</th>
                        <td>
                            <label>
                                <input type="checkbox" name="object_cache_enabled" value="1" <?php checked($object_cache_enabled, 1); ?>>
                                Enable Redis/Memcached object caching
                            </label>
                            <p class="description">Requires Redis or Memcached server</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Image Optimization -->
        <div class="postbox">
            <h2 class="hndle"><span>Image Optimization</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">Lazy Loading</th>
                        <td>
                            <label>
                                <input type="checkbox" name="lazy_loading_enabled" value="1" <?php checked($lazy_loading_enabled, 1); ?>>
                                Enable lazy loading for images
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Image Compression</th>
                        <td>
                            <label>
                                <input type="checkbox" name="image_compression_enabled" value="1" <?php checked($image_compression_enabled, 1); ?>>
                                Enable automatic image compression
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WebP Support</th>
                        <td>
                            <label>
                                <input type="checkbox" name="webp_enabled" value="1" <?php checked($webp_enabled, 1); ?>>
                                Enable WebP image format
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Database Optimization -->
        <div class="postbox">
            <h2 class="hndle"><span>Database Optimization</span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto Cleanup</th>
                        <td>
                            <label>
                                <input type="checkbox" name="db_cleanup_enabled" value="1" <?php checked($db_cleanup_enabled, 1); ?>>
                                Enable automatic database cleanup
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cleanup Frequency</th>
                        <td>
                            <select name="db_cleanup_frequency">
                                <option value="daily" <?php selected($db_cleanup_frequency, 'daily'); ?>>Daily</option>
                                <option value="weekly" <?php selected($db_cleanup_frequency, 'weekly'); ?>>Weekly</option>
                                <option value="monthly" <?php selected($db_cleanup_frequency, 'monthly'); ?>>Monthly</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button('Save Performance Settings', 'primary', 'save_performance_settings'); ?>
    </form>
    
    <!-- Recent Performance Metrics -->
    <?php if (!empty($recent_metrics)): ?>
    <div class="postbox">
        <h2 class="hndle"><span>Recent Performance Metrics</span></h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Load Time</th>
                        <th>Memory Usage</th>
                        <th>Query Count</th>
                        <th>Cache Hit Ratio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_metrics as $metric): ?>
                    <tr>
                        <td><?php echo esc_html($metric->timestamp); ?></td>
                        <td><?php echo number_format($metric->load_time, 3); ?>s</td>
                        <td><?php echo number_format($metric->memory_usage / (1024*1024), 1); ?>MB</td>
                        <td><?php echo number_format($metric->query_count); ?></td>
                        <td><?php echo number_format($metric->cache_hit_ratio, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function clearAllCaches() {
    if (confirm('Are you sure you want to clear all caches?')) {
        jQuery.post(ajaxurl, {
            action: 'env_clear_caches',
            nonce: '<?php echo wp_create_nonce('env_clear_caches'); ?>'
        }, function(response) {
            if (response.success) {
                alert('All caches cleared successfully!');
                location.reload();
            } else {
                alert('Error clearing caches: ' + response.data);
            }
        });
    }
}

function optimizeDatabase() {
    if (confirm('Are you sure you want to optimize the database?')) {
        jQuery.post(ajaxurl, {
            action: 'env_optimize_database',
            nonce: '<?php echo wp_create_nonce('env_optimize_database'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Database optimized successfully!');
                location.reload();
            } else {
                alert('Error optimizing database: ' + response.data);
            }
        });
    }
}

function runPerformanceTest() {
    alert('Performance test started. Results will be available in a few minutes.');
    jQuery.post(ajaxurl, {
        action: 'env_run_performance_test',
        nonce: '<?php echo wp_create_nonce('env_run_performance_test'); ?>'
    }, function(response) {
        if (response.success) {
            alert('Performance test completed!');
            location.reload();
        } else {
            alert('Error running performance test: ' + response.data);
        }
    });
}
</script>
