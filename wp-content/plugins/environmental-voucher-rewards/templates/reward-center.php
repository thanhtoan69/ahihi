<?php
/**
 * Template for displaying reward center
 * 
 * @package Environmental_Voucher_Rewards
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    echo '<p>' . __('Please log in to access the reward center.', 'env-voucher-rewards') . '</p>';
    return;
}

// Get user data
$db_manager = EVR_Database_Manager::get_instance();
$loyalty_program = EVR_Loyalty_Program::get_instance();

$user_points = $db_manager->get_user_total_points($user_id);
$user_tier = $loyalty_program->get_user_tier($user_id);
$available_rewards = $db_manager->get_available_rewards($user_id);
$user_rewards = $db_manager->get_user_rewards($user_id, 10);
?>

<div class="evr-reward-center-container" id="reward-center-container">
    <div class="reward-center-header">
        <h3><?php _e('Reward Center', 'env-voucher-rewards'); ?></h3>
        
        <div class="user-status">
            <div class="points-balance">
                <span class="points-icon">🏆</span>
                <span class="points-amount user-points-balance"><?php echo number_format($user_points); ?></span>
                <span class="points-label"><?php _e('Points', 'env-voucher-rewards'); ?></span>
            </div>
            
            <div class="tier-status">
                <span class="tier-badge tier-<?php echo esc_attr(strtolower($user_tier['name'])); ?>">
                    <?php echo esc_html($user_tier['name']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="reward-center-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="available"><?php _e('Available Rewards', 'env-voucher-rewards'); ?></button>
            <button class="tab-btn" data-tab="my-rewards"><?php _e('My Rewards', 'env-voucher-rewards'); ?></button>
            <button class="tab-btn" data-tab="earn-points"><?php _e('Earn Points', 'env-voucher-rewards'); ?></button>
        </div>
        
        <div class="tab-content">
            <!-- Available Rewards Tab -->
            <div class="tab-panel active" id="available-rewards-panel">
                <div class="rewards-filters">
                    <button class="filter-btn active" data-filter="all"><?php _e('All', 'env-voucher-rewards'); ?></button>
                    <button class="filter-btn" data-filter="vouchers"><?php _e('Vouchers', 'env-voucher-rewards'); ?></button>
                    <button class="filter-btn" data-filter="discounts"><?php _e('Discounts', 'env-voucher-rewards'); ?></button>
                    <button class="filter-btn" data-filter="freebies"><?php _e('Free Items', 'env-voucher-rewards'); ?></button>
                </div>
                
                <?php if (empty($available_rewards)): ?>
                    <div class="no-rewards">
                        <div class="no-rewards-icon">🎁</div>
                        <h4><?php _e('No Rewards Available', 'env-voucher-rewards'); ?></h4>
                        <p><?php _e('Keep earning points to unlock amazing rewards!', 'env-voucher-rewards'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="rewards-grid">
                        <?php foreach ($available_rewards as $reward): ?>
                            <?php $can_afford = $user_points >= $reward->points_required; ?>
                            <div class="reward-card <?php echo $can_afford ? 'affordable' : 'unaffordable'; ?>" 
                                 data-reward-id="<?php echo esc_attr($reward->id); ?>"
                                 data-points-cost="<?php echo esc_attr($reward->points_required); ?>"
                                 data-type="<?php echo esc_attr($reward->reward_type); ?>">
                                
                                <div class="reward-image">
                                    <?php if ($reward->image_url): ?>
                                        <img src="<?php echo esc_url($reward->image_url); ?>" alt="<?php echo esc_attr($reward->title); ?>" />
                                    <?php else: ?>
                                        <div class="reward-placeholder">
                                            <?php echo $reward->reward_type === 'voucher' ? '🎫' : ($reward->reward_type === 'discount' ? '💰' : '🎁'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$can_afford): ?>
                                        <div class="reward-overlay">
                                            <span class="insufficient-points"><?php _e('Insufficient Points', 'env-voucher-rewards'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="reward-content">
                                    <h4 class="reward-title"><?php echo esc_html($reward->title); ?></h4>
                                    <p class="reward-description"><?php echo esc_html($reward->description); ?></p>
                                    
                                    <div class="reward-details">
                                        <?php if ($reward->reward_type === 'voucher'): ?>
                                            <div class="reward-value">
                                                <?php if ($reward->discount_type === 'percentage'): ?>
                                                    <span class="value-amount"><?php echo esc_html($reward->discount_amount); ?>%</span>
                                                    <span class="value-label"><?php _e('Off', 'env-voucher-rewards'); ?></span>
                                                <?php elseif ($reward->discount_type === 'fixed'): ?>
                                                    <span class="value-amount">$<?php echo esc_html($reward->discount_amount); ?></span>
                                                    <span class="value-label"><?php _e('Off', 'env-voucher-rewards'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="reward-cost">
                                            <span class="cost-amount"><?php echo number_format($reward->points_required); ?></span>
                                            <span class="cost-label"><?php _e('Points', 'env-voucher-rewards'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($reward->terms_conditions): ?>
                                        <div class="reward-terms">
                                            <details>
                                                <summary><?php _e('Terms & Conditions', 'env-voucher-rewards'); ?></summary>
                                                <p><?php echo esc_html($reward->terms_conditions); ?></p>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="reward-actions">
                                    <?php if ($can_afford): ?>
                                        <button class="btn btn-primary redeem-reward-btn" 
                                                data-reward-id="<?php echo esc_attr($reward->id); ?>"
                                                data-points-cost="<?php echo esc_attr($reward->points_required); ?>">
                                            <span class="btn-text"><?php _e('Redeem', 'env-voucher-rewards'); ?></span>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>
                                            <span class="btn-text">
                                                <?php 
                                                $needed = $reward->points_required - $user_points;
                                                printf(__('Need %s more points', 'env-voucher-rewards'), number_format($needed)); 
                                                ?>
                                            </span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- My Rewards Tab -->
            <div class="tab-panel" id="my-rewards-panel">
                <?php if (empty($user_rewards)): ?>
                    <div class="no-rewards">
                        <div class="no-rewards-icon">📋</div>
                        <h4><?php _e('No Rewards Earned Yet', 'env-voucher-rewards'); ?></h4>
                        <p><?php _e('Redeem rewards from the available rewards to see them here.', 'env-voucher-rewards'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="user-rewards-list">
                        <?php foreach ($user_rewards as $user_reward): ?>
                            <div class="user-reward-item">
                                <div class="reward-info">
                                    <h4><?php echo esc_html($user_reward->reward_title); ?></h4>
                                    <p><?php echo esc_html($user_reward->reward_description); ?></p>
                                    <div class="reward-meta">
                                        <span class="earned-date">
                                            <?php printf(__('Earned: %s', 'env-voucher-rewards'), date('M j, Y', strtotime($user_reward->earned_at))); ?>
                                        </span>
                                        <span class="points-spent">
                                            <?php printf(__('Cost: %s points', 'env-voucher-rewards'), number_format($user_reward->points_spent)); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="reward-status">
                                    <span class="status-badge <?php echo esc_attr($user_reward->status); ?>">
                                        <?php echo esc_html(ucfirst($user_reward->status)); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Earn Points Tab -->
            <div class="tab-panel" id="earn-points-panel">
                <div class="earning-opportunities">
                    <h4><?php _e('Ways to Earn Points', 'env-voucher-rewards'); ?></h4>
                    
                    <div class="earning-methods">
                        <div class="earning-method">
                            <div class="method-icon">🧠</div>
                            <div class="method-info">
                                <h5><?php _e('Complete Environmental Quiz', 'env-voucher-rewards'); ?></h5>
                                <p><?php _e('Earn 50-100 points for each quiz completed', 'env-voucher-rewards'); ?></p>
                            </div>
                            <div class="method-points">+50-100</div>
                        </div>
                        
                        <div class="earning-method">
                            <div class="method-icon">♻️</div>
                            <div class="method-info">
                                <h5><?php _e('Classify Waste Correctly', 'env-voucher-rewards'); ?></h5>
                                <p><?php _e('Get 20-30 points for accurate waste classification', 'env-voucher-rewards'); ?></p>
                            </div>
                            <div class="method-points">+20-30</div>
                        </div>
                        
                        <div class="earning-method">
                            <div class="method-icon">🌱</div>
                            <div class="method-info">
                                <h5><?php _e('Track Carbon Savings', 'env-voucher-rewards'); ?></h5>
                                <p><?php _e('Earn points based on your carbon footprint reduction', 'env-voucher-rewards'); ?></p>
                            </div>
                            <div class="method-points">+10-50</div>
                        </div>
                        
                        <div class="earning-method">
                            <div class="method-icon">📅</div>
                            <div class="method-info">
                                <h5><?php _e('Daily Login Bonus', 'env-voucher-rewards'); ?></h5>
                                <p><?php _e('Get 5 points for logging in daily', 'env-voucher-rewards'); ?></p>
                            </div>
                            <div class="method-points">+5</div>
                        </div>
                        
                        <div class="earning-method">
                            <div class="method-icon">🏆</div>
                            <div class="method-info">
                                <h5><?php _e('Achieve Milestones', 'env-voucher-rewards'); ?></h5>
                                <p><?php _e('Bonus points for reaching environmental milestones', 'env-voucher-rewards'); ?></p>
                            </div>
                            <div class="method-points">+100-500</div>
                        </div>
                    </div>
                    
                    <div class="cta-section">
                        <h4><?php _e('Ready to Start Earning?', 'env-voucher-rewards'); ?></h4>
                        <a href="<?php echo home_url('/environmental-activities'); ?>" class="btn btn-primary btn-large">
                            <?php _e('Start Environmental Activities', 'env-voucher-rewards'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-panel').removeClass('active');
        $('#' + tab + '-panel').addClass('active');
    });
    
    // Reward filtering
    $('.filter-btn').on('click', function() {
        const filter = $(this).data('filter');
        
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.reward-card').each(function() {
            const cardType = $(this).data('type');
            
            if (filter === 'all' || cardType === filter || 
                (filter === 'vouchers' && cardType === 'voucher') ||
                (filter === 'discounts' && cardType === 'discount') ||
                (filter === 'freebies' && cardType === 'freebie')) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>

<style>
.evr-reward-center-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.reward-center-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.user-status {
    display: flex;
    align-items: center;
    gap: 20px;
}

.points-balance {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 10px 16px;
    border-radius: 20px;
}

.points-amount {
    font-weight: bold;
    font-size: 18px;
    color: #27ae60;
}

.tier-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.tier-bronze { background: #cd7f32; color: white; }
.tier-silver { background: #c0c0c0; color: black; }
.tier-gold { background: #ffd700; color: black; }
.tier-platinum { background: #e5e4e2; color: black; }
.tier-diamond { background: #b9f2ff; color: black; }

.reward-center-tabs {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-btn {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-btn.active {
    background: white;
    border-bottom: 2px solid #27ae60;
    color: #27ae60;
}

.tab-content {
    padding: 30px;
}

.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

.rewards-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.rewards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.reward-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.reward-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.reward-card.unaffordable {
    opacity: 0.7;
}

.reward-image {
    position: relative;
    height: 150px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reward-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.reward-placeholder {
    font-size: 48px;
}

.reward-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.reward-content {
    padding: 20px;
}

.reward-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
}

.reward-value {
    display: flex;
    align-items: center;
    gap: 5px;
}

.value-amount {
    font-size: 20px;
    font-weight: bold;
    color: #27ae60;
}

.reward-cost {
    display: flex;
    align-items: center;
    gap: 5px;
}

.cost-amount {
    font-size: 18px;
    font-weight: bold;
    color: #e74c3c;
}

.earning-methods {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.earning-method {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.method-icon {
    font-size: 32px;
    width: 60px;
    text-align: center;
}

.method-info {
    flex: 1;
}

.method-points {
    font-size: 18px;
    font-weight: bold;
    color: #27ae60;
}

.cta-section {
    text-align: center;
    padding: 30px;
    background: #27ae60;
    color: white;
    border-radius: 10px;
}

.btn-large {
    padding: 12px 30px;
    font-size: 16px;
}

.no-rewards {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.no-rewards-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .reward-center-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .user-status {
        justify-content: center;
    }
    
    .tab-buttons {
        flex-direction: column;
    }
    
    .rewards-grid {
        grid-template-columns: 1fr;
    }
    
    .earning-method {
        flex-direction: column;
        text-align: center;
    }
}
</style>
