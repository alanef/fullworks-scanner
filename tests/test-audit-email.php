<?php
/**
 * Summary email.
 */

use Fullworks_Scanner\Includes\Audit_Email;

class Test_Audit_Email extends Scanner_Test_Case {

	/** @var Audit_Email */
	private $email;

	public function set_up() {
		parent::set_up();
		reset_phpmailer_instance();
		$this->email = new Audit_Email( $this->utilities() );
		update_option( 'FULLWORKS_SCANNER_general', array( 'admin_email' => 'security@example.org' ) );
		update_option( 'FULLWORKS_SCANNER_audit_schedule', array( 'cron' => '10 02 * * *', 'email' => array( 'warning' => 1 ) ) );
	}

	public function tear_down() {
		reset_phpmailer_instance();
		parent::tear_down();
	}

	public function test_no_issues_sends_nothing() {
		$this->email->send_email();
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_critical_issue_emails_the_configured_admin() {
		$this->utilities()->file_scan_log_write( 'Some Plugin', 995, 'plugin', 'T', 'vuln' );
		$this->email->send_email();

		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotFalse( $sent );
		$this->assertSame( 'security@example.org', $sent->to[0][0] );
		$this->assertStringContainsString( 'Issues found during code scan of', $sent->subject );
		$this->assertStringContainsString( 'Number of critical issues: 1', $sent->body );
		$this->assertStringContainsString( 'Number of warnings: 0', $sent->body );
		$this->assertStringContainsString( 'page=fullworks-scanner-code-scan-report', $sent->body );
	}

	public function test_warning_only_is_emailed_when_warnings_enabled() {
		$this->utilities()->file_scan_log_write( 'Some Plugin', 498, 'plugin', 'T', 'update' );
		$this->email->send_email();
		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertNotFalse( $sent );
		$this->assertStringContainsString( 'Number of warnings: 1', $sent->body );
	}

	public function test_warning_only_is_suppressed_when_warnings_disabled() {
		update_option( 'FULLWORKS_SCANNER_audit_schedule', array( 'cron' => '10 02 * * *', 'email' => array( 'warning' => 0 ) ) );
		$this->utilities()->file_scan_log_write( 'Some Plugin', 498, 'plugin', 'T', 'update' );
		$this->email->send_email();
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_accepted_issues_are_not_counted() {
		global $wpdb;
		$this->utilities()->file_scan_log_write( 'Some Plugin', 995, 'plugin', 'T', 'vuln' );
		$wpdb->update( $this->audit_table(), array( 'accept' => 1 ), array( 'filepath' => 'Some Plugin' ) );
		$this->email->send_email();
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_message_filters_are_applied() {
		$this->utilities()->file_scan_log_write( 'Some Plugin', 995, 'plugin', 'T', 'vuln' );
		add_filter( 'fvs_mail_subject_send_email', function () { return 'Custom subject'; } );
		$this->email->send_email();
		$this->assertSame( 'Custom subject', tests_retrieve_phpmailer_instance()->get_sent()->subject );
	}

	public function test_run_schedules_completion_check() {
		$this->email->run();
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_email_check_audit_complete', array(), 'fullworks-scanner-control' ) );
	}
}
