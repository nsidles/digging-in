<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class UBC_DI_WP_List_Table_Assessment_Result extends WP_List_Table {

	/**
	 * This function constructs the UBC_DI_WP_List_Table_Site object.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular'  => __( 'result', 'mylisttable' ),     //singular name of the listed records
			'plural'    => __( 'results', 'mylisttable' ),   //plural name of the listed records
			'ajax'      => false,        //does this table support ajax?
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
		esc_attr_e( 'No assessment results found.' );
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
			case 'date':
			case 'uploader':
			case 'uploader_group':
			case 'site':
			case 'assessment':
			case 'assessment_result':
			case 'assessment_result_evaluation':
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
			'date' => array( 'date', false ),
			'uploader' => array( 'uploader', false ),
			'uploader_group' => array( 'uploader_group', false ),
			'site' => array( 'site', false ),
			'assessment' => array( 'assessment', false ),
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
			'cb'        => '<input type="checkbox" />',
			'id' => __( 'ID', 'mylisttable' ),
			'date' => __( 'Date', 'mylisttable' ),
			'uploader' => __( 'Uploader (#ID)', 'mylisttable' ),
			'uploader_group' => __( 'Uploader Group	 (#ID)', 'mylisttable' ),
			'site' => __( 'Site #ID', 'mylisttable' ),
			'assessment' => __( 'Assessment #ID', 'mylisttable' ),
			'assessment_result' => __( 'View', 'mylisttable' ),
		);
		if ( current_user_can( 'manage_options' ) ) {
			$columns['assessment_result_evaluation'] = __( 'Evaluate', 'mylisttable' );
		}
		return $columns;
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
			'<input type="checkbox" name="result[]" value="%s" />', $item['id']
		);
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
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';
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
				'delete'    => sprintf( '<a href="?page=%s&action=%s&assessment_result=%s">Delete</a>', sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'delete', $item['id'] ),
			);
			return sprintf( '%1$s %2$s', $item['id'], $this->row_actions( $actions ) );
		}
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

		$ubc_di_sites = $this->ubc_di_get_assessment_results();

		usort( $ubc_di_sites, array( &$this, 'usort_reorder' ) );

		$per_page = 50;
		$current_page = $this->get_pagenum();
		$total_items = count( $ubc_di_sites );
		// only ncessary because we have sample data
		$ubc_di_sites_subset = array_slice( $ubc_di_sites, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
		) );
		$this->items = $ubc_di_sites_subset;
	}

	/**
	 * This function gets the Digging In items for display.
	 *
	 * @access public
	 * @return array $response The Digging In items.
	 */
	function ubc_di_get_assessment_results() {
		global $wpdb;
		$response = array();
		if ( current_user_can( 'manage_options' ) ) {
			$ubc_di_sites = get_posts( array(
				'post_type' => 'ubc_di_assess_result',
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
		} else {
			$ubc_di_sites = get_posts( array(
				'post_type' => 'ubc_di_assess_result',
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
			$temp_ubc_di_sites = array();
			$user_id = get_current_user_id();
			foreach ( $ubc_di_sites as $ubc_di_site ) {
				$ubc_di_asr_group_id = get_post_meta( $ubc_di_site->ID, 'ubc_di_assessment_result_group', true );
				$ubc_di_group_people = get_post_meta( $ubc_di_asr_group_id, 'ubc_di_group_people', true );
				if ( '' != $ubc_di_group_people ) {
					foreach ( $ubc_di_group_people as $ubc_di_group_person ) {
						if ( $user_id == $ubc_di_group_person ) {
							$temp_ubc_di_sites[] = $ubc_di_site;
						}
					}
				}
			}
			$ubc_di_sites = $temp_ubc_di_sites;
		}
		foreach ( $ubc_di_sites as $ubc_di_site ) {
			$temp_array = $this->ubc_di_get_site_metadata( $ubc_di_site->ID );
			if ( null != $temp_array ) {
				array_push( $response, $temp_array );
			}
		}
		return $response;
	}

	/**
	 * This function filters posts by title.
	 *
	 * @param array $posts posts to filter
	 *
	 * @access public
	 * @return array $posts The filtered posts
	 */
	function filter_posts( $posts ) {
		if ( isset( $_GET['title'] ) && '' != $_GET['title'] ) {
			$title = sanitize_text_field( wp_unslash( $_GET['title'] ) );
			foreach ( $posts as $post ) {
				if ( $post->post_title ) {

				}
			}
		}
		return $posts;
	}

	/**
	 * This function gets metadata from the Digging In item for display.
	 *
	 * @param int $ubc_di_site_id The Digging In item.
	 *
	 * @access public
	 * @return array $temp_array The Digging In item's metadata.
	 */
	function ubc_di_get_site_metadata( $ubc_di_asr_id ) {
		$ubc_di_asr = get_post( $ubc_di_asr_id );
		$ubc_di_asr_assessment_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_assessment', true );
		$ubc_di_asr_site_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_site', true );
		$ubc_di_site_meta_content = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_', true );
		$ubc_di_asr_author = get_user_by( 'id', $ubc_di_asr->post_author );
		$ubc_di_asr_group_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_group', true );
		$ubc_di_asr_group = get_post( $ubc_di_asr_group_id );
		$temp_array = array();
		$temp_array['id'] = $ubc_di_asr->ID;
		$temp_array['date'] = $ubc_di_asr->post_date;
		$temp_array['uploader'] = $ubc_di_asr_author->first_name . ' ' . $ubc_di_asr_author->last_name . ' (' . $ubc_di_asr_author->ID . ')';
		$temp_array['uploader_group'] = $ubc_di_asr_group->post_title . ' (#' . $ubc_di_asr_group_id . ')';
		$temp_array['site'] = get_post( $ubc_di_asr_site_id )->post_title . ' (#' . $ubc_di_asr_site_id . ')';
		$temp_array['assessment'] = get_post( $ubc_di_asr_assessment_id )->post_title . ' (#' . $ubc_di_asr_assessment_id . ')';
		$temp_array['assessment_result'] = '<div class="button asr-result asr-result-view" assessment_result="' . $ubc_di_asr_id . '">View Result</div>';
		if ( current_user_can( 'manage_options' ) ) {
			$temp_array['assessment_result_evaluation'] = '<div class="button asr-result-evaluate" assessment_result="' . $ubc_di_asr_id . '">Evaluate</div>';
		}
		if ( isset( $_GET['ubc_di_group'] ) && $_GET['ubc_di_group'] != $ubc_di_asr_group_id ) {
			return;
		}
		if ( isset( $_GET['ubc_di_assessment'] ) && $_GET['ubc_di_assessment'] != $ubc_di_asr_assessment_id ) {
			return;
		}
		if ( isset( $_GET['ubc_di_site'] ) && $_GET['ubc_di_site'] != $ubc_di_asr_site_id ) {
			return;
		}
		return $temp_array;
	}

}
