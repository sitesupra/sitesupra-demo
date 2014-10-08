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
	new Action({
		
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
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		//DEPENDANCIES: ['PageToolbar', 'PageButtons'],
		
		
		
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
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			console.log('Execute Root');
		}
	});
	
});