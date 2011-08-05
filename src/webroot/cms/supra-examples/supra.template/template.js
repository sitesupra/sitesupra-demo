//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Template',
		
		/**
		 * Load action stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Load action template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		
		/**
		 * Render
		 * 
		 * @private
		 */
		render: function () {
			
			//Load data
			Supra.io(this.getDataPath(), this.renderList, this);
			
		},
		
		/**
		 * Render list
		 */
		renderTemplate: function (data) {
			
			//Render template inside node
			var html = Supra.Template('itemTemplate', data);
			
			this.one('ul').empty();
			this.one('ul').append(html);
			
		},
		
		
		/**
		 * Execute action
		 */
		execute: function () {
			
			this.show();
			
		}
	});
	
});