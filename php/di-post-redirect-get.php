<?php
	/**
	 * A redirection page
	 *
	 * This file redirects users to the designated return URL.
	 *
	 * @package DiggingIn
	 */

	if( isset( $_GET['return'] ) && sanitize_text_field( $_GET['return'] ) != '' ) {
		$return_url = $_GET['return'];
		if( isset( $_GET['view_point'] ) ) {
			$return_url .= '&ubcar_point_view='. intval( $_GET['view_point'] );
		}
		if( isset( $_GET['map_point'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?point='. intval( $_GET['map_point'] );
		}
		if( isset( $_GET['map_tour'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?tour='. intval( $_GET['map_tour'] );
		}
		if( isset( $_GET['map_layer'] ) ) {
			$return_url = explode( '?', $return_url );
			$return_url = $return_url[0];
			$return_url .= '?tour='. intval( $_GET['map_layer'] );
		}
		header( "Location: " . $return_url );
	} else {
		echo "Digging In has encountered an error. Please return to the previous page and try your action again.";
	}
	exit;
?>
