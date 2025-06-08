<?php
/**
 * Database Tables Exporter - Chạy từ Terminal/Browser
 * Xuất tất cả bảng database thành file markdown
 */

// Check if running from CLI or web
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Web browser output
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Database Export</title><meta charset='utf-8'></head><body>";
}

// WordPress setup
require_once dirname(__FILE__) . '/wp-config.php';

if (!$is_cli) {
    echo "<h1>🗄️ Đang xuất cấu trúc database...</h1>";
    echo "<pre>";
}

global $wpdb;

echo "📋 Bắt đầu xuất database...\n";
echo "Database: " . DB_NAME . "\n";
echo "Thời gian: " . date('d/m/Y H:i:s') . "\n\n";

try {
    // Lấy tất cả bảng
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    
    if (!$tables) {
        echo "❌ Không tìm thấy bảng nào!\n";
        exit;
    }
    
    echo "✅ Tìm thấy " . count($tables) . " bảng\n\n";
    
    // Tạo nội dung markdown
    $content = "# 🗄️ Environmental Platform - Danh Sách Bảng Database\n\n";
    $content .= "**Ngày xuất**: " . date('d/m/Y H:i:s') . "\n";
    $content .= "**Database**: " . DB_NAME . "\n";
    $content .= "**Tổng số bảng**: " . count($tables) . "\n\n";
    $content .= "---\n\n";
    
    // Danh sách tất cả bảng
    $content .= "## 📋 Danh Sách Tất Cả Bảng\n\n";
    $content .= "| STT | Tên Bảng | Số Dòng |\n";
    $content .= "|-----|----------|----------|\n";
    
    $stt = 1;
    foreach ($tables as $table) {
        $table_name = $table[0];
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
        $content .= "| {$stt} | `{$table_name}` | " . number_format($row_count) . " |\n";
        echo "Đang xử lý bảng {$stt}: {$table_name} ({$row_count} dòng)\n";
        $stt++;
    }
    
    $content .= "\n---\n\n";
    
    // CREATE TABLE statements
    $content .= "## 📝 Câu Lệnh CREATE TABLE\n\n";
    $content .= "### Script Tạo Lại Toàn Bộ Database:\n\n";
    $content .= "```sql\n";
    $content .= "-- =====================================================\n";
    $content .= "-- ENVIRONMENTAL PLATFORM - DATABASE STRUCTURE\n";
    $content .= "-- Ngày tạo: " . date('d/m/Y H:i:s') . "\n";
    $content .= "-- Database: " . DB_NAME . "\n";
    $content .= "-- Tổng số bảng: " . count($tables) . "\n";
    $content .= "-- =====================================================\n\n";
    
    echo "\n📝 Đang tạo CREATE TABLE statements...\n";
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        echo "Đang xuất CREATE TABLE cho: {$table_name}\n";
        
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        if ($create_table && isset($create_table[1])) {
            $content .= "-- Bảng: {$table_name}\n";
            $content .= $create_table[1] . ";\n\n";
        }
    }
    
    $content .= "-- Kết thúc script tạo database\n";
    $content .= "```\n\n";
    
    // Chi tiết từng bảng
    $content .= "## 📊 Chi Tiết Từng Bảng\n\n";
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
        
        $content .= "### Bảng: `{$table_name}`\n\n";
        $content .= "**Số dòng**: " . number_format($row_count) . "\n\n";
        
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        if ($create_table && isset($create_table[1])) {
            $content .= "**CREATE TABLE Statement**:\n";
            $content .= "```sql\n";
            $content .= $create_table[1] . ";\n";
            $content .= "```\n\n";
        }
        
        $content .= "---\n\n";
    }
    
    $content .= "*File được tạo tự động - " . date('d/m/Y H:i:s') . "*\n";
    
    // Lưu file
    $filename = 'DATABASE_TABLES_LIST.md';
    $filepath = dirname(__FILE__) . '/' . $filename;
    
    echo "\n💾 Đang lưu file: {$filename}\n";
    
    if (file_put_contents($filepath, $content)) {
        $filesize = filesize($filepath);
        echo "✅ Lưu file thành công!\n";
        echo "📄 File: {$filename}\n";
        echo "📏 Kích thước: " . number_format($filesize) . " bytes\n";
        echo "📊 Số bảng: " . count($tables) . "\n";
        
        if (!$is_cli) {
            echo "\n<a href='{$filename}' target='_blank' style='background:#0073aa;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>📖 Xem File</a>";
        }
    } else {
        echo "❌ Lỗi: Không thể lưu file!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

if (!$is_cli) {
    echo "</pre>";
    echo "<br><br>";
    echo "<a href='" . admin_url() . "' style='background:#0073aa;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;margin-right:10px;'>WordPress Admin</a>";
    echo "<a href='" . home_url() . "' style='background:#00a32a;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Trang Chủ</a>";
    echo "</body></html>";
}

echo "\n🎉 Hoàn thành xuất database!\n";
?>
