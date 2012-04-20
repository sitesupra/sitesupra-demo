//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	'supra.slideshow',
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
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
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['PageToolbar', 'PageButtons'],
		
		
		
		/**
		 * Slideshow object
		 * @type {Object}
		 * @private
		 */
		slideshow: null,
		
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//On page unload destroy everything
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			//Create slideshow
			this.slideshow = new Supra.Slideshow({
				'srcNode': this.one('.design-slideshow')
			});
			this.slideshow.render();
			this.one().after('contentResize', this.slideshow.syncUI, this.slideshow);
		},
		
		/**
		 * Update slideshow
		 */
		sync: function (e) {
			this.slideshow.syncUI();
		},
		
		/**
		 * Show slide
		 */
		slide: function (slide) {
			this.slideshow.set('slide', slide);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Manager.executeAction('DesignList');
		}
	});
	
});