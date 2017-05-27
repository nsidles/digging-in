<?php
/**
* The DI_Admin_Media subclass
*
* This file defines the DI_Admin_Media subclass. The DI_Admin_Media subclass
* manages di_media-type posts. di_media-type posts have one
* extra piece of metadata:
*
* - di_media_meta: an array of metadata for this di_media post:
*   - type: the type of media uploaded.
*	 - image: the ID of an image uploaded to the WordPress gallery
*	 - imagewp: the ID of an image from the WordPress gallery ( treated as
*		 an image type )
*	 - audio: the ID of a public-facing audio file to embed
*	 - video: the ID of a public-facing video file to embed
*	 - eternal: a URL of an external webpage to be displayed as a link
*	 - wiki: a URL to a wiki page to be embedded, dependent on UBC CTLT's
*		 Wiki-Embed plugin
*   - audio_type: Optional. the type of audio media uploaded. Only
*	   SoundCloud is supported currently. Adding new types requires adding
*	   audio_type checking and display code in di-map-view.js.
*   - video_type: Optional. the type of video media uploaded. Only YouTube
*	   is supported currently. Adding new types requires adding audio_type
*	   checking and display code in di-map-view.js.
*   - url: the WordPress ID, external video/audio ID, or URL of the media
*   - location: the di_point post ID of the media's associated location
*   - hidden: determines if the media file is displayed on the front-end
*
* DI_Admin_Media does not use AJAX to upload media files because it was
* a pain to try and implement. It instead uses the PRG design pattern.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ).'list-table/class-di-wp-list-table-media.php' );

class DI_Admin_Media extends DI_Admin {

	/**
	 * This function adds the DI_Admin_Media actions,including its AJAX
	 * callback hooks and upload detection hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'admin_init', array( $this, 'di_media_data_handler' ) );
	}

	/**
	 * This function adds the DI_Admin_Media administration options. It also
	 * detects if a particular item is to be deleted.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		$this->add_new_item();
		$this->add_list_table();
		if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
			$this->delete_item( $_GET['media'] );
		}
	}

	/**
	 * This function adds the DI_Admin_Media add/edit item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item () {
		wp_enqueue_script( 'di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'di_google_maps_api_key' ) );
		wp_register_script( 'di_control_panel_media_updater_script', plugins_url( 'js/di-media-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_media_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_media_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		$di_sites = get_posts( array( 'post_type' => 'di_site', 'order' => 'DESC' ) );
		?>
			<h1>Digging In Student Media</h1>
			<p></p>
			<hr />
			<h3 id="di-add-new-toggle">Add New Media<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-add-new-form" enctype="multipart/form-data">
				<?php
					wp_nonce_field( 'di_nonce_check','di-nonce-field' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="di-media-type">Type</label></th>
						<td>
							<select id="di-media-type" name="di-media-type" class="">
								<option value="image">Image from Computer</option>
								<option value="imagewp">Image from Gallery</option>
								<option value="video">Video</option>
								<option value="audio">Audio</option>
								<option value="external">External Site Link</option>
								<?php
								if( current_user_can( 'edit_pages' ) ) {
									echo '<option value="wiki">Wiki Page</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="di-add-media-image">
						<th scope="row"><label for="di-media-upload">Image Upload</label></th>
						<td><input name="di-media-upload" type="file" id="di-media-upload" class="regular-text ltr" multiple="false" /></td>
					</tr>
					<tr class="di-add-media-imagewp">
						<th scope="row"><label for="di-media">WordPress Gallery #</label></th>
						<td>
							<select id="di-wp-image-url" name="di-wp-image-url">
								<?php
									$gallery_images = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'attachment', 'post_mime_type' => 'image/png, image/jpeg, image/gif' ) );
									foreach( $gallery_images as $gallery_image ) {
										echo '<option value="' . $gallery_image->ID. '">' . $this->di_media_data_cleaner( $gallery_image->post_title ) . ' ( #' . $gallery_image->ID . ' )</option>';
									}
								?>
							</select>
						</td>
					</tr>
					<tr class="di-add-media-external">
						<th scope="row"><label for="di-external">External Web Address</label></th>
						<td><input name="di-external-url" type="text" id="di-external-url" value="" class="regular-text ltr" /></td>
					</tr>
					<tr class="di-add-media-wiki">
						<th scope="row"><label for="di-wiki">Wiki Page URL</label></th>
						<td><input name="di-wiki-url" type="text" id="di-wiki-url" value="" class="regular-text ltr" /></td>
					</tr>
					<tr class="di-add-media-video">
						<th scope="row"><label for="di-video-type">Video Type</label></th>
						<td>
							<select id="di-video-type" name="di-video-type" class="">
								<option value="youtube">YouTube</option>
								<option value="vimeo">Vimeo</option>
							</select>
						</td>
					</tr>
					<tr class="di-add-media-video">
						<th scope="row"><label for="di-video-url">Video ID</label></th>
						<td>
							<input name="di-video-url" type="text" id="di-video-url" value="" class="regular-text ltr" />
							<div id="di-video-explainer">Insert only the video ID ( as highlighted ): <span style="color:grey;">https://www.youtube.com/watch?v=</span><span style="background:red;">ZQVehnkc68M</span></div>
						</td>
					</tr>
					<tr class="di-add-media-audio">
						<th scope="row"><label for="di-audio-type">Audio Type</label></th>
						<td>
							<select id="di-audio-type" name="di-audio-type" class="">
								<option value="soundcloud">SoundCloud</option>
							</select>
							<div id="di-audio-explainer">Insert only the audio ID ( as highlighted ): <span style="color:grey;">&lt;iframe width=&quot;100%&quot; height=&quot;450&quot; scrolling=&quot;no&quot; frameborder=&quot;no&quot; <br />src=&quot;https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/</span><span style="background:red">138550276</span><br /><span style="color:grey">&amp;amp;auto_play=false&amp;amp;hide_related=false&amp;amp;show_comments=true&amp;amp;show_user=true<br />&amp;amp;show_reposts=false&amp;amp;visual=true&quot;&gt;&lt;/iframe&gt;</span></div>
						</td>
					</tr>
					<tr class="di-add-media-audio">
						<th scope="row"><label for="di-audio-url">SoundCloud ID#</label></th>
						<td><input name="di-audio-url" type="text" id="di-audio-url" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="di-media-title">Media Title</label></th>
						<td><input name="di-media-title" type="text" id="di-media-title" value="" class="regular-text ltr" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="di-media-description">Media Description Text</label><br /><span id="di-media-wiki-warning">( n/a for Wiki Pages )</span></th>
						<td>
							<textarea name="di-media-description" rows="5" type="textfield" id="di-media-description" value="" class="regular-text ltr" /></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="di-media-site">Associated Site</label></th>
						<td>
							<select id="di-media-site" name="di-media-site" class="">
								<option value="0">---</option>
								<?php
								foreach ( $di_sites as $di_site ) {
									echo '<option value="' . $di_site->ID . '">' . $di_site->post_title . ' (#' . $di_site->ID . ')</option>';
								}
							?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td><div id="di-map-canvas"></div></td>
					</tr>
					<tr>
						<th scope="row">
							<input class="button button-primary" name="di-media-submit" id="di-media-submit" type="submit" value="Upload">
						</th>
					</tr>
				</table>
			</form>
			<hr />
		<?php
	}

	/**
	 * This function handles the DI_Admin_Media's data inputs when new media is
	 * uploaded.
	 *
	 * @access public
	 * @return void
	 */
	function di_media_data_handler() {
		global $wpdb;
		if( isset( $_POST['di-nonce-field'] ) && isset( $_POST['di-media-type'] ) ) {
			if ( !isset( $_POST['di-nonce-field'] ) || !wp_verify_nonce( $_POST['di-nonce-field'],'di_nonce_check' ) ) {
				die();
			} else {
				$di_url = "";
				$di_media_post_meta = array();
				$di_media_post = array(
					'post_title' => $this->di_media_data_cleaner( $_POST['di-media-title'] ),
					'post_content' => $this->di_media_data_cleaner( $_POST['di-media-description'] ),
					'post_status' => 'publish',
					'post_type' => 'di_media'
				);
				if( $_POST['di-media-type'] == 'image' ) {
					$di_url = media_handle_upload( 'di-media-upload', 0 );
					if( is_wp_error( $di_url ) ) {
						wp_redirect( menu_page_url( 'di-media', 0 ) . '&load=failure' );
						exit;
					}
				} else if( $_POST['di-media-type'] == 'audio' ) {
					$di_url = $this->di_media_data_cleaner( $_POST['di-audio-url'] );
					$di_media_post_meta['audio_type'] = $this->di_media_data_cleaner( $_POST['di-audio-type'] );
				} else if( $_POST['di-media-type'] == 'video' ) {
					$di_url = $this->di_media_data_cleaner(  substr( $_POST['di-video-url'], strrpos( $_POST['di-video-url'], "=" ) ) );
					$di_media_post_meta['video_type'] = $this->di_media_data_cleaner(  $_POST['di-video-type'] );
				} else if( $_POST['di-media-type'] == 'external' || $_POST['di-media-type'] == 'wiki' ) {
					if( $_POST['di-media-type'] == 'external' ) {
						$di_url_string = esc_url( $_POST['di-external-url'] );
					} else if( $_POST['di-media-type'] == 'wiki' ) {
						$di_url_string = esc_url( $_POST['di-wiki-url'] );
						$di_media_post['post_content'] = 'n/a';
					}
					$di_url_array = parse_url( $di_url_string );
					if( isset( $di_url_array['scheme'] ) ) {
						$di_url .= $di_url_string;
					} else {
						$di_url .= 'http://' . $di_url_string;
					}
				} else if( $_POST['di-media-type'] == 'imagewp' ) {
					$di_url = $this->di_media_data_cleaner( $_POST['di-wp-image-url'] );
				}
				$di_media_post_meta['type'] = $this->di_media_data_cleaner( $_POST['di-media-type'] );
				if( $_POST['di-media-type'] == 'imagewp' ) {
					$di_media_post_meta['type'] = 'image';
				}
				$di_media_post_meta['url'] =  $di_url;
				$di_media_post_meta['location'] = $this->di_media_data_cleaner( $_POST['di-media-site'] );
				if( isset( $_POST['di-media-visibility'] ) ) {
					$di_media_post_meta['hidden'] = 'on';
				} else {
					$di_media_post_meta['hidden'] = 'off';
				}
				$di_media_id = wp_insert_post( $di_media_post );
				add_post_meta( $di_media_id, 'di_media_meta', $di_media_post_meta );
				$di_location_media = get_post_meta( $_POST['di-media-site'], 'di_point_media', true );
				if( $di_location_media == null ) {
					$di_location_media = array();
				}
				array_push( $di_location_media, $di_media_id );
				update_post_meta( $this->di_media_data_cleaner( $_POST['di-media-site'] ), 'di_point_media', $di_location_media );

			}
			$return_url = plugins_url( 'di-post-redirect-get.php', dirname( __FILE__ ) ) . '?return=' . menu_page_url( 'di-media', 0 );
			wp_redirect( $return_url );
			exit;
		}
	}

	/**
	 * This function adds a sortable list of existing items to the bottom of the
	 * page.
	 *
	 * @access public
	 * @return void
	 */
	function add_list_table() {
		global $myListTable;
	  $option = 'per_page';
	  $args = array(
			'label' => 'Books',
			'default' => 10,
			'option' => 'books_per_page'
		);

	  add_screen_option( $option, $args );
	  $myListTable = new DI_WP_List_Table_Media();

		echo '<div class="wrap"><h3>Existing Media <div class="button button-primary" id="di-remove-student-media" style="float: right;">Delete all unchecked media</div></h3>';
  	$myListTable->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$myListTable->display();
  	echo '</form></div>';
	}

	/**
	 * This function cleans user-inputted strings.
	 *
	 * @param string $di_string_to_be_cleaned
	 *
	 * @access public
	 * @return void
	 */
	function di_media_data_cleaner( $di_string_to_be_cleaned ) {

		$bad_characters  = array( "&",	 "<",	">",	'"',	  "'",	 "/",	  "\n" );
		$good_characters = array( "&amp;", "&lt;", "&gt;", '&quot;', '&#39;', '&#x2F;', '<br />' );

		return str_replace( $bad_characters, $good_characters, $di_string_to_be_cleaned );
	}

}

?>
