<?php
/**
 * Plugin repository checks: removed, abandoned and outdated plugins (HTTP mocked).
 */

use Fullworks_Scanner\Includes\Audit_Plugin_Code_Scan;

class Test_Plugin_Code_Scan extends Scanner_Test_Case {

	/** @var Audit_Plugin_Code_Scan */
	private $scan;

	public function set_up() {
		parent::set_up();
		$this->scan = new Audit_Plugin_Code_Scan( $this->utilities() );
		$this->mock_http( 'api.wordpress.org/core/stable-check', array( '6.8' => 'outdated', '7.0' => 'outdated', '7.1' => 'latest' ) );
	}

	private function set_plugin_data( $extra = array() ) {
		set_transient(
			'fullworks-vulnerability-plugin-data',
			array(
				'some-plugin' => array(
					'data' => array_merge(
						array(
							'Name'     => 'Some Plugin',
							'Version'  => '1.0',
							'filename' => 'some-plugin/some-plugin.php',
							'repo'     => false,
						),
						$extra
					),
				),
			),
			DAY_IN_SECONDS
		);
	}

	public function test_up_to_date_repo_plugin_records_nothing() {
		$this->set_plugin_data();
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '7.1' ) );
		$this->scan->get_current_plugin( 'some-plugin' );
		$this->assertCount( 0, $this->audit_rows() );
	}

	public function test_plugin_removed_from_repository_is_flagged() {
		$this->set_plugin_data();
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'error' => 'Plugin not found.' ) );
		$this->mock_http( 'plugins.svn.wordpress.org/some-plugin', '<html>svn listing</html>' );
		$this->scan->get_current_plugin( 'some-plugin' );

		$rows = $this->audit_rows( 497 );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'Some Plugin', $rows[0]['filepath'] );
		$this->assertStringContainsString( 'removed from the WordPress repository', $rows[0]['message'] );
	}

	public function test_non_repository_plugin_is_ignored() {
		$this->set_plugin_data();
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'error' => 'Plugin not found.' ) );
		$this->mock_http( 'plugins.svn.wordpress.org/some-plugin', 'not found', 404 );
		$this->scan->get_current_plugin( 'some-plugin' );
		$this->assertCount( 0, $this->audit_rows() );
	}

	public function test_plugin_not_tested_for_three_major_releases_is_flagged_abandoned() {
		$this->set_plugin_data();
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '6.4' ) );
		$this->scan->get_current_plugin( 'some-plugin' );

		$rows = $this->audit_rows( 496 );
		$this->assertCount( 1, $rows );
		$this->assertStringContainsString( 'Maybe abandoned', $rows[0]['message'] );
	}

	public function test_plugin_tested_within_three_releases_is_not_flagged() {
		$this->set_plugin_data();
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '6.8' ) );
		$this->scan->get_current_plugin( 'some-plugin' );
		$this->assertCount( 0, $this->audit_rows( 496 ) );
	}

	public function test_available_update_is_reported_with_changelog() {
		$this->set_plugin_data( array( 'update' => (object) array( 'new_version' => '1.1' ) ) );
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '7.1' ) );
		$this->mock_http( 'plugins.svn.wordpress.org/some-plugin/tags/1.1/readme.txt', "== Changelog ==\n= 1.1 =\n* Fixed the thing\n* Added another thing\n= 1.0 =\n* Initial\n" );
		$this->scan->get_current_plugin( 'some-plugin' );

		$rows = $this->audit_rows( 498 );
		$this->assertCount( 1, $rows );
		$this->assertStringContainsString( 'Installed version 1.0 - Current version 1.1', $rows[0]['message'] );
		$this->assertStringContainsString( '<li>Fixed the thing</li>', $rows[0]['message'] );
		$this->assertStringContainsString( '<li>Added another thing</li>', $rows[0]['message'] );
		$this->assertStringNotContainsString( 'Initial', $rows[0]['message'] );
		$this->assertStringContainsString( 'wordpress.org/plugins/some-plugin/developers/', $rows[0]['message'] );
	}

	public function test_auto_updating_plugin_updated_recently_is_not_reported() {
		$this->set_plugin_data( array( 'update' => (object) array( 'new_version' => '1.1' ) ) );
		update_site_option( 'auto_update_plugins', array( 'some-plugin/some-plugin.php' ) );
		update_site_option( 'FULLWORKS_SCANNER_plugin_updated_some-plugin/some-plugin.php', time() - HOUR_IN_SECONDS );
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '7.1' ) );

		$this->scan->get_current_plugin( 'some-plugin' );
		$this->assertCount( 0, $this->audit_rows( 498 ) );
	}

	public function test_auto_updating_plugin_stuck_for_days_is_reported() {
		$this->set_plugin_data( array( 'update' => (object) array( 'new_version' => '1.1' ) ) );
		update_site_option( 'auto_update_plugins', array( 'some-plugin/some-plugin.php' ) );
		update_site_option( 'FULLWORKS_SCANNER_plugin_updated_some-plugin/some-plugin.php', time() - 3 * DAY_IN_SECONDS );
		$this->mock_http( 'plugins/info/1.0/some-plugin.json', array( 'name' => 'Some Plugin', 'tested' => '7.1' ) );
		$this->mock_http( 'plugins.svn.wordpress.org/some-plugin/tags/1.1/readme.txt', "== Changelog ==\n= 1.1 =\n* Fixed\n" );

		$this->scan->get_current_plugin( 'some-plugin' );
		$rows = $this->audit_rows( 498 );
		$this->assertCount( 1, $rows );
		$this->assertStringContainsString( 'Auto update is enabled but seems not to be working', $rows[0]['message'] );
	}

	public function test_plugin_updated_action_records_update_time() {
		$admin = new \Fullworks_Scanner\Admin\Admin( 'fullworks-scanner', '0', $this->utilities() );
		$admin->plugin_updated_action( null, array( 'action' => 'update', 'type' => 'plugin', 'plugins' => array( 'some-plugin/some-plugin.php' ) ) );
		$this->assertEqualsWithDelta( time(), get_site_option( 'FULLWORKS_SCANNER_plugin_updated_some-plugin/some-plugin.php' ), 5 );
	}

	public function test_run_queues_a_job_per_installed_plugin() {
		$this->scan->run();
		$data = get_transient( 'fullworks-vulnerability-plugin-data' );
		$this->assertArrayHasKey( 'fullworks-scanner', $data );
		$this->assertSame( 'fullworks-scanner/fullworks-vulnerability-scanner.php', $data['fullworks-scanner']['data']['filename'] );

		$pending = as_get_scheduled_actions( array( 'hook' => 'FULLWORKS_SCANNER_get_current_plugin', 'group' => 'FULLWORKS_SCANNER_audit', 'status' => 'pending', 'per_page' => 100 ), 'ids' );
		$this->assertCount( count( $data ), $pending );
	}
}
