<?php
/**
 * Admin Interface Class
 *
 * @package Environmental_Advanced_Search
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAS_Admin {
    
    /**
     * Initialize admin interface
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_eas_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_eas_reset_analytics', array($this, 'reset_analytics'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Advanced Search', 'environmental-advanced-search'),
            __('Advanced Search', 'environmental-advanced-search'),
            'manage_options',
            'environmental-advanced-search',
            array($this, 'admin_page'),
            'dashicons-search',
            30
        );
        
        add_submenu_page(
            'environmental-advanced-search',
            __('Search Settings', 'environmental-advanced-search'),
            __('Settings', 'environmental-advanced-search'),
            'manage_options',
            'environmental-advanced-search',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'environmental-advanced-search',
            __('Search Analytics', 'environmental-advanced-search'),
            __('Analytics', 'environmental-advanced-search'),
            'manage_options',
            'eas-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'environmental-advanced-search',
            __('Elasticsearch', 'environmental-advanced-search'),
            __('Elasticsearch', 'environmental-advanced-search'),
            'manage_options',
            'eas-elasticsearch',
            array($this, 'elasticsearch_page')
        );
        
        add_submenu_page(
            'environmental-advanced-search',
            __('Tools', 'environmental-advanced-search'),
            __('Tools', 'environmental-advanced-search'),
            'manage_options',
            'eas-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('eas_settings', 'eas_enable_elasticsearch');
        register_setting('eas_settings', 'eas_elasticsearch_host');
        register_setting('eas_settings', 'eas_elasticsearch_port');
        register_setting('eas_settings', 'eas_elasticsearch_index');
        register_setting('eas_settings', 'eas_elasticsearch_username');
        register_setting('eas_settings', 'eas_elasticsearch_password');
        register_setting('eas_settings', 'eas_google_maps_api_key');
        register_setting('eas_settings', 'eas_search_suggestions');
        register_setting('eas_settings', 'eas_enable_analytics');
        register_setting('eas_settings', 'eas_analytics_retention_days');
        register_setting('eas_settings', 'eas_search_weights');
        register_setting('eas_settings', 'eas_excluded_post_types');
        register_setting('eas_settings', 'eas_faceted_search_enabled');
        register_setting('eas_settings', 'eas_geolocation_enabled');
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('Environmental Advanced Search Settings', 'environmental-advanced-search'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=environmental-advanced-search&tab=general" 
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'environmental-advanced-search'); ?>
                </a>
                <a href="?page=environmental-advanced-search&tab=elasticsearch" 
                   class="nav-tab <?php echo $active_tab === 'elasticsearch' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Elasticsearch', 'environmental-advanced-search'); ?>
                </a>
                <a href="?page=environmental-advanced-search&tab=weights" 
                   class="nav-tab <?php echo $active_tab === 'weights' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Search Weights', 'environmental-advanced-search'); ?>
                </a>
                <a href="?page=environmental-advanced-search&tab=geolocation" 
                   class="nav-tab <?php echo $active_tab === 'geolocation' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Geolocation', 'environmental-advanced-search'); ?>
                </a>
                <a href="?page=environmental-advanced-search&tab=analytics" 
                   class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Analytics', 'environmental-advanced-search'); ?>
                </a>
            </nav>
            
            <form method="post" action="options.php" id="eas-settings-form">
                <?php settings_fields('eas_settings'); ?>
                
                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_settings();
                        break;
                    case 'elasticsearch':
                        $this->render_elasticsearch_settings();
                        break;
                    case 'weights':
                        $this->render_weights_settings();
                        break;
                    case 'geolocation':
                        $this->render_geolocation_settings();
                        break;
                    case 'analytics':
                        $this->render_analytics_settings();
                        break;
                }
                ?>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render general settings
     */
    private function render_general_settings() {
        $search_suggestions = get_option('eas_search_suggestions', true);
        $faceted_search = get_option('eas_faceted_search_enabled', true);
        $excluded_post_types = get_option('eas_excluded_post_types', array());
        $post_types = get_post_types(array('public' => true), 'objects');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Search Suggestions', 'environmental-advanced-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="eas_search_suggestions" value="1" <?php checked($search_suggestions); ?> />
                        <?php _e('Show search suggestions while typing', 'environmental-advanced-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Enable Faceted Search', 'environmental-advanced-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="eas_faceted_search_enabled" value="1" <?php checked($faceted_search); ?> />
                        <?php _e('Enable advanced filtering with facets', 'environmental-advanced-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Excluded Post Types', 'environmental-advanced-search'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Select post types to exclude from search', 'environmental-advanced-search'); ?></legend>
                        <?php foreach ($post_types as $post_type): ?>
                        <label>
                            <input type="checkbox" 
                                   name="eas_excluded_post_types[]" 
                                   value="<?php echo esc_attr($post_type->name); ?>"
                                   <?php checked(in_array($post_type->name, $excluded_post_types)); ?> />
                            <?php echo esc_html($post_type->label); ?>
                        </label><br>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Post types selected here will be excluded from search results.', 'environmental-advanced-search'); ?></p>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Elasticsearch settings
     */
    private function render_elasticsearch_settings() {
        $enable_elasticsearch = get_option('eas_enable_elasticsearch', false);
        $host = get_option('eas_elasticsearch_host', 'localhost');
        $port = get_option('eas_elasticsearch_port', '9200');
        $index = get_option('eas_elasticsearch_index', 'environmental_platform');
        $username = get_option('eas_elasticsearch_username', '');
        $password = get_option('eas_elasticsearch_password', '');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Elasticsearch', 'environmental-advanced-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="eas_enable_elasticsearch" value="1" <?php checked($enable_elasticsearch); ?> />
                        <?php _e('Use Elasticsearch for advanced search capabilities', 'environmental-advanced-search'); ?>
                    </label>
                    <p class="description"><?php _e('Elasticsearch provides faster and more relevant search results.', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Elasticsearch Host', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="text" name="eas_elasticsearch_host" value="<?php echo esc_attr($host); ?>" class="regular-text" />
                    <p class="description"><?php _e('The hostname or IP address of your Elasticsearch server.', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Elasticsearch Port', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="number" name="eas_elasticsearch_port" value="<?php echo esc_attr($port); ?>" min="1" max="65535" />
                    <p class="description"><?php _e('The port number for your Elasticsearch server (default: 9200).', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Index Name', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="text" name="eas_elasticsearch_index" value="<?php echo esc_attr($index); ?>" class="regular-text" />
                    <p class="description"><?php _e('The name of the Elasticsearch index to use.', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Username', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="text" name="eas_elasticsearch_username" value="<?php echo esc_attr($username); ?>" class="regular-text" />
                    <p class="description"><?php _e('Username for Elasticsearch authentication (if required).', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Password', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="password" name="eas_elasticsearch_password" value="<?php echo esc_attr($password); ?>" class="regular-text" />
                    <p class="description"><?php _e('Password for Elasticsearch authentication (if required).', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="eas-elasticsearch-status">
            <h3><?php _e('Connection Status', 'environmental-advanced-search'); ?></h3>
            <div id="eas-elasticsearch-status-container">
                <button type="button" id="eas-test-elasticsearch" class="button button-secondary">
                    <?php _e('Test Connection', 'environmental-advanced-search'); ?>
                </button>
                <div id="eas-elasticsearch-results"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render search weights settings
     */
    private function render_weights_settings() {
        $weights = get_option('eas_search_weights', array(
            'title' => 3.0,
            'content' => 1.0,
            'excerpt' => 2.0,
            'meta' => 1.5,
            'taxonomy' => 2.0
        ));
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Search Field Weights', 'environmental-advanced-search'); ?></th>
                <td>
                    <p class="description"><?php _e('Adjust the importance of different fields in search results. Higher values mean more importance.', 'environmental-advanced-search'); ?></p>
                    
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Field', 'environmental-advanced-search'); ?></th>
                                <th><?php _e('Weight', 'environmental-advanced-search'); ?></th>
                                <th><?php _e('Description', 'environmental-advanced-search'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php _e('Title', 'environmental-advanced-search'); ?></td>
                                <td>
                                    <input type="number" 
                                           name="eas_search_weights[title]" 
                                           value="<?php echo esc_attr($weights['title']); ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="10" 
                                           class="small-text" />
                                </td>
                                <td><?php _e('Post/page titles', 'environmental-advanced-search'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Content', 'environmental-advanced-search'); ?></td>
                                <td>
                                    <input type="number" 
                                           name="eas_search_weights[content]" 
                                           value="<?php echo esc_attr($weights['content']); ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="10" 
                                           class="small-text" />
                                </td>
                                <td><?php _e('Post/page content', 'environmental-advanced-search'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Excerpt', 'environmental-advanced-search'); ?></td>
                                <td>
                                    <input type="number" 
                                           name="eas_search_weights[excerpt]" 
                                           value="<?php echo esc_attr($weights['excerpt']); ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="10" 
                                           class="small-text" />
                                </td>
                                <td><?php _e('Post/page excerpts', 'environmental-advanced-search'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Meta Fields', 'environmental-advanced-search'); ?></td>
                                <td>
                                    <input type="number" 
                                           name="eas_search_weights[meta]" 
                                           value="<?php echo esc_attr($weights['meta']); ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="10" 
                                           class="small-text" />
                                </td>
                                <td><?php _e('Custom fields and meta data', 'environmental-advanced-search'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Taxonomies', 'environmental-advanced-search'); ?></td>
                                <td>
                                    <input type="number" 
                                           name="eas_search_weights[taxonomy]" 
                                           value="<?php echo esc_attr($weights['taxonomy']); ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="10" 
                                           class="small-text" />
                                </td>
                                <td><?php _e('Categories, tags, and custom taxonomies', 'environmental-advanced-search'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render geolocation settings
     */
    private function render_geolocation_settings() {
        $geolocation_enabled = get_option('eas_geolocation_enabled', false);
        $google_maps_api_key = get_option('eas_google_maps_api_key', '');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Geolocation Search', 'environmental-advanced-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="eas_geolocation_enabled" value="1" <?php checked($geolocation_enabled); ?> />
                        <?php _e('Allow users to search by location and distance', 'environmental-advanced-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Google Maps API Key', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="text" name="eas_google_maps_api_key" value="<?php echo esc_attr($google_maps_api_key); ?>" class="regular-text" />
                    <p class="description">
                        <?php 
                        printf(
                            __('Optional. Get your API key from <a href="%s" target="_blank">Google Cloud Console</a>. If not provided, OpenStreetMap geocoding will be used.', 'environmental-advanced-search'),
                            'https://console.cloud.google.com/apis/credentials'
                        ); 
                        ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render analytics settings
     */
    private function render_analytics_settings() {
        $enable_analytics = get_option('eas_enable_analytics', true);
        $retention_days = get_option('eas_analytics_retention_days', 365);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Analytics', 'environmental-advanced-search'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="eas_enable_analytics" value="1" <?php checked($enable_analytics); ?> />
                        <?php _e('Track search queries and performance metrics', 'environmental-advanced-search'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Data Retention', 'environmental-advanced-search'); ?></th>
                <td>
                    <input type="number" name="eas_analytics_retention_days" value="<?php echo esc_attr($retention_days); ?>" min="30" max="3650" />
                    <?php _e('days', 'environmental-advanced-search'); ?>
                    <p class="description"><?php _e('How long to keep analytics data before automatic cleanup.', 'environmental-advanced-search'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="eas-analytics-actions">
            <h3><?php _e('Analytics Actions', 'environmental-advanced-search'); ?></h3>
            <p>
                <button type="button" id="eas-reset-analytics" class="button button-secondary">
                    <?php _e('Reset All Analytics Data', 'environmental-advanced-search'); ?>
                </button>
                <span class="description"><?php _e('This will permanently delete all search analytics data.', 'environmental-advanced-search'); ?></span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Search Analytics', 'environmental-advanced-search'); ?></h1>
            
            <div class="eas-analytics-dashboard">
                
                <!-- Date Range Selector -->
                <div class="eas-date-range">
                    <label for="eas-date-from"><?php _e('From:', 'environmental-advanced-search'); ?></label>
                    <input type="date" id="eas-date-from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" />
                    
                    <label for="eas-date-to"><?php _e('To:', 'environmental-advanced-search'); ?></label>
                    <input type="date" id="eas-date-to" value="<?php echo date('Y-m-d'); ?>" />
                    
                    <button type="button" id="eas-update-analytics" class="button button-primary">
                        <?php _e('Update', 'environmental-advanced-search'); ?>
                    </button>
                    
                    <button type="button" id="eas-export-analytics" class="button button-secondary">
                        <?php _e('Export CSV', 'environmental-advanced-search'); ?>
                    </button>
                </div>
                
                <!-- Summary Cards -->
                <div class="eas-summary-cards">
                    <div class="eas-card">
                        <h3><?php _e('Total Searches', 'environmental-advanced-search'); ?></h3>
                        <div class="eas-metric" id="eas-total-searches">-</div>
                    </div>
                    
                    <div class="eas-card">
                        <h3><?php _e('Unique Users', 'environmental-advanced-search'); ?></h3>
                        <div class="eas-metric" id="eas-unique-users">-</div>
                    </div>
                    
                    <div class="eas-card">
                        <h3><?php _e('Avg. Results', 'environmental-advanced-search'); ?></h3>
                        <div class="eas-metric" id="eas-avg-results">-</div>
                    </div>
                    
                    <div class="eas-card">
                        <h3><?php _e('Zero Results', 'environmental-advanced-search'); ?></h3>
                        <div class="eas-metric" id="eas-zero-results">-</div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="eas-charts">
                    <div class="eas-chart-container">
                        <h3><?php _e('Search Volume Over Time', 'environmental-advanced-search'); ?></h3>
                        <canvas id="eas-volume-chart"></canvas>
                    </div>
                </div>
                
                <!-- Data Tables -->
                <div class="eas-data-tables">
                    <div class="eas-table-container">
                        <h3><?php _e('Top Search Queries', 'environmental-advanced-search'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Query', 'environmental-advanced-search'); ?></th>
                                    <th><?php _e('Count', 'environmental-advanced-search'); ?></th>
                                    <th><?php _e('Avg Results', 'environmental-advanced-search'); ?></th>
                                    <th><?php _e('Avg Time', 'environmental-advanced-search'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="eas-top-searches">
                                <tr><td colspan="4"><?php _e('Loading...', 'environmental-advanced-search'); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="eas-table-container">
                        <h3><?php _e('No Results Queries', 'environmental-advanced-search'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Query', 'environmental-advanced-search'); ?></th>
                                    <th><?php _e('Count', 'environmental-advanced-search'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="eas-no-results">
                                <tr><td colspan="2"><?php _e('Loading...', 'environmental-advanced-search'); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Elasticsearch page
     */
    public function elasticsearch_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Elasticsearch Management', 'environmental-advanced-search'); ?></h1>
            
            <div class="eas-elasticsearch-dashboard">
                
                <!-- Status Section -->
                <div class="eas-status-section">
                    <h2><?php _e('Connection Status', 'environmental-advanced-search'); ?></h2>
                    <div id="eas-elasticsearch-status">
                        <button type="button" id="eas-check-status" class="button button-primary">
                            <?php _e('Check Status', 'environmental-advanced-search'); ?>
                        </button>
                        <div id="eas-status-results"></div>
                    </div>
                </div>
                
                <!-- Index Management -->
                <div class="eas-index-section">
                    <h2><?php _e('Index Management', 'environmental-advanced-search'); ?></h2>
                    
                    <div class="eas-index-actions">
                        <button type="button" id="eas-create-index" class="button button-secondary">
                            <?php _e('Create Index', 'environmental-advanced-search'); ?>
                        </button>
                        
                        <button type="button" id="eas-delete-index" class="button button-secondary">
                            <?php _e('Delete Index', 'environmental-advanced-search'); ?>
                        </button>
                        
                        <button type="button" id="eas-optimize-index" class="button button-secondary">
                            <?php _e('Optimize Index', 'environmental-advanced-search'); ?>
                        </button>
                    </div>
                    
                    <div id="eas-index-results"></div>
                </div>
                
                <!-- Bulk Reindexing -->
                <div class="eas-reindex-section">
                    <h2><?php _e('Content Reindexing', 'environmental-advanced-search'); ?></h2>
                    
                    <div class="eas-reindex-controls">
                        <label for="eas-batch-size"><?php _e('Batch Size:', 'environmental-advanced-search'); ?></label>
                        <input type="number" id="eas-batch-size" value="100" min="1" max="1000" />
                        
                        <button type="button" id="eas-start-reindex" class="button button-primary">
                            <?php _e('Start Reindexing', 'environmental-advanced-search'); ?>
                        </button>
                        
                        <button type="button" id="eas-stop-reindex" class="button button-secondary" style="display: none;">
                            <?php _e('Stop', 'environmental-advanced-search'); ?>
                        </button>
                    </div>
                    
                    <div class="eas-reindex-progress" style="display: none;">
                        <div class="eas-progress-bar">
                            <div class="eas-progress-fill"></div>
                        </div>
                        <div class="eas-progress-text"></div>
                    </div>
                    
                    <div id="eas-reindex-log"></div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Search Tools', 'environmental-advanced-search'); ?></h1>
            
            <div class="eas-tools-dashboard">
                
                <!-- Database Optimization -->
                <div class="eas-tool-section">
                    <h2><?php _e('Database Optimization', 'environmental-advanced-search'); ?></h2>
                    <p><?php _e('Optimize database tables for better search performance.', 'environmental-advanced-search'); ?></p>
                    
                    <button type="button" id="eas-optimize-database" class="button button-primary">
                        <?php _e('Optimize Database', 'environmental-advanced-search'); ?>
                    </button>
                    
                    <div id="eas-optimize-db-results"></div>
                </div>
                
                <!-- Cache Management -->
                <div class="eas-tool-section">
                    <h2><?php _e('Cache Management', 'environmental-advanced-search'); ?></h2>
                    <p><?php _e('Clear search-related caches to ensure fresh results.', 'environmental-advanced-search'); ?></p>
                    
                    <button type="button" id="eas-clear-cache" class="button button-secondary">
                        <?php _e('Clear Search Cache', 'environmental-advanced-search'); ?>
                    </button>
                    
                    <div id="eas-clear-cache-results"></div>
                </div>
                
                <!-- Search Test -->
                <div class="eas-tool-section">
                    <h2><?php _e('Search Test', 'environmental-advanced-search'); ?></h2>
                    <p><?php _e('Test search functionality and performance.', 'environmental-advanced-search'); ?></p>
                    
                    <div class="eas-search-test">
                        <input type="text" id="eas-test-query" placeholder="<?php _e('Enter test query...', 'environmental-advanced-search'); ?>" />
                        <button type="button" id="eas-run-search-test" class="button button-primary">
                            <?php _e('Run Test', 'environmental-advanced-search'); ?>
                        </button>
                    </div>
                    
                    <div id="eas-search-test-results"></div>
                </div>
                
                <!-- Import/Export -->
                <div class="eas-tool-section">
                    <h2><?php _e('Settings Import/Export', 'environmental-advanced-search'); ?></h2>
                    
                    <div class="eas-import-export">
                        <div class="eas-export">
                            <h3><?php _e('Export Settings', 'environmental-advanced-search'); ?></h3>
                            <button type="button" id="eas-export-settings" class="button button-secondary">
                                <?php _e('Export Settings', 'environmental-advanced-search'); ?>
                            </button>
                        </div>
                        
                        <div class="eas-import">
                            <h3><?php _e('Import Settings', 'environmental-advanced-search'); ?></h3>
                            <input type="file" id="eas-import-file" accept=".json" />
                            <button type="button" id="eas-import-settings" class="button button-secondary">
                                <?php _e('Import Settings', 'environmental-advanced-search'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div id="eas-import-export-results"></div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'environmental-advanced-search') === false && strpos($hook, 'eas-') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'eas-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'eas-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
        
        wp_localize_script('eas-admin', 'easAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eas_admin_nonce'),
            'strings' => array(
                'confirm_reset' => __('Are you sure you want to reset all analytics data? This cannot be undone.', 'environmental-advanced-search'),
                'confirm_delete_index' => __('Are you sure you want to delete the Elasticsearch index? This will remove all indexed content.', 'environmental-advanced-search'),
                'reindexing' => __('Reindexing in progress...', 'environmental-advanced-search'),
                'completed' => __('Completed', 'environmental-advanced-search'),
                'error' => __('An error occurred', 'environmental-advanced-search')
            )
        ));
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        $settings = $_POST['settings'];
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        wp_send_json_success(__('Settings saved successfully', 'environmental-advanced-search'));
    }
    
    /**
     * Reset analytics data
     */
    public function reset_analytics() {
        check_ajax_referer('eas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'environmental-advanced-search'));
        }
        
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'eas_search_analytics';
        $popular_table = $wpdb->prefix . 'eas_popular_searches';
        
        $wpdb->query("TRUNCATE TABLE {$analytics_table}");
        $wpdb->query("TRUNCATE TABLE {$popular_table}");
        
        // Reset filter usage stats
        delete_option('eas_filter_usage_stats');
        delete_option('eas_total_searches');
        delete_option('eas_unique_queries');
        
        wp_send_json_success(__('Analytics data has been reset', 'environmental-advanced-search'));
    }
}
