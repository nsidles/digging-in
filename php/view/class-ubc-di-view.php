<?php
/**
 * The UBC_DI_View class
 *
 * This file defines the UBC_DI_View class. This
 * class produces the main views of the Digging In plugin.
 *
 * @package WordPress
 * @subpackage Digging_In
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-ubc-di-view-json.php' );

class UBC_DI_View {

	/**
	 * This function constructs the UBC_DI_View object.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->add_shortcodes();
		$this->add_actions();
	}

	/**
	 * This function adds Digging In shortcodes. There is only one, the shortcode
	 * that initializes the Digging In page.
	 *
	 * @access public
	 * @return void
	 */
	public function add_shortcodes() {
		add_shortcode( 'di-map', array( $this, 'ubc_di_make_map' ) );
	}

	/**
	 * This function adds the UBC_DI_View actions, including its AJAX
	 * callback hooks and upload detection hooks for both logged in and logged out
	 * users.
	 *
	 * @access public
	 * @return void
	 */
	public function add_actions() {
		add_action( 'wp_ajax_digging_in_get_sites', array( $this, 'ubc_di_map_callback' ) );
		add_action( 'wp_ajax_digging_in_get_site', array( $this, 'ubc_di_map_site_callback' ) );
		add_action( 'wp_ajax_digging_in_get_assessment', array( $this, 'ubc_di_map_assessment_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_sites', array( $this, 'ubc_di_map_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_site', array( $this, 'ubc_di_map_site_callback' ) );
		add_action( 'wp_ajax_nopriv_digging_in_get_assessment', array( $this, 'ubc_di_map_assessment_callback' ) );
		add_action( 'wp_ajax_digging_in_add_assessment_result', array( $this, 'ubc_di_map_add_assessment_result_callback' ) );
		add_action( 'wp_ajax_digging_in_upload_image', array( $this, 'ubc_di_map_upload_image_callback' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	/**
	 * This function adds the UBC_DI_View scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function add_scripts() {
		wp_enqueue_script( 'ubc_di_map_display_google_script', 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' . get_option( 'ubc_di_google_maps_api_key' ), array( 'jquery' ) );
		wp_register_script( 'ubc_di_map_display_script', plugins_url( 'js/ubc-di-map-view.js', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_script( 'ubc_di_map_display_script', array( 'jquery' ) );
		wp_localize_script( 'ubc_di_map_display_script', 'ubc_di_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * This function adds the UBC_DI_View styles.
	 *
	 * @access public
	 * @return void
	 */
	public function add_styles() {
		wp_register_style( 'ubc_di_view_style', plugins_url( 'css/ubc-di-view-style.css', dirname( dirname( __FILE__ ) ) ) );
		wp_enqueue_style( 'ubc_di_view_style' );
		if ( '' != isset( $_GET['ubc_di_point_view'] ) && sanitize_text_field( wp_unslash( $_GET['ubc_di_point_view'] ) ) ) {
			wp_register_style( 'ubc_di_view_style-mobile', plugins_url( 'css/ubc-di-view-style-mobile.css', dirname( dirname( __FILE__ ) ) ) );
			wp_enqueue_style( 'ubc_di_view_style-mobile' );
		}
	}

	/**
	 * This function sets the behavior to be performed if a Digging In cookie is found for a particular soil site.
	 *
	 * @param object $wp
	 *
	 * @access public
	 * @return void
	 */
	function parse_request() {
		if ( isset( $_COOKIE['ubc_di_point_view'] ) ) {
			$site_id = intval( sanitize_text_field( wp_unslash( $_COOKIE['ubc_di_point_view'] ) ) );
			$redirect_string = get_option( 'ubc_di_login_redirect' ) . '?ubc_di_point_view=' . $site_id;
			setcookie( 'ubc_di_point_view', 0, 1 );
			header( 'Location:' . $redirect_string );
			die();
		}
		if ( isset( $_GET['ubc_di_point_view'] ) ) {
			setcookie( 'ubc_di_point_view', esc_html( sanitize_text_field( wp_unslash( $_GET['ubc_di_point_view'] ) ) ) );
		} else {
			setcookie( 'ubc_di_point_view', 0, 1 );
		}
	}

	/**
	 * This function adds the main Digging In structural elements in the element
	 * in which the Digging In shortcode is called. It also calls the Digging In
	 * scripts and styles.
	 *
	 * @access public
	 * @return void
	 */
	public function ubc_di_make_map() {
		$this->add_scripts();
		$this->add_styles();
		wp_nonce_field( 'ubc_di_nonce_check', 'di-nonce-field' );
		?>
			<div id="di-user" class="hidden"><?php echo esc_html( wp_get_current_user()->user_login ); ?></div>
			<div id="di-user-id" class="hidden"><?php echo esc_html( wp_get_current_user()->ID ); ?></div>
			<div id="di-site-id" class="hidden"><?php echo ( isset( $_GET['ubc_di_point_view'] ) && '' !== $_GET['ubc_di_point_view'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['ubc_di_point_view'] ) ) ) : ''; ?></div>
			<div id="di-layer-1" class="hidden" file="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer1_file' ) ); ?>" label="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer1_label' ) ); ?>"></div>
			<div id="di-layer-2" class="hidden" file="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer2_file' ) ); ?>" label="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer2_label' ) ); ?>"></div>
			<div id="di-layer-3" class="hidden" file="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer3_file' ) ); ?>" label="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer3_label' ) ); ?>"></div>
			<div id="di-layer-4" class="hidden" file="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer4_file' ) ); ?>" label="<?php echo esc_html( get_option( 'ubc_di_google_maps_layer4_label' ) ); ?>"></div>
			<div id="di-bounding-box" class="hidden" centerlat="<?php echo esc_attr( get_option( 'ubc_di_google_maps_center_lat' ) ); ?>" centerlon="<?php echo esc_attr( get_option( 'ubc_di_google_maps_center_lon' ) ); ?>" zoom="<?php echo esc_attr( get_option( 'ubc_di_google_maps_zoom' ) ); ?>" ></div>
			<div id="di-assessment-id" class="hidden"></div>
			<div id="di-assessment-slide-id" class="hidden"></div>
			<div id="di-content">
				<div id="di-map">
					<div id="di-map-control">
						<span id="filter">Filter By:</span>
						<div id="map-control-left">
							<label id="label1"><input type="checkbox" id="map-control-1" checked><?php echo esc_html( get_option( 'ubc_di_google_maps_layer1_label' ) ); ?></label>
							<label id="label2"><input type="checkbox" id="map-control-2" checked><?php echo esc_html( get_option( 'ubc_di_google_maps_layer2_label' ) ); ?></label>
						</div>
						<div id="map-control-right">
							<label id="label3"><input type="checkbox" id="map-control-3" checked><?php echo esc_html( get_option( 'ubc_di_google_maps_layer3_label' ) ); ?></label>
							<label id="label4"><input type="checkbox" id="map-control-4" checked><?php echo esc_html( get_option( 'ubc_di_google_maps_layer4_label' ) ); ?></label>
						</div>
						<div id="di-map-canvas">
						</div>
					</div>
				</div>
				<div id="di-site">
					<div class="main-left">
						<p>Welcome to Digging In!</p>
						<p>Digging In is a soil teaching tool. It allows people to get information on soil sites around their community, as well as take assessments to learn more about those sites.</p>
						<p>Click on a soil site to learn more!</p>
						<p>Log in to get started taking assessments.</p>
						<hr />
					</div>
					<div class="main-login">
						<?php
						if ( get_option( 'ubc_di_login_redirect' ) !== '' ) {
							$redirect_string = get_option( 'ubc_di_login_redirect' );
							echo '<div id="di-header-loginout" class="di-as-button">';
							echo wp_loginout( $redirect_string, true );
							echo '</div>';
						} else {
							if ( isset( $_SERVER['SERVER_NAME'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
								$redirect_string = 'http' . ( empty( $_SERVER['HTTPS'] ) ? '' : 's' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
								if ( isset( $_GET['ubc_di_point_view'] ) ) {
									$redirect_string .= '?ubc_di_point_view=' . sanitize_text_field( wp_unslash( $_GET['ubc_di_point_view'] ) );
								}
								echo '<div id="di-header-loginout" class="di-as-button">';
								echo wp_loginout( $redirect_string, true );
								echo '</div>';
							}
						}
						?>
					</div>
					<div class="main-right"></div>
				</div>
			</div>
			<div id="di-assessment-background">
				<div id="di-assessment">
					<div id="di-assessment-header">
					</div>
					<div id="di-assessment-closer">
						&#10005;
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
						&#10005;
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

	/**
		* This is the callback function for di-map-view.js's
		* digging_in_get_sites AJAX request,
		* retrieving sites to populate the Digging In map.
		*
		* @access public
		* @return void
		*/
	public function ubc_di_map_callback() {

		$args = array(
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'post_type'      => 'ubc_di_site',
		);
		$ubc_di_sites_query = new WP_Query( $args );
		if ( $ubc_di_sites_query->have_posts() ) {
			while ( $ubc_di_sites_query->have_posts() ) {
				$ubc_di_sites_query->the_post();
				$ubc_di_sites[] = get_post();
			}
		}
		wp_reset_postdata();

		$ubc_di_geojson_response = array();
		$ubc_di_geojson = array(
			'type'     => 'FeatureCollection',
			'features' => array(),
		);
		foreach ( $ubc_di_sites as $ubc_di_site ) {
			$temp_inner_array = array();
			$temp_inner_array['type'] = 'Feature';
			$temp_longitude = (float) number_format( (float) get_post_meta( $ubc_di_site->ID, 'ubc_di_site_longitude', true ), 7, '.', '' );
			$temp_latitude = (float) number_format( (float) get_post_meta( $ubc_di_site->ID, 'ubc_di_site_latitude', true ), 7, '.', '' );
			$temp_inner_array['geometry'] = array(
				'type'        => 'Point',
				'coordinates' => array(
					$temp_longitude,
					$temp_latitude,
				),
			);
			$temp_inner_array['properties'] = array(
				'id'    => $ubc_di_site->ID,
				'title' => $ubc_di_site->post_title,
			);
			array_push( $ubc_di_geojson['features'], $temp_inner_array );
		}

		$ubc_di_geojson_response['geojson'] = $ubc_di_geojson;

		wp_send_json( $ubc_di_geojson_response );
		die();
	}

	/**
		* This is the callback function for di-map-view.js's
		* digging_in_get_site AJAX request,
		* retrieving information about a single Digging In site.
		*
		* @access public
		* @return void
		*/
	public function ubc_di_map_site_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {
			if ( isset( $_POST['ubc_di_site_id'] ) ) {
				$ubc_di_site = get_post( sanitize_text_field( wp_unslash( $_POST['ubc_di_site_id'] ) ) );
				$json_response['ID'] = $ubc_di_site->ID;
				$json_response['title'] = $ubc_di_site->post_title;
				$json_response['content'] = $ubc_di_site->post_content;
				$ubc_di_site_id = '%' . $wpdb->esc_like( $ubc_di_site->ID ) . '%';
				$ubc_di_assessments = wp_cache_get( $ubc_di_site->ID . '_assessments' );
				if ( false === $ubc_di_assessments ) {
					$ubc_di_assessments = $wpdb->get_results( $wpdb->prepare(
						"SELECT post_id AS ID FROM $wpdb->postmeta
						WHERE meta_key = 'ubc_di_assessment_sites'
						AND meta_value LIKE %s",
						$ubc_di_site_id
					) );
					wp_cache_set( $ubc_di_site->ID . '_assessments', $ubc_di_assessments );
				}
			}
		}

		$ubc_di_media = get_post_meta( $ubc_di_site->ID, 'ubc_di_point_media' );
		foreach ( $ubc_di_media as $ubc_di_media_array ) {
			foreach ( $ubc_di_media_array as $ubc_di_medium_id ) {
				$ubc_di_medium = get_post( $ubc_di_medium_id );
				$ubc_di_media_meta = get_post_meta( $ubc_di_medium_id, 'ubc_di_media_meta', true );
				if ( $ubc_di_media_meta ) {

					$temp_array = array();

					if ( 'image' === $ubc_di_media_meta['type'] || 'imagewp' === $ubc_di_media_meta['type'] ) {
						$temp_array['url'] = wp_get_attachment_thumb_url( $ubc_di_media_meta['url'] );
						$temp_array['full_size_url'] = wp_get_attachment_url( $ubc_di_media_meta['url'] );
						$temp_array['media'] = '<a href="' . $temp_array['full_size_url'] . '"><img src="' . $temp_array['full_size_url'] . '" /></a>';
					} else if ( 'video' === $ubc_di_media_meta['type'] ) {
						$temp_array['media'] = '<iframe width="600" height="450" src="//www.youtube.com/embed/' . $ubc_di_media_meta['url'] . '" frameborder="0" allowfullscreen></iframe>';
					} else {
						$temp_array['media'] = '<a href="' . $ubc_di_media_meta['url'] . '" target="_blank">' . $ubc_di_media_meta['url'] . '</a>';
					}

					$temp_array['id'] = $ubc_di_medium->ID;
					$temp_array['title'] = $ubc_di_medium->post_title;
					$temp_array['description'] = $ubc_di_medium->post_content;
					$json_response['ubc_di_media'][] = $temp_array;
				}
			}
		}

		foreach ( $ubc_di_assessments as $ubc_di_assessment_id ) {
			$ubc_di_assessment = get_post( $ubc_di_assessment_id->ID );
			$ubc_di_assessment_slides = get_post_meta( $ubc_di_assessment->ID, 'ubc_di_assessment_slides', true );
			$json_response['ubc_di_assessments'][] = array(
				'id' => $ubc_di_assessment->ID,
				'title' => $ubc_di_assessment->post_title,
			);
		}

		wp_send_json( $json_response );
		die();
	}

	/**
		* This is the callback function for di-map-view.js's
		* digging_in_get_assessment AJAX request,
		* retrieving information about a single Digging In site's assessment.
		*
		* @access public
		* @return void
		*/
	public function ubc_di_map_assessment_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {
			if ( isset( $_POST['ubc_di_assessment_id'] ) && isset( $_POST['ubc_di_user_id'] ) ) {
				$ubc_di_assessment_id = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_id'] ) );
				$ubc_di_user_id = sanitize_text_field( wp_unslash( $_POST['ubc_di_user_id'] ) );

				$ubc_di_assessment = get_post( $ubc_di_assessment_id );
				$ubc_di_assessment_slides = get_post_meta( $ubc_di_assessment->ID, 'ubc_di_assessment_slides', true );
				$ubc_di_assessment_data = get_post_meta( $ubc_di_assessment->ID, 'ubc_di_assessment_data', true );
				$ubc_di_group_id = $this->ubc_di_get_group( $ubc_di_user_id );
				$ubc_di_assessment_answers = $this->ubc_di_get_group_answers( $ubc_di_group_id, $ubc_di_assessment_id );
				$ubc_di_assessment_result_id = $this->ubc_di_get_group_assessment_result_id( $ubc_di_group_id, $ubc_di_assessment_id );
				$json_response = array(
					'id' => $ubc_di_assessment->ID,
					'title' => $ubc_di_assessment->post_title,
					'content' => $ubc_di_assessment_slides,
					'data' => $ubc_di_assessment_data,
					'group' => $ubc_di_group_id,
					'answers' => $ubc_di_assessment_answers,
					'answers_id' => $ubc_di_assessment_result_id,
				);
				wp_send_json( $json_response );
				die();
			}
		}
	}

	/**
		* This is the callback function for di-map-view.js's
		* digging_in_upload_image AJAX request,
		* processing a single uploaded image file from the front end.
		*
		* @access public
		* @return void
		*/
	public function ubc_di_map_upload_image_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {
			$site_id  = 2;
			$file_id  = media_handle_upload( 'file', 0 );
			$file_url = wp_get_attachment_url( $file_id );

			$ubc_di_media_post = array(
				'post_title'   => 'Test',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'ubc_di_media',
			);

			$ubc_di_media_post_meta['type']     = 'image';
			$ubc_di_media_post_meta['url']      = $file_id;
			$ubc_di_media_post_meta['location'] = $site_id;
			$ubc_di_media_id                    = wp_insert_post( $ubc_di_media_post );
			add_post_meta( $ubc_di_media_id, 'ubc_di_media_meta', $ubc_di_media_post_meta );
			wp_send_json( $file_url );
			die();
		}
	}

	/**
		* This is a helper function for retrieving a user's associated Digging In
		* group.
		*
		* @param int $ubc_di_user_id The user to get group information about
		*
		* @access public
		* @return ID of the relevant Digging In Group.
		*/
	public function ubc_di_get_group( $ubc_di_user_id ) {
		$ubc_di_groups = get_posts( array(
			'posts_per_page' => -1,
			'post_type' => 'ubc_di_group',
			'order' => 'DESC',
		) );
		foreach ( $ubc_di_groups as $ubc_di_group ) {
			$ubc_di_group_people = get_post_meta( $ubc_di_group->ID, 'ubc_di_group_people', true );
			if ( '' !== $ubc_di_group_people ) {
				foreach ( $ubc_di_group_people as $ubc_di_group_person ) {
					if ( $ubc_di_group_person == $ubc_di_user_id ) {
						return $ubc_di_group->ID;
					}
				}
			}
		}
		return;
	}

	/**
		* This is a helper function for retrieving a user group's associated
		* assessment result ID for a particular assessment.
		*
		* @param int $ubc_di_group_id The Digging In group
		* @param int $ubc_di_assessment_id The Digging In assessment
		*
		* @access public
		* @return Digging In assessment result ID
		*/
	public function ubc_di_get_group_assessment_result_id( $ubc_di_group_id, $ubc_di_assessment_id ) {
		$ubc_di_assessment_results = get_posts( array(
			'post_type' => 'ubc_di_assess_result',
			'order' => 'DESC',
			'posts_per_page' => -1,
		) );
		foreach ( $ubc_di_assessment_results as $ubc_di_assessment_result ) {
			$ubc_di_asr_group_id = get_post_meta( $ubc_di_assessment_result->ID, 'ubc_di_assessment_result_group', true );
			$ubc_di_asr_assessment_id = get_post_meta( $ubc_di_assessment_result->ID, 'ubc_di_assessment_result_assessment', true );
			if ( $ubc_di_asr_assessment_id == $ubc_di_assessment_id && $ubc_di_asr_group_id == $ubc_di_group_id ) {
				return $ubc_di_assessment_result->ID;
			}
		}
		return;
	}

	/**
		* This is a helper function for retrieving a user's associated Digging In
		* assessment results.
		*
		* @param int $ubc_di_group_id The Digging In group
		* @param int $ubc_di_assessment_id The Digging In assessment
		*
		* @access public
		* @return information about a Digging In Assessment Result
		*/
	public function ubc_di_get_group_answers( $ubc_di_group_id, $ubc_di_assessment_id ) {
		$ubc_di_assessment_results = get_posts( array(
			'post_type' => 'ubc_di_assess_result',
			'order' => 'DESC',
			'posts_per_page' => -1,
		) );
		foreach ( $ubc_di_assessment_results as $ubc_di_assessment_result ) {
			$ubc_di_asr_group_id = get_post_meta( $ubc_di_assessment_result->ID, 'ubc_di_assessment_result_group', true );
			$ubc_di_asr_assessment_id = get_post_meta( $ubc_di_assessment_result->ID, 'ubc_di_assessment_result_assessment', true );
			if ( $ubc_di_asr_assessment_id == $ubc_di_assessment_id && $ubc_di_asr_group_id == $ubc_di_group_id ) {
				return $ubc_di_assessment_result->post_content;
			}
		}
		return;
	}

	/**
		* This is the callback function for di-map-view.js's
		* digging_in_add_assessment_result AJAX request,
		* padding an assessment result.
		*
		* @access public
		* @return void
		*/
	public function ubc_di_map_add_assessment_result_callback() {
		global $wpdb;
		if ( ! isset( $_POST['ubc_di_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubc_di_nonce_field'] ) ), 'ubc_di_nonce_check' ) ) {
			echo 'Sorry, WordPress has rejected your submission - specifically, your nonce did not verify. Please reload the form page and try again. This message may occur if you took more than a day to complete your form, if you do not have the appropriate privileges to submit soil groups but nonetheless try, or if the Digging In coding team made an error.';
			die();
		} else {

			$ubc_di_results = get_posts( array(
				'posts_per_page' => -1,
				'order' => 'ASC',
				'post_type' => 'ubc_di_assess_result',
			) );
			$ubc_di_sites = get_posts( array(
				'posts_per_page' => -1,
				'order' => 'ASC',
				'post_type' => 'ubc_di_site',
			) );
			$ubc_di_groups = get_posts( array(
				'posts_per_page' => -1,
				'order' => 'ASC',
				'post_type' => 'ubc_di_group',
			) );

			foreach ( $ubc_di_groups as $ubc_di_group ) {
				$ubc_di_group_students = get_post_meta( $ubc_di_group->ID, 'ubc_di_group_people', true );
				$in_group = false;
				foreach ( $ubc_di_group_students as $ubc_di_group_student ) {
					if ( isset( $_POST['ubc_di_assessment_result_title'] ) && isset( $_POST['ubc_di_assessment_result_user'] ) && isset( $_POST['ubc_di_assessment_result_data'] ) && isset( $_POST['ubc_di_assessment_result_site'] ) && isset( $_POST['ubc_di_assessment_result_assessment'] ) ) {
						$ubc_di_assessment_result_title = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_title'] ) );
						$ubc_di_assessment_result_user = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_user'] ) );
						$ubc_di_assessment_result_data = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_data'] ) );
						$ubc_di_assessment_result_site = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_site'] ) );
						$ubc_di_assessment_result_assessment = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_assessment'] ) );

						if ( $ubc_di_group_student === $_POST['ubc_di_assessment_result_user'] ) {
							$in_group = true;
							$ubc_di_assessment_result_post = array(
								'post_title' => sanitize_text_field( $ubc_di_assessment_result_title ),
								'post_author' => sanitize_text_field( $ubc_di_assessment_result_user ),
								'post_content' => sanitize_text_field( $ubc_di_assessment_result_data ),
								'post_status' => 'publish',
								'post_type' => 'ubc_di_assess_result',
							);
							if ( isset( $_POST['ubc_di_assessment_result_id'] ) && '' !== $_POST['ubc_di_assessment_result_id'] ) {
								$ubc_di_assessment_result_post['ID'] = sanitize_text_field( wp_unslash( $_POST['ubc_di_assessment_result_id'] ) );
								$ubc_di_assessment_result_id = wp_update_post( $ubc_di_assessment_result_post );
							} else {
								$ubc_di_assessment_result_id = wp_insert_post( $ubc_di_assessment_result_post );
							}
							add_post_meta( $ubc_di_assessment_result_id, 'ubc_di_assessment_result_group', $ubc_di_group->ID );
							add_post_meta( $ubc_di_assessment_result_id, 'ubc_di_assessment_result_site', $ubc_di_assessment_result_site );
							add_post_meta( $ubc_di_assessment_result_id, 'ubc_di_assessment_result_assessment', $ubc_di_assessment_result_assessment );
						}
					}
				}
			}
			if ( true === $in_group ) {
				echo 'Assessment submitted! (ID# ' . esc_html( $ubc_di_assessment_result_id ) . ')';
			} else {
				echo 'Assessment not submitted. User must be part of user group.';
			}
		}
		die();
	}
}
