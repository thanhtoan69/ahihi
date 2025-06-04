<?php
/**
 * Plugin Name: Environmental Platform Forum System
 * Plugin URI: https://environmental-platform.com
 * Description: Custom forum system integration for Environmental Platform with gamification and moderation features
 * Version: 1.0.0
 * Author: Environmental Platform Team
 * License: GPL v2 or later
 * Text Domain: ep-forum
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EP_FORUM_VERSION', '1.0.0');
define('EP_FORUM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EP_FORUM_PLUGIN_URL', plugin_dir_url(__FILE__));

class EnvironmentalPlatformForum {    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('template_include', array($this, 'load_forum_templates'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // AJAX hooks
        add_action('wp_ajax_ep_create_topic', array($this, 'ajax_create_topic'));
        add_action('wp_ajax_ep_create_post', array($this, 'ajax_create_post'));
        add_action('wp_ajax_ep_moderate_content', array($this, 'ajax_moderate_content'));
        add_action('wp_ajax_nopriv_ep_create_topic', array($this, 'ajax_create_topic'));
        add_action('wp_ajax_nopriv_ep_create_post', array($this, 'ajax_create_post'));
        
        // Include migration class
        if (file_exists(plugin_dir_path(__FILE__) . 'migrate-forum-data.php')) {
            require_once plugin_dir_path(__FILE__) . 'migrate-forum-data.php';
        }
    }
    
    public function init() {
        $this->register_post_types();
        $this->register_taxonomies();
        $this->setup_rewrites();
    }
    
    public function activate() {
        $this->register_post_types();
        $this->register_taxonomies();
        $this->setup_rewrites();
        flush_rewrite_rules();
        $this->create_forum_pages();
        $this->setup_user_roles();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Register Custom Post Types for Forum System
     */
    public function register_post_types() {
        
        // Forum Post Type
        register_post_type('ep_forum', array(
            'labels' => array(
                'name' => __('Forums', 'ep-forum'),
                'singular_name' => __('Forum', 'ep-forum'),
                'add_new' => __('Add New Forum', 'ep-forum'),
                'add_new_item' => __('Add New Forum', 'ep-forum'),
                'edit_item' => __('Edit Forum', 'ep-forum'),
                'new_item' => __('New Forum', 'ep-forum'),
                'view_item' => __('View Forum', 'ep-forum'),
                'search_items' => __('Search Forums', 'ep-forum'),
                'not_found' => __('No forums found', 'ep-forum'),
                'not_found_in_trash' => __('No forums found in trash', 'ep-forum'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'forums'),
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 26,
            'capability_type' => 'post',
            'show_in_rest' => true,
        ));
        
        // Forum Topic Post Type
        register_post_type('ep_topic', array(
            'labels' => array(
                'name' => __('Topics', 'ep-forum'),
                'singular_name' => __('Topic', 'ep-forum'),
                'add_new' => __('Add New Topic', 'ep-forum'),
                'add_new_item' => __('Add New Topic', 'ep-forum'),
                'edit_item' => __('Edit Topic', 'ep-forum'),
                'new_item' => __('New Topic', 'ep-forum'),
                'view_item' => __('View Topic', 'ep-forum'),
                'search_items' => __('Search Topics', 'ep-forum'),
                'not_found' => __('No topics found', 'ep-forum'),
                'not_found_in_trash' => __('No topics found in trash', 'ep-forum'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'topics'),
            'supports' => array('title', 'editor', 'author', 'comments'),
            'menu_icon' => 'dashicons-format-chat',
            'capability_type' => 'post',
            'show_in_rest' => true,
        ));
        
        // Forum Reply Post Type
        register_post_type('ep_reply', array(
            'labels' => array(
                'name' => __('Replies', 'ep-forum'),
                'singular_name' => __('Reply', 'ep-forum'),
                'add_new' => __('Add New Reply', 'ep-forum'),
                'add_new_item' => __('Add New Reply', 'ep-forum'),
                'edit_item' => __('Edit Reply', 'ep-forum'),
                'new_item' => __('New Reply', 'ep-forum'),
                'view_item' => __('View Reply', 'ep-forum'),
                'search_items' => __('Search Replies', 'ep-forum'),
                'not_found' => __('No replies found', 'ep-forum'),
                'not_found_in_trash' => __('No replies found in trash', 'ep-forum'),
            ),
            'public' => true,
            'has_archive' => false,
            'rewrite' => array('slug' => 'replies'),
            'supports' => array('editor', 'author'),
            'menu_icon' => 'dashicons-format-aside',
            'capability_type' => 'post',
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Register Custom Taxonomies for Forum System
     */
    public function register_taxonomies() {
        
        // Forum Category Taxonomy
        register_taxonomy('forum_category', array('ep_forum', 'ep_topic'), array(
            'labels' => array(
                'name' => __('Forum Categories', 'ep-forum'),
                'singular_name' => __('Forum Category', 'ep-forum'),
                'search_items' => __('Search Categories', 'ep-forum'),
                'all_items' => __('All Categories', 'ep-forum'),
                'parent_item' => __('Parent Category', 'ep-forum'),
                'parent_item_colon' => __('Parent Category:', 'ep-forum'),
                'edit_item' => __('Edit Category', 'ep-forum'),
                'update_item' => __('Update Category', 'ep-forum'),
                'add_new_item' => __('Add New Category', 'ep-forum'),
                'new_item_name' => __('New Category Name', 'ep-forum'),
                'menu_name' => __('Categories', 'ep-forum'),
            ),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'forum-category'),
            'show_in_rest' => true,
        ));
        
        // Forum Tags Taxonomy
        register_taxonomy('forum_tag', array('ep_topic', 'ep_reply'), array(
            'labels' => array(
                'name' => __('Forum Tags', 'ep-forum'),
                'singular_name' => __('Forum Tag', 'ep-forum'),
                'search_items' => __('Search Tags', 'ep-forum'),
                'all_items' => __('All Tags', 'ep-forum'),
                'edit_item' => __('Edit Tag', 'ep-forum'),
                'update_item' => __('Update Tag', 'ep-forum'),
                'add_new_item' => __('Add New Tag', 'ep-forum'),
                'new_item_name' => __('New Tag Name', 'ep-forum'),
                'menu_name' => __('Tags', 'ep-forum'),
            ),
            'hierarchical' => false,
            'rewrite' => array('slug' => 'forum-tag'),
            'show_in_rest' => true,
        ));
        
        // Environmental Topic Taxonomy
        register_taxonomy('environmental_topic', array('ep_topic', 'ep_reply'), array(
            'labels' => array(
                'name' => __('Environmental Topics', 'ep-forum'),
                'singular_name' => __('Environmental Topic', 'ep-forum'),
                'search_items' => __('Search Environmental Topics', 'ep-forum'),
                'all_items' => __('All Environmental Topics', 'ep-forum'),
                'edit_item' => __('Edit Environmental Topic', 'ep-forum'),
                'update_item' => __('Update Environmental Topic', 'ep-forum'),
                'add_new_item' => __('Add New Environmental Topic', 'ep-forum'),
                'new_item_name' => __('New Environmental Topic Name', 'ep-forum'),
                'menu_name' => __('Environmental Topics', 'ep-forum'),
            ),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'environmental-topic'),
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Setup URL Rewrites for Forum System
     */
    public function setup_rewrites() {
        add_rewrite_rule('^forums/([^/]*)/page/?([0-9]{1,})/?$', 'index.php?post_type=ep_forum&name=$matches[1]&paged=$matches[2]', 'top');
        add_rewrite_rule('^topics/([^/]*)/page/?([0-9]{1,})/?$', 'index.php?post_type=ep_topic&name=$matches[1]&paged=$matches[2]', 'top');
        add_rewrite_rule('^forum/([^/]*)/topic/([^/]*)/?$', 'index.php?post_type=ep_topic&name=$matches[2]&forum=$matches[1]', 'top');
    }
    
    /**
     * Create Forum Pages
     */
    public function create_forum_pages() {
        // Main forum page
        $forum_page = array(
            'post_title' => 'Forum Cộng đồng Môi trường',
            'post_content' => 'Chào mừng bạn đến với Forum Cộng đồng Môi trường! Nơi chia sẻ kiến thức, kinh nghiệm về bảo vệ môi trường.',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'forum'
        );
        
        if (!get_page_by_path('forum')) {
            $forum_page_id = wp_insert_post($forum_page);
            update_option('ep_forum_page_id', $forum_page_id);
        }
        
        // Forum rules page
        $rules_page = array(
            'post_title' => 'Quy tắc Forum',
            'post_content' => '<h3>Quy tắc tham gia Forum Môi trường</h3>
                <ol>
                    <li>Tôn trọng các thành viên khác và không sử dụng ngôn từ thô tục</li>
                    <li>Chia sẻ nội dung tích cực về môi trường</li>
                    <li>Không spam hoặc quảng cáo không liên quan</li>
                    <li>Trích dẫn nguồn khi chia sẻ thông tin</li>
                    <li>Báo cáo nội dung vi phạm cho moderator</li>
                </ol>',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'forum-rules'
        );
        
        if (!get_page_by_path('forum-rules')) {
            wp_insert_post($rules_page);
        }
    }
    
    /**
     * Setup User Roles and Capabilities
     */
    public function setup_user_roles() {
        // Add forum moderator role
        add_role('forum_moderator', 'Forum Moderator', array(
            'read' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'delete_published_posts' => true,
            'moderate_comments' => true,
        ));
        
        // Add capabilities to existing roles
        $roles = array('administrator', 'editor');
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('manage_forum');
                $role->add_cap('moderate_forum');
                $role->add_cap('edit_forum_posts');
                $role->add_cap('delete_forum_posts');
            }
        }
        
        // Add basic forum capabilities to subscribers
        $subscriber = get_role('subscriber');
        if ($subscriber) {
            $subscriber->add_cap('create_forum_topics');
            $subscriber->add_cap('create_forum_replies');
            $subscriber->add_cap('edit_own_forum_posts');
        }
    }
    
    /**
     * Enqueue Frontend Scripts and Styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('ep-forum-style', EP_FORUM_PLUGIN_URL . 'assets/css/forum.css', array(), EP_FORUM_VERSION);
        wp_enqueue_script('ep-forum-script', EP_FORUM_PLUGIN_URL . 'assets/js/forum.js', array('jquery'), EP_FORUM_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ep-forum-script', 'ep_forum_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ep_forum_nonce'),
            'strings' => array(
                'loading' => __('Đang tải...', 'ep-forum'),
                'error' => __('Có lỗi xảy ra. Vui lòng thử lại.', 'ep-forum'),
                'success' => __('Thành công!', 'ep-forum'),
            )
        ));
    }
    
    /**
     * Enqueue Admin Scripts and Styles
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style('ep-forum-admin-style', EP_FORUM_PLUGIN_URL . 'assets/css/admin.css', array(), EP_FORUM_VERSION);
        wp_enqueue_script('ep-forum-admin-script', EP_FORUM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), EP_FORUM_VERSION, true);
    }
    
    /**
     * AJAX Handler: Create Topic
     */
    public function ajax_create_topic() {
        check_ajax_referer('ep_forum_nonce', 'nonce');
        
        if (!current_user_can('create_forum_topics')) {
            wp_die('Bạn không có quyền tạo chủ đề mới.');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $forum_id = intval($_POST['forum_id']);
        $category = sanitize_text_field($_POST['category']);
        
        $topic_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'ep_topic',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                'forum_id' => $forum_id,
                'topic_views' => 0,
                'topic_replies' => 0,
                'is_sticky' => 0,
                'is_locked' => 0,
            )
        );
        
        $topic_id = wp_insert_post($topic_data);
        
        if ($topic_id && !is_wp_error($topic_id)) {
            // Set category
            if ($category) {
                wp_set_post_terms($topic_id, array($category), 'forum_category');
            }
            
            // Award eco points for creating topic
            $this->award_eco_points(get_current_user_id(), 'create_topic', 10);
            
            wp_send_json_success(array(
                'topic_id' => $topic_id,
                'message' => 'Chủ đề đã được tạo thành công!',
                'redirect' => get_permalink($topic_id)
            ));
        } else {
            wp_send_json_error('Không thể tạo chủ đề. Vui lòng thử lại.');
        }
    }
    
    /**
     * AJAX Handler: Create Post/Reply
     */
    public function ajax_create_post() {
        check_ajax_referer('ep_forum_nonce', 'nonce');
        
        if (!current_user_can('create_forum_replies')) {
            wp_die('Bạn không có quyền trả lời.');
        }
        
        $content = wp_kses_post($_POST['content']);
        $topic_id = intval($_POST['topic_id']);
        $parent_id = intval($_POST['parent_id']);
        
        $reply_data = array(
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'ep_reply',
            'post_author' => get_current_user_id(),
            'post_parent' => $topic_id,
            'meta_input' => array(
                'reply_to' => $parent_id,
                'topic_id' => $topic_id,
            )
        );
        
        $reply_id = wp_insert_post($reply_data);
        
        if ($reply_id && !is_wp_error($reply_id)) {
            // Update topic reply count
            $reply_count = get_post_meta($topic_id, 'topic_replies', true);
            update_post_meta($topic_id, 'topic_replies', intval($reply_count) + 1);
            
            // Award eco points for replying
            $this->award_eco_points(get_current_user_id(), 'create_reply', 5);
            
            wp_send_json_success(array(
                'reply_id' => $reply_id,
                'message' => 'Phản hồi đã được đăng thành công!'
            ));
        } else {
            wp_send_json_error('Không thể đăng phản hồi. Vui lòng thử lại.');
        }
    }
    
    /**
     * AJAX Handler: Moderate Content
     */
    public function ajax_moderate_content() {
        check_ajax_referer('ep_forum_nonce', 'nonce');
        
        if (!current_user_can('moderate_forum')) {
            wp_die('Bạn không có quyền kiểm duyệt.');
        }
        
        $post_id = intval($_POST['post_id']);
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'approve':
                wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
                break;
            case 'reject':
                wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
                break;
            case 'delete':
                wp_delete_post($post_id, true);
                break;
            case 'sticky':
                update_post_meta($post_id, 'is_sticky', 1);
                break;
            case 'unsticky':
                update_post_meta($post_id, 'is_sticky', 0);
                break;
            case 'lock':
                update_post_meta($post_id, 'is_locked', 1);
                break;
            case 'unlock':
                update_post_meta($post_id, 'is_locked', 0);
                break;
        }
        
        wp_send_json_success(array('message' => 'Hành động kiểm duyệt đã được thực hiện.'));
    }
      /**
     * Award Eco Points for Forum Activities
     */
    private function award_eco_points($user_id, $action, $points) {
        global $wpdb;
        
        // Check if user exists in users table
        $user_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM users WHERE user_id = %d", $user_id
        ));
        
        if ($user_exists) {
            // Update eco points
            $wpdb->query($wpdb->prepare(
                "UPDATE users SET total_eco_points = total_eco_points + %d WHERE user_id = %d",
                $points, $user_id
            ));
            
            // Log activity
            $wpdb->insert('user_activities', array(
                'user_id' => $user_id,
                'activity_type' => 'forum_' . $action,
                'activity_data' => json_encode(array('points_earned' => $points)),
                'points_earned' => $points,
                'created_at' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Forum Management',
            'Forum',
            'manage_options',
            'ep-forum-admin',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'ep-forum-admin',
            'Data Migration',
            'Migration',
            'manage_options',
            'ep-forum-migration',
            array($this, 'migration_page')
        );
    }
      /**
     * Load Forum Templates
     */
    public function load_forum_templates($template) {
        global $post;
        
        // Check if we're on a forum-related page
        if (is_singular('ep_forum')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-ep_forum.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_singular('ep_topic')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-ep_topic.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('ep_forum')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-ep_forum.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Admin Page Content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Forum Management</h1>
            <div class="ep-forum-admin-stats">
                <h2>Forum Statistics</h2>
                <?php
                $forums_count = wp_count_posts('ep_forum');
                $topics_count = wp_count_posts('ep_topic');
                $replies_count = wp_count_posts('ep_reply');
                ?>
                <p><strong>Forums:</strong> <?php echo $forums_count->publish; ?></p>
                <p><strong>Topics:</strong> <?php echo $topics_count->publish; ?></p>
                <p><strong>Replies:</strong> <?php echo $replies_count->publish; ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Migration Page Content
     */
    public function migration_page() {
        ?>
        <div class="wrap">
            <h1>Forum Data Migration</h1>
            <div class="ep-forum-migration">
                <h2>Migrate Existing Forum Data</h2>
                <p>This will migrate data from the old forum system to WordPress custom post types.</p>
                
                <div class="migration-actions">
                    <a href="<?php echo admin_url('admin.php?page=ep-forum-migration&action=migrate'); ?>" 
                       class="button button-primary" 
                       onclick="return confirm('Are you sure you want to run the migration? This cannot be undone.');">
                        Run Migration
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ep-forum-migration&action=rollback'); ?>" 
                       class="button button-secondary" 
                       onclick="return confirm('Are you sure you want to rollback? This will delete all migrated posts.');">
                        Rollback Migration
                    </a>
                </div>
                
                <?php
                if (isset($_GET['action'])) {
                    echo '<div class="migration-output">';
                    echo '<h3>Migration Output:</h3>';
                    echo '<pre>';
                    
                    if (class_exists('EP_Forum_Migration')) {
                        $migration = new EP_Forum_Migration();
                        if ($_GET['action'] === 'migrate') {
                            $migration->run_migration();
                        } elseif ($_GET['action'] === 'rollback') {
                            $migration->rollback_migration();
                        }
                    }
                    
                    echo '</pre>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new EnvironmentalPlatformForum();
?>
