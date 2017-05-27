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
			displayAssessmentEvaluate( JSON.parse( response.content ) );
		});
	});

});

/**
 * Function to display soil site assessment results retrieved from the WordPress
 * database.
 *
 * @param {Object} assessmentResult - Assessment result to display
 */
function displayAssessmentEvaluate( assessmentResult ) {
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
			evaluation.innerHTML = '<input type="radio" value="correct" name="evaluation-' + i + '"/>Correct?</label><br /><input type="radio" value="incorrect" name="evaluation-' + i + '"/>Incorrect?';
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes' );
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
			evaluation.innerHTML = '<input type="radio" value="correct" name="evaluation-' + i + '"/>Correct?</label><br /><input type="radio" value="incorrect" name="evaluation-' + i + '"/>Incorrect?';
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes' );
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
			evaluation.innerHTML = '<input type="radio" value="correct" name="evaluation-' + i + '"/>Correct?</label><br /><input type="radio" value="incorrect" name="evaluation-' + i + '"/>Incorrect?';
			container.appendChild( evaluation );

			var notes = createGeneralElement( 'div', 'di-asr-element' );
			var notesBox = createGeneralElement( 'textarea', 'di-asr-notes' );
			notes.appendChild( notesBox );
			container.appendChild( notes );

			slideContent.appendChild( container );
		}

	}

	var submitButton = createGeneralElement( 'div', 'button', 'Add Assessment Result Evaluation' );
	submitButton.classList.add = 'button';
	submitButton.classList.add = 'button-primary';
	result.appendChild( submitButton );
}

// TODO: Complete editing, searching, and privileges
