<?php
/**
* The UBC_DI_Admin superclass.
*
* This file defines the UBC_DI_Admin superclass and requires its subclasses,
* allowing users to administer the Digging In backend.
*
* It also controls some of the Digging In options.
*
* It also defines three types of custom WordPress posts:
*
* 'ubc_di_site' - a soil assessment site.
* 'ubc_di_group' - a group of students.
* 'ubc_di_media' - a bit of media attached to a soil site.
* 'ubc_di_assessment' - a lesson attached to one or more soil sites
* 'ubc_di_assessment_result' - student answers to an assessments
*
* Administration of each of these types is defined by its own subclass of
* UBC_DI_Admin. These types interact in the UBC_DI_View classes.
*
* UBC_DI_Admin depends on jQuery, jQuery UI, and Google Maps.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-admin-site.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-admin-group.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-admin-media.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-admin-assessment.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-admin-assessment-result.php' );

class UBC_DI_Admin {

	var $ubc_di_admin_sites, $ubc_di_admin_groups, $ubc_di_admin_media, $ubc_di_admin_evaluations, $ubc_di_admin_assessments;

	/**
	 * UBC_DI_Admin constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * This function adds the UBC_DI_Admin actions,including its AJAX
	 * callback hooks.
	 *
	 * @access public
	 * @return void
	 */
	public function add_actions() {
			$this->ubc_di_admin_sites = new UBC_DI_Admin_Site();
			$this->ubc_di_admin_media = new UBC_DI_Admin_Media();
			$this->ubc_di_admin_groups  = new UBC_DI_Admin_Group();
			$this->ubc_di_admin_slides  = new UBC_DI_Admin_Assessment();
			$this->ubc_di_admin_results = new UBC_DI_Admin_Assessment_Result();
			add_action( 'admin_init', array( $this, 'menu_init' ) );
			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
			add_action( 'wp_ajax_options_updater', array( $this, 'ubc_di_options_updater_callback' ) );
			add_action( 'wp_ajax_ubc_di_delete_all_media', array( $this, 'ubc_di_delete_all_media_callback' ) );
			add_action( 'wp_ajax_ubc_di_delete_all_groups', array( $this, 'ubc_di_delete_all_groups_callback' ) );
			add_action( 'wp_ajax_ubc_di_delete_all_results', array( $this, 'ubc_di_delete_all_results_callback' ) );
	}

	/**
	 *
	 * This function registers a UI script and registers and enqueues the
	 * Dashboard style.
	 *
	 * @access public
	 * @return void
	 */
	public function menu_init() {
		wp_register_script( 'ubc_di_control_panel_script', plugins_url( 'js/ubc-di-control-panel.js', dirname( dirname( __FILE__ ) ) ) );
		wp_register_style( 'ubc_di_control_panel_style', plugins_url( '/css/ubc-di-admin-style.css', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_style( 'ubc_di_control_panel_style' );
	}

	/**
	 *
	 * This function deletes an item from the WordPress database, regardless of
	 * its type.
	 *
	 * @param object $item
	 *
	 * @access public
	 * @return void
	 */
	function delete_item( $item ) {
		wp_delete_post( $item );
	}

	/**
	 * This function registers the new Digging In post types.
	 *
	 * @access public
	 * @return void
	 */
	public function register_post_types() {
			register_post_type( 'ubc_di_site' );
			register_post_type( 'ubc_di_group' );
			register_post_type( 'ubc_di_media' );
			register_post_type( 'ubc_di_assessment' );
			register_post_type( 'ubc_di_assess_result' );
	}

	/**
	 * This function adds the Digging In menu pages to the WordPress admin
	 * backend.
	 *
	 * @access public
	 * @return void
	 */
	public function add_menu_pages() {
		if ( ! current_user_can( 'edit_pages' ) ) {
		} else {
			add_menu_page( 'Digging In', 'Digging In', 'manage_options', 'di', array( $this, 'add_menu_page' ) );
			add_submenu_page( 'di', 'Soil Sites', 'Soil Sites', 'manage_options', 'di-sites', array( $this->ubc_di_admin_sites, 'add_menu_page' ) );
			add_submenu_page( 'di', 'Soil Site Media', 'Soil Site Media', 'manage_options', 'di-media', array( $this->ubc_di_admin_media, 'add_menu_page' ) );
			add_submenu_page( 'di', 'Students and Groups', 'Student and Groups', 'manage_options', 'di-groups', array( $this->ubc_di_admin_groups, 'add_menu_page' ) );
			add_submenu_page( 'di', 'Assessments', 'Assessments', 'manage_options', 'di-assessments', array( $this->ubc_di_admin_slides, 'add_menu_page' ) );
			add_submenu_page( 'di', 'Assessment Results', 'Assessment Results', 'edit_pages', 'di-assessment-results', array( $this->ubc_di_admin_results, 'add_menu_page' ) );
		}
	}

	/**
	 * This function adds the Digging In main menu page to the WordPress admin
	 * backend.
	 *
	 * @access public
	 * @return void
	 */
	public function add_menu_page() {
		wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
		wp_register_script( 'ubc_di_control_panel_updater_script', plugins_url( 'js/ubc-di-options-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'ubc_di_control_panel_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		?>
			<h2>Digging In Settings</h2>
			<h3>Google Maps</h3>
			<h4>Google Maps API Key</h3>
			<p>This setting sets the API key that allows you to use Google maps to display soil sites.</p>
			<?php echo '<input name="di-app-google-key" type="text" id="di-google-maps-api-key" value="' . esc_attr( get_option( 'ubc_di_google_maps_api_key' ) ) . '" class="regular-text ltr" />'; ?>
			<h4>Google Maps Bounding Area</h4>
			<p>Upper latitude: <?php echo '<input name="di-app-upper-lat" type="text" id="di-google-maps-upper-lat" value="' . esc_attr( get_option( 'ubc_di_google_maps_upper_lat' ) ) . '" />'; ?></p>
			<p>Left longitude: <?php echo '<input name="di-app-left-lon" type="text" id="di-google-maps-left-lon" value="' . esc_attr( get_option( 'ubc_di_google_maps_left_lon' ) ) . '" />'; ?></p>
			<p>Lower latitude: <?php echo '<input name="di-app-lower-lat" type="text" id="di-google-maps-lower-lat" value="' . esc_attr( get_option( 'ubc_di_google_maps_lower_lat' ) ) . '" />'; ?></p>
			<p>Right longitude: <?php echo '<input name="di-app-right-lon" type="text" id="di-google-maps-right-lon" value="' . esc_attr( get_option( 'ubc_di_google_maps_right_lon' ) ) . '" />'; ?></p>
			<h4>Google Maps Layers</h4>
			<p>Enable layers and buttons:</p>
			<p>Layer 1 file: <?php echo '<input name="di-app-layer1-file" type="text" id="di-google-maps-layer1-file" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer1_file' ) ) . '" />'; ?> Layer 1 label: <?php echo '<input name="di-app-layer1-label" type="text" id="di-google-maps-layer1-label" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer1_label' ) ) . '" />'; ?></p>
			<p>Layer 2 file: <?php echo '<input name="di-app-layer2-file" type="text" id="di-google-maps-layer2-file" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer2_file' ) ) . '" />'; ?> Layer 2 label: <?php echo '<input name="di-app-layer2-label" type="text" id="di-google-maps-layer2-label" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer2_label' ) ) . '" />'; ?></p>
			<p>Layer 3 file: <?php echo '<input name="di-app-layer3-file" type="text" id="di-google-maps-layer3-file" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer3_file' ) ) . '" />'; ?> Layer 3 label: <?php echo '<input name="di-app-layer3-label" type="text" id="di-google-maps-layer3-label" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer3_label' ) ) . '" />'; ?></p>
			<p>Layer 4 file: <?php echo '<input name="di-app-layer4-file" type="text" id="di-google-maps-layer4-file" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer4_file' ) ) . '" />'; ?> Layer 4 label: <?php echo '<input name="di-app-layer4-label" type="text" id="di-google-maps-layer4-label" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer4_label' ) ) . '" />'; ?></p>
			<p>Layer 5 file: <?php echo '<input name="di-app-layer5-file" type="text" id="di-google-maps-layer5-file" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer5_file' ) ) . '" />'; ?> Layer 5 label: <?php echo '<input name="di-app-layer5-label" type="text" id="di-google-maps-layer5-label" value="' . esc_attr( get_option( 'ubc_di_google_maps_layer5_label' ) ) . '" />'; ?></p>
			<div class="button button-primary" id="di-options-submit">Submit</div>
			<hr />
			<h3>Delete All Student Media</h3>
			<p>This button deletes all student-submitted media. Use it to remove all student-submitted media from this installation.</p>
			<div class="button button-primary" id="di-delete-all-media">Delete All Media</div>
			<hr />
			<h3>Delete All Student Groups</h3>
			<p>This button deletes all student groups. Use it to remove all student group data from this installation.</p>
			<div class="button button-primary" id="di-delete-all-groups">Delete All Groups</div>
			<hr />
			<h3>Delete All Student Assessment Results</h3>
			<p>This button deletes all student assessment results. Use it to remove all student assessment results from this installation</p>
			<div class="button button-primary" id="di-delete-all-assessment-results">Delete All Assessment Results</div>
		<?php
	}

	/**
		 * This is the callback function for ubc-di-options-updater.js's
		 * AJAX request, updating a Google Maps API key and other parameters.
		 *
		 * @access public
		 * @return void
		 */
	public function ubc_di_options_updater_callback() {
		if ( current_user_can( 'edit_pages' ) ) {
			if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
				echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
				die();
			} else {
				if ( isset( $_POST['ubc_di_google_maps_api_key'] )
					&& isset( $_POST['ubc_di_google_maps_upper_lat'] )
					&& isset( $_POST['ubc_di_google_maps_left_lon'] )
					&& isset( $_POST['ubc_di_google_maps_lower_lat'] )
					&& isset( $_POST['ubc_di_google_maps_right_lon'] )
					&& isset( $_POST['ubc_di_google_maps_layer1_label'] )
					&& isset( $_POST['ubc_di_google_maps_layer1_file'] )
					&& isset( $_POST['ubc_di_google_maps_layer2_label'] )
					&& isset( $_POST['ubc_di_google_maps_layer2_file'] )
					&& isset( $_POST['ubc_di_google_maps_layer3_label'] )
					&& isset( $_POST['ubc_di_google_maps_layer3_file'] )
					&& isset( $_POST['ubc_di_google_maps_layer4_label'] )
					&& isset( $_POST['ubc_di_google_maps_layer4_file'] )
					&& isset( $_POST['ubc_di_google_maps_layer5_label'] )
					&& isset( $_POST['ubc_di_google_maps_layer5_file'] )
				) {
					update_option( 'ubc_di_google_maps_api_key', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_api_key'] ) ) ) );
					update_option( 'ubc_di_google_maps_upper_lat', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_upper_lat'] ) ) ) );
					update_option( 'ubc_di_google_maps_left_lon', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_left_lon'] ) ) ) );
					update_option( 'ubc_di_google_maps_lower_lat', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_lower_lat'] ) ) ) );
					update_option( 'ubc_di_google_maps_right_lon', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_right_lon'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer1_label', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer1_label'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer1_file', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer1_file'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer2_label', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer2_label'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer2_file', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer2_file'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer3_label', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer3_label'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer3_file', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer3_file'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer4_label', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer4_label'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer4_file', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer4_file'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer5_label', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer5_label'] ) ) ) );
					update_option( 'ubc_di_google_maps_layer5_file', esc_attr( sanitize_text_field( wp_unslash( $_POST['ubc_di_google_maps_layer5_file'] ) ) ) );
				}
				echo 'Digging In Google Maps options updated!';
			}
		} else {
			echo 'You do not have privileges to update these options.';
		}
		die();
	}

	/**
		 * This is the callback function for ubc-di-options-updater.js's
		 * AJAX request, deleting all student-submitted media.
		 *
		 * @access public
		 * @return void
		 */
	public function ubc_di_delete_all_media_callback() {
		if ( current_user_can( 'edit_pages' ) ) {
			$ubc_di_media = get_posts( array(
				'post_type' => 'ubc_di_media',
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
			foreach ( $ubc_di_media as $ubc_di_medium ) {
				wp_delete_post( $ubc_di_medium->ID );
			}
			echo 'All student-submitted media deleted!';
		} else {
			echo 'You do not have privileges to update these options.';
		}
		die();
	}

	/**
		 * This is the callback function for ubc-di-options-updater.js's
		 * AJAX request, deleting all student-submitted media.
		 *
		 * @access public
		 * @return void
		 */
	public function ubc_di_delete_all_groups_callback() {
		if ( current_user_can( 'edit_pages' ) ) {
			$ubc_di_groups = get_posts( array(
				'post_type' => 'ubc_di_group',
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
			foreach ( $ubc_di_groups as $ubc_di_group ) {
				wp_delete_post( $ubc_di_group->ID );
			}
			echo 'All student groups deleted!';
		} else {
			echo 'You do not have privileges to update these options.';
		}
		die();
	}

	/**
		 * This is the callback function for ubc-di-options-updater.js's
		 * AJAX request, deleting all student assessment results.
		 *
		 * @access public
		 * @return void
		 */
	public function ubc_di_delete_all_results_callback() {
		if ( current_user_can( 'edit_pages' ) ) {
			$ubc_di_results = get_posts( array(
				'post_type' => 'ubc_di_assess_result',
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
			foreach ( $ubc_di_results as $ubc_di_result ) {
				wp_delete_post( $ubc_di_result->ID );
			}
			echo 'All student-submitted assessment results deleted!';
		} else {
			echo 'You do not have privileges to update these options.';
		}
		die();
	}

	/**
		 * This function sanitizes the elements of an array passed in as a post
		 * variable.
		 * @param string $array_text
		 *
		 * @access public
		 * @return void
		 */
	public function sanitize_text_field( $array_text ) {
		foreach ( $array_text as &$element ) {
			if ( is_array( $element ) ) {
				foreach ( $inner_array as &$inner_element ) {
					$inner_element = sanitize_text_field( wp_unslash( $inner_element ) );
				}
			} else {
				$element = sanitize_text_field( wp_unslash( $element ) );
			}
		}
		return $array_text;
	}

}
