<?php
/**
 * Theme Options Panel for Environmental Platform
 *
 * @package Environmental_Platform
 */

if (!class_exists('Environmental_Platform_Theme_Options')) {
    
    class Environmental_Platform_Theme_Options {
        
        /**
         * Constructor
         */
        public function __construct() {
            add_action('admin_menu', array($this, 'add_theme_options_page'));
            add_action('admin_init', array($this, 'init_theme_options'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        /**
         * Add theme options page to admin menu
         */
        public function add_theme_options_page() {
            add_theme_page(
                esc_html__('Environmental Platform Options', 'environmental-platform'),
                esc_html__('Theme Options', 'environmental-platform'),
                'manage_options',
                'environmental-platform-options',
                array($this, 'render_theme_options_page')
            );
        }

        /**
         * Initialize theme options
         */
        public function init_theme_options() {
            register_setting('environmental_platform_options', 'environmental_platform_options', array($this, 'validate_options'));

            // General Settings Section
            add_settings_section(
                'general_settings',
                esc_html__('General Settings', 'environmental-platform'),
                array($this, 'general_settings_callback'),
                'environmental-platform-options'
            );

            // Environmental Impact Section
            add_settings_section(
                'environmental_impact',
                esc_html__('Environmental Impact Settings', 'environmental-platform'),
                array($this, 'environmental_impact_callback'),
                'environmental-platform-options'
            );

            // Performance Section
            add_settings_section(
                'performance_settings',
                esc_html__('Performance Settings', 'environmental-platform'),
                array($this, 'performance_settings_callback'),
                'environmental-platform-options'
            );

            // Social Media Section
            add_settings_section(
                'social_media',
                esc_html__('Social Media Settings', 'environmental-platform'),
                array($this, 'social_media_callback'),
                'environmental-platform-options'
            );

            // Add individual settings fields
            $this->add_settings_fields();
        }

        /**
         * Add individual settings fields
         */
        private function add_settings_fields() {
            // General Settings Fields
            add_settings_field(
                'site_logo',
                esc_html__('Site Logo', 'environmental-platform'),
                array($this, 'logo_field_callback'),
                'environmental-platform-options',
                'general_settings'
            );

            add_settings_field(
                'enable_dark_mode',
                esc_html__('Enable Dark Mode Toggle', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'general_settings',
                array('field' => 'enable_dark_mode', 'description' => 'Allow users to switch between light and dark themes')
            );

            add_settings_field(
                'breadcrumbs_enable',
                esc_html__('Enable Breadcrumbs', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'general_settings',
                array('field' => 'breadcrumbs_enable', 'description' => 'Show breadcrumb navigation on pages')
            );

            // Environmental Impact Fields
            add_settings_field(
                'show_environmental_scores',
                esc_html__('Show Environmental Scores', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'environmental_impact',
                array('field' => 'show_environmental_scores', 'description' => 'Display environmental impact scores on posts')
            );

            add_settings_field(
                'daily_trees_planted',
                esc_html__('Daily Trees Planted', 'environmental-platform'),
                array($this, 'text_field_callback'),
                'environmental-platform-options',
                'environmental_impact',
                array('field' => 'daily_trees_planted', 'description' => 'Number of trees planted today (for statistics)')
            );

            add_settings_field(
                'daily_waste_recycled',
                esc_html__('Daily Waste Recycled (KG)', 'environmental-platform'),
                array($this, 'text_field_callback'),
                'environmental-platform-options',
                'environmental_impact',
                array('field' => 'daily_waste_recycled', 'description' => 'Amount of waste recycled today')
            );

            add_settings_field(
                'daily_energy_saved',
                esc_html__('Daily Energy Saved (kWh)', 'environmental-platform'),
                array($this, 'text_field_callback'),
                'environmental-platform-options',
                'environmental_impact',
                array('field' => 'daily_energy_saved', 'description' => 'Amount of energy saved today')
            );

            // Performance Fields
            add_settings_field(
                'enable_lazy_loading',
                esc_html__('Enable Lazy Loading', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'performance_settings',
                array('field' => 'enable_lazy_loading', 'description' => 'Lazy load images for better performance')
            );

            add_settings_field(
                'minify_css',
                esc_html__('Minify CSS', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'performance_settings',
                array('field' => 'minify_css', 'description' => 'Minify CSS files for faster loading')
            );

            add_settings_field(
                'enable_caching',
                esc_html__('Enable Theme Caching', 'environmental-platform'),
                array($this, 'checkbox_field_callback'),
                'environmental-platform-options',
                'performance_settings',
                array('field' => 'enable_caching', 'description' => 'Cache theme assets for better performance')
            );

            // Social Media Fields
            add_settings_field(
                'facebook_url',
                esc_html__('Facebook URL', 'environmental-platform'),
                array($this, 'url_field_callback'),
                'environmental-platform-options',
                'social_media',
                array('field' => 'facebook_url', 'description' => 'Your Facebook page URL')
            );

            add_settings_field(
                'twitter_url',
                esc_html__('Twitter URL', 'environmental-platform'),
                array($this, 'url_field_callback'),
                'environmental-platform-options',
                'social_media',
                array('field' => 'twitter_url', 'description' => 'Your Twitter profile URL')
            );

            add_settings_field(
                'instagram_url',
                esc_html__('Instagram URL', 'environmental-platform'),
                array($this, 'url_field_callback'),
                'environmental-platform-options',
                'social_media',
                array('field' => 'instagram_url', 'description' => 'Your Instagram profile URL')
            );

            add_settings_field(
                'linkedin_url',
                esc_html__('LinkedIn URL', 'environmental-platform'),
                array($this, 'url_field_callback'),
                'environmental-platform-options',
                'social_media',
                array('field' => 'linkedin_url', 'description' => 'Your LinkedIn profile URL')
            );
        }

        /**
         * Section callbacks
         */
        public function general_settings_callback() {
            echo '<p>' . esc_html__('Configure general theme settings.', 'environmental-platform') . '</p>';
        }

        public function environmental_impact_callback() {
            echo '<p>' . esc_html__('Configure environmental impact display settings and statistics.', 'environmental-platform') . '</p>';
        }

        public function performance_settings_callback() {
            echo '<p>' . esc_html__('Optimize your site\'s performance with these settings.', 'environmental-platform') . '</p>';
        }

        public function social_media_callback() {
            echo '<p>' . esc_html__('Add your social media profile URLs.', 'environmental-platform') . '</p>';
        }

        /**
         * Field callbacks
         */
        public function logo_field_callback() {
            $options = get_option('environmental_platform_options');
            $logo_url = isset($options['site_logo']) ? $options['site_logo'] : '';
            ?>
            <div class="logo-upload-field">
                <input type="url" name="environmental_platform_options[site_logo]" value="<?php echo esc_attr($logo_url); ?>" class="regular-text logo-url-field" />
                <button type="button" class="button upload-logo-button"><?php esc_html_e('Upload Logo', 'environmental-platform'); ?></button>
                <?php if ($logo_url) : ?>
                    <div class="logo-preview">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview" style="max-width: 200px; height: auto; margin-top: 10px;" />
                    </div>
                <?php endif; ?>
            </div>
            <p class="description"><?php esc_html_e('Upload or enter URL for your site logo.', 'environmental-platform'); ?></p>
            <?php
        }

        public function checkbox_field_callback($args) {
            $options = get_option('environmental_platform_options');
            $value = isset($options[$args['field']]) ? $options[$args['field']] : 0;
            ?>
            <label>
                <input type="checkbox" name="environmental_platform_options[<?php echo esc_attr($args['field']); ?>]" value="1" <?php checked(1, $value); ?> />
                <?php echo esc_html($args['description']); ?>
            </label>
            <?php
        }

        public function text_field_callback($args) {
            $options = get_option('environmental_platform_options');
            $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
            ?>
            <input type="text" name="environmental_platform_options[<?php echo esc_attr($args['field']); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
            <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php
        }

        public function url_field_callback($args) {
            $options = get_option('environmental_platform_options');
            $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
            ?>
            <input type="url" name="environmental_platform_options[<?php echo esc_attr($args['field']); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
            <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php
        }

        /**
         * Validate options
         */
        public function validate_options($input) {
            $validated = array();

            // Validate logo URL
            if (isset($input['site_logo'])) {
                $validated['site_logo'] = esc_url_raw($input['site_logo']);
            }

            // Validate checkboxes
            $checkboxes = array(
                'enable_dark_mode', 'breadcrumbs_enable', 'show_environmental_scores',
                'enable_lazy_loading', 'minify_css', 'enable_caching'
            );
            foreach ($checkboxes as $checkbox) {
                $validated[$checkbox] = isset($input[$checkbox]) ? 1 : 0;
            }

            // Validate text fields
            $text_fields = array('daily_trees_planted', 'daily_waste_recycled', 'daily_energy_saved');
            foreach ($text_fields as $field) {
                if (isset($input[$field])) {
                    $validated[$field] = sanitize_text_field($input[$field]);
                }
            }

            // Validate URLs
            $url_fields = array('facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url');
            foreach ($url_fields as $field) {
                if (isset($input[$field])) {
                    $validated[$field] = esc_url_raw($input[$field]);
                }
            }

            return $validated;
        }

        /**
         * Render theme options page
         */
        public function render_theme_options_page() {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Environmental Platform Theme Options', 'environmental-platform'); ?></h1>
                
                <div class="environmental-options-header">
                    <div class="options-banner">
                        <div class="banner-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="banner-content">
                            <h2><?php esc_html_e('Customize Your Environmental Platform', 'environmental-platform'); ?></h2>
                            <p><?php esc_html_e('Configure your theme settings to create an impactful environmental website.', 'environmental-platform'); ?></p>
                        </div>
                    </div>
                </div>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('environmental_platform_options');
                    do_settings_sections('environmental-platform-options');
                    submit_button(esc_html__('Save Environmental Settings', 'environmental-platform'));
                    ?>
                </form>

                <div class="environmental-options-sidebar">
                    <div class="options-widget">
                        <h3><?php esc_html_e('Environmental Impact Today', 'environmental-platform'); ?></h3>
                        <div class="impact-stats">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-tree"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?php echo esc_html(get_option('daily_trees_planted', '1,247')); ?></span>
                                    <span class="stat-label"><?php esc_html_e('Trees Planted', 'environmental-platform'); ?></span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-recycle"></i>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-number"><?php echo esc_html(get_option('daily_waste_recycled', '850')); ?></span>
                                    <span class="stat-label"><?php esc_html_e('KG Recycled', 'environmental-platform'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="options-widget">
                        <h3><?php esc_html_e('Quick Actions', 'environmental-platform'); ?></h3>
                        <div class="quick-actions">
                            <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="action-btn">
                                <i class="fas fa-paint-brush"></i>
                                <?php esc_html_e('Customize Appearance', 'environmental-platform'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" class="action-btn">
                                <i class="fas fa-bars"></i>
                                <?php esc_html_e('Manage Menus', 'environmental-platform'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('widgets.php')); ?>" class="action-btn">
                                <i class="fas fa-th-large"></i>
                                <?php esc_html_e('Manage Widgets', 'environmental-platform'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .environmental-options-header {
                margin-bottom: 30px;
            }
            .options-banner {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }
            .banner-icon {
                font-size: 3rem;
                margin-right: 20px;
            }
            .banner-content h2 {
                margin: 0 0 10px 0;
                color: white;
            }
            .banner-content p {
                margin: 0;
                opacity: 0.9;
            }
            .environmental-options-sidebar {
                position: fixed;
                top: 150px;
                right: 20px;
                width: 300px;
                z-index: 1000;
            }
            .options-widget {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .options-widget h3 {
                margin-top: 0;
                color: #28a745;
                border-bottom: 2px solid #28a745;
                padding-bottom: 10px;
            }
            .impact-stats {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            .stat-item {
                display: flex;
                align-items: center;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            .stat-icon {
                font-size: 1.5rem;
                color: #28a745;
                margin-right: 15px;
            }
            .stat-content {
                display: flex;
                flex-direction: column;
            }
            .stat-number {
                font-size: 1.2rem;
                font-weight: bold;
                color: #333;
            }
            .stat-label {
                font-size: 0.9rem;
                color: #666;
            }
            .quick-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .action-btn {
                display: flex;
                align-items: center;
                padding: 12px 15px;
                background: #28a745;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background-color 0.3s;
            }
            .action-btn:hover {
                background: #218838;
                color: white;
                text-decoration: none;
            }
            .action-btn i {
                margin-right: 10px;
            }
            .logo-upload-field {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .upload-logo-button {
                max-width: 150px;
            }
            .logo-preview img {
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 5px;
            }
            </style>
            <?php
        }

        /**
         * Enqueue admin scripts
         */
        public function enqueue_admin_scripts($hook) {
            if ($hook !== 'appearance_page_environmental-platform-options') {
                return;
            }

            wp_enqueue_media();
            wp_enqueue_script(
                'environmental-admin-options',
                get_template_directory_uri() . '/js/admin-options.js',
                array('jquery'),
                wp_get_theme()->get('Version'),
                true
            );
        }
    }

    // Initialize the theme options
    new Environmental_Platform_Theme_Options();
}

/**
 * Helper function to get theme option
 */
function environmental_platform_get_option($option_name, $default = '') {
    $options = get_option('environmental_platform_options');
    return isset($options[$option_name]) ? $options[$option_name] : $default;
}
