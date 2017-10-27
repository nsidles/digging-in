<?php

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}


	class DI_WP_List_Table_Assessment_Result extends WP_List_Table {

    function __construct(){
    	global $status, $page;
      parent::__construct( array(
	      'singular'  => __( 'book', 'mylisttable' ),     //singular name of the listed records
	      'plural'    => __( 'books', 'mylisttable' ),   //plural name of the listed records
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
	    _e( 'No assessment results found.' );
	  }

	  function column_default( $item, $column_name ) {
	    switch( $column_name ) {
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
	            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	    }
	  }

		function get_sortable_columns() {
		  $sortable_columns = array(
		    'id' => array('id',false),
				'date' => array('date',false),
		    'uploader' => array('uploader',false),
		    'uploader_group' => array('uploader_group',false),
				'site' => array('site',false),
				'assessment' => array('assessment',false)
		  );
		  return $sortable_columns;
		}

		function get_columns(){
			$columns = array(
				'cb'        => '<input type="checkbox" />',
			  'id' => __( 'ID', 'mylisttable' ),
				'date' => __( 'Date', 'mylisttable' ),
				'uploader' => __( 'Uploader (#ID)' , 'mylisttable' ),
				'uploader_group' => __( 'Uploader Group	 (#ID)' , 'mylisttable' ),
			  'site' => __( 'Site #ID', 'mylisttable' ),
				'assessment' => __( 'Assessment #ID', 'mylisttable' ),
			  'assessment_result' => __( 'View', 'mylisttable' )
			);
			if( current_user_can( 'manage_options' ) )
				$columns['assessment_result_evaluation'] = __( 'Evaluate', 'mylisttable' );
			return $columns;
		}

		function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="book[]" value="%s" />', $item['id']
        );
    }

		function usort_reorder( $a, $b ) {
		  // If no sort, default to title
		  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $sanitize_text_field( _GET['orderby'] ) : 'id';
		  // If no order, default to asc
		  $order = ( ! empty($_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'asc';
		  // Determine sort order
		  $result = strcmp( $a[$orderby], $b[$orderby] );
		  // Send final sort direction to usort
		  return ( $order === 'asc' ) ? $result : -$result;
		}

		function column_id($item){
			$actions = array(
			  'delete'    => sprintf('<a href="?page=%s&action=%s&assessment_result=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		  return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
		}

		// function get_bulk_actions() {
		// 		$actions = array(
		// 			'delete'    => 'Delete'
		// 		);
		// 		return $actions;
		// }

		function prepare_items() {
		  $columns  = $this->get_columns();
		  $hidden   = array();
		  $sortable = $this->get_sortable_columns();
		  $this->_column_headers = array( $columns, $hidden, $sortable );

			$di_sites = $this->di_get_assessment_results();

		  usort( $di_sites, array( &$this, 'usort_reorder' ) );

		  $per_page = 50;
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

		function di_get_assessment_results() {
			global $wpdb;
			$response = array();
			if( current_user_can( 'manage_options' ) ) {
				$di_sites = get_posts( array( 'post_type' => 'di_assessment_result', 'order' => 'DESC', 'posts_per_page' => -1 ) );
			} else {
				$di_sites = get_posts( array( 'post_type' => 'di_assessment_result', 'order' => 'DESC', 'posts_per_page' => -1 ) );
				$temp_di_sites = array();
				$user_id = get_current_user_id();
				foreach( $di_sites as $di_site ) {
					$di_asr_group_id = get_post_meta( $di_site->ID, 'di_assessment_result_group', true );
					$di_group_people = get_post_meta( $di_asr_group_id, 'di_group_people', true );
					if( $di_group_people != '' ) {
						foreach( $di_group_people as $di_group_person ) {
							if( $user_id == $di_group_person ) {
								$temp_di_sites[] = $di_site;
							}
						}
					}
				}
				$di_sites = $temp_di_sites;
			}
			foreach ( $di_sites as $di_site ) {
				$tempArray = $this->di_get_site_metadata( $di_site->ID );
				if( $tempArray != null )
					array_push( $response, $tempArray );
			}
			return $response;
		}

		function filter_posts( $posts ) {
			if( isset( $_GET['title'] ) && $_GET['title'] != '' ) {
				$title = $_GET['title'];
				foreach( $posts as $post ) {
					if( $post->post_title ) {

					}
				}
			}

			return $posts;
		}

		function di_get_site_metadata( $di_asr_id ) {
			$di_asr = get_post( $di_asr_id );
			// $di_asr_meta_group = get_post_meta( $di_asr->ID, 'di_assessment_group', true );
			// $di_asr_meta_site_id = get_post_meta( $di_asr->ID, 'di_assessment_site_id', true );
			// $di_asr_meta_site_name = get_post( $di_asr_meta_site_id )->post_title;
			// $di_asr_group_id = get_post_meta( $di_asr->ID, 'di_assessment_group_id', true );
			$di_asr_assessment_id = get_post_meta( $di_asr->ID, 'di_assessment_result_assessment', true );
			$di_asr_site_id = get_post_meta( $di_asr->ID, 'di_assessment_result_site', true );
			$di_site_meta_content = get_post_meta( $di_asr->ID, 'di_assessment_', true );
			$di_asr_author = get_user_by( 'id', $di_asr->post_author );
			$di_asr_group_id = get_post_meta( $di_asr->ID, 'di_assessment_result_group', true );
			$di_asr_group = get_post( $di_asr_group_id );
			$tempArray = array();
			$tempArray["id"] = $di_asr->ID;
			$tempArray["date"] = $di_asr->post_date;
			$tempArray["uploader"] = $di_asr_author->first_name . ' ' . $di_asr_author->last_name . ' (' . $di_asr_author->ID . ')';
			@$tempArray["uploader_group"] = $di_asr_group->post_title . ' (#' . $di_asr_group_id . ')';
			$tempArray["site"] = get_post( $di_asr_site_id )->post_title . ' (#' . $di_asr_site_id . ')';
			$tempArray["assessment"] = get_post( $di_asr_assessment_id )->post_title . ' (#' . $di_asr_assessment_id . ')';
			$tempArray["assessment_result"] = '<div class="button asr-result asr-result-view" assessment_result="' . $di_asr_id . '">View Result</div>';
			if( current_user_can( 'manage_options' ) )
				$tempArray["assessment_result_evaluation"] = '<div class="button asr-result-evaluate" assessment_result="' . $di_asr_id . '">Evaluate</div>';

			if( isset( $_GET['di_group'] ) && $_GET['di_group'] != $di_asr_group_id )
				return;
			if( isset( $_GET['di_assessment'] ) && $_GET['di_assessment'] != $di_asr_assessment_id )
				return;
			if( isset( $_GET['di_site'] ) && $_GET['di_site'] != $di_asr_site_id )
				return;
			return $tempArray;
		}

	}

?>
