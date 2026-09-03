<?php
/**
 * Uninstall clean-up. DDL commits the test transaction, so the table is recreated afterwards.
 */

use Fullworks_Scanner\Admin\Admin_Settings;
use Fullworks_Scanner\Includes\Activator;
use Fullworks_Scanner\Includes\Uninstall;

class Test_Uninstall extends Scanner_Test_Case {

	public function set_up() {
		parent::set_up();
		// The core test case rewrites CREATE/DROP TABLE to TEMPORARY tables; uninstall needs the real thing.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	public function tear_down() {
		Activator::create_tables();
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		update_option( 'FULLWORKS_SCANNER_general', Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_general' ) );
		update_option( 'FULLWORKS_SCANNER_audit_schedule', Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_audit_schedule' ) );
		parent::tear_down();
	}

	public function test_uninstall_drops_table_and_removes_options() {
		global $wpdb;
		$table = $this->audit_table();
		update_option( 'FULLWORKS_SCANNER_something', 'x' );

		Uninstall::uninstall( false );

		$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );
		$this->assertFalse( get_option( 'FULLWORKS_SCANNER_general' ) );
		$this->assertFalse( get_option( 'FULLWORKS_SCANNER_audit_schedule' ) );
		$this->assertFalse( get_option( 'FULLWORKS_SCANNER_db_version' ) );
		$this->assertFalse( get_option( 'FULLWORKS_SCANNER_something' ) );
	}
}
