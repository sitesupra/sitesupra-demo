//Invoke strict mode
"use strict";

SU(function (Y) {

	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	/**
	 * Action for retrieving all blocks
	 * or block information
	 */
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'Blocks',
		
		/**
		 * Action doesn't have a stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have a template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		
		/**
		 * Block data
		 * @type {Object}
		 */
		data: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 */
		callback: null,
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			//Load data
			var url = this.getDataPath();
			
			Supra.io(url, {
				'on': {
					'success': function (data) {
						this.data = {};
						
						for(var i=0,ii=data.length; i<ii; i++) {
							var block = data[i];
							this.data[block.id] = block;
						}
						
						if (Y.Lang.isFunction(this.callback)) {
							this.callback(this.data);
							this.callback = null;
						}
					}
				}
			}, this);
		},
		
		/**
		 * Returns all block data
		 * 
		 * @return All data
		 * @type {Object}
		 */
		getAllBlocks: function () {
			return this.data;
		},
		
		/**
		 * Returns block by Id
		 * 
		 * @param {String} id Block ID
		 * @return Block properties
		 * @type {Object}
		 */
		getBlock: function (id) {
			var data = (id in this.data ? this.data[id] : null);
			
			//When user will be editing template, then will need possibility to lock blocks;
			//add 'locked' property to property list;
			//input will be hidden and disabled if current page is not template (see plugin-properties.js)
			data = data || {'classname': '', 'type': id, 'properties': [], 'title': ''};
			data.properties = data.properties || [];
			
			data.properties.push({
				'id': '__locked__',
				'type': 'Checkbox',
				'label': 'Global block'
			});
			
			return data;
		},
		
		/**
		 * Execute
		 *
		 * @param {Function} callback Callback function
		 */
		execute: function (callback) {
			this.callback = callback;
		}
	});
	
});