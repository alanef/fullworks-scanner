<?php
/**
 * Scheduling of scan jobs through Action Scheduler.
 */

use Fullworks_Scanner\Includes\Audit_Action_Scheduler;
use Fullworks_Scanner\Includes\Deactivator;

class Test_Action_Scheduler extends Scanner_Test_Case {

	const GROUP = 'fullworks-scanner-control';

	private $jobs = array(
		'FULLWORKS_SCANNER_run_plugin_code_scan',
		'FULLWORKS_SCANNER_run_theme_code_scan',
		'FULLWORKS_SCANNER_run_vulndb_scan',
		'FULLWORKS_SCANNER_run_audit_email',
	);

	/** @var Audit_Action_Scheduler */
	private $scheduler;

	public function set_up() {
		parent::set_up();
		$this->scheduler = new Audit_Action_Scheduler( $this->utilities() );
		update_option( 'FULLWORKS_SCANNER_audit_schedule', array( 'cron' => '10 02 * * *', 'email' => array( 'warning' => 1 ) ) );
		$this->scheduler->cancel_jobs();
	}

	public function tear_down() {
		$this->scheduler->cancel_jobs();
		parent::tear_down();
	}

	private function assert_jobs_scheduled( $expected ) {
		foreach ( $this->jobs as $job ) {
			$next = as_next_scheduled_action( $job, array(), self::GROUP );
			if ( $expected ) {
				$this->assertNotFalse( $next, "$job should be scheduled" );
			} else {
				$this->assertFalse( $next, "$job should not be scheduled" );
			}
		}
	}

	public function test_schedule_requires_manage_options() {
		wp_set_current_user( 0 );
		$this->scheduler->schedule();
		$this->assert_jobs_scheduled( false );
	}

	public function test_schedule_queues_all_jobs_for_admin() {
		$this->login_as_admin();
		$this->scheduler->schedule();
		$this->assert_jobs_scheduled( true );

		// Idempotent.
		$this->scheduler->schedule();
		$this->assertSame( 1, count( as_get_scheduled_actions( array( 'hook' => 'FULLWORKS_SCANNER_run_vulndb_scan', 'group' => self::GROUP, 'status' => 'pending' ), 'ids' ) ) );
	}

	public function test_blank_cron_schedules_nothing() {
		$this->login_as_admin();
		update_option( 'FULLWORKS_SCANNER_audit_schedule', array( 'cron' => '' ) );
		$this->scheduler->schedule();
		$this->assert_jobs_scheduled( false );
	}

	public function test_cron_changed_flag_cancels_and_requeues() {
		$this->login_as_admin();
		$this->scheduler->schedule();
		$before = as_next_scheduled_action( 'FULLWORKS_SCANNER_run_vulndb_scan', array(), self::GROUP );

		update_option( 'FULLWORKS_SCANNER_audit_schedule', array( 'cron' => '30 04 * * *', 'cron_changed' => 1 ) );
		$this->scheduler->schedule();

		$after   = as_next_scheduled_action( 'FULLWORKS_SCANNER_run_vulndb_scan', array(), self::GROUP );
		$options = get_option( 'FULLWORKS_SCANNER_audit_schedule' );
		$this->assertSame( 0, $options['cron_changed'] );
		$this->assertNotFalse( $after );
		$this->assertNotEquals( $before, $after );
	}

	public function test_immediate_jobs_skip_email() {
		$this->scheduler->add_immediate_jobs();
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_run_plugin_code_scan', array(), self::GROUP ) );
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_run_theme_code_scan', array(), self::GROUP ) );
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_run_vulndb_scan', array(), self::GROUP ) );
		$this->assertFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_run_audit_email', array(), self::GROUP ) );
	}

	public function test_rescan_requires_valid_nonce() {
		$this->login_as_admin();
		$_REQUEST['rescan']   = '1';
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'fullworks_scanner_rescan' );
		$this->scheduler->rescan();
		unset( $_REQUEST['rescan'] );
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_run_vulndb_scan', array(), self::GROUP ) );
	}

	public function test_deactivation_unschedules_everything() {
		$this->login_as_admin();
		$this->scheduler->schedule();
		$this->assert_jobs_scheduled( true );
		Deactivator::deactivate();
		$this->assert_jobs_scheduled( false );
	}

	public function test_group_completion_waits_for_outstanding_audit_jobs() {
		$this->assertSame( 0, Audit_Action_Scheduler::count_outstanding_by_group( 'FULLWORKS_SCANNER_audit' ) );
		$this->assertTrue( Audit_Action_Scheduler::check_group_complete( 'FULLWORKS_SCANNER_audit', 'FULLWORKS_SCANNER_email_check_audit_complete' ) );

		as_schedule_single_action( time() + HOUR_IN_SECONDS, 'FULLWORKS_SCANNER_check_vulndb', array( 'endpoint' => 0 ), 'FULLWORKS_SCANNER_audit' );

		$this->assertSame( 1, Audit_Action_Scheduler::count_outstanding_by_group( 'FULLWORKS_SCANNER_audit' ) );
		$this->assertFalse( Audit_Action_Scheduler::check_group_complete( 'FULLWORKS_SCANNER_audit', 'FULLWORKS_SCANNER_email_check_audit_complete' ) );
		// The completion check is re-queued in the control group.
		$this->assertNotFalse( as_next_scheduled_action( 'FULLWORKS_SCANNER_email_check_audit_complete', array(), self::GROUP ) );
	}
}
