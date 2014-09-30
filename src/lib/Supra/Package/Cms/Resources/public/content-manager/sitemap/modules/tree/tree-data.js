//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-data', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	function TreeData(config) {
		var attrs = {
			//Tree 
			'tree': {'value': null}
		};
		
		this._index = {};
		this._loaded = {};
		this._loading = {};
		
		this.addAttrs(attrs, config || {});
	}
	
	TreeData.NAME = 'TreeData';
	
	TreeData.prototype = {
		
		/**
		 * All data
		 * @type {Array}
		 * @private
		 */
		'_data': null,
		
		/**
		 * Data indexed by tree node ID
		 * @type {Object}
		 * @private
		 */
		'_index': {},
		
		/**
		 * Page IDs which children has been loaded
		 * @type {Object}
		 * @private
		 */
		'_loaded': {},
		
		/**
		 * Page IDs which children is being loaded
		 * @type {Object}
		 * @private
		 */
		'_loading': {},
		

		/**
		 * Initialize
		 * 
		 * @private
		 */
		'initializer': function () {
			
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Handle data receive
		 * 
		 * @param {Object} data Page data
		 * @param {Boolean} status Response status
		 * @param {String} id Parent ID
		 * @private
		 */
		'dataReceive': function (data, status, id) {
			var tree = this.get('tree'),
				index = id ? this._index : {};
			
			tree.set('loading', false);
			
			if (id) {
				this._loading[id] = false;
			}
			
			if (status) {
				this.dataReceiveTraverse(data, id || 0, index);
				this._index = index;
				
				if (!id) {
					this._data = data;
				} else {
					this._index[id].children = data;
				}
				
				if (id) {
					this._loaded[id] = true;
				}
				
				tree.fire('load:success', {'id': id, 'data': data, 'status': status});
				
				if (id) {
					tree.fire('load:success:' + id, {'id': id, 'data': data, 'status': status});
				}
			} else {
				tree.fire('load:failure', {'id': id, 'data': null, 'status': status});
				
				if (id) {
					tree.fire('load:failure:' + id, {'id': id, 'data': null, 'status': status});
				}
			}
			
			tree.fire('load:complete', {'id': id, 'data': data, 'status': status});
			
			if (id) {
				tree.fire('load:complete:' + id, {'id': id, 'data': data, 'status': status});
			}
		},
		
		/**
		 * Traverse data, set _id, _parent and create data index
		 * 
		 * @param {Array} data Page data
		 * @param {String} parent Parent node ID
		 * @param {Object} index Data index object
		 * @private
		 */
		'dataReceiveTraverse': function (data, parent, index) {
			var id = null,
				item = null,
				i = 0,
				ii = data.length,
				loaded = this._loaded;
			
			for(; i<ii; i++) {
				item = data[i];
				id = item._id = item.id;
				item._parent = parent;
				item.children_count = item.children_count || (item.children ? item.children.length : 0);
				
				loaded[parent] = true;
				index[id] = item;
				
				if (item.children_count == 0) {
					loaded[id] = true;
				}
				
				if (item.children) {
					this.dataReceiveTraverse(item.children, id, index);
				} else {
					item.children = [];
				}
			}
		},
		
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Update item ID
		 * 
		 * @param {String} id Old data ID, TreeNode ID or node ID
		 * @param {String} new_id New data ID
		 * @return TreeData for call chaining
		 * @type {Object}
		 */
		'updateId': function (id, new_id) {
			var item = this.item(id),
				index = this._index;
			
			if (item) {
				index[new_id] = item;
				delete(index[item._id]);
				item._id = new_id;
			}
		},
		
		/**
		 * Returns item data by TreeNode or tree node ID
		 * 
		 * @param {String} id TreeNode or tree node ID
		 * @return Item data or null if not found
		 * @type {Object}
		 */
		'item': function (id) {
			if (id && id.isInstanceOf && id.isInstanceOf('Tree')) {
				id = null;
			}
			if (id && id.isInstanceOf && id.isInstanceOf('TreeNode')) {
				id = id.get('data')._id;
			}
			
			if (typeof id === 'string') {
				return this._index[id] || null;
			} else if (typeof id === 'object') {
				return id;
			}
		},
		
		/**
		 * Returns Y.Array with children data by parent ID
		 * 
		 * @param {String} id TreeNode or tree node ID
		 * @return Y.Array with children data
		 * @type {Array}
		 */
		'children': function (id) {
			var item = this.item(id);
			return item ? new Y.Array(item.children) : null;
		},
		
		/**
		 * Returns Y.Array with all data
		 * 
		 * @return Y.Array with all data
		 * @type {Array}
		 */
		'all': function () {
			return new Y.Array(Y.Lang.toArray(this._index));
		},
		
		/**
		 * Insert data before/after/inside another page
		 * 
		 * @param {String} id TreeNode, tree node ID or data to insert
		 * @param {String} reference TreeNode, tree node ID or data after/before or inside which data will be inserted
		 * @param {String} where Point of reference
		 * @return TreeData for call chaining
		 * @type {Object}
		 */
		'insert': function (id, reference, where) {
			var item = this.item(id),
				children = null,
				index = null,
				list = null;
			
			reference = reference ? this.item(reference) : null;
			
			//Generate node ID, this is NOT page ID
			if (!item._id) {
				item._id = item.id || Supra.Y.guid();
			}
			
			//Remove item from parent
			if (item._parent) {
				//From another items children list
				index = this.index(item);
				
				if (index != -1) {
					if (this._index[item._parent].children) {
						this._index[item._parent].children.splice(index, 1);
						this._index[item._parent].children_count--;
					}
				}
			} else {
				//From root list
				index = this.index(item);
				
				if (index != -1) {
					this._data.splice(index, 1);
				}
			}
			
			//Add data
			if (where === 'inside') {
				if (!reference) {
					//Add to root
					this._data.push(item);
					
					//Update item data
					item._parent = 0;
				} else {
					//Add as child
					if (!reference.children) reference.children = [];
					reference.children.push(item);
					reference.children_count++;
					
					//Update item data
					item._parent = reference._id;
				}
			} else if (where === 'before' && reference) {
				index = this.index(reference);
				
				if (reference._parent) {
					//Child of some item
					list = this._index[reference._parent].children;
					if (!list) list = this._index[reference._parent].children = [];
					
					this._index[reference._parent].children_count++;
				} else {
					//Root
					list = this._data;
				}
				
				list.splice(index, 0, item);
				
				//Update item data
				item._parent = reference._parent;
			} else if (where === 'after' && reference) {
				index = this.index(reference);
				
				if (reference._parent) {
					//Child of some item
					list = this._index[reference._parent].children;
					if (!list) list = this._index[reference._parent].children = [];
					
					this._index[reference._parent].children_count++;
				} else {
					//Root
					list = this._data;
				}
				
				list.splice(index + 1, 0, item);
				
				//Update item data
				item._parent = reference._parent;
			}
			
			//Add to all data index
			this._index[item._id] = item;
			
			return this;
		},
		
		/**
		 * Alias of insert
		 */
		'add': function (id, reference, where) {
			return this.insert(id, reference, where);
		},
		
		/**
		 * Remove data
		 * 
		 * @param {String} id TreeNode, tree node ID or data to insert
		 * @param {Boolean} keepInIndex If true, then data will not be removed from index. Default false
		 * @return TreeData object for chaining
		 * @type {Object}
		 */
		'remove': function (id, keepInIndex) {
			var item = this.item(id),
				dataIndex = this._index,
				index = null;
			
			if (item && dataIndex[item._id]) {
				if (item._parent) {
					//Remove from another items children list
					index = this.index(item);
					
					if (index != -1) {
						dataIndex[item._parent].children.splice(index, 1);
						dataIndex[item._parent].children_count--;
					}
				} else {
					//Remove from root list
					index = this.index(item);
					
					if (index != -1) {
						this._data.splice(index, 1);
					}
				}
				
				if (!keepInIndex) {
					delete(dataIndex[item._id]);
					
					//Remove all children data from index
					var fn = function (children) {
						for(var i=0,ii=children.length; i<ii; i++) {
							delete(dataIndex[children[i]._id]);
							if (children[i].children) {
								fn(children[i].children);
							}
						}
					};
					
					if (item.children) {
						fn(item.children);
					}
				}
			}
			
			return this;
		},
		
		/**
		 * Remove all data
		 * 
		 * @return TreeData object for chaining
		 * @type {Object}
		 */
		'removeAll': function () {
			this._index = {};
			this._data = [];
			this._loaded = {};
			this._loading = {};

			return this;
		},
		
		/**
		 * Returns item index in parents children list
		 * 
		 * @param {String} id TreeNode or tree node ID
		 * @return Item index in parents children list
		 * @type {Number}
		 */
		'index': function (id) {
			var item = this.item(id),
				list = null;
			
			if (item) {
				if (item._parent) {
					list = this._index[item._parent].children;
				} else {
					list = this._data;
				}
				
				for(var i=0, ii=list.length; i<ii; i++) {
					if (list[i]._id == item._id) return i;
				}
			}
			
			return -1;
		},
		
		/**
		 * Load data
		 * 
		 * @param {String} id Page ID, optional
		 * @param {Number} offset Data offset, optional
		 * @param {Number} limit Data limit, optional
		 * @return TreeData object for chaining
		 * @type {Object}
		 */
		'load': function (id, offset, limit) {
			if (typeof id !== 'string') {
				id = offset = limit = null;
			}
			if (id && (this._loading[id] || this._loaded[id])) {
				return this;
			}
			
			var tree = this.get('tree'),
				locale = tree.get('locale'),
				uri = tree.get('requestURI'),
				data = {
					'locale': locale
				};
			
			if (id) {
				this._loading[id] = true;
				data.parent_id = id;
				
				if (typeof offset === 'number') {
					data.offset = offset;
				}
				if (typeof limit === 'number') {
					data.offset = data.offset || 0;
					data.limit = limit;
				}
			} else {
				data.parent_id = 0;
			}
			
			if (!id) {
				tree.set('loading', true);
				tree.fire('load');

				// Reset all loaded data when root sitemap is requested
				this.removeAll();
			}
			
			Supra.io(uri, {
				'data': data,
				'on': {
					'complete': function (data, status) { this.dataReceive(data, status, id); }
				}
			}, this);
		},
		
		/**
		 * Returns true if children data for this item has been loaded,
		 * otherwise false
		 * 
		 * @param {String} id Page ID
		 * @return True if pages children data has been loaded
		 * @type {Boolean}
		 */
		'isLoaded': function (id) {
			return !!this._loaded[id];
		},
		
		/**
		 * Sets page children as loaded
		 * 
		 * @param {String} id Page ID
		 * @param {Boolean} loaded True to set page as loaded, otherwise false
		 * @return TreeData object for chaining
		 * @type {Object}
		 */
		'setIsLoaded': function (id, loaded) {
			if (loaded) {
				this._loaded[id] = true;
			} else {
				delete(this._loaded[id]);
			}
			return this;
		},
		
		/**
		 * Returns true if children data for this item is being loaded,
		 * otherwise false
		 * 
		 * @param {String} id Page ID
		 * @return True if pages children data is being loaded
		 * @type {Boolean}
		 */
		'isLoading': function (id) {
			return !!this._loading[id];
		}
	};
	
	Y.augment(TreeData, Y.Attribute);
	
	Action.TreeData = TreeData;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree']});