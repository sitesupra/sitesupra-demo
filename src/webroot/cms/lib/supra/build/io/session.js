//Invoke strict mode
"use strict";

YUI().add("supra.io-session", function (Y) {
	
	var PING_INTERVAL = 60000;
	var PING_URI = '/cms/check-session';
	
	/**
	 * Supra.session pings server at specific interval to keep
	 * server-side session alive
	 */
	Supra.session = {
		/**
		 * Timeout handler
		 * @type {Object}
		 * @private
		 */
		timeout_handler: null,
		
		/**
		 * Ping server
		 */
		ping: function () {
			if (this.timeout_handler) return;
			this.timeout_handler = Y.later(PING_INTERVAL, this, this._pingRequest, null, true);
		},
		
		/**
		 * Cancel server pinging
		 */
		cancelPing: function () {
			if (this.timeout_handler) {
				this.timeout_handler.cancel();
				this.timeout_handler = null;
			}
		},
		
		/**
		 * Execute ping request
		 * 
		 * @private
		 */
		_pingRequest: function () {
			Supra.io(PING_URI);
		}
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["io"]});