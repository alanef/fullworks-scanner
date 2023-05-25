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


require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';


/**
 * Class Audit_Theme_Code_Scan
 * @package Fullworks_Security\Includes
 */
class Audit_Theme_Code_Scan {
	/**
	 * Protected
	 *
	 * @var Event_Notifier $notifier Notifier.
	 */
	protected $notifier;
	/**
	 * Protected
	 *
	 * @var Utilities $utilities Utilities.
	 */
	protected $utilities;


	/**
	 * Audit_Theme_Code_Scan constructor.
	 *
	 * @param Event_Notifier $notifier Notifier object.
	 * @param Utilities      $utilities General Utilities.
	 */
	public function __construct( $notifier, $utilities ) {
		$this->notifier        = $notifier;
		$this->utilities       = $utilities;
		add_action( 'FULLWORKS_SCANNER_get_current_theme', array( $this, 'get_current_theme' ) );
	}

	/**
	 * Clear down and schedule scans in chunks
	 */
	public function run() {

		// clear down.
		$this->utilities->clear_all_unaccepted_file_scan( 'theme', __CLASS__ );
		delete_transient( 'fullworks-scanner-theme-data' );
		$updates     = get_site_transient( 'update_themes' );
		$themes     = wp_get_themes( array( 'errors' => null ) );
		$theme_data = array();
		foreach ( $themes as $key => $theme ) {
			$theme_data[ $key ]['data']['theme_object'] = $theme;
			$theme_data[ $key ]['data']['repo']         = false;   // assume false until processed.
			if ( isset( $updates->response[$key] ) ) {
				$theme_data[ $key ]['data']['update'] = $updates->response[$key];
			}
		}
		set_transient( 'fullworks-scanner-theme-data', $theme_data, DAY_IN_SECONDS );
		foreach ( $themes as $key => $theme ) {
			if ( false === as_next_scheduled_action( 'FULLWORKS_SCANNER_get_current_theme', array( 'theme' => $key ), 'FULLWORKS_SCANNER_audit' ) ) {
				as_schedule_single_action( time(), 'FULLWORKS_SCANNER_get_current_theme', array( 'theme' => $key ), 'FULLWORKS_SCANNER_audit' );
			}
		}
	}

	/**
	 * Gets the required theme data
	 *
	 * @param string $theme Theme.
	 */
	public function get_current_theme( $theme ) {
		$theme_data                           = get_transient( 'fullworks-scanner-theme-data' );
		$theme_data[ $theme ]['data']['repo'] = true;
		$theme_info                           = $this->utilities->wp_api( 'https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=' . $theme );
		if ( is_wp_error( $theme_info ) ) {
			if ( 'Theme not found' === $theme_info->get_error_message() ) {
				// now check the repo if it still has translations then .....  it has been removed
				$check = $this->utilities->wp_api( 'https://translate.wordpress.org/projects/wp-themes/' . $theme, array(), false );
				if ( is_wp_error( $check ) ) {
					$theme_data[ $theme ]['data']['repo'] = false;
				} else {
					// report abandoned
					$this->utilities->file_scan_log_write( $theme_data[ $theme ]['data']['theme_object']->get('Name'), 493, 'theme', __CLASS__,  esc_html__( 'This theme may have once been on wordpress.org and now removed - please check', 'fullworks-scanner' )  );

					return;
				}
			} else {
				$this->utilities->error_log(
					array(
						'Message' => 'Unexpected Error in checking theme: ' . $theme,
						'Data'    => $theme_info,
					)
				);

				return;
			}
		}



		if ( isset($theme_data[ $theme ]['data']['update'] ) ) {
			$live_version = $theme_data[ $theme ]['data']['theme_object']->get( 'Version' );
			// report not latest.
			/* translators: leave the %s placeholders. */
			$this->utilities->file_scan_log_write( $theme_data[ $theme ]['data']['theme_object']->get('Name'), 495, 'theme', __CLASS__, sprintf( esc_html__( 'Installed version %1$s - Current version %2$s', 'fullworks-scanner' ), $live_version, $theme_data[ $theme ]['data']['update']['new_version']) );  // Not latest.
		}
	}

}
