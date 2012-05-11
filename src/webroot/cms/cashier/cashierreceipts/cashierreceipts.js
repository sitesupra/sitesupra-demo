//Invoke strict mode
"use strict";

Supra(function (Y) {
	
	//Shortcuts
	var Manager	= Supra.Manager;
	var Action	= Manager.Action;
	var NAME	= 'CashierReceipts';
	
	//When Cashier is closed close also this one
	Supra.Manager.getAction('Cashier').addChildAction(NAME);
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: NAME,
		
		/**
		 * Placeholder node
		 * @type {Object}
		 * @private
		 */
		PLACE_HOLDER: Supra.Manager.getAction('Cashier').getSlide(NAME),
		
		
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.getPlaceHolder().append(Y.Node.create('<div>' + NAME + '</div>'));
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.show();
			
			Supra.Manager.getAction('Cashier').setSlide(this.NAME);
		}
	});
	
});