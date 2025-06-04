<?php
/**
 * Plugin Name: Environmental Platform Events
 * Plugin URI: https://environmental-platform.local
 * Description: Comprehensive event management system for Environmental Platform with registration, calendar view, QR codes, and Google Calendar integration.
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: ep-events
 * Domain Path: /languages
 * 
 * Phase 34: Event Management System
 * Features:
 * - Custom event post type with advanced fields
 * - Event registration and ticketing system
 * - Calendar view and event filtering
 * - QR code generation for event check-ins
 * - Google Calendar integration
 * - Event analytics and reporting
 * - Mobile-responsive design
 * - Environmental impact tracking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EP_EVENTS_VERSION', '1.0.0');
define('EP_EVENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EP_EVENTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EP_EVENTS_TEXT_DOMAIN', 'ep-events');

/**
 * Main plugin class
 */
class Environmental_Platform_Events {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize core functionality
        $this->register_post_types();
        $this->register_taxonomies();
        $this->setup_meta_boxes();
        $this->setup_admin_pages();
        $this->setup_ajax_handlers();
        $this->setup_shortcodes();
        $this->enqueue_scripts();
        $this->setup_url_rewrites();
        $this->setup_template_loading();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(EP_EVENTS_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create custom tables
        $this->create_custom_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Environmental Platform Events Plugin Activated');
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Environmental Platform Events Plugin Deactivated');
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Event post type
        register_post_type('ep_event', array(
            'labels' => array(
                'name' => __('Events', EP_EVENTS_TEXT_DOMAIN),
                'singular_name' => __('Event', EP_EVENTS_TEXT_DOMAIN),
                'menu_name' => __('Events', EP_EVENTS_TEXT_DOMAIN),
                'add_new' => __('Add New Event', EP_EVENTS_TEXT_DOMAIN),
                'add_new_item' => __('Add New Event', EP_EVENTS_TEXT_DOMAIN),
                'edit_item' => __('Edit Event', EP_EVENTS_TEXT_DOMAIN),
                'new_item' => __('New Event', EP_EVENTS_TEXT_DOMAIN),
                'view_item' => __('View Event', EP_EVENTS_TEXT_DOMAIN),
                'search_items' => __('Search Events', EP_EVENTS_TEXT_DOMAIN),
                'not_found' => __('No events found', EP_EVENTS_TEXT_DOMAIN),
                'not_found_in_trash' => __('No events found in trash', EP_EVENTS_TEXT_DOMAIN),
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'rewrite' => array('slug' => 'events', 'with_front' => false),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
        ));
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Event Categories
        register_taxonomy('event_category', 'ep_event', array(
            'labels' => array(
                'name' => __('Event Categories', EP_EVENTS_TEXT_DOMAIN),
                'singular_name' => __('Event Category', EP_EVENTS_TEXT_DOMAIN),
                'menu_name' => __('Categories', EP_EVENTS_TEXT_DOMAIN),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'event-category'),
        ));
        
        // Event Tags
        register_taxonomy('event_tag', 'ep_event', array(
            'labels' => array(
                'name' => __('Event Tags', EP_EVENTS_TEXT_DOMAIN),
                'singular_name' => __('Event Tag', EP_EVENTS_TEXT_DOMAIN),
                'menu_name' => __('Tags', EP_EVENTS_TEXT_DOMAIN),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'event-tag'),
        ));
        
        // Event Types
        register_taxonomy('event_type', 'ep_event', array(
            'labels' => array(
                'name' => __('Event Types', EP_EVENTS_TEXT_DOMAIN),
                'singular_name' => __('Event Type', EP_EVENTS_TEXT_DOMAIN),
                'menu_name' => __('Types', EP_EVENTS_TEXT_DOMAIN),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'event-type'),
        ));
    }
    
    /**
     * Setup meta boxes
     */
    public function setup_meta_boxes() {
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
        add_action('save_post', array($this, 'save_event_meta_data'));
    }
    
    public function add_event_meta_boxes() {
        add_meta_box(
            'ep_event_details',
            __('Event Details', EP_EVENTS_TEXT_DOMAIN),
            array($this, 'render_event_details_meta_box'),
            'ep_event',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ep_event_location',
            __('Event Location', EP_EVENTS_TEXT_DOMAIN),
            array($this, 'render_event_location_meta_box'),
            'ep_event',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ep_event_registration',
            __('Registration Settings', EP_EVENTS_TEXT_DOMAIN),
            array($this, 'render_event_registration_meta_box'),
            'ep_event',
            'side',
            'default'
        );
        
        add_meta_box(
            'ep_event_environmental',
            __('Environmental Impact', EP_EVENTS_TEXT_DOMAIN),
            array($this, 'render_event_environmental_meta_box'),
            'ep_event',
            'side',
            'default'
        );
    }
    
    public function render_event_details_meta_box($post) {
        wp_nonce_field('ep_event_meta_nonce', 'ep_event_meta_nonce');
        
        $start_date = get_post_meta($post->ID, '_ep_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_ep_event_end_date', true);
        $start_time = get_post_meta($post->ID, '_ep_event_start_time', true);
        $end_time = get_post_meta($post->ID, '_ep_event_end_time', true);
        $event_mode = get_post_meta($post->ID, '_ep_event_mode', true);
        $organizer = get_post_meta($post->ID, '_ep_event_organizer', true);
        $organizer_email = get_post_meta($post->ID, '_ep_event_organizer_email', true);
        $organizer_phone = get_post_meta($post->ID, '_ep_event_organizer_phone', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ep_event_start_date"><?php _e('Start Date', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="date" id="ep_event_start_date" name="ep_event_start_date" value="<?php echo esc_attr($start_date); ?>" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_end_date"><?php _e('End Date', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="date" id="ep_event_end_date" name="ep_event_end_date" value="<?php echo esc_attr($end_date); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_start_time"><?php _e('Start Time', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="time" id="ep_event_start_time" name="ep_event_start_time" value="<?php echo esc_attr($start_time); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_end_time"><?php _e('End Time', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="time" id="ep_event_end_time" name="ep_event_end_time" value="<?php echo esc_attr($end_time); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_mode"><?php _e('Event Mode', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select id="ep_event_mode" name="ep_event_mode">
                        <option value="offline" <?php selected($event_mode, 'offline'); ?>><?php _e('Offline/In-person', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="online" <?php selected($event_mode, 'online'); ?>><?php _e('Online/Virtual', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="hybrid" <?php selected($event_mode, 'hybrid'); ?>><?php _e('Hybrid', EP_EVENTS_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_organizer"><?php _e('Organizer Name', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="text" id="ep_event_organizer" name="ep_event_organizer" value="<?php echo esc_attr($organizer); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_organizer_email"><?php _e('Organizer Email', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="email" id="ep_event_organizer_email" name="ep_event_organizer_email" value="<?php echo esc_attr($organizer_email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_organizer_phone"><?php _e('Organizer Phone', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="tel" id="ep_event_organizer_phone" name="ep_event_organizer_phone" value="<?php echo esc_attr($organizer_phone); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    public function render_event_location_meta_box($post) {
        $venue_name = get_post_meta($post->ID, '_ep_event_venue_name', true);
        $venue_address = get_post_meta($post->ID, '_ep_event_venue_address', true);
        $online_link = get_post_meta($post->ID, '_ep_event_online_link', true);
        $latitude = get_post_meta($post->ID, '_ep_event_latitude', true);
        $longitude = get_post_meta($post->ID, '_ep_event_longitude', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ep_event_venue_name"><?php _e('Venue Name', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="text" id="ep_event_venue_name" name="ep_event_venue_name" value="<?php echo esc_attr($venue_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_venue_address"><?php _e('Venue Address', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><textarea id="ep_event_venue_address" name="ep_event_venue_address" rows="3" class="large-text"><?php echo esc_textarea($venue_address); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_online_link"><?php _e('Online Meeting Link', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="url" id="ep_event_online_link" name="ep_event_online_link" value="<?php echo esc_attr($online_link); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_latitude"><?php _e('Latitude', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_latitude" name="ep_event_latitude" value="<?php echo esc_attr($latitude); ?>" step="any" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_longitude"><?php _e('Longitude', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_longitude" name="ep_event_longitude" value="<?php echo esc_attr($longitude); ?>" step="any" class="regular-text" /></td>
            </tr>
        </table>
        <p class="description"><?php _e('Click on the map or enter coordinates manually to set the event location.', EP_EVENTS_TEXT_DOMAIN); ?></p>
        <div id="ep-event-map" style="height: 300px; width: 100%; margin-top: 10px;"></div>
        <?php
    }
    
    public function render_event_registration_meta_box($post) {
        $registration_required = get_post_meta($post->ID, '_ep_event_registration_required', true);
        $max_participants = get_post_meta($post->ID, '_ep_event_max_participants', true);
        $registration_fee = get_post_meta($post->ID, '_ep_event_registration_fee', true);
        $registration_deadline = get_post_meta($post->ID, '_ep_event_registration_deadline', true);
        $external_registration_url = get_post_meta($post->ID, '_ep_event_external_registration_url', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Registration Required', EP_EVENTS_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ep_event_registration_required" value="1" <?php checked($registration_required, '1'); ?> />
                        <?php _e('Require registration for this event', EP_EVENTS_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_max_participants"><?php _e('Max Participants', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_max_participants" name="ep_event_max_participants" value="<?php echo esc_attr($max_participants); ?>" min="1" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_registration_fee"><?php _e('Registration Fee (VND)', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_registration_fee" name="ep_event_registration_fee" value="<?php echo esc_attr($registration_fee); ?>" min="0" step="1000" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_registration_deadline"><?php _e('Registration Deadline', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="datetime-local" id="ep_event_registration_deadline" name="ep_event_registration_deadline" value="<?php echo esc_attr($registration_deadline); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_external_registration_url"><?php _e('External Registration URL', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="url" id="ep_event_external_registration_url" name="ep_event_external_registration_url" value="<?php echo esc_attr($external_registration_url); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    public function render_event_environmental_meta_box($post) {
        $carbon_footprint = get_post_meta($post->ID, '_ep_event_carbon_footprint', true);
        $sustainability_goal = get_post_meta($post->ID, '_ep_event_sustainability_goal', true);
        $eco_points_reward = get_post_meta($post->ID, '_ep_event_eco_points_reward', true);
        $environmental_impact = get_post_meta($post->ID, '_ep_event_environmental_impact', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ep_event_carbon_footprint"><?php _e('Expected Carbon Footprint (kg CO2)', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_carbon_footprint" name="ep_event_carbon_footprint" value="<?php echo esc_attr($carbon_footprint); ?>" step="0.1" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_sustainability_goal"><?php _e('Sustainability Goal', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><textarea id="ep_event_sustainability_goal" name="ep_event_sustainability_goal" rows="3" class="large-text"><?php echo esc_textarea($sustainability_goal); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_eco_points_reward"><?php _e('Eco Points Reward', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td><input type="number" id="ep_event_eco_points_reward" name="ep_event_eco_points_reward" value="<?php echo esc_attr($eco_points_reward); ?>" min="0" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ep_event_environmental_impact"><?php _e('Environmental Impact', EP_EVENTS_TEXT_DOMAIN); ?></label></th>
                <td>
                    <select id="ep_event_environmental_impact" name="ep_event_environmental_impact">
                        <option value=""><?php _e('Select Impact Level', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="high" <?php selected($environmental_impact, 'high'); ?>><?php _e('High Positive Impact', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="medium" <?php selected($environmental_impact, 'medium'); ?>><?php _e('Medium Positive Impact', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="low" <?php selected($environmental_impact, 'low'); ?>><?php _e('Low Positive Impact', EP_EVENTS_TEXT_DOMAIN); ?></option>
                        <option value="neutral" <?php selected($environmental_impact, 'neutral'); ?>><?php _e('Neutral Impact', EP_EVENTS_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_event_meta_data($post_id) {
        if (!isset($_POST['ep_event_meta_nonce']) || !wp_verify_nonce($_POST['ep_event_meta_nonce'], 'ep_event_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'ep_event') {
            return;
        }
        
        // Save event details
        $fields = array(
            'ep_event_start_date',
            'ep_event_end_date',
            'ep_event_start_time',
            'ep_event_end_time',
            'ep_event_mode',
            'ep_event_organizer',
            'ep_event_organizer_email',
            'ep_event_organizer_phone',
            'ep_event_venue_name',
            'ep_event_venue_address',
            'ep_event_online_link',
            'ep_event_latitude',
            'ep_event_longitude',
            'ep_event_registration_required',
            'ep_event_max_participants',
            'ep_event_registration_fee',
            'ep_event_registration_deadline',
            'ep_event_external_registration_url',
            'ep_event_carbon_footprint',
            'ep_event_sustainability_goal',
            'ep_event_eco_points_reward',
            'ep_event_environmental_impact'
        );
        
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            update_post_meta($post_id, '_' . $field, $value);
        }
        
        // Generate event slug for original events table integration
        $slug = sanitize_title(get_the_title($post_id));
        update_post_meta($post_id, '_ep_event_slug', $slug);
        
        // Sync with original events table
        $this->sync_with_original_events_table($post_id);
    }
    
    /**
     * Setup admin pages
     */
    public function setup_admin_pages() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
    }
    
    public function add_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=ep_event',
            __('Event Dashboard', EP_EVENTS_TEXT_DOMAIN),
            __('Dashboard', EP_EVENTS_TEXT_DOMAIN),
            'manage_options',
            'ep-events-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=ep_event',
            __('Event Registrations', EP_EVENTS_TEXT_DOMAIN),
            __('Registrations', EP_EVENTS_TEXT_DOMAIN),
            'manage_options',
            'ep-events-registrations',
            array($this, 'render_registrations_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=ep_event',
            __('Event Analytics', EP_EVENTS_TEXT_DOMAIN),
            __('Analytics', EP_EVENTS_TEXT_DOMAIN),
            'manage_options',
            'ep-events-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=ep_event',
            __('Event Settings', EP_EVENTS_TEXT_DOMAIN),
            __('Settings', EP_EVENTS_TEXT_DOMAIN),
            'manage_options',
            'ep-events-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Event Dashboard', EP_EVENTS_TEXT_DOMAIN); ?></h1>
            
            <div class="ep-dashboard-stats">
                <div class="ep-stat-card">
                    <h3><?php _e('Total Events', EP_EVENTS_TEXT_DOMAIN); ?></h3>
                    <p class="ep-stat-number"><?php echo wp_count_posts('ep_event')->publish; ?></p>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Upcoming Events', EP_EVENTS_TEXT_DOMAIN); ?></h3>
                    <p class="ep-stat-number"><?php echo $this->get_upcoming_events_count(); ?></p>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Total Registrations', EP_EVENTS_TEXT_DOMAIN); ?></h3>
                    <p class="ep-stat-number"><?php echo $this->get_total_registrations_count(); ?></p>
                </div>
                
                <div class="ep-stat-card">
                    <h3><?php _e('Revenue This Month', EP_EVENTS_TEXT_DOMAIN); ?></h3>
                    <p class="ep-stat-number"><?php echo number_format($this->get_monthly_revenue()); ?> VND</p>
                </div>
            </div>
            
            <div class="ep-dashboard-charts">
                <div class="ep-chart-container">
                    <h2><?php _e('Event Registrations Over Time', EP_EVENTS_TEXT_DOMAIN); ?></h2>
                    <canvas id="ep-registrations-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="ep-chart-container">
                    <h2><?php _e('Events by Category', EP_EVENTS_TEXT_DOMAIN); ?></h2>
                    <canvas id="ep-categories-chart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="ep-recent-events">
                <h2><?php _e('Recent Events', EP_EVENTS_TEXT_DOMAIN); ?></h2>
                <?php $this->render_recent_events_table(); ?>
            </div>
        </div>
        
        <style>
        .ep-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .ep-stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .ep-stat-card h3 {
            margin: 0 0 10px 0;
            color: #2E7D4A;
        }
        .ep-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            margin: 0;
        }
        .ep-dashboard-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .ep-chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ep-recent-events {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Setup AJAX handlers
     */
    public function setup_ajax_handlers() {
        add_action('wp_ajax_ep_register_for_event', array($this, 'handle_event_registration'));
        add_action('wp_ajax_nopriv_ep_register_for_event', array($this, 'handle_event_registration'));
        add_action('wp_ajax_ep_cancel_registration', array($this, 'handle_cancel_registration'));
        add_action('wp_ajax_ep_check_in_attendee', array($this, 'handle_check_in_attendee'));
        add_action('wp_ajax_ep_get_event_calendar', array($this, 'handle_get_event_calendar'));
        add_action('wp_ajax_nopriv_ep_get_event_calendar', array($this, 'handle_get_event_calendar'));
    }
    
    public function handle_event_registration() {
        check_ajax_referer('ep_events_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(__('You must be logged in to register for events.', EP_EVENTS_TEXT_DOMAIN));
        }
        
        // Check if registration is still open
        $registration_deadline = get_post_meta($event_id, '_ep_event_registration_deadline', true);
        if ($registration_deadline && strtotime($registration_deadline) < current_time('timestamp')) {
            wp_send_json_error(__('Registration deadline has passed.', EP_EVENTS_TEXT_DOMAIN));
        }
        
        // Check if event is full
        $max_participants = get_post_meta($event_id, '_ep_event_max_participants', true);
        $current_registrations = $this->get_event_registrations_count($event_id);
        
        if ($max_participants && $current_registrations >= $max_participants) {
            wp_send_json_error(__('This event is full.', EP_EVENTS_TEXT_DOMAIN));
        }
        
        // Check if user is already registered
        if ($this->is_user_registered($event_id, $user_id)) {
            wp_send_json_error(__('You are already registered for this event.', EP_EVENTS_TEXT_DOMAIN));
        }
        
        // Register user
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'ep_event_registrations',
            array(
                'event_id' => $event_id,
                'user_id' => $user_id,
                'registration_date' => current_time('mysql'),
                'status' => 'registered'
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            // Award eco points
            $eco_points = get_post_meta($event_id, '_ep_event_eco_points_reward', true);
            if ($eco_points) {
                $this->award_eco_points($user_id, $eco_points, 'event_registration');
            }
            
            // Send confirmation email
            $this->send_registration_confirmation_email($event_id, $user_id);
            
            wp_send_json_success(__('Registration successful!', EP_EVENTS_TEXT_DOMAIN));
        } else {
            wp_send_json_error(__('Registration failed. Please try again.', EP_EVENTS_TEXT_DOMAIN));
        }
    }
    
    /**
     * Setup shortcodes
     */
    public function setup_shortcodes() {
        add_shortcode('ep_events_calendar', array($this, 'render_events_calendar_shortcode'));
        add_shortcode('ep_upcoming_events', array($this, 'render_upcoming_events_shortcode'));
        add_shortcode('ep_event_registration', array($this, 'render_event_registration_shortcode'));
        add_shortcode('ep_my_events', array($this, 'render_my_events_shortcode'));
    }
    
    public function render_events_calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'month',
            'category' => '',
            'limit' => -1
        ), $atts);
        
        ob_start();
        ?>
        <div id="ep-events-calendar" class="ep-calendar-container">
            <div class="calendar-header">
                <button type="button" id="prev-month" class="calendar-nav">&lt;</button>
                <h3 id="current-month-year"></h3>
                <button type="button" id="next-month" class="calendar-nav">&gt;</button>
            </div>
            <div class="calendar-views">
                <button type="button" class="view-btn active" data-view="month"><?php _e('Month', EP_EVENTS_TEXT_DOMAIN); ?></button>
                <button type="button" class="view-btn" data-view="week"><?php _e('Week', EP_EVENTS_TEXT_DOMAIN); ?></button>
                <button type="button" class="view-btn" data-view="list"><?php _e('List', EP_EVENTS_TEXT_DOMAIN); ?></button>
            </div>
            <div id="calendar-grid" class="calendar-grid"></div>
            <div id="calendar-list" class="calendar-list" style="display: none;"></div>
        </div>
        
        <style>
        .ep-calendar-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px 0;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .calendar-nav {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .calendar-nav:hover {
            background: #45a049;
        }
        .calendar-views {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .view-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            cursor: pointer;
            border-radius: 4px;
        }
        .view-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
        }
        .calendar-day {
            background: #fff;
            min-height: 100px;
            padding: 5px;
            position: relative;
        }
        .calendar-day.other-month {
            background: #f5f5f5;
            color: #999;
        }
        .event-item {
            background: #4CAF50;
            color: white;
            padding: 2px 5px;
            margin: 1px 0;
            border-radius: 3px;
            font-size: 0.8em;
            cursor: pointer;
        }
        .event-item:hover {
            background: #45a049;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('ep-events-frontend', EP_EVENTS_PLUGIN_URL . 'assets/css/frontend.css', array(), EP_EVENTS_VERSION);
        wp_enqueue_script('ep-events-frontend', EP_EVENTS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), EP_EVENTS_VERSION, true);
        
        wp_localize_script('ep-events-frontend', 'ep_events_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_events_nonce'),
            'strings' => array(
                'loading' => __('Loading...', EP_EVENTS_TEXT_DOMAIN),
                'error' => __('An error occurred. Please try again.', EP_EVENTS_TEXT_DOMAIN),
                'confirm_cancel' => __('Are you sure you want to cancel your registration?', EP_EVENTS_TEXT_DOMAIN),
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ep_event') === false && strpos($hook, 'ep-events') === false) {
            return;
        }
        
        wp_enqueue_style('ep-events-admin', EP_EVENTS_PLUGIN_URL . 'assets/css/admin.css', array(), EP_EVENTS_VERSION);
        wp_enqueue_script('ep-events-admin', EP_EVENTS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), EP_EVENTS_VERSION, true);
        
        // Google Maps API
        wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . get_option('ep_events_google_maps_api_key', '') . '&libraries=places', array(), null, true);
        
        // Chart.js for analytics
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
    }
    
    /**
     * Setup URL rewrites
     */
    public function setup_url_rewrites() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^events/calendar/?$', 'index.php?ep_events_calendar=1', 'top');
        add_rewrite_rule('^events/category/([^/]+)/?$', 'index.php?event_category=$matches[1]', 'top');
        add_rewrite_rule('^events/([^/]+)/register/?$', 'index.php?ep_event=$matches[1]&ep_action=register', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'ep_events_calendar';
        $vars[] = 'ep_action';
        return $vars;
    }
    
    /**
     * Setup template loading
     */
    public function setup_template_loading() {
        add_filter('template_include', array($this, 'load_custom_templates'));
    }
      public function load_custom_templates($template) {
        // Single event template
        if (is_singular('ep_event')) {
            $custom_template = EP_EVENTS_PLUGIN_DIR . 'templates/single-event.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // Events archive template
        if (is_post_type_archive('ep_event')) {
            $custom_template = EP_EVENTS_PLUGIN_DIR . 'templates/archive-events.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // Events calendar template
        if (get_query_var('ep_events_calendar')) {
            $custom_template = EP_EVENTS_PLUGIN_DIR . 'templates/calendar.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // Event category archive
        if (is_tax('event_category')) {
            $custom_template = EP_EVENTS_PLUGIN_DIR . 'templates/archive-events.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // Event location archive
        if (is_tax('event_location')) {
            $custom_template = EP_EVENTS_PLUGIN_DIR . 'templates/archive-events.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Create custom database tables
     */
    private function create_custom_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Event registrations table
        $table_name = $wpdb->prefix . 'ep_event_registrations';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'registered',
            payment_status varchar(20) DEFAULT 'pending',
            payment_amount decimal(10,2) DEFAULT 0.00,
            check_in_date datetime NULL,
            qr_code varchar(255) NULL,
            notes text NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_event (user_id, event_id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Event check-ins table
        $table_name = $wpdb->prefix . 'ep_event_checkins';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            registration_id bigint(20) unsigned NOT NULL,
            check_in_time datetime DEFAULT CURRENT_TIMESTAMP,
            check_in_method varchar(20) DEFAULT 'manual',
            checked_in_by bigint(20) unsigned NULL,
            notes text NULL,
            PRIMARY KEY (id),
            KEY registration_id (registration_id),
            KEY check_in_time (check_in_time)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Event analytics table
        $table_name = $wpdb->prefix . 'ep_event_analytics';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            metric_name varchar(50) NOT NULL,
            metric_value decimal(15,4) NOT NULL,
            recorded_date date NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_metric (event_id, metric_name, recorded_date),
            KEY event_id (event_id),
            KEY recorded_date (recorded_date)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Helper functions
     */
    private function get_upcoming_events_count() {
        $args = array(
            'post_type' => 'ep_event',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_ep_event_start_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'posts_per_page' => -1
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    private function get_total_registrations_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ep_event_registrations';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status != 'cancelled'");
    }
    
    private function get_monthly_revenue() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ep_event_registrations';
        $current_month = current_time('Y-m');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(payment_amount) FROM $table_name 
             WHERE payment_status = 'completed' 
             AND DATE_FORMAT(registration_date, '%%Y-%%m') = %s",
            $current_month
        ));
    }
    
    private function sync_with_original_events_table($post_id) {
        global $wpdb;
        
        // Get WordPress event data
        $post = get_post($post_id);
        $start_date = get_post_meta($post_id, '_ep_event_start_date', true);
        $start_time = get_post_meta($post_id, '_ep_event_start_time', true);
        $end_date = get_post_meta($post_id, '_ep_event_end_date', true);
        $end_time = get_post_meta($post_id, '_ep_event_end_time', true);
        
        // Format datetime
        $start_datetime = $start_date . ' ' . ($start_time ?: '00:00:00');
        $end_datetime = ($end_date ?: $start_date) . ' ' . ($end_time ?: '23:59:59');
        
        // Check if event exists in original table
        $original_event_id = get_post_meta($post_id, '_ep_original_event_id', true);
        
        if ($original_event_id) {
            // Update existing event
            $wpdb->update(
                'events',
                array(
                    'title' => $post->post_title,
                    'description' => $post->post_content,
                    'start_date' => $start_datetime,
                    'end_date' => $end_datetime,
                    'venue_name' => get_post_meta($post_id, '_ep_event_venue_name', true),
                    'venue_address' => get_post_meta($post_id, '_ep_event_venue_address', true),
                    'max_participants' => get_post_meta($post_id, '_ep_event_max_participants', true),
                    'points_reward' => get_post_meta($post_id, '_ep_event_eco_points_reward', true),
                ),
                array('event_id' => $original_event_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                array('%d')
            );
        } else {
            // Create new event in original table
            $result = $wpdb->insert(
                'events',
                array(
                    'title' => $post->post_title,
                    'slug' => $post->post_name,
                    'description' => $post->post_content,
                    'event_type' => 'workshop', // Default type
                    'organizer_id' => $post->post_author,
                    'start_date' => $start_datetime,
                    'end_date' => $end_datetime,
                    'venue_name' => get_post_meta($post_id, '_ep_event_venue_name', true),
                    'venue_address' => get_post_meta($post_id, '_ep_event_venue_address', true),
                    'max_participants' => get_post_meta($post_id, '_ep_event_max_participants', true),
                    'points_reward' => get_post_meta($post_id, '_ep_event_eco_points_reward', true),
                    'status' => $post->post_status === 'publish' ? 'published' : 'draft',
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
            );
            
            if ($result) {
                update_post_meta($post_id, '_ep_original_event_id', $wpdb->insert_id);
            }
        }
    }
    
    private function set_default_options() {
        add_option('ep_events_google_maps_api_key', '');
        add_option('ep_events_default_eco_points', 10);
        add_option('ep_events_registration_email_template', $this->get_default_email_template());
        add_option('ep_events_currency', 'VND');
        add_option('ep_events_timezone', 'Asia/Ho_Chi_Minh');
    }
    
    private function get_default_email_template() {
        return '<h2>Xác nhận đăng ký sự kiện</h2>
<p>Xin chào {user_name},</p>
<p>Bạn đã đăng ký thành công cho sự kiện: <strong>{event_title}</strong></p>
<p><strong>Thời gian:</strong> {event_date} {event_time}</p>
<p><strong>Địa điểm:</strong> {event_location}</p>
<p>Chúng tôi rất mong được gặp bạn tại sự kiện!</p>
<p>Trân trọng,<br>Đội ngũ Environmental Platform</p>';
    }
}

// Initialize the plugin
Environmental_Platform_Events::get_instance();
?>
