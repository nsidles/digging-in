<?php

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}


	class DL_WP_List_Table_Site extends WP_List_Table {

    function __construct(){
    	global $status, $page;
      parent::__construct( array(
	      'singular'  => __( 'site', 'listtabledlsite' ),     //singular name of the listed records
	      'plural'    => __( 'sites', 'listtabledlsite' ),   //plural name of the listed records
	      'ajax'      => false        //does this table support ajax?
    	) );
    	add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

	  function admin_header() {
	    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
	    if( 'di-sites' != $page )
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
	    _e( 'No soil sites found.' );
	  }

	  function column_default( $item, $column_name ) {
	    switch( $column_name ) {
	        case 'id':
					case 'title':
	        case 'uploader':
					case 'location':
					case 'walkthroughs':
	        case 'description':
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
				'walkthroughs' => array('walkthroughs', false),
				'description' => array('description',false)
		  );
		  return $sortable_columns;
		}

		function get_columns(){
			$columns = array(
			  'cb' => '<input type="checkbox" />',
			  'id' => __( 'ID', 'listtabledlsite' ),
			  'title' => __( 'Title', 'listtabledlsite' ),
			  'uploader' => __( 'Uploader', 'listtabledlsite' ),
				'location' => __( 'Location', 'listtabledlsite' ),
				// 'walkthroughs' => __( 'Walkthroughs', 'listtabledlsite' ),
				'description' => __( 'Description', 'listtabledlsite' )
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
			  'edit'      => sprintf('<a href="?page=%s&action=%s&site=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
			  'delete'    => sprintf('<a href="?page=%s&action=%s&site=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		  return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
		}


		function column_cb($item) {
		  return sprintf(
		    '<input type="checkbox" name="book[]" value="%s" />', $item['id']
		  );
		}

		function prepare_items() {
		  $columns  = $this->get_columns();
		  $hidden   = array();
		  $sortable = $this->get_sortable_columns();
		  $this->_column_headers = array( $columns, $hidden, $sortable );

			$di_sites = $this->di_get_sites();

		  usort( $di_sites, array( &$this, 'usort_reorder' ) );

		  $per_page = 5;
		  $current_page = $this->get_pagenum();
		  $total_items = count( $di_sites );
		  // only ncessary because we have sample data
		  $di_sites_subset = array_slice( $di_sites,( ( $current_page-1 ) * $per_page ), $per_page );
			$this->set_pagination_args( array(
		    'total_items' => $total_items,                  //WE have to calculate the total number of items
		    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		  ) );
		  $this->items = $di_sites_subset;
		}

		function di_get_sites() {
			global $wpdb;
			$response = array();
			$di_sites = get_posts( array( 'post_type' => 'di_site', 'order' => 'DESC', 'posts_per_page' => -1 ) );
			foreach ( $di_sites as $di_site ) {
				$tempArray = $this->di_get_site_metadata( $di_site->ID );
				array_push( $response, $tempArray );
			}
			return $response;
		}

		function di_get_site_metadata( $di_site_id ) {
			$di_site = get_post( $di_site_id );
			$di_site_meta_latitude = get_post_meta( $di_site->ID, 'di_site_latitude', true );
			$di_site_meta_longitude = get_post_meta( $di_site->ID, 'di_site_longitude', true );
			$di_site_author = get_user_by( 'id', $di_site->post_author );
			$tempArray = array();
			$tempArray["id"] = $di_site->ID;
			$tempArray["uploader"] = $di_site_author->first_name . ' ' . $di_site_author->last_name . ' (' . $di_site_author->user_login . ')';
			$tempArray["title"] = $di_site->post_title;
			$tempArray["date"] = get_the_date( 'Y-m-d', $di_site->ID );
			$tempArray["description"] = $di_site->post_content;
			$tempArray["location"] = $di_site_meta_latitude . ", " . $di_site_meta_longitude;
			$tempArray["walkthroughs"] = "<select></select>" . get_post_meta( $di_site->ID, 'di_site_walkthroughs', true );
			return $tempArray;
		}

	}

?>
