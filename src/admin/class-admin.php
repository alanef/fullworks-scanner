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
 * The admin-specific functionality of the plugin.
 *
 *
 */

namespace Fullworks_Scanner\Admin;

use Fullworks_Scanner\Includes\Utilities;
use Plugin_Upgrader;
use LiteSpeed_Cache_API;


/**
 * Class Admin
 * @package Fullworks_Scanner\Admin
 */
class Admin {

	/** @var Utilities $utilities */
	protected $utilities;
	/**
	 * The ID of this plugin.
	 *
	 */
	private $plugin_name;
	/**
	 * The version of this plugin.
	 *
	 */
	private $version;

	/**
	 * Admin constructor.
	 *
	 * @param $plugin_name
	 * @param $version
	 * @param $utilities
	 */
	public function __construct( $plugin_name, $version, $utilities ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->utilities   = $utilities;

	}


	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}


	/**
	 * Register the JavaScript for the admin area.
	 *
	 */
	public function enqueue_scripts() {
		// get current screen
		$screen = get_current_screen();
		// if screen id contains 'fullworks-scanner' then load the js
		if ( strpos( $screen->id, 'fullworks-scanner' ) !== false ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), $this->version, false );
			// localize the script with a message  for the alert
			$translation_array = array(
				'rescan_alert' => esc_html__( 'Once scheduled the rescan will run in background and may take several minutes. The report will be cleared initially. Return to this page after 5 or more minutes and refresh the page. Press OK to schedule. ', 'fullworks-scanner' ),
			);
			wp_localize_script( $this->plugin_name, 'fullworks_scanner', $translation_array );
		}


	}

	public function upgrade_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$dbv             = get_option( 'FULLWORKS_SCANNER_db_version' );
	}


	public function plugin_updated_action( $upgrader_object, $options ) {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
			$plugin_slugs = $options['plugins'];

			foreach ( $plugin_slugs as $plugin_slug ) {
				// Perform actions here for each updated plugin
				update_site_option( 'FULLWORKS_SCANNER_plugin_updated_' . $plugin_slug, time() );
			}
		}
	}


}




