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
 * Fired during plugin deactivation
 *
 * @link       https://fullworks.net/products/fullworks-vulnerability-scanner
 * @since      1.0.0
 *
 * @package    Fullworks_Vulnerability_Scanner
 * @subpackage Fullworks_Vulnerability_Scanner/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */

namespace Fullworks_Vulnerability_Scanner\Includes;

class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		if ( as_next_scheduled_action( 'fullworks_security_run_vulndb_scan', array(), 'fullworks-vulnerability-scanner-control' ) ) {
			as_unschedule_action( 'fullworks_security_run_vulndb_scan', array(), 'fullworks-vulnerability-scanner-control' );
		}

		if ( as_next_scheduled_action( 'fullworks_security_run_audit_email', array(), 'fullworks-vulnerability-scanner-control' ) ) {
			as_unschedule_action( 'fullworks_security_run_audit_email', array(), 'fullworks-vulnerability-scanner-control' );
		}
	}

}
