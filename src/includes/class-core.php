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


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 */

namespace Fullworks_Scanner\Includes;

use Fullworks_Scanner\Admin\Admin;
use Fullworks_Scanner\Admin\Admin_Settings;
use Fullworks_Scanner\Admin\Admin_Table_Code_Scan;
use Fullworks_Scanner\FrontEnd\FrontEnd;
use WP_CLI;


class Core {
	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name = FULLWORKS_SCANNER_PLUGIN_VERSION;

	/**
	 * The current version of the plugin.
	 */
	protected $version = FULLWORKS_SCANNER_PLUGIN_VERSION;

	protected $log_and_block;




	/** @var Utilities $utilities */
	protected $utilities;


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 *
	 */
	public function __construct() {
		$this->utilities = new Utilities();
	}

	/**
	 * Run the plugin
	 *
	 */
	public function run() {

		$this->set_options_data();
		$this->set_locale();
		$this->settings_pages();

		$this->define_admin_hooks();
		$this->define_core_hooks();

		$this->do_cli();
	}

	private function do_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command(
				'fullworks-scanner',
				function () {

						$plugin = new Audit_Plugin_Code_Scan( $this->utilities );
						$plugin->run();
						$vuln = new Audit_VulnDB_Scan( $this->utilities );
						$vuln->run();
						if ($this->utilities->is_issues_found()) {
							WP_CLI::error( esc_html__('Issues found!','fullworks-scanner' ) );
						} else {
							WP_CLI::success( esc_html__('No issues found','fullworks-scanner' ));
					}
				}
			);
		}
	}

	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	private function set_options_data() {

		if ( ! get_option( 'FULLWORKS_SCANNER_general' ) ) {
			update_option( 'FULLWORKS_SCANNER_general', Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_general' ) );
		}
		if ( ! get_option( 'FULLWORKS_SCANNER_audit_schedule' ) ) {
			update_option( 'FULLWORKS_SCANNER_audit_schedule', Admin_Settings::option_defaults( 'FULLWORKS_SCANNER_audit_schedule' ) );
		}
	}


	private function settings_pages() {
		// settings page
		$settings = new Admin_Settings( $this->get_plugin_name(), $this->get_version(), $this->utilities );
		add_action( 'admin_menu', array( $settings, 'settings_setup' ) );
		// report pages
		$codescan = new Admin_Table_Code_Scan( $this->get_plugin_name(), $this->get_version() );
		add_filter( 'set-screen-option', array( $codescan, 'set_screen' ), 10, 3 );
		add_action( 'admin_menu', array( $codescan, 'add_table_page' ) );
	}


	public function get_plugin_name() {
		return $this->plugin_name;
	}


	public function get_version() {
		return $this->version;
	}


	private function define_admin_hooks() {
		$plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version(), $this->utilities );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $plugin_admin, 'upgrade_db' ) );
		add_action( 'upgrader_process_complete', array( $plugin_admin,'plugin_updated_action'), 10, 2 );
	}


	private function define_core_hooks() {

		$action_scheduler = new Audit_Action_Scheduler(  $this->utilities );
		add_action( 'init', array( $action_scheduler, 'schedule' ) );
		add_action( 'init', array( $action_scheduler, 'rescan' ) );
	}

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'fullworks-scanner',
			false,
			FULLWORKS_SCANNER_PLUGIN_DIR . 'languages/'
		);

	}

}
