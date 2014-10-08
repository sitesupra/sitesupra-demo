Supra(function (Y) {
	//Invoke strict mode
	"use strict";

	//Shortcut
	var Manager = Supra.Manager,
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
		 * Group data
		 * @type {Object}
		 */
		groups: {},
		
		/**
		 * Block data
		 * @type {Object}
		 */
		data: {},
		
		/**
		 * Block data
		 * @type {Array}
		 */
		data_array: {},
		
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
			var configuration = Supra.data.get(['page', 'configuration']),
				url = this.getDataPath('blocks-list');
			
			if (configuration) {
				this.processData(configuration);
			} else {
				// @TODO This was used before configuration was set in Supra.data
				// Remove when it's implemented on server-side
				Supra.io(url).done(this.processData, this);
			}
		},
		
		/**
		 * Process configuration data
		 * 
		 * @param {Object} data Configuration data
		 * @private
		 */
		processData: function (data) {
			this.groups = data.groups;
			this.data_array = data.blocks;
			this.data = {};
			
			var block,
				label;
			
			for(var i=0,ii=data.blocks.length; i<ii; i++) {
				block = data.blocks[i];
				
				if (block.classname == 'List') {
					label = Supra.Intl.get(['inputs', 'locked_list']);
				} else {
					label = Supra.Intl.get(['inputs', 'locked']);
				}
				
				this.data[block.id] = block;
				
				//When user will be editing template, then will need possibility to lock blocks;
				//add 'locked' property to property list;
				//input will be hidden and disabled if current page is not template (see plugin-properties.js)
				block.properties = block.properties || [];
				block.property_groups = block.property_groups || [];
				
				block.properties.push({
					'id': '__locked__',
					'type': 'Checkbox',
					'label': label,
					'group': 'advanced'
				});
				block.property_groups.push({
					'id': 'advanced',
					'label': Supra.Intl.get(['inputs', 'advanced']),
					'type': 'sidebar',
					'icon': '/public/cms/supra/img/sidebar/icons/button-advanced.png'
				});
			}
			
			if (Y.Lang.isFunction(this.callback)) {
				this.callback(this.data);
				this.callback = null;
			}
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
		 * Returns all block data 
		 * 
		 * @return All data
		 * @type {Object}
		 */
		getAllBlocksArray: function () {
			return this.data_array;
		},
		
		/**
		 * Returns all block group data
		 * 
		 * @return All block group data
		 * @type {Array}
		 */
		getAllGroups: function () {
			return this.groups;
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
			
			if (!data) {
				data = {
					'classname': '',
					'type': id,
					'properties': [
						//When user will be editing template, then will need possibility to lock blocks;
						//add 'locked' property to property list;
						//input will be hidden and disabled if current page is not template (see plugin-properties.js)
						{
							'id': '__locked__',
							'type': 'Checkbox',
							'label': Supra.Intl.get(['inputs', 'locked']),
							'group': 'advanced'
						}
					],
					'property_groups': [
						{
							'id': 'advanced',
							'label': Supra.Intl.get(['inputs', 'advanced']),
							'type': 'sidebar',
							'icon': null
						}
					],
					'title': ''
				};
			}
			
			return data;
		},
		
		/**
		 * Returns lipsum data for block
		 * 
		 * @param {String} id Block ID
		 * @returns {Object} Lipsum data for block properties
		 */
		getBlockLipsumData: function (id) {
			var block = this.getBlock(id);
			
			if (block) {
				return Supra.Form.lipsum(block.properties);
			} else {
				return {};
			}
		},
		
		/**
		 * Returns default data for block (defaults for inputs)
		 * 
		 * @param {String} id Block ID
		 * @returns {Object} Default data for block properties
		 */
		getBlockDefaultData: function (id) {
			var block = this.getBlock(id),
				properties = block ? block.properties : [],
				data = {},
				i = 0,
				ii = properties.length,
				type = null,
				value = null;
			
			for (; i<ii; i++) {
				value = properties[i].value;
				if (value) {
					type = properties[i].type;
					
					if (type == 'InlineHTML' && typeof value == 'string') {
						data[properties[i].id] = {'data': {}, 'html': value};
					} else {
						data[properties[i].id] = value;
					}
				}
			}
			
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