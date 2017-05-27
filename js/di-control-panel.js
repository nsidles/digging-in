// This file contains JavaScript code that is used by multiple pages.

// Variable to track the 'di-add-toggle-arrow' element's arrow.
var diAddNewMediaStatus = 0;

// Toggle for showing or hiding administrative content adding elements.
jQuery( '#di-add-new-toggle' ).click(function() {
	jQuery( '#di-add-new-form' ).slideToggle( 0 );
	if( diAddNewMediaStatus === 0 ) {
		jQuery( '#di-add-toggle-arrow' ).html( "&#9658" );
		diAddNewMediaStatus = 1;
	} else {
		jQuery( '#di-add-toggle-arrow' ).html("&#9660");
		diAddNewMediaStatus = 0;
	}
});

// Functional call to trigger the 'di-add-new-toggle' toggle event.
jQuery( '#di-add-new-toggle' ).click();

// Set of unsafe user-supplied input fields to be replaced
var entityMap = {
	"&": "&amp;",
	"<": "&lt;",
	">": "&gt;",
	'"': '&quot;',
	"'": '&#39;',
	"/": '&#x2F;',
	"\n": '<br />'
};

/**
 * Function to sanitize user-supplied input fields not handled
 * by WordPress's comments system.
 *
 * @param {String} string - Input string.
 * @return {String} string - Sanitized output string.
 */
function escapeHTML( string ) {
	return String( string ).replace( /[&<>"'\/]|[\n]/g, function ( characterToBeReplaced ) {
		return entityMap[ characterToBeReplaced ];
	});
}

/**
 * Function to create an HTML element with associated class(es) and text node.
 * This function is shared across data types and is duplicated in
 * di-map-view.js.
 *
 * @param {String} tagName - HTML tag name for the created element (e.g., "div")
 * @param {Object} classes - class (string) or classes (object) for this element
 * @param {Object} classes - text contained inside element
 * @return {Object} element - Element
 */
function createGeneralElement( tagName, classes, content = '' ) {
	var element = document.createElement( tagName );
	if( typeof classes === 'string' && classes.length > 0 ) {
		element.classList.add( classes );
	} else if( typeof classes === 'object' ) {
		for( i in classes ) {
			console.log( classes[i]);
			element.classList.add( classes[i] );
		}
	}
	var text = document.createTextNode( content );
	element.appendChild( text );
	return element;
}

// Hiding elements on page load
jQuery( '.di-add-media-video' ).hide();
jQuery( '.di-add-media-audio' ).hide();
jQuery( '.di-add-media-imagewp' ).hide();
jQuery( '.di-add-media-external' ).hide();
jQuery( '.di-add-media-wiki' ).hide();
jQuery( '#di-media-wiki-warning' ).hide();

// Event handler for displaying different media type dropdowns, hiding or showing elements as needed.
jQuery( '#di-media-type' ).change(function() {
		switch( this.value ) {
			case 'image':
				jQuery( '.di-add-media-image' ).show();
				jQuery( '.di-add-media-video' ).hide();
				jQuery( '.di-add-media-audio' ).hide();
				jQuery( '.di-add-media-imagewp' ).hide();
				jQuery( '.di-add-media-external' ).hide();
				jQuery( '.di-add-media-wiki' ).hide();
				jQuery( '#di-media-wiki-warning' ).hide();
				break;
			case 'audio':
				jQuery( '.di-add-media-image' ).hide();
				jQuery( '.di-add-media-video' ).hide();
				jQuery( '.di-add-media-audio' ).show();
				jQuery( '.di-add-media-imagewp' ).hide();
				jQuery( '.di-add-media-external' ).hide();
				jQuery( '.di-add-media-wiki' ).hide();
				jQuery( '#di-media-wiki-warning' ).hide();
				break;
			case 'video':
				jQuery( '.di-add-media-image' ).hide();
				jQuery( '.di-add-media-video' ).show();
				jQuery( '.di-add-media-audio' ).hide();
				jQuery( '.di-add-media-imagewp' ).hide();
				jQuery( '.di-add-media-external' ).hide();
				jQuery( '.di-add-media-wiki' ).hide();
				jQuery( '#di-media-wiki-warning' ).hide();
				break;
			case 'imagewp':
				jQuery( '.di-add-media-image' ).hide();
				jQuery( '.di-add-media-video' ).hide();
				jQuery( '.di-add-media-audio' ).hide();
				jQuery( '.di-add-media-imagewp' ).show();
				jQuery( '.di-add-media-external' ).hide();
				jQuery( '.di-add-media-wiki' ).hide();
				jQuery( '#di-media-wiki-warning' ).hide();
				break;
			case 'external':
				jQuery( '.di-add-media-image' ).hide();
				jQuery( '.di-add-media-video' ).hide();
				jQuery( '.di-add-media-audio' ).hide();
				jQuery( '.di-add-media-imagewp' ).hide();
				jQuery( '.di-add-media-external' ).show();
				jQuery( '.di-add-media-wiki' ).hide();
				jQuery( '#di-media-wiki-warning' ).hide();
				break;
			case 'wiki':
				jQuery( '.di-add-media-image' ).hide();
				jQuery( '.di-add-media-video' ).hide();
				jQuery( '.di-add-media-audio' ).hide();
				jQuery( '.di-add-media-imagewp' ).hide();
				jQuery( '.di-add-media-external' ).hide();
				jQuery( '.di-add-media-wiki' ).show();
				jQuery( '#di-media-wiki-warning' ).show();
				break;
		}
	});
