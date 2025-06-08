# PowerShell script để tạo file markdown database structure
# Chạy lệnh: .\create-database-markdown.ps1

Write-Host "🗄️ Creating Database Structure Markdown File..." -ForegroundColor Green

# Chạy PHP script và lưu output
$phpPath = "c:\xampp\php\php.exe"
$scriptPath = "c:\xampp\htdocs\moitruong\get-database-structure.php"
$outputFile = "c:\xampp\htdocs\moitruong\DATABASE_TABLES_STRUCTURE.md"

# Tạo PHP script tạm thời
$phpScript = @'
<?php
require_once dirname(__FILE__) . '/wp-config.php';
global $wpdb;

echo "# Environmental Platform - Database Tables Structure\n\n";
echo "**Generated Date**: " . date('Y-m-d H:i:s') . "\n";
echo "**Database Name**: " . DB_NAME . "\n\n";

$tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

if ($tables) {
    echo "## Total Tables: " . count($tables) . "\n\n";
    
    echo "| No | Table Name | Rows |\n";
    echo "|----|-----------:|-----:|\n";
    
    $i = 1;
    foreach ($tables as $table) {
        $table_name = $table[0];
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
        echo "| {$i} | `{$table_name}` | " . number_format($row_count) . " |\n";
        $i++;
    }
    
    echo "\n---\n\n";
    echo "## CREATE TABLE Statements\n\n";
    echo "```sql\n";
    echo "-- Environmental Platform Database Structure\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $table_name = $table[0];
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        if ($create_table && isset($create_table[1])) {
            echo "-- Table: {$table_name}\n";
            echo $create_table[1] . ";\n\n";
        }
    }
    
    echo "```\n";
}
?>
'@

# Lưu PHP script tạm thời
$tempPhpFile = "c:\xampp\htdocs\moitruong\temp-db-export.php"
$phpScript | Out-File -FilePath $tempPhpFile -Encoding UTF8

# Chạy PHP script và lưu output
try {
    Write-Host "📋 Running PHP script to extract database structure..." -ForegroundColor Yellow
    $output = & $phpPath $tempPhpFile
    
    # Lưu vào file markdown
    $output | Out-File -FilePath $outputFile -Encoding UTF8
    
    if (Test-Path $outputFile) {
        $fileSize = (Get-Item $outputFile).Length
        Write-Host "✅ SUCCESS: Database structure exported!" -ForegroundColor Green
        Write-Host "📄 File: DATABASE_TABLES_STRUCTURE.md" -ForegroundColor Cyan
        Write-Host "📏 Size: $([math]::Round($fileSize/1KB, 2)) KB" -ForegroundColor Cyan
        Write-Host "🔗 Path: $outputFile" -ForegroundColor Cyan
    } else {
        Write-Host "❌ ERROR: File was not created!" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
} finally {
    # Xóa file PHP tạm thời
    if (Test-Path $tempPhpFile) {
        Remove-Item $tempPhpFile
    }
}

Write-Host "`n🎉 Database export process completed!" -ForegroundColor Green
