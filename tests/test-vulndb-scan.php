<?php
/**
 * Vulnerability database scan (HTTP mocked).
 */

use Fullworks_Scanner\Includes\Audit_VulnDB_Scan;

class Test_VulnDB_Scan extends Scanner_Test_Case {

	/** @var Audit_VulnDB_Scan */
	private $scan;

	public function set_up() {
		parent::set_up();
		$this->scan = new Audit_VulnDB_Scan( $this->utilities() );
		set_transient(
			'fullworks_vulndb_control',
			array(
				0 => array(
					'slug' => 'some-plugin',
					'type' => 'plugin',
					'data' => array( 'Name' => 'Some Plugin', 'Version' => '1.0' ),
					'name' => 'Some Plugin',
				),
				1 => array(
					'slug' => 'twentytwentyfive',
					'type' => 'theme',
					'data' => wp_get_theme( 'twentytwentyfive' ),
					'name' => 'Twenty Twenty-Five',
				),
			),
			DAY_IN_SECONDS
		);
	}

	private function vuln_response( $max_version, $operator = '<=', $count = 1 ) {
		$vulns = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$vulns[] = array(
				'operator' => array( 'max_version' => $max_version, 'max_operator' => $operator ),
				'source'   => array( array( 'name' => "CVE-$i", 'link' => "https://example.com/cve-$i" ) ),
			);
		}

		return array( 'data' => array( 'vulnerability' => $vulns ) );
	}

	public function test_vulnerable_plugin_version_is_recorded() {
		$this->mock_http( 'wpvulnerability.net/plugin/some-plugin', $this->vuln_response( '1.5' ) );
		$this->scan->check_vulndb( 0 );

		$rows = $this->audit_rows( 995 );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'Some Plugin', $rows[0]['filepath'] );
		$this->assertSame( 'plugin', $rows[0]['type'] );
		$this->assertStringContainsString( 'Vulnerability in installed version: 1.0', $rows[0]['message'] );
		$this->assertStringContainsString( 'https://example.com/cve-1', $rows[0]['message'] );
	}

	public function test_patched_plugin_version_is_not_recorded() {
		$this->mock_http( 'wpvulnerability.net/plugin/some-plugin', $this->vuln_response( '0.9' ) );
		$this->scan->check_vulndb( 0 );
		$this->assertCount( 0, $this->audit_rows() );
	}

	public function test_no_vulnerabilities_records_nothing() {
		$this->mock_http( 'wpvulnerability.net/plugin/some-plugin', array( 'data' => array( 'vulnerability' => array() ) ) );
		$this->scan->check_vulndb( 0 );
		$this->assertCount( 0, $this->audit_rows() );
	}

	public function test_multiple_vulnerabilities_are_listed() {
		$this->mock_http( 'wpvulnerability.net/plugin/some-plugin', $this->vuln_response( '2.0', '<', 2 ) );
		$this->scan->check_vulndb( 0 );

		$rows = $this->audit_rows( 995 );
		$this->assertCount( 1, $rows );
		$this->assertStringContainsString( 'Multiple Vulnerabilities', $rows[0]['message'] );
		$this->assertStringContainsString( '<ol>', $rows[0]['message'] );
		$this->assertStringContainsString( 'CVE-2', $rows[0]['message'] );
	}

	public function test_api_error_records_nothing() {
		$this->mock_http( 'wpvulnerability.net/plugin/some-plugin', 'boom', 500 );
		$this->scan->check_vulndb( 0 );
		$this->assertCount( 0, $this->audit_rows() );
	}

	public function test_theme_uses_theme_object_version() {
		$theme = wp_get_theme( 'twentytwentyfive' );
		if ( ! $theme->exists() ) {
			$this->markTestSkipped( 'twentytwentyfive theme not installed' );
		}
		$this->mock_http( 'wpvulnerability.net/theme/twentytwentyfive', $this->vuln_response( '99.0' ) );
		$this->scan->check_vulndb( 1 );

		$rows = $this->audit_rows( 995 );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'theme', $rows[0]['type'] );
		$this->assertStringContainsString( 'installed version: ' . $theme->get( 'Version' ), $rows[0]['message'] );
	}

	public function test_run_queues_a_check_per_core_plugin_and_theme() {
		delete_transient( 'fullworks_vulndb_control' );
		$this->scan->run();
		$endpoints = get_transient( 'fullworks_vulndb_control' );
		$this->assertIsArray( $endpoints );
		$types = array_count_values( wp_list_pluck( $endpoints, 'type' ) );
		$this->assertSame( 1, $types['core'] );
		$this->assertGreaterThanOrEqual( 1, $types['plugin'] );
		$this->assertGreaterThanOrEqual( 1, $types['theme'] );

		$pending = as_get_scheduled_actions( array( 'hook' => 'FULLWORKS_SCANNER_check_vulndb', 'group' => 'FULLWORKS_SCANNER_audit', 'status' => 'pending', 'per_page' => 100 ), 'ids' );
		$this->assertCount( count( $endpoints ), $pending );
	}
}
