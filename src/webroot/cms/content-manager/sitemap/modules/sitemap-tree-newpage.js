//Invoke strict mode
"use strict";

YUI.add('website.sitemap-tree-newpage', function (Y) {
	
	var Manager = Supra.Manager;
	
	var TREENODE_PAGE_DATA = {
		'title': 'New page',
		'preview': '/cms/lib/supra/img/sitemap/preview/blank.jpg',
		'template': '',
		'icon': 'page',
		'path': 'new-page',
		'parent': null,
		'published': false,
		'scheduled': false
	};
	
	var TREENODE_TEMPLATE_DATA = {
		'title': 'New template',
		'preview': '/cms/lib/supra/img/sitemap/preview/blank.jpg',
		'layout': '',
		'icon': 'page',
		'parent': null,
		'published': false,
		'scheduled': false
	};
	
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
		'clickNode': {
			value: null
		}
	};
	
	// Extend Plugin.Base
	Y.extend(NewPagePlugin, Y.Plugin.Base, {
		
		/**
		 * Tree node instance
		 * @type {Object}
		 */
		treenode: null,
		
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
		
		
		setType: function (type) {
			var dragNode = this.get('dragNode'),
				clickNode = this.get('clickNode');
			
			if (this.type != type) {
				if (this.type) {
					dragNode.removeClass('type-' + this.type);
					clickNode.removeClass('type-' + this.type);
				}
				dragNode.addClass('type-' + type);
				clickNode.addClass('type-' + type);
				this.type = type;
			}
		},
		
		createTreeNode: function (proxy, node) {
			var default_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA;
			var data = SU.mix({}, default_data);
			var treenode = new SU.FlowMapItemNormal({
				'data': data,
				'label': data.title,
				'icon': data.icon
			});
			
			treenode.render(document.body);
			treenode.get('boundingBox').remove();
			
			treenode._tree = this.get('host');
			
			var dd = this.dd = new Y.DD.Drag({
				'node': node ? node : treenode.get('boundingBox').one('div.tree-node'),
				'dragMode': 'point',
				'target': false
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
			this.treenode = treenode;
			
			//Set default type
			this.setType('sitemap');
			
			return treenode;
		},
		
		/**
		 * Constructor
		 */
		initializer: function (config) {
			var host = config.host;
			var node = config.dragNode;
			var treenode = this.createTreeNode(true, node);
			
			config.clickNode.on('click', this.createNewNode, this);
		},
		
		/**
		 * 
		 * @param {Object} e
		 */
		_dragEnd: function(e){
			var self = this.treenode,
				tree = this.get('host');
			
			if (self.drop_target) {
				var target = self.drop_target
				var drag_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA;
				var drop_data = target.get('data');
				var position = self.marker_position;
				
				//Fire drop event
				var event = tree.fire('drop', {'drag': drag_data, 'drop': drop_data, 'position': position});
				
				//If event was not prevented, then create node
				if (event) this.addChild(position, target);
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
				this.get('host').getNodeById(page_data.id).edit(null, true);
			});
		},
		
		addChildNodeTemporary: function (data) {
			var default_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA,
				page_data = SU.mix({}, default_data, data),
				parent_node = this.get('host').getNodeById(page_data.parent),
				parent_data = parent_node ? parent_node.get('data') : null,
				temp_id = Supra.Y.guid();
			
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
				this.get('host').getNodeById(page_data.id).editNewPage(null, true);
				
				//Root template is added to the bottom, scroll to it
				Manager.SiteMap.one('.yui3-sitemap-scrollable').set('scrollTop', 10000);
			});
		},
		
		addChild: function (position, target, callback, context) {
			var default_data = this.type == 'templates' ? TREENODE_TEMPLATE_DATA : TREENODE_PAGE_DATA,
				drop_data = target.get('data'),
				parent_data = target.get('parent') ? target.get('parent').get('data') : null,
				pagedata = SU.mix({}, default_data, {
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
			
			this.new_page_index = (position == 'inside' ? target.size() + 1 : (position == 'after' ? target.get('index') + 1 : target.get('index')));
			
			if (this.type == 'templates' && !pagedata.parent) {
				//Create temporary node, template will be created after layout value is set
				this.addChildNodeTemporary(pagedata);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			} else if (!pagedata.id) {
				//Create temporary node, page will be created after layout value is set
				this.addChildNodeTemporary(pagedata);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			} else {
				//Create page
				/*
				if (this.type == 'templates') {
					var call_obj = Manager.getAction('Template'),
						call_fn = 'createTemplate';
				} else {
					var call_obj = Manager.getAction('Page'),
						call_fn = 'createPage';
				}
				
				call_obj[call_fn](pagedata, function () {
					this.addChildNodeFromData.apply(this, arguments);
					if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
				}, this);
				
				this.addChildNodeFromData(pagedata);
				if (Y.Lang.isFunction(callback)) callback.apply(pagedata);
				*/
			}
			
		}
		
	});
	
	Supra.Tree.NewPagePlugin = NewPagePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['website.sitemap-flowmap-item']});
