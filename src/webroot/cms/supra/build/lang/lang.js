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
	
}, YUI.version);