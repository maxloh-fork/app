require(['jquery', 'wikia.window'], function ($, window) {
	function registerPlugin() {
		window.CKEDITOR.plugins.add( 'rte-infobox', {
			init: onEditorInit
		});
	}

	function onEditorInit(editor) {
		editor.addCommand( 'addinfobox', {
			exec: openInfoboxModal
		});
	}

	function openInfoboxBuilder( editor ) {

		window.CKEDITOR.dialog.add( 'infoboxBuilder-dialog', function ( editor ) {
			return {
				title: 'Infobox Builder',
				buttons: [],
				minWidth: 250,
				minHeight: 300,
				contents: [
					{
						id: 'infoboxBuilderDialog',
						label: '',
						title: '',
						elements: [
							{
								type: 'html',
								html: ''
							}
						]
					}
				],

			}
		});

		CKEDITOR.dialog.getCurrent().hide();

		require(['wikia.loader', 'wikia.mustache', 'wikia.location'], function (loader, mustache, location) {
			loader({
				type: loader.MULTI,
				resources: {
					mustache: 'extensions/wikia/PortableInfoboxBuilder/templates/PortableInfoboxBuilderSpecialController_builder.mustache',
					scripts: 'portable_infobox_builder_js'
				}
			}).done(function (assets) {
				var html = mustache.render(assets.mustache[0], {
					iframeUrl: location.origin + '/infobox-builder/',
					classes: 've-ui-infobox-builder'
				});

				loader.processScript(assets.scripts);	
		
			RTE.getInstance().openDialog('infoboxBuilder-dialog');	
				// Content
			//	self.content = new OO.ui.PanelLayout({ padded: false, expanded: true });
			//	self.content.$element.append(html);
			//	self.$body.append(self.content.$element);
			});
		});
	
	}	

	function openInfoboxModal( editor ) {

		buttonStyle = "width:100%;background-image: none;background-color: white; text-align:center; color:black !important; border-radius:0px; border-color: black; border-style: dashed;";

		$.get('/api.php?format=json&action=query&list=allinfoboxes&uselang=' + window.wgContentLanguage)
			.then(function (data) {
				window.CKEDITOR.dialog.add( 'infobox-dialog', function( editor ) {
					return {
						title: 'Select Infobox to Insert',
						buttons: [
						{
							type : 'button',
							id : 'something',
							label : '+ Add Template',
							style : buttonStyle,
							onClick : openInfoboxBuilder
						}
						],
						minWidth: 250,
						minHeight: 300,
						contents: [
							{
								id: 'ckeditorInfoboxPickDialog',
								label: '',
								title: '',
								elements: [
									{
										type: 'html',
										html: getInfoboxListMarkup(data)
									}
								]
							}
						],
						onShow: function () {
							$('.infobox-templates-list').on('click', onInfoboxTemplateChosen);
						},
						onHide: function () {
							$('.infobox-templates-list').off('click', onInfoboxTemplateChosen);
						}
					};
				});

				RTE.getInstance().openDialog('infobox-dialog');
			});
	}

	function getInfoboxListMarkup(data) {
		if (!data || !data.query || !data.query.allinfoboxes || !data.query.allinfoboxes.length) {
			return '';
		}

		var markup = '<ul class="infobox-templates-list" style="height:300px;overflow:hidden;overflow-y:scroll;">';

		data.query.allinfoboxes.forEach(function (infoboxData) {
			markup += '<li><a data-infobox-name="' + infoboxData.title + '">' + infoboxData.title + '</a></li>';
		});

		markup += '</ul>';

		return markup;
	}

	function onInfoboxTemplateChosen(evt) {
		var infoboxName = $(event.target).data('infobox-name');

		if (infoboxName) {
			console.log('did');
			CKEDITOR.dialog.getCurrent().hide();
			RTE.templateEditor.createTemplateEditor(infoboxName);
		}
	}
	registerPlugin();
});
