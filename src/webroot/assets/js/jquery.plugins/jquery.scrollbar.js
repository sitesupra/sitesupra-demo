/**
 * @version 1.0
 */
(function ($) {
	
	var NAMESPACE = 'scrollbar';
	
	/**
	 * Scrollbar widget
	 * 
	 * Dependancies:
	 *     jquery.mousewheel.js  (if mousewheel functionality is enabled)
	 * 
	 * @constructor
	 * @param {Object} node Scrollbar container node 
	 * @param {Object} options Options, optional argument
	 * @version 1.0
	 */
	function Scrollbar (node, options) {
		var options = this.options = $.extend({}, this.DEFAULT_OPTIONS, options || {});
		
		this.nodeContainer = $(node);
		
		//Set value
		this.value = parseFloat(this.options.value);
		
		//Update class names
		//replace %c in classnames with options 'classname' value
		var classnames = ['classnameFocus'];
		for(var i=0,ii=classnames.length; i<ii; i++) {
			options[classnames[i]] = options[classnames[i]].split('%c').join(options.classname);
		}
		
		this._renderUI();
		this._bindUI();
	}
	Scrollbar.prototype = {
		/**
		 * Default options for class
		 */
		DEFAULT_OPTIONS: {
			'classname': 'scrollbar',
			'classnameFocus': '%c-focus',
			'classnameHidden': 'hidden',
			
			//Used for scrollbar positioning
			'margin': 5,
			
			//Used for dragable node max and min position
			'padding': 5,
			
			//Scrollbar padding/border, used for height calculation
			'innerPadding': 2,	
			
			'mouseWheel': true,
			'mouseWheelOffset': 30,
			
			'value': 0,
			'minValue': 0,
			'maxValue': 100
		},
		
		/**
		 * Scrollbar container/parent node (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeContainer: null,
		
		/**
		 * Scrollbar node (jQuery)
		 * @type {Object}
		 * @private
		 */
		node: null,
		
		/**
		 * Scrollbar dragable node (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeDragable: null,
		
		/**
		 * Inner node to which height will be applied (jQuery)
		 * @type {Object}
		 * @private
		 */
		nodeInner: null,
		
		/**
		 * Mouse move callback function, _onMouseMove with .throttle
		 * @type {Function}
		 * @private
		 */
		fnMouseMove: null,
		
		/**
		 * Mouse up callback function, _onMouseUp with .proxy
		 * @type {Function}
		 * @private
		 */
		fnMouseUp: null,
		
		/**
		 * Mouse wheel callback function, _onMouseWheel with .proxy
		 * @type {Function}
		 * @private
		 */
		fnMouseWheel: null,
		
		/**
		 * Container element height
		 * @type {Number}
		 * @private
		 */
		containerHeight: null,
		
		/**
		 * Dragable element height
		 * @type {Number}
		 * @private
		 */
		dragableHeight: null,
		
		/**
		 * Dragable min position
		 * @type {Number}
		 * @private
		 */
		posMinOffset: null,
		
		/**
		 * Dragable max position
		 * @type {Number}
		 * @private
		 */
		posMaxOffset: null,
		
		/**
		 * Mouse position when started dragging
		 * @type {Number}
		 * @private
		 */
		mousePosStart: null,
		
		/**
		 * Value when started dragging
		 * @type {Number}
		 * @private
		 */
		valueStart: null,
		
		/**
		 * Render UI
		 * 
		 * @private
		 */
		_renderUI: function () {
			//Create HTML
			var html = $(this._render('scrollbar'));
			this.nodeContainer.append(html);
			
			this.node = html;
			this.nodeDragable = html.find('.' + this.options.classname + '-dragable')
			this.nodeInner = html.find('.' + this.options.classname + '-b')
			
			this.update();
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		_bindUI: function () {
			this.fnMouseMove = $.throttle($.proxy(this._onMouseMove, this), 30);
			this.fnMouseUp = $.proxy(this._onMouseUp, this);
			this.fnMouseWheel = $.proxy(this._onMouseWheel, this);
			
			this.nodeDragable.mousedown($.proxy(this._onMouseDown, this));
			this.nodeContainer.mousewheel(this.fnMouseWheel);
		},
		
		/**
		 * On mouse wheel update dragable position
		 * 
		 * @private
		 */
		_onMouseWheel: function (evt) {
			var offset = 0,
				wheelOffset = this.options.mouseWheelOffset;
			
			if (evt.detail) offset = evt.detail > 0 ? wheelOffset : -wheelOffset;
			if (evt.wheelDelta) offset = evt.wheelDelta > 0 ? -wheelOffset : wheelOffset;
			
			if (offset) {
				this.setValue(this.value + offset);
			}
				
			return false;
		},
		
		/**
		 * On mouse move update dragable position
		 * 
		 * @private
		 */
		_onMouseMove: function (evt) {
			var mouseDif = evt.clientY - this.mousePosStart,
				valDif = 0,
				minValue = this.options.minValue,
				maxValue = this.options.maxValue;
			
			if (mouseDif && maxValue != minValue) {
				valDif = mouseDif / (this.posMaxOffset - this.posMinOffset) * (maxValue - minValue) + minValue;
			}
			
			this.setValue(this.valueStart + valDif);
		},
		
		/**
		 * On mouse down bind event listeners
		 * 
		 * @private
		 */
		_onMouseDown: function (evt) {
			this.valueStart = this.value;
			this.mousePosStart = evt.clientY;
			
			this.node.addClass(this.options.classnameFocus);
			
			$(document).mousemove(this.fnMouseMove);
			$(document).mouseup(this.fnMouseUp);
			return false;
		},
		
		/**
		 * On mouse up remove event listeners
		 */
		_onMouseUp: function () {
			this.node.removeClass(this.options.classnameFocus);
			$(document).unbind('mousemove', this.fnMouseMove);
			$(document).unbind('mouseup', this.fnMouseUp);
		},
		
		/**
		 * Render specific block
		 * 
		 * @param {String} block
		 * @private
		 */
		_render: function (block) {
			var renderer = this.options.renderer;
			
			if (block in renderer) {
				var args = [this.options];
				return renderer[block].apply(this, args);
			}
			
			return '';
		},
		
		/**
		 * Hide scrollbar
		 */
		hide: function () {
			this.node.addClass(this.options.classnameHidden);
		},
		
		/**
		 * Show scrollbar
		 */
		show: function () {
			this.node.removeClass(this.options.classnameHidden);
		},
		
		/**
		 * Set scrollbar value
		 * 
		 * @param {Number} value
		 */
		setValue: function (value) {
			if (value != this.value) {
				value = Math.min(this.options.maxValue, Math.max(this.options.minValue, parseFloat(value)));
				if (value != this.value) {
					this.value = value;
					this.updateDragable();
					this.node.trigger('scroll', {'value': value});
				}
			}
		},
		
		/**
		 * Returns scrollbar value
		 * 
		 * @return Value
		 * @type {Number}
		 */
		getValue: function () {
			return this.value;
		},
		
		/**
		 * Set minimal value
		 * 
		 * @param {number} minValue
		 */
		setMin: function (minValue) {
			var maxValue = this.options.maxValue,
				value = this.options.value;
			
			this.options.minValue = minValue = parseFloat(minValue);
			
			if (maxValue < minValue) {
				maxValue = this.options.maxValue = minValue;
			}
			
			if (value > maxValue || value < minValue) {
				this.setValue(Math.min(minValue, Math.max(minValue, value)));
			} else {
				this.updateDragable();
			}
		},
		
		/**
		 * Set maximal value
		 * 
		 * @param {number} maxValue
		 */
		setMax: function (maxValue) {
			var minValue = this.options.minValue,
				value = this.options.value;
			
			this.options.maxValue = maxValue = parseFloat(maxValue);
			
			if (minValue > maxValue) {
				minValue = this.options.minValue = maxValue;
			}
			
			if (value > maxValue || value < minValue) {
				this.setValue(Math.min(minValue, Math.max(minValue, value)));
			} else {
				this.updateDragable();
			}
		},
		
		/**
		 * Update dragable position
		 */
		updateDragable: function () {
			var height = this.containerHeight;
			if (!height) return this.update();
			
			var options = this.options,
				minOffset = this.posMinOffset,
				maxOffset = this.posMaxOffset,
				pos = 0;
			
			if (this.value != options.minValue) {
				pos = ~~((maxOffset - minOffset) * (this.value - options.minValue) / (options.maxValue - options.minValue) + minOffset);
			} else {
				pos = ~~(minOffset);
			}
			
			this.nodeDragable.css('top', pos + 'px');
		},
		
		/**
		 * Update UI: dragable position and scrollbar height
		 */
		update: function () {
			var height = this.nodeContainer.innerHeight();
			if (!height) return;
			
			this.containerHeight = height;
			this.dragableHeight = this.nodeDragable.height();
			
			var options = this.options,
				outerHeight = height - options.margin * 2 - options.innerPadding,
				minOffset = options.padding,
				maxOffset = height - options.padding - this.dragableHeight,
				pos = 0;
			
			this.posMinOffset = minOffset;
			this.posMaxOffset = maxOffset;
			this.nodeInner.css('height', outerHeight + 'px');
			
			if (this.value != options.minValue) {
				pos = ~~((maxOffset - minOffset) * (this.value - options.minValue) / (options.maxValue - options.minValue) + minOffset);
			} else {
				pos = ~~(minOffset);
			}
			
			this.nodeDragable.css('top', pos + 'px');
		},
		
		/**
		 * Destroy widget
		 */
		destroy: function () {
			this.nodeContainer.unbind('mousewheel', this.fnMouseWheel);
			this.nodeContainer.remove();
			
			delete(this.nodeContainer);
			delete(this.node);
			delete(this.nodeDragable);
			delete(this.nodeInner);
			
			delete(this.fnMouseMove);
			delete(this.fnMouseUp);
			delete(this.fnMouseWheel);
		}
	};
	
	Scrollbar.prototype.DEFAULT_OPTIONS.renderer = Scrollbar.RENDERER = {
		/**
		 * Render scollbar
		 * 
		 * @param {Object} options
		 * @return HTML
		 * @type {String}
		 */
		'scrollbar': function (options) {
			return '<div class="' + options.classname + '">\
						<div class="' + options.classname + '-t"><div class="' + options.classname + '-b"></div></div>\
						<div class="' + options.classname + '-dragable"></div>\
					</div>';
		}
	};
	
	/**
	 * jQuery Scrollbar plugin
	 * 
	 * @param {Object} options
	 * @return Scrollbar instance for node
	 */
	$.fn.scrollbar = function (options) {
		
		var instance = null, dropdown, node;
		
		//Create drop down for each item
		for(var i=0,ii=this.length; i<ii; i++) {
			node = this.eq(i);
			scrollbar = node.data(NAMESPACE);
			if (!scrollbar) {
				instance = new Scrollbar(node, options);
				node.data(NAMESPACE, instance);
			} else if (!instance) {
				instance = scrollbar;
			}
		}
		
		return instance;
	};
	
	$.Scrollbar = Scrollbar;
	
})(jQuery);