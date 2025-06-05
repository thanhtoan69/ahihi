<?php
/**
 * Partner Admin Class
 * 
 * Handles partner management interface and administration
 * 
 * @package Environmental_Voucher_Rewards
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EVR_Partner_Admin {
    
    private static $instance = null;
    private $db_manager;
    private $partner_integration;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db_manager = EVR_Database_Manager::get_instance();
        $this->partner_integration = EVR_Partner_Integration::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_menu_page'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_evr_create_partner', array($this, 'ajax_create_partner'));
        add_action('wp_ajax_evr_update_partner', array($this, 'ajax_update_partner'));
        add_action('wp_ajax_evr_delete_partner', array($this, 'ajax_delete_partner'));
        add_action('wp_ajax_evr_create_partner_discount', array($this, 'ajax_create_partner_discount'));
        add_action('wp_ajax_evr_sync_partner_data', array($this, 'ajax_sync_partner_data'));
        add_action('wp_ajax_evr_get_partner_stats', array($this, 'ajax_get_partner_stats'));
        add_action('wp_ajax_evr_export_partner_data', array($this, 'ajax_export_partner_data'));
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'evr-admin',
            __('Partners', 'env-voucher-rewards'),
            __('Partners', 'env-voucher-rewards'),
            'manage_options',
            'evr-partners',
            array($this, 'display_partners_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'evr-partners') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script(
            'evr-partner-admin',
            EVR_PLUGIN_URL . 'assets/js/partner-admin.js',
            array('jquery', 'wp-util', 'chart-js'),
            EVR_VERSION,
            true
        );
        
        wp_localize_script('evr-partner-admin', 'evrPartnerAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('evr_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this partner?', 'env-voucher-rewards'),
                'syncSuccess' => __('Partner data synchronized successfully', 'env-voucher-rewards'),
                'syncError' => __('Error synchronizing partner data', 'env-voucher-rewards'),
                'loading' => __('Loading...', 'env-voucher-rewards')
            )
        ));
        
        wp_enqueue_style(
            'evr-partner-admin',
            EVR_PLUGIN_URL . 'assets/css/partner-admin.css',
            array(),
            EVR_VERSION
        );
    }
    
    /**
     * Display partners page
     */
    public function display_partners_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        
        ?>
        <div class="wrap evr-partners-admin">
            <h1 class="wp-heading-inline"><?php _e('Partner Management', 'env-voucher-rewards'); ?></h1>
            <a href="#" class="page-title-action" id="add-new-partner"><?php _e('Add New Partner', 'env-voucher-rewards'); ?></a>
            <hr class="wp-header-end">
            
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo admin_url('admin.php?page=evr-partners&tab=overview'); ?>" 
                   class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Overview', 'env-voucher-rewards'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=evr-partners&tab=partners'); ?>" 
                   class="nav-tab <?php echo $tab === 'partners' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Partners', 'env-voucher-rewards'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=evr-partners&tab=discounts'); ?>" 
                   class="nav-tab <?php echo $tab === 'discounts' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Discounts', 'env-voucher-rewards'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=evr-partners&tab=analytics'); ?>" 
                   class="nav-tab <?php echo $tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Analytics', 'env-voucher-rewards'); ?>
                </a>
            </nav>
            
            <div class="evr-tab-content">
                <?php
                switch ($tab) {
                    case 'overview':
                        $this->display_overview_tab();
                        break;
                    case 'partners':
                        $this->display_partners_tab();
                        break;
                    case 'discounts':
                        $this->display_discounts_tab();
                        break;
                    case 'analytics':
                        $this->display_analytics_tab();
                        break;
                    default:
                        $this->display_overview_tab();
                }
                ?>
            </div>
        </div>
        
        <?php $this->render_modals(); ?>
        <?php
    }
    
    /**
     * Display overview tab
     */
    private function display_overview_tab() {
        $stats = $this->get_overview_stats();
        
        ?>
        <div class="evr-partners-overview">
            <div class="evr-stats-grid">
                <div class="evr-stat-card">
                    <div class="evr-stat-icon">
                        <span class="dashicons dashicons-businessman"></span>
                    </div>
                    <div class="evr-stat-content">
                        <h3><?php echo number_format($stats['total_partners']); ?></h3>
                        <p><?php _e('Total Partners', 'env-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="evr-stat-card">
                    <div class="evr-stat-icon">
                        <span class="dashicons dashicons-store"></span>
                    </div>
                    <div class="evr-stat-content">
                        <h3><?php echo number_format($stats['active_partners']); ?></h3>
                        <p><?php _e('Active Partners', 'env-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="evr-stat-card">
                    <div class="evr-stat-icon">
                        <span class="dashicons dashicons-tag"></span>
                    </div>
                    <div class="evr-stat-content">
                        <h3><?php echo number_format($stats['active_discounts']); ?></h3>
                        <p><?php _e('Active Discounts', 'env-voucher-rewards'); ?></p>
                    </div>
                </div>
                
                <div class="evr-stat-card">
                    <div class="evr-stat-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="evr-stat-content">
                        <h3><?php echo wc_price($stats['total_redemptions_value']); ?></h3>
                        <p><?php _e('Total Redemptions Value', 'env-voucher-rewards'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="evr-charts-grid">
                <div class="evr-chart-container">
                    <h3><?php _e('Partner Types Distribution', 'env-voucher-rewards'); ?></h3>
                    <canvas id="partner-types-chart"></canvas>
                </div>
                
                <div class="evr-chart-container">
                    <h3><?php _e('Monthly Redemptions', 'env-voucher-rewards'); ?></h3>
                    <canvas id="redemptions-chart"></canvas>
                </div>
            </div>
            
            <div class="evr-recent-activity">
                <h3><?php _e('Recent Partner Activity', 'env-voucher-rewards'); ?></h3>
                <div class="evr-activity-list">
                    <?php $this->display_recent_activity(); ?>
                </div>
            </div>
            
            <div class="evr-quick-actions">
                <h3><?php _e('Quick Actions', 'env-voucher-rewards'); ?></h3>
                <div class="evr-action-buttons">
                    <button type="button" class="button button-primary" id="sync-all-partners">
                        <span class="dashicons dashicons-update-alt"></span>
                        <?php _e('Sync All Partners', 'env-voucher-rewards'); ?>
                    </button>
                    <button type="button" class="button" id="export-partner-data">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Data', 'env-voucher-rewards'); ?>
                    </button>
                    <button type="button" class="button" id="send-partner-reports">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('Send Reports', 'env-voucher-rewards'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize charts
            const partnerTypesData = <?php echo json_encode($this->get_partner_types_data()); ?>;
            const redemptionsData = <?php echo json_encode($this->get_redemptions_data()); ?>;
            
            // Partner Types Chart
            new Chart(document.getElementById('partner-types-chart'), {
                type: 'doughnut',
                data: partnerTypesData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Redemptions Chart
            new Chart(document.getElementById('redemptions-chart'), {
                type: 'line',
                data: redemptionsData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display partners tab
     */
    private function display_partners_tab() {
        $partners = $this->get_partners_list();
        
        ?>
        <div class="evr-partners-list">
            <div class="evr-filters">
                <div class="evr-filter-group">
                    <select id="partner-status-filter">
                        <option value=""><?php _e('All Statuses', 'env-voucher-rewards'); ?></option>
                        <option value="active"><?php _e('Active', 'env-voucher-rewards'); ?></option>
                        <option value="pending"><?php _e('Pending', 'env-voucher-rewards'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'env-voucher-rewards'); ?></option>
                    </select>
                    
                    <select id="partner-type-filter">
                        <option value=""><?php _e('All Types', 'env-voucher-rewards'); ?></option>
                        <option value="eco_store"><?php _e('Eco Store', 'env-voucher-rewards'); ?></option>
                        <option value="restaurant"><?php _e('Restaurant', 'env-voucher-rewards'); ?></option>
                        <option value="service"><?php _e('Service', 'env-voucher-rewards'); ?></option>
                        <option value="online_shop"><?php _e('Online Shop', 'env-voucher-rewards'); ?></option>
                        <option value="local_business"><?php _e('Local Business', 'env-voucher-rewards'); ?></option>
                    </select>
                    
                    <input type="text" id="partner-search" placeholder="<?php _e('Search partners...', 'env-voucher-rewards'); ?>">
                </div>
                
                <div class="evr-bulk-actions">
                    <select id="partner-bulk-action">
                        <option value=""><?php _e('Bulk Actions', 'env-voucher-rewards'); ?></option>
                        <option value="activate"><?php _e('Activate', 'env-voucher-rewards'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'env-voucher-rewards'); ?></option>
                        <option value="sync"><?php _e('Sync Data', 'env-voucher-rewards'); ?></option>
                        <option value="delete"><?php _e('Delete', 'env-voucher-rewards'); ?></option>
                    </select>
                    <button type="button" class="button" id="apply-bulk-action"><?php _e('Apply', 'env-voucher-rewards'); ?></button>
                </div>
            </div>
            
            <div class="evr-partners-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-partners">
                            </td>
                            <th class="manage-column"><?php _e('Partner', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Type', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Status', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Discounts', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Redemptions', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Last Sync', 'env-voucher-rewards'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'env-voucher-rewards'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="partners-table-body">
                        <?php foreach ($partners as $partner) : ?>
                        <tr data-partner-id="<?php echo esc_attr($partner->partner_id); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" class="partner-checkbox" value="<?php echo esc_attr($partner->partner_id); ?>">
                            </th>
                            <td class="partner-info">
                                <div class="partner-avatar">
                                    <?php if ($partner->partner_logo) : ?>
                                        <img src="<?php echo esc_url($partner->partner_logo); ?>" alt="<?php echo esc_attr($partner->partner_name); ?>">
                                    <?php else : ?>
                                        <span class="dashicons dashicons-store"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="partner-details">
                                    <strong class="partner-name"><?php echo esc_html($partner->partner_name); ?></strong>
                                    <div class="partner-meta">
                                        <?php if ($partner->website_url) : ?>
                                            <a href="<?php echo esc_url($partner->website_url); ?>" target="_blank" rel="noopener">
                                                <?php echo esc_html(parse_url($partner->website_url, PHP_URL_HOST)); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="partner-type"><?php echo esc_html(ucfirst(str_replace('_', ' ', $partner->partner_type))); ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($partner->status); ?>">
                                    <?php echo esc_html(ucfirst($partner->status)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="discount-count"><?php echo number_format($partner->discount_count ?? 0); ?></span>
                            </td>
                            <td>
                                <span class="redemption-count"><?php echo number_format($partner->redemption_count ?? 0); ?></span>
                            </td>
                            <td>
                                <?php echo $partner->last_sync ? esc_html(date_i18n(get_option('date_format'), strtotime($partner->last_sync))) : __('Never', 'env-voucher-rewards'); ?>
                            </td>
                            <td class="partner-actions">
                                <button type="button" class="button button-small edit-partner" data-partner-id="<?php echo esc_attr($partner->partner_id); ?>">
                                    <?php _e('Edit', 'env-voucher-rewards'); ?>
                                </button>
                                <button type="button" class="button button-small sync-partner" data-partner-id="<?php echo esc_attr($partner->partner_id); ?>">
                                    <?php _e('Sync', 'env-voucher-rewards'); ?>
                                </button>
                                <button type="button" class="button button-small view-stats" data-partner-id="<?php echo esc_attr($partner->partner_id); ?>">
                                    <?php _e('Stats', 'env-voucher-rewards'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display discounts tab
     */
    private function display_discounts_tab() {
        $discounts = $this->get_partner_discounts_list();
        
        ?>
        <div class="evr-partner-discounts">
            <div class="evr-discount-header">
                <button type="button" class="button button-primary" id="create-discount">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create Discount', 'env-voucher-rewards'); ?>
                </button>
            </div>
            
            <div class="evr-discounts-grid">
                <?php foreach ($discounts as $discount) : ?>
                <div class="evr-discount-card" data-discount-id="<?php echo esc_attr($discount->discount_id); ?>">
                    <div class="discount-header">
                        <h3 class="discount-name"><?php echo esc_html($discount->discount_name); ?></h3>
                        <div class="discount-status">
                            <span class="status-badge status-<?php echo esc_attr($discount->status); ?>">
                                <?php echo esc_html(ucfirst($discount->status)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="discount-content">
                        <div class="discount-partner">
                            <strong><?php echo esc_html($discount->partner_name); ?></strong>
                        </div>
                        
                        <div class="discount-value">
                            <?php if ($discount->discount_type === 'percentage') : ?>
                                <span class="discount-amount"><?php echo esc_html($discount->discount_value); ?>%</span>
                            <?php else : ?>
                                <span class="discount-amount"><?php echo wc_price($discount->discount_value); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="discount-description">
                            <?php echo esc_html($discount->description); ?>
                        </div>
                        
                        <div class="discount-meta">
                            <div class="meta-item">
                                <span class="meta-label"><?php _e('Valid until:', 'env-voucher-rewards'); ?></span>
                                <span class="meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($discount->valid_until))); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label"><?php _e('Redemptions:', 'env-voucher-rewards'); ?></span>
                                <span class="meta-value"><?php echo number_format($discount->current_redemptions ?? 0); ?>/<?php echo $discount->max_redemptions ? number_format($discount->max_redemptions) : '∞'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="discount-actions">
                        <button type="button" class="button button-small edit-discount" data-discount-id="<?php echo esc_attr($discount->discount_id); ?>">
                            <?php _e('Edit', 'env-voucher-rewards'); ?>
                        </button>
                        <button type="button" class="button button-small view-discount-stats" data-discount-id="<?php echo esc_attr($discount->discount_id); ?>">
                            <?php _e('Stats', 'env-voucher-rewards'); ?>
                        </button>
                        <?php if ($discount->status === 'active') : ?>
                        <button type="button" class="button button-small deactivate-discount" data-discount-id="<?php echo esc_attr($discount->discount_id); ?>">
                            <?php _e('Deactivate', 'env-voucher-rewards'); ?>
                        </button>
                        <?php else : ?>
                        <button type="button" class="button button-small activate-discount" data-discount-id="<?php echo esc_attr($discount->discount_id); ?>">
                            <?php _e('Activate', 'env-voucher-rewards'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display analytics tab
     */
    private function display_analytics_tab() {
        $analytics_data = $this->get_partner_analytics();
        
        ?>
        <div class="evr-partner-analytics">
            <div class="evr-analytics-filters">
                <div class="filter-group">
                    <label for="analytics-date-range"><?php _e('Date Range:', 'env-voucher-rewards'); ?></label>
                    <select id="analytics-date-range">
                        <option value="7"><?php _e('Last 7 days', 'env-voucher-rewards'); ?></option>
                        <option value="30" selected><?php _e('Last 30 days', 'env-voucher-rewards'); ?></option>
                        <option value="90"><?php _e('Last 3 months', 'env-voucher-rewards'); ?></option>
                        <option value="365"><?php _e('Last year', 'env-voucher-rewards'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="analytics-partner-filter"><?php _e('Partner:', 'env-voucher-rewards'); ?></label>
                    <select id="analytics-partner-filter">
                        <option value=""><?php _e('All Partners', 'env-voucher-rewards'); ?></option>
                        <?php foreach ($this->get_partners_list() as $partner) : ?>
                        <option value="<?php echo esc_attr($partner->partner_id); ?>"><?php echo esc_html($partner->partner_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="evr-analytics-grid">
                <div class="evr-analytics-card">
                    <h3><?php _e('Top Performing Partners', 'env-voucher-rewards'); ?></h3>
                    <canvas id="top-partners-chart"></canvas>
                </div>
                
                <div class="evr-analytics-card">
                    <h3><?php _e('Discount Performance', 'env-voucher-rewards'); ?></h3>
                    <canvas id="discount-performance-chart"></canvas>
                </div>
                
                <div class="evr-analytics-card">
                    <h3><?php _e('Redemption Trends', 'env-voucher-rewards'); ?></h3>
                    <canvas id="redemption-trends-chart"></canvas>
                </div>
                
                <div class="evr-analytics-card">
                    <h3><?php _e('Environmental Impact', 'env-voucher-rewards'); ?></h3>
                    <div class="evr-impact-metrics">
                        <div class="impact-metric">
                            <span class="metric-value"><?php echo number_format($analytics_data['total_eco_points']); ?></span>
                            <span class="metric-label"><?php _e('Eco Points Generated', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="impact-metric">
                            <span class="metric-value"><?php echo number_format($analytics_data['carbon_footprint_reduced'], 2); ?>kg</span>
                            <span class="metric-label"><?php _e('CO₂ Reduced', 'env-voucher-rewards'); ?></span>
                        </div>
                        <div class="impact-metric">
                            <span class="metric-value"><?php echo number_format($analytics_data['sustainability_score'], 1); ?></span>
                            <span class="metric-label"><?php _e('Avg Sustainability Score', 'env-voucher-rewards'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            const analyticsData = <?php echo json_encode($analytics_data); ?>;
            
            // Initialize analytics charts
            // Top Partners Chart
            new Chart(document.getElementById('top-partners-chart'), {
                type: 'bar',
                data: analyticsData.topPartnersData,
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Discount Performance Chart
            new Chart(document.getElementById('discount-performance-chart'), {
                type: 'doughnut',
                data: analyticsData.discountPerformanceData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Redemption Trends Chart
            new Chart(document.getElementById('redemption-trends-chart'), {
                type: 'line',
                data: analyticsData.redemptionTrendsData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render modals
     */
    private function render_modals() {
        ?>
        <!-- Partner Creation/Edit Modal -->
        <div id="partner-modal" class="evr-modal" style="display: none;">
            <div class="evr-modal-content">
                <div class="evr-modal-header">
                    <h2 id="partner-modal-title"><?php _e('Add New Partner', 'env-voucher-rewards'); ?></h2>
                    <button type="button" class="evr-modal-close">&times;</button>
                </div>
                
                <div class="evr-modal-body">
                    <form id="partner-form">
                        <input type="hidden" id="partner-id" name="partner_id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="partner-name"><?php _e('Partner Name *', 'env-voucher-rewards'); ?></label>
                                <input type="text" id="partner-name" name="partner_name" required>
                            </div>
                            <div class="form-group">
                                <label for="partner-type"><?php _e('Partner Type *', 'env-voucher-rewards'); ?></label>
                                <select id="partner-type" name="partner_type" required>
                                    <option value="eco_store"><?php _e('Eco Store', 'env-voucher-rewards'); ?></option>
                                    <option value="restaurant"><?php _e('Restaurant', 'env-voucher-rewards'); ?></option>
                                    <option value="service"><?php _e('Service', 'env-voucher-rewards'); ?></option>
                                    <option value="online_shop"><?php _e('Online Shop', 'env-voucher-rewards'); ?></option>
                                    <option value="local_business"><?php _e('Local Business', 'env-voucher-rewards'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="partner-description"><?php _e('Description', 'env-voucher-rewards'); ?></label>
                            <textarea id="partner-description" name="partner_description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="partner-email"><?php _e('Contact Email *', 'env-voucher-rewards'); ?></label>
                                <input type="email" id="partner-email" name="contact_email" required>
                            </div>
                            <div class="form-group">
                                <label for="partner-phone"><?php _e('Phone Number', 'env-voucher-rewards'); ?></label>
                                <input type="tel" id="partner-phone" name="phone_number">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="partner-website"><?php _e('Website URL', 'env-voucher-rewards'); ?></label>
                            <input type="url" id="partner-website" name="website_url">
                        </div>
                        
                        <div class="form-group">
                            <label for="partner-address"><?php _e('Address', 'env-voucher-rewards'); ?></label>
                            <textarea id="partner-address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="partner-status"><?php _e('Status', 'env-voucher-rewards'); ?></label>
                                <select id="partner-status" name="status">
                                    <option value="pending"><?php _e('Pending', 'env-voucher-rewards'); ?></option>
                                    <option value="active"><?php _e('Active', 'env-voucher-rewards'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'env-voucher-rewards'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sustainability-rating"><?php _e('Sustainability Rating', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="sustainability-rating" name="sustainability_rating" min="1" max="5" step="0.1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="api-enabled" name="api_enabled">
                                <?php _e('Enable API Integration', 'env-voucher-rewards'); ?>
                            </label>
                        </div>
                        
                        <div class="api-settings" style="display: none;">
                            <div class="form-group">
                                <label for="api-endpoint"><?php _e('API Endpoint', 'env-voucher-rewards'); ?></label>
                                <input type="url" id="api-endpoint" name="api_endpoint">
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="auto-sync" name="auto_sync">
                                    <?php _e('Enable Automatic Sync', 'env-voucher-rewards'); ?>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="evr-modal-footer">
                    <button type="button" class="button" id="cancel-partner"><?php _e('Cancel', 'env-voucher-rewards'); ?></button>
                    <button type="button" class="button button-primary" id="save-partner"><?php _e('Save Partner', 'env-voucher-rewards'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Discount Creation Modal -->
        <div id="discount-modal" class="evr-modal" style="display: none;">
            <div class="evr-modal-content">
                <div class="evr-modal-header">
                    <h2><?php _e('Create Partner Discount', 'env-voucher-rewards'); ?></h2>
                    <button type="button" class="evr-modal-close">&times;</button>
                </div>
                
                <div class="evr-modal-body">
                    <form id="discount-form">
                        <div class="form-group">
                            <label for="discount-partner"><?php _e('Partner *', 'env-voucher-rewards'); ?></label>
                            <select id="discount-partner" name="partner_id" required>
                                <option value=""><?php _e('Select Partner', 'env-voucher-rewards'); ?></option>
                                <?php foreach ($this->get_partners_list() as $partner) : ?>
                                <option value="<?php echo esc_attr($partner->partner_id); ?>"><?php echo esc_html($partner->partner_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount-name"><?php _e('Discount Name *', 'env-voucher-rewards'); ?></label>
                            <input type="text" id="discount-name" name="discount_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount-desc"><?php _e('Description', 'env-voucher-rewards'); ?></label>
                            <textarea id="discount-desc" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="discount-type"><?php _e('Discount Type *', 'env-voucher-rewards'); ?></label>
                                <select id="discount-type" name="discount_type" required>
                                    <option value="percentage"><?php _e('Percentage', 'env-voucher-rewards'); ?></option>
                                    <option value="fixed_amount"><?php _e('Fixed Amount', 'env-voucher-rewards'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount-value"><?php _e('Discount Value *', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="discount-value" name="discount_value" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="min-user-level"><?php _e('Minimum User Level', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="min-user-level" name="min_user_level" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label for="min-eco-score"><?php _e('Minimum Eco Score', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="min-eco-score" name="min_eco_score" min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="max-redemptions"><?php _e('Max Redemptions', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="max-redemptions" name="max_redemptions" min="1">
                                <small><?php _e('Leave empty for unlimited', 'env-voucher-rewards'); ?></small>
                            </div>
                            <div class="form-group">
                                <label for="valid-days"><?php _e('Valid for (days)', 'env-voucher-rewards'); ?></label>
                                <input type="number" id="valid-days" name="valid_days" min="1" value="30">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="terms-conditions"><?php _e('Terms & Conditions', 'env-voucher-rewards'); ?></label>
                            <textarea id="terms-conditions" name="terms_conditions" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                
                <div class="evr-modal-footer">
                    <button type="button" class="button" id="cancel-discount"><?php _e('Cancel', 'env-voucher-rewards'); ?></button>
                    <button type="button" class="button button-primary" id="save-discount"><?php _e('Create Discount', 'env-voucher-rewards'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get overview statistics
     */
    private function get_overview_stats() {
        global $wpdb;
        
        $stats = array(
            'total_partners' => 0,
            'active_partners' => 0,
            'active_discounts' => 0,
            'total_redemptions_value' => 0
        );
        
        // Total partners
        $stats['total_partners'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}partner_discounts
        ");
        
        // Active partners
        $stats['active_partners'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}partner_discounts
            WHERE partner_status = 'active'
        ");
        
        // Active discounts (this would need to be adjusted based on actual schema)
        $stats['active_discounts'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}partner_discounts
            WHERE partner_status = 'active'
        ");
        
        // Total redemptions value (this would need to be calculated from redemption transactions)
        $stats['total_redemptions_value'] = $wpdb->get_var("
            SELECT COALESCE(SUM(total_revenue_generated), 0) FROM {$wpdb->prefix}partner_discounts
        ");
        
        return $stats;
    }
    
    /**
     * Get partner types data for chart
     */
    private function get_partner_types_data() {
        global $wpdb;
        
        $data = $wpdb->get_results("
            SELECT partner_type, COUNT(*) as count
            FROM {$wpdb->prefix}partner_discounts
            GROUP BY partner_type
        ");
        
        $labels = array();
        $values = array();
        
        foreach ($data as $row) {
            $labels[] = ucfirst(str_replace('_', ' ', $row->partner_type));
            $values[] = intval($row->count);
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $values,
                    'backgroundColor' => array('#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#795548')
                )
            )
        );
    }
    
    /**
     * Get redemptions data for chart
     */
    private function get_redemptions_data() {
        // This would need to be implemented based on actual redemption tracking
        $labels = array();
        $values = array();
        
        // Generate last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('M j', strtotime("-$i days"));
            $labels[] = $date;
            $values[] = rand(10, 100); // Placeholder data
        }
        
        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Redemptions', 'env-voucher-rewards'),
                    'data' => $values,
                    'borderColor' => '#4CAF50',
                    'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                    'tension' => 0.4
                )
            )
        );
    }
    
    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        // This would fetch recent partner activities from database
        $activities = array(
            array(
                'type' => 'partner_registered',
                'message' => 'New partner "Green Market" registered',
                'time' => '2 hours ago'
            ),
            array(
                'type' => 'discount_created',
                'message' => 'Discount "20% Off Eco Products" created',
                'time' => '5 hours ago'
            ),
            array(
                'type' => 'sync_completed',
                'message' => 'Partner data sync completed for 5 partners',
                'time' => '1 day ago'
            )
        );
        
        foreach ($activities as $activity) :
        ?>
        <div class="evr-activity-item">
            <div class="activity-icon">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="activity-content">
                <div class="activity-message"><?php echo esc_html($activity['message']); ?></div>
                <div class="activity-time"><?php echo esc_html($activity['time']); ?></div>
            </div>
        </div>
        <?php
        endforeach;
    }
    
    /**
     * Get partners list
     */
    private function get_partners_list() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}partner_discounts
            ORDER BY created_at DESC
        ");
    }
    
    /**
     * Get partner discounts list
     */
    private function get_partner_discounts_list() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT pd.*, pd.partner_name
            FROM {$wpdb->prefix}partner_discounts pd
            ORDER BY pd.created_at DESC
        ");
    }
    
    /**
     * Get partner analytics data
     */
    private function get_partner_analytics() {
        return array(
            'total_eco_points' => 15420,
            'carbon_footprint_reduced' => 1250.5,
            'sustainability_score' => 4.2,
            'topPartnersData' => array(
                'labels' => array('Green Market', 'Eco Store', 'Bio Shop', 'Nature Foods'),
                'datasets' => array(
                    array(
                        'label' => 'Redemptions',
                        'data' => array(45, 38, 32, 28),
                        'backgroundColor' => '#4CAF50'
                    )
                )
            ),
            'discountPerformanceData' => array(
                'labels' => array('Active', 'Expired', 'Pending'),
                'datasets' => array(
                    array(
                        'data' => array(65, 25, 10),
                        'backgroundColor' => array('#4CAF50', '#FF5722', '#FFC107')
                    )
                )
            ),
            'redemptionTrendsData' => $this->get_redemptions_data()
        );
    }
    
    /**
     * AJAX: Create partner
     */
    public function ajax_create_partner() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $partner_data = array(
            'partner_name' => sanitize_text_field($_POST['partner_name']),
            'partner_description' => sanitize_textarea_field($_POST['partner_description']),
            'partner_type' => sanitize_text_field($_POST['partner_type']),
            'contact_email' => sanitize_email($_POST['contact_email']),
            'phone_number' => sanitize_text_field($_POST['phone_number']),
            'website_url' => esc_url_raw($_POST['website_url']),
            'address' => sanitize_textarea_field($_POST['address']),
            'status' => sanitize_text_field($_POST['status']),
            'sustainability_rating' => floatval($_POST['sustainability_rating']),
            'api_enabled' => isset($_POST['api_enabled']),
            'api_endpoint' => esc_url_raw($_POST['api_endpoint']),
            'auto_sync' => isset($_POST['auto_sync'])
        );
        
        $result = $this->partner_integration->register_partner($partner_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Partner created successfully', 'env-voucher-rewards'),
            'partner_id' => $result
        ));
    }
    
    /**
     * AJAX: Update partner
     */
    public function ajax_update_partner() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $partner_id = intval($_POST['partner_id']);
        
        // Implementation would update partner data
        wp_send_json_success(array(
            'message' => __('Partner updated successfully', 'env-voucher-rewards')
        ));
    }
    
    /**
     * AJAX: Delete partner
     */
    public function ajax_delete_partner() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $partner_id = intval($_POST['partner_id']);
        
        // Implementation would delete partner
        wp_send_json_success(array(
            'message' => __('Partner deleted successfully', 'env-voucher-rewards')
        ));
    }
    
    /**
     * AJAX: Create partner discount
     */
    public function ajax_create_partner_discount() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $discount_data = array(
            'discount_name' => sanitize_text_field($_POST['discount_name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'min_user_level' => intval($_POST['min_user_level']),
            'min_eco_score' => intval($_POST['min_eco_score']),
            'max_redemptions' => !empty($_POST['max_redemptions']) ? intval($_POST['max_redemptions']) : null,
            'valid_days' => intval($_POST['valid_days']),
            'terms_conditions' => sanitize_textarea_field($_POST['terms_conditions'])
        );
        
        $partner_id = intval($_POST['partner_id']);
        $result = $this->partner_integration->create_partner_discount($partner_id, $discount_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Discount created successfully', 'env-voucher-rewards'),
                'discount_id' => $result
            ));
        } else {
            wp_send_json_error(__('Failed to create discount', 'env-voucher-rewards'));
        }
    }
    
    /**
     * AJAX: Sync partner data
     */
    public function ajax_sync_partner_data() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $partner_id = isset($_POST['partner_id']) ? intval($_POST['partner_id']) : null;
        
        if ($partner_id) {
            // Sync single partner
            $this->partner_integration->sync_single_partner($partner_id);
        } else {
            // Sync all partners
            $this->partner_integration->sync_partner_data();
        }
        
        wp_send_json_success(array(
            'message' => __('Partner data synchronized successfully', 'env-voucher-rewards')
        ));
    }
    
    /**
     * AJAX: Get partner stats
     */
    public function ajax_get_partner_stats() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        $partner_id = intval($_POST['partner_id']);
        $stats = $this->partner_integration->get_partner_stats($partner_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Export partner data
     */
    public function ajax_export_partner_data() {
        check_ajax_referer('evr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        // Implementation would generate and return export data
        wp_send_json_success(array(
            'message' => __('Export generated successfully', 'env-voucher-rewards'),
            'download_url' => '#'
        ));
    }
}
