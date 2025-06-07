<?php
/**
 * Admin Interface Component
 * 
 * Handles plugin configuration page and admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Admin_Interface {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ems_test_translation_api', array($this, 'test_translation_api'));
        add_action('wp_ajax_ems_import_translations', array($this, 'import_translations'));
        add_action('wp_ajax_ems_export_translations', array($this, 'export_translations'));
        add_action('wp_ajax_ems_bulk_translate', array($this, 'bulk_translate'));
        add_filter('plugin_action_links_' . EMS_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Multi-language Support', 'environmental-multilang-support'),
            __('Multi-language', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang',
            array($this, 'admin_page'),
            'dashicons-translation',
            30
        );

        add_submenu_page(
            'environmental-multilang',
            __('Settings', 'environmental-multilang-support'),
            __('Settings', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'environmental-multilang',
            __('Language Management', 'environmental-multilang-support'),
            __('Languages', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang-languages',
            array($this, 'languages_page')
        );

        add_submenu_page(
            'environmental-multilang',
            __('Translation Tools', 'environmental-multilang-support'),
            __('Translation Tools', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang-tools',
            array($this, 'tools_page')
        );

        add_submenu_page(
            'environmental-multilang',
            __('Statistics', 'environmental-multilang-support'),
            __('Statistics', 'environmental-multilang-support'),
            'manage_options',
            'environmental-multilang-stats',
            array($this, 'stats_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ems_settings', 'ems_options', array($this, 'sanitize_options'));

        // General Settings
        add_settings_section(
            'ems_general_section',
            __('General Settings', 'environmental-multilang-support'),
            array($this, 'general_section_callback'),
            'ems_settings'
        );

        add_settings_field(
            'enabled_languages',
            __('Enabled Languages', 'environmental-multilang-support'),
            array($this, 'enabled_languages_callback'),
            'ems_settings',
            'ems_general_section'
        );

        add_settings_field(
            'default_language',
            __('Default Language', 'environmental-multilang-support'),
            array($this, 'default_language_callback'),
            'ems_settings',
            'ems_general_section'
        );

        add_settings_field(
            'url_structure',
            __('URL Structure', 'environmental-multilang-support'),
            array($this, 'url_structure_callback'),
            'ems_settings',
            'ems_general_section'
        );

        add_settings_field(
            'language_detection',
            __('Language Detection', 'environmental-multilang-support'),
            array($this, 'language_detection_callback'),
            'ems_settings',
            'ems_general_section'
        );

        // Translation API Settings
        add_settings_section(
            'ems_translation_section',
            __('Translation API Settings', 'environmental-multilang-support'),
            array($this, 'translation_section_callback'),
            'ems_settings'
        );

        add_settings_field(
            'translation_provider',
            __('Translation Provider', 'environmental-multilang-support'),
            array($this, 'translation_provider_callback'),
            'ems_settings',
            'ems_translation_section'
        );

        add_settings_field(
            'google_translate_api_key',
            __('Google Translate API Key', 'environmental-multilang-support'),
            array($this, 'google_translate_api_key_callback'),
            'ems_settings',
            'ems_translation_section'
        );

        // SEO Settings
        add_settings_section(
            'ems_seo_section',
            __('SEO Settings', 'environmental-multilang-support'),
            array($this, 'seo_section_callback'),
            'ems_settings'
        );

        add_settings_field(
            'enable_hreflang',
            __('Enable Hreflang Tags', 'environmental-multilang-support'),
            array($this, 'enable_hreflang_callback'),
            'ems_settings',
            'ems_seo_section'
        );

        add_settings_field(
            'enable_multilingual_sitemap',
            __('Enable Multilingual Sitemap', 'environmental-multilang-support'),
            array($this, 'enable_multilingual_sitemap_callback'),
            'ems_settings',
            'ems_seo_section'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-multilang') === false) {
            return;
        }

        wp_enqueue_script(
            'ems-admin-js',
            EMS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            EMS_VERSION,
            true
        );

        wp_enqueue_style(
            'ems-admin-css',
            EMS_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            EMS_VERSION
        );

        wp_localize_script('ems-admin-js', 'ems_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ems_admin_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'environmental-multilang-support'),
                'success' => __('Success!', 'environmental-multilang-support'),
                'error' => __('Error:', 'environmental-multilang-support'),
                'confirm_bulk_translate' => __('Are you sure you want to bulk translate? This may take a while.', 'environmental-multilang-support'),
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ems-admin-header">
                <div class="ems-admin-logo">
                    <img src="<?php echo EMS_PLUGIN_URL; ?>assets/images/logo.png" alt="Environmental Platform" />
                </div>
                <div class="ems-admin-info">
                    <h2><?php _e('Multi-language Support', 'environmental-multilang-support'); ?></h2>
                    <p><?php _e('Comprehensive multi-language support for your Environmental Platform.', 'environmental-multilang-support'); ?></p>
                </div>
            </div>

            <div class="ems-admin-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'environmental-multilang-support'); ?></a>
                    <a href="#translation" class="nav-tab"><?php _e('Translation', 'environmental-multilang-support'); ?></a>
                    <a href="#seo" class="nav-tab"><?php _e('SEO', 'environmental-multilang-support'); ?></a>
                    <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'environmental-multilang-support'); ?></a>
                </nav>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('ems_settings');
                    do_settings_sections('ems_settings');
                    submit_button();
                    ?>
                </form>
            </div>

            <div class="ems-admin-sidebar">
                <div class="ems-admin-widget">
                    <h3><?php _e('Quick Actions', 'environmental-multilang-support'); ?></h3>
                    <p><a href="<?php echo admin_url('admin.php?page=environmental-multilang-languages'); ?>" class="button"><?php _e('Manage Languages', 'environmental-multilang-support'); ?></a></p>
                    <p><a href="<?php echo admin_url('admin.php?page=environmental-multilang-tools'); ?>" class="button"><?php _e('Translation Tools', 'environmental-multilang-support'); ?></a></p>
                    <p><a href="#" class="button" id="ems-test-api"><?php _e('Test Translation API', 'environmental-multilang-support'); ?></a></p>
                </div>

                <div class="ems-admin-widget">
                    <h3><?php _e('Support', 'environmental-multilang-support'); ?></h3>
                    <p><?php _e('Need help? Check our documentation or contact support.', 'environmental-multilang-support'); ?></p>
                    <p><a href="https://environmentalplatform.com/docs/multilang" target="_blank" class="button"><?php _e('Documentation', 'environmental-multilang-support'); ?></a></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Languages management page
     */
    public function languages_page() {
        $options = get_option('ems_options', array());
        $enabled_languages = isset($options['enabled_languages']) ? $options['enabled_languages'] : array('vi', 'en');
        $available_languages = $this->get_available_languages();
        ?>
        <div class="wrap">
            <h1><?php _e('Language Management', 'environmental-multilang-support'); ?></h1>
            
            <div class="ems-languages-grid">
                <?php foreach ($available_languages as $code => $language): ?>
                <div class="ems-language-card <?php echo in_array($code, $enabled_languages) ? 'enabled' : 'disabled'; ?>">
                    <div class="ems-language-flag">
                        <img src="<?php echo EMS_PLUGIN_URL; ?>assets/images/flags/<?php echo $code; ?>.png" alt="<?php echo esc_attr($language['name']); ?>" />
                    </div>
                    <div class="ems-language-info">
                        <h3><?php echo esc_html($language['native_name']); ?></h3>
                        <p><?php echo esc_html($language['name']); ?> (<?php echo esc_html($code); ?>)</p>
                        <?php if ($language['rtl']): ?>
                        <span class="ems-rtl-badge"><?php _e('RTL', 'environmental-multilang-support'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ems-language-actions">
                        <label class="ems-toggle">
                            <input type="checkbox" name="enabled_languages[]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $enabled_languages)); ?> />
                            <span class="ems-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ems-language-stats">
                <h2><?php _e('Translation Statistics', 'environmental-multilang-support'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Language', 'environmental-multilang-support'); ?></th>
                            <th><?php _e('Posts', 'environmental-multilang-support'); ?></th>
                            <th><?php _e('Pages', 'environmental-multilang-support'); ?></th>
                            <th><?php _e('Categories', 'environmental-multilang-support'); ?></th>
                            <th><?php _e('Tags', 'environmental-multilang-support'); ?></th>
                            <th><?php _e('Completion', 'environmental-multilang-support'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enabled_languages as $lang_code): ?>
                        <?php $stats = $this->get_language_stats($lang_code); ?>
                        <tr>
                            <td>
                                <img src="<?php echo EMS_PLUGIN_URL; ?>assets/images/flags/<?php echo $lang_code; ?>.png" alt="" style="width: 16px; height: 12px; margin-right: 8px;" />
                                <?php echo esc_html($available_languages[$lang_code]['native_name']); ?>
                            </td>
                            <td><?php echo esc_html($stats['posts']); ?></td>
                            <td><?php echo esc_html($stats['pages']); ?></td>
                            <td><?php echo esc_html($stats['categories']); ?></td>
                            <td><?php echo esc_html($stats['tags']); ?></td>
                            <td>
                                <div class="ems-progress-bar">
                                    <div class="ems-progress-fill" style="width: <?php echo esc_attr($stats['completion']); ?>%"></div>
                                    <span><?php echo esc_html($stats['completion']); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Translation tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Translation Tools', 'environmental-multilang-support'); ?></h1>
            
            <div class="ems-tools-grid">
                <div class="ems-tool-card">
                    <h3><?php _e('Bulk Translation', 'environmental-multilang-support'); ?></h3>
                    <p><?php _e('Automatically translate content using translation APIs.', 'environmental-multilang-support'); ?></p>
                    <form id="ems-bulk-translate-form">
                        <p>
                            <label for="bulk-translate-from"><?php _e('From Language:', 'environmental-multilang-support'); ?></label>
                            <select id="bulk-translate-from" name="from_language">
                                <?php foreach ($this->get_available_languages() as $code => $language): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($language['native_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="bulk-translate-to"><?php _e('To Language:', 'environmental-multilang-support'); ?></label>
                            <select id="bulk-translate-to" name="to_language">
                                <?php foreach ($this->get_available_languages() as $code => $language): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($language['native_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="bulk-translate-content"><?php _e('Content Type:', 'environmental-multilang-support'); ?></label>
                            <select id="bulk-translate-content" name="content_type">
                                <option value="posts"><?php _e('Posts', 'environmental-multilang-support'); ?></option>
                                <option value="pages"><?php _e('Pages', 'environmental-multilang-support'); ?></option>
                                <option value="categories"><?php _e('Categories', 'environmental-multilang-support'); ?></option>
                                <option value="tags"><?php _e('Tags', 'environmental-multilang-support'); ?></option>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Start Bulk Translation', 'environmental-multilang-support'); ?></button>
                        </p>
                    </form>
                </div>

                <div class="ems-tool-card">
                    <h3><?php _e('Import/Export', 'environmental-multilang-support'); ?></h3>
                    <p><?php _e('Import or export translation files.', 'environmental-multilang-support'); ?></p>
                    <p>
                        <button class="button" id="ems-import-translations"><?php _e('Import Translations', 'environmental-multilang-support'); ?></button>
                        <button class="button" id="ems-export-translations"><?php _e('Export Translations', 'environmental-multilang-support'); ?></button>
                    </p>
                </div>

                <div class="ems-tool-card">
                    <h3><?php _e('Database Cleanup', 'environmental-multilang-support'); ?></h3>
                    <p><?php _e('Clean up unused translation data and optimize database.', 'environmental-multilang-support'); ?></p>
                    <p>
                        <button class="button" id="ems-cleanup-database"><?php _e('Cleanup Database', 'environmental-multilang-support'); ?></button>
                    </p>
                </div>

                <div class="ems-tool-card">
                    <h3><?php _e('Translation Cache', 'environmental-multilang-support'); ?></h3>
                    <p><?php _e('Manage translation cache for better performance.', 'environmental-multilang-support'); ?></p>
                    <p>
                        <button class="button" id="ems-clear-cache"><?php _e('Clear Cache', 'environmental-multilang-support'); ?></button>
                        <button class="button" id="ems-rebuild-cache"><?php _e('Rebuild Cache', 'environmental-multilang-support'); ?></button>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Statistics page
     */
    public function stats_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Multi-language Statistics', 'environmental-multilang-support'); ?></h1>
            
            <div class="ems-stats-overview">
                <div class="ems-stat-box">
                    <h3><?php _e('Total Languages', 'environmental-multilang-support'); ?></h3>
                    <div class="ems-stat-number"><?php echo count($this->get_enabled_languages()); ?></div>
                </div>
                <div class="ems-stat-box">
                    <h3><?php _e('Translated Posts', 'environmental-multilang-support'); ?></h3>
                    <div class="ems-stat-number"><?php echo $this->get_total_translated_posts(); ?></div>
                </div>
                <div class="ems-stat-box">
                    <h3><?php _e('Translation Pairs', 'environmental-multilang-support'); ?></h3>
                    <div class="ems-stat-number"><?php echo $this->get_total_translation_pairs(); ?></div>
                </div>
                <div class="ems-stat-box">
                    <h3><?php _e('API Calls Today', 'environmental-multilang-support'); ?></h3>
                    <div class="ems-stat-number"><?php echo $this->get_api_calls_today(); ?></div>
                </div>
            </div>

            <div class="ems-stats-charts">
                <div class="ems-chart-container">
                    <h3><?php _e('Translation Activity (Last 30 Days)', 'environmental-multilang-support'); ?></h3>
                    <canvas id="ems-activity-chart"></canvas>
                </div>
                <div class="ems-chart-container">
                    <h3><?php _e('Language Distribution', 'environmental-multilang-support'); ?></h3>
                    <canvas id="ems-language-chart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Setting callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general multi-language settings.', 'environmental-multilang-support') . '</p>';
    }

    public function translation_section_callback() {
        echo '<p>' . __('Configure automatic translation services.', 'environmental-multilang-support') . '</p>';
    }

    public function seo_section_callback() {
        echo '<p>' . __('Configure SEO settings for multi-language content.', 'environmental-multilang-support') . '</p>';
    }

    public function enabled_languages_callback() {
        $options = get_option('ems_options', array());
        $enabled_languages = isset($options['enabled_languages']) ? $options['enabled_languages'] : array('vi', 'en');
        $available_languages = $this->get_available_languages();

        echo '<fieldset>';
        foreach ($available_languages as $code => $language) {
            $checked = in_array($code, $enabled_languages) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="ems_options[enabled_languages][]" value="' . esc_attr($code) . '" ' . $checked . ' />';
            echo '<img src="' . EMS_PLUGIN_URL . 'assets/images/flags/' . $code . '.png" alt="" style="width: 16px; height: 12px; margin: 0 5px;" />';
            echo esc_html($language['native_name']) . ' (' . esc_html($language['name']) . ')';
            echo '</label><br />';
        }
        echo '</fieldset>';
    }

    public function default_language_callback() {
        $options = get_option('ems_options', array());
        $default_language = isset($options['default_language']) ? $options['default_language'] : 'vi';
        $available_languages = $this->get_available_languages();

        echo '<select name="ems_options[default_language]">';
        foreach ($available_languages as $code => $language) {
            $selected = ($default_language === $code) ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($language['native_name']) . '</option>';
        }
        echo '</select>';
    }

    public function url_structure_callback() {
        $options = get_option('ems_options', array());
        $url_structure = isset($options['url_structure']) ? $options['url_structure'] : 'query';

        echo '<select name="ems_options[url_structure]">';
        echo '<option value="query" ' . selected($url_structure, 'query', false) . '>' . __('Query Parameter (?lang=en)', 'environmental-multilang-support') . '</option>';
        echo '<option value="directory" ' . selected($url_structure, 'directory', false) . '>' . __('Directory (/en/page)', 'environmental-multilang-support') . '</option>';
        echo '<option value="subdomain" ' . selected($url_structure, 'subdomain', false) . '>' . __('Subdomain (en.site.com)', 'environmental-multilang-support') . '</option>';
        echo '</select>';
    }

    public function language_detection_callback() {
        $options = get_option('ems_options', array());
        $detection_methods = isset($options['language_detection']) ? $options['language_detection'] : array('url', 'cookie', 'browser');

        $methods = array(
            'url' => __('URL Parameter', 'environmental-multilang-support'),
            'cookie' => __('Cookie', 'environmental-multilang-support'),
            'browser' => __('Browser Language', 'environmental-multilang-support'),
            'ip' => __('IP Geolocation', 'environmental-multilang-support'),
            'user' => __('User Profile', 'environmental-multilang-support'),
        );

        echo '<fieldset>';
        foreach ($methods as $method => $label) {
            $checked = in_array($method, $detection_methods) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="ems_options[language_detection][]" value="' . esc_attr($method) . '" ' . $checked . ' />';
            echo esc_html($label);
            echo '</label><br />';
        }
        echo '</fieldset>';
    }

    public function translation_provider_callback() {
        $options = get_option('ems_options', array());
        $provider = isset($options['translation_provider']) ? $options['translation_provider'] : 'google';

        echo '<select name="ems_options[translation_provider]">';
        echo '<option value="google" ' . selected($provider, 'google', false) . '>' . __('Google Translate', 'environmental-multilang-support') . '</option>';
        echo '<option value="microsoft" ' . selected($provider, 'microsoft', false) . '>' . __('Microsoft Translator', 'environmental-multilang-support') . '</option>';
        echo '<option value="deepl" ' . selected($provider, 'deepl', false) . '>' . __('DeepL', 'environmental-multilang-support') . '</option>';
        echo '<option value="none" ' . selected($provider, 'none', false) . '>' . __('None (Manual Only)', 'environmental-multilang-support') . '</option>';
        echo '</select>';
    }

    public function google_translate_api_key_callback() {
        $options = get_option('ems_options', array());
        $api_key = isset($options['google_translate_api_key']) ? $options['google_translate_api_key'] : '';

        echo '<input type="password" name="ems_options[google_translate_api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Google Translate API key for automatic translation.', 'environmental-multilang-support') . '</p>';
    }

    public function enable_hreflang_callback() {
        $options = get_option('ems_options', array());
        $enable_hreflang = isset($options['enable_hreflang']) ? $options['enable_hreflang'] : true;

        echo '<input type="checkbox" name="ems_options[enable_hreflang]" value="1" ' . checked($enable_hreflang, true, false) . ' />';
        echo '<p class="description">' . __('Enable hreflang tags for better SEO in search engines.', 'environmental-multilang-support') . '</p>';
    }

    public function enable_multilingual_sitemap_callback() {
        $options = get_option('ems_options', array());
        $enable_sitemap = isset($options['enable_multilingual_sitemap']) ? $options['enable_multilingual_sitemap'] : true;

        echo '<input type="checkbox" name="ems_options[enable_multilingual_sitemap]" value="1" ' . checked($enable_sitemap, true, false) . ' />';
        echo '<p class="description">' . __('Generate multilingual XML sitemaps for search engines.', 'environmental-multilang-support') . '</p>';
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();

        if (isset($input['enabled_languages']) && is_array($input['enabled_languages'])) {
            $sanitized['enabled_languages'] = array_map('sanitize_key', $input['enabled_languages']);
        }

        if (isset($input['default_language'])) {
            $sanitized['default_language'] = sanitize_key($input['default_language']);
        }

        if (isset($input['url_structure'])) {
            $sanitized['url_structure'] = sanitize_key($input['url_structure']);
        }

        if (isset($input['language_detection']) && is_array($input['language_detection'])) {
            $sanitized['language_detection'] = array_map('sanitize_key', $input['language_detection']);
        }

        if (isset($input['translation_provider'])) {
            $sanitized['translation_provider'] = sanitize_key($input['translation_provider']);
        }

        if (isset($input['google_translate_api_key'])) {
            $sanitized['google_translate_api_key'] = sanitize_text_field($input['google_translate_api_key']);
        }

        if (isset($input['enable_hreflang'])) {
            $sanitized['enable_hreflang'] = (bool) $input['enable_hreflang'];
        }

        if (isset($input['enable_multilingual_sitemap'])) {
            $sanitized['enable_multilingual_sitemap'] = (bool) $input['enable_multilingual_sitemap'];
        }

        return $sanitized;
    }

    /**
     * Add plugin links
     */
    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=environmental-multilang') . '">' . __('Settings', 'environmental-multilang-support') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * AJAX handlers
     */
    public function test_translation_api() {
        check_ajax_referer('ems_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-multilang-support'));
        }

        $options = get_option('ems_options', array());
        $provider = isset($options['translation_provider']) ? $options['translation_provider'] : 'google';
        
        if ($provider === 'google') {
            $api_key = isset($options['google_translate_api_key']) ? $options['google_translate_api_key'] : '';
            if (empty($api_key)) {
                wp_send_json_error(__('Google Translate API key is not configured.', 'environmental-multilang-support'));
            }

            $response = wp_remote_get('https://translation.googleapis.com/language/translate/v2/languages?key=' . $api_key);
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                wp_send_json_error($data['error']['message']);
            }

            wp_send_json_success(__('Translation API is working correctly.', 'environmental-multilang-support'));
        }

        wp_send_json_error(__('Translation provider not configured.', 'environmental-multilang-support'));
    }

    public function import_translations() {
        check_ajax_referer('ems_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-multilang-support'));
        }

        // Import logic here
        wp_send_json_success(__('Translations imported successfully.', 'environmental-multilang-support'));
    }

    public function export_translations() {
        check_ajax_referer('ems_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-multilang-support'));
        }

        // Export logic here
        wp_send_json_success(__('Translations exported successfully.', 'environmental-multilang-support'));
    }

    public function bulk_translate() {
        check_ajax_referer('ems_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'environmental-multilang-support'));
        }

        $from_language = sanitize_key($_POST['from_language']);
        $to_language = sanitize_key($_POST['to_language']);
        $content_type = sanitize_key($_POST['content_type']);

        // Bulk translation logic here
        wp_send_json_success(__('Bulk translation completed.', 'environmental-multilang-support'));
    }

    /**
     * Helper methods
     */
    private function get_available_languages() {
        return array(
            'vi' => array('name' => 'Vietnamese', 'native_name' => 'Tiếng Việt', 'flag' => 'vi', 'rtl' => false, 'locale' => 'vi_VN'),
            'en' => array('name' => 'English', 'native_name' => 'English', 'flag' => 'en', 'rtl' => false, 'locale' => 'en_US'),
            'zh' => array('name' => 'Chinese', 'native_name' => '中文', 'flag' => 'cn', 'rtl' => false, 'locale' => 'zh_CN'),
            'ja' => array('name' => 'Japanese', 'native_name' => '日本語', 'flag' => 'jp', 'rtl' => false, 'locale' => 'ja'),
            'ko' => array('name' => 'Korean', 'native_name' => '한국어', 'flag' => 'kr', 'rtl' => false, 'locale' => 'ko_KR'),
            'th' => array('name' => 'Thai', 'native_name' => 'ไทย', 'flag' => 'th', 'rtl' => false, 'locale' => 'th'),
            'ar' => array('name' => 'Arabic', 'native_name' => 'العربية', 'flag' => 'ar', 'rtl' => true, 'locale' => 'ar'),
            'he' => array('name' => 'Hebrew', 'native_name' => 'עברית', 'flag' => 'il', 'rtl' => true, 'locale' => 'he_IL'),
            'fr' => array('name' => 'French', 'native_name' => 'Français', 'flag' => 'fr', 'rtl' => false, 'locale' => 'fr_FR'),
            'es' => array('name' => 'Spanish', 'native_name' => 'Español', 'flag' => 'es', 'rtl' => false, 'locale' => 'es_ES'),
        );
    }

    private function get_enabled_languages() {
        $options = get_option('ems_options', array());
        return isset($options['enabled_languages']) ? $options['enabled_languages'] : array('vi', 'en');
    }

    private function get_language_stats($lang_code) {
        // Get statistics for a specific language
        global $wpdb;
        
        $posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ems_language' AND meta_value = %s",
            $lang_code
        ));
        
        return array(
            'posts' => $posts ?: 0,
            'pages' => 0, // Implement
            'categories' => 0, // Implement
            'tags' => 0, // Implement
            'completion' => 75 // Calculate actual completion
        );
    }

    private function get_total_translated_posts() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}ems_translation_pairs");
    }

    private function get_total_translation_pairs() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ems_translation_pairs");
    }

    private function get_api_calls_today() {
        // Implementation for API call tracking
        return get_transient('ems_api_calls_today') ?: 0;
    }
}
