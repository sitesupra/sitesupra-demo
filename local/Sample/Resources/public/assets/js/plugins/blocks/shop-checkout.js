/**
 * Shop checkout block
 * @version 1.0.0
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'app/app', 'plugins/helpers/throttle', 'plugins/helpers/input-mask'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var block_id = $('.shop-order-cart, .shop-order-checkout').closest('[data-attach^="$.app."]').data('id');
	
	// Extend $.app.AjaxContent instance for custom functionality
	$.app.inject(block_id, {
		
		// Invoice CSS selector
		'SELECTOR_INVOICE': '.order-items-invoice',
		
		// Billing form CSS selector
		'SELECTOR_BILLING': '.billing-form',
		
		// Shipping form CSS selector
		'SELECTOR_SHIPPING': '.shipping-form',
		
		// Payment form CSS selector
		'SELECTOR_PAYMENT': '.payment-form',
		
		// Validation overwrites
		'validation': {
			'inputContainer': '.row'
		},
		
		'afterReload': function () {
			$.app.AjaxContent.prototype.afterReload.apply(this, arguments);
			this.refreshCheckout();
		},
		
		'initCheckout': function () {
			this.onQuantityChangeDelayed = $.throttle(this.onQuantityChange, this, 250, true);
			this.reloadFormDelayed = $.throttle(this.reloadForm, this, 16, true);
			
			this.addValidation('invalidOption', $.proxy(function (value, input, name) {
				input = this.getInputContainerElement(input).find('input[value="' + value + '"]');
				
				if (input.data('invalidOption')) {
					return false;
				} else {
					return true;
				}
			}, this));
			
			this.refreshCheckout();
		},
		
		'refreshCheckout': function () {
			var order_form = this.find(this.SELECTOR_INVOICE),
				billing_form = this.find(this.SELECTOR_BILLING),
				shipping_form = this.find(this.SELECTOR_SHIPPING),
				payment_form = this.find(this.SELECTOR_PAYMENT);
			
			order_form.find('input[type="text"]').on('change', this.onQuantityChangeDelayed);
			order_form.find('input[name^="quantity"]').valueMask(/^[0-9]*$/);
			
			order_form.find('.button-remove').on('click', this.proxy(this.onRemoveButtonClick));
			
			// Billing form
				billing_form.on('submit', {'selector': this.SELECTOR_BILLING}, this.proxy(this.submitForm));
				
				// When country or state changes reload to make sure
				billing_form.find('#field_billing_country, #field_billing_state, #field_billing_zip').on('change', {'selector': this.SELECTOR_BILLING}, this.reloadFormDelayed);
			
			// Shipping form
				shipping_form.on('submit', {'selector': this.SELECTOR_SHIPPING}, this.proxy(this.submitForm));
				
				// When country or state changes reload to make sure
				shipping_form.find('#field_shipping_country, #field_shipping_state, #field_shipping_zip').on('change', {'selector': this.SELECTOR_SHIPPING}, this.reloadFormDelayed);
				
				// When shipping method changes reload to update data
				shipping_form.find('[name="shippingInfo[shippingOption]"]').on('change', {'selector': this.SELECTOR_SHIPPING}, this.reloadFormDelayed);
				
				// When "Same as" checkbox is checked populate shipping form
				shipping_form.find('#field_shipping_address_same_as_billing').on('click change', this.proxy(this.copyBillingFormOntoShippingForm));
			
			// Payment form
				payment_form.on('submit', {'selector': this.SELECTOR_PAYMENT}, this.proxy(this.submitForm));
		},
		
		// Copy billing form values onto shipping form if checkbox is checked
		'copyBillingFormOntoShippingForm': function () {
			if (this.find('#field_shipping_address_same_as_billing').prop('checked')) {
				var billing_form = this.find(this.SELECTOR_BILLING),
					shipping_form = this.find(this.SELECTOR_SHIPPING),
					values = billing_form.serializeArray(),
					i = 0,
					ii = values.length,
					input, name,
					selects = $();
				
				for (; i<ii; i++) {
					name = values[i].name.match(/([a-z0-9\-_]+)(.*)$/i);
					if (name) {
						input = shipping_form.find('[name$="' + name[2] + '"]');
						if (input.size()) {
							input.val(values[i].value);
							
							if (input.is('select')) {
								selects = selects.add(input);
							}
						}
					}
				}
				
				selects.change();
			}
		},
		
		//
		'onRemoveButtonClick': function (e) {
			var button = $(e.target).closest('.button-remove'),
				id     = button.attr('name'),
				data   = {};
			
			data[id] = '';
			this.post(this.SELECTOR_INVOICE, data);
			
			return false;
		},
		
		// When quantity changes reload
		'onQuantityChange': function () {
			this.post(this.SELECTOR_INVOICE, {'updateOrderItems': '1', });
		},
		
		// Default submit handler we don't need, becasue we overwrite it
		// with custom functionality
		'submit': function () {
		},
		
		// Submit form
		'submitForm': function (e) {
			if (!e.isDefaultPrevented()) {
				// If widgets is AjaxContent instead of AjaxForm, then
				// we need to manually post data
				this.post(e.data.selector);
				return false;
			}
		},
		
		// Reload form
		'reloadForm': function (e) {
			var selector = e && e.data ? e.data.selector : null,
				form     = null;
			
			if (!selector) {
				form = $(e.target).closest('form');
			}
			
			this.post(selector || form, {'validate': false});
			return false;
		},
		
		// Send data in post and reload content
		'post': function (selector, params) {
			var form = typeof selector === 'string' ? $(this.find(selector)) : selector,
				post_data = form.closest('form').serialize(),
				key  = null;
			
			if (params) {
				for (key in params) {
					post_data += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
				}
			}
			
			this.method = 'post';
			
			this.showLoadingIcon(selector);
			this.reload(post_data);
		},
		
		// Show loading icon
		'showLoadingIcon': function (selector) {
			var form = this.find(selector);
			form.append('<div class="loading-icon"></div>');
		}
		
	}, function () {
		this.initCheckout();
	});
	
}));