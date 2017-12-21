<?php
/**
* The UBC_DI_Admin_Media subclass
*
* This file defines the UBC_DI_Admin_Media subclass. The UBC_DI_Admin_Media subclass
* manages ubc_di_media-type posts.
*
* ubc_di_media posts contain Digging In-associated media, including links and
* video and sound IDs.
*
* ubc_di_media-type posts have one extra piece of metadata:
*
* - ubc_di_media_meta: an array of metadata for this ubc_di_media post:
* - type: the type of media uploaded.
* - image: the ID of an image uploaded to the WordPress gallery
* - imagewp: the ID of an image from the WordPress gallery ( treated as
* an image type )
* - audio: the ID of a public-facing audio file to embed
* - video: the ID of a public-facing video file to embed
* - eternal: a URL of an external webpage to be displayed as a link
* - wiki: a URL to a wiki page to be embedded, dependent on UBC CTLT's
* Wiki-Embed plugin
* - audio_type: Optional. the type of audio media uploaded. Only
* SoundCloud is supported currently. Adding new types requires adding
* audio_type checking and display code in ubc-di-map-view.js.
* - video_type: Optional. the type of video media uploaded. Only YouTube
* is supported currently. Adding new types requires adding audio_type
* checking and display code in ubc-di-map-view.js.
* - url: the WordPress ID, external video/audio ID, or URL of the media
* - location: the ubc_di_point post ID of the media's associated location
* - hidden: determines if the media file is displayed on the front-end
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'list-table/class-ubc-di-wp-list-table-media.php' );

class UBC_DI_Admin_Media extends UBC_DI_Admin {

	/**
	 * This function adds the UBC_DI_Admin_Media actions,including its AJAX
	 * callback hooks and upload detection hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'admin_init', array( $this, 'ubc_di_media_data_handler' ) );
	}

	/**
	 * This function adds the UBC_DI_Admin_Media administration options. It also
	 * detects if a particular item is to be deleted.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		if ( isset( $_GET['action'] ) && isset( $_GET['media'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) == 'delete' ) {
			$this->delete_item( intval( $_GET['media'] ) );
		}
		$this->add_new_item();
		$this->add_list_table();
	}

	/**
	 * This function adds the UBC_DI_Admin_Media add/edit item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item() {
		wp_enqueue_script( 'ubc_di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'ubc_di_google_maps_api_key' ) );
		wp_register_script( 'ubc_di_control_panel_media_updater_script', plugins_url( 'js/ubc-di-media-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_media_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_media_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		$ubc_di_sites = get_posts( array(
			'post_type' => 'ubc_di_site',
			'order' => 'DESC',
		) );
		?>
			<h1>Digging In Student Media</h1>
			<p></p>
			<hr />
			<h3 id="di-add-new-toggle">Add New Media<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-add-new-form" enctype="multipart/form-data">
				<?php
					wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
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
								if ( current_user_can( 'edit_pages' ) ) {
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
								$gallery_images = get_posts( array(
									'posts_per_page' => -1,
									'order' => 'ASC',
									'post_type' => 'attachment',
									'post_mime_type' => 'image/png, image/jpeg, image/gif',
								) );
								foreach ( $gallery_images as $gallery_image ) {
									echo '<option value="' . esc_attr( $gallery_image->ID ) . '">' . esc_html( $gallery_image->post_title ) . ' ( #' . esc_html( $gallery_image->ID ) . ' )</option>';
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
								foreach ( $ubc_di_sites as $ubc_di_site ) {
									echo '<option value="' . esc_attr( $ubc_di_site->ID ) . '">' . esc_html( $ubc_di_site->post_title ) . ' (#' . esc_html( $ubc_di_site->ID ) . ')</option>';
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
	 * This function handles the UBC_DI_Admin_Media's data inputs when new media is
	 * uploaded.
	 *
	 * @access public
	 * @return void
	 */
	function ubc_di_media_data_handler() {
		global $wpdb;
		if ( isset( $_POST['di-nonce-field'] ) && isset( $_POST['di-media-type'] ) ) {
			if ( ! isset( $_POST['di-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['di-nonce-field'] ) ), 'ubc_di_nonce_check' ) ) {
				die();
			} else {
				if ( isset( $_POST['di-media-title'] ) && isset( $_POST['di-media-description'] ) && isset( $_POST['di-media-type'] ) && isset( $_POST['di-media-site'] ) ) {
					$ubc_di_media_title = sanitize_text_field( wp_unslash( $_POST['di-media-title'] ) );
					$ubc_di_media_description = sanitize_text_field( wp_unslash( $_POST['di-media-description'] ) );
					$ubc_di_media_type = sanitize_text_field( wp_unslash( $_POST['di-media-type'] ) );
				}
				$ubc_di_url = '';
				$ubc_di_media_post_meta = array();
				$ubc_di_media_post = array(
					'post_title' => $ubc_di_media_title,
					'post_content' => $ubc_di_media_description,
					'post_status' => 'publish',
					'post_type' => 'ubc_di_media',
				);
				if ( 'image' == $ubc_di_media_type ) {
					$ubc_di_url = media_handle_upload( 'di-media-upload', 0 );
					if ( is_wp_error( $ubc_di_url ) ) {
						wp_redirect( menu_page_url( 'di-media', 0 ) . '&load=failure' );
						exit;
					}
				} else if ( 'audio' == $ubc_di_media_type ) {
					if ( isset( $_POST['di-audio-url'] ) && isset( $_POST['di-audio-type'] ) ) {
						$ubc_di_url = sanitize_text_field( wp_unslash( $_POST['di-audio-url'] ) );
						$ubc_di_media_post_meta['audio_type'] = sanitize_text_field( wp_unslash( $_POST['di-audio-type'] ) );
					}
				} else if ( 'video' == $ubc_di_media_type ) {
					if ( isset( $_POST['di-video-url'] ) && isset( $_POST['di-video-type'] ) ) {
						$raw_ubc_di_url = sanitize_text_field( wp_unslash( $_POST['di-video-url'] ) );
						$ubc_di_url = substr( $raw_ubc_di_url, strrpos( $raw_ubc_di_url, '=' ) );
						$ubc_di_media_post_meta['video_type'] = sanitize_text_field( wp_unslash( $_POST['di-video-type'] ) );
					}
				} else if ( 'external' == $ubc_di_media_type || 'wiki' == $ubc_di_media_type ) {
					if ( 'external' == $ubc_di_media_type ) {
						if ( isset( $_POST['di-external-url'] ) ) {
							$ubc_di_url_string = sanitize_text_field( wp_unslash( $_POST['di-external-url'] ) );
						}
					} else if ( 'wiki' == $ubc_di_media_type ) {
						if ( isset( $_POST['di-wiki-url'] ) ) {
							$ubc_di_url_string = sanitize_text_field( wp_unslash( $_POST['di-wiki-url'] ) );
						}
						$ubc_di_media_post['post_content'] = 'n/a';
					}
					$ubc_di_url_array = parse_url( $ubc_di_url_string );
					if ( isset( $ubc_di_url_array['scheme'] ) ) {
						$ubc_di_url .= $ubc_di_url_string;
					} else {
						$ubc_di_url .= 'http://' . $ubc_di_url_string;
					}
				} else if ( 'imagewp' == $ubc_di_media_type ) {
					if ( isset( $_POST['di-wp-image-url'] ) ) {
						$ubc_di_url = sanitize_text_field( wp_unslash( $_POST['di-wp-image-url'] ) );
					}
				}
				$ubc_di_media_post_meta['type'] = $ubc_di_media_type;
				if ( 'imagewp' == $ubc_di_media_type ) {
					$ubc_di_media_post_meta['type'] = 'image';
				}
				$ubc_di_media_post_meta['url'] = $ubc_di_url;
				$ubc_di_media_site = sanitize_text_field( wp_unslash( $_POST['di-media-site'] ) );
				$ubc_di_media_post_meta['location'] = $ubc_di_media_site;
				if ( isset( $_POST['di-media-visibility'] ) ) {
					$ubc_di_media_post_meta['hidden'] = 'on';
				} else {
					$ubc_di_media_post_meta['hidden'] = 'off';
				}
				$ubc_di_media_id = wp_insert_post( $ubc_di_media_post );
				add_post_meta( $ubc_di_media_id, 'ubc_di_media_meta', $ubc_di_media_post_meta );
				$ubc_di_location_media = get_post_meta( $ubc_di_media_site, 'ubc_di_point_media', true );
				if ( null == $ubc_di_location_media ) {
					$ubc_di_location_media = array();
				}
				array_push( $ubc_di_location_media, $ubc_di_media_id );
				update_post_meta( $ubc_di_media_site, 'ubc_di_point_media', $ubc_di_location_media );

			}
			wp_redirect( menu_page_url( 'di-media', 0 ) );
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
		global $my_list_table;
		$option = 'per_page';
		$args = array(
			'label' => 'Books',
			'default' => 10,
			'option' => 'media_per_page',
		);

		add_screen_option( $option, $args );
		$my_list_table = new UBC_DI_WP_List_Table_Media();

		$my_list_table->prepare_items();
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="ttest_list_table">';
		$my_list_table->display();
		echo '</form></div>';
	}

}

?>
