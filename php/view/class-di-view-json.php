<?php

	/**
	 * The DI_View_JSON class
	 *
	 * This file defines the DI_View_JSON class. This
	 * class produces JSON-formatted data about
	 * Digging In sites and associated data.
	 *
	 * @package UBCAR
	 */

	/**
	 * The UBCAR_Data_JSON class.
	 */
	class DI_View_JSON {

	   // TODO: create JSON data for individual ubcar_point and associated data

		/**
		 * The UBCAR_Data_JSON constructor.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->add_actions();
		}

    /**
     * This function adds the UBCAR_Data_JSON actions and filters.
     *
     * @access public
     * @return void
     */
    function add_actions() {
        add_filter( 'query_vars', array( $this, 'query_vars' ) );
        add_action( 'parse_request', array( $this, 'parse_request' ) );
    }

    /**
     * This function adds the desired UBCAR query variable to the query_vars
     * array
     *
     * @param array $query_vars
     *
     * @access public
     * @return array
     */
    function query_vars( $query_vars ) {
        $query_vars[] = 'di_download_json';
        return $query_vars;
    }

    /**
     * This function sets the behavior to be performed if the UBCAR query
     * variable is set in a request.
     *
     * @param object $wp
     *
     * @access public
     * @return void
     */
    function parse_request( $wp ) {
        if( array_key_exists( 'di_download_json', $wp->query_vars ) ) {
            $test = $this->di_retrieve_sites();
            wp_send_json( $test );
            exit;
        }
    }

    function di_retrieve_sites() {
        $ubcar_points = array();
        $args = array( 'posts_per_page' => -1, 'post_type' => 'di_site' );
        $all_ubcar_points = get_posts( $args );
        foreach( $all_ubcar_points as $temp_point ) {
            $temp_inner_array = array();
            $temp_inner_array['id'] = $temp_point->ID;
            $temp_inner_array['longitude'] = get_post_meta( $temp_point->ID, 'di_site_longitude', true );
            $temp_inner_array['latitude'] = get_post_meta( $temp_point->ID, 'di_site_latitude', true );
            $temp_inner_array['description'] = $temp_point->post_content;
            $temp_inner_array['name'] = $temp_point->post_title;
						$temp_inner_array['distance'] = 0;
						$temp_inner_array['bearing'] = 0;
            $temp_thumbnails = get_post_meta( $temp_point->ID, 'di_site_media', true );
            if( !empty( $temp_thumbnails ) ) {
                $temp_media = array();
                foreach( $temp_thumbnails as $temp_thumbnail ) {
                    $temp_media_meta = get_post_meta( $temp_thumbnail, 'di_media_meta', true );
                    if( $temp_media_meta != "" ) {
                        $temp_media_type = $temp_media_meta['type'];
                        if( $temp_media_type == 'image' ) {
                            $temp_media_url['url'] = wp_get_attachment_url( $temp_media_meta['url'] );
                            array_push( $temp_media, $temp_media_url );
                        }
                    }
                }
                $temp_inner_array['media'] = $temp_media;
            }
            array_push( $ubcar_points, $temp_inner_array );
        }
        return $ubcar_points;
    }

	}

?>
