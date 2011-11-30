YUI().add('supra.htmleditor-plugin-image-resize', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [SU.HTMLEditor.MODE_SIMPLE, SU.HTMLEditor.MODE_RICH]
	};
	
	var MOUSE_EVENT_DELAY = 250,
		MIN_W = 16,
		MIN_H = 16;
	
	var Manager = Supra.Manager;
	 
	Supra.HTMLEditor.addPlugin('image-resize', defaultConfiguration, {
		
		/**
		 * Resize handle node
		 * @type {Object}
		 */
		handle_node: null,
		
		/**
		 * 
		 */
		visible: false,
		
		/**
		 * Event listeners
		 * @type {Array}
		 */
		events: [],
		
		/**
		 * Mouse over/out timer
		 * @type {Object}
		 */
		mouse_timer: null,
		
		/**
		 * Target node
		 * @type {Object}
		 */
		target_node: null,
		
		/**
		 * Initial image and mouse properties
		 */
		img_w: null,
		img_h: null,
		last_image_w: null,
		last_image_h: null,
		mouse_x: null,
		mouse_y: null,
		handle_x: null,
		handle_y: null,
		last_handle_x: null,
		last_handle_y: null,
		min_diff_x: null,
		event_move: null,
		resizing: false,
		
		
		/**
		 * Show resize handle
		 */
		showResizeHandle: function (e) {
			if (this.resizing || this.htmleditor.get('disabled')) return;
			
			var target = e.target;
			if (target && target.test('img') && !target.hasClass('gallery')) {
				if (this.mouse_timer) {
					this.mouse_timer.cancel();
				}
				
				var xy = target.getXY();
				var pos = [xy[0] + target.get('offsetWidth'), xy[1] + target.get('offsetHeight')];
				
				this.last_handle_x = this.handle_x = pos[0];
				this.last_handle_y = this.handle_y = pos[1];
				
				this.target_node = target;
				this.handle_node.setStyles({
					'left': pos[0] + 'px',
					'top': pos[1] + 'px',
					'display': 'block'
				});
				
			} else if (target && target.test('a.yui3-image-resize-handle')) {
				if (this.mouse_timer) {
					this.mouse_timer.cancel();
				}
			}
		},
		
		/**
		 * Hide resize handle
		 */
		hideResizeHandle: function (e) {
			if (this.resizing) return;
			if (this.handle_node) {
				this.handle_node.setStyle('display', 'none');
				this.target_node = null;
			}
		},
		
		/**
		 * Hide resize handle after small timeout 
		 */
		hideResizeHandleDelayed: function (e) {
			if (this.resizing) return;
			if (this.mouse_timer) this.mouse_timer.cancel();
			this.mouse_timer = Y.later(MOUSE_EVENT_DELAY, this, this.hideResizeHandle);
		},
		
		/**
		 * Handle drag start
		 */
		dragStart: function (e) {
			this.resizing = true;
			
			this.img_w = this.target_node.get('offsetWidth'),
			this.img_h = this.target_node.get('offsetHeight'),
			this.mouse_x = e.clientX,
			this.mouse_y = e.clientY;
			
			//Calculate minimal diff_x
			var ratio = this.img_w / this.img_h,
				diff_x = MIN_W - this.img_w,
				diff_y = Math.round(diff_x / ratio);
			
			if (MIN_H < (this.img_h + diff_y)) {
				diff_y = MIN_H - this.img_h;
				diff_x = Math.round(diff_y * ratio);
			}
			
			this.min_diff_x = diff_x;
			
			Y.one(this.htmleditor.get('doc')).once('mouseup', this.dragEnd, this);
			this.event_move = Y.one(this.htmleditor.get('doc')).on('mousemove', this.dragDrag, this);
			
			e.halt();
		},
		
		dragDrag: function (e) {
			var ratio = this.img_w / this.img_h,
				diff_x = Math.max(this.min_diff_x, e.clientX - this.mouse_x, e.clientY - this.mouse_y),
				diff_y = Math.round(diff_x / ratio);
			
			this.last_image_w = this.img_w + diff_x;
			this.last_image_h = this.img_h + diff_y;
			this.last_handle_x = this.handle_x + diff_x;
			this.last_handle_y = this.handle_y + diff_y;
			
			this.target_node.setAttribute('width', this.last_image_w + 'px');
			this.target_node.setAttribute('height', this.last_image_h + 'px');
			
			this.handle_node.setStyles({
				'left': this.last_handle_x + 'px',
				'top': this.last_handle_y + 'px'
			});
			
			//Update 'image' plugin form values
			this.throttleUpdateImagePluginUI();
		},
		
		dragEnd: function () {
			this.resizing = false;
			this.event_move.detach();
			this.event_move = null;
			
			this.handle_x = this.last_handle_x;
			this.handle_y = this.last_handle_y;
			
			//Save changes
			if (this.img_w != this.last_image_w) {
				//Update data
				var data = this.htmleditor.getData(this.target_node);
				data.size_width = this.last_image_w;
				data.size_height = this.last_image_h;
				this.htmleditor.setData(this.target_node, data);
				
				//Property changed, update editor 'changed' state
				this.htmleditor._changed();
				
				//Update 'image' plugin form values
				this.updateImagePluginUI();
			}
			
			this.hideResizeHandleDelayed();
		},
		
		/**
		 * Update width and height input values in image settings form
		 */
		updateImagePluginUI: function () {
			var plugin = this.htmleditor.getPlugin('image'),
				form = plugin.settings_form;
			
			if (form && form.get('visible')) {
				plugin.silent = true;
				form.getInput('size_width').set('value', this.last_image_w);
				form.getInput('size_height').set('value', this.last_image_h);
				plugin.silent = false;
			}
		},
		
		throttleUpdateImagePluginUI: null,
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			
			this.throttleUpdateImagePluginUI = Y.throttle(Y.bind(this.updateImagePluginUI, this), 60);
			
			// On mouse over/out show/hide handle node
			var container = htmleditor.get('srcNode'),
				events = this.events = [],
				node = null;
			
			events.push(container.delegate('mouseenter', Y.bind(this.showResizeHandle, this), 'img'));
			events.push(container.delegate('mouseleave', Y.bind(this.hideResizeHandleDelayed, this), 'img'));
			
			this.handle_node = node = Y.Node.create('<a class="yui3-image-resize-handle"></a>');
			Y.Node(htmleditor.get('doc')).one('body').prepend(this.handle_node);
			
			events.push(node.on('mouseenter', this.showResizeHandle, this));
			events.push(node.on('mouseleave', this.hideResizeHandleDelayed, this));
			
			//Handle drag
			events.push(node.on('mousedown', this.dragStart, this));
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			if (this.handle_node) {
				this.handle_node.remove();
				delete(this.handle_node);
			}
			
			//Remove events
			var events = this.events;
			for(var i=0,ii=events.length; i<ii; i++) {
				events[i].detach();
			}
			
			this.events = [];
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});