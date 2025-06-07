<?php
/**
 * Standalone Environmental Email Marketing Plugin Validation
 * Validates plugin files without WordPress bootstrap
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force output
ob_start();
print "Environmental Email Marketing Plugin - Standalone Validation\n";
print "============================================================\n\n";
ob_end_flush();
flush();

// Define plugin directory
$plugin_dir = __DIR__;
$results = [];

/**
 * Validate PHP syntax of a file
 */
function validate_php_syntax($file) {
    $output = shell_exec("php -l \"$file\" 2>&1");
    return strpos($output, 'No syntax errors detected') !== false;
}

/**
 * Check if file exists and is readable
 */
function check_file_exists($file, $description) {
    if (file_exists($file) && is_readable($file)) {
        echo "✓ $description: EXISTS\n";
        return true;
    } else {
        echo "✗ $description: MISSING\n";
        return false;
    }
}

/**
 * Validate PHP file syntax
 */
function validate_file_syntax($file, $description) {
    if (file_exists($file)) {
        if (validate_php_syntax($file)) {
            echo "✓ $description: SYNTAX OK\n";
            return true;
        } else {
            echo "✗ $description: SYNTAX ERROR\n";
            return false;
        }
    } else {
        echo "✗ $description: FILE NOT FOUND\n";
        return false;
    }
}

echo "1. CORE FILES VALIDATION\n";
echo "------------------------\n";

// Core plugin files
$core_files = [
    'environmental-email-marketing.php' => 'Main Plugin File',
    'uninstall.php' => 'Uninstall Script',
    'readme.txt' => 'Plugin Documentation'
];

foreach ($core_files as $file => $description) {
    check_file_exists($plugin_dir . '/' . $file, $description);
}

echo "\n2. PHP SYNTAX VALIDATION\n";
echo "------------------------\n";

// PHP files to validate
$php_files = [
    'environmental-email-marketing.php' => 'Main Plugin File',
    'admin/class-eem-admin-main.php' => 'Admin Main Class',
    'includes/class-eem-database.php' => 'Database Class',
    'includes/class-eem-campaign-manager.php' => 'Campaign Manager',
    'includes/class-eem-subscriber-manager.php' => 'Subscriber Manager',
    'includes/class-eem-email-sender.php' => 'Email Sender',
    'includes/class-eem-analytics.php' => 'Analytics Class',
    'includes/class-eem-automation.php' => 'Automation Engine',
    'includes/class-eem-environmental.php' => 'Environmental Features'
];

$syntax_passed = 0;
$syntax_total = 0;

foreach ($php_files as $file => $description) {
    $syntax_total++;
    if (validate_file_syntax($plugin_dir . '/' . $file, $description)) {
        $syntax_passed++;
    }
}

echo "\n3. DIRECTORY STRUCTURE VALIDATION\n";
echo "---------------------------------\n";

// Required directories
$directories = [
    'admin' => 'Admin Interface',
    'admin/views' => 'Admin Views',
    'includes' => 'Core Classes',
    'templates' => 'Email Templates',
    'assets' => 'Static Assets',
    'assets/css' => 'CSS Files',
    'assets/js' => 'JavaScript Files'
];

$dirs_passed = 0;
$dirs_total = 0;

foreach ($directories as $dir => $description) {
    $dirs_total++;
    if (is_dir($plugin_dir . '/' . $dir)) {
        echo "✓ $description: EXISTS\n";
        $dirs_passed++;
    } else {
        echo "✗ $description: MISSING\n";
    }
}

echo "\n4. TEMPLATE FILES VALIDATION\n";
echo "----------------------------\n";

// Template files
$template_files = [
    'templates/email-template-basic.php' => 'Basic Email Template',
    'templates/email-template-newsletter.php' => 'Newsletter Template',
    'templates/email-template-environmental.php' => 'Environmental Template'
];

$templates_passed = 0;
$templates_total = 0;

