YUI.add('supra.template', function (Y) {
	//Invoke strict mode
	"use strict";
	
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
		if (!id && id !== '') return null;
		
		var source = null,
			template = id ? cache[id] : null;
		
		if (template) {
			return data ? template(data) : template;
		}

		if (id.isInstanceOf && id.isInstanceOf('Node')) {
			node = id;
			id = node.getAttribute('id');
		} else if (typeof id == 'string') {
			var node = Y.one('#' + id);
			if (!node) {
				node = Y.one(id);
				if (node) {
					id = node.getAttribute('id');
				}
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
	};
	
	/**
	 * Extract {% template %} tags from HTML and cache them
	 * 
	 * @param {String} html Source HTML
	 * @returns {String} HTML without templates in them 
	 */
	Template.extractTemplates = function (html) {
		// Check if in html is '{%' followed by 'template'
		// for quick validation 
		var check_index = html.indexOf('{%'),
			check_name  = check_index != -1 ? html.indexOf('template ', check_index) : -1;
		
		if (check_name != -1) {
			var regex_start = /{%\s*template\s+([a-zA-Z0-9_\-]+)\s*%}([\s\S]*?){%\s*endtemplate\s*%}/g,
				regex_end   = /{%\s*endtemplate\s*%}/;
			
			html = html.replace(regex_start, function (match, id, template) {
				Template.compile(template, id);
				return '';
			});
		}
		
		return html;
	};
	
	/**
	 * Add custom filter
	 * 
	 * @param {String} name Filter name
	 * @param {Function} fn Filter function
	 */
	Template.addFilter = function (name, fn) {
		return Supra.TemplateCompiler.addFilter.apply(Supra.TemplateCompiler, arguments);
	};
	
	
	Supra.Template = Template;
	
	
	//Since this object has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: [
	'supra.template-compiler'
]});