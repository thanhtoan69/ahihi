<?php
/**
 * WordPress Backup Configuration - Must Use Plugin
 * Phase 47: Backup System Configuration
 * Environmental Platform Backup Management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environmental Platform Backup System
 */
class EP_Backup_System {
    
    private $backup_dir;
    private $db_backup_retention = 7; // days
    private $file_backup_retention = 7; // days
    
    public function __construct() {
        $this->backup_dir = WP_CONTENT_DIR . '/ep-backups/';
        
        // Hooks
        add_action('init', array($this, 'init_backup_system'));
        add_action('admin_menu', array($this, 'add_backup_menu'));
        add_action('wp_ajax_ep_create_backup', array($this, 'ajax_create_backup'));
        add_action('wp_ajax_ep_restore_backup', array($this, 'ajax_restore_backup'));
        
        // Schedule automatic backups
        add_action('wp', array($this, 'schedule_backups'));
        add_action('ep_daily_backup', array($this, 'create_automated_backup'));
        
        // Cleanup old backups
        add_action('wp_scheduled_delete', array($this, 'cleanup_old_backups'));
    }
    
    /**
     * Initialize backup system
     */
    public function init_backup_system() {
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Add protection files
            file_put_contents($this->backup_dir . '.htaccess', 'deny from all');
            file_put_contents($this->backup_dir . 'index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * Schedule automatic backups
     */
    public function schedule_backups() {
        if (!wp_next_scheduled('ep_daily_backup')) {
            wp_schedule_event(time(), 'daily', 'ep_daily_backup');
        }
    }
    
    /**
     * Add backup menu to admin
     */
    public function add_backup_menu() {
        if (current_user_can('manage_options')) {
            add_management_page(
                'Environmental Platform Backup',
                'EP Backup',
                'manage_options',
                'ep-backup-manager',
                array($this, 'backup_manager_page')
            );
        }
    }
    
    /**
     * Backup manager page
     */
    public function backup_manager_page() {
        ?>
        <div class="wrap">
            <h1>Environmental Platform Backup Manager</h1>
            
            <div class="card">
                <h2>Create New Backup</h2>
                <p>Create a complete backup of your Environmental Platform installation.</p>
                <button id="ep-create-backup" class="button button-primary">Create Backup Now</button>
                <div id="ep-backup-progress" style="display:none;">
                    <p>Creating backup... <span class="spinner is-active"></span></p>
                </div>
            </div>
            
            <div class="card">
                <h2>Backup Status</h2>
                <?php
                $next_backup = wp_next_scheduled('ep_daily_backup');
                if ($next_backup) {
                    echo '<p><strong>Next Automatic Backup:</strong> ' . date('Y-m-d H:i:s', $next_backup) . '</p>';
                } else {
                    echo '<p><strong>Automatic Backup:</strong> Not scheduled</p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2>Available Backups</h2>
                <?php $this->display_backup_list(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ep-create-backup').click(function() {
                $('#ep-backup-progress').show();
                $(this).prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'ep_create_backup',
                    nonce: '<?php echo wp_create_nonce('ep_backup_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Backup created successfully!');
                        location.reload();
                    } else {
                        alert('Backup failed: ' + response.data);
                    }
                }).always(function() {
                    $('#ep-backup-progress').hide();
                    $('#ep-create-backup').prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display backup list
     */
    private function display_backup_list() {
        $backups = $this->get_backup_list();
        
        if (empty($backups)) {
            echo '<p>No backups found.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat striped">';
        echo '<thead><tr><th>Backup Date</th><th>Size</th><th>Type</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($backups as $backup) {
            echo '<tr>';
            echo '<td>' . date('Y-m-d H:i:s', $backup['date']) . '</td>';
            echo '<td>' . size_format($backup['size']) . '</td>';
            echo '<td>' . ucfirst($backup['type']) . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('tools.php?page=ep-backup-manager&download=' . urlencode($backup['filename'])) . '" class="button">Download</a> ';
            echo '<a href="' . admin_url('tools.php?page=ep-backup-manager&delete=' . urlencode($backup['filename'])) . '" class="button" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Get list of available backups
     */
    private function get_backup_list() {
        $backups = array();
        
        if (!is_dir($this->backup_dir)) {
            return $backups;
        }
        
        $files = scandir($this->backup_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === 'index.php') {
                continue;
            }
            
            $filepath = $this->backup_dir . $file;
            if (is_file($filepath)) {
                $backups[] = array(
                    'filename' => $file,
                    'date' => filemtime($filepath),
                    'size' => filesize($filepath),
                    'type' => (strpos($file, 'database') !== false) ? 'database' : 'files'
                );
            }
        }
        
        // Sort by date, newest first
        usort($backups, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        return $backups;
    }
    
    /**
     * AJAX handler for creating backup
     */
    public function ajax_create_backup() {
        check_ajax_referer('ep_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = $this->create_backup();
        
        if ($result) {
            wp_send_json_success('Backup created successfully');
        } else {
            wp_send_json_error('Failed to create backup');
        }
    }
    
    /**
     * Create complete backup
     */
    public function create_backup() {
        $timestamp = date('Y-m-d_H-i-s');
        
        try {
            // Create database backup
            $db_backup = $this->create_database_backup($timestamp);
            
            // Create files backup
            $files_backup = $this->create_files_backup($timestamp);
            
            // Log backup creation
            $this->log_backup_event('Backup created successfully', array(
                'database_backup' => $db_backup,
                'files_backup' => $files_backup,
                'timestamp' => $timestamp
            ));
            
            return true;
            
        } catch (Exception $e) {
            $this->log_backup_event('Backup failed: ' . $e->getMessage(), array(
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ));
            
            return false;
        }
    }
    
    /**
     * Create database backup
     */
    private function create_database_backup($timestamp) {
        global $wpdb;
        
        $backup_file = $this->backup_dir . 'database_' . $timestamp . '.sql';
        
        // Get all tables
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $backup_content = "-- Environmental Platform Database Backup\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            $backup_content .= "-- Table: $table_name\n";
            $backup_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $backup_content .= $create_table[1] . ";\n\n";
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($row as $value) {
                        $values[] = is_null($value) ? 'NULL' : "'" . esc_sql($value) . "'";
                    }
                    $backup_content .= "INSERT INTO `$table_name` VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            $backup_content .= "\n";
        }
        
        file_put_contents($backup_file, $backup_content);
        
        return $backup_file;
    }
    
    /**
     * Create files backup
     */
    private function create_files_backup($timestamp) {
        $backup_file = $this->backup_dir . 'files_' . $timestamp . '.tar.gz';
        
        // Create tar archive
        $command = "tar -czf " . escapeshellarg($backup_file) . " " . escapeshellarg(ABSPATH);
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            // Fallback: create zip archive
            $backup_file = $this->backup_dir . 'files_' . $timestamp . '.zip';
            $this->create_zip_backup($backup_file);
        }
        
        return $backup_file;
    }
    
    /**
     * Create zip backup as fallback
     */
    private function create_zip_backup($backup_file) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Neither tar nor zip is available for file backup');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Cannot create zip file');
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ABSPATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(ABSPATH));
                
                // Skip backup files and cache
                if (strpos($relativePath, 'wp-content/ep-backups/') === 0 ||
                    strpos($relativePath, 'wp-content/cache/') === 0) {
                    continue;
                }
                
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
    }
    
    /**
     * Create automated backup
     */
    public function create_automated_backup() {
        $result = $this->create_backup();
        
        if ($result) {
            $this->log_backup_event('Automated backup completed successfully');
        } else {
            $this->log_backup_event('Automated backup failed');
        }
        
        // Cleanup old backups
        $this->cleanup_old_backups();
    }
    
    /**
     * Cleanup old backups
     */
    public function cleanup_old_backups() {
        if (!is_dir($this->backup_dir)) {
            return;
        }
        
        $files = scandir($this->backup_dir);
        $current_time = time();
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === 'index.php') {
                continue;
            }
            
            $filepath = $this->backup_dir . $file;
            if (is_file($filepath)) {
                $file_age = $current_time - filemtime($filepath);
                
                // Delete files older than retention period
                if ($file_age > ($this->db_backup_retention * 24 * 60 * 60)) {
                    unlink($filepath);
                    $this->log_backup_event('Old backup file deleted: ' . $file);
                }
            }
        }
    }
    
