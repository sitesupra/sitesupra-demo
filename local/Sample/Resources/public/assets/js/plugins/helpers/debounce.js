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
define(['jquery'], function ($) {
    'use strict';
	
	$.debounce = function (callback, context, _threshold, delay) {
		if (typeof context === 'number') {
			threshold = context;
			context = null;
		}
		
		var threshold = _threshold || 50;
		var last_time = 0;
		var timeout = null;
		var args = [];
		
		function call () {
			callback.apply(context || window, args);
			last_time = +new Date();
			clearTimeout(timeout);
			timeout = null;
		}
		
		return function () {
			//Save arguments
			args = [].slice.call(arguments, 0);
			
			if (delay) {
				clearTimeout(timeout);
				timeout = setTimeout(call, threshold);
			} else {
				if ((+new Date()) - last_time > threshold) {
					call();
				} else if (!timeout) {
					timeout = setTimeout(call, threshold);
				}
			}
		};
	};
    
    return $.throttle;
	
});
