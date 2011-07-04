//Invoke strict mode
"use strict";

YUI().add("supra.io", function (Y) {
	
	Supra.io = function (url, cfg, context) {
		var cfg = cfg || {},
			fn = null,
			io = null,
			fn_success = function () {};
		
		//Second parameter is allowed to be callback function
		if (Y.Lang.isFunction(cfg)) {
			fn_success = cfg;
			cfg = {};
		}
		
		var cfg_default = {
			'type': 'json',
			'data': null,
			'sync': false,
			'on': {
				'success': fn_success,
				'failure': function () {}
			}
		};
		
		if (context) {
			cfg_default.context = context;
		}
		
		//Use cfg_default and Y.mix to make sure properties for cfg exist
		cfg = Y.mix(cfg, cfg_default, false, null, 0, true);
		
		//Success method is overwrite, save to call later
		fn_success = cfg.on.success;
		
		//Add session id to data
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg.data = {};
		}
		
		var sid_name = SU.data.get('session_name', null),
			sid_id = SU.data.get('session_id', null);
			
		if (sid_name && sid_id) {
			cfg.data[sid_name] = sid_id;
		}
		
		//Convert object into string compatible with PHP
		if ('data' in cfg && Y.Lang.isObject(cfg.data)) {
			cfg.data = Supra.io.serializeIntoString(cfg.data);
		}
		
		cfg.on.success = function (transaction, response, args) {
			var data = null;
			
			try {
				switch((cfg.type || '').toLowerCase()) {
					case 'json':
						data = Y.JSON.parse(response.responseText);
						break;
					default:
						data = response.responseText;
						break;
				}
			} catch (e) {
				//Failed to parse response, call failure event
				if (io.failure) {
					return io.failure.apply(this, args);
				}
			}
			
			//Callback
			if (Y.Lang.isFunction(fn_success)) {
				return fn_success.apply(this, [transaction, data, args]);
			}
			
			return null;
		};
		
		io = Y.io(url, cfg);
		return io;
	};
	
	
	Supra.io.serialize = function (obj, prefix) {
		if (!Y.Lang.isObject(obj) || !Y.Lang.isArray(obj)) return obj;
		var o = {}, name = null;
		
		for(var i in obj) {
			if (obj.hasOwnProperty(i)) {
				name = (prefix ? prefix + '[' + encodeURIComponent(i) + ']' : encodeURIComponent(i));
				
				if (Y.Lang.isObject(obj[i]) || Y.Lang.isArray(obj[i])) {
					o = Y.mix(this.serializeObject(obj[i], name), o);
				} else {
					o[name] = encodeURIComponent(obj[i]);
				}
			}
		}
		
		return o;
	};
	
	Supra.io.serializeIntoString = function (obj, prefix) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var obj = Supra.io.serialize(obj);
		var o = [];
		
		for(var i in obj) {
			o[o.length] = i + '=' + obj[i];
		}
		
		return o.join('&');
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["io", "json"]});