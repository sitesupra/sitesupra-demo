YUI().add('supra.htmleditor-data', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Unique id generator
		 */
		dataNodeUID: 1,
		
		data: {},
		
		/**
		 * Returns all data
		 * 
		 * @return Data
		 * @type {Object}
		 */
		getAllData: function () {
			return this.data;
		},
		
		/**
		 * Returns list of all used Google API fonts
		 * 
		 * @return Fonts list
		 * @type {Array}
		 */
		getUsedFonts: function () {
			var plugin = this.getPlugin('fonts');
			if (plugin) {
				return plugin.getUsedFonts();
			} else {
				return [];
			}
		},
		
		/**
		 * Returns all data processed for saving
		 * 
		 * @return Data
		 * @type {Object}
		 */
		getProcessedData: function () {
			//Data has to be deep cloned to avoid overwriting values
			var data = Supra.mix({}, this.data, true),
				type = null,
				plugins = this.getAllPlugins(),
				plugin = null;
			
			for(var id in data) {
				type = data[id].type;
				if (type in plugins) {
					plugin = plugins[type];
					if (plugin.processData) {
						data[id] = plugin.processData(id, data[id]);
					}
				}
			}
			
			return data;
		},
		
		/**
		 * Returns all data encoded as JSON string
		 * 
		 * @return Data encoded as JSON string
		 * @type {String}
		 */
		getAllDataAsString: function () {
			return Y.JSON.stringify(this.data);
		},
		
		/**
		 * Set all content data
		 * 
		 * @param {Object} data
		 */
		setAllData: function (data) {
			if (typeof data == 'string') {
				try {
					data = Y.JSON.parse(data);
				} catch (e) {
					data = {};
				}
			}
			
			if (!Y.Lang.isObject(data)) {
				data = {};
			}
			
			this.data = data;
		},
		
		/**
		 * Associate data with node 
		 * 
		 * @param {Object} node
		 * @param {Object} data
		 * @param {Boolean} silent Doesn't trigger change if value is true, default is false
		 */
		setData: function (node, data, silent) {
			var id;
			if (typeof node == 'string') {
				id = node;
				node = Y.Node(this.get('doc')).one('#' + node);
			} else {
				node = new Y.Node(node);
				id = node.getAttribute('id');
			}
			
			if (id.indexOf('su') !== 0) {
				id = this.generateDataUID();
				node.setAttribute('id', id);
			}
			
			this.data[id] = data;
			
			//Data was changed, update state
			if (!silent) {
				this._changed();
			}
		},
		
		/**
		 * Returns data which is associated with node or id
		 * 
		 * @param {Object} node Node or ID
		 * @return Data
		 * @type {Object}
		 */
		getData: function (node) {
			if (typeof node == 'string' || typeof node == 'number') {
				var id = node;
			} else {
				node = new Y.Node(node);
				var id = node.getAttribute('id');	
			}
			
			if (!id || !(id in this.data)) return null;
			return this.data[id];
		},
		
		/**
		 * Removes data
		 * 
		 * @param {Object} node
		 * @return True if data was removed otherwise false
		 * @type {Boolean}
		 */
		removeData: function (node) {
			if (typeof node == 'string') {
				var id = node;
			} else {
				node = new Y.Node(node);
				var id = node.getAttribute('id');	
			}
			
			if (!id || !(id in this.data)) return false;
			delete(this.data[id]);
			return true;
		},
		
		/**
		 * Returns unique id, which is not used is page yet
		 * 
		 * @return Unique ID
		 * @type {String}
		 */
		generateDataUID: function () {
			var id = 'su' + this.dataNodeUID++,
				srcNode = this.get('srcNode');
			
			while(srcNode.one('#' + id)) {
				id = 'su' + this.dataNodeUID++;
			}
			
			return id;
		},
		
		/**
		 * Removes data which is associated with nodes, which are not
		 * in content anymore
		 */
		removeExpiredData: function () {
			var data = this.data,
				id,
				srcNode = this.get('srcNode');
			
			for(id in data) {
				if (!srcNode.one('#' + id)) {
					delete(data[id]);
				}
			}
		}
		
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});