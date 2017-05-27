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

// require_once( plugin_dir_path( __FILE__ ).'class-di-admin-site.php' );

require_once( plugin_dir_path( __FILE__ ).'class-di-view-json.php' );
new DI_View_JSON();

class DI_View {

	public function __construct() {
		$this->add_shortcodes();
		$this->add_actions();
	}

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
	}

	public function di_make_map() {
		$this->add_scripts();
		$this->add_styles();
		wp_nonce_field( 'di_nonce_check','di-nonce-field' );
		if( isset( $_GET['di_point_view'] ) && $_GET['di_point_view'] != '' ) {
		}
		?>
			<div id="di-user" class="hidden"><?php echo wp_get_current_user()->user_login; ?></div>
			<div id="di-user-id" class="hidden"><?php echo wp_get_current_user()->ID; ?></div>
			<div id="di-site-id" class="hidden"><?php echo ( isset( $_GET['di_point_view'] ) && $_GET['di_point_view'] != '' ) ? $_GET['di_point_view'] : ''; ?></div>
			<div id="di-assessment-id" class="hidden"></div>
			<div id="di-assessment-slide-id" class="hidden"></div>
			<div id="di-map-canvas">
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
				$di_media_author = get_user_by( 'id', $di_medium_id->post_author );

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
				$tempArray["uploader"] = $di_site_author->first_name . ' ' . $di_site_author->last_name . ' (' . $di_site_author->user_login . ')';
				$tempArray["title"] = $di_medium->post_title;
				$tempArray["description"] = $di_medium->post_content;
				$json_response['di_media'][] = $tempArray;
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
		$json_response = array(
			'id' => $di_assessment->ID,
			'title' => $di_assessment->post_title,
			'content' => $di_assessment_slides,
			'data' => $di_assessment_data
		);
		wp_send_json( $json_response );
		die();
	}

	public function di_map_add_assessment_result_callback() {
		global $wpdb;
		if ( !isset( $_POST['di_nonce_field'] ) || !wp_verify_nonce( $_POST['di_nonce_field'],'di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
		} else {
			$di_assessment_result_post = array(
					'post_title' => sanitize_text_field( $_POST['di_assessment_result_title'] ),
					'post_author' => sanitize_text_field( $_POST['di_assessment_result_user'] ),
					'post_content' => sanitize_text_field( $_POST['di_assessment_result_data'] ),
					'post_status' => 'publish',
					'post_type' => 'di_assessment_result'
			);
			$di_assessment_result_id = wp_insert_post( $di_assessment_result_post );
			add_post_meta( $di_assessment_result_id, 'di_assessment_result_site', $_POST['di_assessment_result_site'] );
			add_post_meta( $di_assessment_result_id, 'di_assessment_result_assessment', $_POST['di_assessment_result_assessment'] );
		}
		echo "Assessment submitted!";
		die();
	}
}

?>
