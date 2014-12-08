/**
 * Input mask
 * Allow to enter only specific symbols or content matching a pattern
 * 
 * @version 1.0.5
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
}(this, function () {
	"use strict";
	


    /*
     * Check for support for 'input' event
     * Deferred till actually needed
     */
    function hasInputSupport () {
        if ('input' in $.support) return $.support.input;
        return ($.support.input = ('oninput' in document.createElement('input')));
    }

    /**
     * Escape special characters to use string in regular expression
     * 
     * @param {String} str
     * @returns {String} Escaped string
     */
    function converToRegExp (str) {
        if (str instanceof RegExp || typeof str === 'function') {
            // Already a regular expression or custom function
            return str;
        } else if (str[0] === '/' && str.substr(-1, 1) === '/') {
            // Regular expression as string "/^[0-9]$/"
            // If regular expression is not valid, then allow error, it's not
            // our responsibility
            str = str.substr(1, str.length - 2);
            return new RegExp(str, 'i');
        } else if (str) {
            // List of allowed characters "1234567890"
            str = ('' + str).replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
            return new RegExp('^[' + str + ']*$');
        } else {
            // Mask is disabled
            return null;
        }
    }

    /**
     * Returns input selection as array with start and end offsets
     *
     * @param {HTMLElement} input Input element
     * @returns {Array} Start and end offsets for selection
     */
    function getInputSelection (input) {
        if (input.selectionStart) {
            return [input.selectionStart, input.selectionEnd];
        } else if (document.selection) {
            var range = document.selection.createRange(),
                r1, r2;
            
            if (range === null) {
                return [0, input.value.length];
            }
            
            r1 = input.createTextRange();
            r2 = r1.duplicate();
            
    		r1.moveToBookmark(range.getBookmark());
    		r2.setEndPoint('EndToStart', r1);

    		return [r1.text.length, r2.text.length + range.text.length];
        }
    }

    /**
     * Set input selection
     * 
     * @param {HTMLElement} input Input element
     * @param {Array} Start and end offsets for selection
     */
    function setInputSelection (input, selection) {
        if (input.setSelectionRange) {
            input.setSelectionRange(selection[0], selection[1]);
        } else if (input.selectionStart) {
            input.selectionStart = selection[0];
            input.selectionEnd = selection[1];
        } else if (node.createTextRange) {
            range = input.createTextRange();
            range.moveStart('character', selection[0]);
            range.moveEnd('character', selection[1]);
            range.select();
        }
    }

    // Namespace for events and jQuery plugin
    var NAMESPACE = 'maskPlugin';

    // Unique id counter, needed to have unique namespace for each
    // instance
    var UID = 0;

    function Mask(el, options) {
    	this.element = $(el);
    	this.namespace = NAMESPACE + (++UID);
    	
    	// Normalize arugments
    	var type = typeof options;
    	if (type === 'string' || type === 'function' || options instanceof RegExp) {
    		options = {'mask': options};
    	}
    	
    	// Normalize options
    	this.options = $.extend({}, Mask.defaults, {
    		// read data-mask="..." attribute
    		'mask': this.element.data('mask')
    	}, options);
    	
    	// Convert mask into regular expression
    	this.options.mask = converToRegExp(this.options.mask);
    	
    	this.last_value = this.element.val();
    	this._bindUI();
    }

    Mask.defaults = {
    	'mask': null
    };

    Mask.prototype = {
    	
    	/**
    	 * Namespace for events
    	 * @type {String}
    	 * @private
    	 */
    	namespace: null,
    	
    	/**
    	* Input element
    	* @type {jQuery}
    	* @private
    	*/
    	element: null,
    	
    	/**
    	* Options
    	* @type {Object}
    	* @private
    	*/
    	options: null,
    	
    	/**
    	 * Last known value
    	 * @type {String}
    	 * @private
    	 */
    	last_value: null,
    	
    	/**
    	 * Last known selection
    	 * @type {String}
    	 * @private
    	 */
    	last_selection: null,
    	
    	/**
    	 * Add event listeners
    	 * 
    	 * @private
    	 */
    	_bindUI: function () {
    		if (hasInputSupport()) {
    			// Latest browser
    			this.element.on('keydown.' + this.namespace, $.proxy(this._handleBeforeInputEvent, this));
    			this.element.on('input.' + this.namespace, $.proxy(this._handleInputEvent, this));
    		} else {
    			// Legacy browser
    			this.element.on('keypress.' + this.namespace, $.proxy(this._handleKeyPressEvent, this));
    			this.element.on('paste.' + this.namespace, $.proxy(this._handlePasteEvent, this));
    		}
    		
    		this.element.on('focus.' + this.namespace, $.proxy(this._handleFocus, this));
    	},
    	
    	/**
    	 * Save current selection before user has entered anything
    	 * Needed to restore previous selection if invalid character is entered
    	 * 
    	 * @param {Object} event Event facade object
    	 * @private
    	 */
    	_handleBeforeInputEvent: function (event) {
    		this.last_selection = getInputSelection(this.element.get(0));
    	},
    	
    	/**
    	 * Input event is runned before paint, but value is correct.
    	 * Revert value if it doesn't match mask
    	 * 
    	 * @param {Object} event Event facade object
    	 * @private
    	 */
    	_handleInputEvent: function (event) {
    		var input = this.element,
    			value = input.val(),
    			selection = this.last_selection;
    		
    		if (!this._test(value)) {
    			// User entered a character which doesn't match mask, revert
    			input.val(this.last_value);
    			setInputSelection(input.get(0), selection);
    		} else {
    			this.last_value = value;
    		}
    	},
    	
    	/**
    	 * Legacy browser. Character is not added to the input value yet,
    	 * 
    	 * @param {Object} event Event facade object
    	 * @private
    	 */
    	_handleKeyPressEvent: function (event) {
    		var key = (typeof event.charCode != 'undefined' ? event.charCode : event.keyCode),
    			chr,
    			value,
    			selection,
    			input;
    		
    		if (key && !event.ctrlKey && !event.altKey) {
    			input = this.element;
    			selection = getInputSelection(input.get(0));
    			chr = String.fromCharCode(key);
    			
    			value = this.element.val();
    			value = value.substr(0, selection[0]) + chr + value.substr(selection[1]);
    			
    			if (!this._test(value)) {
    				// User trying to enter a character which doesn't match mask,
    				// prevent it
    				event.preventDefault();
    			} else {
    				// Save new value
    				this.last_value = value;
    			}
    		}
    	},
    	
    	/**
    	 * Legacy browser. Handle pasting if new value will not match mask
    	 * 
    	 * @param {Object} event Event facade object
    	 * @private
    	 */
    	_handlePasteEvent: function (event) {
    		event.preventDefault();
    	},
    	
    	/**
    	 * Save previous value to revert if user input didn't passed mask filter
    	 * 
    	 * @param {Object} event Event facade object
    	 * @private
    	 */
    	_handleFocus: function (event) {
    		this.last_value = this.element.val();
    	},
    	
    	/**
    	 * Test string against a mask
    	 *
    	 * @param {String} str String
    	 * @returns {Boolean} True if string matches mask, otherwise false
    	 * @private
    	 */
    	_test: function (_str) {
    		var mask = this.options.mask,
    			str  = '' + _str;
    		
    		if (mask) {
    			if (typeof mask === 'function') {
    				return !!mask.call(this.element, str);
    			} else {
    				// Reset regex in case if 'g' flag is used
    				mask.lastIndex = 0;
    				
    				return mask.test(str);
    			}
    		}
    		
    		return true;
    	},


    	/* ------------------------------ API ------------------------------- */
    	
    	
    	/**
    	 * Set mask
    	 * 
    	 * @example input.mask("mask", /^[0-9]+$/);
    	 * @param {String|Function|RegExp} mask Mask
    	 */
    	mask: function (mask) {
    		this.options.mask = converToRegExp(mask);
    	},
    	
    	/**
    	 * Destructor
    	 */
    	destroy: function () {
    		// Teardown
    		this.element.off('.' + this.namespace);
    		this.element = this.last_value = this.options = null;
    	}
    	
    };

    $.fn.mask = function (options) {
    	var inputs = this.find('input, textarea').add(this.filter('input, textarea')),
    		fn,
    		args;

    	if (typeof options === 'string' && typeof Mask.prototype[options] === 'function' && options[0] !== '_') {
    		fn = options;
    		args = [].splice.call(arguments, 1);
    		options = null;
    	}
    		
    	inputs.each(function () {
    		var node = $(this),
    			instance = node.data(NAMESPACE);
    		
    		if (!instance) {
    			instance = new Mask(node, options);
    			node.data(NAMESPACE, instance);
    		}
    		
    		if (fn) {
    			instance[fn].apply(instance, args);
    		}
    	});
    	
    	return this;
    };


	
	
	return Mask;
	
}));
