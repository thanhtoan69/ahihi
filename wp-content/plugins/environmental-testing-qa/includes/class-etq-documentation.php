<?php
/**
 * Documentation Manager
 * 
 * Handles testing documentation, procedures, and guidelines
 * for the Environmental Testing & QA system.
 * 
 * @package EnvironmentalTestingQA
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ETQ_Documentation {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Database instance
     */
    private $db;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = ETQ_Database::get_instance();
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize
     */
    public function init() {
        add_action('wp_ajax_etq_get_documentation', [$this, 'ajax_get_documentation']);
        add_action('wp_ajax_etq_search_docs', [$this, 'ajax_search_docs']);
    }
    
    /**
     * Get testing procedures documentation
     */
    public function get_testing_procedures() {
        return [
            'overview' => $this->get_overview_docs(),
            'phpunit' => $this->get_phpunit_docs(),
            'selenium' => $this->get_selenium_docs(),
            'performance' => $this->get_performance_docs(),
            'staging' => $this->get_staging_docs(),
            'best_practices' => $this->get_best_practices_docs(),
            'troubleshooting' => $this->get_troubleshooting_docs()
        ];
    }
    
    /**
     * Get overview documentation
     */
    private function get_overview_docs() {
        return [
            'title' => 'Environmental Testing & QA Overview',
            'content' => '
# Environmental Testing & Quality Assurance System

## Introduction
The Environmental Testing & QA system provides comprehensive automated testing capabilities for the Environmental Platform WordPress installation. This system includes multiple testing frameworks and quality assurance tools.

## Key Features
- **PHPUnit Testing**: Automated unit and integration tests
- **Selenium Testing**: Browser automation and end-to-end testing
- **Performance Testing**: Load testing and performance monitoring
- **Staging Environments**: Safe testing environments for deployments
- **Test Management**: Comprehensive test suite orchestration
- **Reporting**: Detailed test results and coverage reports

## System Architecture
The system is built with a modular architecture allowing easy extension and customization:

### Core Components
1. **Database Layer**: Centralized test data management
2. **Test Managers**: Specialized handlers for each test type
3. **Test Runner**: Unified execution engine
4. **Admin Dashboard**: Web-based management interface
5. **Documentation System**: Comprehensive guides and procedures

## Getting Started
1. Ensure all dependencies are installed
2. Configure test environments
3. Create your first test suite
4. Run initial smoke tests
5. Review results and configure notifications

## System Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Recommended: Node.js for Selenium testing
- Recommended: PHPUnit installed globally
            ',
            'sections' => [
                'introduction',
                'features',
                'architecture',
                'getting_started',
                'requirements'
            ]
        ];
    }
    
    /**
     * Get PHPUnit documentation
     */
    private function get_phpunit_docs() {
        return [
            'title' => 'PHPUnit Testing Guide',
            'content' => '
# PHPUnit Testing Guide

## Overview
PHPUnit is the primary unit testing framework for PHP applications. This guide covers how to use PHPUnit within the Environmental Testing system.

## Installation
PHPUnit can be installed globally or as a project dependency:

```bash
# Global installation
composer global require phpunit/phpunit

# Project installation
composer require --dev phpunit/phpunit
```

## Configuration
The system automatically generates PHPUnit configuration files (`phpunit.xml`) based on your settings.

### Basic Configuration
- Test directories: `/tests/unit`, `/tests/integration`
- Bootstrap file: `tests/bootstrap.php`
- Code coverage: Enabled by default
- Logging: XML and HTML reports generated

## Writing Tests

### Unit Tests
Unit tests should test individual classes or functions in isolation:

```php
<?php
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase {
    public function testBasicAssertion() {
        $this->assertTrue(true);
    }
    
    public function testStringContains() {
        $this->assertStringContainsString("Environmental", "Environmental Platform");
    }
}
```

### Integration Tests
Integration tests verify that multiple components work together:

```php
<?php
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {
    public function setUp(): void {
        // Set up WordPress test environment
        // Initialize database connections
        // Create test data
    }
    
    public function testDatabaseConnection() {
        global $wpdb;
        $result = $wpdb->get_results("SHOW TABLES");
        $this->assertNotEmpty($result);
    }
}
```

## Running Tests

### Via Dashboard
1. Navigate to Testing & QA → PHPUnit Tests
2. Select test suite or individual tests
3. Click "Run Tests"
4. Monitor progress and view results

### Via Command Line
```bash
# Run all tests
phpunit

# Run specific test file
phpunit tests/SampleTest.php

# Run with coverage
phpunit --coverage-html coverage/
```

## Best Practices
1. **Test Naming**: Use descriptive test method names
2. **Test Structure**: Follow Arrange-Act-Assert pattern
3. **Test Data**: Use factories or fixtures for test data
4. **Mocking**: Mock external dependencies
5. **Assertions**: Use specific assertions for better error messages

## Common Patterns

### Testing WordPress Functions
```php
public function testWordPressFunctionality() {
    // Test WordPress hooks
    $this->assertTrue(has_action("init", "my_init_function"));
    
    // Test option handling
    update_option("test_option", "test_value");
    $this->assertEquals("test_value", get_option("test_option"));
}
```

### Testing Database Operations
```php
public function testDatabaseOperations() {
    global $wpdb;
    
    // Insert test data
    $result = $wpdb->insert("test_table", ["name" => "Test"]);
    $this->assertNotFalse($result);
    
    // Verify data
    $data = $wpdb->get_row("SELECT * FROM test_table WHERE name = \"Test\"");
    $this->assertNotNull($data);
}
```
            ',
            'sections' => [
                'overview',
                'installation',
                'configuration',
                'writing_tests',
                'running_tests',
                'best_practices',
                'common_patterns'
            ]
        ];
    }
    
    /**
     * Get Selenium documentation
     */
    private function get_selenium_docs() {
        return [
            'title' => 'Selenium Testing Guide',
            'content' => '
# Selenium Testing Guide

## Overview
Selenium WebDriver enables automated browser testing for end-to-end functionality verification. This guide covers Selenium integration with the Environmental Testing system.

## Prerequisites
- Java 8+ installed
- Browser drivers (Chrome, Firefox, etc.)
- Selenium Server (optional for remote testing)

## Setup

### Chrome Driver
1. Download ChromeDriver from https://chromedriver.chromium.org/
2. Place in system PATH or specify path in configuration
3. Ensure Chrome browser is installed

### Firefox Driver
1. Download GeckoDriver from https://github.com/mozilla/geckodriver
2. Place in system PATH or specify path in configuration
3. Ensure Firefox browser is installed

## Writing Selenium Tests

### Basic Test Structure
```php
<?php
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class SeleniumTest {
    private $driver;
    
    public function setUp() {
        $this->driver = RemoteWebDriver::create(
            "http://localhost:4444/wd/hub",
            DesiredCapabilities::chrome()
        );
    }
    
    public function testHomePage() {
        $this->driver->get("http://your-site.local");
        $title = $this->driver->getTitle();
        $this->assertStringContainsString("Environmental Platform", $title);
    }
    
    public function tearDown() {
        $this->driver->quit();
    }
}
```

### Common Test Scenarios

#### Login Testing
```php
public function testUserLogin() {
    $this->driver->get("http://your-site.local/wp-login.php");
    
    // Fill login form
    $this->driver->findElement(WebDriverBy::id("user_login"))
                 ->sendKeys("testuser");
    $this->driver->findElement(WebDriverBy::id("user_pass"))
                 ->sendKeys("testpass");
    $this->driver->findElement(WebDriverBy::id("wp-submit"))
                 ->click();
    
    // Verify successful login
    $this->driver->wait()->until(
        WebDriverExpectedCondition::urlContains("/wp-admin/")
    );
}
```

#### Form Submission
```php
public function testContactForm() {
    $this->driver->get("http://your-site.local/contact");
    
    // Fill form
    $this->driver->findElement(WebDriverBy::name("name"))
                 ->sendKeys("Test User");
    $this->driver->findElement(WebDriverBy::name("email"))
                 ->sendKeys("test@example.com");
    $this->driver->findElement(WebDriverBy::name("message"))
                 ->sendKeys("Test message");
    
    // Submit form
    $this->driver->findElement(WebDriverBy::css("input[type=\"submit\"]"))
                 ->click();
    
    // Verify success message
    $successMessage = $this->driver->findElement(
        WebDriverBy::css(".success-message")
    );
    $this->assertTrue($successMessage->isDisplayed());
}
```

## Page Object Model
Use the Page Object Model for maintainable tests:

```php
class LoginPage {
    private $driver;
    
    public function __construct($driver) {
        $this->driver = $driver;
    }
    
    public function open() {
        $this->driver->get("http://your-site.local/wp-login.php");
        return $this;
    }
    
    public function login($username, $password) {
        $this->driver->findElement(WebDriverBy::id("user_login"))
                     ->sendKeys($username);
        $this->driver->findElement(WebDriverBy::id("user_pass"))
                     ->sendKeys($password);
        $this->driver->findElement(WebDriverBy::id("wp-submit"))
                     ->click();
        return new DashboardPage($this->driver);
    }
}
```

## Running Selenium Tests

### Via Dashboard
1. Navigate to Testing & QA → Selenium Tests
2. Configure browser settings
3. Select test scripts
4. Monitor execution with screenshots/videos

### Via Command Line
```bash
# Start Selenium server
java -jar selenium-server-standalone.jar

# Run tests
php selenium-runner.php --suite=smoke --browser=chrome
```

## Best Practices
1. **Wait Strategies**: Use explicit waits instead of sleep()
2. **Element Location**: Prefer IDs over XPath when possible
3. **Test Data**: Use unique test data to avoid conflicts
4. **Screenshots**: Capture screenshots on failures
5. **Browser Management**: Always close browsers after tests
6. **Parallel Execution**: Use multiple browsers for faster testing

## Troubleshooting
- **Element Not Found**: Check for dynamic loading, use waits
- **Stale Element**: Re-locate elements after page changes
- **Timeout Issues**: Increase wait times for slow pages
- **Browser Crashes**: Check driver compatibility and memory usage
            ',
            'sections' => [
                'overview',
                'prerequisites',
                'setup',
                'writing_tests',
                'page_object_model',
                'running_tests',
                'best_practices',
                'troubleshooting'
            ]
        ];
    }
    
    /**
     * Get performance testing documentation
     */
    private function get_performance_docs() {
        return [
            'title' => 'Performance Testing Guide',
            'content' => '
# Performance Testing Guide

## Overview
Performance testing ensures your application can handle expected load and performs efficiently. This guide covers performance testing tools and methodologies.

## Types of Performance Testing

### Load Testing
Tests normal expected load:
- Simulates typical user activity
- Validates response times under normal conditions
- Identifies baseline performance metrics

### Stress Testing
Tests beyond normal capacity:
- Determines breaking point
- Tests recovery capabilities
- Identifies memory leaks and resource issues

### Volume Testing
Tests with large amounts of data:
- Database performance with large datasets
- File upload/download with large files
- Search functionality with extensive content

## Performance Metrics

### Key Metrics to Monitor
1. **Response Time**: Time to complete requests
2. **Throughput**: Requests handled per second
3. **CPU Usage**: Server CPU utilization
4. **Memory Usage**: RAM consumption
5. **Database Performance**: Query execution times
6. **Error Rate**: Failed requests percentage

### Acceptable Thresholds
- **Page Load Time**: < 3 seconds
- **API Response**: < 1 second
- **Database Queries**: < 100ms average
- **Error Rate**: < 1%
- **CPU Usage**: < 80% sustained
- **Memory Usage**: < 85% of available

## Testing Tools

### Built-in Performance Tester
The system includes a comprehensive performance testing module:

```php
// Example performance test
$performance_tester = ETQ_Performance_Tester::get_instance();

$test_config = [
    "url" => "http://your-site.local",
    "concurrent_users" => 10,
    "duration" => 60, // seconds
    "ramp_up" => 10   // seconds
];

$results = $performance_tester->run_load_test($test_config);
```

### External Tools Integration
- **Apache Bench (ab)**: Simple load testing
- **JMeter**: Comprehensive performance testing
- **LoadRunner**: Enterprise performance testing
- **WebPageTest**: Real browser performance testing

## Database Performance Testing

### Query Performance
```sql
-- Enable query logging
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- Analyze slow queries
SELECT * FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Check query execution plans
EXPLAIN SELECT * FROM wp_posts WHERE post_status = 'publish';
```

### Index Optimization
```sql
-- Identify missing indexes
SELECT DISTINCT
    CONCAT('CREATE INDEX idx_', table_name, '_', column_name, ' ON ', table_name, ' (', column_name, ');') AS suggestion
FROM information_schema.columns 
WHERE table_schema = 'your_database'
AND column_name IN ('post_status', 'post_type', 'meta_key');
```

## Performance Test Scenarios

### Homepage Load Test
```php
$homepage_test = [
    "name" => "Homepage Load Test",
    "url" => home_url(),
    "method" => "GET",
    "concurrent_users" => [1, 5, 10, 25, 50],
    "duration" => 120,
    "assertions" => [
        "response_time_avg" => "<= 2000ms",
        "response_time_95th" => "<= 3000ms",
        "error_rate" => "<= 1%"
    ]
];
```

### Login Flow Test
```php
$login_test = [
    "name" => "User Login Performance",
    "steps" => [
        ["GET", "/wp-login.php"],
        ["POST", "/wp-login.php", ["log" => "user", "pwd" => "pass"]],
        ["GET", "/wp-admin/"]
    ],
    "concurrent_users" => 10,
    "duration" => 300
];
```

### API Performance Test
```php
$api_test = [
    "name" => "REST API Performance",
    "url" => rest_url("wp/v2/posts"),
    "method" => "GET",
    "headers" => ["Authorization" => "Bearer token"],
    "concurrent_users" => 20,
    "duration" => 180
];
```

## Optimization Strategies

### Caching
1. **Object Caching**: Redis/Memcached
2. **Page Caching**: WP Rocket, W3 Total Cache
3. **Database Caching**: Query result caching
4. **CDN**: Content Delivery Network

### Database Optimization
1. **Query Optimization**: Reduce complex queries
2. **Index Management**: Add appropriate indexes
3. **Connection Pooling**: Reuse database connections
4. **Read Replicas**: Separate read/write operations

### Code Optimization
1. **Lazy Loading**: Load resources when needed
2. **Minification**: Compress CSS/JS files
3. **Image Optimization**: Compress and resize images
4. **HTTP/2**: Use modern protocols

## Monitoring and Alerting

### Continuous Monitoring
- **New Relic**: Application performance monitoring
- **GTmetrix**: Website speed monitoring
- **Pingdom**: Uptime and performance monitoring
- **Google PageSpeed**: Core Web Vitals tracking

### Alert Configuration
```php
$alert_rules = [
    "response_time_threshold" => 3000, // ms
    "error_rate_threshold" => 5,       // %
    "cpu_usage_threshold" => 85,       // %
    "memory_usage_threshold" => 90,    // %
    "notification_email" => "admin@yoursite.com"
];
```

## Reporting

### Performance Reports
The system generates comprehensive performance reports including:
- Response time trends
- Throughput analysis
- Error rate tracking
- Resource utilization graphs
- Comparison with baseline metrics
- Optimization recommendations

### Custom Metrics
```php
// Track custom performance metrics
$performance_tester->track_metric("custom_function_time", $execution_time);
$performance_tester->track_metric("database_query_count", $query_count);
$performance_tester->track_metric("memory_peak_usage", memory_get_peak_usage());
```
            ',
            'sections' => [
                'overview',
                'types_of_testing',
                'metrics',
                'tools',
                'database_testing',
                'scenarios',
                'optimization',
                'monitoring',
                'reporting'
            ]
        ];
    }
    
    /**
     * Get staging documentation
     */
    private function get_staging_docs() {
        return [
            'title' => 'Staging Environment Guide',
            'content' => '
# Staging Environment Guide

## Overview
Staging environments provide safe spaces to test changes before deploying to production. This guide covers staging environment setup, management, and best practices.

## Environment Types

### Development Environment
- Local development setup
- Rapid iteration and testing
- Individual developer workspaces
- No external access

### Staging Environment
- Production-like environment
- Pre-deployment testing
- Client review and approval
- Limited access

### Production Environment
- Live website
- Real user traffic
- High availability requirements
- Strict change control

## Staging Environment Setup

### Automatic Setup
The system can automatically create staging environments:

```php
$staging_manager = ETQ_Staging_Manager::get_instance();

$staging_config = [
    "name" => "feature-testing",
    "source" => "production",
    "database_copy" => true,
    "files_copy" => true,
    "subdomain" => "staging-feature"
];

$staging_env = $staging_manager->create_environment($staging_config);
```

### Manual Setup
1. **Server Setup**: Configure web server (Apache/Nginx)
2. **Database Setup**: Create separate database
3. **File System**: Copy WordPress files
4. **Configuration**: Update wp-config.php
5. **DNS Setup**: Configure subdomain/domain

## Data Synchronization

### Database Sync
```php
// Sync production data to staging
$sync_config = [
    "source_db" => "production_db",
    "target_db" => "staging_db",
    "exclude_tables" => ["wp_users", "wp_sessions"],
    "sanitize_data" => true
];

$staging_manager->sync_database($sync_config);
```

### File Sync
```php
// Sync uploads and theme files
$file_sync = [
    "source_path" => "/var/www/production",
    "target_path" => "/var/www/staging",
    "include_uploads" => true,
    "include_themes" => true,
    "include_plugins" => true,
    "exclude_cache" => true
];

$staging_manager->sync_files($file_sync);
```

## Deployment Testing

### Pre-deployment Checklist
1. **Code Review**: All changes reviewed and approved
2. **Unit Tests**: All tests passing
3. **Integration Tests**: System components working together
4. **Performance Tests**: No performance degradation
5. **Security Tests**: No security vulnerabilities
6. **User Acceptance**: Stakeholder approval

### Automated Deployment Testing
```php
$deployment_test = [
    "steps" => [
        "backup_production",
        "deploy_to_staging",
        "run_smoke_tests",
        "run_integration_tests",
        "performance_validation",
        "security_scan"
    ],
    "rollback_on_failure" => true,
    "notification_emails" => ["dev@yoursite.com"]
];

$staging_manager->run_deployment_test($deployment_test);
```

## Environment Management

### Environment Lifecycle
1. **Creation**: Set up new staging environment
2. **Configuration**: Apply environment-specific settings
3. **Testing**: Execute test suites
4. **Maintenance**: Keep environment updated
5. **Cleanup**: Remove obsolete environments

### Resource Management
```php
// Monitor staging environment resources
$resource_monitor = [
    "disk_usage_threshold" => 80,    // %
    "memory_usage_threshold" => 85,  // %
    "cpu_usage_threshold" => 90,     // %
    "cleanup_old_environments" => 30 // days
];

$staging_manager->monitor_resources($resource_monitor);
```

## Testing Workflows

### Feature Development Workflow
1. **Create Feature Branch**: Isolate development
2. **Create Staging Environment**: Deploy feature branch
3. **Run Automated Tests**: Validate functionality
4. **Manual Testing**: User acceptance testing
5. **Performance Validation**: Ensure no regression
6. **Merge to Main**: Deploy to production

### Release Testing Workflow
1. **Release Candidate**: Prepare release version
2. **Staging Deployment**: Deploy to staging
3. **Full Test Suite**: Run comprehensive tests
4. **Load Testing**: Validate performance under load
5. **Security Audit**: Security vulnerability scan
6. **Go/No-Go Decision**: Approval for production

## Data Privacy and Security

### Data Sanitization
```php
// Sanitize sensitive data in staging
$sanitization_rules = [
    "user_emails" => "test+{id}@example.com",
    "user_passwords" => "staging_password",
    "customer_data" => "anonymize",
    "payment_info" => "remove",
    "api_keys" => "use_test_keys"
];

$staging_manager->sanitize_data($sanitization_rules);
```

### Access Control
- **VPN Access**: Restrict staging access via VPN
- **HTTP Authentication**: Basic auth protection
- **IP Whitelisting**: Allow specific IP addresses
- **User Permissions**: Limited user accounts

## Monitoring and Alerts

### Health Monitoring
```php
$health_checks = [
    "database_connection" => true,
    "file_permissions" => true,
    "disk_space" => "> 20%",
    "memory_usage" => "< 85%",
    "response_time" => "< 2000ms"
];

$staging_manager->monitor_health($health_checks);
```

### Automated Alerts
- **Environment Down**: Immediate notification
- **High Resource Usage**: Warning alerts
- **Test Failures**: Test result notifications
- **Deployment Status**: Success/failure notifications

## Best Practices

### Environment Consistency
1. **Infrastructure as Code**: Use Docker/Ansible
2. **Version Control**: Track environment configurations
3. **Automated Provisioning**: Consistent environment setup
4. **Configuration Management**: Environment-specific settings

### Testing Best Practices
1. **Test Early and Often**: Continuous testing
2. **Realistic Data**: Use production-like data
3. **Performance Baseline**: Maintain performance standards
4. **Security Testing**: Regular security audits
5. **User Acceptance**: Stakeholder involvement

### Maintenance
1. **Regular Updates**: Keep environments current
2. **Cleanup**: Remove old environments
3. **Backup**: Regular staging backups
4. **Documentation**: Maintain environment docs
5. **Monitoring**: Continuous health monitoring

## Troubleshooting

### Common Issues
- **Database Connection**: Check credentials and connectivity
- **File Permissions**: Verify web server permissions
- **DNS Issues**: Confirm domain/subdomain configuration
- **SSL Certificates**: Ensure valid certificates
- **Resource Limits**: Monitor CPU/memory/disk usage

### Debugging Tools
```php
// Enable staging environment debugging
$debug_config = [
    "wp_debug" => true,
    "wp_debug_log" => true,
    "query_debug" => true,
    "performance_profiling" => true,
    "error_reporting" => E_ALL
];

$staging_manager->enable_debugging($debug_config);
```
            ',
            'sections' => [
                'overview',
                'environment_types',
                'setup',
                'data_sync',
                'deployment_testing',
                'management',
                'workflows',
                'security',
                'monitoring',
                'best_practices',
                'troubleshooting'
            ]
        ];
    }
    
    /**
     * Get best practices documentation
     */
    private function get_best_practices_docs() {
        return [
            'title' => 'Testing Best Practices',
            'content' => '
# Testing Best Practices

## General Testing Principles

### Test Pyramid
Structure your tests following the test pyramid:
1. **Unit Tests (Base)**: Fast, isolated, numerous
2. **Integration Tests (Middle)**: Component interaction
3. **End-to-End Tests (Top)**: Full user workflows, fewer

### FIRST Principles
- **Fast**: Tests should run quickly
- **Independent**: Tests should not depend on each other
- **Repeatable**: Same results in any environment
- **Self-Validating**: Clear pass/fail result
- **Timely**: Written just before production code

## Test Organization

### Directory Structure
```
tests/
├── unit/
│   ├── models/
│   ├── controllers/
│   └── helpers/
├── integration/
│   ├── database/
│   ├── api/
│   └── features/
├── e2e/
│   ├── user-flows/
│   ├── admin-flows/
│   └── checkout-flows/
└── fixtures/
    ├── data/
    └── files/
```

### Naming Conventions
- **Test Files**: `*Test.php` or `*_test.php`
- **Test Methods**: `test_*` or `testCamelCase`
- **Test Classes**: `ClassNameTest`
- **Descriptive Names**: `testUserCanLoginWithValidCredentials`

## Writing Quality Tests

### Arrange-Act-Assert Pattern
```php
public function testCalculateTotalPrice() {
    // Arrange
    $product = new Product("Test Product", 10.00);
    $quantity = 3;
    
    // Act
    $total = $product->calculateTotal($quantity);
    
    // Assert
    $this->assertEquals(30.00, $total);
}
```

### Test Data Management
```php
// Use factories for test data
class ProductFactory {
    public static function create($attributes = []) {
        return new Product(array_merge([
            'name' => 'Test Product',
            'price' => 10.00,
            'status' => 'active'
        ], $attributes));
    }
}

// Use fixtures for complex data
public function setUp(): void {
    $this->testData = json_decode(
        file_get_contents(__DIR__ . '/fixtures/products.json'),
        true
    );
}
```

### Mocking and Stubbing
```php
// Mock external dependencies
public function testEmailNotificationSent() {
    $emailService = $this->createMock(EmailService::class);
    $emailService->expects($this->once())
               ->method('send')
               ->with($this->stringContains('Welcome'));
    
    $userService = new UserService($emailService);
    $userService->registerUser('test@example.com');
}
```

## Test Coverage

### Coverage Goals
- **Unit Tests**: 80-90% code coverage
- **Integration Tests**: Critical paths covered
- **E2E Tests**: Major user workflows covered

### Coverage Analysis
```bash
# Generate coverage report
phpunit --coverage-html coverage/

# Coverage with filter
phpunit --coverage-html coverage/ --whitelist src/
```

### Quality over Quantity
- Focus on critical business logic
- Test edge cases and error conditions
- Avoid testing getters/setters
- Test public interfaces, not implementation

## Continuous Integration

### CI Pipeline Structure
```yaml
# Example CI configuration
stages:
  - lint
  - unit-tests
  - integration-tests
  - security-scan
  - performance-tests
  - e2e-tests
  - deploy-staging
  - manual-approval
  - deploy-production
```

### Test Execution Strategy
1. **Fast Tests First**: Unit tests run immediately
2. **Parallel Execution**: Run tests concurrently
3. **Fail Fast**: Stop on first critical failure
4. **Retry Logic**: Handle flaky tests appropriately

## Performance Testing Best Practices

### Test Environment
- **Production-like**: Similar hardware/software
- **Isolated**: Dedicated testing environment
- **Consistent**: Repeatable test conditions
- **Monitored**: Comprehensive metrics collection

### Test Design
```php
// Gradual load increase
$load_pattern = [
    ['users' => 1,  'duration' => 60],   // Baseline
    ['users' => 5,  'duration' => 120],  // Light load
    ['users' => 10, 'duration' => 180],  // Normal load
    ['users' => 25, 'duration' => 240],  // Heavy load
    ['users' => 50, 'duration' => 120],  // Stress test
];
```

### Metrics Collection
- **Response Time**: Average, median, 95th percentile
- **Throughput**: Requests per second
- **Error Rate**: Failed requests percentage
- **Resource Usage**: CPU, memory, disk, network

## Security Testing Best Practices

### Security Test Categories
1. **Authentication**: Login security, session management
2. **Authorization**: Access control, privilege escalation
3. **Input Validation**: SQL injection, XSS prevention
4. **Data Protection**: Encryption, sensitive data handling
5. **Configuration**: Security headers, error handling

### Automated Security Scanning
```php
// Security test configuration
$security_tests = [
    'sql_injection' => [
        'payloads' => ["' OR 1=1--", "'; DROP TABLE users--"],
        'endpoints' => ['/search', '/login', '/contact']
    ],
    'xss_protection' => [
        'payloads' => ['<script>alert(1)</script>', 'javascript:alert(1)'],
        'form_fields' => ['name', 'email', 'message']
    ]
];
```

## Test Maintenance

### Keeping Tests Relevant
1. **Regular Review**: Monthly test review sessions
2. **Remove Obsolete**: Delete tests for removed features
3. **Update Tests**: Modify tests when requirements change
4. **Refactor**: Improve test code quality regularly

### Managing Test Debt
- **Flaky Tests**: Fix or remove unreliable tests
- **Slow Tests**: Optimize or move to appropriate category
- **Duplicate Tests**: Consolidate redundant tests
- **Unclear Tests**: Improve documentation and naming

## Collaboration and Communication

### Test Documentation
```php
/**
 * Test user registration with valid email and password
 * 
 * Verifies that:
 * - User account is created successfully
 * - Welcome email is sent
 * - User is redirected to dashboard
 * - Session is established
 * 
 * @covers UserService::registerUser
 * @covers EmailService::sendWelcomeEmail
 */
public function testSuccessfulUserRegistration() {
    // Test implementation
}
```

### Code Review for Tests
- **Test Logic**: Verify test correctness
- **Test Coverage**: Ensure adequate coverage
- **Test Quality**: Review test readability and maintainability
- **Performance Impact**: Consider test execution time

### Team Guidelines
1. **Test First**: Write tests before or with code
2. **Review Tests**: Include tests in code review
3. **Shared Responsibility**: Everyone writes and maintains tests
4. **Knowledge Sharing**: Regular testing discussions

## Monitoring and Reporting

### Test Metrics Tracking
- **Test Execution Time**: Monitor test performance
- **Test Success Rate**: Track test reliability
- **Coverage Trends**: Monitor coverage over time
- **Test Maintenance**: Track time spent on test fixes

### Automated Reporting
```php
// Generate test reports
$report_config = [
    'frequency' => 'daily',
    'recipients' => ['team@yoursite.com'],
    'include_metrics' => ['coverage', 'execution_time', 'success_rate'],
    'format' => 'html'
];

$test_reporter->schedule_report($report_config);
```

## Tools and Technologies

### Recommended Tools
- **Unit Testing**: PHPUnit, Jest, Mocha
- **Integration Testing**: Codeception, Postman
- **E2E Testing**: Selenium, Cypress, Puppeteer
- **Performance Testing**: JMeter, LoadRunner, k6
- **Security Testing**: OWASP ZAP, Burp Suite
- **Code Coverage**: Xdebug, Istanbul, JaCoCo

### Tool Integration
- **IDE Integration**: Run tests from development environment
- **CI/CD Integration**: Automated test execution
- **Monitoring Integration**: Test results in monitoring dashboards
- **Reporting Integration**: Test metrics in project reports
            ',
            'sections' => [
                'principles',
                'organization',
                'quality_tests',
                'coverage',
                'ci_cd',
                'performance',
                'security',
                'maintenance',
                'collaboration',
                'monitoring',
                'tools'
            ]
        ];
    }
    
    /**
     * Get troubleshooting documentation
     */
    private function get_troubleshooting_docs() {
        return [
            'title' => 'Troubleshooting Guide',
            'content' => '
# Troubleshooting Guide

## Common Issues and Solutions

### PHPUnit Issues

#### PHPUnit Not Found
**Problem**: Command `phpunit` not recognized
**Solutions**:
1. Install PHPUnit globally: `composer global require phpunit/phpunit`
2. Use project PHPUnit: `./vendor/bin/phpunit`
3. Check PATH environment variable
4. Verify PHP installation

#### Configuration Errors
**Problem**: PHPUnit configuration issues
**Solutions**:
1. Validate `phpunit.xml` syntax
2. Check bootstrap file path
3. Verify test directory paths
4. Ensure autoload configuration

#### Memory Limit Errors
**Problem**: PHP memory limit exceeded during tests
**Solutions**:
```php
// In phpunit.xml
<phpunit>
    <php>
        <ini name="memory_limit" value="512M"/>
    </php>
</phpunit>

// Or via command line
phpunit -d memory_limit=512M
```

### Selenium Issues

#### WebDriver Not Found
**Problem**: ChromeDriver or GeckoDriver not found
**Solutions**:
1. Download correct driver version
2. Add driver to system PATH
3. Specify driver path in configuration
4. Check browser compatibility

#### Element Not Found
**Problem**: Selenium cannot locate page elements
**Solutions**:
```php
// Use explicit waits
$wait = new WebDriverWait($driver, 10);
$element = $wait->until(
    WebDriverExpectedCondition::elementToBeClickable(
        WebDriverBy::id('submit-button')
    )
);

// Try different locator strategies
$element = $driver->findElement(WebDriverBy::id('element-id'));
$element = $driver->findElement(WebDriverBy::className('element-class'));
$element = $driver->findElement(WebDriverBy::cssSelector('.my-class'));
```

#### Browser Timeout Issues
**Problem**: Browser operations timing out
**Solutions**:
1. Increase timeout values
2. Check network connectivity
3. Verify page load performance
4. Use appropriate wait strategies

### Database Issues

#### Connection Failures
**Problem**: Cannot connect to test database
**Solutions**:
1. Verify database credentials
2. Check database server status
3. Ensure test database exists
4. Validate network connectivity

```php
// Test database connection
try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Database connection successful";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

#### Test Data Isolation
**Problem**: Tests interfering with each other
**Solutions**:
1. Use database transactions
2. Clean up test data after each test
3. Use unique test data identifiers
4. Implement proper setUp/tearDown

```php
public function setUp(): void {
    $this->pdo->beginTransaction();
}

public function tearDown(): void {
    $this->pdo->rollBack();
}
```

### Performance Testing Issues

#### Inconsistent Results
**Problem**: Performance test results vary significantly
**Solutions**:
1. Use dedicated test environment
2. Run multiple test iterations
3. Eliminate external factors
4. Monitor system resources

#### Resource Limitations
**Problem**: Test environment resource constraints
**Solutions**:
1. Monitor CPU/memory usage
2. Increase test environment resources
3. Optimize test scenarios
4. Use cloud-based testing

### Environment Issues

#### Staging Environment Problems
**Problem**: Staging environment not working correctly
**Solutions**:
1. Check environment configuration
2. Verify file permissions
3. Validate database connectivity
4. Review error logs

#### SSL Certificate Issues
**Problem**: SSL/TLS certificate problems in staging
**Solutions**:
1. Use self-signed certificates for testing
2. Configure certificate validation bypass
3. Ensure proper certificate installation
4. Check certificate expiration

### Network and Connectivity

#### Firewall Issues
**Problem**: Tests failing due to network restrictions
**Solutions**:
1. Configure firewall rules
2. Use VPN for remote testing
3. Whitelist test IP addresses
4. Check proxy settings

#### DNS Resolution
**Problem**: Domain name resolution failures
**Solutions**:
1. Update hosts file for local testing
2. Use IP addresses instead of domains
3. Configure DNS servers
4. Check domain registration

## Debugging Techniques

### Enable Detailed Logging
```php
// PHPUnit debugging
phpunit --debug --verbose

// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Database query debugging
define('SAVEQUERIES', true);
```

### Performance Profiling
```php
// Profile test execution
$start_time = microtime(true);
// ... test code ...
$execution_time = microtime(true) - $start_time;
echo "Test execution time: " . $execution_time . " seconds";

// Memory usage profiling
echo "Memory usage: " . memory_get_usage(true) . " bytes";
echo "Peak memory: " . memory_get_peak_usage(true) . " bytes";
```

### Error Tracking
```php
// Capture and log errors
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Test Error: $message in $file on line $line");
});

