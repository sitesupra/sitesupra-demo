/**
 * Media library data
 * Handle data loading, saving, searching
 */
YUI.add('supra.medialibrary-data', function (Y) {
	
	//Propeties which will be loaded by default
	var DEFAULT_PROPERTIES = ['id', 'type', 'title'];
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function Data (config) {
		var attrs = {
			//Request URI
			'requestURI': {value: ''},
			
			//Request params
			'requestParams': {value: {}}
		};
		
		this.data = [];
		this.dataIndexed = {};		
		this.addAttrs(attrs, config || {});
	}
	
	Data.TYPE_FOLDER = 1;
	Data.TYPE_IMAGE = 2;
	Data.TYPE_FILE = 3;
	
	Data.prototype = {
		
		/**
		 * First level data
		 * @type {Array}
		 * @private
		 */
		data: null,
		
		/**
		 * Data indexed by file/folder ID
		 * @type {Object}
		 * @private
		 */
		dataIndexed: null,
		
		/**
		 * Add data to the parent.
		 * Chainable
		 * 
		 * @param {Number} parent
		 * @param {Object} data
		 */
		addData: function (parent, data) {
			if (Y.Lang.isArray(data)) {
				//Add each item to the list
				for(var i=0,ii=data.length; i<ii; i++) {
					this.addData(parent, data[i]);
				}
			} else {
				var indexed = this.dataIndexed;
				if (!(data.id in indexed)) {
					indexed[data.id] = data;
					data.parent = parent;
					
					if (parent in indexed) {
						if (!indexed[parent].children) indexed[parent].children = [];
						indexed[parent].children.push(data.id);
					} else if (!parent) {
						this.data.push(data);
					}
				} else {
					Supra.mix(indexed[data.id], data);
				}
			}
			
			return this;
		},
		
		/**
		 * Returns data by ID or null if not found
		 * 
		 * @param {Number} id
		 * @return Folder or file data
		 * @type {Object} 
		 */
		getData: function (id) {
			var indexed = this.dataIndexed;
			return id in indexed ? indexed[id] : null;
		},
		
		/**
		 * Returns list of all children
		 * 
		 * @param {Number} id
		 * @return List of children data
		 * @type {Array}
		 */
		getChildrenData: function (id) {
			var data = this.getData(id),
				children,
				child,
				output = [];
			
			if (!id) {
				return this.data;
			} else if (data && data.children) {
				children = data.children;
				for(var i=0,ii=children.length; i<ii; i++) {
					child = this.getData(children[i]);
					if (child) output.push(child);
				}
			}
			
			return output;
		},
		
		/**
		 * Returns true if item has specific data loaded, otherwise false
		 * 
		 * @param {Number} id Item Id
		 * @param {String} key Data key
		 * @return True if item has data
		 * @type {Boolean}
		 */
		hasData: function (id, key) {
			var indexed = this.dataIndexed;
			return (id in indexed && key in indexed[id] ? true : false);
		},
		
		/**
		 * Remove cached file or folder data or all data
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Boolean} all If true then removes also from parents children list
		 */
		removeData: function (id, all) {
			if (!id) {
				var indexed = this.dataIndexed;
				for(var id in indexed) {
					if (indexed[id].children) delete(indexed[id].children);
					delete(indexed[id]);
				}
				
				delete(this.dataIndexed);
				delete(this.data);
				
				this.dataIndexed = {};
				this.data = [];
			} else {
				var indexed = this.dataIndexed,
					children,
					child_index,
					parent;
				
				if (id in indexed) {
					//Destroy all children
					children = indexed[id].children;
					if (children) {
						delete(indexed[id].children);
						for(var i=0,ii=children.length; i<ii; i++) {
							this.removeData(children[i]);
						}
					}
					
					//Remove from parent children list
					if (all) {
						parent = indexed[id].parent;
						if (parent && parent in indexed) {
							parent = indexed[parent];
							if (parent.children) {
								child_index = Y.Array.lastIndexOf(parent.children, id);
								if (child_index != -1) parent.children.splice(child_index);
							}
						}
					}
					
					//Destroy data
					delete(indexed[id]);
				}
			}
			
			return this;
		},
		
		/**
		 * Set request parameter
		 * Chainable
		 * 
		 * @param {String} name Parameter name
		 * @param {String} value Parameter value
		 */
		setRequestParam: function (name, value) {
			var params = this.get('requestParams') || {};
			params[name] = value;
			this.set('requestParams', params);
			
			return this;
		},
		
		/**
		 * Set request parameters
		 * Chainable
		 * 
		 * @param {Object} data Paramters
		 */
		setRequestParams: function (data) {
			if (Y.Lang.isObject(data)) {
				var params = this.get('requestParams') || {};
				for(var i in data) {
					params[i] = data[i];
				}
				this.set('requestParams', params);
			}
			
			return this;
		},
		
		/**
		 * Load image, file or folder data
		 * Chainable
		 * 
		 * @param {Number} id Item ID
		 * @param {Array} data List of properties which should be loaded
		 */
		loadData: function (id, properties) {
			var url = this.get('requestURI'),
				data;
			
			if (!url) {
				Y.error('Supra.MediaLibraryData missing requestURI attribute');
				return this
			}
			
			properties = Y.Array.unique(properties || []).concat(DEFAULT_PROPERTIES);
			properties = properties.join(',');
			
			data = Supra.mix({
				'id': id || 0,
				'properties': properties
			}, this.get('requestParams') || {}, data || {});
			
			Supra.io(url, {
				'data': data,
				'context': this,
				'on': {
					'success': function (transaction, data) { this._loadComplete(data, id); },
					'failure': function (transaction, data) { this._loadComplete(null, id); }
				}
			});
			
			return this;
		},
		
		/**
		 * Handle data load complete
		 */
		_loadComplete: function (data, id) {
			if (!data || !data.records) {
				Y.error('Supra.MediaLibraryData:loadList error occured while loading data for folder "' + id + '"');
				this.fire('load:failure', {'data': null});
				this.fire('load:failure:' + id, {'data': null});
				return false;
			}
			
			this.addData(id, data.records);
			this.fire('load:success', {'id': id, 'data': data.records});
			this.fire('load:success:' + id, {'id': id, 'data': data.records});
		},
		
		/**
		
		/**
		 * Destroy class
		 */
		destroy: function () {
			delete(this.data);
			delete(this.dataIndexed);
			this.data = [];
			this.dataIndexed = {};
		}
		
	};
	
	Y.augment(Data, Y.Attribute);
	
	Supra.MediaLibraryData = Data;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['attribute', 'array-extras']});