<?php
/**
 * Database Tables to Markdown Export
 * Xuất tất cả bảng database thành file markdown với CREATE TABLE statements
 */

require_once dirname(__FILE__) . '/wp-config.php';

global $wpdb;

// Tạo nội dung markdown
$markdown = "# 🗄️ Environmental Platform - Cấu Trúc Database\n\n";
$markdown .= "**Ngày xuất**: " . date('d/m/Y H:i:s') . "\n";
$markdown .= "**Database**: " . DB_NAME . "\n";
$markdown .= "**WordPress Version**: " . get_bloginfo('version') . "\n";
$markdown .= "**MySQL Version**: " . $wpdb->db_version() . "\n\n";
$markdown .= "---\n\n";

try {
    // Lấy tất cả bảng
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    
    if ($tables) {
        $markdown .= "## 📋 Tổng Quan Database\n\n";
        $markdown .= "**Tổng số bảng**: " . count($tables) . "\n\n";
        
        // Phân loại bảng
        $wordpress_tables = [];
        $plugin_tables = [];
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            $clean_name = str_replace($wpdb->prefix, '', $table_name);
            
            // Bảng WordPress core
            if (in_array($clean_name, ['posts', 'postmeta', 'comments', 'commentmeta', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta', 'options', 'links'])) {
                $wordpress_tables[] = $table_name;
            } else {
                $plugin_tables[] = $table_name;
            }
        }
        
        $markdown .= "### Phân Loại Bảng\n";
        $markdown .= "- **Bảng WordPress Core**: " . count($wordpress_tables) . "\n";
        $markdown .= "- **Bảng Plugin/Custom**: " . count($plugin_tables) . "\n\n";
        
        // Xuất bảng WordPress Core
        if (!empty($wordpress_tables)) {
            $markdown .= "## 🏛️ Bảng WordPress Core\n\n";
            foreach ($wordpress_tables as $table_name) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $markdown .= "### `{$table_name}`\n";
                $markdown .= "**Số dòng**: " . number_format($row_count) . "\n\n";
                
                // Lấy CREATE TABLE statement
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                if ($create_table && isset($create_table[1])) {
                    $markdown .= "```sql\n";
                    $markdown .= $create_table[1] . ";\n";
                    $markdown .= "```\n\n";
                }
                $markdown .= "---\n\n";
            }
        }
        
        // Xuất bảng Plugin/Custom
        if (!empty($plugin_tables)) {
            $markdown .= "## 🔌 Bảng Plugin & Custom\n\n";
            foreach ($plugin_tables as $table_name) {
                $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $markdown .= "### `{$table_name}`\n";
                $markdown .= "**Số dòng**: " . number_format($row_count) . "\n\n";
                
                // Lấy CREATE TABLE statement
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                if ($create_table && isset($create_table[1])) {
                    $markdown .= "```sql\n";
                    $markdown .= $create_table[1] . ";\n";
                    $markdown .= "```\n\n";
                }
                $markdown .= "---\n\n";
            }
        }
        
        // Script tạo lại toàn bộ database
        $markdown .= "## 📝 Script Tạo Lại Toàn Bộ Database\n\n";
        $markdown .= "Copy đoạn SQL sau để tạo lại toàn bộ cấu trúc database:\n\n";
        $markdown .= "```sql\n";
        $markdown .= "-- =====================================================\n";
        $markdown .= "-- ENVIRONMENTAL PLATFORM - COMPLETE DATABASE STRUCTURE\n";
        $markdown .= "-- Ngày tạo: " . date('d/m/Y H:i:s') . "\n";
        $markdown .= "-- Database: " . DB_NAME . "\n";
        $markdown .= "-- Tổng số bảng: " . count($tables) . "\n";
        $markdown .= "-- =====================================================\n\n";
        
        // Tất cả CREATE TABLE statements
        foreach ($tables as $table) {
            $table_name = $table[0];
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            if ($create_table && isset($create_table[1])) {
                $markdown .= "-- Bảng: {$table_name}\n";
                $markdown .= $create_table[1] . ";\n\n";
            }
        }
        
        $markdown .= "-- End of database structure\n";
        $markdown .= "```\n\n";
        
        // Thống kê
        $total_rows = 0;
        foreach ($tables as $table) {
            $table_name = $table[0];
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
            $total_rows += $row_count;
        }
        
        $markdown .= "## 📊 Thống Kê Database\n\n";
        $markdown .= "| Thông Tin | Giá Trị |\n";
        $markdown .= "|-----------|----------|\n";
        $markdown .= "| Tổng số bảng | " . count($tables) . " |\n";
        $markdown .= "| Tổng số dòng | " . number_format($total_rows) . " |\n";
        $markdown .= "| Bảng WordPress Core | " . count($wordpress_tables) . " |\n";
        $markdown .= "| Bảng Plugin/Custom | " . count($plugin_tables) . " |\n";
        $markdown .= "| Database Engine | MySQL " . $wpdb->db_version() . " |\n";
        $markdown .= "| WordPress Version | " . get_bloginfo('version') . " |\n\n";
        
        // Danh sách chi tiết các bảng
        $markdown .= "## 📑 Danh Sách Chi Tiết Tất Cả Bảng\n\n";
        $markdown .= "| STT | Tên Bảng | Số Dòng | Loại |\n";
        $markdown .= "|-----|----------|---------|-------|\n";
        
        $stt = 1;
        foreach ($tables as $table) {
            $table_name = $table[0];
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
            $type = in_array($table_name, $wordpress_tables) ? "WordPress Core" : "Plugin/Custom";
            $markdown .= "| {$stt} | `{$table_name}` | " . number_format($row_count) . " | {$type} |\n";
            $stt++;
        }
        
        $markdown .= "\n---\n\n";
        $markdown .= "*File được tạo tự động bởi Environmental Platform Database Export Tool*\n";
        $markdown .= "*Thời gian xuất: " . date('d/m/Y H:i:s') . "*\n";
        
    } else {
        $markdown .= "❌ **Lỗi**: Không tìm thấy bảng nào trong database.\n";
    }
    
} catch (Exception $e) {
    $markdown .= "❌ **Lỗi**: " . $e->getMessage() . "\n";
}

