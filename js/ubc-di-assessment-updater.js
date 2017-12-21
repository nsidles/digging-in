// This file contains JavaScript code used to create and update soil site assessments.

// Current assessment object
var assessment = {};

jQuery( document ).ready(function( $ ) {

	// Function calls to prepare the page and enable the buttons appropriately.
	sortables();
	detectEditMode();
	enableButtons();

	jQuery( '.delete' ).click(function(e) {
		if ( confirm( "I understand and confirm I wish to delete this assessment." ) === false ) {
			e.preventDefault();
		}
	});

});

/**
 * Function to create the sortables and calendar/datepicker user
 * functionalities.
 */
function sortables() {
	jQuery( "#datepicker" ).datepicker();

	// Event handler for making the list of all assessment slides draggable.
	jQuery(function() {
		jQuery( '#di-as-existing-list' ).sortable();
	});

	// Event handler for making the buttons and multiple choice answers draggable.
	jQuery(function() {
		jQuery( '#di-a-link-add-list, #di-as-button-add-list, #di-as-multiple-choice-add-list, #di-as-recorded-multiple-choice-add-list' ).sortable();
	});
}

/**
 * Function to enable edit modes for a particular assessment. Evaluates
 * di-assessment-id to see if it has a value. If it does, editing mode is
 * enable for that assessment ID. An AJAX call retrieves the data from the
 * WordPress database.
 */
function detectEditMode() {
	if( typeof jQuery( '#di-assessment-id' ).val() !== 'undefined' ) {

		jQuery( '#di-add-new-toggle' ).click();
		jQuery( '#di-add-new-toggle' ).html( 'Edit Assessment' );

		// Data for the AJAX call.
		var data = {
			'action': 'get_assessment',
			'ubc_di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
			'ubc_di_assessment_id': jQuery( '#di-assessment-id' ).val()
		};

		// WordPress AJAX call.
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			assessment = response.data;
			for( var j in assessment ) {
				if( assessment.hasOwnProperty( j ) ) {
					var slideElement = createSlideElement( assessment[j], j );
					slideElement.setAttribute( 'id', j );
					addSlideElement( slideElement, document.getElementById( 'di-as-existing-list' ) );
				}
			}
			addSlideLinkOptions();
		});
	}
}

/**
 * Function to update available slides for linking in the Control Buttons
 * dropdown. This function is called whenever slides are created, edited, or
 * deleted, or when a new assessment is displayed.
 */
function addSlideLinkOptions() {
	jQuery( '#di-as-slide-link' ).html( '' );
	for( var i in assessment ) {
		if( assessment.hasOwnProperty( i ) ) {
			var slideLinkValue = i;
			var slideLinkTitle = assessment[i].title;
			jQuery( '#di-as-slide-link' ).append( '<option value="' + slideLinkValue + '">' + slideLinkTitle + ' (#' + slideLinkValue + ')</option>' );
		}
	}
}

/**
 * AJAX call to class-ubc-di-admin-assessment.php's ubc_di_assessment_adder_callback()
 * function, retrieving and displaying the assessment and adding it to the
 * current cookie value.
 */
function addAssessment() {
	var assessmentLocations = [];
	jQuery( '#di-assessment-locations' ).each(function() {
		assessmentLocations.push( escapeHTML( jQuery( this ).val() ) );
	});
	var assessmentContent = jQuery( '#di-as-existing-list' ).html();
	var assessmentTitle = jQuery( '#di-assessment-title' ).attr('value');
	var assessmentEndDate = jQuery( '#datepicker' ).attr('value');
	var data = {
		'action': 'add_assessment',
		'ubc_di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'ubc_di_assessment_title': assessmentTitle,
		'ubc_di_assessment_content': assessmentContent,
		'ubc_di_assessment_locations': assessmentLocations,
		'ubc_di_assessment_end_date': assessmentEndDate,
		'ubc_di_assessment_data': assessment
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		console.log( response );
		setTimeout(function (){
			eraseCookie( 'di-as-cookie' );
			location.reload();
		}, 500);
	});
}

