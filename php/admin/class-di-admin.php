<?php
/**
* The DI_Admin superclass.
*
* This file defines the DI_Admin superclass and requires its subclasses,
* allowing users to administer the Digging In backend.
*
* It also controls some of the Digging In options.
*
* It also defines three types of custom WordPress posts:
*
* 'di_site' - a soil assessment site.
* 'di_group' - a group of students.
* 'di_media' - a bit of media attached to a soil site.
* 'di_assessment' - a lesson attached to one or more soil sites
* 'di_assessment_result' - student answers to an assessments
*
* Administration of each of these types is defined by its own subclass of
* DI_Admin. These types interact in the DI_View classes.
*
* DI_Admin depends on jQuery, jQuery UI, and Google Maps.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( __FILE__ ).'class-di-admin-site.php' );
require_once( plugin_dir_path( __FILE__ ).'class-di-admin-group.php' );
require_once( plugin_dir_path( __FILE__ ).'class-di-admin-media.php' );
require_once( plugin_dir_path( __FILE__ ).'class-di-admin-assessment.php' );
require_once( plugin_dir_path( __FILE__ ).'class-di-admin-assessment-result.php' );

class DI_Admin {

	var $di_admin_sites, $di_admin_groups, $di_admin_media, $di_admin_evaluations, $di_admin_assessments;

	/**
	 * DI_Admin constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * This function adds the DI_Admin actions,including its AJAX
	 * callback hooks.
	 *
	 * @access public
	 * @return void
	 */
	public function add_actions() {
			$this->di_admin_sites = new DI_Admin_Site();
			$this->di_admin_media = new DI_Admin_Media();
			$this->di_admin_groups  = new DI_Admin_Group();
			$this->di_admin_slides  = new DI_Admin_Assessment();
			$this->di_admin_results = new DI_Admin_Assessment_Result();
			add_action( 'admin_init', array( $this, 'menu_init' ) );
			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
			add_action( 'wp_ajax_options_updater', array( $this, 'di_options_updater_callback' ) );
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
		wp_register_script( 'di_control_panel_script', plugins_url( 'js/di-control-panel.js', dirname( dirname( __FILE__ ) ) ) );
		wp_register_style( 'di_control_panel_style', plugins_url( '/css/di-admin-style.css', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_style( 'di_control_panel_style' );
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
			register_post_type( 'di_site' );
			register_post_type( 'di_group' );
			register_post_type( 'di_media' );
			register_post_type( 'di_assessment' );
			register_post_type( 'di_assessment_result' );
	}

	/**
	 * This function adds the Digging In menu pages to the WordPress admin
	 * backend.
	 *
	 * @access public
	 * @return void
	 */
	public function add_menu_pages() {
			if ( !current_user_can( 'edit_pages' ) ) {
			} else {
				add_menu_page( 'Digging In', 'Digging In', 'edit_pages', 'di', array( $this, 'add_menu_page' ) );
				add_submenu_page( 'di', 'Soil Sites', 'Soil Sites', 'edit_pages', 'di-sites', array( $this->di_admin_sites, 'add_menu_page' ) );
				add_submenu_page( 'di', 'Soil Site Media', 'Soil Site Media', 'edit_pages', 'di-media', array( $this->di_admin_media, 'add_menu_page' ) );
				add_submenu_page( 'di', 'Student Groups', 'Student Groups', 'edit_pages', 'di-groups', array( $this->di_admin_groups, 'add_menu_page' ) );
				add_submenu_page( 'di', 'Assessments', 'Assessments', 'edit_pages', 'di-assessments', array( $this->di_admin_slides, 'add_menu_page' ) );
				add_submenu_page( 'di', 'Assessment Results', 'Assessment Results', 'edit_pages', 'di-assessment-results', array( $this->di_admin_results, 'add_menu_page' ) );
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
		wp_register_script( 'di_control_panel_updater_script', plugins_url( 'js/di-options-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'di_control_panel_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		?>
			<h2>Digging In Settings</h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="di_app_google_key">Google Maps API Key</label></th>
					<td>
						<?php
							echo '<input name="di-app-google-key" type="text" id="di-google-maps-api-key" value="' . get_option( 'di_google_maps_api_key' ) . '" class="regular-text ltr" />';
						?>
					</td>
				</tr>
			</table>
			<div class="button button-primary" id="di-options-submit">Submit</div>
		<?php
	}

	/**
		 * This is the callback function for di-options-updater.js's
		 * AJAX request, updating a Google Maps API key.
		 *
		 * @access public
		 * @return void
		 */
	public function di_options_updater_callback() {
		if( current_user_can( 'edit_pages' ) ) {
			update_option( 'di_google_maps_api_key', esc_attr( $_POST['di_google_maps_api_key'] ) );
			echo 'Digging In options updated!';
		} else {
			echo 'You do not have privileges to update these options.';
		}
		die();
	}

}

?>
