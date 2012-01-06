//Invoke strict mode
"use strict";

YUI.add('website.sitemap-tree-newpage', function (Y) {
	
	var Manager = Supra.Manager;
	
	var TREENODE_TEMPLATE_DATA = {
		'title': 'New template',
		'preview': '/cms/lib/supra/img/sitemap/preview/blank.jpg',
		'layout': '',
		'icon': 'page',
		'parent': null,
		'published': false,
		'scheduled': false,
		'type': 'page'
	};
	
	var TREENODE_PAGE_DATA = {
		'title': 'New page',
		'preview': '/cms/lib/supra/img/sitemap/preview/blank.jpg',
		'template': '',
		'icon': 'page',
		'path': 'new-page',
		'parent': null,
		'published': false,
		'scheduled': false,
		'type': 'page'
	};
	
	var TREENODE_PAGE_GROUP_DATA = Supra.mix({}, TREENODE_PAGE_DATA, {
		'type': 'group',
		'icon': 'group'
	});
	
	var TREENODE_PAGE_APP_DATA = Supra.mix({}, TREENODE_PAGE_DATA, {
		'type': 'application',
		'application_id': '',
		'collapsed': false
	});
	
	/**
	 * New page tree plugin allows adding new page using drag & drop
	 */	
	function NewPagePlugin (config) {
		NewPagePlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a tree instance, the plugin will be 
	// available on the "state" property.
	NewPagePlugin.NS = 'newpage';
	
	NewPagePlugin.ATTRS = {
		'dragNode': {
			value: null
		},
		'newItemDropNode': {
			value: null
		}
	};
	
	// Extend Plugin.Base
	Y.extend(NewPagePlugin, Y.Plugin.Base, {
		
		/**
		 * Tree node instance
		 * @type {Object}
		 */
		treenodes: [],
		
		/**
		 * New page index in tree
		 * @type {Number}
		 */
		new_page_index: null,
		
		/**
		 * New page type
		 * @type {String}
		 */
		type: '',
		
		/**
		 * Set current list type
		 * 
		 * @param {String} type List type, "templates" or "sitemap"
		 */
		setType: function (type) {
			var dragNode = this.get('dragNode');
			
			if (this.type != type) {
				if (this.type) {
					dragNode.removeClass('type-' + this.type);
				}
				dragNode.addClass('type-' + type);
				this.type = type;
			}
		},
		
		/**
		 * Returns default item data based on current list type ("templates" or "sitemap")
		 * and on node type
		 * 
		 * @param {String} type Node type, "page" or "group" or "application"
		 * @return Default data
		 * @type {Object}
		 * @private
		 */
		getDefaultData: function (type) {
			if (this.type == 'templates') {
				return TREENODE_TEMPLATE_DATA;
			} else {
				if (type == 'group') {
					return TREENODE_PAGE_GROUP_DATA;
				} else if (type == 'application') {
					return TREENODE_PAGE_APP_DATA;
				} else {
					return TREENODE_PAGE_DATA;
				}
			}
		},
		
		/**
		 * Create new item node
		 * 
		 * @param {Object} node Node which will be dragable
		 * @param {Object} data Dragable item data
		 * @return Tree node
		 * @type {Object}
		 * @private
		 */
		createProxyTreeNode: function (node, data) {
			var default_data = this.getDefaultData(data.type);
			var data = SU.mix({}, default_data, data);
			var treenode = new SU.FlowMapItemNormal({
				'data': data,
				'label': data.title,
				'icon': data.icon
			});
			
			treenode.render(document.body);
			treenode.get('boundingBox').remove();
			
			treenode._tree = this.get('host');
			
			var dd = new Y.DD.Drag({
				'node': node ? node : treenode.get('boundingBox').one('div.tree-node'),
				'dragMode': 'point',
				'target': false,
				'groups': ['default', 'new']
			}).plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,			// Don't move original node at the end of drag
				cloneNode: true
			});
			
			dd.set('treeNode', treenode);
			
			if (dd.target) {
				dd.target.set('treeNode', treenode);
			}
			
			//When starting drag all children must be locked to prevent
			//parent drop inside children
			dd.on('drag:afterMouseDown', treenode._afterMouseDown);
			
			//Set special style to proxy node
			dd.on('drag:start', treenode._dragStart);
			
			
			// When we leave drop target hide marker
			dd.on('drag:exit', treenode._dragExit);
			
			// When we move mouse over drop target update marker
			dd.on('drag:over', treenode._dragOver);
			
			dd.on('drag:end', this._dragEnd, this);
			this.treenodes.push(treenode);
			
			//Set default type
			this.setType('sitemap');
			
			return treenode;
		},
		
		/**
		 * Constructor
		 * Make all items dragable
		 * 
		 * @constructor
		 * @param {Object} config Plugin configuration
		 * @private
		 */
		initializer: function (config) {
			this.treenodes = [];
			
			var nodes = config.dragNode.get('children'),
				node = null,
				type = null,
				treenode = null;
			
			for(var i=0, ii=nodes.size(); i<ii; i++) {
				node = nodes.item(i);
				type = node.getAttribute('data-type');
				treenode = this.createProxyTreeNode(node, {'type': type});
			}
			
			//"Drop here to create a new master template" drop target
			var dd = new Y.DD.Drop({
				'node': config.newItemDropNode,
				'groups': ['new']
			});
			
			dd.on('drop:hit', function (e) {
				var tree_node = e.drag.get('treeNode'),
					data = tree_node.get('data');
				
				if (!data.id) {
					//If new item then create new template
					this.createNewNode();
				}
			}, this);
		},
		
		/**
		 * On drag end add item to the tree
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		_dragEnd: function(e){
			var self = e.target.get('treeNode'),
				tree = this.get('host');
			
			if (self.drop_target) {
				var target = self.drop_target,
					drag_data = self.get('data'),
					drop_data = target.get('data'),
					position = self.marker_position;
				
				//Fire drop event
				var event = tree.fire('drop', {'drag': drag_data, 'drop': drop_data, 'position': position});
				
				//If event was not prevented, then create node
				if (event) this.addChild(position, target, drag_data);
			}
			
			//Hide marker and cleanup data
			self.setMarker(null);
			
			//Unlock children to allow them being draged
			self.unlockChildren();
			
			//Make sure node is not actually moved
			e.preventDefault();
		},
		
		createNewNode: function () {
			var position = 'after',
				size = this.get('host').size(),
				target = size ? this.get('host').item(size - 1) : null;
			
			this.addChild(position, target);
		},
		
		addChildNodeFromData: function (data) {
			var default_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA,
				page_data = SU.mix({}, default_data, data),
				parent_node = this.get('host').getNodeById(page_data.parent),
				parent_data = parent_node ? parent_node.get('data') : null;
			
			if (parent_node && parent_data) {
				//Add to parent
				if (!parent_data.children) parent_data.children = [];
				parent_data.children.push(page_data);
				
				//Set into data
				var data_indexed = this.get('host').getIndexedData();
				data_indexed[page_data.id] = page_data;
				
				//Expand parent
				parent_node.expand();
				
				//Create node
				parent_node.add({
					'label': page_data.title,
					'icon': page_data.icon,
					'data': page_data
				}, this.new_page_index);
			} else {
				//Add to tree (Root)
				
				//Set into data
				var data = this.get('host').getData();
				data.push(page_data);
				
				//Create node
				this.get('host').add({
					'label': page_data.title,
					'icon': page_data.icon,
					'data': page_data
				}, this.new_page_index);
			}
			
			//Open editor
			Y.later(150, this, function () {
				//Start editing
				this.get('host').getNodeById(page_data.id).edit(null, true);
			});
		},
		
		addChildNodeTemporary: function (data) {
			var default_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA,
				page_data = SU.mix({}, default_data, data),
				parent_node = this.get('host').getNodeById(page_data.parent),
				parent_data = parent_node ? parent_node.get('data') : null,
				temp_id = Supra.Y.guid(),
				scroll = false,
				node = null;
			
			//Set temporary ID
			page_data.id = temp_id;
			page_data.temporary = true;
			
			if (parent_node && parent_data) {
				//Add to parent
				if (!parent_data.children) parent_data.children = [];
				parent_data.children.push(page_data);
				
				//Set into data
				var data_indexed = this.get('host').getIndexedData();
				data_indexed[page_data.id] = page_data;
				
				//Expand parent
				parent_node.expand();
				
				//Create node
				node = parent_node.add({
					'label': page_data.title,
					'icon': page_data.icon,
					'data': page_data,
					'isDropTarget': false
				}, this.new_page_index);
				
			} else {
				//Add new root level item
				
				//Set into data
				var data = this.get('host').getData();
				data.push(page_data);
				
				//Create node
				node = this.get('host').add({
					'label': page_data.title,
					'icon': page_data.icon,
					'data': page_data,
					'isDropTarget': false
				}, this.new_page_index);
				
				//Scroll to the bottom of the page
				scroll = true;
			}
			
			//Style temporary node
			node.item(0).get('boundingBox').addClass('yui3-tree-node-temp');
			
			//Scroll
			Y.later(50, this, function () {
				//Root template is added to the bottom, scroll to it
				if (scroll) {
					Manager.SiteMap.one('.yui3-sitemap-scrollable').set('scrollTop', 10000);
				}
				
				//Open editor
				Y.later(50, this, function () {
					this.get('host').getNodeById(page_data.id).editNewPage(null, true);
				});
			});
		},
		
		addChild: function (position, target, drag_data, callback, context) {
			var drop_data = target.get('data'),
				parent_data = target.get('parent') ? target.get('parent').get('data') : null;
			
			if (typeof drag_data == 'function') {
				context = callback;
				callback = drag_data;
				drag_data = null;
			}
			if (!drag_data) {
				drag_data = this.getDefaultData({'type': 'page'});
			}
			
			var pagedata = SU.mix({}, drag_data, {
				//New parent ID
				'parent': drop_data ? drop_data.id : 0,
				//Item ID before which drag item was inserted
				'reference': '',
				//Locale
				'locale': Manager.SiteMap.languagebar.get('locale')
			});
			
			if (this.type != 'templates') {
				//Page template (parent template)
				pagedata.template = (position == 'inside' ? drop_data.template : (parent_data ? parent_data.template : ''));
			}
			
			if (position == 'before') {
				var parent = target.get('parent'),
					parent_data = parent ? parent.get('data') : null;
				
				parent = parent_data ? parent_data.id : 0;
				
				pagedata.reference = drop_data.id;
				pagedata.parent = parent;
			} else if (position == 'after') {
				var parent = target.get('parent'),
					parent_data = parent ? parent.get('data') : null;
				
				parent = parent_data ? parent_data.id : 0;
				
				var ref = target.next(); 
				if (ref) {
					pagedata.reference = ref.get('data').id;
				}
				
				pagedata.parent = parent;
			}
			
			//Set new page index depending on where it was dropped
			if (position == 'inside') {
				if (parent_data && parent_data.new_children_first) {
					this.new_page_index = 0;
				} else {
					this.new_page_index = target.size() + 1;
				}
			} else {
				if (position == 'after') {
					this.new_page_index = target.get('index') + 1;
				} else {
					this.new_page_index = target.get('index');
				}
			}
			
			if (this.type == 'templates' && !pagedata.parent) {
				//Create temporary node, template will be created after layout value is set
				this.addChildNodeTemporary(pagedata);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			} else if (!pagedata.id) {
				//Create temporary node, page will be created after layout value is set
				this.addChildNodeTemporary(pagedata);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			}
			
		}
		
	});
	
	Supra.Tree.NewPagePlugin = NewPagePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['website.sitemap-flowmap-item']});
