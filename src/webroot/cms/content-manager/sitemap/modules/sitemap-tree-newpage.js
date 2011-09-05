//Invoke strict mode
"use strict";

YUI.add('website.sitemap-tree-newpage', function (Y) {
	
	
	var TREENODE_DATA = {
		'title': 'New page',
		'template': '',
		'icon': 'page',
		'path': 'new-page',
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
		
		
		createTreeNode: function (proxy, node) {
			var data = SU.mix({}, TREENODE_DATA);
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
			
			return treenode;
		},
		
		/**
		 * Constructor
		 */
		initializer: function (config) {
			var host = config.host;
			var node = config.dragNode;
			var treenode = this.createTreeNode(true, node);
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
				var drag_data = TREENODE_DATA;
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
		
		onNewPageDataLoad: function (data) {
			var page_data = SU.mix({}, TREENODE_DATA, data),
				parent_node = this.get('host').getNodeById(page_data.parent),
				parent_data = parent_node.get('data');
			
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
			
			//Open editor
			Y.later(150, this, function () {
				this.get('host').getNodeById(page_data.id).edit(null, true);
			});
		},
		
		addChild: function (position, target, callback, context) {
			var drop_data = target.get('data'),
				pagedata = SU.mix({}, TREENODE_DATA, {
					//New parent ID
					'parent': drop_data.id,
					//Item ID before which drag item was inserted
					'reference': '',
					//Page template (parent template)
					'template': (position == 'inside' ? drop_data.template : target.get('parent').get('data').template),
					//Locale
					'locale': Supra.Manager.SiteMap.languagebar.get('locale')
				});
			
			if (position == 'before') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				pagedata.reference = drop_data.id;
				pagedata.parent = parent;
			} else if (position == 'after') {
				var parent = target.get('parent');
				parent = parent ? parent.get('data').id : 0;
				
				var ref = target.next(); 
				if (ref) {
					pagedata.reference = ref.get('data').id;
				}
				
				pagedata.parent = parent;
			}
			
			
			
			this.new_page_index = (position == 'inside' ? target.size() + 1 : (position == 'after' ? target.get('index') + 1 : target.get('index')));
			
			console.log(pagedata);
			
			SU.Manager.Page.createPage(pagedata, function () {
				this.onNewPageDataLoad.apply(this, arguments);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			}, this);
		}
		
	});
	
	Supra.Tree.NewPagePlugin = NewPagePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['website.sitemap-flowmap-item']});
