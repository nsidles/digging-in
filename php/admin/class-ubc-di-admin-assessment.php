<?php
/**
* The UBC_DI_Admin_Assessment class.
*
* This file defines the UBC_DI_Admin_Assessment class. This subclass manages
* ubc_di_assessment posts.
*
* ubc_di_assessment posts contain assessments, which are linked collections of
* slides that allow students to learn lessons about soil sites and soil.
* Each assessment has a title, associated sites, an end date, and slides.
* Each slide has a title, content, linked buttons, and questions.
*
* ubc_di_assessment posts have four extra pieces of metadata:
* - ubc_di_assessment_sites: the assessment's associated sites.
* - ubc_di_assessment_slides: the assessment's associated slides.
* - ubc_di_assessment_end_data: the assessment's associated end date.
* - ubc_di_assessment_data: the assessment's associated slides (in string form)
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'list-table/class-ubc-di-wp-list-table-assessment.php' );

class UBC_DI_Admin_Assessment extends UBC_DI_Admin {

	/**
	 * This function adds the UBC_DI_Admin_Assessment actions,including its AJAX
	 * callback hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'wp_ajax_get_assessment', array( $this, 'ubc_di_assessment_getter_callback' ) );
		add_action( 'wp_ajax_add_assessment', array( $this, 'ubc_di_assessment_adder_callback' ) );
		add_action( 'wp_ajax_edit_assessment', array( $this, 'ubc_di_assessment_editor_callback' ) );
	}

	/**
	 * This function adds the UBC_DI_Admin_Assessment administration options. It also
	 * detects if a particular item is to be deleted.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		if ( isset( $_GET['assessment'] ) && isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) == 'delete' ) {
			$this->delete_item( intval( $_GET['assessment'] ) );
		}
		if ( isset( $_GET['assessment'] ) && isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) == 'copy' ) {
			$this->copy_item( intval( $_GET['assessment'] ) );
		}
		$this->add_new_item();
		$this->add_list_table();
	}

	/**
   *
   * This function copies an assessment.
   *
   * @param object $item
   *
   * @access public
   * @return void
   */
	function copy_item( $item ) {
		$ubc_di_assessment = get_post( intval( $item ) );
		$ubc_di_assessment_data = get_post_meta( intval( $item ), 'ubc_di_assessment_data', true );
		$ubc_di_assessment_sites = get_post_meta( intval( $item ), 'ubc_di_assessment_sites', true );
		$ubc_di_assessment_end_date = get_post_meta( intval( $item ), 'ubc_di_assessment_end_date', true );
		$ubc_di_assessment_post = array(
			'post_title' => $ubc_di_assessment->post_title,
			'post_status' => 'publish',
			'post_type' => 'ubc_di_assessment',
		);
		$ubc_di_assessment_id = wp_insert_post( $ubc_di_assessment_post );
		if ( isset( $ubc_di_assessment_sites ) ) {
			add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_sites', $ubc_di_assessment_sites );
		}
		if ( isset( $ubc_di_assessment_end_date ) ) {
			add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_end_date', $ubc_di_assessment_end_date );
		}
		if ( isset( $ubc_di_assessment_data ) ) {
			add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_data', $ubc_di_assessment_data );
		}
	}

	/**
	 * This function adds the UBC_DI_Admin_Assessment add/edit item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item() {
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );
		wp_register_script( 'ubc_di_control_panel_assessment_updater_script', plugins_url( 'js/ubc-di-assessment-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_assessment_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_assessment_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		$ubc_di_sites = get_posts( array(
			'posts_per_page' => -1,
			'order' => 'ASC',
			'post_type' => 'ubc_di_site',
		) );
		$ubc_di_assessments = get_posts( array(
			'posts_per_page' => -1,
			'order' => 'ASC',
			'post_type' => 'ubc_di_assessment',
		) );

		if ( isset( $_GET['assessment'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			$ubc_di_edited_assessment_id = sanitize_text_field( wp_unslash( $_GET['assessment'] ) );
			$ubc_di_edited_assessment = get_post( $ubc_di_edited_assessment_id );
		} else {
			$ubc_di_edited_assessment = '';
		}
		if ( '' != $ubc_di_edited_assessment ) {
			$post_meta = get_post_meta( $ubc_di_edited_assessment_id, 'ubc_di_assessment_sites', true );
			$ubc_di_edited_assessment_sites = explode( ',', $post_meta[0] );
			$ubc_di_edited_assessment_end_date = get_post_meta( $ubc_di_edited_assessment_id, 'ubc_di_assessment_end_date', true );
			$ubc_di_edited_assessment_data = get_post_meta( $ubc_di_edited_assessment_id, 'ubc_di_assessment_data', true );
			echo '<input type="hidden" id="di-assessment-id" value="' . esc_attr( $ubc_di_edited_assessment_id ) . '" />';
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
							wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
						?>
						<label style="display: block;">Assessment Title:</label>
						<input name="di-assessment-title" type="text" id="di-assessment-title" value="<?php echo ( '' != esc_attr( $ubc_di_edited_assessment->post_title ) ? esc_attr( $ubc_di_edited_assessment->post_title ) : '' ); ?>" class="regular-text ltr" />
						<label style="display: block;">Associated Sites:</label>
						<select multiple size="6" style="width: 49%" id="di-assessment-locations">
							<?php
							foreach ( $ubc_di_sites as $ubc_di_site ) {
								echo '<option ';
								if ( '' != $ubc_di_edited_assessment && in_array( $ubc_di_site->ID, $ubc_di_edited_assessment_sites ) ) {
									echo 'selected ';
								}
								echo 'value="' . esc_attr( $ubc_di_site->ID ) . '">' . esc_html( $ubc_di_site->post_title ) . '</option>';
							}
							?>
						</select>
					  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

						<p>Closing date: <input type="text" id="datepicker" value="<?php echo ( '' != esc_attr( $ubc_di_edited_assessment_end_date ) ? esc_attr( $ubc_di_edited_assessment_end_date ) : '' ); ?>"></p>
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
											// $ubc_di_medias = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'ubc_di_media' ) );
											// foreach( $ubc_di_medias as $ubc_di_media ) {
											// 	print_r($ubc_di_media);
											// }
										?>
									</div> -->
									<label>Slide Text (displays below main media):</label>
										<?php wp_editor( '', 'ubc_di_slide_text_special' ); ?>
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
							if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
								echo '<input class="button button-primary" name="di-a-edit" id="di-a-edit" value="Edit Assessment" link="' . esc_attr( admin_url( 'admin.php?page=di-assessments' ) ) . '">';
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

	/**
		* This is the callback function for ubc-di-admin-assessment-result.js's
		* get_assessment AJAX request, getting a single assessment.
		*
		* @access public
		* @return void
		*/
	function ubc_di_assessment_getter_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {
			if ( isset( $_POST['ubc_di_assessment_id'] ) ) {
				$ubc_di_assessment = get_post( intval( $_POST['ubc_di_assessment_id'] ) );
				$ubc_di_assessment_data = get_post_meta( $ubc_di_assessment->ID, 'ubc_di_assessment_data', true );
				$json_response = array(
					'id' => $ubc_di_assessment->ID,
					'title' => $ubc_di_assessment->post_title,
					'content' => $ubc_di_assessment_slides,
					'data' => $ubc_di_assessment_data,
				);
				wp_send_json( $json_response );
				die();
			}
		}
	}

	/**
		* This is the callback function for ubc-di-admin-assessment-result.js's
		* add_assessment AJAX request, adding a single assessment.
		*
		* @access public
		* @return void
		*/
	function ubc_di_assessment_adder_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_assessment_title'] ) ) {
				$ubc_di_assessment_post = array(
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_title'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_assessment',
				);
				$ubc_di_assessment_id = wp_insert_post( $ubc_di_assessment_post );
				if ( isset( $_POST['ubc_di_assessment_locations'] ) ) {
					add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_sites', $this->sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_locations'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_content'] ) ) {
					add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_slides', sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_content'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_end_date'] ) ) {
					add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_end_date', sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_end_date'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_data'] ) ) {
					add_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_data', $this->sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_data'] ) ) );
				}
				print_r( $this->sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_data'] ) ) );
				echo esc_html( sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_title'] ) ) );
				die();
			}
		}
	}

	/**
		* This is the callback function for ubc-di-admin-assessment-result.js's
		* edit_assessment AJAX request, editing a single existing assessment.
		*
		* @access public
		* @return void
		*/
	function ubc_di_assessment_editor_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_assessment_id'] ) && isset( $_POST['ubc_di_assessment_title'] ) ) {
				$ubc_di_assessment_post = array(
					'ID' => sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_id'] ) ),
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_title'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_assessment',
				);
				$ubc_di_assessment_id = wp_update_post( $ubc_di_assessment_post );
				if ( isset( $_POST['ubc_di_assessment_locations'] ) ) {
					update_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_sites', $this->sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_locations'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_content'] ) ) {
					update_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_slides', sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_content'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_end_date'] ) ) {
					update_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_end_date', sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_end_date'] ) ) );
				}
				if ( isset( $_POST['ubc_di_assessment_data'] ) ) {
					update_post_meta( $ubc_di_assessment_id, 'ubc_di_assessment_data', $this->sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_data'] ) ) );
				}
				echo esc_html( sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_title'] ) ) );
				die();
			}
		}
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
			'label' => 'Books',
			'default' => 10,
			'option' => 'assessments_per_page',
		);

		add_screen_option( $option, $args );
		$my_list_table = new UBC_DI_WP_List_Table_Assessment();

		echo '<div class="digging-in-admin-list">';
		echo '<h3>Existing Assessments</h3>';
		$my_list_table->prepare_items();
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="test_list_table">';
		$my_list_table->display();
		echo '</form>';
		echo '</div>';
	}

}
