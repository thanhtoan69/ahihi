<?php
// Standalone Database Export Script - No WordPress Dependencies
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration - Update these with your actual database details
$db_host = 'localhost';
$db_name = 'environmental_platform';  // Your database name
$db_user = 'root';       // Your database username
$db_pass = '';           // Your database password

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables in database.\n";
    
    // Start building markdown content
    $markdown_content = "# Database Structure Documentation\n\n";
    $markdown_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
    $markdown_content .= "Database: `$db_name`\n\n";
    $markdown_content .= "## Table of Contents\n\n";
    
    // Create table of contents
    foreach ($tables as $table) {
        $markdown_content .= "- [Table: $table](#table-" . str_replace('_', '-', $table) . ")\n";
    }
    $markdown_content .= "\n---\n\n";
    
    // Process each table
    foreach ($tables as $table) {
        echo "Processing table: $table\n";
        
        $markdown_content .= "## Table: $table\n\n";
        
        // Get table row count
        try {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $row_count = $count_stmt->fetchColumn();
            $markdown_content .= "**Row Count:** $row_count\n\n";
        } catch (Exception $e) {
            $markdown_content .= "**Row Count:** Unable to determine\n\n";
        }
        
        // Get CREATE TABLE statement
        try {
            $create_stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $create_result = $create_stmt->fetch(PDO::FETCH_ASSOC);
            $create_sql = $create_result['Create Table'];
            
            $markdown_content .= "### CREATE TABLE Statement\n\n";
            $markdown_content .= "```sql\n";
            $markdown_content .= $create_sql . ";\n";
            $markdown_content .= "```\n\n";
        } catch (Exception $e) {
            $markdown_content .= "**Error getting CREATE TABLE statement:** " . $e->getMessage() . "\n\n";
        }
        
        // Get table structure
        try {
            $desc_stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $desc_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $markdown_content .= "### Table Structure\n\n";
            $markdown_content .= "| Column | Type | Null | Key | Default | Extra |\n";
            $markdown_content .= "|--------|------|------|-----|---------|-------|\n";
            
            foreach ($columns as $column) {
                $markdown_content .= "| " . $column['Field'] . " | " . $column['Type'] . " | " . $column['Null'] . " | " . $column['Key'] . " | " . $column['Default'] . " | " . $column['Extra'] . " |\n";
            }
            $markdown_content .= "\n";
        } catch (Exception $e) {
            $markdown_content .= "**Error getting table structure:** " . $e->getMessage() . "\n\n";
        }
        
        $markdown_content .= "---\n\n";
    }
    
    // Add summary
    $markdown_content .= "## Summary\n\n";
    $markdown_content .= "- **Total Tables:** " . count($tables) . "\n";
    $markdown_content .= "- **Database:** `$db_name`\n";
    $markdown_content .= "- **Export Date:** " . date('Y-m-d H:i:s') . "\n\n";
    
    // Write to file
    $output_file = 'DATABASE_STRUCTURE_COMPLETE.md';
    $bytes_written = file_put_contents($output_file, $markdown_content);
    
    if ($bytes_written !== false) {
        echo "SUCCESS! Database structure exported to: $output_file\n";
        echo "File size: " . number_format($bytes_written) . " bytes\n";
        echo "Absolute path: " . realpath($output_file) . "\n";
    } else {
        echo "ERROR: Failed to write markdown file!\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
