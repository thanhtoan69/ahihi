<?php
/**
 * Environmental Mobile API Comprehensive Endpoint Test
 * 
 * This script tests all API endpoints to ensure they're working correctly
 */

// WordPress setup
require_once 'wp-config.php';

echo "<h1>Environmental Mobile API Comprehensive Endpoint Test</h1>";

// Base API URL
$api_base = rest_url('environmental-mobile-api/v1/');
echo "<p><strong>API Base URL:</strong> " . $api_base . "</p>";

// Test endpoints
$endpoints_to_test = array(
    // Health and Info
    'health' => array(
        'method' => 'GET',
        'description' => 'Health Check',
        'auth_required' => false
    ),
    'info' => array(
        'method' => 'GET',
        'description' => 'API Information',
        'auth_required' => false
    ),
    'docs' => array(
        'method' => 'GET',
        'description' => 'API Documentation',
        'auth_required' => false
    ),
    
    // Authentication Endpoints
    'auth/register' => array(
        'method' => 'POST',
        'description' => 'User Registration',
        'auth_required' => false,
        'test_data' => array(
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'password' => 'testpass123',
            'first_name' => 'Test',
            'last_name' => 'User'
        )
    ),
    'auth/login' => array(
        'method' => 'POST',
        'description' => 'User Login',
        'auth_required' => false,
        'test_data' => array(
            'username' => 'admin',
            'password' => 'password'
        )
    ),
    'auth/verify' => array(
        'method' => 'POST',
        'description' => 'Token Verification',
        'auth_required' => true
    ),
    'auth/refresh' => array(
        'method' => 'POST',
        'description' => 'Token Refresh',
        'auth_required' => false
    ),
    'auth/logout' => array(
        'method' => 'POST',
        'description' => 'User Logout',
        'auth_required' => true
    ),
    
    // User Endpoints
    'users/profile' => array(
        'method' => 'GET',
        'description' => 'Get User Profile',
        'auth_required' => true
    ),
    'users/preferences' => array(
        'method' => 'GET',
        'description' => 'Get User Preferences',
        'auth_required' => true
    ),
    'users/stats' => array(
        'method' => 'GET',
        'description' => 'Get User Statistics',
        'auth_required' => true
    ),
    
    // Content Endpoints
    'content/posts' => array(
        'method' => 'GET',
        'description' => 'Get Posts',
        'auth_required' => false
    ),
    'content/petitions' => array(
        'method' => 'GET',
        'description' => 'Get Petitions',
        'auth_required' => false
    ),
    'content/events' => array(
        'method' => 'GET',
        'description' => 'Get Events',
        'auth_required' => false
    ),
    'content/items' => array(
        'method' => 'GET',
        'description' => 'Get Items',
        'auth_required' => false
    ),
    'content/search' => array(
        'method' => 'GET',
        'description' => 'Search Content',
        'auth_required' => false,
        'query_params' => array('q' => 'environment')
    ),
    
    // Environmental Data Endpoints
    'environmental/stats' => array(
        'method' => 'GET',
        'description' => 'Environmental Statistics',
        'auth_required' => false
    ),
    'environmental/impact' => array(
        'method' => 'GET',
        'description' => 'User Impact Data',
        'auth_required' => true
    ),
    'environmental/achievements' => array(
        'method' => 'GET',
        'description' => 'User Achievements',
        'auth_required' => true
    ),
    'environmental/leaderboard' => array(
        'method' => 'GET',
        'description' => 'Environmental Leaderboard',
        'auth_required' => false
    ),
    'environmental/carbon-calculator' => array(
        'method' => 'POST',
        'description' => 'Carbon Calculator',
        'auth_required' => false,
        'test_data' => array(
            'transport' => array(
                'car_km' => 100,
                'flight_km' => 0
            ),
            'energy' => array(
                'electricity_kwh' => 200
            )
        )
    )
);

echo "<h2>Endpoint Tests</h2>";

$total_tests = 0;
$passed_tests = 0;
$jwt_token = null;

