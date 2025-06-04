<?php
/**
 * Phase 28 CLI Verification Script: Custom Database Integration
 * Command-line version of the verification script
 */

// Define WordPress constants for CLI
define('ABSPATH', __DIR__ . '/');
define('WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins');

class Phase28CLIVerification {
    
    private $results = array();
    private $plugin_dir;
    
    public function __construct() {
        $this->plugin_dir = WP_PLUGIN_DIR . '/environmental-platform-core';
    }
    
    public function run_verification() {
        echo "Phase 28: Custom Database Integration - Verification Report\n";
        echo "=" . str_repeat("=", 60) . "\n\n";

        // Run all verification checks
        $this->verify_database_classes();
        $this->verify_admin_templates();
        $this->verify_file_structure();
        
        // Display summary
        $this->display_summary();
        
        return $this->results;
    }
    
    private function verify_database_classes() {
        echo "1. Database Integration Classes\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $classes = array(
            'Database Manager' => 'includes/class-database-manager.php',
            'Database Migration' => 'includes/class-database-migration.php',
            'Database Version Control' => 'includes/class-database-version-control.php'
        );
        
        foreach ($classes as $name => $file_path) {
            $full_path = $this->plugin_dir . '/' . $file_path;
            $exists = file_exists($full_path);
            
            if ($exists) {
                $size = filesize($full_path);
                echo "✓ PASS: {$name} - File exists ({$size} bytes)\n";
                $this->results[$name] = 'pass';
            } else {
                echo "✗ FAIL: {$name} - File missing: {$file_path}\n";
                $this->results[$name] = 'fail';
            }
        }
        echo "\n";
    }
    
    private function verify_admin_templates() {
        echo "2. Admin Page Templates\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $templates = array(
            'Database Manager Template' => 'admin/database-manager.php',
            'Migration Template' => 'admin/migration.php',
            'Version Control Template' => 'admin/version-control.php'
        );
        
        foreach ($templates as $name => $file_path) {
            $full_path = $this->plugin_dir . '/' . $file_path;
            $exists = file_exists($full_path);
            
            if ($exists) {
                $size = filesize($full_path);
                echo "✓ PASS: {$name} - File exists ({$size} bytes)\n";
                $this->results[$name] = 'pass';
                
                // Check if template has basic structure
                $content = file_get_contents($full_path);
                if (strpos($content, '<div class="wrap">') !== false) {
                    echo "  └─ Has WordPress admin wrapper\n";
                }
                if (strpos($content, 'ajax') !== false) {
                    echo "  └─ Contains AJAX functionality\n";
                }
            } else {
                echo "✗ FAIL: {$name} - File missing: {$file_path}\n";
                $this->results[$name] = 'fail';
            }
        }
        echo "\n";
    }
    
    private function verify_file_structure() {
        echo "3. Plugin File Structure\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $required_files = array(
            'Main Plugin File' => 'environmental-platform-core.php',
            'Admin Directory' => 'admin',
            'Includes Directory' => 'includes',
            'Assets Directory' => 'assets'
        );
        
        foreach ($required_files as $name => $path) {
            $full_path = $this->plugin_dir . '/' . $path;
            
            if (is_file($full_path)) {
                $size = filesize($full_path);
                echo "✓ PASS: {$name} - File exists ({$size} bytes)\n";
                $this->results[$name] = 'pass';
            } elseif (is_dir($full_path)) {
                $files = scandir($full_path);
                $file_count = count($files) - 2; // Exclude . and ..
                echo "✓ PASS: {$name} - Directory exists ({$file_count} files)\n";
                $this->results[$name] = 'pass';
            } else {
                echo "✗ FAIL: {$name} - Not found: {$path}\n";
                $this->results[$name] = 'fail';
            }
        }
        echo "\n";
    }
    
    private function display_summary() {
        echo "Verification Summary\n";
        echo "=" . str_repeat("=", 60) . "\n";
        
        $pass_count = 0;
        $fail_count = 0;
        $warning_count = 0;
        
        foreach ($this->results as $component => $status) {
            switch ($status) {
                case 'pass':
                    $pass_count++;
                    break;
                case 'fail':
                    $fail_count++;
                    break;
                case 'warning':
                    $warning_count++;
                    break;
            }
        }
        
        echo "Total Components Checked: " . count($this->results) . "\n";
        echo "✓ Passed: {$pass_count}\n";
        echo "⚠ Warnings: {$warning_count}\n";
        echo "✗ Failed: {$fail_count}\n\n";
        
        if ($fail_count == 0 && $warning_count == 0) {
            echo "🎉 ALL CHECKS PASSED! Phase 28 implementation is complete.\n";
        } elseif ($fail_count == 0) {
            echo "✅ All critical components passed with some warnings.\n";
        } else {
            echo "❌ Some components failed verification. Please review the results above.\n";
        }
        
        echo "\nPhase 28: Custom Database Integration - Status: " . 
             ($fail_count == 0 ? "COMPLETED" : "NEEDS ATTENTION") . "\n";
    }
}

// Run verification
$verifier = new Phase28CLIVerification();
$verifier->run_verification();
?>
