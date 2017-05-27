// This file contains JavaScript code used to create and update soil site media. It depends on a previous JavaScript reference to Google Maps code to allow users to select an associated site from a map.

jQuery( document ).ready(function( $ ) {

	// Creating variables used in creating the Google Maps element used to retrieve soil sites
	var requestedLatlng, mapOptions, map, marker, data;

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

	var diMap = new DIMap( map );
	// Retrieving the points for populating the map
	var sites = diMap.retrievePoints();

});

/**
 * Function to create a map that contains soil site points and allows the
 * selected site in the di-media-site option to be updated when a point is
 * clicked.
 *
 * @param {Object} map - Map element for soil site markers to be placed.
 */
function DIMap( map ) {

	var data, sites, tempLatLng, tempMarker, tempData, mapInstance, objectInstance, mapInfowindow;

	mapInfowindow = new google.maps.InfoWindow( { pixelOffset: new google.maps.Size( 0, -35 ) });
	objectInstance = this;
	mapInstance = map;

	/**
	 * AJAX call to retrieve soil sites and place them in the selected Google Map.
	 */
	this.retrievePoints = function() {
		data = {
			'action': 'digging_in_get_sites'
		}
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			tempData = new google.maps.Data();
			tempData.addGeoJson( response.geojson );
			tempData.setMap( mapInstance );
			tempData.addListener( 'mouseover', function( event ) {
				mapInfowindow.setContent( '<div style="text-align: center;">' + event.feature.getProperty( 'title' ) + ' (#' + event.feature.getProperty( 'id' ) + ')</div>' );
				var anchor = new google.maps.MVCObject();
				anchor.set( 'position', event.latLng );
				mapInfowindow.open( mapInstance, anchor );
			});
			tempData.addListener( 'click', function( event ) {
				jQuery( '#di-media-site option[value=' + event.feature.getProperty( 'id' ) + ']').attr('selected', true);
			});
		});
	}
}
