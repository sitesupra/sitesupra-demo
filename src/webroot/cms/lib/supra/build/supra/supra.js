//Invoke strict mode
"use strict";

if (typeof Supra === "undefined") {	
(function () {
	/*
	 * Invoke strict mode because using combo may be
	 * loaded with script which doesn't have strict mode
	 */
	"use strict";
	
	/**
	 * Create YUI instance for internal use
	 */
	var Y = YUI();
	
	/**
	 * Global Supra namespace
	 * 
	 * Shorthand of YUI(Supra.YUI_BASE).use(...)
	 * Only required argument is "ready callback function"
	 * 
	 * @param {Object} base Optional. Extend base with this argument
	 * @param {String} require Optional. Module which will be loaded before calling ready function
	 * @param {Function} fn Required. Ready callback function
	 */
	var Supra = window.Supra = window.SU = function () {
		var base = null;
		var args = [].concat(Supra.useModules);
		
		for(var i=0, ii=arguments.length; i<ii; i++) {
			var type = Y.Lang.type(arguments[i]);
			
			if (type == 'function') {	// Callback function
				
				// catch errors in callback function
				var fn = arguments[i];
				
				if (Supra.data.get('catchNativeErrors')) {
					args.push(function () {
						try {
							fn.apply(this, arguments);
						} catch (e) {
							Y.log(e, 'error');
						}
					});	
				} else {
					args.push(fn);
				}
			
			} else if (type == 'string') {				// Module
				
				// add module to the arguments  
				args.push(arguments[i]);
				
			} else if (type == 'array') { 		// List of modules
			
				args = args.concat(arguments[i]);
			
			} else if (type == 'object') { 		// Base parameters
				
				// additional parameters for base
				base = arguments[i];
				Supra.yui_base_set = false;
				
				if ('modules' in base) {
					base = {'groups': {'supra': base}};
				}
			}
		}
		
		if (!Supra.yui_base_set) {
			base = (base ? Y.mix(base, Supra.YUI_BASE, false, null, 0, true) : Supra.YUI_BASE);
			Supra.yui_base_set = true;
		}
		
		//Re-use same YUI instance
		if (base) {
			//If additional base properties are set, apply them
			Y.applyConfig(base);
			Y._setup();
		}
		
		Y.use.apply(Y,args);
	};
	
	/* Make YUI instance accessible from Supra namespace, single instance for App */
	Supra.Y = Y;
	
	/**
	 * Configuration for YUI
	 */
	
	/* YUI() base configuration */
	Supra.YUI_BASE = {
		//YUI file combo
		combine:	true,
	    root:		"/cms/lib/yui." + YUI.version + "/build/",
		base:		"/cms/lib/yui." + YUI.version + "/build/",
	    comboBase:	"/cms/lib/supra/combo/combo.php?",
	    filter:		{
				        //Remove supra. from module paths
				        'searchExp': "(supra|website)\\.([^/]*?)(-min)?(\\.js|\\.css)?",
				        'replaceStr': "$2$4"
			        },
		
		//Default skin
		skin: {
			defaultSkin: "supra"
		},
		
		//Add groups to enable automatic loading of Supra modules
		//Additional groups can be added using Supra.setModuleGroupPath
		groups: {
			supra: {
				//Supra modules
				combine: true,
				root: "/cms/lib/supra/build/",
				base: "/cms/lib/supra/build/",
				//Use YUI file combo
				comboBase: "/cms/lib/supra/combo/combo.php?",
				modules: {}	//@see modules.js
			},
			website: {
				//Website specific modules
				combine: true,
				root: "/cms/",
				base: "/cms/",
				//Use YUI file combo
				comboBase: "/cms/lib/supra/combo/combo.php?",
				filter: "raw",
				modules: {}
			}
		}
	};
	
	//YUI() base configuration has been applied
	Supra.yui_base_set = false;
	
	/**
	 * Mix objects or arrays together
	 * HTMLNode 	
	 * 
	 * @param {Object} dest Destination object
	 * @param {Object} src Source object
	 * @param {Boolean} deep If true, object children which are object will be mixed together
	 * @return Mixed object
	 * @type {Object}
	 */
	Supra.mix = function() {
		var options = null,
			key = null,
			src = null,
			copy = null,
			copyIsArray = null,
			target = arguments[0] || {},
			length = arguments.length,
			deep = false;
		
		//Last argument for deep mixing
		if ( typeof arguments[arguments.length - 1] === 'boolean' ) {
			deep = arguments[arguments.length - 1];
			length--;
		}
		
		if (!Y.Lang.isObject(target)) {
			//If not an object and not a function reset to object
			target = {};
		}
		
		for (var i=1; i<length; i++) {
			
			if ((options = arguments[i]) != null && options !== target) {
				// Extend the base object
				for (key in options) {
					src = target[key];
					copy = options[key];
	
					//Prevent loop
					if (target === copy) {
						continue;
					}
	
					//Recurse only if object or array, nodes are recursed
					if (deep && copy && (Y.Lang.isPlainObject(copy) || (copyIsArray = Y.Lang.isArray(copy)))) {
						if (copyIsArray) {
							copyIsArray = false;
							src = src && Y.Lang.isArray(src) ? src : [];
						} else {
							src = src && Y.Lang.isPlainObject(src) ? src : {};
						}
						
						target[key] = Supra.mix( src, copy, deep );
	
					} else if (copy !== undefined) {
						target[key] = copy;
					}
				}
			}
		}
	
		// Return the modified object
		return target;
	};
	
	/**
	 * Throttle function call
	 * 
	 * @param {Function} fn
	 * @param {Number} ms
	 * @param {Object} context
	 * @private
	 */
	Supra.throttle = function (fn, ms, context) {
		var ms = ms || 50;
		var last_time = 0;
		var timeout = null;
		var args = [];
		
		if (ms === -1) {
			return (function() {
				fn.apply(context, arguments);
			});
		}
		
		function call () {
			fn.apply(context || window, args);
			last_time = +new Date();
			clearTimeout(timeout);
			timeout = null;
		}
		
		return function () {
			//Save arguments
			args = [].slice.call(arguments, 0);
			
			if ((+new Date()) - last_time > ms) {
				call();
			} else if (!timeout) {
				timeout = setTimeout(call, ms);
			}
		};
	};
	
})();
}