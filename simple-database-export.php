<?php
/**
 * Simple Database Structure Export
 * Xuất đơn giản tất cả câu lệnh CREATE TABLE
 */

require_once dirname(__FILE__) . '/wp-config.php';

header('Content-Type: text/plain; charset=utf-8');

global $wpdb;

echo "-- =====================================================\n";
echo "-- ENVIRONMENTAL PLATFORM - DATABASE STRUCTURE\n";
echo "-- Ngày tạo: " . date('d/m/Y H:i:s') . "\n";
echo "-- Database: " . DB_NAME . "\n";
echo "-- =====================================================\n\n";

try {
    // Lấy tất cả bảng
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    
    if (empty($tables)) {
        echo "-- Không tìm thấy bảng nào!\n";
        exit;
    }
    
    echo "-- Tổng số bảng: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        
        echo "-- =====================================================\n";
        echo "-- Bảng: {$table_name}\n";
        echo "-- =====================================================\n\n";
        
        // Lấy câu lệnh CREATE TABLE
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        
        if ($create_table) {
            $create_sql = $create_table[1];
            
            // Thêm DROP TABLE trước CREATE
            echo "DROP TABLE IF EXISTS `{$table_name}`;\n";
            echo $create_sql . ";\n\n";
        }
    }
    
    echo "-- =====================================================\n";
    echo "-- KẾT THÚC EXPORT DATABASE STRUCTURE\n";
    echo "-- =====================================================\n";
    
} catch (Exception $e) {
    echo "-- LỖI: " . $e->getMessage() . "\n";
}
?>
