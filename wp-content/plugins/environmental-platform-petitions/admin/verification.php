<?php
/**
 * Admin Verification Management Page
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['signature_ids'])) {
    $action = sanitize_text_field($_POST['action']);
    $signature_ids = array_map('intval', $_POST['signature_ids']);
    
    switch ($action) {
        case 'verify':
            foreach ($signature_ids as $id) {
                Environmental_Platform_Petitions_Verification_System::verify_signature($id);
            }
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Selected signatures have been verified.</p></div>';
            });
            break;
            
        case 'reject':
            foreach ($signature_ids as $id) {
                Environmental_Platform_Petitions_Verification_System::reject_signature($id);
            }
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Selected signatures have been rejected.</p></div>';
            });
            break;
            
        case 'resend':
            foreach ($signature_ids as $id) {
                Environmental_Platform_Petitions_Verification_System::resend_verification($id);
            }
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Verification emails have been resent.</p></div>';
            });
            break;
    }
}

// Get verification data
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$petition_filter = isset($_GET['petition']) ? intval($_GET['petition']) : 0;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

$verifications = Environmental_Platform_Petitions_Database::get_verifications([
    'status' => $status_filter,
    'petition_id' => $petition_filter,
    'search' => $search,
    'per_page' => $per_page,
    'page' => $current_page
]);

$total_items = Environmental_Platform_Petitions_Database::get_verifications_count([
    'status' => $status_filter,
    'petition_id' => $petition_filter,
    'search' => $search
]);

$total_pages = ceil($total_items / $per_page);

// Get petitions for filter
$petitions = get_posts(['post_type' => 'env_petition', 'numberposts' => -1, 'post_status' => 'publish']);

// Get verification statistics
$verification_stats = Environmental_Platform_Petitions_Database::get_verification_statistics();
?>

<div class="wrap petition-verifications">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="title-count">(<?php echo number_format($total_items); ?> items)</span>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Verification Statistics -->
    <div class="verification-stats">
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($verification_stats['pending']); ?></div>
                    <div class="stat-label">Pending Verification</div>
                    <div class="stat-action">
                        <a href="?page=petition-verifications&status=pending">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="stat-card verified">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($verification_stats['verified']); ?></div>
                    <div class="stat-label">Verified</div>
                    <div class="stat-action">
                        <a href="?page=petition-verifications&status=verified">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($verification_stats['rejected']); ?></div>
                    <div class="stat-label">Rejected</div>
                    <div class="stat-action">
                        <a href="?page=petition-verifications&status=rejected">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="stat-card expired">
                <div class="stat-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($verification_stats['expired']); ?></div>
                    <div class="stat-label">Expired</div>
                    <div class="stat-action">
                        <a href="?page=petition-verifications&status=expired">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="verification-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="petition-verifications">
            
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" name="status">
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                        <option value="verified" <?php selected($status_filter, 'verified'); ?>>Verified</option>
                        <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
                        <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                        <option value="all" <?php selected($status_filter, 'all'); ?>>All Statuses</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="petition-filter">Petition:</label>
                    <select id="petition-filter" name="petition">
                        <option value="0">All Petitions</option>
                        <?php foreach ($petitions as $petition): ?>
                            <option value="<?php echo $petition->ID; ?>" <?php selected($petition_filter, $petition->ID); ?>>
                                <?php echo esc_html($petition->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search-input">Search:</label>
                    <input type="text" id="search-input" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Search by name or email...">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button">Filter</button>
                    <a href="?page=petition-verifications" class="button">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Bulk Actions -->
    <form method="post" id="verification-form">
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="">Bulk Actions</option>
                    <?php if ($status_filter === 'pending'): ?>
                        <option value="verify">Verify</option>
                        <option value="reject">Reject</option>
                        <option value="resend">Resend Verification</option>
                    <?php endif; ?>
                    <?php if ($status_filter === 'rejected'): ?>
                        <option value="verify">Verify</option>
                        <option value="resend">Resend Verification</option>
                    <?php endif; ?>
                    <?php if ($status_filter === 'expired'): ?>
                        <option value="resend">Resend Verification</option>
                        <option value="reject">Mark as Rejected</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="button action" id="doaction">Apply</button>
            </div>
            
            <!-- Pagination -->
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total_items); ?> items</span>
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-links">
                        <?php if ($current_page > 1): ?>
                            <a class="button" href="<?php echo add_query_arg('paged', $current_page - 1); ?>">‹ Previous</a>
                        <?php endif; ?>
                        
                        <span class="current-page"><?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a class="button" href="<?php echo add_query_arg('paged', $current_page + 1); ?>">Next ›</a>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Verifications Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th class="manage-column column-signer">Signer</th>
                    <th class="manage-column column-petition">Petition</th>
                    <th class="manage-column column-status">Status</th>
                    <th class="manage-column column-verification-method">Method</th>
                    <th class="manage-column column-date">Date</th>
                    <th class="manage-column column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($verifications)): ?>
                    <?php foreach ($verifications as $verification): ?>
                        <tr class="verification-row status-<?php echo esc_attr($verification->verification_status); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="signature_ids[]" value="<?php echo $verification->id; ?>">
                            </th>
                            
                            <td class="column-signer">
                                <div class="signer-info">
                                    <div class="signer-avatar">
                                        <?php echo get_avatar($verification->email, 32); ?>
                                    </div>
                                    <div class="signer-details">
                                        <div class="signer-name">
                                            <strong><?php echo esc_html($verification->first_name . ' ' . $verification->last_name); ?></strong>
                                        </div>
                                        <div class="signer-email">
                                            <a href="mailto:<?php echo esc_attr($verification->email); ?>">
                                                <?php echo esc_html($verification->email); ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($verification->phone)): ?>
                                            <div class="signer-phone">
                                                <?php echo esc_html($verification->phone); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-petition">
                                <div class="petition-info">
                                    <div class="petition-title">
                                        <a href="<?php echo get_edit_post_link($verification->petition_id); ?>">
                                            <?php echo esc_html(get_the_title($verification->petition_id)); ?>
                                        </a>
                                    </div>
                                    <div class="petition-id">
                                        ID: <?php echo $verification->petition_id; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-status">
                                <div class="status-badge status-<?php echo esc_attr($verification->verification_status); ?>">
                                    <?php
                                    $status_labels = [
                                        'pending' => 'Pending',
                                        'verified' => 'Verified',
                                        'rejected' => 'Rejected',
                                        'expired' => 'Expired'
                                    ];
                                    echo esc_html($status_labels[$verification->verification_status] ?? ucfirst($verification->verification_status));
                                    ?>
                                </div>
                                
                                <?php if ($verification->verification_status === 'pending'): ?>
                                    <div class="time-remaining">
                                        <?php
                                        $expires_at = strtotime($verification->created_at) + (24 * 60 * 60); // 24 hours
                                        $time_left = $expires_at - time();
                                        if ($time_left > 0) {
                                            echo 'Expires in ' . human_time_diff(time(), $expires_at);
                                        } else {
                                            echo 'Expired';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="column-verification-method">
                                <div class="verification-methods">
                                    <?php
                                    $methods = json_decode($verification->verification_methods ?? '[]', true);
                                    if (empty($methods)) {
                                        $methods = ['email']; // Default to email
                                    }
                                    
                                    foreach ($methods as $method):
                                        $method_icons = [
                                            'email' => '📧',
                                            'phone' => '📱',
                                            'manual' => '👤'
                                        ];
                                    ?>
                                        <span class="method-badge method-<?php echo esc_attr($method); ?>">
                                            <?php echo $method_icons[$method] ?? '?'; ?>
                                            <?php echo ucfirst($method); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            
                            <td class="column-date">
                                <div class="date-info">
                                    <div class="created-date">
                                        <?php echo date('M j, Y', strtotime($verification->created_at)); ?>
                                    </div>
                                    <div class="created-time">
                                        <?php echo date('g:i A', strtotime($verification->created_at)); ?>
                                    </div>
                                    <div class="time-ago">
                                        <?php echo human_time_diff(strtotime($verification->created_at), current_time('timestamp')); ?> ago
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-actions">
                                <div class="row-actions">
                                    <?php if ($verification->verification_status === 'pending'): ?>
                                        <button class="button-small button-primary verify-single" 
                                                data-signature-id="<?php echo $verification->id; ?>">
                                            Verify
                                        </button>
                                        <button class="button-small reject-single" 
                                                data-signature-id="<?php echo $verification->id; ?>">
                                            Reject
                                        </button>
                                        <button class="button-small resend-single" 
                                                data-signature-id="<?php echo $verification->id; ?>">
                                            Resend
                                        </button>
                                    <?php elseif ($verification->verification_status === 'rejected'): ?>
                                        <button class="button-small button-primary verify-single" 
                                                data-signature-id="<?php echo $verification->id; ?>">
                                            Verify
                                        </button>
                                    <?php elseif ($verification->verification_status === 'expired'): ?>
                                        <button class="button-small resend-single" 
                                                data-signature-id="<?php echo $verification->id; ?>">
                                            Resend
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="button-small view-details" 
                                            data-signature-id="<?php echo $verification->id; ?>">
                                        Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td colspan="7">
                            <div class="no-data">
                                <p>No verifications found for the selected criteria.</p>
                                <?php if (!empty($search) || $petition_filter || $status_filter !== 'pending'): ?>
                                    <a href="?page=petition-verifications" class="button">View All Pending Verifications</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Bottom pagination -->
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-links">
                        <?php if ($current_page > 1): ?>
                            <a class="button" href="<?php echo add_query_arg('paged', $current_page - 1); ?>">‹ Previous</a>
                        <?php endif; ?>
                        
                        <span class="current-page"><?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a class="button" href="<?php echo add_query_arg('paged', $current_page + 1); ?>">Next ›</a>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Verification Details Modal -->
<div id="verification-details-modal" class="petition-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-close">&times;</span>
            <h3>Verification Details</h3>
        </div>
        <div class="modal-body" id="verification-details-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize verification management
    PetitionVerificationManager.init();
});
</script>
