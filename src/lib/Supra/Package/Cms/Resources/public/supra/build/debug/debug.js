(function () {
	//Invoke strict mode
	"use strict";
	
	// create YUI instance
	var Y = YUI();
	
	// namespace for Debug
	var D = Supra.Debug = {
		
		paused: false,
		argument_max_length: 15,
		
		/**
		 * Adds proxy function around function or all functions in 
		 * context to catch errors which occurs during call of these
		 * functions
		 * 
		 * Usage:
		 * 
		 * 		var myObject = {
		 * 			"myFunction": function () { throw "error"; }
		 * 		};
		 * 
		 * 		Supra.Debug.catchErrors(myObject);
		 * 		Supra.Debug.catchErrors("myFunction", myObject);
		 * 		Supra.Debug.catchErrors("myFunction");
		 * 			same as:
		 * 			Supra.Debug("myFunction", window)
		 * 
		 * 		myObject.myFunction();		//logs errors and continues execution
		 * 
		 * @param {Object} fn
		 * @param {Object} context
		 */
		catchErrors: function (fn, context, proxy_generator) {
			//Function which creates proxy
			var proxy_generator = proxy_generator || D.proxyFunction;
			
			// if only argument is object, traverse it and
			// replace all functions with proxy
			if (!context && Y.Lang.isObject(fn, true)) {
				context = fn;
				for(var i in context) {
					if (Y.Lang.isFunction(context[i])) {
						context[i] = proxy_generator(context[i], null, i);
					}
				}
				return context;
			} else if (typeof fn == 'function') {
				return proxy_generator(fn, context);
			} else if (typeof fn == 'string') {
				context = context || window;
				if ((fn in context) && Y.Lang.isFunction(context[fn])) {
					context[fn] = proxy_generator(context[fn], null, fn);
				}
			}
			
			return fn;
		},
		
		/**
		 * Returns proxy function, which catches errors and logs them
		 * 'fn' is called in 'context' or original context if this argument
		 * is not present 
		 * 
		 * @param {Object} fn
		 * @param {Object} context
		 */
		proxyFunction: function (fn, context, fn_name) {
			// proxy function already exists
			if (fn.proxy) return fn.proxy;
			
			// fn already is a proxy function
			if (fn.is_proxy) return fn;
			
			// create wrapper function which will catch errors in
			// function
			var proxy = function () {
				try {
					return fn.apply(context || this, arguments);
				} catch (e) {
					Y.log(e, 'error');
				}
			};
			
			proxy.is_proxy = true;
			fn.proxy = proxy;
			return proxy;
		},
		
		/**
		 * Trace function execution order, arguments and return values
		 */
		traceCalls: function (fn, context) {
			return D.catchErrors(fn, context, D._traceProxyFunction);
		},
		
		/**
		 * Proxy function generator for trace
		 */
		_traceProxyFunction: function (fn, context, fn_name) {
			D._indent = D._indent || 0;
			
			// proxy function already exists
			if (fn.proxy) return fn.proxy;
			
			// fn already is a proxy function
			if (fn.is_proxy) return fn;
			
			if (!fn_name) {
				fn_name = 'anonymous';
				var matches = fn.toString().match(/function ([a-z0-9\-\_]+)/i);
				if (matches) {
					fn_name = matches[1];
				}
			}
			
			// create wrapper function which will output function
			// and arguments and catch errors in function
			var proxy = function () {
				var indent = '';
				var return_value;
				
				for(var i=0; i<D._indent; i++) indent += '    ';
				D._indent++;
				
				try {
					return_value = fn.apply(context || this, arguments);
				} catch (e) {
					return_value = 'ERROR';
					Y.log(e, 'error');
				}
				
				//Get arguments for output
				var output = [indent + fn_name + ' ('];
				var args = [];
				for(var i=0,ii=arguments.length; i<ii; i++) {
					var arg = String(arguments[i]);
					args[args.length] = arg.length > D.argument_max_length ? arg.substr(0, D.argument_max_length) + '...' : arg;
				}
				
				//Get return value for output
				var return_output = String(return_value);
					return_output = return_output.length > D.argument_max_length ? return_output.substr(0, D.argument_max_length) + '...' : return_output;
				
				//Log call
				Y.log(indent + fn_name + ' (' + args.join(', ') + '):' + return_output);
				D._indent = Math.max(0, D._indent-1);
				
				return return_value;
			};
			
			proxy.is_proxy = true;
			fn.proxy = proxy;
			return proxy;
		}
	};
	
})();