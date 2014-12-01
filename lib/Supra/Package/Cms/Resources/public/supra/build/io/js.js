/**
 * Extension to load CSS files
 */
YUI().add("supra.io-js", function (Y) {
	//Invoke strict mode
	"use strict";
	
	Supra.io.js = function (url, cfg) {
		cfg = cfg || {};
		cfg.on = cfg.on || {};
		cfg.deferred = cfg.deferred || new Supra.Deferred();
		
		var success_handler = function (data) {
				// Deferred
				cfg.deferred.resolveWith(cfg.context, [data, true]);
				
				// Backward compatibility
				if (Y.Lang.isFunction(cfg.on.complete)) {
					cfg.on.complete.apply(cfg.context, [data, true]);
				}
				if (Y.Lang.isFunction(cfg.on.success)) {
					cfg.on.success.apply(cfg.context, [data, true]);
				}
			},
			
			failure_handler = function (data) {
				// Deferred
				cfg.deferred.rejectWith(cfg.context, [data, false]);
				
				// Backward compatibility
				if (Y.Lang.isFunction(cfg.on.complete)) {
					cfg.on.complete.apply(cfg.context, [data, false]);
				}
				if (Y.Lang.isFunction(cfg.on.failure)) {
					cfg.on.failure.apply(cfg.context, [data, false]);
				}
			};
		
		var io = Y.Get.js(url, cfg, function (err, transaaction) {
			if (err && err.length) {
				failure_handler({'url': url, 'errors': err});
			} else {
				success_handler({'url': url, 'errors': null});
			}
		});
		
		// Add promise functionality to the transaction
		cfg.deferred.promise(io);
		
		return io;
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["supra.io"]});