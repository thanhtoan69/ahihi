<?php

class Environmental_Database_Manager {
    
    private $wpdb;
    private $table_prefix;
    private $tables;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'env_';
        
        $this->tables = [
            'air_quality_data' => $this->table_prefix . 'air_quality_data',
            'weather_data' => $this->table_prefix . 'weather_data',
            'carbon_footprint' => $this->table_prefix . 'carbon_footprint',
            'user_goals' => $this->table_prefix . 'user_goals',
            'community_data' => $this->table_prefix . 'community_data'
        ];
    }
    
    /**
     * Create all necessary database tables
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Air Quality Data Table
        $sql_air_quality = "CREATE TABLE {$this->tables['air_quality_data']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            location varchar(100) NOT NULL,
            aqi int(11) NOT NULL,
            pm25 decimal(5,2) DEFAULT NULL,
            pm10 decimal(5,2) DEFAULT NULL,
            co decimal(5,2) DEFAULT NULL,
            no2 decimal(5,2) DEFAULT NULL,
            so2 decimal(5,2) DEFAULT NULL,
            o3 decimal(5,2) DEFAULT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            source varchar(50) DEFAULT 'api',
            PRIMARY KEY (id),
            KEY location_idx (location),
            KEY recorded_at_idx (recorded_at),
            KEY aqi_idx (aqi)
        ) $charset_collate;";
        
        // Weather Data Table
        $sql_weather = "CREATE TABLE {$this->tables['weather_data']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            location varchar(100) NOT NULL,
            temperature decimal(5,2) NOT NULL,
            humidity decimal(5,2) NOT NULL,
            wind_speed decimal(5,2) NOT NULL,
            wind_direction int(11) DEFAULT NULL,
            pressure decimal(7,2) DEFAULT NULL,
            uv_index decimal(3,1) DEFAULT NULL,
            visibility decimal(5,2) DEFAULT NULL,
            weather_condition varchar(50) DEFAULT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            source varchar(50) DEFAULT 'api',
            PRIMARY KEY (id),
            KEY location_idx (location),
            KEY recorded_at_idx (recorded_at),
            KEY temperature_idx (temperature)
        ) $charset_collate;";
        
        // Carbon Footprint Table
        $sql_carbon = "CREATE TABLE {$this->tables['carbon_footprint']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            category enum('transportation','energy','food','waste','consumption') NOT NULL,
            emission_amount decimal(10,2) NOT NULL,
            activity_description text,
            activity_type varchar(100) DEFAULT NULL,
            quantity decimal(10,2) DEFAULT NULL,
            unit varchar(20) DEFAULT NULL,
            emission_factor decimal(10,4) DEFAULT NULL,
            recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY category_idx (category),
            KEY recorded_at_idx (recorded_at),
            KEY user_category_idx (user_id, category)
        ) $charset_collate;";
        
        // User Goals Table
        $sql_goals = "CREATE TABLE {$this->tables['user_goals']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            goal_type varchar(50) NOT NULL,
            target_amount decimal(10,2) NOT NULL,
            current_amount decimal(10,2) DEFAULT 0,
            target_date date NOT NULL,
            status enum('active','achieved','paused','expired') DEFAULT 'active',
            description text,
            category varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY status_idx (status),
            KEY target_date_idx (target_date),
            KEY user_status_idx (user_id, status)
        ) $charset_collate;";
        
        // Community Data Table
        $sql_community = "CREATE TABLE {$this->tables['community_data']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            metric_name varchar(100) NOT NULL,
            metric_data longtext NOT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY metric_name_idx (metric_name),
            KEY updated_at_idx (updated_at)
        ) $charset_collate;";
        
        // Execute table creation
        $results = [];
        $results['air_quality'] = dbDelta($sql_air_quality);
        $results['weather'] = dbDelta($sql_weather);
        $results['carbon'] = dbDelta($sql_carbon);
        $results['goals'] = dbDelta($sql_goals);
        $results['community'] = dbDelta($sql_community);
        
        // Create indexes for performance
        $this->create_additional_indexes();
        
        // Insert sample data
        $this->insert_sample_data();
        
        return $results;
    }
    
    /**
     * Create additional indexes for better performance
     */
    private function create_additional_indexes() {
        $indexes = [
            // Air quality indexes
            "CREATE INDEX air_location_date_idx ON {$this->tables['air_quality_data']} (location, recorded_at)",
            "CREATE INDEX air_aqi_date_idx ON {$this->tables['air_quality_data']} (aqi, recorded_at)",
            
            // Weather indexes
            "CREATE INDEX weather_location_date_idx ON {$this->tables['weather_data']} (location, recorded_at)",
            "CREATE INDEX weather_temp_date_idx ON {$this->tables['weather_data']} (temperature, recorded_at)",
            
            // Carbon footprint indexes
            "CREATE INDEX carbon_user_date_idx ON {$this->tables['carbon_footprint']} (user_id, recorded_at)",
            "CREATE INDEX carbon_category_date_idx ON {$this->tables['carbon_footprint']} (category, recorded_at)",
            "CREATE INDEX carbon_date_category_idx ON {$this->tables['carbon_footprint']} (recorded_at, category)",
            
            // Goals indexes
            "CREATE INDEX goals_user_type_idx ON {$this->tables['user_goals']} (user_id, goal_type)",
            "CREATE INDEX goals_status_date_idx ON {$this->tables['user_goals']} (status, target_date)"
        ];
        
        foreach ($indexes as $index_sql) {
            $this->wpdb->query($index_sql);
        }
    }
    
    /**
     * Insert sample data for testing
     */
    private function insert_sample_data() {
        // Sample air quality data
        $air_quality_data = [
            ['Ho Chi Minh City', 85, 35.2, 45.8, 1.2, 25.3, 8.7, 95.2],
            ['Hanoi', 92, 42.1, 52.3, 1.8, 28.7, 12.4, 87.6],
            ['Da Nang', 78, 28.9, 38.4, 0.9, 22.1, 6.8, 102.3],
            ['Can Tho', 88, 38.7, 47.2, 1.1, 24.8, 9.2, 89.7],
            ['Nha Trang', 72, 25.4, 33.8, 0.7, 19.6, 5.9, 108.4]
        ];
        
        foreach ($air_quality_data as $data) {
            for ($i = 0; $i < 30; $i++) {
                $date = date('Y-m-d H:i:s', strtotime("-{$i} days") + rand(0, 86400));
                $variation = rand(-10, 10) / 100;
                
                $this->wpdb->insert(
                    $this->tables['air_quality_data'],
                    [
                        'location' => $data[0],
                        'aqi' => max(1, $data[1] + round($data[1] * $variation)),
                        'pm25' => max(0, $data[2] + ($data[2] * $variation)),
                        'pm10' => max(0, $data[3] + ($data[3] * $variation)),
                        'co' => max(0, $data[4] + ($data[4] * $variation)),
                        'no2' => max(0, $data[5] + ($data[5] * $variation)),
                        'so2' => max(0, $data[6] + ($data[6] * $variation)),
                        'o3' => max(0, $data[7] + ($data[7] * $variation)),
                        'recorded_at' => $date,
                        'source' => 'sample'
                    ]
                );
            }
        }
        
        // Sample weather data
        $weather_data = [
            ['Ho Chi Minh City', 28.5, 78, 12.3, 180, 1013.2, 8.2, 15.5, 'Clear'],
            ['Hanoi', 25.2, 82, 8.7, 90, 1015.8, 6.8, 12.8, 'Cloudy'],
            ['Da Nang', 26.8, 75, 15.2, 270, 1012.5, 9.1, 18.2, 'Partly Cloudy'],
            ['Can Tho', 29.1, 85, 6.4, 45, 1011.9, 7.9, 14.7, 'Humid'],
            ['Nha Trang', 27.4, 72, 18.8, 315, 1014.3, 8.8, 20.1, 'Windy']
        ];
        
        foreach ($weather_data as $data) {
            for ($i = 0; $i < 30; $i++) {
                $date = date('Y-m-d H:i:s', strtotime("-{$i} days") + rand(0, 86400));
                $variation = rand(-15, 15) / 100;
                
                $this->wpdb->insert(
                    $this->tables['weather_data'],
                    [
                        'location' => $data[0],
                        'temperature' => $data[1] + ($data[1] * $variation),
                        'humidity' => max(0, min(100, $data[2] + ($data[2] * $variation * 0.5))),
                        'wind_speed' => max(0, $data[3] + ($data[3] * $variation)),
                        'wind_direction' => ($data[4] + rand(-30, 30)) % 360,
                        'pressure' => $data[5] + ($data[5] * $variation * 0.01),
                        'uv_index' => max(0, min(12, $data[6] + ($data[6] * $variation * 0.3))),
                        'visibility' => max(0, $data[7] + ($data[7] * $variation * 0.5)),
                        'weather_condition' => $data[8],
                        'recorded_at' => $date,
                        'source' => 'sample'
                    ]
                );
            }
        }
        
        // Sample carbon footprint data for admin user (user_id = 1)
        $carbon_activities = [
            ['transportation', 15.2, 'Car commute to work', 'car_gasoline', 20, 'km', 0.76],
            ['transportation', 8.5, 'Motorbike trip', 'motorbike', 15, 'km', 0.57],
            ['energy', 12.8, 'Home electricity usage', 'electricity', 45, 'kWh', 0.28],
            ['energy', 6.2, 'Natural gas heating', 'natural_gas', 12, 'm3', 0.52],
            ['food', 9.4, 'Beef consumption', 'beef', 0.5, 'kg', 18.8],
            ['food', 3.2, 'Chicken meal', 'chicken', 0.3, 'kg', 10.7],
            ['waste', 2.1, 'Household waste', 'general_waste', 5, 'kg', 0.42],
            ['consumption', 4.8, 'New clothing purchase', 'clothing', 1, 'item', 4.8]
        ];
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d H:i:s', strtotime("-{$i} days"));
            $daily_activities = rand(1, 4);
            
            for ($j = 0; $j < $daily_activities; $j++) {
                $activity = $carbon_activities[rand(0, count($carbon_activities) - 1)];
                $variation = rand(80, 120) / 100; // 20% variation
                
                $this->wpdb->insert(
                    $this->tables['carbon_footprint'],
                    [
                        'user_id' => 1,
                        'category' => $activity[0],
                        'emission_amount' => round($activity[1] * $variation, 2),
                        'activity_description' => $activity[2],
                        'activity_type' => $activity[3],
                        'quantity' => round($activity[4] * $variation, 2),
                        'unit' => $activity[5],
                        'emission_factor' => $activity[6],
                        'recorded_at' => $date
                    ]
                );
            }
        }
        
        // Sample user goals
        $sample_goals = [
            ['reduce_transport_emissions', 50, 15.2, '2024-12-31', 'active', 'Reduce monthly transportation emissions by 50%', 'transportation'],
            ['energy_efficiency', 30, 8.7, '2024-11-30', 'active', 'Reduce home energy consumption by 30%', 'energy'],
            ['waste_reduction', 25, 12.1, '2024-10-31', 'achieved', 'Reduce household waste by 25%', 'waste'],
            ['sustainable_diet', 40, 22.3, '2024-12-15', 'active', 'Reduce food-related emissions by 40%', 'food']
        ];
        
        foreach ($sample_goals as $goal) {
            $this->wpdb->insert(
                $this->tables['user_goals'],
                [
                    'user_id' => 1,
                    'goal_type' => $goal[0],
                    'target_amount' => $goal[1],
                    'current_amount' => $goal[2],
                    'target_date' => $goal[3],
                    'status' => $goal[4],
                    'description' => $goal[5],
                    'category' => $goal[6],
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(30, 90) . ' days'))
                ]
            );
        }
    }
    
    /**
     * Clean up old data based on retention policies
     */
    public function cleanup_old_data() {
        $cleanup_results = [];
        
        // Clean up air quality data older than 1 year
        $air_cleanup = $this->wpdb->query("
            DELETE FROM {$this->tables['air_quality_data']} 
            WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        $cleanup_results['air_quality'] = $air_cleanup;
        
        // Clean up weather data older than 1 year
        $weather_cleanup = $this->wpdb->query("
            DELETE FROM {$this->tables['weather_data']} 
            WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        $cleanup_results['weather'] = $weather_cleanup;
        
        // Clean up carbon footprint data older than 2 years
        $carbon_cleanup = $this->wpdb->query("
            DELETE FROM {$this->tables['carbon_footprint']} 
            WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
        $cleanup_results['carbon_footprint'] = $carbon_cleanup;
        
        // Clean up expired goals older than 6 months
        $goals_cleanup = $this->wpdb->query("
            DELETE FROM {$this->tables['user_goals']} 
            WHERE status = 'expired' 
            AND updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ");
        $cleanup_results['goals'] = $goals_cleanup;
        
        // Optimize tables after cleanup
        $this->optimize_tables();
        
        return $cleanup_results;
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        $optimization_results = [];
        
        foreach ($this->tables as $table_name => $table) {
            $result = $this->wpdb->query("OPTIMIZE TABLE {$table}");
            $optimization_results[$table_name] = $result;
        }
        
        return $optimization_results;
    }
    
    /**
     * Get database statistics
     */
    public function get_database_stats() {
        $stats = [];
        
        foreach ($this->tables as $table_name => $table) {
            $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $table_size = $this->wpdb->get_row("
                SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows,
                    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_mb
                FROM information_schema.tables 
                WHERE table_name = '{$table}'
                AND table_schema = DATABASE()
            ");
            
            $stats[$table_name] = [
                'row_count' => $row_count,
                'size_mb' => $table_size->size_mb ?? 0,
                'data_mb' => $table_size->data_mb ?? 0,
                'index_mb' => $table_size->index_mb ?? 0
            ];
        }
        
        return $stats;
    }
    
    /**
     * Backup specific table data
     */
    public function backup_table_data($table_name, $conditions = '') {
        if (!array_key_exists($table_name, $this->tables)) {
            return false;
        }
        
        $table = $this->tables[$table_name];
        $where_clause = $conditions ? "WHERE {$conditions}" : '';
        
        $results = $this->wpdb->get_results("SELECT * FROM {$table} {$where_clause}", ARRAY_A);
        
        if (!$results) {
            return false;
        }
        
        $backup_data = [
            'table' => $table_name,
            'timestamp' => current_time('mysql'),
            'rows' => count($results),
            'data' => $results
        ];
        
        return $backup_data;
    }
    
    /**
     * Restore table data from backup
     */
    public function restore_table_data($backup_data) {
        if (!isset($backup_data['table']) || !array_key_exists($backup_data['table'], $this->tables)) {
            return false;
        }
        
        $table = $this->tables[$backup_data['table']];
        
        // Begin transaction
        $this->wpdb->query('START TRANSACTION');
        
        try {
            foreach ($backup_data['data'] as $row) {
                $result = $this->wpdb->insert($table, $row);
                if ($result === false) {
                    throw new Exception('Failed to insert row');
                }
            }
            
            $this->wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Get data integrity check results
     */
    public function check_data_integrity() {
        $issues = [];
        
        // Check for orphaned carbon footprint records
        $orphaned_carbon = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->tables['carbon_footprint']} cf
            LEFT JOIN {$this->wpdb->users} u ON cf.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        if ($orphaned_carbon > 0) {
            $issues[] = "Found {$orphaned_carbon} carbon footprint records with invalid user IDs";
        }
        
        // Check for orphaned goals
        $orphaned_goals = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->tables['user_goals']} ug
            LEFT JOIN {$this->wpdb->users} u ON ug.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        if ($orphaned_goals > 0) {
            $issues[] = "Found {$orphaned_goals} goal records with invalid user IDs";
        }
        
        // Check for duplicate air quality data
        $duplicate_air = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM (
                SELECT location, recorded_at, COUNT(*) as cnt
                FROM {$this->tables['air_quality_data']}
                GROUP BY location, DATE(recorded_at), HOUR(recorded_at)
                HAVING cnt > 1
            ) as duplicates
        ");
        
        if ($duplicate_air > 0) {
            $issues[] = "Found {$duplicate_air} duplicate air quality records";
        }
        
        // Check for invalid emission amounts
        $invalid_emissions = $this->wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$this->tables['carbon_footprint']}
            WHERE emission_amount < 0 OR emission_amount > 10000
        ");
        
        if ($invalid_emissions > 0) {
            $issues[] = "Found {$invalid_emissions} carbon footprint records with invalid emission amounts";
        }
        
        return [
            'status' => empty($issues) ? 'healthy' : 'issues_found',
            'issues' => $issues,
            'checked_at' => current_time('mysql')
        ];
    }
    
    /**
     * Fix common data integrity issues
     */
    public function fix_data_integrity_issues() {
        $fixes_applied = [];
        
        // Remove orphaned carbon footprint records
        $orphaned_carbon_fixed = $this->wpdb->query("
            DELETE cf FROM {$this->tables['carbon_footprint']} cf
            LEFT JOIN {$this->wpdb->users} u ON cf.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        if ($orphaned_carbon_fixed > 0) {
            $fixes_applied[] = "Removed {$orphaned_carbon_fixed} orphaned carbon footprint records";
        }
        
        // Remove orphaned goals
        $orphaned_goals_fixed = $this->wpdb->query("
            DELETE ug FROM {$this->tables['user_goals']} ug
            LEFT JOIN {$this->wpdb->users} u ON ug.user_id = u.ID
            WHERE u.ID IS NULL
        ");
        
        if ($orphaned_goals_fixed > 0) {
            $fixes_applied[] = "Removed {$orphaned_goals_fixed} orphaned goal records";
        }
        
        // Fix invalid emission amounts (set to 0 if negative, cap at reasonable maximum)
        $invalid_emissions_fixed = $this->wpdb->query("
            UPDATE {$this->tables['carbon_footprint']} 
            SET emission_amount = CASE 
                WHEN emission_amount < 0 THEN 0
                WHEN emission_amount > 10000 THEN 1000
                ELSE emission_amount
            END
            WHERE emission_amount < 0 OR emission_amount > 10000
        ");
        
        if ($invalid_emissions_fixed > 0) {
            $fixes_applied[] = "Fixed {$invalid_emissions_fixed} invalid emission amounts";
        }
        
        return [
            'fixes_applied' => $fixes_applied,
            'fixed_at' => current_time('mysql')
        ];
    }
    
    /**
     * Get table creation SQL for debugging
     */
    public function get_table_creation_sql() {
        $sql_statements = [];
        
        foreach ($this->tables as $table_name => $table) {
            $result = $this->wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_A);
            $sql_statements[$table_name] = $result['Create Table'] ?? '';
        }
        
        return $sql_statements;
    }
    
    /**
     * Drop all plugin tables (use with caution)
     */
    public function drop_tables() {
        $results = [];
        
        foreach ($this->tables as $table_name => $table) {
            $result = $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
            $results[$table_name] = $result;
        }
        
        return $results;
    }
    
    /**
     * Export data to JSON format
     */
    public function export_data_to_json($table_name = null, $conditions = '') {
        if ($table_name && !array_key_exists($table_name, $this->tables)) {
            return false;
        }
        
        $export_data = [];
        $tables_to_export = $table_name ? [$table_name => $this->tables[$table_name]] : $this->tables;
        
        foreach ($tables_to_export as $name => $table) {
            $where_clause = $conditions ? "WHERE {$conditions}" : '';
            $results = $this->wpdb->get_results("SELECT * FROM {$table} {$where_clause}", ARRAY_A);
            
            $export_data[$name] = [
                'count' => count($results),
                'exported_at' => current_time('mysql'),
                'data' => $results
            ];
        }
        
        return json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get data summary for dashboard
     */
    public function get_data_summary() {
        return [
            'total_air_quality_readings' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['air_quality_data']}"),
            'total_weather_readings' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['weather_data']}"),
            'total_carbon_entries' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['carbon_footprint']}"),
            'active_users' => $this->wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->tables['carbon_footprint']}"),
            'total_goals' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['user_goals']}"),
            'achieved_goals' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['user_goals']} WHERE status = 'achieved'"),
            'locations_monitored' => $this->wpdb->get_var("SELECT COUNT(DISTINCT location) FROM {$this->tables['air_quality_data']}"),
            'latest_air_reading' => $this->wpdb->get_var("SELECT MAX(recorded_at) FROM {$this->tables['air_quality_data']}"),
            'latest_weather_reading' => $this->wpdb->get_var("SELECT MAX(recorded_at) FROM {$this->tables['weather_data']}"),
            'database_size_mb' => array_sum(array_column($this->get_database_stats(), 'size_mb'))
        ];
    }
}

?>
