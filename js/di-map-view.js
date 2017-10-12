// This file contains JavaScript code used to display the Digging In plugin on the web frontend.

// Current assessment object and student answers object.
var studentAnswers = new Object();
var studentAnswersID = '';
var assessment = new Object();

jQuery( document ).ready(function( $ ) {
	// Creating variables used in creating the Google Maps element used to retrieve soil sites
	var requestedLatlng, mapOptions, map, marker, data;

	// Instantiating a new Google Maps LatLng location object and setting map options.
	requestedLatlng = new google.maps.LatLng( 49.2683366, -123.2050359 );
	mapOptions = {
		zoom: 11,
		center: requestedLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	// Creating a new Google Maps object.
	map = new google.maps.Map( document.getElementById( 'di-map-canvas' ), mapOptions );
	var diMap = new DIMap( map );

	// Retrieving and displaying a single point's data if di-site-id is enabled (for mobile sites), otherwise displaying all points.
	if( jQuery( '#di-site-id' ).html() != '' ) {
		diMap.retrievePoint( jQuery( '#di-site-id' ).html() );
		var site = document.getElementById( 'di-site' );
		site.classList.add( 'mobile-site' );
	} else {
		var sites = diMap.retrievePoints();
	}
	diMap.enableButtons();

});

/**
 * Function to instantiate a Digging In Map and soil site dispaly.
 *
 * @param {Object} map - Google Maps on which to display the soil sites.
 */
function DIMap( map ) {

	// Creating variables used in creating the soil sites.
	var data, sites, tempLatLng, tempMarker, tempData, mapInstance, objectInstance, mapInfowindow, assessmentID;

	// Repositioning and resizing info windows on the map.
	mapInfowindow = new google.maps.InfoWindow( { pixelOffset: new google.maps.Size( 0, -35 ) });
	objectInstance = this;
	mapInstance = map;

	/**
	 * Function to retrieve all valid soil sites from WordPress's database with an
	 * AJAX call to class-di-view.php's di_map_callback() function.
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
				objectInstance.retrievePoint( event.feature.getProperty( 'id' ) );
			});
		});
	}

	/**
	 * Function to retrieve and display a single soil site from WordPress's
	 * database with an AJAX call to class-di-view.php's di_map_site_callback()
	 * function.
	 *
	 * @param {Number} siteID - ID of the site to retrieve.
	 */
	this.retrievePoint = function( siteID ) {

		jQuery( '#di-site-id' ).html( siteID );

		data = {
			'action': 'digging_in_get_site',
			'di_site_id': siteID
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {

			jQuery( '#di-site .main-left' ).html( '' );
			jQuery( '#di-site .main-right' ).html( '' );

			jQuery( '#di-site .main-left' ).append( '<h2>' + response.title );
			jQuery( '#di-site .main-left' ).append( '<p2>' + response.content + '</p><hr />' );
			for( di_medium in response['di_media'] ) {
				jQuery( '#di-site .main-right' ).append( '<h2>' + response['di_media'][di_medium]['title'] + '</h2><p>' + response['di_media'][di_medium]['media'] + '</p>' + response['di_media'][di_medium]['description'] + '<p><hr /></p>' );
			}
			var tempText = '<ul>';
			for( di_assessment in response['di_assessments'] ) {
				if (typeof response['di_assessments'][di_assessment]['title'] !== 'undefined') {
					tempText = tempText + '<li id="di-assessment-starter-' + response['di_assessments'][di_assessment]['id'] + '"><a href="#">' + response['di_assessments'][di_assessment]['title'] + '</a></li>';
				}
			}
			tempText = tempText + '</ul><hr />';
			jQuery( '#di-site .main-left' ).append( tempText );
			jQuery( '[ id^=di-assessment-starter- ]' ).click(function() {
				assessmentID = jQuery( this ).attr( 'id' ).replace( 'di-assessment-starter-', '' );
				objectInstance.retrieveAssessment( assessmentID );
			});
		});
	}

	/**
	 * Function to retrieve and display a single assessment from WordPress's
	 * database with an AJAX call to class-di-view.php's
	 * di_map_assessment_callback() function.
	 *
	 * @param {Number} siteID - ID of the assessment to retrieve.
	 */
	this.retrieveAssessment = function( assessmentID ) {
		jQuery( '#di-assessment-id' ).html( assessmentID );
		jQuery( '#di-assessment-body' ).empty();
		jQuery('#di-assessment-next').off();
		jQuery('#di-assessment-prev').off();
		data = {
			'action': 'digging_in_get_assessment',
			'di_assessment_id': assessmentID,
			'di_user_id': jQuery( '#di-user-id' ).html()
		};
		jQuery.post( ajax_object.ajax_url, data, function( response ) {

				assessment = response.data;

				if( response.group != '' ) {
					var header = createGeneralElement( 'div', '', response.title );
					jQuery( '#di-assessment-header' ).html( '' );
					jQuery( '#di-assessment-header' ).append( header );

					if( response.answers != '' || typeof response.answers !== 'undefined' ) {
						studentAnswers[assessmentID] = JSON.parse( response.answers );
						studentAnswersID = response.answers_id;
					} else {
						studentAnswers = new Object();
						studentAnswersID = '';
					}
					
					if( typeof studentAnswers[assessmentID] === 'undefined' || studentAnswers[assessmentID] === null ) {
						alert("hello");
						studentAnswers[assessmentID] = new Object();
						for( i in assessment ) {
							if( typeof studentAnswers[assessmentID][i] === 'undefined' ) {
								studentAnswers[assessmentID][i] = new Object();
								studentAnswers[assessmentID][i].recordedMultipleChoice = new Object();
								studentAnswers[assessmentID][i].text = new Object();
								studentAnswers[assessmentID][i].image = new Object();
								studentAnswers[assessmentID][i].recordedMultipleChoice.question = assessment[i].recordedMultipleChoiceQuestion;
								studentAnswers[assessmentID][i].recordedMultipleChoice.answer = '';
								studentAnswers[assessmentID][i].text.question = assessment[i].textBoxQuestion;
								studentAnswers[assessmentID][i].text.answer = '';
								studentAnswers[assessmentID][i].image.question = assessment[i].imageBoxQuestion;
								studentAnswers[assessmentID][i].image.answer = '';
								studentAnswers[assessmentID][i].title = assessment[i].title;
							}
						}
					}

					for( i in assessment ) {
						objectInstance.displaySlide( i );
						break;
					}

					// Makes the assessment appear in front of all other elements.
					jQuery( '#di-assessment-background' ).toggle();
				} else {
					alert( "Sorry, you are not part of a Digging In Group." );
				}

		});
	}

	/**
	 * Function to retrieve and display a single soil slide from the current
	 * assessment.
	 *
	 * @param {Number} slideID - ID of the slide to retrieve.
	 */
	this.displaySlide = function( slideID ) {

		jQuery( '#di-assessment-slide-id' ).html( slideID );

		var slideObject = assessment[slideID];

		var slideBody = document.getElementById( 'di-assessment-body' );
		var slideFooter = document.getElementById( 'di-assessment-footer' )

		jQuery( '#di-assessment-body' ).html( '' );
		jQuery( '#di-assessment-footer' ).html( '' );

		var title = createGeneralElement( 'div', 'di-as-title', slideObject.title );
		slideBody.appendChild( title );

		var body = createGeneralElement( 'div', 'di-as-body' );
		body.innerHTML = slideObject.body;
		slideBody.appendChild( body );

		if( slideObject.multipleChoiceQuestion != '' ) {
			var bodyMultipleChoice = createGeneralElement( 'div', 'di-as-element', 'Multiple Choice Question (Unrecorded)' );
			var question = createGeneralElement( 'div', 'di-as-question', slideObject.multipleChoiceQuestion );
			bodyMultipleChoice.appendChild( question );
			for( i in slideObject.multipleChoiceAnswers ) {
				var answer = createGeneralElement( 'div', 'di-as-multiple-choice-answer', slideObject.multipleChoiceAnswers[i].text );
				if( slideObject.multipleChoiceAnswers[i].correct == 'true' ) {
					var correct = createGeneralElement( 'div', 'di-li-correct', ' (correct)' );
					answer.appendChild( correct );
					answer.classList.add( 'di-as-multiple-choice-correct' )
				} else {
					answer.classList.add( 'di-as-multiple-choice-incorrect' );
				}
				bodyMultipleChoice.appendChild( answer );
			}
			slideBody.appendChild( bodyMultipleChoice );
		}

		if( slideObject.recordedMultipleChoiceQuestion != '' ) {
			var bodyRecordedMultipleChoice = createGeneralElement( 'div', 'di-as-element', 'Recorded Multiple Choice Question' );
			var question = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], slideObject.recordedMultipleChoiceQuestion );
			bodyRecordedMultipleChoice.appendChild( question );
			for( i in slideObject.recordedMultipleChoiceAnswers ) {
				var answer = createGeneralElement( 'div', 'di-as-recorded-multiple-choice-answer', slideObject.recordedMultipleChoiceAnswers[i].text );
				if( typeof studentAnswers[assessmentID] !== 'undefined' && studentAnswers[assessmentID] !== null ) {
					if( typeof studentAnswers[assessmentID][slideID] !== 'undefined' && studentAnswers[assessmentID][slideID] !== null ) {
						if( studentAnswers[assessmentID][slideID].recordedMultipleChoice.answer == slideObject.recordedMultipleChoiceAnswers[i].text ) {
							answer.classList.add( 'di-as-recorded-multiple-choice-answer-chosen' );
						}
					}
				}
				bodyRecordedMultipleChoice.appendChild( answer );
			}
			slideBody.appendChild( bodyRecordedMultipleChoice );
		}

		if( slideObject.textBoxQuestion != '' ) {
			var bodyTextBoxQuestion = createGeneralElement( 'div', 'di-as-element', 'Text Question' );
			var question = createGeneralElement( 'div', 'di-as-question', slideObject.textBoxQuestion );
			var answer = createGeneralElement( 'textarea', 'di-as-answer' );
			answer.setAttribute( 'id', 'di-as-text-answer' );
			if( typeof studentAnswers[assessmentID] !== 'undefined' && studentAnswers[assessmentID] !== null && typeof studentAnswers[assessmentID][slideID] !== 'undefined' && studentAnswers[assessmentID][slideID] !== null ) {
				answer.innerHTML = studentAnswers[assessmentID][slideID].text.answer;
			}

			bodyTextBoxQuestion.appendChild( question );
			bodyTextBoxQuestion.appendChild( answer );
			slideBody.appendChild( bodyTextBoxQuestion );
		}

		if( slideObject.imageBoxQuestion != '' ) {
			var bodyImageBoxQuestion = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], 'Image Question' );
			var question = createGeneralElement( 'div', 'di-as-question', slideObject.imageBoxQuestion );
			var answer = createGeneralElement( 'div' );

			var answerForm = createGeneralElement( 'form' );
			answerForm.setAttribute( 'id', 'uploadimage' );
			answerForm.setAttribute( 'action', '' );
			answerForm.setAttribute( 'method', 'post' );
			answerForm.setAttribute( 'enctype', 'multipart/form-data' );
			answer.appendChild( answerForm );

			var fileInput = createGeneralElement( 'input' );
			fileInput.setAttribute( 'id', 'file' );
			fileInput.setAttribute( 'type', 'file' );
			fileInput.setAttribute( 'name', 'file' );
			fileInput.required = true;
			answerForm.appendChild( fileInput );

			var canvas = createGeneralElement( 'canvas' );
			canvas.setAttribute( 'id', 'canvas' );
			canvas.setAttribute( 'width', '500' );
			canvas.setAttribute( 'height', '500' );
			answerForm.appendChild( canvas );

			if( typeof studentAnswers[assessmentID] !== 'undefined' && studentAnswers[assessmentID] !== null && typeof studentAnswers[assessmentID][slideID] !== 'undefined' && studentAnswers[assessmentID][slideID] !== null ) {
				canvas.setAttribute( 'src', studentAnswers[assessmentID][slideID].image.answer );
				var ctx = canvas.getContext("2d");
				var imageObj = new Image();
				imageObj.onload=function(){
		        ctx.save();
		        ctx.globalAlpha=3;
		        ctx.drawImage(this,0,0,canvas.width,canvas.height);
		        ctx.restore();
		    }
				imageObj.src = studentAnswers[assessmentID][slideID].image.answer;
				canvas.setAttribute( 'src', studentAnswers[assessmentID][slideID].image.answer );
			}

			/**
			 * Function to prevent default behavior on the canvas element used to drawImage
			 * on pictures. This prevents mobile users from scrolling while drawing.
			 *
			 * @param {Object} e - event to prevent.
			 */
			function preventBehavior(e) {
    		e.preventDefault();
			};

			var fileData = createGeneralElement( 'input' );
			fileData.setAttribute( 'type', 'hidden' );
			fileData.setAttribute( 'id', 'filedata' );
			fileData.setAttribute( 'name', 'filedata' );
			fileData.setAttribute( 'value', '' );
			answerForm.appendChild( fileData );

			var userData = createGeneralElement( 'input' );
			userData.setAttribute( 'type', 'hidden' );
			userData.setAttribute( 'id', 'userdata' );
			userData.setAttribute( 'name', 'userdata' );
			userData.setAttribute( 'value', jQuery( '#di-user' ).html() );
			answerForm.appendChild( userData );

			bodyImageBoxQuestion.appendChild( question );
			bodyImageBoxQuestion.appendChild( answer );
			slideBody.appendChild( bodyImageBoxQuestion );

			jQuery( function() {
				jQuery( "#file" ).change( function() {

					var file = this.files[0];
					var imagefile = file.type;
					var match= ["image/jpeg","image/png","image/jpg"];
					if(!((imagefile==match[0]) || (imagefile==match[1]) || (imagefile==match[2]))) {
						return false;
					} else {
						var reader = new FileReader();
						reader.onload = imageIsLoaded;
						reader.readAsDataURL( this.files[0] );
					}
				});
			});

			/**
			 * Function to display an image loaded from a local machine.
			 *
			 * @param {Object} e - event data.
			 */
			function imageIsLoaded( e ) {
				var canvas = document.getElementById("canvas");
    		var ctx = canvas.getContext("2d");
				var lastX;
		    var lastY;
		    var strokeColor="red";
		    var strokeWidth=2;
		    var canMouseX;
		    var canMouseY;
		    var canvasOffset=jQuery("#canvas").offset();
		    var offsetX=canvasOffset.left;
		    var offsetY=canvasOffset.top;
				var mouseX = jQuery( '#di-assessment-body' ).scrollLeft();
				var mouseY = jQuery( '#di-assessment-body' ).scrollTop();
				jQuery( '#di-assessment-body' ).scroll( function() {
					offsetX=canvasOffset.left - ( jQuery( '#di-assessment-body' ).scrollLeft() - mouseX );
			    offsetY=canvasOffset.top - ( jQuery( '#di-assessment-body' ).scrollTop() - mouseY );
				});
		    var isMouseDown=false;
				var imageObj=new Image();
		    imageObj.onload=function(){
						ctx.clearRect(0, 0, 500, 500);
		        ctx.save();
		        ctx.globalAlpha=.3;
		        ctx.drawImage(this,0,0,canvas.width,canvas.height);
		        ctx.restore();
		    }
		    imageObj.src= e.target.result;
				jQuery( '#canvas' ).attr( 'src', e.target.result );

				/**
				 * Function to handle behavior when a mouse is clicked on the drawable
				 * canvas element.
				 *
				 * @param {Object} e - event.
				 */
				function handleMouseDown(e){
		      canMouseX=parseInt(e.clientX-offsetX);
		      canMouseY=parseInt(e.clientY-offsetY);
		      lastX=canMouseX;
		      lastY=canMouseY;
		      isMouseDown=true;
		    }

				/**
				 * Function to handle behavior when a mouse is clicked on the drawable
				 * canvas element.
				 *
				 * @param {Object} e - event.
				 */
		    function handleMouseUp(e){
		      canMouseX=parseInt(e.clientX-offsetX);
		      canMouseY=parseInt(e.clientY-offsetY);
		      isMouseDown=false;
		    }

				/**
				 * Function to handle behavior when a mouse is clicked on the drawable
				 * canvas element.
				 *
				 * @param {Object} e - event.
				 */
		    function handleMouseOut(e){
		      canMouseX=parseInt(e.clientX-offsetX);
		      canMouseY=parseInt(e.clientY-offsetY);
		      isMouseDown=false;
		    }

				/**
				 * Function to handle behavior when a mouse is clicked on the drawable
				 * canvas element.
				 *
				 * @param {Object} e - event.
				 */
		    function handleMouseMove(e){
		      canMouseX=parseInt(e.clientX-offsetX);
		      canMouseY=parseInt(e.clientY-offsetY);
		      if(isMouseDown){
		          ctx.beginPath();
		          ctx.lineWidth=5;
		          ctx.strokeStyle="#FF0000";
		          ctx.moveTo(lastX,lastY);
		          ctx.lineTo(canMouseX,canMouseY);
		          ctx.stroke();
		          lastX=canMouseX;
		          lastY=canMouseY;
		      }
		    }

				// Event handlers to call mouse behavior functions
		    jQuery("#canvas").mousedown(function(e){handleMouseDown(e);});
		    jQuery("#canvas").mousemove(function(e){handleMouseMove(e);});
		    jQuery("#canvas").mouseup(function(e){handleMouseUp(e);});
		    jQuery("#canvas").mouseout(function(e){handleMouseOut(e);});

				// Event handlers to map touch behaviors to mouse behaviors
				canvas.addEventListener("touchstart", function (e) {
				        mousePos = getTouchPos(canvas, e);
				  var touch = e.touches[0];
				  var mouseEvent = new MouseEvent("mousedown", {
				    clientX: touch.clientX,
				    clientY: touch.clientY
				  });
				  canvas.dispatchEvent(mouseEvent);
				}, false);
				canvas.addEventListener("touchend", function (e) {
				  var mouseEvent = new MouseEvent("mouseup", {});
				  canvas.dispatchEvent(mouseEvent);
				}, false);
				canvas.addEventListener("touchmove", function (e) {
				  var touch = e.touches[0];
				  var mouseEvent = new MouseEvent("mousemove", {
				    clientX: touch.clientX,
				    clientY: touch.clientY
				  });
				  canvas.dispatchEvent(mouseEvent);
				}, false);
				document.body.addEventListener("touchstart", function (e) {
				  if (e.target == canvas) {
				    e.preventDefault();
				  }
				}, false);
				document.body.addEventListener("touchend", function (e) {
				  if (e.target == canvas) {
				    e.preventDefault();
				  }
				}, false);
				document.body.addEventListener("touchmove", function (e) {
				  if (e.target == canvas) {
				    e.preventDefault();
				  }
				}, false);

				/**
				 * Function to get the position of a touch event on the canvas element.
				 *
				 * @param {Object} canvasDom - HTML element object.
				 * @param {Object} touchEvent - the touch event to be handled.
				 */
				function getTouchPos(canvasDom, touchEvent) {
				  var rect = canvasDom.getBoundingClientRect();
				  return {
				    x: touchEvent.touches[0].clientX - rect.left,
				    y: touchEvent.touches[0].clientY - rect.top
				  };
				}

			};

		}

		if( slideObject.final == 'checked' ) {
			var bodyFinal = createGeneralElement( 'div', [ 'di-as-element', 'di-as-final' ] );
			var bodyFinalButton = createGeneralElement( 'div', [ 'di-as-button', 'di-as-button-submit' ], 'Review and Submit' );
			bodyFinal.appendChild( bodyFinalButton );
			slideBody.appendChild( bodyFinal );
		}

		if( typeof slideObject.controlButtons !== 'undefined' && slideObject.controlButtons != '' ) {
			var bodyControlButtons = createGeneralElement( 'div', 'di-as-element' );
			for( i in slideObject.controlButtons ) {
				var button = createGeneralElement( 'div', 'di-as-button-slide-link', slideObject.controlButtons[i].text );
				button.setAttribute( 'value', slideObject.controlButtons[i].value );
				bodyControlButtons.appendChild( button );
			}
			slideFooter.appendChild( bodyControlButtons );
		}

	}

	/**
	 * Function to display the special "Review and Submit" slide, which allows
	 * students to submit assessment results.
	 *
	 */
	this.displayReviewSlide = function() {
		var footer = document.getElementById( 'di-reviewer-footer' );
		var firstButton = createGeneralElement( 'div', 'di-as-button', 'Go to First Slide' );
		firstButton.setAttribute( 'id', 'di-as-button-slide-link-first' );
		footer.appendChild( firstButton );

		var submitButton = createGeneralElement( 'div', 'di-as-button', 'Submit' )
		submitButton.setAttribute( 'id', 'di-assessment-submit' );
		footer.appendChild( submitButton );

		var body = document.getElementById( 'di-reviewer-body' );

		for( i in studentAnswers[assessmentID] ) {

			var header = createGeneralElement( 'h3', 'di-review-title', assessment[i].title );
			body.appendChild( header );

			if( studentAnswers[assessmentID][i].recordedMultipleChoice.question != '' || studentAnswers[assessmentID][i].text.question != '' || studentAnswers[assessmentID][i].image.question != '' ) {

				if( studentAnswers[assessmentID][i].recordedMultipleChoice.question != '' ) {
					var recordedMultipleChoiceQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].recordedMultipleChoice.question );
					body.appendChild( recordedMultipleChoiceQuestion );
					if( studentAnswers[assessmentID][i].recordedMultipleChoice.answer != '') {
						var recordedMultipleChoiceAnswer = createGeneralElement( 'p', 'di-review-answer', 'Answer: ' + studentAnswers[assessmentID][i].recordedMultipleChoice.answer );
						body.append( recordedMultipleChoiceAnswer );
					} else {
						var noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
						body.append( noAnswer );
					}
				}

				if( studentAnswers[assessmentID][i].text.question != '' ) {
					var textQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].text.question );
					body.appendChild( textQuestion );
					if( studentAnswers[assessmentID][i].text.answer != '') {
						var textAnswer = createGeneralElement( 'p', 'di-review-answer', 'Answer: ' + studentAnswers[assessmentID][i].text.answer );
						body.append( textAnswer );
					} else {
						var noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
						body.append( noAnswer );
					}
				}

				if( studentAnswers[assessmentID][i].image.question != '' ) {
					var imageQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].image.question );
					body.appendChild( imageQuestion );
					if( studentAnswers[assessmentID][i].image.answer != '') {
						var imageAnswer = createGeneralElement( 'img', 'di-review-answer' );
						imageAnswer.setAttribute( 'src', studentAnswers[assessmentID][i].image.answer );
						body.append( imageAnswer );
					} else {
						var noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
						body.append( noAnswer );
					}
				}

			} else {
				var noQuestions = createGeneralElement( 'p', '', 'No recorded questions in this slide.' );
				body.appendChild( noQuestions );
			}
			var hr = createGeneralElement( 'hr', '' )
			body.appendChild( hr );
		}
	}

	/**
	 * Function to enable appropriate button behaviors.
	 *
	 */
	this.enableButtons = function() {
		jQuery( '#di-assessment-body' ).on( 'click', '.di-as-multiple-choice-incorrect', function() {
			if( typeof jQuery( this ).find( '.di-as-multiple-choice-announcement' ).html() == "undefined" ) {
				jQuery( this ).prepend( '<div class="di-as-multiple-choice-announcement">INCORRECT: </div>' );
				jQuery( this ).css( 'background', 'red' );
			}
		});
		jQuery( '#di-assessment-body' ).on( 'click', '.di-as-multiple-choice-correct', function() {
			if( typeof jQuery( this ).find( '.di-as-multiple-choice-announcement' ).html() == "undefined" ) {
				jQuery( this ).prepend( '<div class="di-as-multiple-choice-announcement">CORRECT: </div>' );
				jQuery( this ).css( 'background', '#0FD' );
				jQuery( this ).css( 'color', 'black' );
			}
		});
		jQuery( '#di-assessment-body' ).on( 'click', '.di-as-recorded-multiple-choice-answer', function() {
			jQuery( '.di-as-recorded-multiple-choice-answer' ).removeClass( 'di-as-recorded-multiple-choice-answer-chosen' );
			jQuery( this ).addClass( 'di-as-recorded-multiple-choice-answer-chosen' );
			objectInstance.addStudentAnswers();
		});
		jQuery( '#di-assessment-footer' ).on( 'click', '.di-as-button-slide-link', function (){
			objectInstance.addStudentAnswers();
			var slideID = jQuery( this ).attr( 'value' );
			objectInstance.displaySlide( slideID );
		});
		jQuery( '#di-assessment-reviewer' ).on( 'click', function (){
			objectInstance.addStudentAnswers();
			jQuery( '#di-review' ).toggle();
			objectInstance.displayReviewSlide();
		});
		jQuery( '#di-assessment-body' ).on( 'click', '.di-as-button-submit', function () {
			objectInstance.addStudentAnswers();
			jQuery( '#di-review' ).toggle();
			objectInstance.displayReviewSlide();
		});
		jQuery( '#di-assessment-returner' ).on( 'click', function (){
			jQuery( '#di-review' ).toggle();
			jQuery( '#di-reviewer-body' ).html( '' );
			jQuery( '#di-reviewer-footer' ).html( '' );
		});
		jQuery( '#di-assessment-closer' ).on( 'click', function() {
			jQuery( '#di-assessment-background' ).hide();
		});
		jQuery( '#di-reviewer-closer' ).on( 'click', function() {
			jQuery( '#di-review' ).toggle();
			jQuery( '#di-assessment-background' ).hide();
		});
		jQuery( '#di-reviewer-footer' ).on( 'click', '#di-assessment-submit', function() {
				var data = {
					'action': 'digging_in_add_assessment_result',
					'di_nonce_field': jQuery( '#di-nonce-field' ).val(),
					'di_assessment_result_title': jQuery( '#di-user' ).html() + ': Site ' + jQuery( '#di-site-id' ).html() + '; Assessment: ' + jQuery( '#di-assessment-id' ).html(),
					'di_assessment_result_user': jQuery( '#di-user-id' ).html(),
					'di_assessment_result_data': JSON.stringify( studentAnswers[assessmentID] ),
					'di_assessment_result_site': jQuery( '#di-site-id' ).html(),
					'di_assessment_result_assessment': jQuery( '#di-assessment-id' ).html(),
					'di_assessment_result_id': studentAnswersID
				};
				jQuery.post( ajax_object.ajax_url, data, function( response ) {
					alert( response );
					jQuery( '#di-review' ).toggle();
					jQuery( '#di-reviewer-body' ).html( '' );
					jQuery( '#di-reviewer-footer' ).html( '' );
					jQuery( '#di-assessment-body' ).html( '' );
					jQuery( '#di-assessment-footer' ).html( '' );
					jQuery( '#di-assessment-background' ).hide();
				});
		});
		jQuery( '#di-reviewer-footer' ).on( 'click', '#di-as-button-slide-link-first', function() {
			jQuery( '#di-review' ).toggle();
			jQuery( '#di-reviewer-body' ).html( '' );
			jQuery( '#di-reviewer-footer' ).html( '' );
			for( i in assessment ) {
				objectInstance.displaySlide( i );
				break;
			}
		});
	}

	/**
	 * Function to add student answers to an assessment result object, including
	 * an AJAX call to upload images to the WordPress database.
	 *
	 */
	this.addStudentAnswers = function() {

		var currentAssessmentID = jQuery( '#di-assessment-id' ).html();
		var currentSlideID = jQuery( '#di-assessment-slide-id' ).html();
		var slideObject = assessment[currentSlideID];

		if( slideObject.recordedMultipleChoiceQuestion != '' && typeof studentAnswers[currentAssessmentID] !== 'undefined' && studentAnswers[currentAssessmentID] !== null && typeof studentAnswers[currentAssessmentID][currentSlideID] !== 'undefined' && studentAnswers[currentAssessmentID][currentSlideID] !== null ) {
			studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer = jQuery( '.di-as-recorded-multiple-choice-answer-chosen' ).html();
			if( typeof studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer === 'undefined' ) {
				studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer = '';
			}
		}

		if( slideObject.textBoxQuestion != '' ) {
			studentAnswers[currentAssessmentID][currentSlideID].text.answer = jQuery( '#di-as-text-answer' ).val();
		}

		if( slideObject.imageBoxQuestion != '' && jQuery( '#canvas' ).attr( 'src' ) != '' ) {
			var canvas = document.getElementById("canvas");
			var dataURL = canvas.toDataURL();
			var blob = dataURItoBlob(dataURL);
			var fd = new FormData(document.forms[0]);
			fd.append("file", blob, "test.jpg");
			jQuery.ajax( {
				url: "../wp-content/plugins/digging-in/php/ajax_php_file.php", 	// Url to which the request is send
				type: "POST",             																			// Type of request to be send, called as method
				data: fd, 																											// Data sent to server, a set of key/value pairs (i.e. form fields and values)
				contentType: false,       																			// The content type used when sending data to the server.
				cache: false,             																			// To unable request pages to be cached
				processData:false,        																			// To send DOMDocument or non processed data file it is set to false
				success: function(data) {
					studentAnswers[currentAssessmentID][currentSlideID].image.answer = data;
				}
			});
		}

	}

}

/**
 * Function to create an HTML element with associated class(es) and text node.
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
			element.classList.add( classes[i] );
		}
	}
	var text = document.createTextNode( content );
	element.appendChild( text );
	return element;
}

/**
 * Function to change a data URI to a blob when uploading images
 *
 * @param {String} dataURI - image string to convert to blob
 * @return {Object} Blob - image object to return
 */
function dataURItoBlob(dataURI) {
		// convert base64/URLEncoded data component to raw binary data held in a string
		var byteString;
		if (dataURI.split(',')[0].indexOf('base64') >= 0)
				byteString = atob(dataURI.split(',')[1]);
		else
				byteString = unescape(dataURI.split(',')[1]);

		// separate out the mime component
		var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
		// write the bytes of the string to a typed array
		var ia = new Uint8Array(byteString.length);
		for (var i = 0; i < byteString.length; i++) {
				ia[i] = byteString.charCodeAt(i);
		}

		return new Blob([ia], {ext:mimeString, type:mimeString} );
}
