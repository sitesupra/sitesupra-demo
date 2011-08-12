//Invoke strict mode
"use strict";

YUI.add('supra.template', function (Y) {
	
	/**
	 * Returns template from ID or CSS selector
	 * If data argument is object then executes template and returns HTML
	 * 
	 * @param {String} id Template ID or css selector
	 * @param {Object} data 
	 * @return Template function or null if source was not found
	 * @type {Function}
	 */
	function Template (id, data) {
		var source = null,
			template = id ? cache[id] : null;
		
		if (template) {
			return data ? template(data) : template;
		}
		
		var node = Y.one('#' + id);
		if (!node) {
			node = Y.one(id);
			if (node) {
				id = node.getAttribute('id');
			}
		}
		
		if (node) {
			var source = node.get('innerHTML');
			var template = Supra.TemplateCompiler.compile(source);
			
			cache[id] = template;
			return data ? template(data) : template;
		}
		
		return null;
	}
	
	/**
	 * Template cache
	 * @type {Object}
	 */
	var cache = Template.cache = {};
	
	/**
	 * Compile HTML into a template
	 * 
	 * @param {String} html Source HTML
	 * @param {String} id Optional template ID
	 * @return Template function
	 * @type {Function}
	 */
	Template.compile = function (html /* Source HTML */, id /* Template ID */) {
		if (id && cache[id]) return cache[id];
		
		var template = Supra.TemplateCompiler.compile(html, {
			'stripCDATA': true,
			'validate': false	//For performance reason disabled
		});
		
		if (id) cache[id] = template;
		
		return template;
	};
	
	/**
	 * Remove template from cache
	 * 
	 * @param {String} id Template ID
	 */
	Template.purgeCache = function (id) {
		if (id && cache[id]) delete(cache[id]);
	}
	
	
	Supra.Template = Template;
	
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: [
	'supra.template-compiler'
]});