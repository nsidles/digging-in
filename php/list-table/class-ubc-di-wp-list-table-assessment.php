<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class UBC_DI_WP_List_Table_Assessment extends WP_List_Table {

	function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular'  => __( 'assessment', 'mylisttable' ),     //singular name of the listed records
			'plural'    => __( 'assessments', 'mylisttable' ),   //plural name of the listed records
			'ajax'      => false, //does this table support ajax?
		) );
		add_action( 'admin_head', array( &$this, 'admin_header' ) );
	}

	/**
	 * This function creates the structure of the table.
	 *
	 * @access public
	 * @return void
	 */
	function admin_header() {
		$page = ( isset( $_GET['page'] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : false;
		if ( 'my_list_test' != $page ) {
			return;
		}
		echo '<style type="text/css">';
		echo '.wp-list-table .column-id { width: 5%; }';
		echo '.wp-list-table .column-title { width: 15%; }';
		echo '.wp-list-table .column-uploader { width: 10%; }';
		echo '.wp-list-table .column-location { width: 15%; }';
		echo '.wp-list-table .column-walkthroughs { width: 15%; }';
		echo '.wp-list-table .column-description { width: 50%; }';
		echo '</style>';
	}

	/**
	 * This function appears in the table if no objects are found.
	 *
	 * @access public
	 * @return void
	 */
	function no_items() {
		esc_attr_e( 'No assessments found.' );
	}

	/**
	 * This function sets the defaults for each column.
	 *
	 * @param array $item The item to display
	 * @param string $column_name The column name to display
	 *
	 * @access public
	 * @return string Column name to display
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'title':
			case 'uploader':
			case 'expiration':
			case 'sites':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * This function gets sortable columns.
	 *
	 * @access public
	 * @return array Sortable columns
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', false ),
			'title' => array( 'title', false ),
			'uploader' => array( 'uploader', false ),
			'expiration' => array( 'expiration', false ),
			'sites' => array( 'sites', false ),
		);
		return $sortable_columns;
	}

	/**
	 * This function gets non-sortable columns.
	 *
	 * @access public
	 * @return array Non-sortable columns
	 */
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'id' => __( 'ID', 'mylisttable' ),
			'uploader' => __( 'Uploader', 'mylisttable' ),
			'title' => __( 'Assessment Title', 'mylisttable' ),
			'expiration' => __( 'Expiration', 'mylisttable' ),
			'sites' => __( 'Soil Sites', 'mylisttable' ),
		);
		return $columns;
	}

	/**
	 * This function sorts two string values.
	 *
	 * @param string $a The first item
	 * @param string $b The second item
	 *
	 * @access public
	 * @return string First string in alphabetical order
	 */
	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id';
		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
		// Determine sort order
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		// Send final sort direction to usort
		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * This function sets actions for the ID column
	 *
	 * @param array $item The item to set actions for
	 *
	 * @access public
	 * @return string Action buttons
	 */
	function column_id( $item ) {
		if ( isset( $_REQUEST['page'] ) ) {
			$actions = array(
				'edit'      => sprintf( '<a href="?page=%s&action=%s&assessment=%s">Edit</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'edit', $item['id'] ),
				'delete'    => sprintf( '<a href="?page=%s&action=%s&assessment=%s">Delete</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'delete', $item['id'] ),
				'copy'    => sprintf( '<a href="?page=%s&action=%s&assessment=%s">Copy</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'copy', $item['id'] ),
			);
			return sprintf( '%1$s %2$s', $item['id'], $this->row_actions( $actions ) );
		}
	}

	/**
	 * This function sets actions for the checkbox column
	 *
	 * @param array $item The item to set actions for
	 *
	 * @access public
	 * @return string Checkbox
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="assessment[]" value="%s" />', $item['id']
		);
	}

	/**
	 * This function prepares items for display in the table, including limits and
	 * pagination.
	 *
	 * @access public
	 * @return void
	 */
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$ubc_di_sites = $this->ubc_di_get_assessments();

		usort( $ubc_di_sites, array( &$this, 'usort_reorder' ) );

		$per_page = 50;
		$current_page = $this->get_pagenum();
		$total_items = count( $ubc_di_sites );
		// only ncessary because we have sample data
		$ubc_di_sites_subset = array_slice( $ubc_di_sites, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                   //WE have to determine how many items to show on a page
		) );
		$this->items = $ubc_di_sites_subset;
	}

	/**
	 * This function gets the Digging In items for display.
	 *
	 * @access public
	 * @return array $response The Digging In items.
	 */
	function ubc_di_get_assessments() {
		global $wpdb;
		$response = array();
		$ubc_di_sites = get_posts( array(
			'post_type' => 'ubc_di_assessment',
			'order' => 'DESC',
			'posts_per_page' => -1,
		) );
		foreach ( $ubc_di_sites as $ubc_di_site ) {
			$temp_array = $this->ubc_di_get_site_metadata( $ubc_di_site->ID );
			array_push( $response, $temp_array );
		}
		return $response;
	}

	/**
	 * This function gets metadata from the Digging In item for display.
	 *
	 * @param int $ubc_di_site_id The Digging In item.
	 *
	 * @access public
	 * @return array $temp_array The Digging In item's metadata.
	 */
	function ubc_di_get_site_metadata( $ubc_di_site_id ) {
		$ubc_di_site = get_post( $ubc_di_site_id );
		$ubc_di_site_meta_sites = get_post_meta( $ubc_di_site->ID, 'ubc_di_assessment_sites', true );
		if ( null == $ubc_di_site_meta_sites[0] ) {
			$ubc_di_site_meta_sites[0] = 'n/a';
		}
		$ubc_di_site_meta_content = get_post_meta( $ubc_di_site->ID, 'ubc_di_assessment_slides', true );
		$ubc_di_site_end_date = get_post_meta( $ubc_di_site->ID, 'ubc_di_assessment_end_date', true );
		$ubc_di_site_author = get_user_by( 'id', $ubc_di_site->post_author );
		$temp_array = array();
		$temp_array['id'] = $ubc_di_site->ID;
		$temp_array['uploader'] = $ubc_di_site_author->first_name . ' ' . $ubc_di_site_author->last_name . ' (' . $ubc_di_site_author->user_login . ')';
		$temp_array['title'] = $ubc_di_site->post_title;
		$temp_array['expiration'] = $ubc_di_site_end_date;
		$temp_array['sites'] = $ubc_di_site_meta_sites[0];
		return $temp_array;
	}

}
