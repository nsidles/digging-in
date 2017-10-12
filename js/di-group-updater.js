// This file contains JavaScript code used to create and update students and groups - associated groups of users who can have their assessments evaluated together. This function depends on jQuery UI being enabled.

jQuery( document ).ready(function( $ ) {

	// Event handler for submitting new groups.
	jQuery( '#di-group-submit' ).click(function() {
		updateGroups();
	});

	// Event handler for making the list of all available students draggable.
	jQuery(function() {
		jQuery( '#di-group-people-selected-list, #di-group-people-complete-list' ).sortable( {
			connectWith: '.di-group-order-people'
		});
	});

	// Event handler for making selected students draggable.
	jQuery(function() {
		jQuery( '#di-group-people-selected-list_edit, #di-group-people-complete-list_edit' ).sortable( {
			connectWith: '.di-group-order-people_edit'
		});
	});

	// Event handler for submitting an edited group.
	jQuery( '#di-group-submit_edit' ).click(function() {
		editGroupSubmit();
	});

	// Event handler for deleting a group.
	jQuery( '.delete' ).click(function(e) {
		if ( confirm( "I understand and confirm I wish to delete this group." ) == false ) {
			e.preventDefault();
		}
	});

});

/**
 * Function to delete a group. It depends on GET parameters being set by
 * class-di-wp-list-table-group, then reloading the table.
 */
function editGroupSubmit() {
	var groupPeople = [];
	jQuery( '#di-group-people-selected-list_edit li input' ).each(function() {
		groupPeople.push( escapeHTML( jQuery( this ).val() ) );
	});
	var data = {
		'action': 'group_editer',
		'di_group_id': escapeHTML( jQuery( '#di-group-id_edit' ).attr('value') ),
		'di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'di_group_title': escapeHTML( jQuery( '#di-group-title_edit' ).val() ),
		'di_group_ta': escapeHTML( jQuery( '#di-group-ta_edit' ).val() ),
		'di_group_people': groupPeople
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		location.reload();
	});
}

/**
 * AJAX call to class-di-admin-group.php's di_group_updater_callback() function,
 * inserting an di_group post and updating the window
 */
function updateGroups() {
	var groupPeople = [];
	jQuery( '#di-group-people-selected-list li input' ).each(function() {
		groupPeople.push( escapeHTML( jQuery( this ).val() ) );
	});
	var data = {
		'action': 'group_updater',
		'di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'di_group_title': escapeHTML( jQuery( '#di-group-title' ).val() ),
		'di_group_description': escapeHTML( jQuery( '#di-group-description' ).val() ),
		'di_group_people': groupPeople,
		'di_group_ta': escapeHTML( jQuery( '#di-group-ta' ).val() )
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		location.reload();
	});
}
