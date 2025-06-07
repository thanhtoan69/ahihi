<?php
/**
 * Events Integration Class
 *
 * Handles integration with environmental events, seminars, workshops,
 * and educational programs for automated email campaigns and engagement tracking.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Events_Integration {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Database manager instance
     */
    private $db_manager;

    /**
     * Subscriber manager instance
     */
    private $subscriber_manager;

    /**
     * Campaign manager instance
     */
    private $campaign_manager;

    /**
     * Automation engine instance
     */
    private $automation_engine;

    /**
     * Analytics tracker instance
     */
    private $analytics_tracker;

    /**
     * Event post type
     */
    const EVENT_POST_TYPE = 'env_event';

    /**
     * Event registration meta key
     */
    const REGISTRATION_META_KEY = '_event_registrations';

    /**
     * Event environmental score meta key
     */
    const ENV_SCORE_META_KEY = '_environmental_impact_score';

    /**
     * Get instance
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
        $this->init_dependencies();
        $this->init_hooks();
    }

    /**
     * Initialize dependencies
     */
    private function init_dependencies() {
        $this->db_manager = EEM_Database_Manager::get_instance();
        $this->subscriber_manager = EEM_Subscriber_Manager::get_instance();
        $this->campaign_manager = EEM_Campaign_Manager::get_instance();
        $this->automation_engine = EEM_Automation_Engine::get_instance();
        $this->analytics_tracker = EEM_Analytics_Tracker::get_instance();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // WordPress hooks
        add_action('init', array($this, 'register_event_post_type'));
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
        add_action('save_post', array($this, 'save_event_meta'));

        // AJAX handlers
        add_action('wp_ajax_eem_register_for_event', array($this, 'handle_event_registration'));
        add_action('wp_ajax_nopriv_eem_register_for_event', array($this, 'handle_event_registration'));
        add_action('wp_ajax_eem_cancel_event_registration', array($this, 'handle_registration_cancellation'));
        add_action('wp_ajax_nopriv_eem_cancel_event_registration', array($this, 'handle_registration_cancellation'));

        // Automation triggers
        add_action('eem_event_registered', array($this, 'trigger_registration_automation'), 10, 2);
        add_action('eem_event_cancelled', array($this, 'trigger_cancellation_automation'), 10, 2);
        add_action('eem_event_reminder', array($this, 'trigger_reminder_automation'), 10, 2);
        add_action('eem_event_follow_up', array($this, 'trigger_follow_up_automation'), 10, 2);

        // Scheduled events
        add_action('eem_send_event_reminders', array($this, 'send_event_reminders'));
        add_action('eem_process_event_follow_ups', array($this, 'process_event_follow_ups'));

        // WP Cron schedules
        if (!wp_next_scheduled('eem_send_event_reminders')) {
            wp_schedule_event(time(), 'daily', 'eem_send_event_reminders');
        }
        if (!wp_next_scheduled('eem_process_event_follow_ups')) {
            wp_schedule_event(time(), 'daily', 'eem_process_event_follow_ups');
        }
    }

    /**
     * Register event post type
     */
    public function register_event_post_type() {
        $labels = array(
            'name'               => 'Environmental Events',
            'singular_name'      => 'Environmental Event',
            'menu_name'          => 'Events',
            'add_new'            => 'Add New Event',
            'add_new_item'       => 'Add New Environmental Event',
            'edit_item'          => 'Edit Event',
            'new_item'           => 'New Event',
            'view_item'          => 'View Event',
            'search_items'       => 'Search Events',
            'not_found'          => 'No events found',
            'not_found_in_trash' => 'No events found in trash'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'events'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true
        );

        register_post_type(self::EVENT_POST_TYPE, $args);

        // Register taxonomies
        register_taxonomy('event_category', self::EVENT_POST_TYPE, array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => 'Event Categories',
                'singular_name'     => 'Event Category',
                'search_items'      => 'Search Categories',
                'all_items'         => 'All Categories',
                'parent_item'       => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item'         => 'Edit Category',
                'update_item'       => 'Update Category',
                'add_new_item'      => 'Add New Category',
                'new_item_name'     => 'New Category Name',
                'menu_name'         => 'Categories',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-category'),
            'show_in_rest'      => true
        ));

        register_taxonomy('event_tag', self::EVENT_POST_TYPE, array(
            'hierarchical'      => false,
            'labels'            => array(
                'name'                       => 'Event Tags',
                'singular_name'              => 'Event Tag',
                'search_items'               => 'Search Tags',
                'popular_items'              => 'Popular Tags',
                'all_items'                  => 'All Tags',
                'edit_item'                  => 'Edit Tag',
                'update_item'                => 'Update Tag',
                'add_new_item'               => 'Add New Tag',
                'new_item_name'              => 'New Tag Name',
                'separate_items_with_commas' => 'Separate tags with commas',
                'add_or_remove_items'        => 'Add or remove tags',
                'choose_from_most_used'      => 'Choose from the most used tags',
                'menu_name'                  => 'Tags',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-tag'),
            'show_in_rest'      => true
        ));
    }

    /**
     * Add event meta boxes
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'event-details',
            'Event Details',
            array($this, 'render_event_details_meta_box'),
            self::EVENT_POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'event-email-settings',
            'Email Marketing Settings',
            array($this, 'render_event_email_settings_meta_box'),
            self::EVENT_POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'event-registrations',
            'Event Registrations',
            array($this, 'render_event_registrations_meta_box'),
            self::EVENT_POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * Render event details meta box
     */
    public function render_event_details_meta_box($post) {
        wp_nonce_field('save_event_meta', 'event_meta_nonce');

        $event_date = get_post_meta($post->ID, '_event_date', true);
        $event_time = get_post_meta($post->ID, '_event_time', true);
        $event_location = get_post_meta($post->ID, '_event_location', true);
        $event_capacity = get_post_meta($post->ID, '_event_capacity', true);
        $registration_deadline = get_post_meta($post->ID, '_registration_deadline', true);
        $event_price = get_post_meta($post->ID, '_event_price', true);
        $event_organizer = get_post_meta($post->ID, '_event_organizer', true);
        $event_contact = get_post_meta($post->ID, '_event_contact', true);
        $environmental_score = get_post_meta($post->ID, self::ENV_SCORE_META_KEY, true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="event_date">Event Date</label></th>
                <td><input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($event_date); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_time">Event Time</label></th>
                <td><input type="time" id="event_time" name="event_time" value="<?php echo esc_attr($event_time); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_location">Location</label></th>
                <td><textarea id="event_location" name="event_location" rows="3" class="large-text"><?php echo esc_textarea($event_location); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="event_capacity">Capacity</label></th>
                <td><input type="number" id="event_capacity" name="event_capacity" value="<?php echo esc_attr($event_capacity); ?>" class="small-text" min="1" /></td>
            </tr>
            <tr>
                <th><label for="registration_deadline">Registration Deadline</label></th>
                <td><input type="datetime-local" id="registration_deadline" name="registration_deadline" value="<?php echo esc_attr($registration_deadline); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_price">Price</label></th>
                <td><input type="number" id="event_price" name="event_price" value="<?php echo esc_attr($event_price); ?>" class="small-text" min="0" step="0.01" /> (0 for free)</td>
            </tr>
            <tr>
                <th><label for="event_organizer">Organizer</label></th>
                <td><input type="text" id="event_organizer" name="event_organizer" value="<?php echo esc_attr($event_organizer); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event_contact">Contact Information</label></th>
                <td><textarea id="event_contact" name="event_contact" rows="3" class="large-text"><?php echo esc_textarea($event_contact); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="environmental_score">Environmental Impact Score</label></th>
                <td>
                    <input type="number" id="environmental_score" name="environmental_score" value="<?php echo esc_attr($environmental_score); ?>" class="small-text" min="0" max="100" />
                    <p class="description">Score from 0-100 based on environmental benefit and sustainability impact</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render event email settings meta box
     */
    public function render_event_email_settings_meta_box($post) {
        $enable_confirmation = get_post_meta($post->ID, '_enable_confirmation_email', true);
        $enable_reminders = get_post_meta($post->ID, '_enable_reminder_emails', true);
        $reminder_days = get_post_meta($post->ID, '_reminder_days', true) ?: array(7, 1);
        $enable_follow_up = get_post_meta($post->ID, '_enable_follow_up_email', true);
        $follow_up_delay = get_post_meta($post->ID, '_follow_up_delay_days', true) ?: 1;

        ?>
        <p>
            <label>
                <input type="checkbox" name="enable_confirmation_email" value="1" <?php checked($enable_confirmation, 1); ?> />
                Send confirmation email upon registration
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="enable_reminder_emails" value="1" <?php checked($enable_reminders, 1); ?> />
                Send reminder emails
            </label>
        </p>
        <div id="reminder-settings" style="margin-left: 20px; <?php echo $enable_reminders ? '' : 'display:none;'; ?>">
            <p>
                <label>Send reminders:</label><br>
                <?php
                $days_options = array(30, 14, 7, 3, 1);
                foreach ($days_options as $days) {
                    $checked = in_array($days, (array)$reminder_days) ? 'checked' : '';
                    echo "<label><input type='checkbox' name='reminder_days[]' value='$days' $checked /> $days day(s) before</label><br>";
                }
                ?>
            </p>
        </div>
        <p>
            <label>
                <input type="checkbox" name="enable_follow_up_email" value="1" <?php checked($enable_follow_up, 1); ?> />
                Send follow-up email after event
            </label>
        </p>
        <div id="follow-up-settings" style="margin-left: 20px; <?php echo $enable_follow_up ? '' : 'display:none;'; ?>">
            <p>
                <label>Days after event:</label>
                <input type="number" name="follow_up_delay_days" value="<?php echo esc_attr($follow_up_delay); ?>" min="1" max="30" class="small-text" />
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="enable_reminder_emails"]').change(function() {
                $('#reminder-settings').toggle(this.checked);
            });
            $('input[name="enable_follow_up_email"]').change(function() {
                $('#follow-up-settings').toggle(this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Render event registrations meta box
     */
    public function render_event_registrations_meta_box($post) {
        $registrations = $this->get_event_registrations($post->ID);
        $capacity = get_post_meta($post->ID, '_event_capacity', true);
        $registration_count = count($registrations);

        ?>
        <div class="event-registration-stats">
            <p><strong>Registrations: <?php echo $registration_count; ?></strong>
            <?php if ($capacity): ?>
                / <?php echo $capacity; ?> (<?php echo round(($registration_count / $capacity) * 100, 1); ?>% full)
            <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($registrations)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td><?php echo esc_html($registration->name); ?></td>
                            <td><?php echo esc_html($registration->email); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($registration->registration_date)); ?></td>
                            <td><?php echo ucfirst($registration->status); ?></td>
                            <td>
                                <button type="button" class="button button-small cancel-registration" 
                                        data-event-id="<?php echo $post->ID; ?>" 
                                        data-email="<?php echo esc_attr($registration->email); ?>">
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No registrations yet.</p>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            $('.cancel-registration').click(function() {
                if (confirm('Are you sure you want to cancel this registration?')) {
                    var eventId = $(this).data('event-id');
                    var email = $(this).data('email');
                    
                    $.post(ajaxurl, {
                        action: 'eem_admin_cancel_registration',
                        event_id: eventId,
                        email: email,
                        nonce: '<?php echo wp_create_nonce('eem_admin_cancel_registration'); ?>'
                    }, function() {
                        location.reload();
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save event meta
     */
    public function save_event_meta($post_id) {
        if (!isset($_POST['event_meta_nonce']) || !wp_verify_nonce($_POST['event_meta_nonce'], 'save_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== self::EVENT_POST_TYPE) {
            return;
        }

        // Save event details
        $fields = array(
            'event_date', 'event_time', 'event_location', 'event_capacity',
            'registration_deadline', 'event_price', 'event_organizer', 
            'event_contact', 'environmental_score'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save email settings
        update_post_meta($post_id, '_enable_confirmation_email', isset($_POST['enable_confirmation_email']) ? 1 : 0);
        update_post_meta($post_id, '_enable_reminder_emails', isset($_POST['enable_reminder_emails']) ? 1 : 0);
        update_post_meta($post_id, '_enable_follow_up_email', isset($_POST['enable_follow_up_email']) ? 1 : 0);

        if (isset($_POST['reminder_days']) && is_array($_POST['reminder_days'])) {
            update_post_meta($post_id, '_reminder_days', array_map('intval', $_POST['reminder_days']));
        }

        if (isset($_POST['follow_up_delay_days'])) {
            update_post_meta($post_id, '_follow_up_delay_days', intval($_POST['follow_up_delay_days']));
        }
    }

    /**
     * Handle event registration
     */
    public function handle_event_registration() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_event_registration')) {
            wp_die('Security check failed');
        }

        $event_id = intval($_POST['event_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);

        // Validate inputs
        if (!$event_id || !$name || !is_email($email)) {
            wp_send_json_error('Invalid input data');
        }

        // Check if event exists and is open for registration
        $event = get_post($event_id);
        if (!$event || $event->post_type !== self::EVENT_POST_TYPE) {
            wp_send_json_error('Event not found');
        }

        // Check registration deadline
        $deadline = get_post_meta($event_id, '_registration_deadline', true);
        if ($deadline && strtotime($deadline) < current_time('timestamp')) {
            wp_send_json_error('Registration deadline has passed');
        }

        // Check capacity
        $capacity = get_post_meta($event_id, '_event_capacity', true);
        if ($capacity) {
            $current_registrations = $this->get_event_registrations_count($event_id);
            if ($current_registrations >= $capacity) {
                wp_send_json_error('Event is fully booked');
            }
        }

        // Check if already registered
        if ($this->is_user_registered($event_id, $email)) {
            wp_send_json_error('You are already registered for this event');
        }

        // Register user
        $registration_id = $this->register_user_for_event($event_id, $name, $email, $phone);
        
        if ($registration_id) {
            // Trigger automation
            do_action('eem_event_registered', $event_id, $email);

            // Send confirmation email if enabled
            if (get_post_meta($event_id, '_enable_confirmation_email', true)) {
                $this->send_confirmation_email($event_id, $email, $name);
            }

            // Add environmental action score
            $this->add_environmental_action_score($email, 'event_registration', $event_id);

            wp_send_json_success('Registration successful');
        } else {
            wp_send_json_error('Registration failed');
        }
    }

    /**
     * Handle registration cancellation
     */
    public function handle_registration_cancellation() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_event_cancellation')) {
            wp_die('Security check failed');
        }

        $event_id = intval($_POST['event_id']);
        $email = sanitize_email($_POST['email']);

        if ($this->cancel_event_registration($event_id, $email)) {
            do_action('eem_event_cancelled', $event_id, $email);
            wp_send_json_success('Registration cancelled');
        } else {
            wp_send_json_error('Cancellation failed');
        }
    }

    /**
     * Register user for event
     */
    private function register_user_for_event($event_id, $name, $email, $phone = '') {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->insert(
            $table_name,
            array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'registration_date' => current_time('mysql'),
                'status' => 'confirmed'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Cancel event registration
     */
    private function cancel_event_registration($event_id, $email) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->update(
            $table_name,
            array('status' => 'cancelled'),
            array('event_id' => $event_id, 'email' => $email),
            array('%s'),
            array('%d', '%s')
        );
    }

    /**
     * Get event registrations
     */
    public function get_event_registrations($event_id) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d AND status = 'confirmed' ORDER BY registration_date DESC",
            $event_id
        ));
    }

    /**
     * Get event registrations count
     */
    public function get_event_registrations_count($event_id) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));
    }

    /**
     * Check if user is registered
     */
    private function is_user_registered($event_id, $email) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND email = %s AND status = 'confirmed'",
            $event_id, $email
        )) > 0;
    }

    /**
     * Send confirmation email
     */
    private function send_confirmation_email($event_id, $email, $name) {
        $event = get_post($event_id);
        $event_date = get_post_meta($event_id, '_event_date', true);
        $event_time = get_post_meta($event_id, '_event_time', true);
        $event_location = get_post_meta($event_id, '_event_location', true);

        $subject = 'Event Registration Confirmation - ' . $event->post_title;
        
        $message = "Dear $name,\n\n";
        $message .= "Thank you for registering for: {$event->post_title}\n\n";
        $message .= "Event Details:\n";
        $message .= "Date: " . date('F j, Y', strtotime($event_date)) . "\n";
        $message .= "Time: " . date('g:i A', strtotime($event_time)) . "\n";
        $message .= "Location: $event_location\n\n";
        $message .= "We look forward to seeing you there!\n\n";
        $message .= "Best regards,\nEnvironmental Events Team";

        wp_mail($email, $subject, $message);
    }

    /**
     * Send event reminders
     */
    public function send_event_reminders() {
        global $wpdb;
        
        // Get events with reminders enabled
        $events = get_posts(array(
            'post_type' => self::EVENT_POST_TYPE,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_enable_reminder_emails',
                    'value' => '1'
                )
            ),
            'numberposts' => -1
        ));

        foreach ($events as $event) {
            $event_date = get_post_meta($event->ID, '_event_date', true);
            $reminder_days = get_post_meta($event->ID, '_reminder_days', true) ?: array(7, 1);
            
            foreach ($reminder_days as $days) {
                $reminder_date = date('Y-m-d', strtotime($event_date . " -$days days"));
                
                if ($reminder_date === date('Y-m-d')) {
                    $this->send_reminder_emails($event->ID, $days);
                }
            }
        }
    }

    /**
     * Send reminder emails for specific event
     */
    private function send_reminder_emails($event_id, $days_before) {
        $registrations = $this->get_event_registrations($event_id);
        $event = get_post($event_id);
        
        foreach ($registrations as $registration) {
            do_action('eem_event_reminder', $event_id, $registration->email);
        }
    }

    /**
     * Process event follow-ups
     */
    public function process_event_follow_ups() {
        global $wpdb;
        
        // Get events with follow-up enabled that occurred in the past
        $events = get_posts(array(
            'post_type' => self::EVENT_POST_TYPE,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_enable_follow_up_email',
                    'value' => '1'
                ),
                array(
                    'key' => '_event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '<'
                )
            ),
            'numberposts' => -1
        ));

        foreach ($events as $event) {
            $event_date = get_post_meta($event->ID, '_event_date', true);
            $follow_up_delay = get_post_meta($event->ID, '_follow_up_delay_days', true) ?: 1;
            $follow_up_date = date('Y-m-d', strtotime($event_date . " +$follow_up_delay days"));
            
            if ($follow_up_date === date('Y-m-d')) {
                $registrations = $this->get_event_registrations($event->ID);
                
                foreach ($registrations as $registration) {
                    do_action('eem_event_follow_up', $event->ID, $registration->email);
                }
            }
        }
    }

    /**
     * Trigger registration automation
     */
    public function trigger_registration_automation($event_id, $email) {
        $this->automation_engine->process_trigger('event_registration', array(
            'email' => $email,
            'event_id' => $event_id,
            'event_title' => get_the_title($event_id)
        ));
    }

    /**
     * Trigger cancellation automation
     */
    public function trigger_cancellation_automation($event_id, $email) {
        $this->automation_engine->process_trigger('event_cancellation', array(
            'email' => $email,
            'event_id' => $event_id,
            'event_title' => get_the_title($event_id)
        ));
    }

    /**
     * Trigger reminder automation
     */
    public function trigger_reminder_automation($event_id, $email) {
        $this->automation_engine->process_trigger('event_reminder', array(
            'email' => $email,
            'event_id' => $event_id,
            'event_title' => get_the_title($event_id)
        ));
    }

    /**
     * Trigger follow-up automation
     */
    public function trigger_follow_up_automation($event_id, $email) {
        $this->automation_engine->process_trigger('event_follow_up', array(
            'email' => $email,
            'event_id' => $event_id,
            'event_title' => get_the_title($event_id)
        ));
    }

    /**
     * Add environmental action score
     */
    private function add_environmental_action_score($email, $action, $event_id) {
        $score = get_post_meta($event_id, self::ENV_SCORE_META_KEY, true) ?: 10;
        
        $this->analytics_tracker->track_environmental_action($email, $action, array(
            'event_id' => $event_id,
            'score' => $score
        ));
    }

    /**
     * Get events by category
     */
    public function get_events_by_category($category, $limit = 10) {
        return get_posts(array(
            'post_type' => self::EVENT_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'tax_query' => array(
                array(
                    'taxonomy' => 'event_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>='
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_date',
            'order' => 'ASC'
        ));
    }

    /**
     * Get upcoming events
     */
    public function get_upcoming_events($limit = 10) {
        return get_posts(array(
            'post_type' => self::EVENT_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>='
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_event_date',
            'order' => 'ASC'
        ));
    }

    /**
     * Get user event registrations
     */
    public function get_user_registrations($email) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s AND status = 'confirmed' ORDER BY registration_date DESC",
            $email
        ));
    }

    /**
     * Create event registration table
     */
    public function create_event_registration_table() {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('event_registrations');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            registration_date datetime NOT NULL,
            status varchar(20) DEFAULT 'confirmed',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY email (email),
            KEY status (status),
            UNIQUE KEY unique_registration (event_id, email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the integration
EEM_Events_Integration::get_instance();
