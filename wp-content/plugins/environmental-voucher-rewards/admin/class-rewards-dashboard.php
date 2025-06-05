<?php
/**
 * Rewards Dashboard Class
 * 
 * Handles rewards analytics, program management,
 * and user reward tracking in the admin interface
 * 
 * @package Environmental_Voucher_Rewards
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Rewards_Dashboard {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db_manager = Environmental_Database_Manager::get_instance();
        $this->analytics = Environmental_Analytics::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_env_get_rewards_overview', array($this, 'get_rewards_overview'));
        add_action('wp_ajax_env_create_reward_program', array($this, 'create_reward_program'));
        add_action('wp_ajax_env_update_reward_program', array($this, 'update_reward_program'));
        add_action('wp_ajax_env_delete_reward_program', array($this, 'delete_reward_program'));
        add_action('wp_ajax_env_manual_reward', array($this, 'manual_reward'));
        add_action('wp_ajax_env_bulk_reward', array($this, 'bulk_reward'));
        add_action('wp_ajax_env_adjust_user_points', array($this, 'adjust_user_points'));
    }
    
    /**
     * Display the rewards dashboard page
     */
    public function display_page() {
        $tab = $_GET['tab'] ?? 'overview';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Rewards Dashboard', 'environmental-voucher-rewards'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=environmental-voucher-rewards-rewards&tab=overview" 
                   class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Overview', 'environmental-voucher-rewards'); ?>
                </a>
                <a href="?page=environmental-voucher-rewards-rewards&tab=programs" 
                   class="nav-tab <?php echo $tab === 'programs' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Reward Programs', 'environmental-voucher-rewards'); ?>
                </a>
                <a href="?page=environmental-voucher-rewards-rewards&tab=users" 
                   class="nav-tab <?php echo $tab === 'users' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('User Rewards', 'environmental-voucher-rewards'); ?>
                </a>
                <a href="?page=environmental-voucher-rewards-rewards&tab=transactions" 
                   class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Transactions', 'environmental-voucher-rewards'); ?>
                </a>
                <a href="?page=environmental-voucher-rewards-rewards&tab=loyalty" 
                   class="nav-tab <?php echo $tab === 'loyalty' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Loyalty Tiers', 'environmental-voucher-rewards'); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($tab) {
                    case 'overview':
                        $this->display_overview_tab();
                        break;
                    case 'programs':
                        $this->display_programs_tab();
                        break;
                    case 'users':
                        $this->display_users_tab();
                        break;
                    case 'transactions':
                        $this->display_transactions_tab();
                        break;
                    case 'loyalty':
                        $this->display_loyalty_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display overview tab
     */
    private function display_overview_tab() {
        $overview_stats = $this->get_overview_statistics();
        ?>
        <div class="env-rewards-overview">
            <div class="env-stats-cards">
                <div class="env-stat-card">
                    <div class="env-stat-icon">🏆</div>
                    <div class="env-stat-content">
                        <h3><?php echo number_format($overview_stats['total_points_distributed']); ?></h3>
                        <p><?php _e('Total Points Distributed', 'environmental-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="env-stat-card">
                    <div class="env-stat-icon">👥</div>
                    <div class="env-stat-content">
                        <h3><?php echo number_format($overview_stats['active_users']); ?></h3>
                        <p><?php _e('Active Reward Users', 'environmental-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="env-stat-card">
                    <div class="env-stat-icon">🎯</div>
                    <div class="env-stat-content">
                        <h3><?php echo number_format($overview_stats['total_transactions']); ?></h3>
                        <p><?php _e('Total Transactions', 'environmental-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="env-stat-card">
                    <div class="env-stat-icon">📈</div>
                    <div class="env-stat-content">
                        <h3><?php echo number_format($overview_stats['avg_points_per_user']); ?></h3>
                        <p><?php _e('Avg Points Per User', 'environmental-voucher-rewards'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="env-dashboard-grid">
                <div class="env-dashboard-section">
                    <h2><?php _e('Reward Distribution (Last 30 Days)', 'environmental-voucher-rewards'); ?></h2>
                    <canvas id="rewardDistributionChart" width="400" height="200"></canvas>
                </div>
                
                <div class="env-dashboard-section">
                    <h2><?php _e('Top Reward Actions', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-action-list">
                        <?php foreach ($overview_stats['top_actions'] as $action): ?>
                        <div class="env-action-item">
                            <span class="env-action-name"><?php echo esc_html(ucfirst(str_replace('_', ' ', $action->transaction_type))); ?></span>
                            <span class="env-action-count"><?php echo number_format($action->transaction_count); ?></span>
                            <span class="env-action-points"><?php echo number_format($action->total_points); ?> pts</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="env-dashboard-section">
                    <h2><?php _e('Recent Transactions', 'environmental-voucher-rewards'); ?></h2>
                    <div class="env-recent-transactions">
                        <?php foreach ($overview_stats['recent_transactions'] as $transaction): ?>
                        <div class="env-transaction-item">
                            <div class="env-transaction-user"><?php echo esc_html($transaction->display_name); ?></div>
                            <div class="env-transaction-action"><?php echo esc_html(ucfirst(str_replace('_', ' ', $transaction->transaction_type))); ?></div>
                            <div class="env-transaction-points">+<?php echo number_format($transaction->points_amount); ?> pts</div>
                            <div class="env-transaction-time"><?php echo human_time_diff(strtotime($transaction->created_at)); ?> ago</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="env-dashboard-section">
                    <h2><?php _e('Loyalty Tier Distribution', 'environmental-voucher-rewards'); ?></h2>
                    <canvas id="loyaltyTierChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="env-quick-actions">
                <h2><?php _e('Quick Actions', 'environmental-voucher-rewards'); ?></h2>
                <div class="env-action-buttons">
                    <button type="button" class="button button-primary" id="manual-reward-btn">
                        <?php _e('Award Manual Reward', 'environmental-voucher-rewards'); ?>
                    </button>
                    <button type="button" class="button" id="bulk-reward-btn">
                        <?php _e('Bulk Reward Users', 'environmental-voucher-rewards'); ?>
                    </button>
                    <button type="button" class="button" id="export-rewards-btn">
                        <?php _e('Export Reward Data', 'environmental-voucher-rewards'); ?>
                    </button>
                    <button type="button" class="button" id="refresh-stats-btn">
                        <?php _e('Refresh Statistics', 'environmental-voucher-rewards'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <?php $this->add_overview_scripts(); ?>
        <?php
    }
    
    /**
     * Display reward programs tab
     */
    private function display_programs_tab() {
        $programs = $this->get_reward_programs();
        ?>
        <div class="env-reward-programs">
            <div class="env-programs-header">
                <h2><?php _e('Reward Programs', 'environmental-voucher-rewards'); ?></h2>
                <button type="button" class="button button-primary" id="new-program-btn">
                    <?php _e('Create New Program', 'environmental-voucher-rewards'); ?>
                </button>
            </div>
            
            <div class="env-programs-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Program Name', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Type', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Points Value', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Status', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Participants', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Total Distributed', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Actions', 'environmental-voucher-rewards'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $program): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($program->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="edit-program" data-program-id="<?php echo $program->id; ?>">
                                            <?php _e('Edit', 'environmental-voucher-rewards'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="#" class="view-program" data-program-id="<?php echo $program->id; ?>">
                                            <?php _e('View', 'environmental-voucher-rewards'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="delete-program" data-program-id="<?php echo $program->id; ?>">
                                            <?php _e('Delete', 'environmental-voucher-rewards'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $program->program_type))); ?></td>
                            <td><?php echo number_format($program->points_value); ?> pts</td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($program->status); ?>">
                                    <?php echo esc_html(ucfirst($program->status)); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($program->participant_count ?? 0); ?></td>
                            <td><?php echo number_format($program->total_distributed ?? 0); ?> pts</td>
                            <td>
                                <button type="button" class="button button-small toggle-status" 
                                        data-program-id="<?php echo $program->id; ?>" 
                                        data-current-status="<?php echo esc_attr($program->status); ?>">
                                    <?php echo $program->status === 'active' ? __('Deactivate', 'environmental-voucher-rewards') : __('Activate', 'environmental-voucher-rewards'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- New Program Modal -->
        <div id="new-program-modal" class="env-modal" style="display: none;">
            <div class="env-modal-content">
                <div class="env-modal-header">
                    <h3><?php _e('Create New Reward Program', 'environmental-voucher-rewards'); ?></h3>
                    <span class="env-modal-close">&times;</span>
                </div>
                <div class="env-modal-body">
                    <form id="new-program-form">
                        <?php wp_nonce_field('env_program_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="program-name"><?php _e('Program Name', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <input type="text" id="program-name" name="name" class="regular-text" required />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="program-type"><?php _e('Program Type', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <select id="program-type" name="program_type" required>
                                        <option value=""><?php _e('Select type...', 'environmental-voucher-rewards'); ?></option>
                                        <option value="quiz_completion"><?php _e('Quiz Completion', 'environmental-voucher-rewards'); ?></option>
                                        <option value="waste_classification"><?php _e('Waste Classification', 'environmental-voucher-rewards'); ?></option>
                                        <option value="carbon_saving"><?php _e('Carbon Saving Action', 'environmental-voucher-rewards'); ?></option>
                                        <option value="daily_login"><?php _e('Daily Login', 'environmental-voucher-rewards'); ?></option>
                                        <option value="challenge_completion"><?php _e('Challenge Completion', 'environmental-voucher-rewards'); ?></option>
                                        <option value="milestone_reached"><?php _e('Milestone Reached', 'environmental-voucher-rewards'); ?></option>
                                        <option value="custom"><?php _e('Custom Action', 'environmental-voucher-rewards'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="points-value"><?php _e('Points Value', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <input type="number" id="points-value" name="points_value" min="1" class="small-text" required />
                                    <p class="description"><?php _e('Number of points awarded per action.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="program-description"><?php _e('Description', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <textarea id="program-description" name="description" class="large-text" rows="3"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="program-conditions"><?php _e('Conditions', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <textarea id="program-conditions" name="conditions" class="large-text" rows="2"></textarea>
                                    <p class="description"><?php _e('Special conditions or requirements (JSON format).', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="env-modal-footer">
                            <button type="submit" class="button button-primary"><?php _e('Create Program', 'environmental-voucher-rewards'); ?></button>
                            <button type="button" class="button env-modal-close"><?php _e('Cancel', 'environmental-voucher-rewards'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php $this->add_programs_scripts(); ?>
        <?php
    }
    
    /**
     * Display users tab
     */
    private function display_users_tab() {
        $users = $this->get_reward_users();
        ?>
        <div class="env-reward-users">
            <div class="env-users-header">
                <h2><?php _e('User Rewards', 'environmental-voucher-rewards'); ?></h2>
                <div class="env-users-filters">
                    <select id="tier-filter">
                        <option value=""><?php _e('All Tiers', 'environmental-voucher-rewards'); ?></option>
                        <option value="bronze"><?php _e('Bronze', 'environmental-voucher-rewards'); ?></option>
                        <option value="silver"><?php _e('Silver', 'environmental-voucher-rewards'); ?></option>
                        <option value="gold"><?php _e('Gold', 'environmental-voucher-rewards'); ?></option>
                        <option value="platinum"><?php _e('Platinum', 'environmental-voucher-rewards'); ?></option>
                        <option value="diamond"><?php _e('Diamond', 'environmental-voucher-rewards'); ?></option>
                    </select>
                    <input type="search" id="user-search" placeholder="<?php _e('Search users...', 'environmental-voucher-rewards'); ?>" />
                    <button type="button" class="button" id="apply-user-filters"><?php _e('Filter', 'environmental-voucher-rewards'); ?></button>
                </div>
            </div>
            
            <div class="env-users-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Loyalty Tier', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Current Points', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Lifetime Points', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Last Activity', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Total Transactions', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Actions', 'environmental-voucher-rewards'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="env-user-info">
                                    <?php echo get_avatar($user->user_id, 32); ?>
                                    <div class="env-user-details">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br><small><?php echo esc_html($user->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tier-badge tier-<?php echo esc_attr($user->loyalty_tier); ?>">
                                    <?php echo esc_html(ucfirst($user->loyalty_tier)); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($user->current_points); ?></td>
                            <td><?php echo number_format($user->lifetime_points); ?></td>
                            <td>
                                <?php 
                                if ($user->last_activity) {
                                    echo human_time_diff(strtotime($user->last_activity)) . ' ago';
                                } else {
                                    echo __('Never', 'environmental-voucher-rewards');
                                }
                                ?>
                            </td>
                            <td><?php echo number_format($user->transaction_count ?? 0); ?></td>
                            <td>
                                <button type="button" class="button button-small adjust-points" 
                                        data-user-id="<?php echo $user->user_id; ?>">
                                    <?php _e('Adjust Points', 'environmental-voucher-rewards'); ?>
                                </button>
                                <button type="button" class="button button-small view-history" 
                                        data-user-id="<?php echo $user->user_id; ?>">
                                    <?php _e('View History', 'environmental-voucher-rewards'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Adjust Points Modal -->
        <div id="adjust-points-modal" class="env-modal" style="display: none;">
            <div class="env-modal-content">
                <div class="env-modal-header">
                    <h3><?php _e('Adjust User Points', 'environmental-voucher-rewards'); ?></h3>
                    <span class="env-modal-close">&times;</span>
                </div>
                <div class="env-modal-body">
                    <form id="adjust-points-form">
                        <?php wp_nonce_field('env_adjust_points_nonce'); ?>
                        <input type="hidden" id="adjust-user-id" name="user_id" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="adjustment-type"><?php _e('Adjustment Type', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <select id="adjustment-type" name="adjustment_type" required>
                                        <option value="add"><?php _e('Add Points', 'environmental-voucher-rewards'); ?></option>
                                        <option value="subtract"><?php _e('Subtract Points', 'environmental-voucher-rewards'); ?></option>
                                        <option value="set"><?php _e('Set Points', 'environmental-voucher-rewards'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="points-amount"><?php _e('Points Amount', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="points-amount" name="points_amount" min="0" class="small-text" required />
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="adjustment-reason"><?php _e('Reason', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <textarea id="adjustment-reason" name="reason" class="large-text" rows="3" 
                                              placeholder="<?php _e('Reason for point adjustment...', 'environmental-voucher-rewards'); ?>"></textarea>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="env-modal-footer">
                            <button type="submit" class="button button-primary"><?php _e('Adjust Points', 'environmental-voucher-rewards'); ?></button>
                            <button type="button" class="button env-modal-close"><?php _e('Cancel', 'environmental-voucher-rewards'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php $this->add_users_scripts(); ?>
        <?php
    }
    
    /**
     * Display transactions tab
     */
    private function display_transactions_tab() {
        $transactions = $this->get_recent_transactions(100);
        ?>
        <div class="env-transactions">
            <div class="env-transactions-header">
                <h2><?php _e('Reward Transactions', 'environmental-voucher-rewards'); ?></h2>
                <div class="env-transactions-filters">
                    <select id="transaction-type-filter">
                        <option value=""><?php _e('All Types', 'environmental-voucher-rewards'); ?></option>
                        <option value="quiz_completion"><?php _e('Quiz Completion', 'environmental-voucher-rewards'); ?></option>
                        <option value="waste_classification"><?php _e('Waste Classification', 'environmental-voucher-rewards'); ?></option>
                        <option value="carbon_saving"><?php _e('Carbon Saving', 'environmental-voucher-rewards'); ?></option>
                        <option value="daily_login"><?php _e('Daily Login', 'environmental-voucher-rewards'); ?></option>
                        <option value="challenge_completion"><?php _e('Challenge Completion', 'environmental-voucher-rewards'); ?></option>
                        <option value="manual_adjustment"><?php _e('Manual Adjustment', 'environmental-voucher-rewards'); ?></option>
                    </select>
                    
                    <input type="date" id="date-from" name="date_from" />
                    <span>to</span>
                    <input type="date" id="date-to" name="date_to" />
                    
                    <button type="button" class="button" id="apply-transaction-filters"><?php _e('Filter', 'environmental-voucher-rewards'); ?></button>
                    <button type="button" class="button" id="export-transactions"><?php _e('Export', 'environmental-voucher-rewards'); ?></button>
                </div>
            </div>
            
            <div class="env-transactions-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('User', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Transaction Type', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Points', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Program', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Date', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Reference', 'environmental-voucher-rewards'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo esc_html($transaction->id); ?></td>
                            <td>
                                <div class="env-user-info">
                                    <?php echo get_avatar($transaction->user_id, 24); ?>
                                    <span><?php echo esc_html($transaction->display_name); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="transaction-type-badge type-<?php echo esc_attr($transaction->transaction_type); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $transaction->transaction_type))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="points-amount <?php echo $transaction->points_amount > 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $transaction->points_amount > 0 ? '+' : ''; ?><?php echo number_format($transaction->points_amount); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($transaction->program_name ?? __('N/A', 'environmental-voucher-rewards')); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($transaction->created_at)); ?></td>
                            <td>
                                <?php if ($transaction->reference_id): ?>
                                <code><?php echo esc_html($transaction->reference_id); ?></code>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php $this->add_transactions_scripts(); ?>
        <?php
    }
    
    /**
     * Display loyalty tab
     */
    private function display_loyalty_tab() {
        $loyalty_stats = $this->get_loyalty_statistics();
        ?>
        <div class="env-loyalty-dashboard">
            <div class="env-loyalty-overview">
                <h2><?php _e('Loyalty Program Overview', 'environmental-voucher-rewards'); ?></h2>
                
                <div class="env-tier-stats">
                    <?php foreach ($loyalty_stats['tier_distribution'] as $tier): ?>
                    <div class="env-tier-card tier-<?php echo esc_attr($tier->loyalty_tier); ?>">
                        <div class="env-tier-header">
                            <h3><?php echo esc_html(ucfirst($tier->loyalty_tier)); ?></h3>
                            <span class="env-tier-count"><?php echo number_format($tier->user_count); ?></span>
                        </div>
                        <div class="env-tier-details">
                            <p><?php _e('Avg Points:', 'environmental-voucher-rewards'); ?> <?php echo number_format($tier->avg_points); ?></p>
                            <p><?php _e('Lifetime Avg:', 'environmental-voucher-rewards'); ?> <?php echo number_format($tier->avg_lifetime_points); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="env-loyalty-charts">
                <div class="env-chart-section">
                    <h3><?php _e('Tier Progression (Last 90 Days)', 'environmental-voucher-rewards'); ?></h3>
                    <canvas id="tierProgressionChart" width="400" height="200"></canvas>
                </div>
                
                <div class="env-chart-section">
                    <h3><?php _e('User Distribution by Tier', 'environmental-voucher-rewards'); ?></h3>
                    <canvas id="tierDistributionChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="env-loyalty-settings">
                <h2><?php _e('Loyalty Tier Settings', 'environmental-voucher-rewards'); ?></h2>
                
                <form id="loyalty-settings-form">
                    <?php wp_nonce_field('env_loyalty_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Bronze Tier Threshold', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <input type="number" name="bronze_threshold" value="0" min="0" class="small-text" readonly />
                                <p class="description"><?php _e('Default tier for all users.', 'environmental-voucher-rewards'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Silver Tier Threshold', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <input type="number" name="silver_threshold" value="500" min="1" class="small-text" />
                                <p class="description"><?php _e('Lifetime points required for Silver tier.', 'environmental-voucher-rewards'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Gold Tier Threshold', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <input type="number" name="gold_threshold" value="1500" min="1" class="small-text" />
                                <p class="description"><?php _e('Lifetime points required for Gold tier.', 'environmental-voucher-rewards'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Platinum Tier Threshold', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <input type="number" name="platinum_threshold" value="3000" min="1" class="small-text" />
                                <p class="description"><?php _e('Lifetime points required for Platinum tier.', 'environmental-voucher-rewards'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Diamond Tier Threshold', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <input type="number" name="diamond_threshold" value="5000" min="1" class="small-text" />
                                <p class="description"><?php _e('Lifetime points required for Diamond tier.', 'environmental-voucher-rewards'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Update Tier Settings', 'environmental-voucher-rewards'); ?>" />
                        <button type="button" class="button" id="recalculate-tiers">
                            <?php _e('Recalculate All User Tiers', 'environmental-voucher-rewards'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <?php $this->add_loyalty_scripts(); ?>
        <?php
    }
    
    /**
     * AJAX Handlers
     */
    public function get_rewards_overview() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $overview = $this->get_overview_statistics();
        wp_send_json_success($overview);
    }
    
    public function manual_reward() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $points = intval($_POST['points']);
        $reason = sanitize_text_field($_POST['reason']);
        
        $reward_engine = Environmental_Reward_Engine::get_instance();
        $transaction_id = $reward_engine->award_points($user_id, $points, 'manual_adjustment', null, $reason);
        
        if ($transaction_id) {
            wp_send_json_success(array('transaction_id' => $transaction_id));
        } else {
            wp_send_json_error('Failed to award points');
        }
    }
    
    public function adjust_user_points() {
        check_ajax_referer('env_adjust_points_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $adjustment_type = sanitize_text_field($_POST['adjustment_type']);
        $points_amount = intval($_POST['points_amount']);
        $reason = sanitize_text_field($_POST['reason']);
        
        $current_points = $this->db_manager->get_user_points($user_id);
        $new_points = $current_points;
        
        switch ($adjustment_type) {
            case 'add':
                $new_points = $current_points + $points_amount;
                break;
            case 'subtract':
                $new_points = max(0, $current_points - $points_amount);
                $points_amount = -$points_amount; // Negative for transaction record
                break;
            case 'set':
                $new_points = $points_amount;
                $points_amount = $points_amount - $current_points; // Difference for transaction record
                break;
        }
        
        // Update user points
        $updated = $this->db_manager->update_user_points($user_id, $new_points);
        
        if ($updated) {
            // Record transaction
            $reward_engine = Environmental_Reward_Engine::get_instance();
            $reward_engine->award_points($user_id, $points_amount, 'manual_adjustment', null, $reason);
            
            wp_send_json_success(array(
                'old_points' => $current_points,
                'new_points' => $new_points,
                'adjustment' => $points_amount
            ));
        } else {
            wp_send_json_error('Failed to adjust points');
        }
    }
    
    /**
     * Helper methods for data retrieval
     */
    private function get_overview_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total points distributed
        $stats['total_points_distributed'] = $wpdb->get_var("
            SELECT SUM(points_amount) FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE points_amount > 0
        ") ?: 0;
        
        // Active users (users with activity in last 30 days)
        $stats['active_users'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ") ?: 0;
        
        // Total transactions
        $stats['total_transactions'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}environmental_reward_transactions
        ") ?: 0;
        
        // Average points per user
        $stats['avg_points_per_user'] = $wpdb->get_var("
            SELECT AVG(current_points) FROM {$wpdb->prefix}environmental_user_rewards
        ") ?: 0;
        
        // Top reward actions
        $stats['top_actions'] = $wpdb->get_results("
            SELECT 
                transaction_type,
                COUNT(*) as transaction_count,
                SUM(points_amount) as total_points
            FROM {$wpdb->prefix}environmental_reward_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND points_amount > 0
            GROUP BY transaction_type
            ORDER BY total_points DESC
            LIMIT 5
        ");
        
        // Recent transactions
        $stats['recent_transactions'] = $wpdb->get_results("
            SELECT 
                rt.*,
                u.display_name
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            JOIN {$wpdb->prefix}users u ON rt.user_id = u.ID
            ORDER BY rt.created_at DESC
            LIMIT 10
        ");
        
        return $stats;
    }
    
    private function get_reward_programs() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                rp.*,
                COUNT(DISTINCT rt.user_id) as participant_count,
                SUM(rt.points_amount) as total_distributed
            FROM {$wpdb->prefix}environmental_reward_programs rp
            LEFT JOIN {$wpdb->prefix}environmental_reward_transactions rt ON rp.id = rt.program_id
            GROUP BY rp.id
            ORDER BY rp.created_at DESC
        ");
    }
    
    private function get_reward_users() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                ur.*,
                u.display_name,
                u.user_email,
                COUNT(rt.id) as transaction_count,
                MAX(rt.created_at) as last_activity
            FROM {$wpdb->prefix}environmental_user_rewards ur
            JOIN {$wpdb->prefix}users u ON ur.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}environmental_reward_transactions rt ON ur.user_id = rt.user_id
            GROUP BY ur.user_id
            ORDER BY ur.lifetime_points DESC
            LIMIT 100
        ");
    }
    
    private function get_recent_transactions($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                rt.*,
                u.display_name,
                rp.name as program_name
            FROM {$wpdb->prefix}environmental_reward_transactions rt
            JOIN {$wpdb->prefix}users u ON rt.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}environmental_reward_programs rp ON rt.program_id = rp.id
            ORDER BY rt.created_at DESC
            LIMIT %d
        ", $limit));
    }
    
    private function get_loyalty_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Tier distribution
        $stats['tier_distribution'] = $wpdb->get_results("
            SELECT 
                loyalty_tier,
                COUNT(*) as user_count,
                AVG(current_points) as avg_points,
                AVG(lifetime_points) as avg_lifetime_points
            FROM {$wpdb->prefix}environmental_user_rewards
            GROUP BY loyalty_tier
            ORDER BY 
                CASE loyalty_tier
                    WHEN 'bronze' THEN 1
                    WHEN 'silver' THEN 2
                    WHEN 'gold' THEN 3
                    WHEN 'platinum' THEN 4
                    WHEN 'diamond' THEN 5
                    ELSE 6
                END
        ");
        
        return $stats;
    }
    
    /**
     * JavaScript for various tabs
     */
    private function add_overview_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Load charts
            loadRewardDistributionChart();
            loadLoyaltyTierChart();
            
            // Quick action handlers
            $('#manual-reward-btn').click(function() {
                // Implementation for manual reward modal
            });
            
            $('#bulk-reward-btn').click(function() {
                // Implementation for bulk reward modal
            });
            
            $('#refresh-stats-btn').click(function() {
                location.reload();
            });
        });
        
        function loadRewardDistributionChart() {
            // Chart implementation using Chart.js
        }
        
        function loadLoyaltyTierChart() {
            // Chart implementation using Chart.js
        }
        </script>
        <?php
    }
    
    private function add_programs_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Modal handlers
            $('#new-program-btn').click(function() {
                $('#new-program-modal').show();
            });
            
            $('.env-modal-close').click(function() {
                $('.env-modal').hide();
            });
            
            // Form submission
            $('#new-program-form').submit(function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'env_create_reward_program');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Program created successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function add_users_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Adjust points modal
            $('.adjust-points').click(function() {
                var userId = $(this).data('user-id');
                $('#adjust-user-id').val(userId);
                $('#adjust-points-modal').show();
            });
            
            // Form submission
            $('#adjust-points-form').submit(function(e) {
                e.preventDefault();
                
                $.post(ajaxurl, $(this).serialize() + '&action=env_adjust_user_points', function(response) {
                    if (response.success) {
                        alert('Points adjusted successfully!');
                        $('#adjust-points-modal').hide();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    private function add_transactions_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Filter functionality
            $('#apply-transaction-filters').click(function() {
                // Implementation for filtering transactions
            });
            
            // Export functionality
            $('#export-transactions').click(function() {
                // Implementation for exporting transactions
            });
        });
        </script>
        <?php
    }
    
    private function add_loyalty_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Load loyalty charts
            loadTierProgressionChart();
            loadTierDistributionChart();
            
            // Recalculate tiers
            $('#recalculate-tiers').click(function() {
                if (confirm('This will recalculate all user tiers based on their lifetime points. Continue?')) {
                    $.post(ajaxurl, {
                        action: 'env_recalculate_tiers',
                        nonce: envAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            alert('User tiers recalculated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        
        function loadTierProgressionChart() {
            // Chart implementation
        }
        
        function loadTierDistributionChart() {
            // Chart implementation
        }
        </script>
        <?php
    }
}

// Initialize the Rewards Dashboard class
Environmental_Rewards_Dashboard::get_instance();
