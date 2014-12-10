YUI.add('supra.dom-style-reset', function(Y) {
	//Invoke strict modet
	"use strict";
	
	function callBefore (o, fn, call) {
		var original = o[fn];
		o[fn] = function () { call.apply(this, arguments); return original.apply(this, arguments); };
	};
	
	// On transitions cssText is used, so we need to reset cache manually
	callBefore(Y.Transition.prototype, '_runNative', function () {
		Y.DOM.resetStyleCache(this._node);
	});
	
	callBefore(Y.TransitionNative.prototype, '_runNative', function () {
		Y.DOM.resetStyleCache(this._node);
	});
	
}, YUI.version ,{requires:['transition']});
