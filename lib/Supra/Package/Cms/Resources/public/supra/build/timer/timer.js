YUI.add('supra.timer', function(Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Throttle function call
	 * If delay argument is true, then instead of calling function every 'ms'
	 * milliseconds it's called once if in last 'ms' function wasn't called 
	 * 
	 * @param {Function} fn
	 * @param {Number} ms
	 * @param {Object} context
	 * @param {Boolean} delay I
	 * @private
	 */
	Supra.throttle = function (fn, ms, context, delay) {
		var ms = ms || 50;
		var last_time = 0;
		var timeout = null;
		var args = [];
		
		if (ms === -1) {
			return (function() {
				fn.apply(context, arguments);
			});
		}
		
		function call () {
			fn.apply(context || window, args);
			last_time = +new Date();
			clearTimeout(timeout);
			timeout = null;
		}
		
		return function () {
			//Save arguments
			args = [].slice.call(arguments, 0);
			
			if (delay) {
				if (timeout) clearTimeout(timeout);
				timeout = setTimeout(call, ms);
			} else {
				if ((+new Date()) - last_time > ms) {
					call();
				} else if (!timeout) {
					timeout = setTimeout(call, ms);
				}
			}
		};
	};
	
	/**
	 * Immediatelly call a callback on next cycle
	 * Similar to Y.later, but executes callback as soon as possible, but still is async
	 * 
	 * @param {Object} context Optional, callback execution context
	 * @param {Function} callback Callback function
	 */
	Supra.immediate = (function () {
		var callbacks = [],
			channel = null,
			transmit = null,
			receive =  null,
			attach = null;
		
		receive = function () {
			var c  = callbacks,
				i  = 0,
				ii = c.length;
			
			for (; i<ii; i++) {
				callbacks[i]();
			}
			
			callbacks = [];
		};
		
		if (window.setImmediate) {
			// No browser support for now
			transmit = function () {
				window.setImmediate(receive);
			};
		} else if (window.msSetImmediate) {
			// IE10+
			transmit = function () {
				window.msSetImmediate(receive);
			};
		} else if (window.postMessage) {
			// FF, Chrome, Safari, Opera, IE8+
			window.addEventListener('message', function (event) {
				if (event.source === window && event.data.indexOf('supra.immediate') === 0) {
					receive();
				}
			}, false);
			
			transmit = function () {
				postMessage('supra.immediate', '*');
			};
		} else if (window.MessageChannel) {
			// 
			channel = new MessageChannel();
			channel.port1.onmessage = function (event) {
				if (event.data == 'supra.immediate') {
					receive();
				}
			};
			
			transmit = function () {
				channel.port2.postMessage('supra.immediate');
			};
		} else {
			transmit = function () {
				setTimeout(receive, 0);
			};
		}
		
		attach = function (context, callback) {
			if (Y.Lang.isFunction(context)) {
				callback = context;
			} else {
				callback = Y.bind(callback, context);
			}
			
			callbacks.push(callback);
			transmit();
		};
		
		return attach;
	})();
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);