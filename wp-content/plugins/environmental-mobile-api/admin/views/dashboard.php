<?php
/**
 * Admin Dashboard View
 * 
 * @package Environmental_Mobile_API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="environmental-mobile-api-dashboard">
        <div class="dashboard-header">
            <h2><?php _e('Environmental Platform Mobile API', 'environmental-mobile-api'); ?></h2>
            <p><?php _e('Comprehensive REST API for mobile app integration with JWT authentication, rate limiting, and real-time webhooks.', 'environmental-mobile-api'); ?></p>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php _e('API Status', 'environmental-mobile-api'); ?></h3>
                <div class="stat-value">
                    <span class="status-indicator active"></span>
                    <?php _e('Active', 'environmental-mobile-api'); ?>
                </div>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Total Endpoints', 'environmental-mobile-api'); ?></h3>
                <div class="stat-value">50+</div>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Authentication', 'environmental-mobile-api'); ?></h3>
                <div class="stat-value">JWT</div>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Version', 'environmental-mobile-api'); ?></h3>
                <div class="stat-value"><?php echo ENVIRONMENTAL_MOBILE_API_VERSION; ?></div>
            </div>
        </div>
        
        <div class="dashboard-sections">
            <div class="section-card">
                <h3><?php _e('Quick Links', 'environmental-mobile-api'); ?></h3>
                <ul>
                    <li><a href="<?php echo rest_url('environmental-mobile-api/v1/'); ?>" target="_blank"><?php _e('API Root', 'environmental-mobile-api'); ?></a></li>
                    <li><a href="<?php echo rest_url('environmental-mobile-api/v1/docs'); ?>" target="_blank"><?php _e('API Documentation', 'environmental-mobile-api'); ?></a></li>
                    <li><a href="<?php echo rest_url('environmental-mobile-api/v1/health'); ?>" target="_blank"><?php _e('Health Check', 'environmental-mobile-api'); ?></a></li>
                </ul>
            </div>
            
            <div class="section-card">
                <h3><?php _e('API Endpoints', 'environmental-mobile-api'); ?></h3>
                <ul>
                    <li><strong><?php _e('Authentication:', 'environmental-mobile-api'); ?></strong> /auth/*</li>
                    <li><strong><?php _e('Users:', 'environmental-mobile-api'); ?></strong> /users/*</li>
                    <li><strong><?php _e('Content:', 'environmental-mobile-api'); ?></strong> /content/*</li>
                    <li><strong><?php _e('Environmental Data:', 'environmental-mobile-api'); ?></strong> /environmental/*</li>
                </ul>
            </div>
            
            <div class="section-card">
                <h3><?php _e('Features', 'environmental-mobile-api'); ?></h3>
                <ul>
                    <li>✅ <?php _e('JWT Authentication', 'environmental-mobile-api'); ?></li>
                    <li>✅ <?php _e('Rate Limiting', 'environmental-mobile-api'); ?></li>
                    <li>✅ <?php _e('Multi-tier Caching', 'environmental-mobile-api'); ?></li>
                    <li>✅ <?php _e('Webhook System', 'environmental-mobile-api'); ?></li>
                    <li>✅ <?php _e('Security Framework', 'environmental-mobile-api'); ?></li>
                    <li>✅ <?php _e('API Documentation', 'environmental-mobile-api'); ?></li>
                </ul>
            </div>
            
            <div class="section-card">
                <h3><?php _e('API Testing', 'environmental-mobile-api'); ?></h3>
                <p><?php _e('Test your API endpoints directly from the admin interface.', 'environmental-mobile-api'); ?></p>
                <button type="button" class="button button-primary" id="test-api-btn">
                    <?php _e('Open API Tester', 'environmental-mobile-api'); ?>
                </button>
            </div>
        </div>
        
        <div class="api-tester" id="api-tester" style="display: none;">
            <h3><?php _e('API Endpoint Tester', 'environmental-mobile-api'); ?></h3>
            <form id="api-test-form">
                <table class="form-table">
                    <tr>
                        <th><label for="test-method"><?php _e('Method', 'environmental-mobile-api'); ?></label></th>
                        <td>
                            <select id="test-method" name="method">
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test-endpoint"><?php _e('Endpoint', 'environmental-mobile-api'); ?></label></th>
                        <td>
                            <select id="test-endpoint" name="endpoint">
                                <option value="health"><?php _e('Health Check', 'environmental-mobile-api'); ?></option>
                                <option value="auth/login"><?php _e('Authentication - Login', 'environmental-mobile-api'); ?></option>
                                <option value="auth/register"><?php _e('Authentication - Register', 'environmental-mobile-api'); ?></option>
                                <option value="users/profile"><?php _e('Users - Profile', 'environmental-mobile-api'); ?></option>
                                <option value="content/posts"><?php _e('Content - Posts', 'environmental-mobile-api'); ?></option>
                                <option value="environmental/stats"><?php _e('Environmental - Stats', 'environmental-mobile-api'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test-headers"><?php _e('Headers (JSON)', 'environmental-mobile-api'); ?></label></th>
                        <td>
                            <textarea id="test-headers" name="headers" rows="3" cols="50" placeholder='{"Authorization": "Bearer YOUR_TOKEN"}'></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="test-body"><?php _e('Request Body (JSON)', 'environmental-mobile-api'); ?></label></th>
                        <td>
                            <textarea id="test-body" name="body" rows="5" cols="50" placeholder='{}'></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Send Request', 'environmental-mobile-api'); ?></button>
                    <button type="button" class="button" id="clear-test-btn"><?php _e('Clear', 'environmental-mobile-api'); ?></button>
                </p>
            </form>
            
            <div id="api-test-results" style="display: none;">
                <h4><?php _e('Response', 'environmental-mobile-api'); ?></h4>
                <div class="response-status"></div>
                <pre class="response-body"></pre>
            </div>
        </div>
        
        <div class="dashboard-footer">
            <p><?php _e('For detailed API documentation and integration guides, visit the', 'environmental-mobile-api'); ?> 
               <a href="<?php echo rest_url('environmental-mobile-api/v1/docs'); ?>" target="_blank"><?php _e('API Documentation', 'environmental-mobile-api'); ?></a>
            </p>
        </div>
    </div>
</div>

<style>
.environmental-mobile-api-dashboard {
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-header {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-indicator.active {
    background-color: #00a32a;
}

.dashboard-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.section-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.section-card h3 {
    margin-top: 0;
    color: #23282d;
}

.section-card ul {
    margin: 0;
    padding-left: 20px;
}

.section-card li {
    margin-bottom: 5px;
}

.api-tester {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
}

#api-test-results {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.response-status {
    margin-bottom: 10px;
    font-weight: bold;
}

.response-body {
    background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    white-space: pre-wrap;
    max-height: 400px;
    overflow: auto;
}

.dashboard-footer {
    text-align: center;
    padding: 20px;
    background: #f1f1f1;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle API tester
    $('#test-api-btn').on('click', function() {
        $('#api-tester').slideToggle();
    });
    
    // Clear test form
    $('#clear-test-btn').on('click', function() {
        $('#api-test-form')[0].reset();
        $('#api-test-results').hide();
    });
    
    // Handle API test form submission
    $('#api-test-form').on('submit', function(e) {
        e.preventDefault();
        
        var method = $('#test-method').val();
        var endpoint = $('#test-endpoint').val();
        var headers = $('#test-headers').val();
        var body = $('#test-body').val();
        
        // Parse headers
        var requestHeaders = {
            'Content-Type': 'application/json'
        };
        
        if (headers.trim()) {
            try {
                var customHeaders = JSON.parse(headers);
                $.extend(requestHeaders, customHeaders);
            } catch (e) {
                alert('Invalid JSON in headers');
                return;
            }
        }
        
        // Parse body
        var requestBody = null;
        if (body.trim() && (method === 'POST' || method === 'PUT')) {
            try {
                requestBody = JSON.parse(body);
            } catch (e) {
                alert('Invalid JSON in request body');
                return;
            }
        }
        
        // Make API request
        var apiUrl = '<?php echo rest_url('environmental-mobile-api/v1/'); ?>' + endpoint;
        
        $.ajax({
            url: apiUrl,
            method: method,
            headers: requestHeaders,
            data: requestBody ? JSON.stringify(requestBody) : null,
            success: function(data, textStatus, xhr) {
                showApiResponse(xhr.status, data);
            },
            error: function(xhr) {
                var response = xhr.responseJSON || xhr.responseText || 'No response';
                showApiResponse(xhr.status, response);
            }
        });
    });
    
    function showApiResponse(status, data) {
        var statusClass = status >= 200 && status < 300 ? 'success' : 'error';
        var statusColor = statusClass === 'success' ? '#00a32a' : '#d63638';
        
        $('.response-status').html('Status: <span style="color: ' + statusColor + '">' + status + '</span>');
        $('.response-body').text(JSON.stringify(data, null, 2));
        $('#api-test-results').show();
    }
});
</script>
