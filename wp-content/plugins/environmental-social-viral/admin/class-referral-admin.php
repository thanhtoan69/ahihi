<?php
/**
 * Environmental Social Viral Referral Admin
 * 
 * Handles admin interface for referral system management including
 * referral tracking, rewards management, and referral analytics
 * 
 * @package Environmental_Social_Viral
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Social_Viral_Referral_Admin {
    
    private static $instance = null;
    private $wpdb;
    private $tables;
    private $referral_system;
    
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
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Initialize referral system
        $this->referral_system = Environmental_Social_Viral_Referral_System::get_instance();
        
        // Get database tables
        $database = new Environmental_Social_Viral_Database();
        $this->tables = $database->get_all_tables();
        
        add_action('admin_init', array($this, 'init_admin'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        // AJAX handlers
        add_action('wp_ajax_env_referral_get_analytics', array($this, 'ajax_get_referral_analytics'));
        add_action('wp_ajax_env_referral_update_settings', array($this, 'ajax_update_referral_settings'));
        add_action('wp_ajax_env_referral_process_reward', array($this, 'ajax_process_reward'));
        add_action('wp_ajax_env_referral_bulk_action', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_env_referral_export_data', array($this, 'ajax_export_referral_data'));
        add_action('wp_ajax_env_referral_generate_links', array($this, 'ajax_generate_referral_links'));
        add_action('wp_ajax_env_referral_validate_code', array($this, 'ajax_validate_referral_code'));
    }
    
    /**
     * Render referral analytics page
     */
    public function render_referral_analytics_page() {
        $analytics = $this->get_referral_analytics();
        $top_referrers = $this->get_top_referrers();
        $recent_referrals = $this->get_recent_referrals();
        $reward_summary = $this->get_reward_summary();
        
        ?>
        <div class="wrap env-referral-admin">
            <h1><?php _e('Referral System Analytics', 'environmental-social-viral'); ?></h1>
            
            <!-- Referral Overview -->
            <div class="env-admin-section">
                <h2><?php _e('Referral Performance Overview', 'environmental-social-viral'); ?></h2>
                
                <div class="env-stats-grid">
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['total_referrals']); ?></div>
                        <div class="env-stat-label"><?php _e('Total Referrals', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['referrals_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['referrals_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['referrals_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['successful_conversions']); ?></div>
                        <div class="env-stat-label"><?php _e('Successful Conversions', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['conversions_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['conversions_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['conversions_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number"><?php echo number_format($analytics['conversion_rate'], 2); ?>%</div>
                        <div class="env-stat-label"><?php _e('Conversion Rate', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-change <?php echo $analytics['conversion_rate_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $analytics['conversion_rate_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics['conversion_rate_change'], 1); ?>%
                        </div>
                    </div>
                    
                    <div class="env-stat-card">
                        <div class="env-stat-number">$<?php echo number_format($analytics['total_rewards'], 2); ?></div>
                        <div class="env-stat-label"><?php _e('Total Rewards Paid', 'environmental-social-viral'); ?></div>
                        <div class="env-stat-sub"><?php echo number_format($analytics['pending_rewards']); ?> <?php _e('pending', 'environmental-social-viral'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="env-admin-section">
                <div class="env-charts-container">
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Referral Activity Over Time', 'environmental-social-viral'); ?></h3>
                        <canvas id="referralChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="env-chart-wrapper">
                        <h3><?php _e('Referral Sources', 'environmental-social-viral'); ?></h3>
                        <canvas id="referralSourcesChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Referrers Table -->
            <div class="env-admin-section">
                <h2><?php _e('Top Referrers', 'environmental-social-viral'); ?></h2>
                
                <div class="env-table-controls">
                    <div class="env-search-box">
                        <input type="text" id="referrer-search" placeholder="<?php _e('Search referrers...', 'environmental-social-viral'); ?>">
                        <button class="button" id="search-referrers"><?php _e('Search', 'environmental-social-viral'); ?></button>
                    </div>
                    
                    <div class="env-bulk-actions">
                        <select id="bulk-referrer-action">
                            <option value=""><?php _e('Bulk Actions', 'environmental-social-viral'); ?></option>
                            <option value="reward"><?php _e('Process Rewards', 'environmental-social-viral'); ?></option>
                            <option value="block"><?php _e('Block Referrers', 'environmental-social-viral'); ?></option>
                            <option value="unblock"><?php _e('Unblock Referrers', 'environmental-social-viral'); ?></option>
                            <option value="export"><?php _e('Export Data', 'environmental-social-viral'); ?></option>
                        </select>
                        <button class="button" id="apply-bulk-referrer-action"><?php _e('Apply', 'environmental-social-viral'); ?></button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped env-referrers-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-referrers">
                            </td>
                            <th class="manage-column"><?php _e('Referrer', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Total Referrals', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Conversions', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Conversion Rate', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Rewards Earned', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Status', 'environmental-social-viral'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_referrers as $referrer): ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="referrer[]" value="<?php echo esc_attr($referrer['user_id']); ?>">
                            </td>
                            <td>
                                <strong><?php echo esc_html($referrer['display_name']); ?></strong>
                                <div class="referrer-email"><?php echo esc_html($referrer['user_email']); ?></div>
                                <div class="referrer-code">
                                    <?php _e('Code:', 'environmental-social-viral'); ?> 
                                    <code><?php echo esc_html($referrer['referral_code']); ?></code>
                                </div>
                            </td>
                            <td><?php echo number_format($referrer['total_referrals']); ?></td>
                            <td><?php echo number_format($referrer['conversions']); ?></td>
                            <td><?php echo number_format($referrer['conversion_rate'], 2); ?>%</td>
                            <td>$<?php echo number_format($referrer['total_rewards'], 2); ?></td>
                            <td>
                                <span class="env-status-badge <?php echo esc_attr($referrer['status']); ?>">
                                    <?php echo esc_html(ucfirst($referrer['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="env-action-buttons">
                                    <button class="button button-small process-reward" 
                                            data-user-id="<?php echo esc_attr($referrer['user_id']); ?>"
                                            data-pending="<?php echo esc_attr($referrer['pending_rewards']); ?>">
                                        <?php _e('Process Reward', 'environmental-social-viral'); ?>
                                    </button>
                                    <button class="button button-small view-details" 
                                            data-user-id="<?php echo esc_attr($referrer['user_id']); ?>">
                                        <?php _e('View Details', 'environmental-social-viral'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Referrals -->
            <div class="env-admin-section">
                <h2><?php _e('Recent Referrals', 'environmental-social-viral'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date/Time', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Referrer', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Referred User', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Source', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Status', 'environmental-social-viral'); ?></th>
                            <th><?php _e('Reward', 'environmental-social-viral'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_referrals as $referral): ?>
                        <tr>
                            <td><?php echo esc_html(date('M j, Y H:i', strtotime($referral['created_at']))); ?></td>
                            <td>
                                <strong><?php echo esc_html($referral['referrer_name']); ?></strong>
                                <div class="referral-code">
                                    <code><?php echo esc_html($referral['referral_code']); ?></code>
                                </div>
                            </td>
                            <td>
                                <?php if ($referral['referred_user_id']): ?>
                                    <strong><?php echo esc_html($referral['referred_name']); ?></strong>
                                    <div><?php echo esc_html($referral['referred_email']); ?></div>
                                <?php else: ?>
                                    <em><?php _e('Anonymous visitor', 'environmental-social-viral'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="env-source-badge <?php echo esc_attr($referral['source']); ?>">
                                    <?php echo esc_html(ucfirst($referral['source'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="env-status-badge <?php echo esc_attr($referral['status']); ?>">
                                    <?php echo esc_html(ucfirst($referral['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($referral['reward_amount'] > 0): ?>
                                    $<?php echo number_format($referral['reward_amount'], 2); ?>
                                <?php else: ?>
                                    <em><?php _e('N/A', 'environmental-social-viral'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Rewards Summary -->
            <div class="env-admin-section">
                <h2><?php _e('Rewards Summary', 'environmental-social-viral'); ?></h2>
                
                <div class="env-rewards-summary">
                    <div class="env-reward-stat">
                        <div class="reward-amount">$<?php echo number_format($reward_summary['total_paid'], 2); ?></div>
                        <div class="reward-label"><?php _e('Total Paid', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-reward-stat">
                        <div class="reward-amount">$<?php echo number_format($reward_summary['total_pending'], 2); ?></div>
                        <div class="reward-label"><?php _e('Pending Payments', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-reward-stat">
                        <div class="reward-amount">$<?php echo number_format($reward_summary['avg_reward'], 2); ?></div>
                        <div class="reward-label"><?php _e('Average Reward', 'environmental-social-viral'); ?></div>
                    </div>
                    
                    <div class="env-reward-stat">
                        <div class="reward-amount"><?php echo number_format($reward_summary['active_referrers']); ?></div>
                        <div class="reward-label"><?php _e('Active Referrers', 'environmental-social-viral'); ?></div>
                    </div>
                </div>
                
                <div class="env-reward-actions">
                    <button class="button button-primary" id="process-all-rewards">
                        <?php _e('Process All Pending Rewards', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button" id="export-reward-data">
                        <?php _e('Export Reward Data', 'environmental-social-viral'); ?>
                    </button>
                    <button class="button" id="send-reward-notifications">
                        <?php _e('Send Reward Notifications', 'environmental-social-viral'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Referral Link Generator Modal -->
        <div id="referral-link-modal" class="env-modal" style="display: none;">
            <div class="env-modal-content">
                <div class="env-modal-header">
                    <h3><?php _e('Generate Referral Links', 'environmental-social-viral'); ?></h3>
                    <span class="env-modal-close">&times;</span>
                </div>
                <div class="env-modal-body">
                    <form id="referral-link-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="referral-user"><?php _e('Select User', 'environmental-social-viral'); ?></label></th>
                                <td>
                                    <select id="referral-user" name="user_id" required>
                                        <option value=""><?php _e('Select a user...', 'environmental-social-viral'); ?></option>
                                        <?php
                                        $users = get_users(array('fields' => array('ID', 'display_name', 'user_email')));
                                        foreach ($users as $user):
                                        ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="referral-pages"><?php _e('Pages/Posts', 'environmental-social-viral'); ?></label></th>
                                <td>
                                    <select id="referral-pages" name="content_ids[]" multiple>
                                        <option value="all"><?php _e('All Pages/Posts', 'environmental-social-viral'); ?></option>
                                        <?php
                                        $posts = get_posts(array(
                                            'post_type' => array('post', 'page'),
                                            'post_status' => 'publish',
                                            'numberposts' => -1
                                        ));
                                        foreach ($posts as $post):
                                        ?>
                                        <option value="<?php echo esc_attr($post->ID); ?>">
                                            <?php echo esc_html($post->post_title . ' (' . ucfirst($post->post_type) . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('Select specific pages/posts or choose "All" for universal links', 'environmental-social-viral'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="link-expiry"><?php _e('Link Expiry', 'environmental-social-viral'); ?></label></th>
                                <td>
                                    <select id="link-expiry" name="expiry_days">
                                        <option value=""><?php _e('No Expiry', 'environmental-social-viral'); ?></option>
                                        <option value="7"><?php _e('7 Days', 'environmental-social-viral'); ?></option>
                                        <option value="30"><?php _e('30 Days', 'environmental-social-viral'); ?></option>
                                        <option value="90"><?php _e('90 Days', 'environmental-social-viral'); ?></option>
                                        <option value="365"><?php _e('1 Year', 'environmental-social-viral'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="env-modal-footer">
                    <button class="button button-primary" id="generate-links"><?php _e('Generate Links', 'environmental-social-viral'); ?></button>
                    <button class="button" id="cancel-link-generation"><?php _e('Cancel', 'environmental-social-viral'); ?></button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize charts
            initReferralCharts();
            
            // Handle bulk actions
            $('#apply-bulk-referrer-action').on('click', function() {
                var action = $('#bulk-referrer-action').val();
                var selected = $('input[name="referrer[]"]:checked').map(function() {
                    return this.value;
                }).get();
                
                if (!action || selected.length === 0) {
                    alert('<?php _e('Please select an action and at least one referrer.', 'environmental-social-viral'); ?>');
                    return;
                }
                
                processBulkReferrerAction(action, selected);
            });
            
            // Handle individual reward processing
            $('.process-reward').on('click', function() {
                var userId = $(this).data('user-id');
                var pendingAmount = $(this).data('pending');
                
                if (confirm('<?php _e('Process reward of $', 'environmental-social-viral'); ?>' + pendingAmount + '?')) {
                    processIndividualReward(userId);
                }
            });
            
            // Handle referral link generation
            $('#generate-referral-links').on('click', function() {
                $('#referral-link-modal').show();
            });
            
            $('.env-modal-close, #cancel-link-generation').on('click', function() {
                $('#referral-link-modal').hide();
            });
            
            $('#generate-links').on('click', function() {
                generateReferralLinks();
            });
        });
        
        function initReferralCharts() {
            // Initialize referral activity chart
            var ctx = document.getElementById('referralChart').getContext('2d');
            // Chart implementation here
            
            // Initialize referral sources chart
            var ctx2 = document.getElementById('referralSourcesChart').getContext('2d');
            // Chart implementation here
        }
        
        function processBulkReferrerAction(action, userIds) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_referral_bulk_action',
                    bulk_action: action,
                    user_ids: userIds,
                    nonce: '<?php echo wp_create_nonce('env_referral_bulk_action'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Action failed.', 'environmental-social-viral'); ?>');
                    }
                }
            });
        }
        
        function processIndividualReward(userId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_referral_process_reward',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('env_referral_process_reward'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Reward processing failed.', 'environmental-social-viral'); ?>');
                    }
                }
            });
        }
        
        function generateReferralLinks() {
            var formData = $('#referral-link-form').serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=env_referral_generate_links&nonce=<?php echo wp_create_nonce('env_referral_generate_links'); ?>',
                success: function(response) {
                    if (response.success) {
                        $('#referral-link-modal').hide();
                        // Show generated links or redirect to links page
                        alert('<?php _e('Referral links generated successfully!', 'environmental-social-viral'); ?>');
                    } else {
                        alert(response.data.message || '<?php _e('Link generation failed.', 'environmental-social-viral'); ?>');
                    }
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render referral settings page
     */
    public function render_referral_settings_page() {
        $settings = get_option('env_social_viral_referral_settings', array());
        
        ?>
        <div class="wrap env-referral-settings">
            <h1><?php _e('Referral System Settings', 'environmental-social-viral'); ?></h1>
            
            <form method="post" action="" id="referral-settings-form">
                <?php wp_nonce_field('env_referral_settings', 'env_referral_settings_nonce'); ?>
                
                <!-- Basic Settings -->
                <div class="env-admin-section">
                    <h2><?php _e('Basic Referral Settings', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="referral_enabled"><?php _e('Enable Referral System', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="referral_enabled" name="referral_enabled" value="1" 
                                       <?php checked(!empty($settings['referral_enabled'])); ?>>
                                <p class="description"><?php _e('Enable or disable the entire referral system', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="default_reward_amount"><?php _e('Default Reward Amount', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="default_reward_amount" name="default_reward_amount" 
                                       value="<?php echo esc_attr($settings['default_reward_amount'] ?? 5.00); ?>" 
                                       step="0.01" min="0">
                                <p class="description"><?php _e('Default reward amount for successful referrals (in dollars)', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="referral_code_length"><?php _e('Referral Code Length', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <select id="referral_code_length" name="referral_code_length">
                                    <option value="6" <?php selected($settings['referral_code_length'] ?? 8, 6); ?>>6 characters</option>
                                    <option value="8" <?php selected($settings['referral_code_length'] ?? 8, 8); ?>>8 characters</option>
                                    <option value="10" <?php selected($settings['referral_code_length'] ?? 8, 10); ?>>10 characters</option>
                                    <option value="12" <?php selected($settings['referral_code_length'] ?? 8, 12); ?>>12 characters</option>
                                </select>
                                <p class="description"><?php _e('Length of automatically generated referral codes', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cookie_duration"><?php _e('Cookie Duration (days)', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cookie_duration" name="cookie_duration" 
                                       value="<?php echo esc_attr($settings['cookie_duration'] ?? 30); ?>" 
                                       min="1" max="365">
                                <p class="description"><?php _e('How long referral cookies are stored (1-365 days)', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Reward Settings -->
                <div class="env-admin-section">
                    <h2><?php _e('Reward Settings', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="reward_trigger"><?php _e('Reward Trigger', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <select id="reward_trigger" name="reward_trigger">
                                    <option value="registration" <?php selected($settings['reward_trigger'] ?? 'registration', 'registration'); ?>>
                                        <?php _e('User Registration', 'environmental-social-viral'); ?>
                                    </option>
                                    <option value="first_share" <?php selected($settings['reward_trigger'] ?? 'registration', 'first_share'); ?>>
                                        <?php _e('First Share Action', 'environmental-social-viral'); ?>
                                    </option>
                                    <option value="first_purchase" <?php selected($settings['reward_trigger'] ?? 'registration', 'first_purchase'); ?>>
                                        <?php _e('First Purchase', 'environmental-social-viral'); ?>
                                    </option>
                                    <option value="manual" <?php selected($settings['reward_trigger'] ?? 'registration', 'manual'); ?>>
                                        <?php _e('Manual Approval', 'environmental-social-viral'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('When should referral rewards be triggered?', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_rewards_per_referrer"><?php _e('Max Rewards per Referrer', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_rewards_per_referrer" name="max_rewards_per_referrer" 
                                       value="<?php echo esc_attr($settings['max_rewards_per_referrer'] ?? 0); ?>" 
                                       min="0">
                                <p class="description"><?php _e('Maximum number of rewards per referrer (0 for unlimited)', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="minimum_payout"><?php _e('Minimum Payout Amount', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="minimum_payout" name="minimum_payout" 
                                       value="<?php echo esc_attr($settings['minimum_payout'] ?? 25.00); ?>" 
                                       step="0.01" min="0">
                                <p class="description"><?php _e('Minimum amount before payout is processed', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="auto_payout"><?php _e('Automatic Payouts', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_payout" name="auto_payout" value="1" 
                                       <?php checked(!empty($settings['auto_payout'])); ?>>
                                <p class="description"><?php _e('Automatically process payouts when minimum is reached', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Fraud Prevention -->
                <div class="env-admin-section">
                    <h2><?php _e('Fraud Prevention', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="self_referral_prevention"><?php _e('Prevent Self-Referrals', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="self_referral_prevention" name="self_referral_prevention" value="1" 
                                       <?php checked(!empty($settings['self_referral_prevention'])); ?>>
                                <p class="description"><?php _e('Prevent users from referring themselves', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ip_tracking"><?php _e('IP Address Tracking', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="ip_tracking" name="ip_tracking" value="1" 
                                       <?php checked(!empty($settings['ip_tracking'])); ?>>
                                <p class="description"><?php _e('Track IP addresses to prevent fraudulent referrals', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_referrals_per_ip"><?php _e('Max Referrals per IP', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_referrals_per_ip" name="max_referrals_per_ip" 
                                       value="<?php echo esc_attr($settings['max_referrals_per_ip'] ?? 3); ?>" 
                                       min="1">
                                <p class="description"><?php _e('Maximum referrals allowed from the same IP address', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="referral_review_period"><?php _e('Review Period (days)', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="referral_review_period" name="referral_review_period" 
                                       value="<?php echo esc_attr($settings['referral_review_period'] ?? 7); ?>" 
                                       min="0" max="30">
                                <p class="description"><?php _e('Days to review referrals before approving rewards (0 for immediate)', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Notification Settings -->
                <div class="env-admin-section">
                    <h2><?php _e('Notification Settings', 'environmental-social-viral'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="notify_referrer"><?php _e('Notify Referrer', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="notify_referrer" name="notify_referrer" value="1" 
                                       <?php checked(!empty($settings['notify_referrer'])); ?>>
                                <p class="description"><?php _e('Send email notification when referral is successful', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="notify_referred"><?php _e('Notify Referred User', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="notify_referred" name="notify_referred" value="1" 
                                       <?php checked(!empty($settings['notify_referred'])); ?>>
                                <p class="description"><?php _e('Send welcome email to referred users', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="admin_notifications"><?php _e('Admin Notifications', 'environmental-social-viral'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="admin_notifications" name="admin_notifications" value="1" 
                                       <?php checked(!empty($settings['admin_notifications'])); ?>>
                                <p class="description"><?php _e('Send notifications to admin for new referrals', 'environmental-social-viral'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button-primary" 
                           value="<?php _e('Save Referral Settings', 'environmental-social-viral'); ?>">
                    <button type="button" class="button" id="reset-referral-settings">
                        <?php _e('Reset to Defaults', 'environmental-social-viral'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get referral analytics data
     */
    private function get_referral_analytics() {
        // Get current period data
        $current_stats = $this->wpdb->get_row("
            SELECT 
                COUNT(*) as total_referrals,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_conversions,
                AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100 as conversion_rate,
                SUM(CASE WHEN reward_status = 'paid' THEN reward_amount ELSE 0 END) as total_rewards,
                COUNT(CASE WHEN reward_status = 'pending' THEN 1 END) as pending_rewards
            FROM {$this->tables['referrals']} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get previous period for comparison
        $previous_stats = $this->wpdb->get_row("
            SELECT 
                COUNT(*) as total_referrals,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_conversions,
                AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100 as conversion_rate
            FROM {$this->tables['referrals']} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Calculate changes
        $referrals_change = $previous_stats->total_referrals > 0 
            ? (($current_stats->total_referrals - $previous_stats->total_referrals) / $previous_stats->total_referrals) * 100 
            : 0;
            
        $conversions_change = $previous_stats->successful_conversions > 0 
            ? (($current_stats->successful_conversions - $previous_stats->successful_conversions) / $previous_stats->successful_conversions) * 100 
            : 0;
            
        $conversion_rate_change = $previous_stats->conversion_rate > 0 
            ? (($current_stats->conversion_rate - $previous_stats->conversion_rate) / $previous_stats->conversion_rate) * 100 
            : 0;
        
        return array(
            'total_referrals' => $current_stats->total_referrals ?: 0,
            'successful_conversions' => $current_stats->successful_conversions ?: 0,
            'conversion_rate' => $current_stats->conversion_rate ?: 0,
            'total_rewards' => $current_stats->total_rewards ?: 0,
            'pending_rewards' => $current_stats->pending_rewards ?: 0,
            'referrals_change' => $referrals_change,
            'conversions_change' => $conversions_change,
            'conversion_rate_change' => $conversion_rate_change
        );
    }
    
    /**
     * Get top referrers
     */
    private function get_top_referrers($limit = 10) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                r.referrer_user_id as user_id,
                u.display_name,
                u.user_email,
                r.referral_code,
                COUNT(*) as total_referrals,
                COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as conversions,
                AVG(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) * 100 as conversion_rate,
                SUM(CASE WHEN r.reward_status = 'paid' THEN r.reward_amount ELSE 0 END) as total_rewards,
                SUM(CASE WHEN r.reward_status = 'pending' THEN r.reward_amount ELSE 0 END) as pending_rewards,
                'active' as status
            FROM {$this->tables['referrals']} r
            LEFT JOIN {$this->wpdb->users} u ON r.referrer_user_id = u.ID
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY r.referrer_user_id
            ORDER BY total_referrals DESC, conversions DESC
            LIMIT %d
        ", $limit), ARRAY_A);
    }
    
    /**
     * Get recent referrals
     */
    private function get_recent_referrals($limit = 15) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT 
                r.*,
                u1.display_name as referrer_name,
                u2.display_name as referred_name,
                u2.user_email as referred_email
            FROM {$this->tables['referrals']} r
            LEFT JOIN {$this->wpdb->users} u1 ON r.referrer_user_id = u1.ID
            LEFT JOIN {$this->wpdb->users} u2 ON r.referred_user_id = u2.ID
            ORDER BY r.created_at DESC
            LIMIT %d
        ", $limit), ARRAY_A);
    }
    
    /**
     * Get reward summary
     */
    private function get_reward_summary() {
        $summary = $this->wpdb->get_row("
            SELECT 
                SUM(CASE WHEN reward_status = 'paid' THEN reward_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN reward_status = 'pending' THEN reward_amount ELSE 0 END) as total_pending,
                AVG(CASE WHEN reward_amount > 0 THEN reward_amount END) as avg_reward,
                COUNT(DISTINCT referrer_user_id) as active_referrers
            FROM {$this->tables['referrals']}
            WHERE reward_amount > 0
        ");
        
        return array(
            'total_paid' => $summary->total_paid ?: 0,
            'total_pending' => $summary->total_pending ?: 0,
            'avg_reward' => $summary->avg_reward ?: 0,
            'active_referrers' => $summary->active_referrers ?: 0
        );
    }
    
    /**
     * AJAX: Get referral analytics
     */
    public function ajax_get_referral_analytics() {
        check_ajax_referer('env_referral_analytics', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        $analytics = $this->get_referral_analytics();
        wp_send_json_success($analytics);
    }
    
    /**
     * AJAX: Update referral settings
     */
    public function ajax_update_referral_settings() {
        check_ajax_referer('env_referral_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        $settings = array();
        
        // Sanitize and save settings
        $fields = array(
            'referral_enabled' => 'boolean',
            'default_reward_amount' => 'float',
            'referral_code_length' => 'int',
            'cookie_duration' => 'int',
            'reward_trigger' => 'string',
            'max_rewards_per_referrer' => 'int',
            'minimum_payout' => 'float',
            'auto_payout' => 'boolean',
            'self_referral_prevention' => 'boolean',
            'ip_tracking' => 'boolean',
            'max_referrals_per_ip' => 'int',
            'referral_review_period' => 'int',
            'notify_referrer' => 'boolean',
            'notify_referred' => 'boolean',
            'admin_notifications' => 'boolean'
        );
        
        foreach ($fields as $field => $type) {
            if (isset($_POST[$field])) {
                switch ($type) {
                    case 'boolean':
                        $settings[$field] = !empty($_POST[$field]);
                        break;
                    case 'int':
                        $settings[$field] = intval($_POST[$field]);
                        break;
                    case 'float':
                        $settings[$field] = floatval($_POST[$field]);
                        break;
                    case 'string':
                        $settings[$field] = sanitize_text_field($_POST[$field]);
                        break;
                }
            }
        }
        
        update_option('env_social_viral_referral_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Referral settings updated successfully!', 'environmental-social-viral')
        ));
    }
    
    /**
     * AJAX: Process reward
     */
    public function ajax_process_reward() {
        check_ajax_referer('env_referral_process_reward', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        $user_id = intval($_POST['user_id']);
        
        // Process pending rewards for this user
        $result = $this->referral_system->process_pending_rewards($user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Rewards processed successfully!', 'environmental-social-viral')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to process rewards.', 'environmental-social-viral')
            ));
        }
    }
    
    /**
     * AJAX: Bulk action
     */
    public function ajax_bulk_action() {
        check_ajax_referer('env_referral_bulk_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $user_ids = array_map('intval', $_POST['user_ids']);
        
        $success_count = 0;
        
        foreach ($user_ids as $user_id) {
            switch ($action) {
                case 'reward':
                    if ($this->referral_system->process_pending_rewards($user_id)) {
                        $success_count++;
                    }
                    break;
                case 'block':
                    // Implement user blocking logic
                    $success_count++;
                    break;
                case 'unblock':
                    // Implement user unblocking logic
                    $success_count++;
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Bulk action completed for %d users.', 'environmental-social-viral'), $success_count)
        ));
    }
    
    /**
     * AJAX: Export referral data
     */
    public function ajax_export_referral_data() {
        check_ajax_referer('env_referral_export', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        // Generate CSV export
        $filename = 'referral-data-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Date',
            'Referrer',
            'Referrer Email',
            'Referred User',
            'Referred Email',
            'Status',
            'Reward Amount',
            'Reward Status'
        ));
        
        // Get referral data
        $referrals = $this->wpdb->get_results("
            SELECT 
                r.*,
                u1.display_name as referrer_name,
                u1.user_email as referrer_email,
                u2.display_name as referred_name,
                u2.user_email as referred_email
            FROM {$this->tables['referrals']} r
            LEFT JOIN {$this->wpdb->users} u1 ON r.referrer_user_id = u1.ID
            LEFT JOIN {$this->wpdb->users} u2 ON r.referred_user_id = u2.ID
            ORDER BY r.created_at DESC
        ");
        
        foreach ($referrals as $referral) {
            fputcsv($output, array(
                $referral->created_at,
                $referral->referrer_name,
                $referral->referrer_email,
                $referral->referred_name ?: 'Anonymous',
                $referral->referred_email ?: 'N/A',
                $referral->status,
                $referral->reward_amount,
                $referral->reward_status
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Generate referral links
     */
    public function ajax_generate_referral_links() {
        check_ajax_referer('env_referral_generate_links', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'environmental-social-viral'));
        }
        
        $user_id = intval($_POST['user_id']);
        $content_ids = $_POST['content_ids'] ?? array();
        $expiry_days = intval($_POST['expiry_days']);
        
        $links = array();
        
        if (in_array('all', $content_ids)) {
            // Generate universal referral link
            $links[] = $this->referral_system->generate_referral_link($user_id, null, $expiry_days);
        } else {
            // Generate specific content links
            foreach ($content_ids as $content_id) {
                $links[] = $this->referral_system->generate_referral_link($user_id, intval($content_id), $expiry_days);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Referral links generated successfully!', 'environmental-social-viral'),
            'links' => $links
        ));
    }
    
    /**
     * AJAX: Validate referral code
     */
    public function ajax_validate_referral_code() {
        check_ajax_referer('env_referral_validate', 'nonce');
        
        $code = sanitize_text_field($_POST['code']);
        $is_valid = $this->referral_system->validate_referral_code($code);
        
        wp_send_json_success(array(
            'valid' => $is_valid,
            'message' => $is_valid ? __('Valid referral code', 'environmental-social-viral') : __('Invalid referral code', 'environmental-social-viral')
        ));
    }
}
