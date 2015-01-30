/*
 * In DOM finds elements, which has some functionality attached
 * loads dependencies and initializes module (function) or jQuery plugin on them
 * 
 * If module/plugin is already initialized on the element, it will be ignored for that element
 * on repeated $.app.parse calls.
 * 
 * To remove/destroy modules/plugins for element and all descendants use $.app.cleanup(element)
 * If module has .destroy function, then it will be called.
 * 
 * @example
 *     <input type="checkbox" data-require="components/form/checkbox" data-attach="$.fn.checkbox" />
 *     <input type="checkbox" data-require="modules/something" data-attach="myFunctionName" />
 * 
 * @version 1.0.4
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
	} else if (typeof module !== "undefined" && module.exports) {
		// CommonJS
		module.exports = factory(jQuery);
	} else { 
        // AMD is not supported, assume all required scripts are already loaded
        factory(jQuery);
    }
}(this, function ($) {
    'use strict';
	
	$.app = {
		
		/**
		 * App options
		 * @type {Object}
		 * @private
		 */
		'options': {
            'id': 'id',
			'namespace': 'attach',
			'require': 'require'
		},
		
		/**
		 * List of instances
		 * @type {Object}
		 * @private
		 */
		'instances': {},
		
		/**
		 * List of pending injections
		 * @type {Object}
		 * @private
		 */
		'injections': {},
		
		/**
		 * Find and instantiate all modules inside element
		 * 
		 * @param {Object} element jQuery element, which should be parsed. All children are also parsed
		 * @return Array of instances, which don't require requirejs
		 * @type {Array}
		 */
		'parse': function (element, options) {
			var instances	= [],
				instance	= null,
				elements	= null,
				i			= 0,
				ii			= 0,
				require_ns  = '',
				require_fn  = null,
				modules     = [];
			
			$.extend(this.options, options || {});
			
			if (element.data(this.options.namespace)) {
				instance = this.factory(element);
				if (instance) instances.push(instance);
			}
			
			//Require all modules
			if (typeof define == 'function' && define.amd) {
				element.find('[data-' + this.options.require + ']').each($.proxy(function (index, element) {
					var mod = $(element).data(this.options.require).split(',');
					modules = modules.concat(mod);
				}, this));
				
				modules = $.unique(modules);
				
				if (modules.length) {
					require(modules, function () {});
				}
			}
			
			//Function for requirejs amd module loading
			require_fn = $.proxy(function (modules, element) {
				var self = this,
					pending;
				
				if (typeof define != 'function' || !define.amd) {
					throw new Error('app.parse needs requirejs for module loading, but library is not included in the page');
				}
				
				pending = $.map(modules.split(','), function (module) {
					if (module && !require.defined(module)) {
						return module;
					}
				});
				
				if (pending.length) {
					require(pending, function () {
						self.factory(element);
					});
				} else {
					this.factory(element);
				}
			}, this);
			
			//Children
			elements = element.find('[data-' + this.options.namespace + ']');
			ii = elements.length;
			
			for(i=0; i < ii; i++) {
				require_ns = elements.eq(i).data(this.options.require);
				if (require_ns) {
					// If 'data-require' attribute is set then load module before instantiating it
					require_fn(require_ns, elements.eq(i));
				} else {
					instance = this.factory(elements.eq(i));
					if (instance) instances.push(instance);
				}
			}
			
			//Trigger $.refresh plugin
			if ($.refresh && typeof $.refresh.trigger === 'function') {
				$.refresh.trigger('refresh', element);
			}
			
			return instances;
		},
		
		/**
		 * Destroy element and children element modules
		 * 
		 * @param {Object} element jQuery element, which children modules will be cleaned up
		 */
		'cleanup': function (element) {
			element.find('[data-' + this.options.id + ']').each(function () {
				$.app.destroy($(this));
			});
			
			//Trigger $.refresh plugin
			if ($.refresh && typeof $.refresh.trigger === 'function') {
				$.refresh.trigger('cleanup', element);
			}
		},
		
		/**
		 * Remove instance, clean up
		 * 
		 * @param {String} id Instance ID or element
		 */
		'destroy': function (id) {
            if (id instanceof $) {
				id = id.attr('data-' + this.options.id);
			}
			
			var instance	= this.instances[id],
				element		= null;
			
			if (instance) {
				if (typeof instance.destroy === 'function') {
					instance.destroy();
				}
				if (instance.element) {
					instance.element.removeData('instance');
					
					//Destroy children module instances
					instance.element.find('[data-' + this.options.id + ']').each(function () {
						$.app.destroy($(this));
					});
				}
				
				delete(this.instances[id]);
			}
		},
		
		/**
		 * Instantiate module on element
		 * 
		 * @param {Object} element jQuery element, which should be parsed
		 * @param {Object} options Optional options with which module will be instantiated
		 * @return Module instance
		 * @type {Object}
		 * @private
		 */
		'factory': function (element, _options) {
			var options	= options || {},
				attach	= options[this.options.namespace] || element.data(this.options.namespace) || '',
				id		= options.id || element.data('id') || element.attr('id') || ('app_' + (+new Date()) + String(~~(Math.random() * 9999))),
				module	= window,
				plugin	= false;
			
			options.id = id;
			options[this.options.namespace] = attach;
			
			if (element.data('instance')) {
				//Element has already module attached to it
				return null;
			}
			
			if (typeof attach === 'function') {
				module = attach;
			} else if (typeof attach === 'string' && attach) {
				if (attach.indexOf('$.fn.') === 0 || attach.indexOf('jQuery.fn.') === 0) {
					//jQuery plugin
					module = attach.replace(/^[^\.]\.fn\./i, '');
					plugin = true;
				} else {
					//Find module function
					$.each(attach.split('.'), function (index, part) {
						module = module[part];
						if (!module) return false;
					});
				}
			} else {
				return null;
			}
			
			//Instantiate
			if (typeof module === 'function' || (plugin && element[module])) {
				//Add data attribute values to the options
				options = $.extend({}, element.data(), options || {});
				
				if (plugin) {
					if (typeof element[module] === 'function') {
						module = element[module](options);
					}
				} else {		
					module = module(element, options);
				}
				
				//jQuery plugins will return same element
				if (typeof module === 'object' && module === element) {
					//Mark as visited
					element.data('instance', 'plugin');
				} else if (typeof module === 'object' && module !== element) {
					//Add data-id attribute to be able to clean up later
					element.attr('data-' + this.options.id, id);
					element.data('instance', module);
					
					this.instances[id] = module;
					return module;
				}
			}
			
			return null;
		},
		
		/**
		 * Inject additional properties and methods into module instance
		 * 
		 * @param {String} id Instance ID or element
		 * @param {Object} proto Properties and methods to inject
		 * @param {Function} callback Optional callback which will be called after injection 
		 */
		'inject': function (id, proto, callback) {
			var instance = this.get(id);
			if (instance) {
				instance.inject(proto, callback);
			}
			
			this.injections[id] = this.injections[id] || [];
			this.injections[id].push([proto, callback]);
		},
		
		/**
		 * Find single instance using ID
		 * 
		 * @param {String} id Instance ID or element
		 * @return Instance with given ID
		 * @type {Object}
		 */
		'get': function (id) {
			if (typeof id === 'object') {
				var instance = id.data('instance');
                
				if (instance) {
					return instance;
				} else {
                    id = id.attr('data-' + this.options.id) || id.attr('id');
				}
			}
			
			return this.instances[id] || null;
		},
		
		/**
		 * Trigger method on instance
		 * 
		 * @param {String} id Instance ID or element
		 * @param {String} method Optional method name or function
		 * @param {Array} args Optional arguments for method
		 * @return Method return value
		 */
		'trigger': function (id, method, args) {
			var instance	= this.get(id),
				type		= $.type(method);
			
			if (!instance) {
				return;
			}
			
			if (type === 'array' && !args) {
				args = method;
				method = null;
			}
			
			if (type === 'string') {
				method = instance[method];
			} else if (type === 'function') {
				//Already a function, don't do anything
			} else {
				method = instance[instance.default_method];
			}
			
			if (typeof method === 'function') {
				args = $.isArray(args) ? args : [];
				return method.apply(instance, args);
			}
		}
		
	};
	
	return $.app;

}));
