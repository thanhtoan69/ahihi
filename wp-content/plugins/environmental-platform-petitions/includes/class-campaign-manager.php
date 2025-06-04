<?php
/**
 * Campaign Manager Class
 * 
 * Handles campaign creation, management, and tracking for petitions
 * 
 * @package Environmental_Platform_Petitions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Platform_Petitions_Campaign_Manager {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Environmental_Platform_Petitions_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_create_petition_campaign', array($this, 'ajax_create_campaign'));
        add_action('wp_ajax_update_petition_campaign', array($this, 'ajax_update_campaign'));
        add_action('wp_ajax_delete_petition_campaign', array($this, 'ajax_delete_campaign'));
        add_action('wp_ajax_get_campaign_analytics', array($this, 'ajax_get_campaign_analytics'));
        
        // Add campaign meta box to petition edit screen
        add_action('add_meta_boxes', array($this, 'add_campaign_meta_boxes'));
        add_action('save_post', array($this, 'save_campaign_meta'));
    }
    
    /**
     * Create a new campaign
     */
    public function create_campaign($petition_id, $data) {
        global $wpdb;
        
        $table = $this->database->get_table_name('campaigns');
        
        $campaign_data = array(
            'petition_id' => absint($petition_id),
            'name' => sanitize_text_field($data['name']),
            'description' => wp_kses_post($data['description']),
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date' => sanitize_text_field($data['end_date']),
            'target_signatures' => absint($data['target_signatures']),
            'budget' => floatval($data['budget']),
            'status' => sanitize_text_field($data['status']),
            'campaign_type' => sanitize_text_field($data['campaign_type']),
            'target_audience' => wp_json_encode($data['target_audience']),
            'channels' => wp_json_encode($data['channels']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $campaign_data);
        
        if ($result) {
            $campaign_id = $wpdb->insert_id;
            
            // Log campaign creation
            $this->log_campaign_event($campaign_id, 'created', 'Campaign created successfully');
            
            // Schedule campaign start if needed
            if (strtotime($data['start_date']) > current_time('timestamp')) {
                $this->schedule_campaign_start($campaign_id, $data['start_date']);
            }
            
            return $campaign_id;
        }
        
        return false;
    }
    
    /**
     * Update existing campaign
     */
    public function update_campaign($campaign_id, $data) {
        global $wpdb;
        
        $table = $this->database->get_table_name('campaigns');
        
        $campaign_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => wp_kses_post($data['description']),
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date' => sanitize_text_field($data['end_date']),
            'target_signatures' => absint($data['target_signatures']),
            'budget' => floatval($data['budget']),
            'status' => sanitize_text_field($data['status']),
            'campaign_type' => sanitize_text_field($data['campaign_type']),
            'target_audience' => wp_json_encode($data['target_audience']),
            'channels' => wp_json_encode($data['channels']),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $table,
            $campaign_data,
            array('id' => absint($campaign_id))
        );
        
        if ($result !== false) {
            $this->log_campaign_event($campaign_id, 'updated', 'Campaign updated successfully');
            return true;
        }
        
        return false;
    }
    
    /**
     * Get campaign by ID
     */
    public function get_campaign($campaign_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('campaigns');
        
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $campaign_id
        ));
        
        if ($campaign) {
            $campaign->target_audience = json_decode($campaign->target_audience, true);
            $campaign->channels = json_decode($campaign->channels, true);
            $campaign->performance = $this->get_campaign_performance($campaign_id);
        }
        
        return $campaign;
    }
    
    /**
     * Get campaigns for petition
     */
    public function get_petition_campaigns($petition_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('campaigns');
        
        $campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE petition_id = %d ORDER BY created_at DESC",
            $petition_id
        ));
        
        foreach ($campaigns as $campaign) {
            $campaign->target_audience = json_decode($campaign->target_audience, true);
            $campaign->channels = json_decode($campaign->channels, true);
            $campaign->performance = $this->get_campaign_performance($campaign->id);
        }
        
        return $campaigns;
    }
    
    /**
     * Get campaign performance metrics
     */
    public function get_campaign_performance($campaign_id) {
        global $wpdb;
        
        $signatures_table = $this->database->get_table_name('signatures');
        $analytics_table = $this->database->get_table_name('analytics');
        $shares_table = $this->database->get_table_name('shares');
        
        // Get campaign details
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->database->get_table_name('campaigns')} WHERE id = %d",
            $campaign_id
        ));
        
        if (!$campaign) {
            return false;
        }
        
        // Calculate date range
        $start_date = $campaign->start_date;
        $end_date = $campaign->end_date ?: current_time('mysql');
        
        // Get signature metrics
        $signature_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_signatures,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_signatures,
                COUNT(CASE WHEN created_at >= %s THEN 1 END) as campaign_signatures
            FROM {$signatures_table} 
            WHERE petition_id = %d",
            $start_date,
            $campaign->petition_id
        ));
        
        // Get analytics metrics
        $analytics_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_type,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$analytics_table} 
            WHERE petition_id = %d 
            AND created_at BETWEEN %s AND %s
            GROUP BY event_type",
            $campaign->petition_id,
            $start_date,
            $end_date
        ));
        
        // Get sharing metrics
        $sharing_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_shares,
                COUNT(DISTINCT platform) as platforms_used,
                AVG(clicks) as avg_clicks
            FROM {$shares_table} 
            WHERE petition_id = %d 
            AND created_at BETWEEN %s AND %s",
            $campaign->petition_id,
            $start_date,
            $end_date
        ));
        
        // Calculate conversion rates
        $page_views = 0;
        $form_views = 0;
        foreach ($analytics_stats as $stat) {
            if ($stat->event_type === 'page_view') {
                $page_views = $stat->count;
            } elseif ($stat->event_type === 'form_view') {
                $form_views = $stat->count;
            }
        }
        
        $conversion_rate = $page_views > 0 ? ($signature_stats->campaign_signatures / $page_views) * 100 : 0;
        $form_conversion_rate = $form_views > 0 ? ($signature_stats->campaign_signatures / $form_views) * 100 : 0;
        
        // Calculate progress
        $progress_percentage = $campaign->target_signatures > 0 ? 
            ($signature_stats->total_signatures / $campaign->target_signatures) * 100 : 0;
        
        return array(
            'total_signatures' => $signature_stats->total_signatures,
            'verified_signatures' => $signature_stats->verified_signatures,
            'campaign_signatures' => $signature_stats->campaign_signatures,
            'total_shares' => $sharing_stats->total_shares ?: 0,
            'platforms_used' => $sharing_stats->platforms_used ?: 0,
            'avg_clicks' => round($sharing_stats->avg_clicks ?: 0, 2),
            'page_views' => $page_views,
            'form_views' => $form_views,
            'conversion_rate' => round($conversion_rate, 2),
            'form_conversion_rate' => round($form_conversion_rate, 2),
            'progress_percentage' => round($progress_percentage, 2),
            'target_signatures' => $campaign->target_signatures,
            'analytics_breakdown' => $analytics_stats
        );
    }
    
    /**
     * Log campaign event
     */
    private function log_campaign_event($campaign_id, $event_type, $message) {
        global $wpdb;
        
        $analytics_table = $this->database->get_table_name('analytics');
        
        $wpdb->insert($analytics_table, array(
            'petition_id' => $this->get_campaign_petition_id($campaign_id),
            'user_id' => get_current_user_id(),
            'event_type' => 'campaign_' . $event_type,
            'event_data' => wp_json_encode(array(
                'campaign_id' => $campaign_id,
                'message' => $message
            )),
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Get petition ID for campaign
     */
    private function get_campaign_petition_id($campaign_id) {
        global $wpdb;
        
        $table = $this->database->get_table_name('campaigns');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT petition_id FROM {$table} WHERE id = %d",
            $campaign_id
        ));
    }
    
    /**
     * Schedule campaign start
     */
    private function schedule_campaign_start($campaign_id, $start_date) {
        wp_schedule_single_event(
            strtotime($start_date),
            'environmental_platform_petitions_campaign_start',
            array($campaign_id)
        );
    }
    
    /**
     * AJAX: Create campaign
     */
    public function ajax_create_campaign() {
        check_ajax_referer('petition_campaign_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $petition_id = absint($_POST['petition_id']);
        $campaign_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'target_signatures' => absint($_POST['target_signatures']),
            'budget' => floatval($_POST['budget']),
            'status' => sanitize_text_field($_POST['status']),
            'campaign_type' => sanitize_text_field($_POST['campaign_type']),
            'target_audience' => isset($_POST['target_audience']) ? $_POST['target_audience'] : array(),
            'channels' => isset($_POST['channels']) ? $_POST['channels'] : array()
        );
        
        $campaign_id = $this->create_campaign($petition_id, $campaign_data);
        
        if ($campaign_id) {
            wp_send_json_success(array(
                'campaign_id' => $campaign_id,
                'message' => 'Campaign created successfully'
            ));
        } else {
            wp_send_json_error('Failed to create campaign');
        }
    }
    
    /**
     * AJAX: Update campaign
     */
    public function ajax_update_campaign() {
        check_ajax_referer('petition_campaign_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = absint($_POST['campaign_id']);
        $campaign_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => wp_kses_post($_POST['description']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'target_signatures' => absint($_POST['target_signatures']),
            'budget' => floatval($_POST['budget']),
            'status' => sanitize_text_field($_POST['status']),
            'campaign_type' => sanitize_text_field($_POST['campaign_type']),
            'target_audience' => isset($_POST['target_audience']) ? $_POST['target_audience'] : array(),
            'channels' => isset($_POST['channels']) ? $_POST['channels'] : array()
        );
        
        $result = $this->update_campaign($campaign_id, $campaign_data);
        
        if ($result) {
            wp_send_json_success('Campaign updated successfully');
        } else {
            wp_send_json_error('Failed to update campaign');
        }
    }
    
    /**
     * AJAX: Delete campaign
     */
    public function ajax_delete_campaign() {
        check_ajax_referer('petition_campaign_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = absint($_POST['campaign_id']);
        
        global $wpdb;
        $table = $this->database->get_table_name('campaigns');
        
        $result = $wpdb->delete($table, array('id' => $campaign_id));
        
        if ($result) {
            wp_send_json_success('Campaign deleted successfully');
        } else {
            wp_send_json_error('Failed to delete campaign');
        }
    }
    
    /**
     * AJAX: Get campaign analytics
     */
    public function ajax_get_campaign_analytics() {
        check_ajax_referer('petition_campaign_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $campaign_id = absint($_POST['campaign_id']);
        $performance = $this->get_campaign_performance($campaign_id);
        
        if ($performance) {
            wp_send_json_success($performance);
        } else {
            wp_send_json_error('Failed to get campaign analytics');
        }
    }
    
    /**
     * Add campaign meta boxes
     */
    public function add_campaign_meta_boxes() {
        add_meta_box(
            'petition_campaigns',
            'Campaign Management',
            array($this, 'render_campaign_meta_box'),
            'env_petition',
            'normal',
            'high'
        );
    }
    
    /**
     * Render campaign meta box
     */
    public function render_campaign_meta_box($post) {
        $campaigns = $this->get_petition_campaigns($post->ID);
        wp_nonce_field('petition_campaign_meta', 'petition_campaign_nonce');
        
        echo '<div id="petition-campaigns-manager">';
        echo '<h4>Active Campaigns</h4>';
        
        if (empty($campaigns)) {
            echo '<p>No campaigns found for this petition.</p>';
        } else {
            echo '<div class="campaigns-list">';
            foreach ($campaigns as $campaign) {
                $this->render_campaign_item($campaign);
            }
            echo '</div>';
        }
        
        echo '<button type="button" class="button button-primary" id="add-new-campaign">Add New Campaign</button>';
        echo '</div>';
        
        // Add campaign form modal
        $this->render_campaign_form_modal();
    }
    
    /**
     * Render individual campaign item
     */
    private function render_campaign_item($campaign) {
        $status_class = 'campaign-status-' . $campaign->status;
        $progress = $campaign->performance['progress_percentage'];
        
        echo '<div class="campaign-item ' . $status_class . '" data-campaign-id="' . $campaign->id . '">';
        echo '<h5>' . esc_html($campaign->name) . '</h5>';
        echo '<p>' . esc_html(wp_trim_words($campaign->description, 20)) . '</p>';
        
        echo '<div class="campaign-stats">';
        echo '<span class="stat">Signatures: ' . $campaign->performance['total_signatures'] . '</span>';
        echo '<span class="stat">Progress: ' . round($progress, 1) . '%</span>';
        echo '<span class="stat">Status: ' . ucfirst($campaign->status) . '</span>';
        echo '</div>';
        
        echo '<div class="campaign-actions">';
        echo '<button type="button" class="button edit-campaign">Edit</button>';
        echo '<button type="button" class="button view-analytics">Analytics</button>';
        echo '<button type="button" class="button button-link-delete delete-campaign">Delete</button>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render campaign form modal
     */
    private function render_campaign_form_modal() {
        ?>
        <div id="campaign-modal" class="petition-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="modal-title">Add New Campaign</h3>
                
                <form id="campaign-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="campaign-name">Campaign Name</label></th>
                            <td><input type="text" id="campaign-name" name="name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="campaign-description">Description</label></th>
                            <td><textarea id="campaign-description" name="description" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="campaign-type">Campaign Type</label></th>
                            <td>
                                <select id="campaign-type" name="campaign_type">
                                    <option value="awareness">Awareness</option>
                                    <option value="signature_drive">Signature Drive</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="email">Email Campaign</option>
                                    <option value="grassroots">Grassroots</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="start-date">Start Date</label></th>
                            <td><input type="date" id="start-date" name="start_date" required></td>
                        </tr>
                        <tr>
                            <th><label for="end-date">End Date</label></th>
                            <td><input type="date" id="end-date" name="end_date"></td>
                        </tr>
                        <tr>
                            <th><label for="target-signatures">Target Signatures</label></th>
                            <td><input type="number" id="target-signatures" name="target_signatures" min="1" required></td>
                        </tr>
                        <tr>
                            <th><label for="budget">Budget ($)</label></th>
                            <td><input type="number" id="budget" name="budget" min="0" step="0.01"></td>
                        </tr>
                        <tr>
                            <th><label for="status">Status</label></th>
                            <td>
                                <select id="status" name="status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <input type="hidden" id="campaign-id" name="campaign_id">
                    <input type="hidden" name="petition_id" value="<?php echo get_the_ID(); ?>">
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">Save Campaign</button>
                        <button type="button" class="button" id="cancel-campaign">Cancel</button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save campaign meta
     */
    public function save_campaign_meta($post_id) {
        if (!isset($_POST['petition_campaign_nonce']) || 
            !wp_verify_nonce($_POST['petition_campaign_nonce'], 'petition_campaign_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Meta saving is handled via AJAX for campaigns
    }
}
