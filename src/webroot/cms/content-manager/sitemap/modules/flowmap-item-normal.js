//Invoke strict mode
"use strict";

YUI.add('website.sitemap-flowmap-item-normal', function (Y) {

	var ITEM_PADDING = 10;

	function FlowMapItemNormal (config) {
		FlowMapItemNormal.superclass.constructor.apply(this, [config]);
	};
	
	FlowMapItemNormal.NAME = 'tree-node-dragable';
	
	FlowMapItemNormal.ATTRS = {
		'defaultChildType': {
			value: FlowMapItemNormal,
		}
	};
	
	Y.extend(FlowMapItemNormal, Supra.TreeNodeDragable, {
		CONTENT_TEMPLATE: '<div class="tree-node">\
			  					<div><span class="toggle hidden"></span><span class="img"><img src="/cms/supra/img/tree/none.png" /></span> <label></label> <span class="edit hidden"></div>\
			  				</div>\
			  				<ul class="tree-children">\
			  				</ul>',
		
		/**
		 * Root element type
		 * @type {Object}
		 * @private
		 */
		ROOT_TYPE: FlowMapItemNormal,
		
		/**
		 * Attach event listeners
		 */
		bindUI: function () {
			if (!Supra.Authorization.isAllowed(['page', 'order'], true)) {
				this.set('isDragable', false);
				this.set('isDropTarget', false);
			}
			
			FlowMapItemNormal.superclass.bindUI.apply(this, arguments);
			
			var node_edit = this.get('boundingBox').one('span.edit');
			node_edit.on('click', this.edit, this);
			
			this.before('addChild', this.syncUISize, this);
			this.before('removeChild', this.syncUISize, this);
		},
		
		/**
		 * Resize FlowMapItem when items are added or removed
		 * 
		 * @private
		 */
		syncUISize: function (event) {
			//Update closest flow item width by expanding or collapsing
			var flowitem = this,
				jsclass = Supra.FlowMapItem;
			
			//Find closest FlowMapItem
			while(flowitem) {
				if (flowitem instanceof jsclass) {
					break;
				}
				flowitem = flowitem.get('parent');
			}
			if (flowitem instanceof jsclass && !flowitem.isRoot()) {
				Y.later(0, this, function () {
					if (flowitem.size()) {
						flowitem.expand(true);
					} else {
						flowitem.collapse(true);
					}
				});
			}
		},
		
		/**
		 * Start editing page
		 */
		edit: function (event, newpage) {
			if (event) event.halt();
			
			var data = this.get('data'),
				node = this.get('boundingBox').one('.tree-node, .flowmap-node-inner');
			
			var plugin = SU.Manager.SiteMap.plugins.getPlugin('PluginSitemapSettings');
				plugin.showPropertyPanel(node, data, newpage);
			
			return false;
		},
		
		/**
		 * Set marker on drop target, overwrite parents setMarker to allow horizontal marking
		 * 
		 * @param {Object} target
		 * @param {Object} position
		 * @param {Object} x
		 * @param {Object} y
		 */
		setMarker: function (target, position, y, x, is_root) {
			var targetNode = target ? new Y.Node(target) : null;
			
			//Normal nodes handled by tree-node-dragable setMarker
			if (!targetNode || targetNode.hasClass('tree-node')) {
				return Supra.FlowMapItemNormal.superclass.setMarker.apply(this, arguments);
			}
			
			//Create marker node if it doesn't exist
			var marker = this.marker;
			if (!this.marker) {
				marker = this.marker = Y.Node.create('<div class="yui3-tree-node-marker"></div>');
			} else {
				Y.DOM.removeFromDOM(marker);
			}
			
			target = targetNode;
			
			var type = target.hasClass('tree-node') ? 'normal' : 'flow',
				target_label = target.one('label'),
				top = target.getY(),
				left = x,
				height = target.get('offsetHeight') + 5,
				width = 6;
			
			if (position == 'after') {
				left = x + target.get('offsetWidth') - ITEM_PADDING;
			} else if (position == 'inside') {
				left = x + ITEM_PADDING;
				width = target.get('offsetWidth') - 4 - ITEM_PADDING * 2;	//4px == 2 * 2px border
			}
			
			//Root node has bottom padding
			if (is_root) {
				height -= 30;
				left = x + ITEM_PADDING / 2;
				width += ITEM_PADDING;
			}
			
			//Update style
			marker.setStyles({
				'display': 'block',
				'top': ~~(top) + 'px',
				'left': ~~(left) + 'px',
				'height': ~~(height) + 'px',
				'width': ~~(width) + 'px'
			});
			
			//Move back to DOM
			var body = new Y.Node(document.body);
			body.append(marker);
			
			//Set current state
			this.marker_target = target;
			this.marker_position = position;
		},
		
		/**
		 * On addChild update data
		 * Handle only sorting, new items should be handled by default handler
		 * 
		 * @param {Object} event
		 */
		onAddChild: function (event) {
			//-1 index is for new items, handle only sorting
			if (event.child.get('index') == -1) return;
			
			//Check if node type needs to be changed
			var change_node = false;
			if (event.target.isRoot() !== event.child.get('parent').isRoot()) {
				change_node = true;
			}
			
			//Stop propagation, otherwise it will propagate to the root element and
			//child-parent association will be wrong
			event.stopPropagation();
			
			//Prevent default implementation, it is buggy!?
			event.preventDefault();
			
			var data = this.getTree().getIndexedData();			//all tree data
			var index = event.index;							//new index
			
			var child = event.child;							//TreeNode instance
			var child_data = child.get('data');					//drag element data
			
			var target = event.currentTarget;					//TreeNode instance
			var target_data = target.get('data');				//drop target data
			
			var parent = child.get('parent');					//TreeNode instance of old parent
			
			if (!('children' in target_data)) {
				target_data.children = [];
			}
			
			//Remove from parents children list
			if (parent) {
				parent.remove(child.get('index'));
			}
			
			//Update "parent" in data
			child_data.parent = target_data.id;
			
			if (change_node) {
				var is_drop_target = child.get('isDropTarget'),
					is_dragable = child.get('isDragable');
				
				//Small delay, otherwise new node will not be created 
				Y.later(0, this, function () {
					target.add({
						'data': child_data,
						'label': child_data.title,
						'icon': child_data.icon,
						'isDropTarget': is_drop_target,
						'isDragable': is_dragable
					}, index);
					
					//Update new parent UI
					target.syncUI();
				});
				
				//Update old parent UI
				parent.syncUI();
				child.destroy();
				
				//Insert into new parents data
				var children = target._items;
				if (Y.Lang.isNumber(index)) {
					target_data.children.splice(index, 0, child_data);
		        }  else {
					target_data.children.push(child_data);
		        }
				
				//Rest will be handled when new item will be added
				return;
			} else {
				//Insert into new parents data and new parents children list
				var children = target._items;
				if (Y.Lang.isNumber(index)) {
		            target_data.children.splice(index, 0, child_data);
					children.splice(index, 0, child);
		        }  else {
		            target_data.children.push(child_data);
					children.push(child);
		        }	
			}
			
			//Update child parent
			child._set("parent", target);
    		child.addTarget(target);
			event.index = child.get("index");
			
			//Insert node into correct position
			var sibling = null;
			if (Y.Lang.isNumber(index)) {
				sibling = target._childrenContainer.get('children').item(index);
			}
			
			if (sibling) {
				sibling.insert(child.get('boundingBox'), 'before');
			} else {
				target._childrenContainer.append(child.get('boundingBox'));
			}
			
			//Update UI
			if (parent) parent.syncUI();
			target.syncUI();
		},
		
		/**
		 * Find marker position, overwrite inherited _dragOver to allow horizontal drop
		 * 
		 * @param {Object} e
		 */
		_dragOver: function (e) {
			if (e.drag.get('node') != e.drop.get('node')) {
				var treenode = e.drop.get('treeNode');
				if (!treenode) return;
				
				var self = this.get('treeNode'),
					type = e.drop.get('node').hasClass('tree-node') ? 'normal' : 'flow',
					node = Y.Node.getDOMNode(e.drop.get('node')),
					place = 'inside',
					padding = type == 'normal' ? 8 : 20;
				
				var dragMouse = e.drag.mouseXY,
					dropRegion = e.drop.region,
					x = dropRegion.left,
					y = dropRegion.top;
				
				var is_root = treenode.isRoot();
				
				if (!is_root) {
					if (type == 'normal') {
						if (dragMouse[1] < dropRegion.top + padding) {
							place = 'before';
							y -= 1;
						} else if (!is_root && dragMouse[1] > dropRegion.bottom - padding) {
							place = 'after';
						}
					} else {
						if (dragMouse[0] < dropRegion.left + padding) {
							place = 'before';
						} else if (!is_root && dragMouse[0] > dropRegion.right - padding) {
							place = 'after';
						}
					}
				}
				
				if (node && node != self.marker_target || place != self.marker_position) {
					self.drop_target = treenode;
					self.setMarker(node, place, y, x, is_root);
				}
			}
		}
	});
	
	Supra.FlowMapItemNormal = FlowMapItemNormal;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['supra.tree', 'supra.tree-node-dragable']});