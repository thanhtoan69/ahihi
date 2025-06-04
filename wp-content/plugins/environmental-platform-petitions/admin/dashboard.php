<?php
/**
 * Admin Dashboard Page
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard statistics
$stats = Environmental_Platform_Petitions_Database::get_dashboard_statistics();
$recent_signatures = Environmental_Platform_Petitions_Database::get_recent_signatures(10);
$top_petitions = Environmental_Platform_Petitions_Database::get_top_petitions_by_signatures(5);
$pending_verifications = Environmental_Platform_Petitions_Database::get_pending_verifications(5);
?>

<div class="wrap petition-admin-dashboard">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="title-count">(<?php echo number_format($stats['total_petitions']); ?> Petitions)</span>
    </h1>
    
    <a href="<?php echo admin_url('post-new.php?post_type=env_petition'); ?>" class="page-title-action">
        Add New Petition
    </a>
    
    <hr class="wp-header-end">
    
    <!-- Dashboard Statistics -->
    <div class="petition-dashboard-stats">
        <div class="stats-grid">
            <div class="stat-card total-signatures">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total_signatures']); ?></div>
                    <div class="stat-label">Total Signatures</div>
                    <div class="stat-change positive">
                        +<?php echo number_format($stats['signatures_today']); ?> today
                    </div>
                </div>
            </div>
            
            <div class="stat-card active-petitions">
                <div class="stat-icon">
                    <span class="dashicons dashicons-admin-post"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['active_petitions']); ?></div>
                    <div class="stat-label">Active Petitions</div>
                    <div class="stat-change">
                        <?php echo number_format($stats['total_petitions']); ?> total
                    </div>
                </div>
            </div>
            
            <div class="stat-card pending-verifications">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['pending_verifications']); ?></div>
                    <div class="stat-label">Pending Verifications</div>
                    <div class="stat-change <?php echo $stats['pending_verifications'] > 10 ? 'warning' : ''; ?>">
                        <?php echo $stats['pending_verifications'] > 10 ? 'High' : 'Normal'; ?>
                    </div>
                </div>
            </div>
            
            <div class="stat-card conversion-rate">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['conversion_rate'], 1); ?>%</div>
                    <div class="stat-label">Conversion Rate</div>
                    <div class="stat-change <?php echo $stats['conversion_rate'] > 5 ? 'positive' : 'neutral'; ?>">
                        <?php echo $stats['conversion_rate'] > 5 ? 'Good' : 'Average'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Dashboard Content -->
    <div class="dashboard-content">
        <div class="dashboard-left">
            
            <!-- Recent Activity -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>Recent Signatures</h3>
                    <a href="<?php echo admin_url('admin.php?page=petition-signatures'); ?>" class="widget-action">
                        View All
                    </a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($recent_signatures)): ?>
                        <div class="signatures-list">
                            <?php foreach ($recent_signatures as $signature): ?>
                                <div class="signature-item">
                                    <div class="signature-avatar">
                                        <?php echo get_avatar($signature->email, 32); ?>
                                    </div>
                                    <div class="signature-details">
                                        <div class="signature-name">
                                            <?php echo esc_html($signature->first_name . ' ' . $signature->last_name); ?>
                                            <?php if ($signature->verification_status === 'verified'): ?>
                                                <span class="verified-badge">✓</span>
                                            <?php elseif ($signature->verification_status === 'pending'): ?>
                                                <span class="pending-badge">⏳</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="signature-meta">
                                            <span class="petition-title">
                                                <?php echo esc_html(get_the_title($signature->petition_id)); ?>
                                            </span>
                                            <span class="signature-time">
                                                <?php echo human_time_diff(strtotime($signature->created_at), current_time('timestamp')); ?> ago
                                            </span>
                                        </div>
                                        <?php if (!empty($signature->comment)): ?>
                                            <div class="signature-comment">
                                                "<?php echo esc_html(wp_trim_words($signature->comment, 15)); ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="signature-actions">
                                        <?php if ($signature->verification_status === 'pending'): ?>
                                            <button class="button-small verify-signature" 
                                                    data-signature-id="<?php echo $signature->id; ?>">
                                                Verify
                                            </button>
                                        <?php endif; ?>
                                        <button class="button-small view-signature" 
                                                data-signature-id="<?php echo $signature->id; ?>">
                                            View
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No recent signatures found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Performance Chart -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>Signature Trends (Last 30 Days)</h3>
                    <div class="widget-controls">
                        <select id="chart-period">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="chart-container">
                        <canvas id="signatures-chart" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #2E8B57;"></span>
                            <span class="legend-label">Daily Signatures</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: #228B22;"></span>
                            <span class="legend-label">Verification Rate</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <div class="dashboard-right">
            
            <!-- Top Performing Petitions -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>Top Performing Petitions</h3>
                    <a href="<?php echo admin_url('edit.php?post_type=env_petition'); ?>" class="widget-action">
                        View All
                    </a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($top_petitions)): ?>
                        <div class="petitions-list">
                            <?php foreach ($top_petitions as $index => $petition): ?>
                                <div class="petition-item">
                                    <div class="petition-rank">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    <div class="petition-details">
                                        <div class="petition-title">
                                            <a href="<?php echo get_edit_post_link($petition->petition_id); ?>">
                                                <?php echo esc_html(get_the_title($petition->petition_id)); ?>
                                            </a>
                                        </div>
                                        <div class="petition-stats">
                                            <span class="signature-count">
                                                <?php echo number_format($petition->signature_count); ?> signatures
                                            </span>
                                            <span class="completion-rate">
                                                <?php 
                                                $goal = get_post_meta($petition->petition_id, 'petition_goal', true) ?: 1000;
                                                $percentage = ($petition->signature_count / $goal) * 100;
                                                echo number_format($percentage, 1); 
                                                ?>% complete
                                            </span>
                                        </div>
                                        <div class="petition-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min(100, $percentage); ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="petition-actions">
                                        <a href="<?php echo get_permalink($petition->petition_id); ?>" 
                                           target="_blank" class="button-small">View</a>
                                        <a href="<?php echo get_edit_post_link($petition->petition_id); ?>" 
                                           class="button-small">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No petitions found.</p>
                            <a href="<?php echo admin_url('post-new.php?post_type=env_petition'); ?>" 
                               class="button">Create Your First Petition</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Verifications -->
            <?php if (!empty($pending_verifications)): ?>
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>Pending Verifications</h3>
                    <a href="<?php echo admin_url('admin.php?page=petition-verifications'); ?>" class="widget-action">
                        View All
                    </a>
                </div>
                <div class="widget-content">
                    <div class="verifications-list">
                        <?php foreach ($pending_verifications as $verification): ?>
                            <div class="verification-item">
                                <div class="verification-details">
                                    <div class="verification-name">
                                        <?php echo esc_html($verification->first_name . ' ' . $verification->last_name); ?>
                                    </div>
                                    <div class="verification-meta">
                                        <span class="petition-title">
                                            <?php echo esc_html(get_the_title($verification->petition_id)); ?>
                                        </span>
                                        <span class="verification-time">
                                            <?php echo human_time_diff(strtotime($verification->created_at), current_time('timestamp')); ?> ago
                                        </span>
                                    </div>
                                </div>
                                <div class="verification-actions">
                                    <button class="button-small button-primary verify-signature" 
                                            data-signature-id="<?php echo $verification->id; ?>">
                                        Verify
                                    </button>
                                    <button class="button-small reject-signature" 
                                            data-signature-id="<?php echo $verification->id; ?>">
                                        Reject
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="widget-content">
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=env_petition'); ?>" 
                           class="quick-action-button">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Create New Petition
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-analytics'); ?>" 
                           class="quick-action-button">
                            <span class="dashicons dashicons-chart-bar"></span>
                            View Analytics
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-signatures'); ?>" 
                           class="quick-action-button">
                            <span class="dashicons dashicons-list-view"></span>
                            Manage Signatures
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-campaigns'); ?>" 
                           class="quick-action-button">
                            <span class="dashicons dashicons-megaphone"></span>
                            Campaign Manager
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=petition-settings'); ?>" 
                           class="quick-action-button">
                            <span class="dashicons dashicons-admin-settings"></span>
                            Settings
                        </a>
                        
                        <button class="quick-action-button export-data" id="export-signatures">
                            <span class="dashicons dashicons-download"></span>
                            Export Data
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h3>System Status</h3>
                </div>
                <div class="widget-content">
                    <div class="system-status">
                        <div class="status-item">
                            <div class="status-indicator <?php echo $stats['database_status'] === 'ok' ? 'healthy' : 'warning'; ?>"></div>
                            <div class="status-label">Database</div>
                            <div class="status-value"><?php echo ucfirst($stats['database_status']); ?></div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-indicator <?php echo $stats['email_queue'] < 100 ? 'healthy' : 'warning'; ?>"></div>
                            <div class="status-label">Email Queue</div>
                            <div class="status-value"><?php echo number_format($stats['email_queue']); ?> pending</div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-indicator healthy"></div>
                            <div class="status-label">Last Backup</div>
                            <div class="status-value"><?php echo $stats['last_backup'] ?: 'Never'; ?></div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-indicator <?php echo $stats['storage_usage'] < 80 ? 'healthy' : 'warning'; ?>"></div>
                            <div class="status-label">Storage</div>
                            <div class="status-value"><?php echo number_format($stats['storage_usage'], 1); ?>% used</div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Signature Details Modal -->
<div id="signature-modal" class="petition-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-close">&times;</span>
            <h3>Signature Details</h3>
        </div>
        <div class="modal-body" id="signature-modal-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize dashboard
    PetitionAdminDashboard.init();
    
    // Chart initialization
    if (typeof Chart !== 'undefined') {
        PetitionAdminDashboard.initCharts();
    }
});
</script>
