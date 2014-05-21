define('wikia.intMaps.createMapUI', ['jquery', 'wikia.window', 'wikia.mustache'], function($, w, mustache) {
	'use strict';

	var body = $('body'),

		// placeholder for holding reference to modal instance
		createMapModal,
		// placeholder for caching create map flow modal sections
		modalSections,
		// placeholder for caching create map modal buttons
		modalButtons,
		// holds last step of create map flow
		lastStep = 0,
		// holds current step of create map flow
		currentStep = 0,
		// holds config for each step
		// TODO: maybe it would be better to use object instead of array
		steps = [
			{
				id: '#intMapsChooseType'
			},
			{
				id: '#intMapsChooseTileSet',
				buttons: {
					'#intMapBack': true
				}
			},
			{
				id: '#intMapsAddTitle',
				buttons: {
					'#intMapBack': true,
					'#intMapNext': true
				}
			}
		],
		// class used for hiding elements
		hiddenClass = 'hidden',

		uploadEntryPoint = '/wikia.php?controller=WikiaInteractiveMaps&method=uploadMap&format=json',
		// modal configuration
		modalConfig = {
			vars: {
				id: 'intMapCreateMapModal',
				classes: ['intMapCreateMapModal'],
				size: 'medium',
				content: '',
				title: $.msg('wikia-interactive-maps-create-map-header'),
				buttons: [
					{
						vars: {
							value: $.msg('wikia-interactive-maps-create-map-next-btn'),
							classes: ['normal', 'primary'],
							id: 'intMapNext',
							data: [
								{
									key: 'event',
									value: 'next'
								}
							]
						}
					},
					{
						vars: {
							value:  $.msg('wikia-interactive-maps-create-map-back-btn'),
							id: 'intMapBack',
							data: [
								{
									key: 'event',
									value: 'back'
								}
							]
						}
					}
				]
			}
		},
		// placeholder for mustache templates
		templates,
		// data for mustache template
		templateData = {
			mapType: [
				{
					type: 'Geo',
					name: $.msg('wikia-interactive-maps-create-map-choose-type-geo')
				},
				{
					type: 'Custom',
					name: $.msg('wikia-interactive-maps-create-map-choose-type-custom')
				}
			],
			uploadFileBtn: $.msg('wikia-interactive-maps-create-map-upload-file')
		},
		// modal event handlers
		modalEvents = {
			next: nextStep,
			back: previousStep,
			intMapCustom: function() {
				switchStep(1);
			},
			intMapGeo: function() {
				switchStep(2);
			}
		};

	// TODO: figure out where is better place to place it and move it there
	body.on('change', '#intMapUpload', function(event) {
		uploadMapImage($(event.target).parent().get(0));
	});


	/**
	 * @desc Entry point for create map modal
	 * @param {array} tmpl - mustache templates
	 */

	function init(tmpl) {
		// set reference to mustache templates
		templates = tmpl;

		renderModalContentMarkup(modalConfig, templates[0], templateData);

		createModal(modalConfig, function() {
			// cache modal sections and buttons
			modalSections = createMapModal.$content.children();
			modalButtons = createMapModal.$element.find('.buttons').children();

			bindEvents(createMapModal, modalEvents);
			// set initial create map step
			switchStep(0);
			createMapModal.show();
		});
	}

	/**
	 * @desc renders HTML markup and adds it to modal config
	 * @param {object} modalConfig - modal config
	 * @param {string} template - mustache template
	 * @param {object} data - mustache template data
	 */

	function renderModalContentMarkup(modalConfig, template, data) {
		modalConfig.vars.content = mustache.render(template, data);
	}

	/**
	 * @desc creates modal component
	 * @param {object} config - modal config
	 * @param {function} cb - callback function called after creating modal
	 */

	function createModal(config, cb) {
		require(['wikia.ui.factory'], function (uiFactory) {
			uiFactory.init(['modal']).then(function (uiModal) {
				uiModal.createComponent(config, function (modal) {
					// set reference to modal component
					createMapModal = modal;

					cb();
				});
			});
		});
	}

	/**
	 * @desc binds events to modal
	 * @param {object} modal - instance of modal component
	 * @param {object} events - events to be bind to the modal
	 */

	function bindEvents(modal, events) {
		Object.keys(events).forEach(function(event) {
			modal.bind(event, events[event]);
		});
	}

	/**
	 * @desc switches to the next step in create map flow
	 */

	function nextStep() {
		switchStep(currentStep + 1);
	}

	/**
	 * @desc switches to the previous step in create map flow
	 */

	function previousStep() {
		switchStep(lastStep);
	}

	/**
	 * @desc switches to the given step in create map flow
	 * @param {number} index - step index
	 */

	function switchStep(index) {
		setStep(index);
		showStepContent(index);
		showStepModalButtons(index);
	}

	/**
	 * @desc sets current step in create map flow
	 * @param {number} index - step index
	 */

	function setStep(index) {
		lastStep = currentStep;
		currentStep = index;
	}

	/**
	 * @desc shows step content
	 * @param {number} index - step index
	 */

	function showStepContent(index) {
		var id = steps[index].id;

		modalSections.addClass(hiddenClass);
		modalSections.filter(id).removeClass(hiddenClass);
	}

	/**
	 * @desc shows step buttons
	 * @param {number} index - step index
	 */

	function showStepModalButtons(index) {
		var buttons = Object.keys(steps[index].buttons || {});

		modalButtons.addClass(hiddenClass);

		buttons.forEach(function(id) {
			modalButtons.filter(id).removeClass(hiddenClass);
		});
	}

	/**
	 * @desc Sends and AJAX request to upload map image
	 * @param {object} form
	 */

	function uploadMapImage(form) {
		$.ajax({
			contentType: false,
			data: new FormData(form),
			processData: false,
			type: 'POST',
			url: w.wgScriptPath + uploadEntryPoint,
			success: function(response) {
				console.log(response);
				var data = response.results;

				if (data && data.isGood) {
					preparePreviewStep(data);
					switchStep(2);
				} else {
					handleUploadErrors(response);
				}
			},
			error: function(response) {
				handleUploadErrors(response);
			}
		});
	}

	/**
	 * @desc prepares image preview and  data for requests to int map service
	 * @param {object} data - params needed to display image preview and prepare data for requests to int map service
	 */

	function preparePreviewStep(data) {
		var templateData = {
				titlePlaceholder: $.msg('wikia-interactive-maps-create-map-title-placeholder'),
				orgImage: data.fileUrl,
				tileSetId: data.tileSetId,
				thumbnailUrl: data.fileThumbUrl,
				userName: w.wgUserName
			};

		$(steps[2].id).html(mustache.render(templates[1], templateData));
	}

	/**
	 * @desc Handles upload errors&exceptions
	 * @param {object} response
	 */

	function handleUploadErrors( response ) {
		// TODO: handle errors (MOB-1626)
	}

	return {
		init: init
	};
});

