//Invoke strict mode
"use strict";

/**
 * Main action, initiates all other actions
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
		DEPENDANCIES: ['Header', 'PageToolbar', 'PageButtons'],
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			this.addChildAction('Applications');
			
			//Show loading icon
			Y.one('body').addClass('loading');
			
			//On page unload destroy everything
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			Manager.loadActions(['Applications']);
			
			Manager.getAction('Applications').after('execute', function () {
				//Hide loading icon
				Y.one('body').removeClass('loading');
			});
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			Manager.executeAction('Applications');
		}
	});
	
});