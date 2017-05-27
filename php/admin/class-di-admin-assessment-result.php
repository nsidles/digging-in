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

require_once( plugin_dir_path( dirname( __FILE__ ) ).'list-table/class-di-wp-list-table-assessment-result.php' );

class DI_Admin_Assessment_Result extends DI_Admin {

	function add_actions() {
		add_action( 'wp_ajax_add_assessment_result', array( $this, 'di_assessment_result_adder_callback' ) );
		add_action( 'wp_ajax_get_assessment_result', array( $this, 'di_get_assessment_result_callback' ) );
	}

	function add_menu_page() {
		if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
			$this->delete_item( $_GET['assessment_result'] );
		}
		$this->add_new_item();
		$this->add_list_table();
	}

	function add_new_item () {
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		wp_register_script( 'di_control_panel_assessment_result_updater_script', plugins_url( 'js/di-assessment-result-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_assessment_result_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_assessment_result_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		$di_assessments = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_assessment' ) );
		?>
			<h1>Digging In Student Assessment Results</h1>
			<p></p>
			<hr />
			<div id="di-assessment-result">
			</div>
			<hr />
		<?php
	}

	function di_assessment_result_adder_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_assessment_result_post = array(
					'post_title' => sanitize_text_field( $_POST['di_assessment_result_title'] ),
					'post_status' => 'publish',
					'post_type' => 'di_assessment_result'
			);
			$di_assessment_result_id = wp_insert_post( $di_assessment_result_post );
		}
		echo $_POST['di_assessment_result_content'];
		die();
	}

	function di_get_assessment_result_callback() {
		global $wpdb;
		$assessment_result = get_post( $_POST['di_assessment_result_id'] );
		$temp_array = array();
		$temp_array['title'] = $assessment_result->post_title;
		$temp_array['content'] = $assessment_result->post_content;
		wp_send_json( $temp_array );
		die();
	}

	function add_list_table() {
		global $myListTable;
	  $option = 'per_page';
	  $args = array(
			'label' => 'Books',
			'default' => 10,
			'option' => 'books_per_page'
		);

	  add_screen_option( $option, $args );
	  $myListTable = new DI_WP_List_Table_Assessment_Result();

		echo '<div class="digging-in-admin-list">';
		echo '<h3>Existing Assessment Results</h3>';
  	$myListTable->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$myListTable->display();
  	echo '</form>';
		echo '</div>';
	}

}

?>
