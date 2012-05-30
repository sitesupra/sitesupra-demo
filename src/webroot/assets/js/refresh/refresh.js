"use strict";

(function ($) {
	
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
	 */
	'trigger': function (type, element) {
		var nodes = element.find('[data-refresh-event]');
		if (element.data('refresh-event')) {
			nodes = nodes.add(element);
		}
		
		var i = 0,
			ii = nodes.length,
			node = null,
			namespace = '';
		
		for (; i<ii; i++) {
			node = nodes.eq(i);
			
			if (type === 'refresh') {
				if (!node.data('refresh-event-triggered')) {
					node.data('refresh-event-triggered', true);
					
					namespace = node.data('refresh-event');
					node.trigger('refresh/' + namespace, {'name': namespace, 'target': node});
					node.trigger('refresh', {'name': namespace, 'target': node});
				}
			} else if (type === 'cleanup') {
				if (node.data('refresh-event-triggered')) {
					namespace = node.data('refresh-event');
					node.trigger('cleanup/' + namespace, {'name': namespace, 'target': node});
					node.trigger('cleanup', {'name': namespace, 'target': node});
				}
			}
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
$(function () {
	$.refresh.init($('body'));
});

})(jQuery);