/**
 * AJAX call to class-ubc-di-admin-assessment.php's ubc_di_assessment_editer_callback()
 * function, retrieving and displaying the assessment and adding it to the
 * current cookie value.
 */
function editAssessment() {
	var assessmentLocations = [];
	jQuery( '#di-assessment-locations' ).each(function() {
		assessmentLocations.push( escapeHTML( jQuery( this ).val() ) );
	});
	var assessmentContent = jQuery( '#di-as-existing-list' ).html();
	var assessmentTitle = jQuery( '#di-assessment-title' ).attr('value');
	var assessmentID = jQuery( '#di-assessment-id' ).val();
	var assessmentEndDate = jQuery( '#datepicker' ).attr('value');
	var data = {
		'action': 'edit_assessment',
		'ubc_di_nonce_field': escapeHTML( jQuery( '#di-nonce-field' ).val() ),
		'ubc_di_assessment_id': assessmentID,
		'ubc_di_assessment_title': assessmentTitle,
		'ubc_di_assessment_content': assessmentContent,
		'ubc_di_assessment_locations': assessmentLocations,
		'ubc_di_assessment_end_date': assessmentEndDate,
		'ubc_di_assessment_data': assessment
	};
	jQuery.post( ajax_object.ajax_url, data, function( response ) {
		eraseCookie( 'di-as-cookie' );
		window.location.href = jQuery( '#di-a-edit' ).attr( 'link' );
	});
}

/**
 * Function to add a slide link to a particular slide's list of control buttons.
 */
function addSlideLink() {
	var slideLink = createGeneralElement( 'li', '' );
	slideLink.setAttribute( 'value', jQuery( '#di-as-slide-link' ).val() );
	var slideText = createGeneralElement( 'div', 'di-li-text', jQuery( '#di-button-text' ).val() );
	slideLink.appendChild( slideText );
	var deleteText = createGeneralElement( 'div', 'di-delete', '(#' + jQuery( '#di-as-slide-link' ).val() + ') X' );
	slideLink.appendChild( deleteText );
	document.getElementById( 'di-as-button-add-list' ).appendChild( slideLink );

	document.getElementById( 'di-button-text' ).value = '';
}

/**
 * Function to add an answer to a particular slide's list of multiple choice
 * options.
 */
function addMultipleChoice() {
	var multipleChoice = createGeneralElement( 'li', '' );
	var multipleChoiceText = createGeneralElement( 'div', 'di-li-text', jQuery( '#di-multiple-choice-answer-text' ).val() );
	multipleChoice.appendChild( multipleChoiceText );
	if( jQuery( '#di-multiple-choice-answer-correct' ).attr('checked') ) {
		var multipleChoiceCorrect = createGeneralElement( 'div', 'di-li-correct',  ' (correct)' );
		multipleChoice.appendChild( multipleChoiceCorrect );
	}
	var deleteText = createGeneralElement( 'div', 'di-delete', 'X' );
	multipleChoice.appendChild( deleteText );
	document.getElementById( 'di-as-multiple-choice-add-list' ).appendChild( multipleChoice );

	jQuery( '#di-multiple-choice-answer-text' ).val( '' );
	jQuery( '#di-multiple-choice-answer-correct' ).attr('checked', false );
}

/**
 * Function to add an answer to a particular slide's list of recorded multiple
 * choice options.
 */
function addRecordedMultipleChoice() {
	var recordedMultipleChoice = createGeneralElement( 'li', '' );
	var recordedMultipleChoiceText = createGeneralElement( 'div', 'di-li-text', jQuery( '#di-recorded-multiple-choice-answer-text' ).val() );
	recordedMultipleChoice.appendChild( recordedMultipleChoiceText );
	var deleteText = createGeneralElement( 'div', 'di-delete', 'X' );
	recordedMultipleChoice.appendChild( deleteText );
	document.getElementById( 'di-as-recorded-multiple-choice-add-list' ).appendChild( recordedMultipleChoice );

	jQuery( '#di-recorded-multiple-choice-answer-text' ).val( '' );

}

