<?php
/**
 * Database Tables Analysis by Plugin Groups
 * Phân tích bảng database theo từng nhóm plugin
 */

require_once dirname(__FILE__) . '/wp-config.php';

global $wpdb;

// Lấy tất cả bảng
$tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
$table_names = [];
foreach ($tables as $table) {
    $table_names[] = $table[0];
}

// Phân loại bảng theo nhóm
$table_groups = [
    'WordPress Core' => [],
    'Environmental Content Recommendation' => [],
    'Environmental Impact Assessment' => [],
    'Petition System' => [],
    'Donation System' => [],
    'Item Exchange' => [],
    'Live Chat' => [],
    'Analytics Dashboard' => [],
    'Testing & QA' => [],
    'WooCommerce' => [],
    'Other Plugins' => [],
    'Custom Tables' => []
];

foreach ($table_names as $table_name) {
    $prefix = $wpdb->prefix;
    
    // WordPress Core tables
    $wp_core_tables = [
        $prefix . 'posts', $prefix . 'postmeta', $prefix . 'users', $prefix . 'usermeta',
        $prefix . 'terms', $prefix . 'termmeta', $prefix . 'term_relationships', $prefix . 'term_taxonomy',
        $prefix . 'comments', $prefix . 'commentmeta', $prefix . 'options', $prefix . 'links'
    ];
    
    if (in_array($table_name, $wp_core_tables)) {
        $table_groups['WordPress Core'][] = $table_name;
    }
    // Environmental Content Recommendation
    elseif (strpos($table_name, $prefix . 'ecr_') === 0) {
        $table_groups['Environmental Content Recommendation'][] = $table_name;
    }
    // Environmental Impact Assessment
    elseif (strpos($table_name, $prefix . 'eia_') === 0) {
        $table_groups['Environmental Impact Assessment'][] = $table_name;
    }
    // Petition System
    elseif (strpos($table_name, $prefix . 'petition') === 0 || strpos($table_name, $prefix . 'ep_') === 0) {
        $table_groups['Petition System'][] = $table_name;
    }
    // Donation System
    elseif (strpos($table_name, $prefix . 'donation') === 0 || strpos($table_name, $prefix . 'ed_') === 0) {
        $table_groups['Donation System'][] = $table_name;
    }
    // Item Exchange
    elseif (strpos($table_name, $prefix . 'item_exchange') === 0 || strpos($table_name, $prefix . 'eie_') === 0) {
        $table_groups['Item Exchange'][] = $table_name;
    }
    // Live Chat
    elseif (strpos($table_name, $prefix . 'live_chat') === 0 || strpos($table_name, $prefix . 'elc_') === 0) {
        $table_groups['Live Chat'][] = $table_name;
    }
    // Analytics Dashboard
    elseif (strpos($table_name, $prefix . 'analytics') === 0 || strpos($table_name, $prefix . 'ead_') === 0) {
        $table_groups['Analytics Dashboard'][] = $table_name;
    }
    // Testing & QA
    elseif (strpos($table_name, $prefix . 'etq_') === 0) {
        $table_groups['Testing & QA'][] = $table_name;
    }
    // WooCommerce
    elseif (strpos($table_name, $prefix . 'wc_') === 0 || strpos($table_name, $prefix . 'woocommerce_') === 0) {
        $table_groups['WooCommerce'][] = $table_name;
    }
    // Other WordPress plugin tables
    elseif (strpos($table_name, $prefix) === 0) {
        $table_groups['Other Plugins'][] = $table_name;
    }
    // Custom tables without WordPress prefix
    else {
        $table_groups['Custom Tables'][] = $table_name;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tables Analysis - Environmental Platform</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; }
        .group { margin: 25px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa; }
        .group h3 { color: #e74c3c; margin-top: 0; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; }
        .table-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin: 15px 0; }
        .table-item { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .table-item h4 { margin: 0 0 10px 0; color: #3498db; }
        .stats { display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 150px; background: #3498db; color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 1.8em; }
        .sql-export { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .sql-export h3 { color: #ecf0f1; margin-top: 0; }
        .btn { display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #219a52; }
        .empty-group { color: #999; font-style: italic; }
        textarea { width: 100%; height: 300px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🗂️ Phân tích Database Tables - Environmental Platform</h1>
    <p><strong>Ngày phân tích:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>

    <?php
    // Thống kê tổng quan
    $total_tables = count($table_names);
    $total_groups = 0;
    $max_group_size = 0;
    $max_group_name = '';
    
    foreach ($table_groups as $group_name => $tables) {
        if (!empty($tables)) {
            $total_groups++;
            if (count($tables) > $max_group_size) {
                $max_group_size = count($tables);
                $max_group_name = $group_name;
            }
        }
    }
    ?>

    <div class="stats">
        <div class="stat-box">
            <h3><?php echo $total_tables; ?></h3>
            <p>Tổng số bảng</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $total_groups; ?></h3>
            <p>Nhóm có bảng</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $max_group_size; ?></h3>
            <p>Bảng nhiều nhất</p>
        </div>
        <div class="stat-box">
            <h3><?php echo strlen($max_group_name) > 10 ? substr($max_group_name, 0, 10) . '...' : $max_group_name; ?></h3>
            <p>Nhóm lớn nhất</p>
        </div>
    </div>

    <h2>📊 Chi tiết từng nhóm</h2>

    <?php foreach ($table_groups as $group_name => $tables): ?>
        <div class="group">
            <h3><?php echo $group_name; ?> (<?php echo count($tables); ?> bảng)</h3>
            
            <?php if (empty($tables)): ?>
                <p class="empty-group">Không có bảng nào trong nhóm này.</p>
            <?php else: ?>
                <div class="table-list">
                    <?php foreach ($tables as $table_name): ?>
                        <div class="table-item">
                            <h4><?php echo $table_name; ?></h4>
                            <?php
                            // Lấy thông tin bảng
                            $table_info = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'");
                            if ($table_info) {
                                echo "<p><strong>Rows:</strong> " . number_format($table_info->Rows) . "</p>";
                                echo "<p><strong>Engine:</strong> {$table_info->Engine}</p>";
                                echo "<p><strong>Size:</strong> " . formatBytes($table_info->Data_length) . "</p>";
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="sql-export">
        <h3>📝 Xuất SQL CREATE TABLE theo nhóm</h3>
        <a href="export-database-structure.php" class="btn">📊 Xuất chi tiết có giao diện</a>
        <a href="simple-database-export.php" class="btn">📄 Xuất SQL đơn giản</a>
        <a href="#" class="btn" onclick="generateGroupSQL()">🔧 Tạo SQL theo nhóm</a>
    </div>

    <div id="sql-output" style="display: none;">
        <h3>SQL Commands by Groups</h3>
        <textarea id="sql-textarea"></textarea>
    </div>
</div>

<script>
function generateGroupSQL() {
    let sql = '';
    sql += '-- =====================================================\n';
    sql += '-- ENVIRONMENTAL PLATFORM - DATABASE STRUCTURE BY GROUPS\n';
    sql += '-- Ngày tạo: <?php echo date("d/m/Y H:i:s"); ?>\n';
    sql += '-- Database: <?php echo DB_NAME; ?>\n';
    sql += '-- =====================================================\n\n';
    
    <?php foreach ($table_groups as $group_name => $tables): ?>
        <?php if (!empty($tables)): ?>
            sql += '\n-- =====================================================\n';
            sql += '-- <?php echo $group_name; ?> (<?php echo count($tables); ?> tables)\n';
            sql += '-- =====================================================\n\n';
            
            <?php foreach ($tables as $table_name): ?>
                <?php
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
                if ($create_table) {
                    $create_sql = addslashes($create_table[1]);
                    echo "sql += '-- Table: {$table_name}\\n';\n";
                    echo "sql += 'DROP TABLE IF EXISTS `{$table_name}`;\\n';\n";
                    echo "sql += '" . $create_sql . ";\\n\\n';\n";
                }
                ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    
    document.getElementById('sql-output').style.display = 'block';
    document.getElementById('sql-textarea').value = sql;
    document.getElementById('sql-textarea').select();
}

<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
</script>

</body>
</html>
