//Invoke strict mode
"use strict";

YUI().add('blog.input-string-clear', function (Y) {
	
	/**
	 * Plugin for String input to clear content on icon click
	 */
	function InputStringClear () {
		InputStringClear.superclass.constructor.apply(this, arguments);
	};
	
	InputStringClear.NAME = 'InputStringClear';
	InputStringClear.NS = 'clear';
	
	Y.extend(InputStringClear, Y.Plugin.Base, {
		
		/**
		 * Clear icon/button
		 * 
		 * @type {Object}
		 * @private 
		 */
		nodeClear: null,
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this.nodeClear = Y.Node.create('<a class="clear"></a>');
			this.nodeClear.on('click', this.clearInputValue, this);
			this.get('host').get('inputNode').insert(this.nodeClear, 'after');
		},
		
		/**
		 * Clear input value
		 * 
		 * @private
		 */
		'clearInputValue': function () {
			this.get('host').set('value', '');
		}
	});
	
	Supra.Input.String.Clear = InputStringClear;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.form', 'plugin']});