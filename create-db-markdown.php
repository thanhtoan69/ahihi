<?php
// Script đơn giản để xuất database structure
require_once dirname(__FILE__) . '/wp-config.php';

global $wpdb;

// Tạo nội dung markdown
$md_content = "# Environmental Platform - Database Tables Structure\n\n";
$md_content .= "**Generated Date**: " . date('Y-m-d H:i:s') . "\n";
$md_content .= "**Database Name**: " . DB_NAME . "\n\n";

// Lấy tất cả bảng
$tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

if ($tables) {
    $md_content .= "## Total Tables: " . count($tables) . "\n\n";
    
    // Danh sách bảng
    $md_content .= "| No | Table Name | Rows |\n";
    $md_content .= "|----|-----------:|-----:|\n";
    
    $i = 1;
    foreach ($tables as $table) {
        $table_name = $table[0];
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
        $md_content .= "| {$i} | `{$table_name}` | " . number_format($row_count) . " |\n";
        $i++;
    }
    
    $md_content .= "\n---\n\n";
    
    // CREATE TABLE statements
    $md_content .= "## CREATE TABLE Statements\n\n";
    $md_content .= "```sql\n";
    $md_content .= "-- Environmental Platform Database Structure\n";
    $md_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        if ($create_table && isset($create_table[1])) {
            $md_content .= "-- Table: {$table_name}\n";
            $md_content .= $create_table[1] . ";\n\n";
        }
    }
    
    $md_content .= "```\n";
}

// Lưu file
$file_path = __DIR__ . '/DATABASE_STRUCTURE.md';
$result = file_put_contents($file_path, $md_content);

if ($result !== false) {
    echo "SUCCESS: File created at DATABASE_STRUCTURE.md\n";
    echo "Size: " . number_format($result) . " bytes\n";
    echo "Tables: " . count($tables) . "\n";
} else {
    echo "ERROR: Could not create file\n";
}
?>
