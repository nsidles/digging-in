<?php
/**
* The DI_Admin_Group subclass.
*
* @package WordPress
* @subpackage Digging_In
*/

require_once( plugin_dir_path( dirname( __FILE__ ) ).'list-table/class-di-wp-list-table-group.php' );

class DI_Admin_Group extends DI_Admin {

	function add_actions() {
		add_action( 'wp_ajax_group_updater', array( $this, 'di_group_updater_callback' ) );
		add_action( 'wp_ajax_group_editer', array( $this, 'di_group_editer_callback' ) );
	}

	function add_menu_page() {
		$this->add_new_item();
		if( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'edit' ) {
			$this->edit_item( intval( $_GET['group'] ) );
		}
		if( isset( sanitize_text_field( $_GET['action'] ) ) && sanitize_text_field( $_GET['action'] ) == 'delete' ) {
			$this->delete_item( intval( $_GET['group'] ) );
		}
		$this->add_list_table();
	}

	function add_new_item () {
		wp_register_script( 'di_control_panel_group_updater_script', plugins_url( 'js/di-group-updater.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_control_panel_group_updater_script', array( 'jquery', 'di_control_panel_script' ) );
		wp_localize_script( 'di_control_panel_group_updater_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'di_control_panel_script', array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ) );

		$di_people = get_users();
		foreach( $di_people as $di_person )
			$di_people_map[$di_person->ID] = $di_person;

		$di_groups = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'di_group', 'order' => 'DESC' ) );
		foreach( $di_groups as $di_group ) {
			$di_group_people = get_post_meta( $di_group->ID, 'di_group_people', true );
			if( $di_group_people != '' )
				foreach( $di_group_people as $di_group_person ) {
					if( isset( $di_people_map[$di_group_person] ) )
						unset( $di_people_map[$di_group_person] );
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
						wp_nonce_field( 'di_nonce_check','di-nonce-field' );
					?>
					<div class="admin-wrapper">
						<label>Group Name</label>
						<input name="di-group-title" type="text" id="di-group-title" value="" class="regular-text ltr" />
					</div>
					<div class="admin-wrapper">
						<label>Soil Group T.A.</label>
						<select id="di-group-ta">
							<?php
							foreach ( $di_people as $di_person ) {
								echo '<option value="' . $di_person->ID . '">' . $di_person->first_name . ' ' . $di_person->last_name . '</option>';
							}
							?>
						</select>
					</div>
					<div class="admin-wrapper">
						<div class="di-tour-sites">
							<h4>Available Students (unassigned)</h4>
							<ul id="di-group-people-complete-list" class="di-group-order-people">
							<?php
								foreach ( $di_people_map as $di_person ) {
									echo '<li>' . $di_person->first_name . ' ' . $di_person->last_name . ' (' . $di_person->display_name . ')<input type="hidden" value="' . $di_person->ID . '"></li>';
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

	function edit_item( $item_id ) {
		$item = get_post( $item_id );
		$item_title = $item->post_title;
		$item_ta = get_post_meta( $item->ID, 'di_group_ta', true );
		$item_students = get_post_meta( $item->ID, 'di_group_people', true );
		$di_people = get_users();
		if( $item_students != "" )
			foreach( $item_students as $di_person ) {
				@$di_people_ids[] = $di_person;
			}
		?>
			<h3 id="di-add-new-toggle">Edit Group</h3>
			<div class="wrap">
				<form method="POST" action="" style="width: 100%;" id="di-edit-form">
					<a id="di-group-id_edit" value="<?php echo $item_id ?>"></a>
					<div class="admin-wrapper">
						<label>Group Name</label>
						<input name="di-group-title_edit" type="text" id="di-group-title_edit" value="<?php echo $item_title; ?>" class="regular-text ltr" />
					</div>
					<div class="admin-wrapper">
						<label>Soil Group T.A.</label>
						<select id="di-group-ta_edit">
							<?php
							foreach ( $di_people as $di_person ) {
								echo '<option value="' . $di_person->ID . '">' . $di_person->first_name . ' ' . $di_person->last_name . '</option>';
							}
							?>
						</select>
					</div>
					<div class="admin-wrapper">
						<div class="di-tour-sites">
							<h4>Available Students</h4>
							<ul id="di-group-people-complete-list_edit" class="di-group-order-people_edit">
							<?php
								foreach ( $di_people as $di_person ) {
									if( isset( $di_people_ids ) )
										if( !in_array( $di_person->ID, $di_people_ids ) )
											echo '<li>' . $di_person->first_name . ' ' . $di_person->last_name . ' (#' . $di_person->ID . ')<input type="hidden" value="' . $di_person->ID . '"></li>';
								}
							?>
							</ul>
						</div>
						<div class="di-tour-sites">
							<h4>Selected Students</h4>
							<ul id="di-group-people-selected-list_edit" class="di-group-order-people_edit">
							<?php
								foreach ( $item_students as $di_person ) {
									$di_person = get_userdata( $di_person );
									if( $di_person != "" )
										echo '<li>' . $di_person->first_name . ' ' . $di_person->last_name . ' (#' . $di_person->ID . ')<input type="hidden" value="' . $di_person->ID . '"></li>';
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

	function delete_item( $item ) {
		wp_delete_post( $item );
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
	  $myListTable = new DI_WP_List_Table_Group();

		echo '<div class="wrap"><h3>Existing Groups</h3>';

  	$myListTable->prepare_items();
  	echo '<form method="post">';
    echo '<input type="hidden" name="page" value="ttest_list_table">';
  	$myListTable->display();
  	echo '</form></div>';
	}

	function di_group_updater_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_group_post = array(
					'post_title' => sanitize_text_field( $_POST['di_group_title'] ),
					'post_status' => 'publish',
					'post_type' => 'di_group'
			);
			$di_group_id = wp_insert_post( $di_group_post );
			add_post_meta( $di_group_id, 'di_group_ta', intval( $_POST['di_group_ta'] ) );
			add_post_meta( $di_group_id, 'di_group_people', intval( $_POST['di_group_people'] ) );
		}
		die();
	}

	function di_group_editer_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil sites but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_group_post = array(
					'ID' => $_POST['di_group_id'],
					'post_title' => sanitize_text_field( $_POST['di_group_title'] ),
					'post_status' => 'publish',
					'post_type' => 'di_group'
			);
			$di_group_id = wp_update_post( $di_group_post );
			update_post_meta( $di_group_id, 'di_group_people', sanitize_text_field( $_POST['di_group_people'] ) );
			update_post_meta( $di_group_id, 'di_group_ta', intval( $_POST['di_group_ta'] ) );
		}
		echo $_POST['di_group_id'];
		die();
	}

}

?>
