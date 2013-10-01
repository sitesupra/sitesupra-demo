YUI.add('supra.scrollable', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var SCROLL_DISTANCE = 35;
	
	
	
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
		'draggableNode': {
			value: null
		},
		
		/**
		 * Disabled state
		 */
		'disabled': {
			'value': false
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
		},
		
		/**
		 * Minimal size of the handle
		 */
		'minHandleSize': {
			'value': 50
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
		 * Animation object for content
		 * @type {Object}
		 * @private
		 */
		animContent: null,
		
		/**
		 * Animation object for scrollbar
		 * @type {Object}
		 * @private
		 */
		animScrollBar: null,
		
		/**
		 * Resize event listener
		 * @type {Object}
		 * @private
		 */
		resizeListener: null,
		
		
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		renderUI: function () {
			Scrollable.superclass.renderUI.apply(this, arguments);
			
			var boundingBox = this.get('boundingBox'),
				draggableNode = null,
				scrollbarNode = Y.Node.create('\
									<div class="' + this.getClassName('scrollbar') + ' ' + this.getClassName('invisible') + '">\
										<div class="' + this.getClassName('scrollbar', 'background') + '">\
											<div class="' + this.getClassName('draggable') + '"></div>\
										</div>\
									</div>');
			
			boundingBox.addClass(this.getClassName(this.get('axis')));
			
			draggableNode = scrollbarNode.one('div div');
			boundingBox.append(scrollbarNode);
			
			this.set('scrollbarNode', scrollbarNode);
			this.set('draggableNode', draggableNode);
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
				this.syncUIPositionThrottled = Supra.throttle(this.syncUIPosition, this.get('throttle'), this);
			} else {
				this.syncUIPositionThrottled = Y.bind(this.syncUIPosition, this);
			}
			
			//Mouse wheel
			var node = this.get('contentBox');
			node.on('mousewheel', Y.bind(this.onMouseWheel, this));
			node.on('scroll', this.syncUIPositionThrottled);
			
			//Drag and drop
			node = this.get('draggableNode');
			node.on('mousedown', this.onDragStart, this);
			
			//Disabled state
			this.after('disabledChange', this.onDisabledChange, this);
			
			this.after('renderedChange', this.syncUI, this);
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
				this.syncUIThrottled = Supra.throttle(this.syncUI, this.get('throttle'), this);
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
			
			this.resizeListener = Y.on('resize', this.syncUIThrottled);
		},
		
		/**
		 * Handle mouse wheel scrolling
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		onMouseWheel: function (e) {
			if (this.get('disabled')) return;
			
			//Check if mouse was scrolled inside container
			var node = e.target.closest('.su-scrollable-content');
			if (node !== this.get('contentBox')) {
				return;
			}
			
			//Update scroll position
			var node = this.get('contentBox'),
				scroll_distance = Math.min(this.viewSize, Math.abs(e.wheelDelta * SCROLL_DISTANCE));
			
			if (e.wheelDelta < 0) scroll_distance = -scroll_distance;
			if (scroll_distance) {
				
				node.set('scrollTop', node.get('scrollTop') - scroll_distance);
				
			}
		},
		
		/**
		 * Drag start
		 */
		onDragStart: function (e) {
			if (this.get('disabled')) return;
			
			this.get('draggableNode').addClass(this.getClassName('draggable-drag'));
			
			this.dragging = true;
			
			if (this.get('axis') == 'y') {
				this.dragStartPos = e.clientY;
				this.draggableStartPos = parseInt(this.get('draggableNode').getStyle('top'), 10);
				this.scrollStartPos = this.get('contentBox').get('scrollTop');
			} else {
				this.dragStartPos = e.clientX;
				this.draggableStartPos = parseInt(this.get('draggableNode').getStyle('left'), 10);
				this.scrollStartPos = -parseInt(this.get('contentBox').getStyle('marginLeft'), 10);
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
				
				this.get('draggableNode').removeClass(this.getClassName('draggable-drag'));
				
				this.fire('sync');
			}
		},
		
		onDrag: function (e) {
			if (!this.dragging) return;
			
			var axis = this.get('axis'),
				mousePos = (axis == 'y' ? e.clientY : e.clientX),
				maxDragPos = this.scrollbarAreaSize - this.scrollbarSize,
				diff = Math.min(mousePos - this.dragStartPos, maxDragPos - this.draggableStartPos),
				scroll = Math.max(0, ~~(this.scrollStartPos + diff * this.pxRatio)),
				pos = ~~Math.min(Math.max(0, this.draggableStartPos + diff), maxDragPos);
			
			if (axis == 'y') {
				this.get('contentBox').set('scrollTop', scroll);
				this.get('draggableNode').setStyle('top', pos);
			} else {
				this.get('contentBox').setStyle('marginLeft', -scroll);
				this.get('draggableNode').setStyle('left', pos);
			}
			
			this.fire('drag');
		},
		
		/**
		 * Update UI
		 */
		syncUI: function () {
			if (this.dragging || this.get('disabled') || !this.get('rendered')) return;
			
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				
				contentBox = this.get('contentBox'),
				draggableNode = this.get('draggableNode'),
				scrollbarNode = this.get('scrollbarNode'),
				backgroundNode = draggableNode.ancestor(),
				viewSize = contentBox.get('offset' + axisSizeProperty),
				scrollSize = contentBox.get('scroll' + axisSizeProperty) || viewSize,
				classInvisible = this.getClassName('invisible'),
				
				padding = null;
			
			if (!viewSize) return;
			
			//Gecho doesn't returns correct scrollWidth value
			if (axis == 'x' && Y.UA.gecko) {
				scrollSize = viewSize;
				
				contentBox.get('children').each(function (item) {
					scrollSize = Math.max(scrollSize, item.get('offsetLeft') + item.get('offsetWidth'));
				});
			}
			
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
				padding = parseInt(backgroundNode.getStyle('top'), 10) + parseInt(backgroundNode.getStyle('bottom'), 10) || 0;
			} else {
				padding = parseInt(backgroundNode.getStyle('left'), 10) + parseInt(backgroundNode.getStyle('right'), 10) || 0;
			}
			
			var scrollbarAreaSize = viewSize - padding,
				scrollPos = null,
				scrollbarSize = Math.max(this.get('minHandleSize'), ~~(viewSize / scrollSize * scrollbarAreaSize)),
				pxRatio = (scrollSize - viewSize) / (scrollbarAreaSize - scrollbarSize);
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
			} else {
				scrollPos = - parseInt(contentBox.getStyle('marginLeft'), 10);
			}
			
			this.pxRatio = pxRatio;
			this.scrollbarAreaSize = scrollbarAreaSize;
			this.scrollbarSize = scrollbarSize;
			this.contentSize = scrollSize;
			this.viewSize = viewSize;
			
			if (axis == 'y') {
				draggableNode.setStyles({
					'height': scrollbarSize,
					'top': ~~(scrollPos / pxRatio)
				});
			} else {
				//Make sure all content is in view
				if (viewSize + scrollPos > scrollSize) {
					scrollPos = Math.max(0, scrollSize - viewSize);
					contentBox.setStyle('marginLeft', - scrollPos + 'px');
				}
				
				draggableNode.setStyles({
					'width': scrollbarSize,
					'left': ~~(scrollPos / pxRatio)
				});
			}
			
			this.fire('sync');
		},
		
		/**
		 * Sync scrollbar position
		 * 
		 * @private
		 */
		syncUIPosition: function () {
			if (this.get('disabled') || this.dragging) return;
			
			var axis = this.get('axis'),
				
				contentBox = this.get('contentBox'),
				draggableNode = this.get('draggableNode'),
				scrollPos = 0;
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
				draggableNode.setStyle('top', ~~(scrollPos / this.pxRatio));
			} else {
				scrollPos = -parseInt(contentBox.get('margin-left'), 10);
				draggableNode.setStyle('left', ~~(scrollPos / this.pxRatio));
			}
			
			this.fire('sync');
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
		 * Returns current scroll position
		 * 
		 * @return Scroll position
		 * @type {Number}
		 */
		getScrollPosition: function () {
			var axis = this.get('axis'),
				axisPosProperty  = (axis == 'y' ? 'Top' : 'Left'),
				
				contentBox = this.get('contentBox'),
				scrollPos = null;
			
			if (axis == 'y') {
				scrollPos = contentBox.get('scrollTop');
			} else {
				scrollPos = -parseInt(contentBox.getStyle('marginLeft'), 10) || 0;
			}
			
			return scrollPos;
		},
		
		/**
		 * Set scroll position
		 */
		setScrollPosition: function (pos) {
			var axis = this.get('axis'),
				contentBox = this.get('contentBox');
			
			if (axis == 'y') {
				contentBox.set('scrollTop', pos);
			} else {
				contentBox.setStyle('marginLeft', -pos + 'px');
			}
			
			this.syncUIPosition();
		},
		
		/**
		 * Returns max scroll position
		 * 
		 * @return Max scroll position
		 * @type {Number}
		 */
		getMaxScrollPosition: function () {
			return Math.max(0, this.contentSize - this.viewSize);
		},
		
		/**
		 * Returns content size
		 * For horizontal scroller returns width, for vertical returns height
		 * 
		 * @return Content size
		 * @type {Number}
		 */
		getContentSize: function () {
			return this.contentSize;
		},
		
		/**
		 * Returns visible area size
		 * For horizontal scroller returns width, for vertical returns height
		 * 
		 * @return View size
		 * @type {Number}
		 */
		getViewSize: function () {
			return this.viewSize;
		},
		
		/**
		 * Scroll node in view
		 * 
		 * @param {Object} node Node
		 * @return True if scrolled to the node, false if node was already in view
		 * @type {Boolean}
		 */
		scrollInView: function (node) {
			if (this.get('disabled')) return;
			
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				axisOffsetFn     = (axis == 'y' ? 'getY' : 'getX'),
				
				contentBox = this.get('contentBox'),
				scrollPos = this.getScrollPosition(),
				viewSize = this.viewSize,
				
				contPos = contentBox[axisOffsetFn](),
				
				size = node.get('offset' + axisSizeProperty),
				pos = node[axisOffsetFn]() - contPos + scrollPos;
			
			if (pos < scrollPos) {
				this.setScrollPosition(pos);
			} else if ((pos + size - viewSize) > scrollPos) {
				this.setScrollPosition(pos + size - viewSize);
			} else {
				return false;
			}
		},
		
		/**
		 * Animate element into view
		 * 
		 * @param {Object} node Node
		 * @return True if scrolled to the node, false if node was already in view
		 * @type {Boolean}
		 */
		animateInView: function (node, duration) {
			if (this.get('disabled')) return;
			
			var axis = this.get('axis'),
				axisSizeProperty = (axis == 'y' ? 'Height' : 'Width'),
				axisOffsetFn     = (axis == 'y' ? 'getY' : 'getX'),
				
				contentBox = this.get('contentBox'),
				scrollPos = this.getScrollPosition(),
				viewSize = this.viewSize,
				
				contPos = contentBox[axisOffsetFn](),
				
				size = node.get('offset' + axisSizeProperty),
				pos = node[axisOffsetFn]() - contPos;
			
			if (pos < scrollPos) {
				this.animateTo(pos, duration);
			} else if ((pos + size) > (scrollPos + viewSize)) {
				this.animateTo(pos + size - viewSize, duration);
			} else {
				return false;
			}
		},
		
		/**
		 * Animate to position
		 * 
		 * @param {Number} pos Position
		 */
		animateTo: function (pos, duration) {
			if (this.get('disabled')) return;
			
			var animContent   = this.animContent,
				animScrollBar = this.animScrollBar,
				
				toContent   = {},
				toScrollBar = {},
				
				axis = this.get('axis'),
				contentBox = this.get('contentBox');
			
			if (!animContent) {
				animContent = this.animContent = new Y.Anim({
					'node': contentBox,
					'duration': 0.35,
					'easing': 'easeBoth'
				});
				animScrollBar = this.animScrollBar = new Y.Anim({
					'node': this.get('draggableNode'),
					'duration': 0.35,
					'easing': 'easeBoth'
				});
			}
			
			if (axis == 'y') {
				toContent['scroll'] = [0, pos];
				toScrollBar['top'] = pos / this.pxRatio;
			} else {
				toContent['marginLeft'] = -pos;
				toScrollBar['left'] = pos / this.pxRatio;
			}
			
			animContent.stop();
			animContent.set('duration', duration || 0.35);
			animContent.set('to', toContent);
			animContent.once('end', this.syncUI, this);
			animContent.run();
			
			animScrollBar.stop();
			animScrollBar.set('duration', duration || 0.35);
			animScrollBar.set('to', toScrollBar);
			animScrollBar.run();
		},
		
		/**
		 * Disabled change handler
		 * 
		 * @param {Event} evt Event facade object
		 * @private
		 */
		onDisabledChange: function (evt) {
			if (evt.newVal != evt.prevVal) {
				if (evt.newVal) {
					this.get('scrollbarNode').addClass(this.getClassName('invisible'));
				} else {
					this.syncUI();
				}
			}
		},
		
		/**
		 * Destructor
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.resizeListener) {
				this.resizeListener.detach();
				this.resizeListener = null;
			}
			
			if (this.animScrollBar) {
				this.animScrollBar.destroy();
				this.animScrollBar = null;
			}
			
			if (this.animContent) {
				this.animContent.destroy();
				this.animContent = null;	
			}
			
			//Mouse wheel
			var node = this.get('contentBox');
			node.detach('mousewheel');
			node.detach('scroll', this.syncUIPositionThrottled);
			
			//Resize
			node.detach('contentResize', this.syncUIThrottled);
			
			var parent = node.get('parentNode');
			if (parent) parent.detach('contentResize', this.syncUIThrottled);
			
			//Container resize
			node = node.closest('.left-container, .right-container');
			if (node) {
				node.detach('contentResize', this.syncUIThrottled);
			}
			
			//Drag and drop
			node = this.get('draggableNode');
			node.detach('mousedown', this.onDragStart);
		}
	});
	
	Supra.Scrollable = Scrollable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'anim']});