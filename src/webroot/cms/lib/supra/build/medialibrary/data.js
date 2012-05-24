//Invoke strict mode
"use strict";

/**
 * Media library data
 * Handle data loading, saving, searching
 */
YUI.add('supra.medialibrary-data', function (Y) {
	
	//Properties which always will be loaded
	var REQUIRED_PROPERTIES = ['id', 'type', 'filename' ,'private'];
	
	/**
	 * Media list
	 * Handles data loading, scrolling, selection
	 */
	function Data (config) {
		var attrs = {
			//Save request URI
			'saveURI': {value: ''},
			
			//Folder insert request URI
			'insertURI': {value: ''},
			
			//Delete request URI
			'deleteURI': {value: ''},
			
			//Request URI for file/image
			'viewURI': {value: ''},
			
			//Request URI for folder, file and image list
			'listURI': {value: ''},
			
			//Requets URI for folder move
			'moveURI': {value: ''},
			
			//Request params
			'requestParams': {value: {}}
		};
		
		this.data = [];
		this.dataIndexed = {};
		this.dataLoaded = {};		
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
		 * Loaded data information
		 * @type {Object}
		 * @private
		 */
		dataLoaded: null,
		
		
		/**
		 * Add data to the parent.
		 * Chainable
		 * 
		 * @param {String} parent Parent folder ID
		 * @param {Object} data File or folder data
		 * @param {Boolean} new_data Data was not laoded from server
		 */
		addData: function (parent /* Parent folder ID */, data /* File or folder data */, new_data /* New data */) {
			if (Y.Lang.isArray(data)) {
				if (data.length) {
					//Add each item to the list
					for(var i=0,ii=data.length; i<ii; i++) {
						this.addData(parent, data[i], new_data);
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
						if (new_data) {
							indexed[parent].children_count++;
						}
					} else if (!parent) {
						this.data.push(data);
					}
					
					if (new_data) {
						if (!this.dataLoaded[parent]) {
							if (!this.dataLoaded[parent]) this.dataLoaded[parent] = {'offset': 0, 'totalRecords': 0};
							this.dataLoaded[parent].offset++;
							this.dataLoaded[parent].totalRecords++;
						}
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
		 * @param {String} id File or folder ID
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
		 * @param {String} id File or folder ID
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
		 * @param {String} id File or folder ID
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
		 * @param {String} id File or folder ID
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
		 * @param {String} id File or folder ID
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
				
				this.dataLoaded = {};
				this.dataIndexed = {};
				this.data = [];
			} else {
				var indexed = this.dataIndexed,
					loaded = this.dataLoaded,
					children,
					child_index,
					parent_id,
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
						parent_id = indexed[id].parent;
						if (parent_id && parent_id in indexed) {
							parent = indexed[parent_id];
							parent.children_count--;
							if (parent.children) {
								child_index = Y.Array.lastIndexOf(parent.children, id);
								if (child_index != -1) {
									parent.children.splice(child_index, 1);
									
									if (loaded[parent_id]) {
										loaded[parent_id].offset--;
										loaded[parent_id].totalRecords--;
									}
								}
							}
						}
					}
					
					//Destroy data
					delete(loaded[id]);
					delete(indexed[id]);
					
					//Remove from root folder list (if it's there)
					var data_list = this.data;
					for(var i=0,ii=data_list.length; i<ii; i++) {
						if (data_list[i].id == id) {
							
							loaded[data_list[i].parent].offset--;
							loaded[data_list[i].parent].totalRecords--;
							
							data_list.splice(i, 1);
							
							break;
						}
					}
				}
			}
			
			return this;
		},
		
		/**
		 * Returns if folder is private
		 * 
		 * @param {String} id Folder ID
		 * @return True if folder is private, otherwise false
		 * @type {Boolean}
		 */
		isFolderPrivate: function (id /* Folder ID */) {
			var data = this.getData(id);
			return Number(data && data.type == Data.TYPE_FOLDER && data['private']);
		},
		
		/**
		 * Set folder private/public
		 * Chainable
		 * 
		 * @param {String} id Folder ID
		 * @param {Boolean} state Folder private state
		 * @param {Fucntion} callback Callback function
		 */
		setFolderPrivate: function (id /* Folder ID */, state /* Private state */, callback /* Callback function */) {
			
			var data = this.getData(id),
				state = Number(state);
			
			if (data.type == Data.TYPE_FOLDER && data['private'] != state) {
				var original_state = data['private'];
				
				//Update local data
				data['private'] = state;
				
				//Save
				this.saveData(id, {
					'private': state
				}, function (status, newdata) {
					if (!status) {
						//Revert changes
						data['private'] = original_state;
					}
					
					if (Y.Lang.isFunction(callback)) {
						callback(status, newdata);
					}
				});
			}
			
			return this;
		},
		
		/**
		 * Move folder
		 * 
		 * @param {String} id Folder ID
		 * @param {String} parent New parent ID
		 * @param {Boolean} noRequest Don't sent move request, optional
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback function context
		 */
		moveFolder: function (id /* Folder ID */, parent /* New parent ID */, noRequest /* Don't send move request */, callback /* Callback function */, context /* Callback context */) {
			var data	= this.data,
				indexed	= this.dataIndexed,
				ii		= data.length,
				i		= 0,
				item	= this.getData(id),
				prev	= item.parent;
			
			//Fix arguments
			if (typeof noRequest === 'function') {
				if (typeof callback === 'object') {
					context = callback;
				}
				
				callback = noRequest;
				noRequest = false;
			}
			
			//If already a child, then skip
			if (item.parent === parent) {
				if (callback) {
					callback(null, true);
				}
				return false;
			}
			
			//Check that parent is not actually child of folder
			var top	= indexed[parent].parent;
			while(top && top !== id) {
				top = indexed[top];
			}
			
			if (top === id) {
				//Parent is a child of folder which is moved, not valid operation
				if (callback) {
					callback(null, false);
				}
				return false;
			}
			
			//Remove item from root data 
			for(; i<ii; i++) {
				if (data[i].id == id) {
					data.splice(i, 1);
					break;
				}
			}
			
			//Add item to the root data?
			if (!parent) {
				data.push(item);
			}
			
			//Remove from previous parent children list
			if (prev && indexed[prev].children) {
				var index = Y.Array.indexOf(indexed[prev].children, id);
				if (index != -1) {
					indexed[prev].children.splice(index, 1);
					indexed[prev].children_count -= 1;
				}
			}
			
			//Add to parent data if children are loaded
			if (parent && indexed[parent].children) {
				indexed[parent].children.push(id);
				indexed[parent].children_count += 1;
			}
			
			//Update parent
			item.parent = parent;
			
			//Request
			var uri = this.get('moveURI');
			
			Supra.io(uri, {
				'data': {
					'id': id,
					'parent_id': parent
				},
				'method': 'post',
				'context': context || this,
				'on': {
					'complete': callback
				}
			});
			
			return true;
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
		 * @param {String} id File or folder ID
		 * @param {Array} data Optional list of properties
		 * @param {String} type Request type
		 * @param {Number} offset Data offset
		 * @param {Number} resultsPerRequest results per request
		 */
		loadData: function (id /* File or folder ID */, properties /* List of properties */, type /* Request type */, offset /* Data offset */, resultsPerRequest /* Results per request */) {
			var url = type == 'view' ? this.get('viewURI') : this.get('listURI'),
				data;
			
			if (!url) {
				Y.error('Supra.MediaLibraryData missing requestURI attribute');
				return this
			}
			
			properties = (properties || []).concat(REQUIRED_PROPERTIES);
			properties = Y.Array.unique(properties).join(',');
			
			data = {
				'id': id || 0,
				'properties': properties
			};
			
			if (type != 'view') {
				//To list request add resultsPerRequest and offset
				data.offset = parseInt(offset || 0, 10);
				data.resultsPerRequest = parseInt(resultsPerRequest || 0, 10);
			}
			
			Supra.mix(data, this.get('requestParams') || {});
			
			Supra.io(url, {
				'data': data,
				'context': this,
				'on': {
					'complete': function (data, success) { this.loadComplete(data, id || 0, type); }
				}
			});
			
			return this;
		},
		
		/**
		 * Save data
		 * Chainable
		 * 
		 * @param {String} id File or folder ID
		 * @param {Object} data Data
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context. Optional
		 */
		saveData: function (id /* File or folder ID */, data /* Data */, callback /* Callback function */, context /* Callback context */) {
			var url = this.get('saveURI');
			data = Supra.mix({}, data);
			
			if (id == -1) {
				var url = this.get('insertURI');
				data.type = Data.TYPE_FOLDER;
			} else {
				data.id = id || 0;
			}
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (data, status) {
						if (status) {
							this.afterSaveData(id || 0, data);
						} else if (id == -1) {
							this.removeData(-1, true);
							data = null;
						}
						
						if (Y.Lang.isFunction(callback)) {
							if (context) {
								callback.call(context, status, data, id || 0);
							} else {
								callback(status, data, id || 0);
							}
						}
					}
				}
			});
		},
		
		/**
		 * After data save
		 * 
		 * @param {String} id
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
		 * Delete data
		 * Chainable
		 * 
		 * @param {String} id File or folder ID
		 * @param {Function} callback Callback function
		 */
		saveDeleteData: function (id /* File or folder ID */, callback /* Callback function */) {
			var url = this.get('deleteURI');
			var data = {
				'id': id,
				'action': 'delete'
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'context': this,
				'on': {
					'complete': function (data, status) {
						if (Y.Lang.isFunction(callback) && status) callback(data, id || 0);
					}
				}
			});
		},
		
		/**
		 * Handle data load complete
		 * 
		 * @param {Object} data File or folder data
		 * @param {String} id Folder ID
		 * @param {String} type Request type
		 * @private
		 */
		loadComplete: function (data /* File or folder data */, id /* Folder ID */, type /* Request type */) {
			if (!data || !data.records) {
				Y.log('Supra.MediaLibraryData:loadData error occured while loading data for folder "' + id + '"', 'debug');
				this.fire('load:failure', {'data': null});
				this.fire('load:failure:' + id, {'data': null});
				
				data = {'records': null};
			} else {
				this.addData(id, data.records);
				this.fire('load:success', {'id': id, 'data': data.records});
				this.fire('load:success:' + id, {'id': id, 'data': data.records});
				
				//Update loaded data list
				if (type == 'list') {
					if (!this.dataLoaded[id]) this.dataLoaded[id] = {'offset': 0, 'totalRecords': 0};
					this.dataLoaded[id].offset += data.records.length;
					this.dataLoaded[id].totalRecords = data.totalRecords;
				}
			}
			
			this.fire('load:complete', {'id': id, 'data': data.records});
			this.fire('load:complete:' + id, {'id': id, 'data': data.records});
		},
		
		/**
		 * Destroy data object
		 */
		destroy: function () {
			delete(this.data);
			delete(this.dataIndexed);
			this.data = [];
			this.dataIndexed = {};
			this.dataLoaded = {};
		}
		
	};
	
	Y.augment(Data, Y.Attribute);
	
	Supra.MediaLibraryData = Data;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['attribute', 'array-extras']});