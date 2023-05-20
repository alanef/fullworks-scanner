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
 * Time: 13:45
 */

namespace Fullworks_Scanner\Admin;


use Fullworks_Scanner\Includes\Log_And_Block;
use Fullworks_Scanner\Includes\Utilities;

class Admin_Settings extends Admin_Pages {

	protected $settings_page;
	protected $settings_page_id = 'toplevel_page_fullworks-settings';
	protected $option_group = 'fullworks-scanner';

	/** @var Utilities $utilities */
	protected $utilities;

	private $titles;

	/**
	 * Settings constructor.
	 *
	 * @param string $plugin_name
	 * @param string $version plugin version.
	 */

	public function __construct( $plugin_name, $version, $utilities ) {
		$this->titles = array(
			'Admin Email'           => array(
				'title' => esc_html__( 'Admin Email', 'fullworks-scanner' ),
				'tip'   => esc_html__( 'This email will be used by the plugin to send all notifications from the plugin. It can be different to the site administrator email', 'fullworks-scanner' ),
			),
		);

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->utilities   = $utilities;

		$options = Utilities::get_instance()->get_white_label();

		$this->settings_title = '<img src="' . esc_url_raw( $options['logo'] ) . '" class="logo" alt="' . sanitize_title( $options['title'] ) . '"/><div class="text">' . esc_html__( 'Settings', 'fullworks-scanner' ) . '</div>';
		parent::__construct();
	}

	public function register_settings() {
		/* Register our setting. */
		register_setting(
			$this->option_group,                         /* Option Group */
			'fullworks-scanner-general',                   /* Option Name */
			array( $this, 'sanitize_general' )          /* Sanitize Callback */
		);
		register_setting(
			$this->option_group,                         /* Option Group */
			'fullworks-scanner-audit-schedule',                   /* Option Name */
			array( $this, 'sanitize_audit_schedule' )          /* Sanitize Callback */
		);

		Utilities::get_instance()->register_settings_page_tab( esc_html__( 'General Settings', 'fullworks-scanner' ), 'settings', admin_url( 'admin.php?page=fullworks-settings' ), 0 );
		/* Add settings menu page */
		$this->settings_page = add_submenu_page(
			'fullworks-settings',
			'Settings', /* Page Title */
			'Settings',                       /* Menu Title */
			'manage_options',                 /* Capability */
			'fullworks-settings',                         /* Page Slug */
			array( $this, 'settings_page' )          /* Settings Page Function Callback */
		);

		register_setting(
			$this->option_group,                         /* Option Group */
			"{$this->option_group}-reset",                   /* Option Name */
			array( $this, 'reset_sanitize' )          /* Sanitize Callback */
		);

	}

	public function reset_sanitize( $settings ) {
		// Detect multiple sanitizing passes.
		// Accomodates bug: https://core.trac.wordpress.org/ticket/21989


		if ( ! empty( $settings ) ) {
			add_settings_error( $this->option_group, '', 'Settings reset to defaults.', 'updated' );
			/* Delete Option */
			$this->delete_options();

		}

		return $settings;
	}

	public function delete_options() {
		update_option( 'fullworks-scanner-general', self::option_defaults( 'fullworks-scanner-general' ) );
		update_option( 'fullworks-scanner-audit-schedule', self::option_defaults( 'fullworks-scanner-audit-schedule' ) );
	}

	public static function option_defaults( $option ) {
		switch ( $option ) {
			case 'fullworks-scanner-general':
				return array(
					'admin_email' => get_bloginfo( 'admin_email' ),
				);
			case 'fullworks-scanner-audit-schedule':
				return array(
					'cron'       => '10 02 * * *',
					'email'      => array(
						'warning' => 1,
					),
					'ignoredirs' => array(),
				);
			default:
				return false;
		}
	}

	public function add_meta_boxes() {
		$this->add_meta_box(
			'general',                  /* Meta Box ID */
			esc_html__( 'General Settings', 'fullworks-scanner' ),               /* Title */
			array( $this, 'meta_box_general' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default',             /* Priority */
			null,
			false
		);


		$this->add_meta_box(
			'audit-schedule',                  /* Meta Box ID */
			esc_html__( 'Code Check', 'fullworks-scanner' ),               /* Title */
			array( $this, 'meta_box_audit_schedule' ),  /* Function Callback */
			$this->settings_page_id,               /* Screen: Our Settings Page */
			'normal',                 /* Context */
			'default',                 /* Priority */
            null,
			false
		);


	}

	private function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null, $closed = true ) {
		add_meta_box(
			$id,
			$title,
			$callback,
			$screen,
			$context,
			$priority,
			$callback_args
		);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
		if ( ! isset( $_GET['settings-updated'] ) ) {
			if ( $closed ) {
				add_filter( "postbox_classes_{$screen}_{$id}", function ( $classes ) {
					array_push( $classes, 'closed' );

					return $classes;
				} );
			}
		}

	}


	public function sanitize_general( $settings ) {
		// check admin referrer nonce
		check_admin_referer( $this->option_group . '-options');
        if ( isset( $_REQUEST['fullworks-scanner-reset'] ) ) {
			return $settings;
		}
		if ( empty( $settings ) ) {
			return $settings;
		}

		if ( isset( $settings['admin_email'] ) ) {
			$settings['admin_email'] = sanitize_email( $settings['admin_email'] );
		}


		return $settings;
	}


