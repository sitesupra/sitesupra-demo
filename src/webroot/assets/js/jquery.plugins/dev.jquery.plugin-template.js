/**
 * Template for developing jQuery plugin
 * It's important to change NAMESPACE value and MyClassName to appropriate names
 * 
 * @version 1.0
 */
(function ($) {
	
	// Data namespace and accessor namespace 
	var NAMESPACE = "pluginNamespace";
	
	function MyClassName (el, options) {
		this.element = $(el);
		this.options = $.extend({}, MyClassName.defaults, options || {});
		
		// Initialize
		// ...
	}
	
	/**
	 * Default options for plugin
	 * @type {Object}
	 */
	MyClassName.defaults = {
		
	};
	
	MyClassName.prototype = {
		
		/**
		 * Element, jQuery instance
		 * @type {Object}
		 * @private
		 */
		element: null,
		
		/**
		 * Plugin options
		 * @type {Object}
		 * @private
		 */
		options: null,
		
		
		/**
		 * Private property, all properties are private
		 * @type {Number}
		 * @private
		 */
		privateProperty: 0,
		
		
		/**
		 * Sample private method, should start with underscore
		 */
		_privateMethod: function () {
			// 
		},
		
		/**
		 * Public methods should start without underscore, should implement
		 * as getter and setter if possible for consistency
		 * 
		 * It will be callable by $('...').pluginNamespace('publicMethod', 123)
		 * 
		 * @param {Number} a Argument a
		 */
		publicMethod: function (a) {
			if (typeof a === 'number') {
				this.privateProperty = a;
			}
			
			return this.privateProperty;
		},
		
		// ... other methods ...
	
	};
	
	/**
	 * jQuery plugin
	 * 
	 * @param {Object} command List of options or public method name
	 */
	$.fn[NAMESPACE] = function (command) {
		
		this.each(function () {
			
			var object = $(this).data(NAMESPACE);
			if (!object) {
				object = new MyClassName($(this), typeof command === "object" ? command : {});
				$(this).data(NAMESPACE, object);
			}
			
			if (command && typeof object[command] === "function") {
				var args = Array.prototype.slice.call(arguments, 1);
				return object[command].apply(object, args) || this;
			}
			
		});
		
		return this;
	};
	
	
	// Set on jQuery global object
	$.MyClassName = MyClassName;

})(jQuery);
