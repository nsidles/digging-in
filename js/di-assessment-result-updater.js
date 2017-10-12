// This file contains JavaScript code used to create and update student assessment results.

jQuery( document ).ready(function( $ ) {

	// Event handler for an AJAX call to retrieve assessment results as an evaulator
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

	// Event handler for an AJAX call to retrieve assessment results as a viewer
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

	// Event handler to filter assessment results by group, site, and assessment
	jQuery( '#di-filter' ).on( 'click', function() {
		var tempURL = jQuery( '#page-link' ).html();
		if( jQuery( '#diar-group' ).val() != '' ) {
			tempURL += '&di_group=' + jQuery( '#diar-group' ).val();
		}
		if( jQuery( '#diar-site' ).val() != '' ) {
			tempURL += '&di_site=' + jQuery( '#diar-site' ).val();
		}
		if( jQuery( '#diar-assessment' ).val() != '' ) {
			tempURL += '&di_assessment=' + jQuery( '#diar-assessment' ).val();
		}
		window.location = tempURL;
	});

	// Event handler to export assessment results as a CSV file
	jQuery( '#diar-export' ).on( 'click', function() {
		var data = {
			'action': 'get_assessments_export',
			'di_nonce_field': jQuery( '#di-nonce-field' ).val(),
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {
			exportToCSV( 'export.csv', response );
		});
	});

	// Event handler to delete a specific assessment result
	jQuery( '.delete' ).click(function(e) {
		if ( confirm( "I understand and confirm I wish to delete this assessment result." ) == false ) {
			e.preventDefault();
		}
	});

});

/**
 * Function to export all assessment results to a CSV file
 *
 * @param {String} - filename - the name of the file to which to export
 * @param {Array} - rows - the file data for exporting
 */
function exportToCSV(filename, rows) {

  var processRow = function (row) {
    var finalVal = '';
		Object.getOwnPropertyNames(row).forEach(
		  function (val, idx, array) {
		    console.log(val + ' -> ' + row[val]);
				var innerValue = row[val] === null ? '' : row[val].toString();
	      if (row[val] instanceof Date) {
	        innerValue = row[val].toLocaleString();
	      };
	      var result = innerValue.replace(/"/g, '""');
	      if (result.search(/("|,|\n)/g) >= 0)
	        result = '"' + result + '"';
	      if (idx > 0)
	        finalVal += ',';
	      finalVal += result;
			}
		);
    return finalVal + '\n';
  };

  var csvFile = '';
	Object.getOwnPropertyNames(rows[0]).forEach(
		function (val, idx, array) {
			csvFile += val;
			csvFile += ',';
		}
	)
	csvFile += '\n';
  for (var i = 0; i < rows.length; i++) {
    csvFile += processRow(rows[i]);
  }

  var blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
  if (navigator.msSaveBlob) { // IE 10+
    navigator.msSaveBlob(blob, filename);
  } else {
    var link = document.createElement("a");
    if (link.download !== undefined) { // feature detection
                // Browsers that support HTML5 download attribute
      var url = URL.createObjectURL(blob);
      console.log( document );
      link.setAttribute("href", url);
      link.setAttribute("download", filename);
      link.style.visibility = 'hidden';
      var main = document.getElementsByTagName( 'body' )[0];
      main.appendChild(link);
      link.click();
      main.removeChild(link);
    }
  }
}

/**
 * Function to display soil site assessment results retrieved from the WordPress
 * database as an evaluator.
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

/**
 * Function to display soil site assessment results retrieved from the WordPress
 * database as a viewer.
 *
 * @param {Object} assessmentResult - Assessment result to display
 */
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
