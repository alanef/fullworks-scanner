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

use WP_CLI;
use WP_Error;


/**
 * Class Utilities
 * @package Fullworks_Scanner\Includes
 */
class Utilities {
	private static $issues_found = false;

	/**
	 * @var
	 */
	protected static $instance;


	protected $settings_page_tabs;

	protected $white_label;

	public function __construct() {
		$this->white_label = get_option( 'fullworks-scanner-whitelabel-names', array(
			'title' => esc_html__( 'Fullworks Scanner', 'fullworks-scanner' ),
			'logo'  => FULLWORKS_SCANNER_PLUGIN_URL . 'admin/images/brand/dark-on-light-full-logo-cropped.gif',
		) );
	}

	public static function error_log( $message, $called_from = 'Log' ) {
		if ( WP_DEBUG === true ) {
			if ( is_wp_error( $message ) ) {
				$error_string = $message->get_error_message();
				$error_code   = $message->get_error_code();
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- inside debug if.
				error_log( esc_html( $called_from . ':' . $error_code . ':' . $error_string ) );

				return;
			}
			if ( is_array( $message ) || is_object( $message ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- inside debug if.
				$msg_out = $called_from . ':' . print_r( $message, true ) . PHP_EOL;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- inside debug if.
				error_log( esc_html( $msg_out ) );

				return;
			}
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- inside debug if.
			error_log( 'Log:' . esc_html( $message ) );

			return;
		}
	}

	public function call_vuln_data_api( $url ) {


		$response = wp_remote_get( esc_url_raw( $url ), array() );

		// Check the response code
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		if ( 200 != $response_code && ! empty( $response_message ) ) {
			$options = Utilities::get_instance()->get_white_label();

			return new WP_Error( $response_code,
				$response_message . sprintf(
				// translators: %1$s is the object name core or plugin name or theme name.
					esc_html__( ' : Error occurred, while getting %1$s vulnerability data', 'fullworks-scanner' ),
					$options['title'] ) );
		} elseif ( 200 != $response_code && ! empty( $response_message ) ) {
			$options = Utilities::get_instance()->get_white_label();

			return new WP_Error( $response_code, $response_message .
			                                     sprintf(
			                                     // translators: %1$s is the object name core or plugin name or theme name.
				                                     esc_html__( ' : Error occurred, while getting %1$s vulnerability data',
					                                     'fullworks-scanner' ),
				                                     $options['title'] ) );
		} elseif ( 200 != $response_code ) {
			$options = Utilities::get_instance()->get_white_label();

			return new WP_Error( $response_code, sprintf(
			// translators: %1$s is the object name core or plugin name or theme name.
				esc_html__( 'Unknown error occurred, while getting %1$s vulnerability data', 'fullworks-scanner' ),
				$options['title'] ) );
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		return $decoded;

	}

	public function get_white_label() {

		return $this->white_label;
	}

	/**
	 * @return Utilities
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_issues() {
		return array(
			999 => __( 'Insecure version', 'fullworks-security' ),
			995 => __( 'Known Vulnerability', 'fullworks-security' ),
			498 => __( 'Plugin has an Update', 'fullworks-security' ),
			497 => __( 'Plugin Removed from wp.org', 'fullworks-security' ),
			496 => __( 'Plugin Abandoned', 'fullworks-security' ),
			495 => __( 'Theme has an Update', 'fullworks-security' ),
			494 => __( 'WordPress has an Update', 'fullworks-security' ),
			493 => __( 'Theme Removed', 'fullworks-security' ),
		);
	}

	public function get_plugin_title() {

		return $this->white_label['title'];
	}

	public function clear_all_unaccepted_file_scan( $type, $origin ) {
		global $wpdb;
		$del = $wpdb->get_var(
			$wpdb->prepare( "DELETE FROM {$wpdb->prefix}fwvs_file_audit WHERE `accept` = 0  AND `type` = %s AND `origin` = %s",
				$type,
				$origin
			) );
	}

	public function file_scan_log_write( $file, $status, $type, $origin, $message = null, $extra_single_text = '' ) {
		global $wpdb;
		// cant do insert -> update on dup as file path needs to be longer than key allowed in MySQL
		$result = $wpdb->query( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}fwvs_file_audit 
WHERE filepath = %s AND status = %d AND origin = %s"
			, $file, $status, $origin ) );
		if ( 0 == $result ) {
			$result = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}fwvs_file_audit
  (filepath, status, type, origin, message)
   VALUES (%s, %s, %s, %s , %s)"
				, $file, $status, $type, $origin, $message ) );
		} else {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}fwvs_file_audit SET
  lastscan =NOW(), message = %s
WHERE ID = %s",
				$message, $result ) );
		}
		// if doing WP CLI write line
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$issues = Utilities::get_instance()->get_issues();
			$this->set_issues_found();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Error is handled in the UI.
			WP_CLI::line( $file . ' ' . $type . ' ' . $issues[ $status ] . ' ' . $extra_single_text);
		}
	}

	public function register_settings_page_tab( $title, $page, $href, $position ) {
		$this->settings_page_tabs[ $page ][ $position ] = array( 'title' => $title, 'href' => $href );

	}

	public function get_settings_page_tabs( $page ) {
		$tabs = $this->settings_page_tabs[ $page ];
		ksort( $tabs );

		return $tabs;
	}

	public function wp_api( $url, $args = array(), $json_decode = true ) {
		global $wp_version;
		$url      = add_query_arg(
			$args,
			$url
		);
		$http_url = $url;
		$ssl      = wp_http_supports( array( 'ssl' ) );
		if ( $ssl ) {
			$url = set_url_scheme( $url, 'https' );
		}
		$http_args = array(
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
		);
		$request   = wp_remote_get( $url, $http_args );
		if ( $ssl && is_wp_error( $request ) ) {
			if ( ! wp_doing_ajax() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Error is handled in the UI.
				trigger_error(
					sprintf(
					/* translators: %s: support forums URL */
						esc_html__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
						esc_html__( 'https://wordpress.org/support/' )
					) . ' ' . esc_html__( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ),
					headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
				);
			}
			$request = wp_remote_get( $http_url, $http_args );
		}
		if ( is_wp_error( $request ) ) {
			$res = new WP_Error(
				'wp_api_failed',
				sprintf(
				/* translators: %s: support forums URL */
					esc_html__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
					esc_html__( 'https://wordpress.org/support/' )
				),
				$request->get_error_message()
			);
		} else {
			if ( $json_decode ) {
				$res = json_decode( wp_remote_retrieve_body( $request ), true );
				if ( is_array( $res ) ) {
					// Object casting is required in order to match the info/1.0 format.
					$res = (object) $res;
				} elseif ( null === $res ) {
					$res = new WP_Error(
						'wp_api_failed',
						sprintf(
						/* translators: %s: support forums URL */
							esc_html__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
							esc_html__( 'https://wordpress.org/support/' )
						),
						wp_remote_retrieve_body( $request )
					);
				}
			} else {
				// Check the response code
				$response_code    = wp_remote_retrieve_response_code( $request );
				$response_message = wp_remote_retrieve_response_message( $request );
				if ( 200 != $response_code && ! empty( $response_message ) ) {
					return new WP_Error( $response_code, $response_message );
				} elseif ( 200 != $response_code ) {
					return new WP_Error( $response_code, esc_html__( 'Unknown error occurred', 'fullworks-scanner' ) );
				}
				$res = wp_remote_retrieve_body( $request );
			}
			if ( isset( $res->error ) ) {
				$res = new WP_Error( 'wp_api_failed', $res->error );
			}
		}

		return $res;
	}

	public function get_type_record_count( $type ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->prefix . "fwvs_file_audit WHERE accept = %s ", $type ) );
	}

	public function get_count_bubble() {
		$uc = $this->get_type_record_count( '0' );

		return ( $uc ) ? '&nbsp;<span class="awaiting-mod">' . (int) $uc . '</span>' : '';
	}

	public function single_action( $time, $action, $args, $group ) {
		// time(), 'FULLWORKS_SCANNER_get_current_plugin', array( 'plugin' => dirname( $key ) ), 'FULLWORKS_SCANNER__audit
		// if a wp cli
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			switch ( $action ) {
				case 'FULLWORKS_SCANNER_get_current_plugin':
					$plugins = new Audit_Plugin_Code_Scan( self::$instance );
					$plugins->get_current_plugin( $args['plugin'] );
					break;
				case 'FULLWORKS_SCANNER_get_current_theme':
					$themes = new Audit_Theme_Code_Scan( self::$instance );
					$themes->get_current_theme( $args['theme'] );
					break;
				case 'FULLWORKS_SCANNER_check_vulndb':
					$vulns = new Audit_VulnDB_Scan( self::$instance );
					$vulns->check_vulndb( $args['endpoint'] );
			}
		} else {
			if ( false === as_next_scheduled_action( $action, $args, $group ) ) {
				as_schedule_single_action( $time, $action, $args, $group );
			}
		}
	}
	public static function set_issues_found() {
		self::$issues_found =true;
	}
	public static function is_issues_found() {
		return self::$issues_found;
	}
}
