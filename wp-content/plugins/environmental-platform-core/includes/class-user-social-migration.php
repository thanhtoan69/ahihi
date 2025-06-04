<?php
/**
 * Database Migration for User Social Accounts
 * 
 * Phase 31: User Management & Authentication - Database Setup
 * Creates and updates user_social_accounts table for social login integration
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EP_User_Social_Migration {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = 'user_social_accounts';
    }
    
    /**
     * Run the migration
     */
    public function run_migration() {
        $this->create_social_accounts_table();
        $this->add_indexes();
        $this->migrate_existing_data();
        $this->verify_migration();
    }
    
    /**
     * Create user_social_accounts table
     */
    private function create_social_accounts_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned NOT NULL,
            `provider` varchar(50) NOT NULL,
            `provider_user_id` varchar(255) NOT NULL,
            `provider_email` varchar(255) DEFAULT NULL,
            `provider_name` varchar(255) DEFAULT NULL,
            `provider_avatar` text DEFAULT NULL,
            `provider_profile_url` text DEFAULT NULL,
            `access_token` text DEFAULT NULL,
            `refresh_token` text DEFAULT NULL,
            `token_expires_at` datetime DEFAULT NULL,
            `additional_data` longtext DEFAULT NULL,
            `connected_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_provider` (`user_id`, `provider`),
            UNIQUE KEY `unique_provider_user` (`provider`, `provider_user_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_provider` (`provider`),
            KEY `idx_provider_user_id` (`provider_user_id`),
            KEY `idx_connected_at` (`connected_at`),
            KEY `idx_is_active` (`is_active`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created successfully
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name) {
            error_log("EP Migration: user_social_accounts table created successfully");
            return true;
        } else {
            error_log("EP Migration: Failed to create user_social_accounts table");
            return false;
        }
    }
    
    /**
     * Add additional indexes for performance
     */
    private function add_indexes() {
        $indexes = array(
            "CREATE INDEX IF NOT EXISTS `idx_provider_email` ON `{$this->table_name}` (`provider_email`)",
            "CREATE INDEX IF NOT EXISTS `idx_updated_at` ON `{$this->table_name}` (`updated_at`)",
            "CREATE INDEX IF NOT EXISTS `idx_token_expires` ON `{$this->table_name}` (`token_expires_at`)"
        );
        
        foreach ($indexes as $index_sql) {
            $result = $this->wpdb->query($index_sql);
            if ($result === false) {
                error_log("EP Migration: Failed to create index: " . $index_sql);
            }
        }
    }
    
    /**
     * Migrate existing social account data if any
     */
    private function migrate_existing_data() {
        // Check if there are any existing social meta fields to migrate
        $existing_social_meta = $this->wpdb->get_results("
            SELECT user_id, meta_key, meta_value 
            FROM {$this->wpdb->usermeta} 
            WHERE meta_key LIKE 'ep_social_%'
        ");
        
        if (empty($existing_social_meta)) {
            error_log("EP Migration: No existing social data to migrate");
            return;
        }
        
        $migrated_count = 0;
        $grouped_data = array();
        
        // Group meta data by user_id
        foreach ($existing_social_meta as $meta) {
            $grouped_data[$meta->user_id][$meta->meta_key] = $meta->meta_value;
        }
        
        // Process each user's social data
        foreach ($grouped_data as $user_id => $meta_data) {
            $this->migrate_user_social_data($user_id, $meta_data);
            $migrated_count++;
        }
        
        error_log("EP Migration: Migrated social data for {$migrated_count} users");
    }
    
    /**
     * Migrate individual user's social data
     */
    private function migrate_user_social_data($user_id, $meta_data) {
        $providers = array('facebook', 'google', 'twitter', 'linkedin', 'github', 'discord');
        
        foreach ($providers as $provider) {
            $provider_id_key = "ep_social_{$provider}_id";
            $provider_email_key = "ep_social_{$provider}_email";
            $provider_name_key = "ep_social_{$provider}_name";
            $provider_avatar_key = "ep_social_{$provider}_avatar";
            $provider_token_key = "ep_social_{$provider}_token";
            
            // Check if this provider has data
            if (isset($meta_data[$provider_id_key]) && !empty($meta_data[$provider_id_key])) {
                $social_data = array(
                    'user_id' => $user_id,
                    'provider' => $provider,
                    'provider_user_id' => $meta_data[$provider_id_key],
                    'provider_email' => $meta_data[$provider_email_key] ?? null,
                    'provider_name' => $meta_data[$provider_name_key] ?? null,
                    'provider_avatar' => $meta_data[$provider_avatar_key] ?? null,
                    'access_token' => isset($meta_data[$provider_token_key]) ? wp_hash($meta_data[$provider_token_key]) : null,
                    'connected_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'is_active' => 1
                );
                
                // Insert the social account data
                $result = $this->wpdb->insert($this->table_name, $social_data);
                
                if ($result === false) {
                    error_log("EP Migration: Failed to migrate {$provider} data for user {$user_id}");
                } else {
                    // Clean up old meta data
                    $this->cleanup_old_social_meta($user_id, $provider);
                    error_log("EP Migration: Successfully migrated {$provider} data for user {$user_id}");
                }
            }
        }
    }
    
    /**
     * Clean up old social meta data after migration
     */
    private function cleanup_old_social_meta($user_id, $provider) {
        $meta_keys = array(
            "ep_social_{$provider}_id",
            "ep_social_{$provider}_email",
            "ep_social_{$provider}_name",
            "ep_social_{$provider}_avatar",
            "ep_social_{$provider}_token"
        );
        
        foreach ($meta_keys as $meta_key) {
            delete_user_meta($user_id, $meta_key);
        }
    }
    
    /**
     * Verify migration was successful
     */
    private function verify_migration() {
        // Check table structure
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name;
        
        if (!$table_exists) {
            error_log("EP Migration: Verification failed - table does not exist");
            return false;
        }
        
        // Check table columns
        $columns = $this->wpdb->get_results("DESCRIBE {$this->table_name}");
        $expected_columns = array(
            'id', 'user_id', 'provider', 'provider_user_id', 'provider_email',
            'provider_name', 'provider_avatar', 'provider_profile_url',
            'access_token', 'refresh_token', 'token_expires_at', 'additional_data',
            'connected_at', 'updated_at', 'is_active'
        );
        
        $actual_columns = array_column($columns, 'Field');
        $missing_columns = array_diff($expected_columns, $actual_columns);
        
        if (!empty($missing_columns)) {
            error_log("EP Migration: Verification failed - missing columns: " . implode(', ', $missing_columns));
            return false;
        }
        
        // Check indexes
        $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$this->table_name}");
        $index_names = array_column($indexes, 'Key_name');
        
        $expected_indexes = array('PRIMARY', 'unique_user_provider', 'unique_provider_user', 'idx_user_id', 'idx_provider');
        $missing_indexes = array_diff($expected_indexes, $index_names);
        
        if (!empty($missing_indexes)) {
            error_log("EP Migration: Warning - missing indexes: " . implode(', ', $missing_indexes));
        }
        
        // Get record count
        $record_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        error_log("EP Migration: Verification successful - table created with {$record_count} records");
        return true;
    }
    
    /**
     * Rollback migration if needed
     */
    public function rollback_migration() {
        // Drop the table
        $result = $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        
        if ($result !== false) {
            error_log("EP Migration: Rollback successful - user_social_accounts table dropped");
            return true;
        } else {
            error_log("EP Migration: Rollback failed - could not drop user_social_accounts table");
            return false;
        }
    }
    
    /**
     * Get migration status
     */
    public function get_migration_status() {
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") == $this->table_name;
        
        if (!$table_exists) {
            return array(
                'status' => 'not_migrated',
                'message' => 'User social accounts table does not exist',
                'table_exists' => false,
                'record_count' => 0
            );
        }
        
        $record_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $columns = $this->wpdb->get_results("DESCRIBE {$this->table_name}");
        $column_count = count($columns);
        
        return array(
            'status' => 'migrated',
            'message' => 'Migration completed successfully',
            'table_exists' => true,
            'record_count' => intval($record_count),
            'column_count' => $column_count,
            'created_at' => $this->get_table_creation_time()
        );
    }
    
    /**
     * Get table creation time
     */
    private function get_table_creation_time() {
        $result = $this->wpdb->get_row("
            SELECT CREATE_TIME 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$this->table_name}'
        ");
        
        return $result ? $result->CREATE_TIME : null;
    }
    
    /**
     * Add sample social accounts for testing
     */
    public function add_sample_data() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Get a few test users
        $test_users = get_users(array(
            'number' => 3,
            'role__in' => array('eco_user', 'administrator')
        ));
        
        if (empty($test_users)) {
            return false;
        }
        
        $providers = array('facebook', 'google', 'twitter');
        $sample_count = 0;
        
        foreach ($test_users as $user) {
            foreach ($providers as $provider) {
                // Skip if already has this provider
                $existing = $this->wpdb->get_var($this->wpdb->prepare("
                    SELECT id FROM {$this->table_name} 
                    WHERE user_id = %d AND provider = %s
                ", $user->ID, $provider));
                
                if ($existing) {
                    continue;
                }
                
                $sample_data = array(
                    'user_id' => $user->ID,
                    'provider' => $provider,
                    'provider_user_id' => 'test_' . $provider . '_' . $user->ID . '_' . wp_rand(1000, 9999),
                    'provider_email' => $user->user_email,
                    'provider_name' => $user->display_name,
                    'provider_avatar' => 'https://via.placeholder.com/150x150.png?text=' . strtoupper(substr($provider, 0, 1)),
                    'provider_profile_url' => 'https://' . $provider . '.com/profile/test_user',
                    'access_token' => wp_hash('sample_token_' . $provider . '_' . $user->ID),
                    'connected_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'is_active' => 1
                );
                
                $result = $this->wpdb->insert($this->table_name, $sample_data);
                
                if ($result !== false) {
                    $sample_count++;
                }
            }
        }
        
        error_log("EP Migration: Added {$sample_count} sample social accounts");
        return $sample_count;
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        $result = $this->wpdb->query("
            UPDATE {$this->table_name} 
            SET access_token = NULL, refresh_token = NULL 
            WHERE token_expires_at IS NOT NULL 
            AND token_expires_at < NOW()
        ");
        
        if ($result !== false) {
            error_log("EP Migration: Cleaned up {$result} expired tokens");
        }
        
        return $result;
    }
    
    /**
     * Get social account statistics
     */
    public function get_social_statistics() {
        $stats = array();
        
        // Total social accounts
        $stats['total_accounts'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Active accounts
        $stats['active_accounts'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1");
        
        // Accounts by provider
        $provider_stats = $this->wpdb->get_results("
            SELECT provider, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE is_active = 1 
            GROUP BY provider 
            ORDER BY count DESC
        ");
        
        $stats['by_provider'] = array();
        foreach ($provider_stats as $stat) {
            $stats['by_provider'][$stat->provider] = intval($stat->count);
        }
        
        // Users with multiple social accounts
        $stats['multi_provider_users'] = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$this->table_name} 
            WHERE user_id IN (
                SELECT user_id 
                FROM {$this->table_name} 
                WHERE is_active = 1 
                GROUP BY user_id 
                HAVING COUNT(*) > 1
            )
        ");
        
        // Recent connections (last 30 days)
        $stats['recent_connections'] = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE connected_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $stats;
    }
}

// Function to run migration
function ep_run_social_migration() {
    $migration = new EP_User_Social_Migration();
    $migration->run_migration();
}

// Function to check migration status
function ep_get_social_migration_status() {
    $migration = new EP_User_Social_Migration();
    return $migration->get_migration_status();
}

// Function to add sample data (for development/testing)
function ep_add_sample_social_data() {
    $migration = new EP_User_Social_Migration();
    return $migration->add_sample_data();
}

// Hook to run migration on plugin activation
register_activation_hook(EP_CORE_PLUGIN_FILE, 'ep_run_social_migration');

// Add admin notice if migration is needed
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $status = ep_get_social_migration_status();
    
    if ($status['status'] === 'not_migrated') {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . __('Environmental Platform:', 'environmental-platform-core') . '</strong> ';
        echo __('Social authentication migration is required.', 'environmental-platform-core');
        echo ' <a href="' . admin_url('admin.php?page=ep-migration') . '">' . __('Run migration', 'environmental-platform-core') . '</a></p>';
        echo '</div>';
    }
});

// Add WP-CLI command if available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('ep social-migration', function($args, $assoc_args) {
        $migration = new EP_User_Social_Migration();
        
        if (isset($args[0])) {
            switch ($args[0]) {
                case 'run':
                    WP_CLI::log('Running social accounts migration...');
                    $migration->run_migration();
                    WP_CLI::success('Migration completed successfully');
                    break;
                    
                case 'status':
                    $status = $migration->get_migration_status();
                    WP_CLI::log('Migration Status: ' . $status['status']);
                    WP_CLI::log('Table exists: ' . ($status['table_exists'] ? 'Yes' : 'No'));
                    if ($status['table_exists']) {
                        WP_CLI::log('Record count: ' . $status['record_count']);
                    }
                    break;
                    
                case 'stats':
                    $stats = $migration->get_social_statistics();
                    WP_CLI::log('Social Account Statistics:');
                    WP_CLI::log('- Total accounts: ' . $stats['total_accounts']);
                    WP_CLI::log('- Active accounts: ' . $stats['active_accounts']);
                    WP_CLI::log('- Multi-provider users: ' . $stats['multi_provider_users']);
                    WP_CLI::log('- Recent connections: ' . $stats['recent_connections']);
                    
                    if (!empty($stats['by_provider'])) {
                        WP_CLI::log('By provider:');
                        foreach ($stats['by_provider'] as $provider => $count) {
                            WP_CLI::log("  - {$provider}: {$count}");
                        }
                    }
                    break;
                    
                case 'cleanup':
                    WP_CLI::log('Cleaning up expired tokens...');
                    $cleaned = $migration->cleanup_expired_tokens();
                    WP_CLI::success("Cleaned up {$cleaned} expired tokens");
                    break;
                    
                case 'rollback':
                    if (isset($assoc_args['confirm']) && $assoc_args['confirm'] === 'yes') {
                        WP_CLI::log('Rolling back migration...');
                        $migration->rollback_migration();
                        WP_CLI::success('Migration rolled back successfully');
                    } else {
                        WP_CLI::error('Please confirm rollback with --confirm=yes');
                    }
                    break;
                    
                default:
                    WP_CLI::error('Invalid command. Available: run, status, stats, cleanup, rollback');
            }
        } else {
            WP_CLI::log('Available commands: run, status, stats, cleanup, rollback');
        }
    });
}
