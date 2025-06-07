<?php
echo "Starting validation...\n";

// Test basic functionality
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current Directory: " . __DIR__ . "\n";

// Test file exists
$main_file = __DIR__ . '/environmental-email-marketing.php';
if (file_exists($main_file)) {
    echo "Main plugin file found!\n";
} else {
    echo "Main plugin file NOT found!\n";
}

// Test simple shell exec
$test_output = shell_exec('echo "Shell exec test"');
if ($test_output) {
    echo "Shell exec working: " . trim($test_output) . "\n";
} else {
    echo "Shell exec not working\n";
}

echo "Test completed.\n";
