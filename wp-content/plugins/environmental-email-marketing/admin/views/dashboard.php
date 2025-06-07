<?php
/**
 * Environmental Email Marketing - Admin Dashboard Template
 * Main dashboard view with statistics, charts, and quick actions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$dashboard_data = $this->get_dashboard_data();
$stats = $dashboard_data['stats'];
$recent_campaigns = $dashboard_data['recent_campaigns'];
$recent_subscribers = $dashboard_data['recent_subscribers'];
$environmental_impact = $dashboard_data['environmental_impact'];
?>

<div class="eem-admin-wrap">
    <div class="eem-dashboard-header">
        <h1 class="eem-page-title">
            <span class="eem-environmental-icon">🌍</span>
            Environmental Email Marketing Dashboard
        </h1>
        <div class="eem-dashboard-actions">
            <button class="eem-btn eem-btn-primary eem-quick-action" data-action="new_campaign">
                <span class="dashicons dashicons-plus-alt"></span>
                New Campaign
            </button>
            <button class="eem-btn eem-btn-secondary eem-quick-action" data-action="import_subscribers">
                <span class="dashicons dashicons-upload"></span>
                Import Subscribers
            </button>
            <button class="eem-btn eem-btn-environmental eem-quick-action" data-action="sync_providers">
                <span class="dashicons dashicons-update"></span>
                Sync Providers
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="eem-stats-grid">
        <div class="eem-stat-card eem-stat-subscribers">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html(number_format($stats['subscribers'])); ?></div>
                <div class="eem-stat-label">Total Subscribers</div>
                <div class="eem-stat-trend <?php echo $stats['subscribers_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $stats['subscribers_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                    <?php echo esc_html(abs($stats['subscribers_trend'])); ?>% this month
                </div>
            </div>
        </div>

        <div class="eem-stat-card eem-stat-campaigns">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html(number_format($stats['campaigns'])); ?></div>
                <div class="eem-stat-label">Campaigns Sent</div>
                <div class="eem-stat-trend <?php echo $stats['campaigns_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $stats['campaigns_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                    <?php echo esc_html(abs($stats['campaigns_trend'])); ?>% this month
                </div>
            </div>
        </div>

        <div class="eem-stat-card eem-stat-opens">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html($stats['open_rate']); ?>%</div>
                <div class="eem-stat-label">Average Open Rate</div>
                <div class="eem-stat-trend <?php echo $stats['open_rate_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $stats['open_rate_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                    <?php echo esc_html(abs($stats['open_rate_trend'])); ?>% vs last month
                </div>
            </div>
        </div>

        <div class="eem-stat-card eem-stat-clicks">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-external"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html($stats['click_rate']); ?>%</div>
                <div class="eem-stat-label">Average Click Rate</div>
                <div class="eem-stat-trend <?php echo $stats['click_rate_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $stats['click_rate_trend'] >= 0 ? 'up' : 'down'; ?>-alt"></span>
                    <?php echo esc_html(abs($stats['click_rate_trend'])); ?>% vs last month
                </div>
            </div>
        </div>

        <div class="eem-stat-card eem-stat-environmental">
            <div class="eem-stat-icon eem-environmental-icon">
                🌱
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html(number_format($stats['environmental_score'])); ?></div>
                <div class="eem-stat-label">Environmental Score</div>
                <div class="eem-stat-trend positive">
                    <span class="dashicons dashicons-heart"></span>
                    <?php echo esc_html(number_format($stats['carbon_offset_kg'], 2)); ?>kg CO₂ saved
                </div>
            </div>
        </div>

        <div class="eem-stat-card eem-stat-automation">
            <div class="eem-stat-icon">
                <span class="dashicons dashicons-controls-repeat"></span>
            </div>
            <div class="eem-stat-content">
                <div class="eem-stat-value"><?php echo esc_html(number_format($stats['automations_active'])); ?></div>
                <div class="eem-stat-label">Active Automations</div>
                <div class="eem-stat-meta">
                    <?php echo esc_html(number_format($stats['automations_triggered'])); ?> triggered this week
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="eem-dashboard-grid">
        <!-- Chart Panels -->
        <div class="eem-dashboard-panel eem-chart-panel">
            <div class="eem-panel-header">
                <h3>Email Engagement Over Time</h3>
                <div class="eem-panel-actions">
                    <select id="eem-chart-period" class="eem-select-small">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                    <button class="eem-btn-icon eem-widget-refresh" title="Refresh">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="eem-panel-content">
                <canvas id="eem-engagement-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Environmental Impact Panel -->
        <div class="eem-dashboard-panel eem-environmental-panel">
            <div class="eem-panel-header">
                <h3>
                    <span class="eem-environmental-icon">🌍</span>
                    Environmental Impact
                </h3>
                <button class="eem-btn-icon eem-widget-refresh" title="Refresh">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            <div class="eem-panel-content">
                <div class="eem-environmental-metrics">
                    <div class="eem-env-metric">
                        <div class="eem-env-icon">🌱</div>
                        <div class="eem-env-content">
                            <div class="eem-env-value"><?php echo esc_html(number_format($environmental_impact['carbon_saved'], 2)); ?>kg</div>
                            <div class="eem-env-label">CO₂ Saved vs Print</div>
                        </div>
                    </div>
                    <div class="eem-env-metric">
                        <div class="eem-env-icon">💧</div>
                        <div class="eem-env-content">
                            <div class="eem-env-value"><?php echo esc_html(number_format($environmental_impact['water_saved'])); ?>L</div>
                            <div class="eem-env-label">Water Saved</div>
                        </div>
                    </div>
                    <div class="eem-env-metric">
                        <div class="eem-env-icon">🌳</div>
                        <div class="eem-env-content">
                            <div class="eem-env-value"><?php echo esc_html(number_format($environmental_impact['trees_saved'], 1)); ?></div>
                            <div class="eem-env-label">Trees Equivalent</div>
                        </div>
                    </div>
                    <div class="eem-env-metric">
                        <div class="eem-env-icon">⚡</div>
                        <div class="eem-env-content">
                            <div class="eem-env-value"><?php echo esc_html(number_format($environmental_impact['energy_saved'])); ?>kWh</div>
                            <div class="eem-env-label">Energy Saved</div>
                        </div>
                    </div>
                </div>
                <div class="eem-environmental-progress">
                    <div class="eem-progress-label">Monthly Environmental Goal</div>
                    <div class="eem-progress-bar-container">
                        <div class="eem-progress-bar" style="width: <?php echo min(($environmental_impact['current_score'] / $environmental_impact['monthly_goal']) * 100, 100); ?>%"></div>
                    </div>
                    <div class="eem-progress-text">
                        <?php echo esc_html($environmental_impact['current_score']); ?> / <?php echo esc_html($environmental_impact['monthly_goal']); ?> points
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="eem-dashboard-panel eem-campaigns-panel">
            <div class="eem-panel-header">
                <h3>Recent Campaigns</h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns')); ?>" class="eem-btn eem-btn-link">View All</a>
            </div>
            <div class="eem-panel-content">
                <?php if (!empty($recent_campaigns)): ?>
                    <div class="eem-campaign-list">
                        <?php foreach ($recent_campaigns as $campaign): ?>
                            <div class="eem-campaign-item">
                                <div class="eem-campaign-info">
                                    <div class="eem-campaign-name">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns&action=edit&id=' . $campaign['id'])); ?>">
                                            <?php echo esc_html($campaign['name']); ?>
                                        </a>
                                    </div>
                                    <div class="eem-campaign-meta">
                                        <?php echo esc_html(date('M j, Y', strtotime($campaign['sent_at']))); ?> • 
                                        <?php echo esc_html(number_format($campaign['recipients'])); ?> recipients
                                    </div>
                                </div>
                                <div class="eem-campaign-stats">
                                    <div class="eem-stat-mini">
                                        <div class="eem-stat-mini-value"><?php echo esc_html($campaign['open_rate']); ?>%</div>
                                        <div class="eem-stat-mini-label">Opens</div>
                                    </div>
                                    <div class="eem-stat-mini">
                                        <div class="eem-stat-mini-value"><?php echo esc_html($campaign['click_rate']); ?>%</div>
                                        <div class="eem-stat-mini-label">Clicks</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="eem-empty-state">
                        <div class="eem-empty-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <div class="eem-empty-message">
                            <h4>No campaigns yet</h4>
                            <p>Create your first environmental email campaign to get started.</p>
                            <button class="eem-btn eem-btn-primary eem-quick-action" data-action="new_campaign">
                                Create Campaign
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Subscribers -->
        <div class="eem-dashboard-panel eem-subscribers-panel">
            <div class="eem-panel-header">
                <h3>Recent Subscribers</h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=eem-subscribers')); ?>" class="eem-btn eem-btn-link">View All</a>
            </div>
            <div class="eem-panel-content">
                <?php if (!empty($recent_subscribers)): ?>
                    <div class="eem-subscriber-list">
                        <?php foreach ($recent_subscribers as $subscriber): ?>
                            <div class="eem-subscriber-item">
                                <div class="eem-subscriber-avatar">
                                    <?php echo get_avatar($subscriber['email'], 32); ?>
                                </div>
                                <div class="eem-subscriber-info">
                                    <div class="eem-subscriber-name">
                                        <?php echo esc_html($subscriber['name'] ?: $subscriber['email']); ?>
                                    </div>
                                    <div class="eem-subscriber-meta">
                                        <?php echo esc_html(date('M j, Y', strtotime($subscriber['subscribed_at']))); ?>
                                        <?php if ($subscriber['environmental_score'] > 0): ?>
                                            • <span class="eem-environmental-badge">
                                                🌱 <?php echo esc_html($subscriber['environmental_score']); ?> pts
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="eem-subscriber-status">
                                    <span class="eem-status-badge eem-status-<?php echo esc_attr($subscriber['status']); ?>">
                                        <?php echo esc_html(ucfirst($subscriber['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="eem-empty-state">
                        <div class="eem-empty-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="eem-empty-message">
                            <h4>No subscribers yet</h4>
                            <p>Start building your environmental community by adding subscription forms.</p>
                            <button class="eem-btn eem-btn-primary eem-quick-action" data-action="import_subscribers">
                                Import Subscribers
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="eem-dashboard-panel eem-actions-panel">
            <div class="eem-panel-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="eem-panel-content">
                <div class="eem-quick-actions-grid">
                    <button class="eem-quick-action-card eem-quick-action" data-action="new_campaign">
                        <div class="eem-action-icon">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </div>
                        <div class="eem-action-text">Create Campaign</div>
                    </button>
                    
                    <button class="eem-quick-action-card eem-quick-action" data-action="import_subscribers">
                        <div class="eem-action-icon">
                            <span class="dashicons dashicons-upload"></span>
                        </div>
                        <div class="eem-action-text">Import Subscribers</div>
                    </button>
                    
                    <button class="eem-quick-action-card eem-quick-action" data-action="view_analytics">
                        <div class="eem-action-icon">
                            <span class="dashicons dashicons-chart-area"></span>
                        </div>
                        <div class="eem-action-text">View Analytics</div>
                    </button>
                    
                    <button class="eem-quick-action-card eem-quick-action" data-action="sync_providers">
                        <div class="eem-action-icon">
                            <span class="dashicons dashicons-update"></span>
                        </div>
                        <div class="eem-action-text">Sync Providers</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="eem-dashboard-panel eem-status-panel">
            <div class="eem-panel-header">
                <h3>System Status</h3>
                <button class="eem-btn-icon eem-widget-refresh" title="Refresh">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            <div class="eem-panel-content">
                <div class="eem-status-items">
                    <div class="eem-status-item">
                        <div class="eem-status-indicator eem-status-<?php echo $dashboard_data['system_status']['email_provider']; ?>"></div>
                        <div class="eem-status-label">Email Provider</div>
                        <div class="eem-status-value"><?php echo esc_html($dashboard_data['system_status']['provider_name']); ?></div>
                    </div>
                    
                    <div class="eem-status-item">
                        <div class="eem-status-indicator eem-status-<?php echo $dashboard_data['system_status']['automation_engine']; ?>"></div>
                        <div class="eem-status-label">Automation Engine</div>
                        <div class="eem-status-value"><?php echo $dashboard_data['system_status']['automations_active']; ?> active</div>
                    </div>
                    
                    <div class="eem-status-item">
                        <div class="eem-status-indicator eem-status-<?php echo $dashboard_data['system_status']['queue_processor']; ?>"></div>
                        <div class="eem-status-label">Queue Processor</div>
                        <div class="eem-status-value"><?php echo $dashboard_data['system_status']['queue_size']; ?> in queue</div>
                    </div>
                    
                    <div class="eem-status-item">
                        <div class="eem-status-indicator eem-status-<?php echo $dashboard_data['system_status']['webhooks']; ?>"></div>
                        <div class="eem-status-label">Webhooks</div>
                        <div class="eem-status-value"><?php echo $dashboard_data['system_status']['webhook_count']; ?> configured</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="text/javascript">
// Dashboard-specific JavaScript
jQuery(document).ready(function($) {
    // Initialize dashboard charts with data
    const chartData = <?php echo json_encode($dashboard_data['chart_data']); ?>;
    
    // Period change handler
    $('#eem-chart-period').on('change', function() {
        const period = $(this).val();
        updateChartData(period);
    });
    
    function updateChartData(period) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eem_get_chart_data',
                nonce: '<?php echo wp_create_nonce('eem_admin_nonce'); ?>',
                period: period
            },
            success: function(response) {
                if (response.success && window.eemAdmin && window.eemAdmin.currentChart) {
                    window.eemAdmin.currentChart.data = response.data;
                    window.eemAdmin.currentChart.update();
                }
            }
        });
    }
});
</script>
