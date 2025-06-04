<?php
/**
 * Phase 34 Status Check - Event Management System
 * Check existing events and prepare for WordPress integration
 */

$host = 'localhost';
$dbname = 'environmental_platform';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PHASE 34: EVENT MANAGEMENT SYSTEM STATUS CHECK ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Check events table structure
    echo "1. EVENTS TABLE STRUCTURE:\n";
    $stmt = $pdo->query("DESCRIBE events");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']}\n";
    }
    
    // Check events count
    echo "\n2. EVENTS DATA:\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM events');
    $result = $stmt->fetch();
    echo "  Total events: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT event_id, title, event_type, start_date, status FROM events ORDER BY start_date DESC LIMIT 5');
        echo "\n  Recent events:\n";
        while ($row = $stmt->fetch()) {
            echo "  - ID: {$row['event_id']}, Title: {$row['title']}, Type: {$row['event_type']}, Date: {$row['start_date']}, Status: {$row['status']}\n";
        }
    }
    
    // Check event registrations
    echo "\n3. EVENT REGISTRATIONS:\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM event_registrations');
    $result = $stmt->fetch();
    echo "  Total registrations: " . $result['count'] . "\n";
    
    // Check event types distribution
    echo "\n4. EVENT TYPES DISTRIBUTION:\n";
    $stmt = $pdo->query('SELECT event_type, COUNT(*) as count FROM events GROUP BY event_type ORDER BY count DESC');
    while ($row = $stmt->fetch()) {
        echo "  - {$row['event_type']}: {$row['count']} events\n";
    }
    
    // Check event status distribution
    echo "\n5. EVENT STATUS DISTRIBUTION:\n";
    $stmt = $pdo->query('SELECT status, COUNT(*) as count FROM events GROUP BY status ORDER BY count DESC');
    while ($row = $stmt->fetch()) {
        echo "  - {$row['status']}: {$row['count']} events\n";
    }
    
    // Check WordPress readiness
    echo "\n6. WORDPRESS INTEGRATION READINESS:\n";
    
    // Check if WordPress is installed
    $wp_check = file_exists('wp-config.php') ? '✅' : '❌';
    echo "  WordPress Installation: $wp_check\n";
    
    // Check if environmental platform core plugin exists
    $plugin_check = file_exists('wp-content/plugins/environmental-platform-core/environmental-platform-core.php') ? '✅' : '❌';
    echo "  Core Plugin: $plugin_check\n";
    
    // Check if forum plugin exists (from Phase 33)
    $forum_check = file_exists('wp-content/plugins/environmental-platform-forum/environmental-platform-forum.php') ? '✅' : '❌';
    echo "  Forum Plugin (Phase 33): $forum_check\n";
    
    echo "\n=== PHASE 34 PREPARATION COMPLETE ===\n";
    echo "Ready to implement WordPress Event Management System\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
