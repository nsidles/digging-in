<?php
/**
* The UBC_DI_Admin_Assessment_Result class.
*
* This file defines the UBC_DI_Admin_Assessment_Result class.  The UBC_DI_Admin_Assessment_Result subclass
* manages ubc_di_assessment_result posts.
*
* ubc_di_assessment_result posts contain student answers to Digging In assessments.
* They are keyed to particular locations and groups. Editors can evaluate these
* results and provide feedback to students.
*
* ubc_di_assessment_result posts have three extra pieces of metadata:
* - ubc_di_assessment_result_group: the assessment result's ubc_di_group.
* - ubc_di_assessment_result_site: the assessment result's ubc_di_site.
* - ubc_di_assessment_result_assessment: the assessment result itself.
*
* Assessment results are not created on the admin backend. Instead, they are
* created in the frontend UBC_DI_View class.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'list-table/class-ubc-di-wp-list-table-assessment-result.php' );

class UBC_DI_Admin_Assessment_Result extends UBC_DI_Admin {

	/**
	 * This function adds the UBC_DI_Admin_Assessment_Result actions,including its AJAX
	 * callback hooks and upload detection hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'wp_ajax_digging_in_add_assessment_result_evaluation', array( $this, 'ubc_di_assessment_result_adder_callback' ) );
		add_action( 'wp_ajax_get_assessment_result', array( $this, 'ubc_di_get_assessment_result_callback' ) );
		add_action( 'wp_ajax_get_assessments_export', array( $this, 'ubc_di_get_assessments_export_callback' ) );
	}

	/**
	 * This function adds the UBC_DI_Admin_Assessment_Result administration options. It also
	 * detects if a particular item is to be deleted.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		if ( isset( $_GET['assessment_result'] ) && isset( $_GET['action'] ) && 'delete' == sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->delete_item( intval( $_GET['assessment_result'] ) );
		}
		$this->add_new_item();
		$this->add_list_table();
	}

	/**
	 * This function adds the UBC_DI_Admin_Assessment_Result add/edit item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item() {
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		wp_register_script( 'ubc_di_control_panel_assessment_result_updater_script', plugins_url( 'js/ubc-di-assessment-result-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_assessment_result_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_assessment_result_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		$ubc_di_assessments = get_posts( array(
			'posts_per_page' => -1,
			'order' => 'ASC',
			'post_type' => 'ubc_di_assessment',
		) );
		wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
		?>
			<h1>Digging In Student Assessment Results</h1>
			<p></p>
			<hr />
			<div id="di-assessment-result">
			</div>
			<hr />
		<?php
	}

	/**
		* This is the callback function for ubc-di-admin-assessment-result-updater.js's
		* digging_in_add_assessment_result_evaluation AJAX request,
		* adding assessment result feedback from a T.A.
		*
		* @access public
		* @return void
		*/
	function ubc_di_assessment_result_adder_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_assessment_result_id'] ) && isset( $_POST['ubc_di_assessment_result_data'] ) ) {
				$ubc_di_assessment_result_post = array(
					'ID' => intval( $_POST['ubc_di_assessment_result_id'] ),
					'post_content' => sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_data'] ) ),
				);
				$ubc_di_assessment_result_id = wp_update_post( $ubc_di_assessment_result_post );
				echo esc_html( sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_id'] ) ) );
			}
		}
		die();
	}

	/**
		* This is the callback function for ubc-di-admin-assessment-result-updater.js's
		* get_assessment_result AJAX request,
		* getting a particular assessment result.
		*
		* @access public
		* @return void
		*/
	function ubc_di_get_assessment_result_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_assessment_result_id'] ) ) {
				$assessment_result = get_post( intval( $_POST['ubc_di_assessment_result_id'] ) );
				$temp_array = array();
				$temp_array['title'] = $assessment_result->post_title;
				$temp_array['content'] = $assessment_result->post_content;
				wp_send_json( $temp_array );
				die();
			}
		}
	}

	/**
		* This is the callback function for ubc-di-admin-assessment-result-updater.js's
		* get_assessments_export AJAX request,
		* getting a set of assessment results as a comma-separated table.
		*
		* @access public
		* @return void
		*/
	function ubc_di_get_assessments_export_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$reponse = array();
			$ubc_di_sites = get_posts( array(
				'post_type' => 'ubc_di_assess_result', // shortened from ubc_di_assessment_result because WordPress has a twenty character limit for post_type names.
				'order' => 'DESC',
				'posts_per_page' => -1,
			) );
			foreach ( $ubc_di_sites as $ubc_di_site ) {
				$temp_array = $this->ubc_di_get_site_metadata( $ubc_di_site->ID );
				if ( null != $temp_array ) {
					$slides = json_decode( $ubc_di_site->post_content );
					foreach ( $slides as $slide ) {

						$temp_array['title'] = $slide->title;
						$temp_array['recorded_multiple_choiceQuestion'] = $slide->recordedMultipleChoice->question;
						$temp_array['recorded_multiple_choiceAnswer'] = $slide->recordedMultipleChoice->answer;
						if ( true == $slide->recordedMultipleChoice->correct ) {
							$temp_array['recorded_multiple_choiceCorrect'] = 'correct';
						} else {
							$temp_array['recorded_multiple_choiceCorrect'] = '';
						}
						$temp_array['recorded_multiple_choiceNotes'] = $slide->recordedMultipleChoice->notes;

						$temp_array['textQuestion'] = $slide->text->question;
						$temp_array['textAnswer'] = $slide->text->answer;
						if ( true == $slide->text->correct ) {
							$temp_array['textCorrect'] = 'correct';
						} else {
							$temp_array['textCorrect'] = '';
						}
						$temp_array['textNotes'] = $slide->text->notes;

						$temp_array['imageQuestion'] = $slide->image->question;
						$temp_array['imageAnswer'] = $slide->image->answer;
						if ( true == $slide->image->correct ) {
							$temp_array['imageCorrect'] = 'correct';
						} else {
							$temp_array['imageCorrect'] = '';
						}
						$temp_array['imageNotes'] = $slide->image->notes;
						$response[] = $temp_array;
					}
				}
			}
			wp_send_json( $response );
		}
		die();
	}

	/**
		* Helper function for ubc_di_get_assessments_export_callback, getting
		* site metadata in an appropriate format.
		*
		* @param int $ubc_di_asr_id - assessment result ID
		* @access public
		* @return array - site metadata
		*/
	function ubc_di_get_site_metadata( $ubc_di_asr_id ) {
		$ubc_di_asr = get_post( $ubc_di_asr_id );
		$ubc_di_asr_assessment_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_assessment', true );
		$ubc_di_asr_site_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_site', true );
		$ubc_di_asr_author = get_user_by( 'id', $ubc_di_asr->post_author );
		$ubc_di_asr_group_id = get_post_meta( $ubc_di_asr->ID, 'ubc_di_assessment_result_group', true );
		$ubc_di_asr_group = get_post( $ubc_di_asr_group_id );
		$temp_array = array();
		$temp_array['id'] = $ubc_di_asr->ID;
		$temp_array['date'] = $ubc_di_asr->post_date;
		$temp_array['uploader'] = $ubc_di_asr_author->first_name . ' ' . $ubc_di_asr_author->last_name . ' (' . $ubc_di_asr_author->ID . ')';
		$temp_array['uploader_group'] = $ubc_di_asr_group->post_title . ' (#' . $ubc_di_asr_group_id . ')';

		$ubc_di_group_meta_students = get_post_meta( $ubc_di_asr_group->ID, 'ubc_di_group_people', true );
		if ( '' != $ubc_di_group_meta_students ) {
			foreach ( $ubc_di_group_meta_students as $student ) {
				$temp_name_array[] = get_user_by( 'id', $student )->first_name . ' ' . get_user_by( 'id', $student )->last_name;
			}
			$temp_array['students'] = implode( ', ', $temp_name_array );
		} else {
			$temp_array['students'] = '';
		}

		$temp_array['site'] = get_post( $ubc_di_asr_site_id )->post_title . ' (#' . $ubc_di_asr_site_id . ')';
		$temp_array['assessment'] = get_post( $ubc_di_asr_assessment_id )->post_title . ' (#' . $ubc_di_asr_assessment_id . ')';
		if ( isset( $_GET['ubc_di_group'] ) && intval( $_GET['ubc_di_group'] ) != $ubc_di_asr_group_id ) {
			return;
		}
		if ( isset( $_GET['ubc_di_assessment'] ) && intval( $_GET['ubc_di_assessment'] ) != $ubc_di_asr_assessment_id ) {
			return;
		}
		if ( isset( $_GET['ubc_di_site'] ) && intval( $_GET['ubc_di_site'] ) != $ubc_di_asr_site_id ) {
			return;
		}
		return $temp_array;
	}

	/**
		* Function to add WP list table to the bottom of the page.
		*
		* @access public
		* @return void
		*/
	function add_list_table() {
		global $my_list_table;
		$option = 'per_page';
		$args = array(
			'label' => 'Assessment Result',
			'default' => 10,
			'option' => 'results_per_page',
		);

		add_screen_option( $option, $args );
		$my_list_table = new UBC_DI_WP_List_Table_Assessment_Result();
		echo '<div class="digging-in-admin-list"><div style="display: none;" id="page-link">' . esc_html( admin_url( 'admin.php?page=di-assessment-results' ) ) . '</div>';
		echo '<h3>Existing Assessment Results</h3>';
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="di-list-controllers">
							<div class="di-list-controller">
								Filter by Group: <input placeholder="Group ID..." type="text" id="diar-group" value="' . ( isset( $_GET['ubc_di_group'] ) ? intval( $_GET['ubc_di_group'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								Filter by Site ID: <input placeholder="Site ID..." type="text" id="diar-site" value="' . ( isset( $_GET['ubc_di_site'] ) ? intval( $_GET['ubc_di_site'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								Filter by Assessment ID: <input placeholder="Assessment ID..." type="text" id="diar-assessment" value="' . ( isset( $_GET['ubc_di_assessment'] ) ? intval( $_GET['ubc_di_assessment'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								<div class="button" id="di-filter">Apply Filter</div>
								<a class="button" id="di-filter-clear" href="' . esc_attr( admin_url( 'admin.php?page=di-assessment-results' ) ) . '">Clear Filters</a>
							</div>
							<div class="di-list-controller">
								<div class="button" id="diar-export">Export</div>
							</div>
						</div>';
		}
		$my_list_table->prepare_items();
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="ttest_list_table">';
		$my_list_table->display();
		echo '</form>';
		echo '</div>';
	}

}

?>
