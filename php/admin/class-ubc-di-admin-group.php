<?php
/**
* The UBC_DI_Admin_Group class.
*
* This file defines the UBC_DI_Admin_Group class. The UBC_DI_Admin_Group class manages
* ubc_di_group posts.
*
* ubc_di_group posts contain groups of users with assigned T.A.s. These groups are
* associated with ubc_di_assessment_result posts for teacher evaluation.
*
* ubc_di_group posts have two extra pieces of metadata:
* - ubc_di_group_people: the students associated with the group.
* - ubc_di_group_ta: the T.A. associated with the group.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ) . 'list-table/class-ubc-di-wp-list-table-group.php' );

class UBC_DI_Admin_Group extends UBC_DI_Admin {

	/**
	 * This function adds the UBC_DI_Admin_Group actions,including its AJAX
	 * callback hooks and upload detection hooks.
	 *
	 * @access public
	 * @return void
	 */
	function add_actions() {
		add_action( 'wp_ajax_group_updater', array( $this, 'ubc_di_group_updater_callback' ) );
		add_action( 'wp_ajax_group_editer', array( $this, 'ubc_di_group_editer_callback' ) );
	}

	/**
	 * This function adds the UBC_DI_Admin_Group administration options. It also
	 * detects if a particular item is to be deleted or edited.
	 *
	 * @access public
	 * @return void
	 */
	function add_menu_page() {
		$this->add_new_item();
		if ( isset( $_GET['group'] ) && isset( $_GET['action'] ) && 'edit' == sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->edit_item( intval( $_GET['group'] ) );
		}
		if ( isset( $_GET['group'] ) && isset( $_GET['action'] ) && 'delete' == sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->delete_item( intval( $_GET['group'] ) );
		}
		$this->add_list_table();
	}

	/**
	 * This function adds the UBC_DI_Admin_Group add item pane and its options to
	 * the top of the page.
	 *
	 * @access public
	 * @return void
	 */
	function add_new_item() {
		wp_register_script( 'ubc_di_control_panel_group_updater_script', plugins_url( 'js/ubc-di-group-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_group_updater_script', array( 'jquery', 'ubc_di_control_panel_script' ) );
		wp_localize_script( 'ubc_di_control_panel_group_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'ubc_di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );

		$ubc_di_people = get_users();
		foreach ( $ubc_di_people as $ubc_di_person ) {
			$ubc_di_people_map[ $ubc_di_person->ID ] = $ubc_di_person;
		}

		$ubc_di_groups = get_posts( array(
			'posts_per_page' => -1,
			'post_type' => 'ubc_di_group',
			'order' => 'DESC',
		) );

		foreach ( $ubc_di_groups as $ubc_di_group ) {
			$ubc_di_group_people = get_post_meta( $ubc_di_group->ID, 'ubc_di_group_people', true );
			if ( '' != $ubc_di_group_people ) {
				foreach ( $ubc_di_group_people as $ubc_di_group_person ) {
					if ( isset( $ubc_di_people_map[ $ubc_di_group_person ] ) ) {
						unset( $ubc_di_people_map[ $ubc_di_group_person ] );
					}
				}
			}
		}

		?>
			<h1>Digging In Students and Groups</h1>
			<p></p>
			<hr />
			<h3 id="di-add-new-toggle">Add New Group<span class="di-menu-toggle" id="di-add-toggle-arrow">&#9660</span></h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-add-new-form">
					<?php
						wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
					?>
					<div class="admin-wrapper">
						<label>Group Name</label>
						<input name="di-group-title" type="text" id="di-group-title" value="" class="regular-text ltr" />
					</div>
					<div class="admin-wrapper">
						<label>Soil Group T.A.</label>
						<select id="di-group-ta">
						<?php
						foreach ( $ubc_di_people as $ubc_di_person ) {
							echo '<option value="' . esc_attr( $ubc_di_person->ID ) . '">' . esc_html( $ubc_di_person->first_name ) . ' ' . esc_html( $ubc_di_person->last_name ) . '</option>';
						}
						?>
						</select>
					</div>
					<div class="admin-wrapper">
						<div class="di-tour-sites">
							<h4>Available Students (unassigned)</h4>
							<ul id="di-group-people-complete-list" class="di-group-order-people">
							<?php
							foreach ( $ubc_di_people_map as $ubc_di_person ) {
								echo '<li>' . esc_html( $ubc_di_person->first_name ) . ' ' . esc_html( $ubc_di_person->last_name ) . ' (' . esc_html( $ubc_di_person->display_name ) . ')<input type="hidden" value="' . esc_attr( $ubc_di_person->ID ) . '"></li>';
							}
							?>
							</ul>
						</div>
						<div class="di-tour-sites">
							<h4>Selected Students</h4>
							<ul id="di-group-people-selected-list" class="di-group-order-people">
							</ul>
						</div>
					</div>
					<div class="admin-wrapper">
						<br />
					</div>
					<div class="admin-wrapper">
						<div class="button button-primary" name="di-group-submit" id="di-group-submit">Upload Group</div>
					</div>
				</form>
			</div>
			<hr />
		<?php
	}

	/**
	 * This function adds the UBC_DI_Admin_Group edit item pane and its options to
	 * the top of the page.
	 *
	 * @param int $item_id - the group to edit
	 *
	 * @access public
	 * @return void
	 */
	function edit_item( $item_id ) {
		$item = get_post( $item_id );
		$item_title = $item->post_title;
		$item_ta = get_post_meta( $item->ID, 'ubc_di_group_ta', true );
		$item_students = get_post_meta( $item->ID, 'ubc_di_group_people', true );
		$ubc_di_people = get_users();
		if ( '' != $item_students ) {
			foreach ( $item_students as $ubc_di_person ) {
				$ubc_di_people_ids[] = $ubc_di_person;
			}
		}
		?>
			<h3 id="di-add-new-toggle">Edit Group</h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-edit-form">
					<a id="di-group-id_edit" value="<?php echo esc_attr( $item_id ); ?>"></a>
					<div class="admin-wrapper">
						<label>Group Name</label>
						<input name="di-group-title_edit" type="text" id="di-group-title_edit" value="<?php echo esc_attr( $item_title ); ?>" class="regular-text ltr" />
					</div>
					<div class="admin-wrapper">
						<label>Soil Group T.A.</label>
						<select id="di-group-ta_edit">
							<?php
							foreach ( $ubc_di_people as $ubc_di_person ) {
								echo '<option value="' . esc_attr( $ubc_di_person->ID ) . '">' . esc_html( $ubc_di_person->first_name ) . ' ' . esc_html( $ubc_di_person->last_name ) . '</option>';
							}
							?>
						</select>
					</div>
					<div class="admin-wrapper">
						<div class="di-tour-sites">
							<h4>Available Students</h4>
							<ul id="di-group-people-complete-list_edit" class="di-group-order-people_edit">
							<?php
							foreach ( $ubc_di_people as $ubc_di_person ) {
								if ( isset( $ubc_di_people_ids ) ) {
									if ( ! in_array( $ubc_di_person->ID, $ubc_di_people_ids ) ) {
										echo '<li>' . esc_html( $ubc_di_person->first_name ) . ' ' . esc_html( $ubc_di_person->last_name ) . ' (#' . esc_html( $ubc_di_person->ID ) . ')<input type="hidden" value="' . esc_attr( $ubc_di_person->ID ) . '"></li>';
									}
								}
							}
							?>
							</ul>
						</div>
						<div class="di-tour-sites">
							<h4>Selected Students</h4>
							<ul id="di-group-people-selected-list_edit" class="di-group-order-people_edit">
							<?php
							foreach ( $item_students as $ubc_di_person ) {
								$ubc_di_person = get_userdata( $ubc_di_person );
								if ( '' != $ubc_di_person ) {
									echo '<li>' . esc_html( $ubc_di_person->first_name ) . ' ' . esc_html( $ubc_di_person->last_name ) . ' (#' . esc_html( $ubc_di_person->ID ) . ')<input type="hidden" value="' . esc_attr( $ubc_di_person->ID ) . '"></li>';
								}
							}
							?>
							</ul>
						</div>
					</div>
					<div class="admin-wrapper">
						<br />
					</div>
					<div class="admin-wrapper">
						<div class="button button-primary" name="di-group-submit_edit" id="di-group-submit_edit">Upload Group</div>
					</div>
				</form>
			</div>
		<?php
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
			'option' => 'groups_per_page',
		);
		add_screen_option( $option, $args );
		$my_list_table = new UBC_DI_WP_List_Table_Group();

		echo '<div class="wrap"><h3>Existing Groups</h3>';
		$my_list_table->prepare_items();
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="ttest_list_table">';
		$my_list_table->display();
		echo '</form></div>';
	}

	/**
		* This is the callback function for ubc-di-admin-group-updater.js's
		* group_updater AJAX request,
		* adding a group to the WordPress site.
		*
		* @access public
		* @return void
		*/
	function ubc_di_group_updater_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_group_title'] ) && isset( $_POST['ubc_di_group_ta'] ) && isset( $_POST['ubc_di_group_people'] ) ) {
				$ubc_di_group_post = array(
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_group_title'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_group',
				);
				$ubc_di_group_id = wp_insert_post( $ubc_di_group_post );
				$ubc_di_group_people = explode( ',', sanitize_text_field( wp_unslash( $_POST['ubc_di_group_people'] ) ) );
				array_pop( $ubc_di_group_people );
				add_post_meta( $ubc_di_group_id, 'ubc_di_group_ta', intval( $_POST['ubc_di_group_ta'] ) );
				add_post_meta( $ubc_di_group_id, 'ubc_di_group_people', $ubc_di_group_people );
				die();
			}
		}
	}

	/**
		* This is the callback function for ubc-di-admin-group-updater.js's
		* group_editer AJAX request,
		* editing an existing group in the WordPress site.
		*
		* @access public
		* @return void
		*/
	function ubc_di_group_editer_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			if ( isset( $_POST['ubc_di_group_title'] ) && isset( $_POST['ubc_di_group_people'] ) && isset( $_POST['ubc_di_group_ta'] ) && isset( $_POST['ubc_di_group_id'] ) ) {
				$ubc_di_group_post = array(
					'ID' => intval( $_POST['ubc_di_group_id'] ),
					'post_title' => sanitize_text_field( wp_unslash( $_POST['ubc_di_group_title'] ) ),
					'post_status' => 'publish',
					'post_type' => 'ubc_di_group',
				);
				$ubc_di_group_id = wp_update_post( $ubc_di_group_post );
				$ubc_di_group_people = explode( ',', sanitize_text_field( wp_unslash( $_POST['ubc_di_group_people'] ) ) );
				array_pop( $ubc_di_group_people );
				update_post_meta( $ubc_di_group_id, 'ubc_di_group_people', $ubc_di_group_people );
				update_post_meta( $ubc_di_group_id, 'ubc_di_group_ta', intval( $_POST['ubc_di_group_ta'] ) );
				echo intval( $_POST['ubc_di_group_id'] );
				die();
			}
		}
	}

}
