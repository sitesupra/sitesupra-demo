//Invoke strict mode
"use strict";

YUI.add('supra.page-content-list', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.PageContent;
	
	//CSS classes
	var CLASSNAME_REORDER = Y.ClassNameManager.getClassName('content', 'reorder'),		//yui3-content-reorder
		CLASSNAME_DRAGING = Y.ClassNameManager.getClassName('content', 'draging'),		//yui3-content-draging
		CLASSNAME_PROXY = Y.ClassNameManager.getClassName('content', 'proxy'),			//yui3-content-proxy
		CLASSNAME_DRAGABLE = Y.ClassNameManager.getClassName('content', 'dragable'),	//yui3-content-dragable
		CLASSNAME_OVERLAY = Y.ClassNameManager.getClassName('content', 'overlay');		//yui3-content-overlay
	
	/**
	 * Content block which is a container for other blocks
	 */
	function ContentList () {
		ContentList.superclass.constructor.apply(this, arguments);
	}
	
	ContentList.NAME = 'page-content-list';
	ContentList.CLASS_NAME = Y.ClassNameManager.getClassName(ContentList.NAME);
	ContentList.ATTRS = {};
	
	Y.extend(ContentList, Action.Proto, {
		drag_delegate: null,
		drag_selector: null,
		dragable_regions: null,
		drag_region: null,
		drag_index: 0,
		drag_order_changed: false,
		
		bindUI: function () {
			ContentList.superclass.bindUI.apply(this, arguments);
			
			//Drag & drop breaks click event
			//Capture 
			var overlay_selector = '.' + CLASSNAME_OVERLAY;
			
			this.getNode().on('click', function (e) {
				var target = e.target.closest(overlay_selector),
					overlay = null;
				
				if (!target) return;
				
				for(var id in this.children) {
					//If children is editable and is not part of drag & drop
					if (this.children[id].get('editable') && this.children[id].get('dragable')) {
						overlay = this.children[id].overlay;
						if (overlay.compareTo(target)) {
							this.get('super').set('activeContent', this.children[id]);
							break;
						}
					}
				}
			}, this);
			
			//On new block drop create it and start editing
			this.on('dragend:hit', function (e) {
				
				this.get('super').getBlockInsertData({
					'type': e.block.id,
					'placeholder_id': this.getId()
				}, this.createChildFromData, this);
				
				return false;
			}, this);
			
			this.bindOrderDnD();
		},
		
		/**
		 * Create block from data
		 * 
		 * @param {Object} data Block data
		 */
		createChildFromData: function (data) {
			var block = this.createChild({
				'id': data.id,
				'locked': false,
				'type': data.type,
				'data': data,
				'value': data.html
			}, {
				'dragable': !this.isLocked(),
				'editable': true
			});
			
			//When new item is created focus on it
			this.get('super').set('activeContent', block);
		},
		
		/**
		 * Bind drag & drop for item ordering
		 */
		bindOrderDnD: function () {
			var cont = this.getNode(),
				selector = 'div.' + CLASSNAME_DRAGABLE,
				nodes = null,
				dragable_regions = null;
			
			this.drag_selector = selector;
			
			//DD must be initialized for iframe
			Action.initDD(this.get('doc'));
			
			var del = this.drag_delegate = new Y.DD.Delegate({
				container: cont,
				nodes: selector,
				target: {},
				dragConfig: {
					haltDown: false,
					clickTimeThresh: 1000
				}
			});
			
			del.dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true,
				resizeFrame: false
			});
			
			del.dd.plug(Y.Plugin.DDConstrained, {
				constrain2node: this.getNode()
			});
			
			del.on('drag:start', this.onDragStart, this);
			del.on('drag:end', this.onDragEnd, this);
			
			//Use throttle, because drag event executes very often
			del.on('drag:drag', Y.throttle(Y.bind(this.onDragDrag, this), 50));
			
			//Restore document
			Action.initDD(document);
		},
		
		onDragStart: function (e) {
			//Add classname to list
			this.getNode().addClass(CLASSNAME_REORDER);
			
			//Add classname to proxy element
	        var proxy = e.target.get('dragNode');
			proxy.addClass(CLASSNAME_PROXY);
			
			//Resize proxy (yui3 native resize doesn't work with borders)
			var node = this.getNode();
			
			//Add classname to item which is dragged (not proxy)
			//to mark item which is dragged
			var node = e.target.get('node').next();
			if (node) node.addClass(CLASSNAME_DRAGING);
			
			//Find drop target regions
			var dragable_regions = [],
				region = null,
				order = this.children_order;
			
			for(var i=0,ii=order.length; i<ii; i++) {
				region = this.getChildRegion(order[i]);
				if (region) dragable_regions.push(region);
			}
			
			this.drag_order_changed = false;
			this.dragable_regions = dragable_regions;
		},
		
		onDragEnd: function (e) {
			//Remove classname to list
			this.getNode().removeClass(CLASSNAME_REORDER);
			
			//Remove classname from node which was dragged (not proxy node)
			var node = e.target.get('node').next();
			if (node) node.removeClass(CLASSNAME_DRAGING);
			
			//Save new order
			if (this.drag_order_changed) {
				this.drag_order_changed = false;
				this.get('super').sendBlockOrder(this, this.children_order);
			}
			
			//Clean up
			this.dragable_regions = null;
			this.dragable_regions_initial = null;
			this.drag_index = null;
			this.drag_region = null;
		},
		
		onDragDrag: function (e) {
			var drag = e.target.get('node'),
				drag_cont = null,
				drop = null,
				drop_cont = null,
				
				drag_region = null,
				drag_index = null,
				top = e.target.get('dragNode').getY(),
				regions = this.dragable_regions,
				
				direction = 0;
			
			if (!this.drag_region) {
				//Find drag region and index
				var id = drag.getData('contentId');
				
				for (var i=0,ii=regions.length; i<ii; i++) {
					if (regions[i].id == id) {
						drag_region = this.drag_region = regions[i].region;
						drag_index = this.drag_index = i;
						break;
					}
				}
			} else {
				drag_region = this.drag_region;
				drag_index = this.drag_index;
			}
			
			//Check if items can and needs to be swapped
			if (drag_index < regions.length - 1) {
				if (this.testDrop(top, drag_index, drag_index + 1)) {
					direction = 1;
				}
			}
			if (drag_index > 0) {
				if (this.testDrop(top, drag_index, drag_index - 1)) {
					direction = -1;
				}
			}
			
			if (direction !== 0) {
				drag_cont = drag.next();
				drop = this.children[regions[drag_index + direction].id].overlay;
				drop_cont = drop.next();
				
				//Swap nodes
				if (direction > 0) {
					drop_cont.insert(drag, 'after');
					drag.insert(drag_cont, 'after');
				} else {
					drop.insert(drag, 'before');
					drag.insert(drag_cont, 'after');
				}
				
				//Update region
				regions[drag_index] = this.getChildRegion(regions[drag_index].id);
				regions[drag_index + direction] = this.getChildRegion(regions[drag_index + direction].id);
				
				//Swap array items
				var item = regions.splice(drag_index, 1)[0];
				regions.splice(drag_index + direction, 0, item);
				
				//Update drag index
				this.drag_index += direction;
				this.drag_region = regions[drag_index].region;
				
				//Update order array
				var order_item_a = String(regions[drag_index].id),
					order_item_b = String(regions[drag_index + direction].id),
					order_index_a = Y.Array.indexOf(this.children_order, order_item_a),
					order_index_b = Y.Array.indexOf(this.children_order, order_item_b);
				
				this.drag_order_changed = true;
				this.children_order.splice(order_index_a, 1, order_item_b);
				this.children_order.splice(order_index_b, 1, order_item_a);
			}
		},
		
		/**
		 * Test if drag is in position to be swapped with drop
		 * 
		 * @param {Number} top Top position of drag
		 * @param {Number} drag Drag index
		 * @param {Number} drop Drop index
		 * @return True if drag and drop elements should be swapped, otherwise false
		 * @type {Boolean}
		 */
		testDrop: function (top, drag, drop) {
			var drag_region = this.dragable_regions[drag].region,
				drop_region = this.dragable_regions[drop].region,
				target_y = drop_region.top + drop_region.height / 2;
			
			if (drag < drop) {
				if (top + drag_region.height >= target_y) {
					return true;
				}
			} else {
				if (top <= target_y) {
					return true;
				}
			}
			
			return false
		},
		
		/**
		 * Returns child block region (left, top, width, height) by ID
		 * 
		 * @param {String} id Block ID
		 * @return Object with 'region' and 'id'
		 * @type {Object}
		 */
		getChildRegion: function (id) {
			if (this.children[id].get('dragable')) {
				var node = this.children[id].getNode(),
					overlay = this.children[id].overlay;
				
				overlay.setData('contentId', id);
				
				return {
					'id': id,
					'region': Y.DOM.region(Y.Node.getDOMNode(node))
				};
			} else {
				return null;
			}
		},
		
		destructor: function () {
			ContentList.superclass.destructor.apply(this, arguments);
			
			if (this.drag_delegate) {
				this.drag_delegate.destroy();
				delete(this.drag_delegate);
			}
			
			delete(this.children_order);
		}
	});
	
	Action.List = ContentList;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'dd-delegate', 'dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', 'dd-scroll']});