<?php
/**
 * PHPUnit bootstrap file for Environmental Email Marketing plugin
 */

// Define test environment
define('RUNNING_TESTS', true);
define('EEM_PLUGIN_URL', 'http://localhost/moitruong/wp-content/plugins/environmental-email-marketing/');
define('EEM_PLUGIN_PATH', dirname(__DIR__) . '/');

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require EEM_PLUGIN_PATH . 'environmental-email-marketing.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load our test case class
require_once dirname(__FILE__) . '/class-eem-test-case.php';
