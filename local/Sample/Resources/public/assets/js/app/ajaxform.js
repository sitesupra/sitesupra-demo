/*
 * @version 1.0.4
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

	var _super = $.app.AjaxContent.prototype;
	
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
		 * @type {Callback}
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
			this.submitEvent = this.proxy(this.submit);
			this.disabled = false;
			
			this.onChange();
			this.element.delegate('input, select, textarea', 'blur', this.proxy(this.validateEventTarget));
			this.element.delegate('input[type="checkbox"], input[type="radio"]', 'click', this.proxy(this.validateEventTarget));
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
				post_data = this.serialize(true);
				
				this.disable(true);
				this.reload(post_data);
			}
			
			return false;
		},
		
		/**
		 * On reload bind form submit action
		 *
		 * @private
		 */
		'onReload': function (html) {
			_super.onReload.apply(this, arguments);
			
			if (!html) {
				$('.tooltip-popup').hide();
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
		 * @param {Object} value Form values
		 * @return True on success, false on failure
		 */
		'validate': function (values) {
			var validation	= this.getValidationInfo(),
				rules		= null,
				rule		= null,
				messages	= validation.messages,
				input		= null,
				name		= null,
				value		= null,
				error		= false,
				any_errors	= false;
			
			for(name in validation.rules) {
				input = this.input(name);
				
				if (input && !input.is(':disabled')) {
					
					rules = validation.rules[name];
					
					if (!this.validateRules(rules, messages, values, input, name)) {
						if (!any_errors) {
							any_errors = true;
							input.focus();
						}
					} else {
						//All rules for this input were satisfied
						this.handleValidateSuccess(input, name, value);
					}
				}
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
		 * @return True on success, false on failure
		 * @private
		 */
		'validateRules': function (rules, messages, values, input, name) {
			var rule	= null,
				error	= false,
				value	= $.trim(values[name]),
				custom  = this.validation.customValidation;
			
			for(rule in rules) {
				if (rules[rule]) {
					
					if (rule === 'required') {
						if (!value) error = true;
					} else if (rule === 'minlength') {
						if (value.length < rules[rule]) error = true;
					} else if (rule === 'maxlength') {
						if (value.length > rules[rule]) error = true;
					} else if (rule === 'email') {
						if (!value.match(/^[a-z0-9\.\-\_]+@[a-z0-9\.\-\_]+\.[a-z0-9]{2,5}$/i)) error = true;
					} else if (rule === 'phone') {
						if (!value.match(/^[0-9\s\+\(\)]+$/)) error = true;
					} else if (rule === 'equalto') {
						if ($.trim(values[rules[rule]]) != value) error = true;
					} else if (typeof rules[rule] === 'function') {
						if (!rules[rule](value, input, name)) error = true;
					} else if (rule in custom) {
						if (!custom[rule](value, input, name)) error = true;
					}
					
					if (error) {
						this.handleValidateError(messages[name][rule], input, name, value);
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
				value		= $(e.target).val(),
				name		= $(e.target).attr('name'),
				input		= this.input(name),
				rules		= null,
				values		= {};
			
			rules = validation.rules[name];
			values[name] = value;
			
			if (rules && input && !input.is(':disabled'))	{
				if (this.validateRules(rules, validation.messages, values, input, name)) {
					//All rules for this input were satisfied
					this.handleValidateSuccess(input, name, value);
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
			inputs.each(function () {
				var input			= $(this),
					attr_validate	= input.data('validate'),
					attr_messages	= input.data('messages'),
					name			= input.attr('name');
				
				if (attr_validate) {
					if (!validation.rules[name]) validation.rules[name] = {};
					$.extend(validation.rules[name], attr_validate);
				}
				if (attr_messages) {
					if (!validation.messages[name]) validation.messages[name] = {};
					$.extend(validation.messages[name], attr_messages);
				}
			});
			
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
			var container = this.getInputContainerElement(element);
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
			
			return $('<' + element + ' class="' + classname + '" />').text(message);
		},
		
		/**
		 * Find error element
		 * 
		 * @param {Object} element Input element
		 * @returns {Object|Null} Error element
		 * @private
		 */
		'validateErrorElementFind': function (element) {
			var label = this.getInputContainerElement(element).find(this.validation.errorElement);
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
				elements[name] = this.validateErrorElementFind(element);
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
				elements[name] = this.validateErrorElementFind(element);
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
		
		/* ------------------------ Helpers ---------------------- */
		
		/**
		 * Returns input by name or ID
		 * 
		 * @param {String} name Input name or ID
		 * @return Input element
		 * @type {Object}
		 */
		'input': function (name) {
			var instance = this.get(name);
			if (instance) return instance;
			
			var id = String(name).replace(/[^a-z0-9_\-]/gi, ''),
				elements = this.element.find('#' + id + ', [name="' + name + '"]').filter('input, textarea, select');
			
			return elements.eq(0);
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
	
	return $.app.AjaxForm;
	
}));