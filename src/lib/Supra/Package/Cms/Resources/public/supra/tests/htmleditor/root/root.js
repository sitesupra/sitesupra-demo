//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	'supra.htmleditor',
	
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
		 * Render 
		 */
		render: function () {
			this.form = new Supra.Form({
				'srcNode': this.one('form')
			});
			this.form.render();
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
		}
	});
	
});