	public function sanitize_audit_schedule( $settings ) {
		// check admin referrer nonce
		check_admin_referer( $this->option_group . '-options');
		if ( isset( $_REQUEST['fullworks-scanner-reset'] ) ) {
			return $settings;
		}
		$options                 = get_option( 'fullworks-scanner-audit-schedule' );
		$options['cron_changed'] = false;
		if ( empty( $settings['cron'] ) ) {
			add_settings_error(
				'fscron',
				'fscron',
				esc_html__( 'No code scans will be performed, as schedule is blank', 'fullworks-scanner' ),
				'updated'
			);

		} else if ( ! $this->is_valid_cron( $settings['cron'] ) ) {
			add_settings_error(
				'fscron',
				'fscron',
				esc_html__( 'Invalid cron format, please try again', 'fullworks-scanner' ),
				'error'
			);
			$settings['cron'] = $options['cron'];
		} else if ( $settings['cron'] != $options['cron'] ) {
			$settings['cron_changed'] = 1;
			add_settings_error(
				'fscron',
				'fscron',
				esc_html__( 'Schedule changed, jobs will be cancelled and re-queued to the new schedule', 'fullworks-scanner' ),
				'updated'
			);
		}

		return $settings;
	}

	private function is_valid_cron( $cron ) {
		$nums     = [
			'min'        => '[0-5]?\d',
			'hour'       => '[01]?\d|2[0-3]',
			'dayOfMonth' => '((0?[1-9]|[12]\d|3[01])W?|L|\?)',
			'month'      => '0?[1-9]|1[012]|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec',
			'dayOfWeek'  => '([0-7]|mon|tue|wed|thu|fri|sat|sun|\?)(L|\#[1-5])?',
		];
		$steps    = [
			'min'        => '(0?[1-9]|[1-5]\d)',
			'hour'       => '(0?[1-9]|1\d|2[0-3])',
			'dayOfMonth' => '(0?[1-9]|[12]\d|3[01])',
			'month'      => '(0?[1-9]|1[012])',
			'dayOfWeek'  => '[1-7]',
		];
		$sections = [];
		foreach ( $nums as $section => $number ) {
			$step                 = $steps[ $section ];
			$range                = "({$number})(-({$number})(/{$step})?)?";
			$sections[ $section ] = "\*(/{$step})?|{$number}(/{$step})?|{$range}(,{$range})*";
		}
		$joinedSections = '(' . implode( ')\s+(', $sections ) . ')';
		$replacements   = '@reboot|@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly';
		$exp            = "^\s*({$joinedSections}|({$replacements}))\s*$";


		return (bool) preg_match( "#{$exp}#i", $cron );

	}

	public function meta_box_general() {
		?>
		<?php
		$options = get_option( 'fullworks-scanner-general' );
		?>
        <table class="form-table">
            <tbody>

            <tr valign="top">
				<?php $this->display_th( 'Admin Email' ); ?>
                <td>
                    <input type="email"
                           name="fullworks-scanner-general[admin_email]"
                           id="fullworks-scanner-general[admin_email]"
                           class="all-options"
                           value="<?php  echo esc_attr($options['admin_email']); ?>"
                    >
                </td>
				<?php $this->display_tip( 'Admin Email' ); ?>
            </tr>
            </tbody>
        </table>
		<?php
	}

	private function display_th( $title ) {
		?>
        <th scope="row"><?php
			echo wp_kses_post( $this->titles[ $title ]['title'] );
			?>
        </th>
		<?php
	}

	private function display_tip( $title ) {
		?>
        <td><?php
			echo ( isset( $this->titles[ $title ]['tip'] ) ) ? '<div class="help-tip"><p>' . wp_kses_post( $this->titles[ $title ]['tip'] ) . '</p></div>' : '';
			?>
        </td>
		<?php
	}

	/**
	 *
	 */


	public function meta_box_audit_schedule() {
		$options = get_option( 'fullworks-scanner-audit-schedule' );
		?>
        <table class="form-table">
            <tbody>

            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Scanning Schedule', 'fullworks-scanner' ); ?></th>
                <td>
                    <input type="hidden"
                           name="fullworks-scanner-audit-schedule[cron_changed]"
                           id="fullworks-scanner-audit-schedule[cron_changed]"
                           value="0"
                    >
                    <input type="text"
                           name="fullworks-scanner-audit-schedule[cron]"
                           id="fullworks-scanner-audit-schedule[cron]"
                           class="all-options"
                           value="<?php echo esc_attr( $options['cron'] ); ?>"
                    >
                    <p>
                        <span class="description"><?php esc_html_e( 'Control the timing of the audit schedule runs, in cron format. e.g. minute hour day (month) month day(week) - 10 2 * * *  is 10 past two am every day. Set to \'blank\' to not perform code scans', 'fullworks-scanner' ); ?></span>
                    </p>
                </td>
            </tr>
            <tr valign="top" class="alternate">
                <th scope="row"><?php esc_html_e( 'Email', 'fullworks-scanner' ); ?></th>
                <td>
                    <p>
                        <span class="description"><?php esc_html_e( 'After each scan you will be emailed critical issues plus:', 'fullworks-scanner' ); ?></span>
                    </p>
                    <p>
                        <label for="fullworks-scanner-audit-schedule[email][warning]"><input
                                    type="checkbox"
                                    name="fullworks-scanner-audit-schedule[email][warning]"
                                    id="fullworks-scanner-audit-schedule[email][warning]"
                                    value="1"
								<?php checked( '1', $options['email']['warning'] ); ?>>
							<?php esc_html_e( 'Warnings: These include updates needed, plugins that may be abandoned or removed without known security issues.', 'fullworks-scanner' ); ?>
                        </label>
                    </p>
                </td>
            </tr>


            </tbody>
        </table>
		<?php

	}
}