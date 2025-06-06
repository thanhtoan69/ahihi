#!/bin/bash
# Environmental Platform Backup Script
# Phase 47: Automated Backup System
# Comprehensive backup solution for WordPress Environmental Platform

# Configuration
BACKUP_DIR="/backups/environmental_platform"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="environmental_platform"
DB_USER="root"
DB_PASS=""
WP_DIR="C:/xampp/htdocs/moitruong"
LOG_FILE="/backups/environmental_platform/backup.log"

# Create backup directory
mkdir -p $BACKUP_DIR

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a $LOG_FILE
}

log_message "========================================="
log_message "Starting Environmental Platform Backup"
log_message "========================================="

# Check if MySQL is available
if ! command -v mysqldump &> /dev/null; then
    log_message "ERROR: mysqldump not found. Please install MySQL client tools."
    exit 1
fi

# Database backup
log_message "Starting database backup..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/database_$DATE.sql

if [ $? -eq 0 ]; then
    log_message "✓ Database backup completed successfully"
    # Compress database backup
    gzip $BACKUP_DIR/database_$DATE.sql
    log_message "✓ Database backup compressed"
else
    log_message "❌ Database backup failed"
    exit 1
fi

# Files backup
log_message "Starting files backup..."

# Use tar for file backup (cross-platform)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz \
    --exclude="$WP_DIR/wp-content/cache" \
    --exclude="$WP_DIR/wp-content/uploads/cache" \
    --exclude="$WP_DIR/wp-content/ep-backups" \
    --exclude="$WP_DIR/wp-content/debug.log" \
    -C "$WP_DIR" .

if [ $? -eq 0 ]; then
    log_message "✓ Files backup completed successfully"
else
    log_message "❌ Files backup failed"
    exit 1
fi

# Create backup manifest
log_message "Creating backup manifest..."
cat > $BACKUP_DIR/manifest_$DATE.txt << EOF
Environmental Platform Backup Manifest
======================================
Backup Date: $(date)
Backup ID: $DATE

Database Backup:
- File: database_$DATE.sql.gz
- Size: $(ls -lh $BACKUP_DIR/database_$DATE.sql.gz | awk '{print $5}')

Files Backup:
- File: files_$DATE.tar.gz
- Size: $(ls -lh $BACKUP_DIR/files_$DATE.tar.gz | awk '{print $5}')

WordPress Info:
- Directory: $WP_DIR
- Database: $DB_NAME

Backup completed at: $(date)
EOF

log_message "✓ Backup manifest created"

# Cleanup old backups (keep last 7 days)
log_message "Cleaning up old backups..."

# Remove database backups older than 7 days
find $BACKUP_DIR -name "database_*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "database_*.sql" -mtime +7 -delete

# Remove file backups older than 7 days
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +7 -delete

# Remove old manifests
find $BACKUP_DIR -name "manifest_*.txt" -mtime +7 -delete

log_message "✓ Old backups cleaned up"

# Calculate total backup size
TOTAL_SIZE=$(du -sh $BACKUP_DIR | cut -f1)
log_message "Total backup directory size: $TOTAL_SIZE"

# Create verification checksums
log_message "Creating verification checksums..."
cd $BACKUP_DIR
sha256sum database_$DATE.sql.gz > database_$DATE.sha256
sha256sum files_$DATE.tar.gz > files_$DATE.sha256
log_message "✓ Checksums created"

log_message "========================================="
log_message "Backup completed successfully!"
log_message "Database: database_$DATE.sql.gz"
log_message "Files: files_$DATE.tar.gz"
log_message "Manifest: manifest_$DATE.txt"
log_message "Total backup size: $TOTAL_SIZE"
log_message "========================================="

# Optional: Send email notification (requires mail command)
if command -v mail &> /dev/null; then
    ADMIN_EMAIL="admin@environmental-platform.local"
    echo "Environmental Platform backup completed successfully at $(date). Total size: $TOTAL_SIZE" | \
    mail -s "Environmental Platform Backup Complete - $DATE" $ADMIN_EMAIL
    log_message "✓ Email notification sent"
fi

exit 0
