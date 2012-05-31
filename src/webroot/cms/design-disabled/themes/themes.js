//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
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
		NAME: 'Themes',
		
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
		 * Design data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		
		
		/**
		 * Set place holder node
		 */
		create: function () {
			this.set('placeHolderNode', Y.one('#designThemes'));
		},
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Load data
			//Supra.io(this.getDataPath('dev/list'), this.setup, this);
			
			this.setup();
		},
		
		/**
		 * Set up
		 */
		setup: function (data, status) {
			//Update main slideshow
			Manager.getAction('Root').sync();
		},
		
		
		/*
		 * ------------------------------- API --------------------------------
		 */
		
		
		/**
		 * Returns design data
		 * 
		 * @return Design data
		 * @type {Object}
		 */
		getData: function () {
			return this.data;
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Manager.getAction('Root').slide('designThemes');
		}
	});
	
});