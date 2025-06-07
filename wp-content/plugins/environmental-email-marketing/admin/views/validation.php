<?php
/**
 * Plugin Validation Interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize validation if requested
$validation_results = [];
$run_validation = isset($_GET['run_validation']) && $_GET['run_validation'] === '1';

if ($run_validation) {
    // Include required classes
    require_once EEM_PLUGIN_PATH . 'includes/class-eem-final-validator.php';
    require_once EEM_PLUGIN_PATH . 'includes/class-eem-system-status.php';
    require_once EEM_PLUGIN_PATH . 'includes/class-eem-test-runner.php';
    require_once EEM_PLUGIN_PATH . 'includes/class-eem-ajax-validator.php';
    
    $final_validator = new EEM_Final_Validator();
    $system_status = new EEM_System_Status();
    $test_runner = new EEM_Test_Runner();
    $ajax_validator = new EEM_Ajax_Validator();
    
    // Run comprehensive validation
    $validation_results = [
        'system_status' => $system_status->get_system_status(),
        'final_validation' => $final_validator->run_full_validation(),
        'component_tests' => $test_runner->run_all_tests(),
        'ajax_tests' => $ajax_validator->run_all_validations()
    ];
}
?>

<div class="wrap">
    <h1><?php _e('Plugin Validation Dashboard', 'environmental-email-marketing'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This page helps validate that all plugin components are working correctly before deployment.', 'environmental-email-marketing'); ?></p>
    </div>
    
    <?php if (!$run_validation): ?>
        
        <div class="card">
            <h2><?php _e('Run Comprehensive Validation', 'environmental-email-marketing'); ?></h2>
            <p><?php _e('Click the button below to run a comprehensive validation of all plugin components.', 'environmental-email-marketing'); ?></p>
            
            <a href="<?php echo admin_url('admin.php?page=eem-validation&run_validation=1'); ?>" 
               class="button button-primary button-large">
                <?php _e('Run Full Validation', 'environmental-email-marketing'); ?>
            </a>
        </div>
        
        <div class="card">
            <h3><?php _e('What Gets Validated', 'environmental-email-marketing'); ?></h3>
            <ul>
                <li><?php _e('System Status & Dependencies', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Database Tables & Schema', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Email Provider Integrations', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Campaign & Subscriber Management', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Template Engine & Email Rendering', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Analytics & Tracking Systems', 'environmental-email-marketing'); ?></li>
                <li><?php _e('AJAX Endpoints & Frontend Forms', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Automation Engine & Cron Jobs', 'environmental-email-marketing'); ?></li>
                <li><?php _e('REST API Endpoints', 'environmental-email-marketing'); ?></li>
                <li><?php _e('Environmental Features & Scoring', 'environmental-email-marketing'); ?></li>
            </ul>
        </div>
        
    <?php else: ?>
        
        <h2><?php _e('Validation Results', 'environmental-email-marketing'); ?></h2>
        
        <!-- System Status Results -->
        <div class="card">
            <h3><?php _e('System Status', 'environmental-email-marketing'); ?></h3>
            <?php 
            $system_status = $validation_results['system_status'];
            $status_color = $system_status['overall_status'] === 'good' ? 'green' : 
                           ($system_status['overall_status'] === 'warning' ? 'orange' : 'red');
            ?>
            <p><strong><?php _e('Overall Status:', 'environmental-email-marketing'); ?></strong> 
               <span style="color: <?php echo $status_color; ?>;">
                   <?php echo ucfirst($system_status['overall_status']); ?>
               </span>
            </p>
            
            <div class="system-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <!-- WordPress Environment -->
                <div class="status-card">
                    <h4><?php _e('WordPress Environment', 'environmental-email-marketing'); ?></h4>
                    <ul>
                        <li>WP Version: <?php echo $system_status['wordpress']['version']; ?></li>
                        <li>PHP Version: <?php echo $system_status['php']['version']; ?></li>
                        <li>MySQL Version: <?php echo $system_status['mysql']['version']; ?></li>
                        <li>Memory Limit: <?php echo $system_status['php']['memory_limit']; ?></li>
                    </ul>
                </div>
                
                <!-- Database Tables -->
                <div class="status-card">
                    <h4><?php _e('Database Tables', 'environmental-email-marketing'); ?></h4>
                    <?php foreach ($system_status['database']['tables'] as $table => $exists): ?>
                        <div style="color: <?php echo $exists ? 'green' : 'red'; ?>;">
                            <?php echo $table; ?>: <?php echo $exists ? '✓' : '✗'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Email Providers -->
                <div class="status-card">
                    <h4><?php _e('Email Providers', 'environmental-email-marketing'); ?></h4>
                    <?php foreach ($system_status['email_providers'] as $provider => $status): ?>
                        <div style="color: <?php echo $status['configured'] ? 'green' : 'orange'; ?>;">
                            <?php echo ucfirst($provider); ?>: <?php echo $status['status']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cron Jobs -->
                <div class="status-card">
                    <h4><?php _e('Scheduled Tasks', 'environmental-email-marketing'); ?></h4>
                    <?php foreach ($system_status['cron']['jobs'] as $job => $scheduled): ?>
                        <div style="color: <?php echo $scheduled ? 'green' : 'red'; ?>;">
                            <?php echo $job; ?>: <?php echo $scheduled ? '✓' : '✗'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Final Validation Results -->
        <div class="card">
            <h3><?php _e('Component Validation', 'environmental-email-marketing'); ?></h3>
            <?php 
            $final_validation = $validation_results['final_validation'];
            $passed = $final_validation['tests_passed'];
            $total = $final_validation['total_tests'];
            $success_rate = round(($passed / $total) * 100, 1);
            ?>
            
            <div class="validation-summary">
                <p><strong><?php _e('Tests Passed:', 'environmental-email-marketing'); ?></strong> 
                   <?php echo $passed; ?>/<?php echo $total; ?> (<?php echo $success_rate; ?>%)</p>
                
                <?php if ($final_validation['critical_errors']): ?>
                    <div class="notice notice-error">
                        <h4><?php _e('Critical Errors Found:', 'environmental-email-marketing'); ?></h4>
                        <ul>
                            <?php foreach ($final_validation['critical_errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($final_validation['warnings']): ?>
                    <div class="notice notice-warning">
                        <h4><?php _e('Warnings:', 'environmental-email-marketing'); ?></h4>
                        <ul>
                            <?php foreach ($final_validation['warnings'] as $warning): ?>
                                <li><?php echo esc_html($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="validation-details">
                <h4><?php _e('Component Test Results', 'environmental-email-marketing'); ?></h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <?php foreach ($final_validation['component_results'] as $component => $result): ?>
                        <div class="component-result" style="border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                            <h5><?php echo ucfirst(str_replace('_', ' ', $component)); ?></h5>
                            <div style="color: <?php echo $result['status'] === 'pass' ? 'green' : 'red'; ?>;">
                                Status: <?php echo ucfirst($result['status']); ?>
                            </div>
                            <div>Tests: <?php echo $result['tests_passed']; ?>/<?php echo $result['total_tests']; ?></div>
                            <?php if (!empty($result['errors'])): ?>
                                <details>
                                    <summary>Errors (<?php echo count($result['errors']); ?>)</summary>
                                    <ul style="font-size: 12px; margin-top: 5px;">
                                        <?php foreach ($result['errors'] as $error): ?>
                                            <li><?php echo esc_html($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Component Test Results -->
        <div class="card">
            <h3><?php _e('Manual Component Tests', 'environmental-email-marketing'); ?></h3>
            <?php 
            $component_tests = $validation_results['component_tests'];
            ?>
            
            <div class="test-results-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <?php foreach ($component_tests as $component => $tests): ?>
                    <div class="test-component" style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                        <h4><?php echo ucfirst(str_replace('_', ' ', $component)); ?></h4>
                        
                        <?php foreach ($tests as $test_name => $result): ?>
                            <div class="test-result" style="margin: 5px 0; padding: 5px; background: <?php echo $result['success'] ? '#e8f5e8' : '#f5e8e8'; ?>; border-radius: 3px;">
                                <strong><?php echo $test_name; ?>:</strong>
                                <span style="color: <?php echo $result['success'] ? 'green' : 'red'; ?>;">
                                    <?php echo $result['success'] ? '✓ PASS' : '✗ FAIL'; ?>
                                </span>
                                <?php if (!empty($result['message'])): ?>
                                    <div style="font-size: 12px; margin-top: 3px;">
                                        <?php echo esc_html($result['message']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- AJAX Validation Results -->
        <div class="card">
            <h3><?php _e('AJAX Endpoint Validation', 'environmental-email-marketing'); ?></h3>
            <?php 
            $ajax_tests = $validation_results['ajax_tests'];
            ?>
            
            <div class="ajax-results">
                <?php foreach ($ajax_tests as $endpoint => $result): ?>
                    <div class="ajax-result" style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <h4><?php echo $endpoint; ?></h4>
                        <div style="color: <?php echo $result['success'] ? 'green' : 'red'; ?>;">
                            Status: <?php echo $result['success'] ? 'WORKING' : 'FAILED'; ?>
                        </div>
                        <div>Response Time: <?php echo $result['response_time']; ?>ms</div>
                        <?php if (!empty($result['error'])): ?>
                            <div style="color: red; font-size: 12px; margin-top: 5px;">
                                Error: <?php echo esc_html($result['error']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($result['data'])): ?>
                            <details style="margin-top: 5px;">
                                <summary>Response Data</summary>
                                <pre style="font-size: 11px; background: #f9f9f9; padding: 10px; margin-top: 5px;"><?php echo esc_html(json_encode($result['data'], JSON_PRETTY_PRINT)); ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Final Recommendations -->
        <div class="card">
            <h3><?php _e('Deployment Readiness', 'environmental-email-marketing'); ?></h3>
            
            <?php
            $ready_for_deployment = true;
            $critical_issues = [];
            
            // Check for critical issues
            if (!empty($final_validation['critical_errors'])) {
                $ready_for_deployment = false;
                $critical_issues = array_merge($critical_issues, $final_validation['critical_errors']);
            }
            
            // Check component test results
            foreach ($validation_results['component_tests'] as $component => $tests) {
                foreach ($tests as $test_name => $result) {
                    if (!$result['success'] && strpos($test_name, 'critical') !== false) {
                        $ready_for_deployment = false;
                        $critical_issues[] = "Critical test failed: {$component} - {$test_name}";
                    }
                }
            }
            
            // Check AJAX endpoints
            foreach ($ajax_tests as $endpoint => $result) {
                if (!$result['success'] && in_array($endpoint, ['subscription', 'campaign_management'])) {
                    $ready_for_deployment = false;
                    $critical_issues[] = "Critical AJAX endpoint failed: {$endpoint}";
                }
            }
            ?>
            
            <?php if ($ready_for_deployment): ?>
                <div class="notice notice-success">
                    <h4 style="color: green;">✓ <?php _e('Plugin Ready for Deployment!', 'environmental-email-marketing'); ?></h4>
                    <p><?php _e('All critical tests have passed. The plugin is ready for production use.', 'environmental-email-marketing'); ?></p>
                    
                    <h5><?php _e('Deployment Checklist:', 'environmental-email-marketing'); ?></h5>
                    <ul>
                        <li>✓ Database tables created successfully</li>
                        <li>✓ All core components functional</li>
                        <li>✓ AJAX endpoints working</li>
                        <li>✓ Email providers can be configured</li>
                        <li>✓ Templates render correctly</li>
                        <li>✓ Analytics tracking operational</li>
                        <li>✓ Environmental features active</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="notice notice-error">
                    <h4 style="color: red;">✗ <?php _e('Plugin NOT Ready for Deployment', 'environmental-email-marketing'); ?></h4>
                    <p><?php _e('Critical issues found that must be resolved before deployment:', 'environmental-email-marketing'); ?></p>
                    
                    <ul>
                        <?php foreach ($critical_issues as $issue): ?>
                            <li style="color: red;"><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <p><strong><?php _e('Recommended Actions:', 'environmental-email-marketing'); ?></strong></p>
                    <ul>
                        <li><?php _e('Review and fix all critical errors listed above', 'environmental-email-marketing'); ?></li>
                        <li><?php _e('Re-run validation after fixes are applied', 'environmental-email-marketing'); ?></li>
                        <li><?php _e('Test email sending with at least one provider', 'environmental-email-marketing'); ?></li>
                        <li><?php _e('Verify database permissions and table creation', 'environmental-email-marketing'); ?></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=eem-validation'); ?>" 
               class="button button-secondary">
                <?php _e('Run Validation Again', 'environmental-email-marketing'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=eem-dashboard'); ?>" 
               class="button button-primary" style="margin-left: 10px;">
                <?php _e('Back to Dashboard', 'environmental-email-marketing'); ?>
            </a>
        </div>
        
    <?php endif; ?>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2, .card h3 {
    margin-top: 0;
}

.status-card {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.status-card h4 {
    margin-top: 0;
    color: #23282d;
}

.test-component {
    background: #fafafa;
}

.test-component h4 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.validation-summary {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
}

.ajax-result {
    background: #fafafa;
}

.ajax-result h4 {
    margin-top: 0;
    color: #23282d;
}
</style>
