// This file contains JavaScript code used to display the Digging In plugin on the web frontend.

// Current assessment object and student answers object.
var studentAnswers = {};
var studentAnswersID = '';
var assessment = {};
var uploadImage = false;

jQuery( document ).ready(function( $ ) {
	// Creating variables used in creating the Google Maps element used to retrieve soil sites
	var requestedLatlng, mapOptions, map, marker, data;

	// Instantiating a new Google Maps LatLng location object and setting map options.
	requestedLatlng = new google.maps.LatLng( parseFloat( jQuery( '#di-bounding-box' ).attr( 'centerlat' ) ), parseFloat( jQuery( '#di-bounding-box' ).attr( 'centerlon' ) ) );
	mapOptions = {
		zoom: parseInt( jQuery( '#di-bounding-box' ).attr( 'zoom' ) ),
		center: requestedLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	// Creating a new Google Maps object.
	map = new google.maps.Map( document.getElementById( 'di-map-canvas' ), mapOptions );
	var diMap = new DIMap( map );

	// Retrieving and displaying a single point's data if di-site-id is enabled (for mobile sites), otherwise displaying all points.
	if( jQuery( '#di-site-id' ).html() !== '' ) {
		diMap.retrievePoint( jQuery( '#di-site-id' ).html() );
		var site = document.getElementById( 'di-site' );
		site.classList.add( 'mobile-site' );
	} else {
		var sites = diMap.retrievePoints();
		diMap.displayLayers( map );
	}
	diMap.enableButtons();

	document.getElementById( 'di-header-loginout' ).addEventListener( 'click', function( event ) {
		var siteID = jQuery( '#di-site-id' ).html();
		setCookie( 'ubc_di_point_view', siteID, '' );
	});

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

	google.maps.event.trigger( map, 'resize' );

	/**
	 * Function to retrieve all valid soil sites from WordPress's database with an
	 * AJAX call to class-ubc-di-view.php's ubc_di_map_callback() function.
	 */
	this.retrievePoints = function() {
		data = {
			'action': 'digging_in_get_sites'
		};
		jQuery.post( ubc_di_ajax_object.ajax_url, data, function( response ) {
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
	};

	/**
	 * Function to retrieve and display a single soil site from WordPress's
	 * database with an AJAX call to class-ubc-di-view.php's ubc_di_map_site_callback()
	 * function.
	 *
	 * @param {Number} siteID - ID of the site to retrieve.
	 */
	this.retrievePoint = function( siteID ) {

		jQuery( '#di-site-id' ).html( siteID );

		data = {
			'action': 'digging_in_get_site',
			'ubc_di_site_id': siteID,
			'ubc_di_nonce_field': jQuery( '#di-nonce-field' ).val()
		};
		jQuery.post( ubc_di_ajax_object.ajax_url, data, function( response ) {

			jQuery( '#di-site .main-left' ).html( '' );
			jQuery( '#di-site .main-right' ).html( '' );

			jQuery( '#di-site .main-left' ).append( '<h2>' + response.title );
			jQuery( '#di-site .main-left' ).append( '<p2>' + response.content + '</p><hr />' );
			for( var ubc_di_medium in response.ubc_di_media ) {
				if( response.ubc_di_media.hasOwnProperty( ubc_di_medium ) ) {
					jQuery( '#di-site .main-right' ).append( '<h2>' + response.ubc_di_media[ubc_di_medium].title + '</h2><p>' + response.ubc_di_media[ubc_di_medium].media + '</p>' + response.ubc_di_media[ubc_di_medium].description + '<p><hr /></p>' );
				}
			}
			var tempText = '<ul>';
			for( var ubc_di_assessment in response.ubc_di_assessments ) {
				if( response.ubc_di_assessments.hasOwnProperty( ubc_di_assessment ) && typeof response.ubc_di_assessments[ubc_di_assessment].title !== 'undefined' ) {
					tempText = tempText + '<li id="di-assessment-starter-' + response.ubc_di_assessments[ubc_di_assessment].id + '"><a href="#">' + response.ubc_di_assessments[ubc_di_assessment].title + '</a></li>';
				}
			}
			tempText = tempText + '</ul><hr />';
			jQuery( '#di-site .main-left' ).append( tempText );
			jQuery( '[ id^=di-assessment-starter- ]' ).click(function() {
				assessmentID = jQuery( this ).attr( 'id' ).replace( 'di-assessment-starter-', '' );
				objectInstance.retrieveAssessment( assessmentID );
			});
		});
	};

	/**
	 * Function to retrieve and display a single assessment from WordPress's
	 * database with an AJAX call to class-ubc-di-view.php's
	 * ubc_di_map_assessment_callback() function.
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
			'ubc_di_assessment_id': assessmentID,
			'ubc_di_user_id': jQuery( '#di-user-id' ).html(),
			'ubc_di_nonce_field': jQuery( '#di-nonce-field' ).val(),
		};
		jQuery.post( ubc_di_ajax_object.ajax_url, data, function( response ) {

				assessment = response.data;

				if( response.group !== '' ) {
					var header = createGeneralElement( 'div', '', response.title );
					jQuery( '#di-assessment-header' ).html( '' );
					jQuery( '#di-assessment-header' ).append( header );

					if( response.answers !== '' || typeof response.answers !== 'undefined' ) {
						studentAnswers[assessmentID] = JSON.parse( response.answers );
						studentAnswersID = response.answers_id;
					} else {
						studentAnswers = {};
						studentAnswersID = '';
					}

					if( typeof studentAnswers[assessmentID] === 'undefined' || studentAnswers[assessmentID] === null ) {
						studentAnswers[assessmentID] = {};
						for( var i in assessment ) {
							if( assessment.hasOwnProperty( i ) && typeof studentAnswers[assessmentID][i] === 'undefined' ) {
								studentAnswers[assessmentID][i] = {};
								studentAnswers[assessmentID][i].recordedMultipleChoice = {};
								studentAnswers[assessmentID][i].text = {};
								studentAnswers[assessmentID][i].image = {};
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

					for( var j in assessment ) {
						if( assessment.hasOwnProperty( j ) ) {
							objectInstance.displaySlide( j );
							break;
						}
					}

					// Makes the assessment appear in front of all other elements.
					jQuery( '#di-assessment-background' ).toggle();
				} else {
					alert( "Sorry, you are not part of a Digging In Group." );
				}

		});
	};

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
		var slideFooter = document.getElementById( 'di-assessment-footer' );

		jQuery( '#di-assessment-body' ).html( '' );
		jQuery( '#di-assessment-footer' ).html( '' );

		if( jQuery( '#di-user-id' ).html() == 0 ) {
			var warning = createGeneralElement( 'p', '', 'Warning: you are not logged in. You will not be able to submit your results or save images.' );
			slideBody.appendChild( warning );
		}

		var title = createGeneralElement( 'div', 'di-as-title', slideObject.title );
		slideBody.appendChild( title );

		var body = createGeneralElement( 'div', 'di-as-body' );
		body.innerHTML = slideObject.body;
		slideBody.appendChild( body );

    var question, answer;

		if( slideObject.multipleChoiceQuestion !== '' ) {
			var bodyMultipleChoice = createGeneralElement( 'div', 'di-as-element' );
			question = createGeneralElement( 'div', 'di-as-question', slideObject.multipleChoiceQuestion );
			bodyMultipleChoice.appendChild( question );
			for( var i in slideObject.multipleChoiceAnswers ) {
				if( slideObject.multipleChoiceAnswers.hasOwnProperty( i ) ) {
					answer = createGeneralElement( 'div', 'di-as-multiple-choice-answer', slideObject.multipleChoiceAnswers[i].text );
					if( slideObject.multipleChoiceAnswers[i].correct === 'true' ) {
						var correct = createGeneralElement( 'div', 'di-li-correct', ' (correct)' );
						answer.appendChild( correct );
						answer.classList.add( 'di-as-multiple-choice-correct' );
					} else {
						answer.classList.add( 'di-as-multiple-choice-incorrect' );
					}
					bodyMultipleChoice.appendChild( answer );
				}
			}
			slideBody.appendChild( bodyMultipleChoice );
		}

		if( slideObject.recordedMultipleChoiceQuestion !== '' ) {
			var bodyRecordedMultipleChoice = createGeneralElement( 'div', 'di-as-element' );
			question = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ], slideObject.recordedMultipleChoiceQuestion );
			bodyRecordedMultipleChoice.appendChild( question );
			for( var h in slideObject.recordedMultipleChoiceAnswers ) {
				if( slideObject.recordedMultipleChoiceAnswers.hasOwnProperty( h ) ) {
					answer = createGeneralElement( 'div', 'di-as-recorded-multiple-choice-answer', slideObject.recordedMultipleChoiceAnswers[h].text );
					if( typeof studentAnswers[assessmentID] !== 'undefined' && studentAnswers[assessmentID] !== null ) {
						if( typeof studentAnswers[assessmentID][slideID] !== 'undefined' && studentAnswers[assessmentID][slideID] !== null ) {
							if( studentAnswers[assessmentID][slideID].recordedMultipleChoice.answer == slideObject.recordedMultipleChoiceAnswers[h].text ) {
								answer.classList.add( 'di-as-recorded-multiple-choice-answer-chosen' );
							}
						}
					}
					bodyRecordedMultipleChoice.appendChild( answer );
				}
			}
			slideBody.appendChild( bodyRecordedMultipleChoice );
		}

		if( slideObject.textBoxQuestion !== '' ) {
			var bodyTextBoxQuestion = createGeneralElement( 'div', 'di-as-element' );
			question = createGeneralElement( 'div', 'di-as-question', slideObject.textBoxQuestion );
			answer = createGeneralElement( 'textarea', 'di-as-answer' );
			answer.setAttribute( 'id', 'di-as-text-answer' );
			if( typeof studentAnswers[assessmentID] !== 'undefined' && studentAnswers[assessmentID] !== null && typeof studentAnswers[assessmentID][slideID] !== 'undefined' && studentAnswers[assessmentID][slideID] !== null ) {
				answer.innerHTML = studentAnswers[assessmentID][slideID].text.answer;
			}

			bodyTextBoxQuestion.appendChild( question );
			bodyTextBoxQuestion.appendChild( answer );
			slideBody.appendChild( bodyTextBoxQuestion );
		}

		if( slideObject.imageBoxQuestion !== '' ) {
			var bodyImageBoxQuestion = createGeneralElement( 'div', [ 'di-as-element', 'di-as-question' ] );
			question = createGeneralElement( 'div', 'di-as-question', slideObject.imageBoxQuestion );
			answer = createGeneralElement( 'div' );

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
		    };
				imageObj.src = studentAnswers[assessmentID][slideID].image.answer;
				canvas.setAttribute( 'src', studentAnswers[assessmentID][slideID].image.answer );
			}

			/**
			 * Function to prevent default behavior on the canvas element used to drawImage
			 * on pictures. This prevents mobile users from scrolling while drawing.
			 *
			 * @param {Object} e - event to prevent.
			 */
			var preventBehavior = function(e) {
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
			var imageIsLoaded = function( e ) {
				uploadImage = true;
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
		        ctx.globalAlpha= '.9';
		        ctx.drawImage(this,0,0,canvas.width,canvas.height);
		        ctx.restore();
		    };
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
		          ctx.strokeStyle="#ffa500";
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
			if( jQuery( '#di-user-id' ).html() > 0 ) {
				var bodyFinalButton = createGeneralElement( 'div', [ 'di-as-button', 'di-as-button-submit' ], 'Review and submit your answers' );
			} else {
				var bodyFinalButton = createGeneralElement( 'div', [ 'di-as-button', 'di-as-no-button' ], 'Log in to be able to submit answers.' );
			}
			bodyFinal.appendChild( bodyFinalButton );
			slideBody.appendChild( bodyFinal );
		}

		if( typeof slideObject.controlButtons !== 'undefined' && slideObject.controlButtons !== '' ) {
			var bodyControlButtons = createGeneralElement( 'div', 'di-as-element' );
			for( var j in slideObject.controlButtons ) {
				if( slideObject.controlButtons.hasOwnProperty( j ) ) {
					var button = createGeneralElement( 'div', 'di-as-button-slide-link', slideObject.controlButtons[j].text );
					button.setAttribute( 'value', slideObject.controlButtons[j].value );
					bodyControlButtons.appendChild( button );
				}
			}
			slideFooter.appendChild( bodyControlButtons );
		}
	};

	/**
	 * Function to display the special "Review and Submit" slide, which allows
	 * students to submit assessment results.
	 *
	 */
	this.displayReviewSlide = function() {
		jQuery( '#di-reviewer-body' ).html( '' );
		jQuery( '#di-reviewer-footer' ).html( '' );
		var footer = document.getElementById( 'di-reviewer-footer' );
		var firstButton = createGeneralElement( 'div', 'di-as-button', 'Go to First Slide' );
		firstButton.setAttribute( 'id', 'di-as-button-slide-link-first' );
		footer.appendChild( firstButton );

		if( jQuery( '#di-user-id' ).html() > 0 ) {
			var submitButton = createGeneralElement( 'div', 'di-as-button', 'Submit' );
			submitButton.setAttribute( 'id', 'di-assessment-submit' );
		} else {
			var submitButton = createGeneralElement( 'div', [ 'di-as-button', 'di-as-no-button' ], 'Log in to be able to submit answers.' );
		}
		footer.appendChild( submitButton );

		var body = document.getElementById( 'di-reviewer-body' );

		for( var i in studentAnswers[assessmentID] ) {
			if( studentAnswers[assessmentID].hasOwnProperty( i ) ) {

				if( studentAnswers[assessmentID][i].recordedMultipleChoice.question !== '' || studentAnswers[assessmentID][i].text.question !== '' || studentAnswers[assessmentID][i].image.question !== '' ) {

					var header = createGeneralElement( 'h3', 'di-review-title', assessment[i].title + ' (tap to jump to this slide)' );
					header.setAttribute( 'slide-id', i );
					body.appendChild( header );

					jQuery( header ).on( 'click', function( e ) {
						var slide = this.getAttribute( 'slide-id' );
						for( var j in assessment ) {
							if( assessment.hasOwnProperty( j ) && j === slide ) {
								jQuery( '#di-review' ).toggle();
								jQuery( '#di-reviewer-body' ).html( '' );
								jQuery( '#di-reviewer-footer' ).html( '' );
								objectInstance.displaySlide( j );
								break;
							}
						}
					});

          var noAnswer;

					if( studentAnswers[assessmentID][i].recordedMultipleChoice.question !== '' ) {
						var recordedMultipleChoiceQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].recordedMultipleChoice.question );
						body.appendChild( recordedMultipleChoiceQuestion );
						if( studentAnswers[assessmentID][i].recordedMultipleChoice.answer !== '') {
							var recordedMultipleChoiceAnswer = createGeneralElement( 'p', 'di-review-answer', 'Answer: ' + studentAnswers[assessmentID][i].recordedMultipleChoice.answer );
							body.append( recordedMultipleChoiceAnswer );
						} else {
							noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
							body.append( noAnswer );
						}
					}

					if( studentAnswers[assessmentID][i].text.question !== '' ) {
						var textQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].text.question );
						body.appendChild( textQuestion );
						if( studentAnswers[assessmentID][i].text.answer !== '') {
							var textAnswer = createGeneralElement( 'p', 'di-review-answer', 'Answer: ' + studentAnswers[assessmentID][i].text.answer );
							body.append( textAnswer );
						} else {
							noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
							body.append( noAnswer );
						}
					}

					if( studentAnswers[assessmentID][i].image.question !== '' ) {
						var imageQuestion = createGeneralElement( 'h4', 'di-review-question', 'Question: ' + studentAnswers[assessmentID][i].image.question );
						body.appendChild( imageQuestion );
						if( studentAnswers[assessmentID][i].image.answer !== '' ) {
							var imageAnswer = createGeneralElement( 'img', 'di-review-answer' );
							imageAnswer.setAttribute( 'src', studentAnswers[assessmentID][i].image.answer );
							body.append( imageAnswer );
						} else {
							noAnswer = createGeneralElement( 'p', 'di-review-no-answer', 'No answer for this question.' );
							body.append( noAnswer );
						}
					}

					var hr = createGeneralElement( 'hr', '' );
					body.appendChild( hr );

				}
			}
		}
	};

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
					'ubc_di_nonce_field': jQuery( '#di-nonce-field' ).val(),
					'ubc_di_assessment_result_title': jQuery( '#di-user' ).html() + ': Site ' + jQuery( '#di-site-id' ).html() + '; Assessment: ' + jQuery( '#di-assessment-id' ).html(),
					'ubc_di_assessment_result_user': jQuery( '#di-user-id' ).html(),
					'ubc_di_assessment_result_data': JSON.stringify( studentAnswers[assessmentID] ),
					'ubc_di_assessment_result_site': jQuery( '#di-site-id' ).html(),
					'ubc_di_assessment_result_assessment': jQuery( '#di-assessment-id' ).html(),
					'ubc_di_assessment_result_id': studentAnswersID
				};
				jQuery.post( ubc_di_ajax_object.ajax_url, data, function( response ) {
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
			for( var i in assessment ) {
				if( assessment.hasOwnProperty( i ) ) {
					objectInstance.displaySlide( i );
					break;
				}
			}
		});
	};

	/**
	 * Function to add student answers to an assessment result object, including
	 * an AJAX call to upload images to the WordPress database.
	 *
	 */
	this.addStudentAnswers = function() {

		var currentAssessmentID = jQuery( '#di-assessment-id' ).html();
		var currentSlideID = jQuery( '#di-assessment-slide-id' ).html();
		var slideObject = assessment[currentSlideID];

		if( typeof slideObject.recordedMultipleChoiceQuestion !== 'undefined' && slideObject.recordedMultipleChoiceQuestion !== '' && typeof studentAnswers[currentAssessmentID] !== 'undefined' && studentAnswers[currentAssessmentID] !== null && typeof studentAnswers[currentAssessmentID][currentSlideID] !== 'undefined' && studentAnswers[currentAssessmentID][currentSlideID] !== null ) {
			studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer = jQuery( '.di-as-recorded-multiple-choice-answer-chosen' ).html();
			if( typeof studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer === 'undefined' ) {
				studentAnswers[currentAssessmentID][currentSlideID].recordedMultipleChoice.answer = '';
			}
		}

		if( slideObject.textBoxQuestion !== '' ) {
			studentAnswers[currentAssessmentID][currentSlideID].text.answer = jQuery( '#di-as-text-answer' ).val();
		}

		if( slideObject.imageBoxQuestion !== '' && jQuery( '#canvas' ).attr( 'src' ) !== '' && uploadImage === true ) {

			var greyOut = this.greyOutScreen();
			var env = this;

			var canvas = document.getElementById("canvas");
			var dataURL = canvas.toDataURL();
			var blob = dataURItoBlob(dataURL);
			var fd = new FormData(document.forms[0]);
			fd.append("file", blob, "test.jpg");
			fd.append('name', 'This is Name');
			fd.append('action', 'digging_in_upload_image');
			fd.append( 'ubc_di_nonce_field', jQuery( '#di-nonce-field' ).val() );
			jQuery.ajax( {
						url: ubc_di_ajax_object.ajax_url, 	// Url to which the request is send
						type: "POST",
						data: fd,
						contentType:false,
				    processData:false,
						success: function( data ) {
							uploadImage = false;
							env.restoreScreen( greyOut );
							studentAnswers[currentAssessmentID][currentSlideID].image.answer = data;
						}
					});
		}
	};

	/**
	 * Function to grey out the screen while an image is loading.
	 *
	 */
	this.greyOutScreen = function() {
		var body = document.getElementsByTagName( 'body' )[0];
		var greyOut = createGeneralElement( 'div', 'grey-out' );
		var greyOutText = createGeneralElement( 'div', 'grey-out-text', 'Please wait while your image uploads to the server.' );
		greyOut.appendChild( greyOutText );
		body.appendChild( greyOut );
		return greyOut;
	}

	/**
	 * Function to restore the screen after the image has loaded.
	 *
	 */
	this.restoreScreen = function( element ) {
		element.parentNode.removeChild( element );
	}

	/**
	 * Function to add KML display layers to the map. Uses the layer options
	 * defined in class-ubc-di-admin.php.
	 *
	 */
	this.displayLayers = function( map ) {

		var kmlLayer_1 = new google.maps.KmlLayer( {
			url: jQuery( '#di-layer-1' ).attr( 'file' ) + '?rand=' + (new Date()).valueOf(),
			preserveViewport: true
		} );

		var kmlLayer_2 = new google.maps.KmlLayer( {
			url: jQuery( '#di-layer-2' ).attr( 'file' ) + '?rand='+(new Date()).valueOf(),
			preserveViewport: true
		} );

		var kmlLayer_3 = new google.maps.KmlLayer({
			url: jQuery( '#di-layer-3' ).attr( 'file' ) + '?rand='+(new Date()).valueOf(),
			preserveViewport: true
		});

		var kmlLayer_4 = new google.maps.KmlLayer({
			url: jQuery( '#di-layer-4' ).attr( 'file' ) + '?rand='+(new Date()).valueOf(),
			preserveViewport: true
		});

		var kmlClick_1 = document.getElementById( 'map-control-1' );
		var kmlClick_2 = document.getElementById( 'map-control-2' );
		var kmlClick_3 = document.getElementById( 'map-control-3' );
		var kmlClick_4 = document.getElementById( 'map-control-4' );

		var kmlLayer_1Status = 1;
		var kmlLayer_2Status = 1;
		var kmlLayer_3Status = 1;
		var kmlLayer_4Status = 1;

		kmlLayer_1.setMap(map);
		kmlLayer_2.setMap(map);
		kmlLayer_3.setMap(map);
		kmlLayer_4.setMap(map);

		kmlClick_1.onclick= function(){
		    if(kmlLayer_1Status == 1) {
		       kmlLayer_1.setMap();
		        kmlLayer_1Status = 0;
		        document.getElementById("label1").style.backgroundColor = "#f7f7f7";
		    } else{
		        kmlLayer_1.setMap(map);
		        kmlLayer_1Status = 1;
		        document.getElementById("label1").style.backgroundColor = "#e4d8f4";
		    }
		};


		kmlClick_2.onclick= function(){
		    if(kmlLayer_2Status == 1) {
		        kmlLayer_2.setMap();
		        kmlLayer_2Status = 0;
		        document.getElementById("label2").style.backgroundColor = "#f7f7f7";
		    } else if(kmlLayer_2Status == 0){
		        kmlLayer_2.setMap(map);
		        kmlLayer_2Status = 1;
		        document.getElementById("label2").style.backgroundColor = "#cbdce8";
		    }
		};

		kmlClick_3.onclick= function(){
		    if(kmlLayer_3Status == 1) {
		        kmlLayer_3.setMap();
		        kmlLayer_3Status = 0;
		        document.getElementById("label3").style.backgroundColor = "#f7f7f7";
		    } else if(kmlLayer_3Status == 0){
		        kmlLayer_3.setMap(map);
		        kmlLayer_3Status = 1;
		        document.getElementById("label3").style.backgroundColor = "#f0d5d4";
		    }
		};

		kmlClick_4.onclick= function(){
		    if(kmlLayer_4Status == 1) {
		        kmlLayer_4.setMap();
		        kmlLayer_4Status = 0;
		        document.getElementById("label4").style.backgroundColor = "#f7f7f7";
		    } else if(kmlLayer_4Status == 0){
		        kmlLayer_4.setMap(map);
		        kmlLayer_4Status = 1;
		        document.getElementById("label4").style.backgroundColor = "#deeccf";
		    }
		};

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
 function createGeneralElement( tagName, classes, content ) {
 	var element = document.createElement( tagName );
 	if( typeof classes === 'string' && classes.length > 0 ) {
 		element.classList.add( classes );
 	} else if( typeof classes === 'object' ) {
 		for( var i in classes ) {
 			if( classes.hasOwnProperty( i ) ) {
 				element.classList.add( classes[i] );
 			}
 		}
 	}
 	if( content !== '' && typeof content !== 'undefined' ) {
 		var text = document.createTextNode( content );
 		element.appendChild( text );
 	}
 	return element;
 }

/**
 * Function to change a data URI to a blob when uploading images
 *
 * @param {String} dataURI - image string to convert to blob
 * @return {Object} Blob - image object to return
 */
function dataURItoBlob( dataURI ) {
		// convert base64/URLEncoded data component to raw binary data held in a string
		var byteString;
		if ( dataURI.split( ',' )[0].indexOf('base64' ) >= 0 )
				byteString = atob(dataURI.split(',')[1]);
		else
				byteString = unescape(dataURI.split(',')[1]);

		// separate out the mime component
		var mimeString = dataURI.split( ',' )[0].split( ':' )[1].split( ';' )[0];
		// write the bytes of the string to a typed array
		var ia = new Uint8Array( byteString.length );
		for ( var i = 0; i < byteString.length; i++ ) {
				ia[i] = byteString.charCodeAt( i );
		}

		return new Blob( [ia], { ext:mimeString, type:mimeString } );
}

/**
 * Sets a cookie with a specified name, value, and expiry date.
 *
 * @param {String} name - name of cookie
 * @param {String} value - value inside cookie
 * @param {String} days - days until the cookie expires
 */
function setCookie( name, value, days ) {
    var expires = '';
    if ( days ) {
        var date = new Date();
        date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + '=' + ( value || '' )  + expires + '; path=/';
}

/**
 * Get a cookie with a specified name.
 *
 * @param {String} name - name of cookie
 */
function getCookie( name ) {
    var nameEQ = name + '=';
    var ca = document.cookie.split( ';' );
    for( var i=0; i < ca.length; i++ ) {
        var c = ca[i];
        while ( c.charAt( 0 )== ' ' ) c = c.substring( 1,c.length );
        if ( c.indexOf( nameEQ ) == 0 ) return c.substring( nameEQ.length, c.length );
    }
    return null;
}

/**
 * Deletes a cookie with a specified name.
 *
 * @param {String} name - name of cookie
 */
function eraseCookie( name ) {
    document.cookie = name + '=; Max-Age=-99999999;';
}
