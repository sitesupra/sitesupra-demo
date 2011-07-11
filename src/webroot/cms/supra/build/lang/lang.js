//Invoke strict mode
"use strict";

YUI().add("supra.lang", function (Y) {
	
	//If already defined, then exit
	if (Y.Lang.escapeHTML) return;
	
	Y.Lang.escapeHTML = function (html) {
		return String(html || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	};
	
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