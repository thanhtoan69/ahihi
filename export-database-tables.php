<?php
/**
 * Export Database Tables to Markdown with CREATE TABLE Statements
 * 
 * This script exports all database tables structure to a markdown file
 * with CREATE TABLE statements for backup and documentation purposes.
 */

// WordPress setup
require_once dirname(__FILE__) . '/wp-config.php';

// Set execution time and memory limits
set_time_limit(300);
ini_set('memory_limit', '512M');

global $wpdb;

// Output file
$output_file = dirname(__FILE__) . '/DATABASE_TABLES_STRUCTURE.md';

// Start output buffering
ob_start();

echo "# Database Tables Structure - Environmental Platform\n\n";
echo "**Generated Date**: " . date('Y-m-d H:i:s') . "\n";
echo "**Database Name**: " . DB_NAME . "\n";
echo "**WordPress Version**: " . get_bloginfo('version') . "\n";
echo "**PHP Version**: " . PHP_VERSION . "\n";
echo "**MySQL Version**: " . $wpdb->db_version() . "\n\n";

echo "---\n\n";

// Get all tables
$tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

if (!$tables) {
    echo "❌ **Error**: No tables found in database.\n\n";
    file_put_contents($output_file, ob_get_clean());
    exit;
}

echo "## 📊 Database Overview\n\n";
echo "**Total Tables**: " . count($tables) . "\n\n";

// Categorize tables
$wordpress_core_tables = [];
$plugin_tables = [];
$custom_tables = [];

$wp_prefix = $wpdb->prefix;

foreach ($tables as $table) {
    $table_name = $table[0];
    $clean_name = str_replace($wp_prefix, '', $table_name);
    
    // WordPress core tables
    if (in_array($clean_name, [
        'posts', 'postmeta', 'comments', 'commentmeta', 'terms', 'term_taxonomy', 
        'term_relationships', 'users', 'usermeta', 'options', 'links'
    ])) {
        $wordpress_core_tables[] = $table_name;
    }
    // Plugin tables (with specific prefixes)
    elseif (preg_match('/^' . preg_quote($wp_prefix) . '(wc_|woocommerce_|edd_|etq_|ecr_|eia_|env_|donation_|petition_|analytics_|chat_|exchange_|mobile_api_)/', $table_name)) {
        $plugin_tables[] = $table_name;
    }
    // Custom tables
    else {
        $custom_tables[] = $table_name;
    }
}

echo "### Table Categories\n";
echo "- **WordPress Core Tables**: " . count($wordpress_core_tables) . "\n";
echo "- **Plugin Tables**: " . count($plugin_tables) . "\n";
echo "- **Custom Tables**: " . count($custom_tables) . "\n\n";

echo "---\n\n";

/**
 * Function to get CREATE TABLE statement
 */
function getCreateTableStatement($table_name) {
    global $wpdb;
    
    $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
    if ($create_table && isset($create_table[1])) {
        return $create_table[1];
    }
    return null;
}

/**
 * Function to get table info
 */
function getTableInfo($table_name) {
    global $wpdb;
    
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
    $table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'", ARRAY_A);
    
    $info = [
        'rows' => $row_count,
        'engine' => $table_status['Engine'] ?? 'Unknown',
        'collation' => $table_status['Collation'] ?? 'Unknown',
        'size' => isset($table_status['Data_length']) ? round($table_status['Data_length'] / 1024, 2) : 0
    ];
    
    return $info;
}

/**
 * Function to export table category
 */
function exportTableCategory($category_name, $tables, $description = '') {
    if (empty($tables)) return;
    
    echo "## {$category_name}\n\n";
    if ($description) {
        echo "{$description}\n\n";
    }
    
    echo "**Tables Count**: " . count($tables) . "\n\n";
    
    foreach ($tables as $table_name) {
        echo "### 📋 Table: `{$table_name}`\n\n";
        
        // Get table info
        $info = getTableInfo($table_name);
        echo "**Table Information**:\n";
        echo "- **Rows**: " . number_format($info['rows']) . "\n";
        echo "- **Engine**: {$info['engine']}\n";
        echo "- **Collation**: {$info['collation']}\n";
        echo "- **Size**: " . number_format($info['size'], 2) . " KB\n\n";
        
        // Get CREATE TABLE statement
        $create_statement = getCreateTableStatement($table_name);
        if ($create_statement) {
            echo "**CREATE TABLE Statement**:\n";
            echo "```sql\n";
            echo $create_statement . ";\n";
            echo "```\n\n";
        } else {
            echo "❌ **Error**: Could not retrieve CREATE TABLE statement.\n\n";
        }
        
        echo "---\n\n";
    }
}

// Export WordPress Core Tables
exportTableCategory(
    "🏛️ WordPress Core Tables", 
    $wordpress_core_tables,
    "These are the standard WordPress database tables that store core content, users, and configuration."
);

// Export Plugin Tables
exportTableCategory(
    "🔌 Plugin Tables", 
    $plugin_tables,
    "These tables are created by various WordPress plugins installed on the platform."
);

// Export Custom Tables
exportTableCategory(
    "⚙️ Custom Tables", 
    $custom_tables,
    "These are custom tables created for specific platform functionality."
);

// Generate summary of all CREATE TABLE statements
echo "## 📝 Complete CREATE TABLE Script\n\n";
echo "Below is the complete script to recreate all database tables:\n\n";
echo "```sql\n";
echo "-- Environmental Platform Database Structure\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Database: " . DB_NAME . "\n\n";

