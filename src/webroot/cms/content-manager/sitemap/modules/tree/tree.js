//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	function Tree(config) {
		Tree.superclass.constructor.apply(this, arguments);
	}
	
	Tree.NAME = 'Tree';
	Tree.CSS_PREFIX = 'su-tree';
	
	Tree.ATTRS = {
		/**
		 * Content mode, pages or templates
		 * @type {String}
		 */
		'mode': {
			'value': 'pages'
		},
		
		/**
		 * Data request URI
		 * @type {String}
		 */
		'requestURI': {
			'value': ''
		},
		
		/**
		 * Current data locale
		 * @type {String}
		 */
		'locale': {
			'value': ''
		},
		
		/**
		 * Children data
		 * @type {Array}
		 */
		'children': {
			'value': null,
			'getter': '_getChildren'
		},
		
		
		/**
		 * TreeView instance
		 * @type {Object}
		 */
		'view': {
			'value': null
		},
		
		/**
		 * TreeData instance
		 * @type {Object}
		 */
		'data': {
			'value': null
		},
		
		
		/**
		 * Tree node which is highlighted
		 * @type {Object}
		 */
		'highlighted': {
			'value': null
		},
		
		/**
		 * Visible root node
		 * @type {Number}
		 */
		'visibilityRootNode': {
			'value': null
		}
	};
	
	Y.extend(Tree, Y.Widget, {
		
		/**
		 * Root level children list
		 * @type {Array}
		 * @private
		 */
		'_children': [],
		
		/**
		 * All children list hashed by ID
		 * @type {Object}
		 * @private
		 */
		'_index': {},
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			var treeView = new Action.TreeView({
				'tree': this,
				'srcNode': this.get('boundingBox')
			});
			
			var treeData = new Action.TreeData({
				'tree': this
			});
			
			this._children = [];
			this._index = {};
			this.set('view', treeView);
			this.set('data', treeData);
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			this.on('load:success', this._renderChildren, this);
			this.on('load:success', this._loadPermissions, this);
			
			this.after('localeChange', function (evt) {
				if (!evt.silent) {
					this.get('data').load();
				}
			}, this);
			
			this.after('modeChange', function (evt) {
				if (!evt.silent) {
					this.get('data').load();
				}
			}, this);
			
			this.after('visibilityRootNodeChange', this._setVisibilityRootNode, this);
			this.on('highlightedChange', this._setHighlighted, this);
			
			this.on('load', this._reset, this);
		},
		
		/**
		 * Clean up
		 * @private
		 */
		'destructor': function () {
			//Destroy nodes
			var children = this._children;
			for(var i=0,ii=children.length; i<ii; i++) {
				children[i].destroy();
			}
			
			this._index = null;
			this._children = null;
			
			//Destroy data
			this.get('data').destroy();
			this.set('data', null);
			
			//Destroy view
			this.get('view').destroy();
			this.set('view', null);
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Reset all attributes
		 */
		'_reset': function () {
			this.set('highlighted', null);
			this.set('visibilityRootNode', null);
		},
		
		/**
		 * Render tree nodes
		 * 
		 * @private
		 */
		'_renderChildren': function (event) {
			//If data was loaded not for root pages then skip
			if (event.id) {
				return;
			}
			
			this.removeAll(this, true);
			
			//Remove element from DOM to prevent unneeded reflows
			var contentBox = this.get('contentBox'),
				reference = Y.DOM.removeFromDOM(contentBox),
				
				data = event.data,
				d = 0,
				dd = data.length,
				item = null,
				
				node = null,
				first_root_node = null,
				children = this._children = [],
				index = this._index = {},
				
				view = this.get('view');
			
			//Create tree
			contentBox.empty();
			
			for(; d<dd; d++) {
				//Since permissions are not loaded yet, we assume that page
				//can't be edited 
				item = data[d];
				node = this._createNode({
					'identifier': item._id,
					'data': item,
					
					'depth': 0,
					'expanded': false
				});
				
				//First root item should be expanded
				if (d === 0) {
					first_root_node = node;
				}
				
				children.push(node);
				index[item._id] = node;
				
				node.render(contentBox);
			}
			
			if (first_root_node) {
				first_root_node.expand();
			}
			
			//Restore in DOM
			Y.DOM.restoreInDOM(reference);
			
			//Reset view
			this.get('view').resetCenter();
		},
		
		/**
		 * Load user permissions
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_loadPermissions': function (e) {
			var data = e.data,
				i = 0,
				ii = data.length,
				permissions = [];
			
			var itterate = function (item) {
				permissions.push({
					'id': item.id,
					'type': 'page'
				});
				if (item.children && item.children.length) {
					Y.Array(item.children).forEach(itterate);
				}
			};
			
			Y.Array(data).forEach(itterate);
			
			if (permissions.length) {
				Supra.Permission.request(permissions, this._setPermissions, this);
			}
		},
		
		/**
		 * Load page permissions for single page
		 * 
		 * @param {Event} e Event facade object
		 */
		'loadPagePermissions': function (data) {
			Supra.Permission.request([{'id': data.id, 'type': 'page'}], this._setPermissions, this);
		},
		
		/**
		 * Update pages when permissions are loaded
		 * 
		 * @param {Object} permissions Permissions list
		 * @private
		 */
		'_setPermissions': function (permissions) {
			var pages = permissions.page,
				id = null,
				node = null,
				data = null;
			
			for(id in pages) {
				node = this.item(id);
				
				//If children is not rendered yet, then there is no node
				if (node && pages[id]) {
					if (pages[id].edit_page) {
						node.set('editable', true);
						
						if (node.get('type') != 'temporary' && node.get('type') != 'group') {
							node.set('selectable', true);
						}
						
						//Enable dragging
						data = node.get('data');
						if (!('isDragable' in data) || data.isDragable) {
							node.set('dragable', true);
						}
						if (!('isDropTarget' in data) || data.isDropTarget) {
							node.set('dropable', true);
						}
					}
					if (pages[id].supervise_page) {
						node.set('publishable', true);
					}
				}
			}
		},
		
		/**
		 * Create TreeNode from data
		 * 
		 * @private
		 */
		'_createNode': function (item) {
			var classname = Action.TreeNode,
				appId = null,
				node = null,
				data = item.data,
				
				editable = Supra.Permission.get('page', data.id, 'edit_page', false),
				publishable = Supra.Permission.get('page', data.id, 'supervise_page', false),
				
				preview = '/cms/content-manager/sitemap/images/preview/blank.jpg';
			
			if (data.childrenListStyle === 'scrollList') {
				classname = Action.TreeNodeList;
			} else if (data.type == 'application') {
				classname = Action.TreeNodeApp;
				if (data.application_id) {
					appId = data.application_id;
					appId = appId.substr(0,1).toUpperCase() + appId.substr(1);
					if (Action.TreeNodeApp[appId]) {
						classname = Action.TreeNodeApp[appId];
					}
				}
			} else if (data.type == 'group') {
				preview = '/cms/content-manager/sitemap/images/preview/group.png';
			}
			
			return new classname(Supra.mix({
				'tree': this,
				'view': this.get('view'),
				
				'id': 'tree_' + (item.identifier || data._id),
				'identifier': (item.identifier || data._id),
				'label': data.title || '',
				'preview': data.preview || preview,
				'type': data.type,
				
				'dragable': editable && (!('isDragable' in data) || data.isDragable),
				'dropable': editable,
				
				'expandable': (data.children_count || (data.children && data.children.length)),
				'selectable': editable && data.type != 'temporary',
				'editable': editable,
				'publishable': publishable,
				'global': data.global || false,
				
				'expanded': false,
				'selected': false,
				'state': 'draft',
				
				'depth': 0,
				'root': (item.depth == 0),
				'index': 0,
				
				'parent': this
			}, item));
		},
		
		/**
		 * Highlighted attribute change even handler
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_setHighlighted': function (e) {
			if (!e.silent) {
				if (e.newVal) {
					e.newVal.set('highlighted', true, {'silent': true});
				}
				if (e.prevVal) {
					e.prevVal.set('highlighted', false, {'silent': true});
				}
			} else if (e.newVal && e.prevVal) {
				e.prevVal.set('highlighted', false, {'silent': true});
			}
			
			if (e.newVal) {
				this.get('boundingBox').addClass(this.getClassName('highlighted'));
			} else {
				this.get('boundingBox').removeClass(this.getClassName('highlighted'));
			}
			
			//Prevent drag and drop during highlight
			for(var i=0, ii=this.size(); i<ii; i++) {
				this.item(i).set('dndLocked', e.newVal);
			}
		},
		
		/**
		 * Children attribute getter
		 * 
		 * @return Array with all children
		 * @type {Array}
		 * @private
		 */
		'_getChildren': function () {
			return this.children();
		},
		
		
		/**
		 * ------------------------------ ATTRIBUTES ------------------------------
		 */
		
		
		/**
		 * visibilityRootNode attribute change event listener
		 * 
		 * @param {Event} event Event facade object
		 * @private
		 */
		'_setVisibilityRootNode': function (event) {
			if (event.newVal !== event.prevVal) {
				if (event.prevVal && event.prevVal.get('visibilityRoot')) {
					event.prevVal.set('visibilityRoot', false);
				}
				if (event.newVal && !event.newVal.get('visibilityRoot')) {
					event.newVal.set('visibilityRoot', true);
				}
				
				//Remove drop marker
	            if (Y.DD.DDM.activeDrag) {
	            	var node = Y.DD.DDM.activeDrag.get('treeNode');
	            	Y.later(16, node, function () {
		            	this.hideDropMarker();
					});
	            }
			}
		},
		
		/**
		 * Go one visibility level up
		 */
		'visibilityRootNodeUp': function (e) {
			if (e && 'button' in e && e.button != 1) return;
			
			var node = this.get('visibilityRootNode');
			
			if (node) {
				if (!node.get('root')) {
					this.set('visibilityRootNode', node.get('parent'));
				} else {
					this.set('visibilityRootNode', null);
				}
				
				if (e) this.get('view').center(node);
				
				//Collapse expanded children
				node = node.children({'expanded': true}, true);
				if (node) {
					node.collapse();
				}
			}
			
			if (e) e.halt();
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		/**
		 * Returns TreeNode by ID or index
		 * 
		 * @param {String} id Tree node ID or child index
		 * @param {Boolean} strict If strict then will return null for invalid index
		 * @return Tree node
		 * @type {Object}
		 */
		'item': function (id, strict) {
			if (typeof id === 'string') {
				if (this._index[id]) {
					return this._index[id];
				} else {
					//Convert real page ID to node ID
					var data = this.get('data'),
						item = data.item(id);
					
					if (item) {
						return this._index[item._id];
					} else {
						return null;
					}
				}
			} else if (typeof id === 'number') {
				//Allow negative as indexes
				var children = this._children,
					count = children.length;
				id = strict ? id : ((id < 0 ? (id % count + count) % count : id) % count);
				
				return children[id];
			} else if (id && id.isInstanceOf && (id.isInstanceOf('TreeNode') || id.isInstanceOf('Tree'))) {
				return id;
			} else {
				return null;
			}
		},
		
		/**
		 * Returns Y.Array with all root level tree nodes
		 * 
		 * @return Y.Array with root level tree nodes
		 * @type {Object}
		 */
		'children': function (filter, one) {
			var children = new Y.Array(this._children);
			
			if (filter) {
				if (Y.Lang.isFunction(filter)) {
					children = children.filter(filter);
				} else if (Y.Lang.isObject(filter)) {
					children = children.filter(function (item) {
						for(var i in filter) {
							if (item.get(i) != filter[i]) return false;
						}
						return true;
					});
				}
			}
			
			return one ? children[0] || null : children;
		},
		
		/**
		 * Returns index of tree node or -1 if not found
		 * 
		 * @param {Object} node TreeNode or tree node ID
		 * @return Tree node index
		 * @type {Number}
		 */
		'indexOf': function (node) {
			node = this.item(node);
			return node ? node.get('index') : -1;
		},
		
		/**
		 * Returns root level children count
		 * 
		 * @return Root level children count
		 * @type {Number}
		 */
		'size': function () {
			return this._children.length;
		},
		
		/**
		 * Insert node 'before', 'after' or 'inside' reference node
		 * 
		 * @param {Object} node TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {Object} reference TreeNode or TreeNode ID after/before/inside which node will be inserted
		 * @param {String} where Point of reference
		 * @return Tree for call chaining
		 * @type {Object}
		 */
		'insert': function (node, reference, where) {
			//Get reference
			reference = this.item(reference);
			
			//Parent
			var old_parent = null,
				new_parent = null,
				parent = null,
				child = null,
				data = this.get('data'),
				item = null;
			
			//Create TreeNode if it doesn't exist
			if (node === 'null') {
				
			} else if (typeof node === 'object' && !node.isInstanceOf) {
				var depth = reference ? (reference.get('depth') + (where == 'inside' ? 1 : 0)) : 0;
				
				node._id = node.id || Y.guid();
				node = this._createNode(Supra.mix({
					'identifier': node._id,
					'data': node,
					
					'dragable': true,
					'dropable': true,
					
					'editable': true,
					'publishable': true,
					
					'depth': depth
				}, node));
				
				node.render();
			} else {
				node = this.item(node);
			}
			
			if (!node || !reference) {
				return;
			}
			
			old_parent = node.get('parent');
			new_parent = (where == 'inside' ? reference : reference.get('parent'));
			
			if (where == 'inside') {
				child = reference.item(-1);
				
				if (child) {
					reference = child;
					where = 'after';
				} else {
					//Remove from old parent
					this.remove(node, old_parent === new_parent);
					
					//Add to new parent
					reference._children.push(node);
					this._index[node.get('identifier')] = node;
					
					node.set('parent', reference);
					node.set('root', false);
					
					//Move nodes
					reference.get('childrenBox').append(node.get('boundingBox'));
				}
			}
			
			if (where == 'before') {
				//Remove from old parent
				this.remove(node, old_parent === new_parent);
				
				//Add to new parent
				parent = reference.get('parent');
				parent._children.splice(reference.get('index'), 0, node);
				this._index[node.get('identifier')] = node;
				
				node.set('parent', parent);
				node.set('root', parent.isInstanceOf('Tree'));
				
				//Move nodes
				reference.get('boundingBox').insert(node.get('boundingBox'), 'before');
			} else if (where == 'after') {
				//Remove from old parent
				this.remove(node, old_parent === new_parent);
				
				//Add to new parent
				parent = reference.get('parent');
				parent._children.splice(reference.get('index') + 1, 0, node);
				this._index[node.get('identifier')] = node;
				
				node.set('parent', parent);
				node.set('root', parent.isInstanceOf('Tree'));
				
				//Move nodes
				reference.get('boundingBox').insert(node.get('boundingBox'), 'after');
			}
			
			//Update data
			data.insert(node.get('data'), reference, where);
			
			//Fire events
			if (old_parent === new_parent) {
				new_parent.fire('child:move', {'node': node, 'data': node.get('data')});
			} else {
				old_parent.fire('child:remove', {'node': node, 'data': node.get('data')});
				new_parent.fire('child:add', {'node': node, 'data': node.get('data')});
			}
			
			//Update arrows
			this.get('view').checkOverflow();
			
			return node;
		},
		
		/**
		 * Alias of insert
		 * 
		 * @param {Object} node TreeNode, TreeNode ID or data for new TreeNdoe
		 * @param {Object} reference TreeNode or TreeNode ID after/before/inside which node will be inserted
		 * @param {String} where Point of reference
		 * @return Tree for call chaining
		 * @type {Object}
		 */
		'add': function (node, reference, where) {
			return this.insert(node, reference, where);
		},
		
		/**
		 * Remove node from tree
		 * Data is not removed
		 * 
		 * @param {Object} node TreeNode or tree node ID
		 * @param {Boolean} silent If true event will not be triggered
		 * @return Tree for call chaining
		 * @type {Object}
		 */
		'remove': function (node, silent) {
			var node = this.item(node);
			if (node) {
				var parent = node.get('parent'),
					id = node.get('identifier');
				
				if (parent && this._index[id]) {
					parent._children.splice(node.get('index'), 1);
					delete(this._index[id]);
					
					node.set('parent', null);
					
					if (!silent) {
						parent.fire('child:remove');
					}
					
					//Update arrows
					this.get('view').checkOverflow();
				}
			}
		},
		
		/**
		 * Removes all children nodes recursively
		 * If node argument is not passed, then ALL nodes are removed
		 * 
		 * @param {Object} node TreeNode or tree node ID to remove all children
		 * @param {Boolean} destroy If true, then nodes will be destroyed instead of just removing them from tree
		 * @return Tree for call chaining
		 * @type {Object}
		 */
		'removeAll': function (node, destroy) {
			node = this.item(node) || this;
			
			var children = node._children;
			
			for(var i=0,ii=children.length; i<ii; i++) {
				if (destroy) {
					children[i].destroy();
				} else {
					children[i].remove();
				}
			}
			
			node._children = [];
			if (node._index) node._index = {};
			
			//Update arrows
			this.get('view').checkOverflow();
			
			return this;
		}
	});
	
	
	Action.Tree = Tree;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget']});