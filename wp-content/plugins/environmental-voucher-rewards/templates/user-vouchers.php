<?php
/**
 * Template for displaying user vouchers
 * 
 * @package Environmental_Voucher_Rewards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    echo '<p>' . __('Please log in to view your vouchers.', 'env-voucher-rewards') . '</p>';
    return;
}

// Get user vouchers
$db_manager = EVR_Database_Manager::get_instance();
$vouchers = $db_manager->get_user_vouchers($user_id);
?>

<div class="evr-user-vouchers-container" id="user-vouchers-container">
    <div class="vouchers-header">
        <h3><?php _e('My Vouchers', 'env-voucher-rewards'); ?></h3>
        
        <div class="vouchers-controls">
            <div class="vouchers-filters">
                <button class="filter-btn active" data-filter="all"><?php _e('All', 'env-voucher-rewards'); ?></button>
                <button class="filter-btn" data-filter="active"><?php _e('Active', 'env-voucher-rewards'); ?></button>
                <button class="filter-btn" data-filter="used"><?php _e('Used', 'env-voucher-rewards'); ?></button>
                <button class="filter-btn" data-filter="expired"><?php _e('Expired', 'env-voucher-rewards'); ?></button>
            </div>
            
            <div class="vouchers-search">
                <form class="voucher-search-form">
                    <input type="search" placeholder="<?php _e('Search vouchers...', 'env-voucher-rewards'); ?>" />
                    <button type="submit"><?php _e('Search', 'env-voucher-rewards'); ?></button>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (empty($vouchers)): ?>
        <div class="no-vouchers">
            <div class="no-vouchers-icon">🎫</div>
            <h4><?php _e('No Vouchers Yet', 'env-voucher-rewards'); ?></h4>
            <p><?php _e('Complete environmental actions to earn vouchers and rewards!', 'env-voucher-rewards'); ?></p>
            <a href="<?php echo home_url('/environmental-activities'); ?>" class="btn btn-primary">
                <?php _e('Start Earning', 'env-voucher-rewards'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="vouchers-grid">
            <?php foreach ($vouchers as $voucher): ?>
                <?php
                $status = $voucher->status;
                $is_expired = strtotime($voucher->expiry_date) < time();
                if ($is_expired && $status === 'active') {
                    $status = 'expired';
                }
                ?>
                <div class="voucher-card <?php echo esc_attr($status); ?>" 
                     data-voucher-id="<?php echo esc_attr($voucher->id); ?>"
                     data-status="<?php echo esc_attr($status); ?>"
                     data-type="<?php echo esc_attr($voucher->discount_type); ?>"
                     data-value="<?php echo esc_attr($voucher->discount_amount); ?>"
                     data-created="<?php echo esc_attr($voucher->created_at); ?>"
                     data-expiry="<?php echo esc_attr($voucher->expiry_date); ?>">
                    
                    <div class="voucher-header">
                        <div class="voucher-type-badge">
                            <?php echo esc_html(ucfirst($voucher->discount_type)); ?>
                        </div>
                        <div class="voucher-status-badge <?php echo esc_attr($status); ?>">
                            <?php echo esc_html(ucfirst($status)); ?>
                        </div>
                    </div>
                    
                    <div class="voucher-content">
                        <h4 class="voucher-title"><?php echo esc_html($voucher->title); ?></h4>
                        <p class="voucher-description"><?php echo esc_html($voucher->description); ?></p>
                        
                        <div class="voucher-value">
                            <?php if ($voucher->discount_type === 'percentage'): ?>
                                <span class="value-amount"><?php echo esc_html($voucher->discount_amount); ?>%</span>
                                <span class="value-label"><?php _e('Off', 'env-voucher-rewards'); ?></span>
                            <?php elseif ($voucher->discount_type === 'fixed'): ?>
                                <span class="value-amount">$<?php echo esc_html($voucher->discount_amount); ?></span>
                                <span class="value-label"><?php _e('Off', 'env-voucher-rewards'); ?></span>
                            <?php else: ?>
                                <span class="value-label"><?php _e('Free Item', 'env-voucher-rewards'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="voucher-code">
                            <span class="code-label"><?php _e('Code:', 'env-voucher-rewards'); ?></span>
                            <span class="code-value"><?php echo esc_html($voucher->code); ?></span>
                        </div>
                        
                        <div class="voucher-expiry">
                            <span class="expiry-label"><?php _e('Expires:', 'env-voucher-rewards'); ?></span>
                            <span class="voucher-countdown" data-expiry="<?php echo esc_attr($voucher->expiry_date); ?>">
                                <?php echo esc_html(date('M j, Y', strtotime($voucher->expiry_date))); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="voucher-actions">
                        <?php if ($status === 'active'): ?>
                            <button class="btn btn-primary apply-voucher-btn" 
                                    data-voucher-id="<?php echo esc_attr($voucher->id); ?>"
                                    data-voucher-code="<?php echo esc_attr($voucher->code); ?>">
                                <span class="btn-text"><?php _e('Apply to Cart', 'env-voucher-rewards'); ?></span>
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-secondary qr-toggle-btn" 
                                data-voucher-id="<?php echo esc_attr($voucher->id); ?>">
                            <span class="btn-text"><?php _e('Show QR', 'env-voucher-rewards'); ?></span>
                        </button>
                        
                        <button class="btn btn-secondary share-voucher-btn" 
                                data-voucher-id="<?php echo esc_attr($voucher->id); ?>"
                                data-voucher-title="<?php echo esc_attr($voucher->title); ?>">
                            <span class="btn-text"><?php _e('Share', 'env-voucher-rewards'); ?></span>
                        </button>
                    </div>
                    
                    <div class="qr-container" style="display: none;">
                        <!-- QR code will be loaded here -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="vouchers-pagination">
            <!-- Pagination will be handled by JavaScript -->
        </div>
    <?php endif; ?>
</div>

<style>
.evr-user-vouchers-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.vouchers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.vouchers-controls {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.vouchers-filters {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 8px 16px;
    border: 2px solid #27ae60;
    background: transparent;
    color: #27ae60;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn.active,
.filter-btn:hover {
    background: #27ae60;
    color: white;
}

.voucher-search-form {
    display: flex;
    gap: 10px;
}

.voucher-search-form input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 200px;
}

.vouchers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.no-vouchers {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.no-vouchers-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .vouchers-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .vouchers-controls {
        flex-direction: column;
    }
    
    .vouchers-grid {
        grid-template-columns: 1fr;
    }
}
</style>
