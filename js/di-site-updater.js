// This file contains JavaScript code used to create and update soil sites. It depends on a previous JavaScript reference to Google Maps code.

jQuery( document ).ready(function( $ ) {

	// Creating variables used in creating the Google Maps element used to place new sites.
	var requestedLatlng, mapOptions, map, marker, data, streetviewTester, streetview, streetviewOptions;

	// Instantiating a new Google Maps LatLng location object and setting map options.
	requestedLatlng = new google.maps.LatLng( 49.2683366, -123.2550359 );
	mapOptions = {
		zoom: 10,
		center: requestedLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	// Creating a new Google Maps object.
	map = new google.maps.Map( document.getElementById( 'di-map-canvas' ), mapOptions );
	marker = new google.maps.Marker( {
	});

	// Creating a new StreetView service and setting StreetView options.
	streetviewTester = new google.maps.StreetViewService();
	streetviewOptions = {
		position: event.latLng
	}

	// Creating a new StreetView object.
	streetview = new google.maps.StreetViewPanorama( document.getElementById( 'di-streetview-canvas' ), streetviewOptions );

	// Event handler to add a site when the di-site-submit button is clicked.
	jQuery( '#di-site-submit' ).click(function() {
		addSite();
	});

	// Event handler to edit a site when the di-site-submit_edit button is clicked. This function depends on GET parameters set up by WordPress and defined in class-di-wp-list-table-site.php.
	jQuery( '#di-site-submit_edit' ).click(function() {
		editSiteSubmit();
	});

	// Event handler to delete a site. This function depends on GET parameters set up by WordPress and defined in class-di-wp-list-table-site.php.
	jQuery( '.delete' ).click(function(e) {
		if ( confirm( "I understand and confirm I wish to delete this site." ) == false ) {
			e.preventDefault();
		}
	});

	// Event handler for the user clicking on Google Maps.
	google.maps.event.addListener( map, 'click', function( event ) {
		jQuery( '#di-site-longitude' ).val( event.latLng.lng() );
		jQuery( '#di-site-latitude' ).val( event.latLng.lat() );
		marker.setPosition( event.latLng );
		marker.setMap( map );
		streetviewTester.getPanoramaByLocation( event.latLng, 50, function( data, status ) {
			if ( status === google.maps.StreetViewStatus.OK ) {
				streetview.setPosition( event.latLng );
			}
		});
	});

	// Event handler for the StreetView to change when the map location changes.
	streetview.addListener( 'position_changed', function( ) {
		jQuery( '#di-site-longitude' ).val( streetview.getPosition().lng() );
		jQuery( '#di-site-latitude' ).val( streetview.getPosition().lat() );
		map.setCenter( streetview.getPosition() );
		marker.setPosition( streetview.getPosition() );
		marker.setMap( map );
	});

	// Event handler for the LatLng to change when new values are put into the di-site-latlng-check element.
	jQuery( '#di-site-latlng-check' ).click(function() {
		requestedLatlng = new google.maps.LatLng( escapeHTML( jQuery( '#di-site-latitude' ).val() ), escapeHTML( jQuery( '#di-site-longitude' ).val() ) );
		map.setCenter( requestedLatlng );
		marker.setPosition( requestedLatlng );
		marker.setMap( map );
	});

});

/**
 * AJAX call to class-di-admin-site.php's di_site_editer_callback(), inserting
 * a di_site post and updating the window
 */
function addSite() {
	var data = {
		'action': 'site_adder',
		'di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'di_site_title': escapeHTML( jQuery( '#di-site-title' ).val() ),
		'di_site_description': escapeHTML( jQuery( '#di-site-description' ).val() ),
		'di_site_latitude': escapeHTML( jQuery( '#di-site-latitude' ).val() ),
		'di_site_longitude': escapeHTML( jQuery( '#di-site-longitude' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		location.reload();
	});
}

/**
 * AJAX call to class-di-admin-site.php's di_site_editer_callback(), inserting
 * a di_site post and updating the window
 */
function editSiteSubmit() {
	var data = {
		'action': 'site_editer',
		'di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'di_site_id': escapeHTML( jQuery( '#di-site-id_edit' ).attr('value') ),
		'di_site_title': escapeHTML( jQuery( '#di-site-title_edit' ).val() ),
		'di_site_description': escapeHTML( jQuery( '#di-site-description_edit' ).val() ),
		'di_site_latitude': escapeHTML( jQuery( '#di-site-latitude_edit' ).val() ),
		'di_site_longitude': escapeHTML( jQuery( '#di-site-longitude_edit' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		location.reload();
	});
}