/**
 * Function to parse a slide object (from an assessment object) into a formatted
 * HTML element.
 *
 * @param {Object} slideObject - the slide JavaScript object to be parsed
 * @return {Object} element - the formatted slide element to be displayed
 */
function createSlideElement( slideObject, slideID ) {

	var slide = createGeneralElement( 'div', 'di-as' );
	var answer;

	var headerEdit = createGeneralElement( 'div', [ 'di-as-header', 'di-as-header-edit' ], 'Edit' );
	slide.appendChild( headerEdit );
	var headerDelete = createGeneralElement( 'div', [ 'di-as-header', 'di-as-header-delete' ], 'Delete' );
	slide.appendChild( headerDelete );

	var body = createGeneralElement( 'div', 'di-as-body' );
	slide.appendChild( body );

	var bodyTitle = createGeneralElement( 'div', [ 'di-as-element', 'di-as-title' ], slideObject.title + ' (#' + slideID + ')' );
	body.appendChild( bodyTitle );

	var bodyMain = createGeneralElement( 'div', [ 'di-as-element', 'di-as-body-main' ] );
	bodyMain.innerHTML = slideObject.body;
	body.appendChild( bodyMain );

	if( slideObject.final === 'checked' ) {
		var bodyFinal = createGeneralElement( 'div', [ 'di-as-element', 'di-as-final' ], 'Final Slide' );
		body.appendChild( bodyFinal );
	}
	if( slideObject.textBoxQuestion !== '' ) {
		var bodyTextBoxQuestion = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], 'Text question: ' + slideObject.textBoxQuestion );
		body.appendChild( bodyTextBoxQuestion );
	}
	if( slideObject.imageBoxQuestion !== '' ) {
		var bodyImageBoxQuestion = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], 'Image question: ' + slideObject.imageBoxQuestion );
		body.appendChild( bodyImageBoxQuestion );
	}
	if( slideObject.multipleChoiceQuestion !== '' ) {
		var bodyMultipleChoice = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], 'Free multiple choice question: ' + slideObject.multipleChoiceQuestion );
		for( var i in slideObject.multipleChoiceAnswers ) {
			if( slideObject.multipleChoiceAnswers.hasOwnProperty( i ) ) {
				answer = createGeneralElement( 'div', 'di-as-answer', slideObject.multipleChoiceAnswers[i].text );
				if( slideObject.multipleChoiceAnswers[i].correct === true ) {
					var correct = createGeneralElement( 'div', 'di-li-correct', ' (correct)' );
					answer.appendChild( correct );
				}
				bodyMultipleChoice.appendChild( answer );
			}
		}
		body.appendChild( bodyMultipleChoice );
	}
	if( slideObject.recordedMultipleChoiceQuestion !== '' ) {
		var bodyRecordedMultipleChoice = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], 'Recorded multiple choice question: ' + slideObject.recordedMultipleChoiceQuestion );
		for( var j in slideObject.recordedMultipleChoiceAnswers ) {
			if( slideObject.recordedMultipleChoiceAnswers.hasOwnProperty( j ) ) {
				answer = createGeneralElement( 'div', 'di-as-answer', slideObject.recordedMultipleChoiceAnswers[j].text );
				bodyRecordedMultipleChoice.appendChild( answer );
			}
		}
		body.appendChild( bodyRecordedMultipleChoice );
	}

	if( typeof slideObject.controlButtons !== 'undefined' && slideObject.controlButtons !== '' ) {
		var bodyControlButtons = createGeneralElement( 'div', [ 'di-as-element' ], 'Control Buttons:' );
		for( var h in slideObject.controlButtons ) {
			if( slideObject.controlButtons.hasOwnProperty( h ) ) {
				var button = createGeneralElement( 'div', 'di-as-button', slideObject.controlButtons[h].text + ' (#' + slideObject.controlButtons[h].value + ')' );
				bodyControlButtons.appendChild( button );
			}
		}
		body.appendChild( bodyControlButtons );
	}
	return slide;

}

/**
 * Function to parse a slide to the larger list of edited slides.
 *
 * @param {Object} element - the slide element to be added
 * @param {Object} parentElement - the element to which the slide is added
 */
