// This file contains Javascript code used to set site-wide options (e.g., the Google Maps API key necessary to use Google Maps functions).

jQuery( document ).ready(function( $ ) {

	// Event handler for submitting options.
	jQuery( '#di-options-submit' ).click(function() {
		updateOptions();
	});

	// Event handler for deleting all media.
	jQuery( '#di-delete-all-media' ).click(function() {
    if ( confirm( "I understand and confirm I wish to delete ALL existing Digging In media" ) === true ) {
        deleteMedia();
    }
	});

	// Event handler for deleting all student groups
	jQuery( '#di-delete-all-groups' ).click(function() {
		if ( confirm( "I understand and confirm I wish to delete ALL existing Digging In student groups. Students will no longer be able to submit assessments until new groups are formed." ) === true ) {
			deleteGroups();
		}
	});

	// Event handler for deleting all student assessment results
	jQuery( '#di-delete-all-assessment-results' ).click(function() {
		if ( confirm( "I understand and confirm I wish to delete ALL existing Digging In assessment results." ) === true ) {
			deleteAssessmentResults();
		}
	});

});

/**
 * AJAX call to class-ubc-di-admin.php's ubc_di_options_updater_callback() function,
 * updating di's options.
 */
function updateOptions() {

	var data;

	data = {
		'action': 'options_updater',
		'ubc_di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'ubc_di_login_redirect': jQuery( '#di-login-redirect' ).val(),
		'ubc_di_google_maps_api_key': escapeHTML( jQuery( '#di-google-maps-api-key' ).val() ),
		'ubc_di_google_maps_center_lat': escapeHTML( jQuery( '#di-google-maps-center-lat' ).val() ),
		'ubc_di_google_maps_center_lon': escapeHTML( jQuery( '#di-google-maps-center-lon' ).val() ),
		'ubc_di_google_maps_zoom': escapeHTML( jQuery( '#di-google-maps-zoom' ).val() ),
		'ubc_di_google_maps_right_lon': escapeHTML( jQuery( '#di-google-maps-right-lon' ).val() ),
		'ubc_di_google_maps_layer1_label': escapeHTML( jQuery( '#di-google-maps-layer1-label' ).val() ),
		'ubc_di_google_maps_layer1_file': escapeHTML( jQuery( '#di-google-maps-layer1-file' ).val() ),
		'ubc_di_google_maps_layer2_label': escapeHTML( jQuery( '#di-google-maps-layer2-label' ).val() ),
		'ubc_di_google_maps_layer2_file': escapeHTML( jQuery( '#di-google-maps-layer2-file' ).val() ),
		'ubc_di_google_maps_layer3_label': escapeHTML( jQuery( '#di-google-maps-layer3-label' ).val() ),
		'ubc_di_google_maps_layer3_file': escapeHTML( jQuery( '#di-google-maps-layer3-file' ).val() ),
		'ubc_di_google_maps_layer4_label': escapeHTML( jQuery( '#di-google-maps-layer4-label' ).val() ),
		'ubc_di_google_maps_layer4_file': escapeHTML( jQuery( '#di-google-maps-layer4-file' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}

/**
 * AJAX call to class-ubc-di-admin.php's ubc_di_delete_all_media_callback() function,
 * deleting all media.
 */
function deleteMedia() {

	var data;

	data = {
		'action': 'ubc_di_delete_all_media'
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}

/**
 * AJAX call to class-ubc-di-admin.php's ubc_di_delete_all_groups_callback() function,
 * deleting all student groups.
 */
function deleteGroups() {

	var data;

	data = {
		'action': 'ubc_di_delete_all_groups'
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}

/**
 * AJAX call to class-ubc-di-admin.php's ubc_di_delete_all_assessment_results_callback() function,
 * deleting all student assessments.
 */
function deleteAssessmentResults() {

	var data;

	data = {
		'action': 'ubc_di_delete_all_results'
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}
