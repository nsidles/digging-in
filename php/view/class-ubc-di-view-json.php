<?php

	/**
	 * The UBC_DI_View_JSON class
	 *
	 * This file defines the UBC_DI_View_JSON class. This
	 * class produces JSON-formatted data about
	 * Digging In sites and associated data.
	 *
	 * @package DiggingIn
	 */

	/**
	 * The UBC_DI_View_JSON class.
	 */
class UBC_DI_View_JSON {

	/**
	 * The UBC_DI_View_JSON constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * This function adds the UBC_DI_View_JSON actions and filters.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	/**
	 * This function adds the desired Digging In query variable to the query_vars
	 * array.
	 *
	 * @param array $query_vars
	 *
	 * @access public
	 * @return array
	 */
	function query_vars( $query_vars ) {
		$query_vars[] = 'ubc_di_download_json';
		return $query_vars;
	}

	/**
	 * This function sets the behavior to be performed if the Digging In query
	 * variable is set in a request.
	 *
	 * @param object $wp
	 *
	 * @access public
	 * @return void
	 */
	function parse_request( $wp ) {
		if ( array_key_exists( 'ubc_di_download_json', $wp->query_vars ) ) {
			$test = $this->ubc_di_retrieve_sites();
			wp_send_json( $test );
			exit;
		}
	}

	/**
	 * This function retrives ubc_di_site posts for display in the JSON output.
	 *
	 * @access public
	 * @return array $ubc_di_points The sites to be returned.
	 */
	function ubc_di_retrieve_sites() {
		$args         = array(
			'posts_per_page' => -1,
			'post_type'      => 'ubc_di_site',
		);
		$ubc_di_points = array();
		$all_ubc_di_points[] = get_posts( $args );
		foreach ( $all_ubc_di_points as $temp_point ) {
			$temp_inner_array                = array();
			$temp_inner_array['id']          = $temp_point->ID;
			$temp_inner_array['longitude']   = get_post_meta( $temp_point->ID, 'ubc_di_site_longitude', true );
			$temp_inner_array['latitude']    = get_post_meta( $temp_point->ID, 'ubc_di_site_latitude', true );
			$temp_inner_array['description'] = $temp_point->post_content;
			$temp_inner_array['name']        = $temp_point->post_title;
			$temp_inner_array['distance']    = 0;
			$temp_inner_array['bearing']     = 0;
			$temp_thumbnails                 = get_post_meta( $temp_point->ID, 'ubc_di_site_media', true );
			if ( ! empty( $temp_thumbnails ) ) {
					$temp_media = array();
				foreach ( $temp_thumbnails as $temp_thumbnail ) {
					$temp_media_meta = get_post_meta( $temp_thumbnail, 'ubc_di_media_meta', true );
					if ( '' !== $temp_media_meta ) {
						$temp_media_type = $temp_media_meta['type'];
						if ( 'image' === $temp_media_type ) {
							$temp_media_url['url'] = wp_get_attachment_url( $temp_media_meta['url'] );
							array_push( $temp_media, $temp_media_url );
						}
					}
				}
					$temp_inner_array['media'] = $temp_media;
			}
				array_push( $ubc_di_points, $temp_inner_array );
		}
		return $ubc_di_points;
	}

}
