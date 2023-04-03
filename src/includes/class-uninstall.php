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
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */

namespace Fullworks_Vulnerability_Scanner\Includes;

class Uninstall {

	/**
	 * Uninstall specific code
	 */
	public static function uninstall( $network_wide ) {
        global $wpdb;

		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and delete tables on each one
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::delete_tables();
				restore_current_blog();
			}
		} else {
			self::delete_tables();
		}


		delete_option( "fullworks-firewall_db_version" );

		// settings

		delete_option( 'fullworks-vulnerability-scanner-general' );

		delete_option( 'fullworks-vulnerability-scanner-audit-schedule' );


	}

	private static function delete_tables() {
		global $wpdb;
		// wpdb drop table fwvs_file_audit
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fwvs_file_audit" );

	}

}
