//Invoke strict mode
"use strict";


//Add module definitions
Supra.addModule('website.iframe', {
	path: 'iframe.js',
	requires: ['widget', 'supra.datatype-color']
});


/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	'website.iframe',
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Preview',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		/**
		 * Content template
		 * Since this is very simple template we will not be
		 * using separate file
		 * @type {String}
		 * @private
		 */
		template: '<div></div>',
		
		/**
		 * Iframe object, Supra.DesignIframe instance
		 * @type {Object}
		 * @private
		 */
		iframe: null,
		
		
		
		/**
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('#designPreview'));
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			this.setup();
			
			this.iframe = new Supra.DesignIframe({});
			this.iframe.render(this.one());
			
			this.iframe.on('ready', this.setInitialValues, this);
		},
		
		/**
		 * Set up
		 */
		setup: function (data, status) {
			//Update main slideshow
			Manager.getAction('Root').sync();
		},
		
		/**
		 * Set initial customization values
		 */
		setInitialValues: function () {
			var data = Manager.getAction('DesignOverview').getData(),
				value = null;
			
			//Button color
			value = data.customize.buttonColor;
			this.iframe.updateBackgroundGradient(".btn", value);
		},
		
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			//Iframe src
			var data = Manager.getAction('DesignOverview').getData();
			this.iframe.set('url', data.url);
			
			//Fonts
			var fonts = [],
				key = null;
			
			if (data.fonts) {
				for(key in data.fonts) {
					if (Y.Lang.isArray(data.fonts[key])) {
						fonts = fonts.concat(data.fonts[key]);
					}
				}
			}
			
			this.iframe.set('fonts', fonts);
			
			//Slide
			Manager.getAction('Root').slide('designPreview');
		}
	});
	
});