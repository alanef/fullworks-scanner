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

namespace Fullworks_Vulnerability_Scanner\Admin;

use Fullworks_Vulnerability_Scanner\Includes\Utilities;
use Plugin_Upgrader;
use LiteSpeed_Cache_API;


/**
 * Class Admin
 * @package Fullworks_Vulnerability_Scanner\Admin
 */
class Admin {

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


	/** @var Utilities $utilities */
	protected $utilities;



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


	}



	public function upgrade_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$dbv             = get_option( 'FULLWORKS_VULNERABILITY_SCANNER_db_version' );
	}





}




