<?php
/**
 * Test truy cập WordPress Admin
 * Kiểm tra các vấn đề có thể xảy ra khi truy cập wp-admin
 */

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Truy Cập WordPress Admin</h1>";
echo "<p>Thời gian kiểm tra: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Kiểm tra file WordPress cơ bản
echo "<h2>1. Kiểm tra Files WordPress</h2>";
$required_files = [
    'wp-config.php' => 'Cấu hình WordPress',
    'wp-load.php' => 'File tải WordPress',
    'wp-admin/index.php' => 'Trang admin chính',
    'wp-includes/functions.php' => 'Functions WordPress'
];

foreach ($required_files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p>✅ $desc ($file) - OK</p>";
    } else {
        echo "<p>❌ $desc ($file) - THIẾU FILE</p>";
    }
}

// Test 2: Kiểm tra kết nối database
echo "<h2>2. Kiểm tra Database</h2>";
try {
    if (file_exists('wp-config.php')) {
        $config = file_get_contents('wp-config.php');
        
        // Lấy thông tin database từ wp-config.php
        preg_match("/define\(\s*'DB_NAME',\s*'([^']+)'\s*\)/", $config, $db_name);
        preg_match("/define\(\s*'DB_USER',\s*'([^']+)'\s*\)/", $config, $db_user);
        preg_match("/define\(\s*'DB_PASSWORD',\s*'([^']*)'[^)]*\)/", $config, $db_pass);
        preg_match("/define\(\s*'DB_HOST',\s*'([^']+)'\s*\)/", $config, $db_host);
        
        if ($db_name && $db_user && $db_host) {
            $dsn = "mysql:host={$db_host[1]};dbname={$db_name[1]}";
            $password = isset($db_pass[1]) ? $db_pass[1] : '';
            
            $pdo = new PDO($dsn, $db_user[1], $password);
            echo "<p>✅ Kết nối database thành công</p>";
            echo "<p>Database: {$db_name[1]}</p>";
            echo "<p>Host: {$db_host[1]}</p>";
            
            // Kiểm tra bảng WordPress
            $stmt = $pdo->query("SHOW TABLES LIKE 'wp_%'");
            $tables = $stmt->fetchAll();
            echo "<p>Số bảng WordPress: " . count($tables) . "</p>";
            
        } else {
            echo "<p>❌ Không thể đọc thông tin database từ wp-config.php</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Lỗi kết nối database: " . $e->getMessage() . "</p>";
}

// Test 3: Kiểm tra tải WordPress
echo "<h2>3. Kiểm tra Tải WordPress</h2>";
try {
    // Tạm thời disable advanced-cache
    if (file_exists('wp-content/advanced-cache.php')) {
        $cache_content = file_get_contents('wp-content/advanced-cache.php');
        if (!empty($cache_content)) {
            rename('wp-content/advanced-cache.php', 'wp-content/advanced-cache-temp.php');
            file_put_contents('wp-content/advanced-cache.php', '<?php // Tạm thời disable cache ?>');
        }
    }
    
    ob_start();
    require_once 'wp-load.php';
    $output = ob_get_clean();
    
    echo "<p>✅ WordPress tải thành công</p>";
    
    // Kiểm tra các function quan trọng
    if (function_exists('is_admin')) {
        echo "<p>✅ Function is_admin() có sẵn</p>";
    }
    
    if (function_exists('current_user_can')) {
        echo "<p>✅ Function current_user_can() có sẵn</p>";
    }
    
    // Restore cache file nếu có
    if (file_exists('wp-content/advanced-cache-temp.php')) {
        unlink('wp-content/advanced-cache.php');
        rename('wp-content/advanced-cache-temp.php', 'wp-content/advanced-cache.php');
    }
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi khi tải WordPress: " . $e->getMessage() . "</p>";
}

// Test 4: Kiểm tra quyền truy cập file
echo "<h2>4. Kiểm tra Quyền File</h2>";
$check_permissions = [
    '.' => 'Thư mục gốc',
    'wp-content' => 'Thư mục wp-content',
    'wp-admin' => 'Thư mục wp-admin'
];

foreach ($check_permissions as $path => $desc) {
    if (is_readable($path)) {
        echo "<p>✅ $desc - Có thể đọc</p>";
    } else {
        echo "<p>❌ $desc - Không thể đọc</p>";
    }
}

// Test 5: Direct test wp-admin
echo "<h2>5. Test Trực Tiếp</h2>";
echo "<p><strong>Thử các link sau:</strong></p>";
echo "<p><a href='wp-admin/index.php' target='_blank'>wp-admin/index.php</a></p>";
echo "<p><a href='wp-login.php' target='_blank'>wp-login.php</a></p>";
echo "<p><a href='index.php' target='_blank'>Trang chủ (index.php)</a></p>";

echo "<hr>";
echo "<p><strong>Hoàn thành kiểm tra!</strong></p>";
?>