foreach ($template_files as $file => $description) {
    $templates_total++;
    if (check_file_exists($plugin_dir . '/' . $file, $description)) {
        $templates_passed++;
    }
}

echo "\n5. ADMIN VIEW FILES VALIDATION\n";
echo "------------------------------\n";

// Admin view files
$admin_views = [
    'admin/views/dashboard.php' => 'Dashboard View',
    'admin/views/campaigns.php' => 'Campaigns View',
    'admin/views/subscribers.php' => 'Subscribers View',
    'admin/views/analytics.php' => 'Analytics View',
    'admin/views/settings.php' => 'Settings View',
    'admin/views/validation.php' => 'Validation View'
];

$views_passed = 0;
$views_total = 0;

foreach ($admin_views as $file => $description) {
    $views_total++;
    if (check_file_exists($plugin_dir . '/' . $file, $description)) {
        $views_passed++;
    }
}

echo "\n6. ASSET FILES VALIDATION\n";
echo "-------------------------\n";

// Asset files
$asset_files = [
    'assets/css/admin.css' => 'Admin CSS',
    'assets/css/frontend.css' => 'Frontend CSS',
    'assets/js/admin.js' => 'Admin JavaScript',
    'assets/js/frontend.js' => 'Frontend JavaScript'
];

$assets_passed = 0;
$assets_total = 0;

foreach ($asset_files as $file => $description) {
    $assets_total++;
    if (check_file_exists($plugin_dir . '/' . $file, $description)) {
        $assets_passed++;
    }
}

echo "\n7. CONFIGURATION FILES CHECK\n";
echo "----------------------------\n";

// Check for important configuration indicators
$main_file = $plugin_dir . '/environmental-email-marketing.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    
    // Check for plugin header
    if (preg_match('/Plugin Name:.*Environmental Email Marketing/i', $content)) {
        echo "✓ Plugin Header: FOUND\n";
    } else {
        echo "✗ Plugin Header: MISSING\n";
    }
    
    // Check for activation hook
    if (strpos($content, 'register_activation_hook') !== false) {
        echo "✓ Activation Hook: FOUND\n";
    } else {
        echo "✗ Activation Hook: MISSING\n";
    }
    
    // Check for deactivation hook
    if (strpos($content, 'register_deactivation_hook') !== false) {
        echo "✓ Deactivation Hook: FOUND\n";
    } else {
        echo "✗ Deactivation Hook: MISSING\n";
    }
    
    // Check for class definition
    if (strpos($content, 'class Environmental_Email_Marketing') !== false) {
        echo "✓ Main Class: FOUND\n";
    } else {
        echo "✗ Main Class: MISSING\n";
    }
}

echo "\n8. VALIDATION SUMMARY\n";
echo "====================\n";

$total_checks = $syntax_total + $dirs_total + $templates_total + $views_total + $assets_total;
$total_passed = $syntax_passed + $dirs_passed + $templates_passed + $views_passed + $assets_passed;

echo "PHP Syntax: $syntax_passed/$syntax_total passed\n";
echo "Directories: $dirs_passed/$dirs_total found\n";
echo "Templates: $templates_passed/$templates_total found\n";
echo "Admin Views: $views_passed/$views_total found\n";
echo "Assets: $assets_passed/$assets_total found\n";
echo "\nOVERALL: $total_passed/$total_checks checks passed\n";

$percentage = ($total_passed / $total_checks) * 100;
echo "Success Rate: " . number_format($percentage, 1) . "%\n";

if ($percentage >= 90) {
    echo "\n🎉 PLUGIN READY FOR DEPLOYMENT!\n";
} elseif ($percentage >= 75) {
    echo "\n⚠️  PLUGIN MOSTLY READY - Minor issues to fix\n";
} else {
    echo "\n❌ PLUGIN NEEDS ATTENTION - Major issues found\n";
}

echo "\nValidation completed at: " . date('Y-m-d H:i:s') . "\n";