// Exception handling
try {
    // Test code
} catch (Exception $e) {
    error_log("Test Exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}
```

## Log Analysis

### Common Log Locations
- **PHP Error Log**: `/var/log/php_errors.log`
- **Apache Error Log**: `/var/log/apache2/error.log`
- **Nginx Error Log**: `/var/log/nginx/error.log`
- **WordPress Debug Log**: `/wp-content/debug.log`
- **MySQL Error Log**: `/var/log/mysql/error.log`

### Log Analysis Tools
```bash
# Search for specific errors
grep "Fatal error" /var/log/php_errors.log

# Monitor logs in real-time
tail -f /var/log/apache2/error.log

# Analyze performance issues
grep "execution time" /wp-content/debug.log | sort -n
```

## Performance Optimization

### Test Execution Optimization
1. **Parallel Execution**: Run tests concurrently
2. **Test Grouping**: Group related tests
3. **Skip Slow Tests**: Use test groups for different execution modes
4. **Database Optimization**: Use in-memory databases for testing

### Resource Management
```php
// Optimize memory usage
ini_set('memory_limit', '256M');

// Clean up resources
unset($large_variables);
gc_collect_cycles();

// Close database connections
$pdo = null;
```

## Getting Help

### Internal Resources
1. **Team Knowledge Base**: Internal documentation
2. **Code Comments**: Inline documentation
3. **Test History**: Previous test results and fixes
4. **Team Experts**: Colleagues with testing expertise

### External Resources
1. **PHPUnit Documentation**: https://phpunit.de/documentation.html
2. **Selenium Documentation**: https://selenium.dev/documentation/
3. **Stack Overflow**: Community Q&A
4. **GitHub Issues**: Project-specific issues and solutions

### Support Escalation
1. **Level 1**: Team member assistance
2. **Level 2**: Team lead or senior developer
3. **Level 3**: External consultant or vendor support
4. **Documentation**: Update troubleshooting guide with solutions

## Preventive Measures

### Regular Maintenance
1. **Update Dependencies**: Keep testing tools current
2. **Monitor Test Health**: Track test success rates
3. **Review Test Coverage**: Ensure adequate coverage
4. **Performance Monitoring**: Track test execution times

### Best Practices Implementation
1. **Code Reviews**: Include test reviews
2. **Documentation**: Maintain current documentation
3. **Training**: Regular team training sessions
4. **Process Improvement**: Continuous improvement initiatives

### Monitoring and Alerting
```php
// Set up monitoring for test failures
$monitoring_config = [
    'test_failure_threshold' => 5,  // percent
    'execution_time_threshold' => 300,  // seconds
    'notification_email' => 'team@yoursite.com',
    'escalation_delay' => 3600  // seconds
];
```
            ',
            'sections' => [
                'common_issues',
                'debugging',
                'log_analysis',
                'optimization',
                'getting_help',
                'preventive_measures'
            ]
        ];
    }
    
    /**
     * AJAX handler for getting documentation
     */
    public function ajax_get_documentation() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-testing-qa'));
        }
        
        $doc_type = sanitize_text_field($_POST['doc_type'] ?? 'overview');
        $docs = $this->get_testing_procedures();
        
        if (isset($docs[$doc_type])) {
            wp_send_json_success($docs[$doc_type]);
        } else {
            wp_send_json_error(__('Documentation not found', 'environmental-testing-qa'));
        }
    }
    
    /**
     * AJAX handler for searching documentation
     */
    public function ajax_search_docs() {
        check_ajax_referer('etq_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'environmental-testing-qa'));
        }
        
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $docs = $this->get_testing_procedures();
        $results = [];
        
        foreach ($docs as $type => $doc) {
            if (stripos($doc['content'], $search_term) !== false) {
                $results[] = [
                    'type' => $type,
                    'title' => $doc['title'],
                    'excerpt' => $this->extract_search_excerpt($doc['content'], $search_term)
                ];
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Extract search excerpt from content
     */
    private function extract_search_excerpt($content, $search_term) {
        $pos = stripos($content, $search_term);
        if ($pos === false) {
            return substr(strip_tags($content), 0, 200) . '...';
        }
        
        $start = max(0, $pos - 100);
        $excerpt = substr($content, $start, 200);
        $excerpt = strip_tags($excerpt);
        
        return '...' . $excerpt . '...';
    }
}
