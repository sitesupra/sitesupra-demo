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
		 * Tree drop target, used when there
		 * are no items in the tree
		 * @type {Object}
		 * @private
		 */
		'_dndDrop': null,

		/**
		 * Deferred expand timer
		 * @type {Object}
		 * @private
		 */
		'_expandTimer': null,

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
			
			this.bindDnD();
		},
		
		/**
		 * Attach drag and drop event listeners
		 * 
		 * @private
		 */
		'bindDnD': function () {
			//Drop target for root item when tree is empty
			this._dndDrop = new Y.DD.Drop({
				'node': this.get('contentBox'),
				'groups': [
					'default',
					'new-page', 'restore-page',
					'new-template', 'restore-template'
				]
			});
			
			this._dndDrop.set('treeNode', this);
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
			
			//Destroy DND
			if (this._dndDrop) {
				this._dndDrop.destroy();
				this._dndDrop = null;
			}
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
			var boundingBox = this.get('boundingBox'),
				contentBox = this.get('contentBox'),
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
			
			//Page/template styles
			boundingBox.removeClass('templates');
			boundingBox.removeClass('pages');
			boundingBox.addClass(this.get('mode'));
			
			//Create tree
			contentBox.empty();
			
			//Different style when empty
			if (!dd) {
				boundingBox.addClass(this.getClassName('empty'));
				this._dndDrop.set('lock', false);
			} else {
				//Different style when empty
				boundingBox.removeClass(this.getClassName('empty'));
				this._dndDrop.set('lock', true);
				
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
				permissions = [];
			
			var iterate = function (item) {
				permissions.push({
					'id': item.id,
					'type': 'page'
				});
				if (item.children && item.children.length) {
					Y.Array(item.children).forEach(iterate);
				}
			};
			
			Y.Array(data).forEach(iterate);
			
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
						
						if (node.isInstanceOf('TreeNodeList')) {
							
							data = node.get('data');
							if (!('isDropTarget' in data) || data.isDropTarget) {
								node.set('droppable', true);
							}
							
							continue;
						}
						
						node.set('editable', true);
						
						if (node.get('type') != 'temporary' && node.get('type') != 'group') {
							node.set('selectable', true);
						}
						
						//Enable dragging
						data = node.get('data');
						if (!('isDraggable' in data) || data.isDraggable) {
							node.set('draggable', true);
						}
						if (!('isDropTarget' in data) || data.isDropTarget) {
							node.set('droppable', true);
						}
						if ('droppablePlaces' in data) {
							node.set('droppablePlaces', data.droppablePlaces);
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
				data = item.data,

				editable = Supra.Permission.get('page', data.id, 'edit_page', false),
				publishable = Supra.Permission.get('page', data.id, 'supervise_page', false),
				
				preview = '/public/cms/supra/img/sitemap/preview/blank.jpg';

			if (data.childrenListStyle === 'scrollList') {
				classname = Action.TreeNodeList;
				
				// virtual list arent editable
				editable = false;
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
				preview = '/public/cms/supra/img/sitemap/preview/group.png';
			}
			
			return new classname(Supra.mix({
				'tree': this,
				'view': this.get('view'),
				
				'id': 'tree_' + (item.identifier || data._id),
				'identifier': (item.identifier || data._id),
				'label': data.title || '',
				'preview': data.preview || preview,
				'type': data.type,
				
				'draggable': editable && (!('isDraggable' in data) || data.isDraggable),
				'droppable': editable,
				'droppablePlaces': data.droppablePlaces,
				
				'expandable': (data.children_count || (data.children && data.children.length)),
				'selectable': editable && data.type != 'temporary' && data.type != 'group',
				'editable': editable,
				'publishable': publishable,
				'global': data.global || false,
				'localized': ('localized' in data) ? data.localized : true,
				
				'expanded': false,
				'selected': false,
				'state': 'draft',
				
				'depth': 0,
				'root': (item.depth == 0),
				'index': 0,
				
				// templates by default are active, published
				'active': (this.get('mode') == 'templates' ? true : data.active),
				'published': (this.get('mode') == 'templates' ? true : data.published),
				'scheduled': (this.get('mode') == 'templates' ? false : data.scheduled),
				
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
			var newVal = (e.newVal && !e.newVal.get('destroyed') ? e.newVal : null),
				prevVal = (e.prevVal && !e.prevVal.get('destroyed') ? e.prevVal : null);
			
			if (!e.silent) {
				if (newVal) {
					newVal.set('highlighted', true, {'silent': true});
				}
				if (prevVal) {
					prevVal.set('highlighted', false, {'silent': true});
				}
			} else if (newVal && prevVal) {
				prevVal.set('highlighted', false, {'silent': true});
			}
			
			if (newVal) {
				this.get('boundingBox').addClass(this.getClassName('highlighted'));
			} else {
				this.get('boundingBox').removeClass(this.getClassName('highlighted'));
			}
			
			//Prevent drag and drop during highlight
			for(var i=0, ii=this.size(); i<ii; i++) {
				this.item(i).set('dndLocked', newVal);
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
					
					if (!event.prevVal.get('destroyed')) {
						event.prevVal.set('visibilityRoot', false);
					}
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
			if (typeof node === 'null') {
				
			} else if (typeof node === 'object' && !node.isInstanceOf) {
				var depth = reference ? (reference.get('depth') + (where == 'inside' ? 1 : 0)) : 0;
				
				node._id = node.id || Y.guid();
				node = this._createNode(Supra.mix({
					'identifier': node._id,
					'data': node,
					
					'draggable': true,
					'droppable': true,
					
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
					if (child === node) {
						return;
					}
					
					reference = child;
					where = 'after';
				} else {
					//Remove from old parent
					this.remove(node, old_parent === new_parent);
					
					//Add to new parent
					reference._children.push(node);
					this._index[node.get('identifier')] = node;
					
					if (reference && reference.isInstanceOf('Tree')) {
						//Add as tree root node
						node.set('parent', reference);
						node.set('root', true);
						
						//Move nodes
						reference.get('contentBox').append(node.get('boundingBox'));
					} else {
						//Add as child of other node
						node.set('parent', reference);
						node.set('root', false);
						
						//Move nodes
						reference.get('childrenBox').append(node.get('boundingBox'));
					}
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
				
				new_parent.expand();
			}
			
			//Style tree
			this.get('boundingBox').removeClass(this.getClassName('empty'));
			this._dndDrop.set('lock', true);
			
			//Update arrows
			this.get('view').checkOverflow();
			
			return node;
		},
		
		/**
		 * Add item to the tree from data
		 * 
		 * @param {Object} data Data for new TreeNdoe
		 * @param {Object} reference TreeNode or TreeNode ID after/before/inside which node will be inserted
		 * @param {String} where Point of reference
		 * @return Tree for call chaining
		 * @type {Object}
		 */
		'insertData': function (data, reference, where) {
			var target = reference;
			if (!target) return;
			
			var added = true;
			
			// Trigger event to allow subscribers to implement custom
			// functionality
			var setter = {'target': target, 'where': where, 'data': data},
				res = null;
			
			if (where == 'inside') {
				res = target.fire('child:before-add', setter, setter);
			}
			
			// event was canceled by callback, meaning that insert was taken
			// care of
			if (res === false) return;
			
			where = setter.where;
			if (setter.target !== target) target = setter.target;
			
			//Add item
			if (target.isInstanceOf('TreeNodeList') && where == 'inside') {
				
				target.set('expandable', true);
				
				//Expand list - show list with all items, needed to show new item popup in correct
				//place
				target.expand();
				
				//Add TreeNodeList row
				var datagrid = target.getWidget('datagrid'),
					data = Supra.mix({'tree': this}, target.NEW_CHILD_PROPERTIES || {}, data),
					row = datagrid.insert(Supra.mix({'id': Y.guid()}, data), target.get('data').new_children_first ? 0 : null),
					params = {
						'data': row.get('data'),
						'node': row
					};
				
			} else {
				//Add page
				var data = Supra.mix({}, target.NEW_CHILD_PROPERTIES || {}, data),
					node = null,
					params = null;
				
				//If target is expandable, then wait till it's expanded before adding new item
				if (where == 'inside' && target.expand && target.get('expandable') && !target.get('expanded')) {
					
					//After expand add node
					target.once('expanded', function () {
						
						var node = this.insert(data, target, 'inside'),
							params = {
								'data': node.get('data'),
								'node': node
							};
						
						if (!data.id) {
							this.fire('page:add', params);
						} else {
							this.fire('page:restore', params);
						}
						
					}, this);
					
					target.expand();
					
					added = false;
					
				} else {
					node = this.insert(data, target, where);
					params = {
						'data': node.get('data'),
						'node': node
					};
					
					if (node.get('parent').expand) node.get('parent').expand();
				}
			}
			
			//Only new items doesn't have ID, if page is restored from recycle bin
			//then there will be ID
			if (added) { 
				if (!data.id) {
					this.fire('page:add', params);
				} else {
					this.fire('page:restore', params);
				}
			}
			
			return this;
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
					
					//Style tree
					if (!this.size()) {
						this.get('boundingBox').addClass(this.getClassName('empty'));
						this._dndDrop.set('lock', false);
					}
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
			
			if (this.get('highlighted')) {
				this.set('highlighted', false);
			}
			
			if (this.get('visibilityRootNode')) {
				this.set('visibilityRootNode', null);
			}
			
			//Different style when empty
			this.get('boundingBox').addClass(this.getClassName('empty'));
			this._dndDrop.set('lock', false);
			
			//Update arrows
			this.get('view').checkOverflow();
			
			return this;
		},

		/**
		 * Expands the node using some timeout, used for dnd
		 *
		 * @param {Object} node TreeNode to expand
		 * @param {Number} time in milliseconds to wait
		 */
		'expand': function(node, when) {
			if (!node) return;
			
			when = when || 0;
			this.stopExpand();
			this._expandTimer = Y.later(when, this, function(node) {
				this._expandTimer = null;
				node.expand();
			}, [node]);
		},

		/**
		 * Stops deferred expand timer if active
		 */
		'stopExpand': function() {
			if (this._expandTimer) {
				this._expandTimer.cancel();
				this._expandTimer = null;
			}
		}
	});
	
	
	Action.Tree = Tree;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['widget']});