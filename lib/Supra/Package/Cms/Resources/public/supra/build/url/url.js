/**
 * Url generator
 */
YUI.add('supra.url', function(Y){
	"use strict";

	var Url = Supra.Url = {
		routes: {},

		generate: function (route, params) {
			if (!this.routes[route]) {
				throw Error('Route "' + route + '" is not defined');
			}

			var routeParams = this.routes[route];

			var path = [];

			for (var i = 0; i < routeParams.tokens.length; i ++) {
				var token = routeParams.tokens[i];

				//token type?
				switch (token[0]) {
					case 'text':
						path.push(token[1]);
						break;
					default:
						throw Error('Sorry, only "text" tokens are supported now');
						break;
				}

				return path.join('/');
			}
		},

		setRoutes: function (routes) {
			this.routes = routes;
		}
	};

	delete(this.fn); this.fn = function () {};
}, YUI.version);
