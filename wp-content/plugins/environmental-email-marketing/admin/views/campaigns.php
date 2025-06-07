<?php
/**
 * Environmental Email Marketing - Campaign Management Template
 * Campaign list and management interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current action and campaign data
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$campaign_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

// Handle different views
switch ($action) {
    case 'new':
    case 'edit':
        include_once plugin_dir_path(__FILE__) . 'campaign-builder.php';
        return;
    case 'duplicate':
        // Handle duplication and redirect
        if ($campaign_id) {
            $duplicated_id = $this->duplicate_campaign($campaign_id);
            wp_redirect(admin_url('admin.php?page=eem-campaigns&action=edit&id=' . $duplicated_id));
            exit;
        }
        break;
    case 'delete':
        // Handle deletion
        if ($campaign_id && wp_verify_nonce($_GET['_wpnonce'], 'delete_campaign_' . $campaign_id)) {
            $this->delete_campaign($campaign_id);
            wp_redirect(admin_url('admin.php?page=eem-campaigns&deleted=1'));
            exit;
        }
        break;
}

// Get campaigns data
$campaigns_data = $this->get_campaigns_data();
$campaigns = $campaigns_data['campaigns'];
$total_campaigns = $campaigns_data['total'];
$filters = $campaigns_data['filters'];

// Get current filters
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$current_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
?>

<div class="eem-admin-wrap">
    <div class="eem-page-header">
        <div class="eem-page-title-section">
            <h1 class="eem-page-title">
                <span class="eem-environmental-icon">📧</span>
                Email Campaigns
            </h1>
            <div class="eem-page-subtitle">
                Manage your environmental email campaigns and track their impact
            </div>
        </div>
        <div class="eem-page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns&action=new')); ?>" class="eem-btn eem-btn-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                New Campaign
            </a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Campaign deleted successfully.</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sent'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>Campaign sent successfully!</p>
        </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="eem-filters-section">
        <div class="eem-filters-row">
            <div class="eem-search-box">
                <form method="get" action="">
                    <input type="hidden" name="page" value="eem-campaigns">
                    <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
                    <input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search campaigns..." class="eem-search-input">
                    <button type="submit" class="eem-btn eem-btn-secondary">
                        <span class="dashicons dashicons-search"></span>
                        Search
                    </button>
                </form>
            </div>
            
            <div class="eem-filter-controls">
                <select name="status_filter" id="eem-status-filter" class="eem-filter-select">
                    <option value="all" <?php selected($current_status, 'all'); ?>>All Statuses</option>
                    <option value="draft" <?php selected($current_status, 'draft'); ?>>Draft (<?php echo esc_html($filters['draft']); ?>)</option>
                    <option value="scheduled" <?php selected($current_status, 'scheduled'); ?>>Scheduled (<?php echo esc_html($filters['scheduled']); ?>)</option>
                    <option value="sending" <?php selected($current_status, 'sending'); ?>>Sending (<?php echo esc_html($filters['sending']); ?>)</option>
                    <option value="sent" <?php selected($current_status, 'sent'); ?>>Sent (<?php echo esc_html($filters['sent']); ?>)</option>
                    <option value="paused" <?php selected($current_status, 'paused'); ?>>Paused (<?php echo esc_html($filters['paused']); ?>)</option>
                </select>
                
                <select name="type_filter" id="eem-type-filter" class="eem-filter-select">
                    <option value="all" <?php selected($current_type, 'all'); ?>>All Types</option>
                    <option value="regular" <?php selected($current_type, 'regular'); ?>>Regular</option>
                    <option value="automated" <?php selected($current_type, 'automated'); ?>>Automated</option>
                    <option value="a_b_test" <?php selected($current_type, 'a_b_test'); ?>>A/B Test</option>
                    <option value="environmental" <?php selected($current_type, 'environmental'); ?>>Environmental</option>
                </select>
                
                <button class="eem-btn eem-btn-secondary" id="eem-apply-filters">Apply Filters</button>
                <button class="eem-btn eem-btn-link" id="eem-clear-filters">Clear</button>
            </div>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="eem-table-container">
        <?php if (!empty($campaigns)): ?>
            <form id="eem-campaigns-form" method="post">
                <?php wp_nonce_field('eem_bulk_actions', 'eem_bulk_nonce'); ?>
                
                <!-- Bulk Actions -->
                <div class="eem-bulk-actions">
                    <select name="bulk_action" id="eem-bulk-action-selector">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete</option>
                        <option value="duplicate">Duplicate</option>
                        <option value="pause">Pause</option>
                        <option value="resume">Resume</option>
                        <option value="export">Export</option>
                    </select>
                    <button type="button" class="eem-btn eem-btn-secondary eem-apply-bulk-action">Apply</button>
                </div>

                <table class="eem-table eem-campaigns-table">
                    <thead>
                        <tr>
                            <th class="eem-checkbox-column">
                                <input type="checkbox" id="eem-select-all">
                            </th>
                            <th class="eem-campaign-column">Campaign</th>
                            <th class="eem-type-column">Type</th>
                            <th class="eem-status-column">Status</th>
                            <th class="eem-recipients-column">Recipients</th>
                            <th class="eem-performance-column">Performance</th>
                            <th class="eem-environmental-column">Environmental</th>
                            <th class="eem-date-column">Date</th>
                            <th class="eem-actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr class="eem-campaign-row" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>">
                                <td class="eem-checkbox-column">
                                    <input type="checkbox" name="campaign_ids[]" value="<?php echo esc_attr($campaign['id']); ?>">
                                </td>
                                
                                <td class="eem-campaign-column">
                                    <div class="eem-campaign-info">
                                        <div class="eem-campaign-name">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns&action=edit&id=' . $campaign['id'])); ?>" class="eem-campaign-title">
                                                <?php echo esc_html($campaign['name']); ?>
                                            </a>
                                            <?php if ($campaign['is_ab_test']): ?>
                                                <span class="eem-badge eem-badge-ab">A/B</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="eem-campaign-subject">
                                            <?php echo esc_html($campaign['subject']); ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="eem-type-column">
                                    <span class="eem-campaign-type eem-type-<?php echo esc_attr($campaign['type']); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $campaign['type']))); ?>
                                    </span>
                                </td>
                                
                                <td class="eem-status-column">
                                    <span class="eem-status-badge eem-status-<?php echo esc_attr($campaign['status']); ?>">
                                        <?php echo esc_html(ucfirst($campaign['status'])); ?>
                                    </span>
                                    <?php if ($campaign['status'] === 'scheduled'): ?>
                                        <div class="eem-scheduled-time">
                                            <?php echo esc_html(date('M j, Y g:i A', strtotime($campaign['scheduled_at']))); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="eem-recipients-column">
                                    <div class="eem-recipients-number">
                                        <?php echo esc_html(number_format($campaign['recipients_count'])); ?>
                                    </div>
                                    <?php if ($campaign['list_names']): ?>
                                        <div class="eem-recipients-lists">
                                            <?php echo esc_html($campaign['list_names']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="eem-performance-column">
                                    <?php if ($campaign['status'] === 'sent'): ?>
                                        <div class="eem-performance-metrics">
                                            <div class="eem-metric">
                                                <span class="eem-metric-label">Opens:</span>
                                                <span class="eem-metric-value"><?php echo esc_html($campaign['open_rate']); ?>%</span>
                                            </div>
                                            <div class="eem-metric">
                                                <span class="eem-metric-label">Clicks:</span>
                                                <span class="eem-metric-value"><?php echo esc_html($campaign['click_rate']); ?>%</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="eem-no-data">—</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="eem-environmental-column">
                                    <div class="eem-environmental-metrics">
                                        <div class="eem-env-score">
                                            🌱 <?php echo esc_html(number_format($campaign['environmental_score'])); ?>
                                        </div>
                                        <?php if ($campaign['carbon_offset'] > 0): ?>
                                            <div class="eem-carbon-offset">
                                                <?php echo esc_html(number_format($campaign['carbon_offset'], 2)); ?>kg CO₂
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="eem-date-column">
                                    <div class="eem-date-created">
                                        <?php echo esc_html(date('M j, Y', strtotime($campaign['created_at']))); ?>
                                    </div>
                                    <?php if ($campaign['sent_at']): ?>
                                        <div class="eem-date-sent">
                                            Sent: <?php echo esc_html(date('M j, Y', strtotime($campaign['sent_at']))); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="eem-actions-column">
                                    <div class="eem-row-actions">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns&action=edit&id=' . $campaign['id'])); ?>" class="eem-action-link" title="Edit">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        
                                        <?php if ($campaign['status'] === 'sent'): ?>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-analytics&campaign_id=' . $campaign['id'])); ?>" class="eem-action-link" title="Analytics">
                                                <span class="dashicons dashicons-chart-area"></span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button class="eem-action-link eem-campaign-action" data-action="duplicate" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>" title="Duplicate">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                        
                                        <?php if (in_array($campaign['status'], ['draft', 'scheduled'])): ?>
                                            <button class="eem-action-link eem-campaign-action eem-action-danger" data-action="delete" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>" title="Delete">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <div class="eem-dropdown">
                                            <button class="eem-action-link eem-dropdown-toggle" title="More actions">
                                                <span class="dashicons dashicons-ellipsis"></span>
                                            </button>
                                            <div class="eem-dropdown-menu">
                                                <?php if ($campaign['status'] === 'draft'): ?>
                                                    <button class="eem-dropdown-item eem-campaign-action" data-action="send_test" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>">
                                                        Send Test Email
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($campaign['status'] === 'sending'): ?>
                                                    <button class="eem-dropdown-item eem-campaign-action" data-action="pause" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>">
                                                        Pause Campaign
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($campaign['status'] === 'paused'): ?>
                                                    <button class="eem-dropdown-item eem-campaign-action" data-action="resume" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>">
                                                        Resume Campaign
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="eem-dropdown-item eem-campaign-action" data-action="export" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>">
                                                    Export Data
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            
            <!-- Pagination -->
            <?php if ($total_campaigns > $per_page): ?>
                <div class="eem-pagination">
                    <?php
                    $total_pages = ceil($total_campaigns / $per_page);
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                        'total' => $total_pages,
                        'current' => $paged,
                        'type' => 'list'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="eem-empty-state eem-campaigns-empty">
                <div class="eem-empty-icon">
                    <span class="eem-environmental-icon">📧</span>
                </div>
                <div class="eem-empty-content">
                    <h3>No campaigns found</h3>
                    <?php if ($search || $current_status !== 'all' || $current_type !== 'all'): ?>
                        <p>No campaigns match your current filters. Try adjusting your search criteria.</p>
                        <button class="eem-btn eem-btn-secondary" id="eem-clear-filters">Clear Filters</button>
                    <?php else: ?>
                        <p>Create your first environmental email campaign to start engaging with your community.</p>
                        <div class="eem-empty-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns&action=new')); ?>" class="eem-btn eem-btn-primary">
                                <span class="dashicons dashicons-plus-alt"></span>
                                Create Your First Campaign
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-templates')); ?>" class="eem-btn eem-btn-secondary">
                                Browse Templates
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Campaign Action Modals -->
<div id="eem-test-email-modal" class="eem-modal" tabindex="-1">
    <div class="eem-modal-dialog">
        <div class="eem-modal-content">
            <div class="eem-modal-header">
                <h5 class="eem-modal-title">Send Test Email</h5>
                <button type="button" class="eem-modal-close" data-dismiss="modal">&times;</button>
            </div>
            <div class="eem-modal-body">
                <form id="eem-test-email-form">
                    <div class="eem-form-group">
                        <label for="test_email_address">Email Address</label>
                        <input type="email" id="test_email_address" name="test_email_address" class="eem-form-control" required>
                    </div>
                    <div class="eem-form-group">
                        <label>
                            <input type="checkbox" name="include_analytics" value="1" checked>
                            Include tracking pixels (recommended for testing)
                        </label>
                    </div>
                </form>
            </div>
            <div class="eem-modal-footer">
                <button type="button" class="eem-btn eem-btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="eem-btn eem-btn-primary" id="eem-send-test-email">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Filter handling
    $('#eem-apply-filters').on('click', function() {
        const status = $('#eem-status-filter').val();
        const type = $('#eem-type-filter').val();
        const url = new URL(window.location);
        
        if (status !== 'all') {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        if (type !== 'all') {
            url.searchParams.set('type', type);
        } else {
            url.searchParams.delete('type');
        }
        
        url.searchParams.delete('paged');
        window.location.href = url.toString();
    });
    
    $('#eem-clear-filters').on('click', function() {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        url.searchParams.delete('type');
        url.searchParams.delete('s');
        url.searchParams.delete('paged');
        window.location.href = url.toString();
    });
    
    // Select all checkbox
    $('#eem-select-all').on('change', function() {
        $('input[name="campaign_ids[]"]').prop('checked', $(this).is(':checked'));
    });
    
    // Campaign actions
    $('.eem-campaign-action').on('click', function(e) {
        e.preventDefault();
        const action = $(this).data('action');
        const campaignId = $(this).data('campaign-id');
        handleCampaignAction(action, campaignId);
    });
    
    // Dropdown menus
    $('.eem-dropdown-toggle').on('click', function(e) {
        e.stopPropagation();
        $('.eem-dropdown-menu').not($(this).next()).removeClass('show');
        $(this).next('.eem-dropdown-menu').toggleClass('show');
    });
    
    $(document).on('click', function() {
        $('.eem-dropdown-menu').removeClass('show');
    });
    
    function handleCampaignAction(action, campaignId) {
        switch (action) {
            case 'delete':
                if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
                    deleteCampaign(campaignId);
                }
                break;
            case 'duplicate':
                duplicateCampaign(campaignId);
                break;
            case 'send_test':
                openTestEmailModal(campaignId);
                break;
            case 'pause':
                pauseCampaign(campaignId);
                break;
            case 'resume':
                resumeCampaign(campaignId);
                break;
            case 'export':
                exportCampaign(campaignId);
                break;
        }
    }
    
    function openTestEmailModal(campaignId) {
        $('#eem-test-email-modal').data('campaign-id', campaignId).modal('show');
    }
    
    $('#eem-send-test-email').on('click', function() {
        const campaignId = $('#eem-test-email-modal').data('campaign-id');
        const email = $('#test_email_address').val();
        const includeAnalytics = $('input[name="include_analytics"]').is(':checked');
        
        if (!email) {
            alert('Please enter an email address.');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'eem_send_test_email',
                nonce: '<?php echo wp_create_nonce('eem_admin_nonce'); ?>',
                campaign_id: campaignId,
                test_email: email,
                include_analytics: includeAnalytics ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    alert('Test email sent successfully!');
                    $('#eem-test-email-modal').modal('hide');
                } else {
                    alert('Error sending test email: ' + response.data);
                }
            }
        });
    });
});
</script>
