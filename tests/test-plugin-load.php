<?php
/**
 * Plugin bootstrap, version consistency and hook registration.
 */

use Fullworks_Scanner\Includes\Core;

class Test_Plugin_Load extends Scanner_Test_Case {

	public function test_constants_are_defined() {
		$this->assertTrue( defined( 'FULLWORKS_SCANNER_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'FULLWORKS_SCANNER_PLUGIN_URL' ) );
		$this->assertSame( 'fullworks-scanner', FULLWORKS_SCANNER_PLUGIN_NAME );
		$this->assertTrue( defined( 'FULLWORKS_SCANNER_PLUGIN_VERSION' ) );
	}

	public function test_version_is_consistent_across_header_constant_and_readme() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugin_file = FULLWORKS_SCANNER_PLUGIN_DIR . 'fullworks-vulnerability-scanner.php';
		$header      = get_plugin_data( $plugin_file, false, false );

		$readme = file_get_contents( FULLWORKS_SCANNER_PLUGIN_DIR . 'readme.txt' );
		$this->assertSame( 1, preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $m ), 'readme.txt has a Stable tag' );

		$this->assertSame( $header['Version'], FULLWORKS_SCANNER_PLUGIN_VERSION, 'Header version matches constant' );
		$this->assertSame( $header['Version'], $m[1], 'Header version matches readme Stable tag' );
	}

	public function test_readme_declares_wp_and_php_requirements() {
		$readme = file_get_contents( FULLWORKS_SCANNER_PLUGIN_DIR . 'readme.txt' );
		$this->assertMatchesRegularExpression( '/^Tested up to:\s*7\.1/m', $readme );
		$this->assertMatchesRegularExpression( '/^Requires at least:\s*6\.8/m', $readme );
		$this->assertMatchesRegularExpression( '/^Requires PHP:\s*7\.4/m', $readme );
	}

	public function test_core_exposes_plugin_name_and_version() {
		$core = new Core();
		$this->assertSame( 'fullworks-scanner', $core->get_plugin_name() );
		$this->assertSame( FULLWORKS_SCANNER_PLUGIN_VERSION, $core->get_version() );
	}

	public function test_running_core_requests_no_translations_before_admin_menu() {
		// Regression: WP 6.7+ warns when a text domain is used before init. Booting the plugin
		// must not translate anything; strings are built later on admin_menu / render.
		$translated = array();
		$spy        = function ( $translation, $text, $domain ) use ( &$translated ) {
			if ( 'fullworks-scanner' === $domain ) {
				$translated[] = $text;
			}

			return $translation;
		};
		add_filter( 'gettext', $spy, 10, 3 );

		$core = new Core();
		$core->run();

		remove_filter( 'gettext', $spy, 10 );

		$this->assertSame( array(), $translated );
	}

	public function test_default_options_are_created_on_boot() {
		$general = get_option( 'FULLWORKS_SCANNER_general' );
		$this->assertIsArray( $general );
		$this->assertArrayHasKey( 'admin_email', $general );

		$schedule = get_option( 'FULLWORKS_SCANNER_audit_schedule' );
		$this->assertIsArray( $schedule );
		$this->assertSame( '10 02 * * *', $schedule['cron'] );
		$this->assertSame( 1, $schedule['email']['warning'] );
	}

	public function test_activation_creates_audit_table() {
		global $wpdb;
		$table = $this->audit_table();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		$this->assertSame( '1.0', get_option( 'FULLWORKS_SCANNER_db_version' ) );
	}

	public function test_admin_menu_pages_are_registered_for_administrators() {
		global $menu, $submenu;
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$this->login_as_admin();
		$menu    = array();
		$submenu = array();

		do_action( 'admin_menu' );

		$slugs = wp_list_pluck( $menu, 2 );
		$this->assertContains( 'fullworks-scanner-settings', $slugs );
		$this->assertArrayHasKey( 'fullworks-scanner-settings', $submenu );
		$sub_slugs = wp_list_pluck( $submenu['fullworks-scanner-settings'], 2 );
		$this->assertContains( 'fullworks-scanner-code-scan-report', $sub_slugs );

		$tabs = $this->utilities()->get_settings_page_tabs( 'report' );
		$this->assertCount( 1, $tabs );
		$this->assertSame( 'Code Scan', $tabs[0]['title'] );
	}

	public function test_scheduler_hooks_are_registered() {
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_run_plugin_code_scan' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_run_theme_code_scan' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_run_vulndb_scan' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_run_audit_email' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_check_vulndb' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_get_current_plugin' ) );
		$this->assertNotFalse( has_action( 'FULLWORKS_SCANNER_get_current_theme' ) );
	}

	public function test_new_site_hook_uses_wp_initialize_site() {
		$this->assertNotFalse( has_action( 'wp_initialize_site', array( '\Fullworks_Scanner\Includes\Activator', 'on_create_blog_tables' ) ) );
		$this->assertFalse( has_action( 'wpmu_new_blog' ) );
	}
}
