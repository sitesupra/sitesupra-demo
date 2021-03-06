/**
 * Throttle call frequency to callback, to avoid
 * callback beeing called more often than 'threshold' milliseconds
 * 
 * @param {Function} callback
 * @param {Number} threshold
 * @return Throttled function
 * @type {Function}
 * @version 1.0.1
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery);
	} else { 
        // AMD is not supported, assume all required scripts are already loaded
        factory(jQuery);
    }
}(this, function ($) {
    'use strict';
	
	return function (callback, context, _threshold, delay) {
		if (typeof context === 'number') {
			threshold = context;
			context = null;
		}
		
		var threshold = _threshold || 50;
		var last_time = 0;
		var timeout = null;
		var args = [];
		
		var trigger = function () {
			callback.apply(context || window, args);
			last_time = +new Date();
			clearTimeout(timeout);
			timeout = null;
		};
		
		return function () {
			//Save arguments
			args = [].slice.call(arguments, 0);
			
			if (delay) {
				clearTimeout(timeout);
				timeout = setTimeout(trigger, threshold);
			} else {
				if ((+new Date()) - last_time > threshold) {
					trigger();
				} else if (!timeout) {
					timeout = setTimeout(trigger, threshold);
				}
			}
		};
	};
	
}));
