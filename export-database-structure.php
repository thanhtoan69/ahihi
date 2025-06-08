<?php
/**
 * Export Database Structure - Liệt kê tất cả bảng và tạo câu lệnh CREATE TABLE
 * 
 * Script này sẽ:
 * 1. Liệt kê tất cả các bảng trong database
 * 2. Tạo câu lệnh CREATE TABLE cho từng bảng
 * 3. Xuất ra file SQL hoàn chỉnh
 * 
 * @package Environmental Platform
 * @version 1.0.0
 */

// Cấu hình WordPress
require_once dirname(__FILE__) . '/wp-config.php';

// Thiết lập header cho output
header('Content-Type: text/html; charset=utf-8');

// Tăng thời gian thực thi
set_time_limit(300);
ini_set('memory_limit', '512M');

echo "<!DOCTYPE html>\n";
echo "<html lang='vi'>\n";
echo "<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>Database Structure Export - Environmental Platform</title>\n";
echo "<style>\n";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f5f5; }\n";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }\n";
echo "h1, h2, h3 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }\n";
echo ".table-info { margin: 20px 0; padding: 15px; background: #ecf0f1; border-radius: 5px; border-left: 4px solid #3498db; }\n";
echo ".sql-code { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 5px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.4; }\n";
echo ".table-list { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0; }\n";
echo ".stats { display: flex; gap: 20px; margin: 20px 0; }\n";
echo ".stat-box { flex: 1; background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; }\n";
echo ".stat-box h3 { margin: 0; font-size: 2em; }\n";
echo ".stat-box p { margin: 5px 0 0 0; }\n";
echo ".download-btn { display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }\n";
echo ".download-btn:hover { background: #219a52; }\n";
echo ".section { margin: 30px 0; }\n";
echo ".warning { background: #f39c12; color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }\n";
echo ".success { background: #27ae60; color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }\n";
echo "pre { white-space: pre-wrap; word-wrap: break-word; }\n";
echo ".table-group { margin: 25px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }\n";
echo ".table-group h4 { color: #8e44ad; margin-top: 0; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<div class='container'>\n";
echo "<h1>🗄️ Database Structure Export - Environmental Platform</h1>\n";
echo "<p><strong>Ngày tạo:</strong> " . date('d/m/Y H:i:s') . "</p>\n";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>\n";

global $wpdb;

