YUI().add("supra.io-session", function (Y) {
	//Invoke strict mode
	"use strict";
	
	var PING_INTERVAL = 60000;
	
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
		 * Has there been any activity since last ping
		 * @type {Boolean}
		 * @private
		 */
		activity: false,
		
		/**
		 * Mouse event listener
		 * @type {Object}
		 * @private
		 */
		activity_mouse_listener: null,
		
		/**
		 * Key event listener
		 * @type {Object}
		 * @private
		 */
		activity_key_listener: null,
		
		
		/**
		 * Ping server
		 */
		ping: function () {
			if (this.timeout_handler) return;
			this.timeout_handler = Y.later(PING_INTERVAL, this, this._pingRequest, null, true);
			
			this.activity = false;
			this._addActivityListeners();
		},
		
		/**
		 * Cancel server pinging
		 */
		cancelPing: function () {
			if (this.timeout_handler) {
				this.timeout_handler.cancel();
				this.timeout_handler = null;
				
				this.activity = false;
				this._removeActivityListeners();
			}
		},
		
		/**
		 * Remove activity event listeners
		 * 
		 * @private
		 */
		_removeActivityListeners: function () {
			if (this.activity_mouse_listener) {
				this.activity_mouse_listener.detach();
				this.activity_key_listener.detach();
				
				this.activity_mouse_listener = null;
				this.activity_key_listener = null;
			}
		},
		
		/**
		 * Add activity event listenesr
		 * 
		 * @private
		 */
		_addActivityListeners: function () {
			if (!this.activity_mouse_listener) {
				var doc = new Y.Node(document);
				this.activity_mouse_listener = doc.on('click', this.triggerActivity, this);
				this.activity_key_listener = doc.on('keydown', this.triggerActivity, this);
			}
		},
		
		/**
		 * Change activity state
		 */
		triggerActivity: function () {
			this.activity = true;
			this._removeActivityListeners();
		},
		
		/**
		 * Execute ping request
		 * 
		 * @private
		 */
		_pingRequest: function () {
			Supra.io(Supra.Url.generate('cms_check_session'), {
				'data': {
					'activity': this.activity
				}
			});
			
			//Add activity event listeners
			this.activity = false;
			this._addActivityListeners();
		}
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["io"]});