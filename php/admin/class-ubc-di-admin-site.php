<?php
/**
* The UBC_DI_Admin_Site subclass.
*
* This file defines the UBC_DI_Admin_Site class. The UBC_DI_Admin_Site class
* manages ubc_di_site posts.
*
* ubc_di_site posts contain Digging In soil sites. They record metadata about the
* sites, including their titles and locations. These sites are associated with
* media and assessments.
*
* ubc_di_site posts are associated with two pieces of metadata:
* - ubc_di_site_latitude: the site's latitude.
* - ubc_di_site_longitude: the site's longitude.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'list-table/class-ubc-di-wp-list-table-site.php' );

class UBC_DI_Admin_Site extends UBC_DI_Admin {

	/**
	 * This function adds the UBC_DI_Admin_Site actions,including its AJAX
	 * callback hooks and upload detection hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'wp_ajax_site_adder', array( $this, 'ubc_di_site_adder_callback' ) );
		add_action( 'wp_ajax_site_editer', array( $this, 'ubc_di_site_editer_callback' ) );
	}

	/**
	 * This function adds the UBC_DI_Admin_Site administration options. It also
	 * detects if a particular item is to be deleted or edited.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
		$this->add_new_item();
		if ( isset( $_GET['action'] ) && isset( $_GET['site'] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->edit_item( intval( $_GET['site'] ) );
		}
		if ( isset( $_GET['action'] ) && isset( $_GET['site'] ) && 'delete' == sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->delete_item( intval( $_GET['site'] ) );
		}
		$this->add_list_table();
	}

	/**
	 * This function adds the UBC_DI_Admin_Site add item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item() {
		wp_enqueue_script( 'ubc_di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'ubc_di_google_maps_api_key' ) );
		wp_register_script( 'ubc_di_control_panel_site_updater_script', plugins_url( 'js/ubc-di-site-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_site_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_site_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		?>
			<h1>Digging In Soil Sites</h1>
			<p></p>
			<hr />
			<h3 id="di-add-new-toggle">Add New Site<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-add-new-form">
					<?php
						wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
					?>
					<div class="admin-wrapper">
						<label>Soil Site Title</label>
						<input name="di-site-title" type="text" id="di-site-title" value="" class="regular-text ltr" />
					</div>
					<div class="admin-wrapper">
						<label>Soil Site Description Text</label>
						<textarea name="di-site-description" rows="5" type="textfield" id="di-site-description" value="" class="regular-text ltr" /></textarea>
					</div>
					<div class="admin-wrapper">
						Latitude: <input name="di-site-latitude" type="number" id="di-site-latitude" value="" class="regular-text ltr" /> Longitude: <input name="di-site-longitude" type="number" id="di-site-longitude" value="" class="regular-text ltr" /><a class="button" id="di-site-latlng-check">Check Location</a><div class="button button-primary" name="di-site-submit" id="di-site-submit">Upload Site</div>
					</div>
					<div id="di-map-canvas"></div><div id="di-streetview-canvas"></div>
				</form>
			</div>
			<hr />
		<?php
	}

	/**
	 * This function adds the UBC_DI_Admin_Site edit item pane and its options to
	 * the top of the page.
	 *
	 * @param int $item_id - the site to edit
	 *
	 * @access public
	 * @return void
	 */
	function edit_item( $item_id ) {
		$item = get_post( $item_id );
		$item_title = $item->post_title;
		$item_description = $item->post_content;
		$item_latitude = get_post_meta( $item->ID, 'ubc_di_site_latitude', true );
		$item_longitude = get_post_meta( $item->ID, 'ubc_di_site_longitude', true );
		?>
			<h3>Edit Site</h3>
			<form method="POST" action="" style="width: 100%;" id="di-edit-form">
				<a id="di-site-id_edit" value="<?php echo esc_attr( $item_id ); ?>"></a>
				<div class="admin-wrapper">
					<label>Soil Site Title</label>
					<input name="di-site-title_edit" type="text" id="di-site-title_edit" value="<?php echo esc_attr( $item_title ); ?>" class="regular-text ltr" />
				</div>
				<div class="admin-wrapper">
					<label>Soil Site Description Text</label>
					<textarea name="di-site-description_edit" rows="5" type="textfield" id="di-site-description_edit" value="" class="regular-text ltr" /><?php echo esc_html( $item_description ); ?></textarea>
				</div>
				<div class="admin-wrapper">
					Latitude: <input name="di-site-latitude_edit" type="number" id="di-site-latitude_edit" value="<?php echo esc_attr( $item_latitude ); ?>" class="regular-text ltr" /> Longitude: <input name="di-site-longitude_edit" type="number" id="di-site-longitude_edit" value="<?php echo esc_attr( $item_longitude ); ?>" class="regular-text ltr" />
					<div class="button button-primary" name="di-site-submit_edit" id="di-site-submit_edit">Update Site</div>
				</div>
			</form>
			<hr />
		<?php
	}

	/**
		* Function to add WP list table to the bottom of the page.
		*
		* @access public
		* @return void
		*/
	function add_list_table() {
		global $list_table_dl_site;
		$option = 'per_page';
		$args = array(
			'label' => 'Soil Sites',
			'default' => 10,
			'option' => 'sites_per_page',
		);

		add_screen_option( $option, $args );
		$list_table_dl_site = new ubc_di_WP_List_Table_Site();

		echo '<div class="wrap"><h3>Existing Sites</h3>';
		$list_table_dl_site->prepare_items();
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="ttest_list_table">';
		$list_table_dl_site->display();
		echo '</form></div>';
	}

	/**
		* This is the callback function for ubc-di-admin-site-updater.js's
		* site_updater AJAX request,
		* adding a group to the WordPress site.
		*
		* @access public
		* @return void
		*/
	function ubc_di_site_adder_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_site_title'] ) && isset( $_POST['ubc_di_site_description'] ) && isset( $_POST['ubc_di_site_latitude'] ) && isset( $_POST['ubc_di_site_longitude'] ) ) {
				$ubc_di_site_post = array(
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_site_title'] ) ),
					'post_content' => sanitize_text_field( wp_unslash( $_POST['ubc_di_site_description'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_site',
				);
				$ubc_di_site_id = wp_insert_post( $ubc_di_site_post );
				add_post_meta( $ubc_di_site_id, 'ubc_di_site_latitude', sanitize_text_field( wp_unslash( $_POST['ubc_di_site_latitude'] ) ) );
				add_post_meta( $ubc_di_site_id, 'ubc_di_site_longitude', sanitize_text_field( wp_unslash( $_POST['ubc_di_site_longitude'] ) ) );
				add_post_meta( $ubc_di_site_id, 'ubc_di_site_media', array() );
			}
		}
		die();
	}

	/**
		* This is the callback function for ubc-di-admin-edit-updater.js's
		* site_editer AJAX request,
		* editing an existing group in the WordPress site.
		*
		* @access public
		* @return void
		*/
	function ubc_di_site_editer_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_site_id'] ) && isset( $_POST['ubc_di_site_title'] ) && isset( $_POST['ubc_di_site_description'] ) && isset( $_POST['ubc_di_site_latitude'] ) && isset( $_POST['ubc_di_site_longitude'] ) ) {
				$ubc_di_site_post = array(
					'ID' => intval( $_POST['ubc_di_site_id'] ),
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_site_title'] ) ),
					'post_content' => sanitize_text_field( wp_unslash( $_POST['ubc_di_site_description'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_site',
				);
				$ubc_di_site_id = wp_update_post( $ubc_di_site_post );
				update_post_meta( $ubc_di_site_id, 'ubc_di_site_latitude', sanitize_text_field( wp_unslash( $_POST['ubc_di_site_latitude'] ) ) );
				update_post_meta( $ubc_di_site_id, 'ubc_di_site_longitude', sanitize_text_field( wp_unslash( $_POST['ubc_di_site_longitude'] ) ) );
				update_post_meta( $ubc_di_site_id, 'ubc_di_site_media', array() );
				echo esc_html( intval( $_POST['ubc_di_site_id'] ) );
				die();
			}
		}
	}
}

?>
