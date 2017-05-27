// This file contains Javascript code used to set site-wide options (e.g., the Google Maps API key necessary to use Google Maps functions).

jQuery( document ).ready(function( $ ) {

	// Event handler for submitting options.
	jQuery( '#di-options-submit' ).click(function() {
		updateOptions();
	});

});

/**
 * AJAX call to class-di-admin.php's di_options_updater_callback() function,
 * updating di's options.
 */
function updateOptions() {

	var data;

	data = {
		'action': 'options_updater',
		'di_google_maps_api_key': escapeHTML( jQuery( '#di-google-maps-api-key' ).val() ),
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		alert( response );
	});
}
