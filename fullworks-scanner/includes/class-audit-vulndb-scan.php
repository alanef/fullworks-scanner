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


/**
 * Class Audit_VulnDB_Scan
 * @package Fullworks_Scanner\Includes
 */
class Audit_VulnDB_Scan {
	/** @var string $api_url */
	protected $api_url = 'https://www.wpvulnerability.net/';
	/** @var Utilities $utilities */
	protected $utilities;
	/** @var $instance */
	private static $instance;

	/**
	 * Audit_VulnDB_Scan constructor.
	 *
	 * @param $notifier
	 * @param $utilities
	 */
	public function __construct(  $utilities ) {
		$this->utilities = $utilities;
		add_action( 'FULLWORKS_SCANNER_check_vulndb', array( $this, 'check_vulndb' ) );
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *
	 */
	public function run() {
		$endpoints = $this->get_endpoints();
		$this->utilities->clear_all_unaccepted_file_scan( 'plugin', __CLASS__ );
		$this->utilities->clear_all_unaccepted_file_scan( 'theme', __CLASS__ );
		$this->utilities->clear_all_unaccepted_file_scan( 'core', __CLASS__ );
		set_transient( 'fullworks_vulndb_control', $endpoints, DAY_IN_SECONDS );
		if ( false !== $endpoints ) {
			foreach ( $endpoints as $key => $endpoint ) {
				// queue the jobs
				$this->utilities->single_action( time(), 'FULLWORKS_SCANNER_check_vulndb', array( 'endpoint' => $key ), 'FULLWORKS_SCANNER_audit' );
			}
		}
	}

	/**
	 * Build list of endpoints to check for themes, plugins and core
	 *
	 * @return array  contains endpoints to check
	 */
	private function get_endpoints() {
		global $wp_version;
		$endpoints   = array();
		$endpoints[] = array(
			'slug' => $wp_version,
			'type' => 'core',
			'data' => array(
				'Name'    => 'WordPress Core',
				'Version' => $wp_version
			),
			'name' => 'WordPress Core: ' . $wp_version
		);
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		foreach ( $plugins as $key => $plugin ) {
			$endpoints[] = array(
				'slug' => dirname( $key ),
				'type' => 'plugin',
				'data' => $plugin,
				'name' => $plugin['Name']
			);
		}
		$themes = wp_get_themes( array( 'errors' => null ) );
		foreach ( $themes as $key => $theme ) {
			$endpoints[] = array(
				'slug' => $key,
				'type' => 'theme',
				'data' => $theme,
				'name' => $theme->get( 'Name' )
			);
		}

		return $endpoints;
	}

	/**
	 * Call to Vulndb
	 *
	 * @param string $endpoint_key API Key.
	 *
	 */
	public function check_vulndb( $endpoint_key ) {
		global $wp_version;
		$endpoints = get_transient( 'fullworks_vulndb_control' );
		$endpoint  = $endpoints[ $endpoint_key ];
		$url       = $this->api_url . $endpoint['type'] . '/' . $endpoint['slug'];
		$response  = $this->utilities->call_vuln_data_api( $url );
		if ( is_wp_error( $response ) ) {
			$this->utilities->error_log( __FUNCTION__ . ' error  ' . $response->get_error_code() . ' target  ' . $this->api_url . '?type=' . $endpoint['type'] . '&slug=' . $endpoint['slug'] );

			return;
		}
		if ( empty ( $response['data']['vulnerability'] ) ) {

			return;  // no vulns.
		}
		if ( 'core' === $endpoint['type'] ) {
			$version = $wp_version;
		} elseif ( 'plugin' === $endpoint['type'] ) {
			$version = $endpoint['data']['Version'];
		} elseif ( 'theme' === $endpoint['type'] ) {
			$version = $endpoint['data']->get( 'Version' );
		}
		// check for vulnerabilities.
		// https://vulnerability.wpsysadmin.com/


		$keys = array();
		foreach ( $response['data']['vulnerability'] as $key => $vulnerability ) {
			if ( 'core' === $endpoint['type'] ||
			     ( in_array( $endpoint['type'], array( 'plugin', 'theme' ) ) &&
			       version_compare( $version, $vulnerability['operator']['max_version'], $vulnerability['operator']['max_operator'] ) )
			) {
				$keys[] = $key;
			}
		}

		if ( ! empty( $keys ) ) {
			list( $ol, $end_ol, $li, $end_li, $text ) = $this->get_vuln_message( $keys );
			$detail = '';
			foreach ( $keys as $key ) {
				$detail .= $li . '<a target="_blank" href="' . $response['data']['vulnerability'][ $key ]['source'][0]['link'] . '">' . $response['data']['vulnerability'][ $key ]['source'][0]['name'] . '</a>' . $end_li;
			}
			$this->utilities->file_scan_log_write(
				$endpoint['name'],
				995,
				$endpoint['type'],
				__CLASS__,
				sprintf( $text, $version, $ol . $detail . $end_ol ),
				$response['data']['vulnerability'][ $key ]['source'][0]['name'] . ' '. $response['data']['vulnerability'][ $key ]['source'][0]['link']
			);
		}
	}

	/**
	 * @param $vulnerability1
	 *
	 * @return array
	 */
	public function get_vuln_message( $keys ) {
		if ( count( $keys ) > 1 ) {
			$ol     = '<ol>';
			$end_ol = '</ol>';
			$li     = '<li>';
			$end_li = '</li>';
			// translators: leave placeholders.

			$text = esc_html__( 'Multiple Vulnerabilities in installed version: %1$s %2$s', 'fullworks-scanner' );
		} else {
			$ol     = '';
			$end_ol = '';
			$li     = '';
			$end_li = '';
			// translators: leave placeholders.
			$text = esc_html__( 'Vulnerability in installed version: %1$s Detail: %2$s', 'fullworks-scanner' );
		}

		return array( $ol, $end_ol, $li, $end_li, $text );
	}

}

