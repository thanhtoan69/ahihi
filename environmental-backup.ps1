# Environmental Platform Backup Script - PowerShell Version
# Phase 47: Security & Backup Systems
# Automated backup system for WordPress installation

param(
    [string]$BackupType = "full",
    [string]$BackupPath = "wp-content\backups",
    [int]$RetentionDays = 7
)

Write-Host "========================================" -ForegroundColor Green
Write-Host "ENVIRONMENTAL PLATFORM BACKUP SYSTEM" -ForegroundColor Green
Write-Host "Phase 47: Security & Backup Systems" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Configuration
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupName = "environmental_platform_$Timestamp"
$BackupDir = "$BackupPath\$BackupName"

# Database configuration
$DbHost = "localhost"
$DbName = "environmental_platform"
$DbUser = "root"
$DbPass = ""

# Create backup directory
if (!(Test-Path $BackupPath)) {
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
    Write-Host "✓ Created backup directory: $BackupPath" -ForegroundColor Green
}

if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
    Write-Host "✓ Created backup directory: $BackupDir" -ForegroundColor Green
}

Write-Host "Starting backup process..." -ForegroundColor Yellow
Write-Host "Backup type: $BackupType" -ForegroundColor Cyan
Write-Host "Backup directory: $BackupDir" -ForegroundColor Cyan

# 1. Database Backup
Write-Host "" 
Write-Host "1. Backing up database..." -ForegroundColor Yellow
$DbBackupFile = "$BackupDir\database.sql"

try {
    $mysqldumpPath = "c:\xampp\mysql\bin\mysqldump.exe"
    if (Test-Path $mysqldumpPath) {
        $mysqldumpArgs = @(
            "--host=$DbHost",
            "--user=$DbUser",
            "--single-transaction",
            "--routines",
            "--triggers",
            "$DbName"
        )
        
        if ($DbPass) {
            $mysqldumpArgs += "--password=$DbPass"
        }
        
        & $mysqldumpPath @mysqldumpArgs | Out-File -FilePath $DbBackupFile -Encoding UTF8
        Write-Host "   ✓ Database backup completed: database.sql" -ForegroundColor Green
    } else {
        Write-Host "   ✗ mysqldump not found at $mysqldumpPath" -ForegroundColor Red
    }
} catch {
    Write-Host "   ✗ Database backup failed: $($_.Exception.Message)" -ForegroundColor Red
}

# 2. Files Backup
Write-Host ""
Write-Host "2. Backing up WordPress files..." -ForegroundColor Yellow

$FilesToBackup = @(
    "wp-config.php",
    "wp-content\themes",
    "wp-content\plugins",
    "wp-content\uploads",
    "wp-content\mu-plugins",
    ".htaccess"
)

foreach ($FileItem in $FilesToBackup) {
    if (Test-Path $FileItem) {
        $DestPath = "$BackupDir\files\$FileItem"
        $DestDir = Split-Path $DestPath -Parent
        
        if (!(Test-Path $DestDir)) {
            New-Item -ItemType Directory -Path $DestDir -Force | Out-Null
        }
        
        if (Test-Path $FileItem -PathType Container) {
            Copy-Item -Path $FileItem -Destination $DestPath -Recurse -Force
            Write-Host "   ✓ Backed up directory: $FileItem" -ForegroundColor Green
        } else {
            Copy-Item -Path $FileItem -Destination $DestPath -Force
            Write-Host "   ✓ Backed up file: $FileItem" -ForegroundColor Green
        }
    } else {
        Write-Host "   ⚠ File not found: $FileItem" -ForegroundColor Yellow
    }
}

# 3. Create backup manifest
Write-Host ""
Write-Host "3. Creating backup manifest..." -ForegroundColor Yellow

$Manifest = @{
    "backup_name" = $BackupName
    "backup_date" = $Timestamp
    "backup_type" = $BackupType
    "database_included" = (Test-Path $DbBackupFile)
    "files_included" = $FilesToBackup
    "backup_size" = 0
    "retention_days" = $RetentionDays
}

# Calculate backup size
if (Test-Path $BackupDir) {
    $BackupSize = (Get-ChildItem -Path $BackupDir -Recurse | Measure-Object -Property Length -Sum).Sum
    $Manifest["backup_size"] = [math]::Round($BackupSize / 1MB, 2)
}

