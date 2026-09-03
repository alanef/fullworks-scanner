<?php
/**
 * PHPUnit bootstrap for the Fullworks Security Scanner test suite.
 *
 * Runs inside the wp-env "tests" container where the WordPress core test
 * library lives at /wordpress-phpunit and the repository root is mapped to
 * /var/www/html (see .wp-env.json "mappings").
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	if ( file_exists( '/wordpress-phpunit/includes/functions.php' ) ) {
		$_tests_dir = '/wordpress-phpunit';
	} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/functions.php' ) ) {
		$_tests_dir = '/tmp/wordpress-tests-lib';
	} else {
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}
}

if ( ! getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	putenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php" . PHP_EOL;
	exit( 1 );
}

require_once "{$_tests_dir}/includes/functions.php";

/**
 * Load the plugin and run its activation routine.
 */
function _fullworks_scanner_load_plugin() {
	$plugin_file = WP_PLUGIN_DIR . '/fullworks-scanner/fullworks-vulnerability-scanner.php';

	if ( ! file_exists( $plugin_file ) ) {
		echo 'Could not find plugin file: ' . $plugin_file . PHP_EOL;
		exit( 1 );
	}

	// Never let Action Scheduler fire its async (HTTP) queue runner during tests.
	tests_add_filter( 'action_scheduler_allow_async_request_runner', '__return_false' );

	require $plugin_file;

	// Activation hooks do not fire in the test environment, so create the table directly.
	\Fullworks_Scanner\Includes\Activator::activate( false );
}

tests_add_filter( 'muplugins_loaded', '_fullworks_scanner_load_plugin' );

require "{$_tests_dir}/includes/bootstrap.php";

require_once __DIR__ . '/class-scanner-test-case.php';
