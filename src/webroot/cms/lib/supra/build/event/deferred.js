//Invoke strict mode
"use strict";

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
	
	// Functions which are available on promise object
	var PROMISE_EXPORT = ["done", "fail", "always", "progress", "then", "state"];
	
	var PREPARE_EXPORT = function (fn, name, context) {
		return function () {
			var result = fn.apply(context, [].splice.call(arguments));
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
		 * @private
		 */
		bind: function (event, listener) {
			var state = this._state;
			
			//Validate arugments
			if (!listener || (event !== "rejected" && event !== "resolved" && event !== "notify")) return this;
			
			// Convert to array for easier manipulation
			if (typeof listener === "function") listener = [listener];
			if (!Y.Lang.isArray(listener)) return this;
			
			// If already resolved or rejected, then call listeners
			if (state === "resolved" || state === "rejected") {
				if (event === state) {
					this.fire(listener, global, this._args);
				}
			} else {
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
			if (dest || !promise) {
				var promise = dest || {},
					exports = PROMISE_EXPORT,
					prepare = PREPARE_EXPORT,
					i = 0,
					ii = exports.length;
				
				for (; i<ii; i++) {
					promise[exports[i]] = prepare(this[exports[i]], this);
				}
				
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
		 */
		done: function (listener) {
			return this.bind("resolved", listener);
		},
		
		/**
		 * Add listener to be called when deferred object is rejected
		 * 
		 * @param {Function} listener Function, or array of functions, called when Deferred object is rejected
		 */
		fail: function (listener) {
			return this.bind("rejected", listener);
		},
		
		/**
		 * Add listener to be called when deferred object notifies progress
		 * 
		 * @param {Function} listener Function, or array of functions, called when Deferred object notifies progress
		 */
		progress: function (listener) {
			return this.bind("notify", listener);
		},
		
		/**
		 * Add listeners to be called when deferred object is resolved or rejected
		 * 
		 * @param {Function} done Function, or array of functions, called when Deferred object is resolved
		 * @param {Function} fail Function, or array of functions, called when Deferred object is rejected
		 * @param {Function} progress Function, or array of functions, called when Deferred object notifies progress
		 */
		then: function (done, fail, progress) {
			return this.bind("resolved", done)
					   .bind("rejected", fail)
					   .bind("notify", progress);
		},
		
		/**
		 * Add listener to be called when deferred object is resolved or rejected
		 */
		always: function (listener) {
			return this.done(listener).fail(listener);
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
	
	Supra.Deferred = Deferred;
	
}, YUI.version);