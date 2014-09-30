YUI.add('supra.page-content-droptarget', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	
	/*
	 * Properties plugin
	 */
	function DropTarget (config) {
		var attrs = {
			//Node to which will be attached listeners
			'srcNode': {value: null},
			
			//srcNode document element
			'doc': {value: null}
		};
				
		this.addAttrs(attrs, config || {});
		this.bindDnD();
	}
	
	DropTarget.prototype = {
		
		/**
		 * Item which is beeing dragged
		 * @type {Object}
		 */
		drag_item: null,
		
		/**
		 * Drag end event listener attach point
		 * @type {Object}
		 */
		fn_drag_end: null,
		
		/**
		 * Drag & drop mouse up event listener attach point
		 * @type {Object}
		 */
		fn_mouse_up: null,
		
		
		destroy: function () {
			// Remove callbacks
			this.onDragEnd();
			
			// Purge source node events
			var node = this.get('srcNode');
			node.purge();
			
			// Clean up
			this.set('srcNode', null);
			this.detachAll();
		},
		
		/**
		 * Returns item data which is being dragged
		 * 
		 * @return Item data
		 * @type {Object}
		 */
		getItem: function () {
			return this.drag_item;
		},
		
		/**
		 * Handle drag end (success or failure)
		 */
		onDragEnd: function () {
			this.drag_item = null;
			if (this.fn_drag_end) this.fn_drag_end.detach();
			if (this.fn_mouse_up) this.fn_mouse_up.detach();
			this.fn_drag_end = null;
			this.fn_mouse_up = null;
		},
		
		/**
		 * Bind HTML5 Drag & Drop event listeners
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 */
		bindDnD: function () {
			//Allow HTML5 Drag & Drop
			var srcNode = this.get('srcNode');
			
			//Handle item drag which is in content
			srcNode.on('dragstart', function (e) {
				this.drag_item = e.target;
				
				//On mouse up or drag end remove temporary listeners and
				//reference to item
				this.fn_mouse_up = srcNode.once('mouseup', this.onDragEnd, this);
				this.fn_drag_end = e.target.once('dragend', this.onDragEnd, this);
			}, this);
			srcNode.on('dragend', this.onDragEnd, this);
			
			//On dragover change cursor to copy and prevent native drop
			srcNode.on('dragover', function (e) {
				//If event propagation was stopped then don't do anything
				if (e.stopped) return;
				
				// Prevent data from actually beeing dropped and change cursor
				if (e.preventDefault) e.preventDefault();
			    e._event.dataTransfer.dropEffect = 'copy';
			    return false;
			}, this);
			
			//Handle drop event (triggered only if native drop was prevented in dragover) 
			srcNode.on('drop', function (e) {
				var data = e._event.dataTransfer.getData('text'),
					item = this.drag_item,
					target = null;
				
				this._fixTarget(e);
				target = e.target;
				
				//Trigger event to allow other plugins to override this behaviour
				var res = srcNode.fire('dataDrop', {
					'drag_id': data,
					'drag': item,
					'drop': target
				});
				
				//Clean up
				this.onDragEnd();
				
				//If any listener called returned false then stop item from being
				//dropped using native drop
				if (res === false) {
					if (e.preventDefault) e.preventDefault(); // Don't drop anything
					return false;
				}
			}, this);
		},
		
		/**
		 * IE reports srcNode as target, get correct drop target from mouse position
		 * 
		 * @param {Object} e
		 * @private
		 */
		_fixTarget: (Y.UA.ie ? function (e) {
			//IE reports srcNode as target, fix it
			var srcNode = this.get('srcNode'),
				target = null,
				tmp_target = null,
				pos = srcNode.getXY(),
				src_dom_node = Y.Node.getDOMNode(srcNode),
				scroll_x = this.get('doc').documentElement.scrollLeft,
				scroll_y = this.get('doc').documentElement.scrollTop;
			
			target = this.get('doc').elementFromPoint(e._event.x + pos[0] - scroll_x, e._event.y + pos[1] - scroll_y);
			tmp_target = target;
			
			//Check if srcNode is target or one of the targets ancestors
			while(tmp_target) {
				if (tmp_target === src_dom_node) {
					e.target = new Y.Node(target);
				}
				tmp_target = tmp_target.parentNode;
			}
		} : function (e) {}),
		
	};
	
	Y.augment(DropTarget, Y.Attribute);
	
	Manager.PageContent.PluginDropTarget = DropTarget;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['attribute']});