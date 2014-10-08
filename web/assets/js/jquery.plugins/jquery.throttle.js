/**
 * Throttle handles call frequency to callback, to avoid
 * callback beeing called more often than 'threshold' milliseconds
 * 
 * @param {Function} callback
 * @param {Number} threshold
 * @return Throttled function
 * @type {Function}
 * @version 1.0
 */
(function ($) {
	
	$.throttle = function (callback, context, threshold) {
		if (typeof context === 'number') {
			threshold = context;
			context = null;
		}
		
		var threshold = threshold || 50;
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
			
			if ((+new Date()) - last_time > threshold) {
				call();
			} else if (!timeout) {
				timeout = setTimeout(call, threshold);
			}
		};
	};
	
})(jQuery);