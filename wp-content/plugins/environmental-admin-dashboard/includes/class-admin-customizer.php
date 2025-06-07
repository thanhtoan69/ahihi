<?php
/**
 * Environmental Admin Customizer Class
 * 
 * Handles admin interface customization, theme options, admin UI enhancements,
 * custom admin pages styling, and administrative interface modifications.
 * 
 * @package Environmental_Admin_Dashboard
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Admin_Customizer {
    
    private static $instance = null;
    private $customizer_options = array();
    
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
        $this->init_hooks();
        $this->load_customizer_options();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'init_admin_customizer'));
        add_action('admin_menu', array($this, 'add_customizer_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_customizer_scripts'));
        add_action('wp_ajax_env_save_customizer_settings', array($this, 'ajax_save_customizer_settings'));
        add_action('wp_ajax_env_reset_customizer_settings', array($this, 'ajax_reset_customizer_settings'));
        add_action('wp_ajax_env_export_customizer_settings', array($this, 'ajax_export_customizer_settings'));
        add_action('wp_ajax_env_import_customizer_settings', array($this, 'ajax_import_customizer_settings'));
        
        // Admin interface modifications
        add_action('admin_head', array($this, 'apply_custom_admin_styles'));
        add_action('admin_footer', array($this, 'apply_custom_admin_scripts'));
        add_filter('admin_footer_text', array($this, 'custom_admin_footer'));
        add_filter('update_footer', array($this, 'custom_admin_footer_version'), 11);
        
        // Custom login page
        add_action('login_enqueue_scripts', array($this, 'custom_login_styles'));
        add_filter('login_headerurl', array($this, 'custom_login_logo_url'));
        add_filter('login_headertitle', array($this, 'custom_login_logo_title'));
        
        // Admin bar customization
        add_action('wp_before_admin_bar_render', array($this, 'customize_admin_bar'));
        add_action('admin_bar_menu', array($this, 'add_custom_admin_bar_items'), 100);
    }
    
    /**
     * Load customizer options from database
     */
    private function load_customizer_options() {
        $default_options = array(
            'admin_theme' => 'default',
            'color_scheme' => 'default',
            'dashboard_layout' => 'default',
            'hide_wp_logo' => false,
            'custom_admin_logo' => '',
            'custom_login_logo' => '',
            'admin_footer_text' => '',
            'hide_help_tabs' => false,
            'hide_screen_options' => false,
            'custom_css' => '',
            'custom_js' => '',
            'menu_order' => array(),
            'hidden_menu_items' => array(),
            'dashboard_widgets_order' => array(),
            'user_role_customizations' => array()
        );
        
        $this->customizer_options = get_option('env_admin_customizer_options', $default_options);
    }
    
    /**
     * Initialize admin customizer
     */
    public function init_admin_customizer() {
        // Apply customizations based on user role
        $this->apply_role_based_customizations();
        
        // Hide admin elements if configured
        if ($this->get_option('hide_help_tabs')) {
            add_filter('contextual_help', array($this, 'remove_help_tabs'), 999, 3);
        }
        
        if ($this->get_option('hide_screen_options')) {
            add_filter('screen_options_show_screen', '__return_false');
        }
        
        // Custom menu order
        $menu_order = $this->get_option('menu_order');
        if (!empty($menu_order)) {
            add_filter('custom_menu_order', '__return_true');
            add_filter('menu_order', array($this, 'custom_menu_order'));
        }
        
        // Hide menu items
        $hidden_items = $this->get_option('hidden_menu_items');
        if (!empty($hidden_items)) {
            add_action('admin_menu', array($this, 'hide_admin_menu_items'), 999);
        }
    }
    
    /**
     * Add customizer admin menu
     */
    public function add_customizer_menu() {
        add_submenu_page(
            'environmental-dashboard',
            __('Admin Customizer', 'env-admin-dashboard'),
            __('Admin Customizer', 'env-admin-dashboard'),
            'manage_options',
            'environmental-admin-customizer',
            array($this, 'render_customizer_page')
        );
    }
    
    /**
     * Enqueue customizer scripts and styles
     */
    public function enqueue_customizer_scripts($hook) {
        if (strpos($hook, 'environmental-admin-customizer') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
            
            wp_enqueue_style(
                'env-admin-customizer',
                ENV_ADMIN_DASHBOARD_PLUGIN_URL . 'assets/css/admin-customizer.css',
                array(),
                ENV_ADMIN_DASHBOARD_VERSION
            );
            
            wp_enqueue_script(
                'env-admin-customizer',
                ENV_ADMIN_DASHBOARD_PLUGIN_URL . 'assets/js/admin-customizer.js',
                array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
                ENV_ADMIN_DASHBOARD_VERSION,
                true
            );
            
            wp_localize_script('env-admin-customizer', 'envAdminCustomizer', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('env_admin_customizer_nonce'),
                'strings' => array(
                    'saving' => __('Saving...', 'env-admin-dashboard'),
                    'saved' => __('Settings saved successfully!', 'env-admin-dashboard'),
                    'error' => __('Error saving settings.', 'env-admin-dashboard'),
                    'confirmReset' => __('Are you sure you want to reset all customizer settings?', 'env-admin-dashboard'),
                    'confirmImport' => __('This will overwrite your current settings. Continue?', 'env-admin-dashboard')
                )
            ));
        }
    }
    
    /**
     * Render customizer admin page
     */
    public function render_customizer_page() {
        ?>
        <div class="wrap env-admin-customizer">
            <h1><?php _e('Environmental Admin Customizer', 'env-admin-dashboard'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'env-admin-dashboard'); ?></a>
                <a href="#appearance" class="nav-tab"><?php _e('Appearance', 'env-admin-dashboard'); ?></a>
                <a href="#menu" class="nav-tab"><?php _e('Menu', 'env-admin-dashboard'); ?></a>
                <a href="#login" class="nav-tab"><?php _e('Login Page', 'env-admin-dashboard'); ?></a>
                <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'env-admin-dashboard'); ?></a>
                <a href="#import-export" class="nav-tab"><?php _e('Import/Export', 'env-admin-dashboard'); ?></a>
            </div>
            
            <form id="env-customizer-form" method="post">
                <?php wp_nonce_field('env_admin_customizer_nonce', 'env_customizer_nonce'); ?>
                
                <!-- General Tab -->
                <div id="general" class="tab-content active">
                    <h2><?php _e('General Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Admin Theme', 'env-admin-dashboard'); ?></th>
                            <td>
                                <select name="admin_theme">
                                    <option value="default" <?php selected($this->get_option('admin_theme'), 'default'); ?>><?php _e('Default', 'env-admin-dashboard'); ?></option>
                                    <option value="environmental" <?php selected($this->get_option('admin_theme'), 'environmental'); ?>><?php _e('Environmental', 'env-admin-dashboard'); ?></option>
                                    <option value="dark" <?php selected($this->get_option('admin_theme'), 'dark'); ?>><?php _e('Dark Mode', 'env-admin-dashboard'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Color Scheme', 'env-admin-dashboard'); ?></th>
                            <td>
                                <select name="color_scheme">
                                    <option value="default" <?php selected($this->get_option('color_scheme'), 'default'); ?>><?php _e('Default', 'env-admin-dashboard'); ?></option>
                                    <option value="green" <?php selected($this->get_option('color_scheme'), 'green'); ?>><?php _e('Environmental Green', 'env-admin-dashboard'); ?></option>
                                    <option value="blue" <?php selected($this->get_option('color_scheme'), 'blue'); ?>><?php _e('Ocean Blue', 'env-admin-dashboard'); ?></option>
                                    <option value="earth" <?php selected($this->get_option('color_scheme'), 'earth'); ?>><?php _e('Earth Tones', 'env-admin-dashboard'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Dashboard Layout', 'env-admin-dashboard'); ?></th>
                            <td>
                                <select name="dashboard_layout">
                                    <option value="default" <?php selected($this->get_option('dashboard_layout'), 'default'); ?>><?php _e('Default', 'env-admin-dashboard'); ?></option>
                                    <option value="single-column" <?php selected($this->get_option('dashboard_layout'), 'single-column'); ?>><?php _e('Single Column', 'env-admin-dashboard'); ?></option>
                                    <option value="two-column" <?php selected($this->get_option('dashboard_layout'), 'two-column'); ?>><?php _e('Two Column', 'env-admin-dashboard'); ?></option>
                                    <option value="three-column" <?php selected($this->get_option('dashboard_layout'), 'three-column'); ?>><?php _e('Three Column', 'env-admin-dashboard'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Admin Footer Text', 'env-admin-dashboard'); ?></th>
                            <td>
                                <input type="text" name="admin_footer_text" value="<?php echo esc_attr($this->get_option('admin_footer_text')); ?>" class="regular-text" />
                                <p class="description"><?php _e('Custom text to display in admin footer. Leave empty for default.', 'env-admin-dashboard'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Appearance Tab -->
                <div id="appearance" class="tab-content">
                    <h2><?php _e('Appearance Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Custom Admin Logo', 'env-admin-dashboard'); ?></th>
                            <td>
                                <input type="hidden" name="custom_admin_logo" id="custom_admin_logo" value="<?php echo esc_attr($this->get_option('custom_admin_logo')); ?>" />
                                <button type="button" class="button" id="upload_admin_logo_button"><?php _e('Upload Logo', 'env-admin-dashboard'); ?></button>
                                <button type="button" class="button" id="remove_admin_logo_button" style="<?php echo empty($this->get_option('custom_admin_logo')) ? 'display:none;' : ''; ?>"><?php _e('Remove Logo', 'env-admin-dashboard'); ?></button>
                                <div id="admin_logo_preview" style="margin-top: 10px;">
                                    <?php if ($this->get_option('custom_admin_logo')): ?>
                                        <img src="<?php echo esc_url($this->get_option('custom_admin_logo')); ?>" style="max-width: 200px; height: auto;" />
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Hide WordPress Logo', 'env-admin-dashboard'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="hide_wp_logo" value="1" <?php checked($this->get_option('hide_wp_logo'), 1); ?> />
                                    <?php _e('Hide WordPress logo from admin bar', 'env-admin-dashboard'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Hide Help Tabs', 'env-admin-dashboard'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="hide_help_tabs" value="1" <?php checked($this->get_option('hide_help_tabs'), 1); ?> />
                                    <?php _e('Hide help tabs from admin pages', 'env-admin-dashboard'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Hide Screen Options', 'env-admin-dashboard'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="hide_screen_options" value="1" <?php checked($this->get_option('hide_screen_options'), 1); ?> />
                                    <?php _e('Hide screen options from admin pages', 'env-admin-dashboard'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Custom CSS', 'env-admin-dashboard'); ?></th>
                            <td>
                                <textarea name="custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($this->get_option('custom_css')); ?></textarea>
                                <p class="description"><?php _e('Add custom CSS for admin pages.', 'env-admin-dashboard'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Menu Tab -->
                <div id="menu" class="tab-content">
                    <h2><?php _e('Menu Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <div class="menu-customizer">
                        <div class="menu-section">
                            <h3><?php _e('Menu Order', 'env-admin-dashboard'); ?></h3>
                            <p><?php _e('Drag and drop to reorder admin menu items:', 'env-admin-dashboard'); ?></p>
                            <ul id="menu-order-list" class="sortable-list">
                                <?php $this->render_menu_order_list(); ?>
                            </ul>
                        </div>
                        
                        <div class="menu-section">
                            <h3><?php _e('Hidden Menu Items', 'env-admin-dashboard'); ?></h3>
                            <p><?php _e('Select menu items to hide:', 'env-admin-dashboard'); ?></p>
                            <div id="hidden-menu-items">
                                <?php $this->render_hidden_menu_items(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Login Page Tab -->
                <div id="login" class="tab-content">
                    <h2><?php _e('Login Page Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Custom Login Logo', 'env-admin-dashboard'); ?></th>
                            <td>
                                <input type="hidden" name="custom_login_logo" id="custom_login_logo" value="<?php echo esc_attr($this->get_option('custom_login_logo')); ?>" />
                                <button type="button" class="button" id="upload_login_logo_button"><?php _e('Upload Logo', 'env-admin-dashboard'); ?></button>
                                <button type="button" class="button" id="remove_login_logo_button" style="<?php echo empty($this->get_option('custom_login_logo')) ? 'display:none;' : ''; ?>"><?php _e('Remove Logo', 'env-admin-dashboard'); ?></button>
                                <div id="login_logo_preview" style="margin-top: 10px;">
                                    <?php if ($this->get_option('custom_login_logo')): ?>
                                        <img src="<?php echo esc_url($this->get_option('custom_login_logo')); ?>" style="max-width: 200px; height: auto;" />
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Advanced Tab -->
                <div id="advanced" class="tab-content">
                    <h2><?php _e('Advanced Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Custom JavaScript', 'env-admin-dashboard'); ?></th>
                            <td>
                                <textarea name="custom_js" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($this->get_option('custom_js')); ?></textarea>
                                <p class="description"><?php _e('Add custom JavaScript for admin pages. Do not include &lt;script&gt; tags.', 'env-admin-dashboard'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('User Role Customizations', 'env-admin-dashboard'); ?></th>
                            <td>
                                <div class="role-customizations">
                                    <?php $this->render_role_customizations(); ?>
                                </div>
                                <p class="description"><?php _e('Configure different customizations for different user roles.', 'env-admin-dashboard'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import-export" class="tab-content">
                    <h2><?php _e('Import/Export Settings', 'env-admin-dashboard'); ?></h2>
                    
                    <div class="import-export-section">
                        <div class="export-section">
                            <h3><?php _e('Export Settings', 'env-admin-dashboard'); ?></h3>
                            <p><?php _e('Download your current customizer settings as a JSON file.', 'env-admin-dashboard'); ?></p>
                            <button type="button" id="export-settings-btn" class="button button-secondary"><?php _e('Export Settings', 'env-admin-dashboard'); ?></button>
                        </div>
                        
                        <div class="import-section">
                            <h3><?php _e('Import Settings', 'env-admin-dashboard'); ?></h3>
                            <p><?php _e('Upload a JSON file to import customizer settings.', 'env-admin-dashboard'); ?></p>
                            <input type="file" id="import-file" accept=".json" />
                            <button type="button" id="import-settings-btn" class="button button-secondary" disabled><?php _e('Import Settings', 'env-admin-dashboard'); ?></button>
                        </div>
                    </div>
                </div>
                
                <div class="submit-section">
                    <button type="submit" class="button button-primary"><?php _e('Save Settings', 'env-admin-dashboard'); ?></button>
                    <button type="button" id="reset-settings-btn" class="button button-secondary"><?php _e('Reset to Defaults', 'env-admin-dashboard'); ?></button>
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Apply custom admin styles
     */
    public function apply_custom_admin_styles() {
        $theme = $this->get_option('admin_theme');
        $color_scheme = $this->get_option('color_scheme');
        $custom_css = $this->get_option('custom_css');
        
        echo '<style type="text/css">';
        
        // Apply theme styles
        if ($theme === 'environmental') {
            echo $this->get_environmental_theme_css();
        } elseif ($theme === 'dark') {
            echo $this->get_dark_theme_css();
        }
        
        // Apply color scheme
        if ($color_scheme !== 'default') {
            echo $this->get_color_scheme_css($color_scheme);
        }
        
        // Apply custom CSS
        if (!empty($custom_css)) {
            echo $custom_css;
        }
        
        echo '</style>';
    }
    
    /**
     * Apply custom admin scripts
     */
    public function apply_custom_admin_scripts() {
        $custom_js = $this->get_option('custom_js');
        
        if (!empty($custom_js)) {
            echo '<script type="text/javascript">';
            echo $custom_js;
            echo '</script>';
        }
    }
    
    /**
     * Custom admin footer text
     */
    public function custom_admin_footer($text) {
        $custom_text = $this->get_option('admin_footer_text');
        if (!empty($custom_text)) {
            return $custom_text;
        }
        return $text;
    }
    
    /**
     * Custom admin footer version
     */
    public function custom_admin_footer_version($text) {
        return __('Environmental Platform Admin', 'env-admin-dashboard');
    }
    
    /**
     * Custom login page styles
     */
    public function custom_login_styles() {
        $login_logo = $this->get_option('custom_login_logo');
        
        if (!empty($login_logo)) {
            ?>
            <style type="text/css">
                #login h1 a, .login h1 a {
                    background-image: url(<?php echo esc_url($login_logo); ?>);
                    height: 80px;
                    width: 320px;
                    background-size: contain;
                    background-repeat: no-repeat;
                    padding-bottom: 30px;
                }
            </style>
            <?php
        }
        
        // Apply environmental login theme
        ?>
        <style type="text/css">
            body.login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .login form {
                background: rgba(255, 255, 255, 0.9);
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .login #nav a, .login #backtoblog a {
                color: #fff;
            }
            .login #nav a:hover, .login #backtoblog a:hover {
                color: #f0f0f0;
            }
        </style>
        <?php
    }
    
    /**
     * Custom login logo URL
     */
    public function custom_login_logo_url() {
        return home_url();
    }
    
    /**
     * Custom login logo title
     */
    public function custom_login_logo_title() {
        return get_bloginfo('name');
    }
    
    /**
     * Customize admin bar
     */
    public function customize_admin_bar() {
        global $wp_admin_bar;
        
        if ($this->get_option('hide_wp_logo')) {
            $wp_admin_bar->remove_node('wp-logo');
        }
    }
    
    /**
     * Add custom admin bar items
     */
    public function add_custom_admin_bar_items($wp_admin_bar) {
        $wp_admin_bar->add_node(array(
            'id' => 'environmental-platform',
            'title' => __('Environmental Platform', 'env-admin-dashboard'),
            'href' => admin_url('admin.php?page=environmental-dashboard'),
            'meta' => array(
                'class' => 'environmental-admin-bar-item'
            )
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'environmental-platform',
            'id' => 'env-dashboard',
            'title' => __('Dashboard', 'env-admin-dashboard'),
            'href' => admin_url('admin.php?page=environmental-dashboard')
        ));
        
        $wp_admin_bar->add_node(array(
            'parent' => 'environmental-platform',
            'id' => 'env-customizer',
            'title' => __('Customizer', 'env-admin-dashboard'),
            'href' => admin_url('admin.php?page=environmental-admin-customizer')
        ));
    }
    
    /**
     * AJAX: Save customizer settings
     */
    public function ajax_save_customizer_settings() {
        check_ajax_referer('env_admin_customizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'env-admin-dashboard'));
        }
        
        $settings = array();
        $allowed_fields = array(
            'admin_theme', 'color_scheme', 'dashboard_layout', 'hide_wp_logo',
            'custom_admin_logo', 'custom_login_logo', 'admin_footer_text',
            'hide_help_tabs', 'hide_screen_options', 'custom_css', 'custom_js',
            'menu_order', 'hidden_menu_items', 'user_role_customizations'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        // Special handling for arrays
        if (isset($_POST['menu_order']) && is_array($_POST['menu_order'])) {
            $settings['menu_order'] = array_map('sanitize_text_field', $_POST['menu_order']);
        }
        
        if (isset($_POST['hidden_menu_items']) && is_array($_POST['hidden_menu_items'])) {
            $settings['hidden_menu_items'] = array_map('sanitize_text_field', $_POST['hidden_menu_items']);
        }
        
        // Special handling for textarea fields
        if (isset($_POST['custom_css'])) {
            $settings['custom_css'] = wp_strip_all_tags($_POST['custom_css']);
        }
        
        if (isset($_POST['custom_js'])) {
            $settings['custom_js'] = wp_strip_all_tags($_POST['custom_js']);
        }
        
        update_option('env_admin_customizer_options', $settings);
        
        wp_send_json_success(__('Settings saved successfully!', 'env-admin-dashboard'));
    }
    
    /**
     * AJAX: Reset customizer settings
     */
    public function ajax_reset_customizer_settings() {
        check_ajax_referer('env_admin_customizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'env-admin-dashboard'));
        }
        
        delete_option('env_admin_customizer_options');
        
        wp_send_json_success(__('Settings reset successfully!', 'env-admin-dashboard'));
    }
    
    /**
     * AJAX: Export customizer settings
     */
    public function ajax_export_customizer_settings() {
        check_ajax_referer('env_admin_customizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'env-admin-dashboard'));
        }
        
        $settings = get_option('env_admin_customizer_options', array());
        $settings['export_date'] = current_time('mysql');
        $settings['site_url'] = home_url();
        
        wp_send_json_success($settings);
    }
    
    /**
     * AJAX: Import customizer settings
     */
    public function ajax_import_customizer_settings() {
        check_ajax_referer('env_admin_customizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'env-admin-dashboard'));
        }
        
        if (!isset($_POST['settings'])) {
            wp_send_json_error(__('No settings data provided.', 'env-admin-dashboard'));
        }
        
        $settings = json_decode(stripslashes($_POST['settings']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON data.', 'env-admin-dashboard'));
        }
        
        // Remove export-specific fields
        unset($settings['export_date'], $settings['site_url']);
        
        update_option('env_admin_customizer_options', $settings);
        
        wp_send_json_success(__('Settings imported successfully!', 'env-admin-dashboard'));
    }
    
    /**
     * Get customizer option value
     */
    private function get_option($key, $default = '') {
        return isset($this->customizer_options[$key]) ? $this->customizer_options[$key] : $default;
    }
    
    /**
     * Apply role-based customizations
     */
    private function apply_role_based_customizations() {
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        $role_customizations = $this->get_option('user_role_customizations', array());
        
        foreach ($user_roles as $role) {
            if (isset($role_customizations[$role])) {
                $customizations = $role_customizations[$role];
                // Apply role-specific customizations
                // This can be extended based on specific needs
            }
        }
    }
    
    /**
     * Get environmental theme CSS
     */
    private function get_environmental_theme_css() {
        return '
            #adminmenu, #adminmenuback, #adminmenuwrap {
                background: linear-gradient(135deg, #2E7D32 0%, #388E3C 100%);
            }
            #adminmenu a {
                color: #E8F5E8;
            }
            #adminmenu .wp-has-current-submenu .wp-submenu-head,
            #adminmenu .wp-menu-arrow,
            #adminmenu .wp-menu-arrow div,
            #adminmenu li.current a.menu-top,
            #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
            .folded #adminmenu li.current.menu-top {
                background: #1B5E20;
                color: #fff;
            }
            .wp-core-ui .button-primary {
                background: #4CAF50;
                border-color: #45a049;
            }
            .wp-core-ui .button-primary:hover {
                background: #45a049;
                border-color: #3d8b40;
            }
        ';
    }
    
    /**
     * Get dark theme CSS
     */
    private function get_dark_theme_css() {
        return '
            body {
                background: #1a1a1a;
                color: #e0e0e0;
            }
            #wpcontent, #wpfooter {
                background: #1a1a1a;
            }
            .wrap {
                color: #e0e0e0;
            }
            .wp-core-ui input[type="text"],
            .wp-core-ui input[type="email"],
            .wp-core-ui input[type="url"],
            .wp-core-ui input[type="password"],
            .wp-core-ui input[type="search"],
            .wp-core-ui textarea,
            .wp-core-ui select {
                background: #2a2a2a;
                border-color: #444;
                color: #e0e0e0;
            }
            .form-table th {
                color: #e0e0e0;
            }
        ';
    }
    
    /**
     * Get color scheme CSS
     */
    private function get_color_scheme_css($scheme) {
        $colors = array(
            'green' => array('primary' => '#4CAF50', 'secondary' => '#2E7D32'),
            'blue' => array('primary' => '#2196F3', 'secondary' => '#1976D2'),
            'earth' => array('primary' => '#8D6E63', 'secondary' => '#5D4037')
        );
        
        if (!isset($colors[$scheme])) {
            return '';
        }
        
        $primary = $colors[$scheme]['primary'];
        $secondary = $colors[$scheme]['secondary'];
        
        return "
            .wp-core-ui .button-primary {
                background: {$primary};
                border-color: {$secondary};
            }
            .wp-core-ui .button-primary:hover {
                background: {$secondary};
            }
            #adminmenu .wp-has-current-submenu .wp-submenu-head,
            #adminmenu .wp-menu-arrow,
            #adminmenu li.current a.menu-top {
                background: {$primary};
            }
        ";
    }
    
    /**
     * Render menu order list
     */
    private function render_menu_order_list() {
        global $menu;
        
        $menu_order = $this->get_option('menu_order', array());
        $ordered_menu = array();
        
        // Sort menu items according to saved order
        if (!empty($menu_order)) {
            foreach ($menu_order as $item) {
                foreach ($menu as $menu_item) {
                    if (isset($menu_item[2]) && $menu_item[2] === $item) {
                        $ordered_menu[] = $menu_item;
                        break;
                    }
                }
            }
            
            // Add any remaining menu items
            foreach ($menu as $menu_item) {
                if (!in_array($menu_item[2], $menu_order)) {
                    $ordered_menu[] = $menu_item;
                }
            }
        } else {
            $ordered_menu = $menu;
        }
        
        foreach ($ordered_menu as $item) {
            if (isset($item[0]) && isset($item[2])) {
                echo '<li data-menu-item="' . esc_attr($item[2]) . '">';
                echo '<span class="menu-item-title">' . wp_strip_all_tags($item[0]) . '</span>';
                echo '<span class="menu-item-handle"></span>';
                echo '</li>';
            }
        }
    }
    
    /**
     * Render hidden menu items
     */
    private function render_hidden_menu_items() {
        global $menu;
        
        $hidden_items = $this->get_option('hidden_menu_items', array());
        
        foreach ($menu as $item) {
            if (isset($item[0]) && isset($item[2])) {
                $checked = in_array($item[2], $hidden_items) ? 'checked' : '';
                echo '<label>';
                echo '<input type="checkbox" name="hidden_menu_items[]" value="' . esc_attr($item[2]) . '" ' . $checked . ' />';
                echo wp_strip_all_tags($item[0]);
                echo '</label><br>';
            }
        }
    }
    
    /**
     * Render role customizations
     */
    private function render_role_customizations() {
        $roles = wp_roles()->get_names();
        $role_customizations = $this->get_option('user_role_customizations', array());
        
        foreach ($roles as $role_key => $role_name) {
            echo '<div class="role-customization">';
            echo '<h4>' . esc_html($role_name) . '</h4>';
            echo '<label>';
            echo '<input type="checkbox" name="user_role_customizations[' . esc_attr($role_key) . '][custom_theme]" value="1" ' . 
                 (isset($role_customizations[$role_key]['custom_theme']) ? 'checked' : '') . ' />';
            echo __('Apply custom theme for this role', 'env-admin-dashboard');
            echo '</label>';
            echo '</div>';
        }
    }
    
    /**
     * Custom menu order
     */
    public function custom_menu_order($menu_order) {
        $custom_order = $this->get_option('menu_order');
        return !empty($custom_order) ? $custom_order : $menu_order;
    }
    
    /**
     * Hide admin menu items
     */
    public function hide_admin_menu_items() {
        $hidden_items = $this->get_option('hidden_menu_items', array());
        
        foreach ($hidden_items as $item) {
            remove_menu_page($item);
        }
    }
    
    /**
     * Remove help tabs
     */
    public function remove_help_tabs($old_help, $screen_id, $screen) {
        $screen->remove_help_tabs();
        return $old_help;
    }
}
