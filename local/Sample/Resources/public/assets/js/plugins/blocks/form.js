/**
 * Form block
 * @version 1.0.2
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh', 'plugins/helpers/responsive', 'app/ajaxform'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var DATA_INSTANCE_PROPERTY = 'form';
	
	
	var FormHandler = function (element) {
		var form = this.form = $(element);
		
		this.handleSubmit = $.proxy(this.handleSubmit, this);
		this.hideTooltip = $.proxy(this.hideTooltip, this);
		
		form.find('[required]').removeAttr('required').attr('data-required', '');
		form.on('submit', this.handleSubmit);
		
		// Extend $.app.AjaxContent instance for custom functionality
		var block_id = form.closest('[data-attach^="$.app."]').data('id');
		if (block_id) {
			$.app.inject(block_id, {
				// Validation overwrites
				'validation': {
					'inputContainer': '.row'
				},
			});
		}
	};
	FormHandler.prototype = {
		'form': null,
		'tooltip': null,
		'last_input': null,
		
		'handleSubmit': function () {
			
			var inputs = this.form.find('[data-required]'),
				input  = null,
				i      = 0,
				ii     = inputs.length;
			
			for (; i<ii; i++) {
				input = inputs.eq(i);
				
				if (!input.val()) {
					this.showTooltip(input);
					input.one('input change', this.hideTooltip);
					input.focus();
					
					return false;
				}
			}
			
		},
		
		'showTooltip': function (input) {
			var tooltip = this.tooltip,
				message = input.data('error-message') || input.closest('form').data('error-message');
			
			if (!message) {
				return;
			}
			if (!tooltip) {
				this.tooltip = tooltip = $('<div class="tooltip error visible align-bottom"></div>');
			}
			
			tooltip.html(message);
			
			tooltip.css({'top': 'auto', 'left': 'auto', 'margin': '7px 0 0 10px'});
			tooltip.removeClass('hidden').insertAfter(input);
			
			input.closest('.row').addClass('error');
			input.addClass('input-error');
			
			this.last_input = input;
		},
		
		'hideTooltip': function () {
			this.tooltip.addClass('hidden');
			
			if (this.last_input) {
				this.last_input.closest('.row').removeClass('error');
				this.last_input.removeClass('input-error');
				this.last_input = null;
			}
		},
		
		'destroy': function () {
			this.form.on('submit', this.handleSubmit);
			
			if (this.tooltip) {
				this.hideTooltip();
				this.tooltip.remove();
			}
			
			this.tooltip = null;
			this.form = null;
			this.last_input = null;
		}
	};
	
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.form = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof FormHandler.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new FormHandler (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};
	
	//$.refresh implementation
	$.refresh.on('refresh/form', function (event, info) {
		info.target.form(info.target.data());
	});
	
	$.refresh.on('cleanup/form', function (event, info) {
		var form = info.target.data(DATA_INSTANCE_PROPERTY);
		if (form) {
			form.destroy();
			info.target.data(DATA_INSTANCE_PROPERTY, null)
		}
	});
	
	return FormHandler;
	
}));