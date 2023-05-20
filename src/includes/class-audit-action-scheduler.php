<?php
/*
 *  @copyright (c) 2023.
 *  @author     Alan Fuller (support@fullworksplugins.com)
 *  @licence    GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 *  @link       https://fullworksplugins.com
 *
 *  This file is part of a Fullworks' Plugin.
 *
 *  This WordPress plugin  is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This WordPress plugin  is  distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  Any copying of any part of this code not in compliance with the licence terms is strictly prohibited.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with the plugin.  https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace Fullworks_Scanner\Includes;

use ActionScheduler;

/**
 * Class Audit_Action_Scheduler
 * @package Fullworks_Scanner\Includes
 */
class Audit_Action_Scheduler {

	/** @var Event_Notifier $notifier */
	protected $notifier;
	/** @var Utilities $utilities */
	protected $utilities;

	protected $jobs = array(
		'\Fullworks_Scanner\Includes\Audit_Plugin_Code_Scan' => 'FULLWORKS_SCANNER_run_plugin_code_scan',
		'\Fullworks_Scanner\Includes\Audit_Theme_Code_Scan'  => 'FULLWORKS_SCANNER_run_theme_code_scan',
		'\Fullworks_Scanner\Includes\Audit_VulnDB_Scan'      => 'FULLWORKS_SCANNER_run_vulndb_scan',
		'\Fullworks_Scanner\Includes\Audit_Email'            => 'FULLWORKS_SCANNER_run_audit_email',
	);

	private $options;

	/**
	 * Action_Scheduler constructor.
	 *
	 * @param $notifier
	 * @param $utilities
	 */
	public function __construct( $notifier, $utilities ) {
		$this->notifier  = $notifier;
		$this->utilities = $utilities;
		foreach ( $this->jobs as $class => $job ) {
			$class_instance = new $class( $this->notifier, $this->utilities );
		    add_action( $job, array( $class_instance, 'run' ) );
		}
	}

	/**
	 * schedule required jobs
	 */
	public function schedule() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->options = get_option( 'fullworks-scanner-audit-schedule' );
		if ( empty ( $this->options['cron'] ) ) {
			$this->cancel_jobs();

			return;
		}

		if ( isset( $this->options['cron_changed'] ) && 1 == $this->options['cron_changed'] ) {
			$this->options['cron_changed'] = 0;
			update_option( 'fullworks-scanner-audit-schedule', $this->options );
			$this->cancel_jobs();
			$this->add_jobs();

			return;
		}
		/**
		 * Handle the code audit scans
		 */

		$this->add_jobs();
	}

	public function cancel_jobs() {
		foreach ( $this->jobs as $class => $job ) {
			if ( false !== as_next_scheduled_action( $job, array(), 'fullworks-scanner-control' ) ) {
				as_unschedule_action( $job, array(), 'fullworks-scanner-control' );
			}
		}
	}

	public function add_jobs() {
		foreach ( $this->jobs as $class => $job ) {
			if ( false === as_next_scheduled_action( $job, array(), 'fullworks-scanner-control' ) ) {
				as_schedule_cron_action( time(), $this->options['cron'], $job, array(), 'fullworks-scanner-control' );
			}
		}
	}


	/**
	 * Counts action schedule jobs by group
	 * for pending and in-progress so a job can run when all complete or failed
	 *
	 * @param $group
	 *
	 * @return array
	 */
	public static function count_outstanding_by_group( $group ) {
		$args           = array(
			'group'    => $group,
			'per_page' => - 1,
			'status'   => 'pending',
		);
		$pending        = ActionScheduler::store()->query_actions( $args, 'count' );
		$args['status'] = 'in - progress';
		$in_progress    = ActionScheduler::store()->query_actions( $args, 'count' );

		return $pending + $in_progress;
	}

	/**
	 * Utility function to allow dependent jobs in ActionScheduler by monitoring group
	 *
	 * @param $group
	 *
	 * @return bool
	 */
	public static function check_group_complete( $groups, $job ) {
		if ( ! is_array( $groups ) ) {
			$groups = array( $groups );
		}
		$count = 0;
		foreach ( $groups as $group ) {
			$count += self::count_outstanding_by_group( $group );
		}
		if ( $count > 0 ) {
			// requeue  as not done yet
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- inside debug if.
				error_log( __FUNCTION__ . ' requeue as count ' . $count );
			}
			if ( false === as_next_scheduled_action( $job, array(), 'fullworks - security - control' ) ) {
				as_schedule_single_action( time() + 60 + ( $count * 5 ), $job, array(), 'fullworks - security - control' );
			}

			return false;
		}

		return true;
	}
}
