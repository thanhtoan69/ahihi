<?php
/**
 * Voucher Administration Class
 * 
 * Handles voucher campaign management, voucher generation,
 * and voucher usage tracking in the admin interface
 * 
 * @package Environmental_Voucher_Rewards
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Voucher_Admin {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    private $db_manager;
    
    /**
     * Voucher manager instance
     */
    private $voucher_manager;
    
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
        $this->voucher_manager = Environmental_Voucher_Manager::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_env_create_campaign', array($this, 'create_campaign'));
        add_action('wp_ajax_env_update_campaign', array($this, 'update_campaign'));
        add_action('wp_ajax_env_delete_campaign', array($this, 'delete_campaign'));
        add_action('wp_ajax_env_generate_vouchers', array($this, 'generate_vouchers'));
        add_action('wp_ajax_env_get_campaign_stats', array($this, 'get_campaign_stats'));
        add_action('wp_ajax_env_export_vouchers', array($this, 'export_vouchers'));
    }
    
    /**
     * Display the voucher management page
     */
    public function display_page() {
        $action = $_GET['action'] ?? 'list';
        $campaign_id = intval($_GET['campaign_id'] ?? 0);
        
        switch ($action) {
            case 'new':
                $this->display_new_campaign_form();
                break;
            case 'edit':
                $this->display_edit_campaign_form($campaign_id);
                break;
            case 'view':
                $this->display_campaign_details($campaign_id);
                break;
            case 'vouchers':
                $this->display_voucher_list($campaign_id);
                break;
            default:
                $this->display_campaign_list();
                break;
        }
    }
    
    /**
     * Display campaign list
     */
    private function display_campaign_list() {
        $campaigns = $this->get_campaigns();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Voucher Campaigns', 'environmental-voucher-rewards'); ?>
                <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=new'); ?>" 
                   class="page-title-action"><?php _e('Add New Campaign', 'environmental-voucher-rewards'); ?></a>
            </h1>
            
            <div class="env-campaigns-filters">
                <select id="campaign-status-filter">
                    <option value=""><?php _e('All Statuses', 'environmental-voucher-rewards'); ?></option>
                    <option value="active"><?php _e('Active', 'environmental-voucher-rewards'); ?></option>
                    <option value="inactive"><?php _e('Inactive', 'environmental-voucher-rewards'); ?></option>
                    <option value="expired"><?php _e('Expired', 'environmental-voucher-rewards'); ?></option>
                </select>
                
                <select id="campaign-type-filter">
                    <option value=""><?php _e('All Types', 'environmental-voucher-rewards'); ?></option>
                    <option value="percentage"><?php _e('Percentage Discount', 'environmental-voucher-rewards'); ?></option>
                    <option value="fixed_amount"><?php _e('Fixed Amount', 'environmental-voucher-rewards'); ?></option>
                    <option value="free_shipping"><?php _e('Free Shipping', 'environmental-voucher-rewards'); ?></option>
                    <option value="points_multiplier"><?php _e('Points Multiplier', 'environmental-voucher-rewards'); ?></option>
                </select>
                
                <button type="button" class="button" id="apply-filters"><?php _e('Apply Filters', 'environmental-voucher-rewards'); ?></button>
            </div>
            
            <div class="env-campaigns-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Campaign Name', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Type', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Discount', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Status', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Period', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Generated', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Used', 'environmental-voucher-rewards'); ?></th>
                            <th><?php _e('Actions', 'environmental-voucher-rewards'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($campaign->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=edit&campaign_id=' . $campaign->id); ?>">
                                            <?php _e('Edit', 'environmental-voucher-rewards'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=view&campaign_id=' . $campaign->id); ?>">
                                            <?php _e('View', 'environmental-voucher-rewards'); ?>
                                        </a> |
                                    </span>
                                    <span class="vouchers">
                                        <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=vouchers&campaign_id=' . $campaign->id); ?>">
                                            <?php _e('Vouchers', 'environmental-voucher-rewards'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="delete-campaign" data-campaign-id="<?php echo $campaign->id; ?>">
                                            <?php _e('Delete', 'environmental-voucher-rewards'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $campaign->discount_type))); ?></td>
                            <td>
                                <?php 
                                if ($campaign->discount_type === 'percentage') {
                                    echo esc_html($campaign->discount_value . '%');
                                } elseif ($campaign->discount_type === 'fixed_amount') {
                                    echo esc_html(wc_price($campaign->discount_value));
                                } elseif ($campaign->discount_type === 'points_multiplier') {
                                    echo esc_html($campaign->discount_value . 'x');
                                } else {
                                    echo esc_html(__('Free Shipping', 'environmental-voucher-rewards'));
                                }
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($campaign->status); ?>">
                                    <?php echo esc_html(ucfirst($campaign->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($campaign->start_date && $campaign->end_date) {
                                    echo esc_html(date('M j, Y', strtotime($campaign->start_date)) . ' - ' . date('M j, Y', strtotime($campaign->end_date)));
                                } elseif ($campaign->start_date) {
                                    echo esc_html(__('From', 'environmental-voucher-rewards') . ' ' . date('M j, Y', strtotime($campaign->start_date)));
                                } else {
                                    echo esc_html(__('No limit', 'environmental-voucher-rewards'));
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($campaign->voucher_count ?? 0); ?></td>
                            <td><?php echo esc_html($campaign->used_count ?? 0); ?></td>
                            <td>
                                <button type="button" class="button button-small generate-vouchers" 
                                        data-campaign-id="<?php echo $campaign->id; ?>">
                                    <?php _e('Generate', 'environmental-voucher-rewards'); ?>
                                </button>
                                <button type="button" class="button button-small view-stats" 
                                        data-campaign-id="<?php echo $campaign->id; ?>">
                                    <?php _e('Stats', 'environmental-voucher-rewards'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php $this->add_campaign_list_scripts(); ?>
        <?php
    }
    
    /**
     * Display new campaign form
     */
    private function display_new_campaign_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Create New Voucher Campaign', 'environmental-voucher-rewards'); ?></h1>
            
            <form id="new-campaign-form" method="post">
                <?php wp_nonce_field('env_campaign_nonce'); ?>
                
                <div class="env-form-grid">
                    <div class="env-form-section">
                        <h2><?php _e('Campaign Details', 'environmental-voucher-rewards'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="campaign-name"><?php _e('Campaign Name', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <input type="text" id="campaign-name" name="name" class="regular-text" required />
                                    <p class="description"><?php _e('Enter a descriptive name for this campaign.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="campaign-description"><?php _e('Description', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <textarea id="campaign-description" name="description" class="large-text" rows="3"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="discount-type"><?php _e('Discount Type', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <select id="discount-type" name="discount_type" required>
                                        <option value=""><?php _e('Select type...', 'environmental-voucher-rewards'); ?></option>
                                        <option value="percentage"><?php _e('Percentage Discount', 'environmental-voucher-rewards'); ?></option>
                                        <option value="fixed_amount"><?php _e('Fixed Amount Discount', 'environmental-voucher-rewards'); ?></option>
                                        <option value="free_shipping"><?php _e('Free Shipping', 'environmental-voucher-rewards'); ?></option>
                                        <option value="points_multiplier"><?php _e('Points Multiplier', 'environmental-voucher-rewards'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="discount-value"><?php _e('Discount Value', 'environmental-voucher-rewards'); ?> *</label>
                                </th>
                                <td>
                                    <input type="number" id="discount-value" name="discount_value" min="0" step="0.01" class="small-text" required />
                                    <span id="discount-unit">%</span>
                                    <p class="description" id="discount-description">
                                        <?php _e('Enter the discount percentage (0-100).', 'environmental-voucher-rewards'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="env-form-section">
                        <h2><?php _e('Campaign Settings', 'environmental-voucher-rewards'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="start-date"><?php _e('Start Date', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="datetime-local" id="start-date" name="start_date" />
                                    <p class="description"><?php _e('Leave empty for immediate activation.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="end-date"><?php _e('End Date', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="datetime-local" id="end-date" name="end_date" />
                                    <p class="description"><?php _e('Leave empty for no expiration.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="usage-limit"><?php _e('Total Usage Limit', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="usage-limit" name="usage_limit" min="0" class="small-text" />
                                    <p class="description"><?php _e('Maximum number of times vouchers from this campaign can be used.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="usage-limit-per-user"><?php _e('Usage Limit Per User', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="usage-limit-per-user" name="usage_limit_per_user" min="0" class="small-text" value="1" />
                                    <p class="description"><?php _e('Maximum number of times each user can use vouchers from this campaign.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="minimum-amount"><?php _e('Minimum Order Amount', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="minimum-amount" name="minimum_amount" min="0" step="0.01" class="small-text" />
                                    <p class="description"><?php _e('Minimum order amount required to use vouchers.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="env-form-section">
                        <h2><?php _e('Eligibility Rules', 'environmental-voucher-rewards'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="required-actions"><?php _e('Required Actions', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <select id="required-actions" name="required_actions[]" multiple>
                                        <option value="quiz_completion"><?php _e('Quiz Completion', 'environmental-voucher-rewards'); ?></option>
                                        <option value="waste_classification"><?php _e('Waste Classification', 'environmental-voucher-rewards'); ?></option>
                                        <option value="carbon_saving"><?php _e('Carbon Saving Action', 'environmental-voucher-rewards'); ?></option>
                                        <option value="challenge_completion"><?php _e('Challenge Completion', 'environmental-voucher-rewards'); ?></option>
                                        <option value="milestone_reached"><?php _e('Milestone Reached', 'environmental-voucher-rewards'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Select environmental actions required to earn vouchers from this campaign.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="loyalty-tiers"><?php _e('Eligible Loyalty Tiers', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <select id="loyalty-tiers" name="loyalty_tiers[]" multiple>
                                        <option value="bronze"><?php _e('Bronze', 'environmental-voucher-rewards'); ?></option>
                                        <option value="silver"><?php _e('Silver', 'environmental-voucher-rewards'); ?></option>
                                        <option value="gold"><?php _e('Gold', 'environmental-voucher-rewards'); ?></option>
                                        <option value="platinum"><?php _e('Platinum', 'environmental-voucher-rewards'); ?></option>
                                        <option value="diamond"><?php _e('Diamond', 'environmental-voucher-rewards'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Leave empty to allow all tiers.', 'environmental-voucher-rewards'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="auto-generate"><?php _e('Auto-Generate Vouchers', 'environmental-voucher-rewards'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="auto-generate" name="auto_generate" value="1" />
                                    <label for="auto-generate"><?php _e('Automatically generate vouchers when users complete required actions', 'environmental-voucher-rewards'); ?></label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Create Campaign', 'environmental-voucher-rewards'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers'); ?>" class="button">
                        <?php _e('Cancel', 'environmental-voucher-rewards'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <?php $this->add_campaign_form_scripts(); ?>
        <?php
    }
    
    /**
     * Display campaign details
     */
    private function display_campaign_details($campaign_id) {
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            wp_die(__('Campaign not found.', 'environmental-voucher-rewards'));
        }
        
        $stats = $this->get_campaign_statistics($campaign_id);
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html($campaign->name); ?>
                <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=edit&campaign_id=' . $campaign_id); ?>" 
                   class="page-title-action"><?php _e('Edit Campaign', 'environmental-voucher-rewards'); ?></a>
            </h1>
            
            <div class="env-campaign-details">
                <div class="env-campaign-info">
                    <h2><?php _e('Campaign Information', 'environmental-voucher-rewards'); ?></h2>
                    
                    <table class="env-info-table">
                        <tr>
                            <th><?php _e('Status:', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($campaign->status); ?>">
                                    <?php echo esc_html(ucfirst($campaign->status)); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Description:', 'environmental-voucher-rewards'); ?></th>
                            <td><?php echo esc_html($campaign->description ?? __('No description', 'environmental-voucher-rewards')); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Discount Type:', 'environmental-voucher-rewards'); ?></th>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $campaign->discount_type))); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Discount Value:', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <?php 
                                if ($campaign->discount_type === 'percentage') {
                                    echo esc_html($campaign->discount_value . '%');
                                } elseif ($campaign->discount_type === 'fixed_amount') {
                                    echo esc_html(wc_price($campaign->discount_value));
                                } elseif ($campaign->discount_type === 'points_multiplier') {
                                    echo esc_html($campaign->discount_value . 'x points');
                                } else {
                                    echo esc_html(__('Free Shipping', 'environmental-voucher-rewards'));
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Period:', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <?php
                                if ($campaign->start_date && $campaign->end_date) {
                                    echo esc_html(date('F j, Y g:i A', strtotime($campaign->start_date)) . ' - ' . date('F j, Y g:i A', strtotime($campaign->end_date)));
                                } elseif ($campaign->start_date) {
                                    echo esc_html(__('From', 'environmental-voucher-rewards') . ' ' . date('F j, Y g:i A', strtotime($campaign->start_date)));
                                } else {
                                    echo esc_html(__('No time limit', 'environmental-voucher-rewards'));
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Usage Limits:', 'environmental-voucher-rewards'); ?></th>
                            <td>
                                <?php
                                $limits = array();
                                if ($campaign->usage_limit) {
                                    $limits[] = sprintf(__('Total: %d', 'environmental-voucher-rewards'), $campaign->usage_limit);
                                }
                                if ($campaign->usage_limit_per_user) {
                                    $limits[] = sprintf(__('Per user: %d', 'environmental-voucher-rewards'), $campaign->usage_limit_per_user);
                                }
                                echo esc_html(implode(', ', $limits) ?: __('No limits', 'environmental-voucher-rewards'));
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Minimum Amount:', 'environmental-voucher-rewards'); ?></th>
                            <td><?php echo $campaign->minimum_amount ? wc_price($campaign->minimum_amount) : __('No minimum', 'environmental-voucher-rewards'); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="env-campaign-stats">
                    <h2><?php _e('Campaign Statistics', 'environmental-voucher-rewards'); ?></h2>
                    
                    <div class="env-stats-grid">
                        <div class="env-stat-card">
                            <h3><?php echo esc_html($stats['total_generated']); ?></h3>
                            <p><?php _e('Vouchers Generated', 'environmental-voucher-rewards'); ?></p>
                        </div>
                        <div class="env-stat-card">
                            <h3><?php echo esc_html($stats['total_used']); ?></h3>
                            <p><?php _e('Vouchers Used', 'environmental-voucher-rewards'); ?></p>
                        </div>
                        <div class="env-stat-card">
                            <h3><?php echo esc_html($stats['usage_rate']); ?>%</h3>
                            <p><?php _e('Usage Rate', 'environmental-voucher-rewards'); ?></p>
                        </div>
                        <div class="env-stat-card">
                            <h3><?php echo wc_price($stats['total_discount']); ?></h3>
                            <p><?php _e('Total Discount Given', 'environmental-voucher-rewards'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="env-campaign-actions">
                <h2><?php _e('Campaign Actions', 'environmental-voucher-rewards'); ?></h2>
                
                <div class="env-action-buttons">
                    <button type="button" class="button button-primary generate-vouchers" 
                            data-campaign-id="<?php echo $campaign_id; ?>">
                        <?php _e('Generate Vouchers', 'environmental-voucher-rewards'); ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers&action=vouchers&campaign_id=' . $campaign_id); ?>" 
                       class="button">
                        <?php _e('View All Vouchers', 'environmental-voucher-rewards'); ?>
                    </a>
                    
                    <button type="button" class="button export-vouchers" 
                            data-campaign-id="<?php echo $campaign_id; ?>">
                        <?php _e('Export Vouchers', 'environmental-voucher-rewards'); ?>
                    </button>
                    
                    <?php if ($campaign->status === 'active'): ?>
                    <button type="button" class="button deactivate-campaign" 
                            data-campaign-id="<?php echo $campaign_id; ?>">
                        <?php _e('Deactivate Campaign', 'environmental-voucher-rewards'); ?>
                    </button>
                    <?php else: ?>
                    <button type="button" class="button activate-campaign" 
                            data-campaign-id="<?php echo $campaign_id; ?>">
                        <?php _e('Activate Campaign', 'environmental-voucher-rewards'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php $this->add_campaign_detail_scripts(); ?>
        <?php
    }
    
    /**
     * AJAX Handlers
     */
    public function create_campaign() {
        check_ajax_referer('env_campaign_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $campaign_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'start_date' => sanitize_text_field($_POST['start_date']) ?: null,
            'end_date' => sanitize_text_field($_POST['end_date']) ?: null,
            'usage_limit' => intval($_POST['usage_limit']) ?: null,
            'usage_limit_per_user' => intval($_POST['usage_limit_per_user']) ?: 1,
            'minimum_amount' => floatval($_POST['minimum_amount']) ?: null,
            'required_actions' => array_map('sanitize_text_field', $_POST['required_actions'] ?? array()),
            'loyalty_tiers' => array_map('sanitize_text_field', $_POST['loyalty_tiers'] ?? array()),
            'auto_generate' => intval($_POST['auto_generate']) === 1,
            'status' => 'active'
        );
        
        $campaign_id = $this->db_manager->create_voucher_campaign($campaign_data);
        
        if ($campaign_id) {
            wp_send_json_success(array('campaign_id' => $campaign_id));
        } else {
            wp_send_json_error('Failed to create campaign');
        }
    }
    
    public function generate_vouchers() {
        check_ajax_referer('env_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        $user_ids = array_map('intval', $_POST['user_ids'] ?? array());
        
        $generated = 0;
        $errors = array();
        
        if (!empty($user_ids)) {
            // Generate vouchers for specific users
            foreach ($user_ids as $user_id) {
                try {
                    $voucher_id = $this->voucher_manager->generate_voucher($campaign_id, $user_id);
                    if ($voucher_id) {
                        $generated++;
                    }
                } catch (Exception $e) {
                    $errors[] = sprintf(__('Failed to generate voucher for user %d: %s', 'environmental-voucher-rewards'), $user_id, $e->getMessage());
                }
            }
        } else {
            // Generate bulk vouchers without specific users
            for ($i = 0; $i < $quantity; $i++) {
                try {
                    $voucher_id = $this->voucher_manager->generate_voucher($campaign_id);
                    if ($voucher_id) {
                        $generated++;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    break;
                }
            }
        }
        
        $response = array(
            'generated' => $generated,
            'requested' => $quantity,
            'errors' => $errors
        );
        
        if ($generated > 0) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }
    
    /**
     * Helper methods
     */
    private function get_campaigns() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                vc.*,
                COUNT(DISTINCT v.id) as voucher_count,
                COUNT(DISTINCT vu.id) as used_count
            FROM {$wpdb->prefix}environmental_voucher_campaigns vc
            LEFT JOIN {$wpdb->prefix}environmental_vouchers v ON vc.id = v.campaign_id
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON v.id = vu.voucher_id
            GROUP BY vc.id
            ORDER BY vc.created_at DESC
        ");
    }
    
    private function get_campaign($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}environmental_voucher_campaigns
            WHERE id = %d
        ", $campaign_id));
    }
    
    private function get_campaign_statistics($campaign_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT v.id) as total_generated,
                COUNT(DISTINCT vu.id) as total_used,
                COALESCE(SUM(vu.discount_amount), 0) as total_discount
            FROM {$wpdb->prefix}environmental_vouchers v
            LEFT JOIN {$wpdb->prefix}environmental_voucher_usage vu ON v.id = vu.voucher_id
            WHERE v.campaign_id = %d
        ", $campaign_id));
        
        $usage_rate = 0;
        if ($stats->total_generated > 0) {
            $usage_rate = round(($stats->total_used / $stats->total_generated) * 100, 2);
        }
        
        return array(
            'total_generated' => $stats->total_generated ?? 0,
            'total_used' => $stats->total_used ?? 0,
            'total_discount' => $stats->total_discount ?? 0,
            'usage_rate' => $usage_rate
        );
    }
    
    /**
     * Add JavaScript for campaign management
     */
    private function add_campaign_list_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Delete campaign
            $('.delete-campaign').click(function(e) {
                e.preventDefault();
                if (confirm(envAdmin.strings.confirmDelete)) {
                    var campaignId = $(this).data('campaign-id');
                    $.post(ajaxurl, {
                        action: 'env_delete_campaign',
                        campaign_id: campaignId,
                        nonce: envAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    });
                }
            });
            
            // Generate vouchers
            $('.generate-vouchers').click(function() {
                var campaignId = $(this).data('campaign-id');
                var quantity = prompt('How many vouchers to generate?', '10');
                if (quantity && quantity > 0) {
                    $.post(ajaxurl, {
                        action: 'env_generate_vouchers',
                        campaign_id: campaignId,
                        quantity: quantity,
                        nonce: envAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Generated ' + response.data.generated + ' vouchers successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    private function add_campaign_form_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Update discount unit and description based on type
            $('#discount-type').change(function() {
                var type = $(this).val();
                var $unit = $('#discount-unit');
                var $description = $('#discount-description');
                var $value = $('#discount-value');
                
                switch(type) {
                    case 'percentage':
                        $unit.text('%');
                        $description.text('Enter the discount percentage (0-100).');
                        $value.attr('max', 100);
                        break;
                    case 'fixed_amount':
                        $unit.text('<?php echo esc_js(get_woocommerce_currency_symbol()); ?>');
                        $description.text('Enter the fixed discount amount.');
                        $value.removeAttr('max');
                        break;
                    case 'free_shipping':
                        $unit.text('');
                        $description.text('Free shipping will be applied.');
                        $value.val(0).attr('readonly', true);
                        break;
                    case 'points_multiplier':
                        $unit.text('x');
                        $description.text('Enter the points multiplier (e.g., 2 for double points).');
                        $value.removeAttr('max').removeAttr('readonly');
                        break;
                }
            });
            
            // Form submission
            $('#new-campaign-form').submit(function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'env_create_campaign');
                formData.append('nonce', $('[name="_wpnonce"]').val());
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Campaign created successfully!');
                            window.location.href = '<?php echo admin_url('admin.php?page=environmental-voucher-rewards-vouchers'); ?>';
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
    
    private function add_campaign_detail_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Generate vouchers
            $('.generate-vouchers').click(function() {
                var campaignId = $(this).data('campaign-id');
                var quantity = prompt('How many vouchers to generate?', '10');
                if (quantity && quantity > 0) {
                    $.post(ajaxurl, {
                        action: 'env_generate_vouchers',
                        campaign_id: campaignId,
                        quantity: quantity,
                        nonce: envAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Generated ' + response.data.generated + ' vouchers successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
            
            // Export vouchers
            $('.export-vouchers').click(function() {
                var campaignId = $(this).data('campaign-id');
                window.location.href = ajaxurl + '?action=env_export_vouchers&campaign_id=' + campaignId + '&nonce=' + envAdmin.nonce;
            });
        });
        </script>
        <?php
    }
}

// Initialize the Voucher Admin class
Environmental_Voucher_Admin::get_instance();
