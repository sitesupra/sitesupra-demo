/**
 * Input placeholder shim
 * Adds support for input placeholders for older browsers
 * 
 * @version 1.0.4
 * @license Copyright 2014 Vide Infra
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], function ($) {
            return factory($);
        });
    } else if (typeof module !== "undefined" && module.exports) {
        // CommonJS
        module.exports = factory(jQuery);
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
    "use strict";
    
    var NAMESPACE = 'placeholder';

    /**
     * Check for native support
     * Old versions of Safari doesn't style placeholder text and it is aligned to
     * the top of the input, use custom implementation for it too.
     */
    if (!('placeholder' in $.support)) {
    	$.support.placeholder = (function () {
    		if (!('placeholder' in $('<input />').get(0))) return false;
    		
    		var safari = navigator.appVersion.match(/Safari\/([\d]+)/);
    		if (safari && parseInt(safari[1], 10) <= 534) {
    			$.support.placeholder = false;
    		}
    	})();
    }

    function PlaceHolder(el, options) {
    	this.element = $(el);
    	
    	if (typeof options === 'string') {
    		options = {'value': options};
    	}
    	this.options = $.extend({
    		'value': this.element.attr('placeholder')
    	}, PlaceHolder.defaults, options || {});
    	
    	this._bindUI();
    	this._onBlur();
    	
    	//Safari
    	this.element.removeAttr('placeholder');
    }

    PlaceHolder.defaults = {
    	'className': 'input-placeholder-shim'
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
    			this.element.removeClass(this.options.className);
    		}
    	},
    	
    	/**
    	 * Handle blur event
    	 * 
    	 * @private
    	 */
    	'_onBlur': function () {
    		var val = $.trim(this.element.val());
    		if (val == this.options.value || !val) {
    			this.element.val(this.options.value);
    			this.element.addClass(this.options.className);
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
     * jQuery plugin
     */
    $.fn.placeholder = function (options) {
    	if ($.support.placeholder) {
    		return this;
    	}
    	
    	this.each(function () {
    		var object = $(this).data(NAMESPACE);
    		if (!object) {
    			object = new PlaceHolder($(this), options && typeof options === "object" ? options : {});
    			$(this).data(NAMESPACE, object);
    		}
    	});
    	
    	return this;
    };


    /*
     * Call plugin on all inputs for browsers which don't support it natively
     */
    if (!$.support.placeholder) {
    	$(function () {
    		$('input[placeholder],textarea[placeholder]').placeholder();
    	});
    }
	
	return PlaceHolder;
	
}));
