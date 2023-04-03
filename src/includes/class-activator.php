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
 * Fired during plugin activation
 *
 *
 * @package    Fullworks_Vulnerability_Scanner
 * @subpackage Fullworks_Vulnerability_Scanner/includes
 */

/**
 * Fired during plugin activation.
 */

namespace Fullworks_Vulnerability_Scanner\Includes;


class Activator {

	/**
	 * Fired during plugin activation.
	 *
	 * Creates database table and sets version option
	 *
	 *
	 *
	 *
	 * @since    1.0.0
	 */
	public static function activate( $network_wide ) {
		global $wpdb;
		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and add tables on each
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::create_tables();
				restore_current_blog();
			}
		} else {
			self::create_tables();
		}

	}

	public static function create_tables() {
		// database set up
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'fwvs_file_audit';
		$sql        = "CREATE TABLE $table_name (
		ID int NOT NULL AUTO_INCREMENT,
		filepath varchar(4096) NOT NULL,
		createdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		lastscan timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		accept tinyint DEFAULT 0,
		status int,
		message text,
		type varchar(24),
		origin varchar(128),
		PRIMARY KEY  (ID)
	) $charset_collate;";
		dbDelta( $sql );
		$dbv = get_option('FULLWORKS_VULNERABILITY_SCANNER_db_version');
		update_option( 'FULLWORKS_VULNERABILITY_SCANNER_db_version', '1.0' );

	}

	public static function on_create_blog_tables( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		if ( is_plugin_active_for_network( trailingslashit( basename( FULLWORKS_VULNERABILITY_SCANNER_PLUGIN_DIR ) ) . 'fullworks-vulnerability-scanner.php' ) ) {
			switch_to_blog( $blog_id );
			self::create_tables();
			restore_current_blog();
		}
	}
}