$all_tables = array_merge($wordpress_core_tables, $plugin_tables, $custom_tables);
sort($all_tables);

foreach ($all_tables as $table_name) {
    echo "-- Table: {$table_name}\n";
    $create_statement = getCreateTableStatement($table_name);
    if ($create_statement) {
        echo $create_statement . ";\n\n";
    }
}

echo "```\n\n";

// Plugin-specific table analysis
echo "## 🔍 Plugin-Specific Table Analysis\n\n";

$plugin_prefixes = [
    'wc_' => 'WooCommerce',
    'woocommerce_' => 'WooCommerce',
    'edd_' => 'Easy Digital Downloads',
    'etq_' => 'Environmental Testing & QA',
    'ecr_' => 'Environmental Content Recommendation',
    'eia_' => 'Environmental Impact Assessment',
    'env_' => 'Environmental Core',
    'donation_' => 'Donation System',
    'petition_' => 'Petition System',
    'analytics_' => 'Analytics System',
    'chat_' => 'Live Chat System',
    'exchange_' => 'Item Exchange System',
    'mobile_api_' => 'Mobile API System'
];

foreach ($plugin_prefixes as $prefix => $plugin_name) {
    $plugin_tables_filtered = array_filter($plugin_tables, function($table) use ($prefix, $wp_prefix) {
        return strpos($table, $wp_prefix . $prefix) === 0;
    });
    
    if (!empty($plugin_tables_filtered)) {
        echo "### {$plugin_name}\n";
        echo "**Tables**: " . count($plugin_tables_filtered) . "\n";
        foreach ($plugin_tables_filtered as $table) {
            $info = getTableInfo($table);
            echo "- `{$table}` (" . number_format($info['rows']) . " rows, " . number_format($info['size'], 2) . " KB)\n";
        }
        echo "\n";
    }
}

// Database statistics
echo "## 📈 Database Statistics\n\n";

$total_rows = 0;
$total_size = 0;

foreach ($all_tables as $table_name) {
    $info = getTableInfo($table_name);
    $total_rows += $info['rows'];
    $total_size += $info['size'];
}

echo "**Overall Statistics**:\n";
echo "- **Total Tables**: " . count($all_tables) . "\n";
echo "- **Total Rows**: " . number_format($total_rows) . "\n";
echo "- **Total Size**: " . number_format($total_size, 2) . " KB (" . number_format($total_size / 1024, 2) . " MB)\n";
echo "- **Average Rows per Table**: " . number_format($total_rows / count($all_tables)) . "\n";
echo "- **Average Size per Table**: " . number_format($total_size / count($all_tables), 2) . " KB\n\n";

// Top 10 largest tables
echo "### 🏆 Top 10 Largest Tables (by size)\n\n";
$table_sizes = [];
foreach ($all_tables as $table_name) {
    $info = getTableInfo($table_name);
    $table_sizes[$table_name] = $info['size'];
}
arsort($table_sizes);
$top_tables = array_slice($table_sizes, 0, 10, true);

echo "| Rank | Table Name | Size (KB) | Rows |\n";
echo "|------|------------|-----------|------|\n";
$rank = 1;
foreach ($top_tables as $table_name => $size) {
    $info = getTableInfo($table_name);
    echo "| {$rank} | `{$table_name}` | " . number_format($size, 2) . " | " . number_format($info['rows']) . " |\n";
    $rank++;
}
echo "\n";

// Export recommendations
echo "## 💡 Recommendations\n\n";
echo "### Backup Strategy\n";
echo "- Regular backup of all tables is recommended\n";
echo "- Consider separate backups for core WordPress tables vs plugin tables\n";
echo "- Monitor large tables for performance impact\n\n";

echo "### Performance Optimization\n";
echo "- Review tables with high row counts for indexing opportunities\n";
echo "- Consider archiving old data from large tables\n";
echo "- Monitor database size growth over time\n\n";

echo "### Maintenance\n";
echo "- Regular OPTIMIZE TABLE operations for InnoDB tables\n";
echo "- Monitor for unused tables from deactivated plugins\n";
echo "- Keep WordPress and plugins updated for security\n\n";

echo "---\n\n";
echo "*Generated by Environmental Platform Database Export Tool*\n";
echo "*Export Date: " . date('Y-m-d H:i:s') . "*\n";

// Save to file
$content = ob_get_clean();
$result = file_put_contents($output_file, $content);

if ($result !== false) {
    echo "✅ **Success**: Database structure exported to `DATABASE_TABLES_STRUCTURE.md`\n";
    echo "📁 **File Size**: " . number_format(filesize($output_file)) . " bytes\n";
    echo "📊 **Tables Exported**: " . count($all_tables) . "\n";
    echo "🔗 **File Path**: {$output_file}\n";
} else {
    echo "❌ **Error**: Failed to write to file.\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Export Complete</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f6f7f7; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: #00a32a; font-weight: bold; }
        .info { color: #0073aa; }
        .nav-links { margin: 20px 0; }
        .nav-links a { 
            display: inline-block; 
            margin-right: 15px; 
            padding: 10px 20px; 
            background: #0073aa; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Structure Export</h1>
        <div class="nav-links">
            <a href="DATABASE_TABLES_STRUCTURE.md" target="_blank">View Exported File</a>
            <a href="<?php echo admin_url(); ?>">WordPress Admin</a>
            <a href="<?php echo home_url(); ?>">Visit Site</a>
        </div>
    </div>
</body>
</html>
