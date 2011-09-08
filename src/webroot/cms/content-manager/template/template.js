//Invoke strict mode
"use strict";

Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Need to copy only some functions from Page action
	var DEFINTION = Manager.getAction('Page');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Template',
		
		/**
		 * Delete template
		 *
		 * @param {Number} template_id Template ID
		 * @param {String} locale Current locale
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		deleteTemplate: DEFINTION.deletePage,
		
		/**
		 * Create new template and returns page data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		createTemplate: DEFINTION.createPage,
		
		/**
		 * Update template data and returns new template data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		updateTemplate: DEFINTION.updatePage
	});
	
});
