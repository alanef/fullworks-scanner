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

namespace Fullworks_Vulnerability_Scanner\Admin;


use Fullworks_Vulnerability_Scanner\Includes\Utilities;
use WP_List_Table;

/**
 * Class Settings
 * @package Fullworks_Vulnerability_Scanner\Admin
 */
class Admin_Tables {

	/**
	 * Protected
	 *
	 * @var WP_List_Table $table_obj WP Tables.
	 */
	public $table_obj;
	protected $hook;
	protected $page_heading;
    protected $page_title;
    protected $version;
    protected $plugin_name;


	/**
	 * Settings constructor.
	 *
	 * @param string $plugin_name
	 * @param string $version plugin version.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;


	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


	public function add_table_page() {

	}

	public function screen_option() {

	}

	public function list_page() {
		?>
		<div class="wrap fs-page">
			<h2><?php echo wp_kses_post($this->page_heading); ?></h2>
			<?php $this->display_tabs(); ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->table_obj->prepare_items();
								$this->table_obj->views();
								$this->table_obj->display();
								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	public function display_tabs() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required for $_GET['page']
		$split     = explode( "-", sanitize_text_field($_GET['page']) );
		$page_type = $split[ count( $split ) - 1 ];
		$tabs      = Utilities::get_instance()->get_settings_page_tabs( $page_type ); ?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $tab ) {
				$active = '';
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required for $_GET['page']
				if ( preg_match( '#' . sanitize_text_field($_GET['page']) . '$#', $tab['href'] ) ) {
					$active = ' nav-tab-active';
				}
				echo '<a href="' . esc_url($tab['href']) . '" class="nav-tab' . esc_attr($active) . '">' . esc_html($tab['title']) . '</a>';
			}
			?>
		</h2>
		<?php
	}


}
