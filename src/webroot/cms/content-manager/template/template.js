Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Need to copy only some functions from Page action
	var DEFINITION = Manager.getAction('Page');
	
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
		deleteTemplate: DEFINITION.deletePage,
		
		
		/**
		 * Duplicate template
		 * 
		 * @param {Object} post_data Post Data
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		duplicateTemplate: DEFINITION.duplicatePage,
		
		/**
		 * Duplicate template
		 * 
		 * @param {Number} template_id Template ID
		 * @param {String} locale Current locale
		 * @param {Function} callback Callback function, optional
		 * @param {Object} context Callback function context, optional
		 */
		createTemplateLocalization: DEFINITION.createPageLocalization,
		
		/**
		 * Create new template and returns page data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		createTemplate: DEFINITION.createPage,
		
		/**
		 * Update template data and returns new template data to callback
		 * 
		 * @param {Object} data Page data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		updateTemplate: DEFINITION.updatePage,
		
		
		
		/**
		 * Unlock template, same is automatically done in publish
		 */
		unlockTemplate: DEFINITION.unlockPage,
		
		/**
		 * Lock page, if page is already locked show message
		 */
		lockTemplate: DEFINITION.lockPage,
		
		/**
		 * On template lock request success start editing
		 *
		 * @param {Object} data Response data
		 * @param {Boolean} status Response status
		 */
		onLockResponse: DEFINITION.onLockResponse,
		
		/**
		 * Publish template
		 */
		publishTemplate: DEFINITION.publishPage,
		
		
		/**
		 * Returns template data if template is loaded, otherwise null
		 * 
		 * @return Template data
		 * @type {Object}
		 */
		getPageData: DEFINITION.getPageData,
		
		/**
		 * Returns true if currently edited page is not template
		 *
		 * @return True if editing page not template
		 * @type {Boolean}
		 */
		isPage: DEFINITION.isPage,
		
		/**
		 * Returns true if currently edited page is template
		 *
		 * @return True if editing template
		 * @type {Boolean}
		 */
		isTemplate: DEFINITION.isTemplate,
		
		/**
		 * Returns 'page' is currently editing page or 'template' if editing template
		 *
		 * @return 'page' if editing page, otherwise 'template'
		 * @type {String}
		 */
		getType: DEFINITION.getType
	});
	
});
