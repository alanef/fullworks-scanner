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

namespace Fullworks_Scanner\Admin;

use Fullworks_Scanner\Includes\Utilities;


/**
 * Class Settings
 * @package Fullworks_Scanner\Admin
 */
class Admin_Pages {

	public $block_table_obj;
	protected $settings_page;  // toplevel appearance etc  followed by slug


	// for the block report
	protected $settings_page_id = 'toplevel_page_fullworks_scanner_settings';

	//
	protected $settings_title;

	protected $plugin_name;
	protected $version;


	public function __construct() {


	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function settings_setup() {
		// @TODO called multiple times - thing maybe singleton

		$options = Utilities::get_instance()->get_white_label();
		$title   = $options['title'];

		add_menu_page(
			$title, /* Page Title */
			$title . Utilities::get_instance()->get_count_bubble(),                       /* Menu Title */
			'manage_options',                 /* Capability */
			'fullworks-scanner-settings',                         /* Page Slug */
			array( $this, 'settings_page' ),           /* Settings Page Function Callback */
			'dashicons-shield',           /* Menu Icon */
			70                                /* Menu Position */
		);

		$this->register_settings();


		/* Vars */
		$page_hook_id = $this->settings_page_id;

		/* Do stuff in settings page, such as adding scripts, etc. */
		if ( ! empty( $this->settings_page ) ) {
			/* Load the JavaScript needed for the settings screen. */
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( "admin_footer-{$page_hook_id}", array( $this, 'footer_scripts' ) );
			/* Set number of column available. */
			add_filter( 'screen_layout_columns', array( $this, 'screen_layout_column' ), 10, 2 );
			add_action( $this->settings_page_id . '_settings_page_boxes', array( $this, 'add_required_meta_boxes' ) );

		}
	}

	public function register_settings() {
		// overide in extended class
	}

	public function enqueue_scripts( $hook_suffix ) {
		$page_hook_id = $this->settings_page_id;
		if ( $hook_suffix == $page_hook_id ) {
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );
		}
	}

	public function footer_scripts() {
		$page_hook_id = $this->settings_page_id;
		//@ TODO think about localize and enqueue
		?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                // toggle
                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                postboxes.add_postbox_toggles('<?php echo esc_attr( $page_hook_id ); ?>');
                // display spinner
                $('#fx-smb-form').submit(function () {
                    $('#publishing-action .spinner').css('visibility', 'visible');
                });
// confirm before reset
                $('#delete-action *').on('click', function () {
                    return confirm('Are you sure want to do this?');
                });
            });
            //]]>
        </script>
		<?php
	}

	public function screen_layout_column( $columns, $screen ) {
		$page_hook_id = $this->settings_page_id;
		if ( $screen == $page_hook_id ) {
			$columns[ $page_hook_id ] = 2;
		}

		return $columns;
	}

	/**
	 *
	 */
	public function settings_page() {

		/* global vars */
		global $hook_suffix;
		if ( $this->settings_page_id == $hook_suffix ) {

			/* enable add_meta_boxes function in this page. */
			do_action( $this->settings_page_id . '_settings_page_boxes', $hook_suffix );
			?>

            <div class="wrap fs-page">

                <h2><?php echo wp_kses_post( $this->settings_title ); ?></h2>

				<?php settings_errors(); ?>
	            <?php do_action('ffpl_ad_display'); ?>


                <div class="fs-settings-meta-box-wrap">

                    <form id="fs-smb-form" method="post" action="options.php">

						<?php settings_fields( $this->option_group ); // options group
						?>
						<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
						<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

						<?php $this->display_tabs(); ?>

                        <div id="poststuff">

                            <div id="post-body"
                                 class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

                                <div id="postbox-container-1" class="postbox-container">

									<?php do_meta_boxes( $hook_suffix, 'side', null ); ?>
                                    <!-- #side-sortables -->

                                </div><!-- #postbox-container-1 -->

                                <div id="postbox-container-2" class="postbox-container">

									<?php do_meta_boxes( $hook_suffix, 'normal', null ); ?>
                                    <!-- #normal-sortables -->

									<?php do_meta_boxes( $hook_suffix, 'advanced', null ); ?>
                                    <!-- #advanced-sortables -->

                                </div><!-- #postbox-container-2 -->

                            </div><!-- #post-body -->

                            <br class="clear">

                        </div><!-- #poststuff -->

                    </form>

                </div><!-- .fs-settings-meta-box-wrap -->

            </div><!-- .wrap -->
			<?php
		}

	}

	public function display_tabs() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required for $_GET['page'] - gatekeeping
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required for $_GET['page']
		$page      = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		$split     = explode( '-', $page );
		$page_type = $split[ count( $split ) - 1 ];
		$tabs      = Utilities::get_instance()->get_settings_page_tabs( $page_type );
		if ( count( $tabs ) < 2 ) {
			return;
		}
		?>
        <h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab ) {
				$active = '';
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required for $_GET['page']
				if ( preg_match( '#' . $page . '$#', $tab['href'] ) ) {
					$active = ' nav-tab-active';
				}
				echo '<a href="' . esc_url( $tab['href'] ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $tab['title'] ) . '</a>';
			}
			?>
        </h2>
		<?php
	}

	public function add_required_meta_boxes() {
		global $hook_suffix;

		if ( $this->settings_page_id === $hook_suffix ) {

			$this->add_meta_boxes();

			add_meta_box(
				'submitdiv',               /* Meta Box ID */
				esc_html__( 'Save Options', 'fullworks-scanner' ),            /* Title */
				array( $this, 'submit_meta_box' ),  /* Function Callback */
				$this->settings_page_id,                /* Screen: Our Settings Page */
				'side',                    /* Context */
				'high'                     /* Priority */
			);
		}
	}

	public function add_meta_boxes() {
		// in extended class
	}

	public function submit_meta_box() {

		?>
        <div id="submitpost" class="submitbox">

            <div id="major-publishing-actions">
				<?php
				// add nonce
				wp_nonce_field( $this->option_group . '-options' );
				?>

                <div id="delete-action">
                    <input type="submit" name="<?php echo esc_attr( $this->option_group ) . '-reset'; ?>"
                           id="<?php echo esc_attr( $this->option_group ) . '-reset'; ?>"
                           class="button"
                           value="Reset Settings">
                </div><!-- #delete-action -->

                <div id="publishing-action">
                    <span class="spinner"></span>
					<?php submit_button( esc_attr( 'Save' ), 'primary', 'submit', false ); ?>
                </div>

                <div class="clear"></div>

            </div><!-- #major-publishing-actions -->

        </div><!-- #submitpost -->

		<?php
	}

	public function reset_sanitize( $settings ) {
		// for extended class to manage
		return $settings;
	}

	public function delete_options() {
		// for extended class to manage
	}


}
