<?php
/**
 * Environmental Platform ACF Export/Import Manager
 * 
 * Provides admin interface for exporting and importing ACF field groups
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0 - Phase 30
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_ACF_Export_Import {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_ep_export_acf_fields', array($this, 'handle_export'));
        add_action('admin_post_ep_import_acf_fields', array($this, 'handle_import'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=env_article',
            'ACF Export/Import',
            'ACF Export/Import',
            'manage_options',
            'ep-acf-export-import',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'env_article_page_ep-acf-export-import') {
            return;
        }
        
        wp_enqueue_script(
            'ep-acf-export-import',
            EP_CORE_PLUGIN_URL . 'assets/acf-export-import.js',
            array('jquery'),
            EP_CORE_VERSION,
            true
        );
        
        wp_localize_script('ep-acf-export-import', 'epAcfExport', array(
            'nonce' => wp_create_nonce('ep_acf_export_import'),
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Environmental Platform - ACF Export/Import</h1>
            
            <div class="ep-acf-export-import-container">
                
                <!-- Export Section -->
                <div class="ep-section">
                    <h2>Export ACF Field Groups</h2>
                    <p>Export all environmental platform ACF field groups to PHP files for version control.</p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('ep_acf_export', 'ep_acf_export_nonce'); ?>
                        <input type="hidden" name="action" value="ep_export_acf_fields">
                        
                        <div class="export-options">
                            <h3>Export Options</h3>
                            
                            <label>
                                <input type="checkbox" name="export_options[]" value="field_groups" checked>
                                Field Groups
                            </label>
                            
                            <label>
                                <input type="checkbox" name="export_options[]" value="location_rules" checked>
                                Location Rules
                            </label>
                            
                            <label>
                                <input type="checkbox" name="export_options[]" value="conditional_logic" checked>
                                Conditional Logic
                            </label>
                            
                            <label>
                                <input type="checkbox" name="export_options[]" value="custom_functions">
                                Custom Functions
                            </label>
                        </div>
                        
                        <div class="export-format">
                            <h3>Export Format</h3>
                            
                            <label>
                                <input type="radio" name="export_format" value="php" checked>
                                PHP File (Recommended for version control)
                            </label>
                            
                            <label>
                                <input type="radio" name="export_format" value="json">
                                JSON File
                            </label>
                        </div>
                        
                        <?php submit_button('Export Field Groups', 'primary', 'submit', false); ?>
                    </form>
                </div>
                
                <!-- Import Section -->
                <div class="ep-section">
                    <h2>Import ACF Field Groups</h2>
                    <p>Import ACF field groups from previously exported files.</p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('ep_acf_import', 'ep_acf_import_nonce'); ?>
                        <input type="hidden" name="action" value="ep_import_acf_fields">
                        
                        <div class="import-file">
                            <h3>Select Import File</h3>
                            <input type="file" name="import_file" accept=".php,.json" required>
                            <p class="description">Select a PHP or JSON file exported from this tool.</p>
                        </div>
                        
                        <div class="import-options">
                            <h3>Import Options</h3>
                            
                            <label>
                                <input type="checkbox" name="import_options[]" value="overwrite_existing" checked>
                                Overwrite existing field groups
                            </label>
                            
                            <label>
                                <input type="checkbox" name="import_options[]" value="backup_existing">
                                Create backup before import
                            </label>
                            
                            <label>
                                <input type="checkbox" name="import_options[]" value="validate_fields" checked>
                                Validate field structure
                            </label>
                        </div>
                        
                        <?php submit_button('Import Field Groups', 'secondary', 'submit', false); ?>
                    </form>
                </div>
                
                <!-- Current Field Groups -->
                <div class="ep-section">
                    <h2>Current Field Groups</h2>
                    <div class="current-field-groups">
                        <?php $this->display_current_field_groups(); ?>
                    </div>
                </div>
                
                <!-- Git Integration -->
                <div class="ep-section">
                    <h2>Git Integration</h2>
                    <p>Automatically sync ACF field groups with your Git repository.</p>
                    
                    <div class="git-integration">
                        <button type="button" id="sync-with-git" class="button">
                            Sync Field Groups to Git
                        </button>
                        
                        <div class="git-status">
                            <h4>Git Status</h4>
                            <div id="git-status-output">
                                <p>Click "Sync Field Groups to Git" to check status.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
        .ep-acf-export-import-container {
            max-width: 1200px;
        }
        
        .ep-section {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .ep-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .export-options label,
        .export-format label,
        .import-options label {
            display: block;
            margin-bottom: 10px;
        }
        
        .export-options input,
        .export-format input,
        .import-options input {
            margin-right: 8px;
        }
        
        .current-field-groups {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .field-group-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            background: #f9f9f9;
        }
        
        .field-group-card h4 {
            margin: 0 0 10px 0;
            color: #2e7d32;
        }
        
        .field-group-meta {
            font-size: 12px;
            color: #666;
        }
        
        .git-integration {
            background: #f0f8f0;
            border: 1px solid #c8e6c9;
            border-radius: 6px;
            padding: 15px;
        }
        
        .git-status {
            margin-top: 15px;
        }
        
        #git-status-output {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        </style>
        <?php
    }
    
    /**
     * Display current field groups
     */
    private function display_current_field_groups() {
        if (!function_exists('acf_get_field_groups')) {
            echo '<p>ACF is not active or available.</p>';
            return;
        }
        
        $field_groups = acf_get_field_groups();
        
        if (empty($field_groups)) {
            echo '<p>No field groups found.</p>';
            return;
        }
        
        foreach ($field_groups as $field_group) {
            if (strpos($field_group['key'], 'group_') === 0) {
                $field_count = count(acf_get_fields($field_group['key']));
                ?>
                <div class="field-group-card">
                    <h4><?php echo esc_html($field_group['title']); ?></h4>
                    <div class="field-group-meta">
                        <strong>Key:</strong> <?php echo esc_html($field_group['key']); ?><br>
                        <strong>Fields:</strong> <?php echo $field_count; ?><br>
                        <strong>Position:</strong> <?php echo esc_html($field_group['position']); ?><br>
                        <strong>Status:</strong> <?php echo $field_group['active'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Handle export request
     */
    public function handle_export() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['ep_acf_export_nonce'], 'ep_acf_export')) {
            wp_die('Unauthorized');
        }
        
        $export_options = isset($_POST['export_options']) ? $_POST['export_options'] : array();
        $export_format = isset($_POST['export_format']) ? $_POST['export_format'] : 'php';
        
        if ($export_format === 'php') {
            $this->export_to_php($export_options);
        } else {
            $this->export_to_json($export_options);
        }
    }
    
    /**
     * Export to PHP format
     */
    private function export_to_php($options) {
        if (!function_exists('acf_get_field_groups')) {
            wp_die('ACF is not available');
        }
        
        $field_groups = acf_get_field_groups();
        $timestamp = date('Y-m-d-H-i-s');
        
        $php_content = "<?php\n";
        $php_content .= "/**\n";
        $php_content .= " * Environmental Platform ACF Field Groups Export\n";
        $php_content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $php_content .= " * Options: " . implode(', ', $options) . "\n";
        $php_content .= " */\n\n";
        $php_content .= "if (!defined('ABSPATH')) {\n    exit;\n}\n\n";
        $php_content .= "if (function_exists('acf_add_local_field_group')) {\n\n";
        
        foreach ($field_groups as $field_group) {
            if (strpos($field_group['key'], 'group_') === 0) {
                // Get full field group with fields
                $full_group = acf_get_field_group($field_group['key']);
                $fields = acf_get_fields($field_group['key']);
                
                if ($fields) {
                    $full_group['fields'] = $fields;
                }
                
                $php_content .= "    // " . $field_group['title'] . "\n";
                $php_content .= "    acf_add_local_field_group(" . var_export($full_group, true) . ");\n\n";
            }
        }
        
        $php_content .= "}\n";
        
        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $filename = 'ep-acf-field-groups-' . $timestamp . '.php';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($filepath, $php_content)) {
            // Force download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            
            // Clean up
            unlink($filepath);
        } else {
            wp_die('Failed to create export file');
        }
        
        exit;
    }
    
    /**
     * Export to JSON format
     */
    private function export_to_json($options) {
        if (!function_exists('acf_get_field_groups')) {
            wp_die('ACF is not available');
        }
        
        $field_groups = acf_get_field_groups();
        $export_data = array(
            'generated' => date('Y-m-d H:i:s'),
            'options' => $options,
            'field_groups' => array()
        );
        
        foreach ($field_groups as $field_group) {
            if (strpos($field_group['key'], 'group_') === 0) {
                $full_group = acf_get_field_group($field_group['key']);
                $fields = acf_get_fields($field_group['key']);
                
                if ($fields) {
                    $full_group['fields'] = $fields;
                }
                
                $export_data['field_groups'][] = $full_group;
            }
        }
        
        $timestamp = date('Y-m-d-H-i-s');
        $filename = 'ep-acf-field-groups-' . $timestamp . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        
        exit;
    }
    
    /**
     * Handle import request
     */
    public function handle_import() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['ep_acf_import_nonce'], 'ep_acf_import')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die('No file uploaded or upload error');
        }
        
        $import_options = isset($_POST['import_options']) ? $_POST['import_options'] : array();
        $file_path = $_FILES['import_file']['tmp_name'];
        $file_name = $_FILES['import_file']['name'];
        
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        
        if ($file_extension === 'php') {
            $this->import_from_php($file_path, $import_options);
        } elseif ($file_extension === 'json') {
            $this->import_from_json($file_path, $import_options);
        } else {
            wp_die('Invalid file format. Only PHP and JSON files are supported.');
        }
        
        wp_redirect(admin_url('edit.php?post_type=env_article&page=ep-acf-export-import&imported=1'));
        exit;
    }
    
    /**
     * Import from PHP file
     */
    private function import_from_php($file_path, $options) {
        // Include the PHP file to register field groups
        include_once $file_path;
        
        // Success message will be shown on redirect
    }
    
    /**
     * Import from JSON file
     */
    private function import_from_json($file_path, $options) {
        $json_content = file_get_contents($file_path);
        $import_data = json_decode($json_content, true);
        
        if (!$import_data || !isset($import_data['field_groups'])) {
            wp_die('Invalid JSON file format');
        }
        
        foreach ($import_data['field_groups'] as $field_group) {
            if (function_exists('acf_add_local_field_group')) {
                acf_add_local_field_group($field_group);
            }
        }
    }
}

// Initialize ACF Export/Import
new EP_ACF_Export_Import();
