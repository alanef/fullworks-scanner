<?php
/**
 * Utilities: audit log writes, counts and white label.
 */

class Test_Utilities extends Scanner_Test_Case {

	public function test_get_instance_is_a_singleton() {
		$this->assertSame( $this->utilities(), \Fullworks_Scanner\Includes\Utilities::get_instance() );
	}

	public function test_white_label_defaults() {
		$white_label = $this->utilities()->get_white_label();
		$this->assertSame( 'Fullworks Scanner', $white_label['title'] );
		$this->assertStringContainsString( 'admin/images/brand/', $white_label['logo'] );
		$this->assertSame( 'Fullworks Scanner', $this->utilities()->get_plugin_title() );
	}

	public function test_file_scan_log_write_inserts_and_then_updates() {
		$u = $this->utilities();

		$u->file_scan_log_write( 'Some Plugin', 498, 'plugin', 'TestOrigin', 'first message' );
		$rows = $this->audit_rows();
		$this->assertCount( 1, $rows );
		$this->assertSame( 'Some Plugin', $rows[0]['filepath'] );
		$this->assertSame( '498', $rows[0]['status'] );
		$this->assertSame( 'plugin', $rows[0]['type'] );
		$this->assertSame( 'TestOrigin', $rows[0]['origin'] );
		$this->assertSame( 'first message', $rows[0]['message'] );
		$this->assertSame( '0', $rows[0]['accept'] );

		// Same file/status/origin updates the message rather than adding a row.
		$u->file_scan_log_write( 'Some Plugin', 498, 'plugin', 'TestOrigin', 'second message' );
		$rows = $this->audit_rows();
		$this->assertCount( 1, $rows );
		$this->assertSame( 'second message', $rows[0]['message'] );

		// A different status is a new row.
		$u->file_scan_log_write( 'Some Plugin', 995, 'plugin', 'TestOrigin', 'vuln' );
		$this->assertCount( 2, $this->audit_rows() );
	}

	public function test_record_counts_and_bubble() {
		$u = $this->utilities();
		$this->assertSame( '0', $u->get_type_record_count( '0' ) );
		$this->assertSame( '', $u->get_count_bubble() );

		$u->file_scan_log_write( 'A', 999, 'plugin', 'X' );
		$u->file_scan_log_write( 'B', 496, 'theme', 'X' );

		$this->assertSame( '2', $u->get_type_record_count( '0' ) );
		$this->assertSame( '0', $u->get_type_record_count( '1' ) );
		$this->assertStringContainsString( '<span class="awaiting-mod">2</span>', $u->get_count_bubble() );
	}

	public function test_clear_all_unaccepted_file_scan_keeps_accepted_rows() {
		global $wpdb;
		$u = $this->utilities();
		$u->file_scan_log_write( 'A', 999, 'plugin', 'OriginA' );
		$u->file_scan_log_write( 'B', 999, 'plugin', 'OriginA' );
		$u->file_scan_log_write( 'C', 999, 'theme', 'OriginA' );
		$u->file_scan_log_write( 'D', 999, 'plugin', 'OriginB' );

		$rows = $this->audit_rows();
		$wpdb->update( $this->audit_table(), array( 'accept' => 1 ), array( 'ID' => $rows[0]['ID'] ) );

		$u->clear_all_unaccepted_file_scan( 'plugin', 'OriginA' );

		$remaining = wp_list_pluck( $this->audit_rows(), 'filepath' );
		$this->assertSame( array( 'A', 'C', 'D' ), $remaining );
	}

	public function test_get_issues_covers_all_status_codes() {
		$issues = $this->utilities()->get_issues();
		foreach ( array( 999, 995, 498, 497, 496, 495, 494, 493 ) as $code ) {
			$this->assertArrayHasKey( $code, $issues );
		}
	}

	public function test_settings_page_tabs_unknown_page_is_empty() {
		$this->assertSame( array(), $this->utilities()->get_settings_page_tabs( 'nope' ) );
	}

	public function test_call_vuln_data_api_decodes_json() {
		$this->mock_http( 'wpvulnerability.net/plugin/foo', array( 'data' => array( 'vulnerability' => array() ) ) );
		$result = $this->utilities()->call_vuln_data_api( 'https://www.wpvulnerability.net/plugin/foo' );
		$this->assertSame( array( 'data' => array( 'vulnerability' => array() ) ), $result );
	}

	public function test_call_vuln_data_api_returns_wp_error_on_http_error() {
		$this->mock_http( 'wpvulnerability.net/plugin/foo', 'nope', 503 );
		$result = $this->utilities()->call_vuln_data_api( 'https://www.wpvulnerability.net/plugin/foo' );
		$this->assertWPError( $result );
		$this->assertSame( 503, $result->get_error_code() );
	}
}
