/**
 * Drag implementation for mouse and touch without blocking 
 * scrolling in touch devices
 * 
 * @version 1.0.0
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	// Data property
	var DATA_INSTANCE_PROPERTY = 'touchDragPlugin';
	
	// Event name
	var EVENT_NAME = 'ontouchstart' in document.documentElement ? 'touchstart' : 'mousedown';
	
	
	function TouchDrag (elements, options) {
		this.elements = $(elements);
		this.setOptions(options);
		
		this.elements.attr('draggable', false);
		this._attach();
	}
	
	TouchDrag.defaultOptions = {
		// If cursor moves more than 'cancelDistance' before 'delay' ms has
		// passed, then drag is canceled, allow timer activated drag
		'delay': 0,
		'cancelDistance': [0, 0],
		'activateDistance': [0, 0],
		
		'touchDelay': 1000,
		'touchCancelDistance': [50, 50],
		'touchActivateDistance': [0, 0]
	};
	
	TouchDrag.prototype = {
		
		// Touch triggered event
		is_touch_event: false,
		
		// Touch event identifier
		touch_identifier: null,
		
		// Elements
		elements: null,
		
		// Target element
		target: null,
		
		// Current state
		state: false,
		
		// XY coordinates when started event
		start_xy: null,
		
		// Last known XY coordinates
		last_xy: null,
		
		// Start scroll position
		start_scroll: null,
		
		// Waiting timer
		timer: null,
		
		
		/* ---------------- Events ---------------- */
		
		
		'_attach': function () {
			this._inputStart  = $.proxy(this._inputStart, this);
			this._inputEnd    = $.proxy(this._inputEnd, this);
			this._inputUpdate = $.proxy(this._inputUpdate, this);
			this._dragStart   = $.proxy(this._dragStart, this);
			
			this.elements.on(EVENT_NAME, this._inputStart);
		},
		
		'_detach': function () {
			
		},
		
		'_inputStart': function (e) {
			if (this.state) return;
			if (e.type == 'mousedown' && e.which != 1) return; // right mous button is not ok
			
			this._waitStart(e);
			
			var doc = $(document),
				xy  = this.start_xy = this.last_xy = this._getCoordinates(e),
				evt,
				memory = this.memory = {},
				target = $(e.target), tmp,
				distance;
			
			this.target = target = $(e.target).closest(this.elements);
			
			if (e.type == 'touchstart') {
				this.is_touch_event = true;
				doc.on('touchmove', this._inputUpdate);
				doc.on('touchend', this._inputEnd);
				
				if (this.options.touchDelay) {
					this.timer = setTimeout(this._dragStart, this.options.touchDelay);
				} else {
					distance = this.options.touchActivateDistance;
					
					if (!distance[0] && !distance[1]) {
						this._dragStart(e);
					}
				}
			} else {
				this.is_touch_event = false;
				doc.on('mousemove', this._inputUpdate);
				doc.on('mouseup', this._inputEnd);
				
				if (this.options.delay) {
					this.timer = setTimeout(this._dragStart, this.options.delay);
				} else {
					distance = this.options.activateDistance;
					
					if (!distance[0] && !distance[1]) {
						this._dragStart(e);
					}
				}
			}
		},
		
		'_inputUpdate': function (e) {
			var xy = this.last_xy = this._getCoordinates(e),
				evt;
			
			if (this.state == 'dragging') {
				this._dragMove(e);
			} else {
				// Cancel
				var distance = this.is_touch_event ? this.options.touchCancelDistance : this.options.cancelDistance;
				
				if (distance[0] || distance[1]) {
					if (distance[1] && distance[1] < Math.abs(xy[1] - this.start_xy[1])) {
						this._cleanup();
					} else if (distance[0] && distance[0] < Math.abs(xy[0] - this.start_xy[0])) {
						this._cleanup();
					}
				}
				
				// Activate
				distance = this.is_touch_event ? this.options.touchActivateDistance : this.options.activateDistance;
				if (distance[0] || distance[1]) {
					if (distance[1] && distance[1] >= Math.abs(xy[1] - this.start_xy[1])) {
						this._dragStart();
					} else if (distance[0] && distance[0] >= Math.abs(xy[0] - this.start_xy[0])) {
						this._dragStart();
					}
				}
			}
		},
		
		'_inputEnd': function (e) {
			if (this.state === 'dragging') {
				if (this.is_touch_event) {
					var touches = e.originalEvent.touches,
						i = 0,
						ii = touches.length,
						identifier = this.touch_identifier;
					
					for (; i<ii; i++) {
						if (touches[i].identifier == identifier) {
							// Touch with given identifier still exists,
							// other than initial was released
							return;
						}
					}
				}
				
				this._dragEnd(e);
			}
			
			this._cleanup();
		},
		
		'_waitStart': function (e) {
			this.state = 'waiting';
		},
		
		'_dragStart': function (e) {
			this.state = 'dragging';
			
			var memory = this.memory,
				last_xy = this.last_xy,
				start_xy = this.start_xy,
				evt,
				target = this.target;
			
			evt = $.Event('drag-start', {
				'dragTarget': target,
				'xy': start_xy,
				'delta': [last_xy[0] - start_xy[0], last_xy[1] - start_xy[1]],
				'memory': memory,
				'touch': this.is_touch_event
			});
			
			target.trigger(evt);
			
			if (e) {
				// Stop event and/or propagation if callback did it
				if (evt.isDefaultPrevented()) e.preventDefault();
				if (evt.isPropagationStopped()) e.stopPropagation();
			}
		},
		
		'_dragMove': function (e) {
			var start_xy = this.start_xy,
				xy  = this.last_xy = this._getCoordinates(e),
				evt,
				target = this.target;
			
			evt = $.Event('drag-move', {
				'dragTarget': target,
				'xy': xy,
				'delta': [xy[0] - start_xy[0], xy[1] - start_xy[1]],
				'memory': this.memory,
				'touch': this.is_touch_event
			});
			
			target.trigger(evt);
			
			if (this.is_touch_event) {
				// Stop scroll, always!
				e.preventDefault();
				e.stopPropagation();
			} else {
				// Stop event and/or propagation if callback did it
				if (evt.isDefaultPrevented()) e.preventDefault();
				if (evt.isPropagationStopped()) e.stopPropagation();
			}
		},
		
		'_dragEnd': function (e) {
			var start_xy = this.start_xy,
				xy  = this._getCoordinates(e) || this.last_xy,
				evt,
				target = this.target;
			
			evt = $.Event('drag-end', {
				'dragTarget': target,
				'xy': xy,
				'delta': [xy[0] - start_xy[0], xy[1] - start_xy[1]],
				'memory': this.memory,
				'touch': this.is_touch_event
			});
			
			this.memory = null;
			target.trigger(evt);
			
			if (e) {
				// Stop event and/or propagation if callback did it
				if (evt.isDefaultPrevented()) e.preventDefault();
				if (evt.isPropagationStopped()) e.stopPropagation();
			}
			
			this._cleanup();
		},
		
		'_cleanup': function () {
			var doc = $(document);
			
			if (this.timer) {
				clearTimeout(this.timer);
				this.timer = null;
			}
			
			this.state = null;
			this.start_xy = [0, 0];
			this.last_xy = [0, 0];
			this.memory = {};
			this.target = null;
			
			this.is_touch_event = false;
			this.touch_identifier = null;
			
			doc.off('touchmove mousemove', this._inputUpdate);
			doc.off('touchend mouseup', this._inputEnd);
		},
		
		
		/* ---------------- Private ---------------- */
		
		
		/**
		 * Returns scroll position
		 * 
		 * @returns {Number} Current scroll postiion
		 */
		'_getScroll': function () {
			return window.scrollTop || $('body').scrollTop() || $('html').scrollTop();
		},
		
		/**
		 * Returns event coordinates
		 * 
		 * @param {Object} e Event facade object
		 * @returns {Array} X and Y coordinates of the event
		 */
		'_getCoordinates': function (e) {
			var type = e.type,
				touches, i, ii,
				identifier = this.touch_identifier;
			
			if (type == 'touchstart' || type == 'touchmove' || type == 'touchend') {
				touches = e.originalEvent.changedTouches;
				
				if (touches.length) {
					if (identifier === null) {
						// Get first identifierz
						this.touch_identifier = identifier = touches[0].identifier;
					}
					
					for (i=0, ii=touches.length; i<ii; i++) {
						if (touches[i].identifier === identifier) {
							return [touches[i].clientX, touches[i].clientY];
						}
					}
				}
			} else {
				return [e.clientX, e.clientY];
			}
			
			return null;
		},
		
		
		/* ---------------- API ---------------- */
		
		
		/**
		 * Stop drag
		 */
		'stop': function () {
			if (this.state == 'dragging') {
				this._dragEnd();
			} else if (this.state == 'waiting') {
				this._cleanup();
			}
			
			this.state = null;
		},
		
		/**
		 * Returns true if currently drag is active
		 */
		'dragging': function () {
			return this.state == 'dragging';
		},
		
		/**
		 * Set options
		 */
		'setOptions': function (change) {
			var options = $.extend({}, TouchDrag.defaultOptions, true),
				props   = ['delay', 'touchDelay'],
				arrays  = ['cancelDistance', 'activateDistance', 'touchCancelDistance', 'touchActivateDistance'];
			
			$.each(props, function (index, item) {
				if (item in change) options[item] = change[item];
			});
			
			$.each(arrays, function (index, item) {
				if (item in change) options[item] = $.isArray(change[item]) ? change[item] : [change[item], change[item]];
			});
			
			this.options = options;
		},
		
		/**
		 * Destroy widget
		 */
		'destroy': function () {
			this.stop();
			this._detach();
			this.options = null;
			this.elements = null;
		}
	};
	
	$.fn.touchdrag = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof TouchDrag.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		var element = $(this),
			widget = element.data(DATA_INSTANCE_PROPERTY);
		
		if (!widget) {
			widget = new TouchDrag (element, $.extend({}, element.data(), options || {}));
			element.data(DATA_INSTANCE_PROPERTY, widget);
		} else {
			if (fn) {
				widget[fn].call(widget);
			}
		}
		
		return element;
	};
	
}));