function addSlideElement( element, parentElement ) {
	parentElement.append( element );
}

/**
 * Function to retrieve a slide object from user input fields.
 *
 * @return {Object} returnObject - the slideObject retrieved from the fields
 */
function getSlideObject() {

	var returnObject = {};
	var title = jQuery( '#di-slide-title' ).val();
	var body = jQuery( '#ubc_di_slide_text_special_ifr' ).contents().find("#tinymce").html();
	var controlButtons = jQuery( '#di-as-button-add-list li' );
	var multipleChoiceQuestion = jQuery( '#di-multiple-choice-question-text' ).val();
	var multipleChoiceAnswers = jQuery( '#di-as-multiple-choice-add-list li' );
	var recordedMultipleChoiceQuestion = jQuery( '#di-recorded-multiple-choice-question-text' ).val();
	var recordedMultipleChoiceAnswers = jQuery( '#di-as-recorded-multiple-choice-add-list li' );
	var textBoxQuestion = jQuery( '#di-slide-text-input-question' ).val();
	var imageBoxQuestion = jQuery( '#di-slide-media-input-question' ).val();
	var final = jQuery( '#di-as-final' ).attr( 'checked' );
	returnObject.title = title;
	returnObject.body = body;

	returnObject.controlButtons = [];
	controlButtons.each( function() {
		var tempObject = {};
		var tempThis = this;
		tempObject.text = jQuery( tempThis ).children( '.di-li-text' ).html();
		tempObject.value = jQuery( tempThis ).attr( 'value' );
		returnObject.controlButtons.push( tempObject );
	});

	returnObject.multipleChoiceAnswers = [];
	returnObject.multipleChoiceQuestion = multipleChoiceQuestion;
	jQuery( multipleChoiceAnswers ).each( function() {
		var tempObject = {};
		var tempThis = this;
		tempObject.text = jQuery( tempThis ).children( '.di-li-text' ).html();
		if( typeof jQuery( tempThis ).children( '.di-li-correct' ).html() !== 'undefined' ) {
			tempObject.correct = true;
		} else {
			tempObject.correct = false;
		}
		returnObject.multipleChoiceAnswers.push( tempObject );
	});

	returnObject.recordedMultipleChoiceAnswers = [];
	returnObject.recordedMultipleChoiceQuestion = recordedMultipleChoiceQuestion;
	jQuery( recordedMultipleChoiceAnswers ).each( function() {
		var tempObject = {};
		var tempThis = this;
		tempObject.text = jQuery( tempThis ).children( '.di-li-text' ).html();
		returnObject.recordedMultipleChoiceAnswers.push( tempObject );
	});

	returnObject.textBoxQuestion = textBoxQuestion;
	returnObject.imageBoxQuestion = imageBoxQuestion;
	returnObject.final = final;

	return returnObject;
}

/**
 * Function to add a slide to an assessment object, then clear input fields.
 *
 * @return {Number} slideID - the ID of the created slide.
 */
function addSlide() {

	var slideObject = getSlideObject();
	var slideID = 1;
	while( typeof assessment[slideID] !== 'undefined' ) {
		slideID += 1;
	}
	var slideElement = createSlideElement( slideObject, slideID );
	slideElement.setAttribute( 'id', slideID );
	addSlideElement( slideElement, document.getElementById( 'di-as-existing-list' ) );

	assessment[slideID] = slideObject;
	createCookie( 'di-a-cookie', assessment, 7);
	clearEditSlide();
	addSlideLinkOptions();

	return slideID;
}

/**
 * Function to retrieve and display a slide to edit from the current assessment
 * object, then allow editing optiosn for that slide.
 *
 * @param {Number} slideID - the ID of the slide to edit
 */
function editSlide( slideID ) {

	var slideObject = getSlideObject();
	var slideElement = createSlideElement( slideObject, slideID );
	slideElement.setAttribute( 'id', slideID );
	jQuery( '#' + slideID ).replaceWith( slideElement );

	assessment[slideID] = getSlideObject();
	createCookie( 'di-a-cookie', assessment, 7);
	clearEditSlide();
	addSlideLinkOptions();
}

