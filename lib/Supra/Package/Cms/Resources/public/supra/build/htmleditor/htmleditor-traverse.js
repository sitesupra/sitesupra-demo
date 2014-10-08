YUI().add('supra.htmleditor-traverse', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Retrieves a nodeList based on the given CSS selector.
		 * 
		 * @param {String} selector The CSS selector to test against.
		 * @return A NodeList instance for the matching HTMLCollection/Array.
		 * @type {Object}
		 */
		all: function (selector) {
			return this.get('srcNode').all(selector);
		},
		
		/**
		 * Retrieves a Node instance of nodes based on the given CSS selector. 
		 * 
		 * @param {String} selector The CSS selector to test against.
		 * @return A Node instance for the matching HTMLElement.
		 * @type {Object}
		 */
		one: function (selector) {
			return this.get('srcNode').one(selector);
		}
		
	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});