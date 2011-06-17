YUI.add('supra.tree-node-dragable', function(Y) {
	
	function TreeNodeDragable (config) {
		TreeNodeDragable.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	TreeNodeDragable.NAME = 'tree-node-dragable';
	TreeNodeDragable.ATTRS = {
		/**
		 * Node can be dragged
		 */
		'isDragable': {
			value: true,
			setter: '_setDragable'
		},
		/**
		 * Inside node, before and after can be dropped 
		 * other nodes
		 */
		'isDropTarget': {
			value: true
		},
		
		/**
		 * Child class
		 */
		'defaultChildType': {  
            value: TreeNodeDragable
        }
	};
	
	Y.extend(TreeNodeDragable, Supra.TreeNode, {
		ROOT_TYPE: TreeNodeDragable,
		
		dd: null,
		
		marker: null,
		marker_position: 'before',
		marker_target: null,
		drop_target: null,
		
		renderUI: function () {
			TreeNodeDragable.superclass.renderUI.apply(this, arguments);
			
			if (this.get('isDragable')) {
				this.get('boundingBox').one('div.tree-node').addClass('isdragable');
			}
		},
		
		setMarker: function (target, position, y) {
			//If empty, then remove target
			if (!target) {
				if (this.marker) {
					this.marker.remove();
					this.marker = null;
					this.marker_target = null;
					this.drop_target = null;
				}
				return;
			}
			
			//Create marker node if it doesn't exist
			var market = this.marker;
			if (!this.marker) {
				marker = this.marker = Y.Node.create('<div class="yui3-tree-node-marker"></div>');
			} else {
				Y.DOM.removeFromDOM(marker);
			}
			
			target = new Y.Node(target);
			
			var target_label = target.one('label');
			var top = y,
				left = target_label.getX() - 23,
				height = 1;
				width = target_label.get('offsetWidth') + 24;
			
			if (position == 'after') {
				top = y + target.ancestor().get('offsetHeight') - 1;
			} else if (position == 'inside') {
				height = 24;
			}
			
			//Update style
			marker.setStyles({
				'display': 'block',
				'top': ~~(top) + 'px',
				'left': ~~(left) + 'px',
				'height': ~~(height) + 'px',
				'width': width + 'px'
			});
			
			//Move back to DOM
			var body = new Y.Node(document.body);
			body.append(marker);
			
			//Set current state
			this.marker_target = target;
			this.marker_position = position;
		},
		
		/**
		 * Lock all children D&D targets
		 */
		lockChildren: function () {
			this.each(function () {
				if (this.dd) {
					this.dd.set('lock', true);
					if (this.dd.target) {
						this.dd.target.set('lock', true);
					}
				}
				this.lockChildren();
			});
		},
		
		/**
		 * Unlock all children D&D targets
		 */
		unlockChildren: function () {
			this.each(function () {
				if (this.dd) {
					this.dd.set('lock', false);
					if (this.dd.target) {
						this.dd.target.set('lock', false);
					}
				}
				this.unlockChildren();
			});
		},
		
		/**
		 * Lock children to prevent this node from being droped on children
		 * 
		 * @param {Object} e Event
		 */
		_afterMouseDown: function (e) {
			this.get('treeNode').lockChildren();
		},
		
		/**
		 * Adjust proxy position
		 * 
		 * @param {Object} e Event
		 */
		_dragStart: function (e) {
			this._setStartPosition([ this.realXY[0] , this.nodeXY[1] + 6 ]);
			this.get('dragNode').addClass('yui3-tree-node-proxy');
		},
		
		/**
		 * Hide marker node
		 * 
		 * @param {Object} e Event
		 */
		_dragExit: function (e) {
			this.get('treeNode').setMarker(null);
		},
		
		/**
		 * Find marker position
		 * 
		 * @param {Object} e
		 */
		_dragOver: function(e){
			if (e.drag.get('node') != e.drop.get('node')) {
				var self = this.get('treeNode');
				var node = Y.Node.getDOMNode(e.drop.get('node'));
				var place = 'inside';
				var padding = 8;
				
				var dragMouse = e.drag.mouseXY;
				var dropRegion = e.drop.region;
				var y = dropRegion.top;
				
				var is_root = e.drop.get('treeNode').isRoot();
				
				if (!is_root) {
					if (dragMouse[1] < dropRegion.top + padding) {
						place = 'before';
						y -= 1;
					} else if (!is_root && dragMouse[1] > dropRegion.bottom - padding) {
						place = 'after';
					}
				}
				
				if (node && node != self.marker_target || place != self.marker_position) {
					self.drop_target = e.drop.get('treeNode');
					self.setMarker(node, place, y);
				}
			}
		},
		
		/**
		 * 
		 * @param {Object} e
		 */
		_dragEnd: function(e){
			var self = this.get('treeNode');
			
			if (self.drop_target) {
				var tree = self.getTree();
				var target = self.drop_target
				var drag_data = self.get('data');
				var drop_data = target.get('data');
				var position = self.marker_position;
				
				//Fire drop event
				var event = tree.fire('drop', {'drag': drag_data, 'drop': drop_data, 'position': position});
				
				//If event was not prevented, then move node
				if (event) {
					if (position == 'inside') {
						target.add(self);
					} else {
						var index = target.get('index');
						if (position == 'after') {
							index++;
						}
						target.get('parent').add(self, index);
					}
				}
			}
			
			//Hide marker and cleanup data
			self.setMarker(null);
			
			//Unlock children to allow them being draged
			self.unlockChildren();
			
			//Make sure node is not actually moved
			e.preventDefault();
		},
		
		expand: function () {
			TreeNodeDragable.superclass.expand.apply(this, arguments);
			this.unlockChildren();
		},
		
		bindUI: function () {
			TreeNodeDragable.superclass.bindUI.apply(this, arguments);
			
			if (this.get('isDragable')) {
				var node = this.get('boundingBox');
				var treenode = node.one('div.tree-node');
				
				var dd = this.dd = new Y.DD.Drag({
					node: treenode,
					dragMode: 'point',
					target: this.get('isDropTarget'),
					treeNode: this
				}).plug(Y.Plugin.DDProxy, {
					moveOnEnd: false,			// Don't move original node at the end of drag
					cloneNode: true
				});
				
				dd.set('treeNode', this);
				
				if (dd.target) {
					dd.target.set('treeNode', this);
				}
				
				//When starting drag all children must be locked to prevent
				//parent drop inside children
				dd.on('drag:afterMouseDown', this._afterMouseDown);
				
				//Set special style to proxy node
				dd.on('drag:start', this._dragStart);
				
				// When we leave drop target hide marker
				dd.on('drag:exit', this._dragExit);
				
				// When we move mouse over drop target update marker
				dd.on('drag:over', this._dragOver);
				
				dd.on('drag:end', this._dragEnd);
				
			} else if (this.get('isDropTarget')) {
				var node = this.get('boundingBox');
				var treenode = node.one('div.tree-node');
				
				var dd = this.dd = new Y.DD.Drop({
					node: treenode,
					treeNode: this
				});
				dd.set('treeNode', this);
			}
		},
		
		_setDragable: function (val) {
			if (val && val != this.get('isDragable')) {
				if (val) {
					this.get('boundingBox').one('div.tree-node').addClass('isdragable');
				} else {
					this.get('boundingBox').one('div.tree-node').removeClass('isdragable');
				}
			}
			
			return !!val;
		}
	});
	
	
	Supra.TreeNodeDragable = TreeNodeDragable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['dd', 'supra.tree-node']});