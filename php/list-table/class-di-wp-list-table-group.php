<?php

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}


	class DI_WP_List_Table_Group extends WP_List_Table {

    function __construct(){
    	global $status, $page;
      parent::__construct( array(
	      'singular'  => __( 'group', 'mylisttable' ),     //singular name of the listed records
	      'plural'    => __( 'groups', 'mylisttable' ),   //plural name of the listed records
	      'ajax'      => false        //does this table support ajax?
    	) );
    	add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

	  function admin_header() {
	    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	    if( 'my_list_test' != $page )
	    return;
	    echo '<style type="text/css">';
	    echo '.wp-list-table .column-id { width: 5%; }';
	    echo '.wp-list-table .column-title { width: 15%; }';
	    echo '.wp-list-table .column-uploader { width: 10%; }';
	    echo '.wp-list-table .column-location { width: 15%; }';
	    echo '.wp-list-table .column-walkthroughs { width: 15%; }';
	    echo '.wp-list-table .column-description { width: 50%; }';
	    echo '</style>';
	  }

	  function no_items() {
	    _e( 'No student groups found.' );
	  }

	  function column_default( $item, $column_name ) {
	    switch( $column_name ) {
	        case 'id':
					case 'title':
	        case 'uploader':
					case 'ta':
					case 'students':
	            return $item[ $column_name ];
	        default:
	            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	    }
	  }

		function get_sortable_columns() {
		  $sortable_columns = array(
		    'id' => array('id',false),
		    'title' => array('title',false),
		    'uploader' => array('uploader',false),
				'location' => array('location',false),
				'ta' => array('ta', false),
				'students' => array('students', false)
		  );
		  return $sortable_columns;
		}

		function get_columns(){
			$columns = array(
			  'cb' => '<input type="checkbox" />',
			  'id' => __( 'ID', 'mylisttable' ),
			  'title' => __( 'Title', 'mylisttable' ),
			  'uploader' => __( 'Uploader', 'mylisttable' ),
				'ta' => __( 'T.A.', 'mylisttable' ),
				'students' => __( 'Students', 'mylisttable' )
			);
			return $columns;
		}

		function usort_reorder( $a, $b ) {
		  // If no sort, default to title
		  $orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'title';
		  // If no order, default to asc
		  $order = ( ! empty($_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'asc';
		  // Determine sort order
		  $result = strcmp( $a[$orderby], $b[$orderby] );
		  // Send final sort direction to usort
		  return ( $order === 'asc' ) ? $result : -$result;
		}

		function column_id($item){
		  $actions = array(
			  'edit'      => sprintf('<a href="?page=%s&action=%s&group=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
			  'delete'    => sprintf('<a href="?page=%s&action=%s&group=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		  return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
		}

		function column_cb($item) {
		  return sprintf(
		    '<input type="checkbox" name="group[]" value="%s" />', $item['id']
		  );
		}

		function prepare_items() {
		  $columns  = $this->get_columns();
		  $hidden   = array();
		  $sortable = $this->get_sortable_columns();
		  $this->_column_headers = array( $columns, $hidden, $sortable );

			$di_groups = $this->di_get_groups();

		  usort( $di_groups, array( &$this, 'usort_reorder' ) );

		  $per_page = 5;
		  $current_page = $this->get_pagenum();
		  $total_items = count( $di_groups );
		  // only ncessary because we have sample data
		  $di_groups_subset = array_slice( $di_groups,( ( $current_page-1 ) * $per_page ), $per_page );
			$this->set_pagination_args( array(
		    'total_items' => $total_items,                  //WE have to calculate the total number of items
		    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		  ) );
		  $this->items = $di_groups_subset;
		}

		function di_get_groups() {
			global $wpdb;
			$response = array();
			$di_groups = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'di_group', 'order' => 'DESC' ) );
			foreach ( $di_groups as $di_group ) {
				$tempArray = $this->di_get_group_metadata( $di_group->ID );
				array_push( $response, $tempArray );
			}
			return $response;
		}

		function di_get_group_metadata( $di_group_id ) {
			$di_group = get_post( $di_group_id );
			$di_group_meta_students = get_post_meta( $di_group->ID, 'di_group_people', true );
			$di_group_meta_ta = get_post_meta( $di_group->ID, 'di_group_ta', true );
			$di_group_author = get_user_by( 'id', $di_group->post_author );
			$tempArray = array();
			$tempArray["id"] = $di_group->ID;
			$tempArray["uploader"] = $di_group_author->first_name . ' ' . $di_group_author->last_name . ' (' . $di_group_author->user_login . ')';
			$tempArray["title"] = $di_group->post_title;
			$tempArray["date"] = get_the_date( 'Y-m-d', $di_group->ID );
			$tempArray["ta"] = get_user_by( 'id', $di_group_meta_ta )->first_name . " " . get_user_by( 'id', $di_group_meta_ta )->last_name;
			if( $di_group_meta_students != "" ) {
				foreach( $di_group_meta_students as $student ) {
					$tempNameArray[] = get_user_by( 'id', $student )->first_name . " " . get_user_by( 'id', $student )->last_name;
				}
				$tempArray["students"] = implode( ", ", $tempNameArray);
			} else {
				$tempArray["students"] = "";
			}
			return $tempArray;
		}

	}

?>
