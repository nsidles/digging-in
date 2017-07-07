// This file contains JavaScript code used to create and update student assessment results.

jQuery( document ).ready(function( $ ) {

	// Event handler for an AJAX call to retrieve assessment results
	jQuery( '.asr-result-evaluate' ).on( 'click', function() {
		var assessment_result_id = jQuery( this ).attr( 'assessment_result' )
		var data = {
			'action': 'get_assessment_result',
			'di_assessment_result_id' : assessment_result_id
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			jQuery( '#di-assessment-result' ).html( '' );
			jQuery( '#di-assessment-result' ).append( '<h2>Assessment Results</h2><hr />' );
			displayAssessmentEvaluate( JSON.parse( response.content ), assessment_result_id );
		});
	});

	jQuery( '.asr-result-view' ).on( 'click', function() {
		var assessment_result_id = jQuery( this ).attr( 'assessment_result' )
		var data = {
			'action': 'get_assessment_result',
			'di_assessment_result_id' : assessment_result_id
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			jQuery( '#di-assessment-result' ).html( '' );
			jQuery( '#di-assessment-result' ).append( '<h2>Assessment Results</h2><hr />' );
			displayAssessmentResult( JSON.parse( response.content ), assessment_result_id );
		});
	});


});

/**
 * Function to display soil site assessment results retrieved from the WordPress
 * database.
 *
 * @param {Object} assessmentResult - Assessment result to display
 */
function displayAssessmentEvaluate( assessmentResult, assessmentResultID ) {

	for( i in assessmentResult ) {
		var result = document.getElementById( 'di-assessment-result' );
		var slide = createGeneralElement( 'div', 'di-assessment-result-slide' );
		result.appendChild( slide );

		var slideTitle = createGeneralElement( 'h3', '', assessmentResult[i].title );
		result.appendChild( slideTitle );

		var slideContent = createGeneralElement( 'div', 'di-assessment-result-slide-content' );
		result.appendChild( slideContent );

		if( assessmentResult[i].text.question == '' && assessmentResult[i].image.question == '' && assessmentResult[i].recordedMultipleChoice.question == '' ) {
			var noQuestions = createGeneralElement( 'p', '', 'No questions on this slide.' );
			slideContent.appendChild( noQuestions );
		}

		if( assessmentResult[i].recordedMultipleChoice.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].recordedMultipleChoice.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].recordedMultipleChoice.answer );
			container.appendChild( answer );

			var evaluation = createGeneralElement( 'div', 'di-asr-element' );

			if( assessmentResult[i].recordedMultipleChoice.correct == true ) {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-rmc-' + i + '" checked /> Correct?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-rmc-' + i + '" /> Correct?';
			}
			evaluation.innerHTML += '<br />';
			if( assessmentResult[i].recordedMultipleChoice.correct == false ) {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-rmc-' + i + '" checked/>Incorrect?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-rmc-' + i + '"/> Incorrect?';
			}
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes', assessmentResult[i].recordedMultipleChoice.notes );
			notesBox.setAttribute( 'id', 'evaluation-notes-rmc-' + i );
			notes.appendChild( notesBox );
			container.appendChild( notes );

			slideContent.appendChild( container );

		}

		if( assessmentResult[i].text.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].text.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].text.answer );
			container.appendChild( answer );

			var evaluation = createGeneralElement( 'div', 'di-asr-element' );
			if( assessmentResult[i].text.correct == true ) {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-text-' + i + '" checked /> Correct?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-text-' + i + '" /> Correct?';
			}
			evaluation.innerHTML += '<br />';
			if( assessmentResult[i].text.correct == false ) {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-text-' + i + '" checked />Incorrect?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-text-' + i + '"/> Incorrect?';
			}
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes', assessmentResult[i].text.notes );
			notesBox.setAttribute( 'id', 'evaluation-notes-text-' + i );
			notes.appendChild( notesBox );
			container.appendChild( notes );

			slideContent.appendChild( container );
		}

		if( assessmentResult[i].image.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].image.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element' );
			var answerImage = createGeneralElement( 'img' );
			answerImage.setAttribute( 'width', '100%' );
			answerImage.setAttribute( 'src', assessmentResult[i].image.answer );
			answer.appendChild( answerImage );
			container.appendChild( answer );

			var evaluation = createGeneralElement( 'div', 'di-asr-element' );
			if( assessmentResult[i].image.correct == true ) {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-image-' + i + '" checked /> Correct?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="correct" name="evaluation-image-' + i + '" /> Correct?';
			}
			evaluation.innerHTML += '<br />';
			if( assessmentResult[i].image.correct == false ) {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-image-' + i + '" checked />Incorrect?';
			} else {
				evaluation.innerHTML += '<input type="radio" value="incorrect" name="evaluation-image-' + i + '"/> Incorrect?';
			}
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes', assessmentResult[i].image.notes );
			notes.appendChild( notesBox );
			notesBox.setAttribute( 'id', 'evaluation-notes-image-' + i );
			container.appendChild( notes );

			slideContent.appendChild( container );
		}

	}

	var submitButton = createGeneralElement( 'div', 'button', 'Add Assessment Result Evaluation' );
	submitButton.classList.add = 'button';
	submitButton.classList.add = 'button-primary';
	submitButton.setAttribute( 'id', 'asr-result-submitter' )
	result.appendChild( submitButton );

	jQuery( '#asr-result-submitter' ).on( 'click', function() {

		for( i in assessmentResult ) {
			if( assessmentResult[i].recordedMultipleChoice.question != '' ) {
				if( jQuery( 'input[name=evaluation-rmc-' + i + ']:checked' ).val() == 'correct' ) {
					assessmentResult[i].recordedMultipleChoice.correct = true;
				} else if( jQuery( 'input[name=evaluation-rmc-' + i + ']:checked' ).val() == 'incorrect' ) {
					assessmentResult[i].recordedMultipleChoice.correct = false;
				}
				assessmentResult[i].recordedMultipleChoice.notes = jQuery( '#evaluation-notes-rmc-' + i ).val();
			}

			if( assessmentResult[i].text.question != '' ) {
				if( jQuery( 'input[name=evaluation-text-' + i + ']:checked' ).val() == 'correct' ) {
					assessmentResult[i].text.correct = true;
				} else if( jQuery( 'input[name=evaluation-text-' + i + ']:checked' ).val() == 'incorrect' ) {
					assessmentResult[i].text.correct = false;
				}
				assessmentResult[i].text.notes = jQuery( '#evaluation-notes-text-' + i ).val();
			}

			if( assessmentResult[i].image.question != '' ) {
				if( jQuery( 'input[name=evaluation-image-' + i + ']:checked' ).val() == 'correct' ) {
					assessmentResult[i].image.correct = true;
				} else if( jQuery( 'input[name=evaluation-image-' + i + ']:checked' ).val() == 'incorrect' ) {
					assessmentResult[i].image.correct = false;
				}
				assessmentResult[i].image.notes = jQuery( '#evaluation-notes-image-' + i ).val();
			}

		}

		var data = {
			'action': 'digging_in_add_assessment_result_evaluation',
			'di_nonce_field': jQuery( '#di-nonce-field' ).val(),
			'di_assessment_result_id': assessmentResultID,
			'di_assessment_result_data': JSON.stringify( assessmentResult )
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			console.log( assessmentResult );
			// alert( response );
			location.reload();

		});

	});
}

