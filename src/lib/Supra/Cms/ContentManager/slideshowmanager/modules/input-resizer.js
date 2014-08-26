YUI.add('slideshowmanager.input-resizer', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	/**
	 * Resize handle
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = false;
	
	Input.NAME = 'input-resizer';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// Node which is resized
		'targetNode': {
			value: null,
			setter: '_setTargetNode'
		},
		// Property which will be changed
		'cssProperty': {
			value: 'minHeight',
			validator: Y.Lang.isString,
			setter: '_setCSSProperty',
		},
		// Input value
		'value': {
			value: 0,
			setter: '_setValue',
			getter: '_getValue'
		},
		// Document element
		'doc': {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Element property name to get value
		 * @type {String}
		 * @private
		 */
		_getterProperty: '',
		
		/**
		 * Element which can be dragged
		 * @type {Object}
		 * @private
		 */
		_dragHandleNode: null,
		
		/**
		 * Tooltip node
		 */
		_tooltipNode: null,
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			if (this.get('targetNode')) {
				this._setTargetNode(this.get('targetNode'));
			}
		},
		
		/**
		 * Attach event listeners
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			this.after('valueChange', this._afterValueChange, this);
		},
		
		destructor: function () {
			if (this._dragging) {
				this._dragging = false;
				this._dragEnd();
			}
			
			this.set('targetNode', null);
		},
		
		/**
		 * Render resize handle
		 */
		renderHandle: function (container) {
			if (this._dragging) {
				this._dragging = false;
				this._dragEnd();
			}
			
			if (this._dragStartEvent) {
				this._dragStartEvent.detach();
				this._dragStartEvent = null;
			}
			if (this._dragHandleNode) {
				this._dragHandleNode.remove(true);
				this._dragHandleNode = null;
			}
			
			if (container) {
				var doc = this.get('doc'),
					element = Y.Node(doc.createElement('DIV'));
				
				container.insert(element, 'after');
				element.set('innerHTML', '<div class="tooltip"></div>')
				element.addClass('su-slideshowmanager-resizer');
				element.addClass('yui3-box-reset');
				
				this._dragStartEvent = element.on('mousedown', this._dragStart, this);
				this._dragHandleNode = element;
			}
		},
		
		
		/* ------------------------------ Drag -------------------------------- */
		
		
		/**
		 * Element height when started dragging
		 * @type {Number}
		 * @private
		 */
		_dragStartHeight: 0,
		
		/**
		 * Mouse Y position when started dragging
		 * @type {Number}
		 * @private
		 */
		_dragStartY: 0,
		
		/**
		 * Minimal height
		 * @type {Number}
		 * @private
		 */
		_dragMinHeight: 0,
		
		/**
		 * Event object for 'mousedown' event
		 * @type {Object}
		 * @private
		 */
		_dragStartEvent: null,
		
		/**
		 * Event object for 'mousemove' event
		 * @type {Object}
		 * @private
		 */
		_dragMoveEvent: null,
		
		/**
		 * Event object for 'mouseup' event
		 * @type {Object}
		 * @private
		 */
		_dragEndEvent: null,
		
		/**
		 * User is dragging resizer
		 * @type {Boolean}
		 * @private
		 */
		_dragging: false,
		
		
		/**
		 * Drag start event
		 * 
		 * @param {Object} e Mouse event facade object
		 * @private
		 */
		_dragStart: function (e) {
			if (this._dragHandleNode && e.button == 1) {
				this._dragMinHeight = this._uiGetMinHeight();
				this._dragStartHeight = this._uiGetHeight();
				this._dragStartY = e.clientY;
				this._dragging = true;
				
				var doc = Y.Node(this.get('doc'));
				this._dragMoveEvent = doc.on('mousemove', this._dragMove, this);
				this._dragEndEvent  = doc.on('mouseup', this._dragEnd, this);
				
				this._dragHandleNode.addClass('yui3-dd-dragging');
				
				// Tooltip
				this._uiSetToolipText(this._dragStartHeight);
			}
			
			e.preventDefault();
		},
		
		/**
		 * Drag move event
		 * 
		 * @param {Object} e Mouse event facade object
		 * @private
		 */
		_dragMove: function (e) {
			var value = this._dragMouseToValue(e);
			this._uiSetHeight(value);
		},
		
		/**
		 * Drag move event
		 * 
		 * @param {Object} e Mouse event facade object
		 * @private
		 */
		_dragEnd: function (e) {
			if (this._dragging) {
				this.set('value', this._dragMouseToValue(e));
				this._dragging = false;
			}
			
			if (this._dragHandleNode) {
				this._dragHandleNode.removeClass('yui3-dd-dragging');
			}
			
			if (this._dragMoveEvent) {
				this._dragMoveEvent.detach();
				this._dragMoveEvent = null;
			}
			if (this._dragEndEvent) {
				this._dragEndEvent.detach();
				this._dragEndEvent = null;
			}
			
			this._dragMinHeight = 0;
			this._dragging = false;
		},
		
		/**
		 * Returns size from event
		 * 
		 * @param {Object} e Mouse event facade object
		 * @returns {Number} Element size
		 * @private
		 */
		_dragMouseToValue: function (e) {
			var value = this._dragStartHeight + e.clientY - this._dragStartY,
				min   = this._dragMinHeight;
			
			if (value <= min) {
				return 0;
			} else {
				return value;
			} 
		},
		
		
		/* ------------------------------ UI -------------------------------- */
		
		
		/**
		 * Set height
		 * 
		 * @param {Number} height Height
		 * @private
		 */
		_uiSetHeight: function (height) {
			var target = this.get('targetNode'),
				nodes = null,
				prop = this.get('cssProperty'),
				i = 0,
				ii = 0;
			
			if (target) {
				nodes = target.all('*[data-supra-item-height]');
				ii = nodes.size();
				
				for (; i<ii; i++) {
					// empty value will remove
					nodes.item(i).setStyle(prop, height ? height + 'px' : '');
				}
				
				/*node = target.one('li > div');
				if (!node) node = target.one('li');
				if (!node) node = target;
				
				if (node) {
					// empty value will remove
					node.setStyle(prop, height ? height + 'px' : '');
				}*/
			}
			
			// Tooltip
			this._uiSetToolipText(height);
		},
		
		/**
		 * Returns element height
		 * 
		 * @returns {Number} Element height
		 * @private
		 */
		_uiGetHeight: function () {
			var node = this.get('targetNode'),
				prop = this._getterProperty,
				val  = 0;
			
			if (node) {
				val = parseInt(node.get(prop) || node.getStyle(prop), 10) || 0;
			}
			
			return Math.max(this._dragMinHeight, val);
		},
		
		/**
		 * Get minimal height to which this can be resized
		 * 
		 * @returns {Number} Minimal height
		 * @private
		 */
		_uiGetMinHeight: function () {
			var value = this.get('value'),
				height = 0;
			
			this._dragMinHeight = 0;
			this._uiSetHeight(0);
			height = this._uiGetHeight();
			this._uiSetHeight(value);
			
			return height; 
		},
		
		/**
		 * Set tooltip text
		 * 
		 * @param {Number} height Height
		 * @private
		 */
		_uiSetToolipText: function (height) {
			var min = this._dragMinHeight,
				text = 0,
				node = this._dragHandleNode;
			
			if (node) {
				if (!height || min == height) {
					text = 'Auto';
				} else {
					text = height + 'px';
				}
				node.one('div').set('text', text);
			}
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Target node attribute change
		 * Attach mouse events to the element
		 * 
		 * @param {Object} element New target node
		 * @returns {Object} New attribute value
		 * @private
		 */
		_setTargetNode: function (element) {
			this.renderHandle(element);
			return element;
		},
		
		/**
		 * cssProperty attribute setter
		 * Calculate property name which to use to get size from element
		 * 
		 * @param {String} property Property name
		 * @returns {String} New attribute value
		 * @private
		 */
		_setCSSProperty: function (property) {
			var property_getter = '',
				property_lower = property.toLowerCase();
			
			if (property_lower.indexOf('height') !== -1) {
				property_getter = 'offsetHeight';
			}
			
			this._getterProperty = property_getter;
			return property;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Number} value New value
		 * @returns {Number} New value
		 * @private
		 */
		_setValue: function (value) {
			value = parseInt(value, 10) || 0;
			this._uiSetHeight(value);
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @param {Number} value Current value
		 * @returns {Number} New value
		 * @private
		 */
		_getValue: function (value) {
			return value;
		},
		
		/**
		 * After value change trigger 'change' event
		 * 
		 * @param {Object} evt
		 */
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		}
		
	});
	
	Supra.Input.SlideshowInputResizer = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});