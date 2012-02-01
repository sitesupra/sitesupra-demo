function onFeedbackRender () {
	
	var form = $('#feedbackForm'),
		success = $('#feedbackFormSuccess');

	new $.AjaxForm(form, {
		'successMessage': success,
		'errorMessages': ErrorMessages
	});
		
	// hide error labels
	form.find('input[type="submit"]').click(function () {
		form.find('.error-message').each(function() {
			$(this).hide();
	
		});
	});
		
	//On Close button click close lightbox
	//should do nothing, if form isn't opened as lightbox
	success.find('input[type="button"]').click(function () {
		if (typeof $.Lightbox == 'object') {
			$.Lightbox.hide();
		}
	});
}

function onFeedbackReset () {
	var form = $('#feedbackForm'),
		success = $('#feedbackFormSuccess');

	//Show form and hide message
	form.removeClass('hidden');
	success.addClass('hidden');

	//Reset values
	form.data('form').reset();
}

var AjaxForm = $.AjaxForm = function (container, options) {
		this.options = $.extend({
			'validation': null,
			'successMessage': null,
			'errorMessages': null,
			'submitUsingAjax': true
		}, options || {});
		
		this.container = container;
		this.form = container.filter('form').size() ? container.filter('form') : container.find('form');
		this.form.data('form', this);
		
		this.init();
	};
	AjaxForm.prototype = {
		/**
		 * Form options/settings
		 * @type {Object}
		 * @private
		 */
		'options': null,
		
		/**
		 * Container element, jQuery
		 * @type {Object}
		 * @private
		 */
		'container': null,
		
		/**
		 * Form element, jQuery
		 * @type {Object}
		 * @private
		 */
		'form': null,
		
		/**
		 * Activity timer
		 * @type {Number}
		 * @private
		 */
		'activity_timer': null,
		
		/**
		 * Activity input
		 * @type {Object}
		 * @private
		 */
		'activity_input': null,
		
		/**
		 * Validation is enabled
		 * @type {Boolean}
		 * @private
		 */
		'validation_enabled': true,
		
		/**
		 * Validate input value
		 * 
		 * @param {String} name Input field name
		 * @param {String} value Input field value
		 * @return Error message or false if there is no error
		 */
		'validateInput': function (name, value, silent) {
			if (!this.validation_enabled) return false;
			
			var rules = this.options.validation,
				fn = [],
				i = null,
				ii = null;
			
			if (rules && rules[name]) {
				fn = typeof rules[name] == 'object' ? rules[name] : [rules[name]];
				for(i = 0, ii = fn.length; i<ii; i++) {
					var error = fn[i](name, value);
					return this.setErrorMessage(name, error, silent);
				}
			}
			
			return false;
		},
		
		/**
		 * Set error message
		 * 
		 * @param {String} name Input name
		 * @param {String} error Error message
		 * @param {Boolean} silent
		 * @private
		 */
		'setErrorMessage': function (name, error, silent) {
			if (!this.validation_enabled) return false;
			
			var row = this.form.find('[name="' + name + '"]').closest('.input-row'),
				message = row.next('.error-message');
			
			if (error) {
				if (!message.length && error !== true) {
					message = $('<div class="error-message"></div>');
					row.after(message);
				}
				
				row.addClass('error');
				if (error !== true) {
					message.show().text(error);
				} else {
					row.addClass('error-label');
				}
				if (!silent) {
					this.afterValidation();
				}
				
				if (this.options.afterValidation) {
					this.options.afterValidation();
				}
				
				return error;
			} else {
				//Delay hiding message to make sure mouse click hits its target
				setTimeout($.proxy(function () {
					row.removeClass('error').removeClass('error-label');
					message.remove();
					
					if (!silent) {
						this.afterValidation();
					}
					
					if (this.options.afterValidation) {
						this.options.afterValidation();
					}
					
				}, this), 250);
				
				return false;
			}
		},
		
		/**
		 * Validate form
		 * 
		 * @return True on success, false on failure
		 */
		'validate': function (values) {	
			if (!this.validation_enabled) return true;
			var success = true;
			
			for(var i in values) {
				if (this.validateInput(i, values[i], true) !== false) {
					success = false;
				}
			}
			
			this.afterValidation();
			
			return success;
		},
		
		/**
		 * Validate input after certain time of inactivity
		 */
		'activityValidate': function () {
			this.activity_timer = null;
			if (this.activity_input) {
				var name = this.activity_input.attr('name'),
					value = this.activity_input.val();
				
				this.validateInput(name, value);
			}
		},
		
		/**
		 * After validation update lightbox position
		 * 
		 * @private
		 */
		'afterValidation': function () {
			var lightbox = this.form.closest('.lightbox').data('lightbox');
			if (lightbox) {
				lightbox.syncPosition();
			}
		},
		
		/**
		 * Handle form submit
		 * 
		 * @private
		 */
		'submit': function () {
			var values = this.serialize();
			var validation = this.validate(values);
			
			if (validation !== true) {
				//Validation failed
				return false;
			}
			
			//Reset timer
			if (this.activity_timer) {
				clearTimeout(this.activity_timer);
				this.activity_timer = null;
			}
			
			if (this.options.submitUsingAjax) {
				//All inputs must be disabled to prevent repeated submit
				this.setDisabled(true);
				
				var uri = this.form.attr('action'),
					method = this.form.attr('method') || 'post';
				
				$.ajax(uri, {
					'data': values,
					'dataType': 'json',
					'type': method
				})
				.done($.proxy(this.handleSubmitResponse, this));
				
				return false;
			}
		},
		
		/**
		 * Handle server response
		 */
		'handleSubmitResponse': function (response) {
			if (typeof response == 'object') {
				if (response.success === true) {
					if (this.options.successMessage) {
						this.form.addClass('hidden');
						this.options.successMessage.removeClass('hidden');
					} else {
						this.hide();
					}
				} else {

					var error_messages = this.options.errorMessages || {},
						msg = '';

					//Handle errors
					for(var name in response.errors) {
						msg = error_messages[name][response.errors[name]];
						this.setErrorMessage(name, msg || true, true);
					}

					//After validation update lightbox position if there is one
					this.afterValidation();
				}
			}
			this.setDisabled(false);
		},
		
		/**
		 * Get all form values
		 * 
		 * @private
		 */
		'serialize': function () {
			var values = {};
			
			$.each(this.form.serializeArray(), function (item) {
				values[this.name] = this.value;
			});
			
			return values;
		},
		
		/**
		 * Enable / disable form
		 * 
		 * @param {Boolean} disabled If true form inputs will be disabled, otherwise enabled
		 */
		'setDisabled': function (disabled) {
			var inputs = this.form.find('input,select,textarea,button');
			if (disabled) {
				inputs.attr('disabled', 'disabled');
			} else {
				inputs.removeAttr('disabled');
			}
		},
		
		/**
		 * Reset form
		 */
		'reset': function () {
			var form = this.form;
			
			//Remove errors
			form.find('.input-row.error').removeClass('error').removeClass('error-label');
			form.find('.error-message').remove();
			
			//Captcha
			this.resetCaptcha();
			
			//Reset all input values (except for buttons)
			this.validation_enabled = false;
			
			form.find('input,textarea').not('[type="submit"],[type="button"]').val('');
			
			form.find('select').each(function () {
				$(this).val($(this).find('option').eq(0).val()).change();
			});
			
			this.validation_enabled = true;
			
			//Timer
			this.activity_input = null;
			if (this.activity_timer) {
				clearTimeout(this.activity_timer);
				this.activity_timer = null;
			}
		},
		
		/**
		 * Reset captcha image
		 */
		'resetCaptcha': function () {
			var img = this.form.find('.input-row-captcha img'),
				src = img.attr('src'),
				rand = +new Date();
			
			img.attr('src', src.indexOf('rand=') != -1 ? src.replace(/rand=[0-9]+/, 'rand=' + rand) : src + '?rand='  + rand);
			
			return false;
		},
		
		/**
		 * Initialize form
		 */
		'init': function () {
			this.form.submit($.proxy(this.submit, this));
			this.activityValidate = $.proxy(this.activityValidate, this);
			
			var rules = this.options.validation,
				name = null,
				input = null;
			
			for(name in rules) {
				input = this.form.find('[name="' + name + '"]');
				
				if (input.is('select,input[type="radio"],input[type="checkbox"]')) {
					input.bind('click change', $.proxy(function (e) {
						this.validateInput($(e.target).attr('name'), $(e.target).val());
						clearTimeout(this.activity_timer);
					}, this));
				} else {
					input.blur($.proxy(function (e) {
						this.validateInput($(e.target).attr('name'), $(e.target).val());
					}, this));
					input.focus($.proxy(function (e) {
						this.activity_input = $(e.target);
					}, this));
					input.keyup($.proxy(function (e) {
						if (this.activity_timer) clearTimeout(this.activity_timer);
						this.activity_timer = setTimeout(this.activityValidate, 6000);
					}, this));
				}
			}
			
			//Captcha
			this.form.find('.input-row-captcha a').click($.proxy(this.resetCaptcha, this));
		}
	};


$(document).ready(onFeedbackRender());