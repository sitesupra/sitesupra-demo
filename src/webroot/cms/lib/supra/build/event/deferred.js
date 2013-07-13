/**
 * Adds Supra.Deferred
 * 
 * Usage:
 * 		var deferred = Supra.Deferred();
 * 		
 * 		//Event handler function will be called when AppList object will exist
 * 		Y.on('exist', function () {...}, 'Supra.Dashboard.AppList');
 */
YUI.add('supra.deferred', function (Y) {
	//Invoke strict mode
	"use strict";
	
	// Functions which are available on promise object
	var PROMISE_EXPORT = ["done", "fail", "always", "progress", "then", "state"];
	
	var PREPARE_EXPORT = function (name, context) {
		return function (a, b, c, d) {
			var result = context[name](a, b, c, d);
			return name === "state" ? result : this;
		};
	};
	
	var global = window;
	
	
	function Deferred () {
		if (!(this instanceof Deferred)) return new Deferred();
		
		//Set initial state
		this._state = "pending";
		this._promise = null;
		this._args = null;
		this._listeners = {"resolved": [], "rejected": [], "notify": []};
		
		//As arguments may be passed done, fail and notify listeners
		if (arguments.length) {
			this.then.apply(this, [].splice.call(arguments));
		}
	}
	
	Deferred.prototype = {
		/**
		 * Deferred object state, values can be
		 *     pending - deferred object has not been resolved or rejected yet
		 *     rejected - deferred object is rejected
		 *     resolved - deferred object was resolved
		 * @type {String}
		 * @private
		 */
		_state: "",
		
		/**
		 * Deffered objects promise
		 * @type {Object}
		 * @private
		 */
		_promise: null,
		
		/**
		 * Listeners
		 * @type {Object}
		 * @private
		 */	
		_listeners: null,
		
		/**
		 * Arguments passed to listeners when Deferred object was resolved or rejected
		 * @type {Array}
		 * @private
		 */
		_args: null,
		
		
		// Deferred private functions
		
		
		/**
		 * Change Deferred object state
		 * 
		 * @param {String} state Deferred state
		 * @param {Object} context Callback context
		 * @param {Array} args Optional arguments passed to callbacks
		 * @private
		 */
		changeState: function (state, context, args) {
			if (this._state === "pending") {
				this._state = state;
				this._args = args;
				this.fire(this._listeners[state], context, args);
				this._listeners = {"resolved": [], "rejected": [], "notify": []};
			}
			return this;
		},
		
		/**
		 * Add listener to be called on state change or when Deferred object notifies progress
		 * 
		 * @param {String} event State on which listener should be called
		 * @param {Function} listener Function, or array of functions, called when Deferred object is resolved
		 * @param {Object} context Listener call context, optional
		 * @private
		 */
		bind: function (event, listener, context) {
			var state = this._state;
			
			//Validate arugments
			if (!listener || (event !== "rejected" && event !== "resolved" && event !== "notify")) return this;
			
			// Convert to array for easier manipulation
			if (typeof listener === "function") listener = [listener];
			if (!Y.Lang.isArray(listener)) return this;
			
			// If already resolved or rejected, then call listeners
			if (state === "resolved" || state === "rejected") {
				if (event === state) {
					this.fire(listener, context || global, this._args);
				}
			} else {
				// Change listener context, NOTE: this overrides resolveWith and rejectWith context
				if (context) {
					for (var i = 0, ii = listener.length; i<ii; i++) {
						listener[i] = Y.bind(listener[i], context);
					}
				}
				this._listeners[event] = this._listeners[event].concat(listener);
			}
			
			return this;
		},
		
		/**
		 * Call all listeners
		 * 
		 * @param {Array} listeners Array of listener functions
		 * @param {Object} context Listener call context
		 * @param {Array} args
		 * @private 
		 */
		fire: function (listeners, context, args) {
			var i = 0,
				ii = listeners.length;
			
			context = context || global;
			args = args || [];
			
			if (!Y.Lang.isArray(args)) {
				args = [args];
			}
			
			for (; i<ii; i++) {
				listeners[i].apply(context, args);
			}
			
			return this;
		},
		
		
		// Deferred public functions
		
		
		/**
		 * Returns deferreds promise object
		 * 
		 * @param {Object} dest Optional object on which deffered promise functions will be set
		 * @return {Object} Deferred promise object 
		 */
		promise: function (dest) {
			if (dest || !this._promise) {
				var promise = dest || {},
					exports = PROMISE_EXPORT,
					prepare = PREPARE_EXPORT,
					i = 0,
					ii = exports.length;
				
				for (; i<ii; i++) {
					promise[exports[i]] = prepare(exports[i], this);
				}
				
				// For convinience promise has 'promise' method
				promise.promise = function () { return this; };
				
				if (!dest) {
					this._promise = promise;
				}
				return promise;
			} else {
				return this._promise;
			}
		},
		
		/**
		 * Resolve a Deferred object
		 * 
		 * @param {Array} args Optional arguments passed to done callbacks
		 */
		resolve: function (args) {
			return this.changeState("resolved", global, args);
		},
		
		/**
		 * Resolve a Deferred object
		 * 
		 * @param {Object} context Callback context
		 * @param {Array} args Optional arguments passed to done callbacks
		 */
		resolveWith: function (context, args) {
			return this.changeState("resolved", context, args);
		},
		
		/**
		 * Reject a Deferred object
		 * 
		 * @param {Array} args Optional arguments passed to fail callbacks
		 */
		reject: function (args) {
			return this.changeState("rejected", global, args);
		},
		
		/**
		 * Reject a Deferred object
		 * 
		 * @param {Object} context Callback context
		 * @param {Array} args Optional arguments passed to fail callbacks
		 */
		rejectWith: function (context, args) {
			return this.changeState("rejected", context, args);
		},
		
		/**
		 * Notify Deferred objects progress listeners
		 * 
		 * @param {Array} args Optional arguments passed to progress callbacks
		 */
		notify: function (args) {
			if (this._state === "pending") {
				this.fire(this._listeners.notify, global, args);
			}
			return this;
		},
		
		/**
		 * Notify Deferred objects progress listeners
		 * 
		 * @param {Object} context Callback context
		 * @param {Array} args Optional arguments passed to progress callbacks
		 */
		notifyWith: function (args) {
			if (this._state === "pending") {
				this.fire(this._listeners.rejected, context, args);
			}
			return this;
		},
		
		
		// Promise functions
		
		
		/**
		 * Add listener to be called when deferred object is resolved
		 * 
		 * @param {Function} listener Function, or array of functions, called when Deferred object is resolved
		 * @param {Object} context Listener call context, optional
		 */
		done: function (listener, context) {
			return this.bind("resolved", listener, context);
		},
		
		/**
		 * Add listener to be called when deferred object is rejected
		 * 
		 * @param {Function} listener Function, or array of functions, called when Deferred object is rejected
		 * @param {Object} context Listener call context, optional
		 */
		fail: function (listener, context) {
			return this.bind("rejected", listener, context);
		},
		
		/**
		 * Add listener to be called when deferred object notifies progress
		 * 
		 * @param {Function} listener Function, or array of functions, called when Deferred object notifies progress
		 * @param {Object} context Listener call context, optional
		 */
		progress: function (listener, context) {
			return this.bind("notify", listener, context);
		},
		
		/**
		 * Add listeners to be called when deferred object is resolved or rejected
		 * 
		 * @param {Function} done Function, or array of functions, called when Deferred object is resolved
		 * @param {Function} fail Function, or array of functions, called when Deferred object is rejected
		 * @param {Function} progress Function, or array of functions, called when Deferred object notifies progress
		 * @param {Object} context Listener call context, optional
		 */
		then: function (done, fail, progress, context) {
			return this.bind("resolved", done, context)
					   .bind("rejected", fail, context)
					   .bind("notify", progress, context);
		},
		
		/**
		 * Add listener to be called when deferred object is resolved or rejected
		 * @param {Object} context Listener call context, optional
		 */
		always: function (listener, context) {
			return this.done(listener, context).fail(listener, context);
		},
		
		/**
		 * Returns deferred object state:
		 *     pending - deferred object has not been resolved or rejected yet
		 *     rejected - deferred object is rejected
		 *     resolved - deferred object was resolved
		 * 
		 * @return {String} Deferred object state
		 */
		state: function () {
			return this._state;
		}
	};
	
	/**
	 * Provides a way to execute callback functions on one or more Deferred objects.
	 * 
	 * If only one deferred object (or promise) is passed then returns its promise
	 * 
	 * If more than one is passed then returns new Deferred object which is resolved
	 * when all Deferred objects are resolved or is rejected when any of them is rejected.
	 * Returned Deferred object is resolved with all arguments from each object, eq.
	 *     Supra.Deferred.when( Supra.io('a'), Supra.io('b') ).then(function (a_args, b_args) {
	 * 	       alert(a_args[1]); // <- status
	 *     })
	 */
	Deferred.when = function () {
		var args = [].splice.call(arguments);
		
		// If first argument is array then use it as deferred list
		if (args.length === 1 && Y.Lang.isArray(args[0])) { 
			args = args[0];
		}
		
		// If there is only a single deferred object, then return its promise
		if (args.length === 1) {
			if (Y.Lang.isFunction(args[0].promise)) {
				return args[0].promise();
			} else {
				var deferred = new Deferred();
				deferred.resolveWith(deferred, args[0]);
				return deferred.promise();
			}
		} else {
			var results = [],
				count = args.length,
				waiting = 0,
				deferred = new Deferred();
			
			for (var i=0; i<count; i++) {
				if (args[i] && Y.Lang.isFunction(args[i].then)) {
					// Promise
					waiting++;
					(function (index, src) {
						src.then(function () {
							// On success update argument list and check if all has been resolved
							results[index] = [].splice.call(arguments);
							waiting--;
							if (!waiting) deferred.resolveWith(results);
						}, function () {
							// On failure reject immediately
							waiting--;
							deferred.reject();
						});
					})(i, args[i]);
				} else {
					results[i] = args[i];
					waiting--;
					if (!waiting) deferred.resolveWith(results);
				}
			}
			
			if (!waiting) {
				// No deferred's
				deferred.resolve();
			}
			
			return deferred;
		}
		
		// Blank promise, which is resolved immediately
		return (new Deferred()).resolve().promise();
	};
	
	Supra.Deferred = Deferred;
	
}, YUI.version);