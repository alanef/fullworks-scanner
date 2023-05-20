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
 * Created
 * User: alan
 * Date: 04/04/18
 * Time: 16:35
 */

namespace Fullworks_Scanner\Admin;

use Fullworks_Scanner\Includes\Utilities;


class Admin_Table_Code_Scan extends Admin_Tables {

	public function add_table_page() {
		Utilities::get_instance()->register_settings_page_tab( esc_html__( 'Code Scan', 'fullworks-scanner' ) , 'report', admin_url( 'admin.php?page=fullworks-scanner-code-scan-report' ),0) ;
		$options = Utilities::get_instance()->get_white_label();

		$this->page_heading = '<img src="' . esc_url_raw($options['logo']) . '" class="logo" alt="' . sanitize_title($options['title']) . '"/><div class="text">' . esc_html__( 'Code Scan Audit Report', 'fullworks-scanner' ) .  '</div>';

		$this->hook         = add_submenu_page(
			'fullworks-settings',
			esc_html__( 'Code Scan Audit Report' , 'fullworks-scanner' ),
			esc_html__( 'Reports', 'fullworks-scanner' ) . Utilities::get_instance()->get_count_bubble(),
			'manage_options',
			'fullworks-scanner-code-scan-report',
			array( $this, 'list_page' )
		);

		add_action( "load-{$this->hook}", array( $this, 'screen_option' ) );
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => esc_html__( 'Issues', 'fullworks-scanner' ),
			'default' => 25,
			'option'  => 'issues_per_page'
		];

		add_screen_option( $option, $args );

		$this->table_obj = new List_Table_Code_Scan();
	}

}