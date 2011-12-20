//Invoke strict mode
"use strict";

/**
 * Form action
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action,
		CRUD = Supra.CRUD;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Form',
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template, Supra.Slideshow and Supra.Form
		 * is used to generate markup
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		
		/**
		 * @constructor
		 */
		initialize: function () {
			//Toolbar buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.save
			}]);
			
		},
		
		/**
		 * Save form
		 * @todo Bug #4418 GJENSIDIGE. CHECK AND FIX CODE IF NEEDED WHEN KASPARS WILL BE BACK.
		 */
		save: function () {
			var form = this.getForm();
			var button = Supra.Manager.getAction('PageButtons').buttons[this.NAME][0];
			
			//Save
			form.save(function (data, status) {
				if (status) {
					//Reload data grid
					CRUD.Providers.getActiveProvider().getDataGrid().reset();
					CRUD.Providers.getActiveProvider().set('mode', 'list');
					button.hide();
				}
				
				form.set('disabled', false);
				button.set('loading', false);
				
			}, this);
			
			//Disable form
			form.set('disabled', true);
			
			//Disable save button
			button.set('loading', true);
		},
		
		/**
		 * Returns form instance
		 * 
		 * @return Form instance
		 * @type {Supra.Form}
		 */
		getForm: function () {
			return CRUD.Providers.getActiveProvider().getForm();
		},
		
		/**
		 * On hide
		 */
		hide: function () {
			//Hide buttons
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Change back to list mode
			CRUD.Providers.getActiveProvider().set('mode', 'list');
		},
		
		/**
		 * Execute action
		 * @todo Bug #4418 GJENSIDIGE. CHECK AND FIX CODE IF NEEDED WHEN KASPARS WILL BE BACK.
		 */
		execute: function () {
			//Show buttons
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			var button = Supra.Manager.getAction('PageButtons').buttons[this.NAME][0];
			button.show();
		}
	});
	
});