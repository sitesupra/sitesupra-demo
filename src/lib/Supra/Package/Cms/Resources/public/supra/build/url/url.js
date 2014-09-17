/**
 * Url generator
 */
YUI.add('supra.url', function(Y){
	"use strict";

	var Url = Supra.Url = {
		generate: function (route, params) {
			console.log('Generating route ' + route);
		}
	};

	delete(this.fn); this.fn = function () {};
}, YUI.version);
