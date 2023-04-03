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
 * Date: 03/04/18
 * Time: 16:45
 */

namespace Fullworks_Vulnerability_Scanner\Admin;

use Fullworks_Vulnerability_Scanner\Includes\Utilities;
use WP_List_Table;


class List_Table_Code_Scan extends WP_List_Table {

	const TABLE = 'fwvs_file_audit';
	const NONCE = 'FULLWORKS_VULNERABILITY_SCANNER_delete_code_issue';

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => esc_html__( 'Code Issue', 'fullworks-vulnerability-scanner' ),
			//singular name of the listed records
			'plural'   => esc_html__( 'Code Issues', 'fullworks-vulnerability-scanner' ),
			//plural name of the listed records
			'ajax'     => false
			//should this table support ajax?

		] );
	}

	public function no_items() {
		esc_html_e( 'No current code issues', 'fullworks-vulnerability-scanner' );
	}

	function column_filepath( $item ) {

		// create a nonce
		$delete_nonce = wp_create_nonce( self::NONCE );

		$title = '<strong>' . $item['filepath'] . '</strong>';

		$actions = array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not required for $_REQUEST['page']
			'accept' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__( 'Accept and Ignore in future scans', 'fullworks-vulnerability-scanner' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'accept', absint( $item['ID'] ), $delete_nonce ),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not required for $_REQUEST['page']
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__( 'Remove notification until next scan', 'fullworks-vulnerability-scanner' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce ),
		);

		if ( isset( $_GET['type'] ) && 'accepted' === $_GET['type'] ) {
			if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], SELF::NONCE ) ) {
				$actions = array(
					'unaccept' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__( 'Unaccept', 'fullworks-vulnerability-scanner' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'unaccept', absint( $item['ID'] ), $delete_nonce ),
				);
			} else {
				die ( 'Security check' );
			}

		}

		return $title . $this->row_actions( $actions );
	}

	public function column_status( $item ) {

		if ( 'core' == $item['type'] ) {
			return $this->wp_update_action( $item );
		} elseif ( 'plugin' == $item['type'] ) {
			return $this->plugin_update_action( $item );
		} elseif ( 'theme' == $item['type'] ) {
			return $this->theme_update_action( $item );
		}

		return $item['status'];
	}

	private function wp_update_action( $item ) {


		$title = '<strong>' . $item['status'] . '</strong>';


		$actions = array(
			'update_wp' => sprintf( '<a class="wp-update" href="%1$s">%2$s</a>', self_admin_url( 'update-core.php' ), __( 'Update WordPresss', 'fullworks-vulnerability-scanner' ) ),
		);

		return $title . $this->row_actions( $actions );
	}

	private function plugin_update_action( $item ) {

		$title = '<strong>' . $item['status'] . '</strong>';


		$actions = array(
			'update_plugins' => sprintf( '<a class="plugins-update" href="%1$s">%2$s</a>', self_admin_url( 'plugins.php?plugin_status=upgrade' ), __( 'Update plugins', 'fullworks-vulnerability-scanner' ) ),
		);

		return $title . $this->row_actions( $actions );
	}

	private function theme_update_action( $item ) {

		$title = '<strong>' . $item['status'] . '</strong>';


		$actions = array(
			'update_themes' => sprintf( '<a class="themes-update" href="%1$s">%2$s</a>', self_admin_url( 'update-core.php' ), __( 'Update themes', 'fullworks-vulnerability-scanner' ) ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				//	return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}

		return $item[ $column_name ];
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}

	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'filepath' => esc_html__( 'File', 'fullworks-vulnerability-scanner' ),
			'type'     => esc_html__( 'Type', 'fullworks-vulnerability-scanner' ),
			'status'   => esc_html__( 'Issue', 'fullworks-vulnerability-scanner' ),
			'message'  => esc_html__( 'Issue Detail', 'fullworks-vulnerability-scanner' ),
			'lastscan' => esc_html__( 'Last Scan', 'fullworks-vulnerability-scanner' ),
		];

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'type'     => array( 'type', true ),
			'status'   => array( 'status', true ),
			'lastscan' => array( 'lastscan', true )
		);

		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete'   => esc_html__( 'Remove notification until next scan', 'fullworks-vulnerability-scanner' ),
			'bulk-accept'   => esc_html__( 'Accept and Ignore in future scans', 'fullworks-vulnerability-scanner' ),
			'bulk-unaccept' => esc_html__( 'Include previously accepted items in future scans', 'fullworks-vulnerability-scanner' )
		);

		return $actions;
	}

	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'issues_per_page', 25 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );


		$this->items = self::get( $per_page, $current_page );
	}

	public function process_bulk_action() {

		// Detect when an non bulk action is being triggered.
		if ( 'delete' === $this->current_action() ) {
			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), self::NONCE ) ) {
				die( 'Security Check!' );
			}
			if ( ! isset( $_GET['id'] ) ) {
				die( 'Security Check!' );
			}
			self::delete( absint( $_GET['id'] ) );
		}
		if ( 'accept' === $this->current_action() ) {

			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), self::NONCE ) ) {
				die( 'Security Check!' );
			}
			if ( ! isset( $_GET['id'] ) ) {
				die( 'Security Check!' );
			}
			self::accept( absint( $_GET['id'] ) );
		}
		if ( 'unaccept' === $this->current_action() ) {

			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), self::NONCE ) ) {
				die( 'Security Check!' );
			} else {
				if ( isset( $_GET['id'] ) ) {
					self::unaccept( absint( $_GET['id'] ) );
				}
			}
		}

		// If bulk action is triggered
		if ( ( isset( $_POST['action'] ) && isset( $_POST['bulk-delete'] ) && 'bulk-delete' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'bulk-delete' === $_POST['action2'] ) ) {
			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), 'bulk-' . $this->_args['plural'] ) ) {
				die( 'Security Check!' );
			}
			if ( isset( $_POST['bulk-delete'] ) && is_array( $_POST['bulk-delete'] ) ) {
				$delete_ids = esc_sql( $_POST['bulk-delete'] );
				// loop over the array of record IDs and delete them.
				foreach ( $delete_ids as $id ) {
					self::delete( $id );
				}
			}
		}
		// If the accept bulk action is triggered.
		if ( ( isset( $_POST['action'] ) && isset( $_POST['bulk-delete'] ) && 'bulk-accept' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'bulk-accept' === $_POST['action2'] )
		) {
			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), 'bulk-' . $this->_args['plural'] ) ) {
				die( 'Security Check!' );
			}
			if ( isset( $_POST['bulk-delete'] ) && is_array( $_POST['bulk-delete'] ) ) {
				$accept_ids = esc_sql( $_POST['bulk-delete'] );
				// loop over the array of record IDs and delete them.
				foreach ( $accept_ids as $id ) {
					self::accept( $id );
				}
			}
		}

		if ( ( isset( $_POST['action'] ) && isset( $_POST['bulk-delete'] ) && 'bulk-unaccept' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'bulk-unaccept' === $_POST['action2'] )
		) {
			if ( ! wp_verify_nonce( esc_attr( $_REQUEST['_wpnonce'] ), 'bulk-' . $this->_args['plural'] ) ) {
				die( 'Security Check!' );
			}

			if ( isset( $_POST['bulk-delete'] ) && is_array( $_POST['bulk-delete'] ) ) {
				$accept_ids = esc_sql( $_POST['bulk-delete'] );
				// loop over the array of record IDs and delete them.
				foreach ( $accept_ids as $id ) {
					self::unaccept( $id );
				}
			}
		}

	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'fwvs_file_audit',
			[ 'ID' => $id ],
			[ '%d' ]
		);
	}

	public static function accept( $id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'fwvs_file_audit',
			[ 'accept' => 1 ],
			[ 'ID' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	public static function unaccept( $id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'fwvs_file_audit',
			[ 'accept' => 0 ],
			[ 'ID' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	public static function record_count( $type = 0 ) {
		$type = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified upstream
		if ( isset( $_GET['type'] ) && 'accepted' === $_GET['type'] ) {
			$type = 1;
		}

		return self::type_record_count( $type );
	}

	protected static function type_record_count( $type ) {
		return Utilities::get_instance()->get_type_record_count( $type );
	}

	public static function get( $per_page = 25, $page_number = 1 ) {

		global $wpdb;
		$issues = array(
			999 => __( 'Insecure version', 'fullworks-security' ),
			995 => __( 'Known Vulnerability', 'fullworks-security' ),
			498 => __( 'Plugin has an Update', 'fullworks-security' ),
			497 => __( 'Plugin Removed', 'fullworks-security' ),
			496 => __( 'Plugin Abandoned', 'fullworks-security' ),
			495 => __( 'Theme has an Update', 'fullworks-security' ),
			494 => __( 'WordPress has an Update', 'fullworks-security' ),
			493 => __( 'Theme Removed', 'fullworks-security' ),
		);


		$type = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified upstream
		if ( isset( $_GET['type'] ) && 'accepted' === $_GET['type'] ) {
							$type = 1;
		}
		$sql     = '';
		$orderby = 'ID';
		$order   = 'DESC';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
			$orderby = esc_sql( $_REQUEST['orderby'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
			$order   .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}


		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, filepath, createdate, lastscan, status, message, type FROM " . $wpdb->prefix . "fwvs_file_audit WHERE accept = %s ORDER BY %s %s LIMIT %d OFFSET %d",
				$type,
				$orderby,
				$order,
				(int) $per_page,
				( (int) $page_number - 1 ) * (int) $per_page
			), 'ARRAY_A' );

		$return = array();
		if ( $results ) {
			foreach ( $results as $result ) {

				if ( substr( $result['filepath'], 0, strlen( ABSPATH ) ) == ABSPATH ) {
					$file = substr( $result['filepath'], strlen( ABSPATH ) );
				} else {
					$file = $result['filepath'];
				}
				if ( $result['status'] > 500 ) {
					$colour = '#dc3232';
				} else {
					$colour = '#ffb900';
				}
				$return[] = array(
					'ID'          => $result['ID'],
					'filepath'    => sanitize_text_field( $file ),
					'createdate'  => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $result['createdate'] ) ),
					'lastscan'    => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $result['lastscan'] ) ),
					'status'      => "<div style='border-left-color: $colour; border-left-style: solid; border-left-width: 4px;'><div style='margin-left:1em'>" . $issues[ $result['status'] ] . "</div></div>",
					'message'     => $result['message'],
					'type'        => $result['type'],
					'status_code' => $result['status'],

				);
			}
		}

		return $return;
	}

	protected function get_views() {

		$nonce = wp_create_nonce( SELF::NONCE );

		$uc       = self::type_record_count( 0 );
		$ac       = self::type_record_count( 1 );
		$uc_class = 'current';
		$ac_class = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified upstream
		if ( isset( $_GET['type'] ) && 'accepted' === $_GET['type'] ) {
			{
				$uc_class = '';
				$ac_class = 'current';
			}
		}
		$status_links = array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
			'unaccepted' => sprintf( '<a href="?page=%1$s&_wpnonce=%5$s" class="%2$s">%3$s</a><span class="count">(%4$d)</span>', esc_attr( $_REQUEST['page'] ), esc_attr( $uc_class ), esc_html__( 'Unaccepted', 'fullworks-vulnerability-scanner' ), (int) $uc, esc_attr( $nonce ) ),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
			'accepted'   => sprintf( '<a href="?page=%1$s&type=accepted&_wpnonce=%5$s" class="%2$s">%3$s</a><span class="count">(%4$d)</span>', esc_attr( $_REQUEST['page'] ), esc_attr( $ac_class ), esc_html__( 'Accepted', 'fullworks-vulnerability-scanner' ), (int) $ac, esc_attr( $nonce ) ),
		);

		return $status_links;
	}

	private function delete_action( $item ) {
		// create a nonce
		$delete_file_nonce = wp_create_nonce( self::NONCE );

		$title = '<strong>' . $item['status'] . '</strong>';


		$actions = array(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not required for $_REQUEST['page']
			'delete_file' => sprintf( '<a class="delete-file" href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__( 'Delete this file', 'fullworks-vulnerability-scanner' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete-file', absint( $item['ID'] ), $delete_file_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}


}