/**
 * Function to enable events for user actions, called whenever the set of
 * user actions changes (like when a slide is created)
 */
function enableButtons() {

	var tempID;

	jQuery( '.di-admin' ).off( 'click', '.di-as-header-edit' );
	jQuery( '.di-admin' ).on( 'click', '#di-as-add', function() {
		addSlide();
	});

	jQuery( '.di-admin' ).off( 'click', '#di-as-edit' );
	jQuery( '.di-admin' ).on( 'click', '#di-as-edit', function() {
		editSlide( tempID );
		jQuery( '#di-as-edit' ).hide();
		jQuery( '#di-as-add' ).show();
	});

	jQuery( '.di-admin' ).off( 'click', '.di-as-header-edit' );
	jQuery( '.di-admin' ).on( 'click', '.di-as-header-edit', function() {
		clearEditSlide();
		tempID = jQuery( this ).parent().attr('id');
		var slide = getSlideObjectToEdit( tempID );
		displaySlideToEdit( slide );
		jQuery( '#di-as-add' ).hide();
		jQuery( '#di-as-edit' ).show();
	});

	jQuery( '.di-admin' ).off( 'click', '.di-as-header-delete' );
	jQuery( '.di-admin' ).on( 'click', '.di-as-header-delete', function() {
		tempID = jQuery( this ).parent().attr( 'id' );
		deleteSlide( tempID );
	});

	jQuery( '.di-admin' ).on( 'click', '#di-a-add', function() {
		addAssessment();
	});

	jQuery( '.di-admin' ).on( 'click', '#di-a-edit', function() {
		editAssessment();
	});

	jQuery( '.di-admin' ).on( 'click', '.di-delete', function() {
		jQuery( this ).parent().remove();
	});

	jQuery( '.di-admin' ).on( 'click', '#di-as-link-add-button', function() {
		addSlideLink();
	});

	jQuery( '.di-admin' ).on( 'click', '#di-as-multiple-choice-add', function() {
		addMultipleChoice();
	});

	jQuery( '.di-admin' ).on( 'click', '#di-as-recorded-multiple-choice-add', function() {
		addRecordedMultipleChoice();
	});
}

/**
 * Function to retrieve a slide from the current assessment object
 *
 * @param {Number} - slideID - the ID of the slide to retrieve
 * @return {Object} assessment[slideID] - the returned slide
 */
function getSlideObjectToEdit( slideID ) {
	return assessment[slideID];
}

/**
 * Function to display a slideObject in the editing fields (as opposed to static
 * display as part of the larger assessment)
 *
 * @param {Object} slideObject - slide object to display in editing fields
 */
