/*
 * @version 1.0.1
 */
"use strict";

(function () {
	var definition = function ($) {

		$.refresh = {
			
			/**
			 * Add event listener for event,
			 * alias of $(document).on
			 */
			'on': function (events, selector, data, handler) {
				return $(document).on(events, selector, data, handler);
			},
			
			/**
			 * Remove event listener from event,
			 * alias of $(document).off
			 */
			'off': function (events, selector, handler) {
				return $(document).off(events, selector, handler);
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
							event_object = jQuery.Event('refresh/' + namespace);
							node.trigger(event_object, evt);
							if (event_object.isDefaultPrevented()) return_value = false;
							
							//Global
							event_object = jQuery.Event('refresh');
							node.trigger(event_object, evt);
							if (event_object.isDefaultPrevented()) return_value = false;
						}
					} else if (type === 'cleanup') {
						if (node.data('refresh-event-triggered')) {
							namespace = node.data('refresh-event');
							
							//Namespaced
							event_object = jQuery.Event('cleanup/' + namespace);
							node.trigger(event_object, evt);
							if (event_object.isDefaultPrevented()) return_value = false;
							
							//Global
							event_object = jQuery.Event('cleanup');
							node.trigger(event_object, evt);
							if (event_object.isDefaultPrevented()) return_value = false;
							
						}
					} else {
						namespace = node.data('refresh-event');
						
						//Namespaced
						event_object = jQuery.Event(type + '/' + namespace);
						node.trigger(event_object, evt);
						if (event_object.isDefaultPrevented()) return_value = false;
						
						//Global
						event_object = jQuery.Event(type);
						node.trigger(event_object, evt);
						if (event_object.isDefaultPrevented()) return_value = false;
					}
				}
				
				return return_value;
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
	
	};

	// AMD suppot
	if (typeof define === 'function' && define.amd) {
		define(['jquery', 'app'], definition);
	} else {
		definition(jQuery);
	}
})();