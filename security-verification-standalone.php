<?php
/**
 * Standalone Security Verification Script for Environmental Platform
 * Phase 47: Security & Backup Systems
 * 
 * This script performs comprehensive security verification without WordPress dependencies
 */

echo "========================================\n";
echo "ENVIRONMENTAL PLATFORM SECURITY VERIFICATION\n";
echo "Phase 47: Security & Backup Systems\n";
echo "========================================\n\n";

$security_score = 0;
$total_checks = 0;
$results = [];

// Check 1: .htaccess Security Headers
echo "1. Checking .htaccess Security Configuration...\n";
$total_checks++;
if (file_exists('.htaccess')) {
    $htaccess_content = file_get_contents('.htaccess');
    $security_headers = [
        'X-Frame-Options' => 'Header always set X-Frame-Options',
        'X-XSS-Protection' => 'Header always set X-XSS-Protection',
        'X-Content-Type-Options' => 'Header always set X-Content-Type-Options',
        'Content-Security-Policy' => 'Header always set Content-Security-Policy',
        'Strict-Transport-Security' => 'Header always set Strict-Transport-Security'
    ];
    
    $headers_found = 0;
    foreach ($security_headers as $header => $pattern) {
        if (strpos($htaccess_content, $pattern) !== false) {
            $headers_found++;
            echo "   ✓ $header header configured\n";
        } else {
            echo "   ✗ $header header missing\n";
        }
    }
    
    if ($headers_found >= 4) {
        $security_score++;
        $results[] = "✓ Security headers: {$headers_found}/5 configured";
    } else {
        $results[] = "✗ Security headers: {$headers_found}/5 configured";
    }
} else {
    echo "   ✗ .htaccess file not found\n";
    $results[] = "✗ .htaccess file missing";
}

// Check 2: WordPress Security Configuration
echo "\n2. Checking WordPress Security Configuration...\n";
$total_checks++;
if (file_exists('wp-config.php')) {
    $wp_config = file_get_contents('wp-config.php');
    $security_constants = [
        'DISALLOW_FILE_EDIT' => 'define.*DISALLOW_FILE_EDIT.*true',
        'WP_AUTO_UPDATE_CORE' => 'define.*WP_AUTO_UPDATE_CORE.*true',
        'FORCE_SSL_ADMIN' => 'define.*FORCE_SSL_ADMIN.*true'
    ];
    
    $constants_found = 0;
    foreach ($security_constants as $constant => $pattern) {
        if (preg_match('/' . $pattern . '/i', $wp_config)) {
            $constants_found++;
            echo "   ✓ $constant configured\n";
        } else {
            echo "   ✗ $constant not configured\n";
        }
    }
    
    if ($constants_found >= 2) {
        $security_score++;
        $results[] = "✓ WordPress security constants: {$constants_found}/3 configured";
    } else {
        $results[] = "✗ WordPress security constants: {$constants_found}/3 configured";
    }
} else {
    echo "   ✗ wp-config.php not found\n";
    $results[] = "✗ wp-config.php missing";
}

// Check 3: Must-Use Security Plugins
echo "\n3. Checking Must-Use Security Plugins...\n";
$total_checks++;
$mu_plugins = [
    'security-monitor.php' => 'Security event monitoring',
    'security-maintenance.php' => 'Security maintenance automation',
    'backup-config.php' => 'Backup system configuration'
];

$plugins_found = 0;
foreach ($mu_plugins as $plugin => $description) {
    $plugin_path = "wp-content/mu-plugins/$plugin";
    if (file_exists($plugin_path)) {
        $plugins_found++;
        echo "   ✓ $description ($plugin)\n";
    } else {
        echo "   ✗ $description ($plugin) missing\n";
    }
}

if ($plugins_found >= 2) {
    $security_score++;
    $results[] = "✓ Must-use security plugins: {$plugins_found}/3 active";
} else {
    $results[] = "✗ Must-use security plugins: {$plugins_found}/3 active";
}

// Check 4: File Permissions
echo "\n4. Checking File Permissions...\n";
$total_checks++;
$critical_files = [
    'wp-config.php' => 0644,
    '.htaccess' => 0644,
    'wp-content' => 0755
];

$permissions_ok = 0;
foreach ($critical_files as $file => $expected) {
    if (file_exists($file)) {
        $perms = fileperms($file) & 0777;
        if ($perms <= $expected) {
            $permissions_ok++;
            echo "   ✓ $file permissions: " . decoct($perms) . "\n";
        } else {
            echo "   ✗ $file permissions too open: " . decoct($perms) . " (should be " . decoct($expected) . ")\n";
        }
    } else {
        echo "   ✗ $file not found\n";
    }
}

