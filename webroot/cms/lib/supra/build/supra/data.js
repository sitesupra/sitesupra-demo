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
					Supra.data[fn](value);
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
					return default_value;
				}
			}
			
			return ret;
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
				this['_' + name + 'Change'] = handler;
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
		}
	};
	
	//Update YUI configuration on load
	Supra.Y.config.dateFormat = Supra.data.dateFormat;
	
})();