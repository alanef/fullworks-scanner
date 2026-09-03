<?php
/**
 * Settings sanitisation.
 */

use Fullworks_Scanner\Admin\Admin_Settings;

class Test_Admin_Settings extends Scanner_Test_Case {

	/** @var Admin_Settings */
	private $settings;

	public function set_up() {
		parent::set_up();
		$this->login_as_admin();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'fullworks-scanner-options' );
		$this->settings       = new Admin_Settings( 'fullworks-scanner', '0.0.0', $this->utilities() );
		update_option( 'FULLWORKS_SCANNER_audit_schedule', Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_audit_schedule' ) );
		global $wp_settings_errors;
		$wp_settings_errors = array();
	}

	public function test_option_defaults() {
		$this->assertSame( array( 'admin_email' => get_bloginfo( 'admin_email' ) ), Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_general' ) );
		$schedule = Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_audit_schedule' );
		$this->assertSame( '10 02 * * *', $schedule['cron'] );
		$this->assertFalse( Admin_Settings::option_defaults( 'unknown' ) );
	}

	public function test_sanitize_general_sanitises_email() {
		$out = $this->settings->sanitize_general( array( 'admin_email' => ' bad <x>@example.com ' ) );
		$this->assertSame( 'badx@example.com', $out['admin_email'] );
	}

	public function test_changed_valid_cron_flags_requeue() {
		$out = $this->settings->sanitize_audit_schedule( array( 'cron' => '0 3 * * *', 'email' => array( 'warning' => 1 ) ) );
		$this->assertSame( '0 3 * * *', $out['cron'] );
		$this->assertSame( 1, $out['cron_changed'] );
		$errors = get_settings_errors( 'fscron' );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'updated', $errors[0]['type'] );
	}

	public function test_unchanged_cron_does_not_flag_requeue() {
		$out = $this->settings->sanitize_audit_schedule( array( 'cron' => '10 02 * * *' ) );
		$this->assertArrayNotHasKey( 'cron_changed', $out );
		$this->assertSame( array(), get_settings_errors( 'fscron' ) );
	}

	public function test_invalid_cron_is_rejected_and_previous_kept() {
		$out = $this->settings->sanitize_audit_schedule( array( 'cron' => 'every tuesday' ) );
		$this->assertSame( '10 02 * * *', $out['cron'] );
		$errors = get_settings_errors( 'fscron' );
		$this->assertCount( 1, $errors );
		$this->assertSame( 'error', $errors[0]['type'] );
	}

	public function test_blank_cron_disables_scans() {
		$out = $this->settings->sanitize_audit_schedule( array( 'cron' => '' ) );
		$this->assertSame( '', $out['cron'] );
		$this->assertSame( 1, $out['cron_changed'] );
	}

	public function test_cron_macros_are_accepted() {
		$out = $this->settings->sanitize_audit_schedule( array( 'cron' => '@daily' ) );
		$this->assertSame( '@daily', $out['cron'] );
	}

	public function test_invalid_nonce_dies() {
		$_REQUEST['_wpnonce'] = 'bogus';
		$this->expectException( 'WPDieException' );
		$this->settings->sanitize_general( array( 'admin_email' => 'a@example.com' ) );
	}
}
