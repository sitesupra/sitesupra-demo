/**
 * Extension to load CSS files
 * Needed because Y.Get.css doesn't 
 */
YUI().add("supra.io-css", function (Y) {
	//Invoke strict mode
	"use strict";
	
	Supra.io.css = function (url, cfg) {
		//Only webkit and gecko has this issue
		if (!Y.UA.webkit && !Y.UA.gecko) return Y.Get.css(url, cfg);
		
		var callback = cfg.onSuccess,
			context = cfg.context || window,
			styleSheets = document.styleSheets,
			args = null,
			itterations = 200;
		
		var check = function () {
			if (!args) args = arguments;
			
			for(var i=0,ii=styleSheets.length; i<ii; i++) {
				if (styleSheets[i].href && styleSheets[i].href.indexOf(url) != -1) {
					if (Y.Lang.isFunction(callback)) {
						callback.apply(context, args);
					}
					return;
				}
			}
			
			itterations--;
			if (itterations == 0) {
				if (Y.Lang.isFunction(cfg.onTimeout)) {
					cfg.onTimeout.call(context);
				}
			} else {
				setTimeout(check, 16);
			}
		};
		
		cfg = Supra.mix(cfg || {}, {
			'onSuccess': check
		});
		
		return Y.Get.css(url, cfg);
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["supra.io"]});