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
		add_action( 'wp_ajax_digging_in_add_assessment_result_evaluation', array( $this, 'di_assessment_result_adder_callback' ) );
		add_action( 'wp_ajax_get_assessment_result', array( $this, 'di_get_assessment_result_callback' ) );
		add_action( 'wp_ajax_get_assessments_export', array( $this, 'di_get_assessments_export_callback' ) );
	}

	function add_menu_page() {
		if( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'delete' ) {
			$this->delete_item( intval( $_GET['assessment_result'] ) );
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
		wp_nonce_field( 'di_nonce_check','di-nonce-field' );
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
					'ID' => intval( $_POST['di_assessment_result_id'] ),
					'post_content' => sanitize_text_field( $_POST['di_assessment_result_data'] )
			);
			$di_assessment_result_id = wp_update_post( $di_assessment_result_post );
			echo sanitize_text_field( $_POST['di_assessment_result_id'] );
		}
		die();
	}

	function di_get_assessment_result_callback() {
		global $wpdb;
		$assessment_result = get_post( intval( $_POST['di_assessment_result_id'] ) );
		$temp_array = array();
		$temp_array['title'] = $assessment_result->post_title;
		$temp_array['content'] = $assessment_result->post_content;
		wp_send_json( $temp_array );
		die();
	}

	function di_get_assessments_export_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$reponse = array();
			$di_sites = get_posts( array( 'post_type' => 'di_assessment_result', 'order' => 'DESC', 'posts_per_page' => -1 ) );
			foreach ( $di_sites as $di_site ) {
				$tempArray = $this->di_get_site_metadata( $di_site->ID );
				if( $tempArray != null ) {
					$slides = json_decode( $di_site->post_content );
					foreach( $slides as $slide ) {

						$tempArray['title'] = $slide->title;
						$tempArray['recordedMultipleChoiceQuestion'] = $slide->recordedMultipleChoice->question;
						$tempArray['recordedMultipleChoiceAnswer'] = $slide->recordedMultipleChoice->answer;
						if( @$slide->recordedMultipleChoice->correct == true ) {
							$tempArray['recordedMultipleChoiceCorrect'] = 'correct';
						} else {
							$tempArray['recordedMultipleChoiceCorrect'] = '';
						}
						@$tempArray['recordedMultipleChoiceNotes'] = $slide->recordedMultipleChoice->notes;

						@$tempArray['textQuestion'] = $slide->text->question;
						@$tempArray['textAnswer'] = $slide->text->answer;
						if( @$slide->text->correct == true ) {
							$tempArray['textCorrect'] = 'correct';
						} else {
							$tempArray['textCorrect'] = '';
						}
						@$tempArray['textNotes'] = $slide->text->notes;

						@$tempArray['imageQuestion'] = $slide->image->question;
						@$tempArray['imageAnswer'] = $slide->image->answer;
						if( @$slide->image->correct == true ) {
							$tempArray['imageCorrect'] = 'correct';
						} else {
							$tempArray['imageCorrect'] = '';
						}
						@$tempArray['imageNotes'] = $slide->image->notes;
						$response[] = $tempArray;
					}
				}
			}
			wp_send_json( $response );
		}
		die();
	}

	function di_get_site_metadata( $di_asr_id ) {
		$di_asr = get_post( $di_asr_id );
		$di_asr_assessment_id = get_post_meta( $di_asr->ID, 'di_assessment_result_assessment', true );
		$di_asr_site_id = get_post_meta( $di_asr->ID, 'di_assessment_result_site', true );
		$di_asr_author = get_user_by( 'id', $di_asr->post_author );
		$di_asr_group_id = get_post_meta( $di_asr->ID, 'di_assessment_result_group', true );
		$di_asr_group = get_post( $di_asr_group_id );
		$tempArray = array();
		$tempArray["id"] = $di_asr->ID;
		$tempArray["date"] = $di_asr->post_date;
		$tempArray["uploader"] = $di_asr_author->first_name . ' ' . $di_asr_author->last_name . ' (' . $di_asr_author->ID . ')';
		@$tempArray["uploader_group"] = $di_asr_group->post_title . ' (#' . $di_asr_group_id . ')';

		$di_group_meta_students = get_post_meta( $di_asr_group->ID, 'di_group_people', true );
		if( $di_group_meta_students != "" ) {
			foreach( $di_group_meta_students as $student ) {
				$tempNameArray[] = get_user_by( 'id', $student )->first_name . " " . get_user_by( 'id', $student )->last_name;
			}
			$tempArray["students"] = implode( ", ", $tempNameArray);
		} else {
			$tempArray["students"] = "";
		}

		$tempArray["site"] = get_post( $di_asr_site_id )->post_title . ' (#' . $di_asr_site_id . ')';
		$tempArray["assessment"] = get_post( $di_asr_assessment_id )->post_title . ' (#' . $di_asr_assessment_id . ')';
		if( isset( $_GET['di_group'] ) && intval( $_GET['di_group'] ) != $di_asr_group_id )
			return;
		if( isset( $_GET['di_assessment'] ) && intval( $_GET['di_assessment'] ) != $di_asr_assessment_id )
			return;
		if( isset( $_GET['di_site'] ) && intval( $_GET['di_site'] ) != $di_asr_site_id )
			return;
		return $tempArray;
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
		echo '<div class="digging-in-admin-list"><div style="display: none;" id="page-link">' . admin_url('admin.php?page=di-assessment-results') . '</div>';
		echo '<h3>Existing Assessment Results</h3>';
		if( current_user_can( 'manage_options' ) ) {
			echo '<div class="di-list-controllers">
							<div class="di-list-controller">
								Filter by Group: <input placeholder="Group ID..." type="text" id="diar-group" value="' . ( isset( $_GET['di_group'] ) ? intval( $_GET['di_group'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								Filter by Site ID: <input placeholder="Site ID..." type="text" id="diar-site" value="' . ( isset( $_GET['di_site'] ) ? intval( $_GET['di_site'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								Filter by Assessment ID: <input placeholder="Assessment ID..." type="text" id="diar-assessment" value="' . ( isset( $_GET['di_assessment'] ) ? intval( $_GET['di_assessment'] ) : '' ) . '"/>
							</div>
							<div class="di-list-controller">
								<div class="button" id="di-filter">Apply Filter</div>
								<a class="button" id="di-filter-clear" href="' . admin_url('admin.php?page=di-assessment-results') . '">Clear Filters</a>
							</div>
							<div class="di-list-controller">
								<div class="button" id="diar-export">Export</div>
							</div>
						</div>';
		}
  	$myListTable->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$myListTable->display();
  	echo '</form>';
		echo '</div>';
	}

}

?>
