YUI.add('supra.intl', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Intl = Supra.Intl = {
		
		/**
		 * Data filename
		 * @type {String}
		 * @private
		 */
		FILENAME: 'lang',
		
		/**
		 * Default locale which filename shouldn't have a prefix
		 * @type {String}
		 * @private
		 */
		DEFAULT_NON_PREFIXED_LOCALE: 'en',
		
		/**
		 * Internationalized data
		 * @type {Object}
		 * @private
		 */
		data: {},
		
		/**
		 * Paths for which Intl data is loaded
		 * @type {Object}
		 * @private
		 */
		loaded: {},
		
		/**
		 * List of Intl data which is being loaded
		 * @type {Object}
		 * @private
		 */
		loading: {},
		
		/**
		 * Callbacks
		 * @type {Object}
		 * @private
		 */
		callbacks: {},
		
		
		/**
		 * Add internationalization
		 * 
		 * @param {Object} data Data
		 */
		add: function (data /* Data */) {
			
			//Add to data
			Supra.mix(this.data, data || {}, true);
			
			//Add to Y.Intl
			for(var ns in data) {
				Supra.Y.Intl.add(ns, '', data[ns]);
			}
		},
		
		/**
		 * Returns true if internationalization data is already loaded
		 * 
		 * @param {String} app_path Application path
		 * @return True if already loaded
		 * @type {Boolean}
		 */
		isLoaded: function (app_path /* Application path */) {
			return this.loaded[app_path];
		},
		
		/**
		 * Load internationalization data
		 * 
		 * @param {String} app_path Application path
		 * @param {String} requestURI Request URI
		 * @param {Function} callback Optional. Callback function
		 * @param {Object} context Optional. Callback execution context
		 */
		load: function (app_path /* Application path*/, requestURI /* Request URI */, callback /* Callback */, context /* Context */) {
			return Supra.io(requestURI, {
				'context': this,
				'on': {
					'complete': function (data, status) {
						this.loading[app_path] = false;
						this.loaded[app_path] = true;
						
						if (data) this.add(data);
						
						//Execute callbacks
						var callbacks = this.callbacks[app_path];
						if (callbacks) {
							delete(this.callbacks[app_path]);
							for(var i=0,ii=callbacks.length; i<ii; i++) {
								callbacks[i][0].call(callbacks[i][1], data);
							}
						}
					}
				}
			});
		},
		
		/**
		 * Load internationalization data for application
		 * 
		 * @param {String} app_path Application path
		 * @param {Function} callback Optional. Callback function
		 * @param {Object} context Optional. Callback execution context
		 */
		loadAppData: function (app_path /* Application path */, callback /* Callback */, context /* Context */) {
			var deferred = null,
				promise  = null;
			
			if (this.loaded[app_path]) {
				//Call callback
				if (Y.Lang.isFunction(callback)) {
					callback.call(context || window, this.data);
				}
				
				//Resolve
				deferred = new Supra.Deferred();
				deferred.resolveWith(this, [this.data]);
				return deferred.promise();
			}
			
			//Add callback to the list
			if (Y.Lang.isFunction(callback)) {
				if (!this.callbacks[app_path]) this.callbacks[app_path] = [];
				this.callbacks[app_path].push([callback, context || window]);
			}
			
			//Already loading, skip
			if (this.loading[app_path]) {
				return this.loading[app_path];
			}
			
			var locale = Supra.data.get('lang', ''),
				prefix = '',
				uri    = app_path + '/';
			
			if (locale && locale != this.DEFAULT_NON_PREFIXED_LOCALE) {
				prefix = '.' + locale;
			}
			
			uri += this.FILENAME + prefix + '.json';
			
			promise = this.load(app_path, uri, callback ,context);
			this.loading[app_path] = promise;
			
			return promise;
		},
		
		/**
		 * Returns internationalized string
		 * 
		 * @param {Array|String} ns Namespace
		 * @param {Object} data Optional. Data to check against
		 * @return Internationalized string
		 * @type {String}
		 */
		get: function (ns /* Namespace */, data /* Data to check against */) {
			if (typeof ns === 'string') {
				ns = ns.split('.');
			}
			
			var obj = data || this.data,
				i = 0,
				ii = ns.length;
			
			for(; i<ii; i++) {
				obj = obj[ns[i]];
				if (obj === undefined) {
					//If data exists then already checked against Y.Intl
					if (data) return null;
					return this.get(ns.slice(1), Y.Intl.get(ns[0]));
				}
			}
			
			return obj;
		},
		
		/**
		 * Replace all occurances of {#...#} with internationalized strings
		 * 
		 * @param {String} template Template
		 * @param {String} escape Escape type
		 * @return Internationalized template
		 * @type {String}
		 */
		replace: function (template /* Template */, escape /* Escape type */) {
			var self = this,
				template = String(template || '');

			if (template.indexOf('#') == -1) {
				return template;
			}
			
			return template.replace(/{#([^#]+)#}/g, function (all, key) {
				var key = Y.Lang.trim(key),
					ret = self.get(key.split('.'));
				
				if (ret === null) {
					// Didn't found the key, skip
					ret = all;
				}
				
				if (escape == 'json') { //Escape as JSON string without leading and trailing quotes
					ret = Y.JSON.stringify(ret).replace(/^"|"$/g, '');
				} else if (escape == 'html') {
					ret = Y.Escape.html(ret);
				}
				
				return ret;
			});
		}
	};
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
	
}, YUI.version, {'requires': ['intl', 'supra.io']});