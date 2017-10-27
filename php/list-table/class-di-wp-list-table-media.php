<?php

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}


	class DI_WP_List_Table_Media extends WP_List_Table {

    function __construct(){
    	global $status, $page;
      parent::__construct( array(
	      'singular'  => __( 'media', 'mylisttable' ),     //singular name of the listed records
	      'plural'    => __( 'media', 'mylisttable' ),   //plural name of the listed records
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
	    _e( 'No media found.' );
	  }

	  function column_default( $item, $column_name ) {
	    switch( $column_name ) {
	        case 'id':
					case 'title':
	        case 'uploader':
					case 'location':
					case 'media':
	        case 'description':
					case 'display':
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
		  );
		  return $sortable_columns;
		}

		function get_columns(){
			$columns = array(
			  'cb' => '<input type="checkbox" />',
			  'id' => __( 'ID', 'mylisttable' ),
			  'title' => __( 'Title', 'mylisttable' ),
			  'uploader' => __( 'Uploader', 'mylisttable' ),
				'description' => __( 'Description', 'mylisttable' ),
				'location' => __( 'Location', 'mylisttable' ),
				'display' => __( 'Display Publicly?', 'mylisttable' ),
				'media' => __( 'Media', 'mylisttable' )
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
			  'edit'      => sprintf('<a href="?page=%s&action=%s&media=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
			  'delete'    => sprintf('<a href="?page=%s&action=%s&media=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		  return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
		}

		function column_cb($item) {
		  return sprintf(
		    '<input type="checkbox" name="media[]" value="%s" />', $item['id']
		  );
		}

		function prepare_items() {
		  $columns  = $this->get_columns();
		  $hidden   = array();
		  $sortable = $this->get_sortable_columns();
		  $this->_column_headers = array( $columns, $hidden, $sortable );

			$di_sites = $this->di_get_media();

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

		function di_get_media() {
			global $wpdb;
			$response = array();
			$di_sites = get_posts( array( 'post_type' => 'di_media', 'order' => 'DESC', 'posts_per_page' => -1 ) );
			foreach ( $di_sites as $di_site ) {
				$tempArray = $this->di_get_site_metadata( $di_site->ID );
				array_push( $response, $tempArray );
			}
			return $response;
		}

		function di_get_site_metadata( $di_site_id ) {
			$di_site = get_post( $di_site_id );
			$di_media_meta = get_post_meta( $di_site->ID, 'di_media_meta', true );
			$di_site_author = get_user_by( 'id', $di_site->post_author );
			$tempArray = array();

			if( $di_media_meta['type'] == 'image' || $di_media_meta['type'] == 'imagewp' ) {
				$tempArray["url"] = wp_get_attachment_thumb_url( $di_media_meta['url'] );
				$tempArray["full_size_url"] = wp_get_attachment_url( $di_media_meta['url'] );
				$tempArray["media"] = '<a href="' . $tempArray["full_size_url"] . '"><img src="' . $tempArray["url"] . '" /></a>';
			} else if( $di_media_meta['type'] == 'video' ) {
				$tempArray["media"] = '<iframe width="150" height="150" src="//www.youtube.com/embed/' . $di_media_meta['url'] . '" frameborder="0" allowfullscreen></iframe>';
			} else {
				$tempArray["media"] = $di_media_meta['url'];
			}

			$location = get_post( $di_media_meta['location'] );
			$location_name = array();
			if( $location != null ) {
				$location_name["ID"] = $location->ID;
				$location_name["title"] = $location->post_title;
			} else {
				$location_name["ID"] = "?";
				$location_name["title"] = "Deleted location";
			}

			$tempArray["id"] = $di_site->ID;
			$tempArray["uploader"] = $di_site_author->first_name . ' ' . $di_site_author->last_name . ' (' . $di_site_author->user_login . ')';
			$tempArray["title"] = $di_site->post_title;
			$tempArray["description"] = $di_site->post_content;
			// $tempArray["media"] = get_post_meta( $di_site->ID, 'di_media_meta', true );
			$tempArray["location"] = $location_name["title"] . " (# " . $location_name["ID"] . ")";
			$tempArray["display"] = '<input type="checkbox" disabled />';
			return $tempArray;
		}

	}

?>
