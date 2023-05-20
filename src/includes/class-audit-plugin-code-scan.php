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


class Audit_Plugin_Code_Scan {

	/** @var Event_Notifier $notifier */
	protected $notifier;
	/** @var Utilities $utilities */
	protected $utilities;


	/**
	 * Audit_Plugin_Code_Scan constructor.
	 *
	 * @param $notifier
	 * @param $utilities
	 */
	public function __construct( $notifier, $utilities ) {
		$this->notifier        = $notifier;
		$this->utilities       = $utilities;
		add_action( 'FULLWORKS_SCANNER_get_current_plugin', array( $this, 'get_current_plugin' ) );
		add_action( 'FULLWORKS_SCANNER_audit_plugin_scan_chunk', array( $this, 'scan_chunk' ) );
	}

	/**
	 *
	 */
	public function run() {

		// clear down
		$this->utilities->clear_all_unaccepted_file_scan( 'plugin', __CLASS__ );
		delete_transient( 'fullworks-vulnerability-plugin-data' );
		$updates     = get_site_transient( 'update_plugins' );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins     = get_plugins();
		$plugin_data = array();
		foreach ( $plugins as $key => $plugin ) {
			$plugin_data[ dirname( $key ) ]['data']         = $plugin;
			$plugin_data[ dirname( $key ) ]['data']['filename']     = $key;
			$plugin_data[ dirname( $key ) ]['data']['repo'] = false;   // assume false until processed
			if ( isset( $updates->response[$key] ) ) {
				$plugin_data[ dirname( $key ) ]['data']['update'] = $updates->response[$key];
			}
		}
		set_transient( 'fullworks-vulnerability-plugin-data', $plugin_data, DAY_IN_SECONDS );
		foreach ( $plugins as $key => $plugin ) {
			if ( false === as_next_scheduled_action( 'FULLWORKS_SCANNER_get_current_plugin', array( 'plugin' => dirname( $key ) ), 'fFULLWORKS_SCANNER_audit' ) ) {
				as_schedule_single_action( time(), 'FULLWORKS_SCANNER_get_current_plugin', array( 'plugin' => dirname( $key ) ), 'FULLWORKS_SCANNER__audit' );
			}
		}
	}


	/**
	 * @param $plugin
	 */
	public function get_current_plugin( $plugin ) {
		$plugin_data                            = get_transient( 'fullworks-vulnerability-plugin-data' );
		$plugin_data[ $plugin ]['data']['repo'] = true;
		$plugin_info                            = $this->utilities->wp_api( 'https://api.wordpress.org/plugins/info/1.0/' . $plugin . '.json' );
		if ( is_wp_error( $plugin_info ) ) {
			//  if not found then record as a non repo plugin - @TODO is this right - localization
			if ( 'Plugin not found.' == $plugin_info->get_error_message() ) {
				// now check the repo if it still has svn then .....  it has been removed
				$check = $this->utilities->wp_api( 'https://plugins.svn.wordpress.org/' . $plugin, array(), false );
				if ( is_wp_error( $check ) ) {
					$plugin_data[ $plugin ]['data']['repo'] = false;
				} else {
					// report abandoned
					$this->utilities->file_scan_log_write( $plugin, 497, 'plugin', __CLASS__,  esc_html__( 'This has been removed from the WordPress repository', 'fullworks-scanner' )  );

					return;
				}
			} else {
				$this->utilities->error_log( array(
					'Message' => 'Unexpected Error in checking plugin: ' . $plugin,
					'Data'    => $plugin_info
				) );

				return;
			}
		}
		if ( $plugin_data[ $plugin ]['data']['repo'] ) {
			// get list of WP releases to check if it is tested for recent releases
			$releases = $this->utilities->wp_api( 'http://api.wordpress.org/core/stable-check/1.0/' );
			if ( is_wp_error( $releases ) ) {
				$this->utilities->error_log( $releases );

				return;
			}
			// convert releases objet to array
			$releases = (array) $releases;
			end( $releases );
			// get latest release  from object as the last key
			$latest     = key( $releases );
			$v1x        = explode( '.', $latest );
			$v2x        = explode( '.', $plugin_info->tested );
			$latest_num = $v1x[0] * 10 + $v1x[1];
			$tested_num = $v2x[0] * 10 + $v2x[1];
			if ( $tested_num < $latest_num - 3 ) {
				// possibly abandoned
				$this->utilities->file_scan_log_write( $plugin, 496, 'plugin', __CLASS__,  esc_html__( 'Maybe abandoned, not updated in the last 3 major releases of WordPress', 'fullworks-scanner' ) );
			}

			// check for updates
			if ( isset($plugin_data[ $plugin ]['data']['update'] ) ) {
				// report not latest
				// translators: %s is the version number
				$this->utilities->file_scan_log_write( $plugin, 498, 'plugin', __CLASS__, sprintf( esc_html__( 'Installed version %1$s - Current version %2$s', 'fullworks-scanner' ), $plugin_data[ $plugin ]['data']['Version'], $plugin_data[ $plugin ]['data']['update']->new_version ) );  // Not latest
			}
		}
	}

}
