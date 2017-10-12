<?php
/**
* The DI_Admin_Site subclass.
*
* This file defines the DI_Admin superclass and requires its subclasses,
* allowing users to administer the Digging In backend.
*
* It also controls some of the Digging In options.
*
* It also defines three types of custom WordPress posts:
*
* - di_site: an individual soil site (or candidate soil site).
* - di_tour: a collection of soil sites.
* - di_medium: an instance of media associated with a particular soil site.
*
* Administration of each of these types is defined by its own subclass of
* DI_Admin. These types interact in the DI_View and DI_Data classes.
*
* DI_Admin depeneds on jQuery and Google Maps.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ).'list-table/class-di-wp-list-table-site.php' );

class DI_Admin_Site extends DI_Admin {

	function add_actions() {
		add_action( 'wp_ajax_site_adder', array( $this, 'di_site_adder_callback' ) );
		add_action( 'wp_ajax_site_editer', array( $this, 'di_site_editer_callback' ) );
	}

	function add_menu_page() {
		wp_nonce_field( 'di_nonce_check','di-nonce-field' );
		$this->add_new_item();
		if( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			$this->edit_item( $_GET['site'] );
		}
		if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
			$this->delete_item( $_GET['site'] );
		}
		$this->add_list_table();
	}

	function add_new_item() {
		wp_enqueue_script( 'di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'di_google_maps_api_key' ) );
		wp_register_script( 'di_control_panel_site_updater_script', plugins_url( 'js/di-site-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_site_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_site_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		?>
			<h1>Digging In Soil Sites</h1>
			<p></p>
			<hr />
			<h3 id="di-add-new-toggle">Add New Site<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-add-new-form">
					<?php
						wp_nonce_field( 'di_nonce_check','di-nonce-field' );
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

	function edit_item( $item_id ) {
		$item = get_post( $item_id );
		$item_title = $item->post_title;
		$item_description = $item->post_content;
		$item_latitude = get_post_meta( $item->ID, 'di_site_latitude', true );
		$item_longitude = get_post_meta( $item->ID, 'di_site_longitude', true );
		$item_assessments = get_post_meta( $item->ID, 'di_site_assessments', true );
		?>
			<h3>Edit Site</h3>
			<form method="POST" action="" style="width: 100%;" id="di-edit-form">
				<a id="di-site-id_edit" value="<?php echo $item_id ?>"></a>
				<div class="admin-wrapper">
					<label>Soil Site Title</label>
					<input name="di-site-title_edit" type="text" id="di-site-title_edit" value="<?php echo $item_title; ?>" class="regular-text ltr" />
				</div>
				<div class="admin-wrapper">
					<label>Soil Site Description Text</label>
					<textarea name="di-site-description_edit" rows="5" type="textfield" id="di-site-description_edit" value="" class="regular-text ltr" /><?php echo $item_description; ?></textarea>
				</div>
				<div class="admin-wrapper">
					Latitude: <input name="di-site-latitude_edit" type="number" id="di-site-latitude_edit" value="<?php echo $item_latitude; ?>" class="regular-text ltr" /> Longitude: <input name="di-site-longitude_edit" type="number" id="di-site-longitude_edit" value="<?php echo $item_longitude; ?>" class="regular-text ltr" />
					<div class="button button-primary" name="di-site-submit_edit" id="di-site-submit_edit">Update Site</div>
				</div>
			</form>
			<hr />
		<?php
	}

	function delete_item( $item ) {
		wp_delete_post( $item );
	}

	function add_list_table() {
		global $list_table_dl_site;
	  $option = 'per_page';
	  $args = array(
			'label' => 'Soil Sites',
			'default' => 10,
			'option' => 'sites_per_page'
		);

	  add_screen_option( $option, $args );
	  $list_table_dl_site = new DL_WP_List_Table_Site();

		echo '<div class="wrap"><h3>Existing Sites</h3>';
  	$list_table_dl_site->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$list_table_dl_site->display();
  	echo '</form></div>';
	}

	function di_site_adder_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_site_post = array(
					'post_title' => sanitize_text_field( $_POST['di_site_title'] ),
					'post_content' => sanitize_text_field( $_POST['di_site_description'] ),
					'post_status' => 'publish',
					'post_type' => 'di_site'
			);
			$di_site_id = wp_insert_post( $di_site_post );
			add_post_meta( $di_site_id, 'di_site_latitude', sanitize_text_field( $_POST['di_site_latitude'] ) );
			add_post_meta( $di_site_id, 'di_site_longitude', sanitize_text_field( $_POST['di_site_longitude'] ) );
			add_post_meta( $di_site_id, 'di_site_media', array() );
		}
		die();
	}

	function di_site_editer_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_site_post = array(
					'ID' => $_POST['di_site_id'],
					'post_title' => sanitize_text_field( $_POST['di_site_title'] ),
					'post_content' => sanitize_text_field( $_POST['di_site_description'] ),
					'post_status' => 'publish',
					'post_type' => 'di_site'
			);
			$di_site_id = wp_update_post( $di_site_post );
			update_post_meta( $di_site_id, 'di_site_latitude', sanitize_text_field( $_POST['di_site_latitude'] ) );
			update_post_meta( $di_site_id, 'di_site_longitude', sanitize_text_field( $_POST['di_site_longitude'] ) );
			update_post_meta( $di_site_id, 'di_site_media', array() );
		}
		echo $_POST['di_site_id'];
		die();
	}
}

?>
