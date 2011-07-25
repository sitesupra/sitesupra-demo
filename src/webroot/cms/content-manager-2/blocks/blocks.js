//Invoke strict mode
"use strict";

SU(function (Y) {

	//Shortcut
	var Action = SU.Manager.Action;
	
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
		 * No template for toolbar
		 * @type {Boolean}
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Block data
		 */
		data: {},
		
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
					}
				}
			}, this);
		},
		
		/**
		 * Returns block by Id
		 * 
		 * @param {String} id Block ID
		 * @return Block properties
		 * @type {Object}
		 */
		getBlock: function (id) {
			return (id in this.data ? this.data[id] : null);
		}
	});
	
});