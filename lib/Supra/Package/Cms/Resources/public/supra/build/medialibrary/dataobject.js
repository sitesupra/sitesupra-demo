/**
 * Media library data
 * Handle data loading, saving, searching
 */
YUI.add('supra.medialibrary-data-object', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Manager = Supra.Manager;
	
	function Data (config) {
		var attrs = {
			//Save request URI
			'saveURI': {value: ''},
			
			//Insert request URI
			'insertURI': {value: ''},
			
			//Delete request URI
			'deleteURI': {value: ''},
			
			//Request URI for viewing item
			'viewURI': {value: ''},
			
			//Request URI for item lists
			'listURI': {value: ''},
			
			//Requets URI for move
			'moveURI': {value: ''},
			
			//Requets URI for move
			'downloadURI': {value: ''},
			
			//Request params
			'requestParams': {value: {}},
			
			//Function to test data completenes
			'completeTest': {value: true}
		};
		
		// Make sure all config properties has attribute
		for (var key in config) {
			if (!attrs[key]) {
				attrs[key] = {value: null};
			}
		}
		
		this.data = {};
		this.dataIndexed = {};
		this.dataLoaded = {};		
		this.dataTimestamps = {};
		this.deferreds = {};
		this.addAttrs(attrs, config || {});
		
		this.cache = {
			'add': Y.bind(this.addCache, this),
			'save': Y.bind(this.saveCache, this),
			'remove': Y.bind(this.removeCache, this),
			
			'one': Y.bind(this.oneFromCache, this),
			'all': Y.bind(this.allFromCache, this),
			'any': Y.bind(this.anyFromCache, this),
			'has': Y.bind(this.has, this),
			
			'purge': Y.bind(this.purge, this),
			'timestamp': Y.bind(this.timestamp, this)
		};
	}
	
	Data.prototype = {
		
		/**
		 * Folder structures, which doesn't have a parent
		 * or parent is not loaded
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * Data indexed by item ID
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
		 * Data last change timestamps
		 * Folder timestamp also changes when direct children are added/removed/changed
		 * @type {Object}
		 * @private
		 */
		dataTimestamps: null,
		
		/**
		 * List of deferreds for saving, loading, etc.
		 * @type {Object}
		 * @private
		 */
		deferreds: null,
		
		/**
		 * Unique ID counter
		 * @type {Number}
		 * @private
		 */
		uid: 0,
		
		
		/* ----------- Public data API --------- */
		
		
		/**
		 * Add item
		 * Item is added to cache immediatelly, if insert fails
		 * then it's removed
		 * 
		 * @param {Object} item Item data which will be added
		 * @returns {Object} Deferred object
		 */
		add: function (item) {
			if (item.id && item.id in this.dataIndexed) {
				return this.save(item);
			}
			
			var deferred = new Supra.Deferred(),
				uri = this.get('insertURI'),
				post = null;
			
			item = Supra.mix({}, item);
			post = Supra.mix({}, item);
			
			this.addCache(item);
			this.deferreds[item.id] = deferred;
			
			Supra.io(uri, {'data': post, 'method': 'post'})
				.done(function (id) {
					this.saveCacheId(item.id, id)
					deferred.resolve([this.one(id)]);
				}, this)
				.fail(function () {
					this.removeCache(item.id);
					deferred.reject();
				}, this)
				.always(function () {
					delete(this.deferreds[item.id]);
				}, this);
			
			return deferred.promise();
		},
		
		/**
		 * Update item
		 * Cache data is updated immediatelly, if update fails then
		 * cache is data reverted 
		 * 
		 * @param {Object} item Item data which needs to be updated
		 * @param {Object} deferred Deferred object to use
		 * @returns {Object} Deferred object
		 */
		save: function (item, deferred) {
			var deferreds = this.deferreds,
				deferred = deferred || new Supra.Deferred(),
				changes = null,
				id = item.id,
				uri = '';
			
			if (deferreds[id]) {
				if (deferreds[id] === deferred) return deferred.promise();
				
				// Return deferred for this change, not the one already running
				deferreds[id].always(function () {
					this.save(item, deferred);
				}, this);
				return deferred.promise();
			}
			
			changes = this.saveCache(item);
			
			if (changes) {
				deferreds[id] = deferred;
				uri = this.get('saveURI');
				
				if ('parent' in changes) {
					uri = this.get('moveURI');
					item = {
						'id': item.id,
						'parent_id': item.parent
					};
				}
				
				Supra.io(uri, {
					'data': item,
					'method': 'post'
				})
					.done(function (item) {
						if (typeof item === 'object') {
							item = Supra.mix({}, this.cache.one(id), item);
							this.saveCache(item);
						}
						
						this.dataTimestamps[id] = +new Date();
						delete(deferreds[id]);
						deferred.resolve([this.cache.one(id)]);
					}, this)
					.fail(function () {
						// Revert changes
						this.saveCache(changes);
						delete(deferreds[id]);
						deferred.reject([changes]);
					}, this);
				
			} else {
				deferred.resolve([this.cache.one(id)]);
			}
			
			return deferred.promise();
		},
		
		/**
		 * Remove item
		 * 
		 * @param {String} id Item ID
		 * @param {Object} Deferred object to use, optional
		 * @returns {Object} Deferred object
		 */
		remove: function (id, deferred) {
			var deferreds = this.deferreds,
				deferred = deferred || new Supra.Deferred(),
				item = null;
			
			if (deferreds[id]) {
				if (deferreds[id] === deferred) return deferred.promise();
				
				// Request already in progress, wait till it ends
				this.deferreds[id].always(function () {
					this.remove(id, deferred);
				}, this);
				
				return deferred.promise();
			}
			
			item = this.removeCache(id);
			if (item) {
				this.deferreds[id] = deferred;
				
				Supra.io(this.get('deleteURI'), {
					'data': {
						'id': item.id
					},
					'method': 'post'
				})
					.done(function (id) {
						this.dataTimestamps[id] = +new Date();
						deferred.resolve([item]);
					}, this)
					.fail(function () {
						// Revert changes
						this.addCache(item);
						deferred.reject(item);
					}, this)
					.always(function () {
						delete(this.deferreds[item.id]);
					}, this);
				
			} else {
				deferred.reject();
			}
			
			return deferred.promise();
		},
		
		/**
		 * Returns 1 if there is data for item, 2 if data is complete
		 * and 0 if there are no data for item 
		 *  
		 * @param {String} id Item ID
		 * @returns {Number} 0 - if there are no data, 1 - if there is data and 2 - data is complete
		 */
		has: function (id) {
			var data = this.dataIndexed[id],
				loaded = this.dataLoaded[id],
				test = this.get('completeTest');
			
			if (data) {
				if (data.type === Supra.DataObject.TYPE_FOLDER) {
					if (loaded && loaded.totalRecords !== null) {
						return loaded.loadedCount >= loaded.totalRecords ? 2 : 1;
					} else if (data.children && data.children.length >= data.children_count) {
						return 2;
					} else {
						return 1;
					}
				}
				
				if (typeof test === 'function') {
					return test(data) ? 2 : 1;
				} else {
					return 2;
				}
			} else if (this.data[id]) {
				var loaded = this.dataLoaded[id];
				if (loaded) {
					return loaded.loadedCount >= loaded.totalRecords ? 2 : 1;
				} else {
					// No info about total records, assume we have all
					return 2;
				}
			} else {
				return 0;
			}
		},
		
		/**
		 * Returns item, from cache or loads from server
		 * 
		 * @param {String} id Item ID
		 * @param {Boolean} full Returns full data if image or file
		 * @param {Object} deferred Deferred object to use
		 * @returns {Object} Deferred oject
		 */
		one: function (id, full, deferred) {
			var deferreds = this.deferreds,
				deferred = deferred || new Supra.Deferred(),
				has = this.has(id);
			
			if (has === 2 || (has === 1 && !full)) {
				deferred.resolve([this.dataIndexed[id]]);
			} else {
				//No data or not complete data
				if (deferreds[id]) {
					deferreds[id].always(function () {
						this.one(id, full, deferred);
					}, this);
					return deferred.promise();
				} else {
					deferreds[id] = deferred;
				}
				
				var uri = this.get('viewURI'),
					data = {'id': id};
				
				Supra.mix(data, this.get('requestParams') || {});
				
				Supra.io(uri, {'data': data})
					.done(function (response) {
						if (!response || !response.records || !response.records.length) {
							deferred.reject();
							return;
						}
						
						var item = response.records[0];
						
						this.saveCache(item);
						this._resolveStructures();
						this.dataTimestamps[item.id] = +new Date();
						delete(deferreds[id]);
						
						deferred.resolve([item]);
					}, this)
					.fail(function () {
						delete(deferreds[id]);
						
						deferred.reject();
					}, this);
			}
			
			return deferred.promise();
		},
		
		/**
		 * Returns all items by parent
		 * 
		 * @param {String} parent Parent ID
		 * @param {Number} offset Result offset, optional
		 * @param {Number} count Number of results to return, optional
		 * @returns {Object} Deferred object
		 */
		all: function (parent, offset, count) {
			var deferred = new Supra.Deferred(),
				children = null,
				data     = this.data,
				indexed  = this.dataIndexed,
				loaded   = this.dataLoaded,
				deferred = new Supra.Deferred(),
				
				loadOffset = offset,
				loadCount  = count;
			
			parent = parent || 0;
			
			if (!(parent in loaded)) {
				loaded[parent] = {
					'loadedCount': 0,
					'totalRecords': null // we don't know how many are there
				};
			}
			
			loaded = loaded[parent];
			
			if (indexed[parent]) {
				children = indexed[parent].children;
				loaded.totalRecords = indexed[parent].children_count;
			} else if (data[parent]) {
				children = data[parent];
				if (loaded.totalRecords) {
					loaded.loadedCount = children.length;
					loaded.totalRecords = children.length;
				}
			}
			
			if (!count) {
				// Return all data
				if (loaded.totalRecords !== null && loaded.loadedCount >= loaded.totalRecords) {
					deferred.resolve([children]);
					return deferred;
				}
			} else {
				// Return chunk of data
				var max = Math.max(offset + count, loaded.loadedCount || 0);
				if (loaded.totalRecords) {
					max = Math.min(max, loaded.totalRecords);
				}
				
				if (max <= loaded.loadedCount) {
					var chunk = children.slice(offset, offset + count);
					deferred.resolve([chunk]);
					return deferred;
				}
			}
			
			if (count) {
				// Load data chunk
				loadCount  = count + offset - loaded.loadedCount; 
				loadOffset = loaded.loadedCount;
				
				var params = Supra.mix({
					'id': parent || 0,
					'offset': loadOffset,
					'resultsPerRequest': loadCount
				}, this.get('requestParams') || {});
				
				Supra.io(this.get('listURI'), {
					'data': params
				})
					.done(function (response) {
						var results = null,
							records = response.records;
						
						if (indexed[parent]) {
							indexed[parent].children = (indexed[parent].children || []);
							indexed[parent].children.splice(loadOffset, loadCount, records);
							indexed[parent].children_count = response.totalRecords;
							results = indexed[parent].children.splice(offset, count);
						} else {
							data[parent] = (data[parent] || []);
							data[parent].splice(loadOffset, loadCount, records);
							results = data[parent].splice(offset, count);
						}
						
						for (var i=0,ii=records.length; i<ii; i++) {
							records[i].parent = parent;
							indexed[records[i].id] = records[i];
							this._fire('add:single', records[i]);
						}
						
						this._fire('add:multiple', records);
						
						loaded.loadedCount = loadCount + loadOffset;
						loaded.totalRecords = response.totalRecords;
						
						this._resolveStructures();
						deferred.resolve([results]);
					}, this)
					.fail(function () {
						deferred.reject();
					}, this);
				
			} else {
				
				var params = Supra.mix({
					'id': parent || 0
				}, this.get('requestParams') || {});
				
				// Load all
				Supra.io(this.get('listURI'), {
					'data': params
				})
					.done(function (response) {
						var records = response.records;
						
						if (indexed[parent]) {
							indexed[parent].children = records;
							indexed[parent].children_count = response.totalRecords;
						} else {
							data[parent] = records;
						}
						
						for (var i=0,ii=records.length; i<ii; i++) {
							records[i].parent = parent;
							indexed[records[i].id] = records[i];
							this._fire('add:single', records[i]);
						}
						
						this._fire('add:multiple', records);
						
						loaded.loadedCount = response.totalRecords;
						loaded.totalRecords = response.totalRecords;
						
						this._resolveStructures();
						deferred.resolve([response.records]);
					}, this)
					.fail(function () {
						deferred.reject();
					}, this);
			}
			
			return deferred;
		},
		
		/**
		 * Load folder item list if item is folder, otherwise item itself
		 * 
		 * @param {String} id Item id
		 * @param {Boolean} full Returns full data if image or file
		 * @returns {Object} Deferred object
		 */
		any: function (id, full) {
			if (this.isFolder(id) === false) {
				return this.one(id, full);
			} else {
				return this.all(id);
			}
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
		 * Returns true if item is folder
		 * 
		 * @param {String} id Item ID
		 * @returns {Boolean} True if item is folder, false if not a folder and null if
		 * it's it's unknown
		 */
		isFolder: function (id) {
			var indexed = this.dataIndexed;
			
			if (id in indexed) {
				return indexed[id].type == Supra.DataObject.TYPE_FOLDER;
			} else {
				return null;
			}
		},
		
		
		/* --------- Public cache API -------- */
		
		
		/**
		 * Add item to the cache
		 * 
		 * @param {Object} item Item data which will be added
		 */
		addCache: function (item) {
			var is_new_item = !item.id;
			
			// Make sure we have ID and parent
			item.parent = item.parent || (item.path ? item.path[item.path.length - 1] : 0) || 0;
			item.id = item.id || this.guid();
			
			var parent  = item.parent || 0,
				indexed = this.dataIndexed,
				data    = this.data;
			
			// For new folder set 0 children to prevent unnecessary loading
			if (is_new_item && item.type == Supra.DataObject.TYPE_FOLDER) {
				item.children = [];
				item.children_count = 0;
				
				this.dataLoaded[item.id] = {
					'loadedCount': 0,
					'totalRecords': 0
				};
			}
			
			if (parent in indexed) {
				indexed[parent].children = indexed[parent].children || [];
				indexed[parent].children_count = indexed[parent].children_count || 0;
				indexed[parent].children.push(item);
				indexed[parent].children_count++;
			} else if (parent in data) {
				data[parent].push(item);
			} else {
				data[parent] = [item];
			}
			
			if (this.dataLoaded[parent]) {
				this.dataLoaded[parent].loadedCount++;
				this.dataLoaded[parent].totalRecords++;
			}
			
			indexed[item.id] = item;
			
			this._fire('add', item);
			return item;
		},
		
		/**
		 * Update items cached data
		 * 
		 * @param {Object} item Item data which will be updated
		 * @private
		 */
		saveCache: function (item) {
			var indexed = this.dataIndexed,
				changes = {'id': item.id},
				data    = indexed[item.id],
				key     = null,
				has     = false;
			
			if (!data) {
				indexed[item.id] = data;
				this.dataTimestamps[item.id] = +new Date();
				this._fire('add', item);
				return item;
			}
			
			// Calculate changes
			for (key in item) {
				if (Y.Lang.isArray(item[key])) {
					if (!data[key] || !Y.Lang.isArray(data[key]) || item[key].length != data[key].length) {
						changes[key] = data[key];
						has = true;
					} else if (item[key].join(',') !== data[key].join(',')) {
						changes[key] = data[key];
						has = true;
					}
				} else if (Y.Lang.isPlainObject(item[key])) {
					//@TODO Do comparison, for now it's assumed that they are different
					changes[key] = data[key];
					has = true;
				} else if (!(key in data)) {
					changes[key] = null;
					has = true;
				} else if (item[key] != data[key]) {
					changes[key] = data[key];
					has = true;
				}
				
				// moveCacheItem() will handle parent change
				if (key != 'parent') {
					data[key] = item[key];
				}
			}
			
			// Move?
			if ('parent' in item && item.parent != data.parent) {
				this.moveCacheItem(item.id, item.parent);
				has = true;
			}
			
			if (has) {
				this._fire('change', data, changes);
				return changes;
			} else {
				return null;
			}
		},
		
		/**
		 * Update item ID
		 * 
		 * @param {String} old_id Old ID
		 * @param {String} new_id New ID
		 * @private
		 */
		saveCacheId: function (old_id, new_id) {
			var data = this.data,
				indexed = this.dataIndexed,
				item = indexed[old_id],
				timestamps = this.dataTimestamps;
			
			if (!item) return; // Something went terribly wrong
			
			item.id = new_id;
			indexed[new_id] = item;
			delete(indexed[old_id]);
			
			if (old_id in data) {
				data[new_id] = data[old_id];
				delete(data[old_id]);
				
				for (var i=0,ii=data[new_id].length; i<ii; i++) {
					data[new_id][i].parent = new_id;
				}
			}
			
			timestamps[new_id] = +new Data();
			
			this._fire('change', item, {'id': old_id});
		},
		
		/**
		 * Remove item from the cache
		 * 
		 * @param {String} id Item ID 
		 * @param {Boolean} temporary Remove only temporary, don't change parent totalRecords count
		 */
		removeCache: function (id, temporary) {
			var data = this.data,
				indexed = this.dataIndexed,
				loaded = this.dataLoaded,
				item = indexed[id],
				timestamps = this.dataTimestamps,
				index = null;
			
			if (item) {
				if (item.parent || item.parent === 0) {
					if (item.parent in indexed) {
						var parent = indexed[item.parent],
							index  = parent.children.indexOf(item);
						
						if (temporary !== true) {
							parent.children_count--;
						}
						
						if (index !== -1) {
							parent.children.splice(index, 1);
						}
					}
					if (item.parent in loaded) {
						var parent = loaded[item.parent];
						
						if (temporary !== true) parent.totalRecords--;
						parent.loadedCount = Math.max(parent.loadedCount - 1, 0);
					}
					if (item.parent in data) {
						var item = indexed[id],
							index  = data[item.parent].indexOf(item);
						
						if (index !== -1) {
							data[item.parent].splice(index, 1);
						}
					}
				}
				
				delete(indexed[id]);
				
				if (data[id]) {
					delete(data[id]);
				}
				if (timestamps[id]) {
					delete(timestamps[id]);
				}
				
				if (loaded[id]) {
					loaded[id].loadedCount = 0;
				}
				
				this._fire('remove', item);
			}
			
			return item;
		},
		
		/**
		 * Move item to different parent
		 * 
		 * @param {String} id Item ID
		 * @param {String} parent_id New parent ID
		 * @private
		 */
		moveCacheItem: function (id, parent_id) {
			var indexed    = this.dataIndexed,
				data       = this.data,
				loaded     = this.dataLoaded,
				timestamps = this.dataTimestamps,
				
				item       = indexed[id],
				old_parent = indexed[item.parent],
				new_parent = indexed[parent_id],
				
				temp       = null;
			
			if (old_parent) {
				old_parent.children_count--;
				temp = old_parent.children;
				if (temp && temp.length) {
					for (var i=0,ii=temp.length; i<ii; i++) {
						if (temp[i].id == id) {
							temp.splice(i, 1);
							break;
						}
					}
				}
			} else if (data[item.parent]) {
				temp = data[item.parent];
				for (var i=0,ii=temp.length; i<ii; i++) {
					if (temp[i].id == id) {
						temp.splice(i, 1);
						break;
					}
				}
			}
			
			if (loaded[item.parent]) {
				loaded[item.parent].loadedCount--;
				loaded[item.parent].totalRecords--;
			}
			if (loaded[parent_id]) {
				loaded[parent_id].loadedCount++;
				loaded[parent_id].totalRecords++;
			}
			
			if (new_parent) {
				new_parent.children_count++;
				new_parent.children = (new_parent.children || []);
				new_parent.children.push(item);
			} else if (data[parent_id]) {
				data[parent_id] = data[parent_id] || [];
				data[parent_id].push(item);
			}
			
			item.parent = parent_id;
			timestamps[item.id] = +new Date();
			timestamps[item.parent] = +new Date();
			timestamps[parent_id] = +new Date();
		},
		
		/**
		 * Returns item timestamp
		 * Time when item was last loaded/changed, folder timestamp changes also when
		 * direct children are added/removed/changed
		 * Can be used to determine if UI is in sync with data
		 * 
		 * @param {String} id Item ID
		 * @returns {Number} Timestamp
		 */
		timestamp: function (id) {
			return this.dataTimestamps[id] || (this.dataTimestamps[id] = +new Date());
		},
		
		/**
		 * Returns item directly from cache
		 * 
		 * @param {String} id Item ID
		 * @returns {Object} Item data from cache, if item doesn't exist then null
		 */
		oneFromCache: function (id) {
			if (this.dataIndexed[id]) {
				return this.dataIndexed[id];
			} else {
				return null;
			}
		},
		
		/**
		 * Returns all items from cache by parent
		 * 
		 * @param {String} parent Parent ID
		 * @param {Number} offset Result offset, optional
		 * @param {Number} count Number of results to return, optional
		 * @returns {Object} Deferred object
		 */
		allFromCache: function (parent, offset, count) {
			var indexed = this.dataIndexed,
				data = this.data,
				items = [];
			
			if (indexed[parent]) {
				items = indexed[parent].children || [];
			} else if (data[parent]) {
				items = data[parent];
			}
			
			if (count) {
				return items.slice(offset, offset + count);
			} else {
				return items;
			}
		},
		
		/**
		 * Returns folder item list if item is folder, otherwise item itself
		 * 
		 * @param {String} id Item id
		 * @returns {Object} Array of items or item
		 */
		anyFromCache: function (id) {
			if (this.isFolder(id) === false) {
				return this.oneFromCache(id);
			} else {
				return this.allFromCache(id);
			}
		},
		
		/**
		 * Remove item or folders all children from cache
		 * 
		 * @param {String} id Item Id 
		 */
		purge: function (id) {
			var data = this.data,
				indexed = this.dataIndexed,
				loaded = this.dataLoaded,
				items = data[id] || [],
				i = 0;
			
			if (id in indexed) {
				items = items.concat(indexed[id].children || []);
				
				if (indexed[id].type == Supra.DataObject.TYPE_FOLDER) {
					indexed[id].children = [];
					
					if (loaded[id]) {
						loaded[id].loadedCount = 0;
					}
				} else {
					delete(indexed[id]);
				}
			}
			
			while (i < items.length) {
				items = items.concat(items[i].children || []);
				this.removeCache(items[i].id, true);
				i++;
			}
			
			if (loaded[id]) {
				loaded[id].loadedCount = 0;
			}
			if (data[id]) {
				delete(data[id]);
			}
		},
		
		/**
		 * Returns all parent folder IDs for item
		 * 
		 * @param {String} id File or folder ID
		 * @returns {Array} List of folder IDs
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
		
		/* --------- Private API -------- */
		
		
		/**
		 * Trigger item specific event
		 * 
		 * @param {String} event_name Event name
		 * @param {Object} item Item data
		 * @param {Object} changes Item changes (for change event)
		 */
		_fire: function (event_name, item, changes) {
			this.fire(event_name, {}, item, changes);
			
			if (item.id) {
				this.fire(event_name + ':' + item.id, {}, item, changes);
			}
		},
		
		/**
		 * Try to unite structures
		 * Each structure is a list of items which has same parent and
		 * this parent is not loaded
		 * 
		 * @private
		 */
		_resolveStructures: function () {
			
			var data = this.data,
				indexed = this.dataIndexed,
				timestamps = this.dataTimestamps,
				key = null;
			
			for (key in data) {
				if (key !== 0 && key !== '0') {
					
					if (indexed[key]) {
						if (indexed[key].children && indexed[key].children.length) {
							var target = indexed[key].children,
								source = data[key],
								i = 0,
								ii = source.length,
								k = 0,
								kk = target.length,
								found = false;
							
							for (; i<ii; i++) {
								found = false;
								for (k=0; k<kk; k++) {
									if (target[k] === source[i]) {
										found = true;
										break;
									}
								}
								
								if (!found) {
									timestamps[key] = +new Date();
									indexed[key].children_count++;
									target.push(source[i]);
								}
							}
						} else {
							timestamps[key] = +new Date();
							indexed[key].children_count += data[key].length;
							indexed[key].children = (indexed[key].children || []).join(data[key]);
						}
						
						delete(data[key]);
					}
					
				}
			}
			
		},
		
		/**
		 * Returns unique id
		 * 
		 * @returns {Number} Unique ID
		 * @private
		 */
		guid: function () {
			return 'temp_' + this.uid++;
		}
	
	};
	
	Y.augment(Data, Y.Attribute);
	
	/* Singleton */
	Supra.DataObject = {
		/**
		 * Item type constants
		 * @constant
		 */
		'TYPE_FOLDER': 1,
		
		/**
		 * Data class
		 * @type {Function}
		 * @private
		 */
		'Data': Data,
		
		/**
		 * Data object instance
		 * @type {Object}
		 * @private
		 */
		'instances': {},
		
		/**
		 * Returns Data object instance
		 * 
		 * @param {Object} options Options
		 * @returns {Object} Data object
		 */
		'get': function (options) {
			var id = (typeof options === 'string' ? options : options.id || options.actionName);
			
			if (!(id in this.instances)) {
				var actionName = (typeof options === 'string' ? options : options.actionName || options.id),
					action = Supra.Manager.getAction(actionName),
					prefix = options.dev ? 'dev/' : '';
				
				this.instances[id] = new this.Data({
					'viewURI': Supra.Url.generate('media_library_view'),
					'listURI': Supra.Url.generate('media_library_list'),
					'saveURI': action.getDataPath(prefix + 'save'),
					'moveURI': action.getDataPath(prefix + 'move'),
					'deleteURI': Supra.Url.generate('media_library_delete'),
					'insertURI': Supra.Url.generate('media_library_insert'),
					'imageRotateURI': Supra.Url.generate('media_library_rotate'),
					'imageCropURI': Supra.Url.generate('media_library_crop'),
					'downloadURI': Supra.Url.generate('media_library_download'),
					'completeTest': options.completeTest
				});
			}
			return this.instances[id];
		}
	};
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['attribute', 'array-extras']});