if ($permissions_ok >= 2) {
    $security_score++;
    $results[] = "✓ File permissions: {$permissions_ok}/3 correct";
} else {
    $results[] = "✗ File permissions: {$permissions_ok}/3 correct";
}

// Check 5: Backup System
echo "\n5. Checking Backup System...\n";
$total_checks++;
$backup_files = [
    'environmental-backup.sh' => 'Backup shell script',
    'wp-content/mu-plugins/backup-config.php' => 'Backup configuration plugin'
];

$backup_components = 0;
foreach ($backup_files as $file => $description) {
    if (file_exists($file)) {
        $backup_components++;
        echo "   ✓ $description\n";
    } else {
        echo "   ✗ $description missing\n";
    }
}

// Check for backup directory
if (is_dir('wp-content/backups')) {
    $backup_components++;
    echo "   ✓ Backup directory exists\n";
} else {
    echo "   ✗ Backup directory missing\n";
}

if ($backup_components >= 2) {
    $security_score++;
    $results[] = "✓ Backup system: {$backup_components}/3 components configured";
} else {
    $results[] = "✗ Backup system: {$backup_components}/3 components configured";
}

// Check 6: Database Security
echo "\n6. Checking Database Security Setup...\n";
$total_checks++;
$db_security_score = 0;

// Check if security tables exist (simplified check)
if (file_exists('wp-content/mu-plugins/security-monitor.php')) {
    $security_monitor = file_get_contents('wp-content/mu-plugins/security-monitor.php');
    if (strpos($security_monitor, 'wp_security_logs') !== false) {
        $db_security_score++;
        echo "   ✓ Security logging table configured\n";
    } else {
        echo "   ✗ Security logging table not configured\n";
    }
}

// Check database connection security
if (file_exists('wp-config.php')) {
    $wp_config = file_get_contents('wp-config.php');
    if (strpos($wp_config, 'DB_HOST') !== false && strpos($wp_config, 'DB_USER') !== false) {
        $db_security_score++;
        echo "   ✓ Database connection configured\n";
    } else {
        echo "   ✗ Database connection not properly configured\n";
    }
}

if ($db_security_score >= 1) {
    $security_score++;
    $results[] = "✓ Database security: {$db_security_score}/2 components configured";
} else {
    $results[] = "✗ Database security: {$db_security_score}/2 components configured";
}

// Check 7: Plugin Installation Framework
echo "\n7. Checking Security Plugin Installation Framework...\n";
$total_checks++;
if (file_exists('phase47-security-plugin-installer.php')) {
    echo "   ✓ Security plugin installer available\n";
    $security_score++;
    $results[] = "✓ Plugin installation framework ready";
} else {
    echo "   ✗ Security plugin installer missing\n";
    $results[] = "✗ Plugin installation framework missing";
}

// Calculate final score
$percentage = round(($security_score / $total_checks) * 100);

echo "\n========================================\n";
echo "SECURITY VERIFICATION SUMMARY\n";
echo "========================================\n";
echo "Security Score: $security_score/$total_checks ($percentage%)\n\n";

echo "Detailed Results:\n";
foreach ($results as $result) {
    echo "$result\n";
}

echo "\n========================================\n";
if ($percentage >= 80) {
    echo "✓ SECURITY STATUS: EXCELLENT\n";
    echo "Your Environmental Platform has strong security measures in place.\n";
} elseif ($percentage >= 60) {
    echo "⚠ SECURITY STATUS: GOOD\n";
    echo "Your Environmental Platform has adequate security, but improvements are recommended.\n";
} else {
    echo "✗ SECURITY STATUS: NEEDS IMPROVEMENT\n";
    echo "Your Environmental Platform requires immediate security enhancements.\n";
}

echo "\nNext Steps:\n";
if ($percentage < 100) {
    echo "1. Install security plugins (Wordfence, UpdraftPlus)\n";
    echo "2. Configure SSL/HTTPS encryption\n";
    echo "3. Set up automated backup scheduling\n";
    echo "4. Configure firewall rules\n";
    echo "5. Enable two-factor authentication\n";
} else {
    echo "1. Deploy to production environment\n";
    echo "2. Configure SSL certificates\n";
    echo "3. Set up monitoring alerts\n";
    echo "4. Perform penetration testing\n";
}

echo "\n========================================\n";
echo "Phase 47 Security Verification Complete\n";
echo "========================================\n";
?>
