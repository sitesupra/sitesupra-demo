/**
 * jQuery $.app Ajax Form
 * Ajax form validator / submitter for $.app
 * 
 * @version 1.0.8
 * @docs http://sitesupra.vig/supra7/js/jquery.app-ajaxform
 * @license Copyright 2014 Vide Infra
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/ajaxcontent'], function ($) {
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
	
    var _super = $.app.AjaxContent.prototype,
    	
    	VALIDATION_SOURCE_EVENT = 'event',
    	VALIDATION_SOURCE_VALIDATE = 'validate';

    $.app.AjaxForm = $.app.module($.app.AjaxContent, {
    	
    	/**
    	 * Default method for 'trigger'
    	 * @type {String}
    	 */
    	'default_method': 'submit',
    	
    	/**
    	 * Form validation rules and messages
    	 * @type {Object}
    	 */
    	'validation': {
    		'errorElement': 'label.error',
    		'errorClassName': 'error',
    		'inputErrorClassName': 'input-error',
    		'inputContainer': '',
    		
    		'errors': {},
    		'rules': {},
    		'messages': {},
    		
    		'customValidation': {}
    	},
    	
    	/**
    	 * Use ajax to submit form
    	 * @type {Boolean}
    	 */
    	'useAjax': true,

    	/**
    	 * Submit event listener
    	 * @type {Function}
    	 * @private
    	 */
    	'submitEvent': null,
    	
    	/**
    	 * Initialize module
    	 * 
    	 * @param {Object} element
    	 * @param {Object} options
    	 * @constructor
    	 */
    	'init': function (element, options) {
    		_super.init.apply(this, arguments);
    		
    		this.validation = $.extend(true, {}, this.validation);
    		
    		this.url    = this.options.url    || element.attr('action');
    		this.method = this.options.method || this.getForm().attr('method');
    		this.useAjax = ('useAjax' in this.options ? this.options.useAjax : this.useAjax);
    		this.submitEvent = this.proxy(this.submit);
    		this.disabled = false;
    		
    		this.onChange();
    		this.element.delegate('input, select, textarea', 'change blur', $.app.AjaxForm.delay.register(this.validateEventTarget, this));
    		this.element.delegate('input[type="checkbox"], input[type="radio"]', 'click', $.app.AjaxForm.delay.register(this.validateEventTarget, this));
    	},
    	
    	/**
    	 * On reload bind form submit action
    	 *
    	 * @private
    	 */
    	'onChange': function () {
    		this.getForm()
    			.unbind('submit', this.submitEvent)
    			.on('submit', this.submitEvent);
    	},

    	/**
    	 * Submit form
    	 */
    	'submit': function () {
    		if (this.disabled) {
    			return false;
    		}

    		var values = this.serialize(),
    			post_data;
    		
    		if (this.validate(values)) {
    			// Send objects (how it's handled depends on transport)
    			// instead of "a[b][c]" = "d"
    			if (this.useAjax) {
    				post_data = this.serialize(true);
    				
    				this.disable(true);
    				this.reload(post_data);
    				
    				return false;
    			}
    		} else {
    			return false;
    		}
    	},
    	
    	/**
    	 * On reload bind form submit action
    	 *
    	 * @private
    	 */
    	'onReload': function (response) {
    		_super.onReload.apply(this, arguments);
    		
    		// Handle JSON response
    		if (this.reload_response_type === 'json') {
    			if (response && response.errors) {
    				// Display errors
    				var form = this.getForm(),
    					input,
    					name,
    					first = true;
    				
    				for (name in response.errors) {
    					input = this.input(name);
    					this.handleValidateError(response.errors[name], input, name, input.val());
    					
    					// Focus first element which has error
    					if (first) {
    						input.focus();
    						first = false;
    					}
    				}
    			}
    		}
    		
    		this.disable(false);
    		this.onChange();
    	},
    	
    	/* ------------------------ Validation ---------------------- */
    	
    	/**
    	 * Add custom validation
    	 * 
    	 * @param {String} name Rule name
    	 * @param {Function} handler
    	 */
    	'addValidation': function (name, handler) {
    		if (typeof handler === 'function') {
    			this.validation.customValidation[name] = handler;
    		}
    	},
    	
    	/**
    	 * Validate form values
    	 * 
    	 * @param {Object} _values Form values
    	 * @return True on success, false on failure
    	 */
    	'validate': function (_values) {
    		var validation	= this.getValidationInfo(),
    			values		= _values || this.serialize(),
    			rules		= null,
    			rule		= null,
    			messages	= validation.messages,
    			input		= null,
    			name		= null,
    			value		= null,
    			error		= false,
    			any_errors	= false,
    			
    			fn;
    		
    		fn = $.proxy(function (index, element) {
    			var input = $(element),
    				input_name;
    			
    			if (input && !input.is(':disabled')) {
    				
    				rules = validation.rules[name];
    				
    				if (!this.validateRules(rules, messages, values, input, name, VALIDATION_SOURCE_VALIDATE)) {
    					if (!any_errors) {
    						any_errors = true;
    						input.focus();
    					}
    				} else {
    					//All rules for this input were satisfied
    					input_name = input.attr('name');
    					this.handleValidateSuccess(input, input_name, value);
    				}
    			}
    		}, this);
    		
    		// We are using var _name here only because of jshint error,
    		// is there any nicer workaround?
    		for (var _name in validation.rules) {
    			name = _name;
    			input = this.input(_name);
    			input.each(fn);
    		}
    		
    		return !any_errors;
    	},
    	
    	/**
    	 * Validate rule
    	 * 
    	 * @param {Object} rules All validation rules
    	 * @param {Object} messages All error messages
    	 * @param {Object} values Form values
    	 * @param {Object} input Input element
    	 * @param {String} name Input name
    	 * @param {String} source Name of the action which triggered validation, either 'event' or 'validate'
    	 * @return True on success, false on failure
    	 * @private
    	 */
    	'validateRules': function (rules, messages, values, input, name, source) {
    		var rule		= null,
    			error		= false,
    			input_name	= input.attr('name') || name,
    			value		= $.trim(values[input_name]),
    			custom		= this.validation.customValidation,
    			target;
    		
    		for(rule in rules) {
    			if (rules[rule]) {
    				 
    				if (rule === 'required') {
    					if (!value) error = true;
    				} else if (rule === 'minlength') {
    					if (value.length < rules[rule]) error = true;
    				} else if (rule === 'maxlength') {
    					if (value.length > rules[rule]) error = true;
    				} else if (rule === 'email') {
    					if (!$.trim(value).match(/^[a-z0-9\.\-\_]+@[a-z0-9\.\-\_]+\.[a-z0-9]{2,5}$/i)) error = true;
    				} else if (rule === 'phone') {
    					if (!$.trim(value).match(/^[0-9\s\+\(\)]+$/)) error = true;
    				} else if (rule === 'equalto') {
    					// Find real target value
    					target = values[rules[rule]];
    					if (typeof target === 'undefined') {
    						target = this.input(rules[rule]).val();
    					}
    					if ($.trim(target) != value) {
    						if (source !== VALIDATION_SOURCE_EVENT) {
    							// Validate only if validation is final, not if user removed focus from input
    							// otherwise error is shown when user focused target element
    							error = true;
    						} else {
    							// If there was an error before then keep showing it
    							if (this.validation.errors[name]) {
    								error = true;
    							}
    						}
    					}
    				} else if (rule === 'pattern') {
    					if (!value.match(rules[rule])) error = true;
    				} else if (typeof rules[rule] === 'function') {
    					if (!rules[rule](value, input, input_name)) error = true;
    				} else if (rule in custom) {
    					if (!custom[rule].call(this, value, input, input_name, rules[rule])) error = true;
    				}
    				
    				if (error) {
    					this.handleValidateError(messages[name][rule], input, input_name, value);
    					return false;
    				}
    			}
    		}
    		
    		return true;
    	},
    	
    	/**
    	 * Validate event target value
    	 * 
    	 * @param {Object} e Event object
    	 * @private
    	 */
    	'validateEventTarget': function (e) {
    		var validation	= this.getValidationInfo(),
    			input		= $(e.target),
    			value		= input.val(),
    			name		= this.name(input),
    			input_name	= input.attr('name'),
    			rules		= null,
    			values		= {},
    			key;
    		
    		rules = validation.rules[name];
    		values[input_name] = value;
    		
    		if (rules && input && !input.is(':disabled'))	{
    			if (this.validateRules(rules, validation.messages, values, input, name, VALIDATION_SOURCE_EVENT)) {
    				//All rules for this input were satisfied
    				this.handleValidateSuccess(input, input_name, value);
    			}
    		}
    		
    		// Revalidate if this element is 'equalto' target
    		for (key in validation.rules) {
    			if ('equalto' in validation.rules[key] && validation.rules[key].equalto === name) {
    				this.validateEventTarget({
    					'target': this.input(key)
    				});
    			}
    		}
    	},
    	
    	/**
    	 * Returns validation information
    	 * 
    	 * @return Validation information
    	 * @type {Object}
    	 * @private
    	 */
    	'getValidationInfo': function () {
    		var inputs = this.element.find('input[data-validate], select[data-validate], textarea[data-validate], input[data-messages], select[data-messages], textarea[data-messages]'),
    			validation = {'rules': {}, 'messages': {}};
    		
    		//Extract validation information from 
    		inputs.each($.proxy(function (index, element) {
    			var input			= $(element),
    				attr_validate	= input.data('validate'),
    				attr_messages	= input.data('messages'),
    				name			= this.name(input);
    			
    			if (attr_validate) {
    				if (!validation.rules[name]) validation.rules[name] = {};
    				$.extend(validation.rules[name], attr_validate);
    			}
    			if (attr_messages) {
    				if (!validation.messages[name]) validation.messages[name] = {};
    				$.extend(validation.messages[name], attr_messages);
    			}
    		}, this));
    		
    		return $.extend(true, validation, this.validation);
    	},
    	
    	/**
    	 * Place error message
    	 * 
    	 * @param {Object} error Error element
    	 * @param {Object} element Input element
    	 * @param {String} name Input name
    	 * @param {String} value Input value
    	 * @private
    	 */
    	'validateErrorPlacement': function (error, element, name, value) {
    		var container = this.getErrorContainerElement(element);
    		container.append(error);
    	},
    	
    	/**
    	 * Render error message element
    	 * 
    	 * @param {String} message Message
    	 * @param {Object} element Input element
    	 * @param {String} name Input name
    	 * @param {String} value Input value
    	 * @private
    	 */
    	'validateErrorRender': function (message, _element, name, value) {
    		var element		= this.validation.errorElement.replace(/\..*/, '') || 'label',
    			classname	= this.validation.errorElement.match(/\.(.*)/);
    		
    		classname = classname ? classname[1] : this.validation.errorClassName;
    		
    		return $('<' + element + ' class="' + classname + '" data-input-name="' + name + '" />').text(message);
    	},
    	
    	/**
    	 * Find error element
    	 * 
    	 * @param {Object} element Input element
    	 * @param {String} name Input name
    	 * @returns {Object|Null} Error element
    	 * @private
    	 */
    	'validateErrorElementFind': function (element, name) {
    		var label = this.getErrorContainerElement(element).find(this.validation.errorElement + '[data-input-name="' + name + '"]');
    		return label.size() ? label.eq(0) : null;
    	},
    	
    	/**
    	 * Hide error message
    	 * 
    	 * @param {String} message Message
    	 * @param {Object} element Input element
    	 * @param {String} name Input name
    	 * @param {String} value Input value
    	 * @private
    	 */
    	'handleValidateSuccess': function (element, name, value) {
    		var elements = this.validation.errors;
    		
    		if (!elements[name]) {
    			// Make sure we will remove element which was already there
    			// when page was loaded
    			elements[name] = this.validateErrorElementFind(element, name);
    		}
    		if (elements[name]) {
    			elements[name].remove();
    			delete(elements[name]);
    		}
    		
    		if (this.validation.inputErrorClassName) {
    			element.removeClass(this.validation.inputErrorClassName);
    		}
    		
    		this.getInputContainerElement(element).removeClass(this.validation.errorClassName);
    	},
    	
    	/**
    	 * Show error message
    	 * 
    	 * @param {String} message Message
    	 * @param {Object} element Input element
    	 * @param {String} name Input name
    	 * @param {String} value Input value
    	 * @private
    	 */
    	'handleValidateError': function (message, element, name, value) {
    		var elements = this.validation.errors;
    		
    		if (!elements[name] || !$.contains(document, elements[name].get(0))) {
    			elements[name] = this.validateErrorElementFind(element, name);
    		}
    		
    		if (elements[name] && $.contains(document, elements[name].get(0))) {
    			elements[name].text(message);
    		} else {
    			elements[name] = this.validateErrorRender(message, element, name, value);
    			this.validateErrorPlacement(elements[name], element, name, value);
    		}
    		
    		if (this.validation.inputErrorClassName) {
    			element.addClass(this.validation.inputErrorClassName);
    		}
    			
    		this.getInputContainerElement(element).addClass(this.validation.errorClassName);
    	},
    	
    	/**
    	 * Returns input container from input element
    	 * 
    	 * @param {Object} element Input element
    	 * @return Input container element
    	 * @type {Object}
    	 * @private
    	 */
    	'getInputContainerElement': function (element) {
    		var container = this.validation.inputContainer;
    		if (container) {
    			container = element.closest(container);
    			if (!container.length) {
    				container = element.parent();
    			}
    		} else {
    			container = element.parent();
    		}
    		
    		return container;
    	},
    	
    	/**
    	 * Returns input error container from input element
    	 * 
    	 * @param {Object} element Input element
    	 * @return Error container element
    	 * @type {Object}
    	 * @private
    	 */
    	'getErrorContainerElement': function (element) {
    		return this.getInputContainerElement(element);
    	},

    	/**
    	 * Returns true if there are errors, otherwise false
    	 * 
    	 * @returns {Boolean} True if there are errors
    	 */
    	'hasErrors': function () {
    		var errors = this.validation.errors,
    			key = null;
    		
    		for (key in errors) {
    			if (errors[key]) return true;
    		}
    		
    		return false;
    	},
    	
    	
    	/* ------------------------ Helpers ---------------------- */
    	
    	/**
    	 * Returns input by name or ID
    	 * 
    	 * @param {String} name Input name or ID
    	 * @returns {Object} Input element
    	 */
    	'input': function (name) {
    		var instance = this.get(name),
    			regex,
    			id,
    			elements;
    		
    		if (instance) return instance;
    		
    		if (name[0] === '/') {
    			// Regular expression
    			regex = new RegExp(name.replace(/(^\/|\/$)/g, ''), 'i');
    			elements = this.element.find('[name]');
    				
    			elements = elements.filter(function () {
    				return !!$(this).attr('name').match(regex);
    			});
    			
    			if (elements.length) {
    				return elements;
    			}
    		}
    		
    		id = String(name).replace(/[^a-z0-9_\-]/gi, '');
    		elements = this.element.find('#' + id + ', [name="' + name + '"]').filter('input, textarea, select');
    		
    		return elements.eq(0);
    	},
    	
    	/**
    	 * Returns input validation name
    	 * 
    	 * @param {Object} input Input element
    	 * @returns {String} Input name or ID
    	 */
    	'name': function (input) {
    		var input_name = input.attr('name'),
    			rules = this.validation.rules,
    			name;
    		
    		if (input_name in rules) {
    			return input_name;
    		} else {
    			for (name in rules) {
    				if (name[0] === '/') {
    					//Regular expression
    					var regex = new RegExp(name.replace(/(^\/|\/$)/g, ''), 'i');
    					
    					if (input_name.match(regex)) {
    						return name;
    					}
    				}
    			}
    		}
    		
    		// Didn't found matching rule
    		return input_name;
    	},
    	
    	/**
    	 * Returns form
    	 * 
    	 * @return Form element
    	 * @type {Object}
    	 */
    	'getForm': function () {
    		if (this.element.is('form')) {
    			return this.element;
    		} else {
    			return this.element.find('form');
    		}
    	},
    	
    	/**
    	 * Returns all form values
    	 * If form contains inputs with [] in their name, then they
    	 * are converted into [0], [1], etc.
    	 *  
    	 * @return All form values
    	 * @type {Object}
    	 */
    	'serialize': function () {
    		var arr	= this.getForm().serializeArray(),
    			i	= 0,
    			ii	= arr.length,
    			obj	= {},
    			name,
    			arrayForm,
    			offset;

    		for(; i<ii; i++) {
    			// Rather limited recognition for now
    			// we correct only if [] are last symbols
    			name = arr[i].name;
    			
    			arrayForm = name.match(/^(.*)\[(.*)\]$/);
    			
    			if (arrayForm) {
    				name = arrayForm[1];
    				offset = arrayForm[2];
    				
    				if (!(name in obj)) {
    					if (offset === '') {
    						obj[name] = [];
    					} else {
    						obj[name] = {};
    					}
    				}

    				if (offset === '') {
    					obj[name].push(arr[i].value);
    				} else {
    					obj[name][offset] = arr[i].value;
    				}
    			} else {
    				obj[name] = arr[i].value;
    			}
    		}
    		
    		return this.serializeStringify(obj);
    	},
    	
    	/**
    	 * Convert deep object into one-dimensional
    	 * 
    	 * @param {Object|Array} obj Object
    	 * @returns {Object} Result
    	 */
    	'serializeStringify': function (obj, out, prefix) {
    		out = out || {};
    		prefix = prefix || '';
    		
    		var key;
    		
    		for (key in obj) {
    			if (obj.hasOwnProperty(key)) {
    				
    				if ($.isPlainObject(obj[key]) || $.isArray(obj[key])) {
    					this.serializeStringify(obj[key], out, prefix ? prefix + '[' + key + ']' : String(key));
    				} else {
    					out[prefix ? prefix + '[' + key + ']' : String(key)] = obj[key];
    				}
    				
    			}
    		}
    		
    		return out;
    	},
    	
    	/**
    	 * Reset form values and errors
    	 */
    	'resetForm': function () {
    		var form = this.getForm(),
    			input,
    			elements = this.validation.errors,
    			name,
    			fn;
    		
    		form.get(0).reset();
    		
    		fn = $.proxy(function (index, element) {
    			var input = $(element),
    				name  = input.attr('name');
    			
    			this.handleValidateSuccess(input, name, input.val());
    		}, this);
    		
    		for (name in elements) {
    			input = this.input(name);
    			
    			input.each(fn);
    		}
    	},
    	
    	/**
    	 * Enable / disable form
    	 * 
    	 * @param {Boolean} disabled If true all form inputs will be disabled, otherwise enabled
    	 */
    	'disable': function (disabled) {
    		var inputs = this.element.find('input,select,textarea,button');
    		if (disabled) {
    			inputs.attr('readonly', 'readonly');
    			this.element.addClass('disabled');
    			this.disabled = true;
    		} else {
    			inputs.removeAttr('readonly');
    			this.element.removeClass('disabled');
    			this.disabled = false;
    		}
    	}
    	
    });


    /*
     * Observe mouse events so that callback is called after mouse event is complete
     */
    $.app.AjaxForm.delay = {
    	
    	/**
    	 * List of callbacks, contexts, arguments
    	 * @type {Array}
    	 * @private
    	 */
    	'queue': null,
    	
    	/**
    	 * Mouse event is active or not
    	 * @type {Boolean}
    	 * @private
    	 */
    	'mouseEvent': false,
    	
    	/**
    	 * Utility has been initialized or not
    	 * @type {Boolean}
    	 * @private
    	 */
    	'initialized': false,
    	
    	/**
    	 * Register a function for delayed call, returns a new function which
    	 * should be used
    	 * 
    	 * @param {Function} callback Function which to delay
    	 * @param {Object} context Optional, callback function context
    	 */
    	'register': function (callback, context) {
    		if (!this.initialized) {
    			this.init();
    		}
    		
    		return $.proxy(function () {
    			var args = [],
    				i = 0,
    				ii = arguments.length;
    			
    			for (; i<ii; i++) {
    				args[i] = arguments[i];
    			}
    			
    			if (this.mouseEvent) {
    				// Queue to be called after mouse event
    				this.queue.push([callback, context || this, args]);
    			} else {
    				callback.apply(context || this, args);
    			}
    		}, this);
    	},
    	
    	/**
    	 * Handle mouse event
    	 * 
    	 * @private
    	 */
    	'handleActivation': function () {
    		this.mouseEvent = true;
    	},
    	
    	/**
    	 * Handle mouse event
    	 * 
    	 * @private
    	 */
    	'handleDectivation': function () {
    		this.mouseEvent = false;
    		
    		var queue = this.queue,
    			i = 0,
    			ii = queue.length;
    		
    		this.queue = [];
    		
    		for (; i<ii; i++) {
    			queue[i][0].apply(queue[i][1], queue[i][2]);
    		}
    	},
    	
    	/**
    	 * @constructor
    	 */
    	'init': function () {
    		this.queue = [];
    		this.initialized = true;
    		$(document).on('mousedown', $.proxy(this.handleActivation, this));
    		$(document).on('mouseup', $.proxy(this.handleDectivation, this));
    	}
    	
    };

	
	
	return $.app.AjaxForm;
	
}));
