/**
 * Scrollbar widget
 * 
 * Dependancies:
 *     jquery.mousewheel.js  (if mousewheel functionality is enabled)
 * 
 * @constructor
 * @param {Object} node Scrollbar container node 
 * @param {Object} options Options, optional argument
 * @version 1.0.3
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'plugins/helpers/throttle'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var NAMESPACE = 'scrollbar';
	
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
			'classnameDisabled': 'disabled',
			
			//Used for scrollbar positioning
			'margin': 5,
			
			//Used for dragable node max and min position
			'padding': 0,
			
			//Scrollbar padding/border, used for height calculation
			'innerPadding': 0,	
			
			'mouseWheel': true,
			'mouseWheelOffset': 30,
			
			'value': 0,
			'minValue': 0,
			'maxValue': 100,
			
			//Scroll axis
			'axis': 'y'
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
		 * Container element size
		 * @type {Number}
		 * @private
		 */
		containerSize: null,
		
		/**
		 * Dragable element height
		 * @type {Number}
		 * @private
		 */
		dragableSize: null,
		
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
			this.fnMouseWheelNatural = $.proxy(this._onMouseWheelNatural, this);
			
			this.nodeDragable.bind('mousedown.scroll touchstart.scroll', $.proxy(this._onMouseDown, this));
			
			if ($.fn.mousewheel) {
				this.nodeContainer.mousewheel(this.fnMouseWheel);
			} else {
				// No mouse wheel plugin, try to mimic it
				var container = this.nodeContainer.get(0);
				if (window.addEventListener) {
					container.addEventListener('DOMMouseScroll', this.fnMouseWheelNatural, false);
					container.addEventListener('mousewheel', this.fnMouseWheelNatural, false);
					container.addEventListener('MozMousePixelScroll', function (event) { event.preventDefault(); }, false);
				} else if (options.scroll) {
					container.onmousewheel = this.fnMouseWheelNatural;
				}
			}
		},
		
		/**
		 * On mouse wheel update dragable position
		 */
		_onMouseWheelNatural: function (event) {
			var evt = event || window.event,
				delta = evt.wheelDelta ? evt.wheelDelta / 120 : -evt.detail / 3;
			
			evt = $.event.fix(evt);
			evt.preventDefault();
			
			this._onMouseWheel(evt, delta, delta, delta);
		},
		
		/**
		 * On mouse wheel update dragable position
		 * 
		 * @private
		 */
		_onMouseWheel: function (evt, delta, deltaX, deltaY) {
			var offset = 0,
				wheelOffset = this.options.mouseWheelOffset,
				delta = (this.options.axis == 'x' ? deltaX : deltaY);
			
			offset = (delta > 0 ? -wheelOffset : wheelOffset);
			
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
			var mouseDif = this._getEventPosition(evt) - this.mousePosStart,
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
			this.mousePosStart = this._getEventPosition(evt);
			
			this.node.addClass(this.options.classnameFocus);
			
			$(document).bind('mousemove.scroll touchmove.scroll', this.fnMouseMove);
			$(document).bind('mouseup.scroll touchend.scroll', this.fnMouseUp);
			return false;
		},
		
		/**
		 * On mouse up remove event listeners
		 */
		_onMouseUp: function () {
			this.node.removeClass(this.options.classnameFocus);
			$(document).unbind('mousemove.scroll touchmove.scroll');
			$(document).unbind('mouseup.scroll touchend.scroll');
		},
		
		/**
		 * Returns input device Y position
		 * 
		 * @param {Object} evt Event object
		 * @private 
		 */
		_getEventPosition: function (evt) {
			var prop_position = (this.options.axis == 'x' ? 'clientX' : 'clientY');
			
			if (evt[prop_position]) {
				return evt[prop_position];
			}
			
			evt = evt.originalEvent;
			if (evt.touches) {
				return evt.touches[0][prop_position];
			}
			
			return 0;
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
			var axis = this.options.axis,
				size = this.containerSize,
				prop_position = (axis == 'x' ? 'left' : 'top');
			
			if (!size) return this.update();
			
			var options = this.options,
				minOffset = this.posMinOffset,
				maxOffset = this.posMaxOffset,
				pos = 0;
			
			if (this.value != options.minValue) {
				pos = ~~((maxOffset - minOffset) * (this.value - options.minValue) / (options.maxValue - options.minValue) + minOffset);
			} else {
				pos = ~~(minOffset);
			}
			
			this.nodeDragable.css(prop_position, pos + 'px');
		},
		
		/**
		 * Update UI: dragable position and scrollbar height
		 */
		update: function () {
			var axis = this.options.axis,
				size = (axis == 'x' ? this.nodeContainer.innerWidth() : this.nodeContainer.innerHeight()),
				prop_size = (axis == 'x' ? 'width' : 'height'),
				prop_position = (axis == 'x' ? 'left' : 'top');
			
			if (!size) return;
			
			this.containerSize = size;
			this.dragableSize = this.nodeDragable[prop_size]();
			
			var options = this.options,
				outerSize = size - options.margin * 2 - options.innerPadding;
			
			this.nodeInner.css(prop_size, outerSize + 'px');
			
			// Calculate scrollbar size
			var scrollbarSize = (axis == 'x' ? this.node.innerWidth() : this.node.innerHeight()),
				minOffset = options.padding,
				maxOffset = scrollbarSize - options.padding - this.dragableSize,
				
				pos = 0;
			
			this.posMinOffset = minOffset;
			this.posMaxOffset = maxOffset;
			
			
			if (this.value != options.minValue) {
				pos = ~~((maxOffset - minOffset) * (this.value - options.minValue) / (options.maxValue - options.minValue) + minOffset);
			} else {
				pos = ~~(minOffset);
			}
			
			this.nodeDragable.css(prop_position, pos + 'px');
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
			return '<div class="' + options.classname + ' ' + options.classname + '-' + options.axis + '">\
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
	
	return $.Scrollbar = Scrollbar;
	
}));