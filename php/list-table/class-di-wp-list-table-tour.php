<?php

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}


	class DI_WP_List_Table_Tour extends WP_List_Table {

    function __construct(){
    	global $status, $page;
      parent::__construct( array(
	      'singular'  => __( 'tour', 'listtabledltour' ),     //singular name of the listed records
	      'plural'    => __( 'tours', 'listtabledltour' ),   //plural name of the listed records
	      'ajax'      => false        //does this table support ajax?
    	) );
    	add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

	  function admin_header() {
	    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	    if( 'di-tours' != $page )
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
	    _e( 'No soil tours found.' );
	  }

	  function column_default( $item, $column_name ) {
	    switch( $column_name ) {
	        case 'id':
					case 'title':
	        case 'uploader':
	        case 'description':
	            return $item[ $column_name ];
					case 'locations':
							return @implode( ",", $item[ $column_name ] );
	        default:
	            // return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	    }
	  }

		function get_sortable_columns() {
		  $sortable_columns = array(
		    'id' => array('id',false),
		    'title' => array('title',false),
		    'uploader' => array('uploader',false),
				'locations' => array('locations',false),
				'description' => array('description',false)
		  );
		  return $sortable_columns;
		}

		function get_columns(){
			$columns = array(
			  'cb' => '<input type="checkbox" />',
			  'id' => __( 'ID', 'listtabledltour' ),
			  'title' => __( 'Title', 'listtabledltour' ),
			  'uploader' => __( 'Uploader', 'listtabledltour' ),
				'locations' => __( 'Locations', 'listtabledltour' ),
				'description' => __( 'Description', 'listtabledltour' )
			);
			return $columns;
		}

		function usort_reorder( $a, $b ) {
		  // If no sort, default to title
		  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'title';
		  // If no order, default to asc
		  $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		  // Determine sort order
		  $result = strcmp( $a[$orderby], $b[$orderby] );
		  // Send final sort direction to usort
		  return ( $order === 'asc' ) ? $result : -$result;
		}

		function column_id($item){
		  $actions = array(
			  'edit'      => sprintf('<a href="?page=%s&action=%s&tour=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
			  'delete'    => sprintf('<a href="?page=%s&action=%s&tour=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		  return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
		}

		function column_cb($item) {
		  return sprintf(
		    '<input type="checkbox" name="tour[]" value="%s" />', $item['id']
		  );
		}

		function prepare_items() {
		  $columns  = $this->get_columns();
		  $hidden   = array();
		  $sortable = $this->get_sortable_columns();
		  $this->_column_headers = array( $columns, $hidden, $sortable );

			$di_tours = $this->di_get_tours();

		  usort( $di_tours, array( &$this, 'usort_reorder' ) );

		  $per_page = 5;
		  $current_page = $this->get_pagenum();
		  $total_items = count( $di_tours );
		  // only ncessary because we have sample data
		  $di_tours_subset = array_slice( $di_tours,( ( $current_page-1 ) * $per_page ), $per_page );
			$this->set_pagination_args( array(
		    'total_items' => $total_items,                  //WE have to calculate the total number of items
		    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		  ) );
		  $this->items = $di_tours_subset;
		}

		function di_get_tours() {
			global $wpdb;
			$response = array();
			$di_tours = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'di_tour', 'order' => 'DESC' ) );
			foreach ( $di_tours as $di_tour ) {
				$tempArray = $this->di_get_tour_metadata( $di_tour->ID );
				array_push( $response, $tempArray );
			}
			return $response;
		}

		function di_get_tour_metadata( $di_tour_id ) {
			$di_tour = get_post( $di_tour_id );
			$di_tour_meta_sites = get_post_meta( $di_tour->ID, 'di_tour_sites', true );
			$di_tour_author = get_user_by( 'id', $di_tour->post_author );
			$tempArray = array();
			$tempArray["id"] = $di_tour->ID;
			$tempArray["uploader"] = $di_tour_author->first_name . ' ' . $di_tour_author->last_name . ' (' . $di_tour_author->user_login . ')';
			$tempArray["title"] = $di_tour->post_title;
			$tempArray["date"] = get_the_date( 'Y-m-d', $di_tour->ID );
			$tempArray["description"] = $di_tour->post_content;
			$tempArray["locations"] = $di_tour_meta_sites;
			return $tempArray;
		}

	}

?>