try {
    // Lấy danh sách tất cả các bảng
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    
    if (empty($tables)) {
        echo "<div class='warning'>⚠️ Không tìm thấy bảng nào trong database!</div>\n";
        exit;
    }
    
    // Thống kê
    $total_tables = count($tables);
    $wordpress_tables = 0;
    $plugin_tables = 0;
    $custom_tables = 0;
    
    $table_names = [];
    foreach ($tables as $table) {
        $table_name = $table[0];
        $table_names[] = $table_name;
        
        if (strpos($table_name, $wpdb->prefix) === 0) {
            $clean_name = str_replace($wpdb->prefix, '', $table_name);
            if (in_array($clean_name, ['posts', 'postmeta', 'users', 'usermeta', 'terms', 'termmeta', 'term_relationships', 'term_taxonomy', 'comments', 'commentmeta', 'options', 'links'])) {
                $wordpress_tables++;
            } else {
                $plugin_tables++;
            }
        } else {
            $custom_tables++;
        }
    }
    
    // Hiển thị thống kê
    echo "<div class='stats'>\n";
    echo "<div class='stat-box'>\n";
    echo "<h3>{$total_tables}</h3>\n";
    echo "<p>Tổng số bảng</p>\n";
    echo "</div>\n";
    echo "<div class='stat-box'>\n";
    echo "<h3>{$wordpress_tables}</h3>\n";
    echo "<p>Bảng WordPress</p>\n";
    echo "</div>\n";
    echo "<div class='stat-box'>\n";
    echo "<h3>{$plugin_tables}</h3>\n";
    echo "<p>Bảng Plugin</p>\n";
    echo "</div>\n";
    echo "<div class='stat-box'>\n";
    echo "<h3>{$custom_tables}</h3>\n";
    echo "<p>Bảng tùy chỉnh</p>\n";
    echo "</div>\n";
    echo "</div>\n";
    
    echo "<div class='success'>✅ Đã tìm thấy {$total_tables} bảng trong database '{DB_NAME}'</div>\n";
    
    // Danh sách các bảng
    echo "<div class='section'>\n";
    echo "<h2>📋 Danh sách tất cả các bảng</h2>\n";
    echo "<div class='table-list'>\n";
    sort($table_names);
    foreach ($table_names as $index => $table_name) {
        echo ($index + 1) . ". <strong>{$table_name}</strong><br>\n";
    }
    echo "</div>\n";
    echo "</div>\n";
    
    // Tạo file SQL output
    $sql_output = "";
    $sql_output .= "-- =====================================================\n";
    $sql_output .= "-- ENVIRONMENTAL PLATFORM - DATABASE STRUCTURE EXPORT\n";
    $sql_output .= "-- Ngày tạo: " . date('d/m/Y H:i:s') . "\n";
    $sql_output .= "-- Database: " . DB_NAME . "\n";
    $sql_output .= "-- Tổng số bảng: {$total_tables}\n";
    $sql_output .= "-- =====================================================\n\n";
    
    $sql_output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_output .= "SET AUTOCOMMIT = 0;\n";
    $sql_output .= "START TRANSACTION;\n";
    $sql_output .= "SET time_zone = \"+00:00\";\n\n";
    
    $sql_output .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $sql_output .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $sql_output .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $sql_output .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
    
    // Phân loại bảng
    $wordpress_core_tables = [];
    $plugin_tables_list = [];
    $custom_tables_list = [];
    
    foreach ($table_names as $table_name) {
        if (strpos($table_name, $wpdb->prefix) === 0) {
            $clean_name = str_replace($wpdb->prefix, '', $table_name);
            if (in_array($clean_name, ['posts', 'postmeta', 'users', 'usermeta', 'terms', 'termmeta', 'term_relationships', 'term_taxonomy', 'comments', 'commentmeta', 'options', 'links'])) {
                $wordpress_core_tables[] = $table_name;
            } else {
                $plugin_tables_list[] = $table_name;
            }
        } else {
            $custom_tables_list[] = $table_name;
        }
    }
    
    // Xuất cấu trúc từng nhóm bảng
    $table_groups = [
        'WordPress Core Tables' => $wordpress_core_tables,
        'Plugin Tables' => $plugin_tables_list,
        'Custom Tables' => $custom_tables_list
    ];
    
    foreach ($table_groups as $group_name => $group_tables) {
        if (empty($group_tables)) continue;
        
        echo "<div class='table-group'>\n";
        echo "<h4>🔧 {$group_name} (" . count($group_tables) . " bảng)</h4>\n";
        
        $sql_output .= "\n-- =====================================================\n";
        $sql_output .= "-- {$group_name}\n";
        $sql_output .= "-- =====================================================\n\n";
        
        foreach ($group_tables as $table_name) {
            echo "<div class='table-info'>\n";
            echo "<h5>📊 Bảng: {$table_name}</h5>\n";
            
            // Lấy cấu trúc bảng
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            
            if ($create_table) {
                $create_sql = $create_table[1];
                
                // Làm đẹp SQL
                $create_sql = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create_sql);
                $create_sql .= ";\n\n";
                
                // Thêm vào output
                $sql_output .= "-- Cấu trúc bảng: {$table_name}\n";
                $sql_output .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
                $sql_output .= $create_sql;
                
                // Hiển thị thông tin bảng
                $table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'");
                if ($table_status) {
                    echo "<p><strong>Engine:</strong> {$table_status->Engine}</p>\n";
                    echo "<p><strong>Rows:</strong> " . number_format($table_status->Rows) . "</p>\n";
                    echo "<p><strong>Data Length:</strong> " . formatBytes($table_status->Data_length) . "</p>\n";
                    echo "<p><strong>Collation:</strong> {$table_status->Collation}</p>\n";
                }
                
                // Hiển thị cấu trúc cột
                $columns = $wpdb->get_results("DESCRIBE `{$table_name}`");
                if ($columns) {
                    echo "<details>\n";
                    echo "<summary><strong>Cấu trúc cột (" . count($columns) . " cột)</strong></summary>\n";
                    echo "<table style='width:100%; border-collapse: collapse; margin: 10px 0;'>\n";
                    echo "<tr style='background: #34495e; color: white;'>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Tên cột</th>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Kiểu dữ liệu</th>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Null</th>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Key</th>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Default</th>\n";
                    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Extra</th>\n";
                    echo "</tr>\n";
                    
                    foreach ($columns as $column) {
                        echo "<tr>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'><strong>{$column->Field}</strong></td>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$column->Type}</td>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$column->Null}</td>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$column->Key}</td>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$column->Default}</td>\n";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$column->Extra}</td>\n";
                        echo "</tr>\n";
                    }
                    echo "</table>\n";
                    echo "</details>\n";
                }
                
                // Hiển thị SQL (có thể mở rộng)
                echo "<details>\n";
                echo "<summary><strong>Câu lệnh CREATE TABLE</strong></summary>\n";
                echo "<div class='sql-code'>\n";
                echo "<pre>" . htmlspecialchars($create_sql) . "</pre>\n";
                echo "</div>\n";
                echo "</details>\n";
            }
            
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    // Kết thúc file SQL
    $sql_output .= "\nCOMMIT;\n\n";
    $sql_output .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $sql_output .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $sql_output .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    
    // Lưu file SQL
    $sql_filename = 'environmental_platform_structure_' . date('Y-m-d_H-i-s') . '.sql';
    $sql_filepath = dirname(__FILE__) . '/' . $sql_filename;
    
    if (file_put_contents($sql_filepath, $sql_output)) {
        echo "<div class='success'>✅ Đã tạo file SQL: {$sql_filename}</div>\n";
        echo "<div class='section'>\n";
        echo "<h3>📥 Tải xuống</h3>\n";
        echo "<a href='{$sql_filename}' class='download-btn' download>📁 Tải xuống file SQL</a>\n";
        echo "<a href='#' class='download-btn' onclick='copyToClipboard()'>📋 Copy SQL vào clipboard</a>\n";
        echo "</div>\n";
    }
    
    // Hiển thị toàn bộ SQL trong textarea (để copy)
    echo "<div class='section'>\n";
    echo "<h3>📝 Toàn bộ câu lệnh SQL</h3>\n";
    echo "<textarea id='sql-content' style='width: 100%; height: 400px; font-family: monospace; font-size: 12px;'>" . htmlspecialchars($sql_output) . "</textarea>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='warning'>❌ Lỗi: " . $e->getMessage() . "</div>\n";
}

echo "</div>\n";

// JavaScript functions
echo "<script>\n";
echo "function copyToClipboard() {\n";
echo "    const textarea = document.getElementById('sql-content');\n";
echo "    textarea.select();\n";
echo "    textarea.setSelectionRange(0, 99999);\n";
echo "    document.execCommand('copy');\n";
echo "    alert('Đã copy SQL vào clipboard!');\n";
echo "}\n";
echo "</script>\n";

echo "</body>\n";
echo "</html>\n";

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