function displaySlideToEdit( slideObject ) {
	console.log( slideObject );
	jQuery( '#di-slide-title' ).val( slideObject.title );
	jQuery( '#ubc_di_slide_text_special_ifr' ).contents().find("#tinymce").html( slideObject.body );

	var deleteText;

	for( var i in slideObject.controlButtons ) {
		if( slideObject.controlButtons.hasOwnProperty( i ) ) {
			var slideLink = createGeneralElement( 'li', '' );
			slideLink.setAttribute( 'value', slideObject.controlButtons[i].value );
			var slideText = createGeneralElement( 'div', 'di-li-text', slideObject.controlButtons[i].text );
			slideLink.appendChild( slideText );
			deleteText = createGeneralElement( 'div', 'di-delete', 'X' );
			slideLink.appendChild( deleteText );
			document.getElementById( 'di-as-button-add-list' ).appendChild( slideLink );
		}
	}

	jQuery( '#di-multiple-choice-question-text' ).val( slideObject.multipleChoiceQuestion );
	for( var j in slideObject.multipleChoiceAnswers ) {
		if( slideObject.multipleChoiceAnswers.hasOwnProperty( j ) ) {
			var multipleChoice = createGeneralElement( 'li', '' );
			var multipleChoiceText = createGeneralElement( 'div', 'di-li-text', slideObject.multipleChoiceAnswers[j].text );
			multipleChoice.appendChild( multipleChoiceText );
			if( slideObject.multipleChoiceAnswers[j].correct === true ) {
				var multipleChoiceCorrect = createGeneralElement( 'div', 'di-li-correct',  ' (correct)' );
				multipleChoice.appendChild( multipleChoiceCorrect );
			}
			deleteText = createGeneralElement( 'div', 'di-delete', 'X' );
			multipleChoice.appendChild( deleteText );
			document.getElementById( 'di-as-multiple-choice-add-list' ).appendChild( multipleChoice );
		}
	}

	jQuery( '#di-recorded-multiple-choice-question-text' ).val( slideObject.recordedMultipleChoiceQuestion );
	for( var h in slideObject.recordedMultipleChoiceAnswers ) {
		if( slideObject.recordedMultipleChoiceAnswers.hasOwnProperty( h ) ) {
			var recordedMultipleChoice = createGeneralElement( 'li', '' );
			var recordedMultipleChoiceText = createGeneralElement( 'div', 'di-li-text', slideObject.recordedMultipleChoiceAnswers[h].text );
			recordedMultipleChoice.appendChild( recordedMultipleChoiceText );
			deleteText = createGeneralElement( 'div', 'di-delete', 'X' );
			recordedMultipleChoice.appendChild( deleteText );
			document.getElementById( 'di-as-recorded-multiple-choice-add-list' ).appendChild( recordedMultipleChoice );
		}
	}

	jQuery( '#di-slide-text-input-question' ).val( slideObject.textBoxQuestion );
	jQuery( '#di-slide-media-input-question' ).val( slideObject.imageBoxQuestion );

	if( typeof slideObject.final !== 'undefined' ) {
		jQuery( '#di-as-final' ).attr( 'checked', true );
	} else {
		jQuery( '#di-as-final' ).attr( 'checked', false );
	}
}

/**
 * Function to delete a slide from an assessment
 *
 * @param {Number} slideID - Slide to delete.
 */
function deleteSlide( slideID ) {
	jQuery( '#' + slideID ).remove();
	delete assessment[slideID];
	createCookie( 'di-a-cookie', assessment, 7);
	addSlideLinkOptions();
}

/**
 * Function to clear values from the editing fields, allowing a new slide to be
 * edited or created.
 */
function clearEditSlide() {
	jQuery( '#di-as-add' ).html("Add Slide");
	jQuery( '#ubc_di_slide_text_special_ifr' ).contents().find("#tinymce").html('');
	jQuery( '#di-slide-title' ).val( '' );
	jQuery( '#di-slide-text-input-question' ).val( '' );
	jQuery( '#di-button-text' ).val( '' );
	jQuery( '#di-as-button-add-list' ).html( '' );
	jQuery( '#di-slide-media-input-question' ).val( '' );
	jQuery( '#di-multiple-choice-question-text' ).val( '' );
	jQuery( '#di-recorded-multiple-choice-question-text' ).val( '' );
	jQuery( '#di-as-multiple-choice-add-list' ).html( '' );
	jQuery( '#di-as-recorded-multiple-choice-add-list' ).html( '' );
	jQuery( '#di-multiple-choice-answer-text' ).val( '' );
	jQuery( '#di-recorded-multiple-choice-answer-text' ).val( '' );
	jQuery( '#di-button-text' ).val( '' );
	jQuery( '#di-as-final' ).attr( 'checked', false );
}

/**
 * Function to create a cookie that allows users to recover their work if they
 * accidentally navigate off the page.
 *
 * @param {String} name - Name of cookie to create.
 * @param {Number} value - Value of cookie created (the assessment JSON object).
 * @param {Number} days - Number of days to hold the slide.
 */
function createCookie(name,value,days) {
    var expires = "";
		if( readCookie( name ) ) {
			eraseCookie( name );
		}
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

/**
 * Function to read an existing Digging In cookie.
 *
 * @param {String} name - Name of cookie to read.
 * @return {String} string - JSON representation of assessment.
 */
function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)===' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/**
 * Function to erase a Digging In cookie.
 *
 * @param {String} name - Name of cookie to delete.
 */
function eraseCookie(name) {
	document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}