function displayAssessmentResult( assessmentResult, assessmentResultID ) {
	for( i in assessmentResult ) {
		var result = document.getElementById( 'di-assessment-result' );
		var slide = createGeneralElement( 'div', 'di-assessment-result-slide' );
		result.appendChild( slide );

		var slideTitle = createGeneralElement( 'h3', '', assessmentResult[i].title );
		result.appendChild( slideTitle );

		var slideContent = createGeneralElement( 'div', 'di-assessment-result-slide-content' );
		result.appendChild( slideContent );

		if( assessmentResult[i].text.question == '' && assessmentResult[i].image.question == '' && assessmentResult[i].recordedMultipleChoice.question == '' ) {
			var noQuestions = createGeneralElement( 'p', '', 'No questions on this slide.' );
			slideContent.appendChild( noQuestions );
		}

		if( assessmentResult[i].recordedMultipleChoice.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].recordedMultipleChoice.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].recordedMultipleChoice.answer );
			container.appendChild( answer );

			slideContent.appendChild( container );
		}

		if( assessmentResult[i].text.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].text.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].text.answer );
			container.appendChild( answer );

			slideContent.appendChild( container );
		}

		if( assessmentResult[i].image.question != '' ) {

			var container = createGeneralElement( 'div', 'di-asr-container' );

			var question = createGeneralElement( 'div', 'di-asr-element', assessmentResult[i].image.question );
			container.appendChild( question );

			var answer = createGeneralElement( 'div', 'di-asr-element' );
			var answerImage = createGeneralElement( 'img' );
			answerImage.setAttribute( 'width', '100%' );
			answerImage.setAttribute( 'src', assessmentResult[i].image.answer );
			answer.appendChild( answerImage );
			container.appendChild( answer );

			slideContent.appendChild( container );
		}

	}

}