foreach ($endpoints_to_test as $endpoint => $config) {
    $total_tests++;
    echo "<h3>Testing: " . $config['description'] . " (" . $config['method'] . " /" . $endpoint . ")</h3>";
    
    $url = $api_base . $endpoint;
    
    // Add query parameters if specified
    if (isset($config['query_params'])) {
        $url .= '?' . http_build_query($config['query_params']);
    }
    
    // Prepare request arguments
    $args = array(
        'method' => $config['method'],
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30
    );
    
    // Add authentication if required and available
    if ($config['auth_required'] && $jwt_token) {
        $args['headers']['Authorization'] = 'Bearer ' . $jwt_token;
    }
    
    // Add request body for POST requests
    if ($config['method'] === 'POST' && isset($config['test_data'])) {
        $args['body'] = json_encode($config['test_data']);
    }
    
    // Make the request
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        echo "❌ Request failed: " . $response->get_error_message() . "<br>";
        echo "&nbsp;&nbsp;🔗 URL: " . $url . "<br>";
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);
        
        echo "&nbsp;&nbsp;📊 Status Code: " . $status_code . "<br>";
        echo "&nbsp;&nbsp;🔗 URL: " . $url . "<br>";
        
        // Check if response is successful
        if ($status_code >= 200 && $status_code < 300) {
            echo "✅ Request successful<br>";
            $passed_tests++;
            
            // Store JWT token from login for subsequent requests
            if ($endpoint === 'auth/login' && isset($response_data['data']['token'])) {
                $jwt_token = $response_data['data']['token'];
                echo "&nbsp;&nbsp;🔐 JWT Token obtained for authenticated requests<br>";
            }
            
            // Show response preview
            if ($response_data) {
                echo "&nbsp;&nbsp;📄 Response preview: ";
                if (isset($response_data['success'])) {
                    echo "Success: " . ($response_data['success'] ? 'true' : 'false');
                }
                if (isset($response_data['message'])) {
                    echo ", Message: " . $response_data['message'];
                }
                if (isset($response_data['data']) && is_array($response_data['data'])) {
                    echo ", Data keys: " . implode(', ', array_keys($response_data['data']));
                }
                echo "<br>";
            }
        } else {
            echo "⚠️ Request returned status: " . $status_code . "<br>";
            
            // Some endpoints might return expected errors (like auth required)
            if ($config['auth_required'] && !$jwt_token && $status_code === 401) {
                echo "&nbsp;&nbsp;ℹ️ Authentication required (expected)<br>";
                $passed_tests++; // Count as passed since it's expected behavior
            } elseif ($status_code === 404) {
                echo "❌ Endpoint not found - implementation may be missing<br>";
            } else {
                echo "&nbsp;&nbsp;📄 Error response: " . substr($body, 0, 200) . "...<br>";
            }
        }
    }
    
    echo "<br>";
}

// Test summary
echo "<h2>Test Summary</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<strong>Total Tests:</strong> " . $total_tests . "<br>";
echo "<strong>Passed Tests:</strong> " . $passed_tests . "<br>";
echo "<strong>Failed Tests:</strong> " . ($total_tests - $passed_tests) . "<br>";
echo "<strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 1) . "%<br>";
echo "</div>";

if ($passed_tests === $total_tests) {
    echo "<h3 style='color: green;'>🎉 All tests passed! The API is working correctly.</h3>";
} elseif ($passed_tests >= ($total_tests * 0.8)) {
    echo "<h3 style='color: orange;'>⚠️ Most tests passed. Some endpoints may need attention.</h3>";
} else {
    echo "<h3 style='color: red;'>❌ Many tests failed. The API needs debugging.</h3>";
}

echo "<h3>Additional Information</h3>";
echo "<ul>";
echo "<li><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</li>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>Plugin Version:</strong> " . (defined('ENVIRONMENTAL_MOBILE_API_VERSION') ? ENVIRONMENTAL_MOBILE_API_VERSION : 'Not defined') . "</li>";
echo "<li><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Review any failed endpoints and their error messages</li>";
echo "<li>Check the WordPress error log for detailed error information</li>";
echo "<li>Test authentication flow by registering a new user and logging in</li>";
echo "<li>Verify database tables contain the expected data</li>";
echo "<li>Test the admin interface for API management</li>";
echo "</ol>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

h3 {
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}

ul, ol {
    padding-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style>
