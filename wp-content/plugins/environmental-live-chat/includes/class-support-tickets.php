<?php
/**
 * Support Tickets Class
 * 
 * Handles support ticket management, workflow, and email integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Support_Tickets {
    
    private static $instance = null;
    private $table_tickets;
    private $table_replies;
    private $table_analytics;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_tickets = $wpdb->prefix . 'elc_support_tickets';
        $this->table_replies = $wpdb->prefix . 'elc_ticket_replies';
        $this->table_analytics = $wpdb->prefix . 'elc_analytics';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_elc_create_ticket', array($this, 'create_ticket'));
        add_action('wp_ajax_nopriv_elc_create_ticket', array($this, 'create_ticket'));
        
        add_action('wp_ajax_elc_reply_ticket', array($this, 'reply_to_ticket'));
        add_action('wp_ajax_elc_update_ticket_status', array($this, 'update_ticket_status'));
        add_action('wp_ajax_elc_assign_ticket', array($this, 'assign_ticket'));
        
        add_action('wp_ajax_elc_get_tickets', array($this, 'get_tickets'));
        add_action('wp_ajax_elc_get_ticket_details', array($this, 'get_ticket_details'));
        
        add_action('wp_ajax_elc_upload_ticket_attachment', array($this, 'handle_ticket_attachment'));
        add_action('wp_ajax_nopriv_elc_upload_ticket_attachment', array($this, 'handle_ticket_attachment'));
        
        // Email hooks
        add_action('elc_ticket_created', array($this, 'send_ticket_created_email'), 10, 2);
        add_action('elc_ticket_replied', array($this, 'send_ticket_reply_email'), 10, 3);
        add_action('elc_ticket_status_changed', array($this, 'send_status_change_email'), 10, 3);
        
        // Scheduled tasks
        add_action('elc_ticket_reminders', array($this, 'send_ticket_reminders'));
        add_action('elc_escalate_tickets', array($this, 'escalate_overdue_tickets'));
    }
    
    /**
     * Create a new support ticket
     */
    public function create_ticket() {
        check_ajax_referer('elc_ticket_nonce', 'nonce');
        
        global $wpdb;
        
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'general');
        $priority = sanitize_text_field($_POST['priority'] ?? 'medium');
        
        // Validation
        if (empty($customer_name) || empty($customer_email) || empty($subject) || empty($message)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'environmental-live-chat')));
        }
        
        if (!is_email($customer_email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'environmental-live-chat')));
        }
        
        // Generate ticket number
        $ticket_number = $this->generate_ticket_number();
        
        // Create ticket
        $ticket_data = array(
            'ticket_number' => $ticket_number,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'subject' => $subject,
            'message' => $message,
            'category' => $category,
            'priority' => $priority,
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $ticket_id = $wpdb->insert($this->table_tickets, $ticket_data);
        
        if ($ticket_id) {
            $ticket_id = $wpdb->insert_id;
            
            // Auto-assign if enabled
            $auto_assign = get_option('elc_auto_assign_tickets', false);
            if ($auto_assign) {
                $assigned_agent = $this->auto_assign_ticket($ticket_id, $category);
                if ($assigned_agent) {
                    $wpdb->update(
                        $this->table_tickets,
                        array('assigned_to' => $assigned_agent),
                        array('id' => $ticket_id)
                    );
                }
            }
            
            // Log analytics
            $this->log_ticket_metric('ticket_created', $ticket_id, array(
                'category' => $category,
                'priority' => $priority
            ));
            
            // Trigger email notification
            do_action('elc_ticket_created', $ticket_id, $ticket_data);
            
            wp_send_json_success(array(
                'ticket_id' => $ticket_id,
                'ticket_number' => $ticket_number,
                'message' => sprintf(__('Support ticket #%s created successfully. We will respond within 24 hours.', 'environmental-live-chat'), $ticket_number)
            ));
            
        } else {
            wp_send_json_error(array('message' => __('Failed to create support ticket. Please try again.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Reply to a support ticket
     */
    public function reply_to_ticket() {
        check_ajax_referer('elc_ticket_nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $is_customer = !current_user_can('manage_options');
        
        if (!$ticket_id || empty($message)) {
            wp_send_json_error(array('message' => __('Ticket ID and message are required.', 'environmental-live-chat')));
        }
        
        // Verify ticket exists
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket not found.', 'environmental-live-chat')));
        }
        
        // For customers, verify email matches
        if ($is_customer) {
            $customer_email = sanitize_email($_POST['customer_email'] ?? '');
            if ($customer_email !== $ticket->customer_email) {
                wp_send_json_error(array('message' => __('Email address does not match ticket.', 'environmental-live-chat')));
            }
        }
        
        // Create reply
        $reply_data = array(
            'ticket_id' => $ticket_id,
            'message' => $message,
            'is_customer_reply' => $is_customer ? 1 : 0,
            'author_name' => $is_customer ? $ticket->customer_name : wp_get_current_user()->display_name,
            'author_email' => $is_customer ? $ticket->customer_email : wp_get_current_user()->user_email,
            'created_at' => current_time('mysql')
        );
        
        $reply_id = $wpdb->insert($this->table_replies, $reply_data);
        
        if ($reply_id) {
            $reply_id = $wpdb->insert_id;
            
            // Update ticket status and timestamp
            $new_status = $is_customer ? 'customer-reply' : 'in-progress';
            $wpdb->update(
                $this->table_tickets,
                array(
                    'status' => $new_status,
                    'updated_at' => current_time('mysql'),
                    'last_reply_at' => current_time('mysql')
                ),
                array('id' => $ticket_id)
            );
            
            // Log analytics
            $this->log_ticket_metric('ticket_reply', $ticket_id, array(
                'is_customer_reply' => $is_customer,
                'reply_id' => $reply_id
            ));
            
            // Trigger email notification
            do_action('elc_ticket_replied', $ticket_id, $reply_id, $is_customer);
            
            wp_send_json_success(array(
                'reply_id' => $reply_id,
                'message' => __('Reply added successfully.', 'environmental-live-chat')
            ));
            
        } else {
            wp_send_json_error(array('message' => __('Failed to add reply. Please try again.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Update ticket status
     */
    public function update_ticket_status() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        $note = sanitize_textarea_field($_POST['note'] ?? '');
        
        if (!$ticket_id || empty($new_status)) {
            wp_send_json_error(array('message' => __('Ticket ID and status are required.', 'environmental-live-chat')));
        }
        
        $valid_statuses = array('open', 'in-progress', 'resolved', 'closed', 'customer-reply');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'environmental-live-chat')));
        }
        
        // Get current ticket
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket not found.', 'environmental-live-chat')));
        }
        
        $old_status = $ticket->status;
        
        // Update ticket
        $update_data = array(
            'status' => $new_status,
            'updated_at' => current_time('mysql')
        );
        
        if ($new_status === 'resolved' || $new_status === 'closed') {
            $update_data['resolved_at'] = current_time('mysql');
        }
        
        $updated = $wpdb->update(
            $this->table_tickets,
            $update_data,
            array('id' => $ticket_id)
        );
        
        if ($updated !== false) {
            // Add internal note if provided
            if (!empty($note)) {
                $wpdb->insert(
                    $this->table_replies,
                    array(
                        'ticket_id' => $ticket_id,
                        'message' => $note,
                        'is_customer_reply' => 0,
                        'is_internal_note' => 1,
                        'author_name' => wp_get_current_user()->display_name,
                        'author_email' => wp_get_current_user()->user_email,
                        'created_at' => current_time('mysql')
                    )
                );
            }
            
            // Log analytics
            $this->log_ticket_metric('status_changed', $ticket_id, array(
                'old_status' => $old_status,
                'new_status' => $new_status
            ));
            
            // Trigger email notification
            do_action('elc_ticket_status_changed', $ticket_id, $old_status, $new_status);
            
            wp_send_json_success(array('message' => __('Ticket status updated successfully.', 'environmental-live-chat')));
            
        } else {
            wp_send_json_error(array('message' => __('Failed to update ticket status.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Assign ticket to agent
     */
    public function assign_ticket() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $agent_id = intval($_POST['agent_id'] ?? 0);
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'environmental-live-chat')));
        }
        
        // Verify agent exists if provided
        if ($agent_id && !get_user_by('ID', $agent_id)) {
            wp_send_json_error(array('message' => __('Invalid agent.', 'environmental-live-chat')));
        }
        
        $updated = $wpdb->update(
            $this->table_tickets,
            array(
                'assigned_to' => $agent_id ?: null,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $ticket_id)
        );
        
        if ($updated !== false) {
            // Log analytics
            $this->log_ticket_metric('ticket_assigned', $ticket_id, array(
                'agent_id' => $agent_id
            ));
            
            $message = $agent_id 
                ? __('Ticket assigned successfully.', 'environmental-live-chat')
                : __('Ticket unassigned successfully.', 'environmental-live-chat');
                
            wp_send_json_success(array('message' => $message));
            
        } else {
            wp_send_json_error(array('message' => __('Failed to assign ticket.', 'environmental-live-chat')));
        }
    }
    
    /**
     * Get tickets with filters
     */
    public function get_tickets() {
        check_ajax_referer('elc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'environmental-live-chat')));
        }
        
        global $wpdb;
        
        $status = sanitize_text_field($_POST['status'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $assigned_to = intval($_POST['assigned_to'] ?? 0);
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $where_conditions = array();
        $params = array();
        
        if (!empty($status) && $status !== 'all') {
            $where_conditions[] = "status = %s";
            $params[] = $status;
        }
        
        if (!empty($category) && $category !== 'all') {
            $where_conditions[] = "category = %s";
            $params[] = $category;
        }
        
        if ($assigned_to > 0) {
            $where_conditions[] = "assigned_to = %d";
            $params[] = $assigned_to;
        } elseif ($assigned_to === -1) {
            $where_conditions[] = "assigned_to IS NULL";
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(subject LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s OR ticket_number LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, array($search_term, $search_term, $search_term, $search_term));
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get tickets
        $tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name as assigned_agent_name,
                    (SELECT COUNT(*) FROM {$this->table_replies} r WHERE r.ticket_id = t.id) as reply_count,
                    (SELECT MAX(created_at) FROM {$this->table_replies} r WHERE r.ticket_id = t.id) as last_reply_date
             FROM {$this->table_tickets} t
             LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
             {$where_clause}
             ORDER BY t.created_at DESC
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        ));
        
        // Get total count
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_tickets} t {$where_clause}",
            $params
        ));
        
        wp_send_json_success(array(
            'tickets' => $tickets,
            'total' => intval($total_count),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        ));
    }
    
    /**
     * Get ticket details with replies
     */
    public function get_ticket_details() {
        check_ajax_referer('elc_ticket_nonce', 'nonce');
        
        global $wpdb;
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $is_customer = !current_user_can('manage_options');
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'environmental-live-chat')));
        }
        
        // Get ticket
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket not found.', 'environmental-live-chat')));
        }
        
        // For customers, verify email access
        if ($is_customer) {
            $customer_email = sanitize_email($_POST['customer_email'] ?? '');
            if ($customer_email !== $ticket->customer_email) {
                wp_send_json_error(array('message' => __('Access denied.', 'environmental-live-chat')));
            }
        }
        
        // Get replies
        $replies_query = "SELECT * FROM {$this->table_replies} WHERE ticket_id = %d";
        if ($is_customer) {
            $replies_query .= " AND is_internal_note = 0"; // Hide internal notes from customers
        }
        $replies_query .= " ORDER BY created_at ASC";
        
        $replies = $wpdb->get_results($wpdb->prepare($replies_query, $ticket_id));
        
        wp_send_json_success(array(
            'ticket' => $ticket,
            'replies' => $replies
        ));
    }
    
    /**
     * Handle ticket attachment upload
     */
    public function handle_ticket_attachment() {
        check_ajax_referer('elc_ticket_nonce', 'nonce');
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        
        if (!$ticket_id) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'environmental-live-chat')));
        }
        
        if (!isset($_FILES['attachment'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'environmental-live-chat')));
        }
        
        // Verify ticket exists
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) {
            wp_send_json_error(array('message' => __('Ticket not found.', 'environmental-live-chat')));
        }
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip');
        $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            wp_send_json_error(array('message' => __('File type not allowed.', 'environmental-live-chat')));
        }
        
        if ($_FILES['attachment']['size'] > 10 * 1024 * 1024) { // 10MB limit
            wp_send_json_error(array('message' => __('File size too large. Maximum 10MB allowed.', 'environmental-live-chat')));
        }
        
        // Upload file
        $upload = wp_handle_upload($_FILES['attachment'], array('test_form' => false));
        
        if ($upload && !isset($upload['error'])) {
            wp_send_json_success(array(
                'file_url' => $upload['url'],
                'file_name' => basename($upload['file']),
                'file_size' => $_FILES['attachment']['size']
            ));
        } else {
            wp_send_json_error(array('message' => $upload['error'] ?? __('Upload failed.', 'environmental-live-chat')));
        }
    }
    
    // Helper Methods
    
    private function generate_ticket_number() {
        $prefix = get_option('elc_ticket_prefix', 'ELC');
        $counter = get_option('elc_ticket_counter', 1000);
        
        $ticket_number = $prefix . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
        
        update_option('elc_ticket_counter', $counter + 1);
        
        return $ticket_number;
    }
    
    private function get_ticket($ticket_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, u.display_name as assigned_agent_name, u.user_email as assigned_agent_email
             FROM {$this->table_tickets} t
             LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
             WHERE t.id = %d",
            $ticket_id
        ));
    }
    
    private function auto_assign_ticket($ticket_id, $category) {
        global $wpdb;
        
        // Get agents with least workload in this category
        $agents = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name,
                    (SELECT COUNT(*) FROM {$this->table_tickets} t 
                     WHERE t.assigned_to = u.ID AND t.status IN ('open', 'in-progress', 'customer-reply')) as active_tickets
             FROM {$wpdb->users} u
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = 'elc_agent_categories'
             AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)
             AND u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'elc_agent_status' AND meta_value = 'available')
             ORDER BY active_tickets ASC
             LIMIT 1",
            '%' . $category . '%',
            '%all%'
        ));
        
        return !empty($agents) ? $agents[0]->ID : null;
    }
    
    private function log_ticket_metric($metric_type, $ticket_id, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_analytics,
            array(
                'metric_type' => $metric_type,
                'metric_value' => $ticket_id,
                'metadata' => json_encode(array_merge($metadata, array('ticket_id' => $ticket_id))),
                'recorded_at' => current_time('mysql')
            )
        );
    }
    
    // Email Methods
    
    public function send_ticket_created_email($ticket_id, $ticket_data) {
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) return;
        
        // Email to customer
        $customer_subject = sprintf(__('[Ticket #%s] Support Request Received', 'environmental-live-chat'), $ticket->ticket_number);
        $customer_message = sprintf(
            __("Dear %s,\n\nThank you for contacting our support team. We have received your request and assigned ticket number #%s.\n\nSubject: %s\n\nWe will respond to your inquiry within 24 hours. You can track the status of your ticket using the ticket number provided.\n\nBest regards,\nSupport Team", 'environmental-live-chat'),
            $ticket->customer_name,
            $ticket->ticket_number,
            $ticket->subject
        );
        
        wp_mail($ticket->customer_email, $customer_subject, $customer_message);
        
        // Email to agents
        $admin_email = get_option('admin_email');
        $agent_subject = sprintf(__('[New Ticket #%s] %s', 'environmental-live-chat'), $ticket->ticket_number, $ticket->subject);
        $agent_message = sprintf(
            __("New support ticket created:\n\nTicket #: %s\nCustomer: %s <%s>\nSubject: %s\nCategory: %s\nPriority: %s\n\nMessage:\n%s\n\nLogin to admin panel to respond.", 'environmental-live-chat'),
            $ticket->ticket_number,
            $ticket->customer_name,
            $ticket->customer_email,
            $ticket->subject,
            $ticket->category,
            $ticket->priority,
            $ticket->message
        );
        
        wp_mail($admin_email, $agent_subject, $agent_message);
    }
    
    public function send_ticket_reply_email($ticket_id, $reply_id, $is_customer_reply) {
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) return;
        
        global $wpdb;
        $reply = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_replies} WHERE id = %d",
            $reply_id
        ));
        
        if (!$reply) return;
        
        if ($is_customer_reply) {
            // Notify agents of customer reply
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[Ticket #%s] Customer Reply', 'environmental-live-chat'), $ticket->ticket_number);
            $message = sprintf(
                __("Customer has replied to ticket #%s:\n\n%s\n\nLogin to admin panel to respond.", 'environmental-live-chat'),
                $ticket->ticket_number,
                $reply->message
            );
            
            wp_mail($admin_email, $subject, $message);
            
        } else {
            // Notify customer of agent reply
            $subject = sprintf(__('[Ticket #%s] Response from Support', 'environmental-live-chat'), $ticket->ticket_number);
            $message = sprintf(
                __("Dear %s,\n\nWe have responded to your support ticket #%s:\n\n%s\n\nIf you have any additional questions, please reply to this ticket.\n\nBest regards,\nSupport Team", 'environmental-live-chat'),
                $ticket->customer_name,
                $ticket->ticket_number,
                $reply->message
            );
            
            wp_mail($ticket->customer_email, $subject, $message);
        }
    }
    
    public function send_status_change_email($ticket_id, $old_status, $new_status) {
        $ticket = $this->get_ticket($ticket_id);
        if (!$ticket) return;
        
        // Only notify customer for significant status changes
        $notify_statuses = array('resolved', 'closed');
        if (!in_array($new_status, $notify_statuses)) return;
        
        $subject = sprintf(__('[Ticket #%s] Status Updated: %s', 'environmental-live-chat'), $ticket->ticket_number, ucfirst($new_status));
        $message = sprintf(
            __("Dear %s,\n\nYour support ticket #%s has been updated.\n\nStatus: %s\n\nSubject: %s\n\n%s\n\nBest regards,\nSupport Team", 'environmental-live-chat'),
            $ticket->customer_name,
            $ticket->ticket_number,
            ucfirst($new_status),
            $ticket->subject,
            $new_status === 'resolved' ? 'If this resolves your issue, no further action is needed. If you need additional assistance, please reply to this ticket.' : 'Thank you for using our support services.'
        );
        
        wp_mail($ticket->customer_email, $subject, $message);
    }
    
    /**
     * Send ticket reminders for overdue tickets
     */
    public function send_ticket_reminders() {
        global $wpdb;
        
        $reminder_threshold = get_option('elc_ticket_reminder_hours', 48);
        
        $overdue_tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_tickets}
             WHERE status IN ('open', 'customer-reply')
             AND TIMESTAMPDIFF(HOUR, updated_at, NOW()) > %d
             AND (last_reminder_at IS NULL OR TIMESTAMPDIFF(HOUR, last_reminder_at, NOW()) > 24)",
            $reminder_threshold
        ));
        
        foreach ($overdue_tickets as $ticket) {
            $admin_email = get_option('admin_email');
            $subject = sprintf(__('[REMINDER] Overdue Ticket #%s', 'environmental-live-chat'), $ticket->ticket_number);
            $message = sprintf(
                __("Ticket #%s has been waiting for response for over %d hours.\n\nCustomer: %s\nSubject: %s\nStatus: %s\n\nPlease respond as soon as possible.", 'environmental-live-chat'),
                $ticket->ticket_number,
                $reminder_threshold,
                $ticket->customer_name,
                $ticket->subject,
                $ticket->status
            );
            
            wp_mail($admin_email, $subject, $message);
            
            // Update last reminder timestamp
            $wpdb->update(
                $this->table_tickets,
                array('last_reminder_at' => current_time('mysql')),
                array('id' => $ticket->id)
            );
        }
    }
    
    /**
     * Escalate overdue tickets
     */
    public function escalate_overdue_tickets() {
        global $wpdb;
        
        $escalation_threshold = get_option('elc_ticket_escalation_hours', 72);
        
        $overdue_tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_tickets}
             WHERE status IN ('open', 'customer-reply')
             AND priority != 'high'
             AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > %d",
            $escalation_threshold
        ));
        
        foreach ($overdue_tickets as $ticket) {
            // Escalate priority
            $wpdb->update(
                $this->table_tickets,
                array(
                    'priority' => 'high',
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $ticket->id)
            );
            
            // Log escalation
            $this->log_ticket_metric('ticket_escalated', $ticket->id, array(
                'escalation_reason' => 'overdue',
                'hours_overdue' => round((time() - strtotime($ticket->created_at)) / 3600)
            ));
        }
    }
    
    /**
     * Get ticket statistics
     */
    public function get_ticket_statistics($date_from = null, $date_to = null) {
        global $wpdb;
        
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        $stats = array();
        
        // Total tickets
        $stats['total_tickets'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Tickets by status
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY status",
            $date_from, $date_to
        ), OBJECT_K);
        
        $stats['by_status'] = $status_counts;
        
        // Average resolution time
        $stats['avg_resolution_time'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
             FROM {$this->table_tickets}
             WHERE status IN ('resolved', 'closed')
             AND DATE(created_at) BETWEEN %s AND %s
             AND resolved_at IS NOT NULL",
            $date_from, $date_to
        ));
        
        // Tickets by category
        $category_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT category, COUNT(*) as count
             FROM {$this->table_tickets}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY category
             ORDER BY count DESC",
            $date_from, $date_to
        ));
        
        $stats['by_category'] = $category_counts;
        
        // Response time (first reply)
        $stats['avg_first_response_time'] = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, r.created_at))
             FROM {$this->table_tickets} t
             JOIN {$this->table_replies} r ON t.id = r.ticket_id
             WHERE r.is_customer_reply = 0
             AND r.id = (SELECT MIN(id) FROM {$this->table_replies} WHERE ticket_id = t.id AND is_customer_reply = 0)
             AND DATE(t.created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        return $stats;
    }
}

// Initialize the support tickets system
Environmental_Support_Tickets::get_instance();
