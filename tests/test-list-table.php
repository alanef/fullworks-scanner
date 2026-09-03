<?php
/**
 * Report list table: querying, sorting and row actions.
 */

use Fullworks_Scanner\Admin\List_Table_Code_Scan;

class Test_List_Table extends Scanner_Test_Case {

	public function set_up() {
		parent::set_up();
		require_once ABSPATH . 'wp-admin/includes/admin.php';
		$this->login_as_admin();
		set_current_screen( 'fullworks-scanner_page_fullworks-scanner-code-scan-report' );
		$_REQUEST['page'] = 'fullworks-scanner-code-scan-report';

		$u = $this->utilities();
		$u->file_scan_log_write( 'aaa-plugin', 496, 'plugin', 'T', 'maybe abandoned' );
		$u->file_scan_log_write( 'zzz-theme', 999, 'theme', 'T', 'insecure' );
		$u->file_scan_log_write( 'mmm-plugin', 498, 'plugin', 'T', 'update' );
	}

	public function test_default_order_is_newest_first() {
		$items = List_Table_Code_Scan::get();
		$this->assertSame( array( 'mmm-plugin', 'zzz-theme', 'aaa-plugin' ), wp_list_pluck( $items, 'filepath' ) );
	}

	public function test_sort_by_filepath_ascending_and_descending() {
		$_REQUEST['orderby'] = 'filepath';
		$_REQUEST['order']   = 'asc';
		$this->assertSame( array( 'aaa-plugin', 'mmm-plugin', 'zzz-theme' ), wp_list_pluck( List_Table_Code_Scan::get(), 'filepath' ) );

		$_REQUEST['order'] = 'desc';
		$this->assertSame( array( 'zzz-theme', 'mmm-plugin', 'aaa-plugin' ), wp_list_pluck( List_Table_Code_Scan::get(), 'filepath' ) );
	}

	public function test_sort_by_status() {
		$_REQUEST['orderby'] = 'status';
		$_REQUEST['order']   = 'asc';
		$this->assertSame( array( '496', '498', '999' ), wp_list_pluck( List_Table_Code_Scan::get(), 'status_code' ) );
	}

	public function test_unknown_orderby_and_order_fall_back_safely() {
		$_REQUEST['orderby'] = 'filepath; DROP TABLE x';
		$_REQUEST['order']   = 'sideways';
		$items               = List_Table_Code_Scan::get();
		$this->assertCount( 3, $items );
		$this->assertSame( 'mmm-plugin', $items[0]['filepath'] );
	}

	public function test_status_column_is_labelled_and_coloured_by_severity() {
		$_REQUEST['orderby'] = 'status';
		$_REQUEST['order']   = 'desc';
		$items               = List_Table_Code_Scan::get();
		$this->assertStringContainsString( 'Insecure version', $items[0]['status'] );
		$this->assertStringContainsString( '#dc3232', $items[0]['status'] );
		$this->assertStringContainsString( 'Plugin Abandoned', $items[2]['status'] );
		$this->assertStringContainsString( '#ffb900', $items[2]['status'] );
	}

	public function test_pagination() {
		$this->assertCount( 2, List_Table_Code_Scan::get( 2, 1 ) );
		$this->assertCount( 1, List_Table_Code_Scan::get( 2, 2 ) );
		$this->assertSame( 3, (int) List_Table_Code_Scan::record_count() );
	}

	public function test_accept_unaccept_and_delete() {
		$rows = $this->audit_rows();
		$id   = (int) $rows[0]['ID'];

		List_Table_Code_Scan::accept( $id );
		$this->assertCount( 2, List_Table_Code_Scan::get() );
		$_GET['type'] = 'accepted';
		$accepted     = List_Table_Code_Scan::get();
		$this->assertCount( 1, $accepted );
		$this->assertSame( 'aaa-plugin', $accepted[0]['filepath'] );
		unset( $_GET['type'] );

		List_Table_Code_Scan::unaccept( $id );
		$this->assertCount( 3, List_Table_Code_Scan::get() );

		List_Table_Code_Scan::delete( $id );
		$this->assertCount( 2, List_Table_Code_Scan::get() );
	}

	public function test_prepare_items_renders_table() {
		$table = new List_Table_Code_Scan();
		$table->prepare_items();
		$this->assertCount( 3, $table->items );

		ob_start();
		$table->display();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'aaa-plugin', $html );
		$this->assertStringContainsString( 'Accept and Ignore in future scans', $html );
		$this->assertStringContainsString( 'Update plugins', $html );
	}
}
