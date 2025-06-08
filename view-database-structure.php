<?php
// Database Structure Viewer - Hiển thị trực tiếp trên browser
require_once dirname(__FILE__) . '/wp-config.php';

global $wpdb;

// Set header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Environmental Platform - Database Structure</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .table-list { background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        pre { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .sql-block { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin: 10px 0; }
        .btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block; }
        .btn:hover { background: #005177; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Environmental Platform - Database Structure</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        
        <?php
        // Lấy tất cả bảng
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        
        if ($tables) {
            echo "<h2>📊 Database Overview</h2>";
            echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";
            
            // Tạo bảng danh sách
            echo "<h3>📋 All Tables List</h3>";
            echo "<table>";
            echo "<tr><th>No</th><th>Table Name</th><th>Rows</th><th>Engine</th></tr>";
            
            $total_rows = 0;
            $i = 1;
            foreach ($tables as $table) {
                $table_name = $table[0];
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'", ARRAY_A);
                $engine = $table_status['Engine'] ?? 'Unknown';
                
                echo "<tr>";
                echo "<td>{$i}</td>";
                echo "<td><code>{$table_name}</code></td>";
                echo "<td>" . number_format($row_count) . "</td>";
                echo "<td>{$engine}</td>";
                echo "</tr>";
                
                $total_rows += $row_count;
                $i++;
            }
            echo "</table>";
            
            echo "<p><strong>Total Rows:</strong> " . number_format($total_rows) . "</p>";
            
            // Tạo markdown content để copy
            echo "<h2>📝 Markdown Content (Copy & Save as .md file)</h2>";
            echo "<div class='sql-block'>";
            echo "<h4>Copy nội dung dưới đây và lưu thành file .md:</h4>";
            echo "<textarea style='width:100%;height:300px;font-family:monospace;'>";
            
            // Markdown content
            echo "# Environmental Platform - Database Tables Structure\n\n";
            echo "**Generated Date**: " . date('Y-m-d H:i:s') . "\n";
            echo "**Database Name**: " . DB_NAME . "\n";
            echo "**Total Tables**: " . count($tables) . "\n";
            echo "**Total Rows**: " . number_format($total_rows) . "\n\n";
            echo "---\n\n";
            
            echo "## 📋 Tables List\n\n";
            echo "| No | Table Name | Rows | Engine |\n";
            echo "|----|------------|------|--------|\n";
            
            $i = 1;
            foreach ($tables as $table) {
                $table_name = $table[0];
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'", ARRAY_A);
                $engine = $table_status['Engine'] ?? 'Unknown';
                echo "| {$i} | `{$table_name}` | " . number_format($row_count) . " | {$engine} |\n";
                $i++;
            }
            
            echo "\n---\n\n";
            echo "## 📝 CREATE TABLE Statements\n\n";
            echo "```sql\n";
            echo "-- Environmental Platform Database Structure\n";
            echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "-- Database: " . DB_NAME . "\n";
            echo "-- Total Tables: " . count($tables) . "\n\n";
            
            foreach ($tables as $table) {
                $table_name = $table[0];
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                if ($create_table && isset($create_table[1])) {
                    echo "-- Table: {$table_name}\n";
                    echo $create_table[1] . ";\n\n";
                }
            }
            
            echo "```\n\n";
            echo "*End of database structure*\n";
            
            echo "</textarea>";
            echo "</div>";
            
            // Hiển thị CREATE TABLE statements
            echo "<h2>🔧 CREATE TABLE Statements</h2>";
            foreach ($tables as $table) {
                $table_name = $table[0];
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                if ($create_table && isset($create_table[1])) {
                    echo "<h4>Table: <code>{$table_name}</code></h4>";
                    echo "<pre>" . htmlspecialchars($create_table[1]) . ";</pre>";
                }
            }
            
        } else {
            echo "<p>❌ No tables found in database.</p>";
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="<?php echo admin_url(); ?>" class="btn">WordPress Admin</a>
            <a href="<?php echo home_url(); ?>" class="btn">Home Page</a>
        </div>
    </div>
</body>
</html>
