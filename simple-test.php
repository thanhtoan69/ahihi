<?php
echo "Testing PHP and WordPress...\n";
echo "Current directory: " . getcwd() . "\n";

if (file_exists('wp-config.php')) {
    echo "WordPress found!\n";
} else {
    echo "WordPress NOT found!\n";
}

echo "Done.\n";
?>
