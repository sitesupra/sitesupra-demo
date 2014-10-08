(function () {
	
	var Y = Supra.Y;
	
	/**
	 * Data storage
	 */
	Supra.data = {
		
		//Date format
		'dateFormat': '%d.%m.%Y',
		
		//First day of the week: 1 - Monday, 0 - Sunday
		'dateFirstWeekDay': 1,
		
		//Time format
		'timeFormat': '%H:%M:%S',
		'timeFormatShort': '%H:%M',
		
		//Locale (LANGUAGE_CONTEXT)
		'locale': '',

		'contexts': [],
		
		//Catch JS errors
		'catchNativeErrors': false,
		
		//Session check by periodically pinging server
		'sessionCheckPings': false,
		
		
		/**
		 * Set data
		 */
		set: function (key ,value) {
			var group = null;
			if (value && Y.Lang.isObject(value)) {
				if (key in Supra.YUI_BASE.groups.supra.modules) group = Supra.YUI_BASE.groups.supra.modules;
				else if (key in Supra.YUI_BASE.groups.website.modules) group = Supra.YUI_BASE.groups.website.modules;
				
				if (group) {
					var mod = Supra.YUI_BASE.groups.supra.modules[key], found = false;
					if (value.requires) {
						for(var i=0,ii=value.requires.length; i<ii; i++) {
							if (Y.Array.indexOf(mod.requires, value.requires[i]) == -1) {
								mod.requires.push(value.requires[i]);
							}
						}
					}
				}
			}
			
			if (value === undefined && Y.Lang.isObject(key)) {
				Supra.mix(Supra.data, key, true); 
			} else {
				var fn = '_' + key + 'Change',
					prevVal = Supra.data[key];
				
				Supra.data[key] = value;
				
				if (fn in Supra.data) {
					Supra.data[fn](value, prevVal);
				}
			}
		},
		
		/**
		 * Returns data
		 * 
		 * @param {String} key
		 * @param {Object} default_value
		 * @return Data item
		 * @type {Object}
		 */
		get: function (keys, default_value) {
			var keys = Y.Lang.isArray(keys) ? keys : [keys],
				ret = Supra.data;
			
			for(var i=0,ii=keys.length; i<ii; i++) {
				if (typeof ret == 'object' && keys[i] in ret) {
					ret = ret[keys[i]];
				} else {
					// Look for getter if there is no such key
					if (keys.length == 1 && typeof keys[0] === 'string') {
						var fn = '_' + keys[0] + 'Getter';
						if (this[fn] && typeof this[fn] === 'function') return this[fn]();
					}
					return default_value;
				}
			}
			
			return ret;
		},

		/**
		 * Get locale data by ID
		 *
		 * @param {String} key
		 * @return locale data object
		 * @type {Object}
		 */
		getLocale: function (localeId) {
			var contexts = Supra.data.get('contexts'),
				context;
			
			for(var i=0,ii=contexts.length; i<ii; i++) {
				context = contexts[i];
				for(var k=0,kk=context.languages.length; k<kk; k++) {
					if (context.languages[k].id == localeId) {
						return context.languages[k];
					}
				}
			}
		},
		
		/**
		 * Mix together
		 * 
		 * @param {Object} target
		 * @param {Object} source
		 */
		mix: function (target, source) {
			Supra.data.set(target, Supra.Y.mix(source, Supra.data.get(target, {}), false, null, 0, 2));
		},
		
		/**
		 * Add function which will handle property change
		 * 
		 * @param {String} property Property name
		 * @param {Function} handler Handler function
		 */
		registerHandler: function (property, handler) {
			if (Y.Lang.isFunction(handler)) {
				this['_' + property + 'Change'] = handler;
			}
		},
		
		
		
		/**
		 * When date format changes update YUI configuration
		 */
		_dateFormatChange: function (newVal, prevVal) {
			if (newVal !== prevVal) {
				Supra.Y.config.dateFormat = newVal;
				Supra.Y.Global.fire('dataFormatChange', {'newVal': newVal, 'prevVal': prevVal});
			}
		},
		
		/**
		 * On locale change fire event
		 */
		_localeChange: function (newVal, prevVal) {
			if (newVal !== prevVal) {
				Supra.Y.Global.fire('localeChange', {'newVal': newVal, 'prevVal': prevVal});
			}
		},
		
		/**
		 * On sessionCheckPings change start/stop timer
		 */
		_sessionCheckPingsChange: function (newVal, prevVal) {
			if (newVal !== prevVal) {
				if (newVal) {
					Supra.session.ping();
				} else {
					Supra.session.cancelPing();
				}
			}
		},
		
		/**
		 * Returns true if language features are enabled, otherwise false
		 * 
		 * @returns {Boolean} True if enabled, otherwise false
		 */
		_languageFeaturesEnabledGetter: (function () {
			var supported = null;
			
			function checkSupport () {
				// Only in portal language features may not be visible to user
				// if (!Supra.data.get(["site", "portal"])) return false;
				
				var contexts = Supra.data.get('contexts') || [],
					count = 0;
			
				for(var i=0,ii=contexts.length; i<ii; i++) count += contexts[i].languages.length;
				
				// There is only one language and this is portal site, don't show any language related features
				if (count <= 1) return false;
				
				// More than one language, show features
				return true;
			}
			
			return function () {
				if (supported === null) {
					supported = checkSupport();
				}
				return supported;
			}
		})()
	};
	
	//Update YUI configuration on load
	Supra.Y.config.dateFormat = Supra.data.dateFormat;
	
})();