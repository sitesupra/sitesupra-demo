/**
 * jQuery plugin to add "placeholder" attribute support to older
 * browsers
 * 
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var NAMESPACE = 'placeholder';
	
	function PlaceHolder(el, options) {
		this.element = $(el);
		this.options = $.extend({
			'value': $(this).attr('placeholder')
		}, PlaceHolder.defaults, options || {});
		
		this._bindUI();
		this._onBlur();
		
		//Safari
		this.element.removeAttr('placeholder');
	}
	
	PlaceHolder.defaults = {
		'className': 'empty'
	};
	
	PlaceHolder.prototype = {
		
		/**
		 * Input element
		 * @type {jQuery}
		 * @private
		 */
		'element': null,
		
		/**
		 * Options
		 * @type {Object}
		 * @private
		 */
		'options': null,
		
		/**
		 * Handle focus event
		 * 
		 * @private
		 */
		'_onFocus': function () {
			if ($.trim(this.element.val()) == this.options.value) {
				this.element.val('');
				this.element.removeClass(this.options.className)
			}
		},
		
		/**
		 * Handle blur event
		 * 
		 * @private
		 */
		'_onBlur': function () {
			var val = $.trim(this.element.val())
			if (val == this.options.value || !val) {
				this.element.val(this.options.value);
				this.element.addClass(this.options.className)
			}
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		'_bindUI': function () {
			var fnFocus = $.proxy(this._onFocus, this),
				fnBlur = $.proxy(this._onBlur, this);
			
			this.element.focus(fnFocus);
			this.element.blur(fnBlur);
			
			//Attach to form events to make sure correct values are sent
			var form = this.element.closest('form');
			form.bind('submit beforesubmit', fnFocus);
			form.bind('aftersubmit invalid reset', fnBlur);
		}
	};
	
	/**
	 * Check if it's supported natively
	 */
	$.support.placeholder = !!('placeholder' in $('<input />').get(0));
	
	/**
	 * jQuery plugin
	 */
	$.fn.placeholder = function (command) {
		if ($.support.placeholder && !$.browser.safari) {
			return this;
		}
		
		this.each(function () {
			
			var object = $(this).data(NAMESPACE);
			if (!object) {
				object = new PlaceHolder($(this), typeof command === "object" ? command : {});
				$(this).data(NAMESPACE, object);
			}
			
			if (command && typeof object[command] === "function" && command[0] !== "_") {
				var args = Array.prototype.slice.call(arguments, 1);
				return object[command].apply(object, args) || this;
			}
			
		});
		
		return this;
	};
	
	/*
	 * Call plugin on all inputs for browsers which don't support it natively
	 * 
	 * @bug Safari doesn't style placeholder text and it is aligned to the top of the input, that's why using this plugin 
	 */
	
	if (!$.support.placeholder || $.browser.safari) {
		$(window).load(function () {
			$('input[placeholder],textarea[placeholder]').placeholder();
		});
	}
	
	return PlaceHolder;
	
}));
