<?php
/**
 * Phase 47: Security Implementation Verification
 * Test and verify all security components are working correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phase 47: Security Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: rgba(255,255,255,0.95); color: #333; padding: 30px; margin: 20px 0; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .success { border-left: 5px solid #4CAF50; }
        .warning { border-left: 5px solid #ff9800; }
        .info { border-left: 5px solid #2196F3; }
        .error { border-left: 5px solid #f44336; }
        h1 { font-size: 2.5em; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); text-align: center; }
        h2 { color: #2c3e50; margin-top: 0; }
        .check { color: #4CAF50; font-weight: bold; }
        .warning-text { color: #ff9800; font-weight: bold; }
        .error-text { color: #f44336; font-weight: bold; }
        .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .test-item { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 PHASE 47: SECURITY VERIFICATION</h1>
        
        <div class='card info'>
            <h2>🛡️ Security Implementation Verification</h2>
            <p><strong>Verification Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Testing all security components and configurations...</p>
        </div>

<?php

$verification_results = [];
$total_tests = 0;
$passed_tests = 0;

// Test 1: Check .htaccess Security Headers
echo "<div class='card success'>";
echo "<h2>📋 Test 1: Security Headers Verification</h2>";

$htaccess_path = __DIR__ . '/.htaccess';
if (file_exists($htaccess_path)) {
    $htaccess_content = file_get_contents($htaccess_path);
    
    $security_headers = [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => 'default-src',
        'Strict-Transport-Security' => 'max-age=31536000'
    ];
    
    foreach ($security_headers as $header => $expected) {
        $total_tests++;
        if (strpos($htaccess_content, $header) !== false) {
            echo "<p class='check'>✓ {$header} header configured</p>";
            $passed_tests++;
        } else {
            echo "<p class='error-text'>❌ {$header} header missing</p>";
        }
    }
    
    // Check firewall rules
    $firewall_rules = [
        'SQL injection protection' => 'union.*select',
        'XSS protection' => '<script.*?</script>',
        'File injection protection' => '[a-zA-Z0-9_]=http://',
        'Bot blocking' => 'libwww-perl|wget|python'
    ];
    
    foreach ($firewall_rules as $rule_name => $pattern) {
        $total_tests++;
        if (strpos($htaccess_content, $pattern) !== false) {
            echo "<p class='check'>✓ {$rule_name} enabled</p>";
            $passed_tests++;
        } else {
            echo "<p class='error-text'>❌ {$rule_name} missing</p>";
        }
    }
    
} else {
    echo "<p class='error-text'>❌ .htaccess file not found</p>";
}

echo "</div>";

// Test 2: Check WordPress Security Configuration
echo "<div class='card warning'>";
echo "<h2>🔐 Test 2: WordPress Security Configuration</h2>";

$wp_config_path = __DIR__ . '/wp-config.php';
if (file_exists($wp_config_path)) {
    $wp_config_content = file_get_contents($wp_config_path);
    
    $security_constants = [
        'DISALLOW_FILE_EDIT' => 'true',
        'WP_AUTO_UPDATE_CORE' => 'true'
    ];
    
    foreach ($security_constants as $constant => $expected) {
        $total_tests++;
        if (strpos($wp_config_content, $constant) !== false) {
            echo "<p class='check'>✓ {$constant} configured</p>";
            $passed_tests++;
        } else {
            echo "<p class='error-text'>❌ {$constant} missing</p>";
        }
    }
} else {
    echo "<p class='warning-text'>⚠ wp-config.php not found (normal for development)</p>";
}

echo "</div>";

// Test 3: Check Security Monitoring Files
echo "<div class='card info'>";
echo "<h2>👁️ Test 3: Security Monitoring System</h2>";

$mu_plugins_dir = __DIR__ . '/wp-content/mu-plugins';
$security_files = [
    'security-monitor.php' => 'Security event monitoring',
    'security-maintenance.php' => 'Automated maintenance',
    'backup-config.php' => 'Backup configuration'
];

foreach ($security_files as $file => $description) {
    $total_tests++;
    $file_path = $mu_plugins_dir . '/' . $file;
    if (file_exists($file_path)) {
        echo "<p class='check'>✓ {$description} ({$file})</p>";
        $passed_tests++;
    } else {
        echo "<p class='error-text'>❌ {$description} missing ({$file})</p>";
    }
}

echo "</div>";

// Test 4: Check Backup System
echo "<div class='card success'>";
echo "<h2>💾 Test 4: Backup System Verification</h2>";

$backup_script_path = __DIR__ . '/environmental-backup.sh';
$total_tests++;
if (file_exists($backup_script_path)) {
    echo "<p class='check'>✓ Backup script created (environmental-backup.sh)</p>";
    $passed_tests++;
    
    // Check script permissions
    $permissions = fileperms($backup_script_path);
    if ($permissions & 0x40) { // Check if executable
        echo "<p class='check'>✓ Backup script is executable</p>";
    } else {
        echo "<p class='warning-text'>⚠ Backup script permissions may need adjustment</p>";
    }
} else {
    echo "<p class='error-text'>❌ Backup script missing</p>";
}

echo "</div>";

// Test 5: Security File Protection
echo "<div class='card warning'>";
echo "<h2>🔒 Test 5: File Protection Verification</h2>";

// Test if sensitive files are protected
$protected_patterns = [
    '*.log files' => '\.log',
    '*.sql files' => '\.sql', 
    'wp-config.php' => 'wp-config\.php',
    '.htaccess file' => '\.htaccess'
];

$total_tests += count($protected_patterns);

foreach ($protected_patterns as $file_type => $pattern) {
    if (strpos($htaccess_content ?? '', $pattern) !== false) {
        echo "<p class='check'>✓ {$file_type} protected</p>";
        $passed_tests++;
    } else {
        echo "<p class='warning-text'>⚠ {$file_type} protection not verified</p>";
    }
}

echo "</div>";

// Test 6: Directory Structure Security
echo "<div class='card info'>";
echo "<h2>📁 Test 6: Directory Security</h2>";

$directories_to_check = [
    '/wp-content/mu-plugins' => 'MU-Plugins directory',
    '/wp-content' => 'WP-Content directory'
];

foreach ($directories_to_check as $dir => $description) {
    $total_tests++;
    $full_path = __DIR__ . $dir;
    if (is_dir($full_path)) {
        echo "<p class='check'>✓ {$description} exists</p>";
        $passed_tests++;
        
        // Check if directory browsing is disabled
        $index_file = $full_path . '/index.php';
        if (!file_exists($index_file)) {
            // Create index.php to prevent directory browsing
            file_put_contents($index_file, "<?php\n// Silence is golden.\n");
            echo "<p class='check'>✓ Created index.php for {$description}</p>";
        }
    } else {
        echo "<p class='error-text'>❌ {$description} missing</p>";
    }
}

echo "</div>";

// Summary
echo "<div class='card " . ($passed_tests >= ($total_tests * 0.8) ? 'success' : 'warning') . "'>";
echo "<h2>📊 Security Verification Summary</h2>";

$success_rate = round(($passed_tests / $total_tests) * 100, 1);

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<div style='font-size: 48px; font-weight: bold; margin: 20px 0;'>{$passed_tests}/{$total_tests}</div>";
echo "<h3>Security Tests Passed ({$success_rate}%)</h3>";
echo "</div>";

if ($success_rate >= 90) {
    echo "<div style='background: rgba(76, 175, 80, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>🎉 Excellent Security Implementation!</h3>";
    echo "<p>Your Environmental Platform has enterprise-level security protection.</p>";
    echo "</div>";
} elseif ($success_rate >= 80) {
    echo "<div style='background: rgba(255, 193, 7, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>✅ Good Security Implementation</h3>";
    echo "<p>Most security features are properly configured. Review any missing components.</p>";
    echo "</div>";
} else {
    echo "<div style='background: rgba(244, 67, 54, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>⚠️ Security Needs Attention</h3>";
    echo "<p>Several security components need to be properly configured.</p>";
    echo "</div>";
}

echo "<div class='test-grid'>";

$security_status = [
    'Security Headers' => strpos($htaccess_content ?? '', 'X-Frame-Options') !== false,
    'Firewall Rules' => strpos($htaccess_content ?? '', 'union.*select') !== false,
    'WordPress Hardening' => file_exists($wp_config_path) && strpos(file_get_contents($wp_config_path), 'DISALLOW_FILE_EDIT') !== false,
    'Monitoring System' => file_exists($mu_plugins_dir . '/security-monitor.php'),
    'Backup System' => file_exists($backup_script_path),
    'File Protection' => strpos($htaccess_content ?? '', 'wp-config\.php') !== false
];

foreach ($security_status as $component => $status) {
    $icon = $status ? '✓' : '❌';
    $class = $status ? 'check' : 'error-text';
    echo "<div class='test-item'>";
    echo "<h3><span class='{$class}'>{$icon}</span> {$component}</h3>";
    echo "<p>" . ($status ? 'Configured' : 'Needs Configuration') . "</p>";
    echo "</div>";
}

echo "</div>";

echo "<h3>🔐 Active Security Features:</h3>";
echo "<ul>";
if (isset($htaccess_content) && strpos($htaccess_content, 'X-Frame-Options') !== false) {
    echo "<li>✓ Clickjacking Protection (X-Frame-Options)</li>";
}
if (isset($htaccess_content) && strpos($htaccess_content, 'X-XSS-Protection') !== false) {
    echo "<li>✓ XSS Attack Prevention</li>";
}
if (isset($htaccess_content) && strpos($htaccess_content, 'union.*select') !== false) {
    echo "<li>✓ SQL Injection Protection</li>";
}
if (file_exists($mu_plugins_dir . '/security-monitor.php')) {
    echo "<li>✓ Real-time Security Monitoring</li>";
}
if (file_exists($backup_script_path)) {
    echo "<li>✓ Automated Backup System</li>";
}
if (isset($htaccess_content) && strpos($htaccess_content, 'libwww-perl') !== false) {
    echo "<li>✓ Bot Attack Blocking</li>";
}
echo "</ul>";

echo "</div>";

// Create verification report
$verification_report = "# PHASE 47: SECURITY VERIFICATION REPORT

## Verification Summary
**Date:** " . date('Y-m-d H:i:s') . "
**Tests Passed:** {$passed_tests}/{$total_tests} ({$success_rate}%)
**Security Level:** " . ($success_rate >= 90 ? 'EXCELLENT' : ($success_rate >= 80 ? 'GOOD' : 'NEEDS ATTENTION')) . "

## Security Components Status

### Security Headers
- X-Frame-Options: " . (strpos($htaccess_content ?? '', 'X-Frame-Options') !== false ? 'CONFIGURED' : 'MISSING') . "
- X-XSS-Protection: " . (strpos($htaccess_content ?? '', 'X-XSS-Protection') !== false ? 'CONFIGURED' : 'MISSING') . "
- X-Content-Type-Options: " . (strpos($htaccess_content ?? '', 'X-Content-Type-Options') !== false ? 'CONFIGURED' : 'MISSING') . "
- Content-Security-Policy: " . (strpos($htaccess_content ?? '', 'Content-Security-Policy') !== false ? 'CONFIGURED' : 'MISSING') . "

### Firewall Protection
- SQL Injection Protection: " . (strpos($htaccess_content ?? '', 'union.*select') !== false ? 'ACTIVE' : 'INACTIVE') . "
- XSS Protection: " . (strpos($htaccess_content ?? '', '<script.*?</script>') !== false ? 'ACTIVE' : 'INACTIVE') . "
- Bot Blocking: " . (strpos($htaccess_content ?? '', 'libwww-perl') !== false ? 'ACTIVE' : 'INACTIVE') . "

### Monitoring System
- Security Monitor: " . (file_exists($mu_plugins_dir . '/security-monitor.php') ? 'INSTALLED' : 'MISSING') . "
- Maintenance Automation: " . (file_exists($mu_plugins_dir . '/security-maintenance.php') ? 'INSTALLED' : 'MISSING') . "

### Backup System
- Backup Script: " . (file_exists($backup_script_path) ? 'CREATED' : 'MISSING') . "
- Backup Configuration: " . (file_exists($mu_plugins_dir . '/backup-config.php') ? 'INSTALLED' : 'MISSING') . "

## Recommendations for Production
1. Install SSL Certificate for HTTPS
2. Configure external security monitoring
3. Set up cloud backup storage
4. Install security plugins (Wordfence, UpdraftPlus)
5. Regular security audits and penetration testing
6. Admin user security training

---

**VERIFICATION STATUS:** " . ($success_rate >= 80 ? '✅ PASSED' : '⚠️ NEEDS ATTENTION') . "
**PRODUCTION READINESS:** " . round($success_rate) . "%

*Generated by Environmental Platform Phase 47 Security Verification*
";

file_put_contents(__DIR__ . '/PHASE_47_SECURITY_VERIFICATION_REPORT.md', $verification_report);

echo "<div class='card success'>";
echo "<h2>📄 Verification Complete</h2>";
echo "<p class='check'>✓ Security verification report generated</p>";
echo "<p class='check'>✓ Phase 47 security verification completed</p>";
echo "<p><strong>Overall Security Level:</strong> " . ($success_rate >= 90 ? 'EXCELLENT' : ($success_rate >= 80 ? 'GOOD' : 'NEEDS ATTENTION')) . " ({$success_rate}%)</p>";
echo "</div>";

?>

    </div>
</body>
</html>
