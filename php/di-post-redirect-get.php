<?php
	/**
	 * A redirection page
	 *
	 * This file redirects users to the designated return URL.
	 *
	 * @package UBCAR
	 */

	if( isset( $_GET['return'] ) && $_GET['return'] != '' ) {
		$return_url = $_GET['return'];
		if( isset( $_GET['view_point'] ) ) {
			$return_url .= '&ubcar_point_view='. $_GET['view_point'];
		}
		if( isset( $_GET['map_point'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?point='. $_GET['map_point'];
		}
		if( isset( $_GET['map_tour'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?tour='. $_GET['map_tour'];
		}
		if( isset( $_GET['map_layer'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?tour='. $_GET['map_layer'];
		}
		header( "Location: " . $return_url );
	} else {
		echo "UBCAR has encountered an error. Please return to the previous page and try your action again.";
	}
	exit;
?>
