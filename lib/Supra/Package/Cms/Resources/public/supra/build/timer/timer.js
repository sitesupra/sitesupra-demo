YUI.add('supra.timer', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var requestAnimationFrame = window.requestAnimationFrame ||
								window.mozRequestAnimationFrame ||
								window.webkitRequestAnimationFrame ||
								function (fn) {
									setTimeout(fn, 16);
								}; // IE9
	
	var cancelAnimationFrame = window.cancelAnimationFrame ||
							   window.mozCancelAnimationFrame ||
							   window.webkitCancelAnimationFrame ||
							   function (identifier) {
									clearTimeout(identifier);
								}; // IE9
	
	/**
	 * Throttle function call
	 * If delay argument is true, then instead of calling function every 'ms'
	 * milliseconds it's called once if in last 'ms' function wasn't called 
	 * 
	 * @param {Function} fn
	 * @param {Number} ms
	 * @param {Object} context
	 * @param {Boolean} delay If true then 'fn' will be called 'ms' milliseconds after last call
	 */
	Supra.throttle = function (fn, ms, context, delay) {
		var ms = ms || 50;
		var last_time = 0;
		var timeout = null;
		var timeout_raf = null;
		var args = [];
		var waiting = false;
		
		if (ms === -1) {
			return (function() {
				fn.apply(context, arguments);
			});
		}
		
		function call () {
			waiting = false;
			last_time = Date.now();
			
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}
			if (timeout_raf) {
				cancelAnimationFrame(timeout_raf);
				timeout_raf = null;
			}
			
			fn.apply(context || window, args);
		}
		
		if (ms === 16) {
			// Use requestAnimationFrame because it's more precise than setTimeout
			return function () {
				//Save arguments
				args = [].slice.call(arguments, 0);
				
				if (delay) {
					if (timeout_raf) cancelAnimationFrame(timeout_raf);
					timeout_raf = requestAnimationFrame(call);
				} else {
					if (!waiting) {
						requestAnimationFrame(call);
						waiting = true;
					}
				}
			};
		} else {
			return function () {
				//Save arguments
				args = [].slice.call(arguments, 0);
				
				if (delay) {
					if (timeout) clearTimeout(timeout);
					timeout = setTimeout(call, ms);
				} else {
					var delta = ms - (Date.now() - last_time);
					if (delta <= 0) {
						call();
					} else if (!timeout) {
						timeout = setTimeout(call, delta);
					}
				}
			};
		}
	};
	
	/**
	 * Returns function which when called will return true if it hasn't been called
	 * in last 'ms' milliseconds, otherwise false
	 * 
	 * @param {Number} ms Milliseconds
	 * @returns {Function} Function
	 */
	Supra.throttleValue = function (ms) {
		var last = 0;
		
		return function () {
			var now = Date.now();
			
			if (now - last > ms) {
				last = now;
				return true;
			} else {
				return false;
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
			
			callbacks = [];
			
			for (; i<ii; i++) {
				c[i]();
			}
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
	
	/**
	 * Promise based delayed execution
	 * 
	 * @param {Number} ms Number of milliseconds
	 * @param {Object} [context] Callback context, optional
	 * @param {Function} [fn] Callback function, optional
	 * @param {Array} [data] Data which will be passed to callbacks, optional
	 * @returns {Object} Promise
	 */
	Supra.later = function (ms, context, fn, data) {
		var deferred = new Supra.Deferred(),
			promise  = deferred.promise();
		
		if (Y.Lang.isArray(context)) {
			data = context;
			context = fn = null;
		}
		
		if (typeof fn === 'function') {
			deferred.done(fn, context);
		}
		
		if (ms >= 0) {
			setTimeout(function () {
				deferred.resolveWith(context || window, data || []);
			}, ms);
		} else {
			deferred.resolveWith(context || window, data || []);
		}
		
		// Cancel should reject promise
		promise.cancel = function () {
			deferred.reject();
		};
		
		return promise;
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version);
