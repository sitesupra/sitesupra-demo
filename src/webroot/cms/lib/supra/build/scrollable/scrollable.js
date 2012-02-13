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
		},
		
		/**
		 * Scroll axis
		 */
		'axis': {
			'value': 'y'
		}
	};
	
	Y.extend(Scrollable, Y.Widget, {
		
		/**
		 * Content height
		 * @type {Number}
		 * @private
		 */
		contentSize: 0,
		
		/**
		 * Visible area height
		 * @type {Number}
		 * @private
		 */
		viewSize: 0,
		
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
		 * Scrollbar area width or height
		 * @type {Number}
		 * @private
		 */
		scrollbarAreaSize: 0,
		
		
		
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
			
			boundingBox.addClass(this.getClassName(this.get('axis')));
			
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
				scroll_distance = Math.min(this.viewSize, Math.abs(e.wheelDelta * SCROLL_DISTANCE));
			
			if (e.wheelDelta < 0) scroll_distance = -scroll_distance;
			
			node.set('scrollTop', node.get('scrollTop') - scroll_distance);
		},
		
		/**
		 * Drag start
		 */
		onDragStart: function (e) {
			this.get('dragableNode').addClass(this.getClassName('dragable-drag'));
			
			this.dragging = true;
			
			if (this.get('axis') == 'y') {
				this.dragStartPos = e.clientY;
				this.dragableStartPos = parseInt(this.get('dragableNode').getStyle('top'), 10);
				this.scrollStartPos = this.get('contentBox').get('scrollTop');
			} else {
				this.dragStartPos = e.clientX;
				this.dragableStartPos = parseInt(this.get('dragableNode').getStyle('left'), 10);
				this.scrollStartPos = -parseInt(this.get('contentBox').getStyle('margin-left'), 10);
			}
			
			
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
			
			var axis = this.get('axis'),
				mousePos = (axis == 'y' ? e.clientY : e.clientX),
				maxDragPos = this.scrollbarAreaSize - this.scrollbarSize,
				diff = Math.min(mousePos - this.dragStartPos, maxDragPos - this.dragableStartPos),
				scroll = Math.max(0, ~~(this.scrollStartPos + diff * this.pxRatio)),
				pos = ~~Math.min(Math.max(0, this.dragableStartPos + diff), maxDragPos);
			
			if (axis == 'y') {
				this.get('contentBox').set('scrollTop', scroll);
				this.get('dragableNode').setStyle('top', pos);
			} else {
				this.get('contentBox').setStyle('margin-left', -scroll);
				this.get('dragableNode').setStyle('left', pos);
			}
		},
		
		/**
		 * Update UI
		 */
		syncUI: function () {
			if (this.dragging) return;
			
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				
				contentBox = this.get('contentBox'),
				dragableNode = this.get('dragableNode'),
				scrollbarNode = this.get('scrollbarNode'),
				viewSize = contentBox.get('offset' + axisSizeProperty),
				scrollSize = contentBox.get('scroll' + axisSizeProperty) || viewSize,
				classInvisible = this.getClassName('invisible'),
				
				padding = null;
			
			if (!viewSize) return;
			if (viewSize == scrollSize) {
				if (!scrollbarNode.hasClass(classInvisible)) {
					scrollbarNode.addClass(classInvisible);
				}
			} else {
				if (scrollbarNode.hasClass(classInvisible)) {
					scrollbarNode.removeClass(classInvisible);
				}
			}
			
			if (axis == 'y') {
				padding = parseInt(scrollbarNode.getStyle('top'), 10) + parseInt(scrollbarNode.getStyle('bottom'), 10);
			} else {
				padding = parseInt(scrollbarNode.getStyle('left'), 10) + parseInt(scrollbarNode.getStyle('right'), 10);
			}
			
			var scrollbarAreaSize = viewSize - padding,
				scrollPos = null,
				scrollbarSize = ~~(viewSize / scrollSize * scrollbarAreaSize),
				pxRatio = (scrollSize - viewSize) / (scrollbarAreaSize - scrollbarSize);
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
			} else {
				scrollPos = - parseInt(contentBox.getStyle('margin-left'), 10);
			}
			
			this.pxRatio = pxRatio;
			this.scrollbarAreaSize = scrollbarAreaSize;
			this.scrollbarSize = scrollbarSize;
			this.contentSize = scrollSize;
			this.viewSize = viewSize;
			
			if (axis == 'y') {
				dragableNode.setStyles({
					'height': scrollbarSize,
					'top': ~~(scrollPos / pxRatio)
				});
			} else {
				//Make sure all content is in view
				if (viewSize + scrollPos > scrollSize) {
					scrollPos = Math.max(0, scrollSize - viewSize);
					contentBox.setStyle('margin-left', - scrollPos + 'px');
				}
				
				dragableNode.setStyles({
					'width': scrollbarSize,
					'left': ~~(scrollPos / pxRatio)
				});
			}
		},
		
		/**
		 * Sync scrollbar position
		 * 
		 * @private
		 */
		syncUIPosition: function () {
			if (this.dragging) return;
			
			var axis = this.get('axis'),
				
				contentBox = this.get('contentBox'),
				dragableNode = this.get('dragableNode'),
				scrollPos = 0;
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
				dragableNode.setStyle('top', ~~(scrollPos / this.pxRatio));
			} else {
				scrollPos = -parseInt(contentBox.get('margin-left'), 10);
				dragableNode.setStyle('left', ~~(scrollPos / this.pxRatio));
			}
		},
		
		/**
		 * Check if node is in view
		 * 
		 * @param {Object} node Node
		 * @return True if node is fully visible, otherwise false
		 * @type {Boolean}
		 */
		isInView: function (node) {
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				axisPosProperty  = (axis == 'y' ? 'Top' : 'Left'),
				
				contentBox = this.get('contentBox'),
				scrollPos = contentBox.get('scroll' + axisPosProperty),
				viewSize = this.viewSize,
				
				size = node.get('offset' + axisSizeProperty),
				pos = node.get('offset' + axisPosProperty);
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
			} else {
				scrollPos = -parseInt(contentBox.get('margin-left'), 10);
			}
			
			if (pos >= scrollPos && (pos + size) < scrollPos + viewSize) {
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
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				axisPosProperty  = (axis == 'y' ? 'Top' : 'Left'),
				
				contentBox = this.get('contentBox'),
				scrollPos = contentBox.get('scroll' + axisPosProperty),
				viewSize = this.viewSize,
				
				size = node.get('offset' + axisSizeProperty),
				pos = node.get('offset' + axisPosProperty);
				
			if (pos < scrollPos) {
				contentBox.set('scroll' + axisPosProperty, pos);
				this.syncUIPosition();
			} else if ((pos + height) > (scrollPos + viewSize)) {
				contentBox.set('scroll' + axisPosProperty, pos + size - viewSize);
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