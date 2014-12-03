/*
 * @version 1.0.4
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/app'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	if (typeof window.CustomEvent !== 'function') {
		// Custom event polyfill
		var CustomEvent = function ( event, params ) {
			var evt = document.createEvent('CustomEvent');
			params = params || { bubbles: false, cancelable: false, detail: undefined };
			evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
			return evt;
		};
		
		if (window.CustomEvent) {
			CustomEvent.prototype = window.CustomEvent.prototype;
		}
	
		window.CustomEvent = CustomEvent;
	} else {
		var CustomEvent = window.CustomEvent;
	}
	

	$.refresh = {
		
		// Default was prevented
		'_defaultPreventedIE9': false,
		
		/**
		 * Add event listener for event
		 * Normalizes native event to be compatible with jQuery trigerred events 
		 * 
		 * @param {String} events Event name(s)
		 * @param {String} selector Optional selector for targets
		 * @param {Object} additional_data Optional data, which will be passed to callbacks event.data
		 * @param {Function} handler Event handler function
		 */
		'on': function (events, selector, data, handler) {
			// Normalize arguments
			if (typeof selector === 'function') {
				handler = selector;
				selector = data = null;
			} else if (data === 'function') {
				handler = data;
				data = null;
			}
			
			var fn = function (event, data) {
				// If jQuery data is seconds argument, while in native events it's
				// in .detail object
				data = data || event.originalEvent.detail;
				
				var res = handler.call(this, event, data);
				if (res === false) {
					event.preventDefault();
					return false;
				} else {
					return res;
				}
			};
			
			(handler._proxies || (handler._proxies = [])).push(fn);
			
			return $(document).on(events, selector, data, fn);
		},
		
		/**
		 * Remove event listener from event,
		 * alias of $(document).off
		 */
		'off': function (events, selector, handler) {
			var doc = $(document);
			
			if (handler._proxies) {
				for (var i=0, ii=handler._proxies.length; i<ii; i++) {
					doc.off(events, selector, handler._proxies[i]);
				}
			}
			
			return doc.off(events, selector, handler);
		},
		
		/**
		 * Trigger refresh events on element and all its children
		 * 
		 * @param {String} type Event type, "refresh" or "cleanup"
		 * @param {Object} element Element which changed
		 * @param {Object} data Additional data
		 */
		'trigger': function (type, element, data) {
			var nodes = element.find('[data-refresh-event]');
			if (element.data('refresh-event')) {
				nodes = nodes.add(element);
			}
			
			var i = 0,
				ii = nodes.length,
				node = null,
				namespace = '',
				evt = null,
				event_object = null,
				return_value = true;
			
			for (; i<ii; i++) {
				node = nodes.eq(i);
				evt = $.extend({'name': namespace, 'target': node}, data || {});
				
				if (type === 'refresh') {
					if (!node.data('refresh-event-triggered')) {
						node.data('refresh-event-triggered', true);
						
						namespace = node.data('refresh-event');
						
						//Namespaced
						event_object = this.triggerCustomEvent(node, 'refresh/' + namespace, evt);
						
						if (this.isTriggeredEventDefaultPrevented(event_object)) {
							return_value = false;
						}
						
						//Global
						event_object = this.triggerCustomEvent(node, 'refresh', evt);
						
						if (this.isTriggeredEventDefaultPrevented(event_object)) {
							return_value = false;
						}
					}
				} else if (type === 'cleanup') {
					if (node.data('refresh-event-triggered')) {
						node.data('refresh-event-triggered', false);
						namespace = node.data('refresh-event');
						
						//Namespaced
						event_object = this.triggerCustomEvent(node, 'cleanup/' + namespace, evt);
						
						if (this.isTriggeredEventDefaultPrevented(event_object)) {
							return_value = false;
						}
						
						//Global
						event_object = this.triggerCustomEvent(node, 'cleanup', evt);
						
						if (this.isTriggeredEventDefaultPrevented(event_object)) {
							return_value = false;
						}
						
					}
				} else {
					namespace = node.data('refresh-event');
					
					//Namespaced
					event_object = this.triggerCustomEvent(node, type + '/' + namespace, evt);
					
					if (this.isTriggeredEventDefaultPrevented(event_object)) {
						return_value = false;
					}
					
					//Global
					event_object = this.triggerCustomEvent(node, type, evt);
					
					if (this.isTriggeredEventDefaultPrevented(event_object)) {
						return_value = false;
					}
				}
			}
			
			return return_value;
		},
		
		/**
		 * Returns true if default was prevented for event
		 * 
		 * @private
		 */
		'isTriggeredEventDefaultPrevented': function (event) {
			if (this._defaultPreventedIE9) {
				return true;
			} else if (event.isDefaultPrevented && event.isDefaultPrevented()) {
				return true;
			} else if (event.defaultPrevented === true) {
				return true;
			} else if (event.returnValue === false) {
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Trigger custom event
		 * 
		 * @private
		 */
		'triggerCustomEvent': function (node, event, data) {
			var event_object;
			
			// Reset
			this._defaultPreventedIE9 = false;
			
			// Native event	
			if (node.get(0).dispatchEvent) {
				// Built-in event mechanism
				event_object = new CustomEvent(event, {'bubbles': true, 'cancelable': true, 'detail': data});
				
				try {
					var result = node.get(0).dispatchEvent(event_object);
					
					if (result === false) {
						// result is false if one of the listeners prevented default
						// IE9 doesn't set 'defaultPrevented' correctly
						this._defaultPreventedIE9 = true;
					}
					
				} catch (e) {
					// Report that error occured
					if (console && console.error) {
						console.error(e);
					}
				}
				
				return event_object;
			} else {
				event_object = jQuery.Event(event);
				
				// Triggering using disptachEvent also triggers jQuery event listeners
				// so no need to do this manually as last resort
				node.trigger(event_object, data);
				return event_object;
			}
		},
		
		/**
		 * Before content changes trigger events or delegate to $.app
		 */
		'cleanup': function (element) {
			if ($.app && typeof $.app.cleanup === 'function') {
				//App plugin is loaded, it will call trigger after cleanup
				$.app.cleanup(element);
			} else {
				//Trigger manually
				this.trigger('cleanup', element);
			}
		},
		
		/**
		 * Content changed, trigger events or delegate to $.app
		 * 
		 * @param {Object} element Element which changed
		 * @param {Boolean} initial Initial init call
		 */
		'init': function (element) {
			if ($.app && typeof $.app.parse === 'function') {
				//App plugin is loaded, it will call trigger after parse
				$.app.parse(element);
			} else {
				//Trigger manually
				this.trigger('refresh', element);
			}
		}
		
	};

	//On document ready do initial check
	$(window).on('load', function () {
		$.refresh.init($('body'));
	});

}));