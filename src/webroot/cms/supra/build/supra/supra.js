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
	var Y = YUI(), yui_base_set = false;
	
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
				args.push(function () {
					try {
						fn.apply(this, arguments);
					} catch (e) {
						Y.log(e, 'error');
					}
				});
			
			} else if (type == 'string') {				// Module
				
				// add module to the arguments  
				args.push(arguments[i]);
				
			} else if (type == 'array') { 		// List of modules
			
				args = args.concat(arguments[i]);
			
			} else if (type == 'object') { 		// Base parameters
				
				// additional parameters for base
				base = arguments[i];
				
				if ('modules' in base) {
					base = {'groups': {'supra': base}};
				}
			}
		}
		
		if (!yui_base_set) {
			base = (base ? Y.mix(base, Supra.YUI_BASE, false, null, 0, true) : Supra.YUI_BASE);
		}
		
		//Re-use same YUI instance
		if (base) {
			//If additional base properties are set, apply them
			Y.applyConfig(base);
			Y._setup();
		}
		
		Y.use.apply(Y,args);
	};
	
	/* Make YUI instance accessible from Supra namespace */
	Supra.Y = Y;
	
	/**
	 * Configuration for YUI
	 */
	
	/* YUI() base configuration */
	Supra.YUI_BASE = {
		//YUI file combo
		combine:	true,
	    root:		"/cms/yui." + YUI.version + "/build/",
		base:		"/cms/yui." + YUI.version + "/build/",
	    comboBase:	"/cms/yui." + YUI.version + "/combo/combo.php?",
	    filter:		"min",	//min, debug, raw
		
		//Default skin
		skin: {
			defaultSkin: "supra"
		},
		
		//Add groups to enable automatic loading of Supra modules
		groups: {
			supra: {
				//Supra modules
				combine: true,
				root: "/cms/supra/build/",
				base: "/cms/supra/build/",
				//Use YUI file combo
				comboBase: "/cms/yui." + YUI.version + "/combo/combo.php?",
				filter: "raw",
				modules: null	//@see modules.js
			},
			website: {
				//Website specific modules
				combine: true,
				root: "/cms/",
				base: "/cms/",
				//Use YUI file combo
				comboBase: "/cms/yui." + YUI.version + "/combo/combo.php?",
				filter: "raw",
				modules: {}
			}
		}
	};
	
	/**
	 * Mix objects together
	 * 
	 * @param {Object} dest Destination object
	 * @param {Object} src Source object
	 * @param {Boolean} deep If true, object children which are object will be mixed together
	 * @return Mixed object
	 * @type {Object}
	 */
	Supra.mix = function () {
		if (!arguments.length || !Y.Lang.isObject(arguments[0])) return null;
		var dest = arguments[0];
		var args = [].slice.call(arguments, 1, arguments.length);
		var deep = Y.Lang.isBoolean(args[args.length-1]) ? args[args.length-1] : false;
		
		for(var i=0, ii=args.length; i<ii; i++) {
			if (Y.Lang.isObject(args[i])) {
				for(var k in args[i]) {
					if (deep && Y.Lang.isObject(args[i][k])) {
						if (Y.Lang.isObject(dest[k])) {
							dest[k] = Supra.mix(dest[k], args[i][k]);
						} else {
							dest[k] = Supra.mix({}, args[i][k]);
						}
					} else {
						dest[k] = args[i][k];
					}
				}
			}
		}
		
		return dest;
	};

})();
}