// Lưu vào file
$filename = 'DANH_SACH_BANG_DATABASE.md';
$filepath = dirname(__FILE__) . '/' . $filename;

if (file_put_contents($filepath, $markdown)) {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Xuất Database Thành Công</title>\n";
    echo "<meta charset='utf-8'>\n";
    echo "<style>\n";
    echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f1; }\n";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }\n";
    echo ".success { color: #00a32a; font-weight: bold; }\n";
    echo ".info { background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0; }\n";
    echo ".button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }\n";
    echo ".button:hover { background: #005177; }\n";
    echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; }\n";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
    echo "th { background: #f2f2f2; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    echo "<div class='container'>\n";
    echo "<h1>✅ Xuất Cấu Trúc Database Thành Công!</h1>\n";
    
    echo "<div class='info'>\n";
    echo "<h3>📋 Thông Tin File Đã Xuất:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Tên file:</strong> {$filename}</li>\n";
    echo "<li><strong>Kích thước:</strong> " . number_format(filesize($filepath)) . " bytes</li>\n";
    echo "<li><strong>Số bảng:</strong> " . count($tables) . "</li>\n";
    echo "<li><strong>Thời gian tạo:</strong> " . date('d/m/Y H:i:s') . "</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<h3>🔗 Liên Kết Nhanh:</h3>\n";
    echo "<a href='{$filename}' target='_blank' class='button'>📖 Xem File Đã Xuất</a>\n";
    echo "<a href='" . admin_url() . "' class='button'>🏛️ WordPress Admin</a>\n";
    echo "<a href='" . home_url() . "' class='button'>🏠 Trang Chủ</a>\n";
    
    echo "<h3>📊 Tóm Tắt Database:</h3>\n";
    echo "<table>\n";
    echo "<tr><th>Thông Tin</th><th>Giá Trị</th></tr>\n";
    echo "<tr><td>Database Name</td><td>" . DB_NAME . "</td></tr>\n";
    echo "<tr><td>Tổng Số Bảng</td><td>" . count($tables) . "</td></tr>\n";
    echo "<tr><td>MySQL Version</td><td>" . $wpdb->db_version() . "</td></tr>\n";
    echo "<tr><td>WordPress Version</td><td>" . get_bloginfo('version') . "</td></tr>\n";
    echo "</table>\n";
    
    echo "</div>\n</body>\n</html>";
} else {
    echo "❌ Lỗi: Không thể tạo file markdown.";
}
?>
