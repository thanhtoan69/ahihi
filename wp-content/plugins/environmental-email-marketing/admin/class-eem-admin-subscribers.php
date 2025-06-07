<?php
/**
 * Environmental Email Marketing - Subscribers Admin Interface
 *
 * Admin interface for managing subscribers
 *
 * @package Environmental_Email_Marketing
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Admin_Subscribers {

    /**
     * Subscriber manager instance
     *
     * @var EEM_Subscriber_Manager
     */
    private $subscriber_manager;

    /**
     * Items per page
     *
     * @var int
     */
    private $per_page = 20;

    /**
     * Constructor
     */
    public function __construct() {
        $this->subscriber_manager = new EEM_Subscriber_Manager();
        
        add_action('wp_ajax_eem_add_subscriber', array($this, 'ajax_add_subscriber'));
        add_action('wp_ajax_eem_update_subscriber', array($this, 'ajax_update_subscriber'));
        add_action('wp_ajax_eem_delete_subscriber', array($this, 'ajax_delete_subscriber'));
        add_action('wp_ajax_eem_bulk_action_subscribers', array($this, 'ajax_bulk_action'));
        add_action('wp_ajax_eem_export_subscribers', array($this, 'ajax_export_subscribers'));
        add_action('wp_ajax_eem_import_subscribers', array($this, 'ajax_import_subscribers'));
        add_action('wp_ajax_eem_sync_subscriber', array($this, 'ajax_sync_subscriber'));
        add_action('wp_ajax_eem_get_subscriber_details', array($this, 'ajax_get_subscriber_details'));
    }

    /**
     * Render subscribers page
     */
    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $subscriber_id = isset($_GET['subscriber_id']) ? intval($_GET['subscriber_id']) : 0;

        switch ($action) {
            case 'add':
                $this->render_add_form();
                break;
            case 'edit':
                $this->render_edit_form($subscriber_id);
                break;
            case 'view':
                $this->render_subscriber_details($subscriber_id);
                break;
            case 'import':
                $this->render_import_form();
                break;
            default:
                $this->render_subscribers_list();
                break;
        }
    }

    /**
     * Render subscribers list
     */
    private function render_subscribers_list() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $list_id = isset($_GET['list_id']) ? intval($_GET['list_id']) : 0;

        $args = array(
            'per_page' => $this->per_page,
            'page' => $current_page,
            'search' => $search,
            'status' => $status,
            'list_id' => $list_id
        );

        $subscribers = $this->subscriber_manager->get_subscribers($args);
        $total_items = $this->subscriber_manager->get_subscribers_count($args);
        $total_pages = ceil($total_items / $this->per_page);

        // Get available lists
        $lists = $this->subscriber_manager->get_lists();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Subscribers', 'environmental-email-marketing'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=eem-subscribers&action=add'); ?>" class="page-title-action">
                <?php _e('Add New', 'environmental-email-marketing'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=eem-subscribers&action=import'); ?>" class="page-title-action">
                <?php _e('Import', 'environmental-email-marketing'); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="eem-subscribers">
                        
                        <select name="status">
                            <option value=""><?php _e('All Statuses', 'environmental-email-marketing'); ?></option>
                            <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'environmental-email-marketing'); ?></option>
                            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'environmental-email-marketing'); ?></option>
                            <option value="unsubscribed" <?php selected($status, 'unsubscribed'); ?>><?php _e('Unsubscribed', 'environmental-email-marketing'); ?></option>
                            <option value="bounced" <?php selected($status, 'bounced'); ?>><?php _e('Bounced', 'environmental-email-marketing'); ?></option>
                        </select>

                        <select name="list_id">
                            <option value=""><?php _e('All Lists', 'environmental-email-marketing'); ?></option>
                            <?php foreach ($lists as $list): ?>
                                <option value="<?php echo $list->id; ?>" <?php selected($list_id, $list->id); ?>>
                                    <?php echo esc_html($list->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="submit" class="button" value="<?php _e('Filter', 'environmental-email-marketing'); ?>">
                    </form>
                </div>

                <div class="alignright actions">
                    <button type="button" id="eem-export-subscribers" class="button">
                        <?php _e('Export', 'environmental-email-marketing'); ?>
                    </button>
                </div>

                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>

            <!-- Search box -->
            <div class="search-box">
                <form method="get">
                    <input type="hidden" name="page" value="eem-subscribers">
                    <?php if ($status): ?><input type="hidden" name="status" value="<?php echo esc_attr($status); ?>"><?php endif; ?>
                    <?php if ($list_id): ?><input type="hidden" name="list_id" value="<?php echo esc_attr($list_id); ?>"><?php endif; ?>
                    
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search subscribers...', 'environmental-email-marketing'); ?>">
                    <input type="submit" class="button" value="<?php _e('Search', 'environmental-email-marketing'); ?>">
                </form>
            </div>

            <!-- Bulk actions -->
            <form method="post" id="eem-subscribers-form">
                <?php wp_nonce_field('eem_bulk_action', 'eem_bulk_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value=""><?php _e('Bulk Actions', 'environmental-email-marketing'); ?></option>
                            <option value="activate"><?php _e('Activate', 'environmental-email-marketing'); ?></option>
                            <option value="deactivate"><?php _e('Deactivate', 'environmental-email-marketing'); ?></option>
                            <option value="delete"><?php _e('Delete', 'environmental-email-marketing'); ?></option>
                            <option value="add_to_list"><?php _e('Add to List', 'environmental-email-marketing'); ?></option>
                            <option value="remove_from_list"><?php _e('Remove from List', 'environmental-email-marketing'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Apply', 'environmental-email-marketing'); ?>">
                    </div>
                </div>

                <!-- Subscribers table -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th class="manage-column"><?php _e('Email', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Name', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Status', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Lists', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Environmental Score', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Subscribed', 'environmental-email-marketing'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'environmental-email-marketing'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="8" class="no-items"><?php _e('No subscribers found.', 'environmental-email-marketing'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $subscriber): ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="subscriber_ids[]" value="<?php echo $subscriber->id; ?>">
                                    </th>
                                    <td>
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=eem-subscribers&action=view&subscriber_id=' . $subscriber->id); ?>">
                                                <?php echo esc_html($subscriber->email); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html($subscriber->first_name . ' ' . $subscriber->last_name); ?></td>
                                    <td>
                                        <span class="eem-status-<?php echo $subscriber->status; ?>">
                                            <?php echo ucfirst($subscriber->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $subscriber_lists = $this->subscriber_manager->get_subscriber_lists($subscriber->id);
                                        echo count($subscriber_lists) . ' ' . __('lists', 'environmental-email-marketing');
                                        ?>
                                    </td>
                                    <td>
                                        <div class="eem-env-score">
                                            <span class="score"><?php echo number_format($subscriber->environmental_score, 1); ?></span>
                                            <div class="score-bar">
                                                <div class="score-fill" style="width: <?php echo min(100, $subscriber->environmental_score); ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo mysql2date('M j, Y', $subscriber->created_at); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=eem-subscribers&action=edit&subscriber_id=' . $subscriber->id); ?>" class="button button-small">
                                            <?php _e('Edit', 'environmental-email-marketing'); ?>
                                        </a>
                                        <button type="button" class="button button-small eem-sync-subscriber" data-subscriber-id="<?php echo $subscriber->id; ?>">
                                            <?php _e('Sync', 'environmental-email-marketing'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <!-- Pagination -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php echo paginate_links($pagination_args); ?>
                </div>
            </div>
        </div>

        <style>
        .eem-status-active { color: #46b450; font-weight: bold; }
        .eem-status-pending { color: #ffb900; font-weight: bold; }
        .eem-status-unsubscribed { color: #dc3232; font-weight: bold; }
        .eem-status-bounced { color: #826eb4; font-weight: bold; }
        
        .eem-env-score {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .eem-env-score .score {
            font-weight: bold;
            color: #46b450;
        }
        
        .score-bar {
            width: 50px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #46b450, #00a32a);
            transition: width 0.3s ease;
        }
        </style>
        <?php
    }

    /**
     * Render add subscriber form
     */
    private function render_add_form() {
        $lists = $this->subscriber_manager->get_lists();
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Subscriber', 'environmental-email-marketing'); ?></h1>
            
            <form method="post" id="eem-add-subscriber-form" class="eem-form">
                <?php wp_nonce_field('eem_add_subscriber', 'eem_subscriber_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email Address', 'environmental-email-marketing'); ?> *</label>
                        </th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="first_name"><?php _e('First Name', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="first_name" id="first_name" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="last_name"><?php _e('Last Name', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="last_name" id="last_name" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <select name="status" id="status">
                                <option value="active"><?php _e('Active', 'environmental-email-marketing'); ?></option>
                                <option value="pending"><?php _e('Pending', 'environmental-email-marketing'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lists"><?php _e('Subscribe to Lists', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($lists as $list): ?>
                                <label>
                                    <input type="checkbox" name="lists[]" value="<?php echo $list->id; ?>">
                                    <?php echo esc_html($list->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Environmental Preferences', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="preferences[]" value="climate_change">
                                <?php _e('Climate Change', 'environmental-email-marketing'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="preferences[]" value="renewable_energy">
                                <?php _e('Renewable Energy', 'environmental-email-marketing'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="preferences[]" value="conservation">
                                <?php _e('Conservation', 'environmental-email-marketing'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="preferences[]" value="sustainable_living">
                                <?php _e('Sustainable Living', 'environmental-email-marketing'); ?>
                            </label><br>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Add Subscriber', 'environmental-email-marketing'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=eem-subscribers'); ?>" class="button">
                        <?php _e('Cancel', 'environmental-email-marketing'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render edit subscriber form
     */
    private function render_edit_form($subscriber_id) {
        $subscriber = $this->subscriber_manager->get_subscriber($subscriber_id);
        if (!$subscriber) {
            wp_die(__('Subscriber not found.', 'environmental-email-marketing'));
        }

        $lists = $this->subscriber_manager->get_lists();
        $subscriber_lists = $this->subscriber_manager->get_subscriber_lists($subscriber_id);
        $subscriber_list_ids = wp_list_pluck($subscriber_lists, 'id');

        ?>
        <div class="wrap">
            <h1><?php _e('Edit Subscriber', 'environmental-email-marketing'); ?></h1>
            
            <form method="post" id="eem-edit-subscriber-form" class="eem-form">
                <?php wp_nonce_field('eem_edit_subscriber', 'eem_subscriber_nonce'); ?>
                <input type="hidden" name="subscriber_id" value="<?php echo $subscriber->id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email Address', 'environmental-email-marketing'); ?> *</label>
                        </th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" 
                                   value="<?php echo esc_attr($subscriber->email); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="first_name"><?php _e('First Name', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="first_name" id="first_name" class="regular-text" 
                                   value="<?php echo esc_attr($subscriber->first_name); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="last_name"><?php _e('Last Name', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="last_name" id="last_name" class="regular-text" 
                                   value="<?php echo esc_attr($subscriber->last_name); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <select name="status" id="status">
                                <option value="active" <?php selected($subscriber->status, 'active'); ?>><?php _e('Active', 'environmental-email-marketing'); ?></option>
                                <option value="pending" <?php selected($subscriber->status, 'pending'); ?>><?php _e('Pending', 'environmental-email-marketing'); ?></option>
                                <option value="unsubscribed" <?php selected($subscriber->status, 'unsubscribed'); ?>><?php _e('Unsubscribed', 'environmental-email-marketing'); ?></option>
                                <option value="bounced" <?php selected($subscriber->status, 'bounced'); ?>><?php _e('Bounced', 'environmental-email-marketing'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lists"><?php _e('Subscribe to Lists', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($lists as $list): ?>
                                <label>
                                    <input type="checkbox" name="lists[]" value="<?php echo $list->id; ?>" 
                                           <?php checked(in_array($list->id, $subscriber_list_ids)); ?>>
                                    <?php echo esc_html($list->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Environmental Score', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <strong><?php echo number_format($subscriber->environmental_score, 1); ?></strong>
                            <p class="description"><?php _e('Score is calculated based on environmental engagement.', 'environmental-email-marketing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Last Activity', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <?php echo $subscriber->last_activity ? mysql2date('M j, Y g:i A', $subscriber->last_activity) : __('Never', 'environmental-email-marketing'); ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Update Subscriber', 'environmental-email-marketing'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=eem-subscribers'); ?>" class="button">
                        <?php _e('Cancel', 'environmental-email-marketing'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render import form
     */
    private function render_import_form() {
        $lists = $this->subscriber_manager->get_lists();
        ?>
        <div class="wrap">
            <h1><?php _e('Import Subscribers', 'environmental-email-marketing'); ?></h1>
            
            <div class="eem-import-instructions">
                <h3><?php _e('Import Instructions', 'environmental-email-marketing'); ?></h3>
                <p><?php _e('Upload a CSV file with subscriber data. The first row should contain column headers.', 'environmental-email-marketing'); ?></p>
                <p><?php _e('Required columns: email', 'environmental-email-marketing'); ?></p>
                <p><?php _e('Optional columns: first_name, last_name, status', 'environmental-email-marketing'); ?></p>
            </div>
            
            <form method="post" enctype="multipart/form-data" id="eem-import-subscribers-form" class="eem-form">
                <?php wp_nonce_field('eem_import_subscribers', 'eem_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php _e('CSV File', 'environmental-email-marketing'); ?> *</label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".csv" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_status"><?php _e('Default Status', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <select name="default_status" id="default_status">
                                <option value="pending"><?php _e('Pending (Requires Confirmation)', 'environmental-email-marketing'); ?></option>
                                <option value="active"><?php _e('Active', 'environmental-email-marketing'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="import_lists"><?php _e('Add to Lists', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <?php foreach ($lists as $list): ?>
                                <label>
                                    <input type="checkbox" name="import_lists[]" value="<?php echo $list->id; ?>">
                                    <?php echo esc_html($list->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="send_welcome"><?php _e('Send Welcome Email', 'environmental-email-marketing'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_welcome" id="send_welcome" value="1">
                                <?php _e('Send welcome email to imported subscribers', 'environmental-email-marketing'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Import Subscribers', 'environmental-email-marketing'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=eem-subscribers'); ?>" class="button">
                        <?php _e('Cancel', 'environmental-email-marketing'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX handler for adding subscriber
     */
    public function ajax_add_subscriber() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $status = sanitize_text_field($_POST['status']);
        $lists = array_map('intval', $_POST['lists'] ?? array());
        $preferences = array_map('sanitize_text_field', $_POST['preferences'] ?? array());

        $subscriber_data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'status' => $status,
            'preferences' => $preferences,
            'source' => 'admin'
        );

        $result = $this->subscriber_manager->add_subscriber($subscriber_data);

        if ($result['success']) {
            // Add to lists
            foreach ($lists as $list_id) {
                $this->subscriber_manager->add_to_list($result['subscriber_id'], $list_id);
            }

            wp_send_json_success(array(
                'message' => __('Subscriber added successfully.', 'environmental-email-marketing'),
                'subscriber_id' => $result['subscriber_id']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for updating subscriber
     */
    public function ajax_update_subscriber() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $subscriber_id = intval($_POST['subscriber_id']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $status = sanitize_text_field($_POST['status']);
        $lists = array_map('intval', $_POST['lists'] ?? array());

        $subscriber_data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'status' => $status
        );

        $result = $this->subscriber_manager->update_subscriber($subscriber_id, $subscriber_data);

        if ($result['success']) {
            // Update list subscriptions
            $this->subscriber_manager->update_list_subscriptions($subscriber_id, $lists);

            wp_send_json_success(array(
                'message' => __('Subscriber updated successfully.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for deleting subscriber
     */
    public function ajax_delete_subscriber() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $subscriber_id = intval($_POST['subscriber_id']);
        $result = $this->subscriber_manager->delete_subscriber($subscriber_id);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Subscriber deleted successfully.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for bulk actions
     */
    public function ajax_bulk_action() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $action = sanitize_text_field($_POST['bulk_action']);
        $subscriber_ids = array_map('intval', $_POST['subscriber_ids'] ?? array());

        if (empty($subscriber_ids)) {
            wp_send_json_error(__('No subscribers selected.', 'environmental-email-marketing'));
        }

        $processed = 0;
        $errors = array();

        foreach ($subscriber_ids as $subscriber_id) {
            $result = false;

            switch ($action) {
                case 'activate':
                    $result = $this->subscriber_manager->update_subscriber($subscriber_id, array('status' => 'active'));
                    break;
                case 'deactivate':
                    $result = $this->subscriber_manager->update_subscriber($subscriber_id, array('status' => 'pending'));
                    break;
                case 'delete':
                    $result = $this->subscriber_manager->delete_subscriber($subscriber_id);
                    break;
            }

            if ($result && $result['success']) {
                $processed++;
            } else {
                $errors[] = $subscriber_id;
            }
        }

        if ($processed > 0) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d subscribers processed successfully.', 'environmental-email-marketing'), $processed),
                'processed' => $processed,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(__('No subscribers were processed.', 'environmental-email-marketing'));
        }
    }

    /**
     * AJAX handler for exporting subscribers
     */
    public function ajax_export_subscribers() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'list_id' => intval($_POST['list_id'] ?? 0)
        );

        $subscribers = $this->subscriber_manager->get_subscribers($args);

        // Generate CSV
        $filename = 'subscribers_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $fp = fopen($filepath, 'w');
        
        // CSV headers
        fputcsv($fp, array('Email', 'First Name', 'Last Name', 'Status', 'Environmental Score', 'Subscribed Date'));

        foreach ($subscribers as $subscriber) {
            fputcsv($fp, array(
                $subscriber->email,
                $subscriber->first_name,
                $subscriber->last_name,
                $subscriber->status,
                $subscriber->environmental_score,
                $subscriber->created_at
            ));
        }

        fclose($fp);

        wp_send_json_success(array(
            'download_url' => wp_upload_dir()['url'] . '/' . $filename,
            'filename' => $filename
        ));
    }

    /**
     * AJAX handler for syncing subscriber
     */
    public function ajax_sync_subscriber() {
        check_ajax_referer('eem_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $subscriber_id = intval($_POST['subscriber_id']);
        $result = $this->subscriber_manager->sync_subscriber_with_providers($subscriber_id);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Subscriber synced successfully.', 'environmental-email-marketing')
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
}
