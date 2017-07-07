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

require_once( plugin_dir_path( dirname( __FILE__ ) ).'list-table/class-di-wp-list-table-assessment.php' );

class DI_Admin_Assessment extends DI_Admin {

	function add_actions() {
		add_action( 'wp_ajax_get_assessment', array( $this, 'di_assessment_getter_callback' ) );
		add_action( 'wp_ajax_add_assessment', array( $this, 'di_assessment_adder_callback' ) );
		add_action( 'wp_ajax_edit_assessment', array( $this, 'di_assessment_editor_callback' ) );
	}

	function add_menu_page() {
		if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
			$this->delete_item( $_GET['assessment'] );
		}
		$this->add_new_item();
		$this->add_list_table();
	}

	function add_new_item () {
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		wp_register_script( 'di_control_panel_assessment_updater_script', plugins_url( 'js/di-assessment-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_assessment_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_assessment_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		$di_sites = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_site' ) );
		$di_assessments = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_assessment' ) );

		if( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['assessment'] ) ) {
			$di_edited_assessment_id = $_GET['assessment'];
			$di_edited_assessment = get_post( $di_edited_assessment_id );
		} else {
			$di_edited_assessment = '';
		}
		if( $di_edited_assessment != '' ) {
			$di_edited_assessment_sites = explode( ',', get_post_meta( $di_edited_assessment_id, 'di_assessment_sites', true )[0] );
			$di_edited_assessment_end_date = get_post_meta( $di_edited_assessment_id, 'di_assessment_end_date', true );
			$di_edited_assessment_data = get_post_meta( $di_edited_assessment_id, 'di_assessment_data', true );
			echo '<input type="hidden" id="di-assessment-id" value="' . $di_edited_assessment_id . '" />';
		}

		?>
			<div class="di-admin">
				<h1>Digging In Student Assessment</h1>
				<p></p>
				<hr />
				<h3 id="di-add-new-toggle">Add New Assessment<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
				<div class="di-as">
					<form method="POST" action="" style="width: 100%;" id="di-add-new-form">
						<?php
							wp_nonce_field( 'di_nonce_check','di-nonce-field' );
						?>
						<label style="display: block;">Assessment Title:</label>
						<input name="di-assessment-title" type="text" id="di-assessment-title" value="<?php echo ( $di_edited_assessment != '' ? $di_edited_assessment->post_title : '' ) ?>" class="regular-text ltr" />
						<label style="display: block;">Associated Sites:</label>
						<select multiple size="6" style="width: 49%" id="di-assessment-locations">
							<?php
								foreach( $di_sites as $di_site ) {
									echo '<option ';
									if( $di_edited_assessment != '' && in_array( $di_site->ID, $di_edited_assessment_sites ) )
										echo 'selected ';
									echo 'value="' . $di_site->ID . '">' . $di_site->post_title . '</option>';
								}
							?>
						</select>
					  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

						<p>Closing date: <input type="text" id="datepicker" value="<?php echo ( $di_edited_assessment != '' ? $di_edited_assessment_end_date : '' ) ?>"></p>
						<div style="width: 100%; height: 10px; clear: both;"></div>
						<label style="display: block;">Assessment Slides:</label>
						<div class="di-as-new">
							<!-- <div class="di-as-header">
								<div class="di-as-header-button">
									&#10006;
								</div>
							</div> -->
							<div class="di-as-body">
								<div class="di-as-text">
									<label>Slide Title (displays on top):</label>
									<input name="di-slide-title" type="text" id="di-slide-title" value="" class="regular-text ltr" />
									<!-- <label>Insert Digging In Media (displays below title):</label>
									<div id="di-as-media">
										<?php
											// $di_medias = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_media' ) );
											// foreach( $di_medias as $di_media ) {
											// 	print_r($di_media);
											// }
										?>
									</div> -->
									<label>Slide Text (displays below main media):</label>
										<?php wp_editor( '', 'di_slide_text_special' ) ?>
								</div>

								<div class="di-half-left-float">
									<h4>Add a Control Button</h4>
										<input id="di-button-text" type="text" style="width: 90%;" placeholder="Button text" />
										<p>Linked to:
										<select id="di-as-slide-link">
											<option value="">Choose a slide</option>
											<!-- <option value="next">Next (default)</option> -->
											<!-- <option value="prev">Prev (default)</option> -->
										</select>
										</p>
									<div class="button button-primary" name="di-as-link-add-button" id="di-as-link-add-button" style="margin: 10px;">Add Navigation Button</div>
								</div>
								<div class="di-half-left-float">
									<h4>Existing Buttons</h4>
									<ul id="di-as-button-add-list" class="di-as-button-list">
										<!-- <li value="1">Next (default)<div class="di-delete">X</div></li><li value="0">Previous (default)<div class="di-delete">X</div></li> -->
									</ul>
								</div>
								<div style="clear: both; height: 20px;"></div>

								<!-- <hr />
								<div class="di-half-left-float">
									<h4>Add pop-out assessment as sidebar:</h4>
									<p>Linked to:<select id="di-as-to-add">
										<option value="">Choose an assessment:</option>
										<?php
											foreach( $di_assessments as $di_assessment ) {
												echo '<option value="' . $di_assessment->ID . '">' . $di_assessment->post_title . '</option>';
											}
										?>
									</select>
									</p>
									<div style="clear: both; height: 10px;"></div>
									<div class="button button-primary" name="di-a-link-add" id="di-a-link-add" style="margin: 10px;">Add Assessment</div>
								</div>
								<div class="di-tour-sites">
									<h4>Existing Sidebar Assessment Links</h4>
									<ul id="di-a-link-add-list" class="di-as-button-list"></ul>
								</div>
								<div style="clear: both; height: 10px;"></div> -->

								<hr />
								<div class="di-half-left-float">
									<h4>Add multiple-choice question (not recorded)</h4>
									<input id="di-multiple-choice-question-text" type="text" style="width: 90%;" placeholder="Multiple-choice question" /><br />(leave blank for none)
									<div style="clear: both; height: 10px;"></div>
									<input id="di-multiple-choice-answer-text" type="text" style="width: 90%;" placeholder="Multiple-choice answer" />
									<div style="clear: both; height: 10px;"></div>
									<input id="di-multiple-choice-answer-correct" type="checkbox"> Correct answer
									<div style="clear: both; height: 10px;"></div>
									<div class="button button-primary" name="di-as-multiple-choice-add" id="di-as-multiple-choice-add" style="margin: 10px;">Add Multiple-Choice Answer</div>
								</div>
								<div class="di-tour-sites">
									<h4>Multiple choice answers</h4>
									<ul id="di-as-multiple-choice-add-list" class="di-as-button-list"></ul>
								</div>
								<div style="clear: both; height: 10px;"></div>

								<hr />
								<div class="di-half-left-float">
									<h4>Add multiple-choice question (recorded)</h4>
									<input id="di-recorded-multiple-choice-question-text" type="text" style="width: 90%;" placeholder="Multiple-choice question" /><br />(leave blank for none)
									<div style="clear: both; height: 10px;"></div>
									<input id="di-recorded-multiple-choice-answer-text" type="text" style="width: 90%;" placeholder="Multiple-choice answer" />
									<div style="clear: both; height: 10px;"></div>
									<div style="clear: both; height: 10px;"></div>
									<div class="button button-primary" name="di-as-recorded-multiple-choice-add" id="di-as-recorded-multiple-choice-add" style="margin: 10px;">Add Multiple-Choice Answer</div>
								</div>
								<div class="di-tour-sites">
									<h4>Multiple choice answers</h4>
									<ul id="di-as-recorded-multiple-choice-add-list" class="di-as-button-list"></ul>
								</div>
								<div style="clear: both; height: 10px;"></div>

								<hr />
								<h4>Add assessment response textbox:</h4>
								<input id="di-slide-text-input-question" type="text" style="width: 90%;" placeholder="Assessment response question" /><br />(leave blank for none)
								<hr />
								<h4>Add user response image uploader:</h4>
								<input id="di-slide-media-input-question" type="text" style="width: 90%;" placeholder="Image response question" /><br />(leave blank for none)
								<hr />
								<h4>Display Existing Answers: <input id="di-as-review" type="checkbox"></h4>
								<h4>Final Slide: <input id="di-as-final" type="checkbox"></h4>
								<div class="button button-primary" name="di-as-add" id="di-as-add">Add Slide</div>
								<div class="button button-primary" name="di-as-edit" id="di-as-edit">Edit Slide</div>
							</div>
						</div>
						<div class="di-as-existing">
							<ul id="di-as-existing-list"></ul>
						</div>
						<div style="width: 100%; height: 10px; clear: both;"></div>
						<div>
							<?php
								if( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
									echo '<input class="button button-primary" name="di-a-edit" id="di-a-edit" value="Edit Assessment" link="' . admin_url('admin.php?page=di-assessments') . '">';
								} else {
									echo '<input class="button button-primary" name="di-a-add" id="di-a-add" value="Add Assessment">';
								}
							?>
						</div>
					</form>
				</div>
				<hr />
			</div>
		<?php
	}

	function di_assessment_getter_callback() {
		global $wpdb;
		$di_assessment = get_post( $_POST['di_assessment_id'] );
		$di_assessment_data = get_post_meta( $di_assessment->ID, 'di_assessment_data', true );
		$json_response = array(
			'id' => $di_assessment->ID,
			'title' => $di_assessment->post_title,
			'content' => $di_assessment_slides,
			'data' => $di_assessment_data
		);
		wp_send_json( $json_response );
		die();
	}

	function di_assessment_adder_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_assessment_post = array(
					'post_title' => sanitize_text_field( $_POST['di_assessment_title'] ),
					'post_status' => 'publish',
					'post_type' => 'di_assessment'
			);
			$di_assessment_id = wp_insert_post( $di_assessment_post );
			add_post_meta( $di_assessment_id, 'di_assessment_sites', $_POST['di_assessment_locations'] );
			add_post_meta( $di_assessment_id, 'di_assessment_slides', $_POST['di_assessment_content'] );
			add_post_meta( $di_assessment_id, 'di_assessment_end_date', $_POST['di_assessment_end_date'] );
			add_post_meta( $di_assessment_id, 'di_assessment_data', $_POST['di_assessment_data'] );
		}
		echo sanitize_text_field( $_POST['di_assessment_title'] );
		die();
	}

	function di_assessment_editor_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_assessment_post = array(
					'ID' => sanitize_text_field( $_POST['di_assessment_id'] ),
					'post_title' => sanitize_text_field( $_POST['di_assessment_title'] ),
					'post_status' => 'publish',
					'post_type' => 'di_assessment'
			);
			$di_assessment_id = wp_update_post( $di_assessment_post );
			update_post_meta( $di_assessment_id, 'di_assessment_sites', $_POST['di_assessment_locations'] );
			update_post_meta( $di_assessment_id, 'di_assessment_slides', $_POST['di_assessment_content'] );
			update_post_meta( $di_assessment_id, 'di_assessment_end_date', $_POST['di_assessment_end_date'] );
			update_post_meta( $di_assessment_id, 'di_assessment_data', $_POST['di_assessment_data'] );
		}
		echo sanitize_text_field( $_POST['di_assessment_title'] );
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
	  $myListTable = new DI_WP_List_Table_Assessment();

		echo '<div class="digging-in-admin-list">';
		echo '<h3>Existing Assessments</h3>';
  	$myListTable->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$myListTable->display();
  	echo '</form>';
		echo '</div>';
	}

}

?>
