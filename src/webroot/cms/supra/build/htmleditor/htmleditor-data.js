YUI().add('supra.htmleditor-data', function (Y) {
	
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
			this.removeExpiredData();
			return this.data;
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
			
			this.data = data;
		},
		
		/**
		 * Associate data with node 
		 * 
		 * @param {Object} node
		 * @param {Object} data
		 */
		setData: function (node, data) {
			var id;
			if (typeof node == 'string') {
				id = node;
			} else {
				node = new Y.Node(node);
				id = node.getAttribute('id');
			}
			
			if (id.indexOf('su') !== 0) {
				id = this.generateDataUID();
				node.setAttribute('id', id);
			}
			
			this.data[id] = data;
		},
		
		/**
		 * Returns data which is associated with node or id
		 * 
		 * @param {Object} node Node or ID
		 * @return Data
		 * @type {Object}
		 */
		getData: function (node) {
			if (typeof node == 'string') {
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
				id = this.dataNodeUID++;
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
				if (!srcNode.one('#' + id).size()) {
					delete(data[id]);
				}
			}
		}
		
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});