    /**
     * Log backup events
     */
    private function log_backup_event($message, $data = array()) {
        if (function_exists('ep_log_security_event')) {
            ep_log_security_event('backup_event', $message, 'info', $data);
        } else {
            error_log("EP Backup: " . $message);
        }
    }
}

// Initialize backup system
new EP_Backup_System();

/**
 * Backup utility functions
 */

/**
 * Get backup statistics
 */
function ep_get_backup_stats() {
    $backup_dir = WP_CONTENT_DIR . '/ep-backups/';
    $stats = array(
        'total_backups' => 0,
        'total_size' => 0,
        'last_backup' => null,
        'next_backup' => wp_next_scheduled('ep_daily_backup')
    );
    
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        $last_backup_time = 0;
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === 'index.php') {
                continue;
            }
            
            $filepath = $backup_dir . $file;
            if (is_file($filepath)) {
                $stats['total_backups']++;
                $stats['total_size'] += filesize($filepath);
                
                $file_time = filemtime($filepath);
                if ($file_time > $last_backup_time) {
                    $last_backup_time = $file_time;
                }
            }
        }
        
        if ($last_backup_time > 0) {
            $stats['last_backup'] = $last_backup_time;
        }
    }
    
    return $stats;
}

/**
 * Check if UpdraftPlus is available and configure it
 */
function ep_configure_updraftplus() {
    if (class_exists('UpdraftPlus')) {
        $updraft_options = array(
            'updraft_interval' => 'daily',
            'updraft_interval_database' => 'daily',
            'updraft_retain' => 7,
            'updraft_retain_db' => 7,
            'updraft_split_every' => 50, // MB
            'updraft_include_uploads' => 1,
            'updraft_include_plugins' => 1,
            'updraft_include_themes' => 1,
            'updraft_include_others' => 1,
            'updraft_include_wpcore' => 0,
            'updraft_email' => get_option('admin_email'),
            'updraft_delete_local' => 1
        );
        
        foreach ($updraft_options as $key => $value) {
            update_option($key, $value);
        }
        
        return true;
    }
    
    return false;
}

// Auto-configure UpdraftPlus if available
add_action('plugins_loaded', 'ep_configure_updraftplus');
