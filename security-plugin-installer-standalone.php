<?php
/**
 * Standalone Security Plugin Installer
 * Environmental Platform - Phase 47
 * 
 * Downloads and installs essential security plugins
 */

echo "========================================\n";
echo "ENVIRONMENTAL PLATFORM SECURITY PLUGIN INSTALLER\n";
echo "Phase 47: Security & Backup Systems\n";
echo "========================================\n\n";

// Configuration
$plugins_dir = 'wp-content/plugins/';
$temp_dir = 'wp-content/temp/';

// Create directories if they don't exist
if (!is_dir($plugins_dir)) {
    mkdir($plugins_dir, 0755, true);
    echo "✓ Created plugins directory\n";
}

if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
    echo "✓ Created temp directory\n";
}

// Security plugins to install
$security_plugins = [
    'wordfence' => [
        'name' => 'Wordfence Security',
        'url' => 'https://downloads.wordpress.org/plugin/wordfence.latest-stable.zip',
        'description' => 'Comprehensive WordPress security plugin with firewall and malware scanner'
    ],
    'updraftplus' => [
        'name' => 'UpdraftPlus WordPress Backup Plugin',
        'url' => 'https://downloads.wordpress.org/plugin/updraftplus.latest-stable.zip',
        'description' => 'WordPress backup and restoration plugin'
    ],
    'two-factor' => [
        'name' => 'Two Factor Authentication',
        'url' => 'https://downloads.wordpress.org/plugin/two-factor.latest-stable.zip',
        'description' => 'Two-factor authentication for WordPress'
    ],
    'limit-login-attempts-reloaded' => [
        'name' => 'Limit Login Attempts Reloaded',
        'url' => 'https://downloads.wordpress.org/plugin/limit-login-attempts-reloaded.latest-stable.zip',
        'description' => 'Limit rate of login attempts and block IP addresses'
    ],
    'wp-security-audit-log' => [
        'name' => 'WP Activity Log',
        'url' => 'https://downloads.wordpress.org/plugin/wp-security-audit-log.latest-stable.zip',
        'description' => 'WordPress security audit log and monitoring'
    ]
];

echo "Installing Security Plugins...\n";
echo "========================================\n";

$installed_count = 0;
$total_plugins = count($security_plugins);

foreach ($security_plugins as $plugin_slug => $plugin_info) {
    echo "\n" . ($installed_count + 1) . ". Installing {$plugin_info['name']}...\n";
    echo "   Description: {$plugin_info['description']}\n";
    
    $plugin_dir = $plugins_dir . $plugin_slug;
    
    // Check if plugin is already installed
    if (is_dir($plugin_dir)) {
        echo "   ✓ Plugin already installed\n";
        $installed_count++;
        continue;
    }
    
    // Download plugin
    $zip_file = $temp_dir . $plugin_slug . '.zip';
    echo "   → Downloading from WordPress.org...\n";
    
    // Use curl if available, otherwise file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $plugin_info['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $plugin_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $plugin_data !== false) {
            file_put_contents($zip_file, $plugin_data);
            echo "   ✓ Downloaded successfully\n";
        } else {
            echo "   ✗ Download failed (HTTP: $http_code)\n";
            continue;
        }
    } else {
        // Fallback to file_get_contents (less reliable)
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'Environmental Platform Plugin Installer'
            ]
        ]);
        
        $plugin_data = @file_get_contents($plugin_info['url'], false, $context);
        if ($plugin_data !== false) {
            file_put_contents($zip_file, $plugin_data);
            echo "   ✓ Downloaded successfully\n";
        } else {
            echo "   ✗ Download failed\n";
            continue;
        }
    }
    
    // Extract plugin
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo($plugins_dir);
            $zip->close();
            echo "   ✓ Plugin extracted successfully\n";
            $installed_count++;
            
            // Clean up zip file
            unlink($zip_file);
        } else {
            echo "   ✗ Failed to extract plugin\n";
        }
    } else {
        echo "   ✗ ZipArchive not available - cannot extract plugin\n";
        echo "   → Manual installation required\n";
    }
}

// Clean up temp directory
if (is_dir($temp_dir) && count(scandir($temp_dir)) == 2) {
    rmdir($temp_dir);
}

echo "\n========================================\n";
echo "INSTALLATION SUMMARY\n";
echo "========================================\n";
echo "Plugins installed: $installed_count/$total_plugins\n\n";

if ($installed_count > 0) {
    echo "✓ Successfully installed $installed_count security plugins\n\n";
    
    echo "Next Steps:\n";
    echo "1. Activate plugins through WordPress admin\n";
    echo "2. Configure Wordfence firewall settings\n";
    echo "3. Set up UpdraftPlus backup schedule\n";
    echo "4. Enable two-factor authentication\n";
    echo "5. Configure login attempt limits\n";
    echo "6. Review security audit logs\n";
} else {
    echo "⚠ No plugins were installed automatically\n";
    echo "Manual installation may be required\n\n";
    
    echo "Manual Installation Instructions:\n";
    echo "1. Download plugins from WordPress.org\n";
    echo "2. Upload to wp-content/plugins/ directory\n";
    echo "3. Extract plugin files\n";
    echo "4. Activate through WordPress admin\n";
}

echo "\n========================================\n";
echo "Security Plugin Installation Complete\n";
echo "========================================\n";

// Create plugin activation checklist
$checklist_content = "# Security Plugin Activation Checklist
## Environmental Platform - Phase 47

### Installed Plugins:
";

foreach ($security_plugins as $plugin_slug => $plugin_info) {
    $status = is_dir($plugins_dir . $plugin_slug) ? "✓ Installed" : "✗ Not Installed";
    $checklist_content .= "- [ ] {$plugin_info['name']} - $status\n";
}

$checklist_content .= "
### Configuration Tasks:
- [ ] Activate all security plugins
- [ ] Configure Wordfence firewall
- [ ] Set up UpdraftPlus backup schedule  
- [ ] Enable two-factor authentication
- [ ] Configure login attempt limits
- [ ] Set up security monitoring alerts
- [ ] Test backup and restore functionality
- [ ] Review security audit logs
- [ ] Configure malware scanning schedule
- [ ] Set up email notifications for security events

### Production Deployment:
- [ ] Install SSL certificate
- [ ] Enable HTTPS redirection
- [ ] Configure cloud backup storage
- [ ] Set up monitoring alerts
- [ ] Perform security penetration testing
- [ ] Document security procedures
";

file_put_contents('phase47-security-checklist.md', $checklist_content);
echo "\n✓ Security checklist created: phase47-security-checklist.md\n";
?>
