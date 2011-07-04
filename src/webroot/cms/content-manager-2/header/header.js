//Invoke strict mode
"use strict";

/**
 * Header action
 */
Supra('supra.header', function (Y) {

	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Header',
		
		/**
		 * Change place holder to document body
		 * @type {HTMLElement}
		 * @private
		 */
		PLACE_HOLDER: new Y.Node(document.body),
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			
			//Move node the begining of document
			var body = new Y.Node(document.body);
			body.prepend(this.getContainer());
			
			this.header = new Supra.Header({
				'srcNode': this.getContainer()
			});
			
			//Widget functions should be available on Action
			//Supra.Manager.Header.addItem, etc.
			this.importMethods(this.header, ['addItem', 'removeItem', 'getItem']);
			
			//On item click fire event on Action
			this.header.on('itemClick', function (event) {
				this.fire(event.id + 'Click', event);
				this.fire('itemClick', event);
			}, this);
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			this.header.render();
		},
		
		/**
		 * Add item to the header
		 * Call to this method will actually call widgets addItem method
		 * 
		 * @param {String} item_id Item ID
		 * @param {Object} item_config Item configuration
		 */
		addItem: function (item_id, item_config) {},
		
		/**
		 * Remove item from the header
		 * Call to this method will actually call widgets removeItem method
		 * 
		 * @param {String} item_id Item ID
		 */
		removeItem: function (item_id) {},
		
		/**
		 * Returns item instance Supra.HeaderItem
		 * Call to this method will actually call widgets getItem method
		 * 
		 * @param {String} item_id Item ID
		 * @return Item instance
		 * @type {Object}
		 */
		getItem: function (item_id) {}
	});
	
});