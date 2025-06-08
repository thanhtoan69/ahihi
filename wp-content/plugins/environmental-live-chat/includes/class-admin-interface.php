<?php
/**
 * Admin Interface Class
 * 
 * Handles the admin dashboard for support management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Admin_Interface {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_chat_settings', array($this, 'save_chat_settings'));
        add_action('wp_ajax_get_chat_statistics', array($this, 'get_chat_statistics'));
        add_action('wp_ajax_update_operator_status', array($this, 'update_operator_status'));
        add_action('wp_ajax_manage_support_ticket', array($this, 'manage_support_ticket'));
        add_action('wp_ajax_export_chat_data', array($this, 'export_chat_data'));
        add_action('wp_ajax_import_faq_data', array($this, 'import_faq_data'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // Real-time updates
        add_action('wp_ajax_get_live_stats', array($this, 'get_live_stats'));
        add_action('wp_ajax_get_pending_chats', array($this, 'get_pending_chats'));
        add_action('wp_ajax_get_operator_workload', array($this, 'get_operator_workload'));
    }
    
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('Environmental Support', 'environmental-live-chat'),
            __('Support Center', 'environmental-live-chat'),
            'manage_options',
            'environmental-support',
            array($this, 'dashboard_page'),
            'dashicons-format-chat',
            30
        );
        
        // Submenus
        add_submenu_page(
            'environmental-support',
            __('Dashboard', 'environmental-live-chat'),
            __('Dashboard', 'environmental-live-chat'),
            'manage_options',
            'environmental-support',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'environmental-support',
            __('Live Chat', 'environmental-live-chat'),
            __('Live Chat', 'environmental-live-chat'),
            'edit_posts',
            'environmental-live-chat',
            array($this, 'live_chat_page')
        );
        
        add_submenu_page(
            'environmental-support',
            __('Support Tickets', 'environmental-live-chat'),
            __('Support Tickets', 'environmental-live-chat'),
            'edit_posts',
            'environmental-tickets',
            array($this, 'tickets_page')
        );
        
        add_submenu_page(
            'environmental-support',
            __('FAQ Management', 'environmental-live-chat'),
            __('FAQ Management', 'environmental-live-chat'),
            'edit_posts',
            'environmental-faq',
            array($this, 'faq_page')
        );
        
        add_submenu_page(
            'environmental-support',
            __('Analytics', 'environmental-live-chat'),
            __('Analytics', 'environmental-live-chat'),
            'manage_options',
            'environmental-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'environmental-support',
            __('Settings', 'environmental-live-chat'),
            __('Settings', 'environmental-live-chat'),
            'manage_options',
            'environmental-support-settings',
            array($this, 'settings_page')
        );
        
        // Operators management
        add_submenu_page(
            'environmental-support',
            __('Operators', 'environmental-live-chat'),
            __('Operators', 'environmental-live-chat'),
            'manage_options',
            'environmental-operators',
            array($this, 'operators_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', array('jquery'), '1.13.4', true);
        
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css', array(), '1.13.4');
        wp_enqueue_style('environmental-admin-css', ENVIRONMENTAL_LIVE_CHAT_URL . 'assets/css/admin.css', array(), ENVIRONMENTAL_LIVE_CHAT_VERSION);
        wp_enqueue_script('environmental-admin-js', ENVIRONMENTAL_LIVE_CHAT_URL . 'assets/js/admin.js', array('jquery'), ENVIRONMENTAL_LIVE_CHAT_VERSION, true);
        
        wp_localize_script('environmental-admin-js', 'environmental_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'environmental-live-chat'),
                'save_success' => __('Settings saved successfully!', 'environmental-live-chat'),
                'error_occurred' => __('An error occurred. Please try again.', 'environmental-live-chat'),
                'loading' => __('Loading...', 'environmental-live-chat'),
                'no_data' => __('No data available', 'environmental-live-chat')
            )
        ));
    }
    
    public function dashboard_page() {
        $analytics = Environmental_Analytics::get_instance();
        $overview = $analytics->get_overview_metrics();
        $live_stats = $analytics->get_real_time_stats();
        
        ?>
        <div class="wrap environmental-dashboard">
            <h1><?php _e('Environmental Support Dashboard', 'environmental-live-chat'); ?></h1>
            
            <!-- Real-time Stats -->
            <div class="environmental-stats-grid">
                <div class="stat-card active-chats">
                    <div class="stat-icon">💬</div>
                    <div class="stat-content">
                        <h3><?php echo esc_html($live_stats['active_chats']); ?></h3>
                        <p><?php _e('Active Chats', 'environmental-live-chat'); ?></p>
                    </div>
                </div>
                
                <div class="stat-card online-operators">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo esc_html($live_stats['online_operators']); ?></h3>
                        <p><?php _e('Online Operators', 'environmental-live-chat'); ?></p>
                    </div>
                </div>
                
                <div class="stat-card pending-tickets">
                    <div class="stat-icon">🎫</div>
                    <div class="stat-content">
                        <h3><?php echo esc_html($live_stats['pending_tickets']); ?></h3>
                        <p><?php _e('Pending Tickets', 'environmental-live-chat'); ?></p>
                    </div>
                </div>
                
                <div class="stat-card avg-response-time">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <h3><?php echo esc_html($overview['avg_response_time']); ?>s</h3>
                        <p><?php _e('Avg Response Time', 'environmental-live-chat'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="environmental-charts-section">
                <div class="chart-container">
                    <h3><?php _e('Chat Activity (Last 7 Days)', 'environmental-live-chat'); ?></h3>
                    <canvas id="chatActivityChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Satisfaction Ratings', 'environmental-live-chat'); ?></h3>
                    <canvas id="satisfactionChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="environmental-recent-activity">
                <h3><?php _e('Recent Activity', 'environmental-live-chat'); ?></h3>
                <div id="recent-activity-list">
                    <?php $this->render_recent_activity(); ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="environmental-quick-actions">
                <h3><?php _e('Quick Actions', 'environmental-live-chat'); ?></h3>
                <div class="action-buttons">
                    <button class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=environmental-live-chat'); ?>'">
                        <?php _e('Open Live Chat', 'environmental-live-chat'); ?>
                    </button>
                    <button class="button" onclick="window.location.href='<?php echo admin_url('admin.php?page=environmental-tickets'); ?>'">
                        <?php _e('View Tickets', 'environmental-live-chat'); ?>
                    </button>
                    <button class="button" onclick="exportChatData()">
                        <?php _e('Export Data', 'environmental-live-chat'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Initialize dashboard
        jQuery(document).ready(function($) {
            initializeDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                refreshLiveStats();
            }, 30000);
        });
        
        function initializeDashboard() {
            loadChartData();
        }
        
        function loadChartData() {
            jQuery.post(ajaxurl, {
                action: 'get_chart_data',
                nonce: environmental_admin.nonce
            }, function(response) {
                if (response.success) {
                    renderCharts(response.data);
                }
            });
        }
        
        function refreshLiveStats() {
            jQuery.post(ajaxurl, {
                action: 'get_live_stats',
                nonce: environmental_admin.nonce
            }, function(response) {
                if (response.success) {
                    updateStatsCards(response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    public function live_chat_page() {
        ?>
        <div class="wrap environmental-live-chat-admin">
            <h1><?php _e('Live Chat Management', 'environmental-live-chat'); ?></h1>
            
            <div class="environmental-chat-interface">
                <!-- Operator Status Panel -->
                <div class="operator-status-panel">
                    <h3><?php _e('Operator Status', 'environmental-live-chat'); ?></h3>
                    <div class="status-controls">
                        <label>
                            <input type="radio" name="operator_status" value="online" id="status-online">
                            <span class="status-indicator online"></span>
                            <?php _e('Online', 'environmental-live-chat'); ?>
                        </label>
                        <label>
                            <input type="radio" name="operator_status" value="away" id="status-away">
                            <span class="status-indicator away"></span>
                            <?php _e('Away', 'environmental-live-chat'); ?>
                        </label>
                        <label>
                            <input type="radio" name="operator_status" value="offline" id="status-offline">
                            <span class="status-indicator offline"></span>
                            <?php _e('Offline', 'environmental-live-chat'); ?>
                        </label>
                    </div>
                </div>
                
                <!-- Active Chats List -->
                <div class="active-chats-panel">
                    <h3><?php _e('Active Chats', 'environmental-live-chat'); ?></h3>
                    <div id="active-chats-list">
                        <!-- Populated via AJAX -->
                    </div>
                </div>
                
                <!-- Chat Window -->
                <div class="chat-window-panel">
                    <div id="chat-messages-container">
                        <div class="no-chat-selected">
                            <?php _e('Select a chat to start responding', 'environmental-live-chat'); ?>
                        </div>
                    </div>
                    
                    <div class="chat-input-area" style="display: none;">
                        <div class="chat-tools">
                            <button type="button" id="insert-canned-response"><?php _e('Canned Responses', 'environmental-live-chat'); ?></button>
                            <button type="button" id="transfer-chat"><?php _e('Transfer', 'environmental-live-chat'); ?></button>
                            <button type="button" id="end-chat"><?php _e('End Chat', 'environmental-live-chat'); ?></button>
                        </div>
                        
                        <div class="message-input">
                            <textarea id="chat-message-input" placeholder="<?php _e('Type your message...', 'environmental-live-chat'); ?>"></textarea>
                            <button type="button" id="send-message"><?php _e('Send', 'environmental-live-chat'); ?></button>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Info Panel -->
                <div class="customer-info-panel">
                    <h3><?php _e('Customer Information', 'environmental-live-chat'); ?></h3>
                    <div id="customer-info-content">
                        <!-- Populated when chat is selected -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            initializeLiveChatInterface();
            
            // Poll for new chats every 5 seconds
            setInterval(function() {
                refreshActiveChatsList();
            }, 5000);
        });
        </script>
        <?php
    }
    
    public function tickets_page() {
        $tickets = Environmental_Support_Tickets::get_instance();
        $all_tickets = $tickets->get_tickets(array('limit' => 50));
        
        ?>
        <div class="wrap environmental-tickets-admin">
            <h1><?php _e('Support Tickets Management', 'environmental-live-chat'); ?></h1>
            
            <div class="tickets-filters">
                <select id="status-filter">
                    <option value=""><?php _e('All Status', 'environmental-live-chat'); ?></option>
                    <option value="open"><?php _e('Open', 'environmental-live-chat'); ?></option>
                    <option value="in-progress"><?php _e('In Progress', 'environmental-live-chat'); ?></option>
                    <option value="resolved"><?php _e('Resolved', 'environmental-live-chat'); ?></option>
                    <option value="closed"><?php _e('Closed', 'environmental-live-chat'); ?></option>
                </select>
                
                <select id="priority-filter">
                    <option value=""><?php _e('All Priorities', 'environmental-live-chat'); ?></option>
                    <option value="low"><?php _e('Low', 'environmental-live-chat'); ?></option>
                    <option value="medium"><?php _e('Medium', 'environmental-live-chat'); ?></option>
                    <option value="high"><?php _e('High', 'environmental-live-chat'); ?></option>
                    <option value="urgent"><?php _e('Urgent', 'environmental-live-chat'); ?></option>
                </select>
                
                <input type="text" id="search-tickets" placeholder="<?php _e('Search tickets...', 'environmental-live-chat'); ?>">
                <button type="button" id="filter-tickets" class="button"><?php _e('Filter', 'environmental-live-chat'); ?></button>
            </div>
            
            <table id="tickets-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Ticket ID', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Subject', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Customer', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Status', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Priority', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Agent', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Created', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Actions', 'environmental-live-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_tickets as $ticket): ?>
                    <tr data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                        <td><strong>#<?php echo esc_html($ticket['ticket_number']); ?></strong></td>
                        <td>
                            <a href="#" class="ticket-subject" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                                <?php echo esc_html($ticket['subject']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($ticket['customer_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($ticket['status']); ?>">
                                <?php echo esc_html(ucfirst($ticket['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="priority-badge priority-<?php echo esc_attr($ticket['priority']); ?>">
                                <?php echo esc_html(ucfirst($ticket['priority'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($ticket['agent_name'] ?: __('Unassigned', 'environmental-live-chat')); ?></td>
                        <td><?php echo esc_html(date('M j, Y H:i', strtotime($ticket['created_at']))); ?></td>
                        <td>
                            <button class="button button-small view-ticket" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                                <?php _e('View', 'environmental-live-chat'); ?>
                            </button>
                            <button class="button button-small assign-ticket" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                                <?php _e('Assign', 'environmental-live-chat'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Ticket Details Modal -->
        <div id="ticket-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-ticket-subject"></h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body" id="ticket-details-content">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#tickets-table').DataTable({
                order: [[6, 'desc']],
                pageLength: 25,
                responsive: true
            });
            
            initializeTicketManagement();
        });
        </script>
        <?php
    }
    
    public function faq_page() {
        ?>
        <div class="wrap environmental-faq-admin">
            <h1><?php _e('FAQ Management', 'environmental-live-chat'); ?></h1>
            
            <div class="faq-actions">
                <button type="button" id="add-new-faq" class="button button-primary">
                    <?php _e('Add New FAQ', 'environmental-live-chat'); ?>
                </button>
                <button type="button" id="import-faq" class="button">
                    <?php _e('Import FAQ', 'environmental-live-chat'); ?>
                </button>
                <button type="button" id="export-faq" class="button">
                    <?php _e('Export FAQ', 'environmental-live-chat'); ?>
                </button>
            </div>
            
            <div class="faq-categories">
                <h3><?php _e('Categories', 'environmental-live-chat'); ?></h3>
                <div id="faq-categories-list">
                    <!-- Populated via AJAX -->
                </div>
                <button type="button" id="add-category" class="button">
                    <?php _e('Add Category', 'environmental-live-chat'); ?>
                </button>
            </div>
            
            <div class="faq-list">
                <h3><?php _e('FAQ Items', 'environmental-live-chat'); ?></h3>
                <table id="faq-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Question', 'environmental-live-chat'); ?></th>
                            <th><?php _e('Category', 'environmental-live-chat'); ?></th>
                            <th><?php _e('Views', 'environmental-live-chat'); ?></th>
                            <th><?php _e('Helpful Rating', 'environmental-live-chat'); ?></th>
                            <th><?php _e('Last Updated', 'environmental-live-chat'); ?></th>
                            <th><?php _e('Actions', 'environmental-live-chat'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="faq-table-body">
                        <!-- Populated via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- FAQ Edit Modal -->
        <div id="faq-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="faq-modal-title"><?php _e('Edit FAQ', 'environmental-live-chat'); ?></h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="faq-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="faq-question"><?php _e('Question', 'environmental-live-chat'); ?></label></th>
                                <td><input type="text" id="faq-question" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="faq-answer"><?php _e('Answer', 'environmental-live-chat'); ?></label></th>
                                <td><textarea id="faq-answer" rows="6" class="large-text" required></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="faq-category"><?php _e('Category', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <select id="faq-category" required>
                                        <option value=""><?php _e('Select Category', 'environmental-live-chat'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="faq-tags"><?php _e('Tags', 'environmental-live-chat'); ?></label></th>
                                <td><input type="text" id="faq-tags" class="regular-text" placeholder="<?php _e('Comma-separated tags', 'environmental-live-chat'); ?>"></td>
                            </tr>
                        </table>
                        
                        <div class="modal-actions">
                            <button type="submit" class="button button-primary"><?php _e('Save FAQ', 'environmental-live-chat'); ?></button>
                            <button type="button" class="button cancel-modal"><?php _e('Cancel', 'environmental-live-chat'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            initializeFAQManagement();
            loadFAQData();
        });
        </script>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap environmental-analytics-admin">
            <h1><?php _e('Support Analytics', 'environmental-live-chat'); ?></h1>
            
            <div class="analytics-filters">
                <label><?php _e('Date Range:', 'environmental-live-chat'); ?></label>
                <select id="date-range">
                    <option value="7"><?php _e('Last 7 days', 'environmental-live-chat'); ?></option>
                    <option value="30"><?php _e('Last 30 days', 'environmental-live-chat'); ?></option>
                    <option value="90"><?php _e('Last 3 months', 'environmental-live-chat'); ?></option>
                    <option value="365"><?php _e('Last year', 'environmental-live-chat'); ?></option>
                </select>
                <button type="button" id="refresh-analytics" class="button"><?php _e('Refresh', 'environmental-live-chat'); ?></button>
                <button type="button" id="export-analytics" class="button"><?php _e('Export Report', 'environmental-live-chat'); ?></button>
            </div>
            
            <div class="analytics-overview">
                <div class="analytics-card">
                    <h3><?php _e('Total Chats', 'environmental-live-chat'); ?></h3>
                    <div class="metric-value" id="total-chats">-</div>
                    <div class="metric-change" id="chats-change"></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Total Tickets', 'environmental-live-chat'); ?></h3>
                    <div class="metric-value" id="total-tickets">-</div>
                    <div class="metric-change" id="tickets-change"></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Avg Response Time', 'environmental-live-chat'); ?></h3>
                    <div class="metric-value" id="avg-response-time">-</div>
                    <div class="metric-change" id="response-time-change"></div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Satisfaction Rate', 'environmental-live-chat'); ?></h3>
                    <div class="metric-value" id="satisfaction-rate">-</div>
                    <div class="metric-change" id="satisfaction-change"></div>
                </div>
            </div>
            
            <div class="analytics-charts">
                <div class="chart-section">
                    <h3><?php _e('Chat Volume Over Time', 'environmental-live-chat'); ?></h3>
                    <canvas id="chatVolumeChart"></canvas>
                </div>
                
                <div class="chart-section">
                    <h3><?php _e('Response Time Trends', 'environmental-live-chat'); ?></h3>
                    <canvas id="responseTimeChart"></canvas>
                </div>
                
                <div class="chart-section">
                    <h3><?php _e('Agent Performance', 'environmental-live-chat'); ?></h3>
                    <canvas id="agentPerformanceChart"></canvas>
                </div>
                
                <div class="chart-section">
                    <h3><?php _e('Customer Satisfaction', 'environmental-live-chat'); ?></h3>
                    <canvas id="satisfactionTrendChart"></canvas>
                </div>
            </div>
            
            <div class="analytics-tables">
                <div class="table-section">
                    <h3><?php _e('Top FAQ Items', 'environmental-live-chat'); ?></h3>
                    <table id="top-faq-table" class="wp-list-table widefat fixed">
                        <thead>
                            <tr>
                                <th><?php _e('Question', 'environmental-live-chat'); ?></th>
                                <th><?php _e('Views', 'environmental-live-chat'); ?></th>
                                <th><?php _e('Helpful Rating', 'environmental-live-chat'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="top-faq-body">
                        </tbody>
                    </table>
                </div>
                
                <div class="table-section">
                    <h3><?php _e('Agent Performance', 'environmental-live-chat'); ?></h3>
                    <table id="agent-performance-table" class="wp-list-table widefat fixed">
                        <thead>
                            <tr>
                                <th><?php _e('Agent', 'environmental-live-chat'); ?></th>
                                <th><?php _e('Chats Handled', 'environmental-live-chat'); ?></th>
                                <th><?php _e('Avg Response Time', 'environmental-live-chat'); ?></th>
                                <th><?php _e('Satisfaction Rate', 'environmental-live-chat'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="agent-performance-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            initializeAnalytics();
            loadAnalyticsData();
        });
        </script>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            $this->save_settings();
        }
        
        $options = get_option('environmental_live_chat_options', array());
        ?>
        <div class="wrap environmental-settings-admin">
            <h1><?php _e('Support Settings', 'environmental-live-chat'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('environmental_settings_nonce', 'environmental_settings_nonce'); ?>
                
                <div class="settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'environmental-live-chat'); ?></a>
                        <a href="#chat" class="nav-tab"><?php _e('Live Chat', 'environmental-live-chat'); ?></a>
                        <a href="#tickets" class="nav-tab"><?php _e('Support Tickets', 'environmental-live-chat'); ?></a>
                        <a href="#chatbot" class="nav-tab"><?php _e('Chatbot', 'environmental-live-chat'); ?></a>
                        <a href="#notifications" class="nav-tab"><?php _e('Notifications', 'environmental-live-chat'); ?></a>
                    </nav>
                    
                    <!-- General Settings -->
                    <div id="general" class="tab-content">
                        <h3><?php _e('General Settings', 'environmental-live-chat'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="enable_support"><?php _e('Enable Support System', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_support" name="enable_support" value="1" <?php checked($options['enable_support'] ?? true); ?>>
                                    <p class="description"><?php _e('Enable or disable the entire support system', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="support_email"><?php _e('Support Email', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="email" id="support_email" name="support_email" class="regular-text" value="<?php echo esc_attr($options['support_email'] ?? get_option('admin_email')); ?>">
                                    <p class="description"><?php _e('Email address for support notifications', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="business_hours"><?php _e('Business Hours', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <textarea id="business_hours" name="business_hours" rows="5" class="large-text"><?php echo esc_textarea($options['business_hours'] ?? 'Monday-Friday: 9:00 AM - 5:00 PM'); ?></textarea>
                                    <p class="description"><?php _e('Display business hours to customers', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Live Chat Settings -->
                    <div id="chat" class="tab-content" style="display: none;">
                        <h3><?php _e('Live Chat Settings', 'environmental-live-chat'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="enable_chat"><?php _e('Enable Live Chat', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_chat" name="enable_chat" value="1" <?php checked($options['enable_chat'] ?? true); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="chat_widget_position"><?php _e('Widget Position', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <select id="chat_widget_position" name="chat_widget_position">
                                        <option value="bottom-right" <?php selected($options['chat_widget_position'] ?? 'bottom-right', 'bottom-right'); ?>><?php _e('Bottom Right', 'environmental-live-chat'); ?></option>
                                        <option value="bottom-left" <?php selected($options['chat_widget_position'] ?? 'bottom-right', 'bottom-left'); ?>><?php _e('Bottom Left', 'environmental-live-chat'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="chat_welcome_message"><?php _e('Welcome Message', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <textarea id="chat_welcome_message" name="chat_welcome_message" rows="3" class="large-text"><?php echo esc_textarea($options['chat_welcome_message'] ?? 'Hello! How can we help you with your environmental questions today?'); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="max_file_size"><?php _e('Max File Upload Size (MB)', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="number" id="max_file_size" name="max_file_size" min="1" max="50" value="<?php echo esc_attr($options['max_file_size'] ?? 10); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Support Tickets Settings -->
                    <div id="tickets" class="tab-content" style="display: none;">
                        <h3><?php _e('Support Tickets Settings', 'environmental-live-chat'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="enable_tickets"><?php _e('Enable Support Tickets', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_tickets" name="enable_tickets" value="1" <?php checked($options['enable_tickets'] ?? true); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="auto_assign_tickets"><?php _e('Auto-assign Tickets', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="auto_assign_tickets" name="auto_assign_tickets" value="1" <?php checked($options['auto_assign_tickets'] ?? false); ?>>
                                    <p class="description"><?php _e('Automatically assign tickets to available agents', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ticket_escalation_hours"><?php _e('Escalation Time (hours)', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="number" id="ticket_escalation_hours" name="ticket_escalation_hours" min="1" max="168" value="<?php echo esc_attr($options['ticket_escalation_hours'] ?? 24); ?>">
                                    <p class="description"><?php _e('Hours before a ticket is escalated', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Chatbot Settings -->
                    <div id="chatbot" class="tab-content" style="display: none;">
                        <h3><?php _e('Chatbot Settings', 'environmental-live-chat'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="enable_chatbot"><?php _e('Enable Chatbot', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="enable_chatbot" name="enable_chatbot" value="1" <?php checked($options['enable_chatbot'] ?? true); ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="chatbot_confidence_threshold"><?php _e('Confidence Threshold', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="number" id="chatbot_confidence_threshold" name="chatbot_confidence_threshold" min="0" max="100" step="5" value="<?php echo esc_attr($options['chatbot_confidence_threshold'] ?? 70); ?>">%
                                    <p class="description"><?php _e('Minimum confidence level for chatbot responses', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="escalate_to_human"><?php _e('Auto-escalate Complex Queries', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="escalate_to_human" name="escalate_to_human" value="1" <?php checked($options['escalate_to_human'] ?? true); ?>>
                                    <p class="description"><?php _e('Automatically escalate complex queries to human operators', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div id="notifications" class="tab-content" style="display: none;">
                        <h3><?php _e('Notification Settings', 'environmental-live-chat'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="email_notifications"><?php _e('Email Notifications', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <fieldset>
                                        <label><input type="checkbox" name="notify_new_chat" value="1" <?php checked($options['notify_new_chat'] ?? true); ?>> <?php _e('New chat started', 'environmental-live-chat'); ?></label><br>
                                        <label><input type="checkbox" name="notify_new_ticket" value="1" <?php checked($options['notify_new_ticket'] ?? true); ?>> <?php _e('New ticket created', 'environmental-live-chat'); ?></label><br>
                                        <label><input type="checkbox" name="notify_ticket_reply" value="1" <?php checked($options['notify_ticket_reply'] ?? true); ?>> <?php _e('Ticket reply received', 'environmental-live-chat'); ?></label><br>
                                        <label><input type="checkbox" name="notify_chat_rating" value="1" <?php checked($options['notify_chat_rating'] ?? false); ?>> <?php _e('Chat rating received', 'environmental-live-chat'); ?></label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="daily_reports"><?php _e('Daily Reports', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="daily_reports" name="daily_reports" value="1" <?php checked($options['daily_reports'] ?? false); ?>>
                                    <p class="description"><?php _e('Send daily summary reports via email', 'environmental-live-chat'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="<?php _e('Save Settings', 'environmental-live-chat'); ?>">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Settings tabs functionality
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab-active').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
        });
        </script>
        <?php
    }
    
    public function operators_page() {
        global $wpdb;
        
        // Get all operators
        $operators = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}environmental_chat_operators 
            ORDER BY created_at DESC
        ");
        
        ?>
        <div class="wrap environmental-operators-admin">
            <h1><?php _e('Chat Operators Management', 'environmental-live-chat'); ?></h1>
            
            <div class="operators-actions">
                <button type="button" id="add-operator" class="button button-primary">
                    <?php _e('Add New Operator', 'environmental-live-chat'); ?>
                </button>
            </div>
            
            <table id="operators-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Email', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Department', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Status', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Max Concurrent Chats', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Active Chats', 'environmental-live-chat'); ?></th>
                        <th><?php _e('Actions', 'environmental-live-chat'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operators as $operator): ?>
                    <tr data-operator-id="<?php echo esc_attr($operator->id); ?>">
                        <td><?php echo esc_html($operator->name); ?></td>
                        <td><?php echo esc_html($operator->email); ?></td>
                        <td><?php echo esc_html($operator->department); ?></td>
                        <td>
                            <span class="status-indicator <?php echo esc_attr($operator->status); ?>"></span>
                            <?php echo esc_html(ucfirst($operator->status)); ?>
                        </td>
                        <td><?php echo esc_html($operator->max_concurrent_chats); ?></td>
                        <td>
                            <span class="active-chats-count" data-operator-id="<?php echo esc_attr($operator->id); ?>">
                                <?php echo esc_html($operator->current_chats); ?>
                            </span>
                        </td>
                        <td>
                            <button class="button button-small edit-operator" data-operator-id="<?php echo esc_attr($operator->id); ?>">
                                <?php _e('Edit', 'environmental-live-chat'); ?>
                            </button>
                            <button class="button button-small delete-operator" data-operator-id="<?php echo esc_attr($operator->id); ?>">
                                <?php _e('Delete', 'environmental-live-chat'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Operator Modal -->
        <div id="operator-modal" class="environmental-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="operator-modal-title"><?php _e('Add Operator', 'environmental-live-chat'); ?></h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="operator-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="operator-name"><?php _e('Name', 'environmental-live-chat'); ?></label></th>
                                <td><input type="text" id="operator-name" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="operator-email"><?php _e('Email', 'environmental-live-chat'); ?></label></th>
                                <td><input type="email" id="operator-email" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="operator-department"><?php _e('Department', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <select id="operator-department" required>
                                        <option value=""><?php _e('Select Department', 'environmental-live-chat'); ?></option>
                                        <option value="general"><?php _e('General Support', 'environmental-live-chat'); ?></option>
                                        <option value="technical"><?php _e('Technical Support', 'environmental-live-chat'); ?></option>
                                        <option value="environmental"><?php _e('Environmental Consulting', 'environmental-live-chat'); ?></option>
                                        <option value="billing"><?php _e('Billing', 'environmental-live-chat'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="operator-max-chats"><?php _e('Max Concurrent Chats', 'environmental-live-chat'); ?></label></th>
                                <td><input type="number" id="operator-max-chats" min="1" max="10" value="3" required></td>
                            </tr>
                            <tr>
                                <th><label for="operator-status"><?php _e('Status', 'environmental-live-chat'); ?></label></th>
                                <td>
                                    <select id="operator-status">
                                        <option value="offline"><?php _e('Offline', 'environmental-live-chat'); ?></option>
                                        <option value="online"><?php _e('Online', 'environmental-live-chat'); ?></option>
                                        <option value="away"><?php _e('Away', 'environmental-live-chat'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="modal-actions">
                            <button type="submit" class="button button-primary"><?php _e('Save Operator', 'environmental-live-chat'); ?></button>
                            <button type="button" class="button cancel-modal"><?php _e('Cancel', 'environmental-live-chat'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            initializeOperatorManagement();
        });
        </script>
        <?php
    }
    
    private function render_recent_activity() {
        global $wpdb;
        
        // Get recent activities from different systems
        $activities = array();
        
        // Recent chats
        $recent_chats = $wpdb->get_results("
            SELECT 'chat' as type, customer_name as title, created_at, status
            FROM {$wpdb->prefix}environmental_chat_sessions 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        // Recent tickets
        $recent_tickets = $wpdb->get_results("
            SELECT 'ticket' as type, subject as title, created_at, status
            FROM {$wpdb->prefix}environmental_support_tickets 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        $activities = array_merge($recent_chats, $recent_tickets);
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        foreach (array_slice($activities, 0, 10) as $activity) {
            $icon = $activity->type === 'chat' ? '💬' : '🎫';
            $time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ago';
            
            echo '<div class="activity-item">';
            echo '<span class="activity-icon">' . $icon . '</span>';
            echo '<div class="activity-content">';
            echo '<strong>' . esc_html($activity->title) . '</strong>';
            echo '<span class="activity-meta">' . esc_html($time_ago) . ' - ' . esc_html(ucfirst($activity->status)) . '</span>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'environmental_support_widget',
            __('Environmental Support Overview', 'environmental-live-chat'),
            array($this, 'dashboard_widget_content')
        );
    }
    
    public function dashboard_widget_content() {
        $analytics = Environmental_Analytics::get_instance();
        $stats = $analytics->get_real_time_stats();
        
        ?>
        <div class="environmental-dashboard-widget">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($stats['active_chats']); ?></span>
                    <span class="stat-label"><?php _e('Active Chats', 'environmental-live-chat'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($stats['pending_tickets']); ?></span>
                    <span class="stat-label"><?php _e('Pending Tickets', 'environmental-live-chat'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($stats['online_operators']); ?></span>
                    <span class="stat-label"><?php _e('Online Operators', 'environmental-live-chat'); ?></span>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=environmental-support'); ?>" class="button button-primary">
                    <?php _e('View Dashboard', 'environmental-live-chat'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=environmental-live-chat'); ?>" class="button">
                    <?php _e('Live Chat', 'environmental-live-chat'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    // AJAX Handlers
    public function get_live_stats() {
        check_ajax_referer('environmental_admin_nonce', 'nonce');
        
        $analytics = Environmental_Analytics::get_instance();
        $stats = $analytics->get_real_time_stats();
        
        wp_send_json_success($stats);
    }
    
    public function save_chat_settings() {
        check_ajax_referer('environmental_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-live-chat'));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        // Sanitize settings
        $clean_settings = array();
        foreach ($settings as $key => $value) {
            $clean_settings[sanitize_key($key)] = sanitize_text_field($value);
        }
        
        update_option('environmental_live_chat_options', $clean_settings);
        
        wp_send_json_success(__('Settings saved successfully', 'environmental-live-chat'));
    }
    
    public function update_operator_status() {
        check_ajax_referer('environmental_admin_nonce', 'nonce');
        
        $operator_id = intval($_POST['operator_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$operator_id || !in_array($status, array('online', 'away', 'offline'))) {
            wp_send_json_error(__('Invalid parameters', 'environmental-live-chat'));
        }
        
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'environmental_chat_operators',
            array(
                'status' => $status,
                'last_seen' => current_time('mysql')
            ),
            array('id' => $operator_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            wp_send_json_success(__('Operator status updated', 'environmental-live-chat'));
        } else {
            wp_send_json_error(__('Failed to update status', 'environmental-live-chat'));
        }
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['environmental_settings_nonce'], 'environmental_settings_nonce')) {
            wp_die(__('Security check failed', 'environmental-live-chat'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-live-chat'));
        }
        
        $settings = array();
        
        // General settings
        $settings['enable_support'] = isset($_POST['enable_support']);
        $settings['support_email'] = sanitize_email($_POST['support_email'] ?? '');
        $settings['business_hours'] = sanitize_textarea_field($_POST['business_hours'] ?? '');
        
        // Chat settings
        $settings['enable_chat'] = isset($_POST['enable_chat']);
        $settings['chat_widget_position'] = sanitize_text_field($_POST['chat_widget_position'] ?? 'bottom-right');
        $settings['chat_welcome_message'] = sanitize_textarea_field($_POST['chat_welcome_message'] ?? '');
        $settings['max_file_size'] = intval($_POST['max_file_size'] ?? 10);
        
        // Ticket settings
        $settings['enable_tickets'] = isset($_POST['enable_tickets']);
        $settings['auto_assign_tickets'] = isset($_POST['auto_assign_tickets']);
        $settings['ticket_escalation_hours'] = intval($_POST['ticket_escalation_hours'] ?? 24);
        
        // Chatbot settings
        $settings['enable_chatbot'] = isset($_POST['enable_chatbot']);
        $settings['chatbot_confidence_threshold'] = intval($_POST['chatbot_confidence_threshold'] ?? 70);
        $settings['escalate_to_human'] = isset($_POST['escalate_to_human']);
        
        // Notification settings
        $settings['notify_new_chat'] = isset($_POST['notify_new_chat']);
        $settings['notify_new_ticket'] = isset($_POST['notify_new_ticket']);
        $settings['notify_ticket_reply'] = isset($_POST['notify_ticket_reply']);
        $settings['notify_chat_rating'] = isset($_POST['notify_chat_rating']);
        $settings['daily_reports'] = isset($_POST['daily_reports']);
        
        update_option('environmental_live_chat_options', $settings);
        
        add_settings_error(
            'environmental_settings',
            'settings_saved',
            __('Settings saved successfully!', 'environmental-live-chat'),
            'updated'
        );
    }
}
