<?php
/**
 * Campaign Manager Class
 * 
 * Handles donation campaign creation, management, and operations
 * for the Environmental Donation System plugin.
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EDS_Campaign_Manager {
    
    /**
     * Initialize campaign manager
     */
    public function __construct() {
        add_action('wp_ajax_eds_create_campaign', array($this, 'ajax_create_campaign'));
        add_action('wp_ajax_eds_update_campaign', array($this, 'ajax_update_campaign'));
        add_action('wp_ajax_eds_delete_campaign', array($this, 'ajax_delete_campaign'));
        add_action('wp_ajax_eds_get_campaign_stats', array($this, 'ajax_get_campaign_stats'));
        add_action('wp_ajax_eds_featured_campaign', array($this, 'ajax_toggle_featured'));
        add_action('wp_ajax_eds_update_campaign_status', array($this, 'ajax_update_status'));
        
        // Public AJAX for campaign data
        add_action('wp_ajax_eds_get_campaign_data', array($this, 'ajax_get_campaign_data'));
        add_action('wp_ajax_nopriv_eds_get_campaign_data', array($this, 'ajax_get_campaign_data'));
        
        // Scheduled tasks
        add_action('eds_daily_campaign_tasks', array($this, 'daily_campaign_maintenance'));
        add_action('init', array($this, 'schedule_campaign_tasks'));
    }
    
    /**
     * Schedule campaign maintenance tasks
     */
    public function schedule_campaign_tasks() {
        if (!wp_next_scheduled('eds_daily_campaign_tasks')) {
            wp_schedule_event(time(), 'daily', 'eds_daily_campaign_tasks');
        }
    }
    
    /**
     * Create new campaign
     */
    public function create_campaign($data) {
        global $wpdb;
        
        // Validate required fields
        $validation = $this->validate_campaign_data($data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Prepare campaign data
        $campaign_data = array(
            'organization_id' => intval($data['organization_id']),
            'campaign_title' => sanitize_text_field($data['title']),
            'campaign_slug' => $this->generate_campaign_slug($data['title']),
            'campaign_description' => wp_kses_post($data['description']),
            'campaign_goal' => floatval($data['goal']),
            'currency_code' => sanitize_text_field($data['currency_code']),
            'start_date' => sanitize_text_field($data['start_date']),
            'end_date' => !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'campaign_type' => sanitize_text_field($data['campaign_type']),
            'environmental_category' => sanitize_text_field($data['environmental_category']),
            'target_beneficiaries' => !empty($data['target_beneficiaries']) ? intval($data['target_beneficiaries']) : null,
            'expected_impact' => !empty($data['expected_impact']) ? sanitize_textarea_field($data['expected_impact']) : null,
            'location_focus' => !empty($data['location_focus']) ? sanitize_text_field($data['location_focus']) : null,
            'coordinates' => !empty($data['coordinates']) ? sanitize_text_field($data['coordinates']) : null,
            'priority_level' => sanitize_text_field($data['priority_level']),
            'min_donation_amount' => floatval($data['min_donation_amount']),
            'suggested_amounts' => !empty($data['suggested_amounts']) ? wp_json_encode($data['suggested_amounts']) : null,
            'allow_recurring' => isset($data['allow_recurring']) ? 1 : 0,
            'tax_deductible' => isset($data['tax_deductible']) ? 1 : 0,
            'thank_you_message' => !empty($data['thank_you_message']) ? sanitize_textarea_field($data['thank_you_message']) : null,
            'email_notifications' => isset($data['email_notifications']) ? 1 : 0,
            'public_donor_list' => isset($data['public_donor_list']) ? 1 : 0,
            'anonymous_donations' => isset($data['anonymous_donations']) ? 1 : 0,
            'campaign_status' => 'draft',
            'created_by' => get_current_user_id(),
        );
        
        // Handle milestone goals
        if (!empty($data['milestone_goals'])) {
            $campaign_data['milestone_goals'] = wp_json_encode($data['milestone_goals']);
        }
        
        // Handle featured image
        if (!empty($data['featured_image'])) {
            $campaign_data['featured_image_url'] = esc_url_raw($data['featured_image']);
        }
        
        // Handle gallery images
        if (!empty($data['gallery_images'])) {
            $campaign_data['gallery_images'] = wp_json_encode($data['gallery_images']);
        }
        
        // Insert campaign
        $result = $wpdb->insert(
            $wpdb->prefix . 'donation_campaigns',
            $campaign_data
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create campaign');
        }
        
        $campaign_id = $wpdb->insert_id;
        
        // Create corresponding WordPress post for SEO and content management
        $this->create_campaign_post($campaign_id, $campaign_data);
        
        do_action('eds_campaign_created', $campaign_id, $campaign_data);
        
        return $campaign_id;
    }
    
    /**
     * Update existing campaign
     */
    public function update_campaign($campaign_id, $data) {
        global $wpdb;
        
        // Check if campaign exists and user has permission
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return new WP_Error('not_found', 'Campaign not found');
        }
        
        if (!$this->user_can_edit_campaign($campaign_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to edit this campaign');
        }
        
        // Validate data
        $validation = $this->validate_campaign_data($data, $campaign_id);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Prepare update data
        $update_data = array();
        
        $allowed_fields = array(
            'campaign_title' => 'title',
            'campaign_description' => 'description',
            'campaign_goal' => 'goal',
            'end_date' => 'end_date',
            'environmental_category' => 'environmental_category',
            'target_beneficiaries' => 'target_beneficiaries',
            'expected_impact' => 'expected_impact',
            'location_focus' => 'location_focus',
            'coordinates' => 'coordinates',
            'priority_level' => 'priority_level',
            'min_donation_amount' => 'min_donation_amount',
            'thank_you_message' => 'thank_you_message',
        );
        
        foreach ($allowed_fields as $db_field => $data_field) {
            if (isset($data[$data_field])) {
                switch ($db_field) {
                    case 'campaign_goal':
                    case 'min_donation_amount':
                    case 'target_beneficiaries':
                        $update_data[$db_field] = floatval($data[$data_field]);
                        break;
                    case 'campaign_description':
                        $update_data[$db_field] = wp_kses_post($data[$data_field]);
                        break;
                    case 'expected_impact':
                    case 'thank_you_message':
                        $update_data[$db_field] = sanitize_textarea_field($data[$data_field]);
                        break;
                    default:
                        $update_data[$db_field] = sanitize_text_field($data[$data_field]);
                }
            }
        }
        
        // Handle boolean fields
        $boolean_fields = array('allow_recurring', 'tax_deductible', 'email_notifications', 'public_donor_list', 'anonymous_donations');
        foreach ($boolean_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = isset($data[$field]) ? 1 : 0;
            }
        }
        
        // Handle JSON fields
        if (isset($data['suggested_amounts'])) {
            $update_data['suggested_amounts'] = wp_json_encode($data['suggested_amounts']);
        }
        
        if (isset($data['milestone_goals'])) {
            $update_data['milestone_goals'] = wp_json_encode($data['milestone_goals']);
        }
        
        if (isset($data['gallery_images'])) {
            $update_data['gallery_images'] = wp_json_encode($data['gallery_images']);
        }
        
        // Handle featured image
        if (isset($data['featured_image'])) {
            $update_data['featured_image_url'] = esc_url_raw($data['featured_image']);
        }
        
        // Update slug if title changed
        if (isset($data['title']) && $data['title'] !== $campaign->campaign_title) {
            $update_data['campaign_slug'] = $this->generate_campaign_slug($data['title'], $campaign_id);
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_changes', 'No changes to update');
        }
        
        // Perform update
        $result = $wpdb->update(
            $wpdb->prefix . 'donation_campaigns',
            $update_data,
            array('campaign_id' => $campaign_id),
            null,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to update campaign');
        }
        
        // Update corresponding WordPress post
        $this->update_campaign_post($campaign_id, $update_data);
        
        do_action('eds_campaign_updated', $campaign_id, $update_data);
        
        return true;
    }
    
    /**
     * Delete campaign
     */
    public function delete_campaign($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return new WP_Error('not_found', 'Campaign not found');
        }
        
        if (!$this->user_can_delete_campaign($campaign_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to delete this campaign');
        }
        
        // Check if campaign has donations
        $donation_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}donations WHERE campaign_id = %d",
            $campaign_id
        ));
        
        if ($donation_count > 0) {
            return new WP_Error('has_donations', 'Cannot delete campaign with existing donations');
        }
        
        // Delete campaign
        $result = $wpdb->delete(
            $wpdb->prefix . 'donation_campaigns',
            array('campaign_id' => $campaign_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to delete campaign');
        }
        
        // Delete corresponding WordPress post
        $this->delete_campaign_post($campaign_id);
        
        do_action('eds_campaign_deleted', $campaign_id);
        
        return true;
    }
    
    /**
     * Get campaign by ID
     */
    public function get_campaign($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, o.organization_name 
            FROM {$wpdb->prefix}donation_campaigns c
            LEFT JOIN {$wpdb->prefix}donation_organizations o ON c.organization_id = o.organization_id
            WHERE c.campaign_id = %d",
            $campaign_id
        ));
    }
    
    /**
     * Get campaigns with filters
     */
    public function get_campaigns($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'organization_id' => null,
            'campaign_type' => null,
            'environmental_category' => null,
            'featured' => null,
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();
        
        // Status filter
        if ($args['status']) {
            $where_conditions[] = "c.campaign_status = %s";
            $where_values[] = $args['status'];
        }
        
        // Organization filter
        if ($args['organization_id']) {
            $where_conditions[] = "c.organization_id = %d";
            $where_values[] = $args['organization_id'];
        }
        
        // Campaign type filter
        if ($args['campaign_type']) {
            $where_conditions[] = "c.campaign_type = %s";
            $where_values[] = $args['campaign_type'];
        }
        
        // Environmental category filter
        if ($args['environmental_category']) {
            $where_conditions[] = "c.environmental_category = %s";
            $where_values[] = $args['environmental_category'];
        }
        
        // Featured filter
        if ($args['featured'] !== null) {
            $where_conditions[] = "c.featured = %d";
            $where_values[] = $args['featured'] ? 1 : 0;
        }
        
        // Search filter
        if ($args['search']) {
            $where_conditions[] = "(c.campaign_title LIKE %s OR c.campaign_description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Build WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Build ORDER BY clause
        $allowed_orderby = array('created_at', 'campaign_title', 'campaign_goal', 'current_amount', 'end_date');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build LIMIT clause
        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        $query = "
            SELECT c.*, o.organization_name,
                   (c.current_amount / c.campaign_goal * 100) as progress_percentage
            FROM {$wpdb->prefix}donation_campaigns c
            LEFT JOIN {$wpdb->prefix}donation_organizations o ON c.organization_id = o.organization_id
            {$where_clause}
            ORDER BY c.{$orderby} {$order}
            {$limit_clause}
        ";
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get campaign statistics
     */
    public function get_campaign_stats($campaign_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(d.donation_id) as total_donations,
                COUNT(DISTINCT d.donor_email) as unique_donors,
                SUM(CASE WHEN d.payment_status = 'completed' THEN d.net_amount ELSE 0 END) as total_raised,
                AVG(CASE WHEN d.payment_status = 'completed' THEN d.net_amount ELSE NULL END) as average_donation,
                MAX(d.payment_date) as last_donation_date,
                SUM(CASE WHEN d.payment_status = 'completed' AND d.donation_type = 'recurring' THEN 1 ELSE 0 END) as recurring_donors,
                SUM(CASE WHEN d.payment_status = 'pending' THEN d.donation_amount ELSE 0 END) as pending_amount
            FROM {$wpdb->prefix}donations d
            WHERE d.campaign_id = %d",
            $campaign_id
        ));
        
        if (!$stats) {
            return null;
        }
        
        // Get campaign goal for progress calculation
        $campaign = $this->get_campaign($campaign_id);
        if ($campaign) {
            $stats->campaign_goal = $campaign->campaign_goal;
            $stats->progress_percentage = ($stats->total_raised / $campaign->campaign_goal) * 100;
            $stats->remaining_amount = max(0, $campaign->campaign_goal - $stats->total_raised);
        }
        
        // Get recent donations
        $stats->recent_donations = $wpdb->get_results($wpdb->prepare(
            "SELECT donor_name, donor_email, donation_amount, payment_date, is_anonymous
            FROM {$wpdb->prefix}donations
            WHERE campaign_id = %d AND payment_status = 'completed'
            ORDER BY payment_date DESC
            LIMIT 5",
            $campaign_id
        ));
        
        return $stats;
    }
    
    /**
     * Update campaign status
     */
    public function update_campaign_status($campaign_id, $status) {
        global $wpdb;
        
        $allowed_statuses = array('draft', 'active', 'paused', 'completed', 'cancelled');
        if (!in_array($status, $allowed_statuses)) {
            return new WP_Error('invalid_status', 'Invalid campaign status');
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'donation_campaigns',
            array('campaign_status' => $status),
            array('campaign_id' => $campaign_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('eds_campaign_status_changed', $campaign_id, $status);
        }
        
        return $result !== false;
    }
    
    /**
     * Toggle campaign featured status
     */
    public function toggle_featured($campaign_id) {
        global $wpdb;
        
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign) {
            return new WP_Error('not_found', 'Campaign not found');
        }
        
        $new_featured = $campaign->featured ? 0 : 1;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'donation_campaigns',
            array('featured' => $new_featured),
            array('campaign_id' => $campaign_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Daily campaign maintenance
     */
    public function daily_campaign_maintenance() {
        $this->update_expired_campaigns();
        $this->update_campaign_metrics();
        $this->cleanup_draft_campaigns();
    }
    
    /**
     * Update expired campaigns
     */
    private function update_expired_campaigns() {
        global $wpdb;
        
        $wpdb->query(
            "UPDATE {$wpdb->prefix}donation_campaigns 
            SET campaign_status = 'completed' 
            WHERE campaign_status = 'active' 
            AND end_date IS NOT NULL 
            AND end_date < NOW()"
        );
    }
    
    /**
     * Update campaign metrics
     */
    private function update_campaign_metrics() {
        global $wpdb;
        
        // Update trending scores, view counts, etc.
        $campaigns = $wpdb->get_results(
            "SELECT campaign_id FROM {$wpdb->prefix}donation_campaigns WHERE campaign_status = 'active'"
        );
        
        foreach ($campaigns as $campaign) {
            $this->calculate_trending_score($campaign->campaign_id);
        }
    }
    
    /**
     * Calculate trending score for campaign
     */
    private function calculate_trending_score($campaign_id) {
        global $wpdb;
        
        // Get recent donation activity (last 7 days)
        $recent_activity = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as recent_donations,
                SUM(net_amount) as recent_amount,
                COUNT(DISTINCT donor_email) as recent_donors
            FROM {$wpdb->prefix}donations
            WHERE campaign_id = %d 
            AND payment_status = 'completed'
            AND payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $campaign_id
        ));
        
        if (!$recent_activity) {
            return;
        }
        
        // Calculate trending score based on multiple factors
        $score = 0;
        $score += $recent_activity->recent_donations * 2; // Recent donation count
        $score += $recent_activity->recent_amount * 0.01; // Recent amount (scaled)
        $score += $recent_activity->recent_donors * 3; // Unique recent donors
        
        // Update trending score
        $wpdb->update(
            $wpdb->prefix . 'donation_campaigns',
            array('trending_score' => $score),
            array('campaign_id' => $campaign_id),
            array('%f'),
            array('%d')
        );
    }
    
    /**
     * Cleanup old draft campaigns
     */
    private function cleanup_draft_campaigns() {
        global $wpdb;
        
        // Delete draft campaigns older than 30 days with no donations
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}donation_campaigns 
            WHERE campaign_status = 'draft' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND campaign_id NOT IN (
                SELECT DISTINCT campaign_id FROM {$wpdb->prefix}donations
            )"
        );
    }
    
    /**
     * Validate campaign data
     */
    private function validate_campaign_data($data, $campaign_id = null) {
        // Required fields
        $required_fields = array('title', 'description', 'goal', 'organization_id');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Field '$field' is required");
            }
        }
        
        // Validate goal amount
        if (!is_numeric($data['goal']) || $data['goal'] <= 0) {
            return new WP_Error('invalid_goal', 'Campaign goal must be a positive number');
        }
        
        // Validate organization exists
        $org_exists = $this->organization_exists($data['organization_id']);
        if (!$org_exists) {
            return new WP_Error('invalid_organization', 'Organization does not exist');
        }
        
        // Validate dates
        if (!empty($data['start_date'])) {
            $start_date = strtotime($data['start_date']);
            if (!$start_date) {
                return new WP_Error('invalid_start_date', 'Invalid start date format');
            }
        }
        
        if (!empty($data['end_date'])) {
            $end_date = strtotime($data['end_date']);
            if (!$end_date) {
                return new WP_Error('invalid_end_date', 'Invalid end date format');
            }
            
            if (!empty($data['start_date']) && $end_date <= strtotime($data['start_date'])) {
                return new WP_Error('invalid_date_range', 'End date must be after start date');
            }
        }
        
        return true;
    }
    
    /**
     * Check if organization exists
     */
    private function organization_exists($organization_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}donation_organizations WHERE organization_id = %d",
            $organization_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Generate unique campaign slug
     */
    private function generate_campaign_slug($title, $campaign_id = null) {
        $slug = sanitize_title($title);
        
        global $wpdb;
        
        // Check if slug exists
        $where_clause = $campaign_id ? $wpdb->prepare("AND campaign_id != %d", $campaign_id) : "";
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}donation_campaigns 
            WHERE campaign_slug = %s {$where_clause}",
            $slug
        ));
        
        if ($existing > 0) {
            $counter = 1;
            do {
                $new_slug = $slug . '-' . $counter;
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}donation_campaigns 
                    WHERE campaign_slug = %s {$where_clause}",
                    $new_slug
                ));
                $counter++;
            } while ($existing > 0);
            
            $slug = $new_slug;
        }
        
        return $slug;
    }
    
    /**
     * Check if user can edit campaign
     */
    private function user_can_edit_campaign($campaign_id) {
        if (current_user_can('manage_options')) {
            return true;
        }
        
        $campaign = $this->get_campaign($campaign_id);
        return $campaign && $campaign->created_by == get_current_user_id();
    }
    
    /**
     * Check if user can delete campaign
     */
    private function user_can_delete_campaign($campaign_id) {
        return current_user_can('manage_options');
    }
    
    /**
     * AJAX Handlers
     */
    
    public function ajax_create_campaign() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->create_campaign($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'campaign_id' => $result,
            'message' => 'Campaign created successfully'
        ));
    }
    
    public function ajax_update_campaign() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        $campaign_id = intval($_POST['campaign_id']);
        $result = $this->update_campaign($campaign_id, $_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Campaign updated successfully');
    }
    
    public function ajax_delete_campaign() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $result = $this->delete_campaign($campaign_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Campaign deleted successfully');
    }
    
    public function ajax_get_campaign_stats() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        $campaign_id = intval($_POST['campaign_id']);
        $stats = $this->get_campaign_stats($campaign_id);
        
        if (!$stats) {
            wp_send_json_error('Campaign not found');
        }
        
        wp_send_json_success($stats);
    }
    
    public function ajax_toggle_featured() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $result = $this->toggle_featured($campaign_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Featured status updated');
    }
    
    public function ajax_update_status() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = $this->update_campaign_status($campaign_id, $status);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Campaign status updated');
    }
    
    public function ajax_get_campaign_data() {
        check_ajax_referer('eds_donation_nonce', 'nonce');
        
        $campaign_id = intval($_POST['campaign_id']);
        $campaign = $this->get_campaign($campaign_id);
        
        if (!$campaign) {
            wp_send_json_error('Campaign not found');
        }
        
        // Only return public data for frontend
        $public_data = array(
            'id' => $campaign->campaign_id,
            'title' => $campaign->campaign_title,
            'description' => $campaign->campaign_description,
            'goal' => $campaign->campaign_goal,
            'current_amount' => $campaign->current_amount,
            'currency_code' => $campaign->currency_code,
            'progress_percentage' => ($campaign->current_amount / $campaign->campaign_goal) * 100,
            'total_donors' => $campaign->total_donors,
            'min_donation_amount' => $campaign->min_donation_amount,
            'suggested_amounts' => json_decode($campaign->suggested_amounts, true),
            'allow_recurring' => $campaign->allow_recurring,
            'tax_deductible' => $campaign->tax_deductible,
            'organization_name' => $campaign->organization_name,
            'featured_image' => $campaign->featured_image_url,
            'end_date' => $campaign->end_date,
        );
        
        wp_send_json_success($public_data);
    }
    
    /**
     * Create corresponding WordPress post for SEO
     */
    private function create_campaign_post($campaign_id, $campaign_data) {
        $post_data = array(
            'post_title' => $campaign_data['campaign_title'],
            'post_content' => $campaign_data['campaign_description'],
            'post_status' => 'publish',
            'post_type' => 'donation_campaign',
            'meta_input' => array(
                'campaign_id' => $campaign_id,
            ),
        );
        
        wp_insert_post($post_data);
    }
    
    /**
     * Update corresponding WordPress post
     */
    private function update_campaign_post($campaign_id, $update_data) {
        $posts = get_posts(array(
            'post_type' => 'donation_campaign',
            'meta_key' => 'campaign_id',
            'meta_value' => $campaign_id,
            'posts_per_page' => 1,
        ));
        
        if (!empty($posts)) {
            $post_update = array('ID' => $posts[0]->ID);
            
            if (isset($update_data['campaign_title'])) {
                $post_update['post_title'] = $update_data['campaign_title'];
            }
            
            if (isset($update_data['campaign_description'])) {
                $post_update['post_content'] = $update_data['campaign_description'];
            }
            
            wp_update_post($post_update);
        }
    }
    
    /**
     * Delete corresponding WordPress post
     */
    private function delete_campaign_post($campaign_id) {
        $posts = get_posts(array(
            'post_type' => 'donation_campaign',
            'meta_key' => 'campaign_id',
            'meta_value' => $campaign_id,
            'posts_per_page' => 1,
        ));
        
        if (!empty($posts)) {
            wp_delete_post($posts[0]->ID, true);
        }
    }
}