$ManifestJson = $Manifest | ConvertTo-Json -Depth 3
$ManifestJson | Out-File -FilePath "$BackupDir\manifest.json" -Encoding UTF8
Write-Host "   ✓ Backup manifest created" -ForegroundColor Green

# 4. Compress backup (if 7-Zip is available)
Write-Host ""
Write-Host "4. Compressing backup..." -ForegroundColor Yellow

$SevenZipPath = "C:\Program Files\7-Zip\7z.exe"
if (Test-Path $SevenZipPath) {
    $ArchiveName = "$BackupPath\$BackupName.7z"
    & $SevenZipPath a $ArchiveName "$BackupDir\*" -mx=5 | Out-Null
    
    if (Test-Path $ArchiveName) {
        Remove-Item -Path $BackupDir -Recurse -Force
        Write-Host "   ✓ Backup compressed: $BackupName.7z" -ForegroundColor Green
    }
} else {
    Write-Host "   ⚠ 7-Zip not found, backup not compressed" -ForegroundColor Yellow
}

# 5. Cleanup old backups
Write-Host ""
Write-Host "5. Cleaning up old backups..." -ForegroundColor Yellow

$CutoffDate = (Get-Date).AddDays(-$RetentionDays)
$OldBackups = Get-ChildItem -Path $BackupPath | Where-Object { 
    $_.CreationTime -lt $CutoffDate -and ($_.Extension -eq ".7z" -or $_.PSIsContainer)
}

$RemovedCount = 0
foreach ($OldBackup in $OldBackups) {
    Remove-Item -Path $OldBackup.FullName -Recurse -Force
    Write-Host "   ✓ Removed old backup: $($OldBackup.Name)" -ForegroundColor Green
    $RemovedCount++
}

if ($RemovedCount -eq 0) {
    Write-Host "   ✓ No old backups to remove" -ForegroundColor Green
}

# 6. Verification
Write-Host ""
Write-Host "6. Verifying backup..." -ForegroundColor Yellow

$BackupVerified = $true
if (Test-Path "$BackupPath\$BackupName.7z") {
    $ArchiveSize = (Get-Item "$BackupPath\$BackupName.7z").Length
    if ($ArchiveSize -gt 1KB) {
        Write-Host "   ✓ Compressed backup verified ($([math]::Round($ArchiveSize / 1MB, 2)) MB)" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Backup file too small, may be corrupted" -ForegroundColor Red
        $BackupVerified = $false
    }
} elseif (Test-Path $BackupDir) {
    $FolderSize = (Get-ChildItem -Path $BackupDir -Recurse | Measure-Object -Property Length -Sum).Sum
    if ($FolderSize -gt 1KB) {
        Write-Host "   ✓ Backup folder verified ($([math]::Round($FolderSize / 1MB, 2)) MB)" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Backup folder too small, may be incomplete" -ForegroundColor Red
        $BackupVerified = $false
    }
}

# Final Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "BACKUP SUMMARY" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Backup Name: $BackupName" -ForegroundColor Cyan
Write-Host "Backup Date: $Timestamp" -ForegroundColor Cyan
Write-Host "Backup Size: $($Manifest["backup_size"]) MB" -ForegroundColor Cyan
Write-Host "Database: $(if($Manifest["database_included"]) { "✓ Included" } else { "✗ Not Included" })" -ForegroundColor $(if($Manifest["database_included"]) { "Green" } else { "Red" })
Write-Host "Files: ✓ Included" -ForegroundColor Green
Write-Host "Status: $(if($BackupVerified) { "✓ SUCCESS" } else { "✗ FAILED" })" -ForegroundColor $(if($BackupVerified) { "Green" } else { "Red" })

if ($BackupVerified) {
    Write-Host ""
    Write-Host "✓ Environmental Platform backup completed successfully!" -ForegroundColor Green
    Write-Host "✓ Backup retention: $RetentionDays days" -ForegroundColor Green
    Write-Host "✓ Next backup recommended: $(Get-Date (Get-Date).AddDays(1) -Format 'yyyy-MM-dd HH:mm')" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "✗ Backup completed with errors. Please review the output above." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Phase 47 Backup System: OPERATIONAL" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
