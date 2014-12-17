/**
 * Main action, initiates all other actions
 */
Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
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
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			this.addChildAction('Crud');
			
			//Show loading icon
			Y.one('body').addClass('loading');
			
			//On page unload destroy everything
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			Manager.loadActions(['Crud']);
			
			Manager.getAction('Crud').after('execute', function () {
				//Hide loading icon
				Y.one('body').removeClass('loading');
			});
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			var providerId = Supra.data.get(['crudManager', 'providerId']);
			Manager.executeAction('Crud', providerId);
		}
	});
	
});
