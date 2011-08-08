//Invoke strict mode
"use strict";

YUI.add('supra.intl', function (Y) {
	
	var Intl = Supra.Intl = {
		
		/**
		 * Data filename
		 * @type {String}
		 * @private
		 */
		FILENAME: 'lang.json',
		
		/**
		 * Internationalized data
		 * @type {Object}
		 * @private
		 */
		data: {},
		
		
		
		/**
		 * Add internationalization
		 * 
		 * @param {Object} data Data
		 */
		add: function (data /* Data */) {
			
			//Add to data
			Supra.mix(this.data, data || {});
			
			//Add to Y.Intl
			for(var ns in data) {
				SU.Y.Intl.add(ns, '', data[ns]);
			}
		},
		
		/**
		 * Load internationalization data
		 * 
		 * @param {String} requestURI Request URI
		 * @param {Function} callback Optional. Callback function
		 * @param {Object} context Optional. Callback execution context
		 */
		load: function (requestURI /* Request URI */, callback /* Callback */, context /* Context */) {
			
			Supra.io(requestURI, {
				'data': {
					'lang': Supra.data.get('lang', '')
				},
				'context': this,
				'on': {
					'complete': function (data, status) {
						if (data) this.add(data);
						if (Y.Lang.isFunction(callback)) {
							context = context || window;
							callback.call(context, data);
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
			var uri = app_path + '/' + this.FILENAME;
			this.load(uri, callback ,context);
		},
		
		/**
		 * Returns internationalized string
		 * 
		 * @param {Array} ns Namespace
		 * @param {Object} data Optional. Data to check against
		 * @return Internationalized string
		 * @type {String}
		 */
		get: function (ns /* Namespace */, data /* Data to check against */) {
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
		replace: function (template /* Template */, escape /* Escape type*/) {
			var self = this;
			return template.replace(/{#([^#]+)#}/g, function (all, key) {
				var ret = self.get(key.split('.')) || all;
				
				if (escape == 'json') { //Escape as JSON string without leading and trailing quotes
					ret = Y.JSON.stringify(ret).replace(/^"|"$/g, '');
				}
				
				return ret;
			});
		}
	};
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
	
}, YUI.version, {'requires': ['intl', 'supra.io']});