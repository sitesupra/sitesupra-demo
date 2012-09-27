YUI().add("supra.lang", function (Y) {
	//Invoke strict mode
	"use strict";
	
	//If already defined, then exit
	if (Y.Lang.toArray) return;
	
	//Shortcuts
	var hasOwnProperty = Object.prototype.hasOwnProperty;
	var WINDOW = window.constructor;
	var DOCUMENT = document.constructor;
	
	
	
	/**
	 * Convert Object into Array
	 * 
	 * @param {Object} obj
	 * @return Array
	 * @type {Array}
	 */
	Y.Lang.toArray = function (obj) {
		if ('length' in obj) {
			return [].slice.call(obj, 0);
		} else {
			var arr = [], ii=0;
			for(var i in obj) {
				if (obj.hasOwnProperty(i)) arr[ii++] = obj[i];
			}
			return arr;
		}
	};
	
	/**
	 * Returns true if obj is Object and not Array, Function, document or window
	 * 
	 * @param {Object} obj
	 * @return True if obj is plain object
	 * @type {Boolean}
	 */
	Y.Lang.isPlainObject = function (obj) {
		if (obj &&									//not empty
			Y.Lang.isObject(obj, true) &&			//is object and not function
			!obj.nodeType &&						//not HTMLElement
			!Y.Lang.isArray(obj) &&					//not array
			!(obj instanceof DOCUMENT) &&			//not document
			!(obj instanceof WINDOW)) {				//not window
			
			// Not own constructor property must be Object
			if ( obj.constructor &&
				!hasOwnProperty.call(obj, "constructor") &&
				!hasOwnProperty.call(obj.constructor.prototype, "isPrototypeOf") ) {
				return false;
			}
			
			return true;
			
		}
		return false;
	};
	
	/**
	 * Returns true if obj is widget instance
	 * 
	 * @param {Object} obj Object to check
	 * 
	 */
	Y.Lang.isWidget = function (obj, classname) {
		if (obj && Y.Lang.isObject(obj) && !Y.Lang.isPlainObject(obj) && obj.isInstanceOf) {
			
			if (classname) {
				return obj.isInstanceOf(classname);
			}
			
			return true;
		}
		
		return false;
	};
	
	/**
	 * Compare if two objects has all the same properties
	 * 
	 * @param {Object} o1
	 * @param {Object} o2
	 * @private
	 */
	Y.Lang.compareObjects = function (o1, o2) {
		var o1_size = 0,
			o2_size = 0,
			v1 = null,
			v2 = null;
			key = null;
		
		for(key in o2) o2_size++;
		
		for(key in o1) {
			if (!(key in o2)) return false;
			v1 = o1[key];
			v2 = o2[key];
			
			if ((Y.Lang.isArray(v1) && Y.Lang.isArray(v2)) || (Y.Lang.isObject(v1) && Y.Lang.isObject(v2))) {
				if (!Y.Lang.compareObjects(v1, v2)) return false;
			} else {
				if (v1 !== v2) return false;
			}
			
			o1_size++;
		}
		
		return o1_size == o2_size;
	};

}, YUI.version);