<?php
/**
 * The DI_View class
 *
 * This file defines the DI_View class. This
 * class produces the main views of the Digging In plugin.
 *
 * @package WordPress
 * @subpackage Digging_In
 */

// require_once( plugin_dir_path( __FILE__ ).'class-di-admin-site.php' );

require_once( plugin_dir_path( __FILE__ ).'class-di-view-json.php' );
new DI_View_JSON();

class DI_View {

	/**
	 * This function constructs the DI_View object.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->add_shortcodes();
		$this->add_actions();
	}

	/**
	 * This function adds the desired UBCAR query variable to the query_vars
	 * array
	 *
	 * @param array $query_vars
	 *
	 * @access public
	 * @return array
	 */
	public function add_shortcodes() {
		add_shortcode( 'di-map', array( $this, 'di_make_map' ) );
	}

	public function add_actions() {
		add_action( 'wp_ajax_digging_in_get_sites', array( $this, 'di_map_callback' ) );
		add_action( 'wp_ajax_digging_in_get_site', array( $this, 'di_map_site_callback' ) );
		add_action( 'wp_ajax_digging_in_get_assessment', array( $this, 'di_map_assessment_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_sites', array( $this, 'di_map_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_site', array( $this, 'di_map_site_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_assessment', array( $this, 'di_map_assessment_callback' ) );
		add_action( 'wp_ajax_digging_in_add_assessment_result', array( $this, 'di_map_add_assessment_result_callback' ) );
	}

	public function add_scripts() {
		wp_enqueue_script( 'di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'di_google_maps_api_key' ), array( 'jquery' ) );
		wp_register_script( 'di_map_display_script', plugins_url( 'js/di-map-view.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'di_map_display_script', array( 'jquery' ) );
		wp_localize_script( 'di_map_display_script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function add_styles() {
		wp_register_style( 'di_view_style', plugins_url( 'css/di-view-style.css', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_style( 'di_view_style' );
		if( isset( $_GET['di_point_view'] ) && $_GET['di_point_view'] != '' ) {
			wp_register_style( 'di_view_style-mobile', plugins_url( 'css/di-view-style-mobile.css', dirname( dirname( __FILE__ ) ) ) );
			wp_enqueue_style( 'di_view_style-mobile' );
		}
	}

	public function di_make_map() {
		$this->add_scripts();
		$this->add_styles();
		wp_nonce_field( 'di_nonce_check','di-nonce-field' );
		?>
			<div id="di-user" class="hidden"><?php echo wp_get_current_user()->user_login; ?></div>
			<div id="di-user-id" class="hidden"><?php echo wp_get_current_user()->ID; ?></div>
			<div id="di-site-id" class="hidden"><?php echo ( isset( $_GET['di_point_view'] ) && $_GET['di_point_view'] != '' ) ? $_GET['di_point_view'] : ''; ?></div>
			<div id="di-assessment-id" class="hidden"></div>
			<div id="di-assessment-slide-id" class="hidden"></div>
			<div id="di-map">
				<div id="di-map-canvas">
				</div>
				<div id="di-map-control">
	        <label id="label1"><input type="checkbox" id="map-control-1" checked> Delta-Tsawwassen</label>
	        <label id="label2"><input type="checkbox" id="map-control-2" checked> Bose-Heron</label>
	        <label id="label3"><input type="checkbox" id="map-control-3" checked> Langley-Cloverdale</label>
	        <label id="label4"><input type="checkbox" id="map-control-4" checked> Whatcom-Scat</label>
	      </div>
			</div>
			<div id="di-site">
				<div class="main-left"></div>
				<div class="main-right"></div>
			</div>
			<div id="di-assessment-background">
				<div id="di-assessment">
					<div id="di-assessment-header">
					</div>
					<div id="di-assessment-closer">
						&#10006;
					</div>
					<div id="di-assessment-reviewer">
						Review
					</div>
					<div id="di-assessment-body">
					</div>
					<div id="di-assessment-footer">
					</div>
				</div>
				<div id="di-review">
					<div id="di-review-header">
						<h2>Review Answers</h2>
					</div>
					<div id="di-reviewer-closer">
						&#10006;
					</div>
					<div id="di-assessment-returner">
						Return
					</div>
					<div id="di-reviewer-body">
					</div>
					<div id="di-reviewer-footer">
					</div>
				</div>
			</div>
		<?php
	}

	public function di_map_callback() {
		$di_sites = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_site' ) );

		$di_geojson_response = array();
		$di_geojson = array(
			'type' => 'FeatureCollection',
			'features' => array()
		);
		foreach( $di_sites as $di_site ) {
			$temp_inner_array = array();
			$temp_inner_array['type'] = 'Feature';
			$temp_longitude = ( float )number_format( ( float )get_post_meta( $di_site->ID, 'di_site_longitude', true ), 7, '.', '' );
			$temp_latitude = ( float )number_format( ( float )get_post_meta( $di_site->ID, 'di_site_latitude', true ), 7, '.', '' );
			$temp_inner_array['geometry'] = array(
				'type' => 'Point',
				'coordinates' =>  array(
														$temp_longitude,
														$temp_latitude
													)
			);
			$temp_inner_array['properties'] = array(
				'id' => $di_site->ID,
				'title' => $di_site->post_title
			);
			array_push( $di_geojson['features'], $temp_inner_array );
		}

		$di_geojson_response['geojson'] = $di_geojson;

		wp_send_json( $di_geojson_response );
		die();
	}

	public function di_map_site_callback() {
		global $wpdb;
		$di_site = get_post( $_POST['di_site_id'] );
		$json_response['ID'] = $di_site->ID;
		$json_response['title'] = $di_site->post_title;
		$json_response['content'] = $di_site->post_content;
		$di_assessments = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id AS ID FROM $wpdb->postmeta
			WHERE meta_key = '%s'
			AND meta_value LIKE '%%%s%%'",
			'di_assessment_sites',
			$di_site->ID
		) );

		$di_media = get_post_meta( $di_site->ID, 'di_point_media' );
		foreach( $di_media as $di_media_array ) {
			foreach( $di_media_array as $di_medium_id ) {
				$di_medium = get_post( $di_medium_id );
				$di_media_meta = get_post_meta( $di_medium_id, 'di_media_meta', true );
				if( $di_media_meta ) {

					$tempArray = array();

					if( $di_media_meta['type'] == 'image' || $di_media_meta['type'] == 'imagewp' ) {
						$tempArray["url"] = wp_get_attachment_thumb_url( $di_media_meta['url'] );
						$tempArray["full_size_url"] = wp_get_attachment_url( $di_media_meta['url'] );
						$tempArray["media"] = '<a href="' . $tempArray["full_size_url"] . '"><img src="' . $tempArray["full_size_url"] . '" /></a>';
					} else if( $di_media_meta['type'] == 'video' ) {
						$tempArray["media"] = '<iframe width="600" height="450" src="//www.youtube.com/embed/' . $di_media_meta['url'] . '" frameborder="0" allowfullscreen></iframe>';
					} else {
						$tempArray["media"] = $di_media_meta['url'];
					}

					$tempArray["id"] = $di_medium->ID;
					$tempArray["title"] = $di_medium->post_title;
					$tempArray["description"] = $di_medium->post_content;
					$json_response['di_media'][] = $tempArray;
				}
			}
		}

		foreach( $di_assessments as $di_assessment_id ) {
			$di_assessment = get_post( $di_assessment_id->ID );
			$di_assessment_slides = get_post_meta( $di_assessment->ID, 'di_assessment_slides', true );
			$json_response['di_assessments'][] = array(
				'id' => $di_assessment->ID,
				'title' => $di_assessment->post_title
			);
		}

		wp_send_json( $json_response );
		die();
	}

	public function di_map_assessment_callback() {
		global $wpdb;
		$di_assessment = get_post( $_POST['di_assessment_id'] );
		$di_assessment_slides = get_post_meta( $di_assessment->ID, 'di_assessment_slides', true );
		$di_assessment_data = get_post_meta( $di_assessment->ID, 'di_assessment_data', true );
		$di_group_id = $this->di_get_group( $_POST['di_user_id'] );
		$di_assessment_answers = $this->di_get_group_answers( $di_group_id, $_POST['di_assessment_id'] );
		$di_assessment_result_id = $this->di_get_group_assessment_result_id( $di_group_id, $_POST['di_assessment_id'] );
		$json_response = array(
			'id' => $di_assessment->ID,
			'title' => $di_assessment->post_title,
			'content' => $di_assessment_slides,
			'data' => $di_assessment_data,
			'group' => $di_group_id,
			'answers' => $di_assessment_answers,
			'answers_id' => $di_assessment_result_id
		);
		wp_send_json( $json_response );
		die();
	}

	public function di_get_group( $di_user_id ) {
		$di_groups = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'di_group', 'order' => 'DESC' ) );
		foreach( $di_groups as $di_group ) {
			$di_group_people = get_post_meta( $di_group->ID, 'di_group_people', true );
			if( $di_group_people != '' ) {
				foreach( $di_group_people as $di_group_person ) {
					if( $di_group_person == $di_user_id ) {
						return $di_group->ID;
					}
				}
			}
		}
		return;
	}

	public function di_get_group_assessment_result_id( $di_group_id, $di_assessment_id ) {
		$di_assessment_results = get_posts( array( 'post_type' => 'di_assessment_result', 'order' => 'DESC', 'posts_per_page' => -1 ) );
		foreach( $di_assessment_results as $di_assessment_result ) {
			$di_asr_group_id = get_post_meta( $di_assessment_result->ID, 'di_assessment_result_group', true );
			$di_asr_assessment_id = get_post_meta( $di_assessment_result->ID, 'di_assessment_result_assessment', true );
			if( $di_asr_assessment_id == $di_assessment_id && $di_asr_group_id == $di_group_id ) {
				return $di_assessment_result->ID;
			}
		}
		return;
	}

	public function di_get_group_answers( $di_group_id, $di_assessment_id ) {
		$di_assessment_results = get_posts( array( 'post_type' => 'di_assessment_result', 'order' => 'DESC', 'posts_per_page' => -1 ) );
		foreach( $di_assessment_results as $di_assessment_result ) {
			$di_asr_group_id = get_post_meta( $di_assessment_result->ID, 'di_assessment_result_group', true );
			$di_asr_assessment_id = get_post_meta( $di_assessment_result->ID, 'di_assessment_result_assessment', true );
			if( $di_asr_assessment_id == $di_assessment_id && $di_asr_group_id == $di_group_id ) {
				return $di_assessment_result->post_content;
			}
		}
		return;
	}

	public function di_map_add_assessment_result_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {

			$di_results = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_assessment_result' ) );
			$di_groups = $di_sites = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_site' ) );

			$di_groups = get_posts( array( 'posts_per_page' => -1, 'order' => 'ASC', 'post_type' => 'di_group' ) );

			foreach( $di_groups as $di_group ) {
				$di_group_students = get_post_meta( $di_group->ID, 'di_group_people', true );
				foreach( $di_group_students as $di_group_student ) {
					if( $di_group_student == $_POST['di_assessment_result_user'] ) {
						$di_assessment_result_post = array(
								'post_title' => sanitize_text_field( $_POST['di_assessment_result_title'] ),
								'post_author' => sanitize_text_field( $_POST['di_assessment_result_user'] ),
								'post_content' => sanitize_text_field( $_POST['di_assessment_result_data'] ),
								'post_status' => 'publish',
								'post_type' => 'di_assessment_result'
						);
						if( $_POST['di_assessment_result_id'] != '' ) {
							$di_assessment_result_post['ID'] = $_POST['di_assessment_result_id'];
							$di_assessment_result_id = wp_update_post( $di_assessment_result_post );
						} else {
							$di_assessment_result_id = wp_insert_post( $di_assessment_result_post );
						}
						add_post_meta( $di_assessment_result_id, 'di_assessment_result_group', $di_group->ID );
						add_post_meta( $di_assessment_result_id, 'di_assessment_result_site', $_POST['di_assessment_result_site'] );
						add_post_meta( $di_assessment_result_id, 'di_assessment_result_assessment', $_POST['di_assessment_result_assessment'] );
					}
				}
			}
			echo "Assessment submitted! (ID# " . $di_assessment_result_id . ")";
		}
		die();
	}
}

?>
