//Invoke strict mode
"use strict";

if (typeof Supra === "undefined") {	
(function (window) {
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
	var Supra = window.Supra = function () {
		var base = null,
			args = [].concat(Supra.useModules),
			cache_errors = Supra.data.catchNativeErrors,
			type = null;
		
		for(var i=0, ii=arguments.length; i<ii; i++) {
			type = Y.Lang.type(arguments[i]);
			
			if (type == 'function') {	// Callback function
				
				// catch errors in callback function
				var fn = arguments[i];
				
				if (cache_errors) {
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
		}
		
		Y.use.apply(Y,args);
	};
	
	/* Make YUI instance accessible from Supra namespace, single instance for App */
	Supra.Y = Y;
	
	/**
	 * Configuration for YUI
	 */
	
	/* Increase URL max length */
	Y.Env._loader.maxURLLength = 2000;
	
	/* YUI() base configuration */
	Supra.YUI_BASE = {
		//YUI file combo
		combine:	true,
		root: "/public/cms/yui." + Y.version + "/build/",
		base: "/public/cms/yui." + Y.version + "/build/",
	    comboBase:	window.comboBase,
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
				root: "/public/cms/supra/build/",
				base: "/public/cms/supra/build/",
				//Use YUI file combo
				comboBase: window.comboBase,
				modules: {}	//@see modules.js
			},
			website: {
				//Website specific modules
				combine: true,
                /* todo: hardcode */
				root: null,
				base: "/public/cms/",
				//Use YUI file combo
				comboBase: window.comboBase,
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
	 * Retrieves the sub value at the provided path, from the value object provided.
	 * 
	 * @param {Object} obj The object from which to extract the property value.
	 * @param {Object} path A path array, specifying the object traversal path from which to obtain the sub value.
	 * @returns {Object} The value stored in the path, undefined if not found, undefined if the source is not an object. Returns the source object if an empty path is provided.
	 */
	Supra.getObjectValue = function (obj, path) {
		if (!path || !path.length) {
			return obj;
		}
		if(!obj || !Y.Lang.isObject(obj)) {
			return undefined;
		}
		
		var i = 0,
			path = Y.Array(path),
			size = path.length;
		
		for (; obj !== undefined && i < size; i++) {
			obj = obj ? obj[path[i]] : undefined;
		}
		
		return obj;
	};
	
})(window);
}
