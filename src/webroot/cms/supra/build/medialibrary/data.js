//Invoke strict mode
"use strict";

/**
 * Media library data
 * Handle data loading, saving, searching
 */
YUI.add('supra.medialibrary-data', function (Y) {
	
	//Properties which always will be loaded
	var REQUIRED_PROPERTIES = ['id', 'type', 'title'];
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function Data (config) {
		var attrs = {
			//Save request URI
			'saveURI': {value: ''},
			
			//Request URI for file/image
			'viewURI': {value: ''},
			
			//Request URI for folder, file and image list
			'listURI': {value: ''},
			
			//Request params
			'requestParams': {value: {}}
		};
		
		this.data = [];
		this.dataIndexed = {};		
		this.addAttrs(attrs, config || {});
	}
	
	Data.PARAM_DISPLAY_TYPE = 'type';
	
	Data.TYPE_FOLDER = 1;
	Data.TYPE_IMAGE = 2;
	Data.TYPE_FILE = 3;
	Data.TYPE_TEMP = 4;
	
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
		 * @param {Number} parent Parent folder ID
		 * @param {Object} data File or folder data
		 */
		addData: function (parent /* Parent folder ID */, data /* File or folder data */) {
			if (Y.Lang.isArray(data)) {
				if (data.length) {
					//Add each item to the list
					for(var i=0,ii=data.length; i<ii; i++) {
						this.addData(parent, data[i]);
					}
				} else {
					//Add empty children array to parent
					var indexed = this.dataIndexed;
					if (parent in indexed) {
						if (!indexed[parent].children) indexed[parent].children = [];
					}
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
		 * Returns all parent folder IDs for item
		 * 
		 * @param {Number} id File or folder ID
		 * @return List of folder IDs
		 * @type {Array}
		 */
		getPath: function (id /* File or folder ID */) {
			var indexed = this.dataIndexed,
				ret = [],
				item = id;
			
			while (item && item in indexed) {
				item = indexed[item].parent;
				ret.push(item);
			}
			
			return ret.length ? ret.reverse() : [0];
		},
		
		/**
		 * Returns data by ID or null if not found
		 * 
		 * @param {Number} id File or folder ID
		 * @return Folder or file data
		 * @type {Object} 
		 */
		getData: function (id /* File or folder ID */) {
			var indexed = this.dataIndexed;
			return id in indexed ? indexed[id] : null;
		},
		
		/**
		 * Returns list of all children
		 * 
		 * @param {Number} id File or folder ID
		 * @return List of children data
		 * @type {Array}
		 */
		getChildrenData: function (id /* File or folder ID */) {
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
		 * If key is an array, then file or folder must have data for all specified keys loaded
		 * If key is an object, then also values must match 
		 * 
		 * @param {Number} id File or folder ID
		 * @param {String} key Optional. Data key, array of keys or object of keys and values
		 * @return True if item has all data, otherwise false
		 * @type {Boolean}
		 */
		hasData: function (id /* File or folder ID */, key /* Data key or array of keys */) {
			var indexed = this.dataIndexed,
				data;
			if (!id in indexed) return false;
			data = indexed[id];
			
			if (Y.Lang.isArray(key)) {
				for(var i=0,ii=key.length; i<ii; i++) {
					if (!(key[i] in data)) return false;
				}
				return true;
			} else if (Y.Lang.isObject(key)) {
				for(var i in key) {
					if (!(i in data) || data[i] !== key[i]) return false;
				}
				return true;
			} else if (key) {
				return (key in data ? true : false);
			} else {
				return true;
			}
		},
		
		/**
		 * Remove cached file or folder data or all data
		 * Chainable.
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Boolean} all If true then removes also from parents children list
		 */
		removeData: function (id /* File or folder ID */, all /* Remove all data */) {
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
							parent.children_count--;
							if (parent.children) {
								child_index = Y.Array.lastIndexOf(parent.children, id);
								if (child_index != -1) {
									parent.children.splice(child_index, 1);
								}
							}
						}
					}
					
					//Destroy data
					delete(indexed[id]);
					
					//Remove from root folder list (if it's there)
					var data_list = this.data;
					for(var i=0,ii=data_list.length; i<ii; i++) {
						if (data_list[i].id == id) {
							data_list.splice(i, 1);
							break;
						}
					}
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
		setRequestParam: function (name /* Parameter name */, value /* Parameter value */) {
			var params = this.get('requestParams') || {};
			if (name in params && params[name] === value) return this;
			
			params[name] = value;
			this.set('requestParams', params);
			
			return this;
		},
		
		/**
		 * Set request parameters
		 * Chainable
		 * 
		 * @param {Object} data List of parameters
		 */
		setRequestParams: function (data /* List of parameters */) {
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
		 * @param {Number} id File or folder ID
		 * @param {Array} data Optional list of properties
		 */
		loadData: function (id /* File or folder ID */, properties /* List of properties */, type /* Request type */) {
			var url = type == 'view' ? this.get('viewURI') : this.get('listURI'),
				data;
			
			if (!url) {
				Y.error('Supra.MediaLibraryData missing requestURI attribute');
				return this
			}
			
			properties = (properties || []).concat(REQUIRED_PROPERTIES);
			properties = Y.Array.unique(properties).join(',');
			
			data = Supra.mix({
				'id': id || 0,
				'properties': properties
			}, this.get('requestParams') || {}, data || {});
			
			Supra.io(url, {
				'data': data,
				'context': this,
				'on': {
					'complete': function (data, success) { this.loadComplete(data, id || 0); }
				}
			});
			
			return this;
		},
		
		/**
		 * Save data
		 * Chainable
		 * 
		 * @param {Number} id File or folder ID
		 * @param {Object} data Data
		 */
		saveData: function (id /* File or folder ID */, data /* Data */, callback /* Callback function */) {
			var url = this.get('saveURI');
			data = Supra.mix({}, data);
			
			if (id == -1) {
				data.action = 'insert';
				data.type = Data.TYPE_FOLDER;
			} else {
				data.id = id || 0;
				data.action = 'update';
			}
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (data, status) {
						this.afterSaveData(id || 0, data);
						if (Y.Lang.isFunction(callback)) callback(data, id || 0);
					}
				}
			});
		},
		
		/**
		 * After data save
		 * 
		 * @param {Number} id
		 * @param {Object} data
		 * @private
		 */
		afterSaveData: function (id, data) {
			//After new item is saved update data
			if (id == -1) {
				if (data) {
					var data_item = this.dataIndexed[-1];
					data_item.id = data;
					
					//New folder
					this.dataIndexed[data] = data_item;
					delete(this.dataIndexed[-1]);
					
					//Add to parents children list
					if (data_item.parent && data_item.parent in this.dataIndexed) {
						this.dataIndexed[data_item.parent].children.push(data);
					}
				} else {
					delete(this.dataIndexed[-1]);
				}
			}
		},
		
		/**
		 * Handle data load complete
		 * 
		 * @param {Object} data File or folder data
		 * @param {Number} id Folder ID
		 * @private
		 */
		loadComplete: function (data /* File or folder data */, id /* Folder ID */) {
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
		 * Destroy data object
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