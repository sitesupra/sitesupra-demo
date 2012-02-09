//Invoke strict mode
"use strict";
	
YUI.add('supra.scrollable', function (Y) {
	
	var SCROLL_DISTANCE = 100;
	
	
	
	function Scrollable (config) {
		Scrollable.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Scrollable.NAME = 'scrollable';
	Scrollable.CSS_PREFIX = 'su-' + Scrollable.NAME;
	
	Scrollable.ATTRS = {
		'scrollbarNode': {
			value: null
		},
		'dragableNode': {
			value: null
		},
		
		/**
		 * Use throttle
		 */
		'throttle': {
			'value': 100
		}
	};
	
	Y.extend(Scrollable, Y.Widget, {
		
		/**
		 * Content height
		 * @type {Number}
		 * @private
		 */
		contentHeight: 0,
		
		/**
		 * Visible area height
		 * @type {Number}
		 * @private
		 */
		viewHeight: 0,
		
		/**
		 * Pixel ration between scrollbar and view
		 * pixels
		 * @type {Number}
		 * @private
		 */
		pxRatio: 0,
		
		/**
		 * User is dragging scrollbar
		 * @type {Boolean}
		 * @private
		 */
		dragging: false,
		
		/**
		 * Scrollbar area height
		 * @type {Number}
		 * @private
		 */
		scrollbarAreaHeight: 0,
		
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			Scrollable.superclass.renderUI.apply(this, arguments);
			
			var boundingBox = this.get('boundingBox'),
				dragableNode = null,
				scrollbarNode = Y.Node.create('\
									<div class="' + this.getClassName('scrollbar') + ' ' + this.getClassName('invisible') + '">\
										<div class="' + this.getClassName('dragable') + '"></div>\
									</div>');
			
			dragableNode = scrollbarNode.one('div');
			boundingBox.append(scrollbarNode);
			
			this.set('scrollbarNode', scrollbarNode);
			this.set('dragableNode', dragableNode);
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Scrollable.superclass.bindUI.apply(this, arguments);
			
			this.bindUIResize();
			
			//Scroll
			var throttle = this.get('throttle');
			if (throttle) {
				this.syncUIPositionThrottled = this.throttle(this.syncUIPosition, this.get('throttle'), this);
			} else {
				this.syncUIPositionThrottled = Y.bind(this.syncUIPosition, this);
			}
			
			//Mouse wheel
			var node = this.get('contentBox');
			node.on('mousewheel', Y.bind(this.onMouseWheel, this));
			node.on('scroll', this.syncUIPositionThrottled);
			
			//Drag and drop
			node = this.get('dragableNode');
			node.on('mousedown', this.onDragStart, this);
		},
		
		/**
		 * Rind resize event listeners
		 * 
		 * @private
		 */
		bindUIResize: function () {
			//On resize update scrollbars
			var throttle = this.get('throttle');
			if (throttle) {
				this.syncUIThrottled = this.throttle(this.syncUI, this.get('throttle'), this);
			} else {
				this.syncUIThrottled = Y.bind(this.syncUI, this);
			}
			
			var node = this.get('contentBox');
			node.on('contentResize', this.syncUIThrottled);
			node.get('parentNode').on('contentResize', this.syncUIThrottled);
			
			node = node.closest('.left-container, .right-container');
			if (node) {
				node.on('contentResize', this.syncUIThrottled);
			}
			
			Y.on('resize', this.syncUIThrottled);
		},
		
		/**
		 * Handle mouse wheel scrolling
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onMouseWheel: function (e) {
			//Check if mouse was scrolled inside container
			var node = e.target.closest('.su-scrollable-content');
			if (node !== this.get('contentBox')) {
				return;
			}
			
			//Update scroll position
			var node = this.get('contentBox'),
				scroll_distance = Math.min(this.viewHeight, Math.abs(e.wheelDelta * SCROLL_DISTANCE));
			
			if (e.wheelDelta < 0) scroll_distance = -scroll_distance;
			
			node.set('scrollTop', node.get('scrollTop') - scroll_distance);
		},
		
		/**
		 * Drag start
		 */
		onDragStart: function (e) {
			this.get('dragableNode').addClass(this.getClassName('dragable-drag'));
			
			this.dragging = true;
			this.dragStartY = e.clientY;
			this.dragableStartY = parseInt(this.get('dragableNode').getStyle('top'), 10);
			this.scrollStartY = this.get('contentBox').get('scrollTop');
			
			var doc = Y.Node(document);
			this.dragMoveEvent = doc.on('mousemove', this.onDrag, this);
			this.dragEndEvent = doc.on('mouseup', this.onDragEnd, this);
			
			e.halt();
		},
		
		/**
		 * On drag end remove listeners, etc.
		 */
		onDragEnd: function (e) {
			if (this.dragging) {
				this.dragging = false;
				
				this.dragMoveEvent.detach();
				this.dragMoveEvent = null;
				this.dragEndEvent.detach();
				this.dragEndEvent = null;
				
				this.get('dragableNode').removeClass(this.getClassName('dragable-drag'));
			}
		},
		
		onDrag: function (e) {
			if (!this.dragging) return;
			
			var diff = e.clientY - this.dragStartY,
				scroll = Math.max(0, ~~(this.scrollStartY + diff * this.pxRatio)),
				maxDragTop = this.scrollbarAreaHeight - this.scrollbarHeight,
				pos = ~~Math.min(Math.max(0, this.dragableStartY + diff), maxDragTop);
			
			this.get('contentBox').set('scrollTop', scroll);
			this.get('dragableNode').setStyle('top', pos);
		},
		
		/**
		 * Update UI
		 */
		syncUI: function () {
			if (this.dragging) return;
			
			var contentBox = this.get('contentBox'),
				dragableNode = this.get('dragableNode'),
				scrollbarNode = this.get('scrollbarNode'),
				height = contentBox.get('offsetHeight'),
				scrollHeight = contentBox.get('scrollHeight') || height,
				classInvisible = this.getClassName('invisible');
			
			if (!height) return;
			if (height == scrollHeight) {
				if (!scrollbarNode.hasClass(classInvisible)) {
					scrollbarNode.addClass(classInvisible);
				}
			} else {
				if (scrollbarNode.hasClass(classInvisible)) {
					scrollbarNode.removeClass(classInvisible);
				}
			}
			
			var padding = parseInt(scrollbarNode.getStyle('top'), 10) + parseInt(scrollbarNode.getStyle('bottom'), 10),
				scrollbarAreaHeight = height - padding,
				scrollTop = contentBox.get('scrollTop'),
				scrollbarHeight = ~~(height / scrollHeight * scrollbarAreaHeight),
				pxRatio = (scrollHeight - height) / (scrollbarAreaHeight - scrollbarHeight);
			
			this.pxRatio = pxRatio;
			this.scrollbarAreaHeight = scrollbarAreaHeight;
			this.scrollbarHeight = scrollbarHeight;
			this.contentHeight = scrollHeight;
			this.viewHeight = height;
			
			dragableNode.setStyles({
				'height': scrollbarHeight,
				'top': ~~(scrollTop / pxRatio)
			});
		},
		
		/**
		 * Sync scrollbar position
		 * 
		 * @private
		 */
		syncUIPosition: function () {
			if (this.dragging) return;
			
			var contentBox = this.get('contentBox'),
				dragableNode = this.get('dragableNode'),
				scrollTop = contentBox.get('scrollTop');
			
			dragableNode.setStyles({
				'top': ~~(scrollTop / this.pxRatio)
			});
		},
		
		/**
		 * Check if node is in view
		 * 
		 * @param {Object} node Node
		 * @return True if node is fully visible, otherwise false
		 * @type {Boolean}
		 */
		isInView: function (node) {
			var contentBox = this.get('contentBox'),
				scrollTop = contentBox.get('scrollTop'),
				viewHeight = this.viewHeight,
				
				height = node.get('offsetHeight'),
				top = node.get('offsetTop');
				
			if (top >= scrollTop && (top + height) < scrollTop + viewHeight) {
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Scroll ndoe in view
		 * 
		 * @param {Object} node Node
		 * @return True if scrolled to the node, false if node was already in view
		 * @type {Boolean}
		 */
		scrollInView: function (node) {
			var contentBox = this.get('contentBox'),
				scrollTop = contentBox.get('scrollTop'),
				viewHeight = this.viewHeight,
				
				height = node.get('offsetHeight'),
				top = node.get('offsetTop');
				
			if (top < scrollTop) {
				contentBox.set('scrollTop', top);
				this.syncUIPosition();
			} else if ((top + height) > (scrollTop + viewHeight)) {
				contentBox.set('scrollTop', top + height - viewHeight);
				this.syncUIPosition();
			} else {
				return false;
			}
		},
		
		/**
		 * Throttle function call
		 * 
		 * @param {Function} fn
		 * @param {Number} ms
		 * @param {Object} context
		 */
		throttle: function (fn, ms, context) {
			ms = (ms) ? ms : 150;
			
			if (true || ms === -1) {
				return (function() {
					fn.apply(context, arguments);
				});
			}
			
			var last = (new Date()).getTime();
			var t = null;
			
			return (function() {
				var now = (new Date()).getTime();
				if (now - last > ms) {
					last = now;
					fn.apply(context, arguments);
					clearTimeout(t);
				} else {
					clearTimeout(t);
					t = setTimeout(arguments.callee, ms);
				}
			});
		}
	});
	
	Supra.Scrollable = Scrollable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget']});