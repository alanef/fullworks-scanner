<?php
/**
 * Shared helpers for scanner tests.
 */

abstract class Scanner_Test_Case extends WP_UnitTestCase {

	/**
	 * URL substring => response map for the current test.
	 *
	 * @var array<string, array|WP_Error>
	 */
	protected $http_mocks = array();

	/**
	 * URLs requested through the mock, in order.
	 *
	 * @var string[]
	 */
	protected $http_requests = array();

	public function set_up() {
		parent::set_up();
		// The WP test installer only resets core tables; start every test from an empty audit table.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}fwvs_file_audit" ); // phpcs:ignore WordPress.DB
		$this->http_mocks    = array();
		$this->http_requests = array();
		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
		// wp-env's tests site is "localhost", which PHPMailer rejects as a From address.
		add_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
	}

	public function mail_from() {
		return 'wordpress@example.org';
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		remove_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
		unset( $_REQUEST['orderby'], $_REQUEST['order'], $_REQUEST['_wpnonce'], $_GET['type'], $_REQUEST['page'] );
		parent::tear_down();
	}

	/**
	 * Intercept every outbound HTTP request. Unmatched URLs fail the test so that
	 * nothing in the suite can reach the network by accident.
	 */
	public function mock_http_request( $preempt, $args, $url ) {
		$this->http_requests[] = $url;
		foreach ( $this->http_mocks as $needle => $response ) {
			if ( false !== strpos( $url, $needle ) ) {
				return $response;
			}
		}
		$this->fail( 'Unexpected HTTP request to ' . $url );
	}

	protected function mock_http( $url_contains, $body, $code = 200 ) {
		if ( ! is_string( $body ) ) {
			$body = wp_json_encode( $body );
		}
		$this->http_mocks[ $url_contains ] = array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Error',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	protected function mock_http_error( $url_contains, $message = 'connection failed' ) {
		$this->http_mocks[ $url_contains ] = new WP_Error( 'http_request_failed', $message );
	}

	protected function audit_table() {
		global $wpdb;

		return $wpdb->prefix . 'fwvs_file_audit';
	}

	/**
	 * @return array[] rows from the audit table, optionally filtered by status code.
	 */
	protected function audit_rows( $status = null ) {
		global $wpdb;
		$table = $this->audit_table();
		if ( null === $status ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY ID", ARRAY_A ); // phpcs:ignore WordPress.DB
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %d ORDER BY ID", $status ), ARRAY_A ); // phpcs:ignore WordPress.DB
	}

	protected function utilities() {
		return \Fullworks_Scanner\Includes\Utilities::get_instance();
	}

	protected function login_as_admin() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		return $admin;
	}
}
