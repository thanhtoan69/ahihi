<?php
/**
 * Tests for EEM_Database_Manager class
 */

class Test_EEM_Database_Manager extends EEM_Test_Case {
    
    public function test_database_manager_initialization() {
        $this->assertInstanceOf('EEM_Database_Manager', $this->database_manager);
    }
    
    public function test_create_tables() {
        global $wpdb;
        
        // Drop tables first
        $this->database_manager->drop_tables();
        
        // Create tables
        $result = $this->database_manager->create_tables();
        $this->assertTrue($result);
        
        // Verify tables exist
        $tables = [
            'eem_subscribers',
            'eem_lists',
            'eem_campaigns',
            'eem_templates',
            'eem_automations',
            'eem_analytics',
            'eem_ab_tests',
            'eem_segments',
            'eem_webhooks',
            'eem_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            $this->assertEquals($table_name, $table_exists, "Table $table does not exist");
        }
    }
    
    public function test_table_schema_structure() {
        global $wpdb;
        
        // Test subscribers table structure
        $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}eem_subscribers");
        $column_names = wp_list_pluck($columns, 'Field');
        
        $expected_columns = [
            'id', 'email', 'first_name', 'last_name', 'status',
            'environmental_score', 'preferences', 'source', 'ip_address',
            'user_agent', 'confirmed_at', 'created_at', 'updated_at'
        ];
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $column_names, "Subscribers table missing column: $column");
        }
    }
    
    public function test_get_table_name() {
        $table_name = $this->database_manager->get_table_name('subscribers');
        $expected = $GLOBALS['wpdb']->prefix . 'eem_subscribers';
        $this->assertEquals($expected, $table_name);
    }
    
    public function test_table_exists() {
        $this->assertTrue($this->database_manager->table_exists('subscribers'));
        $this->assertFalse($this->database_manager->table_exists('nonexistent_table'));
    }
    
    public function test_get_table_version() {
        $version = $this->database_manager->get_table_version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
    
    public function test_update_tables() {
        // Simulate version change
        update_option('eem_db_version', '1.0.0');
        
        $result = $this->database_manager->update_tables();
        $this->assertTrue($result);
        
        // Verify version updated
        $new_version = get_option('eem_db_version');
        $this->assertNotEquals('1.0.0', $new_version);
    }
    
    public function test_backup_table() {
        // Add test data
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'eem_subscribers',
            [
                'email' => 'backup@test.com',
                'first_name' => 'Backup',
                'status' => 'active',
                'created_at' => current_time('mysql')
            ]
        );
        
        $backup_result = $this->database_manager->backup_table('subscribers');
        $this->assertTrue($backup_result);
        
        // Verify backup table exists
        $backup_table = $wpdb->prefix . 'eem_subscribers_backup_' . date('Ymd');
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$backup_table'");
        $this->assertEquals($backup_table, $table_exists);
    }
    
    public function test_cleanup_old_data() {
        global $wpdb;
        
        // Add old analytics data
        $old_date = date('Y-m-d H:i:s', strtotime('-95 days'));
        $wpdb->insert(
            $wpdb->prefix . 'eem_analytics',
            [
                'event_type' => 'email_open',
                'campaign_id' => 1,
                'subscriber_id' => 1,
                'created_at' => $old_date
            ]
        );
        
        $result = $this->database_manager->cleanup_old_data();
        $this->assertTrue($result);
        
        // Verify old data removed
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eem_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $this->assertEquals(0, $count);
    }
    
    public function test_get_database_size() {
        $size = $this->database_manager->get_database_size();
        $this->assertIsArray($size);
        $this->assertArrayHasKey('total_size', $size);
        $this->assertArrayHasKey('table_sizes', $size);
    }
    
    public function test_optimize_tables() {
        $result = $this->database_manager->optimize_tables();
        $this->assertTrue($result);
    }
    
    public function test_drop_tables() {
        // This should be last test as it drops tables
        $result = $this->database_manager->drop_tables();
        $this->assertTrue($result);
        
        // Verify tables dropped
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}eem_subscribers'");
        $this->assertNull($table_exists);
    }
}
